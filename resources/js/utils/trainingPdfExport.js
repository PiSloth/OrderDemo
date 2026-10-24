import html2canvas from 'html2canvas';
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
 * Ensures the Pyidaungsu webfont is fully loaded in the browser before rendering with html2canvas.
 */
const ensurePyidaungsuFontLoaded = async () => {
  try {
    if (document.fonts && document.fonts.load) {
      await Promise.all([
        document.fonts.load('12px Pyidaungsu'),
        document.fonts.load('bold 12px Pyidaungsu'),
      ]);
      await document.fonts.ready;
    }
  } catch (e) {
    console.warn('Pyidaungsu font load check warning:', e);
  }
};

const escapeHtml = (text) => {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

const cleanTextHtml = (text) => {
  if (!text) return '';
  let s = typeof text !== 'string' ? String(text) : text;
  if (/<[a-z][\s\S]*>/i.test(s)) {
    const div = document.createElement('div');
    div.innerHTML = s;
    s = (div.textContent || div.innerText || '').trim();
  }
  return escapeHtml(s.trim());
};

const buildTrainingCardHtml = (t, index) => {
  const docs = t.company_documents || t.companyDocuments || [];
  const scopes = t.scopes || [];

  const docsRowsHtml = docs.length === 0
    ? `<tr><td colspan="4" style="padding: 10px 12px; text-align: center; color: #94a3b8; font-size: 11px; font-style: italic;">No company SOPs or reference documents are currently linked to this catalog.</td></tr>`
    : docs.map((d, dIdx) => `
      <tr style="border-bottom: 1px solid #f1f5f9; background-color: ${dIdx % 2 === 0 ? '#ffffff' : '#f8fafc'};">
        <td style="padding: 7px 8px; text-align: center; font-size: 11px; color: #64748b; width: 36px; border-right: 1px solid #e2e8f0;">${dIdx + 1}</td>
        <td style="padding: 7px 12px; font-size: 11.5px; font-weight: 600; color: #0f172a; font-family: 'Pyidaungsu', sans-serif; border-right: 1px solid #e2e8f0;">${cleanTextHtml(d.title || 'Untitled Document')}</td>
        <td style="padding: 7px 12px; font-size: 11px; color: #475569; width: 150px; font-family: 'Pyidaungsu', sans-serif; border-right: 1px solid #e2e8f0;">${cleanTextHtml(d.type?.name || 'SOP / Procedure')}</td>
        <td style="padding: 7px 12px; font-size: 11px; color: #475569; width: 140px; font-family: 'Pyidaungsu', sans-serif;">${cleanTextHtml(d.department?.name || 'Company Wide')}</td>
      </tr>
    `).join('');

  let scopesHtml = '';
  if (scopes.length > 0) {
    const scopePills = scopes.map(s => {
      const dept = cleanTextHtml(s.department?.name || 'Any Dept');
      const pos = cleanTextHtml(s.office_position?.name || s.officePosition?.name || 'All Positions');
      return `&bull; ${dept} &rarr; <strong>${pos}</strong>`;
    }).join('&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;');

    scopesHtml = `
      <div style="margin-top: 12px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 11px; color: #475569; font-family: 'Pyidaungsu', sans-serif;">
        <span style="font-weight: bold; text-transform: uppercase; margin-right: 6px;">Target Scopes:</span>
        ${scopePills}
      </div>
    `;
  }

  const categoryName = cleanTextHtml(t.category?.name || 'General');
  const durationText = t.duration_days ? `<span style="margin-left: 14px; color: #64748b;">Duration: <strong style="color: #0f172a;">${t.duration_days} Day(s)</strong></span>` : '';

  return `
    <table class="training-catalog-card" style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 16px; background-color: #ffffff; table-layout: fixed;">
      <!-- Header bar -->
      <tr style="background-color: #f8fafc; border-bottom: 1px solid #cbd5e1;">
        <td style="padding: 10px 14px; text-align: left; vertical-align: middle; border-left: 4px solid #0284c7;">
          <span style="font-size: 11px; font-weight: bold; color: #0284c7; background-color: #e0f2fe; padding: 2px 7px; border-radius: 3px; font-family: monospace;">${cleanTextHtml(t.code || 'N/A')}</span>
          <span style="font-size: 13.5px; font-weight: bold; color: #0f172a; margin-left: 8px; font-family: 'Pyidaungsu', sans-serif;">${index}. ${cleanTextHtml(t.title || 'Untitled Training')}</span>
        </td>
        <td style="padding: 10px 14px; text-align: right; vertical-align: middle; width: 310px; font-size: 10.5px; color: #475569;">
          <span style="background-color: #ecfdf5; color: #059669; font-weight: bold; padding: 2px 8px; border-radius: 10px; border: 1px solid #a7f3d0; font-size: 10px; margin-right: 8px;">${escapeHtml((t.status || 'ACTIVE').toUpperCase())}</span>
          <span>Pass: <strong style="color: #059669;">${t.passing_score || 80}%</strong></span>
          &nbsp;&bull;&nbsp;
          <span>Retrain: <strong>Every ${t.retrain_interval || 12} ${escapeHtml(t.retrain_unit || 'month')}(s)</strong></span>
        </td>
      </tr>

      <!-- Card Body -->
      <tr>
        <td colspan="2" style="padding: 14px 16px;">
          <!-- Category & Duration info -->
          <div style="font-size: 11px; color: #64748b; margin-bottom: 8px; font-family: 'Pyidaungsu', sans-serif;">
            Category: <strong style="color: #0284c7;">${categoryName}</strong>
            ${durationText}
          </div>

          <!-- Description -->
          <div style="margin-bottom: 12px;">
            <div style="font-size: 10.5px; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 3px;">Description / Course Objectives:</div>
            <div style="font-size: 12px; color: #1e293b; line-height: 1.6; font-family: 'Pyidaungsu', sans-serif; white-space: pre-wrap; word-break: break-word;">${cleanTextHtml(t.description || 'No detailed description provided for this training catalog.')}</div>
          </div>

          <!-- Connected Documents Table -->
          <div style="margin-top: 10px;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
              <tr>
                <td style="font-size: 10.5px; font-weight: bold; color: #0f172a; text-transform: uppercase;">
                  Connected Documents &amp; SOPs Contained Under This Title:
                </td>
                <td style="font-size: 10px; color: #64748b; text-align: right;">
                  ${docs.length} Document(s) Attached
                </td>
              </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1;">
              <thead>
                <tr style="background-color: #1e293b; color: #ffffff; font-size: 10.5px; text-align: left;">
                  <th style="padding: 7px 8px; width: 36px; text-align: center; border-right: 1px solid #334155;">#</th>
                  <th style="padding: 7px 12px; border-right: 1px solid #334155;">Document Title / Standard Procedure</th>
                  <th style="padding: 7px 12px; width: 150px; border-right: 1px solid #334155;">Document Type</th>
                  <th style="padding: 7px 12px; width: 140px;">Department</th>
                </tr>
              </thead>
              <tbody>
                ${docsRowsHtml}
              </tbody>
            </table>
          </div>

          ${scopesHtml}
        </td>
      </tr>
    </table>
  `;
};

const partitionTrainingsForPages = (trainingsList, measureContainer) => {
  if (trainingsList.length === 0) return [[]];

  measureContainer.innerHTML = trainingsList.map((t, idx) => buildTrainingCardHtml(t, idx + 1)).join('');
  const cardElements = measureContainer.querySelectorAll('.training-catalog-card');
  const cardHeights = Array.from(cardElements).map((el) => el.offsetHeight || 240);
  measureContainer.innerHTML = '';

  const MAX_PAGE_1_HEIGHT = 760;
  const MAX_OTHER_PAGE_HEIGHT = 910;
  const SIGNATURE_HEIGHT = 90;

  const pages = [];
  let currentPage = [];
  let currentHeight = 0;

  for (let i = 0; i < trainingsList.length; i++) {
    const item = trainingsList[i];
    const cHeight = cardHeights[i];
    const isFirstPage = pages.length === 0;
    const isLastItem = i === trainingsList.length - 1;

    let maxAllowed = isFirstPage ? MAX_PAGE_1_HEIGHT : MAX_OTHER_PAGE_HEIGHT;
    if (isLastItem) {
      maxAllowed -= SIGNATURE_HEIGHT;
    }

    if (currentPage.length > 0 && currentHeight + cHeight > maxAllowed) {
      pages.push(currentPage);
      currentPage = [item];
      currentHeight = cHeight;
    } else {
      currentPage.push(item);
      currentHeight += cHeight;
    }
  }

  if (currentPage.length > 0) {
    pages.push(currentPage);
  }

  return pages;
};

/**
 * Generates and downloads the Training Catalog & Connected Documents Report PDF
 * with 100% native Myanmar Unicode text shaping (Pyidaungsu font).
 * Excludes related personnel/employees, showing only course info, descriptions,
 * connected SOPs/documents, and high-level target scopes.
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
  const mainTitle = singleTraining
    ? 'Training Catalog & Document Specification Report'
    : 'Training Catalog & Connected Documents Report';
  const subtitle = singleTraining
    ? `Curriculum Objectives & Connected Documents / SOPs: [${singleTraining.code}] ${singleTraining.title}`
    : 'Comprehensive Catalog Overview: Course Descriptions & Connected SOPs / Documents';

  // Create temporary container for measurement and rendering
  // Coordinates (0, 0) with pointer-events: none and high zIndex below app body to prevent subpixel ghosting
  const renderContainer = document.createElement('div');
  renderContainer.style.position = 'fixed';
  renderContainer.style.left = '0';
  renderContainer.style.top = '0';
  renderContainer.style.width = '794px';
  renderContainer.style.zIndex = '-9999';
  renderContainer.style.pointerEvents = 'none';
  renderContainer.style.backgroundColor = '#ffffff';
  renderContainer.style.fontFamily = `'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;
  document.body.appendChild(renderContainer);

  try {
    const pagesData = partitionTrainingsForPages(trainingsList, renderContainer);
    const totalPages = pagesData.length;

    const doc = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
    });

    let currentItemIdx = 1;

    for (let pageIdx = 0; pageIdx < totalPages; pageIdx++) {
      const pageItems = pagesData[pageIdx];
      const isFirstPage = pageIdx === 0;
      const isLastPage = pageIdx === totalPages - 1;

      const pageElement = document.createElement('div');
      pageElement.style.width = '794px';
      pageElement.style.height = '1123px';
      pageElement.style.minHeight = '1123px';
      pageElement.style.maxHeight = '1123px';
      pageElement.style.boxSizing = 'border-box';
      pageElement.style.padding = '36px 40px';
      pageElement.style.backgroundColor = '#ffffff';
      pageElement.style.fontFamily = `'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;

      // Header Banner (Full on page 1, compact on page 2+)
      let headerHtml = '';
      if (isFirstPage) {
        headerHtml = `
          <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: fixed;">
            <tr>
              <td style="vertical-align: top; text-align: left;">
                <div style="font-size: 18px; font-weight: bold; color: #0f172a; font-family: 'Pyidaungsu', sans-serif;">${escapeHtml(mainTitle)}</div>
                <div style="font-size: 11px; color: #475569; font-family: 'Pyidaungsu', sans-serif; margin-top: 4px;">${escapeHtml(subtitle)}</div>
              </td>
              <td style="vertical-align: top; text-align: right; width: 230px; font-size: 10px; color: #64748b;">
                <div>Generated: ${nowStr}</div>
                <div style="font-weight: 600; color: #0284c7; margin-top: 2px;">Training Master System</div>
              </td>
            </tr>
          </table>

          <div style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 4px; padding: 8px 14px; margin-bottom: 16px; font-size: 11px; color: #0369a1; font-weight: bold; font-family: 'Pyidaungsu', sans-serif;">
            Total Training Programs: ${totalCatalogs} &nbsp;&nbsp;|&nbsp;&nbsp; Total Connected SOPs &amp; Documents: ${totalDocsCount}
          </div>
        `;
      } else {
        headerHtml = `
          <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; table-layout: fixed;">
            <tr>
              <td style="font-size: 10.5px; font-weight: bold; color: #0f172a; font-family: 'Pyidaungsu', sans-serif;">${escapeHtml(mainTitle)} (Continued)</td>
              <td style="font-size: 10px; color: #64748b; text-align: right;">Generated: ${nowStr}</td>
            </tr>
          </table>
        `;
      }

      // Cards
      const cardsHtml = pageItems.map((t) => {
        const html = buildTrainingCardHtml(t, currentItemIdx);
        currentItemIdx++;
        return html;
      }).join('');

      // Signature Section on last page
      let signatureHtml = '';
      if (isLastPage) {
        signatureHtml = `
          <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #cbd5e1; font-size: 10.5px;">
            <div style="font-weight: bold; color: #0f172a; margin-bottom: 2px;">Training Curriculum Review &amp; Verification:</div>
            <div style="font-size: 10px; color: #64748b; margin-bottom: 12px;">This official document specifies the training course curriculum, passing scores, retrain intervals, and connected SOP documents.</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 11px; color: #0f172a; table-layout: fixed;">
              <tr>
                <td style="width: 50%; vertical-align: top;">Reviewed By (Training Manager): ___________________________ &nbsp;&nbsp; Date: ______________</td>
                <td style="width: 50%; vertical-align: top; text-align: right;">Authorized By (HR / Approver): ___________________________ &nbsp;&nbsp; Date: ______________</td>
              </tr>
            </table>
          </div>
        `;
      }

      // Running Footer on every page
      const footerHtml = `
        <div style="margin-top: 10px; padding-top: 6px; border-top: 1px solid #f1f5f9; font-size: 9.5px; color: #94a3b8; font-style: italic;">
          <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tr>
              <td>Training Master Management System &mdash; Training Catalog &amp; Connected Documents Report</td>
              <td style="text-align: right;">Page ${pageIdx + 1} of ${totalPages}</td>
            </tr>
          </table>
        </div>
      `;

      // Structure page content using a pure 2-row table (Top content & Bottom content)
      // This guarantees vertical spacing without ANY flexbox or flex-direction
      pageElement.innerHTML = `
        <table style="width: 100%; height: 1051px; border-collapse: collapse; border: none; table-layout: fixed;">
          <tr>
            <td style="vertical-align: top; padding: 0;">
              ${headerHtml}
              <div>${cardsHtml}</div>
            </td>
          </tr>
          <tr>
            <td style="vertical-align: bottom; height: 60px; padding: 0;">
              ${signatureHtml}
              ${footerHtml}
            </td>
          </tr>
        </table>
      `;

      renderContainer.innerHTML = '';
      renderContainer.appendChild(pageElement);

      const canvas = await html2canvas(pageElement, {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#ffffff',
        scrollX: 0,
        scrollY: 0,
        windowWidth: 794,
      });

      const imgData = canvas.toDataURL('image/jpeg', 0.98);
      if (pageIdx > 0) {
        doc.addPage();
      }
      doc.addImage(imgData, 'JPEG', 0, 0, 210, 297);
    }

    const filename = singleTraining
      ? `Training_Catalog_${singleTraining.code || 'Course'}_Documents_${new Date().toISOString().slice(0, 10)}.pdf`
      : `Training_Catalog_Documents_Report_${new Date().toISOString().slice(0, 10)}.pdf`;

    doc.save(filename);
    return { doc, filename };
  } finally {
    if (renderContainer && renderContainer.parentNode) {
      renderContainer.parentNode.removeChild(renderContainer);
    }
  }
}


