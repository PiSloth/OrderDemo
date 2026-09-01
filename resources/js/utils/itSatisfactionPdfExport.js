import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { applyPyidaungsuFont } from './pdfFont';

/**
 * Generates and downloads a structured IT Department Satisfaction Survey PDF Report.
 * Groups responses by Rating Score (5 Stars down to 1 Star) and lists all feedbacks
 * under each score as a clean bullet list.
 *
 * @param {Object} reportData
 * @param {Object} reportData.survey Survey information (title, date period)
 * @param {Object} reportData.analytics Summary metrics (total_responses, average_score, satisfaction_rate)
 * @param {Array} reportData.score_groups Grouped ratings (score, total_count, percentage, feedbacks)
 */
export async function exportSatisfactionPdf(reportData = {}) {
    const {
        survey = {},
        analytics = {},
        score_groups = []
    } = reportData;

    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    let fontName = 'helvetica';
    try {
        fontName = await applyPyidaungsuFont(doc);
    } catch (e) {
        console.warn('Pyidaungsu font load error, using helvetica fallback:', e);
        fontName = 'helvetica';
    }

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 14;
    let currentY = 16;

    const primaryColor = [79, 70, 229]; // Indigo-600 #4f46e5
    const darkText = [15, 23, 42]; // Slate-900
    const mutedText = [100, 116, 139]; // Slate-500
    const lightBg = [248, 250, 252]; // Slate-50

    // 1. Header Title & Subtitle
    try {
        doc.setFont(fontName, 'bold');
    } catch (e) {
        doc.setFont('helvetica', 'bold');
    }
    doc.setFontSize(16);
    doc.setTextColor(...primaryColor);
    doc.text('IT Department Satisfaction Survey Report', margin, currentY);

    currentY += 6;
    try {
        doc.setFont(fontName, 'normal');
    } catch (e) {
        doc.setFont('helvetica', 'normal');
    }
    doc.setFontSize(9);
    doc.setTextColor(...mutedText);

    const periodText = survey?.start_date_formatted 
        ? `Campaign Period: ${survey.start_date_formatted} to ${survey.end_date_formatted}`
        : 'All Survey Periods';
    const nowStr = new Date().toLocaleString();
    doc.text(`${periodText} | Generated on: ${nowStr}`, margin, currentY);

    currentY += 8;

    // 2. Executive Summary KPI Box
    const totalResponses = analytics?.total_responses ?? 0;
    const avgScore = analytics?.average_score ?? 0;
    const satRate = analytics?.satisfaction_rate ?? 0;

    doc.setFillColor(...lightBg);
    doc.setDrawColor(226, 232, 240);
    doc.roundedRect(margin, currentY, pageWidth - margin * 2, 16, 2, 2, 'FD');

    try {
        doc.setFont(fontName, 'bold');
    } catch (e) {
        doc.setFont('helvetica', 'bold');
    }
    doc.setFontSize(9);
    doc.setTextColor(...darkText);

    const boxWidth = (pageWidth - margin * 2) / 3;
    doc.text(`Total Submissions: ${totalResponses}`, margin + 6, currentY + 6);
    doc.text(`Average Score: ${avgScore} / 5.0`, margin + boxWidth + 6, currentY + 6);
    doc.text(`Satisfaction Rate (4-5 Star): ${satRate}%`, margin + boxWidth * 2 + 6, currentY + 6);

    try {
        doc.setFont(fontName, 'normal');
    } catch (e) {
        doc.setFont('helvetica', 'normal');
    }
    doc.setFontSize(7.5);
    doc.setTextColor(...mutedText);
    doc.text('All departments (Anonymous)', margin + 6, currentY + 11);
    doc.text('Overall user satisfaction', margin + boxWidth + 6, currentY + 11);
    doc.text('Positive sentiment ratio', margin + boxWidth * 2 + 6, currentY + 11);

    currentY += 22;

    // 3. Summary Score Breakdown Table
    const starLabels = {
        5: '5 Stars (Very Satisfied)',
        4: '4 Stars (Satisfied)',
        3: '3 Stars (Neutral)',
        2: '2 Stars (Dissatisfied)',
        1: '1 Star (Very Dissatisfied)',
    };

    const scoreBreakdownRows = score_groups.map((group) => {
        const writtenFeedbacksCount = group.feedbacks ? group.feedbacks.filter(f => f.feedback && f.feedback.length > 0).length : 0;
        return [
            `${group.score} Star`,
            starLabels[group.score] || `${group.score} Stars`,
            `${group.total_count} responses`,
            `${group.percentage}%`,
            `${writtenFeedbacksCount} comments`,
        ];
    });

    autoTable(doc, {
        startY: currentY,
        head: [['Rating', 'Score Level', 'Total Submissions', 'Share (%)', 'Written Comments']],
        body: scoreBreakdownRows,
        margin: { left: margin, right: margin },
        styles: {
            font: fontName,
            fontSize: 8.5,
            cellPadding: 2.5,
            lineColor: [226, 232, 240],
            lineWidth: 0.2,
        },
        headStyles: {
            fillColor: primaryColor,
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 9,
        },
        alternateRowStyles: {
            fillColor: [248, 250, 252],
        },
    });

    currentY = doc.lastAutoTable.finalY + 10;

    // 4. Detailed Feedbacks Grouped by Rating Score (Bullet List)
    const scoreHeadings = {
        5: { title: '[5 Stars] Very Satisfied', color: [16, 185, 129] }, // Emerald
        4: { title: '[4 Stars] Satisfied', color: [59, 130, 246] }, // Blue
        3: { title: '[3 Stars] Neutral', color: [245, 158, 11] }, // Amber
        2: { title: '[2 Stars] Dissatisfied - Action Required', color: [239, 68, 68] }, // Red
        1: { title: '[1 Star] Very Dissatisfied - Action Required', color: [220, 38, 38] }, // Red
    };

    for (const group of score_groups) {
        const headerInfo = scoreHeadings[group.score] || {
            title: `[${group.score} Stars]`,
            color: primaryColor,
        };

        // Check for page overflow before rendering score section
        if (currentY > pageHeight - 35) {
            doc.addPage();
            currentY = 16;
        }

        // Score Section Header Banner
        doc.setFillColor(241, 245, 249);
        doc.setDrawColor(...headerInfo.color);
        doc.roundedRect(margin, currentY, pageWidth - margin * 2, 7, 1, 1, 'FD');

        try {
            doc.setFont(fontName, 'bold');
        } catch (e) {
            doc.setFont('helvetica', 'bold');
        }
        doc.setFontSize(9.5);
        doc.setTextColor(...headerInfo.color);
        doc.text(
            `${headerInfo.title} - ${group.total_count} Submissions (${group.percentage}%)`,
            margin + 4,
            currentY + 4.8
        );

        currentY += 10;

        // Feedback List under this score
        const writtenFeedbacks = group.feedbacks ? group.feedbacks.filter(f => f.feedback && f.feedback.trim().length > 0) : [];

        if (writtenFeedbacks.length === 0) {
            try {
                doc.setFont(fontName, 'normal');
            } catch (e) {
                doc.setFont('helvetica', 'normal');
            }
            doc.setFontSize(8.5);
            doc.setTextColor(...mutedText);
            doc.text('- No written feedback comments submitted for this rating score.', margin + 6, currentY);
            currentY += 7;
        } else {
            writtenFeedbacks.forEach((item, index) => {
                if (currentY > pageHeight - 25) {
                    doc.addPage();
                    currentY = 16;
                }

                try {
                    doc.setFont(fontName, 'normal');
                } catch (e) {
                    doc.setFont('helvetica', 'normal');
                }
                doc.setFontSize(8.5);
                doc.setTextColor(...darkText);

                const deptTag = `[${item.department_name || 'General'}]`;
                const dateTag = item.submitted_at ? `(${item.submitted_at})` : '';
                const bulletPrefix = `- #${index + 1} ${deptTag} ${dateTag}: `;
                const textContent = `"${item.feedback}"`;
                
                const fullText = `${bulletPrefix}${textContent}`;
                const splitText = doc.splitTextToSize(fullText, pageWidth - margin * 2 - 8);

                doc.text(splitText, margin + 4, currentY);
                currentY += splitText.length * 4.5 + 2.5;
            });
        }

        currentY += 4;
    }

    // 5. Page Numbering Footer
    const totalPages = doc.internal.getNumberOfPages();
    for (let p = 1; p <= totalPages; p++) {
        doc.setPage(p);
        try {
            doc.setFont(fontName, 'normal');
        } catch (e) {
            doc.setFont('helvetica', 'normal');
        }
        doc.setFontSize(7.5);
        doc.setTextColor(148, 163, 184);
        doc.text(
            `Page ${p} of ${totalPages} - IT Department Satisfaction Survey Confidential Report`,
            pageWidth / 2,
            pageHeight - 8,
            { align: 'center' }
        );
    }

    // 6. Download the PDF
    const filename = `IT_Satisfaction_Report_${new Date().toISOString().slice(0, 10)}.pdf`;
    doc.save(filename);
}
