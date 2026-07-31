import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { router, useForm } from '@inertiajs/react';

export default function ExclusionRequestModal({
    isOpen,
    onClose,
    taskAssignments = [],
    users = [],
    selectedUserId,
    canManageHolidays = false,
    canApproveExclusions = false,
}) {
    const [mode, setMode] = useState('exclusion'); // 'exclusion' | 'holiday'

    // Exclusion form
    const exclusionForm = useForm({
        request_type: 'day',
        requested_date: new Date().toISOString().slice(0, 10),
        task_assignment_id: '',
        reason: '',
        target_user_id: selectedUserId || '',
    });

    // Holiday form
    const holidayForm = useForm({
        holiday_date: new Date().toISOString().slice(0, 10),
        name: '',
        user_id: selectedUserId || '',
        remark: '',
    });

    // Escape to close
    useEffect(() => {
        if (!isOpen) return;
        const handler = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [isOpen, onClose]);

    // Reset forms on open
    useEffect(() => {
        if (isOpen) {
            exclusionForm.reset();
            holidayForm.reset();
            exclusionForm.setData('target_user_id', selectedUserId || '');
            holidayForm.setData('user_id', selectedUserId || '');
            setMode('exclusion');
        }
    }, [isOpen]);

    const submitExclusion = (e) => {
        e.preventDefault();
        exclusionForm.post('/kpi/audit/exclusion-request', {
            preserveScroll: true,
            onSuccess: () => { exclusionForm.reset(); onClose(); },
        });
    };

    const submitHoliday = (e) => {
        e.preventDefault();
        holidayForm.post('/kpi/audit/holiday', {
            preserveScroll: true,
            onSuccess: () => { holidayForm.reset(); onClose(); },
        });
    };

    if (!isOpen || typeof document === 'undefined') return null;

    const inputCls = 'w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition';
    const labelCls = 'block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1';
    const errorCls = 'text-xs text-rose-500 mt-1';

    return createPortal(
        <div
            style={{ position: 'fixed', inset: 0, zIndex: 9000, backgroundColor: 'rgba(2,6,23,0.6)' }}
            className="flex items-center justify-center p-4 backdrop-blur-sm"
            onClick={onClose}
        >
            <div
                style={{ position: 'relative', zIndex: 9001 }}
                className="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center">
                            <svg className="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 className="text-sm font-bold text-slate-800 dark:text-slate-100">New Request</h2>
                    </div>
                    <button onClick={onClose} className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {/* Mode tabs */}
                <div className="flex border-b border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        onClick={() => setMode('exclusion')}
                        className={`flex-1 py-2.5 text-xs font-semibold transition cursor-pointer ${mode === 'exclusion' ? 'text-indigo-600 border-b-2 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'}`}
                    >
                        Day / Task Exclusion
                    </button>
                    {canManageHolidays && (
                        <button
                            type="button"
                            onClick={() => setMode('holiday')}
                            className={`flex-1 py-2.5 text-xs font-semibold transition cursor-pointer ${mode === 'holiday' ? 'text-indigo-600 border-b-2 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'}`}
                        >
                            Add Holiday
                        </button>
                    )}
                </div>

                <div className="px-6 py-5">
                    {/* ── EXCLUSION FORM ── */}
                    {mode === 'exclusion' && (
                        <form onSubmit={submitExclusion} className="space-y-4">
                            {/* Target user (managers only) */}
                            {canApproveExclusions && users.length > 0 && (
                                <div>
                                    <label className={labelCls}>Employee</label>
                                    <select
                                        value={exclusionForm.data.target_user_id}
                                        onChange={e => exclusionForm.setData('target_user_id', e.target.value)}
                                        className={inputCls}
                                    >
                                        {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                                    </select>
                                </div>
                            )}

                            {/* Type */}
                            <div>
                                <label className={labelCls}>Request Type</label>
                                <div className="flex gap-2">
                                    {[{ value: 'day', label: 'Day Exclusion' }, { value: 'task', label: 'Task Exclusion' }].map(opt => (
                                        <button
                                            key={opt.value}
                                            type="button"
                                            onClick={() => exclusionForm.setData('request_type', opt.value)}
                                            className={`flex-1 h-9 rounded-xl text-xs font-semibold border transition cursor-pointer ${exclusionForm.data.request_type === opt.value ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-600 hover:border-indigo-400'}`}
                                        >
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Date */}
                            <div>
                                <label className={labelCls}>Requested Date</label>
                                <input
                                    type="date"
                                    value={exclusionForm.data.requested_date}
                                    onChange={e => exclusionForm.setData('requested_date', e.target.value)}
                                    className={inputCls}
                                />
                                {exclusionForm.errors.requested_date && <p className={errorCls}>{exclusionForm.errors.requested_date}</p>}
                            </div>

                            {/* Task (only for task type) */}
                            {exclusionForm.data.request_type === 'task' && (
                                <div>
                                    <label className={labelCls}>Task</label>
                                    <select
                                        value={exclusionForm.data.task_assignment_id}
                                        onChange={e => exclusionForm.setData('task_assignment_id', e.target.value)}
                                        className={inputCls}
                                    >
                                        <option value="">Select task…</option>
                                        {taskAssignments.map(a => (
                                            <option key={a.id} value={a.id}>
                                                {a.title}{a.group ? ` — ${a.group}` : ''} ({a.frequency})
                                            </option>
                                        ))}
                                    </select>
                                    {exclusionForm.errors.task_assignment_id && <p className={errorCls}>{exclusionForm.errors.task_assignment_id}</p>}
                                </div>
                            )}

                            {/* Reason */}
                            <div>
                                <label className={labelCls}>Reason</label>
                                <textarea
                                    rows={3}
                                    value={exclusionForm.data.reason}
                                    onChange={e => exclusionForm.setData('reason', e.target.value)}
                                    placeholder="Explain why you need this exclusion…"
                                    className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"
                                />
                                {exclusionForm.errors.reason && <p className={errorCls}>{exclusionForm.errors.reason}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={exclusionForm.processing}
                                className="w-full h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition disabled:opacity-60 cursor-pointer"
                            >
                                {exclusionForm.processing ? 'Submitting…' : 'Submit Request'}
                            </button>
                        </form>
                    )}

                    {/* ── HOLIDAY FORM ── */}
                    {mode === 'holiday' && canManageHolidays && (
                        <form onSubmit={submitHoliday} className="space-y-4">
                            {/* Employee */}
                            <div>
                                <label className={labelCls}>Employee</label>
                                <select
                                    value={holidayForm.data.user_id}
                                    onChange={e => holidayForm.setData('user_id', e.target.value)}
                                    className={inputCls}
                                >
                                    <option value="">Select employee…</option>
                                    {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                                </select>
                                {holidayForm.errors.user_id && <p className={errorCls}>{holidayForm.errors.user_id}</p>}
                            </div>

                            {/* Date */}
                            <div>
                                <label className={labelCls}>Holiday Date</label>
                                <input
                                    type="date"
                                    value={holidayForm.data.holiday_date}
                                    onChange={e => holidayForm.setData('holiday_date', e.target.value)}
                                    className={inputCls}
                                />
                                {holidayForm.errors.holiday_date && <p className={errorCls}>{holidayForm.errors.holiday_date}</p>}
                            </div>

                            {/* Name */}
                            <div>
                                <label className={labelCls}>Holiday Name</label>
                                <input
                                    type="text"
                                    value={holidayForm.data.name}
                                    onChange={e => holidayForm.setData('name', e.target.value)}
                                    placeholder="e.g. Personal Leave"
                                    className={inputCls}
                                />
                                {holidayForm.errors.name && <p className={errorCls}>{holidayForm.errors.name}</p>}
                            </div>

                            {/* Remark */}
                            <div>
                                <label className={labelCls}>Remark <span className="font-normal text-slate-400">(optional)</span></label>
                                <textarea
                                    rows={2}
                                    value={holidayForm.data.remark}
                                    onChange={e => holidayForm.setData('remark', e.target.value)}
                                    placeholder="Optional note…"
                                    className="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition resize-none"
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={holidayForm.processing}
                                className="w-full h-10 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition disabled:opacity-60 cursor-pointer"
                            >
                                {holidayForm.processing ? 'Adding…' : 'Add Holiday'}
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </div>,
        document.body
    );
}
