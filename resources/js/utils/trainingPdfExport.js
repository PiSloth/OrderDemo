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
 * Generates and downloads (or returns a Blob URL preview for) the Training Catalog & Curriculum Document Report.
 * Displays:
 *  - Training Catalog Title, Code & Category
 *  - Course Description & Objectives
 *  - Documents / SOPs contained under each training catalog title
 *  - Target Scopes (Departments & Positions)
 *  - Eligible workforce assignees table with review checkboxes & status
 *
 * @param {Object} options
 * @param {Array} [options.trainings=[]] List of training catalog objects
 * @param {Array} [options.activeUsers=[]] List of active employees for assignee scoping
 * @param {Object|null} [options.singleTraining=null] If set, exports/previews only this specific training
 * @param {Boolean} [options.returnBlobOnly=false] If true, returns { doc, blobUrl, filename } for in-browser review
 */
export async function exportTrainingCurriculumPdf({
  trainings = [],
  activeUsers = [],
  singleTraining = null,
  returnBlobOnly = false,
}) {
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  const fontName = await applyPyidaungsuFont(doc);

  const setFont = (style = 'normal', size = 9) => {
    try {
      doc.setFont(fontName, style);
    } catch (e) {
      doc.setFont('helvetica', style);
    }
    doc.setFontSize(size);
  };

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const margin = 14;
  let currentY = 16;

  // Modern corporate color palette
  const primaryNavy = [15, 23, 42]; // Slate-900
  const brandSky = [14, 116, 144]; // Sky-700
  const secondarySlate = [71, 85, 105]; // Slate-600
  const lightBg = [240, 249, 255]; // Sky-50
  const borderColor = [226, 232, 240]; // Slate-200

  const runAutoTable = (options) => {
    if (typeof autoTable === 'function') {
      autoTable(doc, options);
    } else if (typeof doc.autoTable === 'function') {
      doc.autoTable(options);
    }
  };

  const ensureSpace = (neededHeight) => {
    if (currentY + neededHeight > pageHeight - margin - 15) {
      doc.addPage();
      currentY = margin + 8;
      return true;
    }
    return false;
  };

  const trainingsList = singleTraining
    ? [singleTraining]
    : (trainings.length > 0 ? trainings : []);

  // 1. Header Banner
  setFont('bold', 15);
  doc.setTextColor(...primaryNavy);
  const mainTitle = singleTraining
    ? `Training Catalog Specification & Assignee Review`
    : `Training Catalog & Curriculum Document Report`;
  doc.text(mainTitle, margin, currentY);

  currentY += 5.5;
  setFont('normal', 8.5);
  doc.setTextColor(...secondarySlate);
  const subtitle = singleTraining
    ? `Curriculum Objectives, Connected Documents / SOPs & Audience Scope: [${singleTraining.code}] ${singleTraining.title}`
    : `Comprehensive Catalog Overview: Descriptions, Connected Documents / SOPs & Eligible Assignees`;
  doc.text(subtitle, margin, currentY);

  currentY += 4.5;
  const nowStr = new Date().toLocaleString();
  doc.text(`Generated on: ${nowStr} • Training Master Management System`, margin, currentY);

  currentY += 7;

  // 2. Summary KPI Box
  const totalCatalogs = trainingsList.length;
  let totalDocsCount = 0;
  trainingsList.forEach((t) => {
    const docs = t.company_documents || t.companyDocuments || [];
    totalDocsCount += docs.length;
  });

  doc.setFillColor(...lightBg);
  doc.setDrawColor(186, 230, 253);
  doc.roundedRect(margin, currentY, pageWidth - margin * 2, 13, 2, 2, 'FD');

  setFont('bold', 8.5);
  doc.setTextColor(...brandSky);
  const kpiText = singleTraining
    ? `Course Code: ${singleTraining.code}   |   Status: ${(singleTraining.status || 'ACTIVE').toUpperCase()}   |   Connected SOPs / Documents: ${totalDocsCount}`
    : `Total Training Programs: ${totalCatalogs}   |   Total Connected Documents: ${totalDocsCount}   |   Workforce Database: ${activeUsers.length} Active Employees`;
  doc.text(kpiText, margin + 4, currentY + 8);

  currentY += 18;

  if (trainingsList.length === 0) {
    setFont('normal', 10);
    doc.setTextColor(...secondarySlate);
    doc.text('No training catalog records available for export.', margin, currentY);
  } else {
    trainingsList.forEach((t, tIdx) => {
      // Check page break before starting each training catalog block
      if (tIdx > 0) {
        ensureSpace(40);
      }

      // Catalog Header Bar
      const startCardY = currentY;
      doc.setFillColor(248, 250, 252); // Slate-50
      doc.setDrawColor(203, 213, 225); // Slate-300
      doc.roundedRect(margin, startCardY, pageWidth - margin * 2, 11, 1.5, 1.5, 'FD');

      // Left Accent Strip
      doc.setFillColor(...brandSky);
      doc.rect(margin, startCardY, 3, 11, 'F');

      setFont('bold', 9.5);
      doc.setTextColor(...primaryNavy);
      const titleLine = `${tIdx + 1}. [${t.code || 'N/A'}] ${t.title || 'Untitled Training'}`;
      doc.text(titleLine, margin + 5, startCardY + 7);

      // Meta pills on right of header
      setFont('normal', 7.5);
      doc.setTextColor(...secondarySlate);
      const metaRight = `Pass: ${t.passing_score || 80}% • Retrain: Every ${t.retrain_interval || 12} ${t.retrain_unit || 'month'}(s)`;
      doc.text(metaRight, pageWidth - margin - 4, startCardY + 7, { align: 'right' });

      currentY += 15;

      // Category & Details row
      setFont('bold', 8);
      doc.setTextColor(...brandSky);
      doc.text('Category: ', margin + 2, currentY);
      setFont('normal', 8);
      doc.setTextColor(...primaryNavy);
      const categoryName = t.category?.name || 'General';
      doc.text(categoryName, margin + 17, currentY);

      if (t.duration_days) {
        setFont('bold', 8);
        doc.setTextColor(...brandSky);
        doc.text('Duration: ', margin + 70, currentY);
        setFont('normal', 8);
        doc.setTextColor(...primaryNavy);
        doc.text(`${t.duration_days} Day(s)`, margin + 84, currentY);
      }

      currentY += 5;

      // Description Section
      setFont('bold', 8);
      doc.setTextColor(...secondarySlate);
      doc.text('Description / Course Objectives:', margin + 2, currentY);
      currentY += 4;

      setFont('normal', 7.8);
      doc.setTextColor(30, 41, 59);
      const descText = t.description ? t.description.trim() : 'No detailed description provided for this training catalog.';
      const descLines = doc.splitTextToSize(descText, pageWidth - margin * 2 - 4);
      doc.text(descLines, margin + 2, currentY);
      currentY += descLines.length * 3.8 + 4;

      // Connected Documents Table ("which document contain under each title")
      const docs = t.company_documents || t.companyDocuments || [];

      const docRows = [];
      if (docs.length === 0) {
        docRows.push([
          {
            content: 'No company SOPs or reference documents are currently linked to this training catalog.',
            colSpan: 4,
            styles: { halign: 'center', fontSize: 7.5, textColor: [148, 163, 184], fontStyle: 'italic' },
          },
        ]);
      } else {
        docs.forEach((d, dIdx) => {
          docRows.push([
            `${dIdx + 1}`,
            d.title || 'Untitled Document',
            d.type?.name || 'SOP / Procedure',
            d.department?.name || 'Company Wide',
          ]);
        });
      }

      runAutoTable({
        startY: currentY,
        head: [
          [
            '#',
            `Connected Documents & SOPs Contained Under This Title (${docs.length})`,
            'Document Type',
            'Department',
          ],
        ],
        body: docRows,
        theme: 'grid',
        styles: {
          font: fontName,
          fontSize: 7.5,
          cellPadding: 2.2,
          valign: 'middle',
          lineColor: borderColor,
          lineWidth: 0.15,
        },
        headStyles: {
          fillColor: [30, 41, 59], // Slate-800
          textColor: [255, 255, 255],
          fontStyle: 'bold',
          fontSize: 7.5,
          halign: 'left',
        },
        columnStyles: {
          0: { cellWidth: 8, halign: 'center' },
          1: { cellWidth: 'auto', fontStyle: 'bold' },
          2: { cellWidth: 38 },
          3: { cellWidth: 38 },
        },
        margin: { left: margin, right: margin, bottom: 16 },
      });

      currentY = (doc.lastAutoTable?.finalY || currentY) + 5;

      // Scopes & Eligible Assignees Review Section
      const scopes = t.scopes || [];
      const matchingEmployees = activeUsers.filter((u) => {
        if (scopes.length === 0) return false;
        return scopes.some((s) => {
          if (s.department_id && s.department_id !== u.department_id) return false;
          if (s.office_position_id && s.office_position_id !== u.office_position_id) return false;
          return true;
        });
      });

      const assigneeRows = [];
      if (scopes.length === 0) {
        assigneeRows.push([
          {
            content: 'No target scopes configured. This training can be manually assigned to any employee as needed.',
            colSpan: 5,
            styles: { halign: 'center', fontSize: 7.5, textColor: [148, 163, 184], fontStyle: 'italic' },
          },
        ]);
      } else if (matchingEmployees.length === 0) {
        assigneeRows.push([
          {
            content: 'No active workforce employees currently match the configured target scope combinations.',
            colSpan: 5,
            styles: { halign: 'center', fontSize: 7.5, textColor: [217, 119, 6], fontStyle: 'italic' },
          },
        ]);
      } else {
        matchingEmployees.forEach((u, uIdx) => {
          const userAssignments = u.training_assignments || u.trainingAssignments || [];
          const existingAssign = userAssignments.find(
            (a) => (a.training_id || a.training?.id) === t.id
          );
          const statusText = existingAssign
            ? (existingAssign.status || 'ASSIGNED').toUpperCase()
            : 'Eligible (Not Assigned)';

          assigneeRows.push([
            '[  ]', // Review & choose checkbox
            `${uIdx + 1}`,
            `${u.name}\n${u.email}`,
            `${u.department?.name || 'Unassigned'} • ${u.officePosition?.name || u.office_position?.name || 'Staff'}`,
            statusText,
          ]);
        });
      }

      runAutoTable({
        startY: currentY,
        head: [
          [
            'Pick',
            '#',
            `Target Workforce Assignee Candidates (${matchingEmployees.length} Eligible)`,
            'Department & Office Position',
            'Assignment Status',
          ],
        ],
        body: assigneeRows,
        theme: 'grid',
        styles: {
          font: fontName,
          fontSize: 7.2,
          cellPadding: 2.2,
          valign: 'middle',
          lineColor: borderColor,
          lineWidth: 0.15,
        },
        headStyles: {
          fillColor: [14, 116, 144], // Sky-700
          textColor: [255, 255, 255],
          fontStyle: 'bold',
          fontSize: 7.5,
          halign: 'left',
        },
        columnStyles: {
          0: { cellWidth: 10, halign: 'center', fontStyle: 'bold' },
          1: { cellWidth: 8, halign: 'center' },
          2: { cellWidth: 62, fontStyle: 'bold' },
          3: { cellWidth: 60 },
          4: { cellWidth: 'auto', halign: 'center' },
        },
        margin: { left: margin, right: margin, bottom: 16 },
      });

      currentY = (doc.lastAutoTable?.finalY || currentY) + 8;
    });

    // Sign-off / Reviewer Verification Section
    ensureSpace(25);
    setFont('bold', 8);
    doc.setTextColor(...primaryNavy);
    doc.text('Training Review & Assignee Authorization:', margin, currentY);

    setFont('normal', 7.5);
    doc.setTextColor(...secondarySlate);
    doc.text('This document serves as the curriculum syllabus and target assignee review for company compliance auditing.', margin, currentY + 4);

    currentY += 14;
    setFont('normal', 8);
    doc.setTextColor(...primaryNavy);
    doc.text('Reviewed By (Training Manager): ___________________________', margin, currentY);
    doc.text('Date: ______________', margin + 85, currentY);

    doc.text('Authorized By (HR / Approver): ___________________________', pageWidth - margin - 85, currentY);
    doc.text('Date: ______________', pageWidth - margin - 22, currentY);
  }

  // Footer: Page numbering
  const totalPages = typeof doc.getNumberOfPages === 'function'
    ? doc.getNumberOfPages()
    : (doc.internal?.getNumberOfPages ? doc.internal.getNumberOfPages() : 1);

  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    setFont('normal', 7.5);
    doc.setTextColor(148, 163, 184);
    const footerLabel = singleTraining
      ? `Page ${i} of ${totalPages} • Training Course Review: [${singleTraining.code}] ${singleTraining.title}`
      : `Page ${i} of ${totalPages} • Training Catalog Curriculum & Document Report`;
    doc.text(footerLabel, pageWidth / 2, pageHeight - 6, { align: 'center' });
  }

  const filename = singleTraining
    ? `Training_Catalog_${singleTraining.code}_Curriculum_${new Date().toISOString().slice(0, 10)}.pdf`
    : `Training_Catalog_Curriculum_Report_${new Date().toISOString().slice(0, 10)}.pdf`;

  if (returnBlobOnly) {
    const pdfBlob = doc.output('blob');
    const blobUrl = URL.createObjectURL(pdfBlob);
    return {
      doc,
      blobUrl,
      filename,
      totalPages,
      totalCatalogs,
    };
  }

  doc.save(filename);
  return { doc, filename };
}


