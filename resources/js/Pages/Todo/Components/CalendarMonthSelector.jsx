import React, { useState, useRef, useEffect, useMemo } from 'react';

const MONTH_SHORT_NAMES = [
    'Jan', 'Feb', 'Mar', 'Apr',
    'May', 'Jun', 'Jul', 'Aug',
    'Sep', 'Oct', 'Nov', 'Dec',
];

function formatMonthLong(ym) {
    if (!ym || ym === 'all') return 'All Months';
    const [y, m] = String(ym).split('-').map(Number);
    if (!y || !m) return ym;
    return new Date(y, m - 1, 1).toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });
}

function getCurrentYm() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

/**
 * Interactive calendar month selector popover supporting "All Months",
 * year navigation, and task count badges per month.
 */
export default function CalendarMonthSelector({
    id,
    value = 'all',
    onChange,
    availableMonths = [],
    allowAll = true,
    fullWidth = false,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef(null);

    const currentYm = getCurrentYm();
    const initialYear = useMemo(() => {
        if (value && value !== 'all') {
            const y = parseInt(String(value).split('-')[0], 10);
            if (!Number.isNaN(y)) return y;
        }
        return new Date().getFullYear();
    }, [value]);

    const [viewYear, setViewYear] = useState(initialYear);

    useEffect(() => {
        if (isOpen) {
            setViewYear(initialYear);
        }
    }, [isOpen, initialYear]);

    useEffect(() => {
        if (!isOpen) return;

        const handleOutsideClick = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutsideClick);
        window.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen]);

    const monthCountsMap = useMemo(() => {
        const map = {};
        availableMonths.forEach((m) => {
            if (m?.value) {
                map[m.value] = m.count ?? 0;
            }
        });
        return map;
    }, [availableMonths]);

    const selectedLabel = useMemo(() => {
        if (!value || value === 'all') return 'All Months';
        const match = availableMonths.find((m) => m.value === value);
        const base = match?.label || formatMonthLong(value);
        return match?.count !== undefined ? `${base} (${match.count})` : base;
    }, [value, availableMonths]);

    const handleSelect = (newValue) => {
        onChange?.(newValue);
        setIsOpen(false);
    };

    return (
        <div ref={containerRef} className={`relative ${fullWidth ? 'block w-full' : 'inline-block'}`}>
            <button
                id={id}
                type="button"
                onClick={() => setIsOpen((prev) => !prev)}
                aria-haspopup="dialog"
                aria-expanded={isOpen}
                className={`inline-flex items-center justify-between gap-2.5 rounded-xl px-3 py-2 text-xs shadow-xs transition focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 ${
                    fullWidth
                        ? 'w-full border border-slate-300 bg-white font-medium text-slate-800 shadow-sm hover:border-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:hover:border-slate-600'
                        : 'min-w-[175px] border border-slate-200 bg-slate-50 font-semibold text-slate-800 hover:border-slate-300 hover:bg-slate-100/70 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-slate-600'
                }`}
            >
                <span className="flex items-center gap-2 truncate">
                    <svg
                        className="h-3.5 w-3.5 shrink-0 text-indigo-500 dark:text-indigo-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <span className="truncate">{selectedLabel}</span>
                </span>
                <svg
                    className={`h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-150 ${isOpen ? 'rotate-180' : ''}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {isOpen && (
                <div
                    role="dialog"
                    aria-label="Calendar Month Selector"
                    className="absolute left-0 top-full z-50 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-xl ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/10"
                >
                    {/* Year Navigation Header */}
                    <div className="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5 dark:border-slate-800">
                        <button
                            type="button"
                            onClick={() => setViewYear((y) => y - 1)}
                            className="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                            title="Previous Year"
                            aria-label="Previous Year"
                        >
                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <span className="text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">
                            {viewYear}
                        </span>

                        <button
                            type="button"
                            onClick={() => setViewYear((y) => y + 1)}
                            className="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                            title="Next Year"
                            aria-label="Next Year"
                        >
                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    {/* 12-Month Calendar Grid */}
                    <div className="grid grid-cols-3 gap-1.5">
                        {MONTH_SHORT_NAMES.map((name, idx) => {
                            const ym = `${viewYear}-${String(idx + 1).padStart(2, '0')}`;
                            const count = monthCountsMap[ym] || 0;
                            const isSelected = value === ym;
                            const isCurrent = currentYm === ym;

                            let tileClasses =
                                'group relative flex flex-col items-center justify-center rounded-xl px-2 py-2.5 text-xs font-bold transition cursor-pointer ';

                            if (isSelected) {
                                tileClasses +=
                                    'bg-indigo-600 text-white shadow-sm shadow-indigo-500/30';
                            } else if (isCurrent) {
                                tileClasses +=
                                    'border border-indigo-300 bg-indigo-50/70 text-indigo-700 hover:bg-indigo-100/80 dark:border-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 dark:hover:bg-indigo-900/60';
                            } else if (count > 0) {
                                tileClasses +=
                                    'bg-slate-50 text-slate-800 hover:bg-indigo-50 hover:text-indigo-700 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-300';
                            } else {
                                tileClasses +=
                                    'text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200';
                            }

                            return (
                                <button
                                    key={ym}
                                    type="button"
                                    onClick={() => handleSelect(ym)}
                                    className={tileClasses}
                                    title={`${formatMonthLong(ym)}${count > 0 ? ` (${count} tasks)` : ''}`}
                                >
                                    <span>{name}</span>
                                    {count > 0 && (
                                        <span
                                            className={`mt-0.5 rounded-full px-1.5 py-0.2 text-[9px] font-extrabold leading-tight ${
                                                isSelected
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/70 dark:text-indigo-300'
                                            }`}
                                        >
                                            {count}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    {/* Quick Footer Actions */}
                    <div className="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5 dark:border-slate-800">
                        {allowAll ? (
                            <button
                                type="button"
                                onClick={() => handleSelect('all')}
                                className={`flex-1 rounded-xl px-2.5 py-1.5 text-[11px] font-bold transition ${
                                    value === 'all'
                                        ? 'bg-indigo-600 text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                }`}
                            >
                                All Months
                            </button>
                        ) : (
                            <span />
                        )}
                        <button
                            type="button"
                            onClick={() => {
                                setViewYear(new Date().getFullYear());
                                handleSelect(currentYm);
                            }}
                            className="flex-1 rounded-xl bg-indigo-50 px-2.5 py-1.5 text-[11px] font-bold text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/70"
                        >
                            This Month
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
