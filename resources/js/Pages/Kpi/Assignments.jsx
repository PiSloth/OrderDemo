import React, { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import KpiLayout from '../../Layouts/KpiLayout';
import AssignmentModal from './Components/AssignmentModal';
import InstanceEditModal from './Components/InstanceEditModal';
import SearchableEmployeeSelect from './Components/SearchableEmployeeSelect';

export default function Assignments({
    assignments = [],
    templates = [],
    departments = [],
    users = [],
    instances = [],
    filters = {},
    canManageInstances = false,
    instanceStatusOptions = {},
}) {
    const { flash = {}, errors = {} } = usePage().props;

    // Assignment Modal state
    const [isAssignmentModalOpen, setIsAssignmentModalOpen] = useState(false);
    const [editingAssignment, setEditingAssignment] = useState(null);

    // Instance Modal state
    const [isInstanceModalOpen, setIsInstanceModalOpen] = useState(false);
    const [editingInstance, setEditingInstance] = useState(null);

    // Client-side search filters
    const [clientSearch, setClientSearch] = useState('');
    const [selectedUserFilter, setSelectedUserFilter] = useState(filters.selectedUserId || 0);
    const [isActiveFilter, setIsActiveFilter] = useState(filters.isActive !== undefined ? String(filters.isActive) : '1');

    // Instance filter states
    const [instanceUserFilter, setInstanceUserFilter] = useState(filters.instanceUserId || 0);
    const [instanceStatusFilter, setInstanceStatusFilter] = useState(filters.instanceStatusFilter || 'all');
    const [instanceDateFilter, setInstanceDateFilter] = useState(filters.instanceDateFilter || '');
    const [instanceSearch, setInstanceSearch] = useState(filters.instanceSearch || '');

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        if (name === 'kpi.assignments.destroy') return `/kpi/assignments/${id}`;
        if (name === 'kpi.assignments.instances.destroy') return `/kpi/assignments/instances/${id}`;
        return '/kpi/assignments';
    };

    // Filter server-side requests
    const applyServerFilters = (newFilters) => {
        router.get(
            resolveUrl('kpi.assignments'),
            {
                selectedUserId: newFilters.selectedUserId !== undefined ? newFilters.selectedUserId : selectedUserFilter,
                isActive: newFilters.isActive !== undefined ? newFilters.isActive : isActiveFilter,
                instanceUserId: newFilters.instanceUserId !== undefined ? newFilters.instanceUserId : instanceUserFilter,
                instanceStatusFilter: newFilters.instanceStatusFilter !== undefined ? newFilters.instanceStatusFilter : instanceStatusFilter,
                instanceDateFilter: newFilters.instanceDateFilter !== undefined ? newFilters.instanceDateFilter : instanceDateFilter,
                instanceSearch: newFilters.instanceSearch !== undefined ? newFilters.instanceSearch : instanceSearch,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const handleUserFilterChange = (userId) => {
        setSelectedUserFilter(userId);
        applyServerFilters({ selectedUserId: userId });
    };

    const handleActiveFilterChange = (e) => {
        const val = e.target.value;
        setIsActiveFilter(val);
        applyServerFilters({ isActive: val });
    };

    const handleInstanceFilterChange = (updates) => {
        if (updates.instanceUserId !== undefined) setInstanceUserFilter(updates.instanceUserId);
        if (updates.instanceStatusFilter !== undefined) setInstanceStatusFilter(updates.instanceStatusFilter);
        if (updates.instanceDateFilter !== undefined) setInstanceDateFilter(updates.instanceDateFilter);
        if (updates.instanceSearch !== undefined) setInstanceSearch(updates.instanceSearch);
        applyServerFilters(updates);
    };

    const handleClearInstanceFilters = () => {
        setInstanceUserFilter(0);
        setInstanceStatusFilter('all');
        setInstanceDateFilter('');
        setInstanceSearch('');
        applyServerFilters({
            instanceUserId: 0,
            instanceStatusFilter: 'all',
            instanceDateFilter: '',
            instanceSearch: '',
        });
    };

    // Client-side quick filter on current assignments list
    const filteredAssignments = useMemo(() => {
        if (!clientSearch.trim()) return assignments;
        const q = clientSearch.toLowerCase().trim();
        return assignments.filter((a) => {
            const title = a.template?.title?.toLowerCase() || '';
            const group = a.template?.group?.name?.toLowerCase() || '';
            const user = a.user?.name?.toLowerCase() || '';
            const first = a.first_approver?.name?.toLowerCase() || '';
            const final = a.final_approver?.name?.toLowerCase() || '';
            return title.includes(q) || group.includes(q) || user.includes(q) || first.includes(q) || final.includes(q);
        });
    }, [assignments, clientSearch]);

    const handleOpenCreateAssignment = () => {
        setEditingAssignment(null);
        setIsAssignmentModalOpen(true);
    };

    const handleOpenEditAssignment = (assignment) => {
        setEditingAssignment(assignment);
        setIsAssignmentModalOpen(true);
    };

    const handleDeleteAssignment = (assignment) => {
        if (confirm(`Delete assignment for "${assignment.user?.name} - ${assignment.template?.title}"?`)) {
            router.delete(resolveUrl('kpi.assignments.destroy', assignment.id), {
                preserveScroll: true,
            });
        }
    };

    const handleOpenEditInstance = (instance) => {
        setEditingInstance(instance);
        setIsInstanceModalOpen(true);
    };

    const handleDeleteInstance = (instance) => {
        if (confirm(`Delete task instance #${instance.id}?`)) {
            router.delete(resolveUrl('kpi.assignments.instances.destroy', instance.id), {
                preserveScroll: true,
            });
        }
    };

    const getFrequencyBadgeClass = (freq) => {
        switch (freq) {
            case 'daily':
                return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300';
            case 'weekly':
                return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300';
            case 'monthly':
                return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300';
            default:
                return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300';
        }
    };

    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'passed':
                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
            case 'failed_late':
            case 'failed_missed':
            case 'rejected':
                return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
            case 'waiting_first_approval':
            case 'waiting_final_approval':
                return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
            case 'pending':
                return 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300';
            case 'excluded':
                return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
            default:
                return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200';
        }
    };

    const usersWithAll = useMemo(() => {
        return [{ id: 0, name: 'All Employees' }, ...users];
    }, [users]);

    return (
        <KpiLayout>
            <Head title="Employee Task Assignments" />

            <div className="mx-auto max-w-7xl space-y-6">
                {/* Header Banner */}
                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    Manual Employee Assignment
                                </span>
                            </div>
                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                                Employee Task Assignments
                            </h1>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Assign KPI task templates to employees with first approvers, final approvers, active dates, and automated calendar push rules.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={handleOpenCreateAssignment}
                            className="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Assignment
                        </button>
                    </div>
                </div>

                {/* Flash & Error Notices */}
                {flash?.message && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {flash.message}
                    </div>
                )}
                {errors?.assignmentDelete && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        {errors.assignmentDelete}
                    </div>
                )}
                {errors?.assignmentGenerator && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        {errors.assignmentGenerator}
                    </div>
                )}

                {/* Assignments Section */}
                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                                Existing Assignments
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Showing {filteredAssignments.length} assignment{filteredAssignments.length !== 1 ? 's' : ''}
                            </p>
                        </div>

                        {/* Filters */}
                        <div className="flex flex-wrap items-center gap-3">
                            {/* Employee Filter */}
                            <div className="min-w-[200px]">
                                <SearchableEmployeeSelect
                                    users={usersWithAll}
                                    value={selectedUserFilter}
                                    onChange={handleUserFilterChange}
                                    className="w-full"
                                />
                            </div>

                            {/* Active Status Filter */}
                            <select
                                value={isActiveFilter}
                                onChange={handleActiveFilterChange}
                                className="h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-slate-300 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <option value="1">Active Only</option>
                                <option value="0">Inactive Only</option>
                                <option value="all">All Statuses</option>
                            </select>

                            {/* Search Input */}
                            <div className="relative">
                                <input
                                    type="text"
                                    value={clientSearch}
                                    onChange={(e) => setClientSearch(e.target.value)}
                                    placeholder="Search in list..."
                                    className="h-9 w-48 rounded-lg border border-slate-200 bg-white pl-8 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 sm:w-60"
                                />
                                <svg
                                    className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Assignments List */}
                    <div className="mt-6 space-y-4">
                        {filteredAssignments.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                No employee assignments found matching your filter criteria.
                            </div>
                        ) : (
                            filteredAssignments.map((assignment) => {
                                const isExpired = assignment.ends_on && new Date(assignment.ends_on) < new Date();
                                const freq = assignment.template?.frequency;

                                return (
                                    <div
                                        key={assignment.id}
                                        className="rounded-3xl border border-slate-200 bg-slate-50/70 p-5 transition hover:shadow-md dark:border-slate-800 dark:bg-slate-800/40"
                                    >
                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            {/* Left details */}
                                            <div className="space-y-3">
                                                {/* Header row */}
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
                                                        {assignment.template?.title || '—'}
                                                    </h3>
                                                    <span className={`rounded-full border px-2.5 py-0.5 text-xs font-bold uppercase ${getFrequencyBadgeClass(freq)}`}>
                                                        {freq || '—'}
                                                    </span>
                                                    <span
                                                        className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                            assignment.is_active
                                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                                : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400'
                                                        }`}
                                                    >
                                                        {assignment.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                    <span
                                                        className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                            isExpired
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                                                : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                        }`}
                                                    >
                                                        {isExpired ? 'Expired' : 'Ongoing'}
                                                    </span>
                                                </div>

                                                {/* Metadata Grid */}
                                                <div className="grid gap-x-6 gap-y-2 text-xs text-slate-600 dark:text-slate-300 md:grid-cols-2 lg:grid-cols-3">
                                                    <div>
                                                        <span className="font-semibold text-slate-700 dark:text-slate-200">Employee: </span>
                                                        <span className="font-bold text-slate-900 dark:text-slate-100">{assignment.user?.name || '—'}</span>
                                                        {assignment.user?.position?.name && ` • ${assignment.user.position.name}`}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-700 dark:text-slate-200">Group: </span>
                                                        {assignment.template?.group?.name || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-700 dark:text-slate-200">Department: </span>
                                                        {assignment.user?.department?.name || assignment.template?.group?.department?.name || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-700 dark:text-slate-200">First Approver: </span>
                                                        {assignment.first_approver?.name || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-700 dark:text-slate-200">Final Approver: </span>
                                                        {assignment.final_approver?.name || 'Not required'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-700 dark:text-slate-200">Active Dates: </span>
                                                        {assignment.starts_on ? assignment.starts_on.substring(0, 10) : 'Open'} ~{' '}
                                                        {assignment.ends_on ? assignment.ends_on.substring(0, 10) : 'Open'}
                                                    </div>
                                                </div>

                                                {/* Guideline text */}
                                                {assignment.template?.guideline && (
                                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                                        <span className="font-medium text-slate-600 dark:text-slate-300">Guideline: </span>
                                                        {assignment.template.guideline}
                                                    </p>
                                                )}

                                                {/* Calendar push summary card */}
                                                <div className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                                    <span className="font-semibold">Calendar Push: </span>
                                                    {assignment.calendar_push_enabled ? (
                                                        <span className="font-bold text-emerald-600 dark:text-emerald-400">Enabled</span>
                                                    ) : (
                                                        <span className="text-slate-400">Disabled</span>
                                                    )}
                                                    {' • '}
                                                    <span className="font-semibold">Reminder: </span>
                                                    {assignment.calendar_control?.reminder_start_time
                                                        ? String(assignment.calendar_control.reminder_start_time).substring(0, 5)
                                                        : '08:45'}
                                                    {' • '}
                                                    <span className="font-semibold">Interval: </span>
                                                    Every {assignment.calendar_control?.reminder_interval_minutes ?? 60} mins
                                                    {' • '}
                                                    <span className="font-semibold">Refresh: </span>
                                                    {assignment.calendar_control?.weekly_monthly_refresh_time
                                                        ? String(assignment.calendar_control.weekly_monthly_refresh_time).substring(0, 5)
                                                        : '09:15'}
                                                </div>

                                                {/* Instances count */}
                                                <div className="flex items-center gap-2">
                                                    <span className="rounded-lg bg-slate-200/80 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                        {assignment.instances_count ?? 0} generated instances
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Action Buttons */}
                                            <div className="flex items-center gap-2 self-end xl:self-start">
                                                <button
                                                    type="button"
                                                    onClick={() => handleOpenEditAssignment(assignment)}
                                                    className="inline-flex items-center gap-1.5 rounded-xl border border-yellow-300 bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-800 shadow-sm hover:bg-yellow-100 dark:border-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-300"
                                                >
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteAssignment(assignment)}
                                                    className="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-800 shadow-sm hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300"
                                                >
                                                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>

                {/* Super Admin: Created Task Instances Section */}
                {canManageInstances && (
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <span className="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                    Super Admin Control
                                </span>
                                <h2 className="mt-1 text-lg font-bold text-slate-900 dark:text-slate-100">
                                    Created Task Instances
                                </h2>
                                <p className="text-xs text-slate-500 dark:text-slate-400">
                                    Review, adjust status, edit submission images, and manage generated KPI instances per employee.
                                </p>
                            </div>
                        </div>

                        {/* Instance Filters */}
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {/* Employee select */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Employee
                                </label>
                                <div className="mt-1">
                                    <SearchableEmployeeSelect
                                        users={usersWithAll}
                                        value={instanceUserFilter}
                                        onChange={(id) => handleInstanceFilterChange({ instanceUserId: id })}
                                        className="w-full"
                                    />
                                </div>
                            </div>

                            {/* Status dropdown */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Status
                                </label>
                                <select
                                    value={instanceStatusFilter}
                                    onChange={(e) => handleInstanceFilterChange({ instanceStatusFilter: e.target.value })}
                                    className="mt-1 block h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    {Object.entries(instanceStatusOptions).map(([val, label]) => (
                                        <option key={val} value={val}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Task Date */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Task Date
                                </label>
                                <input
                                    type="date"
                                    value={instanceDateFilter}
                                    onChange={(e) => handleInstanceFilterChange({ instanceDateFilter: e.target.value })}
                                    className="mt-1 block h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                />
                            </div>

                            {/* Search */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Search Instances
                                </label>
                                <input
                                    type="text"
                                    value={instanceSearch}
                                    onChange={(e) => handleInstanceFilterChange({ instanceSearch: e.target.value })}
                                    placeholder="Template, employee, group..."
                                    className="mt-1 block h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                />
                            </div>
                        </div>

                        <div className="mt-3 flex justify-end">
                            <button
                                type="button"
                                onClick={handleClearInstanceFilters}
                                className="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                Clear filters
                            </button>
                        </div>

                        {/* Instances List */}
                        <div className="mt-4 space-y-3">
                            {instances.length === 0 ? (
                                <div className="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                    No generated task instances found.
                                </div>
                            ) : (
                                instances.map((instance) => (
                                    <div
                                        key={instance.id}
                                        className="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-slate-100/60 dark:border-slate-800 dark:bg-slate-800/50"
                                    >
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="space-y-1.5">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-bold text-slate-900 dark:text-slate-100 text-sm">
                                                        {instance.template?.title || '—'}
                                                    </span>
                                                    <span className="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-bold uppercase text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                                        {instance.period_type || '—'}
                                                    </span>
                                                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${getStatusBadgeClass(instance.status)}`}>
                                                        {instance.status}
                                                    </span>
                                                </div>

                                                <div className="grid gap-x-6 gap-y-1 text-xs text-slate-600 dark:text-slate-300 sm:grid-cols-2 md:grid-cols-3">
                                                    <div>
                                                        <span className="font-semibold">Employee: </span>
                                                        {instance.user?.name || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold">Group: </span>
                                                        {instance.template?.group?.name || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold">Task Date: </span>
                                                        {instance.task_date || '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold">Due At: </span>
                                                        {instance.due_at ? instance.due_at.replace('T', ' ') : '—'}
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold">Period: </span>
                                                        {instance.period_start} ~ {instance.period_end}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2 shrink-0">
                                                <button
                                                    type="button"
                                                    onClick={() => handleOpenEditInstance(instance)}
                                                    className="rounded-lg border border-yellow-300 bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-800 hover:bg-yellow-100 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-300"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteInstance(instance)}
                                                    className="rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-800 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Assignment Create / Edit Modal */}
            <AssignmentModal
                isOpen={isAssignmentModalOpen}
                onClose={() => setIsAssignmentModalOpen(false)}
                editingAssignment={editingAssignment}
                templates={templates}
                users={users}
                departments={departments}
            />

            {/* Super Admin Instance Edit Modal */}
            <InstanceEditModal
                isOpen={isInstanceModalOpen}
                onClose={() => setIsInstanceModalOpen(false)}
                instance={editingInstance}
                statusOptions={instanceStatusOptions}
            />
        </KpiLayout>
    );
}
