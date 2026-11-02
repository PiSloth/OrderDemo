import React, { useState, useRef, useEffect, useMemo } from 'react';

export default function SearchableUserSelect({ users = [], selectedUserId, onChange }) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [filterTab, setFilterTab] = useState('all'); // 'all' | 'needs_approval'
    const containerRef = useRef(null);

    const selectedUser = users.find((u) => String(u.id) === String(selectedUserId)) || users[0];

    const usersNeedingApproval = useMemo(() => {
        return users.filter((u) => (u.pending_my_approvals_count || 0) > 0 || (u.total_pending_my_approvals_count || 0) > 0);
    }, [users]);

    const totalNeedingApprovalCount = usersNeedingApproval.length;

    const filteredUsers = useMemo(() => {
        return users.filter((u) => {
            if (filterTab === 'needs_approval') {
                const hasPending = (u.pending_my_approvals_count || 0) > 0 || (u.total_pending_my_approvals_count || 0) > 0;
                if (!hasPending) return false;
            }

            const term = search.toLowerCase();
            return (
                u.name.toLowerCase().includes(term) ||
                (u.email && u.email.toLowerCase().includes(term)) ||
                (u.department?.name && u.department.name.toLowerCase().includes(term))
            );
        });
    }, [users, filterTab, search]);

    // Sort so users with pending approvals in current month appear first, then by name
    const displayUsers = useMemo(() => {
        return [...filteredUsers].sort((a, b) => {
            const countA = (a.pending_my_approvals_count || 0) * 10 + (a.total_pending_my_approvals_count || 0);
            const countB = (b.pending_my_approvals_count || 0) * 10 + (b.total_pending_my_approvals_count || 0);
            if (countA !== countB) return countB - countA;
            return a.name.localeCompare(b.name);
        });
    }, [filteredUsers]);

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
                className="w-full h-10 px-3.5 flex items-center justify-between gap-2.5 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:outline-none cursor-pointer"
            >
                <div className="flex items-center gap-2 truncate">
                    <div className="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[11px] shrink-0 shadow-xs">
                        {selectedUser?.name?.charAt(0)?.toUpperCase() || 'U'}
                    </div>
                    <span className="truncate">{selectedUser?.name || 'Select Employee'}</span>
                    {(selectedUser?.pending_my_approvals_count || 0) > 0 && (
                        <span className="px-1.5 py-0.2 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 font-extrabold text-[10px] shrink-0 border border-amber-300 dark:border-amber-800 flex items-center gap-1">
                            <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" />
                            {selectedUser.pending_my_approvals_count} to approve
                        </span>
                    )}
                </div>

                <div className="flex items-center gap-1.5 shrink-0">
                    {totalNeedingApprovalCount > 0 && (selectedUser?.pending_my_approvals_count || 0) === 0 && (
                        <span
                            className="w-2 h-2 rounded-full bg-amber-500 animate-pulse"
                            title={`${totalNeedingApprovalCount} employees need your approval`}
                        />
                    )}
                    <svg
                        className={`w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0 ${isOpen ? 'rotate-180' : ''}`}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>

            {/* Dropdown Menu */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden no-scrollbar animate-in fade-in zoom-in-95 duration-100">
                    {/* Search Input */}
                    <div className="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50">
                        <div className="relative flex items-center">
                            <svg className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by employee name..."
                                className="w-full h-9 pl-10 pr-8 text-xs rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                autoFocus
                            />
                            {search && (
                                <button
                                    onClick={() => setSearch('')}
                                    className="absolute right-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold p-1 cursor-pointer"
                                >
                                    ✕
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Filter Tabs: All vs Need Approval */}
                    {totalNeedingApprovalCount > 0 && (
                        <div className="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-1 bg-slate-50/50 dark:bg-slate-900/50">
                            <button
                                type="button"
                                onClick={() => setFilterTab('all')}
                                className={`flex-1 py-1 px-2 rounded-lg text-[11px] font-semibold transition text-center cursor-pointer ${
                                    filterTab === 'all'
                                        ? 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 shadow-2xs border border-slate-200/80 dark:border-slate-700 font-bold'
                                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                                }`}
                            >
                                All ({users.length})
                            </button>
                            <button
                                type="button"
                                onClick={() => setFilterTab('needs_approval')}
                                className={`flex-1 py-1 px-2 rounded-lg text-[11px] font-bold transition flex items-center justify-center gap-1.5 cursor-pointer ${
                                    filterTab === 'needs_approval'
                                        ? 'bg-amber-500 text-white shadow-2xs'
                                        : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30'
                                }`}
                            >
                                <span className={`w-1.5 h-1.5 rounded-full ${filterTab === 'needs_approval' ? 'bg-white' : 'bg-amber-500 animate-pulse'}`} />
                                <span>Need Approval</span>
                                <span className={`px-1.5 py-0.2 rounded-full text-[9px] font-extrabold ${
                                    filterTab === 'needs_approval' ? 'bg-black/20 text-white' : 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300'
                                }`}>
                                    {totalNeedingApprovalCount}
                                </span>
                            </button>
                        </div>
                    )}

                    {/* Options List */}
                    <div className="max-h-64 overflow-y-auto p-1.5 space-y-0.5 no-scrollbar">
                        {displayUsers.length === 0 ? (
                            <div className="p-4 text-center text-xs text-slate-400">
                                {filterTab === 'needs_approval' ? 'No employees currently need your approval.' : 'No employees found.'}
                            </div>
                        ) : (
                            displayUsers.map((u) => {
                                const isSelected = String(u.id) === String(selectedUserId);
                                const pendingMonth = u.pending_my_approvals_count || 0;
                                const pendingTotal = u.total_pending_my_approvals_count || 0;
                                const hasPending = pendingMonth > 0 || pendingTotal > 0;

                                return (
                                    <button
                                        key={u.id}
                                        type="button"
                                        onClick={() => {
                                            onChange(u.id);
                                            setIsOpen(false);
                                            setSearch('');
                                        }}
                                        className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition text-left cursor-pointer ${
                                            isSelected
                                                ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-semibold'
                                                : hasPending
                                                ? 'hover:bg-amber-50/60 dark:hover:bg-amber-950/20 text-slate-700 dark:text-slate-300'
                                                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2.5 truncate flex-1 mr-2">
                                            <div className={`w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 ${
                                                isSelected
                                                    ? 'bg-indigo-600 text-white'
                                                    : hasPending
                                                    ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300/80 dark:border-amber-700'
                                                    : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                                            }`}>
                                                {u.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="truncate flex-1">
                                                <div className="flex items-center gap-1.5 truncate">
                                                    <span className="truncate font-medium">{u.name}</span>
                                                </div>
                                                {u.department?.name && (
                                                    <span className="block text-[10px] text-slate-400 dark:text-slate-500 truncate">
                                                        {u.department.name}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 shrink-0">
                                            {pendingMonth > 0 ? (
                                                <span className="px-1.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 font-extrabold text-[10px] border border-amber-300 dark:border-amber-800 flex items-center gap-1">
                                                    <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" />
                                                    {pendingMonth} to approve
                                                </span>
                                            ) : pendingTotal > 0 ? (
                                                <span className="px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px]">
                                                    {pendingTotal} pending
                                                </span>
                                            ) : null}

                                            {isSelected && (
                                                <svg className="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            )}
                                        </div>
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
