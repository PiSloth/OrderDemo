import React, { useState, useEffect, useMemo } from 'react';
import { router } from '@inertiajs/react';

export default function TaskDetailModal({
    task,
    isOpen,
    onClose,
    users = [],
    statuses = [],
    currentUser = null,
}) {
    if (!isOpen || !task) return null;

    // States for comments and action step requests
    const [commentText, setCommentText] = useState('');
    const [isActionStep, setIsActionStep] = useState(false);
    const [copiedTaskId, setCopiedTaskId] = useState(null);

    // Inner Modals
    const [showCustomDateModal, setShowCustomDateModal] = useState(false);
    const [customDueDate, setCustomDueDate] = useState('');
    const [customDateReason, setCustomDateReason] = useState('');

    const [showStatusModal, setShowStatusModal] = useState(false);
    const [requestedStatusId, setRequestedStatusId] = useState('');
    const [statusReason, setStatusReason] = useState('');

    const [showResolverModal, setShowResolverModal] = useState(false);
    const [requestedResolverId, setRequestedResolverId] = useState('');
    const [resolverReason, setResolverReason] = useState('');

    const [showNegotiationModal, setShowNegotiationModal] = useState(false);
    const [negotiationCommentId, setNegotiationCommentId] = useState(null);
    const [proposedDate, setProposedDate] = useState('');
    const [negotiationReason, setNegotiationReason] = useState('');

    // Confirmation Modal for Deletion
    const [commentToDelete, setCommentToDelete] = useState(null);

    // Escape Key Listener (closes active sub-modal first, or main modal if no sub-modal is open)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();

                if (commentToDelete) {
                    setCommentToDelete(null);
                } else if (showCustomDateModal) {
                    setShowCustomDateModal(false);
                } else if (showStatusModal) {
                    setShowStatusModal(false);
                } else if (showResolverModal) {
                    setShowResolverModal(false);
                } else if (showNegotiationModal) {
                    setShowNegotiationModal(false);
                } else {
                    onClose();
                }
            }
        };
        window.addEventListener('keydown', handleKeyDown, true);
        return () => window.removeEventListener('keydown', handleKeyDown, true);
    }, [commentToDelete, showCustomDateModal, showStatusModal, showResolverModal, showNegotiationModal, onClose]);

    // Timeline calculations
    const timelineMetrics = useMemo(() => {
        const now = new Date();
        const createdAt = task.created_at ? new Date(task.created_at) : now;
        const dueDate = task.due_date ? new Date(task.due_date) : now;
        const totalDurationHours = task.due_time?.duration || 0;

        const totalScheduledMs = Math.max(1, dueDate.getTime() - createdAt.getTime());
        const elapsedMs = Math.max(0, now.getTime() - createdAt.getTime());

        const progressPercentage = Math.min(100, Math.max(0, (elapsedMs / totalScheduledMs) * 100));

        const msRemaining = dueDate.getTime() - now.getTime();
        const hoursRemaining = Math.round(msRemaining / (1000 * 60 * 60));
        const isOverdue = msRemaining < 0;

        let progressColor = 'bg-blue-500';
        if (isOverdue) {
            progressColor = 'bg-rose-500';
        } else if (progressPercentage > 80) {
            progressColor = 'bg-amber-500';
        } else if (progressPercentage > 60) {
            progressColor = 'bg-orange-500';
        }

        return {
            createdAt,
            dueDate,
            totalDurationHours,
            progressPercentage: Math.round(progressPercentage * 10) / 10,
            hoursRemaining,
            isOverdue,
            progressColor,
        };
    }, [task]);

    // Check pending action step requests by type
    const hasPendingDueDateRequest = useMemo(() => {
        return (task.comments || []).some(
            (c) => c.comment_type === 'action_step' && c.action_status === 'pending' && c.action_data?.type === 'due_date_change'
        );
    }, [task.comments]);

    const hasPendingStatusRequest = useMemo(() => {
        return (task.comments || []).some(
            (c) => c.comment_type === 'action_step' && c.action_status === 'pending' && c.action_data?.type === 'status_change'
        );
    }, [task.comments]);

    const hasPendingResolverRequest = useMemo(() => {
        return (task.comments || []).some(
            (c) => c.comment_type === 'action_step' && c.action_status === 'pending' && c.action_data?.type === 'resolver_change'
        );
    }, [task.comments]);

    // Helper status styling
    const getStatusBadge = (statusObj) => {
        const name = statusObj?.status || 'Open';
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

    // Actions
    const handleCloseTask = () => {
        router.patch(`/todo/tasks/${task.id}/close`, {}, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    const handleArchiveTask = () => {
        router.delete(`/todo/tasks/${task.id}`, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    const handleRestoreTask = () => {
        router.patch(`/todo/tasks/${task.id}/restore`, {}, {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    const handleCopyLink = () => {
        const shareUrl = `${window.location.origin}/todo/list?task_id=${task.id}`;
        navigator.clipboard.writeText(shareUrl);
        setCopiedTaskId(task.id);
        setTimeout(() => setCopiedTaskId(null), 3000);
    };

    // Post Normal / Action Step Comment
    const submitComment = (payload) => {
        router.post(`/todo/tasks/${task.id}/comments`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                setCommentText('');
                setIsActionStep(false);
                setShowCustomDateModal(false);
                setShowStatusModal(false);
                setShowResolverModal(false);
            },
        });
    };

    const handleAddComment = (e) => {
        if (e) e.preventDefault();
        if (!commentText.trim()) return;

        submitComment({
            comment: commentText.trim(),
            comment_type: isActionStep ? 'action_step' : 'normal',
        });
    };

    // Quick 24h Due Date Extension Request
    const handleRequest24hExtension = () => {
        const currentDue = task.due_date ? new Date(task.due_date) : new Date();
        const extended = new Date(currentDue.getTime() + 24 * 60 * 60 * 1000);
        const isoStr = new Date(extended.getTime() - extended.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);

        submitComment({
            comment: `Requested 24-Hour Due Date Extension (New Target: ${isoStr.replace('T', ' ')})`,
            comment_type: 'action_step',
            action_data: {
                type: 'due_date_change',
                original_due_date: task.due_date,
                new_due_date: isoStr,
                reason: 'Requested 24-hour extension',
            },
        });
    };

    // Custom Due Date Request Submit
    const handleCustomDueDateSubmit = (e) => {
        e.preventDefault();
        if (!customDueDate) return;

        submitComment({
            comment: `Requested Custom Due Date Change to ${customDueDate.replace('T', ' ')}${customDateReason ? `. Reason: ${customDateReason}` : ''}`,
            comment_type: 'action_step',
            action_data: {
                type: 'due_date_change',
                original_due_date: task.due_date,
                new_due_date: customDueDate,
                reason: customDateReason,
            },
        });
    };

    // Status Change Request Submit
    const handleStatusRequestSubmit = (e) => {
        e.preventDefault();
        if (!requestedStatusId) return;

        const targetStatusObj = statuses.find((s) => String(s.id) === String(requestedStatusId));

        submitComment({
            comment: `Requested Status Change to "${targetStatusObj?.status || 'New Status'}"${statusReason ? `. Reason: ${statusReason}` : ''}`,
            comment_type: 'action_step',
            action_data: {
                type: 'status_change',
                original_status_id: task.todo_status_id,
                new_status_id: requestedStatusId,
                new_status_name: targetStatusObj?.status,
                reason: statusReason,
            },
        });
    };

    // Resolver Change Request Submit
    const handleResolverRequestSubmit = (e) => {
        e.preventDefault();
        if (!requestedResolverId) return;

        const targetUserObj = users.find((u) => String(u.id) === String(requestedResolverId));

        submitComment({
            comment: `Requested Resolver Change to ${targetUserObj?.name || 'User'}${resolverReason ? `. Reason: ${resolverReason}` : ''}`,
            comment_type: 'action_step',
            action_data: {
                type: 'resolver_change',
                original_assigned_user_id: task.assigned_user_id,
                new_assigned_user_id: requestedResolverId,
                new_assigned_user_name: targetUserObj?.name,
                reason: resolverReason,
            },
        });
    };

    const isSuperUser = currentUser?.is_super_user || currentUser?.role === 'admin' || currentUser?.role === 'super_user';

    // Delete comment action trigger
    const handleDeleteComment = (commentId) => {
        setCommentToDelete(commentId);
    };

    const confirmDeleteComment = () => {
        if (!commentToDelete) return;
        router.post(`/todo/task-comments/${commentToDelete}/delete`, {}, {
            preserveScroll: true,
            onSuccess: () => setCommentToDelete(null),
        });
    };

    // Respond to Action Step (Accept / Reject)
    const handleRespondActionStep = (commentId, action) => {
        router.post(`/todo/task-comments/${commentId}/respond`, { action }, {
            preserveScroll: true,
        });
    };

    // Counter Offer Submit
    const handleCounterOfferSubmit = (e) => {
        e.preventDefault();
        if (!negotiationCommentId || !proposedDate) return;

        router.post(`/todo/task-comments/${negotiationCommentId}/respond`, {
            action: 'counter_offer',
            proposed_date: proposedDate,
            reason: negotiationReason,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setShowNegotiationModal(false);
                setNegotiationCommentId(null);
                setProposedDate('');
                setNegotiationReason('');
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-white dark:bg-slate-900 overflow-y-auto p-4 sm:p-6 lg:p-10 transition-all">
            <div className="mx-auto w-full max-w-6xl space-y-6 flex-1 flex flex-col justify-between">
                <div className="space-y-6">
                    {/* Header Bar */}
                    <div className="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-5">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-3">
                                <span className="rounded-lg bg-indigo-100 px-3 py-1 text-xs font-mono font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                    #TASK-{task.id}
                                </span>
                                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {task.due_time?.category?.name || 'Task Category'}
                                </span>
                                <span className={`rounded-full border px-3 py-1 text-xs font-bold ${getStatusBadge(task.status)}`}>
                                    {task.status?.status || 'Open'}
                                </span>
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300">
                                    <span className="relative flex h-2 w-2">
                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Live Sync Active
                                </span>
                            </div>
                            <h1 className="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                {task.task}
                            </h1>
                        </div>

                        <button
                            type="button"
                            onClick={onClose}
                            className="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            title="Press ESC to exit"
                        >
                            <kbd className="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-mono dark:bg-slate-700">ESC</kbd> Exit Fullscreen
                        </button>
                    </div>

                    {/* Timeline Progress Bar */}
                    <div className="rounded-3xl border border-slate-200 bg-slate-50/90 p-5 dark:border-slate-800 dark:bg-slate-800/40 space-y-3">
                        <div className="flex flex-wrap items-center justify-between gap-2 text-xs font-bold">
                            <span className="text-slate-700 dark:text-slate-300">⏱️ Task Timeline & SLA Progress</span>
                            <div className="flex items-center gap-2">
                                <span className="text-slate-500">{timelineMetrics.totalDurationHours}h scheduled</span>
                                <span className="text-slate-300">•</span>
                                {timelineMetrics.isOverdue ? (
                                    <span className="text-rose-600 dark:text-rose-400 font-extrabold">
                                        🚨 {Math.abs(timelineMetrics.hoursRemaining)}h Overdue
                                    </span>
                                ) : (
                                    <span className="text-emerald-600 dark:text-emerald-400 font-extrabold">
                                        ⏳ {timelineMetrics.hoursRemaining}h Remaining
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Progress Bar Track */}
                        <div className="h-3.5 w-full rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                            <div
                                className={`h-full ${timelineMetrics.progressColor} transition-all duration-500 rounded-full`}
                                style={{ width: `${timelineMetrics.progressPercentage}%` }}
                            />
                        </div>

                        <div className="flex justify-between text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            <span>Started: {timelineMetrics.createdAt.toLocaleString()}</span>
                            <span className="font-extrabold text-slate-700 dark:text-slate-300">{timelineMetrics.progressPercentage}% Elapsed</span>
                            <span>Due: {timelineMetrics.dueDate.toLocaleString()}</span>
                        </div>
                    </div>

                    {/* Task Metadata Cards */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {/* Priority & Job Title */}
                        <div className="rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-800/40">
                            <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Priority & Job Title</p>
                            <p className="mt-2 text-base font-bold text-slate-900 dark:text-white">
                                {task.due_time?.priority?.level || 'Normal Priority'}
                            </p>
                            <p className="text-xs text-slate-500 mt-1">Duration: {task.due_time?.duration || '-'} hours</p>
                        </div>

                        {/* Request By Branch & Dept */}
                        <div className="rounded-3xl border border-blue-200 bg-blue-50/50 p-5 dark:border-blue-900/40 dark:bg-blue-950/20">
                            <p className="text-xs font-bold uppercase tracking-wider text-blue-800 dark:text-blue-300">Request By (Branch)</p>
                            <p className="mt-2 text-base font-bold text-slate-900 dark:text-white">
                                {task.requested_by_branch?.name || 'No Branch'}
                            </p>
                            <p className="text-xs text-slate-500 mt-1">Department: {task.department?.name || task.assigned_user?.department?.name || '-'}</p>
                        </div>

                        {/* Assignee User */}
                        <div className="rounded-3xl border border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                            <p className="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Assigned To (တာဝန်ခံ)</p>
                            <p className="mt-2 text-base font-bold text-slate-900 dark:text-white">
                                {task.assigned_user?.name || 'Unassigned'}
                            </p>
                            <p className="text-xs text-slate-500 mt-1">{task.assigned_user?.email || '-'}</p>
                        </div>
                    </div>

                    {/* Detailed Info Grid */}
                    <div className="rounded-3xl border border-slate-200 bg-slate-50/50 p-6 dark:border-slate-800 dark:bg-slate-800/30 space-y-4">
                        <h4 className="text-xs font-extrabold uppercase tracking-wider text-slate-400">Task Timeline & Ownership</h4>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm text-slate-700 dark:text-slate-300">
                            <div>
                                <span className="text-slate-400 block text-xs">Created By:</span>
                                <span className="font-semibold text-slate-900 dark:text-white">
                                    {task.created_by_user?.name || 'System'} ({task.created_by_user?.email || '-'})
                                </span>
                            </div>

                            <div>
                                <span className="text-slate-400 block text-xs">Created Date & Time:</span>
                                <span className="font-semibold text-slate-900 dark:text-white">
                                    {task.created_at ? new Date(task.created_at).toLocaleString() : '-'}
                                </span>
                            </div>

                            <div>
                                <span className="text-slate-400 block text-xs">Cutoff Due Date:</span>
                                <span className={`font-bold ${timelineMetrics.isOverdue && !task.status?.status?.toLowerCase().includes('complete') ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'}`}>
                                    {task.due_date ? task.due_date.replace('T', ' ') : 'N/A'}
                                    {timelineMetrics.isOverdue && !task.status?.status?.toLowerCase().includes('complete') && ' (OVERDUE)'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Task Comments & Action Steps Log */}
                    <div className="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <h4 className="text-base font-bold text-slate-900 dark:text-white">
                            Discussion & Action Step Log ({task.comments ? task.comments.length : 0})
                        </h4>

                        {/* Comments & Action Logs Feed */}
                        <div className="space-y-3 max-h-72 overflow-y-auto pr-2">
                            {task.comments && task.comments.length > 0 ? (
                                task.comments.map((c) => {
                                    const isAction = c.comment_type === 'action_step';
                                    const actionType = c.action_data?.type;
                                    const isPending = c.action_status === 'pending';
                                    const isAccepted = c.action_status === 'accepted';
                                    const isRejected = c.action_status === 'rejected';

                                    return (
                                        <div
                                            key={c.id}
                                            className={`rounded-2xl p-4 text-xs space-y-2 border transition ${
                                                isAction
                                                    ? 'border-indigo-200 bg-indigo-50/70 dark:border-indigo-900/60 dark:bg-indigo-950/40'
                                                    : 'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between font-bold text-slate-800 dark:text-slate-200">
                                                <div className="flex items-center gap-2">
                                                    <span>{c.user?.name || 'User'}</span>
                                                    {c.user?.department?.name && (
                                                        <span className="rounded-md bg-slate-200 px-1.5 py-0.5 text-[10px] text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                                            🏢 {c.user.department.name}
                                                        </span>
                                                    )}
                                                    {isAction && (
                                                        <span className="rounded-md bg-indigo-600 px-2 py-0.5 text-[10px] font-extrabold text-white">
                                                            ⚡ Action Step Request
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className="text-[11px] text-slate-400 font-normal">
                                                        {c.created_at ? new Date(c.created_at).toLocaleString() : ''}
                                                    </span>
                                                    {(isSuperUser || (currentUser && currentUser.id === c.user_id)) && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteComment(c.id)}
                                                            className="text-[11px] font-semibold text-rose-500 hover:text-rose-700 dark:hover:text-rose-400"
                                                            title="Delete comment"
                                                        >
                                                            🗑️ Delete
                                                        </button>
                                                    )}
                                                </div>
                                            </div>

                                            <p className="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{c.comment}</p>

                                            {/* Action Step Request Details & Response Controls */}
                                            {isAction && (
                                                <div className="mt-2 pt-2 border-t border-indigo-200/80 dark:border-indigo-900/80 flex flex-wrap items-center justify-between gap-3">
                                                    <div className="flex items-center gap-2 text-[11px] font-bold">
                                                        <span className="text-indigo-700 dark:text-indigo-300">
                                                            Request Type: {actionType ? actionType.replace('_', ' ').toUpperCase() : 'ACTION'}
                                                        </span>
                                                        {isPending && <span className="rounded-full bg-amber-100 px-2 py-0.5 text-amber-800 font-extrabold">⏳ Pending Response</span>}
                                                        {isAccepted && <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800 font-extrabold">✅ Accepted</span>}
                                                        {isRejected && <span className="rounded-full bg-rose-100 px-2 py-0.5 text-rose-800 font-extrabold">❌ Rejected</span>}
                                                        {isSuperUser && isPending && (
                                                            <span className="rounded-full bg-purple-100 px-2 py-0.5 text-purple-800 dark:bg-purple-950 dark:text-purple-300 font-extrabold">
                                                                👑 Super User Override Available
                                                            </span>
                                                        )}
                                                    </div>

                                                    {/* Accept / Reject / Counter-Offer Actions for Pending Requests */}
                                                    {isPending && (
                                                        <div className="flex items-center gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleRespondActionStep(c.id, 'accept')}
                                                                className="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-extrabold text-white shadow-xs hover:bg-emerald-700"
                                                            >
                                                                ✓ Accept
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleRespondActionStep(c.id, 'reject')}
                                                                className="rounded-lg bg-rose-600 px-3 py-1 text-xs font-extrabold text-white shadow-xs hover:bg-rose-700"
                                                            >
                                                                ✕ Reject
                                                            </button>
                                                            {actionType === 'due_date_change' && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        setNegotiationCommentId(c.id);
                                                                        setProposedDate(c.action_data?.new_due_date || '');
                                                                        setShowNegotiationModal(true);
                                                                    }}
                                                                    className="rounded-lg bg-amber-600 px-3 py-1 text-xs font-extrabold text-white shadow-xs hover:bg-amber-700"
                                                                >
                                                                    💬 Propose Counter-Offer
                                                                </button>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })
                            ) : (
                                <p className="text-xs text-slate-400 italic py-4">No comments or action logs recorded yet.</p>
                            )}
                        </div>

                        {/* Comment & Action Step Submission Controls */}
                        <div className="space-y-3 pt-2">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <label className="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={isActionStep}
                                        onChange={(e) => setIsActionStep(e.target.checked)}
                                        className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span>⚡ Enable Action Step Request Mode</span>
                                </label>

                                {/* Action Step Preset Buttons */}
                                <div className="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={handleRequest24hExtension}
                                        disabled={hasPendingDueDateRequest}
                                        className={`rounded-xl border px-3 py-1.5 text-xs font-bold transition ${
                                            hasPendingDueDateRequest
                                                ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'
                                                : 'bg-amber-500/15 border-amber-300/80 text-amber-700 dark:text-amber-300 hover:bg-amber-500/25'
                                        }`}
                                        title={hasPendingDueDateRequest ? "A due date change request is already pending for this task" : "Request a 24-hour extension on task due date"}
                                    >
                                        ⚡ Request 24h Extension {hasPendingDueDateRequest && '(Pending)'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (hasPendingDueDateRequest) return;
                                            setCustomDueDate(task.due_date ? task.due_date.slice(0, 16) : '');
                                            setShowCustomDateModal(true);
                                        }}
                                        disabled={hasPendingDueDateRequest}
                                        className={`rounded-xl border px-3 py-1.5 text-xs font-bold transition ${
                                            hasPendingDueDateRequest
                                                ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'
                                                : 'bg-blue-500/15 border-blue-300/80 text-blue-700 dark:text-blue-300 hover:bg-blue-500/25'
                                        }`}
                                        title={hasPendingDueDateRequest ? "A due date change request is already pending for this task" : "Request custom due date"}
                                    >
                                        📅 Custom Due Date {hasPendingDueDateRequest && '(Pending)'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (hasPendingStatusRequest) return;
                                            setRequestedStatusId(task.todo_status_id || '');
                                            setShowStatusModal(true);
                                        }}
                                        disabled={hasPendingStatusRequest}
                                        className={`rounded-xl border px-3 py-1.5 text-xs font-bold transition ${
                                            hasPendingStatusRequest
                                                ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'
                                                : 'bg-emerald-500/15 border-emerald-300/80 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/25'
                                        }`}
                                        title={hasPendingStatusRequest ? "A status change request is already pending for this task" : "Request status change"}
                                    >
                                        🔄 Request Status Change {hasPendingStatusRequest && '(Pending)'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (hasPendingResolverRequest) return;
                                            setRequestedResolverId(task.assigned_user_id || '');
                                            setShowResolverModal(true);
                                        }}
                                        disabled={hasPendingResolverRequest}
                                        className={`rounded-xl border px-3 py-1.5 text-xs font-bold transition ${
                                            hasPendingResolverRequest
                                                ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'
                                                : 'bg-violet-500/15 border-violet-300/80 text-violet-700 dark:text-violet-300 hover:bg-violet-500/25'
                                        }`}
                                        title={hasPendingResolverRequest ? "A resolver change request is already pending for this task" : "Request resolver change"}
                                    >
                                        👤 Request Resolver {hasPendingResolverRequest && '(Pending)'}
                                    </button>
                                </div>
                            </div>

                            {/* Textarea Input */}
                            <textarea
                                value={commentText}
                                onChange={(e) => setCommentText(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        handleAddComment();
                                    }
                                }}
                                placeholder={isActionStep ? "Describe your action step request... (Press Enter to post)" : "Write a comment... (Press Enter to post, Shift+Enter for new line)"}
                                rows="2"
                                className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                            <div className="flex items-center justify-between text-xs text-slate-400">
                                <span>Press <kbd className="rounded bg-slate-200 px-1 py-0.5 text-[10px] font-mono dark:bg-slate-700">Enter</kbd> to post comment</span>
                                <button
                                    type="button"
                                    onClick={handleAddComment}
                                    className="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                >
                                    Post Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer Controls */}
                <div className="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        onClick={handleCopyLink}
                        className="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-semibold"
                    >
                        {copiedTaskId === task.id ? 'Copied Share Link!' : '🔗 Copy Task Share Link'}
                    </button>

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-xl border border-slate-300 px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Close (ESC)
                        </button>
                        {!task.deleted_at ? (
                            <>
                                {task.kpi_task_instance_id ? (
                                    <span className="rounded-xl bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60">
                                        🔒 Managed by KPI Approval
                                    </span>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={handleCloseTask}
                                        className="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700"
                                    >
                                        Mark Completed / Close
                                    </button>
                                )}
                                {task.todo_status_id && (
                                    <button
                                        type="button"
                                        onClick={handleArchiveTask}
                                        className="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        Archive
                                    </button>
                                )}
                            </>
                        ) : (
                            <button
                                type="button"
                                onClick={handleRestoreTask}
                                className="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-700"
                            >
                                Restore Task
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {/* Custom Due Date Modal */}
            {showCustomDateModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            e.stopPropagation();
                            setShowCustomDateModal(false);
                        }
                    }}
                >
                    <div
                        className="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900 space-y-4 border border-slate-200 dark:border-slate-800 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <h3 className="text-base font-extrabold text-slate-900 dark:text-white">📅 Request Custom Due Date</h3>
                        <form onSubmit={handleCustomDueDateSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Requested Due Date & Time</label>
                                <input
                                    type="datetime-local"
                                    value={customDueDate}
                                    onChange={(e) => setCustomDueDate(e.target.value)}
                                    required
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Reason for Extension (Optional)</label>
                                <textarea
                                    value={customDateReason}
                                    onChange={(e) => setCustomDateReason(e.target.value)}
                                    rows="3"
                                    placeholder="Explain why a due date extension is needed..."
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowCustomDateModal(false)}
                                    className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white hover:bg-indigo-700"
                                >
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Status Change Request Modal */}
            {showStatusModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            e.stopPropagation();
                            setShowStatusModal(false);
                        }
                    }}
                >
                    <div
                        className="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900 space-y-4 border border-slate-200 dark:border-slate-800 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <h3 className="text-base font-extrabold text-slate-900 dark:text-white">🔄 Request Status Change</h3>
                        <form onSubmit={handleStatusRequestSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Requested Status</label>
                                <select
                                    value={requestedStatusId}
                                    onChange={(e) => setRequestedStatusId(e.target.value)}
                                    required
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                >
                                    <option value="">-- Select Status --</option>
                                    {statuses.map((st) => (
                                        <option key={st.id} value={st.id}>{st.status}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Reason for Request (Optional)</label>
                                <textarea
                                    value={statusReason}
                                    onChange={(e) => setStatusReason(e.target.value)}
                                    rows="3"
                                    placeholder="Explain why status change is required..."
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowStatusModal(false)}
                                    className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700"
                                >
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Resolver Change Request Modal */}
            {showResolverModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            e.stopPropagation();
                            setShowResolverModal(false);
                        }
                    }}
                >
                    <div
                        className="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900 space-y-4 border border-slate-200 dark:border-slate-800 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <h3 className="text-base font-extrabold text-slate-900 dark:text-white">👤 Request Resolver Change</h3>
                        <form onSubmit={handleResolverRequestSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Requested Resolver / Assignee</label>
                                <select
                                    value={requestedResolverId}
                                    onChange={(e) => setRequestedResolverId(e.target.value)}
                                    required
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                >
                                    <option value="">-- Select Assignee --</option>
                                    {users.map((u) => (
                                        <option key={u.id} value={u.id}>{u.name} ({u.email})</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Reason for Re-assignment (Optional)</label>
                                <textarea
                                    value={resolverReason}
                                    onChange={(e) => setResolverReason(e.target.value)}
                                    rows="3"
                                    placeholder="Explain why resolver change is needed..."
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowResolverModal(false)}
                                    className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="rounded-xl bg-violet-600 px-5 py-2 text-xs font-bold text-white hover:bg-violet-700"
                                >
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Negotiation Counter-Offer Modal */}
            {showNegotiationModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            e.stopPropagation();
                            setShowNegotiationModal(false);
                        }
                    }}
                >
                    <div
                        className="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900 space-y-4 border border-slate-200 dark:border-slate-800 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <h3 className="text-base font-extrabold text-slate-900 dark:text-white">💬 Propose Counter-Offer Date</h3>
                        <form onSubmit={handleCounterOfferSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Proposed Counter-Offer Due Date</label>
                                <input
                                    type="datetime-local"
                                    value={proposedDate}
                                    onChange={(e) => setProposedDate(e.target.value)}
                                    required
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Counter-Offer Reason</label>
                                <textarea
                                    value={negotiationReason}
                                    onChange={(e) => setNegotiationReason(e.target.value)}
                                    rows="3"
                                    placeholder="Explain why you're proposing this counter date..."
                                    className="w-full rounded-2xl border border-slate-300 bg-white p-3 text-xs text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                />
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowNegotiationModal(false)}
                                    className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="rounded-xl bg-amber-600 px-5 py-2 text-xs font-bold text-white hover:bg-amber-700"
                                >
                                    Propose Counter-Offer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* React Confirmation Alert Modal for Comment Deletion */}
            {commentToDelete && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            e.stopPropagation();
                            setCommentToDelete(null);
                        }
                    }}
                >
                    <div
                        className="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900 space-y-4 border border-slate-200 dark:border-slate-800 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center gap-3 text-rose-600 dark:text-rose-400">
                            <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-100 dark:bg-rose-950 text-xl font-bold">
                                🗑️
                            </span>
                            <div>
                                <h3 className="text-base font-extrabold text-slate-900 dark:text-white">Delete Comment Confirmation</h3>
                                <p className="text-xs text-slate-500">This action cannot be undone.</p>
                            </div>
                        </div>

                        <p className="text-xs text-slate-700 dark:text-slate-300">
                            Are you sure you want to permanently delete this comment/request log entry?
                        </p>

                        <div className="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => setCommentToDelete(null)}
                                className="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                No, Cancel
                            </button>
                            <button
                                type="button"
                                onClick={confirmDeleteComment}
                                className="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700"
                            >
                                Yes, Delete Comment
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
