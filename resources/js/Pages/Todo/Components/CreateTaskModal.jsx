import React, { useState, useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import SearchableUserSelect from '../../../Components/SearchableUserSelect';
import SearchableDueTimeSelect from '../../../Components/SearchableDueTimeSelect';

export default function CreateTaskModal({
    isOpen: propIsOpen = false,
    onClose: propOnClose = null,
    formattedDueTimes = [],
    branches = [],
    departments = [],
    itAdminDepartments = [],
    users = [],
    userBranchId = null,
    dueTimes = [],
}) {
    const [isOpen, setIsOpen] = useState(propIsOpen);
    const [selectedDepartmentId, setSelectedDepartmentId] = useState('');

    const { data: formData, setData: setFormData, post: postTask, processing: taskProcessing, reset: resetTaskForm, errors: formErrors } = useForm({
        selectedDueTimeId: '',
        task: '',
        assignedUserId: '',
        requestedByBranchId: userBranchId ? String(userBranchId) : '',
        dueDate: '',
    });

    // Departments list: prioritize IT & Admin departments if available
    const departmentOptions = itAdminDepartments && itAdminDepartments.length > 0 ? itAdminDepartments : departments;

    const selectedDeptObj = departments.find((d) => String(d.id) === String(selectedDepartmentId)) ||
        departmentOptions.find((d) => String(d.id) === String(selectedDepartmentId));

    // Combine dueTimes & formattedDueTimes into normalized objects with department_id
    const allNormalizedDueTimes = useMemo(() => {
        if (dueTimes && dueTimes.length > 0) {
            return dueTimes.map((dt) => ({
                id: dt.id,
                name: dt.name || `${dt.category?.name || 'N/A'} (${dt.priority?.level || 'N/A'}) - ${dt.duration} Hours`,
                duration: dt.duration,
                department_id: dt.category?.department_id ?? dt.department_id ?? null,
                categoryName: dt.category?.name || 'N/A',
                priorityLevel: dt.priority?.level || 'N/A',
                description: dt.description || '',
                generate_kpi_instance: dt.generate_kpi_instance,
                raw: dt,
            }));
        }
        return formattedDueTimes.map((fdt) => ({
            id: fdt.id,
            name: fdt.name,
            duration: fdt.duration,
            department_id: fdt.department_id ?? null,
            generate_kpi_instance: fdt.generate_kpi_instance,
            raw: fdt,
        }));
    }, [dueTimes, formattedDueTimes]);

    // Filter due times strictly by selected department when a department is selected
    const filteredDueTimes = useMemo(() => {
        if (!selectedDepartmentId) return [];
        return allNormalizedDueTimes.filter((dt) => String(dt.department_id) === String(selectedDepartmentId));
    }, [allNormalizedDueTimes, selectedDepartmentId]);

    const selectedDueTimeObj = allNormalizedDueTimes.find((dt) => String(dt.id) === String(formData.selectedDueTimeId));

    // Reset selected due time if department changes and current due time is invalid for new department
    useEffect(() => {
        if (formData.selectedDueTimeId && selectedDepartmentId) {
            const isValid = filteredDueTimes.some((dt) => String(dt.id) === String(formData.selectedDueTimeId));
            if (!isValid) {
                setFormData((prev) => ({ ...prev, selectedDueTimeId: '', dueDate: '' }));
            }
        }
    }, [selectedDepartmentId, filteredDueTimes]);

    // Keep internal isOpen state synced with prop
    useEffect(() => {
        setIsOpen(propIsOpen);
    }, [propIsOpen]);

    // Global custom event listener so any module or page can trigger this modal:
    useEffect(() => {
        const handleGlobalTrigger = (e) => {
            const detail = e.detail || {};
            if (detail.task) setFormData('task', detail.task);
            if (detail.selectedDueTimeId) setFormData('selectedDueTimeId', String(detail.selectedDueTimeId));
            if (detail.assignedUserId) setFormData('assignedUserId', String(detail.assignedUserId));
            if (detail.requestedByBranchId) setFormData('requestedByBranchId', String(detail.requestedByBranchId));
            if (detail.dueDate) setFormData('dueDate', detail.dueDate);
            if (detail.departmentId) setSelectedDepartmentId(String(detail.departmentId));

            setIsOpen(true);
        };

        window.addEventListener('open-create-todo-modal', handleGlobalTrigger);
        return () => window.removeEventListener('open-create-todo-modal', handleGlobalTrigger);
    }, []);

    // ESC Key handling to exit modal
    useEffect(() => {
        if (!isOpen) return;

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                handleClose();
            }
        };

        window.addEventListener('keydown', handleKeyDown, true);
        return () => window.removeEventListener('keydown', handleKeyDown, true);
    }, [isOpen]);

    const handleClose = () => {
        setIsOpen(false);
        if (propOnClose) propOnClose();
    };

    // Calculate due date based on selected due time
    const handleDueTimeSelect = (dueTimeId) => {
        setFormData('selectedDueTimeId', dueTimeId);

        const found = allNormalizedDueTimes.find((dt) => String(dt.id) === String(dueTimeId));
        if (found && found.duration) {
            const calculated = new Date(Date.now() + found.duration * 60 * 60 * 1000);
            const isoLocal = new Date(calculated.getTime() - calculated.getTimezoneOffset() * 60000)
                .toISOString()
                .slice(0, 16);
            setFormData((prev) => ({ ...prev, selectedDueTimeId: dueTimeId, dueDate: isoLocal }));
        } else {
            setFormData((prev) => ({ ...prev, selectedDueTimeId: '', dueDate: '' }));
        }
    };

    // Submit task
    const handleSubmit = (e) => {
        e.preventDefault();
        postTask('/todo/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                resetTaskForm();
                setSelectedDepartmentId('');
                handleClose();
            },
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
            <div className="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-2xl dark:border-slate-800 dark:bg-slate-900 space-y-6 my-auto">
                {/* Modal Header */}
                <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            Task Request Modal
                        </span>
                        <h2 className="mt-1 text-xl font-extrabold text-slate-900 dark:text-white sm:text-2xl">
                            Create New Todo Task
                        </h2>
                    </div>

                    <button
                        type="button"
                        onClick={handleClose}
                        className="rounded-xl p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Press ESC to exit"
                    >
                        ✕
                    </button>
                </div>

                {/* Task Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* 1. TOP SECTION: DEPARTMENT & EMPLOYEE SELECTION */}
                    <div className="space-y-4 rounded-2xl border border-indigo-100 bg-slate-50/50 p-4 sm:p-5 dark:border-indigo-950/50 dark:bg-slate-800/40">
                        <div className="border-b border-slate-200 dark:border-slate-700/60 pb-3">
                            <h3 className="text-xs font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span>🏢</span> Department & Assignee Employee Selection
                            </h3>
                        </div>

                        {!selectedDepartmentId ? (
                            /* Step 1: Department Selection Buttons */
                            <div className="space-y-2.5">
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Step 1 → Select Department *
                                </label>
                                <div className="flex flex-wrap gap-2">
                                    {departmentOptions.map((d) => (
                                        <button
                                            key={d.id}
                                            type="button"
                                            onClick={() => {
                                                setSelectedDepartmentId(String(d.id));
                                                setFormData('assignedUserId', '');
                                                setFormData('selectedDueTimeId', '');
                                                setFormData('dueDate', '');
                                            }}
                                            className="rounded-2xl px-4 py-2 text-xs font-bold transition flex items-center gap-2 border bg-white text-slate-700 border-slate-300 hover:border-indigo-500 hover:bg-indigo-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 active:scale-95 shadow-sm"
                                        >
                                            <span>🏢 {d.name}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            /* Step 2: Employee Selection with Department Header */
                            <div className="space-y-3">
                                <div className="flex items-center justify-between bg-indigo-50/80 dark:bg-indigo-950/60 p-2.5 rounded-xl border border-indigo-200 dark:border-indigo-900">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm">🏢</span>
                                        <div>
                                            <span className="text-[10px] uppercase font-bold text-indigo-500 dark:text-indigo-400 block">Selected Department</span>
                                            <span className="text-sm font-extrabold text-indigo-950 dark:text-indigo-200">
                                                {selectedDeptObj?.name || 'Department'}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedDepartmentId('');
                                            setFormData('assignedUserId', '');
                                            setFormData('selectedDueTimeId', '');
                                            setFormData('dueDate', '');
                                        }}
                                        className="text-xs font-bold px-2.5 py-1 rounded-lg bg-white text-rose-600 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 border border-rose-200 dark:border-rose-900 transition shadow-sm"
                                    >
                                        Change Dept ✕
                                    </button>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Step 2 → Select Assignee Employee (တာဝန်ခံ) *
                                    </label>
                                    <SearchableUserSelect
                                        users={users}
                                        selectedUserId={formData.assignedUserId}
                                        selectedDepartmentId={selectedDepartmentId}
                                        onSelectUser={(userId) => setFormData('assignedUserId', userId)}
                                        placeholder={`Search employee in ${selectedDeptObj?.name || 'Department'}...`}
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* 2. MIDDLE SECTION: DUE TIME (DEPENDENT ON DEPT & SEARCHABLE) + BRANCH SELECTION */}
                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Searchable Job Title / Due Time Selection */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                Job Title / Due Time * {selectedDeptObj ? `(${selectedDeptObj.name})` : ''}
                            </label>
                            <SearchableDueTimeSelect
                                dueTimes={filteredDueTimes}
                                selectedDueTimeId={formData.selectedDueTimeId}
                                onSelectDueTime={handleDueTimeSelect}
                                disabled={!selectedDepartmentId}
                                disabledMessage="← Select department above first"
                                placeholder={
                                    selectedDepartmentId
                                        ? `Search Job Title for ${selectedDeptObj?.name}...`
                                        : 'Select department above first...'
                                }
                            />
                            {formErrors.selectedDueTimeId && (
                                <p className="mt-1 text-xs text-rose-500">{formErrors.selectedDueTimeId}</p>
                            )}
                        </div>

                        {/* Request By Branch */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                Request By (Branch) *
                            </label>
                            <select
                                value={formData.requestedByBranchId}
                                onChange={(e) => setFormData('requestedByBranchId', e.target.value)}
                                className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Select Branch</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name}
                                    </option>
                                ))}
                            </select>
                            {formErrors.requestedByBranchId && (
                                <p className="mt-1 text-xs text-rose-500">{formErrors.requestedByBranchId}</p>
                            )}
                        </div>
                    </div>

                    {/* 3. TASK DESCRIPTION: 1 ROW FULL WIDTH TEXTAREA */}
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                            Task Description *
                        </label>
                        <textarea
                            rows={4}
                            value={formData.task}
                            onChange={(e) => setFormData('task', e.target.value)}
                            placeholder="Enter detailed task instructions..."
                            className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            required
                        />
                        {formErrors.task && <p className="mt-1 text-xs text-rose-500">{formErrors.task}</p>}
                    </div>

                    {/* Calculated Due Date Preview */}
                    {formData.dueDate && (
                        <div className="text-xs text-slate-500 dark:text-slate-400 bg-indigo-50/50 dark:bg-indigo-950/30 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-between">
                            <span>Calculated Cutoff Due Date:</span>
                            <span className="font-mono font-extrabold text-indigo-600 dark:text-indigo-400 text-sm">
                                {formData.dueDate.replace('T', ' ')}
                            </span>
                        </div>
                    )}

                    {/* KPI Auto-Generation Notice */}
                    {selectedDueTimeObj?.generate_kpi_instance && (
                        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30 flex items-start gap-3">
                            <span className="text-lg">⚡</span>
                            <div>
                                <h4 className="text-xs font-bold text-amber-900 dark:text-amber-200">
                                    KPI Task Auto-Generation Active
                                </h4>
                                <p className="text-[11px] text-amber-800 dark:text-amber-300 mt-0.5">
                                    Creating this task will automatically generate an on-demand KPI Instance assigned to the selected <strong>Assignee Employee (တာဝန်ခံ)</strong>.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Footer Controls */}
                    <div className="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <span className="text-xs text-slate-400">
                            Press <kbd className="rounded bg-slate-200 px-1 py-0.5 text-[10px] font-mono dark:bg-slate-700">ESC</kbd> to cancel
                        </span>
                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={handleClose}
                                className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={taskProcessing}
                                className="rounded-xl bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition"
                            >
                                {taskProcessing ? 'Creating Task...' : 'Create Task'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}
