import React, { useState, useMemo, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import TodoLayout from '../../Layouts/TodoLayout';
import CreateTaskModal from './Components/CreateTaskModal';
import TaskDetailModal from './Components/TaskDetailModal';
import { exportTodoReportPDF } from '@/utils/todoReportPdfExport';

export default function Dashboard({
    todoLists = [],
    archivedTasks = [],
    dueTimes = [],
    formattedDueTimes = [],
    statuses = [],
    branches = [],
    departments = [],
    categories = [],
    priorities = [],
    itAdminDepartments = [],
    users = [],
    userBranchId = null,
    topPerformers = [],
    monthsWithTasks = [],
    filters = {},
}) {
    const { auth = {} } = usePage().props;
    const user = auth?.user;

    // Filters state
    const [selectedMonth, setSelectedMonth] = useState(filters?.filterMonth || 'all');
    const [selectedDepartmentId, setSelectedDepartmentId] = useState(filters?.filterDepartmentId || 'all');
    const [isExportingPdf, setIsExportingPdf] = useState(false);
    const [expandedGroups, setExpandedGroups] = useState({});
    const [groupPages, setGroupPages] = useState({});

    // Modals
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedTaskDetail, setSelectedTaskDetail] = useState(null);
    const [commentInput, setCommentInput] = useState('');
    const [copiedTaskId, setCopiedTaskId] = useState(null);

    // Escape listener for Task Detail Modal
    useEffect(() => {
        if (!selectedTaskDetail) return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                setSelectedTaskDetail(null);
            }
        };
        window.addEventListener('keydown', handleKeyDown, true);
        return () => window.removeEventListener('keydown', handleKeyDown, true);
    }, [selectedTaskDetail]);

    // Sync open detail modal with fresh props data
    useEffect(() => {
        const allTasks = [...todoLists, ...archivedTasks];

        if (selectedTaskDetail) {
            const fresh = allTasks.find((t) => t.id === selectedTaskDetail.id);
            if (fresh) {
                setSelectedTaskDetail(fresh);
            }
        }
    }, [todoLists, archivedTasks]);

    // Realtime background sync interval for live updates across dashboard and detail view
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

    // Extract available months for dropdown
    const availableMonths = useMemo(() => {
        if (monthsWithTasks && monthsWithTasks.length > 0) {
            return monthsWithTasks;
        }
        const counts = {};
        todoLists.forEach((t) => {
            const d = t.due_date || t.created_at;
            if (d) {
                const ym = String(d).slice(0, 7);
                counts[ym] = (counts[ym] || 0) + 1;
            }
        });
        return Object.keys(counts).sort().reverse().map((ym) => {
            const [y, m] = ym.split('-');
            const dateObj = new Date(parseInt(y, 10), parseInt(m, 10) - 1, 1);
            const label = dateObj.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            return { value: ym, label, count: counts[ym] };
        });
    }, [monthsWithTasks, todoLists]);

    // Filter tasks based on selected month and department
    const filteredTasks = useMemo(() => {
        return todoLists.filter((t) => {
            // Month filter
            if (selectedMonth && selectedMonth !== 'all') {
                const d = t.due_date || t.created_at;
                if (!d || !String(d).startsWith(selectedMonth)) {
                    return false;
                }
            }

            // Department filter
            if (selectedDepartmentId && selectedDepartmentId !== 'all') {
                const targetDeptId = String(selectedDepartmentId);
                const reqDeptId = t.requested_by_department_id ? String(t.requested_by_department_id) : null;
                const assignedDeptId = t.assigned_user?.department_id ? String(t.assigned_user.department_id) : null;
                const deptId = t.department?.id ? String(t.department.id) : null;
                const catDeptId = t.due_time?.category?.department_id ? String(t.due_time.category.department_id) : null;

                const matches = (reqDeptId === targetDeptId) ||
                                (assignedDeptId === targetDeptId) ||
                                (deptId === targetDeptId) ||
                                (catDeptId === targetDeptId);
                if (!matches) return false;
            }

            return true;
        });
    }, [todoLists, selectedMonth, selectedDepartmentId]);

    // KPI Metrics calculation based on filtered tasks
    const totalTasks = filteredTasks.length;

    const completedTasks = useMemo(() => {
        return filteredTasks.filter((t) => {
            const stName = (t.status?.status || '').toLowerCase();
            return stName.includes('complete') || stName.includes('success') || stName.includes('done');
        }).length;
    }, [filteredTasks]);

    const openTasks = totalTasks - completedTasks;

    const overdueTasks = useMemo(() => {
        const now = new Date();
        return filteredTasks.filter((t) => {
            const stName = (t.status?.status || '').toLowerCase();
            const isDone = stName.includes('complete') || stName.includes('success') || stName.includes('done');
            if (isDone || !t.due_date) return false;
            return new Date(t.due_date) < now;
        }).length;
    }, [filteredTasks]);

    // Overall Success Rate
    const overallSuccessRate = useMemo(() => {
        if (totalTasks === 0) return '0.0';
        return ((completedTasks / totalTasks) * 100).toFixed(1);
    }, [totalTasks, completedTasks]);

    // Overdue Task Rate (Non-success tasks passed cutoff)
    const overdueRate = useMemo(() => {
        if (totalTasks === 0) return '0.0';
        return ((overdueTasks / totalTasks) * 100).toFixed(1);
    }, [totalTasks, overdueTasks]);

    // Task Details Grouped by Due Time Types
    const dueTimeTypeGroups = useMemo(() => {
        const groupsMap = {};

        filteredTasks.forEach((t) => {
            const dt = t.due_time;
            const groupId = dt ? String(dt.id) : 'custom';

            if (!groupsMap[groupId]) {
                const catName = dt?.category?.name || 'General / Standard Due Time';
                const prioLevel = dt?.priority?.level || 'Normal';
                const prioColor = dt?.priority?.color_code || '#6366f1';
                const duration = dt?.duration ? `${dt.duration} Hours` : 'Standard';

                groupsMap[groupId] = {
                    id: groupId,
                    title: dt ? `${catName} (${prioLevel}) - ${duration}` : 'Standard / Custom Due Time',
                    categoryName: catName,
                    priorityLevel: prioLevel,
                    duration: dt?.duration || null,
                    color: prioColor,
                    tasks: [],
                };
            }
            groupsMap[groupId].tasks.push(t);
        });

        // Compute group metrics and rates
        const groupsArr = Object.values(groupsMap).map((g) => {
            const total = g.tasks.length;
            const completed = g.tasks.filter((t) => {
                const stName = (t.status?.status || '').toLowerCase();
                return stName.includes('complete') || stName.includes('success') || stName.includes('done');
            }).length;

            const overdue = g.tasks.filter((t) => {
                const stName = (t.status?.status || '').toLowerCase();
                const isDone = stName.includes('complete') || stName.includes('success') || stName.includes('done');
                if (isDone || !t.due_date) return false;
                return new Date(t.due_date) < new Date();
            }).length;

            const open = total - completed;
            const successRate = total > 0 ? ((completed / total) * 100).toFixed(1) : '0.0';
            const groupOverdueRate = total > 0 ? ((overdue / total) * 100).toFixed(1) : '0.0';

            return {
                ...g,
                total,
                completed,
                overdue,
                open,
                successRate,
                overdueRate: groupOverdueRate,
            };
        });

        // Sort groups by largest task count
        groupsArr.sort((a, b) => b.total - a.total);
        return groupsArr;
    }, [filteredTasks]);

    // Priority columns with fallback
    const priorityColumns = useMemo(() => {
        if (priorities && priorities.length > 0) {
            return priorities;
        }
        const pMap = {};
        dueTimes.forEach((dt) => {
            if (dt.priority) {
                pMap[dt.priority.id] = dt.priority;
            }
        });
        const list = Object.values(pMap);
        return list.length > 0 ? list : [
            { id: 1, level: 'Urgent / P1', color_code: '#EF4444' },
            { id: 2, level: 'High / P2', color_code: '#F59E0B' },
            { id: 3, level: 'Medium / P3', color_code: '#3B82F6' },
            { id: 4, level: 'Low / P4', color_code: '#64748B' },
        ];
    }, [priorities, dueTimes]);

    // Group by Category and Priority Levels Matrix with Success Rate
    const categoryPriorityMatrix = useMemo(() => {
        const catMap = {};

        // Seed with all known categories
        categories.forEach((cat) => {
            catMap[String(cat.id)] = {
                id: cat.id,
                name: cat.name,
                priorityCounts: {},
                totalTasks: 0,
                completedTasks: 0,
            };
        });

        // Uncategorized bucket for tasks without a defined category
        const uncategorized = {
            id: 'uncategorized',
            name: 'General / Uncategorized',
            priorityCounts: {},
            totalTasks: 0,
            completedTasks: 0,
        };

        filteredTasks.forEach((task) => {
            const catId = task.due_time?.category?.id || task.category?.id;
            const catName = task.due_time?.category?.name || task.category?.name;

            let targetCat = null;
            if (catId && catMap[String(catId)]) {
                targetCat = catMap[String(catId)];
            } else if (catName) {
                const found = Object.values(catMap).find((c) => c.name === catName);
                if (found) {
                    targetCat = found;
                } else {
                    catMap[catName] = {
                        id: catName,
                        name: catName,
                        priorityCounts: {},
                        totalTasks: 0,
                        completedTasks: 0,
                    };
                    targetCat = catMap[catName];
                }
            } else {
                targetCat = uncategorized;
            }

            const prioId = task.due_time?.priority?.id || task.priority?.id;
            const prioLevel = task.due_time?.priority?.level || task.priority?.level;

            const matchedPriority = priorityColumns.find(
                (p) => (prioId && p.id === prioId) || (prioLevel && p.level === prioLevel)
            );
            const colKey = matchedPriority ? String(matchedPriority.id) : (prioId ? String(prioId) : 'other');

            if (!targetCat.priorityCounts[colKey]) {
                targetCat.priorityCounts[colKey] = { total: 0, completed: 0 };
            }

            const stName = (task.status?.status || '').toLowerCase();
            const isDone = stName.includes('complete') || stName.includes('success') || stName.includes('done');

            targetCat.priorityCounts[colKey].total += 1;
            if (isDone) {
                targetCat.priorityCounts[colKey].completed += 1;
                targetCat.completedTasks += 1;
            }
            targetCat.totalTasks += 1;
        });

        let list = Object.values(catMap);
        if (uncategorized.totalTasks > 0) {
            list.push(uncategorized);
        }

        // Sort: categories with tasks first (descending by total tasks), then alphabetically
        list.sort((a, b) => {
            if (b.totalTasks !== a.totalTasks) return b.totalTasks - a.totalTasks;
            return a.name.localeCompare(b.name);
        });

        // Compute column totals for table footer
        const columnTotals = {};
        priorityColumns.forEach((p) => {
            columnTotals[String(p.id)] = 0;
        });
        let grandTotal = 0;
        let grandCompleted = 0;

        list.forEach((row) => {
            grandTotal += row.totalTasks;
            grandCompleted += row.completedTasks;
            priorityColumns.forEach((p) => {
                const count = row.priorityCounts[String(p.id)]?.total || 0;
                columnTotals[String(p.id)] = (columnTotals[String(p.id)] || 0) + count;
            });
        });

        const overallRate = grandTotal > 0 ? ((grandCompleted / grandTotal) * 100).toFixed(1) : '0.0';

        return {
            rows: list,
            columnTotals,
            grandTotal,
            grandCompleted,
            overallSuccessRate: overallRate,
        };
    }, [categories, filteredTasks, priorityColumns]);

    // Initialize all groups to expanded on first load
    useEffect(() => {
        if (dueTimeTypeGroups.length > 0) {
            setExpandedGroups((prev) => {
                const next = { ...prev };
                dueTimeTypeGroups.forEach((g) => {
                    if (next[g.id] === undefined) {
                        next[g.id] = true;
                    }
                });
                return next;
            });
        }
    }, [dueTimeTypeGroups]);

    const toggleGroup = (groupId) => {
        setExpandedGroups((prev) => ({
            ...prev,
            [groupId]: !prev[groupId],
        }));
    };

    const expandAllGroups = () => {
        const next = {};
        dueTimeTypeGroups.forEach((g) => { next[g.id] = true; });
        setExpandedGroups(next);
    };

    const collapseAllGroups = () => {
        setExpandedGroups({});
    };

    // Department Distribution based on filteredTasks
    const departmentDistribution = useMemo(() => {
        const counts = {};
        filteredTasks.forEach((t) => {
            const deptName = t.assigned_user?.department?.name || t.department?.name || 'Unassigned';
            counts[deptName] = (counts[deptName] || 0) + 1;
        });

        const total = totalTasks || 1;
        return Object.entries(counts).map(([name, count]) => ({
            name,
            count,
            percentage: Math.round((count / total) * 100),
        })).sort((a, b) => b.count - a.count);
    }, [filteredTasks, totalTasks]);

    // Priority Breakdown based on filteredTasks
    const priorityBreakdown = useMemo(() => {
        const counts = {};
        filteredTasks.forEach((t) => {
            const prioLevel = t.due_time?.priority?.level || 'Normal';
            const colorCode = t.due_time?.priority?.color_code || '#3b82f6';
            if (!counts[prioLevel]) {
                counts[prioLevel] = { count: 0, color: colorCode };
            }
            counts[prioLevel].count += 1;
        });

        return Object.entries(counts).map(([level, data]) => ({
            level,
            count: data.count,
            color: data.color,
        }));
    }, [filteredTasks]);

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

    // Actions
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
        router.delete(`/todo/tasks/${taskId}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedTaskDetail && selectedTaskDetail.id === taskId) {
                    setSelectedTaskDetail(null);
                }
            },
        });
    };

    const handleRestoreTask = (taskId) => {
        router.post(`/todo/tasks/${taskId}/restore`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedTaskDetail && selectedTaskDetail.id === taskId) {
                    setSelectedTaskDetail(null);
                }
            },
        });
    };

    const handleCopyLink = (taskId) => {
        const shareUrl = `${window.location.origin}/todo/list?task_id=${taskId}`;
        navigator.clipboard.writeText(shareUrl);
        setCopiedTaskId(taskId);
        setTimeout(() => setCopiedTaskId(null), 3000);
    };

    // Post Comment Action
    const handlePostComment = (e) => {
        if (e) e.preventDefault();
        if (!commentInput.trim() || !selectedTaskDetail) return;

        const payload = { comment: commentInput.trim(), comment_type: 'normal' };
        router.post(`/todo/tasks/${selectedTaskDetail.id}/comments`, payload, {
            preserveScroll: true,
            onSuccess: () => setCommentInput(''),
        });
    };

    const handleCommentKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handlePostComment();
        }
    };

    const renderPriorityBadge = (prioLevel, colorCode = '#3b82f6') => {
        const isHex = colorCode && colorCode.startsWith('#');
        if (isHex) {
            return (
                <span
                    className="inline-flex items-center justify-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-extrabold text-center leading-none"
                    style={{
                        backgroundColor: `${colorCode}20`,
                        color: colorCode,
                        borderColor: `${colorCode}50`,
                        borderWidth: '1px',
                    }}
                >
                    <span className="leading-none text-[11px]">🔥</span>
                    <span className="leading-none">{prioLevel}</span>
                </span>
            );
        }
        return (
            <span className="inline-flex items-center justify-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-extrabold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 leading-none text-center">
                <span className="leading-none text-[11px]">🔥</span>
                <span className="leading-none">{prioLevel}</span>
            </span>
        );
    };

    const renderSlaBadge = (task) => {
        const stName = (task.status?.status || '').toLowerCase();
        const isSuccess = stName.includes('complete') || stName.includes('success') || stName.includes('done');
        const isLate = !isSuccess && task.due_date && new Date(task.due_date) < new Date();

        if (isSuccess) {
            return (
                <span className="inline-flex items-center justify-center gap-1.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 border border-emerald-300 dark:border-emerald-800 px-3 py-1 text-[11px] font-extrabold text-emerald-800 dark:text-emerald-300 leading-none text-center">
                    <span className="leading-none">✅</span>
                    <span className="leading-none">Success</span>
                </span>
            );
        }
        if (isLate) {
            return (
                <span className="inline-flex items-center justify-center gap-1.5 rounded-full bg-rose-100 dark:bg-rose-950/80 border border-rose-300 dark:border-rose-800 px-3 py-1 text-[11px] font-extrabold text-rose-800 dark:text-rose-300 leading-none text-center">
                    <span className="leading-none">🚨</span>
                    <span className="leading-none">Overdue</span>
                </span>
            );
        }
        return (
            <span className="inline-flex items-center justify-center gap-1.5 rounded-full bg-amber-100 dark:bg-amber-950/80 border border-amber-300 dark:border-amber-800 px-3 py-1 text-[11px] font-extrabold text-amber-800 dark:text-amber-300 leading-none text-center">
                <span className="leading-none">⏳</span>
                <span className="leading-none">On Track</span>
            </span>
        );
    };

    // PDF Export Action
    const handleExportPDF = async () => {
        setIsExportingPdf(true);
        try {
            const selectedMonthObj = availableMonths.find((m) => m.value === selectedMonth);
            const selectedMonthLabel = selectedMonth === 'all' ? 'All Months' : (selectedMonthObj?.label || selectedMonth);

            const selectedDeptObj = departments.find((d) => String(d.id) === String(selectedDepartmentId));
            const selectedDepartmentName = selectedDepartmentId === 'all' ? 'All Departments' : (selectedDeptObj?.name || 'Selected Department');

            await exportTodoReportPDF({
                tasks: filteredTasks,
                groups: dueTimeTypeGroups,
                metrics: {
                    totalTasks,
                    completedTasks,
                    openTasks,
                    overdueTasks,
                    successRate: overallSuccessRate,
                    overdueRate,
                },
                filters: {
                    selectedMonth,
                    selectedMonthLabel,
                    selectedDepartmentId,
                    selectedDepartmentName,
                },
                authUser: user,
                appName: 'OrderDemo',
            });
        } catch (err) {
            console.error('Failed to export PDF:', err);
        } finally {
            setIsExportingPdf(false);
        }
    };

    return (
        <TodoLayout title="Todo Analytics Dashboard">
            <Head title="Todo Analytics Dashboard" />

            <div className="space-y-8">
                {/* Header Welcome Banner */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div>
                        <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400 mb-2">
                            Interactive Overview
                        </span>
                        <h1 className="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            Welcome back, {user?.name || 'User'}! 👋
                        </h1>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Here is the real-time operational breakdown, SLA compliance, and task progress metrics for your team.
                        </p>
                    </div>

                    <div className="flex items-center gap-3 shrink-0 flex-wrap">
                        {/* PDF Export Report Button */}
                        <button
                            type="button"
                            onClick={handleExportPDF}
                            disabled={isExportingPdf || filteredTasks.length === 0}
                            className="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-rose-600 to-red-600 px-5 py-3 text-xs font-extrabold text-white shadow-lg shadow-rose-500/25 transition hover:from-rose-700 hover:to-red-700 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Export current filtered report as PDF"
                        >
                            {isExportingPdf ? (
                                <>
                                    <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Generating PDF...</span>
                                </>
                            ) : (
                                <>
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Export Report PDF</span>
                                </>
                            )}
                        </button>

                        <button
                            type="button"
                            onClick={() => setIsCreateModalOpen(true)}
                            className="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-xs font-extrabold text-white shadow-lg shadow-indigo-500/25 transition hover:from-indigo-700 hover:to-violet-700 active:scale-95 shrink-0"
                        >
                            + Create Task Request
                        </button>
                    </div>
                </div>

                {/* Filter Toolbar: Month and Department */}
                <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="flex flex-wrap items-center gap-4">
                            {/* Month Filter */}
                            <div className="flex items-center gap-2">
                                <label htmlFor="filter-month" className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <span>📅</span> Month:
                                </label>
                                <select
                                    id="filter-month"
                                    value={selectedMonth}
                                    onChange={(e) => setSelectedMonth(e.target.value)}
                                    className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 shadow-xs focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <option value="all">All Months</option>
                                    {availableMonths.map((m) => (
                                        <option key={m.value} value={m.value}>
                                            {m.label} ({m.count} tasks)
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Department Filter */}
                            <div className="flex items-center gap-2">
                                <label htmlFor="filter-department" className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <span>🏢</span> Department:
                                </label>
                                <select
                                    id="filter-department"
                                    value={selectedDepartmentId}
                                    onChange={(e) => setSelectedDepartmentId(e.target.value)}
                                    className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 shadow-xs focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 max-w-xs"
                                >
                                    <option value="all">All Departments</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={String(dept.id)}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Reset Button */}
                            {(selectedMonth !== 'all' || selectedDepartmentId !== 'all') && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSelectedMonth('all');
                                        setSelectedDepartmentId('all');
                                    }}
                                    className="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                                >
                                    <span>✕</span> Reset Filters
                                </button>
                            )}
                        </div>

                        {/* Active Scope Summary */}
                        <div className="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span className="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800/60 px-3 py-1 text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                Showing {filteredTasks.length} of {todoLists.length} Total Tasks
                            </span>
                        </div>
                    </div>
                </div>

                {/* KPI Metrics Cards Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {/* Card 1: Total Tasks */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tasks</span>
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400 text-base">
                                📋
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-black text-slate-900 dark:text-white">{totalTasks}</div>
                            <span className="mt-1 block text-[11px] text-slate-500">Filtered volume</span>
                        </div>
                    </div>

                    {/* Card 2: Open / Pending */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-amber-500">Pending</span>
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/70 dark:text-amber-400 text-base">
                                ⏳
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-black text-amber-600 dark:text-amber-400">{openTasks}</div>
                            <span className="mt-1 block text-[11px] text-slate-500">In progress / Open</span>
                        </div>
                    </div>

                    {/* Card 3: Completed Tasks */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-emerald-500">Completed</span>
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400 text-base">
                                ✅
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400">{completedTasks}</div>
                            <span className="mt-1 block text-[11px] text-slate-500">Successfully closed</span>
                        </div>
                    </div>

                    {/* Card 4: Overdue Tasks (Not Success) */}
                    <div className="rounded-3xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm dark:border-rose-900/40 dark:bg-rose-950/20 flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Overdue Tasks</span>
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-300 text-base">
                                🚨
                            </span>
                        </div>
                        <div className="mt-3">
                            <div className="text-2xl font-black text-rose-600 dark:text-rose-400">{overdueTasks}</div>
                            <span className="mt-1 block text-[11px] text-rose-500 font-semibold">Missed due cutoff</span>
                        </div>
                    </div>

                    {/* Card 5: Overall Success Rate */}
                    <div className="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50/90 to-teal-50/50 p-5 shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-teal-950/20 flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Overall Success</span>
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/80 dark:text-emerald-200 text-base">
                                🎯
                            </span>
                        </div>
                        <div className="mt-3 space-y-1.5">
                            <div className="text-2xl font-black text-emerald-700 dark:text-emerald-300">{overallSuccessRate}%</div>
                            <div className="h-1.5 w-full rounded-full bg-emerald-200 dark:bg-emerald-900 overflow-hidden">
                                <div
                                    className="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                    style={{ width: `${Math.min(parseFloat(overallSuccessRate), 100)}%` }}
                                />
                            </div>
                            <span className="block text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Success compliance rate</span>
                        </div>
                    </div>

                    {/* Card 6: Overdue Task Rate */}
                    <div className="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50/90 to-pink-50/50 p-5 shadow-sm dark:border-rose-900/50 dark:from-rose-950/40 dark:to-pink-950/20 flex flex-col justify-between">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Overdue Rate</span>
                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/80 dark:text-rose-200 text-base">
                                ⚠️
                            </span>
                        </div>
                        <div className="mt-3 space-y-1.5">
                            <div className="text-2xl font-black text-rose-700 dark:text-rose-300">{overdueRate}%</div>
                            <div className="h-1.5 w-full rounded-full bg-rose-200 dark:bg-rose-900 overflow-hidden">
                                <div
                                    className="h-full rounded-full bg-rose-500 transition-all duration-500"
                                    style={{ width: `${Math.min(parseFloat(overdueRate), 100)}%` }}
                                />
                            </div>
                            <span className="block text-[10px] text-rose-600 dark:text-rose-400 font-semibold">Non-success SLA breach</span>
                        </div>
                    </div>
                </div>

                {/* Department & Priority Analytics Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Department Task Distribution */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>🏢</span> Department Task Workload
                            </h2>
                            <span className="text-xs text-slate-400 font-medium">By Department</span>
                        </div>

                        <div className="space-y-3 pt-2">
                            {departmentDistribution.length > 0 ? (
                                departmentDistribution.map((dept) => (
                                    <div key={dept.name} className="space-y-1.5">
                                        <div className="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            <span>{dept.name}</span>
                                            <span className="font-extrabold text-indigo-600 dark:text-indigo-400">
                                                {dept.count} tasks ({dept.percentage}%)
                                            </span>
                                        </div>
                                        <div className="h-2.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-600 transition-all duration-500"
                                                style={{ width: `${dept.percentage}%` }}
                                            />
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-xs text-slate-400 italic">No department task data available.</p>
                            )}
                        </div>
                    </div>

                    {/* Priority & Category Breakdown */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>🔥</span> Priority Level Breakdown
                            </h2>
                            <span className="text-xs text-slate-400 font-medium">By Priority</span>
                        </div>

                        <div className="flex flex-wrap gap-3 pt-2">
                            {priorityBreakdown.length > 0 ? (
                                priorityBreakdown.map((prio) => (
                                    <div
                                        key={prio.level}
                                        className="flex flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50/60 p-4 min-w-[140px] flex-1 dark:border-slate-800 dark:bg-slate-800/50"
                                    >
                                        <div className="mb-2">
                                            {renderPriorityBadge(prio.level, prio.color)}
                                        </div>
                                        <div>
                                            <span className="text-2xl font-black text-slate-900 dark:text-white">{prio.count}</span>
                                            <span className="block text-[10px] text-slate-400 font-medium">Tasks queued</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-xs text-slate-400 italic">No priority breakdown data.</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* CATEGORY & PRIORITY MATRIX TABLE */}
                <div className="rounded-3xl border border-slate-200 bg-white shadow-xs overflow-hidden dark:border-slate-800 dark:bg-slate-900 transition-all">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between p-5 border-b border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-800/50 gap-3">
                        <div>
                            <span className="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400 mb-1">
                                Category Cross-Tabulation Matrix
                            </span>
                            <h3 className="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>📊</span> Category by Priority & Success Rate
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Task distribution across priority levels and completion success rate grouped by category.
                            </p>
                        </div>
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className="inline-flex items-center justify-center rounded-xl bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-2xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                Categories: <strong className="ml-1">{categoryPriorityMatrix.rows.length}</strong>
                            </span>
                            <span className="inline-flex items-center justify-center rounded-xl bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/70 dark:border-emerald-800 dark:text-emerald-300">
                                Overall: <strong className="ml-1">{categoryPriorityMatrix.grandCompleted}/{categoryPriorityMatrix.grandTotal} ({categoryPriorityMatrix.overallSuccessRate} %)</strong>
                            </span>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                            <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/80 dark:text-slate-300">
                                <tr>
                                    <th className="px-5 py-3.5 text-left min-w-[220px]">Category</th>
                                    {priorityColumns.map((prio) => (
                                        <th key={prio.id} className="px-5 py-3.5 text-center min-w-[110px]">
                                            <span
                                                className="inline-flex items-center justify-center gap-1 rounded-lg px-2.5 py-1 text-xs font-extrabold border"
                                                style={{
                                                    backgroundColor: prio.color_code ? `${prio.color_code}15` : '#f1f5f9',
                                                    color: prio.color_code || '#475569',
                                                    borderColor: prio.color_code ? `${prio.color_code}30` : '#cbd5e1',
                                                }}
                                            >
                                                {prio.level}
                                            </span>
                                        </th>
                                    ))}
                                    <th className="px-5 py-3.5 text-center min-w-[160px]">
                                        Success Rate
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {categoryPriorityMatrix.rows.map((row) => {
                                    const rateNum = row.totalTasks > 0 ? ((row.completedTasks / row.totalTasks) * 100).toFixed(1) : '0.0';
                                    const hasTasks = row.totalTasks > 0;

                                    return (
                                        <tr
                                            key={row.id}
                                            className={`hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition ${
                                                !hasTasks ? 'opacity-60' : ''
                                            }`}
                                        >
                                            <td className="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm">📁</span>
                                                    <span>{row.name}</span>
                                                </div>
                                            </td>

                                            {priorityColumns.map((prio) => {
                                                const pData = row.priorityCounts[String(prio.id)];
                                                const count = pData?.total || 0;
                                                const completed = pData?.completed || 0;

                                                return (
                                                    <td key={prio.id} className="px-5 py-3.5 text-center">
                                                        {count > 0 ? (
                                                            <span
                                                                className="inline-flex items-center justify-center min-w-[32px] h-7 px-2.5 rounded-xl font-bold text-xs shadow-2xs border"
                                                                style={{
                                                                    backgroundColor: prio.color_code ? `${prio.color_code}15` : '#f8fafc',
                                                                    color: prio.color_code || '#334155',
                                                                    borderColor: prio.color_code ? `${prio.color_code}40` : '#cbd5e1',
                                                                }}
                                                                title={`${count} total tasks (${completed} completed)`}
                                                            >
                                                                {count}
                                                            </span>
                                                        ) : (
                                                            <span className="text-slate-300 dark:text-slate-600 font-medium">-</span>
                                                        )}
                                                    </td>
                                                );
                                            })}

                                            <td className="px-5 py-3.5 text-center">
                                                {hasTasks ? (
                                                    <div className="inline-flex items-center justify-center gap-2">
                                                        <span className="font-extrabold text-slate-900 dark:text-white text-xs">
                                                            {row.completedTasks}/{row.totalTasks}
                                                        </span>
                                                        <span
                                                            className={`inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-[11px] font-black leading-none ${
                                                                parseFloat(rateNum) >= 80
                                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                                                    : parseFloat(rateNum) >= 50
                                                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800'
                                                            }`}
                                                        >
                                                            ({rateNum} %)
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-400 font-normal">0/0 (0 %)</span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot className="bg-slate-50/90 font-bold text-xs text-slate-800 dark:bg-slate-800/90 dark:text-slate-200 border-t-2 border-slate-200 dark:border-slate-700">
                                <tr>
                                    <td className="px-5 py-3.5 font-extrabold uppercase tracking-wide">
                                        Total
                                    </td>
                                    {priorityColumns.map((prio) => {
                                        const colCount = categoryPriorityMatrix.columnTotals[String(prio.id)] || 0;
                                        return (
                                            <td key={prio.id} className="px-5 py-3.5 text-center font-extrabold">
                                                {colCount > 0 ? (
                                                    <span className="font-extrabold text-slate-900 dark:text-white">
                                                        {colCount}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400 font-normal">0</span>
                                                )}
                                            </td>
                                        );
                                    })}
                                    <td className="px-5 py-3.5 text-center font-black">
                                        <div className="inline-flex items-center justify-center gap-2">
                                            <span className="font-black text-slate-900 dark:text-white text-xs">
                                                {categoryPriorityMatrix.grandCompleted}/{categoryPriorityMatrix.grandTotal}
                                            </span>
                                            <span
                                                className={`inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-[11px] font-black leading-none ${
                                                    parseFloat(categoryPriorityMatrix.overallSuccessRate) >= 80
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                                        : parseFloat(categoryPriorityMatrix.overallSuccessRate) >= 50
                                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800'
                                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800'
                                                }`}
                                            >
                                                ({categoryPriorityMatrix.overallSuccessRate} %)
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {/* TASK DETAILS GROUPED BY DUE TIME TYPES */}
                <div className="space-y-4">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div>
                            <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400 mb-1">
                                Categorized Operational Matrix
                            </span>
                            <h2 className="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>⏱️</span> Task Details Grouped by Due Time Types
                            </h2>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Detailed task breakdown, SLA compliance, and individual progress grouped by due time configuration.
                            </p>
                        </div>

                        <div className="flex items-center gap-2 self-start sm:self-auto">
                            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                {dueTimeTypeGroups.length} Types
                            </span>
                            <button
                                type="button"
                                onClick={expandAllGroups}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                            >
                                Expand All
                            </button>
                            <button
                                type="button"
                                onClick={collapseAllGroups}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                            >
                                Collapse All
                            </button>
                        </div>
                    </div>

                    {dueTimeTypeGroups.length > 0 ? (
                        <div className="space-y-4">
                            {dueTimeTypeGroups.map((group) => {
                                const isExpanded = !!expandedGroups[group.id];
                                const rateNum = parseFloat(group.successRate);
                                const rateColor = rateNum >= 80 ? 'text-emerald-600 dark:text-emerald-400' : (rateNum >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400');

                                // Pagination (5 items per page)
                                const ITEMS_PER_PAGE = 5;
                                const totalGroupTasks = group.tasks.length;
                                const totalGroupPages = Math.max(1, Math.ceil(totalGroupTasks / ITEMS_PER_PAGE));
                                const currentGroupPage = Math.min(Math.max(1, groupPages[group.id] || 1), totalGroupPages);
                                const startIdx = (currentGroupPage - 1) * ITEMS_PER_PAGE;
                                const endIdx = Math.min(startIdx + ITEMS_PER_PAGE, totalGroupTasks);
                                const paginatedGroupTasks = group.tasks.slice(startIdx, endIdx);

                                return (
                                    <div
                                        key={group.id}
                                        className="rounded-3xl border border-slate-200 bg-white shadow-xs overflow-hidden dark:border-slate-800 dark:bg-slate-900 transition-all"
                                    >
                                        {/* Group Accordion Header */}
                                        <div
                                            onClick={() => toggleGroup(group.id)}
                                            className="flex flex-col lg:flex-row lg:items-center lg:justify-between p-5 bg-slate-50/80 hover:bg-slate-100/70 dark:bg-slate-800/40 dark:hover:bg-slate-800/70 cursor-pointer select-none transition gap-4"
                                        >
                                            <div className="flex items-center gap-3 flex-wrap">
                                                <span className="text-base font-extrabold text-slate-900 dark:text-white">
                                                    ⏱️ {group.categoryName || group.title}
                                                </span>
                                                <span className="inline-flex items-center justify-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 leading-none">
                                                    {group.duration ? `${group.duration} Hours` : 'Standard'}
                                                </span>
                                                {renderPriorityBadge(group.priorityLevel, group.color)}
                                            </div>

                                            <div className="flex items-center gap-3 flex-wrap">
                                                {/* Group Metrics Pills */}
                                                <span className="inline-flex items-center justify-center rounded-xl bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-2xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                                    Total: <strong className="ml-1">{group.total}</strong>
                                                </span>
                                                <span className="inline-flex items-center justify-center rounded-xl bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/70 dark:border-emerald-800 dark:text-emerald-300">
                                                    Success: <strong className="ml-1">{group.completed}</strong>
                                                </span>
                                                <span className="inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 border border-rose-200 dark:bg-rose-950/70 dark:border-rose-800 dark:text-rose-300">
                                                    Overdue: <strong className="ml-1">{group.overdue}</strong>
                                                </span>
                                                <span className="inline-flex items-center justify-center rounded-xl bg-white px-3 py-1 text-xs font-black shadow-2xs border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                                    Success Rate: <span className={`ml-1 ${rateColor}`}>{group.successRate}%</span>
                                                </span>

                                                {/* Chevron Toggle */}
                                                <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 text-xs font-bold transition-transform">
                                                    {isExpanded ? '▲' : '▼'}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Group Task Table Body */}
                                        {isExpanded && (
                                            <div className="border-t border-slate-200 dark:border-slate-800">
                                                <div className="overflow-x-auto">
                                                    <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                                                        <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/60">
                                                            <tr>
                                                                <th className="px-5 py-3 w-12 text-center">#</th>
                                                                <th className="px-5 py-3">Task Title & Details</th>
                                                                <th className="px-5 py-3">Assignee Employee</th>
                                                                <th className="px-5 py-3">Department</th>
                                                                <th className="px-5 py-3">Due Date</th>
                                                                <th className="px-5 py-3 text-center">Status</th>
                                                                <th className="px-5 py-3 text-center">SLA Compliance</th>
                                                                <th className="px-5 py-3 text-right">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                                            {paginatedGroupTasks.map((task, taskIdx) => (
                                                                <tr key={task.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                                    <td className="px-5 py-3 text-center font-semibold text-slate-400">
                                                                        {startIdx + taskIdx + 1}
                                                                    </td>
                                                                    <td className="px-5 py-3">
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => setSelectedTaskDetail(task)}
                                                                            className="text-left font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 text-sm block"
                                                                        >
                                                                            {task.task}
                                                                        </button>
                                                                        <span className="text-[10.5px] text-slate-400">
                                                                            Created {new Date(task.created_at).toLocaleDateString()}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-5 py-3">
                                                                        <span className="font-bold text-slate-800 dark:text-slate-200">
                                                                            👤 {task.assigned_user?.name || 'Unassigned'}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-5 py-3">
                                                                        <span className="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                                            🏢 {task.assigned_user?.department?.name || task.department?.name || 'N/A'}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-5 py-3 font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                                                        {task.due_date ? task.due_date.replace('T', ' ').slice(0, 16) : '-'}
                                                                    </td>
                                                                    <td className="px-5 py-3 text-center">
                                                                        <span className={`inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-bold border leading-none text-center ${getStatusBadge(task.status)}`}>
                                                                            {task.status?.status || 'Open'}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-5 py-3 text-center">
                                                                        {renderSlaBadge(task)}
                                                                    </td>
                                                                    <td className="px-5 py-3 text-right">
                                                                        <div className="flex items-center justify-end gap-2">
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => setSelectedTaskDetail(task)}
                                                                                className="rounded-xl border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                                            >
                                                                                Details
                                                                            </button>
                                                                            {task.kpi_task_instance_id ? (
                                                                                <span className="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-2.5 py-1.5 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60">
                                                                                    🔒 KPI Lock
                                                                                </span>
                                                                            ) : (
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() => handleCloseTask(task.id)}
                                                                                    className="rounded-xl bg-emerald-600 px-2.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-700"
                                                                                >
                                                                                    Complete
                                                                                </button>
                                                                            )}
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>

                                                {/* Group Table Pagination (5 Items per page) */}
                                                {totalGroupTasks > 0 && (
                                                    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-3 text-xs dark:border-slate-800 dark:bg-slate-800/40">
                                                        <span className="font-semibold text-slate-500 dark:text-slate-400">
                                                            Showing <strong className="text-slate-800 dark:text-slate-200">{startIdx + 1}</strong> to <strong className="text-slate-800 dark:text-slate-200">{endIdx}</strong> of <strong className="text-slate-800 dark:text-slate-200">{totalGroupTasks}</strong> tasks
                                                        </span>

                                                        {totalGroupPages > 1 && (
                                                            <div className="flex items-center gap-1.5">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setGroupPages((prev) => ({ ...prev, [group.id]: currentGroupPage - 1 }))}
                                                                    disabled={currentGroupPage <= 1}
                                                                    className="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-2.5 py-1 font-bold text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                                                >
                                                                    Prev
                                                                </button>

                                                                {Array.from({ length: totalGroupPages }, (_, i) => i + 1).map((pageNum) => (
                                                                    <button
                                                                        key={pageNum}
                                                                        type="button"
                                                                        onClick={() => setGroupPages((prev) => ({ ...prev, [group.id]: pageNum }))}
                                                                        className={`inline-flex h-7 w-7 items-center justify-center rounded-xl text-xs font-bold transition ${
                                                                            currentGroupPage === pageNum
                                                                                ? 'bg-indigo-600 text-white shadow-xs'
                                                                                : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                                                        }`}
                                                                    >
                                                                        {pageNum}
                                                                    </button>
                                                                ))}

                                                                <button
                                                                    type="button"
                                                                    onClick={() => setGroupPages((prev) => ({ ...prev, [group.id]: currentGroupPage + 1 }))}
                                                                    disabled={currentGroupPage >= totalGroupPages}
                                                                    className="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-2.5 py-1 font-bold text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                                                >
                                                                    Next
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-3xl border border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900">
                            <p className="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                No tasks match the selected month and department filters.
                            </p>
                        </div>
                    )}
                </div>

                {/* Department Top Performers Leaderboard */}
                <div className="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50/90 via-white to-slate-50 dark:border-indigo-900/60 dark:from-indigo-950/80 dark:via-indigo-900/40 dark:to-slate-900 p-6 sm:p-8 shadow-xl dark:shadow-indigo-950/30 space-y-6 transition-all">
                    <div className="flex items-center justify-between border-b border-indigo-100 dark:border-indigo-800/60 pb-4">
                        <div>
                            <span className="inline-flex items-center rounded-full bg-indigo-100/80 dark:bg-indigo-900/60 px-3 py-0.5 text-[10px] font-extrabold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider mb-1">
                                Department Top Performers
                            </span>
                            <h2 className="text-xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                                🏆 Top Task Completers & Performers
                            </h2>
                        </div>
                        <span className="text-xs text-slate-500 dark:text-indigo-300 font-semibold">Ranked by Closed Tasks</span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {topPerformers.length > 0 ? (
                            topPerformers.slice(0, 4).map((performer, index) => (
                                <div
                                    key={performer.id}
                                    className="relative flex flex-col justify-between rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm hover:shadow-md dark:border-indigo-800/50 dark:bg-slate-900/80 backdrop-blur-md transition-all"
                                >
                                    <div className="absolute -top-3 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-tr from-amber-400 to-amber-500 text-xs font-black text-slate-950 shadow-md">
                                        #{index + 1}
                                    </div>
                                    <div className="space-y-1.5">
                                        <h3 className="text-sm font-extrabold text-slate-900 dark:text-white truncate">{performer.name}</h3>
                                        <span className="inline-block rounded-lg bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-100 dark:border-indigo-900 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:text-indigo-300">
                                            🏢 {performer.department}
                                        </span>
                                    </div>
                                    <div className="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                        <span className="text-[11px] text-slate-500 dark:text-indigo-300 font-medium">Tasks Closed</span>
                                        <span className="text-xl font-black text-amber-500 dark:text-amber-400">{performer.completed_count}</span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="text-xs text-slate-500 dark:text-indigo-300 italic col-span-full">No completed task performance records yet.</p>
                        )}
                    </div>
                </div>

                {/* Recent Task Activity Feed */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>⚡</span> Recent Active Tasks
                        </h2>
                        <a
                            href="/todo/list"
                            className="text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            View All Tasks →
                        </a>
                    </div>

                    <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <table className="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                            <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-800/50">
                                <tr>
                                    <th className="px-5 py-4">Task Details</th>
                                    <th className="px-5 py-4">Category</th>
                                    <th className="px-5 py-4">Assignee Employee</th>
                                    <th className="px-5 py-4">Department</th>
                                    <th className="px-5 py-4">Due Date</th>
                                    <th className="px-5 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {filteredTasks.slice(0, 8).map((task) => (
                                    <tr key={task.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                        <td className="px-5 py-4">
                                            <button
                                                type="button"
                                                onClick={() => setSelectedTaskDetail(task)}
                                                className="text-left font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 text-sm block"
                                            >
                                                {task.task}
                                            </button>
                                            <span className="text-[11px] text-slate-400">Created {new Date(task.created_at).toLocaleDateString()}</span>
                                        </td>
                                        <td className="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">
                                            {task.due_time?.category?.name || 'General Task'}
                                        </td>
                                        <td className="px-5 py-4 font-bold text-slate-800 dark:text-slate-200">
                                            👤 {task.assigned_user?.name || 'Unassigned'}
                                        </td>
                                        <td className="px-5 py-4">
                                            <span className="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                🏢 {task.assigned_user?.department?.name || task.department?.name || 'N/A'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">
                                            {task.due_date ? task.due_date.replace('T', ' ').slice(0, 16) : '-'}
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedTaskDetail(task)}
                                                    className="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                                >
                                                    View Details
                                                </button>
                                                {task.kpi_task_instance_id ? (
                                                    <span className="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60">
                                                        🔒 Managed by KPI Approval
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleCloseTask(task.id)}
                                                        className="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-700"
                                                    >
                                                        Complete
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* FULL-SCREEN TASK DETAIL VIEW MODAL */}
                <TaskDetailModal
                    task={selectedTaskDetail}
                    isOpen={!!selectedTaskDetail}
                    onClose={() => setSelectedTaskDetail(null)}
                    users={users}
                    statuses={statuses}
                    currentUser={user}
                />

                {/* GLOBAL CREATE TASK REQUEST MODAL */}
                <CreateTaskModal
                    isOpen={isCreateModalOpen}
                    onClose={() => setIsCreateModalOpen(false)}
                    formattedDueTimes={formattedDueTimes}
                    branches={branches}
                    departments={departments}
                    categories={categories}
                    itAdminDepartments={itAdminDepartments}
                    users={users}
                    userBranchId={userBranchId}
                    dueTimes={dueTimes}
                />
            </div>
        </TodoLayout>
    );
}
