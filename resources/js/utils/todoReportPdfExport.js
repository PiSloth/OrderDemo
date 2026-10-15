import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { applyPyidaungsuFont } from './pdfFont';

/**
 * Strips HTML tags if text contains markup, preserving plain text.
 * Also strips emoji/symbols that vector PDF fonts cannot render
 * (these caused missing-glyph boxes in jsPDF output).
 */
const cleanText = (text) => {
    if (!text) return '';
    let s = typeof text !== 'string' ? String(text) : text;
    if (/<[a-z][\s\S]*>/i.test(s)) {
        const div = document.createElement('div');
        div.innerHTML = s;
        s = (div.textContent || div.innerText || '').trim();
    }
    // Remove emoji / pictographs / symbols that Pyidaungsu/Helvetica lack.
    // Keep Myanmar, Latin, digits and common punctuation.
    return s
        .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}\u{200D}]/gu, '')
        .replace(/\s+/g, ' ')
        .trim();
};

/**
 * Format datetime into readable string
 */
const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return String(dateStr).replace('T', ' ').slice(0, 16);
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return String(dateStr);
    }
};

/**
 * Export Todo Report PDF (vector version).
 *
 * Previous implementation rasterized an off-screen DOM tree with html2canvas
 * 1.4.1, which does not support CSS grid + gap, letter-spacing, gradients or
 * flex space-between reliably. The result was the ghosted / double-struck
 * text visible in the exported report.
 *
 * This rewrite draws the report natively with jsPDF + jspdf-autotable
 * (same proven pattern as itSatisfactionPdfExport.js) with the embedded
 * Pyidaungsu font, so every glyph is drawn exactly once as selectable text,
 * including Myanmar script.
 */
export const exportTodoReportPDF = async ({
    tasks = [],
    groups = [],
    categories = [],
    priorities = [],
    categoryMatrix = null,
    metrics = {},
    filters = {},
    authUser = {},
    appName = 'OrderDemo',
}) => {
    const {
        selectedMonth = 'all',
        selectedMonthLabel = 'All Months',
        selectedDepartmentId = 'all',
        selectedDepartmentName = 'All Departments',
    } = filters;

    const {
        totalTasks = 0,
        completedTasks = 0,
        openTasks = 0,
        overdueTasks = 0,
        successRate = '0.0',
        overdueRate = '0.0',
    } = metrics;

    const exporterName = authUser?.name || 'Authorized User';
    const exporterDept = authUser?.department?.name || authUser?.department || '';
    const fullExporterText = exporterDept ? `${exporterName} (${exporterDept})` : exporterName;
    const todayDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const generatedAt = new Date().toLocaleString();

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // Embed Pyidaungsu so Myanmar category / priority names print correctly.
    let fontName = 'helvetica';
    try {
        fontName = await applyPyidaungsuFont(doc);
    } catch (e) {
        console.warn('Pyidaungsu font load error, using helvetica fallback:', e);
        fontName = 'helvetica';
    }

    const setFont = (style) => {
        try {
            doc.setFont(fontName, style);
        } catch (e) {
            doc.setFont('helvetica', style);
        }
    };

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 12;
    const contentWidth = pageWidth - margin * 2;
    let currentY = 10;

    const NAVY = [30, 27, 75];
    const INDIGO = [67, 56, 202];
    const DARK = [15, 23, 42];
    const MUTED = [100, 116, 139];
    const LIGHT_BG = [248, 250, 252];
    const BORDER = [203, 213, 225];
    const GREEN = [4, 120, 87];
    const RED = [185, 28, 28];
    const AMBER = [180, 83, 9];

    const isDoneStatus = (statusName) => {
        const s = (statusName || '').toLowerCase();
        return s.includes('complete') || s.includes('success') || s.includes('done');
    };
    const slaLabel = (t) => {
        if (isDoneStatus(t.status?.status)) return 'Success';
        if (t.due_date && new Date(t.due_date) < new Date()) return 'Overdue';
        return 'In Progress';
    };

    // ------------------------------------------------------------------
    // 1. Header banner (solid fill — no gradient, drawn once, no ghosting)
    // ------------------------------------------------------------------
    const headerH = 20;
    doc.setFillColor(...NAVY);
    doc.roundedRect(margin, currentY, contentWidth, headerH, 2, 2, 'F');

    setFont('bold');
    doc.setFontSize(13);
    doc.setTextColor(255, 255, 255);
    doc.text('TODO OPERATIONS & SLA PERFORMANCE REPORT', margin + 5, currentY + 8);

    setFont('normal');
    doc.setFontSize(8);
    doc.setTextColor(199, 210, 254);
    doc.text(`${appName}  |  Task Execution, Due Time Grouping & SLA Compliance Audit`, margin + 5, currentY + 13.5);

    setFont('normal');
    doc.setFontSize(7.5);
    doc.setTextColor(226, 232, 240);
    doc.text(`Generated: ${generatedAt}`, pageWidth - margin - 5, currentY + 8, { align: 'right' });
    doc.text(`Report By: ${cleanText(fullExporterText)}`, pageWidth - margin - 5, currentY + 13.5, { align: 'right' });

    currentY += headerH + 3;

    // ------------------------------------------------------------------
    // 2. Filter metadata bar
    // ------------------------------------------------------------------
    const metaH = 10;
    doc.setFillColor(...LIGHT_BG);
    doc.setDrawColor(...BORDER);
    doc.roundedRect(margin, currentY, contentWidth, metaH, 1.5, 1.5, 'FD');

    const metaItems = [
        ['Report Month: ', selectedMonthLabel],
        ['Department Scope: ', selectedDepartmentName],
        ['Total Scope: ', `${totalTasks} Tasks Analyzed`],
        ['Overall Success Rate: ', `${successRate}%`],
        ['Overdue (Not Success): ', `${overdueTasks} (${overdueRate}%)`],
    ];
    const colW = contentWidth / metaItems.length;
    doc.setFontSize(7.5);
    metaItems.forEach(([label, value], i) => {
        const x = margin + 4 + i * colW;
        setFont('normal');
        doc.setTextColor(...MUTED);
        doc.text(label, x, currentY + 6.2);
        const labelW = doc.getTextWidth(label);
        setFont('bold');
        doc.setTextColor(...(i === 3 ? GREEN : i === 4 ? RED : NAVY));
        doc.text(cleanText(value), x + labelW, currentY + 6.2);
    });

    currentY += metaH + 4;

    // ------------------------------------------------------------------
    // 3. KPI summary cards (6 equal boxes, plain rects)
    // ------------------------------------------------------------------
    const cards = [
        { label: 'TOTAL TASKS', value: String(totalTasks), sub: 'Filtered volume', bg: [255, 255, 255], border: BORDER, labelC: MUTED, valueC: DARK, subC: [148, 163, 184] },
        { label: 'SUCCESS (CLOSED)', value: String(completedTasks), sub: 'Completed tasks', bg: [236, 253, 245], border: [167, 243, 208], labelC: GREEN, valueC: GREEN, subC: [5, 150, 105] },
        { label: 'PENDING / IN PROGRESS', value: String(openTasks), sub: 'Awaiting completion', bg: [255, 251, 235], border: [253, 230, 138], labelC: AMBER, valueC: [217, 119, 6], subC: AMBER },
        { label: 'OVERDUE (NOT SUCCESS)', value: String(overdueTasks), sub: 'Missed due cutoff', bg: [254, 242, 242], border: [254, 202, 202], labelC: RED, valueC: [220, 38, 38], subC: RED },
        { label: 'OVERALL SUCCESS RATE', value: `${successRate}%`, sub: 'Completed vs Total', bg: GREEN, border: GREEN, labelC: [255, 255, 255], valueC: [255, 255, 255], subC: [209, 250, 229] },
        { label: 'OVERDUE TASK RATE', value: `${overdueRate}%`, sub: 'SLA breach ratio', bg: [190, 18, 60], border: [190, 18, 60], labelC: [255, 255, 255], valueC: [255, 255, 255], subC: [255, 228, 230] },
    ];
    const gap = 3;
    const cardW = (contentWidth - gap * (cards.length - 1)) / cards.length;
    const cardH = 20;
    cards.forEach((c, i) => {
        const x = margin + i * (cardW + gap);
        doc.setFillColor(...c.bg);
        doc.setDrawColor(...c.border);
        doc.roundedRect(x, currentY, cardW, cardH, 1.5, 1.5, 'FD');
        setFont('bold');
        doc.setFontSize(6.5);
        doc.setTextColor(...c.labelC);
        doc.text(c.label, x + cardW / 2, currentY + 5, { align: 'center' });
        doc.setFontSize(13);
        doc.setTextColor(...c.valueC);
        doc.text(c.value, x + cardW / 2, currentY + 12.5, { align: 'center' });
        setFont('normal');
        doc.setFontSize(6.5);
        doc.setTextColor(...c.subC);
        doc.text(c.sub, x + cardW / 2, currentY + 16.5, { align: 'center' });
    });

    currentY += cardH + 5;

    // ------------------------------------------------------------------
    // 4. Category x Priority matrix (autoTable handles pagination)
    // ------------------------------------------------------------------
    let priorityCols = priorities && priorities.length > 0 ? priorities : [];
    let matrixData = categoryMatrix;

    if (!matrixData) {
        const catMap = {};
        (categories || []).forEach((c) => {
            catMap[String(c.id)] = { id: c.id, name: c.name, totalTasks: 0, completedTasks: 0, priorityCounts: {} };
        });
        const uncategorized = { id: 'uncategorized', name: 'General / Uncategorized', totalTasks: 0, completedTasks: 0, priorityCounts: {} };
        tasks.forEach((t) => {
            const catId = t.due_time?.todo_category_id || t.todo_category_id;
            const targetCat = catId && catMap[String(catId)] ? catMap[String(catId)] : uncategorized;
            const prioId = t.due_time?.todo_priority_id || t.todo_priority_id || 'unknown';
            const done = isDoneStatus(t.status?.status);
            if (!targetCat.priorityCounts[String(prioId)]) targetCat.priorityCounts[String(prioId)] = { total: 0, completed: 0 };
            targetCat.priorityCounts[String(prioId)].total += 1;
            if (done) {
                targetCat.priorityCounts[String(prioId)].completed += 1;
                targetCat.completedTasks += 1;
            }
            targetCat.totalTasks += 1;
        });
        const list = Object.values(catMap);
        if (uncategorized.totalTasks > 0) list.push(uncategorized);
        const activeRows = list.filter((r) => r.totalTasks > 0);
        activeRows.sort((a, b) => b.totalTasks - a.totalTasks || a.name.localeCompare(b.name));
        const columnTotals = {};
        priorityCols.forEach((p) => { columnTotals[String(p.id)] = 0; });
        let grandTotal = 0;
        let grandCompleted = 0;
        activeRows.forEach((row) => {
            grandTotal += row.totalTasks;
            grandCompleted += row.completedTasks;
            priorityCols.forEach((p) => {
                columnTotals[String(p.id)] = (columnTotals[String(p.id)] || 0) + (row.priorityCounts[String(p.id)]?.total || 0);
            });
        });
        matrixData = {
            rows: activeRows,
            columnTotals,
            grandTotal,
            grandCompleted,
            overallSuccessRate: grandTotal > 0 ? ((grandCompleted / grandTotal) * 100).toFixed(1) : '0.0',
        };
    }

    if (currentY > pageHeight - 60) {
        doc.addPage();
        currentY = 12;
    }

    setFont('bold');
    doc.setFontSize(10);
    doc.setTextColor(...DARK);
    doc.text('Category by Priority & Success Rate', margin, currentY);
    setFont('normal');
    doc.setFontSize(7.5);
    doc.setTextColor(...MUTED);
    doc.text('Performance cross-tabulation across priority levels and completion success rate', pageWidth - margin, currentY, { align: 'right' });
    currentY += 4;

    const matrixHead = ['Category', ...priorityCols.map((p) => cleanText(p.level)), 'Success Rate'];
    const matrixBody = (matrixData.rows || []).map((row) => {
        const rate = row.totalTasks > 0 ? ((row.completedTasks / row.totalTasks) * 100).toFixed(1) : '0.0';
        return [
            cleanText(row.name),
            ...priorityCols.map((p) => {
                const count = row.priorityCounts[String(p.id)]?.total || 0;
                return count > 0 ? String(count) : '-';
            }),
            `${row.completedTasks}/${row.totalTasks} (${rate}%)`,
        ];
    });
    const matrixFoot = [[
        'TOTAL',
        ...priorityCols.map((p) => String(matrixData.columnTotals[String(p.id)] || 0)),
        `${matrixData.grandCompleted}/${matrixData.grandTotal} (${matrixData.overallSuccessRate}%)`,
    ]];

    autoTable(doc, {
        startY: currentY,
        head: [matrixHead],
        body: matrixBody.length > 0 ? matrixBody : [['No active categorized tasks found.', ...priorityCols.map(() => ''), '']],
        foot: matrixFoot,
        margin: { left: margin, right: margin },
        styles: { font: fontName, fontSize: 8, cellPadding: 2, lineColor: BORDER, lineWidth: 0.2, textColor: DARK },
        headStyles: { fillColor: NAVY, textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 8.5 },
        footStyles: { fillColor: [241, 245, 249], textColor: DARK, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: LIGHT_BG },
        columnStyles: {
            0: { fontStyle: 'bold', cellWidth: 55 },
            [matrixHead.length - 1]: { halign: 'center', fontStyle: 'bold', textColor: GREEN },
        },
        didParseCell: (data) => {
            if (data.section === 'body' && data.column.index > 0 && data.column.index < matrixHead.length - 1) {
                data.cell.styles.halign = 'center';
            }
        },
    });

    currentY = doc.lastAutoTable.finalY + 6;

    // ------------------------------------------------------------------
    // 5. Task detail groups (header banner + table per group)
    // ------------------------------------------------------------------
    const detailGroups = (groups || [])
        .map((g) => ({ ...g, list: g.tasks || [] }))
        .filter((g) => g.list.length > 0);

    for (const g of detailGroups) {
        if (currentY > pageHeight - 45) {
            doc.addPage();
            currentY = 12;
        }

        const rateNum = parseFloat(g.successRate || 0);
        const rateColor = rateNum >= 80 ? GREEN : rateNum >= 50 ? [217, 119, 6] : RED;
        const bannerH = 9;

        doc.setFillColor(...LIGHT_BG);
        doc.setDrawColor(...BORDER);
        doc.roundedRect(margin, currentY, contentWidth, bannerH, 1, 1, 'FD');

        setFont('bold');
        doc.setFontSize(8.5);
        doc.setTextColor(...DARK);
        const leftTitle = cleanText(g.categoryName || g.title);
        const midTitle = `${g.duration ? `${g.duration} Hours` : 'Standard'}  |  ${cleanText(g.priorityLevel || 'Normal')}`;
        doc.text(leftTitle, margin + 4, currentY + 5.8);
        setFont('normal');
        doc.setFontSize(7.5);
        doc.setTextColor(...INDIGO);
        doc.text(midTitle, margin + 4 + doc.getTextWidth(`${leftTitle}  `), currentY + 5.8);

        setFont('normal');
        doc.setFontSize(7.5);
        doc.setTextColor(...MUTED);
        const rightStats = `Total: ${g.total}  |  Success: ${g.completed}  |  Overdue: ${g.overdue}  |  Success Rate: ${g.successRate}%`;
        doc.text(rightStats, pageWidth - margin - 4, currentY + 5.8, { align: 'right' });
        // Paint the rate value in its color (drawn over the tail of rightStats).
        setFont('bold');
        doc.setTextColor(...rateColor);
        doc.text(`${g.successRate}%`, pageWidth - margin - 4, currentY + 5.8, { align: 'right' });

        currentY += bannerH;

        const body = g.list.map((t, idx) => {
            const deptName = t.assigned_user?.department?.name || t.department?.name || 'N/A';
            return [
                String(idx + 1),
                cleanText(t.task),
                `${cleanText(t.assigned_user?.name || 'Unassigned')}\n${cleanText(deptName)}`,
                formatDateTime(t.due_date),
                cleanText(t.status?.status || 'Open'),
                slaLabel(t),
            ];
        });

        autoTable(doc, {
            startY: currentY,
            head: [['#', 'Task Title & Details', 'Assignee & Department', 'Due Date', 'Status', 'SLA Compliance']],
            body,
            margin: { left: margin, right: margin },
            styles: { font: fontName, fontSize: 7.5, cellPadding: 2, lineColor: [226, 232, 240], lineWidth: 0.2, textColor: DARK, valign: 'middle' },
            headStyles: { fillColor: [30, 41, 59], textColor: [255, 255, 255], fontStyle: 'bold', fontSize: 8 },
            alternateRowStyles: { fillColor: LIGHT_BG },
            columnStyles: {
                0: { halign: 'center', cellWidth: 10 },
                1: { cellWidth: 95 },
                2: { cellWidth: 52 },
                3: { halign: 'center', cellWidth: 38 },
                4: { halign: 'center', cellWidth: 32 },
                5: { halign: 'center', cellWidth: 32 },
            },
            didParseCell: (data) => {
                if (data.section !== 'body') return;
                const sla = slaLabel(g.list[data.row.index]);
                if (data.column.index === 5) {
                    data.cell.styles.fontStyle = 'bold';
                    data.cell.styles.textColor = sla === 'Success' ? GREEN : sla === 'Overdue' ? RED : AMBER;
                }
                if (data.column.index === 4 && isDoneStatus(g.list[data.row.index]?.status?.status)) {
                    data.cell.styles.textColor = GREEN;
                    data.cell.styles.fontStyle = 'bold';
                }
            },
        });

        currentY = doc.lastAutoTable.finalY + 5;
    }

    // ------------------------------------------------------------------
    // 6. Signature block (last page; new page if no room)
    // ------------------------------------------------------------------
    const sigH = 26;
    if (currentY + sigH + 8 > pageHeight - 10) {
        doc.addPage();
        currentY = 14;
    } else {
        currentY += 2;
    }

    doc.setDrawColor(...BORDER);
    doc.line(margin, currentY, pageWidth - margin, currentY);
    currentY += 4;

    setFont('bold');
    doc.setFontSize(8);
    doc.setTextColor(...DARK);
    doc.text('Report Prepared & Exported By:', margin, currentY);
    doc.text('Management / Head of Dept Acknowledged:', margin + contentWidth / 2 + 5, currentY);
    currentY += 5;

    const sigColW = contentWidth / 2 - 5;
    const sigRows = [
        ['Name:', cleanText(fullExporterText), 'Name:', ''],
        ['Signature:', '', 'Signature:', ''],
        ['Date:', todayDate, 'Date:', ''],
    ];
    sigRows.forEach(([l1, v1, l2, v2]) => {
        setFont('normal');
        doc.setFontSize(7.5);
        doc.setTextColor(...MUTED);
        doc.text(l1, margin, currentY);
        setFont('bold');
        doc.setTextColor(...DARK);
        doc.text(v1 || '', margin + 18, currentY);
        doc.setDrawColor(148, 163, 184);
        doc.line(margin + 18, currentY + 1.2, margin + sigColW, currentY + 1.2);

        const x2 = margin + contentWidth / 2 + 5;
        setFont('normal');
        doc.setTextColor(...MUTED);
        doc.text(l2, x2, currentY);
        doc.line(x2 + 18, currentY + 1.2, x2 + sigColW, currentY + 1.2);
        if (v2) {
            setFont('bold');
            doc.setTextColor(...DARK);
            doc.text(v2, x2 + 18, currentY);
        }
        currentY += 6;
    });

    // ------------------------------------------------------------------
    // 7. Footer page numbers on every page
    // ------------------------------------------------------------------
    const totalPages = doc.internal.getNumberOfPages();
    for (let p = 1; p <= totalPages; p++) {
        doc.setPage(p);
        setFont('normal');
        doc.setFontSize(7);
        doc.setTextColor(148, 163, 184);
        doc.text(
            `${appName} - Todo Task Operations & Due Time Group Performance Report - ${selectedMonthLabel}`,
            margin,
            pageHeight - 7
        );
        doc.text(`Page ${p} of ${totalPages}`, pageWidth - margin, pageHeight - 7, { align: 'right' });
    }

    const safeMonth = String(selectedMonth).replace(/[^a-zA-Z0-9_-]/g, '_');
    const filename = `Todo_Report_${safeMonth}_${new Date().toISOString().slice(0, 10)}.pdf`;
    doc.save(filename);
};
