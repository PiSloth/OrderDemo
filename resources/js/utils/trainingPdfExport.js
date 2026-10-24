import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { applyPyidaungsuFont } from './pdfFont';

/**
 * Generates and downloads a structured PDF report of all active users
 * grouped by Department -> then grouped by Office Position -> showing User Name & Applicable Training Catalogs.
 *
 * @param {Object} options
 * @param {Array} options.activeUsers List of active users with department, officePosition, and trainingAssignments
 * @param {Array} options.allTrainings List of active trainings with scopes and category
 * @param {Array} options.departments List of departments
 * @param {Array} options.officePositions List of office positions
 */
export async function exportTrainingMatrixPdf({
  activeUsers = [],
  allTrainings = [],
  departments = [],
  officePositions = []
}) {
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  const fontName = await applyPyidaungsuFont(doc);

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 14;
  let currentY = 16;

  // Header Colors
  const primaryColor = [14, 116, 144]; // Sky-700
  const secondaryColor = [71, 85, 105]; // Slate-600
  const lightBg = [240, 249, 255]; // Sky-50

  // 1. Report Title & Header
  try {
    doc.setFont(fontName, 'bold');
  } catch (e) {
    doc.setFont('helvetica', 'bold');
  }
  doc.setFontSize(16);
  doc.setTextColor(...primaryColor);
  doc.text('Employee Training & Compliance Matrix Report', margin, currentY);

  currentY += 6;
  try {
    doc.setFont(fontName, 'normal');
  } catch (e) {
    doc.setFont('helvetica', 'normal');
  }
  doc.setFontSize(9);
  doc.setTextColor(...secondaryColor);

  const nowStr = new Date().toLocaleString();
  doc.text(`Generated on: ${nowStr} • Training Master Management System`, margin, currentY);

  currentY += 8;

  // 2. Summary KPI Box
  const totalEmployees = activeUsers.length;
  const totalTrainingsCount = allTrainings.length;
  const totalDeptsCount = departments.length;

  doc.setFillColor(...lightBg);
  doc.setDrawColor(186, 230, 253);
  doc.roundedRect(margin, currentY, pageWidth - margin * 2, 14, 2, 2, 'FD');

  try {
    doc.setFont(fontName, 'bold');
  } catch (e) {
    doc.setFont('helvetica', 'bold');
  }
  doc.setFontSize(9);
  doc.setTextColor(3, 105, 161);

  const kpiText = `Active Workforce: ${totalEmployees} Employee(s)   |   Departments: ${totalDeptsCount}   |   Active Training Catalogs: ${totalTrainingsCount}`;
  doc.text(kpiText, margin + 4, currentY + 9);

  currentY += 20;

  // 3. Build Department -> Office Position -> Users Tree
  // Helper to find applicable trainings for a user's (department_id, office_position_id)
  const getApplicableTrainings = (user) => {
    const userDeptId = user.department_id;
    const userPosId = user.office_position_id;

    // Filter trainings matching this scope
    const scopedTrainings = allTrainings.filter((t) => {
      if (!t.scopes || t.scopes.length === 0) return false;
      return t.scopes.some((sc) => {
        const matchesDept = !sc.department_id || sc.department_id == userDeptId;
        const matchesPos = !sc.office_position_id || (userPosId && sc.office_position_id == userPosId);
        return matchesDept && matchesPos;
      });
    });

    // Also include any explicitly assigned trainings
    const assignments = user.training_assignments || user.trainingAssignments || [];
    const assignedIds = new Set(assignments.map((a) => a.training_id || a.training?.id));
    const assignedTrainings = allTrainings.filter((t) => assignedIds.has(t.id));

    // Combine unique
    const combined = [...scopedTrainings];
    assignedTrainings.forEach((t) => {
      if (!combined.some((item) => item.id === t.id)) {
        combined.push(t);
      }
    });

    return combined;
  };

  // Group Users by Department
  const deptGroups = {};
  activeUsers.forEach((user) => {
    const deptKey = user.department?.name || 'General / Unassigned Department';
    if (!deptGroups[deptKey]) {
      deptGroups[deptKey] = {};
    }

    const posKey = user.office_position?.name || user.officePosition?.name || 'General Staff / Unassigned Position';
    if (!deptGroups[deptKey][posKey]) {
      deptGroups[deptKey][posKey] = [];
    }

    deptGroups[deptKey][posKey].push(user);
  });

  // Table Body Rows Construction
  const tableRows = [];

  const deptNames = Object.keys(deptGroups).sort();
  if (deptNames.length === 0) {
    tableRows.push([
      {
        content: 'No active workforce employees found.',
        colSpan: 4,
        styles: { halign: 'center', fontSize: 9, textColor: [100, 116, 139] },
      },
    ]);
  } else {
    deptNames.forEach((deptName) => {
      const posGroups = deptGroups[deptName];
      let deptTotalEmployees = 0;
      Object.values(posGroups).forEach((uList) => (deptTotalEmployees += uList.length));

      // Department Header Row (Spans all columns)
      tableRows.push([
        {
          content: `DEPARTMENT: ${deptName.toUpperCase()} (${deptTotalEmployees} Employee${deptTotalEmployees > 1 ? 's' : ''})`,
          colSpan: 4,
          styles: {
            fillColor: [15, 23, 42], // Slate-900
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 9.5,
            halign: 'left',
          },
        },
      ]);

      Object.keys(posGroups).sort().forEach((posName) => {
        const usersInPos = posGroups[posName];

        // Office Position Subheader Row
        tableRows.push([
          {
            content: `  Position: ${posName} (${usersInPos.length} Employee${usersInPos.length > 1 ? 's' : ''})`,
            colSpan: 4,
            styles: {
              fillColor: [224, 242, 254], // Sky-100
              textColor: [3, 105, 161], // Sky-700
              fontStyle: 'bold',
              fontSize: 8.5,
              halign: 'left',
            },
          },
        ]);

        // Employee Rows
        usersInPos.forEach((user, uIdx) => {
          const applicableTrainings = getApplicableTrainings(user);

          let trainingsText = '— No matching training scopes';
          if (applicableTrainings.length > 0) {
            trainingsText = applicableTrainings
              .map((t, idx) => `${idx + 1}. [${t.code}] ${t.title} (${t.passing_score}% pass • ${t.retrain_interval} ${t.retrain_unit})`)
              .join('\n');
          }

          tableRows.push([
            `${uIdx + 1}`,
            `${user.name}\n(${user.email})`,
            posName,
            trainingsText,
          ]);
        });
      });
    });
  }

  // 4. Render Table with AutoTable
  const autoTableConfig = {
    startY: currentY,
    head: [
      [
        '#',
        'Active Employee Name & Email',
        'Office Position',
        'Belonging / In-Scope Training Catalog(s)',
      ],
    ],
    body: tableRows,
    theme: 'grid',
    styles: {
      font: fontName,
      fontSize: 8,
      cellPadding: 3.5,
      valign: 'middle',
      lineColor: [226, 232, 240],
      lineWidth: 0.2,
    },
    headStyles: {
      fillColor: [14, 116, 144], // Sky-700
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      fontSize: 8.5,
      halign: 'center',
    },
    columnStyles: {
      0: { cellWidth: 10, halign: 'center' },
      1: { cellWidth: 50, fontStyle: 'bold' },
      2: { cellWidth: 35 },
      3: { cellWidth: 'auto', fontStyle: 'normal' },
    },
    margin: { left: margin, right: margin, bottom: 18 },
  };

  if (typeof autoTable === 'function') {
    autoTable(doc, autoTableConfig);
  } else if (typeof doc.autoTable === 'function') {
    doc.autoTable(autoTableConfig);
  }

  // Page Numbers Footer
  const totalPages = typeof doc.getNumberOfPages === 'function'
    ? doc.getNumberOfPages()
    : (doc.internal?.getNumberOfPages ? doc.internal.getNumberOfPages() : 1);

  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    try {
      doc.setFont(fontName, 'normal');
    } catch (e) {
      doc.setFont('helvetica', 'normal');
    }
    doc.setFontSize(8);
    doc.setTextColor(148, 163, 184);

    const footerText = `Page ${i} of ${totalPages} • Training & Compliance Matrix`;
    doc.text(footerText, pageWidth / 2, pageHeight - 8, { align: 'center' });
  }

  // Save / Trigger Download
  const filename = `Training_Compliance_Matrix_Report_${new Date().toISOString().slice(0, 10)}.pdf`;
  doc.save(filename);
}

/**
 * Generates and downloads a Training Session Announcement PDF
 * with attendees grouped by Department, session schedule, venue, and daily attendance sign-in blocks.
 *
 * @param {Object} session
 */
export async function exportSessionAnnouncementPdf(session) {
  if (!session) return;

  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  const fontName = await applyPyidaungsuFont(doc);

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 14;
  let currentY = 16;

  // Colors
  const primaryColor = [14, 116, 144]; // Sky-700
  const darkNavy = [15, 23, 42]; // Slate-900
  const secondaryColor = [71, 85, 105]; // Slate-600
  const lightBg = [240, 249, 255]; // Sky-50

  // 1. Document Header
  try {
    doc.setFont(fontName, 'bold');
  } catch (e) {
    doc.setFont('helvetica', 'bold');
  }
  doc.setFontSize(16);
  doc.setTextColor(...primaryColor);
  doc.text('OFFICIAL TRAINING SESSION ANNOUNCEMENT', margin, currentY);

  currentY += 6;
  try {
    doc.setFont(fontName, 'normal');
  } catch (e) {
    doc.setFont('helvetica', 'normal');
  }
  doc.setFontSize(9);
  doc.setTextColor(...secondaryColor);
  doc.text(`Reference Session Code: ${session.session_code} • Issued: ${new Date().toLocaleDateString()}`, margin, currentY);

  currentY += 6;

  // 2. Training Session Summary Box
  const trainingTitle = session.training?.title || session.title || 'Training Program';
  const trainingCode = session.training?.code || '';
  const venue = session.venue || 'Training Room / Meeting Hall';
  const meetingLink = session.meeting_link ? `Online: ${session.meeting_link}` : '';

  // Extract trainer(s) from schedule_slots or session.trainer
  const slotTrainers = Array.isArray(session.schedule_slots)
    ? [...new Set(session.schedule_slots.map((sl) => sl.trainer_name).filter(Boolean))]
    : [];
  const trainerName = slotTrainers.length > 0
    ? slotTrainers.join(', ')
    : (session.trainer?.name || 'To Be Assigned');

  const durationDays = session.duration_days || session.training?.duration_days || (session.schedule_slots?.length || 1);
  const startDate = session.start_date || (session.scheduled_at ? session.scheduled_at.substring(0, 10) : 'TBD');
  const endDate = session.end_date || startDate;

  // Build schedule text with slots details if available
  let scheduleText = `${startDate} ${durationDays > 1 ? `to ${endDate} (${durationDays} Days Duration)` : '(1 Day)'}`;
  if (Array.isArray(session.schedule_slots) && session.schedule_slots.length > 0) {
    const slotSummaries = session.schedule_slots.map((sl, i) => {
      const timeStr = sl.time ? ` @ ${sl.time}` : '';
      const trainerStr = sl.trainer_name ? ` (${sl.trainer_name})` : '';
      const statusStr = sl.status ? ` [${sl.status}]` : '';
      return `Day ${i + 1}: ${sl.date}${timeStr}${trainerStr}${statusStr}`;
    });
    scheduleText = slotSummaries.join('   |   ');
  }

  const boxHeight = Array.isArray(session.schedule_slots) && session.schedule_slots.length > 1 ? 36 : 30;

  doc.setFillColor(...lightBg);
  doc.setDrawColor(186, 230, 253);
  doc.roundedRect(margin, currentY, pageWidth - margin * 2, boxHeight, 2, 2, 'FD');

  try {
    doc.setFont(fontName, 'bold');
  } catch (e) {
    doc.setFont('helvetica', 'bold');
  }
  doc.setFontSize(11);
  doc.setTextColor(...darkNavy);
  doc.text(`${trainingTitle} [${trainingCode}]`, margin + 4, currentY + 7);

  try {
    doc.setFont(fontName, 'normal');
  } catch (e) {
    doc.setFont('helvetica', 'normal');
  }
  doc.setFontSize(8);
  doc.setTextColor(...secondaryColor);

  doc.text(`• Trainer / Instructor(s): ${trainerName}`, margin + 4, currentY + 13);
  doc.text(`• Location / Venue: ${venue} ${meetingLink ? `| ${meetingLink}` : ''}`, margin + 4, currentY + 18);
  doc.text(`• Schedule & Timetable:`, margin + 4, currentY + 23);

  // Wrap scheduleText in case there are multiple slots
  const wrappedSchedule = doc.splitTextToSize(scheduleText, pageWidth - margin * 2 - 8);
  doc.text(wrappedSchedule, margin + 8, currentY + 27);

  currentY += boxHeight + 6;

  // 3. Group Participants by Department
  const participants = session.participants || [];
  const deptMap = {};

  participants.forEach((p) => {
    const deptName = p.user?.department?.name || 'General / Unassigned Department';
    if (!deptMap[deptName]) {
      deptMap[deptName] = [];
    }
    deptMap[deptName].push(p);
  });

  const sessionDates = session.session_dates && session.session_dates.length > 0
    ? session.session_dates
    : [startDate];

  const tableRows = [];

  Object.keys(deptMap).sort().forEach((deptName) => {
    const deptParticipants = deptMap[deptName];

    // Department Header Banner Row
    tableRows.push([
      {
        content: `DEPARTMENT: ${deptName.toUpperCase()} (${deptParticipants.length} Participants)`,
        colSpan: 4 + Math.min(sessionDates.length, 3),
        styles: {
          fillColor: [224, 242, 254], // sky-100
          textColor: [3, 105, 161], // sky-700
          fontStyle: 'bold',
          fontSize: 8.5,
        },
      },
    ]);

    deptParticipants.forEach((p, idx) => {
      const row = [
        String(idx + 1),
        `${p.user?.name || 'Unknown'}\n${p.user?.email || ''}`,
        p.user?.office_position?.name || 'Staff Member',
        p.attendance_status || 'REGISTERED',
      ];

      // Add sign-in/check-in columns for dates (up to 3 dates in portrait table)
      for (let d = 0; d < Math.min(sessionDates.length, 3); d++) {
        const dateKey = sessionDates[d];
        const dailyRecord = p.daily_attendance ? p.daily_attendance[dateKey] : null;
        row.push(dailyRecord ? dailyRecord.status : '__________');
      }

      tableRows.push(row);
    });
  });

  // Table Headers
  const dynamicHeaders = [
    '#',
    'Participant Name & Email',
    'Office Position',
    'Overall Status',
  ];

  for (let d = 0; d < Math.min(sessionDates.length, 3); d++) {
    const slot = (session.schedule_slots || [])[d];
    let headerText = `Day ${d + 1}\n${sessionDates[d]}`;
    if (slot?.time) {
      headerText += `\n@ ${slot.time}`;
    }
    if (slot?.trainer_name) {
      headerText += `\n(${slot.trainer_name})`;
    }
    dynamicHeaders.push(headerText);
  }

  const autoTableConfig = {
    startY: currentY,
    head: [dynamicHeaders],
    body: tableRows,
    theme: 'grid',
    styles: {
      font: fontName,
      fontSize: 8,
      cellPadding: 3,
      valign: 'middle',
      lineColor: [226, 232, 240],
      lineWidth: 0.2,
    },
    headStyles: {
      fillColor: [14, 116, 144], // Sky-700
      textColor: [255, 255, 255],
      fontStyle: 'bold',
      fontSize: 8,
      halign: 'center',
    },
    columnStyles: {
      0: { cellWidth: 8, halign: 'center' },
      1: { cellWidth: 50, fontStyle: 'bold' },
      2: { cellWidth: 35 },
      3: { cellWidth: 24, halign: 'center' },
    },
    margin: { left: margin, right: margin, bottom: 25 },
  };

  if (typeof autoTable === 'function') {
    autoTable(doc, autoTableConfig);
  } else if (typeof doc.autoTable === 'function') {
    doc.autoTable(autoTableConfig);
  }

  // 4. Instructions & Sign-off Block
  let finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY + 8 : currentY + 40;
  if (finalY > pageHeight - 35) {
    doc.addPage();
    finalY = 20;
  }

  try {
    doc.setFont(fontName, 'bold');
  } catch (e) {
    doc.setFont('helvetica', 'bold');
  }
  doc.setFontSize(8);
  doc.setTextColor(...darkNavy);
  doc.text('Important Training Guidelines & Compliance Notice:', margin, finalY);

  try {
    doc.setFont(fontName, 'normal');
  } catch (e) {
    doc.setFont('helvetica', 'normal');
  }
  doc.setFontSize(7.5);
  doc.setTextColor(...secondaryColor);
  doc.text('1. Full session attendance is mandatory. Any absence must be excused with supervisor approval.', margin, finalY + 4.5);
  doc.text('2. Following training completion and trainer approval, the online assessment test will unlock in the Employee Portal.', margin, finalY + 8.5);

  finalY += 16;

  // Signatures
  doc.setFontSize(8);
  doc.setTextColor(...darkNavy);
  doc.text('_____________________________', margin, finalY);
  doc.text('Trainer / Facilitator Signature', margin, finalY + 4);

  doc.text('_____________________________', pageWidth - margin - 50, finalY);
  doc.text('Training Approver / HR Manager', pageWidth - margin - 50, finalY + 4);

  // Footer Page Numbers
  const totalPages = typeof doc.getNumberOfPages === 'function'
    ? doc.getNumberOfPages()
    : (doc.internal?.getNumberOfPages ? doc.internal.getNumberOfPages() : 1);

  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    try {
      doc.setFont(fontName, 'normal');
    } catch (e) {
      doc.setFont('helvetica', 'normal');
    }
    doc.setFontSize(7.5);
    doc.setTextColor(148, 163, 184);
    doc.text(`Page ${i} of ${totalPages} • Session Announcement: ${session.session_code}`, pageWidth / 2, pageHeight - 6, { align: 'center' });
  }

  // Save PDF
  const filename = `Training_Announcement_${session.session_code}_${new Date().toISOString().slice(0, 10)}.pdf`;
  doc.save(filename);
}

/**
 * Ensures the Pyidaungsu webfont is loaded in the browser's FontFaceSet
 * before drawing Myanmar script onto an offscreen 2D canvas.
 */
const ensurePyidaungsuFontLoaded = async () => {
  try {
    if (typeof document !== 'undefined' && document.fonts && document.fonts.load) {
      await Promise.all([
        document.fonts.load('14px Pyidaungsu'),
        document.fonts.load('bold 14px Pyidaungsu'),
      ]);
      await document.fonts.ready;
    }
  } catch (e) {
    console.warn('Pyidaungsu font load check warning:', e);
  }
};

/**
 * Strips HTML tags and normalizes whitespace.
 */
const cleanPlainText = (text, preserveNewlines = false) => {
  if (!text) return '';
  let s = typeof text !== 'string' ? String(text) : text;
  if (/<[a-z][\s\S]*>/i.test(s)) {
    const div = document.createElement('div');
    div.innerHTML = s;
    s = (div.textContent || div.innerText || '').trim();
  }
  s = s.replace(/\r\n/g, '\n');
  if (preserveNewlines) {
    return s
      .split('\n')
      .map((line) => line.replace(/[ \t]+/g, ' ').trim())
      .filter((line, idx, arr) => line.length > 0 || (idx > 0 && idx < arr.length - 1))
      .join('\n')
      .trim();
  }
  return s.replace(/\s+/g, ' ').trim();
};

/**
 * Detects whether a string contains Myanmar Unicode characters or non-ASCII glyphs
 * that require the browser's native HarfBuzz OpenType text shaper.
 */
const hasComplexUnicode = (text) => /[^\x00-\x7F]/.test(String(text || ''));

/**
 * Splits a single paragraph into wrappable tokens (words & Myanmar syllable clusters)
 * so 2D canvas measureText wraps cleanly without overflowing.
 */
const tokenizeForMyanmarWrap = (paragraph) => {
  if (!paragraph) return [''];
  if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
    try {
      const segmenter = new Intl.Segmenter('my', { granularity: 'word' });
      const tokens = [];
      for (const seg of segmenter.segment(paragraph)) {
        tokens.push(seg.segment);
      }
      if (tokens.length > 0) return tokens;
    } catch {
      // Fallback below
    }
  }
  // Regex fallback: split on whitespace and Myanmar base consonant syllable boundaries
  return paragraph
    .split(/( +|(?=[\u1000-\u1021\u1023-\u102A\u103F\u1040-\u104F])(?<![\u1031\u1039\u103A]))/g)
    .filter(Boolean);
};

/**
 * Renders Unicode / Myanmar text onto an offscreen 2D <canvas> using native
 * CanvasRenderingContext2D.fillText() at 4x DPI (384 DPI).
 *
 * Why native 2D canvas instead of html2canvas or raw jsPDF text:
 * 1. Unlike raw jsPDF, the browser's 2D canvas uses HarfBuzz OpenType shaping so
 *    Myanmar vowels (ေ), medials (ြ, ွ), and stacked consonants render in 100% accurate visual order.
 * 2. Unlike html2canvas (which breaks under `html { zoom: 0.75 }` and collides words),
 *    offscreen 2D canvas `ctx.fillText(line, x, y)` draws each full line in a single native pass
 *    in unzoomed canvas coordinate space with zero word collisions.
 */
const renderUnicodeTextBlockToPng = (
  text,
  {
    fontSizePt = 9,
    maxWidthMm = 170,
    color = '#0f172a',
    fontWeight = 'normal',
    lineHeightMultiplier = 1.65,
  } = {}
) => {
  const PX_PER_MM = 96 / 25.4;
  const MM_PER_PX = 25.4 / 96;
  const SCALE = 4; // 384 DPI print quality

  const fontSizePx = fontSizePt * (96 / 72);
  const maxWidthPx = Math.max(24, maxWidthMm * PX_PER_MM);
  const fontSpec = `${fontWeight} ${fontSizePx}px 'Pyidaungsu', 'Padauk', 'Myanmar Text', 'Segoe UI', Arial, sans-serif`;

  const measureCanvas = document.createElement('canvas');
  const measureCtx = measureCanvas.getContext('2d');
  measureCtx.font = fontSpec;

  const paragraphs = String(text || '').split('\n');
  const wrappedLines = [];

  for (const para of paragraphs) {
    const trimmedPara = para.trim();
    if (!trimmedPara) {
      wrappedLines.push('');
      continue;
    }

    // First try space-delimited tokens so English phrases and spaced Myanmar phrases stay grouped;
    // break long tokens further using Myanmar syllable segmentation when needed.
    const spaceTokens = trimmedPara.split(/( +)/).filter(Boolean);
    const fineTokens = [];
    for (const st of spaceTokens) {
      if (measureCtx.measureText(st).width > maxWidthPx * 0.65 && !/^ +$/.test(st)) {
        fineTokens.push(...tokenizeForMyanmarWrap(st));
      } else {
        fineTokens.push(st);
      }
    }

    let currentLine = '';
    for (const token of fineTokens) {
      const candidate = currentLine ? currentLine + token : token.trimStart();
      if (!candidate) continue;

      if (measureCtx.measureText(candidate).width <= maxWidthPx || !currentLine) {
        currentLine = candidate;
      } else {
        wrappedLines.push(currentLine.trimEnd());
        currentLine = token.trimStart();
      }
    }
    if (currentLine) {
      wrappedLines.push(currentLine.trimEnd());
    }
  }

  if (wrappedLines.length === 0) {
    wrappedLines.push('');
  }

  const lineHeightPx = Math.ceil(fontSizePx * lineHeightMultiplier);
  const topPadPx = Math.ceil(fontSizePx * 0.35);
  const bottomPadPx = Math.ceil(fontSizePx * 0.45);
  const totalHeightPx = wrappedLines.length * lineHeightPx + topPadPx + bottomPadPx;

  const canvas = document.createElement('canvas');
  canvas.width = Math.ceil(maxWidthPx * SCALE);
  canvas.height = Math.ceil(totalHeightPx * SCALE);

  const ctx = canvas.getContext('2d');
  ctx.scale(SCALE, SCALE);
  ctx.font = fontSpec;
  ctx.fillStyle = color;
  ctx.textBaseline = 'alphabetic';

  wrappedLines.forEach((line, idx) => {
    if (!line) return;
    const baselineY = topPadPx + idx * lineHeightPx + fontSizePx * 1.05;
    ctx.fillText(line, 0, baselineY);
  });

  return {
    dataUrl: canvas.toDataURL('image/png'),
    widthMm: maxWidthMm,
    heightMm: totalHeightPx * MM_PER_PX,
    lineCount: wrappedLines.length,
  };
};

/**
 * Generates and downloads the Training Catalog & Connected Documents Report PDF.
 * Uses vector jsPDF + jspdf-autotable for crisp layout/tables and native 2D Canvas
 * HarfBuzz shaping for Myanmar Unicode text.
 * Excludes individual employees/related persons, showing only training catalog specs,
 * descriptions, connected SOPs/documents, and high-level target scopes.
 *
 * @param {Object} options
 * @param {Array} [options.trainings=[]] List of training catalog objects
 * @param {Object|null} [options.singleTraining=null] If set, exports only this specific training
 */
export async function exportTrainingCurriculumPdf({
  trainings = [],
  singleTraining = null,
}) {
  await ensurePyidaungsuFontLoaded();

  const trainingsList = singleTraining
    ? [singleTraining]
    : (trainings && trainings.length > 0 ? trainings : []);

  const totalCatalogs = trainingsList.length;
  let totalDocsCount = 0;
  trainingsList.forEach((t) => {
    const docs = t.company_documents || t.companyDocuments || [];
    totalDocsCount += docs.length;
  });

  const nowStr = new Date().toLocaleString();
  const todayDate = new Date().toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });

  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  const pageWidth = doc.internal.pageSize.getWidth(); // 210mm
  const pageHeight = doc.internal.pageSize.getHeight(); // 297mm
  const margin = 14;
  const contentWidth = pageWidth - margin * 2; // 182mm
  let currentY = 12;

  // Palette (consistent with todoReportPdfExport.js)
  const NAVY = [15, 23, 42]; // Slate-900
  const SKY_DARK = [3, 105, 161]; // Sky-700
  const SKY_ACCENT = [2, 132, 199]; // Sky-600
  const SKY_LIGHT_BG = [240, 249, 255]; // Sky-50
  const SKY_BORDER = [186, 230, 253]; // Sky-200
  const CARD_HEADER_BG = [241, 245, 249]; // Slate-100
  const LIGHT_BG = [248, 250, 252]; // Slate-50
  const BORDER = [203, 213, 225]; // Slate-300
  const MUTED = [100, 116, 139]; // Slate-500
  const SLATE_TEXT = [51, 65, 85]; // Slate-700
  const GREEN = [5, 150, 105]; // Emerald-600

  const mainTitle = singleTraining
    ? 'TRAINING CATALOG & DOCUMENT SPECIFICATION'
    : 'TRAINING CATALOG & CONNECTED DOCUMENTS REPORT';
  const subtitle = singleTraining
    ? `Official Curriculum & Connected SOPs: [${cleanPlainText(singleTraining.code || 'N/A')}] ${cleanPlainText(singleTraining.title || '')}`
    : 'Comprehensive Catalog Overview: Course Descriptions, Passing Criteria & Connected SOPs';

  const drawContinuationHeader = () => {
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(...NAVY);
    doc.text(`${mainTitle} (CONTINUED)`, margin, 11);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(...MUTED);
    doc.text(`Generated: ${nowStr}`, pageWidth - margin, 11, { align: 'right' });

    doc.setDrawColor(...BORDER);
    doc.setLineWidth(0.25);
    doc.line(margin, 13, pageWidth - margin, 13);
    return 17;
  };

  // ------------------------------------------------------------------
  // 1. Page 1 Top Header Banner
  // ------------------------------------------------------------------
  const headerH = 19;
  doc.setFillColor(...NAVY);
  doc.roundedRect(margin, currentY, contentWidth, headerH, 2, 2, 'F');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.setTextColor(255, 255, 255);
  doc.text(mainTitle, margin + 5, currentY + 7.5);

  if (hasComplexUnicode(subtitle)) {
    const subImg = renderUnicodeTextBlockToPng(subtitle, {
      fontSizePt: 8,
      maxWidthMm: contentWidth - 62,
      color: '#bae6fd',
      fontWeight: 'normal',
    });
    doc.addImage(subImg.dataUrl, 'PNG', margin + 5, currentY + 9.5, subImg.widthMm, subImg.heightMm);
  } else {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(186, 230, 253);
    doc.text(subtitle, margin + 5, currentY + 13.5);
  }

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(226, 232, 240);
  doc.text(`Generated: ${nowStr}`, pageWidth - margin - 5, currentY + 7.5, { align: 'right' });
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(125, 211, 252);
  doc.text('Training Master System', pageWidth - margin - 5, currentY + 13.5, { align: 'right' });

  currentY += headerH + 3;

  // ------------------------------------------------------------------
  // 2. Summary KPI Bar
  // ------------------------------------------------------------------
  const metaH = 9.5;
  doc.setFillColor(...SKY_LIGHT_BG);
  doc.setDrawColor(...SKY_BORDER);
  doc.setLineWidth(0.25);
  doc.roundedRect(margin, currentY, contentWidth, metaH, 1.5, 1.5, 'FD');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(...SKY_DARK);
  doc.text(
    `Total Training Programs: ${totalCatalogs}     |     Total Connected SOPs & Documents: ${totalDocsCount}`,
    margin + 4,
    currentY + 6.1
  );

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(...SLATE_TEXT);
  doc.text(
    singleTraining ? 'Scope: Single Catalog Export' : 'Scope: Active Curriculum Catalog',
    pageWidth - margin - 4,
    currentY + 6.1,
    { align: 'right' }
  );

  currentY += metaH + 5;

  // ------------------------------------------------------------------
  // 3. Render Each Training Catalog Section
  // ------------------------------------------------------------------
  if (trainingsList.length === 0) {
    doc.setFillColor(...LIGHT_BG);
    doc.setDrawColor(...BORDER);
    doc.roundedRect(margin, currentY, contentWidth, 20, 1.5, 1.5, 'FD');
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(...MUTED);
    doc.text('No training catalogs available for export.', pageWidth / 2, currentY + 11.5, { align: 'center' });
    currentY += 26;
  }

  for (let i = 0; i < trainingsList.length; i++) {
    const t = trainingsList[i];
    const idx = i + 1;

    const code = cleanPlainText(t.code || 'N/A');
    const title = cleanPlainText(t.title || 'Untitled Training');
    const status = cleanPlainText((t.status || 'ACTIVE').toUpperCase());
    const passScore = `${t.passing_score || 80}%`;
    const retrainText = `Every ${t.retrain_interval || 12} ${cleanPlainText(t.retrain_unit || 'month')}(s)`;
    const categoryName = cleanPlainText(t.category?.name || 'General');
    const durationText = t.duration_days ? `${t.duration_days} Day(s)` : 'Standard';
    const description =
      cleanPlainText(t.description, true) ||
      'No detailed description provided for this training catalog.';
    const docs = t.company_documents || t.companyDocuments || [];
    const scopes = t.scopes || [];

    const scopesSummary =
      scopes.length > 0
        ? scopes
            .map((s) => {
              const dept = cleanPlainText(s.department?.name || 'Any Dept');
              const pos = cleanPlainText(
                s.office_position?.name || s.officePosition?.name || 'All Positions'
              );
              return `${dept} -> ${pos}`;
            })
            .join('   |   ')
        : '';

    const innerTextWidth = contentWidth - 8; // 174mm

    // Pre-measure Description block
    let descUnicodeImg = null;
    let descLines = [];
    let descContentH = 0;

    if (hasComplexUnicode(description)) {
      descUnicodeImg = renderUnicodeTextBlockToPng(description, {
        fontSizePt: 9,
        maxWidthMm: innerTextWidth,
        color: '#1e293b',
        fontWeight: 'normal',
      });
      descContentH = descUnicodeImg.heightMm;
    } else {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);
      descLines = doc.splitTextToSize(description, innerTextWidth);
      descContentH = Math.max(4.5, descLines.length * 4.2);
    }

    // Pre-measure Scopes block (if present)
    let scopesUnicodeImg = null;
    let scopesLines = [];
    let scopesContentH = 0;

    if (scopesSummary) {
      const fullScopeStr = `Target Scopes:  ${scopesSummary}`;
      if (hasComplexUnicode(fullScopeStr)) {
        scopesUnicodeImg = renderUnicodeTextBlockToPng(fullScopeStr, {
          fontSizePt: 8,
          maxWidthMm: innerTextWidth,
          color: '#334155',
          fontWeight: 'normal',
        });
        scopesContentH = scopesUnicodeImg.heightMm + 3;
      } else {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        scopesLines = doc.splitTextToSize(fullScopeStr, innerTextWidth);
        scopesContentH = Math.max(4, scopesLines.length * 3.8) + 3;
      }
    }

    const headerBannerH = 14.5;
    const descBoxH = 6.5 + descContentH + (scopesSummary ? scopesContentH + 1.5 : 0) + 2.5;
    const minNeededBeforeBreak = headerBannerH + descBoxH + 22;

    if (currentY + Math.min(minNeededBeforeBreak, 95) > pageHeight - 18) {
      doc.addPage();
      currentY = drawContinuationHeader();
    }

    // ----------------------------------------------------------------
    // 3A. Training Catalog Header Bar
    // ----------------------------------------------------------------
    doc.setFillColor(...CARD_HEADER_BG);
    doc.setDrawColor(...BORDER);
    doc.setLineWidth(0.25);
    doc.roundedRect(margin, currentY, contentWidth, headerBannerH, 1.5, 1.5, 'FD');

    // Left Sky Accent Strip
    doc.setFillColor(...SKY_ACCENT);
    doc.rect(margin, currentY, 2.5, headerBannerH, 'F');

    // Row 1 Left: Index + Code + Title
    const prefixStr = `${idx}. [${code}] `;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9.5);
    doc.setTextColor(...SKY_ACCENT);
    doc.text(prefixStr, margin + 5, currentY + 5.6);
    const titleStartX = margin + 5 + doc.getTextWidth(prefixStr);

    if (hasComplexUnicode(title)) {
      const titleImg = renderUnicodeTextBlockToPng(title, {
        fontSizePt: 9.5,
        maxWidthMm: 95,
        color: '#0f172a',
        fontWeight: 'bold',
      });
      doc.addImage(
        titleImg.dataUrl,
        'PNG',
        titleStartX,
        currentY + 1.3,
        titleImg.widthMm,
        titleImg.heightMm
      );
    } else {
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(10);
      doc.setTextColor(...NAVY);
      const clippedTitle = doc.splitTextToSize(title, 98)[0] || title;
      doc.text(clippedTitle, titleStartX, currentY + 5.6);
    }

    // Row 1 Right: Status | Pass | Retrain
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.8);
    doc.setTextColor(...GREEN);
    const rightInfo = `${status}   |   Pass: ${passScore}   |   Retrain: ${retrainText}`;
    doc.text(rightInfo, pageWidth - margin - 4, currentY + 5.6, { align: 'right' });

    // Divider line between Row 1 and Row 2 of Card Header
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.2);
    doc.line(margin + 2.5, currentY + 8, pageWidth - margin, currentY + 8);

    // Row 2: Category, Duration & Attached Document Count
    const subHeaderStr = `Category: ${categoryName}     |     Duration: ${durationText}     |     Connected Documents / SOPs: ${docs.length} Attached`;
    if (hasComplexUnicode(subHeaderStr)) {
      const subHeaderImg = renderUnicodeTextBlockToPng(subHeaderStr, {
        fontSizePt: 8,
        maxWidthMm: contentWidth - 10,
        color: '#475569',
        fontWeight: 'normal',
      });
      doc.addImage(
        subHeaderImg.dataUrl,
        'PNG',
        margin + 5,
        currentY + 8.3,
        subHeaderImg.widthMm,
        subHeaderImg.heightMm
      );
    } else {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(7.8);
      doc.setTextColor(...SLATE_TEXT);
      doc.text(subHeaderStr, margin + 5, currentY + 12);
    }

    currentY += headerBannerH + 1.5;

    // ----------------------------------------------------------------
    // 3B. Description & Target Scopes Box
    // ----------------------------------------------------------------
    if (currentY + descBoxH > pageHeight - 18) {
      doc.addPage();
      currentY = drawContinuationHeader();
    }

    doc.setFillColor(255, 255, 255);
    doc.setDrawColor(...BORDER);
    doc.setLineWidth(0.2);
    doc.roundedRect(margin, currentY, contentWidth, descBoxH, 1, 1, 'FD');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.2);
    doc.setTextColor(...MUTED);
    doc.text('DESCRIPTION / COURSE OBJECTIVES:', margin + 4, currentY + 4.5);

    if (descUnicodeImg) {
      doc.addImage(
        descUnicodeImg.dataUrl,
        'PNG',
        margin + 4,
        currentY + 5.8,
        descUnicodeImg.widthMm,
        descUnicodeImg.heightMm
      );
    } else {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.8);
      doc.setTextColor(...NAVY);
      doc.text(descLines, margin + 4, currentY + 9.2);
    }

    if (scopesSummary) {
      const scopeDividerY = currentY + 6.5 + descContentH;
      doc.setDrawColor(226, 232, 240);
      doc.setLineWidth(0.2);
      doc.line(margin + 4, scopeDividerY, pageWidth - margin - 4, scopeDividerY);

      if (scopesUnicodeImg) {
        doc.addImage(
          scopesUnicodeImg.dataUrl,
          'PNG',
          margin + 4,
          scopeDividerY + 1,
          scopesUnicodeImg.widthMm,
          scopesUnicodeImg.heightMm
        );
      } else {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(...SLATE_TEXT);
        const label = 'TARGET SCOPES: ';
        doc.text(label, margin + 4, scopeDividerY + 4.2);
        const lw = doc.getTextWidth(label);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.8);
        doc.setTextColor(...NAVY);
        const wrappedScopeOnly = doc.splitTextToSize(scopesSummary, innerTextWidth - lw);
        doc.text(wrappedScopeOnly, margin + 4 + lw, scopeDividerY + 4.2);
      }
    }

    currentY += descBoxH + 1.5;

    // ----------------------------------------------------------------
    // 3C. Connected Documents / SOPs Table (autoTable)
    // ----------------------------------------------------------------
    const colWidths = {
      0: 10,
      1: 96,
      2: 40,
      3: 36,
    };

    const docTableBody =
      docs.length === 0
        ? [
            [
              {
                content:
                  'No company SOPs or reference documents are currently linked to this training catalog.',
                colSpan: 4,
                styles: {
                  halign: 'center',
                  fontStyle: 'italic',
                  textColor: MUTED,
                  cellPadding: 3,
                },
              },
            ],
          ]
        : docs.map((d, dIdx) => [
            String(dIdx + 1),
            cleanPlainText(d.title || 'Untitled Document'),
            cleanPlainText(d.type?.name || 'SOP / Procedure'),
            cleanPlainText(d.department?.name || 'Company Wide'),
          ]);

    const tableConfig = {
      startY: currentY,
      head: [
        [
          '#',
          `Connected Documents & SOPs Under This Title (${docs.length})`,
          'Document Type',
          'Department',
        ],
      ],
      body: docTableBody,
      theme: 'grid',
      margin: { left: margin, right: margin, bottom: 18 },
      styles: {
        font: 'helvetica',
        fontSize: 8,
        cellPadding: 2.2,
        valign: 'middle',
        lineColor: [226, 232, 240],
        lineWidth: 0.2,
        textColor: NAVY,
      },
      headStyles: {
        fillColor: [30, 41, 59], // Slate-800
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        fontSize: 8,
        halign: 'left',
      },
      alternateRowStyles: {
        fillColor: LIGHT_BG,
      },
      columnStyles: {
        0: { cellWidth: colWidths[0], halign: 'center', textColor: MUTED },
        1: { cellWidth: colWidths[1], fontStyle: 'bold', textColor: NAVY },
        2: { cellWidth: colWidths[2], textColor: SLATE_TEXT },
        3: { cellWidth: colWidths[3], textColor: SLATE_TEXT },
      },
      didParseCell: (data) => {
        if (data.section === 'head' && data.column.index === 0) {
          data.cell.styles.halign = 'center';
        }
        if (data.section !== 'body') return;
        const rawVal = data.cell.raw;
        if (!rawVal || typeof rawVal === 'object') return;
        const strVal = String(rawVal);
        if (hasComplexUnicode(strVal)) {
          const colW = colWidths[data.column.index] || 50;
          const innerW = Math.max(15, colW - 5);
          const isTitleCol = data.column.index === 1;
          const unicodeImg = renderUnicodeTextBlockToPng(strVal, {
            fontSizePt: 8.2,
            maxWidthMm: innerW,
            color: isTitleCol ? '#0f172a' : '#334155',
            fontWeight: isTitleCol ? 'bold' : 'normal',
          });
          data.cell._unicodeImg = unicodeImg;
          data.cell.text = new Array(Math.max(1, unicodeImg.lineCount)).fill(' ');
          data.cell.styles.minCellHeight = Math.max(6.8, unicodeImg.heightMm + 2.4);
        }
      },
      didDrawCell: (data) => {
        if (data.section === 'body' && data.cell._unicodeImg) {
          const img = data.cell._unicodeImg;
          const drawX = data.cell.x + 2.2;
          const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
          doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
        }
      },
    };

    if (typeof autoTable === 'function') {
      autoTable(doc, tableConfig);
    } else if (typeof doc.autoTable === 'function') {
      doc.autoTable(tableConfig);
    }

    currentY = (doc.lastAutoTable ? doc.lastAutoTable.finalY : currentY + 15) + 6;
  }

  // ------------------------------------------------------------------
  // 4. Official Verification & Sign-Off Block (Last Page)
  // ------------------------------------------------------------------
  const sigBlockH = 26;
  if (currentY + sigBlockH > pageHeight - 16) {
    doc.addPage();
    currentY = drawContinuationHeader() + 2;
  } else {
    currentY += 2;
  }

  doc.setDrawColor(...BORDER);
  doc.setLineWidth(0.3);
  doc.line(margin, currentY, pageWidth - margin, currentY);
  currentY += 4.5;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(...NAVY);
  doc.text('Prepared & Reviewed By (Training Coordinator):', margin, currentY);
  doc.text('Approved & Authorized By (HR / Management):', margin + contentWidth / 2 + 5, currentY);
  currentY += 5.5;

  const sigColW = contentWidth / 2 - 5;
  const sigRows = [
    ['Signature:', '', 'Signature:', ''],
    ['Name & Title:', '', 'Name & Title:', ''],
    ['Date:', todayDate, 'Date:', ''],
  ];

  sigRows.forEach(([l1, v1, l2, v2]) => {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(...MUTED);
    doc.text(l1, margin, currentY);
    if (v1) {
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(...NAVY);
      doc.text(v1, margin + 20, currentY);
    }
    doc.setDrawColor(148, 163, 184);
    doc.setLineWidth(0.2);
    doc.line(margin + 20, currentY + 1, margin + sigColW, currentY + 1);

    const x2 = margin + contentWidth / 2 + 5;
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(...MUTED);
    doc.text(l2, x2, currentY);
    if (v2) {
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(...NAVY);
      doc.text(v2, x2 + 20, currentY);
    }
    doc.line(x2 + 20, currentY + 1, x2 + sigColW, currentY + 1);

    currentY += 5.5;
  });

  // ------------------------------------------------------------------
  // 5. Running Page Number Footers on All Pages
  // ------------------------------------------------------------------
  const totalPages =
    typeof doc.getNumberOfPages === 'function'
      ? doc.getNumberOfPages()
      : doc.internal?.getNumberOfPages
      ? doc.internal.getNumberOfPages()
      : 1;

  for (let p = 1; p <= totalPages; p++) {
    doc.setPage(p);
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.2);
    doc.line(margin, pageHeight - 11, pageWidth - margin, pageHeight - 11);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.2);
    doc.setTextColor(148, 163, 184);
    doc.text(
      'Training Master Management System  |  Training Catalog & Connected Documents Report',
      margin,
      pageHeight - 7
    );
    doc.text(`Page ${p} of ${totalPages}`, pageWidth - margin, pageHeight - 7, {
      align: 'right',
    });
  }

  const filename = singleTraining
    ? `Training_Catalog_${singleTraining.code || 'Course'}_Documents_${new Date().toISOString().slice(0, 10)}.pdf`
    : `Training_Catalog_Documents_Report_${new Date().toISOString().slice(0, 10)}.pdf`;

  doc.save(filename);
  return { doc, filename };
}


