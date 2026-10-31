import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { applyPyidaungsuFont } from './pdfFont';

/**
 * Preload the Pyidaungsu WebFont in browser FontFaceSet if available
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
 * Detect Myanmar or complex Unicode text
 */
const hasComplexUnicode = (text) => /[^\x00-\x7F]/.test(String(text || ''));

/**
 * Tokenize Myanmar text for line wrapping
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
            // fallback below
        }
    }
    return paragraph
        .split(/( +|(?=[\u1000-\u1021\u1023-\u102A\u103F\u1040-\u104F])(?<![\u1031\u1039\u103A]))/g)
        .filter(Boolean);
};

/**
 * Render non-ASCII text block to offscreen PNG canvas at 4x DPI
 */
const renderUnicodeTextBlockToPng = (
    text,
    {
        fontSizePt = 8,
        maxWidthMm = 40,
        color = '#0f172a',
        fontWeight = 'normal',
        lineHeightMultiplier = 1.6,
    } = {}
) => {
    const PX_PER_MM = 96 / 25.4;
    const MM_PER_PX = 25.4 / 96;
    const SCALE = 4; // 384 DPI print quality

    const fontSizePx = fontSizePt * (96 / 72);
    const maxWidthPx = Math.max(20, maxWidthMm * PX_PER_MM);
    const fontSpec = `${fontWeight} ${fontSizePx}px 'Pyidaungsu', 'Padauk', 'Myanmar Text', 'Segoe UI', Arial, sans-serif`;

    const measureCanvas = document.createElement('canvas');
    const measureCtx = measureCanvas.getContext('2d');
    measureCtx.font = fontSpec;

    const paragraphs = String(text || '').split('\n');
    const wrappedLines = [];

    for (const para of paragraphs) {
        const trimmed = para.trim();
        if (!trimmed) {
            wrappedLines.push('');
            continue;
        }

        const spaceTokens = trimmed.split(/( +)/).filter(Boolean);
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

    if (wrappedLines.length === 0) wrappedLines.push('');

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
 * Format date string
 */
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return String(dateStr);
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return String(dateStr);
    }
};

/**
 * EXPORT REPORT 1: PIC Access Report (Platform accounts held by a specific PIC person)
 */
export async function exportPicAccessReportPdf({
    user,
    records = [],
    authUser = {},
    companyName = 'ShweTatar Gold & Jewellery'
}) {
    if (!user) return;
    await ensurePyidaungsuFontLoaded();

    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4',
    });

    const fontName = await applyPyidaungsuFont(doc);
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 14;
    let currentY = 16;

    // Header Colors
    const primaryColor = [20, 60, 120];
    const textColor = [30, 41, 59];

    // 1. Company & Document Header
    doc.setFont(fontName, 'bold');
    doc.setFontSize(16);
    doc.setTextColor(...primaryColor);
    doc.text(companyName, margin, currentY);

    currentY += 6;
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text('Person in Charge (PIC) - Platform Accounts & Access Report', margin, currentY);

    currentY += 5;
    doc.setFont(fontName, 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(100, 116, 139);
    const nowStr = new Date().toLocaleString('en-GB');
    doc.text(`Official Access Authorization & Custodianship Audit • Generated: ${nowStr}`, margin, currentY);

    currentY += 7;

    // 2. Summary Info Box
    const activeCount = records.filter(r => r.status === 'active').length;
    const inactiveCount = records.filter(r => r.status !== 'active').length;

    doc.setFillColor(248, 250, 252);
    doc.setDrawColor(226, 232, 240);
    doc.roundedRect(margin, currentY, pageWidth - margin * 2, 18, 2, 2, 'FD');

    doc.setFont(fontName, 'bold');
    doc.setFontSize(9.5);
    doc.setTextColor(15, 23, 42);
    doc.text(`Person in Charge: ${user.name || 'Unspecified'}`, margin + 5, currentY + 6.5);

    doc.setFont(fontName, 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(71, 85, 105);
    doc.text(`Email Address: ${user.email || '-'}`, margin + 5, currentY + 12.5);

    const statsX = margin + 120;
    doc.setFont(fontName, 'bold');
    doc.text(`Total Assigned: ${records.length}`, statsX, currentY + 6.5);
    doc.setTextColor(22, 101, 52); // green
    doc.text(`Active Access: ${activeCount}`, statsX + 50, currentY + 6.5);
    doc.setTextColor(185, 28, 28); // red
    doc.text(`Revoked / Inactive: ${inactiveCount}`, statsX + 95, currentY + 6.5);

    currentY += 24;

    // 3. Table of Accounts Held
    const tableHeaders = [
        '#',
        'Platform',
        'Account Name',
        'Login Identity / Username',
        'Account Status',
        'PIC Status',
        'Assigned Date',
        'Revoked Date & Reason'
    ];

    const tableRows = records.map((rec, index) => {
        const platformName = rec.account?.platform?.name || '-';
        const accountName = rec.account?.account_name || '-';
        const username = rec.account?.username || '-';
        const accountLifecycle = (rec.account?.lifecycle_status || 'active').toUpperCase();
        const picStatus = (rec.status || 'active').toUpperCase();
        const assignedDate = formatDate(rec.assigned_at);
        let revokedInfo = '-';
        if (rec.status !== 'active' && (rec.revoked_at || rec.revocation_reason)) {
            const dateStr = rec.revoked_at ? formatDate(rec.revoked_at) : '';
            const reasonStr = rec.revocation_reason ? `(${rec.revocation_reason})` : '';
            revokedInfo = [dateStr, reasonStr].filter(Boolean).join(' ');
        }

        return [
            index + 1,
            platformName,
            accountName,
            username,
            accountLifecycle,
            picStatus,
            assignedDate,
            revokedInfo
        ];
    });

    const columnWidths = [10, 40, 50, 50, 26, 26, 28, 39];

    autoTable(doc, {
        startY: currentY,
        head: [tableHeaders],
        body: tableRows,
        margin: { left: margin, right: margin, bottom: 15 },
        theme: 'grid',
        styles: {
            font: fontName,
            fontSize: 8.5,
            cellPadding: 2.5,
            textColor: [30, 41, 59],
            lineColor: [226, 232, 240],
            lineWidth: 0.1,
            valign: 'middle',
        },
        headStyles: {
            fillColor: [30, 58, 138], // Dark Blue
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8.5,
        },
        alternateRowStyles: {
            fillColor: [248, 250, 252],
        },
        columnStyles: {
            0: { halign: 'center', cellWidth: columnWidths[0] },
            1: { cellWidth: columnWidths[1], fontStyle: 'bold' },
            2: { cellWidth: columnWidths[2] },
            3: { cellWidth: columnWidths[3] },
            4: { halign: 'center', cellWidth: columnWidths[4] },
            5: { halign: 'center', cellWidth: columnWidths[5] },
            6: { halign: 'center', cellWidth: columnWidths[6] },
            7: { cellWidth: columnWidths[7] },
        },
        didParseCell: (data) => {
            if (data.section !== 'body') return;
            const rawVal = data.cell.raw;
            if (!rawVal || typeof rawVal === 'object') return;
            const strVal = String(rawVal);
            if (hasComplexUnicode(strVal)) {
                const colW = columnWidths[data.column.index] || 35;
                const innerW = Math.max(12, colW - 5);
                const unicodeImg = renderUnicodeTextBlockToPng(strVal, {
                    fontSizePt: 8,
                    maxWidthMm: innerW,
                    color: '#0f172a',
                    fontWeight: data.column.index === 1 ? 'bold' : 'normal',
                });
                data.cell._unicodeImg = unicodeImg;
                data.cell.text = new Array(Math.max(1, unicodeImg.lineCount)).fill(' ');
                data.cell.styles.minCellHeight = Math.max(6.5, unicodeImg.heightMm + 2);
            }
        },
        didDrawCell: (data) => {
            if (data.section === 'body' && data.cell._unicodeImg) {
                const img = data.cell._unicodeImg;
                const drawX = data.cell.x + 2;
                const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
                doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
            }
        },
        didDrawPage: (data) => {
            // Footer
            const str = `Page ${doc.internal.getNumberOfPages()}`;
            doc.setFont(fontName, 'normal');
            doc.setFontSize(8);
            doc.setTextColor(148, 163, 184);
            doc.text('Confidential • Internal IT Access & Security Audit Document', margin, pageHeight - 8);
            doc.text(str, pageWidth - margin - doc.getTextWidth(str), pageHeight - 8);
        }
    });

    const safeName = (user.name || 'PIC').replace(/[^a-zA-Z0-9_-]/g, '_');
    doc.save(`PIC_Access_Report_${safeName}.pdf`);
}

/**
 * EXPORT REPORT 2: Account PIC Holders Report (All PIC persons holding a specific platform account)
 */
export async function exportAccountHoldersReportPdf({
    account,
    records = [],
    authUser = {},
    companyName = 'ShweTatar Gold & Jewellery'
}) {
    if (!account) return;
    await ensurePyidaungsuFontLoaded();

    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4',
    });

    const fontName = await applyPyidaungsuFont(doc);
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 14;
    let currentY = 16;

    const primaryColor = [20, 60, 120];

    // 1. Company & Document Header
    doc.setFont(fontName, 'bold');
    doc.setFontSize(16);
    doc.setTextColor(...primaryColor);
    doc.text(companyName, margin, currentY);

    currentY += 6;
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text('Software Platform Account - Custodianship & PIC Report', margin, currentY);

    currentY += 5;
    doc.setFont(fontName, 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(100, 116, 139);
    const nowStr = new Date().toLocaleString('en-GB');
    doc.text(`Official Custodian Assignment & Account Holding Audit • Generated: ${nowStr}`, margin, currentY);

    currentY += 7;

    // 2. Summary Info Box
    const activeCount = records.filter(r => r.status === 'active').length;
    const inactiveCount = records.filter(r => r.status !== 'active').length;

    doc.setFillColor(248, 250, 252);
    doc.setDrawColor(226, 232, 240);
    doc.roundedRect(margin, currentY, pageWidth - margin * 2, 18, 2, 2, 'FD');

    doc.setFont(fontName, 'bold');
    doc.setFontSize(9.5);
    doc.setTextColor(15, 23, 42);
    doc.text(`Account Name: ${account.account_name || '-'} (${account.platform?.name || 'Platform'})`, margin + 5, currentY + 6.5);

    doc.setFont(fontName, 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(71, 85, 105);
    doc.text(`Login Username: ${account.username || '-'}   |   Lifecycle: ${(account.lifecycle_status || 'active').toUpperCase()}`, margin + 5, currentY + 12.5);

    const statsX = margin + 140;
    doc.setFont(fontName, 'bold');
    doc.text(`Total PICs: ${records.length}`, statsX, currentY + 6.5);
    doc.setTextColor(22, 101, 52); // green
    doc.text(`Active Custodians: ${activeCount}`, statsX + 45, currentY + 6.5);
    doc.setTextColor(185, 28, 28); // red
    doc.text(`Revoked / Inactive: ${inactiveCount}`, statsX + 90, currentY + 6.5);

    currentY += 24;

    // 3. Table of Custodians
    const tableHeaders = [
        '#',
        'Person in Charge (PIC)',
        'Email Address',
        'Assignment Status',
        'Assigned Date',
        'Revoked Date',
        'Revocation Reason'
    ];

    const tableRows = records.map((rec, index) => {
        const userName = rec.user?.name || 'Unknown User';
        const userEmail = rec.user?.email || '-';
        const picStatus = (rec.status || 'active').toUpperCase();
        const assignedDate = formatDate(rec.assigned_at);
        const revokedDate = rec.revoked_at ? formatDate(rec.revoked_at) : '-';
        const reason = rec.revocation_reason || '-';

        return [
            index + 1,
            userName,
            userEmail,
            picStatus,
            assignedDate,
            revokedDate,
            reason
        ];
    });

    const columnWidths = [12, 50, 55, 30, 30, 30, 62];

    autoTable(doc, {
        startY: currentY,
        head: [tableHeaders],
        body: tableRows,
        margin: { left: margin, right: margin, bottom: 15 },
        theme: 'grid',
        styles: {
            font: fontName,
            fontSize: 8.5,
            cellPadding: 2.5,
            textColor: [30, 41, 59],
            lineColor: [226, 232, 240],
            lineWidth: 0.1,
            valign: 'middle',
        },
        headStyles: {
            fillColor: [15, 76, 129], // Classic Navy
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8.5,
        },
        alternateRowStyles: {
            fillColor: [248, 250, 252],
        },
        columnStyles: {
            0: { halign: 'center', cellWidth: columnWidths[0] },
            1: { cellWidth: columnWidths[1], fontStyle: 'bold' },
            2: { cellWidth: columnWidths[2] },
            3: { halign: 'center', cellWidth: columnWidths[3] },
            4: { halign: 'center', cellWidth: columnWidths[4] },
            5: { halign: 'center', cellWidth: columnWidths[5] },
            6: { cellWidth: columnWidths[6] },
        },
        didParseCell: (data) => {
            if (data.section !== 'body') return;
            const rawVal = data.cell.raw;
            if (!rawVal || typeof rawVal === 'object') return;
            const strVal = String(rawVal);
            if (hasComplexUnicode(strVal)) {
                const colW = columnWidths[data.column.index] || 40;
                const innerW = Math.max(12, colW - 5);
                const unicodeImg = renderUnicodeTextBlockToPng(strVal, {
                    fontSizePt: 8,
                    maxWidthMm: innerW,
                    color: '#0f172a',
                    fontWeight: data.column.index === 1 ? 'bold' : 'normal',
                });
                data.cell._unicodeImg = unicodeImg;
                data.cell.text = new Array(Math.max(1, unicodeImg.lineCount)).fill(' ');
                data.cell.styles.minCellHeight = Math.max(6.5, unicodeImg.heightMm + 2);
            }
        },
        didDrawCell: (data) => {
            if (data.section === 'body' && data.cell._unicodeImg) {
                const img = data.cell._unicodeImg;
                const drawX = data.cell.x + 2;
                const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
                doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
            }
        },
        didDrawPage: (data) => {
            const str = `Page ${doc.internal.getNumberOfPages()}`;
            doc.setFont(fontName, 'normal');
            doc.setFontSize(8);
            doc.setTextColor(148, 163, 184);
            doc.text('Confidential • Internal IT Access & Security Audit Document', margin, pageHeight - 8);
            doc.text(str, pageWidth - margin - doc.getTextWidth(str), pageHeight - 8);
        }
    });

    const safeName = (account.account_name || 'Account').replace(/[^a-zA-Z0-9_-]/g, '_');
    doc.save(`Account_PIC_Report_${safeName}.pdf`);
}
