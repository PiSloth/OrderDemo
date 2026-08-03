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
