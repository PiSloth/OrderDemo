import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';

export default function InstanceEditModal({
    isOpen = false,
    onClose,
    instance = null,
    statusOptions = {},
}) {
    const [existingImages, setExistingImages] = useState([]);
    const [removeImageIds, setRemoveImageIds] = useState([]);
    const [newFiles, setNewFiles] = useState([]);
    const [newTitles, setNewTitles] = useState([]);
    const [newRemarks, setNewRemarks] = useState([]);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        instanceStatus: 'pending',
        instanceTaskDate: '',
        instancePeriodStart: '',
        instancePeriodEnd: '',
        instanceDueAt: '',
        instanceIsLate: false,
        editingSubmissionId: null,
        removeSubmissionImageIds: [],
        newSubmissionPhotos: [],
        newSubmissionPhotoTitles: [],
        newSubmissionPhotoRemarks: [],
    });

    useEffect(() => {
        if (isOpen && instance) {
            document.body.style.overflow = 'hidden';
            clearErrors();

            const latestSub = instance.latest_submission;
            const images = latestSub?.images || [];

            setExistingImages(images);
            setRemoveImageIds([]);
            setNewFiles([]);
            setNewTitles([]);
            setNewRemarks([]);

            setData({
                instanceStatus: instance.status || 'pending',
                instanceTaskDate: instance.task_date || '',
                instancePeriodStart: instance.period_start || '',
                instancePeriodEnd: instance.period_end || '',
                instanceDueAt: instance.due_at || '',
                instanceIsLate: Boolean(latestSub?.is_late ?? false),
                editingSubmissionId: latestSub?.id || null,
                removeSubmissionImageIds: [],
                newSubmissionPhotos: [],
                newSubmissionPhotoTitles: [],
                newSubmissionPhotoRemarks: [],
            });

            return () => {
                document.body.style.overflow = 'unset';
            };
        }
    }, [isOpen, instance]);

    if (!isOpen || !instance) return null;

    const resolveUrl = (name, id = null) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return id ? window.route(name, id) : window.route(name);
        }
        return `/kpi/assignments/instances/${id}`;
    };

    const handleFileChange = (e) => {
        const files = Array.from(e.target.files || []);
        setNewFiles(files);
        setNewTitles(files.map(() => ''));
        setNewRemarks(files.map(() => ''));
        setData('newSubmissionPhotos', files);
    };

    const toggleRemoveImage = (imgId) => {
        const current = [...removeImageIds];
        const index = current.indexOf(imgId);
        if (index > -1) {
            current.splice(index, 1);
        } else {
            current.push(imgId);
        }
        setRemoveImageIds(current);
        setData('removeSubmissionImageIds', current);
    };

    const handleTitleChange = (index, value) => {
        const updated = [...newTitles];
        updated[index] = value;
        setNewTitles(updated);
        setData('newSubmissionPhotoTitles', updated);
    };

    const handleRemarkChange = (index, value) => {
        const updated = [...newRemarks];
        updated[index] = value;
        setNewRemarks(updated);
        setData('newSubmissionPhotoRemarks', updated);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(resolveUrl('kpi.assignments.instances.update', instance.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    const buildEvidenceSummary = () => {
        const tpl = instance.template;
        if (!tpl?.requires_images) return 'No image evidence required for this task.';
        if (tpl.max_images !== null && tpl.max_images > 0) {
            if (tpl.min_images > 0 && tpl.min_images === tpl.max_images) {
                return `Image evidence required: ${tpl.min_images} photo(s).`;
            }
            return `Image evidence required: ${tpl.min_images || 0} to ${tpl.max_images} photo(s).`;
        }
        if (tpl.min_images > 0) {
            return `Image evidence required: at least ${tpl.min_images} photo(s).`;
        }
        return 'Image evidence is required.';
    };

    return (
        <div
            onClick={(e) => e.target === e.currentTarget && onClose()}
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6"
        >
            <div className="max-h-[92vh] w-full max-w-3xl overflow-y-auto overflow-x-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 no-scrollbar">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div>
                        <span className="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold uppercase text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            Super Admin Action
                        </span>
                        <h3 className="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">
                            Edit Task Instance #{instance.id}
                        </h3>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            {instance.template?.title} • Employee: {instance.user?.name}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                    >
                        ✕
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="mt-5 space-y-5">
                    {/* Status & Mark as late */}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Instance Status
                            </label>
                            <select
                                value={data.instanceStatus}
                                onChange={(e) => setData('instanceStatus', e.target.value)}
                                className="mt-1.5 block w-full rounded-2xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-800 shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                {Object.entries(statusOptions).map(([val, label]) => {
                                    if (val === 'all') return null;
                                    return (
                                        <option key={val} value={val}>
                                            {label}
                                        </option>
                                    );
                                })}
                            </select>
                            {errors.instanceStatus && (
                                <p className="mt-1 text-xs text-rose-600">{errors.instanceStatus}</p>
                            )}
                        </div>

                        {data.editingSubmissionId && (
                            <div className="flex items-center pt-6">
                                <label className="flex items-center gap-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input
                                        type="checkbox"
                                        checked={data.instanceIsLate}
                                        onChange={(e) => setData('instanceIsLate', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    Mark this submission as late
                                </label>
                            </div>
                        )}
                    </div>

                    {/* Requirements Notice */}
                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
                        <span className="font-bold text-slate-900 dark:text-slate-100">Evidence Rule: </span>
                        {buildEvidenceSummary()}
                    </div>

                    {/* Task Date & Due Date */}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Task Date
                            </label>
                            <input
                                type="date"
                                value={data.instanceTaskDate}
                                onChange={(e) => setData('instanceTaskDate', e.target.value)}
                                className="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            {errors.instanceTaskDate && (
                                <p className="mt-1 text-xs text-rose-600">{errors.instanceTaskDate}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Due At
                            </label>
                            <input
                                type="datetime-local"
                                value={data.instanceDueAt}
                                onChange={(e) => setData('instanceDueAt', e.target.value)}
                                className="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            {errors.instanceDueAt && (
                                <p className="mt-1 text-xs text-rose-600">{errors.instanceDueAt}</p>
                            )}
                        </div>
                    </div>

                    {/* Period Start & Period End */}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Period Start <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                required
                                value={data.instancePeriodStart}
                                onChange={(e) => setData('instancePeriodStart', e.target.value)}
                                className="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            {errors.instancePeriodStart && (
                                <p className="mt-1 text-xs text-rose-600">{errors.instancePeriodStart}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Period End <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                required
                                value={data.instancePeriodEnd}
                                onChange={(e) => setData('instancePeriodEnd', e.target.value)}
                                className="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 shadow-sm focus:border-slate-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            {errors.instancePeriodEnd && (
                                <p className="mt-1 text-xs text-rose-600">{errors.instancePeriodEnd}</p>
                            )}
                        </div>
                    </div>

                    {/* Submission Photos Management */}
                    <div className="space-y-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                            Submission Photos &amp; Evidence
                        </h4>

                        {/* Existing images */}
                        {existingImages.length > 0 && (
                            <div className="grid gap-3 sm:grid-cols-2">
                                {existingImages.map((img) => {
                                    const isMarked = removeImageIds.includes(img.id);
                                    return (
                                        <div
                                            key={img.id}
                                            className={`rounded-2xl border p-3 transition ${
                                                isMarked
                                                    ? 'border-rose-300 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/40'
                                                    : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60'
                                            }`}
                                        >
                                            <img
                                                src={img.url}
                                                alt="Submission Evidence"
                                                className="h-28 w-full rounded-xl object-cover"
                                            />
                                            <p className="mt-1.5 truncate text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                                {img.title || 'Evidence Image'}
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => toggleRemoveImage(img.id)}
                                                className={`mt-2 w-full rounded-lg py-1 text-xs font-bold ${
                                                    isMarked
                                                        ? 'border border-emerald-300 bg-emerald-100 text-emerald-700'
                                                        : 'border border-rose-300 bg-white text-rose-700 dark:border-rose-700 dark:bg-slate-900'
                                                }`}
                                            >
                                                {isMarked ? '✓ Keep Image' : '✕ Remove Image'}
                                            </button>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {/* Upload new images */}
                        <div>
                            <label className="block text-xs font-medium text-slate-700 dark:text-slate-300">
                                Add New Photos
                            </label>
                            <input
                                type="file"
                                multiple
                                accept="image/*"
                                onChange={handleFileChange}
                                className="mt-1.5 block w-full rounded-xl border border-slate-300 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                        </div>

                        {newFiles.length > 0 && (
                            <div className="grid gap-3 sm:grid-cols-2">
                                {newFiles.map((file, idx) => (
                                    <div
                                        key={idx}
                                        className="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900"
                                    >
                                        <p className="truncate text-xs font-bold text-slate-700 dark:text-slate-300">
                                            {file.name}
                                        </p>
                                        <input
                                            type="text"
                                            placeholder="Title"
                                            value={newTitles[idx] || ''}
                                            onChange={(e) => handleTitleChange(idx, e.target.value)}
                                            className="mt-1.5 block w-full rounded-lg border border-slate-300 px-2.5 py-1 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                        />
                                        <textarea
                                            rows="2"
                                            placeholder="Remark"
                                            value={newRemarks[idx] || ''}
                                            onChange={(e) => handleRemarkChange(idx, e.target.value)}
                                            className="mt-1.5 block w-full rounded-lg border border-slate-300 px-2.5 py-1 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-2xl bg-slate-900 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900"
                        >
                            {processing ? 'Saving...' : 'Update Task Instance'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
