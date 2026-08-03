import React, { useState, useMemo, useEffect } from 'react';
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
    const [templateSearchTerm, setTemplateSearchTerm] = useState('');

    // Pagination per page 6
    const [currentPage, setCurrentPage] = useState(1);
    const PAGE_SIZE = 6;

    // Reset pagination when search or employee filter changes
    useEffect(() => {
        setCurrentPage(1);
    }, [templateSearchTerm, employeeFilter]);

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

    // Real-time search filter for group name and template name/title
    const filteredTemplates = useMemo(() => {
        let list = templates;

        if (templateSearchTerm.trim()) {
            const term = templateSearchTerm.toLowerCase();
            list = list.filter((tpl) => {
                const title = (tpl.title || '').toLowerCase();
                const groupName = (tpl.group?.name || '').toLowerCase();
                const desc = (tpl.description || '').toLowerCase();
                return title.includes(term) || groupName.includes(term) || desc.includes(term);
            });
        }

        return list;
    }, [templates, templateSearchTerm]);

    // Pagination calculations
    const totalPages = Math.max(1, Math.ceil(filteredTemplates.length / PAGE_SIZE));
    const paginatedTemplates = useMemo(() => {
        const start = (currentPage - 1) * PAGE_SIZE;
        return filteredTemplates.slice(start, start + PAGE_SIZE);
    }, [filteredTemplates, currentPage]);

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
                            {/* Search & Filter Controls */}
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                {/* Real-time Search by Group Name or Template Name */}
                                <div className="relative w-full sm:w-80">
                                    <input
                                        type="text"
                                        value={templateSearchTerm}
                                        onChange={(e) => setTemplateSearchTerm(e.target.value)}
                                        placeholder="🔍 Search template title or group..."
                                        className="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 pl-9 text-xs text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    />
                                    <svg className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    {templateSearchTerm && (
                                        <button
                                            type="button"
                                            onClick={() => setTemplateSearchTerm('')}
                                            className="absolute right-2.5 top-2 text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        >
                                            ✕
                                        </button>
                                    )}
                                </div>

                                {/* Assigned User Filter */}
                                <div className="w-full sm:w-64">
                                    <select
                                        value={employeeFilter}
                                        onChange={handleFilterChange}
                                        className="w-full rounded-xl border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    >
                                        <option value="">All Assigned Users</option>
                                        {templateEmployees.map((emp) => (
                                            <option key={emp.id} value={emp.id}>
                                                {emp.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {/* Templates Table */}
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
                                            {paginatedTemplates.length > 0 ? (
                                                paginatedTemplates.map((tpl) => (
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
                                                            {tpl.frequency === 'on_demand' ? (
                                                                <span className="inline-flex items-center rounded-lg bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                                    ⚡ On-Demand
                                                                </span>
                                                            ) : (
                                                                tpl.frequency
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleOpenEditTemplate(tpl)}
                                                                title="Click to edit status"
                                                                className={`rounded-full px-2.5 py-1 text-xs font-bold transition shadow-sm border ${
                                                                    tpl.is_active
                                                                        ? 'bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                                        : 'bg-rose-100 text-rose-800 border-rose-300 hover:bg-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800'
                                                                }`}
                                                            >
                                                                {tpl.is_active ? '● Active' : '○ Inactive'}
                                                            </button>
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
                                                        No task templates found matching search.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Pagination Footer (6 items per page) */}
                                {filteredTemplates.length > 0 && (
                                    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40">
                                        <div className="text-xs text-slate-500 dark:text-slate-400">
                                            Showing <span className="font-semibold text-slate-800 dark:text-white">{Math.min((currentPage - 1) * PAGE_SIZE + 1, filteredTemplates.length)}</span> to{' '}
                                            <span className="font-semibold text-slate-800 dark:text-white">{Math.min(currentPage * PAGE_SIZE, filteredTemplates.length)}</span> of{' '}
                                            <span className="font-semibold text-slate-800 dark:text-white">{filteredTemplates.length}</span> templates
                                        </div>

                                        {totalPages > 1 && (
                                            <div className="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    disabled={currentPage === 1}
                                                    onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                                                    className="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                                                >
                                                    ← Prev
                                                </button>

                                                {Array.from({ length: totalPages }, (_, idx) => idx + 1).map((pNum) => (
                                                    <button
                                                        key={pNum}
                                                        type="button"
                                                        onClick={() => setCurrentPage(pNum)}
                                                        className={`min-w-[32px] h-8 rounded-lg text-xs font-bold transition ${
                                                            currentPage === pNum
                                                                ? 'bg-indigo-600 text-white shadow-sm'
                                                                : 'text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800'
                                                        }`}
                                                    >
                                                        {pNum}
                                                    </button>
                                                ))}

                                                <button
                                                    type="button"
                                                    disabled={currentPage === totalPages}
                                                    onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                                                    className="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                                                >
                                                    Next →
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                )}
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
