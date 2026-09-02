import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import UserSelectModal from '@/components/IT/UserSelectModal';
import TemplateSelectModal from './TemplateSelectModal';

export default function CreateTaskInstanceModal({
    isOpen = false,
    onClose,
    templates = [],
    users = [],
    departments = [],
    initialTemplateId = '',
    initialUserId = '',
    initialDueDate = '',
    onSuccess,
}) {
    const pageProps = usePage()?.props || {};

    const safeTemplates = useMemo(() => {
        if (Array.isArray(templates) && templates.length > 0) return templates;
        if (Array.isArray(pageProps.templates)) return pageProps.templates;
        if (pageProps.templates?.data && Array.isArray(pageProps.templates.data)) return pageProps.templates.data;
        return [];
    }, [templates, pageProps.templates]);

    const safeUsers = useMemo(() => {
        if (Array.isArray(users) && users.length > 0) return users;
        if (Array.isArray(pageProps.users)) return pageProps.users;
        if (pageProps.users?.data && Array.isArray(pageProps.users.data)) return pageProps.users.data;
        return [];
    }, [users, pageProps.users]);

    const safeDepartments = useMemo(() => {
        if (Array.isArray(departments) && departments.length > 0) return departments;
        if (Array.isArray(pageProps.departments)) return pageProps.departments;
        if (pageProps.departments?.data && Array.isArray(pageProps.departments.data)) return pageProps.departments.data;
        return [];
    }, [departments, pageProps.departments]);

    // Direct tracking of selected entities
    const [chosenTemplate, setChosenTemplate] = useState(null);
    const [chosenEmployee, setChosenEmployee] = useState(null);

    // Sub-modal states
    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [isUserModalOpen, setIsUserModalOpen] = useState(false);

    // Collapsible for advanced period settings
    const [showAdvanced, setShowAdvanced] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        task_template_id: '',
        user_id: '',
        due_at: '',
        task_date: '',
        period_type: 'daily',
        period_start: '',
        period_end: '',
        status: 'pending',
    });

    const resolveUrl = (name) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return window.route(name);
        }
        return '/kpi/assignments/instances';
    };

    // Calculate default due date/time (e.g. today at 18:00 or tomorrow at 18:00)
    const getDefaultDueDateTime = (cutoffTime = '18:00') => {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const timePart = cutoffTime ? cutoffTime.substring(0, 5) : '18:00';
        return `${year}-${month}-${day}T${timePart}`;
    };

    // Compute period dates based on task date and frequency
    const calculatePeriods = (taskDateStr, freq) => {
        if (!taskDateStr) return { start: '', end: '' };
        const d = new Date(taskDateStr);
        if (isNaN(d.getTime())) return { start: '', end: '' };

        const formatDate = (dateObj) => {
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const day = String(dateObj.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };

        if (freq === 'weekly') {
            const dayOfWeek = d.getDay();
            const diffToMonday = d.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
            const monday = new Date(d.setDate(diffToMonday));
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            return { start: formatDate(monday), end: formatDate(sunday) };
        }

        if (freq === 'monthly') {
            const start = new Date(d.getFullYear(), d.getMonth(), 1);
            const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
            return { start: formatDate(start), end: formatDate(end) };
        }

        return { start: taskDateStr, end: taskDateStr };
    };

    // Reset or populate on modal open
    const prevOpenRef = useRef(false);
    useEffect(() => {
        if (isOpen && !prevOpenRef.current) {
            clearErrors();
            setShowAdvanced(false);

            let initialTpl = null;
            if (initialTemplateId) {
                initialTpl = safeTemplates.find((t) => String(t.id) === String(initialTemplateId)) || null;
            }

            let initialUser = null;
            if (initialUserId) {
                initialUser = safeUsers.find((u) => String(u.id) === String(initialUserId)) || null;
            }

            setChosenTemplate(initialTpl);
            setChosenEmployee(initialUser);

            const initialDue = initialDueDate || getDefaultDueDateTime(initialTpl?.cutoff_time || '18:00');
            const taskDateVal = initialDue ? initialDue.substring(0, 10) : '';
            const freq = initialTpl?.frequency || 'daily';
            const periods = calculatePeriods(taskDateVal, freq);

            setData({
                task_template_id: initialTpl ? String(initialTpl.id) : '',
                user_id: initialUser ? String(initialUser.id) : '',
                due_at: initialDue,
                task_date: taskDateVal,
                period_type: freq,
                period_start: periods.start,
                period_end: periods.end,
                status: 'pending',
            });
        }
        prevOpenRef.current = isOpen;
    }, [isOpen, initialTemplateId, initialUserId, initialDueDate, safeTemplates, safeUsers]);

    // Handle template selection
    const handleSelectTemplate = (template) => {
        setChosenTemplate(template);
        const tplId = String(template.id);
        const freq = template.frequency || 'daily';

        // Update default due time using template cutoff time
        const currentTaskDate = data.task_date || new Date().toISOString().substring(0, 10);
        const cutoff = template.cutoff_time ? template.cutoff_time.substring(0, 5) : '18:00';
        const newDueAt = `${currentTaskDate}T${cutoff}`;
        const periods = calculatePeriods(currentTaskDate, freq);

        setData((prev) => ({
            ...prev,
            task_template_id: tplId,
            due_at: prev.due_at || newDueAt,
            period_type: freq,
            period_start: periods.start,
            period_end: periods.end,
        }));
    };

    // Handle employee selection
    const handleSelectEmployee = (user) => {
        setChosenEmployee(user);
        setData('user_id', String(user.id));
    };

    // When Due At changes, update task_date and periods accordingly
    const handleDueAtChange = (e) => {
        const val = e.target.value;
        const taskDateVal = val ? val.substring(0, 10) : '';
        const periods = calculatePeriods(taskDateVal, data.period_type);

        setData((prev) => ({
            ...prev,
            due_at: val,
            task_date: taskDateVal,
            period_start: periods.start,
            period_end: periods.end,
        }));
    };

    // When Task Date changes, update due_at date part and period dates
    const handleTaskDateChange = (e) => {
        const newDate = e.target.value;
        const currentTimePart = data.due_at && data.due_at.includes('T') ? data.due_at.split('T')[1] : '18:00';
        const newDueAt = newDate ? `${newDate}T${currentTimePart}` : '';
        const periods = calculatePeriods(newDate, data.period_type);

        setData((prev) => ({
            ...prev,
            task_date: newDate,
            due_at: newDueAt,
            period_start: periods.start,
            period_end: periods.end,
        }));
    };

    // When Period Type changes
    const handlePeriodTypeChange = (e) => {
        const newFreq = e.target.value;
        const periods = calculatePeriods(data.task_date, newFreq);
        setData((prev) => ({
            ...prev,
            period_type: newFreq,
            period_start: periods.start,
            period_end: periods.end,
        }));
    };

    // Quick helper buttons for due date
    const setQuickDueDate = (offsetDays = 0, time = '18:00') => {
        const d = new Date();
        d.setDate(d.getDate() + offsetDays);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const dateStr = `${y}-${m}-${day}`;
        const newDueAt = `${dateStr}T${time}`;
        const periods = calculatePeriods(dateStr, data.period_type);

        setData((prev) => ({
            ...prev,
            due_at: newDueAt,
            task_date: dateStr,
            period_start: periods.start,
            period_end: periods.end,
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(resolveUrl('kpi.assignments.instances.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                if (typeof onSuccess === 'function') {
                    onSuccess();
                }
                if (typeof onClose === 'function') {
                    onClose();
                }
            },
        });
    };

    const handleBackdropClick = (e) => {
        if (e.target === e.currentTarget && !isTemplateModalOpen && !isUserModalOpen) {
            onClose();
        }
    };

    if (!isOpen) return null;

    return (
        <>
            <div
                onClick={handleBackdropClick}
                className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm"
            >
                <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto overflow-x-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 no-scrollbar">
                    {/* Header */}
                    <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    Manual Instance
                                </span>
                            </div>
                            <h3 className="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">
                                Create Manual Task Instance
                            </h3>
                            <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                Target a specific employee, task template, and due date for immediate execution.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                        {/* 1. KPI Task Template Selection */}
                        <div>
                            <label className="text-xs font-bold text-slate-700 dark:text-slate-300">
                                Target KPI Task Template <span className="text-rose-500">*</span>
                            </label>
                            <div className="mt-1 flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-3 transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/60">
                                <div className="flex flex-1 items-center gap-3 overflow-hidden mr-2">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400">
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div className="truncate">
                                        <p className="truncate text-xs font-bold text-slate-800 dark:text-slate-100">
                                            {chosenTemplate ? chosenTemplate.title : 'Select KPI Task Template...'}
                                        </p>
                                        <p className="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                            {chosenTemplate
                                                ? `${chosenTemplate.group?.name || 'No Group'} • ${chosenTemplate.frequency || 'Daily'}`
                                                : 'Click to choose from available templates'}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setIsTemplateModalOpen(true)}
                                    className="shrink-0 rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                >
                                    {chosenTemplate ? 'Change' : 'Select'}
                                </button>
                            </div>
                            {chosenTemplate && (
                                <div className="mt-1.5 flex flex-wrap items-center gap-2 px-1 text-[11px] text-slate-500 dark:text-slate-400">
                                    <span className="rounded-full bg-blue-50 px-2 py-0.5 font-bold uppercase text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                        {chosenTemplate.frequency}
                                    </span>
                                    {chosenTemplate.requires_images && (
                                        <span className="rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                            Evidence: {chosenTemplate.min_images || 0}+ photo(s)
                                        </span>
                                    )}
                                    {chosenTemplate.cutoff_time && (
                                        <span>Cutoff: {chosenTemplate.cutoff_time.substring(0, 5)}</span>
                                    )}
                                </div>
                            )}
                            {errors.task_template_id && (
                                <p className="mt-1 text-xs text-rose-600">{errors.task_template_id}</p>
                            )}
                        </div>

                        {/* 2. Target Employee Selection */}
                        <div>
                            <label className="text-xs font-bold text-slate-700 dark:text-slate-300">
                                Target Employee <span className="text-rose-500">*</span>
                            </label>
                            <div className="mt-1 flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-3 transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/60">
                                <div className="flex flex-1 items-center gap-3 overflow-hidden mr-2">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400">
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div className="truncate">
                                        <p className="truncate text-xs font-bold text-slate-800 dark:text-slate-100">
                                            {chosenEmployee ? chosenEmployee.name : 'Select Employee...'}
                                        </p>
                                        <p className="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                            {chosenEmployee
                                                ? `${chosenEmployee.position?.name || 'Staff'} • ${chosenEmployee.department?.name || 'Department'}`
                                                : 'Click to choose the assigned employee'}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setIsUserModalOpen(true)}
                                    className="shrink-0 rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                >
                                    {chosenEmployee ? 'Change' : 'Select'}
                                </button>
                            </div>
                            {errors.user_id && (
                                <p className="mt-1 text-xs text-rose-600">{errors.user_id}</p>
                            )}
                        </div>

                        {/* 3. Target Due Date & Time */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Target Due Date &amp; Time <span className="text-rose-500">*</span>
                                </label>
                                <div className="flex gap-1.5 text-[11px]">
                                    <button
                                        type="button"
                                        onClick={() => setQuickDueDate(0, chosenTemplate?.cutoff_time?.substring(0, 5) || '18:00')}
                                        className="rounded-lg border border-slate-200 bg-white px-2 py-0.5 font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        Today
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setQuickDueDate(1, chosenTemplate?.cutoff_time?.substring(0, 5) || '18:00')}
                                        className="rounded-lg border border-slate-200 bg-white px-2 py-0.5 font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        Tomorrow
                                    </button>
                                </div>
                            </div>

                            <div className="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <span className="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Due At (Date &amp; Time)</span>
                                    <input
                                        type="datetime-local"
                                        required
                                        value={data.due_at}
                                        onChange={handleDueAtChange}
                                        className="mt-1 block h-9 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {errors.due_at && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.due_at}</p>
                                    )}
                                </div>

                                <div>
                                    <span className="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Task Date</span>
                                    <input
                                        type="date"
                                        value={data.task_date}
                                        onChange={handleTaskDateChange}
                                        className="mt-1 block h-9 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {errors.task_date && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.task_date}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* 4. Advanced Period & Status Controls */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                            <button
                                type="button"
                                onClick={() => setShowAdvanced(!showAdvanced)}
                                className="flex w-full items-center justify-between text-left text-xs font-bold text-slate-700 dark:text-slate-300"
                            >
                                <span className="flex items-center gap-1.5">
                                    <svg
                                        className={`h-4 w-4 transition-transform ${showAdvanced ? 'rotate-90' : ''}`}
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    Advanced Period &amp; Schedule Settings
                                </span>
                                <span className="text-[11px] font-normal text-slate-400">
                                    {showAdvanced ? 'Hide options' : 'Auto-computed from template'}
                                </span>
                            </button>

                            {showAdvanced && (
                                <div className="mt-3 space-y-3 border-t border-slate-200 pt-3 dark:border-slate-700">
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Period Frequency
                                            </label>
                                            <select
                                                value={data.period_type}
                                                onChange={handlePeriodTypeChange}
                                                className="mt-1 block h-9 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                            >
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="on_demand">On Demand</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Initial Status
                                            </label>
                                            <select
                                                value={data.status}
                                                onChange={(e) => setData('status', e.target.value)}
                                                className="mt-1 block h-9 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="waiting_first_approval">Waiting First Approval</option>
                                                <option value="waiting_final_approval">Waiting Final Approval</option>
                                                <option value="passed">Passed</option>
                                                <option value="excluded">Excluded</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Period Start
                                            </label>
                                            <input
                                                type="date"
                                                value={data.period_start}
                                                onChange={(e) => setData('period_start', e.target.value)}
                                                className="mt-1 block h-9 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            />
                                        </div>

                                        <div>
                                            <label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Period End
                                            </label>
                                            <input
                                                type="date"
                                                value={data.period_end}
                                                onChange={(e) => setData('period_end', e.target.value)}
                                                className="mt-1 block h-9 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Footer Actions */}
                        <div className="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button
                                type="button"
                                onClick={onClose}
                                className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                            >
                                {processing ? (
                                    <>
                                        <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Creating Instance...
                                    </>
                                ) : (
                                    <>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Task Instance
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* Template Select Sub-Modal */}
            <TemplateSelectModal
                open={isTemplateModalOpen}
                onClose={() => setIsTemplateModalOpen(false)}
                onSelect={(tpl) => {
                    handleSelectTemplate(tpl);
                    setIsTemplateModalOpen(false);
                }}
                templates={safeTemplates}
                selectedTemplateId={data.task_template_id}
            />

            {/* Employee User Select Sub-Modal */}
            <UserSelectModal
                open={isUserModalOpen}
                onClose={() => setIsUserModalOpen(false)}
                onSelect={(u) => {
                    handleSelectEmployee(u);
                    setIsUserModalOpen(false);
                }}
                title="Select Target Employee"
                subtitle="Choose an employee to assign this task instance to"
                users={safeUsers}
                departments={safeDepartments}
                selectedUserId={data.user_id}
                selectedUserName={chosenEmployee?.name}
            />
        </>
    );
}
