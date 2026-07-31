import React, { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import PhotoCarouselModal from './PhotoCarouselModal';

export default function SubmissionDetailModal({ submission, onClose, onOpenPhoto }) {
    const [photoIndex, setPhotoIndex] = React.useState(null);

    useEffect(() => {
        if (!submission) return;
        const handleKey = (e) => {
            if (e.key === 'Escape' && photoIndex === null) {
                e.preventDefault();
                onClose();
            }
        };
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, [submission, onClose, photoIndex]);

    if (!submission) return null;
    if (typeof document === 'undefined') return null;

    const images = submission.images || [];
    const steps = submission.approval_steps || [];

    const statusColor = {
        passed: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        rejected: 'bg-rose-100 text-rose-700 border-rose-200',
        waiting_first_approval: 'bg-sky-100 text-sky-700 border-sky-200',
        waiting_final_approval: 'bg-blue-100 text-blue-700 border-blue-200',
        pending: 'bg-amber-100 text-amber-700 border-amber-200',
    };

    const stepStatusColor = (s) => ({
        approved: 'text-emerald-600',
        rejected: 'text-rose-600',
    }[s] || 'text-slate-500');

    return createPortal(
        <div
            style={{ position: 'fixed', inset: 0, zIndex: 9000, backgroundColor: 'rgba(2,6,23,0.7)' }}
            className="flex items-center justify-center p-4 sm:p-6 backdrop-blur-sm"
            onClick={onClose}
        >
            <div
                style={{ position: 'relative', zIndex: 9001 }}
                className="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[90vh] overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-start justify-between gap-4 bg-slate-50 dark:bg-slate-800/60">
                    <div className="flex-1 min-w-0">
                        <div className="flex flex-wrap items-center gap-2 mb-1">
                            <span className="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">
                                {submission.instance?.template?.title ?? '—'}
                            </span>
                            {submission.instance?.template?.group && (
                                <span className="px-2 py-0.5 text-xs rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600">
                                    {submission.instance.template.group.name}
                                </span>
                            )}
                            <span className={`px-2 py-0.5 text-xs rounded-full border font-medium ${statusColor[submission.status] || 'bg-slate-100 text-slate-600 border-slate-200'}`}>
                                {(submission.status || '').replace(/_/g, ' ')}
                            </span>
                        </div>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {submission.instance?.user?.name ?? '—'}
                        </p>
                        <div className="flex flex-wrap gap-4 mt-1 text-xs text-slate-400">
                            {submission.submitted_at && (
                                <span>Submitted: {new Date(submission.submitted_at).toLocaleDateString()}</span>
                            )}
                            {submission.instance?.due_at && (
                                <span>Due: {new Date(submission.instance.due_at).toLocaleDateString()}</span>
                            )}
                            {submission.submitted_by && (
                                <span>By: {submission.submitted_by.name}</span>
                            )}
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition flex-shrink-0 cursor-pointer"
                        title="Close (Esc)"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Body */}
                <div className="overflow-y-auto flex-1 p-6 space-y-6">
                    {/* Employee Remark */}
                    {submission.remarks && (
                        <div>
                            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Employee Remark</p>
                            <div className="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">
                                {submission.remarks}
                            </div>
                        </div>
                    )}

                    {/* Photos */}
                    <div>
                        <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                            Submitted Photos {images.length > 0 && <span className="ml-1 text-slate-400 normal-case font-normal">({images.length})</span>}
                        </p>
                        {images.length > 0 ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                {images.map((img, idx) => (
                                    <button
                                        key={img.id || idx}
                                        type="button"
                                        onClick={() => setPhotoIndex(idx)}
                                        className="relative aspect-video rounded-xl overflow-hidden border-2 border-slate-200 dark:border-slate-700 hover:border-indigo-400 transition group cursor-pointer bg-slate-100"
                                    >
                                        <img
                                            src={img.url}
                                            alt={img.title || `Photo ${idx + 1}`}
                                            className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                        />
                                        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center">
                                            <svg className="w-7 h-7 text-white opacity-0 group-hover:opacity-100 transition drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                            </svg>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 py-8 text-center text-sm text-slate-400">
                                No photos submitted
                            </div>
                        )}
                    </div>

                    {/* Approval Steps */}
                    {steps.length > 0 && (
                        <div>
                            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Approval Steps</p>
                            <div className="space-y-2">
                                {steps.map((step) => (
                                    <div key={step.id} className="flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-4 py-3">
                                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center">
                                            {step.step_order ?? step.step}
                                        </span>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                    {step.approver?.name ?? 'Approver'}
                                                </span>
                                                <span className={`text-xs font-medium ${stepStatusColor(step.status)}`}>
                                                    {(step.status || '').replace(/_/g, ' ')}
                                                </span>
                                                {step.acted_at && (
                                                    <span className="text-xs text-slate-400">{new Date(step.acted_at).toLocaleString()}</span>
                                                )}
                                            </div>
                                            {step.remark && (
                                                <p className="text-sm text-slate-500 dark:text-slate-400 mt-1 italic">"{step.remark}"</p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Photo Carousel */}
            <PhotoCarouselModal
                isOpen={photoIndex !== null}
                images={images}
                selectedIndex={photoIndex ?? 0}
                onClose={() => setPhotoIndex(null)}
                onSelectIndex={setPhotoIndex}
            />
        </div>,
        document.body
    );
}
