import React, { useState, useMemo, useRef, useEffect } from 'react';

export default function SearchableDueTimeSelect({
    dueTimes = [],
    selectedDueTimeId = '',
    onSelectDueTime = () => {},
    placeholder = 'Search & select Job Title / Category...',
    disabled = false,
    disabledMessage = 'Please select a department first...',
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const containerRef = useRef(null);
    const searchInputRef = useRef(null);

    // Focus input whenever menu opens
    useEffect(() => {
        if (isOpen && searchInputRef.current) {
            searchInputRef.current.focus();
        }
    }, [isOpen]);

    // Close dropdown on click outside
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Filter due times by search term
    const filteredDueTimes = useMemo(() => {
        if (!searchTerm.trim()) return dueTimes;

        const term = searchTerm.toLowerCase();
        return dueTimes.filter((dt) => {
            const name = (dt.name || '').toLowerCase();
            const catName = (dt.category?.name || dt.categoryName || '').toLowerCase();
            const priority = (dt.priority?.level || dt.priorityLevel || '').toLowerCase();
            const duration = String(dt.duration || '');
            const desc = (dt.description || '').toLowerCase();

            return (
                name.includes(term) ||
                catName.includes(term) ||
                priority.includes(term) ||
                duration.includes(term) ||
                desc.includes(term)
            );
        });
    }, [dueTimes, searchTerm]);

    const selectedDueTime = useMemo(() => {
        return dueTimes.find((dt) => String(dt.id) === String(selectedDueTimeId));
    }, [dueTimes, selectedDueTimeId]);

    const handleSelect = (dt) => {
        onSelectDueTime(dt ? String(dt.id) : '');
        setIsOpen(false);
        setSearchTerm('');
    };

    return (
        <div className="relative w-full" ref={containerRef}>
            {/* Selected Due Time Button */}
            <button
                type="button"
                disabled={disabled}
                onClick={() => setIsOpen(!isOpen)}
                className={`w-full text-left rounded-2xl border bg-white px-4 py-2.5 text-sm font-medium shadow-sm transition flex items-center justify-between dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 ${
                    disabled
                        ? 'opacity-60 cursor-not-allowed border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/50 text-slate-400'
                        : 'border-slate-300 dark:border-slate-700 hover:border-indigo-400 text-slate-800 dark:text-white'
                }`}
            >
                {disabled ? (
                    <span className="text-slate-400 dark:text-slate-500 text-xs italic">{disabledMessage}</span>
                ) : selectedDueTime ? (
                    <div className="flex items-center gap-2 truncate">
                        <span className="inline-flex items-center rounded-lg bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            ⏱️ {selectedDueTime.duration ? `${selectedDueTime.duration}h` : 'Due Time'}
                        </span>
                        <span className="truncate text-slate-900 dark:text-white font-semibold">
                            {selectedDueTime.name}
                        </span>
                    </div>
                ) : (
                    <span className="text-slate-400 dark:text-slate-500 text-xs">{placeholder}</span>
                )}

                <div className="flex items-center gap-1 ml-2">
                    {selectedDueTime && !disabled && (
                        <span
                            onClick={(e) => {
                                e.stopPropagation();
                                handleSelect(null);
                            }}
                            className="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 text-xs"
                            title="Clear selection"
                        >
                            ✕
                        </span>
                    )}
                    <svg className="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>

            {/* Dropdown Menu Overlay */}
            {isOpen && !disabled && (
                <div className="absolute left-0 right-0 z-50 mt-1.5 max-h-72 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-2xl dark:border-slate-800 dark:bg-slate-900 flex flex-col space-y-2">
                    {/* Search Input Field */}
                    <div className="relative">
                        <input
                            ref={searchInputRef}
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search job title, priority, duration..."
                            className="w-full rounded-xl border border-slate-300 bg-slate-50 pl-8 pr-3 py-2 text-xs text-slate-800 shadow-inner dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            autoFocus
                        />
                        <svg className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    {/* Options List */}
                    <div className="overflow-y-auto max-h-52 space-y-1 pr-1">
                        {filteredDueTimes.length > 0 ? (
                            filteredDueTimes.map((dt) => {
                                const isSelected = String(dt.id) === String(selectedDueTimeId);
                                const priorityLevel = dt.priority?.level || dt.priorityLevel || '';
                                return (
                                    <button
                                        key={dt.id}
                                        type="button"
                                        onClick={() => handleSelect(dt)}
                                        className={`w-full text-left rounded-xl px-3 py-2.5 text-xs transition flex items-center justify-between ${
                                            isSelected
                                                ? 'bg-indigo-50 text-indigo-900 font-bold dark:bg-indigo-950/70 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800'
                                                : 'hover:bg-slate-100 text-slate-700 dark:hover:bg-slate-800 dark:text-slate-300'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2.5 truncate">
                                            <span className="flex-shrink-0 font-mono text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                                                ⏱️ {dt.duration}h
                                            </span>
                                            <div className="truncate">
                                                <div className="font-semibold text-slate-900 dark:text-white truncate flex items-center gap-1.5">
                                                    <span>{dt.name}</span>
                                                    {priorityLevel && (
                                                        <span className="text-[10px] px-1.5 py-0.5 rounded font-normal bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                                            {priorityLevel}
                                                        </span>
                                                    )}
                                                </div>
                                                {dt.description && (
                                                    <p className="text-[10px] text-slate-400 truncate mt-0.5">{dt.description}</p>
                                                )}
                                            </div>
                                        </div>
                                        {isSelected && <span className="text-indigo-600 dark:text-indigo-400 font-bold ml-2">✓</span>}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="p-4 text-center text-xs text-slate-400 italic">
                                {dueTimes.length === 0
                                    ? 'No job titles available for this department.'
                                    : 'No job titles matching search.'}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
