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
  doc.setFillColor(...lightBg);
  doc.setDrawColor(186, 230, 253);
  doc.roundedRect(margin, currentY, pageWidth - margin * 2, 28, 2, 2, 'FD');

  const trainingTitle = session.training?.title || session.title || 'Training Program';
  const trainingCode = session.training?.code || '';
  const trainerName = session.trainer?.name || 'To Be Assigned';
  const venue = session.venue || 'Training Room / Meeting Hall';
  const meetingLink = session.meeting_link ? `Online: ${session.meeting_link}` : '';

  const durationDays = session.duration_days || session.training?.duration_days || 1;
  const startDate = session.start_date || (session.scheduled_at ? session.scheduled_at.substring(0, 10) : 'TBD');
  const endDate = session.end_date || startDate;
  const scheduleText = `${startDate} ${durationDays > 1 ? `to ${endDate} (${durationDays} Days Duration)` : '(1 Day)'}`;

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
  doc.setFontSize(8.5);
  doc.setTextColor(...secondaryColor);

  doc.text(`• Schedule: ${scheduleText}`, margin + 4, currentY + 13);
  doc.text(`• Trainer / Instructor: ${trainerName}`, margin + 4, currentY + 18);
  doc.text(`• Location / Venue: ${venue} ${meetingLink ? `| ${meetingLink}` : ''}`, margin + 4, currentY + 23);

  currentY += 34;

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
    dynamicHeaders.push(`Day ${d + 1}\n(${sessionDates[d]})`);
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

