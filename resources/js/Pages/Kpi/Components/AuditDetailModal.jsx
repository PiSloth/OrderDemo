import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';

export default function AuditDetailModal({ isOpen, onClose, selectedMarker, isSuperAdmin }) {
    const [selectedPhotoIndex, setSelectedPhotoIndex] = useState(null);

    const instance = selectedMarker?.instance || {};
    const latestSubmission = instance.latest_submission || null;
    const images = latestSubmission?.images || [];

    const { data, setData, post, processing, errors } = useForm({
        status: instance.status || 'pending',
        failure_reason: instance.failure_reason || '',
    });

    // Reset state when selectedMarker changes
    useEffect(() => {
        if (selectedMarker) {
            setData({
                status: instance.status || 'pending',
                failure_reason: instance.failure_reason || '',
            });
            setSelectedPhotoIndex(null);
        }
    }, [selectedMarker]);

    // Body scroll lock handling
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }

        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);

    // Keyboard navigation listener
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                if (selectedPhotoIndex !== null) {
                    setSelectedPhotoIndex(null);
                } else {
                    onClose();
                }
            } else if (selectedPhotoIndex !== null) {
                if (e.key === 'ArrowLeft') {
                    handlePrevPhoto();
                } else if (e.key === 'ArrowRight') {
                    handleNextPhoto();
                }
            }
        };

        if (isOpen) {
            window.addEventListener('keydown', handleKeyDown);
        }

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen, selectedPhotoIndex, images.length, onClose]);

    const handlePrevPhoto = () => {
        if (selectedPhotoIndex === null || images.length === 0) return;
        setSelectedPhotoIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
    };

    const handleNextPhoto = () => {
        if (selectedPhotoIndex === null || images.length === 0) return;
        setSelectedPhotoIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
    };

    const handleSubmitOverride = (e) => {
        e.preventDefault();
        post(`/kpi/audit/instance/${instance.id}/status`, {
            onSuccess: () => {
                onClose();
            },
        });
    };

    if (!isOpen || !selectedMarker) {
        return null;
    }

    return (
        <>
            {/* Main Inspection Modal (z-50) */}
            <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                {/* Backdrop click listener */}
                <div
                    className="fixed inset-0"
                    onClick={() => {
                        document.body.style.overflow = '';
                        onClose();
                    }}
                ></div>

                {/* Modal Card */}
                <div className="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl overflow-hidden z-10 border border-slate-200 dark:border-slate-800 no-scrollbar max-h-[90vh] flex flex-col">
                    {/* Header */}
                    <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                        <div>
                            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span>Task Instance Inspection</span>
                                <span className={`px-2.5 py-0.5 text-xs font-semibold rounded-full ${selectedMarker.classes}`}>
                                    {selectedMarker.label}
                                </span>
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                ID #{instance.id} • Date: {instance.task_date || 'N/A'}
                            </p>
                        </div>

                        <button
                            onClick={() => {
                                document.body.style.overflow = '';
                                onClose();
                            }}
                            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Body Content */}
                    <div className="p-6 overflow-y-auto space-y-6 flex-1 no-scrollbar">
                        {/* Status & Failure Reason overview */}
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

                                {/* Images Gallery Thumbnails */}
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
                                                <button
                                                    key={img.id || idx}
                                                    type="button"
                                                    onClick={() => setSelectedPhotoIndex(idx)}
                                                    className="group relative aspect-video rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-xs"
                                                >
                                                    <img
                                                        src={img.file_url}
                                                        alt={`Evidence ${idx + 1}`}
                                                        className="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                                                    />
                                                    <div className="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/30 transition flex items-center justify-center">
                                                        <svg className="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                                        </svg>
                                                    </div>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Approval Steps */}
                                {latestSubmission.approval_steps && latestSubmission.approval_steps.length > 0 && (
                                    <div className="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <span className="text-xs font-semibold text-slate-600 dark:text-slate-400">Approval Workflow Steps</span>
                                        <div className="space-y-1.5">
                                            {latestSubmission.approval_steps.map((step) => (
                                                <div key={step.id} className="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60">
                                                    <div className="flex items-center gap-2">
                                                        <span className="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-[10px]">
                                                            {step.step}
                                                        </span>
                                                        <span className="text-slate-700 dark:text-slate-300 font-medium">
                                                            {step.approver?.name || `Step ${step.step} Approver`}
                                                        </span>
                                                    </div>
                                                    <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase ${
                                                        step.status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                                        step.status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'
                                                    }`}>
                                                        {step.status}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="p-6 text-center text-slate-400 text-sm">
                                No submission evidence attached to this task instance yet.
                            </div>
                        )}

                        {/* Super Admin Status Override Section */}
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
                                        <label className="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                            Override Status
                                        </label>
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
                                        <label className="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                            Override Reason / Audit Note
                                        </label>
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
                                            className="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs rounded-lg shadow-sm transition disabled:opacity-50"
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

            {/* FULL-SCREEN PHOTO CAROUSEL LIGHTBOX MODAL (z-[100] to sit strictly above main modal) */}
            {selectedPhotoIndex !== null && images[selectedPhotoIndex] && (
                <div className="fixed inset-0 z-[100] bg-black/95 backdrop-blur-md flex items-center justify-center p-4">
                    {/* Backdrop click listener */}
                    <div
                        className="fixed inset-0"
                        onClick={() => setSelectedPhotoIndex(null)}
                    ></div>

                    {/* Close Lightbox Button */}
                    <button
                        type="button"
                        onClick={() => setSelectedPhotoIndex(null)}
                        className="absolute top-4 right-4 z-[110] p-2.5 rounded-full bg-white/10 hover:bg-white/20 text-white transition focus:outline-none cursor-pointer"
                        title="Close image view (Esc)"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    {/* Image Counter Badge */}
                    <div className="absolute top-4 left-4 z-[110] px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-sm text-white text-xs font-semibold">
                        Image {selectedPhotoIndex + 1} of {images.length}
                    </div>

                    {/* Previous Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handlePrevPhoto}
                            className="absolute left-4 z-[110] p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition focus:outline-none cursor-pointer"
                            title="Previous image (←)"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    )}

                    {/* Next Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handleNextPhoto}
                            className="absolute right-4 z-[110] p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition focus:outline-none cursor-pointer"
                            title="Next image (→)"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    )}

                    {/* Full Size Image Display */}
                    <div className="relative z-[105] max-w-5xl max-h-[85vh] flex flex-col items-center justify-center p-2 select-none">
                        <img
                            src={images[selectedPhotoIndex].file_url}
                            alt={`Evidence Full View ${selectedPhotoIndex + 1}`}
                            className="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl transition duration-300"
                        />
                    </div>
                </div>
            )}
        </>
    );
}
