import React, { useState, useMemo, useRef, useEffect } from 'react';

export default function SearchableUserSelect({
    users = [],
    selectedUserId = '',
    selectedDepartmentId = '',
    onSelectUser = () => {},
    placeholder = 'Search & select employee (တာဝန်ခံ)...',
    disabled = false,
    autoOpen = true,
}) {
    const [isOpen, setIsOpen] = useState(autoOpen);
    const [searchTerm, setSearchTerm] = useState('');
    const containerRef = useRef(null);
    const searchInputRef = useRef(null);

    // Auto open & focus search input when department is selected or component mounts
    useEffect(() => {
        if (selectedDepartmentId && !disabled) {
            setIsOpen(true);
            const timer = setTimeout(() => {
                if (searchInputRef.current) {
                    searchInputRef.current.focus();
                }
            }, 60);
            return () => clearTimeout(timer);
        }
    }, [selectedDepartmentId, disabled]);

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

    // Filter users by selected department first, then by search term
    const filteredUsers = useMemo(() => {
        let list = users;

        // Filter by selected department if specified
        if (selectedDepartmentId) {
            list = list.filter((u) => String(u.department_id) === String(selectedDepartmentId));
        }

        // Filter by search term
        if (searchTerm.trim()) {
            const term = searchTerm.toLowerCase();
            list = list.filter(
                (u) =>
                    (u.name || '').toLowerCase().includes(term) ||
                    (u.email || '').toLowerCase().includes(term)
            );
        }

        return list;
    }, [users, selectedDepartmentId, searchTerm]);

    const selectedUser = useMemo(() => {
        return users.find((u) => String(u.id) === String(selectedUserId));
    }, [users, selectedUserId]);

    const handleSelect = (user) => {
        onSelectUser(user ? String(user.id) : '');
        setIsOpen(false);
        setSearchTerm('');
    };

    return (
        <div className="relative w-full" ref={containerRef}>
            {/* Selected User Display Button */}
            <button
                type="button"
                disabled={disabled}
                onClick={() => setIsOpen(!isOpen)}
                className={`w-full text-left rounded-xl border bg-white px-3.5 py-2.5 text-xs font-semibold shadow-sm transition flex items-center justify-between dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 ${
                    disabled
                        ? 'opacity-50 cursor-not-allowed border-slate-200 dark:border-slate-800'
                        : 'border-emerald-300 dark:border-emerald-800 hover:border-emerald-400'
                }`}
            >
                {selectedUser ? (
                    <div className="flex items-center gap-2 truncate">
                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-bold text-[10px]">
                            {selectedUser.name ? selectedUser.name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <span className="truncate text-slate-900 dark:text-white font-medium">
                            {selectedUser.name} <span className="text-slate-400 font-normal">({selectedUser.email})</span>
                        </span>
                    </div>
                ) : (
                    <span className="text-slate-400 dark:text-slate-500">{placeholder}</span>
                )}

                <div className="flex items-center gap-1">
                    {selectedUser && (
                        <span
                            onClick={(e) => {
                                e.stopPropagation();
                                handleSelect(null);
                            }}
                            className="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700"
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
                <div className="absolute left-0 right-0 z-50 mt-1.5 max-h-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-800 dark:bg-slate-900 flex flex-col space-y-2">
                    {/* Search Input Field */}
                    <div className="relative">
                        <input
                            ref={searchInputRef}
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Type employee name or email to search..."
                            className="w-full rounded-xl border border-slate-300 bg-slate-50 pl-8 pr-3 py-2 text-xs text-slate-800 shadow-inner dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            autoFocus
                        />
                        <svg className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    {/* Employee List */}
                    <div className="overflow-y-auto max-h-48 space-y-1 pr-1">
                        {filteredUsers.length > 0 ? (
                            filteredUsers.map((u) => {
                                const isSelected = String(u.id) === String(selectedUserId);
                                return (
                                    <button
                                        key={u.id}
                                        type="button"
                                        onClick={() => handleSelect(u)}
                                        className={`w-full text-left rounded-xl px-3 py-2 text-xs transition flex items-center justify-between ${
                                            isSelected
                                                ? 'bg-emerald-50 text-emerald-900 font-bold dark:bg-emerald-950/70 dark:text-emerald-200'
                                                : 'hover:bg-slate-100 text-slate-700 dark:hover:bg-slate-800 dark:text-slate-300'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2 truncate">
                                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] dark:bg-slate-700 dark:text-slate-200">
                                                {u.name ? u.name.charAt(0).toUpperCase() : 'U'}
                                            </div>
                                            <div className="truncate">
                                                <p className="font-semibold text-slate-900 dark:text-white truncate">{u.name}</p>
                                                <p className="text-[10px] text-slate-400 truncate">{u.email}</p>
                                            </div>
                                        </div>
                                        {isSelected && <span className="text-emerald-600 dark:text-emerald-400 font-bold">✓</span>}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="p-4 text-center text-xs text-slate-400 italic">
                                {selectedDepartmentId
                                    ? 'No employees found in selected department.'
                                    : 'No employees matching search.'}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
