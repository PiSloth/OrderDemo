import React from 'react';
import { Link, router } from '@inertiajs/react';

export default function GroupTable({ groups, onEditGroup, canManage = false }) {
    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        return id ? `/kpi/groups/${id}` : '/kpi/groups';
    };

    const handleDelete = (group) => {
        if (confirm(`Delete KPI group "${group.name}"?`)) {
            router.delete(resolveUrl('kpi.groups.destroy', group.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead className="bg-slate-50 dark:bg-slate-800/80">
                        <tr className="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            <th className="px-4 py-3">Group</th>
                            <th className="px-4 py-3">Department</th>
                            <th className="px-4 py-3">Templates</th>
                            <th className="px-4 py-3">Rule</th>
                            <th className="px-4 py-3">Status</th>
                            {canManage && <th className="px-4 py-3 text-right">Actions</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200 bg-white text-sm dark:divide-slate-800 dark:bg-slate-900">
                        {groups.data && groups.data.length > 0 ? (
                            groups.data.map((group) => (
                                <tr key={group.id} className="align-top">
                                    <td className="px-4 py-4">
                                        <div className="font-semibold text-slate-900 dark:text-slate-100">{group.name}</div>
                                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{group.code || 'No code'}</div>
                                        <div className="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                            {group.description || 'No description'}
                                        </div>
                                    </td>
                                    <td className="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        {group.department?.name || 'All departments'}
                                    </td>
                                    <td className="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        {group.task_templates_count ?? 0}
                                    </td>
                                    <td className="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        <div className="capitalize">{group.rule_type?.replace(/_/g, ' ') || 'Not set'}</div>
                                        {group.rule_type === 'pass_percentage' && (
                                            <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Target {group.target_percentage !== null ? Number(group.target_percentage).toFixed(2) + '%' : '-'}
                                            </div>
                                        )}
                                        {group.rule_type === 'fail_count' && (
                                            <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Max {group.max_fail_count ?? '-'} fail(s)
                                            </div>
                                        )}
                                        {group.rule_type === 'spend_cost_lte' && (
                                            <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Max {group.max_cost_amount !== null ? Number(group.max_cost_amount).toFixed(2) : '-'}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-4">
                                        <span
                                            className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                                                group.is_active
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                            }`}
                                        >
                                            {group.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    {canManage && (
                                        <td className="px-4 py-4">
                                            <div className="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => onEditGroup(group)}
                                                    className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(group)}
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
                                    No KPI groups yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination controls with preserveScroll */}
            {groups.links && groups.links.length > 3 && (
                <div className="flex items-center justify-between border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                    <div className="text-xs text-slate-500 dark:text-slate-400">
                        Showing {groups.from || 0} to {groups.to || 0} of {groups.total || 0} groups
                    </div>
                    <div className="flex gap-1">
                        {groups.links.map((link, idx) => {
                            if (!link.url) {
                                return (
                                    <span
                                        key={idx}
                                        className="rounded-lg border border-slate-200 px-3 py-1 text-xs text-slate-400 dark:border-slate-800 dark:text-slate-600"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            }
                            return (
                                <Link
                                    key={idx}
                                    href={link.url}
                                    preserveScroll
                                    className={`rounded-lg border px-3 py-1 text-xs font-medium transition ${
                                        link.active
                                            ? 'border-slate-900 bg-slate-900 text-white dark:border-slate-100 dark:bg-slate-100 dark:text-slate-900'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
