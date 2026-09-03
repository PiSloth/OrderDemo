import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';

/**
 * Ensures webfonts (including Myanmar Pyidaungsu) are fully loaded before rendering.
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
 * Export Todo Report PDF
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
    await ensureFontLoaded();

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

    // Container for off-screen rendering
    const renderContainer = document.createElement('div');
    renderContainer.style.position = 'fixed';
    renderContainer.style.left = '-99999px';
    renderContainer.style.top = '0';
    renderContainer.style.width = '1122px'; // A4 Landscape at 96 DPI
    renderContainer.style.zIndex = '-9999';
    renderContainer.style.backgroundColor = '#ffffff';
    renderContainer.style.fontFamily = `'Segoe UI', 'Pyidaungsu', -apple-system, BlinkMacSystemFont, Roboto, sans-serif`;
    document.body.appendChild(renderContainer);

    try {
        // Build items list per group
        const groupSections = groups.map((g) => {
            const groupTasks = g.tasks || [];
            const rowsHtml = groupTasks.map((t, idx) => {
                const stName = (t.status?.status || 'Open').toLowerCase();
                const isSuccess = stName.includes('complete') || stName.includes('success') || stName.includes('done');
                const isLate = !isSuccess && t.due_date && new Date(t.due_date) < new Date();

                // SLA Badge - clean colored text
                let slaBadge = `<strong style="color: #d97706; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif; white-space: nowrap;">⏳ In Progress</strong>`;
                if (isSuccess) {
                    slaBadge = `<strong style="color: #047857; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif; white-space: nowrap;">✅ Success</strong>`;
                } else if (isLate) {
                    slaBadge = `<strong style="color: #dc2626; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif; white-space: nowrap;">🚨 Overdue</strong>`;
                }

                // Status Badge - clean colored text
                let stColor = '#334155';
                if (isSuccess) {
                    stColor = '#047857';
                } else if (stName.includes('fail') || stName.includes('reject')) {
                    stColor = '#dc2626';
                } else if (stName.includes('process') || stName.includes('progress')) {
                    stColor = '#2563eb';
                }

                const statusBadge = `<strong style="color: ${stColor}; font-size: 11px; font-family: 'Pyidaungsu', 'Segoe UI', Arial, sans-serif; white-space: nowrap;">${cleanText(t.status?.status || 'Open')}</strong>`;

                const deptName = t.assigned_user?.department?.name || t.department?.name || 'N/A';
                const rowBg = idx % 2 === 0 ? '#ffffff' : '#f8fafc';

                return `
                    <tr style="background-color: ${rowBg}; border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 8px 8px; text-align: center; font-size: 10.5px; font-weight: 600; color: #64748b; width: 35px; vertical-align: middle; border-right: 1px solid #f1f5f9;">${idx + 1}</td>
                        <td style="padding: 8px 12px; font-size: 11px; font-weight: 600; color: #0f172a; word-break: break-word; line-height: 1.45; font-family: 'Pyidaungsu', 'Segoe UI', sans-serif; vertical-align: middle; border-right: 1px solid #f1f5f9;">
                            ${cleanText(t.task)}
                        </td>
                        <td style="padding: 8px 10px; font-size: 10.5px; color: #334155; width: 180px; vertical-align: middle; border-right: 1px solid #f1f5f9;">
                            <div style="font-weight: 600; color: #1e293b;">${cleanText(t.assigned_user?.name || 'Unassigned')}</div>
                            <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">🏢 ${cleanText(deptName)}</div>
                        </td>
                        <td style="padding: 8px 10px; text-align: center; font-size: 10px; color: #475569; width: 140px; white-space: nowrap; vertical-align: middle; border-right: 1px solid #f1f5f9;">
                            ${formatDateTime(t.due_date)}
                        </td>
                        <td style="padding: 8px 10px; text-align: center; width: 110px; vertical-align: middle; border-right: 1px solid #f1f5f9;">
                            ${statusBadge}
                        </td>
                        <td style="padding: 8px 10px; text-align: center; width: 110px; vertical-align: middle;">
                            ${slaBadge}
                        </td>
                    </tr>
                `;
            }).join('');

            return {
                title: g.title,
                categoryName: g.categoryName,
                priorityLevel: g.priorityLevel,
                duration: g.duration,
                color: g.color || '#4f46e5',
                total: g.total,
                completed: g.completed,
                overdue: g.overdue,
                successRate: g.successRate,
                overdueRate: g.overdueRate,
                tasksCount: groupTasks.length,
                rowsHtml,
            };
        });

        // Prepare Category by Priority & Success Rate Matrix Data
        let matrixData = categoryMatrix;
        let priorityCols = priorities && priorities.length > 0 ? priorities : [];

        if (!matrixData) {
            // Fallback computation from tasks & categories if not passed
            const catMap = {};
            (categories || []).forEach((c) => {
                catMap[String(c.id)] = {
                    id: c.id,
                    name: c.name,
                    totalTasks: 0,
                    completedTasks: 0,
                    priorityCounts: {},
                };
            });
            const uncategorized = {
                id: 'uncategorized',
                name: 'General / Uncategorized',
                totalTasks: 0,
                completedTasks: 0,
                priorityCounts: {},
            };

            tasks.forEach((t) => {
                const catId = t.due_time?.todo_category_id || t.todo_category_id;
                const targetCat = (catId && catMap[String(catId)]) ? catMap[String(catId)] : uncategorized;
                const prioId = t.due_time?.todo_priority_id || t.todo_priority_id || 'unknown';
                const stName = (t.status?.status || '').toLowerCase();
                const isCompleted = stName.includes('complete') || stName.includes('success') || stName.includes('done');

                if (!targetCat.priorityCounts[String(prioId)]) {
                    targetCat.priorityCounts[String(prioId)] = { total: 0, completed: 0 };
                }
                targetCat.priorityCounts[String(prioId)].total += 1;
                if (isCompleted) {
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
                    const c = row.priorityCounts[String(p.id)]?.total || 0;
                    columnTotals[String(p.id)] = (columnTotals[String(p.id)] || 0) + c;
                });
            });
            const overallRate = grandTotal > 0 ? ((grandCompleted / grandTotal) * 100).toFixed(1) : '0.0';
            matrixData = {
                rows: activeRows,
                columnTotals,
                grandTotal,
                grandCompleted,
                overallSuccessRate: overallRate,
            };
        }

        // Build HTML for Category by Priority Matrix Rows (only active categories with tasks > 0)
        const matrixRowsHtml = (matrixData.rows || []).map((row, idx) => {
            const rowBg = idx % 2 === 0 ? '#ffffff' : '#f8fafc';
            const rateNum = row.totalTasks > 0 ? ((row.completedTasks / row.totalTasks) * 100).toFixed(1) : '0.0';
            const num = parseFloat(rateNum);
            const rateBg = num >= 80 ? '#ecfdf5' : (num >= 50 ? '#fffbeb' : '#fef2f2');
            const rateColor = num >= 80 ? '#047857' : (num >= 50 ? '#b45309' : '#b91c1c');
            const rateBorder = num >= 80 ? '#a7f3d0' : (num >= 50 ? '#fde68a' : '#fecaca');

            return `
                <tr style="background-color: ${rowBg}; border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 7px 10px; font-size: 11px; font-weight: 700; color: #0f172a; border-right: 1px solid #f1f5f9; font-family: 'Pyidaungsu', 'Segoe UI', sans-serif;">
                        ${cleanText(row.name)}
                    </td>
                    ${priorityCols.map((prio) => {
                        const count = row.priorityCounts[String(prio.id)]?.total || 0;
                        return `
                            <td style="padding: 7px 8px; text-align: center; border-right: 1px solid #f1f5f9; vertical-align: middle;">
                                ${count > 0 ? `
                                    <strong style="color: ${prio.color_code || '#0f172a'}; font-size: 12px; font-family: 'Segoe UI', Arial, sans-serif;">
                                        ${count}
                                    </strong>
                                ` : `<span style="color: #cbd5e1; font-size: 11px;">-</span>`}
                            </td>
                        `;
                    }).join('')}
                    <td style="padding: 7px 10px; text-align: center; vertical-align: middle;">
                        <strong style="color: ${rateColor}; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif; white-space: nowrap;">
                            ${row.completedTasks}/${row.totalTasks} (${rateNum} %)
                        </strong>
                    </td>
                </tr>
            `;
        }).join('');

        // Pages array
        const pages = [];

        // Build Page 1 Content
        const page1Content = document.createElement('div');
        page1Content.innerHTML = `
            <!-- Top Banner -->
            <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%); color: #ffffff; padding: 14px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 18px; font-weight: 900; letter-spacing: 0.5px;">TODO OPERATIONS & SLA PERFORMANCE REPORT</div>
                    <div style="font-size: 11px; opacity: 0.9; margin-top: 3px;">
                        ${appName} &nbsp;|&nbsp; Task Execution, Due Time Grouping & SLA Compliance Audit
                    </div>
                </div>
                <div style="font-size: 10px; opacity: 0.85; text-align: right; line-height: 1.4;">
                    <div><strong>Generated:</strong> ${generatedAt}</div>
                    <div><strong>Report By:</strong> ${cleanText(fullExporterText)}</div>
                </div>
            </div>

            <!-- Filter Metadata Bar -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: none; padding: 8px 18px; border-radius: 0 0 8px 8px; font-size: 10.5px; margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div><span style="color: #64748b; font-weight: 600;">Report Month:</span> <strong style="color: #1e1b4b; font-size: 11px;">${selectedMonthLabel}</strong></div>
                    <div><span style="color: #64748b; font-weight: 600;">Department Scope:</span> <strong style="color: #1e1b4b; font-size: 11px;">${selectedDepartmentName}</strong></div>
                    <div><span style="color: #64748b; font-weight: 600;">Total Scope:</span> <strong style="color: #1e1b4b;">${totalTasks} Tasks Analyzed</strong></div>
                    <div><span style="color: #64748b; font-weight: 600;">Overall Success Rate:</span> <strong style="color: #047857; font-size: 11.5px;">${successRate}%</strong></div>
                    <div><span style="color: #64748b; font-weight: 600;">Overdue (Not Success):</span> <strong style="color: #b91c1c; font-size: 11.5px;">${overdueTasks} (${overdueRate}%)</strong></div>
                </div>
            </div>

            <!-- KPI Summary Cards Row -->
            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 16px;">
                <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; text-align: center;">
                    <div style="font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Tasks</div>
                    <div style="font-size: 20px; font-weight: 900; color: #0f172a; margin-top: 4px;">${totalTasks}</div>
                    <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">Filtered volume</div>
                </div>
                <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 10px; text-align: center;">
                    <div style="font-size: 9px; font-weight: 700; color: #047857; text-transform: uppercase;">Success (Closed)</div>
                    <div style="font-size: 20px; font-weight: 900; color: #047857; margin-top: 4px;">${completedTasks}</div>
                    <div style="font-size: 9px; color: #059669; margin-top: 2px;">Completed tasks</div>
                </div>
                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px; text-align: center;">
                    <div style="font-size: 9px; font-weight: 700; color: #b45309; text-transform: uppercase;">Pending / In Progress</div>
                    <div style="font-size: 20px; font-weight: 900; color: #d97706; margin-top: 4px;">${openTasks}</div>
                    <div style="font-size: 9px; color: #b45309; margin-top: 2px;">Awaiting completion</div>
                </div>
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; text-align: center;">
                    <div style="font-size: 9px; font-weight: 700; color: #b91c1c; text-transform: uppercase;">Overdue (Not Success)</div>
                    <div style="font-size: 20px; font-weight: 900; color: #dc2626; margin-top: 4px;">${overdueTasks}</div>
                    <div style="font-size: 9px; color: #b91c1c; margin-top: 2px;">Missed due cutoff</div>
                </div>
                <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; border-radius: 8px; padding: 10px; text-align: center;">
                    <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; opacity: 0.9;">Overall Success Rate</div>
                    <div style="font-size: 20px; font-weight: 900; margin-top: 4px;">${successRate}%</div>
                    <div style="font-size: 9px; opacity: 0.85; margin-top: 2px;">Completed vs Total</div>
                </div>
                <div style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; border-radius: 8px; padding: 10px; text-align: center;">
                    <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; opacity: 0.9;">Overdue Task Rate</div>
                    <div style="font-size: 20px; font-weight: 900; margin-top: 4px;">${overdueRate}%</div>
                    <div style="font-size: 9px; opacity: 0.85; margin-top: 2px;">SLA breach ratio</div>
                </div>
            </div>

            <!-- Category by Priority & Success Rate Table Header (Replaced Due Time Summary Div per user request) -->
            <div style="margin-bottom: 6px; display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-size: 13px; font-weight: 800; color: #0f172a;">Category by Priority & Success Rate</span>
                <span style="font-size: 10px; color: #64748b;">Performance cross-tabulation across priority levels and completion success rate</span>
            </div>

            <!-- Category by Priority & Success Rate Table -->
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-bottom: 12px;">
                <thead>
                    <tr style="background-color: #1e1b4b; color: #ffffff; font-size: 10.5px;">
                        <th style="padding: 7px 10px; text-align: left; border-right: 1px solid #312e81; min-width: 180px; vertical-align: middle;">Category</th>
                        ${priorityCols.map((prio) => `
                            <th style="padding: 8px 8px; text-align: center; border-right: 1px solid #312e81; min-width: 90px; vertical-align: middle;">
                                <span style="color: #ffffff; font-size: 11px; font-weight: 700; font-family: 'Segoe UI', Arial, sans-serif;">
                                    ${cleanText(prio.level)}
                                </span>
                            </th>
                        `).join('')}
                        <th style="padding: 8px 10px; width: 140px; text-align: center; vertical-align: middle; color: #ffffff; font-size: 11px; font-weight: 700;">Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    ${matrixRowsHtml || '<tr><td colspan="6" style="padding: 14px; text-align: center; color: #94a3b8; font-style: italic;">No active categorized tasks found.</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-size: 11px; font-weight: 800; border-top: 2px solid #cbd5e1;">
                        <td style="padding: 8px 10px; color: #0f172a; border-right: 1px solid #e2e8f0; font-weight: 800; text-transform: uppercase; vertical-align: middle;">Total</td>
                        ${priorityCols.map((prio) => {
                            const count = matrixData.columnTotals[String(prio.id)] || 0;
                            return `
                                <td style="padding: 8px 8px; text-align: center; color: #0f172a; border-right: 1px solid #e2e8f0; vertical-align: middle;">
                                    <strong style="color: #0f172a; font-size: 12px; font-family: 'Segoe UI', Arial, sans-serif;">
                                        ${count > 0 ? count : '0'}
                                    </strong>
                                </td>
                            `;
                        }).join('')}
                        <td style="padding: 8px 10px; text-align: center; vertical-align: middle;">
                            <strong style="color: #047857; font-size: 12px; font-family: 'Segoe UI', Arial, sans-serif; white-space: nowrap;">
                                ${matrixData.grandCompleted}/${matrixData.grandTotal} (${matrixData.overallSuccessRate} %)
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        `;

        pages.push(page1Content);

        // Partition tasks into subsequent pages
        const allGroupElements = [];
        groupSections.forEach((g) => {
            if (g.tasksCount === 0) return;

            const groupDiv = document.createElement('div');
            groupDiv.style.marginBottom = '14px';

            const rateNum = parseFloat(g.successRate || 0);
            const rateColor = rateNum >= 80 ? '#047857' : (rateNum >= 50 ? '#d97706' : '#b91c1c');

            groupDiv.innerHTML = `
                <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-bottom: none; border-radius: 6px 6px 0 0; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12.5px; font-weight: 800; color: #0f172a; display: inline-flex; align-items: center; gap: 4px; font-family: 'Pyidaungsu', 'Segoe UI', sans-serif;">
                            <span>⏱️</span>
                            <span>${cleanText(g.categoryName || g.title)}</span>
                        </span>
                        <span style="color: #4338ca; font-weight: 700; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif;">
                            • &nbsp;${g.duration ? g.duration + ' Hours' : 'Standard'}
                        </span>
                        <span style="color: ${g.color || '#e11d48'}; font-weight: 700; font-size: 11px; font-family: 'Pyidaungsu', 'Segoe UI', Arial, sans-serif;">
                            • &nbsp;🔥 ${cleanText(g.priorityLevel || 'Normal')}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif;">
                        <span style="color: #64748b;">Total: <strong style="color: #0f172a;">${g.total}</strong></span>
                        <span style="color: #cbd5e1;">|</span>
                        <span style="color: #64748b;">Success: <strong style="color: #047857;">${g.completed}</strong></span>
                        <span style="color: #cbd5e1;">|</span>
                        <span style="color: #64748b;">Overdue: <strong style="color: #dc2626;">${g.overdue}</strong></span>
                        <span style="color: #cbd5e1;">|</span>
                        <span style="color: #64748b;">Success Rate: <strong style="color: ${rateColor}; font-weight: 800;">${g.successRate}%</strong></span>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 0 0 6px 6px; overflow: hidden;">
                    <thead>
                        <tr style="background-color: #1e293b; color: #ffffff; font-size: 10px;">
                            <th style="padding: 6px 6px; width: 35px; text-align: center; border-right: 1px solid #334155;">#</th>
                            <th style="padding: 6px 10px; text-align: left; border-right: 1px solid #334155;">Task Title & Details</th>
                            <th style="padding: 6px 10px; width: 180px; text-align: left; border-right: 1px solid #334155;">Assignee & Department</th>
                            <th style="padding: 6px 8px; width: 140px; text-align: center; border-right: 1px solid #334155;">Due Date</th>
                            <th style="padding: 6px 8px; width: 110px; text-align: center; border-right: 1px solid #334155;">Status</th>
                            <th style="padding: 6px 8px; width: 110px; text-align: center;">SLA Compliance</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${g.rowsHtml}
                    </tbody>
                </table>
            `;

            allGroupElements.push(groupDiv);
        });

        // Pack group elements into pages
        let currentDetailContent = document.createElement('div');
        let currentRowsInPage = 0;
        const MAX_ROWS_PER_PAGE = 7;

        allGroupElements.forEach((groupElem) => {
            const rowCount = groupElem.querySelectorAll('tbody tr').length;

            if (currentRowsInPage > 0 && currentRowsInPage + rowCount > MAX_ROWS_PER_PAGE) {
                pages.push(currentDetailContent);
                currentDetailContent = document.createElement('div');
                currentRowsInPage = 0;
            }

            currentDetailContent.appendChild(groupElem);
            currentRowsInPage += rowCount + 2; // header overhead
        });

        if (currentDetailContent.children.length > 0) {
            pages.push(currentDetailContent);
        }

        // Render PDF pages with jsPDF
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const totalPages = pages.length;

        for (let pageIdx = 0; pageIdx < totalPages; pageIdx++) {
            const isLastPage = pageIdx === totalPages - 1;
            const pageContainer = document.createElement('div');
            pageContainer.style.width = '1122px';
            pageContainer.style.minHeight = '793px';
            pageContainer.style.maxHeight = '793px';
            pageContainer.style.height = '793px';
            pageContainer.style.padding = '18px 24px';
            pageContainer.style.boxSizing = 'border-box';
            pageContainer.style.backgroundColor = '#ffffff';
            pageContainer.style.display = 'flex';
            pageContainer.style.flexDirection = 'column';
            pageContainer.style.justifyContent = 'space-between';

            // Top section
            const topDiv = document.createElement('div');

            if (pageIdx > 0) {
                topDiv.innerHTML = `
                    <div style="background-color: #1e1b4b; color: #ffffff; padding: 7px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 10.5px;">
                        <span style="font-weight: 800;">${appName} — Todo Task Details Grouped by Due Time Types</span>
                        <span style="opacity: 0.85;">Month: <strong>${selectedMonthLabel}</strong> &nbsp;|&nbsp; Dept: <strong>${selectedDepartmentName}</strong></span>
                    </div>
                `;
            }

            topDiv.appendChild(pages[pageIdx]);
            pageContainer.appendChild(topDiv);

            // Bottom Section (Signature block on last page + Footer on all pages)
            const bottomDiv = document.createElement('div');

            let signatureHtml = '';
            if (isLastPage) {
                signatureHtml = `
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #cbd5e1; display: flex; justify-content: space-between; font-size: 10px; color: #334155;">
                        <!-- Left: Exporter -->
                        <div style="width: 360px;">
                            <div style="font-weight: 800; color: #0f172a; margin-bottom: 5px;">Report Prepared & Exported By:</div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                                <span style="width: 45px; font-weight: 600;">Name:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8; font-weight: 700; padding-left: 4px; color: #0f172a;">${cleanText(fullExporterText)}</span>
                            </div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                                <span style="width: 60px; font-weight: 600;">Signature:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: baseline;">
                                <span style="width: 45px; font-weight: 600;">Date:</span>
                                <span style="padding-left: 4px; font-weight: 700;">${todayDate}</span>
                            </div>
                        </div>

                        <!-- Right: Management Approval -->
                        <div style="width: 360px;">
                            <div style="font-weight: 800; color: #0f172a; margin-bottom: 5px;">Management / Head of Dept Acknowledged:</div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                                <span style="width: 45px; font-weight: 600;">Name:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                                <span style="width: 60px; font-weight: 600;">Signature:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                            <div style="display: flex; align-items: baseline;">
                                <span style="width: 45px; font-weight: 600;">Date:</span>
                                <span style="flex: 1; border-bottom: 1px solid #94a3b8;">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            const footerHtml = `
                <div style="margin-top: 6px; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; font-style: italic;">
                    <div>${appName} — Todo Task Operations & Due Time Group Performance Report — ${selectedMonthLabel}</div>
                    <div>Page ${pageIdx + 1} of ${totalPages}</div>
                </div>
            `;

            bottomDiv.innerHTML = signatureHtml + footerHtml;
            pageContainer.appendChild(bottomDiv);

            renderContainer.innerHTML = '';
            renderContainer.appendChild(pageContainer);

            const canvas = await html2canvas(pageContainer, {
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

        const safeMonth = selectedMonth.replace(/[^a-zA-Z0-9_-]/g, '_');
        const filename = `Todo_Report_${safeMonth}_${new Date().toISOString().slice(0, 10)}.pdf`;
        doc.save(filename);
    } finally {
        if (renderContainer && renderContainer.parentNode) {
            renderContainer.parentNode.removeChild(renderContainer);
        }
    }
};