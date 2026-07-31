import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';

export default function GroupModal({ isOpen, onClose, editingGroup = null, departments = [] }) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        groupCode: '',
        groupName: '',
        groupDescription: '',
        groupDepartmentId: '',
        groupIsActive: true,
        groupRuleType: 'pass_percentage',
        groupTargetPercentage: '',
        groupMaxFailCount: '',
        groupMaxCostAmount: '',
    });

    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            const handleKeyDown = (e) => {
                if (e.key === 'Escape') {
                    onClose();
                }
            };
            window.addEventListener('keydown', handleKeyDown);

            clearErrors();
            if (editingGroup) {
                setData({
                    groupCode: editingGroup.code || '',
                    groupName: editingGroup.name || '',
                    groupDescription: editingGroup.description || '',
                    groupDepartmentId: editingGroup.department_id ? String(editingGroup.department_id) : '',
                    groupIsActive: editingGroup.is_active ?? true,
                    groupRuleType: editingGroup.rule_type || 'pass_percentage',
                    groupTargetPercentage: editingGroup.target_percentage !== null ? String(editingGroup.target_percentage) : '',
                    groupMaxFailCount: editingGroup.max_fail_count !== null ? String(editingGroup.max_fail_count) : '',
                    groupMaxCostAmount: editingGroup.max_cost_amount !== null ? String(editingGroup.max_cost_amount) : '',
                });
            } else {
                reset();
            }

            return () => {
                document.body.style.overflow = 'unset';
                window.removeEventListener('keydown', handleKeyDown);
            };
        }
    }, [editingGroup, isOpen, onClose]);

    if (!isOpen) return null;

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        if (name === 'kpi.groups.store') return '/kpi/groups';
        if (name === 'kpi.groups.update') return `/kpi/groups/${id}`;
        return '/kpi/groups';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingGroup) {
            put(resolveUrl('kpi.groups.update', editingGroup.id), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
                preserveScroll: true,
            });
        } else {
            post(resolveUrl('kpi.groups.store'), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
                preserveScroll: true,
            });
        }
    };

    const handleBackdropClick = (e) => {
        if (e.target === e.currentTarget) {
            onClose();
        }
    };

    return (
        <div
            onClick={handleBackdropClick}
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6"
        >
            <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto overflow-x-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 no-scrollbar">
                <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            {editingGroup ? 'Edit KPI Group' : 'Create KPI Group'}
                        </h3>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {editingGroup ? 'Update the selected group parameters.' : 'Define a category and rule target.'}
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

                {errors.groupRuleType && (
                    <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        {errors.groupRuleType}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Group Code</label>
                            <input
                                type="text"
                                value={data.groupCode}
                                onChange={(e) => setData('groupCode', e.target.value)}
                                placeholder="e.g. GRP-OPS"
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            {errors.groupCode && <p className="mt-1 text-xs text-rose-600">{errors.groupCode}</p>}
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Department</label>
                            <select
                                value={data.groupDepartmentId}
                                onChange={(e) => setData('groupDepartmentId', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="">All Departments</option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>
                                        {dept.name}
                                    </option>
                                ))}
                            </select>
                            {errors.groupDepartmentId && <p className="mt-1 text-xs text-rose-600">{errors.groupDepartmentId}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Group Name <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            required
                            value={data.groupName}
                            onChange={(e) => setData('groupName', e.target.value)}
                            placeholder="e.g. Store Operations"
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        />
                        {errors.groupName && <p className="mt-1 text-xs text-rose-600">{errors.groupName}</p>}
                    </div>

                    <div>
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                        <textarea
                            rows="3"
                            value={data.groupDescription}
                            onChange={(e) => setData('groupDescription', e.target.value)}
                            placeholder="Optional description..."
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        ></textarea>
                        {errors.groupDescription && <p className="mt-1 text-xs text-rose-600">{errors.groupDescription}</p>}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Group Rule Type</label>
                            <select
                                value={data.groupRuleType}
                                onChange={(e) => setData('groupRuleType', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="pass_percentage">Pass Percentage</option>
                                <option value="fail_count">Fail Count</option>
                                <option value="spend_cost_lte">Spend Cost &lt;= Target</option>
                            </select>
                            {errors.groupRuleType && <p className="mt-1 text-xs text-rose-600">{errors.groupRuleType}</p>}
                        </div>
                        <div>
                            {data.groupRuleType === 'pass_percentage' && (
                                <>
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Target Percentage (%)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={data.groupTargetPercentage}
                                        onChange={(e) => setData('groupTargetPercentage', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {errors.groupTargetPercentage && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.groupTargetPercentage}</p>
                                    )}
                                </>
                            )}
                            {data.groupRuleType === 'fail_count' && (
                                <>
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Max Fail Count</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.groupMaxFailCount}
                                        onChange={(e) => setData('groupMaxFailCount', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {errors.groupMaxFailCount && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.groupMaxFailCount}</p>
                                    )}
                                </>
                            )}
                            {data.groupRuleType === 'spend_cost_lte' && (
                                <>
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Max Spend Cost Limit</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.groupMaxCostAmount}
                                        onChange={(e) => setData('groupMaxCostAmount', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {errors.groupMaxCostAmount && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.groupMaxCostAmount}</p>
                                    )}
                                </>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2 pt-2">
                        <input
                            type="checkbox"
                            id="groupIsActive"
                            checked={data.groupIsActive}
                            onChange={(e) => setData('groupIsActive', e.target.checked)}
                            className="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800"
                        />
                        <label htmlFor="groupIsActive" className="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Active Group
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                        >
                            {processing ? 'Saving...' : editingGroup ? 'Save Changes' : 'Create Group'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
