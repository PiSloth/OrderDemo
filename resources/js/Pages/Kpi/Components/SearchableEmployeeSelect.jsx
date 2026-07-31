import React, { useState, useRef, useEffect } from 'react';

/**
 * A searchable dropdown for selecting an employee.
 * Props:
 *   users        – array of { id, name, department? }
 *   value        – currently selected user id (number|string)
 *   onChange     – called with the selected user id (number)
 *   className    – optional extra classes on the trigger button
 */
export default function SearchableEmployeeSelect({ users = [], value, onChange, className = '' }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const containerRef = useRef(null);
    const inputRef = useRef(null);

    const selected = users.find(u => String(u.id) === String(value));

    const filtered = query.trim()
        ? users.filter(u => u.name.toLowerCase().includes(query.toLowerCase()))
        : users;

    // Close on outside click
    useEffect(() => {
        const handle = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setOpen(false);
                setQuery('');
            }
        };
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, []);

    // Focus the search input when dropdown opens
    useEffect(() => {
        if (open && inputRef.current) {
            inputRef.current.focus();
        }
    }, [open]);

    const handleSelect = (id) => {
        onChange(id);
        setOpen(false);
        setQuery('');
    };

    return (
        <div ref={containerRef} className="relative">
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => setOpen(prev => !prev)}
                className={`flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition min-w-[160px] max-w-[240px] cursor-pointer ${className}`}
            >
                <svg className="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span className="flex-1 truncate text-left">
                    {selected ? selected.name : <span className="text-slate-400">Select employee…</span>}
                </span>
                <svg
                    className={`w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {/* Dropdown Panel */}
            {open && (
                <div
                    style={{ zIndex: 9999 }}
                    className="absolute top-full left-0 mt-1.5 w-72 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
                >
                    {/* Search input */}
                    <div className="p-2 border-b border-slate-100 dark:border-slate-700">
                        <div className="relative">
                            <svg className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                ref={inputRef}
                                type="text"
                                value={query}
                                onChange={e => setQuery(e.target.value)}
                                placeholder="Search employee…"
                                className="w-full h-8 pl-8 pr-3 text-sm rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            {query && (
                                <button
                                    type="button"
                                    onClick={() => setQuery('')}
                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Options list */}
                    <ul className="max-h-56 overflow-y-auto py-1">
                        {filtered.length === 0 ? (
                            <li className="px-4 py-3 text-xs text-slate-400 text-center">No employees found</li>
                        ) : (
                            filtered.map(u => {
                                const isSelected = String(u.id) === String(value);
                                return (
                                    <li key={u.id}>
                                        <button
                                            type="button"
                                            onClick={() => handleSelect(u.id)}
                                            className={`w-full text-left px-4 py-2 text-sm flex items-center gap-2 transition cursor-pointer ${
                                                isSelected
                                                    ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-medium'
                                                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60'
                                            }`}
                                        >
                                            <span className={`w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 ${
                                                isSelected ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300'
                                            }`}>
                                                {u.name.charAt(0).toUpperCase()}
                                            </span>
                                            <span className="flex-1 truncate">{u.name}</span>
                                            {isSelected && (
                                                <svg className="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            )}
                                        </button>
                                    </li>
                                );
                            })
                        )}
                    </ul>
                </div>
            )}
        </div>
    );
}
