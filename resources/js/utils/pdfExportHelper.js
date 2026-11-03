import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { applyPyidaungsuFont } from './pdfFont';

/**
 * Ensures the Pyidaungsu webfont is fully loaded in the browser before measuring/drawing on 2D canvas.
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
 * Detect Myanmar or complex Unicode text (characters beyond ASCII range 0x00-0x7F).
 */
const hasComplexUnicode = (text) => /[^\x00-\x7F]/.test(String(text || ''));

/**
 * Strips HTML tags if text contains markup, preserving plain text.
 */
const cleanText = (text) => {
    if (!text) return '';
    if (typeof text !== 'string') return String(text);
    if (/<[a-z][\s\S]*>/i.test(text)) {
        const div = document.createElement('div');
        div.innerHTML = text;
        return (div.textContent || div.innerText || '').trim();
    }
    return text.trim();
};

/**
 * Tokenize Myanmar text for natural line wrapping using Intl.Segmenter with regex fallback.
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
 * Render non-ASCII / Myanmar text block to offscreen PNG canvas at 4x DPI (384 DPI).
 * Utilizes the browser's native HarfBuzz OpenType shaping engine for 100% correct
 * Myanmar vowel, medial, and consonant rendering without word collisions or zoom bugs.
 */
const renderUnicodeTextBlockToPng = (
    text,
    {
        fontSizePt = 8,
        maxWidthMm = 40,
        color = '#0f172a',
        fontWeight = 'normal',
        lineHeightMultiplier = 1.5,
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
 * Renders the multi-part Issue Details cell (Title, Description, Propose Solution)
 * onto a 4x DPI offscreen canvas with color-coded labels and full Myanmar Unicode support.
 */
const renderIssueDetailsToPng = ({
    title = '',
    description = '',
    proposedSolution = '',
    maxWidthMm = 133,
    fontSizePt = 8.2,
}) => {
    const PX_PER_MM = 96 / 25.4;
    const MM_PER_PX = 25.4 / 96;
    const SCALE = 4; // 384 DPI print quality

    const fontSizePx = fontSizePt * (96 / 72);
    const maxWidthPx = Math.max(30, maxWidthMm * PX_PER_MM);
    const fontRegular = `normal ${fontSizePx}px 'Pyidaungsu', 'Padauk', 'Myanmar Text', 'Segoe UI', Arial, sans-serif`;
    const fontBold = `bold ${fontSizePx}px 'Pyidaungsu', 'Padauk', 'Myanmar Text', 'Segoe UI', Arial, sans-serif`;

    const measureCanvas = document.createElement('canvas');
    const measureCtx = measureCanvas.getContext('2d');

    const sections = [
        { label: 'Title: ', labelColor: '#1e1b4b', text: String(title || '-'), textColor: '#0f172a' },
        { label: 'Description: ', labelColor: '#4338ca', text: String(description || '-'), textColor: '#334155' },
        { label: 'Propose Solution: ', labelColor: '#047857', text: String(proposedSolution || '-'), textColor: '#1f2937' },
    ];

    const linesToDraw = [];

    sections.forEach((sec, sIdx) => {
        measureCtx.font = fontBold;
        const labelW = measureCtx.measureText(sec.label).width;

        const paras = sec.text.split('\n');
        let isFirstLineOfSec = true;

        paras.forEach((para) => {
            const trimmed = para.trim();
            if (!trimmed) {
                if (isFirstLineOfSec) {
                    linesToDraw.push([
                        { text: sec.label, isBold: true, color: sec.labelColor },
                        { text: '-', isBold: false, color: sec.textColor },
                    ]);
                    isFirstLineOfSec = false;
                }
                return;
            }

            const spaceTokens = trimmed.split(/( +)/).filter(Boolean);
            const fineTokens = [];
            measureCtx.font = fontRegular;
            for (const st of spaceTokens) {
                if (measureCtx.measureText(st).width > maxWidthPx * 0.6 && !/^ +$/.test(st)) {
                    fineTokens.push(...tokenizeForMyanmarWrap(st));
                } else {
                    fineTokens.push(st);
                }
            }

            let curLineText = '';

            for (const token of fineTokens) {
                const availableW = isFirstLineOfSec ? (maxWidthPx - labelW) : maxWidthPx;
                const candidate = curLineText ? curLineText + token : token.trimStart();
                if (!candidate) continue;

                if (measureCtx.measureText(candidate).width <= availableW || !curLineText) {
                    // Check if candidate exceeds available width even as first token on line 1
                    if (isFirstLineOfSec && !curLineText && measureCtx.measureText(candidate).width > availableW) {
                        linesToDraw.push([
                            { text: sec.label, isBold: true, color: sec.labelColor }
                        ]);
                        isFirstLineOfSec = false;
                        curLineText = candidate;
                    } else {
                        curLineText = candidate;
                    }
                } else {
                    if (isFirstLineOfSec) {
                        linesToDraw.push([
                            { text: sec.label, isBold: true, color: sec.labelColor },
                            { text: curLineText.trimEnd(), isBold: false, color: sec.textColor },
                        ]);
                        isFirstLineOfSec = false;
                    } else {
                        linesToDraw.push([
                            { text: curLineText.trimEnd(), isBold: false, color: sec.textColor },
                        ]);
                    }
                    curLineText = token.trimStart();
                }
            }

            if (curLineText) {
                if (isFirstLineOfSec) {
                    linesToDraw.push([
                        { text: sec.label, isBold: true, color: sec.labelColor },
                        { text: curLineText.trimEnd(), isBold: false, color: sec.textColor },
                    ]);
                    isFirstLineOfSec = false;
                } else {
                    linesToDraw.push([
                        { text: curLineText.trimEnd(), isBold: false, color: sec.textColor },
                    ]);
                }
            }
        });

        if (isFirstLineOfSec) {
            linesToDraw.push([
                { text: sec.label, isBold: true, color: sec.labelColor },
                { text: '-', isBold: false, color: sec.textColor },
            ]);
        }

        if (sIdx < sections.length - 1 && linesToDraw.length > 0) {
            linesToDraw[linesToDraw.length - 1]._hasSectionGap = true;
        }
    });

    const lineHeightPx = Math.ceil(fontSizePx * 1.48);
    const sectionGapPx = Math.ceil(fontSizePx * 0.35);
    const topPadPx = Math.ceil(fontSizePx * 0.35);
    const bottomPadPx = Math.ceil(fontSizePx * 0.45);

    let totalHeightPx = topPadPx + bottomPadPx;
    linesToDraw.forEach((l) => {
        totalHeightPx += lineHeightPx;
        if (l._hasSectionGap) totalHeightPx += sectionGapPx;
    });

    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(maxWidthPx * SCALE);
    canvas.height = Math.ceil(totalHeightPx * SCALE);

    const ctx = canvas.getContext('2d');
    ctx.scale(SCALE, SCALE);
    ctx.textBaseline = 'alphabetic';

    let currentY = topPadPx;

    linesToDraw.forEach((lineParts) => {
        const baselineY = currentY + fontSizePx * 1.05;
        let currentX = 0;

        lineParts.forEach((part) => {
            ctx.font = part.isBold ? fontBold : fontRegular;
            ctx.fillStyle = part.color;
            ctx.fillText(part.text, currentX, baselineY);
            currentX += ctx.measureText(part.text).width;
        });

        currentY += lineHeightPx;
        if (lineParts._hasSectionGap) {
            currentY += sectionGapPx;
        }
    });

    return {
        dataUrl: canvas.toDataURL('image/png'),
        widthMm: maxWidthMm,
        heightMm: totalHeightPx * MM_PER_PX,
        lineCount: linesToDraw.length,
    };
};

/**
 * Renders the Priority badge (e.g. "P1\n(24/7 Clock)") centered onto an offscreen PNG canvas.
 */
const renderPriorityBadgeToPng = ({ name = 'N/A', code = '', is24h = false, maxWidthMm = 22 }) => {
    const PX_PER_MM = 96 / 25.4;
    const MM_PER_PX = 25.4 / 96;
    const SCALE = 4;
    const fontSizePt = 8.5;
    const fontSizePx = fontSizePt * (96 / 72);
    const maxWidthPx = Math.max(20, maxWidthMm * PX_PER_MM);
    const fontSpec = `bold ${fontSizePx}px 'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;
    const subFontSpec = `600 ${fontSizePx * 0.8}px 'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;

    const color = code === 'P1' ? '#dc2626' : (code === 'P2' ? '#d97706' : '#2563eb');
    const totalLines = is24h ? 2 : 1;
    const lineHeightPx = Math.ceil(fontSizePx * 1.35);
    const totalHeightPx = totalLines * lineHeightPx + Math.ceil(fontSizePx * 0.4);

    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(maxWidthPx * SCALE);
    canvas.height = Math.ceil(totalHeightPx * SCALE);
    const ctx = canvas.getContext('2d');
    ctx.scale(SCALE, SCALE);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    const centerX = maxWidthPx / 2;
    if (is24h) {
        ctx.font = fontSpec;
        ctx.fillStyle = color;
        ctx.fillText(name, centerX, lineHeightPx * 0.55);

        ctx.font = subFontSpec;
        ctx.fillStyle = '#7c3aed';
        ctx.fillText('(24/7 Clock)', centerX, lineHeightPx * 1.5);
    } else {
        ctx.font = fontSpec;
        ctx.fillStyle = color;
        ctx.fillText(name, centerX, totalHeightPx / 2);
    }

    return {
        dataUrl: canvas.toDataURL('image/png'),
        widthMm: maxWidthMm,
        heightMm: totalHeightPx * MM_PER_PX,
        lineCount: totalLines,
    };
};

/**
 * Sorts report items:
 * 1. Top priority: Continuous 24/7 clock ('continuous_24h' or Level 1 / P1).
 * 2. Other types: Sorted by Due Date with today / earliest first.
 */
const sortReportItems = (items = []) => {
    return [...items].sort((a, b) => {
        const isA24h = a.priority_clock_type === 'continuous_24h' || a.priority_code === 'P1';
        const isB24h = b.priority_clock_type === 'continuous_24h' || b.priority_code === 'P1';

        // Continuous 24/7 clock at the top
        if (isA24h && !isB24h) return -1;
        if (!isA24h && isB24h) return 1;

        // Due date sorting (today / earliest due date first)
        const parseDate = (item) => {
            if (item.due_date_raw) {
                const t = new Date(item.due_date_raw).getTime();
                if (!isNaN(t)) return t;
            }
            if (item.due_date && item.due_date !== 'N/A') {
                const t = new Date(item.due_date).getTime();
                if (!isNaN(t)) return t;
            }
            return Infinity; // Put items without due date at the end
        };

        const dateA = parseDate(a);
        const dateB = parseDate(b);

        if (dateA !== dateB) {
            return dateA - dateB;
        }

        // Secondary sort: Issue creation date ascending
        const issueAtA = a.issue_at_raw ? new Date(a.issue_at_raw).getTime() : 0;
        const issueAtB = b.issue_at_raw ? new Date(b.issue_at_raw).getTime() : 0;
        if (issueAtA !== issueAtB) return issueAtA - issueAtB;

        return (a.id || 0) - (b.id || 0);
    });
};

/**
 * Exports the Detailed IT Issues List PDF using vector jsPDF + jspdf-autotable
 * and 4x DPI offscreen 2D Canvas shaping for Myanmar Unicode (Pyidaungsu).
 * Completely immune to html2canvas zoom scaling and word collision bugs.
 */
export const exportDetailedIssuePDF = async ({
    report,
    filters = {},
    auth_user = {},
    app_name = 'OrderDemo',
    categories = [],
    departments = [],
    formatDateShort,
    formatDateCustom,
}) => {
    await ensurePyidaungsuFontLoaded();

    const { periodType = 'weekly', startDate, endDate, resolverType = 'all', selectedCategoryIds = [], selectedDepartmentIds = [] } = filters;
    const isSingle = startDate && endDate && startDate === endDate;
    const reportAtLabel = isSingle
        ? `Report At: ${formatDateShort ? formatDateShort(startDate) : startDate}`
        : (startDate && endDate
            ? `Period: ${formatDateShort ? formatDateShort(startDate) : startDate}  →  ${formatDateShort ? formatDateShort(endDate) : endDate}`
            : `Period: ${report.period_label || 'All Dates'}`);

    const resolverLabel = resolverType === 'third_party' ? 'Third-Party Developer'
        : resolverType === 'internal' ? 'Internal IT Team'
            : 'All Resolvers';

    const catLabel = selectedCategoryIds && selectedCategoryIds.length > 0
        ? categories.filter(c => selectedCategoryIds.includes(c.id)).map(c => c.name).join(', ')
        : 'All Categories';

    const deptLabel = selectedDepartmentIds && selectedDepartmentIds.length > 0
        ? departments.filter(d => selectedDepartmentIds.includes(d.id)).map(d => d.name).join(', ')
        : 'All Resolver Depts';

    const exporterName = auth_user?.name || '';
    const exporterDept = auth_user?.department ? ` (${auth_user.department})` : '';
    const fullExporterText = exporterName ? `${exporterName}${exporterDept}` : '';
    const todayDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const generatedAt = new Date().toLocaleString();

    // Sort items according to business rules
    const rawItems = report.items || [];
    const sortedItems = sortReportItems(rawItems);

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    let fontName = 'helvetica';
    try {
        fontName = await applyPyidaungsuFont(doc);
    } catch (e) {
        console.warn('Pyidaungsu font load error, using helvetica fallback:', e);
        fontName = 'helvetica';
    }

    const pageWidth = doc.internal.pageSize.getWidth(); // 297mm
    const pageHeight = doc.internal.pageSize.getHeight(); // 210mm
    const margin = 12;
    const contentWidth = pageWidth - margin * 2; // 273mm
    let currentY = 12;

    // ------------------------------------------------------------------
    // 1. Top Header Banner
    // ------------------------------------------------------------------
    const headerH = 18;
    doc.setFillColor(59, 7, 100); // #3b0764 Deep purple
    doc.roundedRect(margin, currentY, contentWidth, headerH, 2, 2, 'F');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(255, 255, 255);
    doc.text('Issue Tracking Detail Report', margin + 6, currentY + 7);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(233, 213, 255); // #e9d5ff
    doc.text(`${app_name}  |  ${resolverLabel}`, margin + 6, currentY + 13);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(226, 232, 240);
    doc.text(`Generated: ${generatedAt}`, pageWidth - margin - 6, currentY + 7, { align: 'right' });

    if (fullExporterText) {
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(216, 180, 254);
        doc.text(`Exported by: ${cleanText(fullExporterText)}`, pageWidth - margin - 6, currentY + 13, { align: 'right' });
    }

    currentY += headerH + 2.5;

    // ------------------------------------------------------------------
    // 2. Filter Summary Bar
    // ------------------------------------------------------------------
    const filterH = 13;
    doc.setFillColor(245, 240, 255); // #f5f0ff
    doc.setDrawColor(233, 213, 255); // #e9d5ff
    doc.setLineWidth(0.25);
    doc.roundedRect(margin, currentY, contentWidth, filterH, 1.5, 1.5, 'FD');

    const filterLine1 = `${reportAtLabel}     |     Resolver: ${resolverLabel}     |     Categories: ${catLabel}     |     Depts: ${deptLabel}`;
    const filterLine2 = 'Continuous 24/7 Clock issues are listed first, followed by issues sorted by Due Date (today/upcoming first).';

    if (hasComplexUnicode(filterLine1)) {
        const filterImg = renderUnicodeTextBlockToPng(`${filterLine1}\n${filterLine2}`, {
            fontSizePt: 7.5,
            maxWidthMm: contentWidth - 8,
            color: '#581c87',
            lineHeightMultiplier: 1.5,
        });
        doc.addImage(filterImg.dataUrl, 'PNG', margin + 4, currentY + 1.8, filterImg.widthMm, filterImg.heightMm);
    } else {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(88, 28, 135);
        doc.text(filterLine1, margin + 4, currentY + 5.2);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(107, 33, 168);
        doc.text(filterLine2, margin + 4, currentY + 9.8);
    }

    currentY += filterH + 4;

    // ------------------------------------------------------------------
    // 3. Issue Table Data Construction
    // ------------------------------------------------------------------
    const colWidths = [10, 137, 36, 26, 32, 32]; // Sum = 273mm

    const tableRows = sortedItems.map((item, idx) => {
        const isContinuous24h = item.priority_clock_type === 'continuous_24h' || item.priority_code === 'P1';

        const formattedIssueAt = formatDateCustom ? formatDateCustom(item.issue_at) : (item.issue_at || 'N/A');
        const formattedDueDate = formatDateCustom ? formatDateCustom(item.due_date) : (item.due_date || 'N/A');

        return [
            String(idx + 1),
            {
                title: cleanText(item.title),
                description: cleanText(item.description),
                proposedSolution: cleanText(item.proposed_solution),
            },
            cleanText(item.category_name || 'N/A'),
            {
                name: cleanText(item.priority_name || 'N/A'),
                code: item.priority_code,
                is24h: isContinuous24h,
            },
            formattedIssueAt.replace(', ', '\n'),
            formattedDueDate.replace(', ', '\n'),
        ];
    });

    const bodyRows = tableRows.length > 0 ? tableRows : [
        [
            {
                content: 'No issues found for the selected criteria.',
                colSpan: 6,
                styles: { halign: 'center', textColor: [100, 116, 139], fontSize: 9, minCellHeight: 14 }
            }
        ]
    ];

    const tableConfig = {
        startY: currentY,
        head: [
            ['#', 'Issue Details (Title / Description / Solution)', 'Category', 'Priority', 'Issued At', 'Due Date']
        ],
        body: bodyRows,
        theme: 'grid',
        margin: { top: 14, left: margin, right: margin, bottom: 14 },
        styles: {
            font: fontName,
            fontSize: 8,
            cellPadding: { top: 2.5, bottom: 2.5, left: 2.5, right: 2.5 },
            lineColor: [226, 232, 240],
            lineWidth: 0.15,
            valign: 'middle',
            textColor: [30, 41, 59],
        },
        headStyles: {
            fillColor: [59, 7, 100], // Deep purple #3b0764
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8.5,
            halign: 'center',
        },
        alternateRowStyles: {
            fillColor: [248, 245, 255], // Light purple tint
        },
        columnStyles: {
            0: { cellWidth: colWidths[0], halign: 'center', fontStyle: 'bold', textColor: [100, 116, 139] },
            1: { cellWidth: colWidths[1], halign: 'left' },
            2: { cellWidth: colWidths[2], halign: 'left', textColor: [51, 65, 85] },
            3: { cellWidth: colWidths[3], halign: 'center' },
            4: { cellWidth: colWidths[4], halign: 'center', textColor: [71, 85, 105], fontSize: 7.5 },
            5: { cellWidth: colWidths[5], halign: 'center', textColor: [71, 85, 105], fontSize: 7.5 },
        },
        didParseCell: (data) => {
            if (data.section === 'head') {
                if (data.column.index === 1 || data.column.index === 2) {
                    data.cell.styles.halign = 'left';
                }
            }
            if (data.section === 'body') {
                if (data.column.index === 1) {
                    const raw = data.cell.raw;
                    if (raw && typeof raw === 'object' && raw.title !== undefined) {
                        const innerW = colWidths[1] - 4; // 133mm
                        const detailsImg = renderIssueDetailsToPng({
                            title: raw.title,
                            description: raw.description,
                            proposedSolution: raw.proposedSolution,
                            maxWidthMm: innerW,
                            fontSizePt: 8.2,
                        });
                        data.cell._detailsImg = detailsImg;
                        data.cell.text = new Array(Math.max(1, detailsImg.lineCount)).fill(' ');
                        data.cell.styles.minCellHeight = Math.max(8, detailsImg.heightMm + 2.5);
                    }
                } else if (data.column.index === 2) {
                    const strVal = String(data.cell.raw || '');
                    if (hasComplexUnicode(strVal)) {
                        const innerW = colWidths[2] - 4; // 32mm
                        const catImg = renderUnicodeTextBlockToPng(strVal, {
                            fontSizePt: 8,
                            maxWidthMm: innerW,
                            color: '#334155',
                            fontWeight: 'normal',
                        });
                        data.cell._catImg = catImg;
                        data.cell.text = new Array(Math.max(1, catImg.lineCount)).fill(' ');
                        data.cell.styles.minCellHeight = Math.max(data.cell.styles.minCellHeight || 0, catImg.heightMm + 2.5);
                    }
                } else if (data.column.index === 3) {
                    const raw = data.cell.raw;
                    if (raw && typeof raw === 'object' && raw.name !== undefined) {
                        const innerW = colWidths[3] - 4; // 22mm
                        const priImg = renderPriorityBadgeToPng({
                            name: raw.name,
                            code: raw.code,
                            is24h: raw.is24h,
                            maxWidthMm: innerW,
                        });
                        data.cell._priImg = priImg;
                        data.cell.text = new Array(Math.max(1, priImg.lineCount)).fill(' ');
                        data.cell.styles.minCellHeight = Math.max(data.cell.styles.minCellHeight || 0, priImg.heightMm + 2.5);
                    }
                }
            }
        },
        didDrawCell: (data) => {
            if (data.section === 'body') {
                if (data.column.index === 1 && data.cell._detailsImg) {
                    const img = data.cell._detailsImg;
                    const drawX = data.cell.x + 2;
                    const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
                    doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
                } else if (data.column.index === 2 && data.cell._catImg) {
                    const img = data.cell._catImg;
                    const drawX = data.cell.x + 2;
                    const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
                    doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
                } else if (data.column.index === 3 && data.cell._priImg) {
                    const img = data.cell._priImg;
                    const drawX = data.cell.x + (data.cell.width - img.widthMm) / 2;
                    const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
                    doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
                }
            }
        },
        didDrawPage: (data) => {
            // Continuation header on page 2+
            if (data.pageNumber > 1) {
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(8);
                doc.setTextColor(59, 7, 100);
                doc.text('Issue Tracking Detail Report (Continued)', margin, 10);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7.5);
                doc.setTextColor(100, 116, 139);
                doc.text(`Generated: ${generatedAt}`, pageWidth - margin, 10, { align: 'right' });
            }
        }
    };

    if (typeof autoTable === 'function') {
        autoTable(doc, tableConfig);
    } else if (typeof doc.autoTable === 'function') {
        doc.autoTable(tableConfig);
    }

    // ------------------------------------------------------------------
    // 4. Verification & Signature Block (Placed on Last Page)
    // ------------------------------------------------------------------
    const finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY : currentY + 30;
    const sigBlockH = 26;

    let sigY = finalY + 6;
    if (sigY + sigBlockH > pageHeight - margin - 8) {
        doc.addPage('a4', 'landscape');
        sigY = 20;
    }

    doc.setDrawColor(203, 213, 225);
    doc.setLineWidth(0.2);
    doc.line(margin, sigY, pageWidth - margin, sigY);
    sigY += 5;

    const blockW = 105;
    const leftX = margin + 4;
    const rightX = pageWidth - margin - blockW - 4;

    // Left: Report Exported By
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(30, 41, 59);
    doc.text('Report Exported By:', leftX, sigY);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105);
    doc.text('Name:', leftX, sigY + 6);

    if (hasComplexUnicode(fullExporterText)) {
        const nameImg = renderUnicodeTextBlockToPng(fullExporterText, {
            fontSizePt: 8,
            maxWidthMm: 75,
            fontWeight: 'bold',
            color: '#0f172a',
        });
        doc.addImage(nameImg.dataUrl, 'PNG', leftX + 16, sigY + 2.5, nameImg.widthMm, nameImg.heightMm);
    } else {
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(fullExporterText || '—', leftX + 16, sigY + 6);
    }
    doc.setDrawColor(148, 163, 184);
    doc.line(leftX + 16, sigY + 7, leftX + blockW, sigY + 7);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(71, 85, 105);
    doc.text('Signature:', leftX, sigY + 12);
    doc.line(leftX + 16, sigY + 13, leftX + blockW, sigY + 13);

    doc.text('Date:', leftX, sigY + 18);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text(todayDate, leftX + 16, sigY + 18);

    // Right: Acknowledged By
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(30, 41, 59);
    doc.text('Acknowledged By:', rightX, sigY);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105);
    doc.text('Name:', rightX, sigY + 6);
    doc.setDrawColor(148, 163, 184);
    doc.line(rightX + 16, sigY + 7, rightX + blockW, sigY + 7);

    doc.text('Signature:', rightX, sigY + 12);
    doc.line(rightX + 16, sigY + 13, rightX + blockW, sigY + 13);

    doc.text('Date:', rightX, sigY + 18);
    doc.line(rightX + 16, sigY + 19, rightX + blockW, sigY + 19);

    // ------------------------------------------------------------------
    // 5. Page Numbering & Bottom Footer (All Pages)
    // ------------------------------------------------------------------
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(7.5);
        doc.setTextColor(148, 163, 184);
        const footerLeft = `${app_name} — IT Issue Detail Report — ${cleanText(reportAtLabel)}`;
        doc.text(footerLeft, margin, pageHeight - 6);
        const footerRight = `Page ${i} of ${totalPages}`;
        doc.text(footerRight, pageWidth - margin, pageHeight - 6, { align: 'right' });
    }

    const filename = `IT_Issue_Detail_Report_${startDate || 'all'}_to_${endDate || 'all'}.pdf`;
    doc.save(filename);
};

/**
 * Exports the Category Summary Report PDF using vector jsPDF + jspdf-autotable.
 * Completely immune to html2canvas zoom scaling and word collision bugs.
 */
export const exportCategorySummaryPDF = async ({
    report,
    filters = {},
    app_name = 'OrderDemo',
}) => {
    await ensurePyidaungsuFontLoaded();

    const { periodType = 'weekly', startDate, endDate, resolverType = 'all' } = filters;

    const companyDisplay =
        resolverType === 'third_party'
            ? `${app_name}  ↔  External Developer`
            : resolverType === 'internal'
                ? `${app_name}  (Internal IT)`
                : `${app_name}  (All Resolvers)`;

    const resolverLabel =
        resolverType === 'third_party' ? 'Third-Party Developer Fix'
            : resolverType === 'internal' ? 'Internal IT / User Fix'
                : 'All Resolvers (Internal + Third-Party)';

    const catMap = {};
    (report.items || []).forEach((item) => {
        const cat = item.category_name || 'Uncategorized';
        if (!catMap[cat]) catMap[cat] = { success: 0, fail: 0, failPoints: 0 };
        if (item.is_sla_failed) {
            catMap[cat].fail++;
            catMap[cat].failPoints += Number(item.fail_points || 0);
        } else {
            catMap[cat].success++;
        }
    });

    const rows = Object.entries(catMap).map(([cat, d], idx) => {
        const total = d.success + d.fail;
        const rate = total > 0 ? ((d.success / total) * 100).toFixed(1) : '0.0';
        return {
            idx: idx + 1,
            cat,
            success: d.success,
            fail: d.fail,
            failPoints: d.failPoints,
            rate: `${rate}%`,
            rateNum: parseFloat(rate),
        };
    });

    const totalSuccess = rows.reduce((s, r) => s + r.success, 0);
    const totalFail = rows.reduce((s, r) => s + r.fail, 0);
    const totalFP = rows.reduce((s, r) => s + r.failPoints, 0);
    const grandTotal = totalSuccess + totalFail;
    const grandRate = grandTotal > 0 ? ((totalSuccess / grandTotal) * 100).toFixed(1) : '0.0';
    const svcCredit = report.summary?.service_credit_pct ?? 0;

    const periodStr = startDate && endDate
        ? `${startDate}  →  ${endDate}`
        : report.period_label || periodType.toUpperCase();

    const todayDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    let fontName = 'helvetica';
    try {
        fontName = await applyPyidaungsuFont(doc);
    } catch (e) {
        console.warn('Pyidaungsu font load error, using helvetica fallback:', e);
        fontName = 'helvetica';
    }

    const pageWidth = doc.internal.pageSize.getWidth(); // 210mm
    const pageHeight = doc.internal.pageSize.getHeight(); // 297mm
    const margin = 14;
    const contentWidth = pageWidth - margin * 2; // 182mm
    let currentY = 14;

    // ------------------------------------------------------------------
    // 1. Top Header Banner
    // ------------------------------------------------------------------
    const headerH = 20;
    doc.setFillColor(59, 7, 100); // #3b0764
    doc.roundedRect(margin, currentY, contentWidth, headerH, 2, 2, 'F');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(255, 255, 255);
    doc.text('SLA & Service Credit — Category Summary Report', margin + 6, currentY + 7.5);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(233, 213, 255);
    doc.text(companyDisplay, margin + 6, currentY + 14);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(226, 232, 240);
    doc.text(`Generated: ${todayDate}`, pageWidth - margin - 6, currentY + 7.5, { align: 'right' });

    currentY += headerH + 3;

    // ------------------------------------------------------------------
    // 2. Info / Meta Block
    // ------------------------------------------------------------------
    const infoH = 14;
    doc.setFillColor(240, 240, 255);
    doc.setDrawColor(224, 224, 252);
    doc.setLineWidth(0.25);
    doc.roundedRect(margin, currentY, contentWidth, infoH, 1.5, 1.5, 'FD');

    const colW = contentWidth / 3;

    // Col 1: Report Period
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(59, 7, 100);
    doc.text('REPORT PERIOD', margin + 4, currentY + 5);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(30, 41, 59);
    doc.text(periodStr, margin + 4, currentY + 10);

    // Col 2: Resolver Scope
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(59, 7, 100);
    doc.text('RESOLVER SCOPE', margin + colW + 4, currentY + 5);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(30, 41, 59);
    doc.text(resolverLabel, margin + colW + 4, currentY + 10);

    // Col 3: Report Explains
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(59, 7, 100);
    doc.text('REPORT EXPLAINS', margin + colW * 2 + 4, currentY + 5);
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(7);
    doc.setTextColor(71, 85, 105);
    doc.text('Shows SLA-passed/failed IT issues per category, weighted fail points, and resolution rate.', margin + colW * 2 + 4, currentY + 10, { maxWidth: colW - 6 });

    currentY += infoH + 5;

    // ------------------------------------------------------------------
    // 3. Category Summary Table
    // ------------------------------------------------------------------
    const summaryColWidths = [12, 70, 25, 25, 25, 25]; // Sum = 182mm

    const summaryTableRows = rows.map((r) => [
        String(r.idx),
        cleanText(r.cat),
        String(r.success),
        String(r.fail),
        String(r.failPoints),
        r.rate,
    ]);

    const summaryBody = summaryTableRows.length > 0 ? summaryTableRows : [
        [
            {
                content: 'No category data available for the selected period.',
                colSpan: 6,
                styles: { halign: 'center', textColor: [100, 116, 139], fontSize: 9, minCellHeight: 12 }
            }
        ]
    ];

    const summaryFoot = [
        ['', 'GRAND TOTAL', String(totalSuccess), String(totalFail), String(totalFP), `${grandRate}%`]
    ];

    const summaryTableConfig = {
        startY: currentY,
        head: [
            ['#', 'Category', 'Success ✔', 'Fail ✖', 'Fail Points', 'Success Rate']
        ],
        body: summaryBody,
        foot: summaryFoot,
        theme: 'grid',
        margin: { top: 14, left: margin, right: margin, bottom: 14 },
        styles: {
            font: fontName,
            fontSize: 8.5,
            cellPadding: { top: 3, bottom: 3, left: 3, right: 3 },
            lineColor: [226, 232, 240],
            lineWidth: 0.15,
            valign: 'middle',
            textColor: [30, 41, 59],
        },
        headStyles: {
            fillColor: [59, 7, 100],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8.5,
            halign: 'center',
        },
        footStyles: {
            fillColor: [13, 148, 136], // Teal #0d9488
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8.5,
            halign: 'center',
        },
        alternateRowStyles: {
            fillColor: [248, 245, 255],
        },
        columnStyles: {
            0: { cellWidth: summaryColWidths[0], halign: 'center', fontStyle: 'bold', textColor: [100, 116, 139] },
            1: { cellWidth: summaryColWidths[1], halign: 'left', fontStyle: 'bold' },
            2: { cellWidth: summaryColWidths[2], halign: 'center', fontStyle: 'bold', textColor: [21, 128, 61] },
            3: { cellWidth: summaryColWidths[3], halign: 'center', fontStyle: 'bold', textColor: [185, 28, 28] },
            4: { cellWidth: summaryColWidths[4], halign: 'center', fontStyle: 'bold', textColor: [185, 28, 28] },
            5: { cellWidth: summaryColWidths[5], halign: 'center', fontStyle: 'bold' },
        },
        didParseCell: (data) => {
            if (data.section === 'head' && data.column.index === 1) {
                data.cell.styles.halign = 'left';
            }
            if (data.section === 'foot' && data.column.index === 1) {
                data.cell.styles.halign = 'left';
            }
            if (data.section === 'body' && data.column.index === 1) {
                const strVal = String(data.cell.raw || '');
                if (hasComplexUnicode(strVal)) {
                    const innerW = summaryColWidths[1] - 4; // 66mm
                    const catImg = renderUnicodeTextBlockToPng(strVal, {
                        fontSizePt: 8.5,
                        maxWidthMm: innerW,
                        color: '#111827',
                        fontWeight: 'bold',
                    });
                    data.cell._catImg = catImg;
                    data.cell.text = new Array(Math.max(1, catImg.lineCount)).fill(' ');
                    data.cell.styles.minCellHeight = Math.max(7, catImg.heightMm + 2.5);
                }
            }
        },
        didDrawCell: (data) => {
            if (data.section === 'body' && data.column.index === 1 && data.cell._catImg) {
                const img = data.cell._catImg;
                const drawX = data.cell.x + 2;
                const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
                doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
            }
        }
    };

    if (typeof autoTable === 'function') {
        autoTable(doc, summaryTableConfig);
    } else if (typeof doc.autoTable === 'function') {
        doc.autoTable(summaryTableConfig);
    }

    // ------------------------------------------------------------------
    // 4. Service Credit Callout & Footnote
    // ------------------------------------------------------------------
    const finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY : currentY + 30;
    let bannerY = finalY + 6;

    if (bannerY + 22 > pageHeight - margin - 10) {
        doc.addPage('a4', 'portrait');
        bannerY = 20;
    }

    const bannerH = 12;
    if (svcCredit > 0) {
        doc.setFillColor(254, 242, 242);
        doc.setDrawColor(254, 202, 202);
        doc.setLineWidth(0.25);
        doc.roundedRect(margin, bannerY, contentWidth, bannerH, 1.5, 1.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(185, 28, 28);
        doc.text(
            `Service Credit Refund Due: ${svcCredit}% — Maintenance credit must be applied for this period.`,
            pageWidth / 2,
            bannerY + 7.5,
            { align: 'center' }
        );
    } else {
        doc.setFillColor(240, 253, 244);
        doc.setDrawColor(187, 247, 208);
        doc.setLineWidth(0.25);
        doc.roundedRect(margin, bannerY, contentWidth, bannerH, 1.5, 1.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(21, 128, 61);
        doc.text(
            '✓ No Service Credit Refund Required — SLA targets were met for this period.',
            pageWidth / 2,
            bannerY + 7.5,
            { align: 'center' }
        );
    }

    doc.setFont('helvetica', 'italic');
    doc.setFontSize(7.5);
    doc.setTextColor(148, 163, 184);
    doc.text(
        'Fail Point Weightage: P1 = 10 pts  |  P2 = 5 pts  |  P3/P4 = 1 pt',
        pageWidth / 2,
        bannerY + bannerH + 6,
        { align: 'center' }
    );

    // ------------------------------------------------------------------
    // 5. Page Numbering & Footer
    // ------------------------------------------------------------------
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(7.5);
        doc.setTextColor(148, 163, 184);
        const footerLeft = `${app_name} — SLA Category Summary — ${cleanText(periodStr)}`;
        doc.text(footerLeft, margin, pageHeight - 6);
        const footerRight = `Page ${i} of ${totalPages}`;
        doc.text(footerRight, pageWidth - margin, pageHeight - 6, { align: 'right' });
    }

    const filename = `SLA_Category_Summary_${periodType}_${startDate || 'all'}_to_${endDate || 'all'}.pdf`;
    doc.save(filename);
};
