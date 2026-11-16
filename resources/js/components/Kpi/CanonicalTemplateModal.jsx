import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import axios from 'axios';
import {
    X,
    Plus,
    Trash2,
    CheckSquare,
    Square,
    Save,
    Layers,
    AlertCircle,
} from 'lucide-react';

const createEmptyRow = (index = 1) => ({
    id: `row_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    label: `Row ${index}`,
    type_ids: [],
});

export default function CanonicalTemplateModal({
    isOpen,
    onClose,
    template = null,
    availableTypes = [],
    onSaved,
    onDeleted,
}) {
    const [name, setName] = useState('');
    const [rows, setRows] = useState([createEmptyRow(1)]);
    const [activeRowId, setActiveRowId] = useState(null);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!isOpen) return;
        setError(null);

        if (template) {
            setName(template.name || '');
            const clonedRows =
                Array.isArray(template.rows) && template.rows.length > 0
                    ? template.rows.map((r) => ({
                          id: String(r.id),
                          label: r.label || '',
                          type_ids: Array.isArray(r.type_ids) ? r.type_ids.map(Number) : [],
                      }))
                    : [createEmptyRow(1)];
            setRows(clonedRows);
            setActiveRowId(clonedRows[0].id);
        } else {
            const initialRow = createEmptyRow(1);
            setName('');
            setRows([initialRow]);
            setActiveRowId(initialRow.id);
        }
    }, [isOpen, template]);

    useEffect(() => {
        if (!isOpen) return;
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = prevOverflow;
        };
    }, [isOpen]);

    if (!isOpen) return null;

    const activeRow = rows.find((r) => r.id === activeRowId) || rows[0] || null;

    const handleAddRow = () => {
        const newRow = createEmptyRow(rows.length + 1);
        setRows((prev) => [...prev, newRow]);
        setActiveRowId(newRow.id);
    };

    const handleRemoveRow = (rowId) => {
        if (rows.length <= 1) return;
        setRows((prev) => {
            const next = prev.filter((r) => r.id !== rowId);
            if (activeRowId === rowId && next.length > 0) {
                setActiveRowId(next[0].id);
            }
            return next;
        });
    };

    const handleRowLabelChange = (rowId, value) => {
        setRows((prev) =>
            prev.map((r) => (r.id === rowId ? { ...r, label: value } : r))
        );
    };

    const handleToggleTypeForActiveRow = (typeId) => {
        if (!activeRow) return;
        const numericId = Number(typeId);
        setRows((prev) =>
            prev.map((r) => {
                if (r.id !== activeRow.id) return r;
                const exists = r.type_ids.includes(numericId);
                return {
                    ...r,
                    type_ids: exists
                        ? r.type_ids.filter((id) => id !== numericId)
                        : [...r.type_ids, numericId],
                };
            })
        );
    };

    const handleSave = async () => {
        if (!name.trim()) {
            setError('Please enter a template name.');
            return;
        }
        const cleanedRows = rows.map((r, idx) => ({
            id: r.id,
            label: (r.label || '').trim() || `Row ${idx + 1}`,
            type_ids: r.type_ids,
        }));

        setSaving(true);
        setError(null);
        try {
            const res = await axios.post('/sale-kpi/canonical-templates', {
                id: template?.id || null,
                name: name.trim(),
                rows: cleanedRows,
            });
            onSaved?.(res.data.template, res.data.templates);
            onClose();
        } catch (err) {
            console.error('Error saving canonical template:', err);
            setError(err.response?.data?.message || 'Failed to save template');
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        if (!template?.id) return;
        setDeleting(true);
        setError(null);
        try {
            const res = await axios.delete(`/sale-kpi/canonical-templates/${encodeURIComponent(template.id)}`);
            onDeleted?.(template.id, res.data.templates);
            onClose();
        } catch (err) {
            console.error('Error deleting canonical template:', err);
            setError(err.response?.data?.message || 'Failed to delete template');
        } finally {
            setDeleting(false);
        }
    };

    return createPortal(
        <div
            className="fixed inset-0 z-[9999] bg-black/55 backdrop-blur-sm p-4 flex items-center justify-center overflow-hidden overscroll-contain"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <div
                className="w-full max-w-4xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col"
                style={{ maxHeight: 'calc(100vh - 2rem)' }}
                onClick={(e) => e.stopPropagation()}
            >
                {/* Modal Header */}
                <div className="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                    <div className="flex items-center gap-2.5">
                        <div className="p-2 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                            <Layers className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                {template ? 'Edit Canonical Template' : 'Create Canonical Template'}
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Add rows on the left, edit row names, and check items to summarize on the right.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Template Name */}
                <div className="shrink-0 px-6 py-3.5 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800">
                    <label className="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
                        Template Name
                    </label>
                    <input
                        type="text"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        placeholder="e.g. Monthly Gold & Customer Summary"
                        className="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                    />
                    {error && (
                        <div className="mt-2 flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}
                </div>

                {/* Split Body: Left (Rows) & Right (Checkable Items) */}
                <div className="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 dark:divide-slate-800 flex-1 min-h-0 overflow-hidden">
                    {/* Left Side: Template Rows */}
                    <div className="flex flex-col min-h-0 overflow-hidden">
                        <div className="shrink-0 flex items-center justify-between px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Template Rows ({rows.length})
                            </span>
                            <button
                                type="button"
                                onClick={handleAddRow}
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-white transition"
                            >
                                <Plus className="w-3.5 h-3.5" />
                                <span>Add Row</span>
                            </button>
                        </div>

                        <div className="flex-1 min-h-0 overflow-y-auto overscroll-contain p-4 space-y-2.5">
                            {rows.map((row, idx) => {
                                const isActive = activeRow?.id === row.id;
                                return (
                                    <div
                                        key={row.id}
                                        onClick={() => setActiveRowId(row.id)}
                                        className={`p-3 rounded-2xl border transition cursor-pointer flex items-center gap-2.5 ${
                                            isActive
                                                ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/30 shadow-2xs'
                                                : 'border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/40 hover:border-slate-300 dark:hover:border-slate-600'
                                        }`}
                                    >
                                        <span
                                            className={`w-6 h-6 rounded-lg text-[11px] font-bold flex items-center justify-center shrink-0 ${
                                                isActive
                                                    ? 'bg-indigo-600 text-white'
                                                    : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                                            }`}
                                        >
                                            {idx + 1}
                                        </span>

                                        <input
                                            type="text"
                                            value={row.label}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setActiveRowId(row.id);
                                            }}
                                            onChange={(e) => handleRowLabelChange(row.id, e.target.value)}
                                            placeholder={`Row ${idx + 1} name`}
                                            className="flex-1 min-w-0 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                        />

                                        <span className="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 shrink-0">
                                            {row.type_ids.length} items
                                        </span>

                                        {rows.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handleRemoveRow(row.id);
                                                }}
                                                className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="Remove row"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Right Side: Checkable Canonical Items */}
                    <div className="flex flex-col min-h-0 overflow-hidden bg-slate-50/40 dark:bg-slate-900/50">
                        <div className="shrink-0 px-5 py-3 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Items for:{' '}
                                <span className="text-indigo-600 dark:text-indigo-400 normal-case">
                                    {activeRow?.label || 'Selected Row'}
                                </span>
                            </span>
                        </div>

                        <div className="flex-1 min-h-0 overflow-y-auto overscroll-contain p-4 space-y-1.5">
                            {availableTypes.map((typeItem) => {
                                const checked = activeRow?.type_ids?.includes(Number(typeItem.id)) || false;
                                return (
                                    <button
                                        key={typeItem.id}
                                        type="button"
                                        onClick={() => handleToggleTypeForActiveRow(typeItem.id)}
                                        className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl border text-left transition ${
                                            checked
                                                ? 'border-indigo-400 bg-indigo-50/80 dark:bg-indigo-950/40 text-indigo-950 dark:text-indigo-100'
                                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            {checked ? (
                                                <CheckSquare className="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                                            ) : (
                                                <Square className="w-4 h-4 text-slate-400 shrink-0" />
                                            )}
                                            <span className="text-xs font-medium truncate">{typeItem.name}</span>
                                        </div>
                                        {(typeItem.is_sale_gram || typeItem.is_sale_quantity) && (
                                            <span className="ml-2 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 shrink-0">
                                                {typeItem.is_sale_gram ? 'Gram' : 'Pcs'}
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Modal Footer */}
                <div className="shrink-0 flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900">
                    <div>
                        {template?.id && (
                            <button
                                type="button"
                                onClick={handleDelete}
                                disabled={deleting || saving}
                                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition disabled:opacity-50"
                            >
                                <Trash2 className="w-3.5 h-3.5" />
                                <span>{deleting ? 'Deleting...' : 'Delete Template'}</span>
                            </button>
                        )}
                    </div>

                    <div className="flex items-center gap-2.5">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            onClick={handleSave}
                            disabled={saving || deleting}
                            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 dark:bg-indigo-600 text-white hover:bg-slate-800 dark:hover:bg-indigo-500 transition disabled:opacity-50 shadow-sm"
                        >
                            <Save className="w-3.5 h-3.5" />
                            <span>{saving ? 'Saving...' : 'Save Template'}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>,
        document.body
    );
}
