import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function TemplateEditModal({
    isOpen,
    template,
    assignment,
    kpiGroups = [],
    selectedMonth,
    onClose
}) {
    const [formData, setFormData] = useState({
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
        templateTargetPercentage: '80',
        templateMaxFailCount: '0',
        templateMaxCostAmount: '0',
        inactivateForMonth: false,
        deactivateAssignment: false,
        applyGroupScope: 'all', // 'all' | 'month_only'
    });

    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (template) {
            setFormData({
                templateGroupId: template.kpi_group_id || (template.group?.id ?? ''),
                templateTitle: template.title || '',
                templateDescription: template.description || '',
                templateGuideline: template.guideline || '',
                templateFrequency: template.frequency || 'daily',
                templateMonthlyRequiredCount: template.monthly_required_count || 1,
                templateCutoffTime: template.cutoff_time ? template.cutoff_time.substring(0, 5) : '',
                templateRequiresImages: Boolean(template.requires_images),
                templateRequiresTable: Boolean(template.requires_table),
                templateMinImages: template.min_images ?? 0,
                templateMaxImages: template.max_images ?? '',
                templateImageRemarkRequired: Boolean(template.image_remark_required),
                templateIsActive: template.is_active !== undefined ? Boolean(template.is_active) : true,
                templateRuleType: template.rule?.rule_type || 'pass_percentage',
                templateTargetPercentage: template.rule?.target_percentage !== null && template.rule?.target_percentage !== undefined ? String(template.rule.target_percentage) : '80',
                templateMaxFailCount: template.rule?.max_fail_count !== null && template.rule?.max_fail_count !== undefined ? String(template.rule.max_fail_count) : '0',
                templateMaxCostAmount: template.rule?.max_cost_amount !== null && template.rule?.max_cost_amount !== undefined ? String(template.rule.max_cost_amount) : '0',
                inactivateForMonth: Boolean(assignment?.ends_on && selectedMonth && new Date(assignment.ends_on) < new Date(selectedMonth + '-01')),
                deactivateAssignment: false,
                applyGroupScope: 'all',
            });
            setErrors({});
        }
    }, [template, assignment, selectedMonth, isOpen]);

    if (!isOpen || !template) return null;

    const formattedMonthName = selectedMonth ? new Date(selectedMonth + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'this month';

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            templateGroupId: formData.templateGroupId,
            templateTitle: formData.templateTitle,
            templateDescription: formData.templateDescription,
            templateGuideline: formData.templateGuideline,
            templateFrequency: formData.templateFrequency,
            templateMonthlyRequiredCount: formData.templateMonthlyRequiredCount,
            templateCutoffTime: formData.templateCutoffTime,
            templateRequiresImages: formData.templateRequiresImages,
            templateRequiresTable: formData.templateRequiresTable,
            templateMinImages: formData.templateMinImages,
            templateMaxImages: formData.templateMaxImages,
            templateImageRemarkRequired: formData.templateImageRemarkRequired,
            templateIsActive: formData.templateIsActive,
            templateRuleType: formData.templateRuleType,
            templateTargetPercentage: formData.templateTargetPercentage,
            templateMaxFailCount: formData.templateMaxFailCount,
            templateMaxCostAmount: formData.templateMaxCostAmount,
            taskAssignmentId: assignment?.id || null,
            deactivateAssignment: formData.deactivateAssignment,
            inactivateForMonth: formData.inactivateForMonth,
            inactivateMonth: selectedMonth || null,
            applyGroupScope: formData.applyGroupScope,
            targetMonth: selectedMonth || null,
        };

        router.put(`/kpi/templates/${template.id}`, payload, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setProcessing(false);
                onClose();
            },
            onError: (errs) => {
                setProcessing(false);
                setErrors(errs || {});
            }
        });
    };

    return (
        <div style={{ position: 'fixed', inset: 0, zIndex: 99999, outline: 'none' }} className="bg-slate-950/75 dark:bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4">
            <div style={{ position: 'fixed', inset: 0 }} onClick={onClose}></div>

            <div style={{ position: 'relative', zIndex: 100000 }} className="w-full max-w-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                {/* Modal Header */}
                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-900/90">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">
                                Edit Task Template
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                {template.title}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Form Body */}
                <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-6">
                    {/* Error Banner */}
                    {Object.keys(errors).length > 0 && (
                        <div className="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs font-semibold space-y-1">
                            {Object.values(errors).map((err, i) => (
                                <p key={i}>• {err}</p>
                            ))}
                        </div>
                    )}
                    {/* General Information */}
                    <div className="space-y-4">
                        <h4 className="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">General Information</h4>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    KPI Group <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={formData.templateGroupId}
                                    onChange={(e) => setFormData({ ...formData, templateGroupId: e.target.value })}
                                    className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                >
                                    <option value="">Select Group</option>
                                    {kpiGroups.map((g) => (
                                        <option key={g.id} value={g.id}>{g.name}</option>
                                    ))}
                                </select>
                                {errors.templateGroupId && <p className="text-xs text-rose-500 mt-1">{errors.templateGroupId}</p>}

                                {selectedMonth && (
                                    <div className="mt-2.5 space-y-1.5 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-2.5 dark:border-indigo-950 dark:bg-indigo-950/30">
                                        <span className="block text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">
                                            KPI Group Change Scope
                                        </span>
                                        <div className="flex flex-col sm:flex-row gap-3">
                                            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                <input
                                                    type="radio"
                                                    name="applyGroupScope"
                                                    value="all"
                                                    checked={formData.applyGroupScope === 'all'}
                                                    onChange={() => setFormData({ ...formData, applyGroupScope: 'all' })}
                                                    className="w-3.5 h-3.5 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                All Months (Global)
                                            </label>

                                            <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                <input
                                                    type="radio"
                                                    name="applyGroupScope"
                                                    value="month_only"
                                                    checked={formData.applyGroupScope === 'month_only'}
                                                    onChange={() => setFormData({ ...formData, applyGroupScope: 'month_only' })}
                                                    className="w-3.5 h-3.5 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span>
                                                    Only <strong className="text-indigo-600 dark:text-indigo-400">{formattedMonthName}</strong>
                                                </span>
                                            </label>
                                        </div>
                                        <p className="text-[10px] text-slate-500 dark:text-slate-400">
                                            {formData.applyGroupScope === 'month_only'
                                                ? `KPI group will change for ${formattedMonthName} only.`
                                                : 'KPI group will update globally for all months.'}
                                        </p>
                                    </div>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Task Title <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={formData.templateTitle}
                                    onChange={(e) => setFormData({ ...formData, templateTitle: e.target.value })}
                                    className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    placeholder="Enter task title"
                                />
                                {errors.templateTitle && <p className="text-xs text-rose-500 mt-1">{errors.templateTitle}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                            <textarea
                                value={formData.templateDescription}
                                onChange={(e) => setFormData({ ...formData, templateDescription: e.target.value })}
                                rows={2}
                                className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="Task description..."
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Guideline</label>
                            <textarea
                                value={formData.templateGuideline}
                                onChange={(e) => setFormData({ ...formData, templateGuideline: e.target.value })}
                                rows={2}
                                className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                placeholder="Step-by-step instructions or guidelines..."
                            />
                        </div>
                    </div>

                    {/* Frequency & Timing */}
                    <div className="space-y-4 border-t border-slate-200 dark:border-slate-800 pt-5">
                        <h4 className="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Frequency & Timing</h4>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Frequency</label>
                                <select
                                    value={formData.templateFrequency}
                                    onChange={(e) => setFormData({ ...formData, templateFrequency: e.target.value })}
                                    className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                >
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="on_demand">On-Demand (Todo Task Auto-Trigger Only)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Monthly Required Count</label>
                                <input
                                    type="number"
                                    min={1}
                                    max={31}
                                    value={formData.templateMonthlyRequiredCount}
                                    onChange={(e) => setFormData({ ...formData, templateMonthlyRequiredCount: parseInt(e.target.value) || 1 })}
                                    className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Cutoff Time</label>
                                <input
                                    type="time"
                                    value={formData.templateCutoffTime}
                                    onChange={(e) => setFormData({ ...formData, templateCutoffTime: e.target.value })}
                                    className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Evidence & Submission Requirements */}
                    <div className="space-y-4 border-t border-slate-200 dark:border-slate-800 pt-5">
                        <h4 className="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Evidence Requirements</h4>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    checked={formData.templateRequiresImages}
                                    onChange={(e) => setFormData({ ...formData, templateRequiresImages: e.target.checked })}
                                    className="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                                Requires Images
                            </label>

                            <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    checked={formData.templateImageRemarkRequired}
                                    onChange={(e) => setFormData({ ...formData, templateImageRemarkRequired: e.target.checked })}
                                    className="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                                Image Remark Required
                            </label>

                            <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input
                                    type="checkbox"
                                    checked={formData.templateRequiresTable}
                                    onChange={(e) => setFormData({ ...formData, templateRequiresTable: e.target.checked })}
                                    className="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                                Requires Table / Form
                            </label>
                        </div>

                        {formData.templateRequiresImages && (
                            <div className="grid grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Min Images</label>
                                    <input
                                        type="number"
                                        min={0}
                                        value={formData.templateMinImages}
                                        onChange={(e) => setFormData({ ...formData, templateMinImages: parseInt(e.target.value) || 0 })}
                                        className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Images</label>
                                    <input
                                        type="number"
                                        min={0}
                                        value={formData.templateMaxImages}
                                        onChange={(e) => setFormData({ ...formData, templateMaxImages: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        placeholder="No max limit"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Performance Evaluation Rule */}
                    <div className="space-y-4 border-t border-slate-200 dark:border-slate-800 pt-5">
                        <h4 className="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Performance Evaluation Rule</h4>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rule Type</label>
                                <select
                                    value={formData.templateRuleType}
                                    onChange={(e) => setFormData({ ...formData, templateRuleType: e.target.value })}
                                    className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                >
                                    <option value="pass_percentage">Pass Percentage (%)</option>
                                    <option value="fail_count">Max Fail Count</option>
                                    <option value="spend_cost_lte">Spend Cost Limit (&le;)</option>
                                </select>
                            </div>

                            {formData.templateRuleType === 'pass_percentage' && (
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Percentage (%)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        max={100}
                                        value={formData.templateTargetPercentage}
                                        onChange={(e) => setFormData({ ...formData, templateTargetPercentage: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    />
                                    {errors.templateTargetPercentage && <p className="text-xs text-rose-500 mt-1">{errors.templateTargetPercentage}</p>}
                                </div>
                            )}

                            {formData.templateRuleType === 'fail_count' && (
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Allowed Fail Count</label>
                                    <input
                                        type="number"
                                        min={0}
                                        value={formData.templateMaxFailCount}
                                        onChange={(e) => setFormData({ ...formData, templateMaxFailCount: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    />
                                    {errors.groupMaxFailCount && <p className="text-xs text-rose-500 mt-1">{errors.groupMaxFailCount}</p>}
                                </div>
                            )}

                            {formData.templateRuleType === 'spend_cost_lte' && (
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Cost Amount ($)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={formData.templateMaxCostAmount}
                                        onChange={(e) => setFormData({ ...formData, templateMaxCostAmount: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    />
                                    {errors.groupMaxCostAmount && <p className="text-xs text-rose-500 mt-1">{errors.groupMaxCostAmount}</p>}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Active Status & Month Specific Control */}
                    <div className="space-y-4 border-t border-slate-200 dark:border-slate-800 pt-5">
                        <h4 className="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Active Status & Calendar Control</h4>

                        <div className="p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-2xl space-y-3">
                            <label className="flex items-center justify-between cursor-pointer">
                                <div>
                                    <span className="text-xs font-bold text-slate-800 dark:text-white">Template Global Active Status</span>
                                    <p className="text-[11px] text-slate-500 dark:text-slate-400">Controls whether this task template is active across the system.</p>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={formData.templateIsActive}
                                    onChange={(e) => setFormData({ ...formData, templateIsActive: e.target.checked })}
                                    className="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                />
                            </label>

                            {assignment && (
                                <div className="border-t border-slate-200 dark:border-slate-700/60 pt-3">
                                    <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            checked={formData.deactivateAssignment}
                                            onChange={(e) => setFormData({ ...formData, deactivateAssignment: e.target.checked })}
                                            className="w-4 h-4 text-rose-600 rounded focus:ring-rose-500"
                                        />
                                        Deactivate this template assignment for the selected employee only
                                    </label>
                                    <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                        This will unassign the selected employee from the template without deactivating the template globally.
                                    </p>
                                </div>
                            )}

                            <div className="border-t border-slate-200 dark:border-slate-700/60 pt-3">
                                <label className="flex items-center justify-between cursor-pointer">
                                    <div>
                                        <span className="text-xs font-bold text-rose-600 dark:text-rose-400">Inactive for {formattedMonthName}</span>
                                        <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                            Deactivates this task assignment for {formattedMonthName} so it is skipped during audit evaluation.
                                        </p>
                                    </div>
                                    <input
                                        type="checkbox"
                                        checked={formData.inactivateForMonth}
                                        onChange={(e) => setFormData({ ...formData, inactivateForMonth: e.target.checked })}
                                        className="w-4 h-4 text-rose-600 rounded focus:ring-rose-500"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Footer Actions */}
                    <div className="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition disabled:opacity-50 cursor-pointer"
                        >
                            {processing ? 'Saving...' : 'Save Template Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
