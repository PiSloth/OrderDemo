import React, { useState, useMemo, useEffect, useRef } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';
import TodoLayout from '../../Layouts/TodoLayout';

function SearchableSelect({
    options = [],
    value,
    onChange,
    placeholder = 'Select option...',
    searchPlaceholder = 'Search...',
    labelFormatter = (opt) => opt.label || opt.name || opt.title,
    valueKey = 'id',
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef(null);

    const selectedOption = useMemo(() => {
        return options.find(o => String(o[valueKey]) === String(value));
    }, [options, value, valueKey]);

    const filteredOptions = useMemo(() => {
        if (!search) return options;
        const query = search.toLowerCase();
        return options.filter(o => {
            const labelText = labelFormatter(o);
            return labelText.toLowerCase().includes(query);
        });
    }, [options, search, labelFormatter]);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <div ref={containerRef} className="relative w-full">
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full flex items-center justify-between rounded-2xl border border-indigo-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-indigo-800 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 text-left transition"
            >
                <span className={selectedOption ? 'font-semibold text-slate-900 dark:text-white truncate' : 'text-slate-400 truncate'}>
                    {selectedOption ? labelFormatter(selectedOption) : placeholder}
                </span>
                <span className="ml-2 text-slate-400 text-[10px]">▼</span>
            </button>

            {/* Dropdown Menu with Integrated Search Input */}
            {isOpen && (
                <div className="absolute z-50 mt-1.5 w-full rounded-2xl border border-indigo-200 bg-white p-2 shadow-xl dark:border-indigo-800 dark:bg-slate-800 space-y-2 max-h-60 flex flex-col">
                    {/* Search Input embedded inside dropdown */}
                    <div className="relative px-1 pt-1">
                        <input
                            type="text"
                            autoFocus
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="w-full rounded-xl border border-indigo-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-indigo-700 dark:bg-slate-900 dark:text-white"
                        />
                    </div>

                    {/* Filtered Options List */}
                    <div className="overflow-y-auto max-h-40 space-y-0.5 pr-1">
                        <button
                            type="button"
                            onClick={() => {
                                onChange('');
                                setIsOpen(false);
                                setSearch('');
                            }}
                            className={`w-full text-left px-3 py-2 text-xs rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950/60 ${!value ? 'bg-indigo-100/60 dark:bg-indigo-900/60 font-bold text-indigo-700 dark:text-indigo-300' : 'text-slate-500'}`}
                        >
                            -- Clear Selection --
                        </button>
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map((opt) => {
                                const isSelected = String(opt[valueKey]) === String(value);
                                return (
                                    <button
                                        key={opt[valueKey]}
                                        type="button"
                                        onClick={() => {
                                            onChange(String(opt[valueKey]));
                                            setIsOpen(false);
                                            setSearch('');
                                        }}
                                        className={`w-full text-left px-3 py-2 text-xs rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950/60 transition-colors ${
                                            isSelected
                                                ? 'bg-indigo-600 text-white font-bold'
                                                : 'text-slate-700 dark:text-slate-200'
                                        }`}
                                    >
                                        {labelFormatter(opt)}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="p-3 text-center text-xs text-slate-400 italic">
                                No matching options found
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

function SearchableMultiSelect({
    options = [],
    values = [],
    onChange,
    placeholder = 'Select employees...',
    searchPlaceholder = 'Search...',
    labelFormatter = (opt) => opt.label || opt.name || opt.title,
    valueKey = 'id',
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef(null);

    const selectedOptions = useMemo(() => {
        const strValues = (values || []).map(v => String(v));
        return options.filter(o => strValues.includes(String(o[valueKey])));
    }, [options, values, valueKey]);

    const filteredOptions = useMemo(() => {
        if (!search) return options;
        const query = search.toLowerCase();
        return options.filter(o => {
            const labelText = labelFormatter(o);
            return labelText.toLowerCase().includes(query);
        });
    }, [options, search, labelFormatter]);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const toggleOption = (optId) => {
        const strId = String(optId);
        const currentStrValues = (values || []).map(v => String(v));
        let newValues;
        if (currentStrValues.includes(strId)) {
            newValues = currentStrValues.filter(v => v !== strId);
        } else {
            newValues = [...currentStrValues, strId];
        }
        onChange(newValues);
    };

    return (
        <div ref={containerRef} className="relative w-full">
            <div
                onClick={() => setIsOpen(!isOpen)}
                className="w-full flex items-center justify-between min-h-[42px] rounded-2xl border border-indigo-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm dark:border-indigo-800 dark:bg-slate-800 dark:text-white cursor-pointer"
            >
                <div className="flex flex-wrap gap-1.5 items-center max-w-[90%]">
                    {selectedOptions.length > 0 ? (
                        selectedOptions.map(opt => (
                            <span
                                key={opt[valueKey]}
                                className="inline-flex items-center gap-1 rounded-lg bg-indigo-100 px-2 py-0.5 text-[11px] font-extrabold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200"
                            >
                                👤 {opt.name || opt.label}
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        toggleOption(opt[valueKey]);
                                    }}
                                    className="hover:text-rose-600 ml-0.5 font-bold"
                                >
                                    ✕
                                </button>
                            </span>
                        ))
                    ) : (
                        <span className="text-slate-400">{placeholder}</span>
                    )}
                </div>
                <span className="ml-2 text-slate-400 text-[10px]">▼</span>
            </div>

            {isOpen && (
                <div className="absolute z-50 mt-1.5 w-full rounded-2xl border border-indigo-200 bg-white p-2 shadow-xl dark:border-indigo-800 dark:bg-slate-800 space-y-2 max-h-64 flex flex-col">
                    <div className="relative px-1 pt-1 flex items-center justify-between gap-2">
                        <input
                            type="text"
                            autoFocus
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="w-full rounded-xl border border-indigo-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-indigo-700 dark:bg-slate-900 dark:text-white"
                        />
                        {selectedOptions.length > 0 && (
                            <button
                                type="button"
                                onClick={() => onChange([])}
                                className="text-[11px] text-rose-500 hover:underline font-bold whitespace-nowrap px-1"
                            >
                                Clear All
                            </button>
                        )}
                    </div>

                    <div className="overflow-y-auto max-h-44 space-y-0.5 pr-1">
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map((opt) => {
                                const isChecked = (values || []).map(v => String(v)).includes(String(opt[valueKey]));
                                return (
                                    <label
                                        key={opt[valueKey]}
                                        className={`flex items-center gap-2.5 px-3 py-2 text-xs rounded-xl cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-950/60 transition-colors ${
                                            isChecked ? 'bg-indigo-50/80 font-bold text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-200' : 'text-slate-700 dark:text-slate-200'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={isChecked}
                                            onChange={() => toggleOption(opt[valueKey])}
                                            className="h-4 w-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="truncate">{labelFormatter(opt)}</span>
                                    </label>
                                );
                            })
                        ) : (
                            <div className="p-3 text-center text-xs text-slate-400 italic">
                                No matching employees found
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Config({
    categories = [],
    departments = [],
    priorities = [],
    statuses = [],
    locations = [],
    branches = [],
    dueTimes = [],
    kpiGroups = [],
    kpiTemplates = [],
    users = [],
}) {
    const { flash = {} } = usePage().props;

    // Active tab state: 'categories', 'dueTimes', 'statuses', 'priorities'
    const [activeTab, setActiveTab] = useState('categories');

    // Search terms
    const [categorySearch, setCategorySearch] = useState('');
    const [dueTimeSearch, setDueTimeSearch] = useState('');
    const [statusSearch, setStatusSearch] = useState('');
    const [prioritySearch, setPrioritySearch] = useState('');

    // Category Modal state
    const [isCategoryModalOpen, setIsCategoryModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);

    // Category Form
    const { data: categoryFormData, setData: setCategoryFormData, post: postCategory, patch: patchCategory, processing: categoryProcessing, reset: resetCategoryForm, errors: categoryErrors } = useForm({
        name: '',
        department_id: '',
        description: '',
    });

    // Due Time Modal state
    const [isDueTimeModalOpen, setIsDueTimeModalOpen] = useState(false);
    const [editingDueTime, setEditingDueTime] = useState(null);
    const [kpiGroupFilter, setKpiGroupFilter] = useState('');
    const [kpiTemplateFilter, setKpiTemplateFilter] = useState('');

    // Due Time Form
    const { data: dueTimeFormData, setData: setDueTimeFormData, post: postDueTime, patch: patchDueTime, processing: dueTimeProcessing, reset: resetDueTimeForm, errors: dueTimeErrors } = useForm({
        todo_category_id: '',
        todo_priority_id: '',
        duration: '24',
        description: '',
        generate_kpi_instance: false,
        kpi_group_id: '',
        kpi_task_template_id: '',
        kpi_assigned_user_id: '',
        kpi_assigned_user_ids: [],
    });

    // Status Modal state
    const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
    const [editingStatus, setEditingStatus] = useState(null);

    // Status Form
    const { data: statusFormData, setData: setStatusFormData, post: postStatus, patch: patchStatus, processing: statusProcessing, reset: resetStatusForm, errors: statusErrors } = useForm({
        status: '',
        description: '',
        color_code: '#3b82f6',
    });

    // Priority Modal state
    const [isPriorityModalOpen, setIsPriorityModalOpen] = useState(false);
    const [editingPriority, setEditingPriority] = useState(null);

    // Priority Form
    const { data: priorityFormData, setData: setPriorityFormData, post: postPriority, patch: patchPriority, processing: priorityProcessing, reset: resetPriorityForm, errors: priorityErrors } = useForm({
        level: '',
        rank: '',
        color_code: '#ef4444',
    });

    // ESC Key listener to close active modal
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                if (isCategoryModalOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeCategoryModal();
                } else if (isDueTimeModalOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDueTimeModal();
                } else if (isStatusModalOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeStatusModal();
                } else if (isPriorityModalOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    closePriorityModal();
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown, true);
        return () => window.removeEventListener('keydown', handleKeyDown, true);
    }, [isCategoryModalOpen, isDueTimeModalOpen, isStatusModalOpen, isPriorityModalOpen]);

    // Category Modal Handlers
    const openCategoryModal = (category = null) => {
        if (category) {
            setEditingCategory(category);
            setCategoryFormData({
                name: category.name || '',
                department_id: category.department_id ? String(category.department_id) : '',
                description: category.description || '',
            });
        } else {
            setEditingCategory(null);
            resetCategoryForm();
        }
        setIsCategoryModalOpen(true);
    };

    const closeCategoryModal = () => {
        setIsCategoryModalOpen(false);
        setEditingCategory(null);
        resetCategoryForm();
    };

    const handleCategorySubmit = (e) => {
        e.preventDefault();
        if (editingCategory) {
            patchCategory(`/todo/config/categories/${editingCategory.id}`, {
                preserveScroll: true,
                onSuccess: () => closeCategoryModal(),
            });
        } else {
            postCategory('/todo/config/categories', {
                preserveScroll: true,
                onSuccess: () => closeCategoryModal(),
            });
        }
    };

    const handleDeleteCategory = (categoryId) => {
        if (confirm('Are you sure you want to delete this category?')) {
            router.delete(`/todo/config/categories/${categoryId}`, {
                preserveScroll: true,
            });
        }
    };

    // Due Time Modal Handlers
    const openDueTimeModal = (dueTime = null) => {
        setKpiGroupFilter('');
        setKpiTemplateFilter('');
        if (dueTime) {
            setEditingDueTime(dueTime);
            setDueTimeFormData({
                todo_category_id: dueTime.todo_category_id ? String(dueTime.todo_category_id) : '',
                todo_priority_id: dueTime.todo_priority_id ? String(dueTime.todo_priority_id) : '',
                duration: dueTime.duration ? String(dueTime.duration) : '24',
                description: dueTime.description || '',
                generate_kpi_instance: Boolean(dueTime.generate_kpi_instance),
                kpi_group_id: dueTime.kpi_group_id ? String(dueTime.kpi_group_id) : '',
                kpi_task_template_id: dueTime.kpi_task_template_id ? String(dueTime.kpi_task_template_id) : '',
                kpi_assigned_user_id: dueTime.kpi_assigned_user_id ? String(dueTime.kpi_assigned_user_id) : '',
                kpi_assigned_user_ids: Array.isArray(dueTime.kpi_assigned_user_ids) ? dueTime.kpi_assigned_user_ids.map(String) : (dueTime.kpi_assigned_user_id ? [String(dueTime.kpi_assigned_user_id)] : []),
            });
        } else {
            setEditingDueTime(null);
            resetDueTimeForm();
        }
        setIsDueTimeModalOpen(true);
    };

    const closeDueTimeModal = () => {
        setIsDueTimeModalOpen(false);
        setEditingDueTime(null);
        resetDueTimeForm();
    };

    const handleDueTimeSubmit = (e) => {
        e.preventDefault();
        if (dueTimeFormData.generate_kpi_instance) {
            if (!dueTimeFormData.kpi_group_id) {
                toast.error('Target KPI Group is required when Auto-Generate KPI Instance is enabled.');
                return;
            }
            if (!dueTimeFormData.kpi_task_template_id) {
                toast.error('Evidence Template is required when Auto-Generate KPI Instance is enabled.');
                return;
            }
        }
        if (editingDueTime) {
            patchDueTime(`/todo/config/due-times/${editingDueTime.id}`, {
                preserveScroll: true,
                onSuccess: () => closeDueTimeModal(),
            });
        } else {
            postDueTime('/todo/config/due-times', {
                preserveScroll: true,
                onSuccess: () => closeDueTimeModal(),
            });
        }
    };

    const handleDeleteDueTime = (dueTimeId) => {
        if (confirm('Are you sure you want to delete this job title / due time setting?')) {
            router.delete(`/todo/config/due-times/${dueTimeId}`, {
                preserveScroll: true,
            });
        }
    };

    // Status Modal Handlers
    const openStatusModal = (statusObj = null) => {
        if (statusObj) {
            setEditingStatus(statusObj);
            let color = statusObj.color_code || '#3b82f6';
            if (!color.startsWith('#')) {
                const presets = {
                    blue: '#3b82f6',
                    emerald: '#10b981',
                    green: '#10b981',
                    amber: '#f59e0b',
                    yellow: '#f59e0b',
                    rose: '#ef4444',
                    red: '#ef4444',
                    purple: '#8b5cf6',
                };
                color = presets[color.toLowerCase()] || '#3b82f6';
            }
            setStatusFormData({
                status: statusObj.status || '',
                description: statusObj.description || '',
                color_code: color,
            });
        } else {
            setEditingStatus(null);
            setStatusFormData({
                status: '',
                description: '',
                color_code: '#3b82f6',
            });
        }
        setIsStatusModalOpen(true);
    };

    const closeStatusModal = () => {
        setIsStatusModalOpen(false);
        setEditingStatus(null);
        resetStatusForm();
    };

    const handleStatusSubmit = (e) => {
        e.preventDefault();
        if (editingStatus) {
            patchStatus(`/todo/config/statuses/${editingStatus.id}`, {
                preserveScroll: true,
                onSuccess: () => closeStatusModal(),
            });
        } else {
            postStatus('/todo/config/statuses', {
                preserveScroll: true,
                onSuccess: () => closeStatusModal(),
            });
        }
    };

    const handleDeleteStatus = (statusId) => {
        if (confirm('Are you sure you want to delete this status?')) {
            router.delete(`/todo/config/statuses/${statusId}`, {
                preserveScroll: true,
            });
        }
    };

    // Priority Modal Handlers
    const openPriorityModal = (priorityObj = null) => {
        if (priorityObj) {
            setEditingPriority(priorityObj);
            let color = priorityObj.color_code || '#ef4444';
            if (!color.startsWith('#')) {
                const presets = {
                    red: '#ef4444',
                    rose: '#ef4444',
                    amber: '#f59e0b',
                    yellow: '#f59e0b',
                    emerald: '#10b981',
                    green: '#10b981',
                    blue: '#3b82f6',
                    purple: '#8b5cf6',
                };
                color = presets[color.toLowerCase()] || '#ef4444';
            }
            setPriorityFormData({
                level: priorityObj.level || '',
                rank: priorityObj.rank !== undefined ? String(priorityObj.rank) : '',
                color_code: color,
            });
        } else {
            setEditingPriority(null);
            const nextRank = priorities.length > 0 ? Math.max(...priorities.map((p) => p.rank || 0)) + 1 : 1;
            setPriorityFormData({
                level: '',
                rank: String(nextRank),
                color_code: '#ef4444',
            });
        }
        setIsPriorityModalOpen(true);
    };

    const closePriorityModal = () => {
        setIsPriorityModalOpen(false);
        setEditingPriority(null);
        resetPriorityForm();
    };

    const handlePrioritySubmit = (e) => {
        e.preventDefault();
        if (editingPriority) {
            patchPriority(`/todo/config/priorities/${editingPriority.id}`, {
                preserveScroll: true,
                onSuccess: () => closePriorityModal(),
            });
        } else {
            postPriority('/todo/config/priorities', {
                preserveScroll: true,
                onSuccess: () => closePriorityModal(),
            });
        }
    };

    const handleDeletePriority = (priorityId) => {
        if (confirm('Are you sure you want to delete this priority?')) {
            router.delete(`/todo/config/priorities/${priorityId}`, {
                preserveScroll: true,
            });
        }
    };

    // Filter categories
    const filteredCategories = useMemo(() => {
        if (!categorySearch.trim()) return categories;
        const term = categorySearch.toLowerCase();
        return categories.filter(
            (c) =>
                (c.name || '').toLowerCase().includes(term) ||
                (c.description || '').toLowerCase().includes(term) ||
                (c.department?.name || '').toLowerCase().includes(term)
        );
    }, [categories, categorySearch]);

    // Filter due times
    const filteredDueTimes = useMemo(() => {
        if (!dueTimeSearch.trim()) return dueTimes;
        const term = dueTimeSearch.toLowerCase();
        return dueTimes.filter(
            (dt) =>
                (dt.category?.name || '').toLowerCase().includes(term) ||
                (dt.priority?.level || '').toLowerCase().includes(term) ||
                (dt.description || '').toLowerCase().includes(term)
        );
    }, [dueTimes, dueTimeSearch]);

    // Filter statuses
    const filteredStatuses = useMemo(() => {
        if (!statusSearch.trim()) return statuses;
        const term = statusSearch.toLowerCase();
        return statuses.filter(
            (s) =>
                (s.status || '').toLowerCase().includes(term) ||
                (s.description || '').toLowerCase().includes(term)
        );
    }, [statuses, statusSearch]);

    // Filter priorities
    const filteredPriorities = useMemo(() => {
        if (!prioritySearch.trim()) return priorities;
        const term = prioritySearch.toLowerCase();
        return priorities.filter(
            (p) =>
                (p.level || '').toLowerCase().includes(term) ||
                String(p.rank || '').includes(term)
        );
    }, [priorities, prioritySearch]);

    const renderStatusBadge = (statusName, colorCode = '') => {
        const isHex = colorCode && colorCode.startsWith('#');

        if (isHex) {
            return (
                <span
                    className="inline-block rounded-full border px-3 py-1 text-xs font-extrabold shadow-2xs"
                    style={{
                        backgroundColor: `${colorCode}20`,
                        color: colorCode,
                        borderColor: `${colorCode}60`,
                    }}
                >
                    {statusName}
                </span>
            );
        }

        const lower = (colorCode || '').toLowerCase();
        let badgeClass = 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950 dark:text-blue-300';
        if (lower.includes('emerald') || lower.includes('green') || lower.includes('success')) {
            badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300';
        } else if (lower.includes('rose') || lower.includes('red') || lower.includes('danger')) {
            badgeClass = 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300';
        } else if (lower.includes('amber') || lower.includes('yellow') || lower.includes('warn')) {
            badgeClass = 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300';
        } else if (lower.includes('purple') || lower.includes('indigo')) {
            badgeClass = 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950 dark:text-purple-300';
        }

        return (
            <span className={`inline-block rounded-full border px-3 py-1 text-xs font-extrabold ${badgeClass}`}>
                {statusName}
            </span>
        );
    };

    const renderPriorityBadge = (levelName, colorCode = '') => {
        const isHex = colorCode && colorCode.startsWith('#');

        if (isHex) {
            return (
                <span
                    className="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1 text-xs font-extrabold shadow-2xs"
                    style={{
                        backgroundColor: `${colorCode}20`,
                        color: colorCode,
                        borderColor: `${colorCode}60`,
                    }}
                >
                    🔥 {levelName}
                </span>
            );
        }

        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3.5 py-1 text-xs font-extrabold text-rose-800 border border-rose-300 dark:bg-rose-950 dark:text-rose-300">
                🔥 {levelName}
            </span>
        );
    };

    return (
        <TodoLayout title="Todo Config & Categories">
            <Head title="Todo Configurations" />

            <div className="space-y-6">
                {/* Header Banner */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div>
                        <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400">
                            Settings & Management
                        </span>
                        <h1 className="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            Todo Configuration & Categories
                        </h1>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Manage task categories, job titles, due times, statuses, and custom priority badge colors.
                        </p>
                    </div>
                </div>

                {/* Tab Navigation */}
                <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                    <button
                        type="button"
                        onClick={() => setActiveTab('categories')}
                        className={`rounded-2xl px-5 py-2.5 text-xs font-bold transition ${
                            activeTab === 'categories'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                        }`}
                    >
                        📁 Category Management ({categories.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('dueTimes')}
                        className={`rounded-2xl px-5 py-2.5 text-xs font-bold transition ${
                            activeTab === 'dueTimes'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                        }`}
                    >
                        ⏱️ Job Titles & Due Times ({dueTimes.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('statuses')}
                        className={`rounded-2xl px-5 py-2.5 text-xs font-bold transition ${
                            activeTab === 'statuses'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                        }`}
                    >
                        🏷️ Status Management ({statuses.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('priorities')}
                        className={`rounded-2xl px-5 py-2.5 text-xs font-bold transition ${
                            activeTab === 'priorities'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                        }`}
                    >
                        🔥 Priority Management ({priorities.length})
                    </button>
                </div>

                {/* CATEGORIES TAB */}
                {activeTab === 'categories' && (
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <input
                                type="text"
                                value={categorySearch}
                                onChange={(e) => setCategorySearch(e.target.value)}
                                placeholder="Search categories by name, department..."
                                className="w-full sm:w-80 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />

                            <button
                                type="button"
                                onClick={() => openCategoryModal(null)}
                                className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95"
                            >
                                + Create New Category
                            </button>
                        </div>

                        {/* Category Table */}
                        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50">
                                    <tr>
                                        <th className="px-5 py-4">ID</th>
                                        <th className="px-5 py-4">Category Name</th>
                                        <th className="px-5 py-4">Assigned Department</th>
                                        <th className="px-5 py-4">Description</th>
                                        <th className="px-5 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {filteredCategories.length > 0 ? (
                                        filteredCategories.map((cat) => (
                                            <tr key={cat.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td className="px-5 py-4 font-mono font-semibold text-slate-400">#{cat.id}</td>
                                                <td className="px-5 py-4 font-bold text-slate-900 dark:text-white text-sm">
                                                    {cat.name}
                                                </td>
                                                <td className="px-5 py-4">
                                                    {cat.department ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-extrabold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                            🏢 {cat.department.name}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                            Global / All Departments
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                                    {cat.description || '-'}
                                                </td>
                                                <td className="px-5 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => openCategoryModal(cat)}
                                                            className="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteCategory(cat.id)}
                                                            className="rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-400"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="p-8 text-center text-xs text-slate-400 italic">
                                                No categories found. Click "+ Create New Category" to add one.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* DUE TIMES TAB */}
                {activeTab === 'dueTimes' && (
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <input
                                type="text"
                                value={dueTimeSearch}
                                onChange={(e) => setDueTimeSearch(e.target.value)}
                                placeholder="Search job titles by category, priority..."
                                className="w-full sm:w-80 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />

                            <button
                                type="button"
                                onClick={() => openDueTimeModal(null)}
                                className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95"
                            >
                                + Add Job Title / Due Time
                            </button>
                        </div>

                        {/* Due Times Table */}
                        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50">
                                    <tr>
                                        <th className="px-5 py-4">Category</th>
                                        <th className="px-5 py-4">Priority Level</th>
                                        <th className="px-5 py-4">Duration (Hours)</th>
                                        <th className="px-5 py-4">KPI Integration</th>
                                        <th className="px-5 py-4">Description</th>
                                        <th className="px-5 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {filteredDueTimes.length > 0 ? (
                                        filteredDueTimes.map((dt) => (
                                            <tr key={dt.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td className="px-5 py-4 font-bold text-slate-900 dark:text-white">
                                                    {dt.category?.name || 'N/A'}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <span className="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                        {dt.priority?.level || 'Normal'}
                                                    </span>
                                                </td>
                                                <td className="px-5 py-4 font-bold text-indigo-600 dark:text-indigo-400">
                                                    {dt.duration} hours
                                                </td>
                                                <td className="px-5 py-4">
                                                    {dt.generate_kpi_instance ? (
                                                        <div className="space-y-1">
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                                ⚡ Auto KPI ({dt.kpi_group?.name || dt.kpi_group_id ? `Group #${dt.kpi_group_id}` : 'Enabled'})
                                                            </span>
                                                            {dt.kpi_assigned_user_ids && dt.kpi_assigned_user_ids.length > 0 ? (
                                                                <span className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                                                    👥 Target: {dt.kpi_assigned_user_ids.length} Designated Employees
                                                                </span>
                                                            ) : dt.kpi_assigned_user ? (
                                                                <span className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                                                    👤 Target: {dt.kpi_assigned_user.name}
                                                                </span>
                                                            ) : (
                                                                <span className="block text-[10px] text-slate-400 italic">
                                                                    (Task Assignee)
                                                                </span>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400 text-xs font-medium">Standard Task</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-4 text-slate-500 max-w-xs truncate">
                                                    {dt.description || '-'}
                                                </td>
                                                <td className="px-5 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => openDueTimeModal(dt)}
                                                            className="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteDueTime(dt.id)}
                                                            className="rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-400"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="p-8 text-center text-xs text-slate-400 italic">
                                                No due times configured yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* STATUSES TAB */}
                {activeTab === 'statuses' && (
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <input
                                type="text"
                                value={statusSearch}
                                onChange={(e) => setStatusSearch(e.target.value)}
                                placeholder="Search statuses by name, description..."
                                className="w-full sm:w-80 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />

                            <button
                                type="button"
                                onClick={() => openStatusModal(null)}
                                className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95"
                            >
                                + Create New Status
                            </button>
                        </div>

                        {/* Statuses Table */}
                        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50">
                                    <tr>
                                        <th className="px-5 py-4">ID</th>
                                        <th className="px-5 py-4">Status Name</th>
                                        <th className="px-5 py-4">Badge Preview</th>
                                        <th className="px-5 py-4">Description</th>
                                        <th className="px-5 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {filteredStatuses.length > 0 ? (
                                        filteredStatuses.map((st) => (
                                            <tr key={st.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td className="px-5 py-4 font-mono font-semibold text-slate-400">#{st.id}</td>
                                                <td className="px-5 py-4 font-bold text-slate-900 dark:text-white text-sm">
                                                    {st.status}
                                                </td>
                                                <td className="px-5 py-4">
                                                    {renderStatusBadge(st.status, st.color_code)}
                                                </td>
                                                <td className="px-5 py-4 text-slate-500 max-w-xs truncate">
                                                    {st.description || '-'}
                                                </td>
                                                <td className="px-5 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => openStatusModal(st)}
                                                            className="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteStatus(st.id)}
                                                            className="rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-400"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="p-8 text-center text-xs text-slate-400 italic">
                                                No statuses configured yet. Click "+ Create New Status" to add one.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* PRIORITIES TAB */}
                {activeTab === 'priorities' && (
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <input
                                type="text"
                                value={prioritySearch}
                                onChange={(e) => setPrioritySearch(e.target.value)}
                                placeholder="Search priority by level or rank..."
                                className="w-full sm:w-80 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />

                            <button
                                type="button"
                                onClick={() => openPriorityModal(null)}
                                className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95"
                            >
                                + Add New Priority Level
                            </button>
                        </div>

                        {/* Priorities Table */}
                        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50">
                                    <tr>
                                        <th className="px-5 py-4">ID</th>
                                        <th className="px-5 py-4">Priority Level</th>
                                        <th className="px-5 py-4">Badge Preview</th>
                                        <th className="px-5 py-4">Rank Order</th>
                                        <th className="px-5 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {filteredPriorities.length > 0 ? (
                                        filteredPriorities.map((pr) => (
                                            <tr key={pr.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td className="px-5 py-4 font-mono font-semibold text-slate-400">#{pr.id}</td>
                                                <td className="px-5 py-4 font-bold text-slate-900 dark:text-white text-sm">
                                                    {pr.level}
                                                </td>
                                                <td className="px-5 py-4">
                                                    {renderPriorityBadge(pr.level, pr.color_code)}
                                                </td>
                                                <td className="px-5 py-4 font-mono font-extrabold text-indigo-600 dark:text-indigo-400">
                                                    Rank {pr.rank}
                                                </td>
                                                <td className="px-5 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => openPriorityModal(pr)}
                                                            className="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeletePriority(pr.id)}
                                                            className="rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-400"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="p-8 text-center text-xs text-slate-400 italic">
                                                No priorities configured yet. Click "+ Add New Priority Level" to add one.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* CATEGORY CREATE / EDIT MODAL */}
                {isCategoryModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                        <div className="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                    {editingCategory ? 'Edit Category' : 'Create New Category'}
                                </h3>
                                <button
                                    type="button"
                                    onClick={closeCategoryModal}
                                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    ✕
                                </button>
                            </div>

                            <form onSubmit={handleCategorySubmit} className="space-y-5">
                                {/* Category Name */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Category Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={categoryFormData.name}
                                        onChange={(e) => setCategoryFormData('name', e.target.value)}
                                        placeholder="e.g. IT Support, Administrative Task..."
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                    {categoryErrors.name && (
                                        <p className="mt-1 text-xs text-rose-500">{categoryErrors.name}</p>
                                    )}
                                </div>

                                {/* Department Mapping */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300 mb-2">
                                        Assigned Department (Department Column)
                                    </label>
                                    <select
                                        value={categoryFormData.department_id}
                                        onChange={(e) => setCategoryFormData('department_id', e.target.value)}
                                        className="w-full rounded-2xl border border-indigo-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-indigo-800 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Global / All Departments --</option>
                                        {departments.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                🏢 {d.name}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="mt-1 text-[11px] text-slate-400">
                                        Selecting a department links this category directly to that department.
                                    </p>
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        value={categoryFormData.description}
                                        onChange={(e) => setCategoryFormData('description', e.target.value)}
                                        placeholder="Optional notes or guidelines for this category..."
                                        rows="3"
                                        className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>

                                <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                    <button
                                        type="button"
                                        onClick={closeCategoryModal}
                                        className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        Cancel (ESC)
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={categoryProcessing}
                                        className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {categoryProcessing
                                            ? 'Saving...'
                                            : editingCategory
                                            ? 'Update Category'
                                            : 'Create Category'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* DUE TIME CREATE / EDIT MODAL */}
                {isDueTimeModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                        <div className="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 space-y-6 max-h-[90vh] overflow-y-auto">
                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                    {editingDueTime ? 'Edit Job Title / Due Time' : 'Add Job Title / Due Time'}
                                </h3>
                                <button
                                    type="button"
                                    onClick={closeDueTimeModal}
                                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    ✕
                                </button>
                            </div>

                            <form onSubmit={handleDueTimeSubmit} className="space-y-5">
                                {/* Category */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Category *
                                    </label>
                                    <select
                                        value={dueTimeFormData.todo_category_id}
                                        onChange={(e) => setDueTimeFormData('todo_category_id', e.target.value)}
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">Select Category</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} {c.department ? `(${c.department.name})` : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Priority */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Priority Level *
                                    </label>
                                    <select
                                        value={dueTimeFormData.todo_priority_id}
                                        onChange={(e) => setDueTimeFormData('todo_priority_id', e.target.value)}
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">Select Priority</option>
                                        {priorities.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.level}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Duration */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Duration (Hours) *
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={dueTimeFormData.duration}
                                        onChange={(e) => setDueTimeFormData('duration', e.target.value)}
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                </div>

                                 {/* Description */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        value={dueTimeFormData.description}
                                        onChange={(e) => setDueTimeFormData('description', e.target.value)}
                                        rows="2"
                                        className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>

                                {/* KPI Integration Toggle */}
                                <div className="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20 space-y-4">
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={dueTimeFormData.generate_kpi_instance}
                                            onChange={(e) => setDueTimeFormData('generate_kpi_instance', e.target.checked)}
                                            className="h-4 w-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <div>
                                            <span className="text-xs font-black text-indigo-900 dark:text-indigo-200">
                                                ⚡ Auto-Generate KPI Instance on Task Request
                                            </span>
                                            <span className="block text-[11px] text-slate-500 dark:text-slate-400">
                                                Automatically creates an on-demand KPI instance for the assigned employee upon task creation.
                                            </span>
                                        </div>
                                    </label>

                                    {dueTimeFormData.generate_kpi_instance && (
                                        <div className="space-y-3 pt-2 border-t border-indigo-200/60 dark:border-indigo-800/60">
                                            {/* Searchable KPI Group Select */}
                                            <div>
                                                <label className="block text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200 mb-1.5">
                                                    Target KPI Group *
                                                </label>
                                                <SearchableSelect
                                                    options={kpiGroups}
                                                    value={dueTimeFormData.kpi_group_id}
                                                    onChange={(val) => setDueTimeFormData('kpi_group_id', val)}
                                                    placeholder="-- Select Target KPI Group --"
                                                    searchPlaceholder="🔍 Type to search KPI group..."
                                                    labelFormatter={(g) => `🎯 ${g.name} ${g.code ? `(${g.code})` : ''}`}
                                                />
                                            </div>

                                            {/* KPI Template Select */}
                                            <div>
                                                <label className="block text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200 mb-1.5">
                                                    Evidence Template *
                                                </label>
                                                <SearchableSelect
                                                    options={kpiTemplates.filter(t => !dueTimeFormData.kpi_group_id || String(t.kpi_group_id) === String(dueTimeFormData.kpi_group_id))}
                                                    value={dueTimeFormData.kpi_task_template_id}
                                                    onChange={(val) => setDueTimeFormData('kpi_task_template_id', val)}
                                                    placeholder="-- Default On-Demand Template --"
                                                    searchPlaceholder="🔍 Type to search evidence template..."
                                                    labelFormatter={(t) => `📋 ${t.title} (${t.requires_images ? 'Requires Images' : 'Standard Rules'})`}
                                                />
                                                <p className="mt-1 text-[10px] text-slate-400">
                                                    Defines evidence submission requirements and passing rules for the KPI instance.
                                                </p>
                                            </div>

                                            {/* Designated Target KPI Employees (Multi-Select) */}
                                            <div>
                                                <label className="block text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200 mb-1.5">
                                                    Target KPI Employees (Multi-Select Pre-assignment)
                                                </label>
                                                <SearchableMultiSelect
                                                    options={users}
                                                    values={dueTimeFormData.kpi_assigned_user_ids}
                                                    onChange={(newValues) => setDueTimeFormData('kpi_assigned_user_ids', newValues)}
                                                    placeholder="-- Fallback to Task Assignee Employee --"
                                                    searchPlaceholder="🔍 Search employee name or department..."
                                                    labelFormatter={(u) => `👤 ${u.name} ${u.department ? `(${u.department.name})` : ''}`}
                                                />
                                                <p className="mt-1 text-[10px] text-slate-400">
                                                    Select multiple employees who will automatically receive on-demand KPI instances when this task is created.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                    <button
                                        type="button"
                                        onClick={closeDueTimeModal}
                                        className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        Cancel (ESC)
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={dueTimeProcessing}
                                        className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {dueTimeProcessing ? 'Saving...' : 'Save Job Title'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* STATUS CREATE / EDIT MODAL */}
                {isStatusModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                        <div className="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                    {editingStatus ? 'Edit Status' : 'Create New Status'}
                                </h3>
                                <button
                                    type="button"
                                    onClick={closeStatusModal}
                                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    ✕
                                </button>
                            </div>

                            <form onSubmit={handleStatusSubmit} className="space-y-5">
                                {/* Status Name */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Status Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={statusFormData.status}
                                        onChange={(e) => setStatusFormData('status', e.target.value)}
                                        placeholder="e.g. New, In Progress, Completed..."
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                    {statusErrors.status && (
                                        <p className="mt-1 text-xs text-rose-500">{statusErrors.status}</p>
                                    )}
                                </div>

                                {/* Badge Color Picker Input Field */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Badge Color Theme Picker *
                                    </label>
                                    <div className="flex flex-wrap items-center gap-3">
                                        {/* Color Picker Box */}
                                        <input
                                            type="color"
                                            value={statusFormData.color_code && statusFormData.color_code.startsWith('#') ? statusFormData.color_code : '#3b82f6'}
                                            onChange={(e) => setStatusFormData('color_code', e.target.value)}
                                            className="h-10 w-14 cursor-pointer rounded-xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-800"
                                            title="Click to open color picker"
                                        />

                                        {/* Hex Code Input */}
                                        <input
                                            type="text"
                                            value={statusFormData.color_code}
                                            onChange={(e) => setStatusFormData('color_code', e.target.value)}
                                            placeholder="#3b82f6"
                                            className="flex-1 rounded-2xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-mono text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        />

                                        {/* Live Preview */}
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs text-slate-400 font-semibold">Preview:</span>
                                            {renderStatusBadge(statusFormData.status || 'Preview', statusFormData.color_code)}
                                        </div>
                                    </div>

                                    {/* Quick Palette */}
                                    <div className="mt-3 flex items-center gap-2">
                                        <span className="text-[11px] text-slate-400">Quick Palette:</span>
                                        {[
                                            { label: 'Blue', hex: '#3b82f6' },
                                            { label: 'Emerald', hex: '#10b981' },
                                            { label: 'Amber', hex: '#f59e0b' },
                                            { label: 'Rose', hex: '#ef4444' },
                                            { label: 'Purple', hex: '#8b5cf6' },
                                            { label: 'Indigo', hex: '#6366f1' },
                                            { label: 'Teal', hex: '#14b8a6' },
                                            { label: 'Dark Slate', hex: '#475569' },
                                        ].map((p) => (
                                            <button
                                                key={p.hex}
                                                type="button"
                                                onClick={() => setStatusFormData('color_code', p.hex)}
                                                className="h-6 w-6 rounded-full border border-white shadow-xs transition hover:scale-110 focus:ring-2 focus:ring-indigo-500"
                                                style={{ backgroundColor: p.hex }}
                                                title={p.label}
                                            />
                                        ))}
                                    </div>
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        value={statusFormData.description}
                                        onChange={(e) => setStatusFormData('description', e.target.value)}
                                        placeholder="Optional description of this task status..."
                                        rows="3"
                                        className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>

                                <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                    <button
                                        type="button"
                                        onClick={closeStatusModal}
                                        className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        Cancel (ESC)
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={statusProcessing}
                                        className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {statusProcessing
                                            ? 'Saving...'
                                            : editingStatus
                                            ? 'Update Status'
                                            : 'Create Status'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* PRIORITY CREATE / EDIT MODAL */}
                {isPriorityModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                        <div className="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                    {editingPriority ? 'Edit Priority Level' : 'Add New Priority Level'}
                                </h3>
                                <button
                                    type="button"
                                    onClick={closePriorityModal}
                                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    ✕
                                </button>
                            </div>

                            <form onSubmit={handlePrioritySubmit} className="space-y-5">
                                {/* Level Name */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Priority Level Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={priorityFormData.level}
                                        onChange={(e) => setPriorityFormData('level', e.target.value)}
                                        placeholder="e.g. Low, Normal, High, Urgent..."
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                    {priorityErrors.level && (
                                        <p className="mt-1 text-xs text-rose-500">{priorityErrors.level}</p>
                                    )}
                                </div>

                                {/* Priority Badge Color Picker */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Priority Badge Color Picker *
                                    </label>
                                    <div className="flex flex-wrap items-center gap-3">
                                        {/* Color Picker Box */}
                                        <input
                                            type="color"
                                            value={priorityFormData.color_code && priorityFormData.color_code.startsWith('#') ? priorityFormData.color_code : '#ef4444'}
                                            onChange={(e) => setPriorityFormData('color_code', e.target.value)}
                                            className="h-10 w-14 cursor-pointer rounded-xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-800"
                                            title="Click to open color picker"
                                        />

                                        {/* Hex Code Input */}
                                        <input
                                            type="text"
                                            value={priorityFormData.color_code}
                                            onChange={(e) => setPriorityFormData('color_code', e.target.value)}
                                            placeholder="#ef4444"
                                            className="flex-1 rounded-2xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-mono text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        />

                                        {/* Live Preview */}
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs text-slate-400 font-semibold">Preview:</span>
                                            {renderPriorityBadge(priorityFormData.level || 'Priority Preview', priorityFormData.color_code)}
                                        </div>
                                    </div>

                                    {/* Quick Palette */}
                                    <div className="mt-3 flex items-center gap-2">
                                        <span className="text-[11px] text-slate-400">Quick Palette:</span>
                                        {[
                                            { label: 'Rose / Urgent', hex: '#ef4444' },
                                            { label: 'Orange / High', hex: '#f97316' },
                                            { label: 'Amber / Medium', hex: '#f59e0b' },
                                            { label: 'Blue / Normal', hex: '#3b82f6' },
                                            { label: 'Emerald / Low', hex: '#10b981' },
                                            { label: 'Purple', hex: '#8b5cf6' },
                                            { label: 'Dark Slate', hex: '#475569' },
                                        ].map((p) => (
                                            <button
                                                key={p.hex}
                                                type="button"
                                                onClick={() => setPriorityFormData('color_code', p.hex)}
                                                className="h-6 w-6 rounded-full border border-white shadow-xs transition hover:scale-110 focus:ring-2 focus:ring-indigo-500"
                                                style={{ backgroundColor: p.hex }}
                                                title={p.label}
                                            />
                                        ))}
                                    </div>
                                </div>

                                {/* Rank Order */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Rank Order (Numerical Rank) *
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={priorityFormData.rank}
                                        onChange={(e) => setPriorityFormData('rank', e.target.value)}
                                        placeholder="e.g. 1, 2, 3..."
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                    <p className="mt-1 text-[11px] text-slate-400">
                                        Lower numbers indicate higher urgency / ranking order.
                                    </p>
                                    {priorityErrors.rank && (
                                        <p className="mt-1 text-xs text-rose-500">{priorityErrors.rank}</p>
                                    )}
                                </div>

                                <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                    <button
                                        type="button"
                                        onClick={closePriorityModal}
                                        className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        Cancel (ESC)
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={priorityProcessing}
                                        className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {priorityProcessing
                                            ? 'Saving...'
                                            : editingPriority
                                            ? 'Update Priority'
                                            : 'Create Priority'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </TodoLayout>
    );
}
