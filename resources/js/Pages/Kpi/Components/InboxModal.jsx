import React, { useState, useEffect, useMemo } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';

// Format date into requested format: e.g. "SUN 2 July 26"
const formatDateCustom = (dateStr) => {
    if (!dateStr) return '-';
    const parts = String(dateStr).split('T')[0].split('-');
    if (parts.length !== 3) return dateStr;

    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);

    const d = new Date(year, month, day);
    if (isNaN(d.getTime())) return dateStr;

    const dayName = d.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase(); // "SUN"
    const monthName = d.toLocaleDateString('en-US', { month: 'long' }); // "July"
    const yearShort = String(year).slice(-2); // "26"

    return `${dayName} ${day} ${monthName} ${yearShort}`;
};

export default function InboxModal({
    isOpen,
    onClose,
    pendingExclusions = [],
    approvedExclusions = [],
    approvedHolidays = [],
    users = [],
}) {
    const [activeTab, setActiveTab] = useState('pending'); // 'pending' | 'approved'
    const [selectedEmployeeId, setSelectedEmployeeId] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [remarks, setRemarks] = useState({});
    const [processing, setProcessing] = useState(null);

    useEffect(() => {
        if (!isOpen) return;
        const handler = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [isOpen, onClose]);

    useEffect(() => {
        if (!isOpen) {
            setRemarks({});
            setSearchTerm('');
            setSelectedEmployeeId('');
            setActiveTab('pending');
        }
    }, [isOpen]);

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            try {
                return id ? window.route(name, id) : window.route(name);
            } catch (e) {
                // fallback if route not found in Ziggy
            }
        }
        if (name === 'kpi.audit.holiday.destroy') return `/kpi/audit/holiday/${id}`;
        if (name === 'kpi.audit.exclusion-request.destroy') return `/kpi/audit/exclusion-request/${id}`;
        if (name === 'kpi.audit.exclusion-request.approve') return `/kpi/audit/exclusion-request/${id}/approve`;
        if (name === 'kpi.audit.exclusion-request.reject') return `/kpi/audit/exclusion-request/${id}/reject`;
        return '/kpi/audit';
    };

    const handleAction = (id, action) => {
        setProcessing(id);
        const routeName = action === 'approve' ? 'kpi.audit.exclusion-request.approve' : 'kpi.audit.exclusion-request.reject';
        const url = resolveUrl(routeName, id);

        router.post(url, {
            reviewer_remark: remarks[id] || '',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setRemarks(prev => { const n = { ...prev }; delete n[id]; return n; });
                setProcessing(null);
            },
            onError: () => setProcessing(null),
        });
    };

    const handleDeleteApproved = (item) => {
        const itemTypeName = item.item_type === 'holiday' ? 'holiday' : 'exclusion request';
        const formattedDate = formatDateCustom(item.requested_date);

        if (!confirm(`Are you sure you want to delete approved ${itemTypeName} for ${item.user_name} on ${formattedDate}?`)) {
            return;
        }
        setProcessing(`del-${item.item_type}-${item.id}`);

        const routeName = item.item_type === 'holiday'
            ? 'kpi.audit.holiday.destroy'
            : 'kpi.audit.exclusion-request.destroy';

        const url = resolveUrl(routeName, item.id);

        router.delete(url, {
            preserveScroll: true,
            onSuccess: () => setProcessing(null),
            onError: () => setProcessing(null),
        });
    };

    // Combine approved exclusions & holidays
    const allApprovedItems = useMemo(() => {
        const list = [...approvedExclusions, ...approvedHolidays];
        return list.sort((a, b) => new Date(b.requested_date) - new Date(a.requested_date));
    }, [approvedExclusions, approvedHolidays]);

    // Filter items based on active tab, employee selection, and search term
    const displayedItems = useMemo(() => {
        let list = activeTab === 'pending' ? pendingExclusions : allApprovedItems;

        if (selectedEmployeeId) {
            list = list.filter(item => String(item.user_id) === String(selectedEmployeeId));
        }

        if (searchTerm.trim()) {
            const term = searchTerm.toLowerCase();
            list = list.filter(item => {
                const name = (item.user_name || '').toLowerCase();
                const dept = (item.user_dept || '').toLowerCase();
                const date = (item.requested_date || '').toLowerCase();
                const formattedDate = formatDateCustom(item.requested_date).toLowerCase();
                const task = (item.task_title || '').toLowerCase();
                const reason = (item.reason || '').toLowerCase();
                return name.includes(term) || dept.includes(term) || date.includes(term) || formattedDate.includes(term) || task.includes(term) || reason.includes(term);
            });
        }

        return list;
    }, [activeTab, pendingExclusions, allApprovedItems, selectedEmployeeId, searchTerm]);

    if (!isOpen || typeof document === 'undefined') return null;

    return createPortal(
        <div
            style={{ position: 'fixed', inset: 0, zIndex: 9000, backgroundColor: 'rgba(2,6,23,0.85)', scrollbarWidth: 'none', msOverflowStyle: 'none' }}
            className="fixed inset-0 z-[9000] w-screen h-screen bg-slate-950/85 backdrop-blur-md flex flex-col p-0 m-0 overflow-hidden no-scrollbar"
        >
            <div className="w-full h-full bg-white dark:bg-slate-900 flex flex-col overflow-hidden rounded-none shadow-none no-scrollbar">
                {/* 1. Full Screen Header Bar */}
                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between flex-shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center">
                            <svg className="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="text-lg font-extrabold text-slate-900 dark:text-white">KPI Audit Inbox &amp; Holiday Management</h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">Review pending requests or filter and delete approved holidays and exclusions.</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>Close ✕</span>
                    </button>
                </div>

                {/* 2. Controls & Search/Filter Header */}
                <div className="px-6 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col sm:flex-row items-center justify-between gap-4 flex-shrink-0">
                    {/* Tabs */}
                    <div className="inline-flex rounded-2xl bg-slate-200/70 p-1 dark:bg-slate-800">
                        <button
                            type="button"
                            onClick={() => setActiveTab('pending')}
                            className={`px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 ${
                                activeTab === 'pending'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            }`}
                        >
                            <span>Pending Requests</span>
                            <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-white">
                                {pendingExclusions.length}
                            </span>
                        </button>

                        <button
                            type="button"
                            onClick={() => setActiveTab('approved')}
                            className={`px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 ${
                                activeTab === 'approved'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            }`}
                        >
                            <span>Approved Holidays &amp; Exclusions</span>
                            <span className="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-600 text-white">
                                {allApprovedItems.length}
                            </span>
                        </button>
                    </div>

                    {/* Employee Filter & Search Input */}
                    <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                        {/* Filter by Employee */}
                        <div className="w-full sm:w-64">
                            <select
                                value={selectedEmployeeId}
                                onChange={(e) => setSelectedEmployeeId(e.target.value)}
                                className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-800 dark:text-white shadow-sm focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">Filter by Employee (All)</option>
                                {users.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        👤 {u.name} {u.department ? `(${u.department.name})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Search Input */}
                        <div className="relative w-full sm:w-64">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="🔍 Search date, reason, name..."
                                className="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 pl-8 pr-7 py-2 text-xs text-slate-800 dark:text-white shadow-sm focus:ring-2 focus:ring-indigo-500"
                            />
                            {searchTerm && (
                                <button
                                    type="button"
                                    onClick={() => setSearchTerm('')}
                                    className="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600"
                                >
                                    ✕
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* 3. Main Full Screen Content Grid */}
                <div
                    style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                    className="overflow-y-auto flex-1 p-6 space-y-4 no-scrollbar max-w-7xl mx-auto w-full"
                >
                    {displayedItems.length === 0 ? (
                        <div className="py-20 text-center space-y-3">
                            <div className="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-2xl">
                                {activeTab === 'pending' ? '🎉' : '📂'}
                            </div>
                            <p className="text-base font-bold text-slate-700 dark:text-slate-300">
                                {activeTab === 'pending' ? 'No pending exclusion requests!' : 'No approved holidays or exclusions found.'}
                            </p>
                            <p className="text-xs text-slate-400 dark:text-slate-500">
                                {selectedEmployeeId ? 'Try clearing the employee filter.' : 'All requests up to date.'}
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {displayedItems.map((item) => {
                                const isDeleting = processing === `del-${item.item_type}-${item.id}`;
                                const isActioning = processing === item.id;
                                const formattedDate = formatDateCustom(item.requested_date);

                                return (
                                    <div
                                        key={`${item.item_type}-${item.id}`}
                                        className="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-4 shadow-sm hover:shadow-md transition flex flex-col justify-between"
                                    >
                                        <div className="space-y-3">
                                            {/* Employee info & Type Badge */}
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-sm flex-shrink-0">
                                                        {item.user_name ? item.user_name.charAt(0).toUpperCase() : 'U'}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-extrabold text-slate-900 dark:text-white">{item.user_name}</p>
                                                        <p className="text-xs text-slate-500 dark:text-slate-400">{item.user_dept}</p>
                                                    </div>
                                                </div>

                                                <div className="flex flex-col items-end gap-1 flex-shrink-0">
                                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${
                                                        item.item_type === 'holiday'
                                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                                            : item.request_type === 'day'
                                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800'
                                                            : 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800'
                                                    }`}>
                                                        {item.item_type === 'holiday' ? '🌴 Holiday' : item.request_type === 'day' ? '📅 Day Excl.' : '📋 Task Excl.'}
                                                    </span>

                                                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                                        activeTab === 'pending'
                                                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                    }`}>
                                                        {activeTab === 'pending' ? 'Pending' : '✓ Approved'}
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Details */}
                                            <div className="text-xs text-slate-600 dark:text-slate-300 space-y-1 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-800">
                                                <p><span className="font-bold text-slate-500">Date:</span> <span className="font-mono text-indigo-600 dark:text-indigo-400 font-bold">{formattedDate}</span></p>
                                                {item.task_title && <p><span className="font-bold text-slate-500">Title/Task:</span> {item.task_title}</p>}
                                                <p><span className="font-bold text-slate-500">Reason:</span> {item.reason}</p>
                                                {item.reviewer_remark && <p><span className="font-bold text-slate-500">Approver Remark:</span> {item.reviewer_remark}</p>}
                                            </div>
                                        </div>

                                        {/* Actions */}
                                        {activeTab === 'pending' ? (
                                            <div className="space-y-2 pt-2">
                                                <textarea
                                                    value={remarks[item.id] || ''}
                                                    onChange={e => setRemarks(prev => ({ ...prev, [item.id]: e.target.value }))}
                                                    rows={2}
                                                    placeholder="Optional reviewer remark…"
                                                    className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"
                                                />
                                                <div className="flex gap-2">
                                                    <button
                                                        type="button"
                                                        disabled={isActioning}
                                                        onClick={() => handleAction(item.id, 'approve')}
                                                        className="flex-1 h-9 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition disabled:opacity-60 cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
                                                    >
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" /></svg>
                                                        {isActioning ? 'Processing…' : 'Approve'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        disabled={isActioning}
                                                        onClick={() => handleAction(item.id, 'reject')}
                                                        className="flex-1 h-9 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition disabled:opacity-60 cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
                                                    >
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                        Reject
                                                    </button>
                                                </div>
                                            </div>
                                        ) : (
                                            /* DELETE APPROVED ITEM BUTTON */
                                            <div className="pt-3 border-t border-slate-100 dark:border-slate-800">
                                                <button
                                                    type="button"
                                                    disabled={isDeleting}
                                                    onClick={() => handleDeleteApproved(item)}
                                                    className="w-full h-9 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-xs font-bold transition disabled:opacity-60 cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
                                                >
                                                    <svg className="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    {isDeleting ? 'Deleting Approved...' : 'Delete Approved Request 🗑️'}
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </div>,
        document.body
    );
}
