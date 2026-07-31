import React, { useState, useRef, useEffect } from 'react';

export default function SearchableUserSelect({ users = [], selectedUserId, onChange }) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef(null);

    const selectedUser = users.find((u) => String(u.id) === String(selectedUserId)) || users[0];

    const filteredUsers = users.filter((u) => {
        const term = search.toLowerCase();
        return (
            u.name.toLowerCase().includes(term) ||
            (u.email && u.email.toLowerCase().includes(term)) ||
            (u.department?.name && u.department.name.toLowerCase().includes(term))
        );
    });

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <div className="relative min-w-[220px]" ref={containerRef}>
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full h-10 px-3.5 flex items-center justify-between gap-3 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
            >
                <div className="flex items-center gap-2.5 truncate">
                    <div className="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[11px] shrink-0 shadow-xs">
                        {selectedUser?.name?.charAt(0)?.toUpperCase() || 'U'}
                    </div>
                    <span className="truncate">{selectedUser?.name || 'Select Employee'}</span>
                </div>

                <svg
                    className={`w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0 ${isOpen ? 'rotate-180' : ''}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {/* Dropdown Menu */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden no-scrollbar animate-in fade-in zoom-in-95 duration-100">
                    {/* Search Input */}
                    <div className="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50">
                        <div className="relative flex items-center">
                            <svg className="w-4 h-4 absolute left-3 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by employee name..."
                                className="w-full h-9 pl-9 pr-8 text-xs rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                autoFocus
                            />
                            {search && (
                                <button
                                    onClick={() => setSearch('')}
                                    className="absolute right-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold p-1"
                                >
                                    ✕
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Options List */}
                    <div className="max-h-60 overflow-y-auto p-1.5 space-y-0.5 no-scrollbar">
                        {filteredUsers.length === 0 ? (
                            <div className="p-4 text-center text-xs text-slate-400">
                                No employees found.
                            </div>
                        ) : (
                            filteredUsers.map((u) => {
                                const isSelected = String(u.id) === String(selectedUserId);
                                return (
                                    <button
                                        key={u.id}
                                        type="button"
                                        onClick={() => {
                                            onChange(u.id);
                                            setIsOpen(false);
                                            setSearch('');
                                        }}
                                        className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition text-left ${
                                            isSelected
                                                ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-semibold'
                                                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2.5 truncate">
                                            <div className={`w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 ${
                                                isSelected ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                                            }`}>
                                                {u.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="truncate">
                                                <span className="block truncate font-medium">{u.name}</span>
                                                {u.department?.name && (
                                                    <span className="block text-[10px] text-slate-400 dark:text-slate-500 truncate">
                                                        {u.department.name}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        {isSelected && (
                                            <svg className="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        )}
                                    </button>
                                );
                            })
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
