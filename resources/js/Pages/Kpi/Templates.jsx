import React, { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import KpiLayout from '../../Layouts/KpiLayout';
import GroupModal from './Components/GroupModal';
import GroupTable from './Components/GroupTable';
import TemplateModal from './Components/TemplateModal';

export default function Templates({ groups, allGroups = [], departments = [], templates = [], templateEmployees = [], filters = {} }) {
    const { auth = {}, flash = {}, errors = {} } = usePage().props;
    const canManage = auth?.can?.kpiManageTemplates ?? false;

    const [activeTab, setActiveTab] = useState('groups');
    const [isGroupModalOpen, setIsGroupModalOpen] = useState(false);
    const [editingGroup, setEditingGroup] = useState(null);

    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState(null);
    const [employeeFilter, setEmployeeFilter] = useState(filters.templateEmployeeFilter || '');

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        if (name === 'kpi.templates.destroy') return `/kpi/templates/${id}`;
        return '/kpi/templates';
    };

    const handleOpenCreateGroup = () => {
        setEditingGroup(null);
        setIsGroupModalOpen(true);
    };

    const handleOpenEditGroup = (group) => {
        setEditingGroup(group);
        setIsGroupModalOpen(true);
    };

    const handleOpenCreateTemplate = () => {
        setEditingTemplate(null);
        setIsTemplateModalOpen(true);
    };

    const handleOpenEditTemplate = (template) => {
        setEditingTemplate(template);
        setIsTemplateModalOpen(true);
    };

    const handleDeleteTemplate = (template) => {
        if (confirm(`Delete task template "${template.title}"?`)) {
            router.delete(resolveUrl('kpi.templates.destroy', template.id), {
                preserveScroll: true,
            });
        }
    };

    const handleFilterChange = (e) => {
        const value = e.target.value;
        setEmployeeFilter(value);
        router.get(
            resolveUrl('kpi.templates'),
            { templateEmployeeFilter: value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    return (
        <KpiLayout>
            <Head title="KPI Task Templates & Groups" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header section */}
                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                Configuration
                            </span>
                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                                Task Templates &amp; Groups
                            </h1>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Organize KPI task templates into groups and set target performance rules.
                            </p>
                        </div>

                        {canManage && (
                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    onClick={handleOpenCreateGroup}
                                    className="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    + Create Group
                                </button>
                                <button
                                    type="button"
                                    onClick={handleOpenCreateTemplate}
                                    className="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                >
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    + Create Template
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Flash Messages / Errors */}
                {flash?.message && (
                    <div className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {flash.message}
                    </div>
                )}
                {errors?.groupDelete && (
                    <div className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        {errors.groupDelete}
                    </div>
                )}
                {errors?.groupRuleType && (
                    <div className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        {errors.groupRuleType}
                    </div>
                )}

                {/* Tab selector */}
                <div className="mt-6 flex justify-start">
                    <div className="inline-flex rounded-full border border-slate-200 bg-slate-200/60 p-1 dark:border-slate-800 dark:bg-slate-800/80 sm:w-80">
                        <button
                            type="button"
                            onClick={() => setActiveTab('groups')}
                            className={`flex-1 rounded-full px-4 py-2 text-sm font-medium transition ${
                                activeTab === 'groups'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                            }`}
                        >
                            Created Groups
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('templates')}
                            className={`flex-1 rounded-full px-4 py-2 text-sm font-medium transition ${
                                activeTab === 'templates'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                            }`}
                        >
                            Created Templates
                        </button>
                    </div>
                </div>

                {/* Tab content */}
                <div className="mt-6">
                    {activeTab === 'groups' && groups && (
                        <GroupTable groups={groups} onEditGroup={handleOpenEditGroup} canManage={canManage} />
                    )}

                    {activeTab === 'templates' && (
                        <div className="space-y-4">
                            <div className="flex flex-col gap-3 sm:max-w-xs">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    Assigned User Filter
                                </label>
                                <select
                                    value={employeeFilter}
                                    onChange={handleFilterChange}
                                    className="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                >
                                    <option value="">All Assigned Users</option>
                                    {templateEmployees.map((emp) => (
                                        <option key={emp.id} value={emp.id}>
                                            {emp.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                        <thead className="bg-slate-50 dark:bg-slate-800/80">
                                            <tr className="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                                <th className="px-4 py-3">Template</th>
                                                <th className="px-4 py-3">Group</th>
                                                <th className="px-4 py-3">Assigned User</th>
                                                <th className="px-4 py-3">Frequency</th>
                                                <th className="px-4 py-3">Status</th>
                                                {canManage && <th className="px-4 py-3 text-right">Actions</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-200 bg-white text-sm dark:divide-slate-800 dark:bg-slate-900">
                                            {templates.length > 0 ? (
                                                templates.map((tpl) => (
                                                    <tr key={tpl.id} className="align-top">
                                                        <td className="px-4 py-4">
                                                            <div className="font-semibold text-slate-900 dark:text-slate-100">
                                                                {tpl.title}
                                                            </div>
                                                            <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                {tpl.description || 'No description'}
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 text-slate-600 dark:text-slate-300">
                                                            {tpl.group?.name || '-'}
                                                        </td>
                                                        <td className="px-4 py-4 text-slate-600 dark:text-slate-300">
                                                            {tpl.task_assignments && tpl.task_assignments.length > 0 ? (
                                                                tpl.task_assignments.map((asg) => (
                                                                    <div key={asg.id}>{asg.user?.name || '-'}</div>
                                                                ))
                                                            ) : (
                                                                <span className="text-slate-400">Unassigned</span>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-4 capitalize text-slate-600 dark:text-slate-300">
                                                            {tpl.frequency}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <span
                                                                className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                                                                    tpl.is_active
                                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                                        : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                                                }`}
                                                            >
                                                                {tpl.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </td>
                                                        {canManage && (
                                                            <td className="px-4 py-4">
                                                                <div className="flex justify-end gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleOpenEditTemplate(tpl)}
                                                                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                                                                    >
                                                                        Edit
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleDeleteTemplate(tpl)}
                                                                        className="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-slate-800"
                                                                    >
                                                                        Delete
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        )}
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td
                                                        colSpan={canManage ? 6 : 5}
                                                        className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400"
                                                    >
                                                        No task templates found.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Modals */}
                <GroupModal
                    isOpen={isGroupModalOpen}
                    onClose={() => setIsGroupModalOpen(false)}
                    editingGroup={editingGroup}
                    departments={departments}
                />

                <TemplateModal
                    isOpen={isTemplateModalOpen}
                    onClose={() => setIsTemplateModalOpen(false)}
                    editingTemplate={editingTemplate}
                    allGroups={allGroups}
                />
            </div>
        </KpiLayout>
    );
}
