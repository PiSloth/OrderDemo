import React, { useRef, useState, useEffect } from 'react';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';

/**
 * Ensures the Pyidaungsu webfont is loaded before rendering to canvas.
 */
const ensureFontLoaded = async () => {
    try {
        if (document.fonts && document.fonts.load) {
            await Promise.all([
                document.fonts.load('11px Pyidaungsu'),
                document.fonts.load('bold 11px Pyidaungsu'),
                document.fonts.load('12px Pyidaungsu'),
                document.fonts.load('bold 12px Pyidaungsu'),
                document.fonts.load('13.5px Pyidaungsu'),
                document.fonts.load('14.5px Pyidaungsu'),
                document.fonts.load('bold 14.5px Pyidaungsu'),
            ]);
            await document.fonts.ready;
        }
    } catch (e) {
        console.warn('Font loading check failed:', e);
    }
};

/**
 * Reform department name into reference code:
 * - The department must be from createdby department.
 * - If the department name is 2 words like "Internal Audit", reform to "IA".
 * - If ending with "Department" / "Dept", treat the base words (e.g. "Internal Audit Department" -> "IA", "Sales Department" -> "Sales").
 * - Special acronyms: "Information Technology" / "IT" -> "IT", "Human Resources" / "HR" -> "HR".
 * - Single word -> word itself (e.g. "Finance", "Audit", "Management").
 */
export const getDepartmentCode = (rawDept) => {
    if (!rawDept || typeof rawDept !== 'string') return 'GEN';
    const trimmed = rawDept.trim();
    if (!trimmed) return 'GEN';

    // Standard IT / HR check
    if (/^\s*(it|information\s+technology)(\s+department|\s+dept)?\s*$/i.test(trimmed)) return 'IT';
    if (/^\s*(hr|human\s+resources)(\s+department|\s+dept)?\s*$/i.test(trimmed)) return 'HR';

    // Split words by whitespace, hyphen, underscore, slash, and ignore '&'
    let words = trimmed.split(/[\s\-_\/]+/).filter((w) => w.length > 0 && w !== '&');

    // If starts with "IT" or "HR"
    if (words[0]?.toLowerCase() === 'it') return 'IT';
    if (words[0]?.toLowerCase() === 'hr') return 'HR';

    // Strip trailing 'Department' / 'Dept' if present
    if (words.length > 1) {
        const lastWord = words[words.length - 1].toLowerCase();
        if (lastWord === 'department' || lastWord === 'dept') {
            words = words.slice(0, -1);
        }
    }

    // If 2 words like "Internal Audit" -> "IA"
    if (words.length === 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }

    // If 1 word -> keep the word
    if (words.length === 1) {
        return words[0];
    }

    // If more than 2 words -> take first letter of each word (up to 4 chars)
    if (words.length > 2) {
        return words.map((w) => w[0]).join('').toUpperCase().slice(0, 4);
    }

    return trimmed;
};

export const capitalizeWords = (str) => {
    if (!str || typeof str !== 'string') return str || '-';
    return str.replace(/\b([a-z])/g, (c) => c.toUpperCase());
};

/**
 * Generate Reference No: Re-[Request department Name]-[131026]-[today total of created issue + 1 for that requested department]
 * Format: Re-[Request department Name]-[DDMMYY]-[IssueNo]
 */
export const generateRepairRequestRefNo = (task, currentUser, departments = [], todoLists = []) => {
    // 1. Resolve Requested Department
    const requestedDeptId =
        task?.requested_by_department_id ||
        task?.requestedByDepartmentId ||
        task?.formData?.requestedByDepartmentId ||
        task?.requested_by_department?.id ||
        task?.requestedByDepartment?.id ||
        task?.department?.id ||
        null;

    const matchedDept = Array.isArray(departments)
        ? departments.find((d) => String(d.id) === String(requestedDeptId))
        : null;

    const rawDept =
        task?.requested_by_department?.name ||
        task?.requestedByDepartment?.name ||
        task?.department?.name ||
        matchedDept?.name ||
        (typeof task?.requested_by_department === 'string' ? task.requested_by_department : null) ||
        (typeof task?.department === 'string' ? task.department : null) ||
        task?.created_by_user?.department?.name ||
        task?.createdByUser?.department?.name ||
        currentUser?.department?.name ||
        'GEN';

    const deptCode = getDepartmentCode(rawDept);

    // 2. Format Date as DDMMYY (e.g. 131026)
    const taskDate = task?.created_at ? new Date(task.created_at) : new Date();
    const day = String(taskDate.getDate()).padStart(2, '0');
    const month = String(taskDate.getMonth() + 1).padStart(2, '0');
    const year2Digit = String(taskDate.getFullYear()).slice(-2);
    const dateFormatted = `${day}${month}${year2Digit}`;

    // 3. Issue Count for that requested department created today (+ 1)
    let issueNo = task?.daily_dept_issue_no || task?.dailyDeptIssueNo || task?.issue_number;

    if (!issueNo) {
        if (Array.isArray(todoLists) && todoLists.length > 0) {
            const taskDateStr = taskDate.toISOString().slice(0, 10);
            const sameDayDeptTasks = todoLists.filter((t) => {
                const tDeptId =
                    t.requested_by_department_id ||
                    t.department?.id ||
                    t.requested_by_department?.id ||
                    t.department_id;
                if (requestedDeptId && tDeptId && String(tDeptId) !== String(requestedDeptId)) {
                    return false;
                }
                const tDateStr = t.created_at ? new Date(t.created_at).toISOString().slice(0, 10) : '';
                return tDateStr === taskDateStr;
            });

            if (task?.id) {
                const priorCount = sameDayDeptTasks.filter((t) => Number(t.id) <= Number(task.id)).length;
                issueNo = priorCount > 0 ? priorCount : 1;
            } else {
                issueNo = sameDayDeptTasks.length + 1;
            }
        } else {
            issueNo = 1;
        }
    }

    return `Re-${deptCode}-${dateFormatted}-${issueNo}`;
};

export default function RepairRequestModal({
    isOpen = false,
    onClose,
    task = null,
    currentUser = null,
    branches = [],
    departments = [],
    users = [],
    todoLists = [],
}) {
    const formRef = useRef(null);
    const [isCopying, setIsCopying] = useState(false);
    const [isDownloadingPdf, setIsDownloadingPdf] = useState(false);
    const [isDownloadingPng, setIsDownloadingPng] = useState(false);
    const [notification, setNotification] = useState(null);

    // Preload Pyidaungsu webfonts as soon as modal is opened
    useEffect(() => {
        if (isOpen) {
            ensureFontLoaded();
        }
    }, [isOpen]);

    if (!isOpen || !task) return null;

    const refNo = generateRepairRequestRefNo(task, currentUser, departments, todoLists);

    const taskDate = task.created_at ? new Date(task.created_at) : new Date();
    const formattedDate = taskDate.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    // Branch name for Form row 'ဌာန' (The ဌာန row must be branch name)
    const branchName =
        task.requested_by_branch?.name ||
        task.requestedByBranch?.name ||
        (typeof task.requested_by_branch === 'string' ? task.requested_by_branch : null) ||
        branches?.find((b) => String(b.id) === String(task.requested_by_branch_id || task.requestedByBranchId || task.formData?.requestedByBranchId))?.name ||
        task.created_by_user?.branch?.name ||
        task.createdByUser?.branch?.name ||
        (typeof task.created_by_user?.branch === 'string' ? task.created_by_user.branch : null) ||
        currentUser?.branch?.name ||
        (typeof currentUser?.branch === 'string' ? currentUser.branch : null) ||
        task.branch?.name ||
        '-';

    // Due time type (not show due date or duration hours)
    const dueTimeType =
        task.due_time?.category?.name ||
        task.due_time?.categoryName ||
        task.due_time?.name?.split('(')[0]?.trim() ||
        task.category?.name ||
        'General Issue / Equipment';

    const taskDescription = task.task || task.description || '-';

    const priorityLevel =
        task.due_time?.priority?.level ||
        task.due_time?.priority?.name ||
        task.due_time?.priorityLevel ||
        task.priority?.level ||
        task.priority?.name ||
        'Normal';

    // Requester info
    const requesterUser = task.created_by_user || task.createdByUser || currentUser;
    const requesterName = requesterUser?.name || 'Authorized Requester';
    const matchedRequesterUser = users?.find(
        (u) => String(u.id) === String(requesterUser?.id || task.created_by_user_id || task.createdByUserId)
    );
    const requesterPosition =
        requesterUser?.office_position?.name ||
        requesterUser?.officePosition?.name ||
        matchedRequesterUser?.office_position?.name ||
        matchedRequesterUser?.officePosition?.name ||
        '';
    const requesterDept =
        requesterUser?.department?.name ||
        (typeof requesterUser?.department === 'string' ? requesterUser.department : null) ||
        matchedRequesterUser?.department?.name ||
        (typeof matchedRequesterUser?.department === 'string' ? matchedRequesterUser.department : null) ||
        task?.department?.name ||
        task?.requested_by_department?.name ||
        '';
    const requesterRoleAndDept = [requesterPosition, requesterDept].filter(Boolean).join(' / ') || '-';

    // Assigned info: Only show office_position and department name
    const assignedUser = task.assigned_user;
    const assignedName = assignedUser?.name || 'Unassigned';
    const matchedAssignedUser = users?.find(
        (u) => String(u.id) === String(assignedUser?.id || task.assigned_user_id || task.assignedUserId || task.formData?.assignedUserId)
    );

    const assignedPosition =
        assignedUser?.office_position?.name ||
        assignedUser?.officePosition?.name ||
        matchedAssignedUser?.office_position?.name ||
        matchedAssignedUser?.officePosition?.name ||
        '';

    const assignedDept =
        assignedUser?.department?.name ||
        (typeof assignedUser?.department === 'string' ? assignedUser.department : null) ||
        matchedAssignedUser?.department?.name ||
        (typeof matchedAssignedUser?.department === 'string' ? matchedAssignedUser.department : null) ||
        '';

    const assignedRoleAndDept = [assignedPosition, assignedDept].filter(Boolean).join(' / ') || '-';

    // Helper to format Due Date & Time for မှတ်ချက် (Remarks) row
    const formatDueDateTime = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return String(dateStr);
            const day = String(d.getDate()).padStart(2, '0');
            const month = d.toLocaleDateString('en-GB', { month: 'short' });
            const year = d.getFullYear();
            let hours = d.getHours();
            const minutes = String(d.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const timeStr = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
            return `${day} ${month} ${year}, ${timeStr}`;
        } catch (e) {
            return String(dateStr);
        }
    };

    const taskDueDateTimeStr = formatDueDateTime(task.due_date || task.dueDate || task.formData?.dueDate);

    const showToast = (msg, type = 'success') => {
        setNotification({ message: msg, type });
        setTimeout(() => setNotification(null), 3500);
    };

    /**
     * Unified canvas renderer that ensures:
     * 1. Pyidaungsu fonts are loaded and ready in main document.
     * 2. Custom document fonts and @font-face are injected into html2canvas's cloned iframe DOM.
     * 3. Font ligatures are disabled on clone to prevent double glyph painting.
     * 4. An integer scale (2) is used to avoid subpixel anti-aliasing ghost borders.
     */
    const renderFormToCanvas = async () => {
        if (!formRef.current) return null;

        await ensureFontLoaded();
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }
        // Small delay to ensure layout and webfont metrics are fully resolved
        await new Promise((resolve) => setTimeout(resolve, 80));

        return await html2canvas(formRef.current, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            onclone: (clonedDoc) => {
                const style = clonedDoc.createElement('style');
                style.innerHTML = `
                    @font-face {
                        font-family: 'Pyidaungsu';
                        src: url('/fonts/Pyidaungsu-Regular.ttf') format('truetype');
                        font-weight: normal;
                        font-style: normal;
                    }
                    @font-face {
                        font-family: 'Pyidaungsu';
                        src: url('/fonts/Pyidaungsu-Bold.ttf') format('truetype');
                        font-weight: bold;
                        font-style: normal;
                    }
                `;
                clonedDoc.head.appendChild(style);

                // Ensure all loaded document fonts exist in the isolated iframe
                if (document.fonts) {
                    document.fonts.forEach((font) => {
                        try {
                            clonedDoc.fonts.add(font);
                        } catch (e) {
                            // Ignore duplicate addition
                        }
                    });
                }
                const printable = clonedDoc.getElementById('repair-request-form-printable');
                if (printable) {
                    printable.style.fontFamily = "'Pyidaungsu', sans-serif";
                    printable.style.fontFeatureSettings = '"liga" 0';
                    printable.style.textRendering = 'geometricPrecision';
                }
            },
        });
    };

    // Copy as Image using ClipboardItem API (captures pure document without card borders)
    const handleCopyAsImage = async () => {
        if (!formRef.current) return;
        setIsCopying(true);

        try {
            const canvas = await renderFormToCanvas();
            if (!canvas) {
                showToast('Failed to render document to canvas', 'error');
                setIsCopying(false);
                return;
            }

            canvas.toBlob(async (blob) => {
                if (!blob) {
                    showToast('Failed to create image blob', 'error');
                    setIsCopying(false);
                    return;
                }

                try {
                    if (navigator.clipboard && window.ClipboardItem) {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob }),
                        ]);
                        showToast('✅ Copied A5 Form image to clipboard! You can paste (Ctrl+V) anywhere.');
                    } else {
                        const link = document.createElement('a');
                        link.download = `Repair_Request_${refNo.replace(/[^a-zA-Z0-9_-]/g, '_')}.png`;
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                        showToast('Downloaded PNG image (clipboard not supported on this browser)');
                    }
                } catch (clipErr) {
                    console.warn('Clipboard write failed, downloading as PNG fallback', clipErr);
                    const link = document.createElement('a');
                    link.download = `Repair_Request_${refNo.replace(/[^a-zA-Z0-9_-]/g, '_')}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    showToast('Downloaded PNG image (clipboard permission blocked)');
                } finally {
                    setIsCopying(false);
                }
            }, 'image/png');
        } catch (err) {
            console.error('Error generating image', err);
            showToast('Failed to generate image', 'error');
            setIsCopying(false);
        }
    };

    // Download A5 PDF: Exact full-page fit (0, 0, 148, 210) with no outer card border lines
    const handleDownloadPDF = async () => {
        if (!formRef.current) return;
        setIsDownloadingPdf(true);

        try {
            const canvas = await renderFormToCanvas();
            if (!canvas) {
                showToast('Failed to render document to canvas', 'error');
                setIsDownloadingPdf(false);
                return;
            }

            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a5',
            });

            // Fits 100% of the A5 page with zero clipping
            pdf.addImage(imgData, 'PNG', 0, 0, 148, 210, undefined, 'FAST');

            pdf.save(`Repair_Request_Form_${refNo.replace(/[^a-zA-Z0-9_-]/g, '_')}.pdf`);
            showToast('✅ A5 PDF downloaded successfully!');
        } catch (err) {
            console.error('Error downloading PDF', err);
            showToast('Failed to export PDF', 'error');
        } finally {
            setIsDownloadingPdf(false);
        }
    };

    // Download PNG directly
    const handleDownloadPNG = async () => {
        if (!formRef.current) return;
        setIsDownloadingPng(true);

        try {
            const canvas = await renderFormToCanvas();
            if (!canvas) {
                showToast('Failed to render document to canvas', 'error');
                setIsDownloadingPng(false);
                return;
            }

            const link = document.createElement('a');
            link.download = `Repair_Request_Form_${refNo.replace(/[^a-zA-Z0-9_-]/g, '_')}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
            showToast('✅ A5 Form PNG image downloaded!');
        } catch (err) {
            console.error('Error downloading PNG', err);
            showToast('Failed to download PNG', 'error');
        } finally {
            setIsDownloadingPng(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/70 backdrop-blur-sm overflow-y-auto">
            <div className="relative w-full max-w-2xl rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-auto overflow-hidden flex flex-col max-h-[96vh]">
                {/* Modal Top Action Bar */}
                <div className="flex flex-wrap items-center justify-between border-b border-slate-100 bg-slate-50/90 px-5 py-3 dark:border-slate-800 dark:bg-slate-800/80 gap-3">
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            <span>📄</span> A5 Format
                        </span>
                        <h3 className="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                            Repair Request Form
                        </h3>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        {/* Copy as Image Button */}
                        <button
                            type="button"
                            onClick={handleCopyAsImage}
                            disabled={isCopying}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-violet-700 disabled:opacity-50 transition"
                            title="Copy A5 layout to clipboard as image to paste in Viber, Telegram or Word"
                        >
                            {isCopying ? (
                                <>
                                    <svg className="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Copying...</span>
                                </>
                            ) : (
                                <>
                                    <span>📋</span>
                                    <span>Copy as Image</span>
                                </>
                            )}
                        </button>

                        {/* Download PDF Button */}
                        <button
                            type="button"
                            onClick={handleDownloadPDF}
                            disabled={isDownloadingPdf}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 transition"
                            title="Download formatted A5 printable PDF"
                        >
                            {isDownloadingPdf ? (
                                <>
                                    <svg className="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>PDF...</span>
                                </>
                            ) : (
                                <>
                                    <span>📥</span>
                                    <span>Download PDF</span>
                                </>
                            )}
                        </button>

                        {/* Download PNG Button */}
                        <button
                            type="button"
                            onClick={handleDownloadPNG}
                            disabled={isDownloadingPng}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition"
                            title="Download PNG image"
                        >
                            <span>🖼️</span>
                            <span>PNG</span>
                        </button>

                        {/* Close Button */}
                        <button
                            type="button"
                            onClick={onClose}
                            className="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 transition"
                            title="Close"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                {/* Toast Notification Alert */}
                {notification && (
                    <div
                        className={`mx-5 mt-3 flex items-center justify-between rounded-xl px-4 py-2 text-xs font-bold ${
                            notification.type === 'error'
                                ? 'bg-rose-100 text-rose-800 border border-rose-300'
                                : 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                        }`}
                    >
                        <span>{notification.message}</span>
                        <button type="button" onClick={() => setNotification(null)} className="ml-2 font-black">
                            ✕
                        </button>
                    </div>
                )}

                {/* Modal Body: A5 Document Preview Area */}
                <div className="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-200/80 dark:bg-slate-950 flex justify-center">
                    {/* Shadow Desk Wrapper (Screen only - not captured inside formRef) */}
                    <div className="shadow-2xl bg-white" style={{ borderRadius: '2px' }}>
                        {/* 
                            Pure A5 Document Container (560px x 792px - exact A5 148x210mm proportion).
                            No outer borders or box shadows so exported PDF & PNG are clean.
                        */}
                        <div
                            ref={formRef}
                            id="repair-request-form-printable"
                            style={{
                                width: '560px',
                                height: '792px',
                                backgroundColor: '#ffffff',
                                padding: '24px 28px',
                                border: 'none',
                                boxShadow: 'none',
                                fontFamily: `'Pyidaungsu', 'Segoe UI', Arial, sans-serif`,
                                color: '#000000',
                                boxSizing: 'border-box',
                                display: 'flex',
                                flexDirection: 'column',
                                justifyContent: 'flex-start',
                            }}
                        >
                            {/* Title Header: Regular weight for Myanmar text to prevent ligature spelling distortion */}
                            <div
                                style={{
                                    textAlign: 'center',
                                    marginBottom: '12px',
                                    lineHeight: '1.4',
                                }}
                            >
                                <span
                                    style={{
                                        fontSize: '14.5px',
                                        fontWeight: 700,
                                        fontFamily: `'Segoe UI', Arial, sans-serif`,
                                        letterSpacing: '0.2px',
                                    }}
                                >
                                    REPAIR REQUEST FORM
                                </span>{' '}
                                <span
                                    style={{
                                        fontSize: '13.5px',
                                        fontWeight: 'normal',
                                        fontFamily: `'Pyidaungsu', sans-serif`,
                                    }}
                                >
                                    (ပြုပြင်ထိန်းသိမ်းပေးရန် တောင်းခံလွှာ)
                                </span>
                            </div>

                            {/* Form Reference Number */}
                            <div
                                style={{
                                    textAlign: 'right',
                                    fontSize: '11px',
                                    marginBottom: '8px',
                                    lineHeight: '1.4',
                                }}
                            >
                                <span
                                    style={{
                                        fontWeight: 'normal',
                                        fontFamily: `'Pyidaungsu', sans-serif`,
                                        color: '#111827',
                                    }}
                                >
                                    တောင်းခံလွှာအမှတ်
                                </span>{' '}
                                <span
                                    style={{
                                        fontFamily: 'monospace',
                                        fontSize: '11.5px',
                                        fontWeight: 700,
                                        color: '#000000',
                                        marginLeft: '4px',
                                    }}
                                >
                                    {refNo}
                                </span>
                            </div>

                            {/* Form Table */}
                            <table
                                style={{
                                    width: '100%',
                                    borderCollapse: 'collapse',
                                    border: '1px solid #000000',
                                    fontSize: '11px',
                                    lineHeight: '1.4',
                                    color: '#000000',
                                }}
                            >
                                <tbody>
                                    {/* Row 1: ဌာန (Branch Name) */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                width: '32%',
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            ဌာန
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                            }}
                                        >
                                            {capitalizeWords(branchName)}
                                        </td>
                                    </tr>

                                    {/* Row 2: ရက်စွဲ */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            ရက်စွဲ
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                            }}
                                        >
                                            {formattedDate}
                                        </td>
                                    </tr>

                                    {/* Row 3: ပြုပြင်ရန်အချက်အလက် Section Header */}
                                    <tr>
                                        <td
                                            colSpan={2}
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                fontSize: '11.5px',
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            ပြုပြင်ရန်အချက်အလက်
                                        </td>
                                    </tr>

                                    {/* Row 4: ပြုပြင်ရမည့် နေရာ/ ပစ္စည်း */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            ပြုပြင်ရမည့် နေရာ/ ပစ္စည်း
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                            }}
                                        >
                                            {dueTimeType}
                                        </td>
                                    </tr>

                                    {/* Row 5: ဖြစ်စဉ် အကျဉ်းချုပ် */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                backgroundColor: '#ffffff',
                                                verticalAlign: 'top',
                                            }}
                                        >
                                            ဖြစ်စဉ် အကျဉ်းချုပ်
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                minHeight: '48px',
                                                lineHeight: '1.45',
                                                wordBreak: 'break-word',
                                            }}
                                        >
                                            {taskDescription}
                                        </td>
                                    </tr>

                                    {/* Row 6: အရေးပေါ်အဆင့် */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            အရေးပေါ်အဆင့်
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                            }}
                                        >
                                            {priorityLevel}
                                        </td>
                                    </tr>

                                    {/* Row 7: စီမံရေးရာဌာန မှတ်ချက် Section Header */}
                                    <tr>
                                        <td
                                            colSpan={2}
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                fontSize: '11.5px',
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            စီမံရေးရာဌာန မှတ်ချက်
                                        </td>
                                    </tr>

                                    {/* Row 8: ပြုပြင်ရန်နည်းလမ်း */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                backgroundColor: '#ffffff',
                                            }}
                                        >
                                            ပြုပြင်ရန်နည်းလမ်း
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                            }}
                                        >
                                            <span style={{ marginRight: '24px' }}>☐ ဌာနတွင်းပြုပြင်ခြင်း</span>
                                            <span>☐ လုပ်ငန်းအပ်နှံပြုပြင်ခြင်း</span>
                                        </td>
                                    </tr>

                                    {/* Row 9: မှတ်ချက် */}
                                    <tr>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                fontWeight: 'normal',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                backgroundColor: '#ffffff',
                                                verticalAlign: 'top',
                                            }}
                                        >
                                            မှတ်ချက်
                                        </td>
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '6px 10px',
                                                height: '45px',
                                                verticalAlign: 'top',
                                                fontFamily: `'Pyidaungsu', sans-serif`,
                                                fontSize: '11px',
                                                lineHeight: '1.4',
                                            }}
                                        >
                                            {taskDueDateTimeStr && (
                                                <div style={{ marginBottom: (task.remarks || task.note) ? '4px' : '0' }}>
                                                    <span style={{ fontWeight: 'normal', color: '#111827' }}>
                                                        သတ်မှတ်ပြီးစီးရမည့်အချိန် -{' '}
                                                    </span>
                                                    <span style={{ fontWeight: 600, color: '#000000' }}>
                                                        {taskDueDateTimeStr}
                                                    </span>
                                                    {task.due_time?.duration ? (
                                                        <span style={{ color: '#4b5563', marginLeft: '6px' }}>
                                                            ({task.due_time.duration} Hours)
                                                        </span>
                                                    ) : null}
                                                </div>
                                            )}
                                            {(task.remarks || task.note) && (
                                                <div style={{ color: '#111827' }}>
                                                    {task.remarks || task.note}
                                                </div>
                                            )}
                                        </td>
                                    </tr>

                                    {/* Row 10: Signatures */}
                                    <tr>
                                        {/* Requester Column */}
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '12px 10px',
                                                width: '50%',
                                                verticalAlign: 'top',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    fontWeight: 'normal',
                                                    fontFamily: `'Pyidaungsu', sans-serif`,
                                                    marginBottom: '12px',
                                                }}
                                            >
                                                တောင်းခံသူ{' '}
                                                <span style={{ fontWeight: 'normal', marginLeft: '3px' }}>
                                                    {requesterName}
                                                </span>
                                            </div>
                                            <div
                                                style={{
                                                    marginBottom: '12px',
                                                    color: '#111827',
                                                    fontWeight: 'normal',
                                                    fontFamily: `'Pyidaungsu', sans-serif`,
                                                }}
                                            >
                                                လက်မှတ် ........................................
                                            </div>
                                            <div
                                                style={{
                                                    fontSize: '10.5px',
                                                    fontWeight: 'normal',
                                                    fontFamily: `'Pyidaungsu', sans-serif`,
                                                }}
                                            >
                                                <span>ရာထူး/ဌာန</span>{' '}
                                                <span style={{ marginLeft: '3px' }}>
                                                    {requesterRoleAndDept}
                                                </span>
                                            </div>
                                        </td>

                                        {/* Assigned / Inspector Column */}
                                        <td
                                            style={{
                                                border: '1px solid #000000',
                                                padding: '12px 10px',
                                                width: '50%',
                                                verticalAlign: 'top',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    fontWeight: 'normal',
                                                    fontFamily: `'Pyidaungsu', sans-serif`,
                                                    marginBottom: '12px',
                                                }}
                                            >
                                                စစ်ဆေးသူ{' '}
                                                <span style={{ fontWeight: 'normal', marginLeft: '3px' }}>
                                                    {assignedName}
                                                </span>
                                            </div>
                                            <div
                                                style={{
                                                    marginBottom: '12px',
                                                    color: '#111827',
                                                    fontWeight: 'normal',
                                                    fontFamily: `'Pyidaungsu', sans-serif`,
                                                }}
                                            >
                                                လက်မှတ် ........................................
                                            </div>
                                            <div
                                                style={{
                                                    fontSize: '10.5px',
                                                    fontWeight: 'normal',
                                                    fontFamily: `'Pyidaungsu', sans-serif`,
                                                }}
                                            >
                                                <span>ရာထူး/ဌာန</span>{' '}
                                                <span style={{ marginLeft: '3px' }}>
                                                    {assignedRoleAndDept}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
