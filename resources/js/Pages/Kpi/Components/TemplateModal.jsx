import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';

export default function TemplateModal({ isOpen, onClose, editingTemplate = null, allGroups = [] }) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        templateGroupId: '',
        templateTitle: '',
        templateDescription: '',
        templateGuideline: '',
        templateFrequency: 'daily',
        templateMonthlyRequiredCount: 1,
        templateCutoffTime: '',
        templateRequiresImages: false,
        templateRequiresTable: false,
        templateMinImages: 0,
        templateMaxImages: '',
        templateImageRemarkRequired: false,
        templateIsActive: true,
        templateRuleType: 'pass_percentage',
        templateTargetPercentage: '',
        templateMaxFailCount: '',
        templateMaxCostAmount: '',
        taskAssignmentId: '',
        deactivateAssignment: false,
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
            if (editingTemplate) {
                setData({
                    templateGroupId: editingTemplate.kpi_group_id ? String(editingTemplate.kpi_group_id) : '',
                    templateTitle: editingTemplate.title || '',
                    templateDescription: editingTemplate.description || '',
                    templateGuideline: editingTemplate.guideline || '',
                    templateFrequency: editingTemplate.frequency || 'daily',
                    templateMonthlyRequiredCount: editingTemplate.monthly_required_count || 1,
                    templateCutoffTime: editingTemplate.cutoff_time ? editingTemplate.cutoff_time.substring(0, 5) : '',
                    templateRequiresImages: editingTemplate.requires_images ?? false,
                    templateRequiresTable: editingTemplate.requires_table ?? false,
                    templateMinImages: editingTemplate.min_images ?? 0,
                    templateMaxImages: editingTemplate.max_images !== null ? String(editingTemplate.max_images) : '',
                    templateImageRemarkRequired: editingTemplate.image_remark_required ?? false,
                    templateIsActive: editingTemplate.is_active ?? true,
                    templateRuleType: editingTemplate.rule?.rule_type || 'pass_percentage',
                    templateTargetPercentage: editingTemplate.rule?.target_percentage !== null ? String(editingTemplate.rule.target_percentage) : '',
                    templateMaxFailCount: editingTemplate.rule?.max_fail_count !== null ? String(editingTemplate.rule.max_fail_count) : '',
                    templateMaxCostAmount: editingTemplate.rule?.max_cost_amount !== null ? String(editingTemplate.rule.max_cost_amount) : '',
                    taskAssignmentId: '',
                    deactivateAssignment: false,
                });
            } else {
                reset();
            }

            return () => {
                document.body.style.overflow = 'unset';
                window.removeEventListener('keydown', handleKeyDown);
            };
        }
    }, [editingTemplate, isOpen, onClose]);

    if (!isOpen) return null;

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        if (name === 'kpi.templates.store') return '/kpi/templates';
        if (name === 'kpi.templates.update') return `/kpi/templates/${id}`;
        return '/kpi/templates';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingTemplate) {
            put(resolveUrl('kpi.templates.update', editingTemplate.id), {
                onSuccess: () => {
                    reset();
                    onClose();
                },
                preserveScroll: true,
            });
        } else {
            post(resolveUrl('kpi.templates.store'), {
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
            <div className="max-h-[90vh] w-full max-w-4xl overflow-y-auto overflow-x-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 no-scrollbar">
                <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            {editingTemplate ? 'Edit Task Template' : 'Create Task Template'}
                        </h3>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {editingTemplate ? 'Update selected template parameters.' : 'Add a new task template for KPI assignments.'}
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
                    {/* Active Status Toggle Button */}
                    <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40 space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h4 className="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                    Template Status
                                </h4>
                                <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {data.templateIsActive
                                        ? 'Template is currently ACTIVE across the system.'
                                        : 'Template is INACTIVE (disabled).'}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setData('templateIsActive', !data.templateIsActive)}
                                className={`px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 border shadow-sm ${
                                    data.templateIsActive
                                        ? 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700'
                                        : 'bg-rose-600 text-white border-rose-600 hover:bg-rose-700'
                                }`}
                            >
                                <span>{data.templateIsActive ? '✓ Active' : '✕ Inactive'}</span>
                                <span className="text-[10px] opacity-80 underline">(Click to Toggle)</span>
                            </button>
                        </div>

                        {editingTemplate?.task_assignments?.length > 0 ? (
                            <div className="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/80">
                                <div className="text-xs font-semibold uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-3">
                                    Deactivate for specific employee
                                </div>
                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Assigned employee
                                        </label>
                                        <select
                                            value={data.taskAssignmentId}
                                            onChange={(e) => setData('taskAssignmentId', e.target.value)}
                                            className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <option value="">Select an assigned employee</option>
                                            {editingTemplate.task_assignments.map((assignment) => (
                                                <option key={assignment.id} value={assignment.id}>
                                                    {assignment.user?.name || 'Unknown user'}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            checked={data.deactivateAssignment}
                                            onChange={(e) => setData('deactivateAssignment', e.target.checked)}
                                            disabled={!data.taskAssignmentId}
                                            className="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-slate-600"
                                        />
                                        Deactivate this template assignment for the selected employee
                                    </label>

                                    <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                        This will unassign the selected employee from the task template without deactivating the template globally.
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-400">
                                No active template assignments are available to unassign.
                            </div>
                        )}
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                KPI Group <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={data.templateGroupId}
                                onChange={(e) => setData('templateGroupId', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="">Select Group</option>
                                {allGroups.map((group) => (
                                    <option key={group.id} value={group.id}>
                                        {group.name}
                                    </option>
                                ))}
                            </select>
                            {errors.templateGroupId && <p className="mt-1 text-xs text-rose-600">{errors.templateGroupId}</p>}
                        </div>

                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                Task Title <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                value={data.templateTitle}
                                onChange={(e) => setData('templateTitle', e.target.value)}
                                placeholder="e.g. Daily Store Opening Audit"
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            {errors.templateTitle && <p className="mt-1 text-xs text-rose-600">{errors.templateTitle}</p>}
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Frequency</label>
                            <select
                                value={data.templateFrequency}
                                onChange={(e) => setData('templateFrequency', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="on_demand">On-Demand (Todo Task Auto-Trigger Only)</option>
                            </select>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Monthly Required Target</label>
                            <input
                                type="number"
                                min="1"
                                max="31"
                                value={data.templateMonthlyRequiredCount}
                                onChange={(e) => setData('templateMonthlyRequiredCount', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Cutoff Time (HH:MM)</label>
                            <input
                                type="time"
                                value={data.templateCutoffTime}
                                onChange={(e) => setData('templateCutoffTime', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                        <textarea
                            rows="2"
                            value={data.templateDescription}
                            onChange={(e) => setData('templateDescription', e.target.value)}
                            placeholder="Brief task description..."
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Execution Guideline</label>
                        <textarea
                            rows="3"
                            value={data.templateGuideline}
                            onChange={(e) => setData('templateGuideline', e.target.value)}
                            placeholder="Step-by-step guideline for employees..."
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        ></textarea>
                    </div>

                    {/* Evidence & Submission Requirements */}
                    <div className="space-y-3 border-t border-slate-200 dark:border-slate-800 pt-4">
                        <h4 className="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                            Evidence Requirements
                        </h4>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    checked={data.templateRequiresImages}
                                    onChange={(e) => setData('templateRequiresImages', e.target.checked)}
                                    className="h-4 w-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                                Requires Images
                            </label>

                            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    checked={data.templateImageRemarkRequired}
                                    onChange={(e) => setData('templateImageRemarkRequired', e.target.checked)}
                                    className="h-4 w-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                                Image Remark Required
                            </label>

                            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    checked={data.templateRequiresTable}
                                    onChange={(e) => setData('templateRequiresTable', e.target.checked)}
                                    className="h-4 w-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                                Requires Table / Form
                            </label>
                        </div>

                        {data.templateRequiresImages && (
                            <div className="grid grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Min Images
                                    </label>
                                    <input
                                        type="number"
                                        min={0}
                                        value={data.templateMinImages}
                                        onChange={(e) => setData('templateMinImages', parseInt(e.target.value) || 0)}
                                        className="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Max Images
                                    </label>
                                    <input
                                        type="number"
                                        min={0}
                                        value={data.templateMaxImages}
                                        onChange={(e) => setData('templateMaxImages', e.target.value)}
                                        className="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        placeholder="No limit"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Rule Type</label>
                            <select
                                value={data.templateRuleType}
                                onChange={(e) => setData('templateRuleType', e.target.value)}
                                className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option value="pass_percentage">Pass Percentage</option>
                                <option value="fail_count">Fail Count</option>
                                <option value="spend_cost_lte">Spend Cost &lt;= Target</option>
                            </select>
                        </div>
                        <div>
                            {data.templateRuleType === 'pass_percentage' && (
                                <>
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Target Percentage (%)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={data.templateTargetPercentage}
                                        onChange={(e) => setData('templateTargetPercentage', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </>
                            )}
                            {data.templateRuleType === 'fail_count' && (
                                <>
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Max Fail Count</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.templateMaxFailCount}
                                        onChange={(e) => setData('templateMaxFailCount', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </>
                            )}
                            {data.templateRuleType === 'spend_cost_lte' && (
                                <>
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-200">Max Cost Limit</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.templateMaxCostAmount}
                                        onChange={(e) => setData('templateMaxCostAmount', e.target.value)}
                                        className="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                </>
                            )}
                        </div>
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
                            {processing ? 'Saving...' : editingTemplate ? 'Save Changes' : 'Create Template'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
