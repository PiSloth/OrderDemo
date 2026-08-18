import React, { useState, useEffect, useMemo } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import UserSelectModal from '@/components/IT/UserSelectModal';
import TemplateSelectModal from './TemplateSelectModal';

export default function AssignmentModal({
    isOpen = false,
    onClose,
    editingAssignment = null,
    templates = [],
    users = [],
    departments = [],
}) {
    const { auth = {} } = usePage().props;
    const currentUserName = auth?.user?.name || '';

    // Modal state for Sub-Modals
    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [userModalType, setUserModalType] = useState(null); // 'employee' | 'firstApprover' | 'finalApprover' | null

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        assignmentTemplateId: '',
        assignmentUserId: '',
        assignmentFirstApproverId: '',
        assignmentFinalApproverId: '',
        assignmentStartsOn: '',
        assignmentEndsOn: '',
        assignmentCalendarPushEnabled: true,
        assignmentDailyReminderEnabled: true,
        assignmentReminderStartTime: '08:45',
        assignmentReminderIntervalMinutes: 60,
        assignmentWeeklyMonthlyRefreshEnabled: true,
        assignmentWeeklyMonthlyRefreshTime: '09:15',
        assignmentPushUntilFinalized: true,
        assignmentIsActive: true,
    });

    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            const handleKeyDown = (e) => {
                if (e.key === 'Escape' && !isTemplateModalOpen && !userModalType) {
                    onClose();
                }
            };
            window.addEventListener('keydown', handleKeyDown);

            clearErrors();
            if (editingAssignment) {
                setData({
                    assignmentTemplateId: editingAssignment.task_template_id ? String(editingAssignment.task_template_id) : '',
                    assignmentUserId: editingAssignment.user_id ? String(editingAssignment.user_id) : '',
                    assignmentFirstApproverId: editingAssignment.first_approver_user_id ? String(editingAssignment.first_approver_user_id) : '',
                    assignmentFinalApproverId: editingAssignment.final_approver_user_id ? String(editingAssignment.final_approver_user_id) : '',
                    assignmentStartsOn: editingAssignment.starts_on ? editingAssignment.starts_on.substring(0, 10) : '',
                    assignmentEndsOn: editingAssignment.ends_on ? editingAssignment.ends_on.substring(0, 10) : '',
                    assignmentCalendarPushEnabled: Boolean(editingAssignment.calendar_push_enabled ?? true),
                    assignmentDailyReminderEnabled: Boolean(editingAssignment.calendar_control?.daily_reminder_enabled ?? true),
                    assignmentReminderStartTime: editingAssignment.calendar_control?.reminder_start_time
                        ? String(editingAssignment.calendar_control.reminder_start_time).substring(0, 5)
                        : '08:45',
                    assignmentReminderIntervalMinutes: editingAssignment.calendar_control?.reminder_interval_minutes ?? 60,
                    assignmentWeeklyMonthlyRefreshEnabled: Boolean(editingAssignment.calendar_control?.weekly_monthly_refresh_enabled ?? true),
                    assignmentWeeklyMonthlyRefreshTime: editingAssignment.calendar_control?.weekly_monthly_refresh_time
                        ? String(editingAssignment.calendar_control.weekly_monthly_refresh_time).substring(0, 5)
                        : '09:15',
                    assignmentPushUntilFinalized: Boolean(editingAssignment.calendar_control?.push_until_finalized ?? true),
                    assignmentIsActive: Boolean(editingAssignment.is_active ?? true),
                });
            } else {
                reset();
            }

            return () => {
                document.body.style.overflow = 'unset';
                window.removeEventListener('keydown', handleKeyDown);
            };
        }
    }, [editingAssignment, isOpen, onClose, isTemplateModalOpen, userModalType]);

    // Selected objects resolution
    const selectedTemplate = useMemo(() => {
        return templates.find((t) => String(t.id) === String(data.assignmentTemplateId)) || null;
    }, [templates, data.assignmentTemplateId]);

    const selectedEmployee = useMemo(() => {
        return users.find((u) => String(u.id) === String(data.assignmentUserId)) || null;
    }, [users, data.assignmentUserId]);

    const selectedFirstApprover = useMemo(() => {
        return users.find((u) => String(u.id) === String(data.assignmentFirstApproverId)) || null;
    }, [users, data.assignmentFirstApproverId]);

    const selectedFinalApprover = useMemo(() => {
        return users.find((u) => String(u.id) === String(data.assignmentFinalApproverId)) || null;
    }, [users, data.assignmentFinalApproverId]);

    // Initial Department filter for employee selector if template has a specific group department
    const templateDepartmentIds = useMemo(() => {
        if (selectedTemplate?.group?.department_id) {
            return [Number(selectedTemplate.group.department_id)];
        }
        return [];
    }, [selectedTemplate]);

    if (!isOpen) return null;

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        if (name === 'kpi.assignments.store') return '/kpi/assignments';
        if (name === 'kpi.assignments.update') return `/kpi/assignments/${id}`;
        return '/kpi/assignments';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingAssignment) {
            put(resolveUrl('kpi.assignments.update', editingAssignment.id), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
                preserveScroll: true,
            });
        } else {
            post(resolveUrl('kpi.assignments.store'), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
                preserveScroll: true,
            });
        }
    };

    const handleBackdropClick = (e) => {
        if (e.target === e.currentTarget && !isTemplateModalOpen && !userModalType) {
            onClose();
        }
    };

    return (
        <>
            <div
                onClick={handleBackdropClick}
                className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/60 px-4 py-6"
            >
                <div className="max-h-[92vh] w-full max-w-3xl overflow-y-auto overflow-x-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 no-scrollbar">
                    {/* Header */}
                    <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div>
                            <h3 className="text-xl font-bold text-slate-900 dark:text-slate-100">
                                {editingAssignment ? 'Edit Employee Assignment' : 'New Employee Assignment'}
                            </h3>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Assign a KPI task template to an employee with approvers and automated schedule rules.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                        >
                            ✕
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="mt-6 space-y-6">
                        {/* 1. KPI Task Template Chooser */}
                        <div>
                            <label className="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                                KPI Task Template <span className="text-rose-500">*</span>
                            </label>

                            {selectedTemplate ? (
                                <div className="mt-2 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-bold text-slate-900 dark:text-slate-100">
                                                {selectedTemplate.title}
                                            </span>
                                            <span className="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                                {selectedTemplate.group?.name || 'No Group'}
                                            </span>
                                            <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold uppercase text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                {selectedTemplate.frequency}
                                            </span>
                                        </div>
                                        {selectedTemplate.guideline && (
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                {selectedTemplate.guideline}
                                            </p>
                                        )}
                                        {selectedTemplate.group?.department && (
                                            <p className="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                Filtered to {selectedTemplate.group.department.name} department
                                            </p>
                                        )}
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setIsTemplateModalOpen(true)}
                                        className="shrink-0 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                                    >
                                        Change Template
                                    </button>
                                </div>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => setIsTemplateModalOpen(true)}
                                    className="mt-2 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 p-4 text-sm font-semibold text-slate-600 hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    <svg className="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search &amp; Choose KPI Task Template...
                                </button>
                            )}

                            {errors.assignmentTemplateId && (
                                <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.assignmentTemplateId}</p>
                            )}
                        </div>

                        {/* 2. Assigned Employee Chooser */}
                        <div>
                            <label className="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                                Assigned Employee <span className="text-rose-500">*</span>
                            </label>

                            {selectedEmployee ? (
                                <div className="mt-2 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 font-bold text-white shadow-sm">
                                            {selectedEmployee.name ? selectedEmployee.name.charAt(0).toUpperCase() : 'U'}
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-bold text-slate-900 dark:text-slate-100">
                                                    {selectedEmployee.name}
                                                </span>
                                                {selectedEmployee.name === currentUserName && (
                                                    <span className="rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold text-sky-700">
                                                        You
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                {selectedEmployee.position?.name || selectedEmployee.department?.name || selectedEmployee.email}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setUserModalType('employee')}
                                        className="shrink-0 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                                    >
                                        Change Employee
                                    </button>
                                </div>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => setUserModalType('employee')}
                                    className="mt-2 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 p-4 text-sm font-semibold text-slate-600 hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    <svg className="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Search &amp; Select Employee...
                                </button>
                            )}

                            {errors.assignmentUserId && (
                                <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.assignmentUserId}</p>
                            )}
                        </div>

                        {/* 3. Approvers Grid */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            {/* First Approver */}
                            <div>
                                <label className="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    First Approver <span className="text-rose-500">*</span>
                                </label>

                                {selectedFirstApprover ? (
                                    <div className="mt-2 flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                        <div className="flex items-center gap-2.5 truncate">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 font-bold text-xs text-white">
                                                {selectedFirstApprover.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="truncate">
                                                <p className="truncate text-xs font-bold text-slate-900 dark:text-slate-100">
                                                    {selectedFirstApprover.name}
                                                </p>
                                                <p className="truncate text-[11px] text-slate-500">
                                                    {selectedFirstApprover.department?.name || selectedFirstApprover.email}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setUserModalType('firstApprover')}
                                            className="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                                        >
                                            Change
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => setUserModalType('firstApprover')}
                                        className="mt-2 flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white p-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    >
                                        Choose First Approver...
                                    </button>
                                )}

                                {errors.assignmentFirstApproverId && (
                                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.assignmentFirstApproverId}</p>
                                )}
                            </div>

                            {/* Final Approver */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <label className="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                                        Final Approver <span className="text-xs font-normal text-slate-400">(Optional)</span>
                                    </label>
                                    {selectedFinalApprover && (
                                        <button
                                            type="button"
                                            onClick={() => setData('assignmentFinalApproverId', '')}
                                            className="text-xs font-medium text-rose-600 hover:underline"
                                        >
                                            Clear
                                        </button>
                                    )}
                                </div>

                                {selectedFinalApprover ? (
                                    <div className="mt-2 flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                        <div className="flex items-center gap-2.5 truncate">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-600 font-bold text-xs text-white">
                                                {selectedFinalApprover.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="truncate">
                                                <p className="truncate text-xs font-bold text-slate-900 dark:text-slate-100">
                                                    {selectedFinalApprover.name}
                                                </p>
                                                <p className="truncate text-[11px] text-slate-500">
                                                    {selectedFinalApprover.department?.name || selectedFinalApprover.email}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setUserModalType('finalApprover')}
                                            className="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                                        >
                                            Change
                                        </button>
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => setUserModalType('finalApprover')}
                                        className="mt-2 flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white p-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    >
                                        Choose Final Approver...
                                    </button>
                                )}

                                {errors.assignmentFinalApproverId && (
                                    <p className="mt-1.5 text-xs font-medium text-rose-600">{errors.assignmentFinalApproverId}</p>
                                )}
                            </div>
                        </div>

                        {/* 4. Active Date Range */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    Start Date
                                </label>
                                <input
                                    type="date"
                                    value={data.assignmentStartsOn}
                                    onChange={(e) => setData('assignmentStartsOn', e.target.value)}
                                    className="mt-1.5 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                {errors.assignmentStartsOn && (
                                    <p className="mt-1.5 text-xs text-rose-600">{errors.assignmentStartsOn}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    End Date
                                </label>
                                <input
                                    type="date"
                                    value={data.assignmentEndsOn}
                                    onChange={(e) => setData('assignmentEndsOn', e.target.value)}
                                    className="mt-1.5 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                {errors.assignmentEndsOn && (
                                    <p className="mt-1.5 text-xs text-rose-600">{errors.assignmentEndsOn}</p>
                                )}
                            </div>
                        </div>

                        {/* 5. Calendar Control Section */}
                        <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/60">
                            <h4 className="text-sm font-bold text-slate-900 dark:text-slate-100">
                                Calendar Push &amp; Reminder Settings
                            </h4>
                            <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                Configure automated calendar entries and interval notifications for this employee.
                            </p>

                            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                <label className="flex items-center gap-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={data.assignmentCalendarPushEnabled}
                                        onChange={(e) => setData('assignmentCalendarPushEnabled', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    Enable Calendar Push
                                </label>

                                <label className="flex items-center gap-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={data.assignmentDailyReminderEnabled}
                                        onChange={(e) => setData('assignmentDailyReminderEnabled', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    Daily Reminder Enabled
                                </label>

                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400">
                                        Reminder Start Time
                                    </label>
                                    <input
                                        type="time"
                                        value={data.assignmentReminderStartTime}
                                        onChange={(e) => setData('assignmentReminderStartTime', e.target.value)}
                                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400">
                                        Reminder Interval (Minutes)
                                    </label>
                                    <input
                                        type="number"
                                        min="15"
                                        max="240"
                                        value={data.assignmentReminderIntervalMinutes}
                                        onChange={(e) => setData('assignmentReminderIntervalMinutes', parseInt(e.target.value, 10) || 60)}
                                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </div>

                                <label className="flex items-center gap-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={data.assignmentWeeklyMonthlyRefreshEnabled}
                                        onChange={(e) => setData('assignmentWeeklyMonthlyRefreshEnabled', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    Weekly/Monthly Refresh Enabled
                                </label>

                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400">
                                        Weekly/Monthly Refresh Time
                                    </label>
                                    <input
                                        type="time"
                                        value={data.assignmentWeeklyMonthlyRefreshTime}
                                        onChange={(e) => setData('assignmentWeeklyMonthlyRefreshTime', e.target.value)}
                                        className="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap gap-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                                <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={data.assignmentPushUntilFinalized}
                                        onChange={(e) => setData('assignmentPushUntilFinalized', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    Push Until Finalized
                                </label>

                                <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={data.assignmentIsActive}
                                        onChange={(e) => setData('assignmentIsActive', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    Assignment Active
                                </label>
                            </div>
                        </div>

                        {/* Error messages banner */}
                        {errors.assignmentGenerator && (
                            <div className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
                                {errors.assignmentGenerator}
                            </div>
                        )}

                        {/* Action buttons */}
                        <div className="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button
                                type="button"
                                onClick={onClose}
                                disabled={processing}
                                className="rounded-2xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                            >
                                {processing && (
                                    <svg className="h-4 w-4 animate-spin text-current" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                )}
                                {editingAssignment ? 'Update Assignment' : 'Create Assignment'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* Template Select Sub-Modal */}
            <TemplateSelectModal
                open={isTemplateModalOpen}
                onClose={() => setIsTemplateModalOpen(false)}
                templates={templates}
                selectedTemplateId={data.assignmentTemplateId}
                onSelect={(template) => {
                    setData('assignmentTemplateId', String(template.id));
                }}
            />

            {/* User Select Sub-Modal (Reusing UserSelectModal from Issue Creation) */}
            <UserSelectModal
                open={Boolean(userModalType)}
                onClose={() => setUserModalType(null)}
                title={
                    userModalType === 'employee'
                        ? 'Select Assigned Employee'
                        : userModalType === 'firstApprover'
                        ? 'Select First Approver'
                        : 'Select Final Approver'
                }
                subtitle={
                    userModalType === 'employee'
                        ? 'Choose the employee responsible for completing this KPI task'
                        : 'Choose the approver for reviewing submissions'
                }
                selectedUserId={
                    userModalType === 'employee'
                        ? data.assignmentUserId
                        : userModalType === 'firstApprover'
                        ? data.assignmentFirstApproverId
                        : data.assignmentFinalApproverId
                }
                users={users}
                departments={departments}
                currentUserName={currentUserName}
                initialDepartmentIds={userModalType === 'employee' ? templateDepartmentIds : []}
                onSelect={(user) => {
                    if (userModalType === 'employee') {
                        setData('assignmentUserId', String(user.id));
                    } else if (userModalType === 'firstApprover') {
                        setData('assignmentFirstApproverId', String(user.id));
                    } else if (userModalType === 'finalApprover') {
                        setData('assignmentFinalApproverId', String(user.id));
                    }
                    setUserModalType(null);
                }}
            />
        </>
    );
}
