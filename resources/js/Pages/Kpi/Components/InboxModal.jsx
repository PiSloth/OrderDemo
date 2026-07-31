import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';

export default function InboxModal({ isOpen, onClose, pendingExclusions = [] }) {
    const [remarks, setRemarks] = useState({});
    const [processing, setProcessing] = useState(null); // id of row being processed

    useEffect(() => {
        if (!isOpen) return;
        const handler = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [isOpen, onClose]);

    // Reset remarks when modal opens/closes
    useEffect(() => {
        if (!isOpen) setRemarks({});
    }, [isOpen]);

    const handleAction = (id, action) => {
        setProcessing(id);
        router.post(`/kpi/audit/exclusion-request/${id}/${action}`, {
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

    if (!isOpen || typeof document === 'undefined') return null;

    return createPortal(
        <div
            style={{ position: 'fixed', inset: 0, zIndex: 9000, backgroundColor: 'rgba(2,6,23,0.6)' }}
            className="flex items-center justify-center p-4 backdrop-blur-sm"
            onClick={onClose}
        >
            <div
                style={{ position: 'relative', zIndex: 9001 }}
                className="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[85vh] overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center">
                            <svg className="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="text-sm font-bold text-slate-800 dark:text-slate-100">Exclusion Request Inbox</h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">{pendingExclusions.length} pending request{pendingExclusions.length !== 1 ? 's' : ''}</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {/* Body */}
                <div className="overflow-y-auto flex-1 p-4 space-y-3">
                    {pendingExclusions.length === 0 ? (
                        <div className="py-12 text-center">
                            <div className="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                                <svg className="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p className="text-sm font-medium text-slate-600 dark:text-slate-400">All clear!</p>
                            <p className="text-xs text-slate-400 dark:text-slate-500 mt-1">No pending exclusion requests.</p>
                        </div>
                    ) : (
                        pendingExclusions.map((req) => (
                            <div key={req.id} className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-4 space-y-3">
                                {/* Employee info & request type */}
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-center gap-2.5">
                                        <div className="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-sm flex-shrink-0">
                                            {req.user_name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-800 dark:text-slate-100">{req.user_name}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">{req.user_dept}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${req.request_type === 'day' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'}`}>
                                            {req.request_type === 'day' ? 'Day Excl.' : 'Task Excl.'}
                                        </span>
                                        <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            Pending
                                        </span>
                                    </div>
                                </div>

                                {/* Details */}
                                <div className="text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                                    <p><span className="font-medium text-slate-500">Date:</span> {req.requested_date}</p>
                                    {req.task_title && <p><span className="font-medium text-slate-500">Task:</span> {req.task_title}</p>}
                                    <p><span className="font-medium text-slate-500">Reason:</span> {req.reason}</p>
                                </div>

                                {/* Reviewer remark */}
                                <textarea
                                    value={remarks[req.id] || ''}
                                    onChange={e => setRemarks(prev => ({ ...prev, [req.id]: e.target.value }))}
                                    rows={2}
                                    placeholder="Optional reviewer remark…"
                                    className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"
                                />

                                {/* Actions */}
                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        disabled={processing === req.id}
                                        onClick={() => handleAction(req.id, 'approve')}
                                        className="flex-1 h-9 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition disabled:opacity-60 cursor-pointer flex items-center justify-center gap-1.5"
                                    >
                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" /></svg>
                                        {processing === req.id ? 'Processing…' : 'Approve'}
                                    </button>
                                    <button
                                        type="button"
                                        disabled={processing === req.id}
                                        onClick={() => handleAction(req.id, 'reject')}
                                        className="flex-1 h-9 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition disabled:opacity-60 cursor-pointer flex items-center justify-center gap-1.5"
                                    >
                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>,
        document.body
    );
}
