import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import axios from 'axios';

export default function CreatePromoteActionModal() {
    const [isOpen, setIsOpen] = useState(false);
    const [branches, setBranches] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [onSuccessCallback, setOnSuccessCallback] = useState(null);

    // Form fields
    const [name, setName] = useState('');
    const [targetBranchId, setTargetBranchId] = useState('');
    const [actionBy, setActionBy] = useState('');
    const [startAt, setStartAt] = useState('');
    const [endAt, setEndAt] = useState('');
    const [referenceType, setReferenceType] = useState(''); // 'todo_list_id' or 'kpi_task_id'
    const [referenceId, setReferenceId] = useState('');
    const [referenceSearch, setReferenceSearch] = useState('');

    // Autocomplete states
    const [searchResults, setSearchResults] = useState([]);
    const [showDropdown, setShowDropdown] = useState(false);
    const [loadingSearch, setLoadingSearch] = useState(false);
    const searchTimeout = useRef(null);

    // Errors & Submitting state
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    // Listen to global custom event
    useEffect(() => {
        const handleShowModal = (e) => {
            const { branches: b = [], departments: d = [], onSuccess = null } = e.detail || {};
            setBranches(b);
            setDepartments(d);
            setOnSuccessCallback(() => onSuccess);
            
            // Reset form
            setName('');
            setTargetBranchId('');
            setActionBy('');
            setStartAt('');
            setEndAt('');
            setReferenceType('');
            setReferenceId('');
            setReferenceSearch('');
            setSearchResults([]);
            setErrors({});
            setIsOpen(true);
        };

        window.addEventListener('show-promote-action-modal', handleShowModal);
        return () => window.removeEventListener('show-promote-action-modal', handleShowModal);
    }, []);

    // Escape key to close
    useEffect(() => {
        if (!isOpen) return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') setIsOpen(false);
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen]);

    // Handle search input typing
    useEffect(() => {
        if (!referenceType) {
            setSearchResults([]);
            return;
        }

        if (searchTimeout.current) {
            clearTimeout(searchTimeout.current);
        }

        if (!referenceSearch.trim()) {
            setSearchResults([]);
            return;
        }

        setLoadingSearch(true);
        searchTimeout.current = setTimeout(() => {
            const url = referenceType === 'todo_list_id' 
                ? '/kpi/sale-kpi/search-todos' 
                : '/kpi/sale-kpi/search-kpi-tasks';

            axios.get(url, { params: { q: referenceSearch } })
                .then((res) => {
                    setSearchResults(res.data || []);
                    setShowDropdown(true);
                })
                .catch((err) => console.error(err))
                .finally(() => setLoadingSearch(false));
        }, 300);

        return () => clearTimeout(searchTimeout.current);
    }, [referenceSearch, referenceType]);

    if (!isOpen || typeof document === 'undefined') return null;

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        const referencePayload = referenceType && referenceId 
            ? { [referenceType]: referenceId } 
            : null;

        axios.post('/kpi/sale-kpi/promote-actions', {
            name,
            target_branch_id: targetBranchId || null,
            action_by: actionBy,
            start_at: startAt,
            end_at: endAt,
            reference: referencePayload,
        })
        .then((res) => {
            if (res.data.success) {
                if (onSuccessCallback) onSuccessCallback(res.data.data);
                setIsOpen(false);
            }
        })
        .catch((err) => {
            if (err.response && err.response.data && err.response.data.errors) {
                setErrors(err.response.data.errors);
            } else {
                setErrors({ general: 'Something went wrong. Please check your input.' });
            }
        })
        .finally(() => setSubmitting(false));
    };

    const handleSelectResult = (item) => {
        setReferenceId(item.id);
        setReferenceSearch(item.task || item.name || `ID: ${item.id}`);
        setShowDropdown(false);
    };

    const inputCls = 'w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#FEF08A] transition';
    const labelCls = 'block text-xs font-semibold text-slate-650 dark:text-slate-400 mb-1';
    const errorCls = 'text-xs text-rose-500 mt-1';

    return createPortal(
        <div
            style={{ position: 'fixed', inset: 0, zIndex: 9000, backgroundColor: 'rgba(2,6,23,0.6)' }}
            className="flex items-center justify-center p-4 backdrop-blur-sm"
            onClick={() => setIsOpen(false)}
        >
            <div
                style={{ position: 'relative', zIndex: 9001 }}
                className="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                            <span className="text-slate-700 dark:text-slate-300 font-bold">P</span>
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Create Promote Action</h3>
                            <p className="text-xs text-slate-500">Define a new target campaign or operational promotion</p>
                        </div>
                    </div>
                    <button
                        onClick={() => setIsOpen(false)}
                        className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        ✕
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    {errors.general && (
                        <div className="p-3 text-sm text-rose-600 bg-rose-50 dark:bg-rose-950/20 dark:text-rose-400 rounded-xl">
                            {errors.general}
                        </div>
                    )}

                    {/* Name */}
                    <div>
                        <label className={labelCls}>Promote Action Name</label>
                        <input
                            type="text"
                            required
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="E.g., Mid-Autumn Festival Sale"
                            className={inputCls}
                        />
                        {errors.name && <p className={errorCls}>{errors.name[0]}</p>}
                    </div>

                    {/* Target Branch & Department */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className={labelCls}>Target Branch</label>
                            <select
                                value={targetBranchId}
                                onChange={(e) => setTargetBranchId(e.target.value)}
                                className={inputCls}
                            >
                                <option value="">All Branches</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>{b.name}</option>
                                ))}
                            </select>
                            {errors.target_branch_id && <p className={errorCls}>{errors.target_branch_id[0]}</p>}
                        </div>

                        <div>
                            <label className={labelCls}>Action By (Department)</label>
                            <select
                                required
                                value={actionBy}
                                onChange={(e) => setActionBy(e.target.value)}
                                className={inputCls}
                            >
                                <option value="">Select Department</option>
                                {departments.map((d) => (
                                    <option key={d.id} value={d.id}>{d.name}</option>
                                ))}
                            </select>
                            {errors.action_by && <p className={errorCls}>{errors.action_by[0]}</p>}
                        </div>
                    </div>

                    {/* Date Range */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className={labelCls}>Start Date</label>
                            <input
                                type="date"
                                required
                                value={startAt}
                                onChange={(e) => setStartAt(e.target.value)}
                                className={inputCls}
                            />
                            {errors.start_at && <p className={errorCls}>{errors.start_at[0]}</p>}
                        </div>

                        <div>
                            <label className={labelCls}>End Date</label>
                            <input
                                type="date"
                                required
                                value={endAt}
                                onChange={(e) => setEndAt(e.target.value)}
                                className={inputCls}
                            />
                            {errors.end_at && <p className={errorCls}>{errors.end_at[0]}</p>}
                        </div>
                    </div>

                    {/* Dynamic Reference Section */}
                    <div className="border-t border-slate-100 dark:border-slate-800 pt-4">
                        <label className={labelCls}>Attach Task Reference (Optional)</label>
                        <div className="grid grid-cols-3 gap-2">
                            <select
                                value={referenceType}
                                onChange={(e) => {
                                    setReferenceType(e.target.value);
                                    setReferenceId('');
                                    setReferenceSearch('');
                                    setSearchResults([]);
                                }}
                                className="col-span-1 h-10 px-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-slate-500"
                            >
                                <option value="">None</option>
                                <option value="todo_list_id">Todo List</option>
                                <option value="kpi_task_id">KPI Task</option>
                            </select>

                            <div className="col-span-2 relative">
                                <input
                                    type="text"
                                    disabled={!referenceType}
                                    value={referenceSearch}
                                    onChange={(e) => {
                                        setReferenceSearch(e.target.value);
                                        setReferenceId('');
                                    }}
                                    placeholder={
                                        referenceType 
                                            ? `Search by ${referenceType === 'todo_list_id' ? 'task task' : 'task name'}...` 
                                            : 'Select reference type first'
                                    }
                                    className="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 disabled:opacity-50 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500 transition"
                                />

                                {/* Search Dropdown */}
                                {showDropdown && searchResults.length > 0 && (
                                    <div className="absolute left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg z-50">
                                        {searchResults.map((item) => (
                                            <div
                                                key={item.id}
                                                onClick={() => handleSelectResult(item)}
                                                className="px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition"
                                            >
                                                {item.task || item.name} (ID: {item.id})
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {loadingSearch && (
                                    <div className="absolute right-3 top-3">
                                        <div className="w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"></div>
                                    </div>
                                )}
                            </div>
                        </div>
                        {errors.reference && <p className={errorCls}>{errors.reference[0]}</p>}
                    </div>

                    {/* Footer Actions */}
                    <div className="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            onClick={() => setIsOpen(false)}
                            className="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={submitting}
                            className="h-10 px-5 rounded-xl bg-[#FEF08A] hover:bg-[#FDE047] text-slate-800 text-sm font-semibold shadow-sm transition disabled:opacity-50 flex items-center gap-2"
                        >
                            {submitting && (
                                <div className="w-4 h-4 border-2 border-white dark:border-slate-900 border-t-transparent rounded-full animate-spin"></div>
                            )}
                            Save Promote Action
                        </button>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    );
}
