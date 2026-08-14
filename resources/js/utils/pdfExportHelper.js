import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';

/**
 * Ensures the Pyidaungsu webfont is fully loaded in the browser before rendering.
 */
const ensureFontLoaded = async () => {
    try {
        if (document.fonts && document.fonts.load) {
            await Promise.all([
                document.fonts.load('12px Pyidaungsu'),
                document.fonts.load('bold 12px Pyidaungsu'),
            ]);
            await document.fonts.ready;
        }
    } catch (e) {
        console.warn('Font loading check failed:', e);
    }
};

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
 * Generates single row HTML for the detailed issues table.
 */
const createRowHtml = (item, rowNumber, formatDateCustom) => {
    const isContinuous24h = item.priority_clock_type === 'continuous_24h' || item.priority_code === 'P1';
    const priorityColor = item.priority_code === 'P1' ? '#dc2626' : (item.priority_code === 'P2' ? '#d97706' : '#2563eb');
    
    const titleText = cleanText(item.title);
    const descText = cleanText(item.description);
    const solText = cleanText(item.proposed_solution);

    const formattedIssueAt = formatDateCustom ? formatDateCustom(item.issue_at) : (item.issue_at || 'N/A');
    const formattedDueDate = formatDateCustom ? formatDateCustom(item.due_date) : (item.due_date || 'N/A');
    const rowBg = rowNumber % 2 === 0 ? '#f8f5ff' : '#ffffff';

    return `
        <tr style="background-color: ${rowBg}; border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 6px 8px; text-align: center; font-size: 11px; font-weight: 600; color: #4b5563; width: 35px; vertical-align: top; border-right: 1px solid #f1f5f9;">
                ${rowNumber}
            </td>
            <td style="padding: 6px 12px; width: 520px; font-size: 11px; color: #111827; word-break: break-word; overflow-wrap: anywhere; white-space: normal; line-height: 1.45; font-family: 'Pyidaungsu', sans-serif; vertical-align: top; border-right: 1px solid #f1f5f9;">
                <div style="margin-bottom: 3px;">
                    <span style="font-weight: bold; color: #1e1b4b;">Title:</span>
                    <span style="color: #0f172a; margin-left: 3px;">${titleText || '-'}</span>
                </div>
                <div style="margin-bottom: 3px;">
                    <span style="font-weight: bold; color: #4338ca;">Description:</span>
                    <span style="color: #334155; margin-left: 3px;">${descText || '-'}</span>
                </div>
                <div>
                    <span style="font-weight: bold; color: #047857;">Propose Solution:</span>
                    <span style="color: #1f2937; margin-left: 3px;">${solText || '-'}</span>
                </div>
            </td>
            <td style="padding: 6px 10px; font-size: 10.5px; color: #374151; width: 140px; word-break: break-word; overflow-wrap: anywhere; white-space: normal; vertical-align: top; border-right: 1px solid #f1f5f9;">
                ${item.category_name || 'N/A'}
            </td>
            <td style="padding: 6px 8px; text-align: center; font-size: 10.5px; width: 120px; vertical-align: top; border-right: 1px solid #f1f5f9;">
                <div style="font-weight: bold; color: ${priorityColor};">${item.priority_name || 'N/A'}</div>
                ${isContinuous24h ? `<div style="font-size: 9px; color: #7c3aed; font-weight: 600; margin-top: 2px;">(24/7 Clock)</div>` : ''}
            </td>
            <td style="padding: 6px 8px; text-align: center; font-size: 10px; color: #4b5563; width: 130px; white-space: nowrap; vertical-align: top; border-right: 1px solid #f1f5f9;">
                ${formattedIssueAt}
            </td>
            <td style="padding: 6px 8px; text-align: center; font-size: 10px; color: #4b5563; width: 130px; white-space: nowrap; vertical-align: top;">
                ${formattedDueDate}
            </td>
        </tr>
    `;
};

/**
 * Dynamically partitions items into pages based on actual rendered row heights.
 * Ensures maximum page utilization without overflowing or clipping.
 */
const partitionItemsDynamically = (sortedItems, formatDateCustom, measureContainer) => {
    if (sortedItems.length === 0) return [[]];

    // Measure each row's height in DOM
    const testTable = document.createElement('table');
    testTable.style.width = '1074px';
    testTable.style.borderCollapse = 'collapse';
    testTable.style.fontFamily = `'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;
    
    testTable.innerHTML = `
        <tbody>
            ${sortedItems.map((item, idx) => createRowHtml(item, idx + 1, formatDateCustom)).join('')}
        </tbody>
    `;
    measureContainer.appendChild(testTable);
    const rowElements = testTable.querySelectorAll('tbody > tr');

    const rowHeights = Array.from(rowElements).map((tr) => tr.offsetHeight || 50);
    measureContainer.innerHTML = '';

    // Constants for page heights (A4 Landscape = 793px)
    const MAX_PAGE_BODY_HEIGHT = 580; // Available for rows on regular pages
    const LAST_PAGE_BODY_HEIGHT = 440; // Available for rows on last page with signature

    const pages = [];
    let currentPage = [];
    let currentHeight = 0;

    for (let i = 0; i < sortedItems.length; i++) {
        const item = sortedItems[i];
        const rHeight = rowHeights[i];
        const remainingItems = sortedItems.length - 1 - i;

        // Check if remaining items could all fit on this page as the last page
        const isPotentialLastPage = remainingItems === 0;
        const maxAllowed = isPotentialLastPage ? LAST_PAGE_BODY_HEIGHT : MAX_PAGE_BODY_HEIGHT;

        if (currentPage.length > 0 && currentHeight + rHeight > maxAllowed) {
            pages.push(currentPage);
            currentPage = [item];
            currentHeight = rHeight;
        } else {
            currentPage.push(item);
            currentHeight += rHeight;
        }
    }

    if (currentPage.length > 0) {
        pages.push(currentPage);
    }

    return pages;
};

/**
 * Exports the Detailed IT Issues List PDF with:
 * - Dynamic row packing (maximizes rows per page based on content height)
 * - Concatenated Title, Description, and Propose Solution with bold prefixes
 * - 100% native Myanmar Unicode text shaping (Pyidaungsu font)
 * - Sorting: Continuous 24/7 clock first, then by due date (today first)
 * - Multi-page pagination and signature blocks
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
    await ensureFontLoaded();

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
    const todayDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: '2-digit' });
    const generatedAt = new Date().toLocaleString();

    // Sort items according to requirements
    const rawItems = report.items || [];
    const sortedItems = sortReportItems(rawItems);

    // Container for rendering hidden DOM elements
    const renderContainer = document.createElement('div');
    renderContainer.style.position = 'fixed';
    renderContainer.style.left = '-99999px';
    renderContainer.style.top = '0';
    renderContainer.style.width = '1122px'; // Standard A4 Landscape width at 96 DPI
    renderContainer.style.zIndex = '-9999';
    renderContainer.style.backgroundColor = '#ffffff';
    renderContainer.style.fontFamily = `'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;
    document.body.appendChild(renderContainer);

    try {
        // Dynamically partition items into pages based on actual rendered height
        const pagesData = partitionItemsDynamically(sortedItems, formatDateCustom, renderContainer);
        const totalPages = pagesData.length;

        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        let currentItemNumber = 1;

        for (let pageIdx = 0; pageIdx < totalPages; pageIdx++) {
            const pageItems = pagesData[pageIdx];
            const isLastPage = pageIdx === totalPages - 1;

            const pageElement = document.createElement('div');
            pageElement.style.width = '1122px';
            pageElement.style.minHeight = '793px'; // Standard A4 Landscape height
            pageElement.style.maxHeight = '793px';
            pageElement.style.height = '793px';
            pageElement.style.padding = '18px 24px';
            pageElement.style.boxSizing = 'border-box';
            pageElement.style.backgroundColor = '#ffffff';
            pageElement.style.position = 'relative';
            pageElement.style.display = 'flex';
            pageElement.style.flexDirection = 'column';
            pageElement.style.justifyContent = 'space-between';

            // Top Content (Header + Info Bar + Table)
            const topWrapper = document.createElement('div');

            // Header Banner
            topWrapper.innerHTML = `
                <div style="background-color: #3b0764; color: #ffffff; padding: 10px 16px; border-radius: 6px 6px 0 0; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 16px; font-weight: bold; letter-spacing: 0.3px; font-family: 'Pyidaungsu', sans-serif;">Issue Tracking Detail Report</div>
                        <div style="font-size: 10.5px; opacity: 0.9; margin-top: 2px;">${app_name} &nbsp;|&nbsp; ${resolverLabel}</div>
                    </div>
                    <div style="font-size: 10px; opacity: 0.85; text-align: right;">
                        Generated: ${generatedAt}
                    </div>
                </div>

                <div style="background-color: #f5f0ff; color: #3b0764; padding: 6px 16px; border-radius: 0 0 6px 6px; font-size: 10.5px; margin-bottom: 10px; border: 1px solid #e9d5ff; border-top: none;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 2px; flex-wrap: wrap; gap: 8px;">
                        <span>${reportAtLabel}</span>
                        <span>Resolver: ${resolverLabel}</span>
                        <span>Categories: ${catLabel}</span>
                        <span>Depts: ${deptLabel}</span>
                    </div>
                    <div style="font-size: 9.5px; color: #581c87; opacity: 0.9;">
                        Continuous 24/7 Clock issues are listed first, followed by issues sorted by Due Date (today/upcoming first).
                    </div>
                </div>
            `;

            // Table Rows
            let tableRowsHtml = '';
            if (pageItems.length === 0) {
                tableRowsHtml = `
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280; font-size: 12px;">No issues found for the selected criteria.</td>
                    </tr>
                `;
            } else {
                pageItems.forEach((item) => {
                    tableRowsHtml += createRowHtml(item, currentItemNumber++, formatDateCustom);
                });
            }

            const tableHtml = `
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden;">
                    <thead>
                        <tr style="background-color: #3b0764; color: #ffffff; font-size: 11px; text-align: center;">
                            <th style="padding: 7px 6px; width: 35px; border-right: 1px solid #581c87;">#</th>
                            <th style="padding: 7px 12px; width: 520px; text-align: left; border-right: 1px solid #581c87;">Issue Details (Title / Description / Solution)</th>
                            <th style="padding: 7px 10px; width: 140px; text-align: left; border-right: 1px solid #581c87;">Category</th>
                            <th style="padding: 7px 8px; width: 120px; border-right: 1px solid #581c87;">Priority</th>
                            <th style="padding: 7px 8px; width: 130px; border-right: 1px solid #581c87;">Issue At</th>
                            <th style="padding: 7px 8px; width: 130px;">Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHtml}
                    </tbody>
                </table>
            `;

            topWrapper.innerHTML += tableHtml;
            pageElement.appendChild(topWrapper);

            // Bottom Section (Signature on last page, Footer on every page)
            const bottomWrapper = document.createElement('div');

            let signatureHtml = '';
            if (isLastPage) {
                signatureHtml = `
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #cbd5e1; display: flex; justify-content: space-between; font-size: 10.5px; color: #374151;">
                        <!-- Left Exporter -->
                        <div style="width: 380px;">
                            <div style="font-weight: bold; color: #1e293b; margin-bottom: 6px;">Report Exported By:</div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                                <span style="width: 50px; font-weight: 500;">Name:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8; font-weight: bold; padding-left: 4px; color: #0f172a;">${fullExporterText}</span>
                            </div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                                <span style="width: 65px; font-weight: 500;">Signature:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: baseline;">
                                <span style="width: 50px; font-weight: 500;">Date:</span>
                                <span style="padding-left: 4px; font-weight: 600;">${todayDate}</span>
                            </div>
                        </div>

                        <!-- Right Acknowledged -->
                        <div style="width: 380px;">
                            <div style="font-weight: bold; color: #1e293b; margin-bottom: 6px;">Acknowledged By:</div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                                <span style="width: 50px; font-weight: 500;">Name:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                                <span style="width: 65px; font-weight: 500;">Signature:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: baseline;">
                                <span style="width: 50px; font-weight: 500;">Date:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            const footerHtml = `
                <div style="margin-top: 8px; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; font-style: italic;">
                    <div>${app_name} — IT Issue Detail Report — ${reportAtLabel}</div>
                    <div>Page ${pageIdx + 1} of ${totalPages}</div>
                </div>
            `;

            bottomWrapper.innerHTML = signatureHtml + footerHtml;
            pageElement.appendChild(bottomWrapper);

            renderContainer.innerHTML = '';
            renderContainer.appendChild(pageElement);

            const canvas = await html2canvas(pageElement, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
            });

            const imgData = canvas.toDataURL('image/jpeg', 0.98);
            if (pageIdx > 0) {
                doc.addPage('a4', 'landscape');
            }
            doc.addImage(imgData, 'JPEG', 0, 0, 297, 210);
        }

        const filename = `IT_Issue_Detail_Report_${startDate || 'all'}_to_${endDate || 'all'}.pdf`;
        doc.save(filename);
    } finally {
        if (renderContainer && renderContainer.parentNode) {
            renderContainer.parentNode.removeChild(renderContainer);
        }
    }
};

/**
 * Exports the Category Summary Report PDF.
 */
export const exportCategorySummaryPDF = async ({
    report,
    filters = {},
    app_name = 'OrderDemo',
}) => {
    await ensureFontLoaded();

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

    const renderContainer = document.createElement('div');
    renderContainer.style.position = 'fixed';
    renderContainer.style.left = '-99999px';
    renderContainer.style.top = '0';
    renderContainer.style.width = '793px'; // Standard A4 Portrait width at 96 DPI
    renderContainer.style.zIndex = '-9999';
    renderContainer.style.backgroundColor = '#ffffff';
    renderContainer.style.fontFamily = `'Pyidaungsu', 'Segoe UI', Arial, sans-serif`;
    document.body.appendChild(renderContainer);

    try {
        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        const pageElement = document.createElement('div');
        pageElement.style.width = '793px';
        pageElement.style.minHeight = '1122px'; // Standard A4 Portrait height
        pageElement.style.maxHeight = '1122px';
        pageElement.style.height = '1122px';
        pageElement.style.padding = '24px 28px';
        pageElement.style.boxSizing = 'border-box';
        pageElement.style.backgroundColor = '#ffffff';
        pageElement.style.display = 'flex';
        pageElement.style.flexDirection = 'column';
        pageElement.style.justifyContent = 'space-between';

        let rowsHtml = '';
        rows.forEach((r, idx) => {
            const rowBg = idx % 2 === 1 ? '#f8f5ff' : '#ffffff';
            const rateColor = r.rateNum >= 80 ? '#15803d' : (r.rateNum >= 50 ? '#a16207' : '#b91c1c');
            rowsHtml += `
                <tr style="background-color: ${rowBg}; border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 8px 10px; text-align: center; font-size: 11px; font-weight: 600; color: #4b5563; border-right: 1px solid #f1f5f9;">${r.idx}</td>
                    <td style="padding: 8px 12px; font-size: 12px; font-weight: 500; color: #111827; font-family: 'Pyidaungsu', sans-serif; border-right: 1px solid #f1f5f9;">${r.cat}</td>
                    <td style="padding: 8px 10px; text-align: center; font-size: 11.5px; font-weight: bold; color: #15803d; border-right: 1px solid #f1f5f9;">${r.success}</td>
                    <td style="padding: 8px 10px; text-align: center; font-size: 11.5px; font-weight: bold; color: #b91c1c; border-right: 1px solid #f1f5f9;">${r.fail}</td>
                    <td style="padding: 8px 10px; text-align: center; font-size: 11.5px; font-weight: bold; color: #b91c1c; border-right: 1px solid #f1f5f9;">${r.failPoints}</td>
                    <td style="padding: 8px 10px; text-align: center; font-size: 12px; font-weight: bold; color: ${rateColor};">${r.rate}</td>
                </tr>
            `;
        });

        const serviceCreditMsg = svcCredit > 0
            ? `<div style="background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px; font-size: 11.5px; font-weight: bold; text-align: center; margin-top: 16px;">
                ⚠️ Service Credit Refund Due: ${svcCredit}% — Maintenance credit must be applied for this period.
               </div>`
            : `<div style="background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; border-radius: 6px; padding: 10px 14px; font-size: 11.5px; font-weight: bold; text-align: center; margin-top: 16px;">
                ✓ No Service Credit Refund Required — SLA targets were met for this period.
               </div>`;

        pageElement.innerHTML = `
            <div>
                <!-- Banner -->
                <div style="background-color: #3b0764; color: #ffffff; padding: 14px 20px; border-radius: 6px 6px 0 0;">
                    <div style="font-size: 17px; font-weight: bold;">SLA & Service Credit — Category Summary Report</div>
                    <div style="font-size: 11px; opacity: 0.9; margin-top: 3px;">${companyDisplay}</div>
                    <div style="font-size: 10.5px; opacity: 0.8; margin-top: 2px;">Generated: ${todayDate}</div>
                </div>

                <!-- Info Block -->
                <div style="background-color: #f0f0ff; color: #1e1e50; padding: 10px 20px; border-radius: 0 0 6px 6px; border: 1px solid #e0e0fc; border-top: none; margin-bottom: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1.5fr; gap: 10px; font-size: 11px;">
                        <div>
                            <div style="font-weight: bold; color: #3b0764; font-size: 10px; margin-bottom: 2px;">REPORT PERIOD</div>
                            <div>${periodStr}</div>
                        </div>
                        <div>
                            <div style="font-weight: bold; color: #3b0764; font-size: 10px; margin-bottom: 2px;">RESOLVER SCOPE</div>
                            <div>${resolverLabel}</div>
                        </div>
                        <div>
                            <div style="font-weight: bold; color: #3b0764; font-size: 10px; margin-bottom: 2px;">REPORT EXPLAINS</div>
                            <div style="font-size: 10px; font-style: italic; color: #475569;">Shows SLA-passed/failed IT issues per category, weighted fail points, and resolution rate.</div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden;">
                    <thead>
                        <tr style="background-color: #3b0764; color: #ffffff; font-size: 11.5px;">
                            <th style="padding: 10px 8px; width: 40px; text-align: center; border-right: 1px solid #581c87;">#</th>
                            <th style="padding: 10px 12px; text-align: left; border-right: 1px solid #581c87;">Category</th>
                            <th style="padding: 10px 10px; width: 90px; text-align: center; border-right: 1px solid #581c87;">Success ✔</th>
                            <th style="padding: 10px 10px; width: 90px; text-align: center; border-right: 1px solid #581c87;">Fail ✖</th>
                            <th style="padding: 10px 10px; width: 95px; text-align: center; border-right: 1px solid #581c87;">Fail Points</th>
                            <th style="padding: 10px 10px; width: 105px; text-align: center;">Success Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #0d9488; color: #ffffff; font-size: 12px; font-weight: bold;">
                            <td style="padding: 10px 8px; text-align: center;"></td>
                            <td style="padding: 10px 12px;">GRAND TOTAL</td>
                            <td style="padding: 10px 10px; text-align: center;">${totalSuccess}</td>
                            <td style="padding: 10px 10px; text-align: center;">${totalFail}</td>
                            <td style="padding: 10px 10px; text-align: center;">${totalFP}</td>
                            <td style="padding: 10px 10px; text-align: center;">${grandRate}%</td>
                        </tr>
                    </tfoot>
                </table>

                ${serviceCreditMsg}
            </div>

            <!-- Footer -->
            <div style="text-align: center; font-size: 10px; color: #94a3b8; font-style: italic; margin-top: 20px;">
                Fail Point Weightage: P1 = 10 pts  |  P2 = 5 pts  |  P3/P4 = 1 pt
            </div>
        `;

        renderContainer.innerHTML = '';
        renderContainer.appendChild(pageElement);

        const canvas = await html2canvas(pageElement, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff',
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.98);
        doc.addImage(imgData, 'JPEG', 0, 0, 210, 297);

        const filename = `SLA_Category_Summary_${periodType}_${startDate || 'all'}_to_${endDate || 'all'}.pdf`;
        doc.save(filename);
    } finally {
        if (renderContainer && renderContainer.parentNode) {
            renderContainer.parentNode.removeChild(renderContainer);
        }
    }
};
