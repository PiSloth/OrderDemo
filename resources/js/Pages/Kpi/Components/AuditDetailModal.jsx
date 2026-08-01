import React, { useEffect, useState, useCallback } from 'react';
import { useForm, router } from '@inertiajs/react';
import { createPortal } from 'react-dom';
import PhotoCarouselModal from './PhotoCarouselModal';

// Robust image tile: shows skeleton while loading, retry on error
function EvidenceImage({ src, alt, onClick }) {
    const [status, setStatus] = useState('loading'); // loading | loaded | error
    const [retryKey, setRetryKey] = useState(0);

    // Reset whenever the src changes (new marker)
    useEffect(() => { setStatus('loading'); setRetryKey(0); }, [src]);

    const handleLoad = () => setStatus('loaded');
    const handleError = () => setStatus('error');
    const handleRetry = (e) => { e.stopPropagation(); setStatus('loading'); setRetryKey((k) => k + 1); };

    const bustedSrc = retryKey > 0 ? `${src}${src.includes('?') ? '&' : '?'}_r=${retryKey}` : src;

    return (
        <button
            type="button"
            onClick={onClick}
            className="group relative aspect-video rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-xs"
        >
            {/* Skeleton shown while loading */}
            {status === 'loading' && (
                <div className="absolute inset-0 animate-pulse bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                    <svg className="w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            )}

            {/* Error state */}
            {status === 'error' && (
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-rose-50 dark:bg-rose-950/30">
                    <svg className="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span className="text-[10px] text-rose-500 font-medium">Failed to load</span>
                    <button
                        type="button"
                        onClick={handleRetry}
                        className="text-[10px] px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 hover:bg-rose-200 transition font-semibold cursor-pointer"
                    >
                        Retry
                    </button>
                </div>
            )}

            {/* Actual image — always mounted so browser can load; hidden via opacity until ready */}
            <img
                key={bustedSrc}
                src={bustedSrc}
                alt={alt}
                loading="eager"
                decoding="async"
                onLoad={handleLoad}
                onError={handleError}
                className={`w-full h-full object-cover group-hover:scale-105 transition duration-200 ${
                    status === 'loaded' ? 'opacity-100' : 'opacity-0'
                }`}
            />

            {/* Hover overlay */}
            {status === 'loaded' && (
                <div className="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/30 transition flex items-center justify-center">
                    <svg className="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                    </svg>
                </div>
            )}
        </button>
    );
}

export default function AuditDetailModal({
    isOpen,
    onClose,
    selectedMarker,
    isSuperAdmin,
    canApproveTasks = false,
    authUserId = null,
    currentIndex = null,
    totalCount = 0,
    onNavigate,
    markerTypeFilter = null,
    markerTypeCounts = {},
    onTypeFilterChange,
}) {
    const [selectedPhotoIndex, setSelectedPhotoIndex] = useState(null);
    const [rejectRemark, setRejectRemark] = useState('');
    const [rejectError, setRejectError] = useState('');
    const [actionProcessing, setActionProcessing] = useState(false);

    const instance = selectedMarker?.instance || {};
    const template = instance.template || selectedMarker?.template || {};
    const latestSubmission = instance.latest_submission || null;
    const images = latestSubmission?.images || [];
    const approvalSteps = latestSubmission?.approval_steps || [];

    // Find the pending step this user needs to act on
    const myPendingStep = canApproveTasks
        ? approvalSteps.find(
              (s) => s.status === 'pending' && String(s.approver_user_id) === String(authUserId)
          )
        : null;

    const isWaitingApproval = ['waiting_first_approval', 'waiting_final_approval'].includes(instance.status);
    const showApproveBar = !!(myPendingStep && isWaitingApproval);

    const { data, setData, post, processing, errors } = useForm({
        status: instance.status || 'pending',
        failure_reason: instance.failure_reason || '',
    });

    // Reset state when selectedMarker or instance status/reason changes
    useEffect(() => {
        if (selectedMarker) {
            setData({
                status: instance.status || 'pending',
                failure_reason: instance.failure_reason || '',
            });
            setSelectedPhotoIndex(null);
            setRejectRemark('');
            setRejectError('');
        }
    }, [selectedMarker, instance.status, instance.failure_reason, instance.id]);

    // Body scroll lock
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => { document.body.style.overflow = ''; };
    }, [isOpen]);

    // Keyboard: Escape closes, arrows navigate
    useEffect(() => {
        if (!isOpen) return;
        const handleKeyDown = (e) => {
            if (selectedPhotoIndex !== null) return; // let carousel handle keys
            if (e.key === 'Escape') {
                e.preventDefault();
                document.body.style.overflow = '';
                onClose();
            }
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                onNavigate?.(1);
            }
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                onNavigate?.(-1);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, selectedPhotoIndex, onClose, onNavigate]);

    const handleSubmitOverride = (e) => {
        e.preventDefault();
        post(`/kpi/audit/instance/${instance.id}/status`, { preserveScroll: true });
    };

    const handleApprove = () => {
        if (!myPendingStep) return;
        setActionProcessing(true);
        router.post(`/kpi/audit/step/${myPendingStep.id}/approve`, { remark: '' }, {
            preserveScroll: true,
            onSuccess: () => { setActionProcessing(false); },
            onError: () => setActionProcessing(false),
        });
    };

    const handleReject = () => {
        if (!myPendingStep) return;
        if (!rejectRemark.trim()) { setRejectError('Remark is required to reject.'); return; }
        setRejectError('');
        setActionProcessing(true);
        router.post(`/kpi/audit/step/${myPendingStep.id}/reject`, { remark: rejectRemark }, {
            preserveScroll: true,
            onSuccess: () => { setActionProcessing(false); setRejectError(''); },
            onError: (errs) => { setActionProcessing(false); setRejectError(errs?.remark || 'Failed to reject.'); },
        });
    };

    if (!isOpen || !selectedMarker || typeof document === 'undefined') return null;

    const hasPrev = currentIndex !== null && currentIndex > 0;
    const hasNext = currentIndex !== null && currentIndex < totalCount - 1;

    return createPortal(
        <>
            {/* Main Inspection Modal */}
            <div
                style={{ position: 'fixed', inset: 0, zIndex: 9100, backgroundColor: 'rgba(2,6,23,0.65)' }}
                className={`overflow-y-auto flex items-start justify-center py-6 px-4 backdrop-blur-sm ${selectedPhotoIndex !== null ? 'hidden' : ''}`}
                onClick={() => { document.body.style.overflow = ''; onClose(); }}
            >
                <div
                    style={{ position: 'relative', zIndex: 9101 }}
                    className="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col my-auto"
                    onClick={(e) => e.stopPropagation()}
                >

                    {/* ── Status type filter pills ── */}
                    {Object.keys(markerTypeCounts).length > 0 && (() => {
                        const typeMeta = {
                            pending:  { label: 'Needs Approval', color: 'amber',   icon: '•' },
                            approved: { label: 'Approved',       color: 'emerald',  icon: '✓' },
                            failed:   { label: 'Failed',         color: 'rose',     icon: '✕' },
                            rejected: { label: 'Rejected',       color: 'orange',   icon: '!' },
                            overdue:  { label: 'Overdue',        color: 'rose',     icon: '⚠' },
                        };
                        const colorMap = {
                            amber:   { active: 'bg-amber-500 text-white border-amber-500',   inactive: 'border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30' },
                            emerald: { active: 'bg-emerald-500 text-white border-emerald-500', inactive: 'border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30' },
                            rose:    { active: 'bg-rose-500 text-white border-rose-500',     inactive: 'border-rose-200 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30' },
                            orange:  { active: 'bg-orange-500 text-white border-orange-500', inactive: 'border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-950/30' },
                        };
                        return (
                            <div className="px-5 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center gap-2 flex-wrap flex-shrink-0">
                                <span className="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mr-1">View:</span>
                                {Object.entries(markerTypeCounts).map(([type, count]) => {
                                    const meta = typeMeta[type] || { label: type, color: 'amber', icon: '•' };
                                    const colors = colorMap[meta.color] || colorMap.amber;
                                    const isActive = markerTypeFilter === type;
                                    return (
                                        <button
                                            key={type}
                                            type="button"
                                            onClick={() => onTypeFilterChange?.(type)}
                                            className={`flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border transition cursor-pointer ${isActive ? colors.active : `bg-white dark:bg-slate-900 ${colors.inactive}`}`}
                                        >
                                            <span>{meta.icon}</span>
                                            <span>{meta.label}</span>
                                            <span className={`px-1.5 py-0.5 rounded-full text-[10px] font-bold ${isActive ? 'bg-white/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'}`}>
                                                {count}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        );
                    })()}

                    {/* ── Header ── */}
                    <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50 flex-shrink-0">
                        <div className="min-w-0 flex-1 mr-4">
                            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span className="truncate">{template.title || 'Task Inspection'}</span>
                                <span className={`px-2.5 py-0.5 text-xs font-semibold rounded-full shrink-0 ${selectedMarker.classes}`}>
                                    {selectedMarker.label}
                                </span>
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2 flex-wrap">
                                {template.group?.name && (
                                    <span className="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px]">
                                        {template.group.name}
                                    </span>
                                )}
                                <span>ID #{instance.id} • Date: {instance.task_date || 'N/A'}</span>
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            {/* Prev / Next navigation */}
                            {totalCount > 1 && (
                                <div className="flex items-center gap-1 mr-2">
                                    <button
                                        type="button"
                                        disabled={!hasPrev}
                                        onClick={() => onNavigate?.(-1)}
                                        className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
                                        title="Previous task (←)"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <span className="text-xs font-mono text-slate-500 dark:text-slate-400 min-w-[48px] text-center">
                                        {(currentIndex ?? 0) + 1} / {totalCount}
                                    </span>
                                    <button
                                        type="button"
                                        disabled={!hasNext}
                                        onClick={() => onNavigate?.(1)}
                                        className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
                                        title="Next task (→)"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            )}

                            <button
                                onClick={() => { document.body.style.overflow = ''; onClose(); }}
                                className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition cursor-pointer"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* ── Body ── */}
                    <div className="p-6 space-y-6">
                        {/* Template Description & Guidelines */}
                        {(template.description || template.guideline) && (
                            <div className="p-4 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 space-y-3">
                                {template.description && (
                                    <div>
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 mb-1 flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Task Description
                                        </h4>
                                        <p className="text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                                            {template.description}
                                        </p>
                                    </div>
                                )}

                                {template.guideline && (
                                    <div className={template.description ? "pt-3 border-t border-indigo-100 dark:border-indigo-900/30" : ""}>
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 mb-1 flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Guidelines & Instructions
                                        </h4>
                                        <div className="text-xs text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-white/70 dark:bg-slate-900/60 p-3 rounded-lg border border-indigo-100/60 dark:border-indigo-900/30">
                                            {template.guideline}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Status overview */}
                        <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-slate-500 dark:text-slate-400 font-medium">Current Status:</span>
                                <span className="font-semibold text-slate-800 dark:text-slate-200 capitalize">
                                    {instance.status?.replace(/_/g, ' ')}
                                </span>
                            </div>
                            {instance.failure_reason && (
                                <div className="text-xs text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 p-2.5 rounded-lg border border-rose-200 dark:border-rose-900/50">
                                    <strong>Reason / Note:</strong> {instance.failure_reason}
                                </div>
                            )}
                        </div>

                        {/* Submission Evidence */}
                        {latestSubmission ? (
                            <div className="space-y-4">
                                <h4 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                    <svg className="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Latest Submission Evidence</span>
                                </h4>

                                <div className="space-y-2 text-sm">
                                    <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 text-xs">
                                        <span>Submitted by: <strong>{latestSubmission.submitted_by?.name || 'User'}</strong></span>
                                        <span>•</span>
                                        <span>{latestSubmission.submitted_at ? new Date(latestSubmission.submitted_at).toLocaleString() : 'N/A'}</span>
                                    </div>

                                    {latestSubmission.remarks && (
                                        <div className="p-3 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-300 text-xs">
                                            "{latestSubmission.remarks}"
                                        </div>
                                    )}
                                </div>

                                {/* Images */}
                                {images.length > 0 && (
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-medium text-slate-500 dark:text-slate-400">
                                                Uploaded Evidence ({images.length})
                                            </span>
                                            <span className="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">
                                                Click photo to view full carousel
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-3 gap-2">
                                            {images.map((img, idx) => (
                                                <EvidenceImage
                                                    key={img.id ?? img.file_url ?? idx}
                                                    src={img.file_url}
                                                    alt={`Evidence ${idx + 1}`}
                                                    onClick={() => setSelectedPhotoIndex(idx)}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* ── Approve / Reject section (shown when viewer is the pending approver) ── */}
                                {showApproveBar && (
                                    <div className="rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 p-4 space-y-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="flex items-center gap-2">
                                                <div className="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center shrink-0">
                                                    <svg className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                                <span className="text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                                                    Your approval required — Step {myPendingStep.step_order ?? myPendingStep.step}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                disabled={actionProcessing}
                                                onClick={handleApprove}
                                                className="flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition disabled:opacity-60 cursor-pointer shrink-0"
                                            >
                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {actionProcessing ? 'Processing…' : 'Approve'}
                                            </button>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="text"
                                                value={rejectRemark}
                                                onChange={e => { setRejectRemark(e.target.value); setRejectError(''); }}
                                                placeholder="Rejection remark (required to reject)…"
                                                className="flex-1 h-8 px-3 text-xs rounded-lg border border-rose-200 dark:border-rose-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500"
                                            />
                                            <button
                                                type="button"
                                                disabled={actionProcessing}
                                                onClick={handleReject}
                                                className="px-4 h-8 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition disabled:opacity-60 cursor-pointer shrink-0"
                                            >
                                                Reject
                                            </button>
                                        </div>
                                        {rejectError && <p className="text-xs text-rose-600 dark:text-rose-400">{rejectError}</p>}
                                    </div>
                                )}

                                {/* Approval Steps */}
                                {approvalSteps.length > 0 && (
                                    <div className="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <span className="text-xs font-semibold text-slate-600 dark:text-slate-400">Approval Workflow Steps</span>
                                        <div className="space-y-1.5">
                                            {approvalSteps.map((step) => {
                                                const isMyPending = myPendingStep?.id === step.id;
                                                return (
                                                    <div
                                                        key={step.id}
                                                        className={`flex items-center justify-between text-xs p-2 rounded-lg ${isMyPending ? 'bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-slate-800/60'}`}
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            <span className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] ${isMyPending ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700'}`}>
                                                                {step.step_order ?? step.step}
                                                            </span>
                                                            <span className="text-slate-700 dark:text-slate-300 font-medium">
                                                                {step.approver?.name || `Step ${step.step} Approver`}
                                                                {isMyPending && <span className="ml-1 text-emerald-600 dark:text-emerald-400 font-bold">(You)</span>}
                                                            </span>
                                                        </div>
                                                        <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${
                                                            step.status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                                            step.status === 'rejected' ? 'bg-rose-100 text-rose-700' :
                                                            step.status === 'cancelled' ? 'bg-slate-200 text-slate-500' :
                                                            'bg-amber-100 text-amber-700'
                                                        }`}>
                                                            {step.status}
                                                        </span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="p-6 text-center text-slate-400 text-sm">
                                No submission evidence attached to this task instance yet.
                            </div>
                        )}

                        {/* Super Admin Override */}
                        {isSuperAdmin && (
                            <form onSubmit={handleSubmitOverride} className="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-4">
                                <div className="flex items-center gap-2">
                                    <svg className="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        Super Admin Audit Status Override
                                    </h4>
                                </div>

                                <div className="space-y-3 bg-amber-500/5 dark:bg-amber-500/10 p-4 rounded-xl border border-amber-500/20">
                                    <div>
                                        <label className="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Override Status</label>
                                        <select
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="passed">Passed (Approved)</option>
                                            <option value="failed_late">Failed (Late)</option>
                                            <option value="failed_missed">Failed (Missed)</option>
                                            <option value="excluded">Excluded</option>
                                            <option value="rejected">Rejected</option>
                                            <option value="waiting_first_approval">Waiting 1st Approval</option>
                                            <option value="waiting_final_approval">Waiting Final Approval</option>
                                        </select>
                                        {errors.status && <p className="text-xs text-rose-500 mt-1">{errors.status}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Override Reason / Audit Note</label>
                                        <textarea
                                            rows={2}
                                            value={data.failure_reason}
                                            onChange={(e) => setData('failure_reason', e.target.value)}
                                            placeholder="Reason for manual audit override..."
                                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        />
                                        {errors.failure_reason && <p className="text-xs text-rose-500 mt-1">{errors.failure_reason}</p>}
                                    </div>

                                    <div className="flex justify-end pt-1">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs rounded-lg shadow-sm transition disabled:opacity-50 cursor-pointer"
                                        >
                                            {processing ? 'Saving Override...' : 'Apply Admin Override'}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>

            {/* Photo Carousel Modal */}
            <PhotoCarouselModal
                isOpen={selectedPhotoIndex !== null}
                images={images}
                selectedIndex={selectedPhotoIndex}
                onClose={() => setSelectedPhotoIndex(null)}
                onSelectIndex={(newIdx) => setSelectedPhotoIndex(newIdx)}
            />
        </>,
        document.body
    );
}
