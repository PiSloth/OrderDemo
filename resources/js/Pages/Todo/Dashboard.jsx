import React, { useState, useMemo, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import TodoLayout from '../../Layouts/TodoLayout';
import CreateTaskModal from './Components/CreateTaskModal';

export default function Dashboard({
    todoLists = [],
    archivedTasks = [],
    dueTimes = [],
    formattedDueTimes = [],
    statuses = [],
    branches = [],
    departments = [],
    itAdminDepartments = [],
    users = [],
    userBranchId = null,
    topPerformers = [],
}) {
    const { auth = {} } = usePage().props;
    const user = auth?.user;

    // Modals
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedTaskDetail, setSelectedTaskDetail] = useState(null);
    const [commentInput, setCommentInput] = useState('');

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

    // KPI Metrics calculation
    const totalTasks = todoLists.length;

    const completedTasks = useMemo(() => {
        return todoLists.filter((t) => {
            const stName = (t.status?.status || '').toLowerCase();
            return stName.includes('complete') || stName.includes('success') || stName.includes('done');
        }).length;
    }, [todoLists]);

    const openTasks = totalTasks - completedTasks;

    const overdueTasks = useMemo(() => {
        const now = new Date();
        return todoLists.filter((t) => {
            const stName = (t.status?.status || '').toLowerCase();
            const isDone = stName.includes('complete') || stName.includes('success') || stName.includes('done');
            if (isDone || !t.due_date) return false;
            return new Date(t.due_date) < now;
        }).length;
    }, [todoLists]);

    // Department Distribution
    const departmentDistribution = useMemo(() => {
        const counts = {};
        todoLists.forEach((t) => {
            const deptName = t.assigned_user?.department?.name || t.department?.name || 'Unassigned';
            counts[deptName] = (counts[deptName] || 0) + 1;
        });

        const total = totalTasks || 1;
        return Object.entries(counts).map(([name, count]) => ({
            name,
            count,
            percentage: Math.round((count / total) * 100),
        })).sort((a, b) => b.count - a.count);
    }, [todoLists, totalTasks]);

    // Priority Breakdown
    const priorityBreakdown = useMemo(() => {
        const counts = {};
        todoLists.forEach((t) => {
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
    }, [todoLists]);

    // Complete Task Action
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
                    className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-extrabold"
                    style={{
                        backgroundColor: `${colorCode}20`,
                        color: colorCode,
                        borderColor: `${colorCode}50`,
                    }}
                >
                    🔥 {prioLevel}
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-extrabold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                🔥 {prioLevel}
            </span>
        );
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
                            Here is the real-time operational breakdown and task progress metrics for your team.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={() => setIsCreateModalOpen(true)}
                        className="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-xs font-extrabold text-white shadow-lg shadow-indigo-500/25 transition hover:from-indigo-700 hover:to-violet-700 active:scale-95 shrink-0"
                    >
                        + Create Task Request
                    </button>
                </div>

                {/* KPI Metrics Cards Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Card 1: Total Tasks */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-400">Total Tasks</span>
                            <div className="mt-2 text-3xl font-black text-slate-900 dark:text-white">{totalTasks}</div>
                            <span className="mt-1 inline-block text-[11px] text-slate-500">Active operational items</span>
                        </div>
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400 text-xl">
                            📋
                        </div>
                    </div>

                    {/* Card 2: Open / Pending */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-amber-500">Pending / In-Progress</span>
                            <div className="mt-2 text-3xl font-black text-amber-600 dark:text-amber-400">{openTasks}</div>
                            <span className="mt-1 inline-block text-[11px] text-slate-500">Awaiting completion</span>
                        </div>
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/70 dark:text-amber-400 text-xl">
                            ⏳
                        </div>
                    </div>

                    {/* Card 3: Completed Tasks */}
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-emerald-500">Completed</span>
                            <div className="mt-2 text-3xl font-black text-emerald-600 dark:text-emerald-400">{completedTasks}</div>
                            <span className="mt-1 inline-block text-[11px] text-slate-500">Successfully closed</span>
                        </div>
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400 text-xl">
                            ✅
                        </div>
                    </div>

                    {/* Card 4: Overdue Tasks */}
                    <div className="rounded-3xl border border-rose-200 bg-rose-50/40 p-6 shadow-sm dark:border-rose-900/40 dark:bg-rose-950/20 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Overdue Tasks</span>
                            <div className="mt-2 text-3xl font-black text-rose-600 dark:text-rose-400">{overdueTasks}</div>
                            <span className="mt-1 inline-block text-[11px] text-rose-500 font-semibold">Passed due cutoff</span>
                        </div>
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-300 text-xl">
                            🚨
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

                {/* Department Top Performers Leaderboard */}
                <div className="rounded-3xl border border-indigo-200/80 bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl space-y-6">
                    <div className="flex items-center justify-between border-b border-indigo-800/80 pb-4">
                        <div>
                            <span className="inline-flex items-center rounded-full bg-indigo-500/20 px-3 py-0.5 text-[10px] font-extrabold text-indigo-300 uppercase tracking-wider mb-1">
                                Department Top Performers
                            </span>
                            <h2 className="text-xl font-black tracking-tight text-white flex items-center gap-2">
                                🏆 Top Task Completers & Performers
                            </h2>
                        </div>
                        <span className="text-xs text-indigo-300 font-semibold">Ranked by Closed Tasks</span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {topPerformers.length > 0 ? (
                            topPerformers.slice(0, 4).map((performer, index) => (
                                <div
                                    key={performer.id}
                                    className="relative flex flex-col justify-between rounded-2xl border border-indigo-700/60 bg-indigo-900/40 p-5 backdrop-blur-md"
                                >
                                    <div className="absolute -top-3 -right-2 flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-tr from-amber-400 to-amber-500 text-xs font-black text-slate-950 shadow-md">
                                        #{index + 1}
                                    </div>
                                    <div className="space-y-1">
                                        <h3 className="text-sm font-extrabold text-white truncate">{performer.name}</h3>
                                        <span className="inline-block rounded-md bg-indigo-500/30 px-2 py-0.5 text-[10px] font-bold text-indigo-200">
                                            🏢 {performer.department}
                                        </span>
                                    </div>
                                    <div className="mt-4 pt-3 border-t border-indigo-800/60 flex items-center justify-between">
                                        <span className="text-[11px] text-indigo-300 font-medium">Tasks Closed</span>
                                        <span className="text-xl font-black text-amber-400">{performer.completed_count}</span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="text-xs text-indigo-300 italic col-span-full">No completed task performance records yet.</p>
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
                                {todoLists.slice(0, 8).map((task) => (
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

                {/* TASK DETAIL MODAL */}
                {selectedTaskDetail && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
                        <div className="w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-2xl dark:border-slate-800 dark:bg-slate-900 space-y-6 my-auto">
                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                                <div>
                                    <span className="text-xs font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                        Task Detail View
                                    </span>
                                    <h2 className="text-xl font-extrabold text-slate-900 dark:text-white sm:text-2xl mt-1">
                                        {selectedTaskDetail.task}
                                    </h2>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setSelectedTaskDetail(null)}
                                    className="rounded-xl p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                    title="Press ESC to exit"
                                >
                                    ✕
                                </button>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3 text-xs bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl">
                                <div>
                                    <span className="text-slate-400 block font-semibold">Assignee Employee:</span>
                                    <span className="font-extrabold text-slate-800 dark:text-slate-200">👤 {selectedTaskDetail.assigned_user?.name || 'Unassigned'}</span>
                                </div>
                                <div>
                                    <span className="text-slate-400 block font-semibold">Department:</span>
                                    <span className="font-extrabold text-indigo-600 dark:text-indigo-400">🏢 {selectedTaskDetail.assigned_user?.department?.name || 'N/A'}</span>
                                </div>
                                <div>
                                    <span className="text-slate-400 block font-semibold">Due Cutoff Date:</span>
                                    <span className="font-extrabold text-slate-800 dark:text-slate-200">{selectedTaskDetail.due_date ? selectedTaskDetail.due_date.replace('T', ' ') : '-'}</span>
                                </div>
                            </div>

                            {/* Task Comments Feed */}
                            <div className="space-y-3 pt-2">
                                <h3 className="text-xs font-extrabold uppercase tracking-wider text-slate-500">Task Comments</h3>
                                <div className="space-y-2 max-h-56 overflow-y-auto pr-1">
                                    {selectedTaskDetail.comments && selectedTaskDetail.comments.length > 0 ? (
                                        selectedTaskDetail.comments.map((cm) => (
                                            <div key={cm.id} className="rounded-2xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                                                <div className="flex items-center justify-between text-[11px] mb-1">
                                                    <span className="font-bold text-slate-800 dark:text-slate-200">{cm.user?.name || 'User'}</span>
                                                    <span className="text-slate-400">{new Date(cm.created_at).toLocaleString()}</span>
                                                </div>
                                                <p className="text-xs text-slate-700 dark:text-slate-300">{cm.comment}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-xs text-slate-400 italic">No comments posted yet.</p>
                                    )}
                                </div>

                                {/* Post Comment Box */}
                                <form onSubmit={handlePostComment} className="flex items-center gap-2 pt-2">
                                    <input
                                        type="text"
                                        value={commentInput}
                                        onChange={(e) => setCommentInput(e.target.value)}
                                        onKeyDown={handleCommentKeyDown}
                                        placeholder="Write a comment (Press Enter to post)..."
                                        className="flex-1 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                    <button
                                        type="submit"
                                        className="rounded-2xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-700"
                                    >
                                        Post
                                    </button>
                                </form>
                            </div>

                            <div className="flex justify-between items-center pt-4 border-t border-slate-100 dark:border-slate-800">
                                <span className="text-xs text-slate-400">Press <kbd className="rounded bg-slate-200 px-1 py-0.5 text-[10px] font-mono dark:bg-slate-700">ESC</kbd> to exit modal</span>
                                <div className="flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setSelectedTaskDetail(null)}
                                        className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        Close
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleCloseTask(selectedTaskDetail.id)}
                                        className="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700"
                                    >
                                        Complete Task
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* GLOBAL CREATE TASK REQUEST MODAL */}
                <CreateTaskModal
                    isOpen={isCreateModalOpen}
                    onClose={() => setIsCreateModalOpen(false)}
                    formattedDueTimes={formattedDueTimes}
                    branches={branches}
                    departments={departments}
                    itAdminDepartments={itAdminDepartments}
                    users={users}
                    userBranchId={userBranchId}
                    dueTimes={dueTimes}
                />
            </div>
        </TodoLayout>
    );
}
