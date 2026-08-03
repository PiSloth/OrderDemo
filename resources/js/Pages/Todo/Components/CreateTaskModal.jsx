import React, { useState, useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import SearchableUserSelect from '../../../Components/SearchableUserSelect';

export default function CreateTaskModal({
    isOpen: propIsOpen = false,
    onClose: propOnClose = null,
    formattedDueTimes = [],
    branches = [],
    departments = [],
    categories = [],
    itAdminDepartments = [],
    users = [],
    userBranchId = null,
    dueTimes = [],
}) {
    const [isOpen, setIsOpen] = useState(propIsOpen);
    const [selectedDepartmentId, setSelectedDepartmentId] = useState('');
    const [selectedCategoryId, setSelectedCategoryId] = useState('');

    const { data: formData, setData: setFormData, post: postTask, processing: taskProcessing, reset: resetTaskForm, errors: formErrors } = useForm({
        selectedDueTimeId: '',
        task: '',
        assignedUserId: '',
        requestedByBranchId: userBranchId ? String(userBranchId) : '',
        dueDate: '',
    });

    // Departments list: show all departments so users can create tasks for their own or other departments
    const departmentOptions = departments && departments.length > 0 ? departments : itAdminDepartments;

    const selectedDeptObj = departments.find((d) => String(d.id) === String(selectedDepartmentId)) ||
        departmentOptions.find((d) => String(d.id) === String(selectedDepartmentId));

    // Combine dueTimes & formattedDueTimes into normalized objects with department_id, categoryId, etc.
    const allNormalizedDueTimes = useMemo(() => {
        if (dueTimes && dueTimes.length > 0) {
            return dueTimes.map((dt) => ({
                id: dt.id,
                name: dt.name || `${dt.category?.name || 'N/A'} (${dt.priority?.level || 'N/A'}) - ${dt.duration} Hours`,
                duration: dt.duration,
                department_id: dt.category?.department_id ?? dt.department_id ?? null,
                categoryId: dt.category?.id ?? dt.todo_category_id ?? null,
                categoryName: dt.category?.name || 'N/A',
                categoryDescription: dt.category?.description || '',
                priorityLevel: dt.priority?.level || dt.priority?.name || 'N/A',
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
            categoryId: fdt.todo_category_id ?? fdt.category_id ?? null,
            categoryName: fdt.categoryName || fdt.category_name || 'N/A',
            categoryDescription: fdt.categoryDescription || '',
            priorityLevel: fdt.priorityLevel || 'N/A',
            description: fdt.description || '',
            generate_kpi_instance: fdt.generate_kpi_instance,
            raw: fdt,
        }));
    }, [dueTimes, formattedDueTimes]);

    // Filter categories strictly by selected department
    const departmentCategories = useMemo(() => {
        if (!selectedDepartmentId) return [];

        const catMap = new Map();

        // 1. From categories prop (if provided)
        if (categories && categories.length > 0) {
            categories.forEach((cat) => {
                if (String(cat.department_id) === String(selectedDepartmentId)) {
                    catMap.set(String(cat.id), {
                        id: cat.id,
                        name: cat.name,
                        description: cat.description || '',
                    });
                }
            });
        }

        // 2. From allNormalizedDueTimes
        allNormalizedDueTimes.forEach((dt) => {
            if (String(dt.department_id) === String(selectedDepartmentId) && dt.categoryId) {
                if (!catMap.has(String(dt.categoryId))) {
                    catMap.set(String(dt.categoryId), {
                        id: dt.categoryId,
                        name: dt.categoryName,
                        description: dt.categoryDescription || '',
                    });
                }
            }
        });

        return Array.from(catMap.values());
    }, [categories, allNormalizedDueTimes, selectedDepartmentId]);

    // Filter due times strictly by selected category
    const filteredDueTimes = useMemo(() => {
        if (!selectedCategoryId) return [];
        return allNormalizedDueTimes.filter((dt) => String(dt.categoryId) === String(selectedCategoryId));
    }, [allNormalizedDueTimes, selectedCategoryId]);

    const selectedDueTimeObj = allNormalizedDueTimes.find((dt) => String(dt.id) === String(formData.selectedDueTimeId));

    const selectedCategoryObj = departmentCategories.find((c) => String(c.id) === String(selectedCategoryId)) ||
        (selectedDueTimeObj ? { id: selectedDueTimeObj.categoryId, name: selectedDueTimeObj.categoryName } : null);

    // Auto sync selected category and department if selectedDueTimeId is populated externally
    useEffect(() => {
        if (formData.selectedDueTimeId) {
            const dt = allNormalizedDueTimes.find((d) => String(d.id) === String(formData.selectedDueTimeId));
            if (dt) {
                if (dt.department_id && String(selectedDepartmentId) !== String(dt.department_id)) {
                    setSelectedDepartmentId(String(dt.department_id));
                }
                if (dt.categoryId && String(selectedCategoryId) !== String(dt.categoryId)) {
                    setSelectedCategoryId(String(dt.categoryId));
                }
            }
        }
    }, [formData.selectedDueTimeId, allNormalizedDueTimes]);

    // Reset selected category & due time if department changes
    useEffect(() => {
        if (selectedDepartmentId && selectedCategoryId) {
            const isValidCat = departmentCategories.some((cat) => String(cat.id) === String(selectedCategoryId));
            if (!isValidCat) {
                setSelectedCategoryId('');
                setFormData((prev) => ({ ...prev, selectedDueTimeId: '', dueDate: '' }));
            }
        }
    }, [selectedDepartmentId, departmentCategories]);

    // Reset selected due time if category changes and current due time is invalid for new category
    useEffect(() => {
        if (formData.selectedDueTimeId && selectedCategoryId) {
            const isValidDueTime = filteredDueTimes.some((dt) => String(dt.id) === String(formData.selectedDueTimeId));
            if (!isValidDueTime) {
                setFormData((prev) => ({ ...prev, selectedDueTimeId: '', dueDate: '' }));
            }
        }
    }, [selectedCategoryId, filteredDueTimes]);

    // Keep internal isOpen state synced with prop
    useEffect(() => {
        setIsOpen(propIsOpen);
    }, [propIsOpen]);

    // Global custom event listener so any module or page can trigger this modal
    useEffect(() => {
        const handleGlobalTrigger = (e) => {
            const detail = e.detail || {};
            if (detail.task) setFormData('task', detail.task);
            if (detail.selectedDueTimeId) setFormData('selectedDueTimeId', String(detail.selectedDueTimeId));
            if (detail.assignedUserId) setFormData('assignedUserId', String(detail.assignedUserId));
            if (detail.requestedByBranchId) setFormData('requestedByBranchId', String(detail.requestedByBranchId));
            if (detail.dueDate) setFormData('dueDate', detail.dueDate);
            if (detail.departmentId) setSelectedDepartmentId(String(detail.departmentId));
            if (detail.categoryId) setSelectedCategoryId(String(detail.categoryId));

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

    // Helper to calculate working hours (Monday: 09:45 AM - 17:00 PM, Tue-Fri: 09:00 AM - 17:00 PM)
    const calculateWorkingHoursDueDate = (durationHours, startDate = new Date()) => {
        let current = new Date(startDate.getTime());
        let remainingMinutes = Math.max(0, Math.round(durationHours * 60));

        const WORK_END_HOUR = 17; // 17:00 PM

        const isWeekend = (date) => date.getDay() === 0 || date.getDay() === 6;
        const isMonday = (date) => date.getDay() === 1;

        const getWorkStart = (date) => ({
            hour: 9,
            minute: isMonday(date) ? 45 : 0,
        });

        const setWorkStart = (date) => {
            const start = getWorkStart(date);
            date.setHours(start.hour, start.minute, 0, 0);
        };

        const moveToNextWorkingDayStart = (date) => {
            date.setDate(date.getDate() + 1);
            while (isWeekend(date)) {
                date.setDate(date.getDate() + 1);
            }
            setWorkStart(date);
        };

        if (isWeekend(current)) {
            moveToNextWorkingDayStart(current);
        } else {
            const start = getWorkStart(current);
            const startTotalMinutes = start.hour * 60 + start.minute;
            const currentTotalMinutes = current.getHours() * 60 + current.getMinutes();
            const endTotalMinutes = WORK_END_HOUR * 60;

            if (currentTotalMinutes < startTotalMinutes) {
                setWorkStart(current);
            } else if (currentTotalMinutes >= endTotalMinutes) {
                moveToNextWorkingDayStart(current);
            }
        }

        while (remainingMinutes > 0) {
            const todayWorkEnd = new Date(current.getTime());
            todayWorkEnd.setHours(WORK_END_HOUR, 0, 0, 0);

            const availableMinutesToday = Math.max(0, (todayWorkEnd.getTime() - current.getTime()) / (1000 * 60));

            if (remainingMinutes <= availableMinutesToday) {
                current = new Date(current.getTime() + remainingMinutes * 60 * 1000);
                remainingMinutes = 0;
            } else {
                remainingMinutes -= availableMinutesToday;
                moveToNextWorkingDayStart(current);
            }
        }

        return current;
    };

    // Calculate due date based on selected due time duration (working hours)
    const handleDueTimeSelect = (dueTimeId) => {
        setFormData('selectedDueTimeId', dueTimeId);

        const found = allNormalizedDueTimes.find((dt) => String(dt.id) === String(dueTimeId));
        if (found && found.duration) {
            const calculated = calculateWorkingHoursDueDate(found.duration);
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
                setSelectedCategoryId('');
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
                    {/* STEP-BY-STEP SELECTION FLOW: DEPT -> EMPLOYEE -> CATEGORY -> DUE TIME DESCRIPTION */}
                    <div className="space-y-4 rounded-2xl border border-indigo-100 bg-slate-50/50 p-4 sm:p-5 dark:border-indigo-950/50 dark:bg-slate-800/40">
                        <div className="border-b border-slate-200 dark:border-slate-700/60 pb-3 flex items-center justify-between">
                            <h3 className="text-xs font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span>🏢</span> Task Request Configuration Flow
                            </h3>
                            <span className="text-[11px] font-semibold text-slate-400">Step-by-Step Selection</span>
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
                                                setSelectedCategoryId('');
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
                            <div className="space-y-4">
                                {/* Department Selected Header Card */}
                                <div className="flex items-center justify-between bg-indigo-50/80 dark:bg-indigo-950/60 p-2.5 sm:p-3 rounded-xl border border-indigo-200 dark:border-indigo-900 shadow-sm">
                                    <div className="flex items-center gap-2.5">
                                        <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white text-xs font-bold shadow-sm">
                                            🏢
                                        </span>
                                        <div>
                                            <span className="text-[10px] uppercase font-extrabold tracking-wider text-indigo-500 dark:text-indigo-400 block">
                                                Selected Department
                                            </span>
                                            <span className="text-sm font-black text-slate-900 dark:text-indigo-100">
                                                {selectedDeptObj?.name || 'Department'}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSelectedDepartmentId('');
                                            setFormData('assignedUserId', '');
                                            setSelectedCategoryId('');
                                            setFormData('selectedDueTimeId', '');
                                            setFormData('dueDate', '');
                                        }}
                                        className="text-xs font-bold px-2.5 py-1 rounded-lg bg-white text-rose-600 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 border border-rose-200 dark:border-rose-900 transition shadow-sm"
                                    >
                                        Change Dept ✕
                                    </button>
                                </div>

                                {/* Step 2: Employee Selection */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                        Step 2 → Select Assignee Employee (တာဝန်ခံ) *
                                    </label>
                                    <SearchableUserSelect
                                        users={users}
                                        selectedUserId={formData.assignedUserId}
                                        selectedDepartmentId={selectedDepartmentId}
                                        onSelectUser={(userId) => {
                                            setFormData('assignedUserId', userId);
                                            setSelectedCategoryId('');
                                            setFormData('selectedDueTimeId', '');
                                            setFormData('dueDate', '');
                                        }}
                                        placeholder={`Search employee in ${selectedDeptObj?.name || 'Department'}...`}
                                    />
                                </div>

                                {/* Step 3: Category Selection (Appears after Assignee Employee is selected!) */}
                                {formData.assignedUserId && (
                                    <div className="space-y-3 pt-3 border-t border-slate-200/80 dark:border-slate-700/60">
                                        {!selectedCategoryId ? (
                                            <div className="space-y-2.5">
                                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                    Step 3 → Select Category ({selectedDeptObj?.name}) *
                                                </label>
                                                {departmentCategories.length > 0 ? (
                                                    <div className="flex flex-wrap gap-2">
                                                        {departmentCategories.map((cat) => (
                                                            <button
                                                                key={cat.id}
                                                                type="button"
                                                                onClick={() => {
                                                                    setSelectedCategoryId(String(cat.id));
                                                                    setFormData('selectedDueTimeId', '');
                                                                    setFormData('dueDate', '');
                                                                }}
                                                                className="rounded-2xl px-4 py-2 text-xs font-bold transition flex items-center gap-2 border bg-white text-slate-700 border-slate-300 hover:border-indigo-500 hover:bg-indigo-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 active:scale-95 shadow-sm"
                                                            >
                                                                <span>📁 {cat.name}</span>
                                                            </button>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                                        ⚠️ No categories found for {selectedDeptObj?.name || 'this department'}.
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="space-y-3">
                                                {/* Category Selected Header Card */}
                                                <div className="flex items-center justify-between bg-violet-50/80 dark:bg-violet-950/60 p-2.5 sm:p-3 rounded-xl border border-violet-200 dark:border-violet-900 shadow-sm">
                                                    <div className="flex items-center gap-2.5">
                                                        <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-600 text-white text-xs font-bold shadow-sm">
                                                            📁
                                                        </span>
                                                        <div>
                                                            <span className="text-[10px] uppercase font-extrabold tracking-wider text-violet-600 dark:text-violet-400 block">
                                                                Selected Category
                                                            </span>
                                                            <span className="text-sm font-black text-slate-900 dark:text-violet-100">
                                                                {selectedCategoryObj?.name || 'Category'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setSelectedCategoryId('');
                                                            setFormData('selectedDueTimeId', '');
                                                            setFormData('dueDate', '');
                                                        }}
                                                        className="text-xs font-bold px-2.5 py-1 rounded-lg bg-white text-rose-600 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 border border-rose-200 dark:border-rose-900 transition shadow-sm"
                                                    >
                                                        Change Category ✕
                                                    </button>
                                                </div>

                                                {/* Step 4: Due Time Description Selection (Appears after Category is selected!) */}
                                                <div className="pt-2 border-t border-slate-200/80 dark:border-slate-700/60">
                                                    {!formData.selectedDueTimeId ? (
                                                        <div className="space-y-2.5">
                                                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                                                Step 4 → Select Due Time Description *
                                                            </label>
                                                            {filteredDueTimes.length > 0 ? (
                                                                <div className="flex flex-wrap gap-2">
                                                                    {filteredDueTimes.map((dt) => (
                                                                        <button
                                                                            key={dt.id}
                                                                            type="button"
                                                                            onClick={() => handleDueTimeSelect(dt.id)}
                                                                            className="rounded-2xl px-4 py-2 text-xs font-bold transition flex items-center gap-2 border bg-white text-slate-700 border-slate-300 hover:border-indigo-500 hover:bg-indigo-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 active:scale-95 shadow-sm text-left"
                                                                        >
                                                                            <span className="inline-flex items-center gap-1 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 text-[11px] font-mono font-bold">
                                                                                ⏱️ {dt.duration ? `${dt.duration}h` : 'Due Time'}
                                                                            </span>
                                                                            <span className="font-semibold text-slate-800 dark:text-white">
                                                                                {dt.description ? dt.description : dt.name}
                                                                            </span>
                                                                            {dt.priorityLevel && dt.priorityLevel !== 'N/A' && (
                                                                                <span className="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">
                                                                                    ({dt.priorityLevel})
                                                                                </span>
                                                                            )}
                                                                            {dt.generate_kpi_instance && <span title="KPI Auto-Generation Active">⚡</span>}
                                                                        </button>
                                                                    ))}
                                                                </div>
                                                            ) : (
                                                                <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                                                    ⚠️ No due time options configured for {selectedCategoryObj?.name || 'this category'}.
                                                                </div>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <div className="flex items-center justify-between bg-emerald-50/80 dark:bg-emerald-950/60 p-2.5 sm:p-3 rounded-xl border border-emerald-200 dark:border-emerald-900 shadow-sm">
                                                            <div className="flex items-center gap-2.5">
                                                                <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white text-xs font-bold shadow-sm">
                                                                    ⏱️
                                                                </span>
                                                                <div>
                                                                    <span className="text-[10px] uppercase font-extrabold tracking-wider text-emerald-600 dark:text-emerald-400 block">
                                                                        Selected Due Time Description
                                                                    </span>
                                                                    <div className="flex items-center gap-2">
                                                                        <span className="text-sm font-black text-slate-900 dark:text-emerald-100">
                                                                            {selectedDueTimeObj?.description || selectedDueTimeObj?.name}
                                                                        </span>
                                                                        <span className="inline-flex items-center gap-1 rounded-md bg-emerald-200/80 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 px-2 py-0.5 text-[11px] font-mono font-bold">
                                                                            {selectedDueTimeObj?.duration}h
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    setFormData('selectedDueTimeId', '');
                                                                    setFormData('dueDate', '');
                                                                }}
                                                                className="text-xs font-bold px-2.5 py-1 rounded-lg bg-white text-rose-600 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 border border-rose-200 dark:border-rose-900 transition shadow-sm"
                                                            >
                                                                Change Due Time ✕
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* REQUEST BY BRANCH */}
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

                    {/* TASK DESCRIPTION TEXTAREA */}
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
                            <div className="flex items-center gap-1.5">
                                <span>⏱️</span>
                                <span className="font-semibold">Calculated Cutoff Due Date</span>
                                <span className="text-[10px] font-medium text-indigo-500 dark:text-indigo-400 bg-indigo-100/80 dark:bg-indigo-900/60 px-1.5 py-0.5 rounded">
                                    Mon: 09:45-17:00, Tue-Fri: 09:00-17:00 Working Hours
                                </span>
                            </div>
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

                    {/* Validation Errors Alert */}
                    {formErrors.selectedDueTimeId && (
                        <div className="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-400">
                            ⚠️ Please select a due time description before submitting.
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
