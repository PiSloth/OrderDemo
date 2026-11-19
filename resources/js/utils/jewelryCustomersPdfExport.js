import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

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

const hasComplexUnicode = (text) => /[^\x00-\x7F]/.test(String(text || ''));

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
    return paragraph
        .split(/( +|(?=[\u1000-\u1021\u1023-\u102A\u103F\u1040-\u104F])(?<![\u1031\u1039\u103A]))/g)
        .filter(Boolean);
};

const renderUnicodeTextBlockToImage = (
    text,
    {
        fontSizePt = 9,
        maxWidthMm = 170,
        color = '#0f172a',
        backgroundColor = null,
        fontWeight = 'normal',
        lineHeightMultiplier = 1.65,
        scale = 2,
    } = {}
) => {
    const PX_PER_MM = 96 / 25.4;
    const MM_PER_PX = 25.4 / 96;

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

    if (wrappedLines.length === 0) wrappedLines.push('');

    let maxLineWidthPx = 0;
    for (const line of wrappedLines) {
        if (!line) continue;
        const w = measureCtx.measureText(line).width;
        if (w > maxLineWidthPx) maxLineWidthPx = w;
    }

    const tightWidthPx = Math.max(12, Math.min(maxWidthPx, Math.ceil(maxLineWidthPx + 4)));
    const lineHeightPx = Math.ceil(fontSizePx * lineHeightMultiplier);
    const topPadPx = Math.ceil(fontSizePx * 0.35);
    const bottomPadPx = Math.ceil(fontSizePx * 0.45);
    const totalHeightPx = wrappedLines.length * lineHeightPx + topPadPx + bottomPadPx;

    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(tightWidthPx * scale);
    canvas.height = Math.ceil(totalHeightPx * scale);

    const ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);

    if (backgroundColor) {
        ctx.fillStyle = backgroundColor;
        ctx.fillRect(0, 0, tightWidthPx, totalHeightPx);
    }

    ctx.font = fontSpec;
    ctx.fillStyle = color;
    ctx.textBaseline = 'alphabetic';

    wrappedLines.forEach((line, idx) => {
        if (!line) return;
        const baselineY = topPadPx + idx * lineHeightPx + fontSizePx * 1.05;
        ctx.fillText(line, 0, baselineY);
    });

    const format = backgroundColor ? 'JPEG' : 'PNG';
    const dataUrl = backgroundColor
        ? canvas.toDataURL('image/jpeg', 0.88)
        : canvas.toDataURL('image/png');

    return {
        dataUrl,
        format,
        widthMm: tightWidthPx * MM_PER_PX,
        heightMm: totalHeightPx * MM_PER_PX,
        lineCount: wrappedLines.length,
    };
};

/**
 * Generate and download a PDF report of Jewelry Sales customers
 * containing Customer Name, Phone Number, and Sale Voucher Count.
 */
export async function exportJewelryCustomersPdf({
    customers = [],
    branchName = null,
    exportedBy = null,
} = {}) {
    await ensurePyidaungsuFontLoaded();

    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 14;
    const contentWidth = pageWidth - margin * 2;

    const warningText = 'ကန့်သတ်။ ပြင်ပသို့ ဖြန့်ဝေခြင်း မပြုရ။ ကျူးလွန်ပါက သက်ဆိုင်ရာ ဥပဒေအရ အရေးယူခြင်းခံရမည်';
    const warningImg = renderUnicodeTextBlockToImage(warningText, {
        fontSizePt: 8.5,
        maxWidthMm: contentWidth,
        color: '#b91c1c',
        backgroundColor: '#ffffff',
        fontWeight: 'bold',
    });

    const exportedByText = `Exported by: ${exportedBy || 'System'}`;
    const exportedByImg = hasComplexUnicode(exportedByText)
        ? renderUnicodeTextBlockToImage(exportedByText, {
              fontSizePt: 8.5,
              maxWidthMm: contentWidth,
              color: '#334155',
              backgroundColor: '#ffffff',
          })
        : null;

    const branchLabel = branchName ? `Branch: ${branchName}` : 'Branch: All Branches';
    const metaText = `${branchLabel}   |   Total Customers: ${customers.length.toLocaleString()}   |   Generated: ${new Date().toLocaleString()}`;
    const metaImg = hasComplexUnicode(metaText)
        ? renderUnicodeTextBlockToImage(metaText, {
              fontSizePt: 8.5,
              maxWidthMm: contentWidth,
              color: '#475569',
              backgroundColor: '#ffffff',
          })
        : null;

    const warningY = 8;
    const titleY = warningY + warningImg.heightMm + 4.5;
    const exportedByY = titleY + 5;
    const metaY = exportedByY + (exportedByImg ? exportedByImg.heightMm + 1.5 : 4.5);
    const dividerY = metaY + (metaImg ? metaImg.heightMm + 2 : 3.5);
    const tableStartY = dividerY + 3;

    const drawPageHeader = () => {
        doc.addImage(
            warningImg.dataUrl,
            warningImg.format,
            margin,
            warningY,
            warningImg.widthMm,
            warningImg.heightMm,
            'header-warning'
        );

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(15, 23, 42);
        doc.text('Jewelry Sales - Customer List', margin, titleY);

        if (exportedByImg) {
            doc.addImage(
                exportedByImg.dataUrl,
                exportedByImg.format,
                margin,
                exportedByY - 3,
                exportedByImg.widthMm,
                exportedByImg.heightMm,
                'header-exported-by'
            );
        } else {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor(51, 65, 85);
            doc.text(exportedByText, margin, exportedByY);
        }

        if (metaImg) {
            doc.addImage(
                metaImg.dataUrl,
                metaImg.format,
                margin,
                metaY - 3,
                metaImg.widthMm,
                metaImg.heightMm,
                'header-meta'
            );
        } else {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor(71, 85, 105);
            doc.text(metaText, margin, metaY);
        }

        doc.setDrawColor(203, 213, 225);
        doc.setLineWidth(0.25);
        doc.line(margin, dividerY, pageWidth - margin, dividerY);
    };

    const colWidths = [14, 86, 48, 34]; // 182mm total (A4 210mm - 28mm margins)
    const imageCache = new Map();
    let aliasCounter = 0;

    const body = customers.map((c, idx) => [
        String(idx + 1),
        c.name || '-',
        c.phone || '-',
        String(Number(c.sales_count || 0).toLocaleString()),
    ]);

    autoTable(doc, {
        startY: tableStartY,
        margin: { top: tableStartY, left: margin, right: margin, bottom: 16 },
        head: [['#', 'Customer Name', 'Phone Number', 'Sale Voucher Count']],
        body,
        theme: 'grid',
        styles: {
            font: 'helvetica',
            fontSize: 9,
            cellPadding: 2.4,
            textColor: [30, 41, 59],
            lineColor: [203, 213, 225],
            lineWidth: 0.2,
            valign: 'middle',
        },
        headStyles: {
            fillColor: [15, 23, 42],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 9,
        },
        alternateRowStyles: {
            fillColor: [248, 250, 252],
        },
        columnStyles: {
            0: { cellWidth: colWidths[0], halign: 'center' },
            1: { cellWidth: colWidths[1], halign: 'left' },
            2: { cellWidth: colWidths[2], halign: 'left' },
            3: { cellWidth: colWidths[3], halign: 'right' },
        },
        didParseCell: (data) => {
            if (data.section !== 'body') return;
            const rawVal = data.cell.raw;
            if (!rawVal || typeof rawVal === 'object') return;
            const strVal = String(rawVal);
            if (hasComplexUnicode(strVal)) {
                const colW = colWidths[data.column.index] || 50;
                const innerW = Math.max(15, colW - 5);
                const isBoldCol = data.column.index === 1;
                const bg = data.row.index % 2 === 1 ? '#f8fafc' : '#ffffff';
                const cacheKey = `${strVal}|${isBoldCol ? 'b' : 'n'}|${bg}|${innerW}`;

                let unicodeImg = imageCache.get(cacheKey);
                if (!unicodeImg) {
                    unicodeImg = renderUnicodeTextBlockToImage(strVal, {
                        fontSizePt: 8.5,
                        maxWidthMm: innerW,
                        color: isBoldCol ? '#0f172a' : '#334155',
                        backgroundColor: bg,
                        fontWeight: isBoldCol ? 'bold' : 'normal',
                    });
                    unicodeImg.alias = `uimg_${aliasCounter++}`;
                    imageCache.set(cacheKey, unicodeImg);
                }

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
                doc.addImage(img.dataUrl, img.format, drawX, drawY, img.widthMm, img.heightMm, img.alias);
            }
        },
        didDrawPage: (data) => {
            drawPageHeader();
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(100, 116, 139);
            const pageCount = doc.internal.getNumberOfPages();
            doc.text(
                `Page ${data.pageNumber} of ${pageCount}`,
                pageWidth - margin,
                pageHeight - 8,
                { align: 'right' }
            );
        },
    });

    const dateSuffix = new Date().toISOString().slice(0, 10);
    const branchSlug = branchName
        ? `-${String(branchName).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`
        : '';
    doc.save(`jewelry-customers${branchSlug}-${dateSuffix}.pdf`);
}
