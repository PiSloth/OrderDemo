import React, { useState, useEffect, useMemo, useRef } from 'react';
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
    const pageProps = usePage()?.props || {};
    const { auth = {} } = pageProps;
    const currentUserName = auth?.user?.name || pageProps.auth_user?.name || '';

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

    // Direct state tracking for picked entities
    const [chosenTemplate, setChosenTemplate] = useState(null);
    const [chosenEmployee, setChosenEmployee] = useState(null);
    const [chosenFirstApprover, setChosenFirstApprover] = useState(null);
    const [chosenFinalApprover, setChosenFinalApprover] = useState(null);

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

    // Reset or populate only when the modal opens / editingAssignment changes
    const prevOpenRef = useRef(false);
    useEffect(() => {
        if (isOpen && !prevOpenRef.current) {
            clearErrors();
            if (editingAssignment) {
                setChosenTemplate(editingAssignment.template || null);
                setChosenEmployee(editingAssignment.user || null);
                setChosenFirstApprover(editingAssignment.first_approver || null);
                setChosenFinalApprover(editingAssignment.final_approver || null);

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
                setChosenTemplate(null);
                setChosenEmployee(null);
                setChosenFirstApprover(null);
                setChosenFinalApprover(null);
                reset();
            }
        }
        prevOpenRef.current = isOpen;
    }, [isOpen, editingAssignment]);

    // Handle Escape key and body scroll lock
    useEffect(() => {
        if (!isOpen) return;
        document.body.style.overflow = 'hidden';
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && !isTemplateModalOpen && !userModalType) {
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => {
            document.body.style.overflow = 'unset';
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen, isTemplateModalOpen, userModalType, onClose]);

    // Selected objects resolution
    const selectedTemplate = useMemo(() => {
        if (chosenTemplate) return chosenTemplate;
        if (!data.assignmentTemplateId) return null;
        return templates.find((t) => String(t.id) === String(data.assignmentTemplateId)) || null;
    }, [templates, data.assignmentTemplateId, chosenTemplate]);

    const selectedEmployee = useMemo(() => {
        if (chosenEmployee) return chosenEmployee;
        if (!data.assignmentUserId) return null;
        return safeUsers.find((u) => String(u.id) === String(data.assignmentUserId)) || null;
    }, [safeUsers, data.assignmentUserId, chosenEmployee]);

    const selectedFirstApprover = useMemo(() => {
        if (chosenFirstApprover) return chosenFirstApprover;
        if (!data.assignmentFirstApproverId) return null;
        return safeUsers.find((u) => String(u.id) === String(data.assignmentFirstApproverId)) || null;
    }, [safeUsers, data.assignmentFirstApproverId, chosenFirstApprover]);

    const selectedFinalApprover = useMemo(() => {
        if (chosenFinalApprover) return chosenFinalApprover;
        if (!data.assignmentFinalApproverId) return null;
        return safeUsers.find((u) => String(u.id) === String(data.assignmentFinalApproverId)) || null;
    }, [safeUsers, data.assignmentFinalApproverId, chosenFinalApprover]);

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
                className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6"
            >
                <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto overflow-x-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 no-scrollbar">
                    {/* Header */}
                    <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                {editingAssignment ? 'Edit Employee Assignment' : 'Create Employee Assignment'}
                            </h3>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Assign a KPI task template to an employee with approvers and automated schedule rules.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                        {/* 1. KPI Task Template Input-Style Chooser */}
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                KPI Task Template <span className="text-rose-500">*</span>
                            </label>
                            <div className="mt-1 flex items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <div className="flex flex-1 items-center gap-2 overflow-hidden mr-2">
                                    <svg className="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <input
                                        type="text"
                                        readOnly
                                        placeholder="Search & Choose KPI Task Template..."
                                        value={selectedTemplate ? selectedTemplate.title : ''}
                                        onClick={() => setIsTemplateModalOpen(true)}
                                        className="w-full border-none bg-transparent p-0 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 cursor-pointer"
                                    />
                                </div>
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setIsTemplateModalOpen(true);
                                    }}
                                    className="shrink-0 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                >
                                    {selectedTemplate ? 'Change Template' : 'Select Template'}
                                </button>
                            </div>
                            {selectedTemplate && (
                                <div className="mt-1 flex flex-wrap items-center gap-2 px-1 text-xs text-slate-500 dark:text-slate-400">
                                    <span className="font-semibold text-slate-700 dark:text-slate-300">
                                        {selectedTemplate.group?.name || 'No Group'}
                                    </span>
                                    <span>•</span>
                                    <span className="uppercase text-blue-600 dark:text-blue-400 font-semibold">
                                        {selectedTemplate.frequency}
                                    </span>
                                    {selectedTemplate.group?.department && (
                                        <>
                                            <span>•</span>
                                            <span className="text-emerald-600 dark:text-emerald-400 font-medium">
                                                Filtered to {selectedTemplate.group.department.name} department
                                            </span>
                                        </>
                                    )}
                                </div>
                            )}
                            {errors.assignmentTemplateId && (
                                <p className="mt-1 text-xs text-rose-600">{errors.assignmentTemplateId}</p>
                            )}
                        </div>

                        {/* 2. Assigned Employee Input-Style Chooser */}
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                Assigned Employee <span className="text-rose-500">*</span>
                            </label>
                            <div className="mt-1 flex items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <div className="flex flex-1 items-center gap-2 overflow-hidden mr-2">
                                    <svg className="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <input
                                        type="text"
                                        readOnly
                                        placeholder="Search & Select Assigned Employee..."
                                        value={selectedEmployee ? selectedEmployee.name : ''}
                                        onClick={() => setUserModalType('employee')}
                                        className="w-full border-none bg-transparent p-0 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 cursor-pointer"
                                    />
                                </div>
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setUserModalType('employee');
                                    }}
                                    className="shrink-0 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                >
                                    {selectedEmployee ? 'Change Employee' : 'Select User'}
                                </button>
                            </div>
                            {selectedEmployee && (
                                <div className="mt-1 flex flex-wrap items-center gap-2 px-1 text-xs text-slate-500 dark:text-slate-400">
                                    <span>
                                        {selectedEmployee.department?.name || selectedEmployee.branch?.name || selectedEmployee.email}
                                    </span>
                                    {selectedEmployee.position?.name && (
                                        <>
                                            <span>•</span>
                                            <span>{selectedEmployee.position.name}</span>
                                        </>
                                    )}
                                    {selectedEmployee.name === currentUserName && (
                                        <span className="rounded bg-sky-100 px-1.5 py-0.2 text-[10px] font-bold text-sky-700">
                                            You
                                        </span>
                                    )}
                                </div>
                            )}
                            {errors.assignmentUserId && (
                                <p className="mt-1 text-xs text-rose-600">{errors.assignmentUserId}</p>
                            )}
                        </div>

                        {/* 3. Approvers Grid (Input-Style) */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            {/* First Approver */}
                            <div>
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    First Approver <span className="text-rose-500">*</span>
                                </label>
                                <div className="mt-1 flex items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <div className="flex flex-1 items-center gap-2 overflow-hidden mr-2">
                                        <svg className="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <input
                                            type="text"
                                            readOnly
                                            placeholder="Choose First Approver..."
                                            value={selectedFirstApprover ? selectedFirstApprover.name : ''}
                                            onClick={() => setUserModalType('firstApprover')}
                                            className="w-full border-none bg-transparent p-0 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 cursor-pointer"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setUserModalType('firstApprover');
                                        }}
                                        className="shrink-0 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                    >
                                        {selectedFirstApprover ? 'Change' : 'Select User'}
                                    </button>
                                </div>
                                {selectedFirstApprover && (
                                    <p className="mt-1 px-1 text-xs text-slate-500 dark:text-slate-400">
                                        {selectedFirstApprover.department?.name || selectedFirstApprover.email}
                                    </p>
                                )}
                                {errors.assignmentFirstApproverId && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.assignmentFirstApproverId}</p>
                                )}
                            </div>

                            {/* Final Approver */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        Final Approver <span className="text-xs font-normal text-slate-400">(Optional)</span>
                                    </label>
                                    {selectedFinalApprover && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setData('assignmentFinalApproverId', '');
                                                setChosenFinalApprover(null);
                                            }}
                                            className="text-xs font-medium text-rose-600 hover:underline"
                                        >
                                            Clear
                                        </button>
                                    )}
                                </div>
                                <div className="mt-1 flex items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <div className="flex flex-1 items-center gap-2 overflow-hidden mr-2">
                                        <svg className="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <input
                                            type="text"
                                            readOnly
                                            placeholder="Choose Final Approver (Optional)..."
                                            value={selectedFinalApprover ? selectedFinalApprover.name : ''}
                                            onClick={() => setUserModalType('finalApprover')}
                                            className="w-full border-none bg-transparent p-0 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 cursor-pointer"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setUserModalType('finalApprover');
                                        }}
                                        className="shrink-0 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                    >
                                        {selectedFinalApprover ? 'Change' : 'Select User'}
                                    </button>
                                </div>
                                {selectedFinalApprover && (
                                    <p className="mt-1 px-1 text-xs text-slate-500 dark:text-slate-400">
                                        {selectedFinalApprover.department?.name || selectedFinalApprover.email}
                                    </p>
                                )}
                                {errors.assignmentFinalApproverId && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.assignmentFinalApproverId}</p>
                                )}
                            </div>
                        </div>

                        {/* 4. Active Date Range */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    Start Date
                                </label>
                                <input
                                    type="date"
                                    value={data.assignmentStartsOn}
                                    onChange={(e) => setData('assignmentStartsOn', e.target.value)}
                                    className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                {errors.assignmentStartsOn && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.assignmentStartsOn}</p>
                                )}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    End Date
                                </label>
                                <input
                                    type="date"
                                    value={data.assignmentEndsOn}
                                    onChange={(e) => setData('assignmentEndsOn', e.target.value)}
                                    className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                {errors.assignmentEndsOn && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.assignmentEndsOn}</p>
                                )}
                            </div>
                        </div>

                        {/* 5. Calendar Control Section */}
                        <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40 space-y-4">
                            <div>
                                <h4 className="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                    Calendar Push &amp; Reminder Settings
                                </h4>
                                <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    Configure automated calendar entries and interval notifications for this employee.
                                </p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
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
                                    <label className="text-xs font-medium text-slate-700 dark:text-slate-200">
                                        Reminder Start Time
                                    </label>
                                    <input
                                        type="time"
                                        value={data.assignmentReminderStartTime}
                                        onChange={(e) => setData('assignmentReminderStartTime', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </div>

                                <div>
                                    <label className="text-xs font-medium text-slate-700 dark:text-slate-200">
                                        Reminder Interval (Minutes)
                                    </label>
                                    <input
                                        type="number"
                                        min="15"
                                        max="240"
                                        value={data.assignmentReminderIntervalMinutes}
                                        onChange={(e) => setData('assignmentReminderIntervalMinutes', parseInt(e.target.value, 10) || 60)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
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
                                    <label className="text-xs font-medium text-slate-700 dark:text-slate-200">
                                        Weekly/Monthly Refresh Time
                                    </label>
                                    <input
                                        type="time"
                                        value={data.assignmentWeeklyMonthlyRefreshTime}
                                        onChange={(e) => setData('assignmentWeeklyMonthlyRefreshTime', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-4 border-t border-slate-200 pt-3 dark:border-slate-700">
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
                            <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                {errors.assignmentGenerator}
                            </div>
                        )}

                        {/* Footer Actions */}
                        <div className="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button
                                type="button"
                                onClick={onClose}
                                disabled={processing}
                                className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900"
                            >
                                {processing && (
                                    <svg className="h-4 w-4 animate-spin text-current" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                )}
                                {editingAssignment ? 'Update Assignment' : 'Save Assignment'}
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
                    if (template) {
                        setChosenTemplate(template);
                        setData('assignmentTemplateId', String(template.id || ''));
                    }
                    setIsTemplateModalOpen(false);
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
                users={safeUsers}
                departments={safeDepartments}
                currentUserName={currentUserName}
                initialDepartmentIds={userModalType === 'employee' ? templateDepartmentIds : []}
                onSelect={(user) => {
                    if (user) {
                        const matchedId = user.id
                            ? String(user.id)
                            : (safeUsers.find((u) => u.name === user.name)?.id ? String(safeUsers.find((u) => u.name === user.name).id) : '');

                        if (userModalType === 'employee') {
                            setChosenEmployee(user);
                            setData('assignmentUserId', matchedId);
                        } else if (userModalType === 'firstApprover') {
                            setChosenFirstApprover(user);
                            setData('assignmentFirstApproverId', matchedId);
                        } else if (userModalType === 'finalApprover') {
                            setChosenFinalApprover(user);
                            setData('assignmentFinalApproverId', matchedId);
                        }
                    }
                    setUserModalType(null);
                }}
            />
        </>
    );
}
