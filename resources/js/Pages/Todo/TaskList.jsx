import React, { useState, useMemo, useEffect } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import TodoLayout from '../../Layouts/TodoLayout';
import CreateTaskModal from './Components/CreateTaskModal';
import TaskDetailModal from './Components/TaskDetailModal';

export default function TaskList({
    todoLists = [],
    archivedTasks = [],
    dueTimes = [],
    formattedDueTimes = [],
    statuses = [],
    branches = [],
    departments = [],
    categories = [],
    itAdminDepartments = [],
    users = [],
    calendarTasks = {},
    monthsWithTasks = [],
    userBranchId = null,
    userDepartmentId = null,
    filters = {},
}) {
    const { flash = {} } = usePage().props;

    // View state
    const [viewMode, setViewMode] = useState(filters.viewMode || 'calendar'); // 'calendar' or 'list'
    const [viewStyle, setViewStyle] = useState(filters.viewStyle || 'card'); // 'card' or 'table'
    const [activeTab, setActiveTab] = useState('active'); // 'active' or 'archived'
    const [isFormCollapsed, setIsFormCollapsed] = useState(true);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

    // Auto open Create Task modal if URL contains ?createTask=1
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('createTask') === '1' || params.get('openModal') === '1') {
            setIsCreateModalOpen(true);
        }
    }, []);

    // Filters state
    const [filterBranchId, setFilterBranchId] = useState(filters.filterBranchId || '');
    const [filterDepartmentId, setFilterDepartmentId] = useState(filters.filterDepartmentId || '');
    const [selectedStatusIds, setSelectedStatusIds] = useState(filters.selectedStatusIds || []);
    const [sortBy, setSortBy] = useState(filters.sortBy || 'due_date');
    const [selectedMonth, setSelectedMonth] = useState(filters.selectedMonth || new Date().toISOString().slice(0, 7));
    const [searchTerm, setSearchTerm] = useState('');

    // Modals state
    const [dayModalData, setDayModalData] = useState(null); // { date, categoryId, categoryName, tasks }
    const [commentsTask, setCommentsTask] = useState(null); // task object for comments drawer
    const [selectedTaskDetail, setSelectedTaskDetail] = useState(null); // task object for detailed view modal
    const [commentText, setCommentText] = useState('');
    const [copiedTaskId, setCopiedTaskId] = useState(null);

    // Form state for creating task
    const { data: formData, setData: setFormData, post: postTask, processing: taskProcessing, reset: resetTaskForm, errors: formErrors } = useForm({
        selectedDueTimeId: '',
        task: '',
        assignedUserId: '',
        requestedByBranchId: userBranchId ? String(userBranchId) : '',
        dueDate: '',
    });

    // ESC key listener to exit modals (Task Detail view takes top priority)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                if (selectedTaskDetail) {
                    e.preventDefault();
                    e.stopPropagation();
                    setSelectedTaskDetail(null);
                } else if (commentsTask) {
                    e.preventDefault();
                    e.stopPropagation();
                    setCommentsTask(null);
                } else if (dayModalData) {
                    e.preventDefault();
                    e.stopPropagation();
                    setDayModalData(null);
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown, true);
        return () => window.removeEventListener('keydown', handleKeyDown, true);
    }, [selectedTaskDetail, commentsTask, dayModalData]);

    // Sync open detail modal and comments drawer with fresh props data
    useEffect(() => {
        const allTasks = [...todoLists, ...archivedTasks];

        if (selectedTaskDetail) {
            const fresh = allTasks.find((t) => t.id === selectedTaskDetail.id);
            if (fresh) {
                setSelectedTaskDetail(fresh);
            }
        }

        if (commentsTask) {
            const fresh = allTasks.find((t) => t.id === commentsTask.id);
            if (fresh) {
                setCommentsTask(fresh);
            }
        }

        if (typeof window !== 'undefined' && !selectedTaskDetail && !commentsTask) {
            const urlParams = new URLSearchParams(window.location.search);
            const taskIdParam = urlParams.get('task_id');
            if (taskIdParam) {
                const found = allTasks.find((t) => String(t.id) === String(taskIdParam));
                if (found) {
                    setSelectedTaskDetail(found);
                }
            }
        }
    }, [todoLists, archivedTasks]);

    // Realtime background sync interval for live updates across calendar, list, and detail view
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['todoLists', 'archivedTasks'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 4000);

        return () => clearInterval(interval);
    }, []);

    // Helper: Refresh page query with current filter state
    const applyFilters = (overrides = {}) => {
        const query = {
            filterBranchId: overrides.filterBranchId !== undefined ? overrides.filterBranchId : filterBranchId,
            filterDepartmentId: overrides.filterDepartmentId !== undefined ? overrides.filterDepartmentId : filterDepartmentId,
            selectedStatusIds: (overrides.selectedStatusIds !== undefined ? overrides.selectedStatusIds : selectedStatusIds).join(','),
            sortBy: overrides.sortBy !== undefined ? overrides.sortBy : sortBy,
            selectedMonth: overrides.selectedMonth !== undefined ? overrides.selectedMonth : selectedMonth,
            viewMode: overrides.viewMode !== undefined ? overrides.viewMode : viewMode,
            viewStyle: overrides.viewStyle !== undefined ? overrides.viewStyle : viewStyle,
        };

        router.get('/todo/list', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const handleBranchFilterChange = (e) => {
        const val = e.target.value;
        setFilterBranchId(val);
        applyFilters({ filterBranchId: val });
    };

    const handleDepartmentFilterChange = (e) => {
        const val = e.target.value;
        setFilterDepartmentId(val);
        applyFilters({ filterDepartmentId: val });
    };

    const handleSortChange = (e) => {
        const val = e.target.value;
        setSortBy(val);
        applyFilters({ sortBy: val });
    };

    const handleMonthChange = (e) => {
        const val = e.target.value;
        setSelectedMonth(val);
        applyFilters({ selectedMonth: val });
    };

    const handleStatusToggle = (statusId) => {
        const numId = Number(statusId);
        let next;
        if (selectedStatusIds.includes(numId)) {
            next = selectedStatusIds.filter((id) => id !== numId);
        } else {
            next = [...selectedStatusIds, numId];
        }
        setSelectedStatusIds(next);
        applyFilters({ selectedStatusIds: next });
    };

    const handleClearFilters = () => {
        setFilterBranchId('');
        setFilterDepartmentId('');
        setSelectedStatusIds([]);
        setSortBy('due_date');
        setSearchTerm('');
        router.get('/todo/list', {}, { preserveState: true, preserveScroll: true, replace: true });
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

    // Submit new task
    const handleCreateTaskSubmit = (e) => {
        e.preventDefault();
        postTask('/todo/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                resetTaskForm();
                setIsFormCollapsed(true);
            },
        });
    };

    // Actions on task
    const handleCloseTask = (taskId) => {
        router.patch(`/todo/tasks/${taskId}/close`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedTaskDetail && selectedTaskDetail.id === taskId) {
                    setSelectedTaskDetail(null);
                }
            },
        });
    };

    const handleArchiveTask = (taskId) => {
        if (confirm('Are you sure you want to archive this task?')) {
            router.delete(`/todo/tasks/${taskId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedTaskDetail && selectedTaskDetail.id === taskId) {
                        setSelectedTaskDetail(null);
                    }
                },
            });
        }
    };

    const handleRestoreTask = (taskId) => {
        router.patch(`/todo/tasks/${taskId}/restore`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedTaskDetail && selectedTaskDetail.id === taskId) {
                    setSelectedTaskDetail(null);
                }
            },
        });
    };

    const handleCopyLink = (taskId) => {
        const url = `${window.location.origin}/todo/list?task_id=${taskId}`;
        navigator.clipboard.writeText(url);
        setCopiedTaskId(taskId);
        setTimeout(() => setCopiedTaskId(null), 2500);
    };

    // Handle Comment Submit
    const handleAddComment = (taskId, text) => {
        if (!text.trim()) return;

        router.post(
            `/todo/tasks/${taskId}/comments`,
            { comment: text },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCommentText('');
                },
            }
        );
    };

    // Handle Enter Key press in Comment Textarea
    const handleCommentKeyDown = (e, taskId, text) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleAddComment(taskId, text);
        }
    };

    // Filter tasks based on client search
    const filteredActiveTasks = useMemo(() => {
        if (!searchTerm.trim()) return todoLists;
        const term = searchTerm.toLowerCase();
        return todoLists.filter((t) => {
            const desc = t.task || '';
            const category = t.due_time?.category?.name || '';
            const assignee = t.assigned_user?.name || '';
            return desc.toLowerCase().includes(term) || category.toLowerCase().includes(term) || assignee.toLowerCase().includes(term);
        });
    }, [todoLists, searchTerm]);

    const filteredArchivedTasks = useMemo(() => {
        if (!searchTerm.trim()) return archivedTasks;
        const term = searchTerm.toLowerCase();
        return archivedTasks.filter((t) => {
            const desc = t.task || '';
            const category = t.due_time?.category?.name || '';
            const assignee = t.assigned_user?.name || '';
            return desc.toLowerCase().includes(term) || category.toLowerCase().includes(term) || assignee.toLowerCase().includes(term);
        });
    }, [archivedTasks, searchTerm]);

    // Calendar matrix generator
    const calendarDays = useMemo(() => {
        try {
            const [year, month] = selectedMonth.split('-').map(Number);
            const firstDay = new Date(year, month - 1, 1);
            const lastDay = new Date(year, month, 0);

            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay(); // 0 = Sun

            const days = [];
            // Padding days before start of month
            for (let i = 0; i < startingDayOfWeek; i++) {
                days.push(null);
            }
            // Month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                days.push({
                    dayNumber: day,
                    dateStr,
                    categories: calendarTasks[dateStr] || {},
                });
            }
            return days;
        } catch (e) {
            return [];
        }
    }, [selectedMonth, calendarTasks]);

    // Helper status styling
    const getStatusBadge = (status) => {
        const name = status?.status || 'Open';
        const lower = name.toLowerCase();

        if (lower.includes('success') || lower.includes('complete') || lower.includes('done')) {
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-300';
        }
        if (lower.includes('fail') || lower.includes('reject')) {
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border-rose-300';
        }
        if (lower.includes('process') || lower.includes('progress')) {
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border-blue-300';
        }
        return 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border-amber-300';
    };

    const isOverdue = (dueDateStr) => {
        if (!dueDateStr) return false;
        return new Date(dueDateStr) < new Date();
    };

    return (
        <TodoLayout title="Todo Task List">
            <Head title="Todo Task List" />

            <div className="space-y-6">
                {/* Header Title Banner */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div>
                        <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400">
                            Task Management
                        </span>
                        <h1 className="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            Todo Tasks & Workflow
                        </h1>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Organize, assign, track, and close operational tasks across branches and departments.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setIsCreateModalOpen(true)}
                            className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + New Task Request (Modal)
                        </button>
                        <button
                            type="button"
                            onClick={() => setIsFormCollapsed(!isFormCollapsed)}
                            className="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            {isFormCollapsed ? 'Inline Form' : 'Hide Inline Form'}
                        </button>
                    </div>
                </div>

                {/* Collapsible New Task Form */}
                {!isFormCollapsed && (
                    <div className="rounded-3xl border border-indigo-200 bg-white p-6 shadow-md dark:border-indigo-900/60 dark:bg-slate-900 transition-all">
                        <h2 className="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 text-xs font-bold">
                                +
                            </span>
                            Create New Todo Task
                        </h2>

                        <form onSubmit={handleCreateTaskSubmit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                {/* Job Title / Due Time */}
                                <div>
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                                        Job Title / Due Time *
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
                                        placeholder="Describe the task instructions..."
                                        className="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                    {formErrors.task && <p className="mt-1 text-xs text-rose-500">{formErrors.task}</p>}
                                </div>
                            </div>

                            {/* Request Flow: Branch & Assignee */}
                            <div className="grid gap-6 md:grid-cols-2">
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

                                {/* Assign To User */}
                                <div className="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                    <label className="block text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 mb-2">
                                        Assign To (တာဝန်ခံ)
                                    </label>
                                    <select
                                        value={formData.assignedUserId}
                                        onChange={(e) => setFormData('assignedUserId', e.target.value)}
                                        className="w-full rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm dark:border-emerald-800 dark:bg-slate-800 dark:text-white"
                                    >
                                        <option value="">Select Assignee User</option>
                                        {users.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name} ({u.email})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {/* Calculated Due Date Preview */}
                            {formData.dueDate && (
                                <div className="text-xs text-slate-500 dark:text-slate-400">
                                    Calculated Cutoff Due Date: <span className="font-semibold text-indigo-600 dark:text-indigo-400">{formData.dueDate.replace('T', ' ')}</span>
                                </div>
                            )}

                            <div className="flex justify-end gap-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsFormCollapsed(true)}
                                    className="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={taskProcessing}
                                    className="rounded-xl bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {taskProcessing ? 'Creating...' : 'Create Task'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Filters, View Mode Toggle & Controls */}
                <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        {/* View Mode Selector: Calendar vs List */}
                        <div className="inline-flex rounded-2xl bg-slate-100 p-1 dark:bg-slate-800">
                            <button
                                type="button"
                                onClick={() => { setViewMode('calendar'); applyFilters({ viewMode: 'calendar' }); }}
                                className={`flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition ${
                                    viewMode === 'calendar'
                                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-900 dark:text-indigo-400'
                                        : 'text-slate-600 dark:text-slate-400'
                                }`}
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Calendar View
                            </button>
                            <button
                                type="button"
                                onClick={() => { setViewMode('list'); applyFilters({ viewMode: 'list' }); }}
                                className={`flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition ${
                                    viewMode === 'list'
                                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-900 dark:text-indigo-400'
                                        : 'text-slate-600 dark:text-slate-400'
                                }`}
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                Task List View
                            </button>
                        </div>

                        {/* Search Input */}
                        <div className="relative w-full sm:w-72">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search tasks by title, category..."
                                className="w-full rounded-2xl border border-slate-300 bg-white pl-9 pr-4 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            />
                            <svg className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Filter Inputs Grid */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {/* Branch Filter */}
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Branch</label>
                            <select
                                value={filterBranchId}
                                onChange={handleBranchFilterChange}
                                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">All Branches</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>{b.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Department Filter */}
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department</label>
                            <select
                                value={filterDepartmentId}
                                onChange={handleDepartmentFilterChange}
                                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">All Departments</option>
                                {departments.map((d) => (
                                    <option key={d.id} value={d.id}>{d.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Month Selector for Calendar */}
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Month</label>
                            <select
                                value={selectedMonth}
                                onChange={handleMonthChange}
                                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                {monthsWithTasks.length > 0 ? (
                                    monthsWithTasks.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.label} ({m.count} tasks)
                                        </option>
                                    ))
                                ) : (
                                    <option value={new Date().toISOString().slice(0, 7)}>{new Date().toLocaleDateString('default', { month: 'long', year: 'numeric' })}</option>
                                )}
                            </select>
                        </div>

                        {/* Sort By */}
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Sort By</label>
                            <select
                                value={sortBy}
                                onChange={handleSortChange}
                                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="due_date">Due Date</option>
                                <option value="created_at">Created Date</option>
                                <option value="priority">Priority</option>
                            </select>
                        </div>
                    </div>

                    {/* Status Filter Chips */}
                    <div className="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <span className="text-xs font-medium text-slate-500">Status:</span>
                        {statuses.map((st) => {
                            const isSelected = selectedStatusIds.includes(st.id);
                            return (
                                <button
                                    key={st.id}
                                    type="button"
                                    onClick={() => handleStatusToggle(st.id)}
                                    className={`rounded-full px-3 py-1 text-xs font-semibold transition ${
                                        isSelected
                                            ? 'bg-indigo-600 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                    }`}
                                >
                                    {st.status}
                                </button>
                            );
                        })}

                        {(filterBranchId || filterDepartmentId || selectedStatusIds.length > 0 || searchTerm) && (
                            <button
                                type="button"
                                onClick={handleClearFilters}
                                className="ml-auto text-xs text-indigo-600 hover:underline dark:text-indigo-400 font-medium"
                            >
                                Clear All Filters
                            </button>
                        )}
                    </div>
                </div>

                {/* CALENDAR VIEW */}
                {viewMode === 'calendar' && (
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                {new Date(selectedMonth + '-01').toLocaleDateString('default', { month: 'long', year: 'numeric' })} Calendar Matrix
                            </h2>
                        </div>

                        {/* Calendar Grid Header */}
                        <div className="grid grid-cols-7 gap-2 text-center text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>

                        {/* Calendar Grid Days */}
                        <div className="grid grid-cols-7 gap-2">
                            {calendarDays.map((cell, idx) => {
                                if (!cell) {
                                    return <div key={idx} className="min-h-[100px] rounded-2xl bg-slate-50/50 dark:bg-slate-950/30" />;
                                }

                                const isToday = cell.dateStr === new Date().toISOString().slice(0, 10);

                                return (
                                    <div
                                        key={cell.dateStr}
                                        className={`min-h-[100px] rounded-2xl border p-2 flex flex-col justify-between transition ${
                                            isToday
                                                ? 'border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20'
                                                : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className={`text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center ${
                                                isToday ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-300'
                                            }`}>
                                                {cell.dayNumber}
                                            </span>
                                        </div>

                                        {/* Category Chips */}
                                        <div className="mt-2 space-y-1.5 overflow-y-auto max-h-[80px]">
                                            {Object.values(cell.categories).map((catGroup) => (
                                                <button
                                                    key={catGroup.categoryId}
                                                    type="button"
                                                    onClick={() => setDayModalData({
                                                        date: cell.dateStr,
                                                        categoryId: catGroup.categoryId,
                                                        categoryName: catGroup.name,
                                                        tasks: catGroup.tasks,
                                                    })}
                                                    className="w-full text-left rounded-xl bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60 flex items-center justify-between transition"
                                                >
                                                    <span className="truncate">{catGroup.name}</span>
                                                    <span className="ml-1 rounded-full bg-indigo-200 px-1.5 text-[10px] font-extrabold text-indigo-900 dark:bg-indigo-800 dark:text-indigo-100">
                                                        {catGroup.count}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* LIST / CARD / TABLE VIEW */}
                {viewMode === 'list' && (
                    <div className="space-y-4">
                        {/* Tabs: Active vs Archived */}
                        <div className="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div className="flex gap-4">
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('active')}
                                    className={`pb-2 text-sm font-bold border-b-2 transition ${
                                        activeTab === 'active'
                                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                                    }`}
                                >
                                    Active Tasks ({filteredActiveTasks.length})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('archived')}
                                    className={`pb-2 text-sm font-bold border-b-2 transition ${
                                        activeTab === 'archived'
                                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                            : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                                    }`}
                                >
                                    Archived Tasks ({filteredArchivedTasks.length})
                                </button>
                            </div>

                            {/* View Style Toggle: Card vs Table */}
                            <div className="flex items-center gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setViewStyle('card')}
                                    className={`rounded-lg px-2.5 py-1 text-xs font-semibold ${viewStyle === 'card' ? 'bg-white shadow dark:bg-slate-900' : ''}`}
                                >
                                    Card
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setViewStyle('table')}
                                    className={`rounded-lg px-2.5 py-1 text-xs font-semibold ${viewStyle === 'table' ? 'bg-white shadow dark:bg-slate-900' : ''}`}
                                >
                                    Table
                                </button>
                            </div>
                        </div>

                        {/* Task Content List */}
                        {((activeTab === 'active' ? filteredActiveTasks : filteredArchivedTasks).length === 0) ? (
                            <div className="rounded-3xl border border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
                                <p className="text-sm text-slate-500">No tasks found matching your filters.</p>
                            </div>
                        ) : viewStyle === 'card' ? (
                            /* Card View Grid */
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {(activeTab === 'active' ? filteredActiveTasks : filteredArchivedTasks).map((t) => (
                                    <div
                                        key={t.id}
                                        className="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition hover:shadow-md cursor-pointer group"
                                        onClick={() => setSelectedTaskDetail(t)}
                                    >
                                        <div className="space-y-3">
                                            {/* Header Tags */}
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                    {t.due_time?.category?.name || 'Task'}
                                                </span>

                                                <span className={`rounded-full border px-2.5 py-0.5 text-xs font-bold ${getStatusBadge(t.status)}`}>
                                                    {t.status?.status || 'Open'}
                                                </span>
                                            </div>

                                            {/* Task Body */}
                                            <div>
                                                <h3 className="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                                    {t.task}
                                                </h3>
                                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    Priority: <span className="font-semibold text-slate-700 dark:text-slate-300">{t.due_time?.priority?.level || 'Normal'}</span>
                                                </p>
                                            </div>

                                            {/* Info Rows */}
                                            <div className="space-y-1.5 text-xs text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                                                <p className="flex items-center justify-between">
                                                    <span>Due Date:</span>
                                                    <span className={`font-semibold ${isOverdue(t.due_date) && !t.status?.status?.toLowerCase().includes('complete') ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-200'}`}>
                                                        {t.due_date ? t.due_date.replace('T', ' ') : 'N/A'}
                                                        {isOverdue(t.due_date) && !t.status?.status?.toLowerCase().includes('complete') && ' (Overdue)'}
                                                    </span>
                                                </p>
                                                <p className="flex items-center justify-between">
                                                    <span>Branch:</span>
                                                    <span className="font-medium text-slate-800 dark:text-slate-200">{t.requested_by_branch?.name || '-'}</span>
                                                </p>
                                                <p className="flex items-center justify-between">
                                                    <span>Assignee (တာဝန်ခံ):</span>
                                                    <span className="font-medium text-slate-800 dark:text-slate-200">{t.assigned_user?.name || 'Unassigned'}</span>
                                                </p>
                                            </div>
                                        </div>

                                        {/* Footer Action Buttons */}
                                        <div
                                            className="mt-4 flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800"
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            <div className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedTaskDetail(t)}
                                                    className="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                                                >
                                                    View Details →
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleCopyLink(t.id)}
                                                    className="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                                >
                                                    {copiedTaskId === t.id ? 'Copied!' : 'Copy Link'}
                                                </button>
                                            </div>

                                            {activeTab === 'active' ? (
                                                <div className="flex gap-2">
                                                    {t.kpi_task_instance_id ? (
                                                        <span className="rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60">
                                                            🔒 Managed by KPI Approval
                                                        </span>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleCloseTask(t.id)}
                                                            className="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
                                                        >
                                                            Close Task
                                                        </button>
                                                    )}
                                                    {t.todo_status_id && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleArchiveTask(t.id)}
                                                            className="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                        >
                                                            Archive
                                                        </button>
                                                    )}
                                                </div>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => handleRestoreTask(t.id)}
                                                    className="rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                                >
                                                    Restore
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            /* Table View */
                            <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                    <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50">
                                        <tr>
                                            <th className="px-4 py-3">Task Description</th>
                                            <th className="px-4 py-3">Category</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3">Due Date</th>
                                            <th className="px-4 py-3">Branch</th>
                                            <th className="px-4 py-3">Assignee</th>
                                            <th className="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {(activeTab === 'active' ? filteredActiveTasks : filteredArchivedTasks).map((t) => (
                                            <tr
                                                key={t.id}
                                                className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 cursor-pointer"
                                                onClick={() => setSelectedTaskDetail(t)}
                                            >
                                                <td className="px-4 py-3 font-semibold text-slate-900 dark:text-white max-w-xs truncate">{t.task}</td>
                                                <td className="px-4 py-3">{t.due_time?.category?.name || '-'}</td>
                                                <td className="px-4 py-3">
                                                    <span className={`inline-block rounded-full border px-2.5 py-0.5 text-[11px] font-bold ${getStatusBadge(t.status)}`}>
                                                        {t.status?.status || 'Open'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">{t.due_date ? t.due_date.replace('T', ' ') : '-'}</td>
                                                <td className="px-4 py-3">{t.requested_by_branch?.name || '-'}</td>
                                                <td className="px-4 py-3">{t.assigned_user?.name || 'Unassigned'}</td>
                                                <td className="px-4 py-3 text-right" onClick={(e) => e.stopPropagation()}>
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => setSelectedTaskDetail(t)}
                                                            className="text-indigo-600 hover:underline dark:text-indigo-400 font-semibold"
                                                        >
                                                            View Details
                                                        </button>
                                                        {activeTab === 'active' ? (
                                                            <>
                                                                {t.kpi_task_instance_id ? (
                                                                    <span className="rounded-lg bg-amber-50 px-2 py-1 text-[11px] font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60">
                                                                        🔒 KPI Approval
                                                                    </span>
                                                                ) : (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleCloseTask(t.id)}
                                                                        className="rounded-lg bg-emerald-600 px-2.5 py-1 font-semibold text-white hover:bg-emerald-700"
                                                                    >
                                                                        Close
                                                                    </button>
                                                                )}
                                                                {t.todo_status_id && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleArchiveTask(t.id)}
                                                                        className="rounded-lg border border-slate-300 px-2.5 py-1 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                                                    >
                                                                        Archive
                                                                    </button>
                                                                )}
                                                            </>
                                                        ) : (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleRestoreTask(t.id)}
                                                                className="rounded-lg bg-indigo-600 px-2.5 py-1 font-semibold text-white hover:bg-indigo-700"
                                                            >
                                                                Restore
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* FULL-SCREEN TASK DETAIL VIEW MODAL */}
                <TaskDetailModal
                    task={selectedTaskDetail}
                    isOpen={!!selectedTaskDetail}
                    onClose={() => setSelectedTaskDetail(null)}
                    users={users}
                    statuses={statuses}
                    currentUser={user}
                />

                {/* DAY DETAIL MODAL */}
                {dayModalData && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                        <div className="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                        {dayModalData.categoryName} ({dayModalData.date})
                                    </h3>
                                    <p className="text-xs text-slate-500">{dayModalData.tasks.length} task(s) on this date</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setDayModalData(null)}
                                    className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    ✕
                                </button>
                            </div>

                            <div className="space-y-3 max-h-[60vh] overflow-y-auto">
                                {dayModalData.tasks.map((t) => (
                                    <div key={t.id} className="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50 flex flex-col gap-2">
                                        <div className="flex items-start justify-between">
                                            <h4 className="font-bold text-sm text-slate-900 dark:text-white">{t.task}</h4>
                                            <span className={`rounded-full border px-2 py-0.5 text-[10px] font-bold ${getStatusBadge(t.status)}`}>
                                                {t.status?.status || 'Open'}
                                            </span>
                                        </div>
                                        <div className="text-xs text-slate-500 dark:text-slate-400 flex flex-wrap gap-x-4">
                                            <span>Assignee: <strong>{t.assigned_user?.name || 'Unassigned'}</strong></span>
                                            <span>Branch: <strong>{t.requested_by_branch?.name || '-'}</strong></span>
                                        </div>
                                        <div className="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                                            <button
                                                type="button"
                                                onClick={() => { setDayModalData(null); setSelectedTaskDetail(t); }}
                                                className="text-xs text-indigo-600 hover:underline dark:text-indigo-400 font-semibold"
                                            >
                                                View Details →
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => { handleCloseTask(t.id); setDayModalData(null); }}
                                                className="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                                            >
                                                Close Task
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* COMMENTS SLIDE-OVER / MODAL */}
                {commentsTask && (
                    <div className="fixed inset-0 z-50 flex items-center justify-end bg-slate-900/50 backdrop-blur-sm">
                        <div className="h-full w-full max-w-lg border-l border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                                    <div>
                                        <h3 className="text-lg font-bold text-slate-900 dark:text-white">Task Comments</h3>
                                        <p className="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs">{commentsTask.task}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setCommentsTask(null)}
                                        className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                    >
                                        ✕
                                    </button>
                                </div>

                                {/* Comments Thread List */}
                                <div className="space-y-3 overflow-y-auto max-h-[60vh] pr-1">
                                    {(commentsTask.comments && commentsTask.comments.length > 0) ? (
                                        commentsTask.comments.map((c) => (
                                            <div key={c.id} className="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                                                <div className="flex items-center justify-between text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                                    <span>{c.user?.name || 'User'}</span>
                                                    <span className="text-[10px] text-slate-400 font-normal">{c.created_at ? new Date(c.created_at).toLocaleString() : ''}</span>
                                                </div>
                                                <p className="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{c.comment}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-center text-xs text-slate-400 py-8">No comments yet. Start a discussion below.</p>
                                    )}
                                </div>
                            </div>

                            {/* Comment Input */}
                            <form onSubmit={(e) => { e.preventDefault(); handleAddComment(commentsTask.id, commentText); }} className="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <textarea
                                    value={commentText}
                                    onChange={(e) => setCommentText(e.target.value)}
                                    onKeyDown={(e) => handleCommentKeyDown(e, commentsTask.id, commentText)}
                                    placeholder="Write a comment... (Press Enter to post)"
                                    rows="3"
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    required
                                />
                                <div className="flex items-center justify-between">
                                    <span className="text-[11px] text-slate-400">Press Enter to post</span>
                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setCommentsTask(null)}
                                            className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                        >
                                            Close
                                        </button>
                                        <button
                                            type="submit"
                                            className="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                        >
                                            Post Comment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
                {/* GLOBAL CREATE TASK MODAL */}
                <CreateTaskModal
                    isOpen={isCreateModalOpen}
                    onClose={() => setIsCreateModalOpen(false)}
                    formattedDueTimes={formattedDueTimes}
                    dueTimes={dueTimes}
                    branches={branches}
                    departments={departments}
                    categories={categories}
                    itAdminDepartments={itAdminDepartments}
                    users={users}
                    userBranchId={userBranchId}
                />
            </div>
        </TodoLayout>
    );
}
