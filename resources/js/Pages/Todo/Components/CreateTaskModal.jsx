import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import SearchableUserSelect from '../../../Components/SearchableUserSelect';

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

    // Departments list: prioritize IT & Admin departments
    const departmentOptions = itAdminDepartments && itAdminDepartments.length > 0 ? itAdminDepartments : departments;

    const selectedDeptObj = departmentOptions.find((d) => String(d.id) === String(selectedDepartmentId));

    // Keep internal isOpen state synced with prop
    useEffect(() => {
        setIsOpen(propIsOpen);
    }, [propIsOpen]);

    // Global custom event listener so any module or page can trigger this modal:
    // window.dispatchEvent(new CustomEvent('open-create-todo-modal', { detail: { task: '...', ... } }))
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
    const handleDueTimeChange = (e) => {
        const dueTimeId = e.target.value;
        setFormData('selectedDueTimeId', dueTimeId);

        const found = dueTimes.find((dt) => String(dt.id) === String(dueTimeId));
        if (found && found.duration) {
            const calculated = new Date(Date.now() + found.duration * 60 * 60 * 1000);
            const isoLocal = new Date(calculated.getTime() - calculated.getTimezoneOffset() * 60000)
                .toISOString()
                .slice(0, 16);
            setFormData((prev) => ({ ...prev, selectedDueTimeId: dueTimeId, dueDate: isoLocal }));
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
                        className="rounded-xl p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                        title="Press ESC to exit"
                    >
                        ✕
                    </button>
                </div>

                {/* Task Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Job Title / Due Time */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                Job Title / Category *
                            </label>
                            <select
                                value={formData.selectedDueTimeId}
                                onChange={handleDueTimeChange}
                                className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Select Job Title / Category</option>
                                {formattedDueTimes.map((dt) => (
                                    <option key={dt.id} value={dt.id}>
                                        {dt.name}
                                    </option>
                                ))}
                            </select>
                            {formErrors.selectedDueTimeId && (
                                <p className="mt-1 text-xs text-rose-500">{formErrors.selectedDueTimeId}</p>
                            )}
                        </div>

                        {/* Task Description */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                Task Description *
                            </label>
                            <input
                                type="text"
                                value={formData.task}
                                onChange={(e) => setFormData('task', e.target.value)}
                                placeholder="Enter task instructions..."
                                className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                required
                            />
                            {formErrors.task && <p className="mt-1 text-xs text-rose-500">{formErrors.task}</p>}
                        </div>
                    </div>

                    {/* STEP-BY-STEP DEPARTMENT & EMPLOYEE SELECTION */}
                    <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
                        {!selectedDepartmentId ? (
                            /* Step 1: Department Selection Buttons */
                            <div className="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20 space-y-3">
                                <label className="block text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300">
                                    Step 1 → Choose Department *
                                </label>
                                <div className="flex flex-wrap gap-2.5">
                                    {departmentOptions.map((d) => (
                                        <button
                                            key={d.id}
                                            type="button"
                                            onClick={() => {
                                                setSelectedDepartmentId(String(d.id));
                                                setFormData('assignedUserId', '');
                                            }}
                                            className="rounded-2xl px-4 py-2 text-xs font-bold transition flex items-center gap-2 border bg-white text-slate-700 border-slate-300 hover:border-indigo-500 hover:bg-indigo-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 active:scale-95"
                                        >
                                            <span>🏢 {d.name}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            /* Step 2: Replaces Step 1 with a clean, borderless text-sm header + employee search */
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <label className="text-sm font-bold text-slate-800 dark:text-slate-200 flex flex-wrap items-center gap-1.5">
                                        <span>Step 2 → Select Assignee Employee (တာဝန်ခံ) for</span>
                                        <span className="text-indigo-600 dark:text-indigo-400 font-extrabold underline">
                                            {selectedDeptObj?.name || 'Department'}
                                        </span>
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedDepartmentId('');
                                            setFormData('assignedUserId', '');
                                        }}
                                        className="text-xs font-semibold text-rose-500 hover:underline hover:text-rose-600 dark:text-rose-400"
                                    >
                                        Change Dept ✕
                                    </button>
                                </div>
                                <SearchableUserSelect
                                    users={users}
                                    selectedUserId={formData.assignedUserId}
                                    selectedDepartmentId={selectedDepartmentId}
                                    onSelectUser={(userId) => setFormData('assignedUserId', userId)}
                                    placeholder={`Search employee in ${selectedDeptObj?.name || 'Department'}...`}
                                />
                            </div>
                        )}
                    </div>

                    {/* Request By Branch */}
                    <div className="rounded-2xl border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                        <label className="block text-xs font-bold uppercase tracking-wider text-blue-800 dark:text-blue-300 mb-2">
                            Request By (Branch) *
                        </label>
                        <select
                            value={formData.requestedByBranchId}
                            onChange={(e) => setFormData('requestedByBranchId', e.target.value)}
                            className="w-full rounded-xl border border-blue-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm dark:border-blue-800 dark:bg-slate-800 dark:text-white"
                            required
                        >
                            <option value="">Select Branch</option>
                            {branches.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Calculated Due Date Preview */}
                    {formData.dueDate && (
                        <div className="text-xs text-slate-500 dark:text-slate-400">
                            Calculated Cutoff Due Date: <span className="font-semibold text-indigo-600 dark:text-indigo-400">{formData.dueDate.replace('T', ' ')}</span>
                        </div>
                    )}

                    {/* Footer Controls */}
                    <div className="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <span className="text-xs text-slate-400">Press <kbd className="rounded bg-slate-200 px-1 py-0.5 text-[10px] font-mono dark:bg-slate-700">ESC</kbd> to cancel</span>
                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={handleClose}
                                className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={taskProcessing}
                                className="rounded-xl bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
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
