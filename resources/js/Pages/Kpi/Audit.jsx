import React, { useState, useMemo, useEffect } from 'react';
import { router } from '@inertiajs/react';
import KpiLayout from '../../Layouts/KpiLayout';
import AuditDetailModal from './Components/AuditDetailModal';
import SearchableUserSelect from './Components/SearchableUserSelect';
import ExclusionRequestModal from './Components/ExclusionRequestModal';
import InboxModal from './Components/InboxModal';

export default function Audit({
    month,
    users = [],
    selectedUser,
    days = [],
    rows = [],
    groupSummaries = [],
    groupCards = { passed: 0, failed: 0, not_set: 0 },
    isSuperAdmin = false,
    canApproveExclusions = false,
    canManageHolidays = false,
    canApproveTasks = false,
    authUserId = null,
    taskAssignments = [],
    pendingExclusions = [],
    pendingExclusionsCount = 0,
}) {
    const [selectedMonth, setSelectedMonth] = useState(month || new Date().toISOString().slice(0, 7));
    const [selectedUserId, setSelectedUserId] = useState(selectedUser?.id || '');
    const [taskSearch, setTaskSearch] = useState('');
    const [selectedInstanceId, setSelectedInstanceId] = useState(null);
    const [markerTypeFilter, setMarkerTypeFilter] = useState(null); // scoped type for navigation
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [requestModalOpen, setRequestModalOpen] = useState(false);
    const [inboxOpen, setInboxOpen] = useState(false);

    const handleFilterChange = (newMonth, newUserId) => {
        const m = newMonth !== undefined ? newMonth : selectedMonth;
        const u = newUserId !== undefined ? newUserId : selectedUserId;

        setSelectedMonth(m);
        setSelectedUserId(u);

        router.get('/kpi/audit', { month: m, userId: u }, { preserveState: true, replace: true });
    };

    // Client-side task filter inside table grid
    const filteredRows = useMemo(() => {
        if (!taskSearch.trim()) return rows;
        const term = taskSearch.toLowerCase();
        return rows.filter((r) => {
            const title = r.assignment.template?.title || '';
            const groupName = r.assignment.template?.group?.name || '';
            return title.toLowerCase().includes(term) || groupName.toLowerCase().includes(term);
        });
    }, [rows, taskSearch]);

    // Parse day metadata and group days into weeks
    const { daysMeta, weekGroups } = useMemo(() => {
        const meta = [];
        const weeks = [];
        let currentWeekIndex = 0;
        let currentWeekDays = [];

        days.forEach((dayStr, idx) => {
            const d = new Date(dayStr + 'T00:00:00');
            const dayNum = d.getDate();
            const dayName = d.toLocaleDateString('en-US', { weekday: 'short' }); // Mon, Tue, etc.
            const isWeekend = d.getDay() === 0 || d.getDay() === 6;

            // A new week starts on Monday (d.getDay() === 1)
            if (idx > 0 && d.getDay() === 1) {
                weeks.push({
                    weekNum: currentWeekIndex + 1,
                    days: currentWeekDays,
                });
                currentWeekIndex++;
                currentWeekDays = [];
            }

            const dayObj = {
                dayStr,
                dayNum,
                dayName,
                isWeekend,
                weekIndex: currentWeekIndex,
                isWeekEnd: d.getDay() === 0 || idx === days.length - 1, // Sunday or last day of month
            };

            currentWeekDays.push(dayObj);
            meta.push(dayObj);
        });

        if (currentWeekDays.length > 0) {
            weeks.push({
                weekNum: currentWeekIndex + 1,
                days: currentWeekDays,
            });
        }

        return { daysMeta: meta, weekGroups: weeks };
    }, [days]);

    // Build a flat list of ALL clickable markers across all rows
    const allMarkersFlat = useMemo(() => {
        const list = [];
        rows.forEach((row) => {
            row.cells.forEach((cell) => {
                (cell.markers || []).forEach((m) => {
                    if (m && m.instance) {
                        list.push({
                            ...m,
                            template: m.instance.template || row.assignment?.template,
                        });
                    }
                });
            });
        });
        return list;
    }, [rows]);

    // Per-type counts (for filter pills in modal)
    const markerTypeCounts = useMemo(() => {
        const counts = {};
        allMarkersFlat.forEach((m) => {
            counts[m.type] = (counts[m.type] || 0) + 1;
        });
        return counts;
    }, [allMarkersFlat]);

    // Navigation list scoped to current type filter (null = all)
    const scopedMarkers = useMemo(() => {
        if (!markerTypeFilter) return allMarkersFlat;
        return allMarkersFlat.filter((m) => m.type === markerTypeFilter);
    }, [allMarkersFlat, markerTypeFilter]);

    // Find active marker by selectedInstanceId (resilient across data reloads)
    const selectedMarker = useMemo(() => {
        if (!selectedInstanceId) return null;
        return allMarkersFlat.find((m) => m.instance?.id === selectedInstanceId) || null;
    }, [allMarkersFlat, selectedInstanceId]);

    // Track active marker's index within current scoped filter list
    const currentIndex = useMemo(() => {
        if (!selectedMarker) return null;
        const idx = scopedMarkers.findIndex((m) => m.instance?.id === selectedMarker.instance?.id);
        return idx >= 0 ? idx : 0;
    }, [scopedMarkers, selectedMarker]);

    // Automatically advance to the next task when an approved/rejected task leaves the current filter list
    useEffect(() => {
        if (!isModalOpen || !selectedInstanceId) return;

        const isInScoped = scopedMarkers.some((m) => m.instance?.id === selectedInstanceId);

        if (!isInScoped && markerTypeFilter) {
            if (scopedMarkers.length > 0) {
                const targetIdx = Math.min(currentIndex ?? 0, scopedMarkers.length - 1);
                const nextMarker = scopedMarkers[targetIdx >= 0 ? targetIdx : 0];
                if (nextMarker && nextMarker.instance) {
                    setSelectedInstanceId(nextMarker.instance.id);
                }
            } else {
                setIsModalOpen(false);
                setSelectedInstanceId(null);
                setMarkerTypeFilter(null);
                if (typeof document !== 'undefined') {
                    document.body.style.overflow = '';
                }
            }
        }
    }, [scopedMarkers, isModalOpen, selectedInstanceId, markerTypeFilter, currentIndex]);

    const handleOpenMarker = (marker) => {
        if (!marker || !marker.instance) return;
        setMarkerTypeFilter(marker.type);
        setSelectedInstanceId(marker.instance.id);
        setIsModalOpen(true);
    };

    const handleNavigate = (direction) => {
        if (currentIndex === null || !scopedMarkers.length) return;
        const next = currentIndex + direction;
        if (next >= 0 && next < scopedMarkers.length) {
            const nextMarker = scopedMarkers[next];
            if (nextMarker && nextMarker.instance) {
                setSelectedInstanceId(nextMarker.instance.id);
            }
        }
    };

    const handleTypeFilterChange = (type) => {
        setMarkerTypeFilter(type);
        const scoped = type ? allMarkersFlat.filter((m) => m.type === type) : allMarkersFlat;
        if (scoped.length > 0) {
            setSelectedInstanceId(scoped[0].instance?.id || null);
        }
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedInstanceId(null);
        setMarkerTypeFilter(null);
        if (typeof document !== 'undefined') {
            document.body.style.overflow = '';
        }
    };

    return (
        <KpiLayout title="KPI Monthly Audit">
            <div className="space-y-6">
                {/* Header & Controls Bar */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2.5">
                            <div className="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <span>KPI Monthly Audit Matrix</span>
                        </h1>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Track daily task completion, monthly score percentages, and group rule evaluations.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        {/* Task Search Input */}
                        <div className="relative flex items-center min-w-[200px]">
                            <svg className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                value={taskSearch}
                                onChange={(e) => setTaskSearch(e.target.value)}
                                placeholder="Filter tasks in grid..."
                                className="w-full h-10 pl-10 pr-8 text-xs rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition shadow-xs"
                            />
                            {taskSearch && (
                                <button
                                    onClick={() => setTaskSearch('')}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold p-1"
                                >
                                    ✕
                                </button>
                            )}
                        </div>

                        {/* Month Picker */}
                        <div className="relative flex items-center h-10 px-3.5 bg-white dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition">
                            <svg className="w-4 h-4 text-slate-400 mr-2 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <input
                                type="month"
                                value={selectedMonth}
                                onChange={(e) => handleFilterChange(e.target.value, undefined)}
                                className="bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 border-none focus:outline-none cursor-pointer p-0"
                            />
                        </div>

                        {/* Searchable Employee Dropdown */}
                        {users.length > 0 && (
                            <SearchableUserSelect
                                users={users}
                                selectedUserId={selectedUserId}
                                onChange={(newUserId) => handleFilterChange(undefined, newUserId)}
                            />
                        )}

                        {/* ── Request button ── */}
                        <button
                            type="button"
                            onClick={() => setRequestModalOpen(true)}
                            className="flex items-center gap-1.5 h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm cursor-pointer"
                            title="Submit holiday or exclusion request"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Request
                        </button>

                        {/* ── Inbox button (approvers only) ── */}
                        {canApproveExclusions && (
                            <button
                                type="button"
                                onClick={() => setInboxOpen(true)}
                                className="relative flex items-center gap-1.5 h-10 px-3.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:border-amber-400 hover:text-amber-600 transition shadow-xs cursor-pointer"
                                title="View pending exclusion requests"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                Inbox
                                {pendingExclusionsCount > 0 && (
                                    <span className="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center">
                                        {pendingExclusionsCount > 99 ? '99+' : pendingExclusionsCount}
                                    </span>
                                )}
                            </button>
                        )}
                    </div>
                </div>

                {/* Group Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Passed Groups</span>
                            <h3 className="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{groupCards.passed}</h3>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <div className="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Failed Groups</span>
                            <h3 className="text-2xl font-black text-rose-700 dark:text-rose-300 mt-1">{groupCards.failed}</h3>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <div className="bg-slate-500/10 border border-slate-500/20 rounded-2xl p-4 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wider">Rule Not Set</span>
                            <h3 className="text-2xl font-black text-slate-700 dark:text-slate-300 mt-1">{groupCards.not_set}</h3>
                        </div>
                        <div className="w-10 h-10 rounded-xl bg-slate-500/20 flex items-center justify-center text-slate-600 dark:text-slate-400">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {/* Audit Grid Table Container */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                    <div className="overflow-x-auto max-h-[600px] no-scrollbar">
                        <table className="w-full text-left text-xs border-collapse min-w-[1200px]">
                            {/* Table Header */}
                            <thead className="sticky top-0 z-20 bg-slate-50 dark:bg-slate-800 shadow-xs border-b border-slate-200 dark:border-slate-700 select-none">
                                {/* Header Row 1: Grouping by Weeks */}
                                <tr className="text-slate-600 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                                    <th rowSpan={2} className="p-3 font-bold sticky left-0 z-30 bg-slate-50 dark:bg-slate-800 min-w-[240px] border-r border-slate-200 dark:border-slate-700 shadow-r text-slate-800 dark:text-slate-200">
                                        Task Template
                                    </th>
                                    {weekGroups.map((w, wIdx) => {
                                        const isOddWeek = wIdx % 2 === 1;
                                        return (
                                            <th
                                                key={w.weekNum}
                                                colSpan={w.days.length}
                                                className={`py-1 px-1 text-center font-bold text-[11px] uppercase tracking-wider border-r-2 border-r-indigo-300 dark:border-r-indigo-700/80 ${
                                                    isOddWeek
                                                        ? 'bg-indigo-100/70 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300'
                                                        : 'bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300'
                                                }`}
                                            >
                                                Week {w.weekNum}
                                            </th>
                                        );
                                    })}
                                    <th rowSpan={2} className="p-3 font-semibold text-center min-w-[70px] border-l border-slate-200 dark:border-slate-700">Must Do</th>
                                    <th rowSpan={2} className="p-3 font-semibold text-center min-w-[60px]">Done</th>
                                    <th rowSpan={2} className="p-3 font-semibold text-center min-w-[60px]">Fail</th>
                                    <th rowSpan={2} className="p-3 font-semibold text-center min-w-[60px]">Score %</th>
                                    <th rowSpan={2} className="p-3 font-semibold text-center min-w-[90px]">Rule Status</th>
                                </tr>

                                {/* Header Row 2: Days (Weekday + Day Number) */}
                                <tr className="text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    {daysMeta.map((d) => {
                                        const isOddWeek = d.weekIndex % 2 === 1;
                                        return (
                                            <th
                                                key={d.dayStr}
                                                className={`py-1.5 px-0.5 text-center w-8 min-w-[34px] ${
                                                    d.isWeekEnd ? 'border-r-2 border-r-indigo-300 dark:border-r-indigo-700/80' : 'border-r border-slate-200/50 dark:border-slate-800/50'
                                                } ${
                                                    d.isWeekend
                                                        ? 'bg-amber-500/15 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400'
                                                        : isOddWeek
                                                        ? 'bg-indigo-50/40 dark:bg-indigo-950/30'
                                                        : 'bg-slate-50 dark:bg-slate-800'
                                                }`}
                                            >
                                                <div className="text-[9px] uppercase font-extrabold tracking-tight opacity-75">
                                                    {d.dayName}
                                                </div>
                                                <div className="text-xs font-black">
                                                    {d.dayNum}
                                                </div>
                                            </th>
                                        );
                                    })}
                                </tr>
                            </thead>

                            {/* Table Body */}
                            <tbody className="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                                {filteredRows.length === 0 ? (
                                    <tr>
                                        <td colSpan={days.length + 6} className="p-8 text-center text-slate-400">
                                            No task assignments matching search in {selectedMonth}.
                                        </td>
                                    </tr>
                                ) : (
                                    filteredRows.map((row, rIdx) => (
                                        <tr key={row.assignment.id || rIdx} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                            {/* Task Name Column */}
                                            <td className="p-3 sticky left-0 z-10 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 font-medium">
                                                <div className="truncate max-w-[230px] text-slate-800 dark:text-slate-200" title={row.assignment.template?.title}>
                                                    {row.assignment.template?.title || 'Untitled Task'}
                                                </div>
                                                <div className="text-[10px] text-slate-400 dark:text-slate-500 flex items-center gap-1.5 mt-0.5">
                                                    <span className="capitalize">{row.assignment.template?.frequency}</span>
                                                    <span>•</span>
                                                    <span>{row.assignment.template?.group?.name || 'No Group'}</span>
                                                </div>
                                            </td>

                                            {/* Day Cells */}
                                            {row.cells.map((cell, cIdx) => {
                                                const d = daysMeta[cIdx] || {};
                                                const isOddWeek = (d.weekIndex ?? 0) % 2 === 1;
                                                const isWeekEnd = d.isWeekEnd;

                                                return (
                                                    <td
                                                        key={cell.date || cIdx}
                                                        className={`p-1 text-center align-middle ${
                                                            isWeekEnd ? 'border-r-2 border-r-indigo-300/80 dark:border-r-indigo-700/60' : 'border-r border-slate-200/40 dark:border-slate-800/40'
                                                        } ${
                                                            d.isWeekend
                                                                ? 'bg-amber-500/5 dark:bg-amber-500/10'
                                                                : isOddWeek
                                                                ? 'bg-indigo-50/20 dark:bg-indigo-950/15'
                                                                : ''
                                                        } ${cell.classes}`}
                                                    >
                                                        {cell.markers && cell.markers.length > 0 ? (
                                                            <div className="flex flex-col items-center justify-center gap-1">
                                                                {cell.markers.map((m, mIdx) => (
                                                                    <button
                                                                        key={mIdx}
                                                                        onClick={() => handleOpenMarker(m)}
                                                                        className={`w-6 h-6 rounded-md text-[10px] font-bold flex items-center justify-center shadow-xs transition hover:scale-110 cursor-pointer ${m.classes}`}
                                                                        title={`${m.label} (${d.dayName} ${d.dayNum}) - Click to inspect`}
                                                                    >
                                                                        {m.type === 'approved' ? '✓' : m.type === 'failed' ? '✕' : m.type === 'rejected' ? '!' : '•'}
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-[10px] text-slate-400 font-mono">
                                                                {cell.label || ''}
                                                            </span>
                                                        )}
                                                    </td>
                                                );
                                            })}

                                            {/* Summary Columns */}
                                            <td className="p-2 text-center font-mono font-medium text-slate-700 dark:text-slate-300">
                                                {row.summary?.must_do || 0}
                                            </td>
                                            <td className="p-2 text-center font-mono font-medium text-emerald-600 dark:text-emerald-400">
                                                {row.summary?.passed || 0}
                                            </td>
                                            <td className="p-2 text-center font-mono font-medium text-rose-600 dark:text-rose-400">
                                                {row.summary?.failed || 0}
                                            </td>
                                            <td className="p-2 text-center font-bold text-slate-800 dark:text-slate-200">
                                                {row.summary?.percentage || 0}%
                                            </td>
                                            <td className="p-2 text-center">
                                                {row.rule_evaluation?.passes_rule === true ? (
                                                    <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                        PASS
                                                    </span>
                                                ) : row.rule_evaluation?.passes_rule === false ? (
                                                    <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                                        FAIL
                                                    </span>
                                                ) : (
                                                    <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                        N/A
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* KPI Group Summaries Breakdown */}
                {groupSummaries.length > 0 && (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200/80 dark:border-slate-800 space-y-4">
                        <h3 className="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <svg className="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>KPI Group Aggregated Performance</span>
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {groupSummaries.map((g, idx) => (
                                <div key={g.group?.id || idx} className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                                    <div className="flex items-center justify-between">
                                        <h4 className="font-bold text-sm text-slate-800 dark:text-slate-200">{g.group_name}</h4>
                                        {g.passes_rule === true ? (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                GROUP PASS
                                            </span>
                                        ) : g.passes_rule === false ? (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                GROUP FAIL
                                            </span>
                                        ) : (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                NOT SET
                                            </span>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-4 gap-2 text-center text-xs">
                                        <div className="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-xs">
                                            <span className="block text-[10px] text-slate-400">Must-Do</span>
                                            <span className="font-bold text-slate-800 dark:text-slate-200">{g.must_do}</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-xs">
                                            <span className="block text-[10px] text-slate-400">Passed</span>
                                            <span className="font-bold text-emerald-600 dark:text-emerald-400">{g.passed}</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-xs">
                                            <span className="block text-[10px] text-slate-400">Failed</span>
                                            <span className="font-bold text-rose-600 dark:text-rose-400">{g.failed}</span>
                                        </div>
                                        <div className="p-2 rounded-lg bg-white dark:bg-slate-800 shadow-xs">
                                            <span className="block text-[10px] text-slate-400">Group Score</span>
                                            <span className="font-bold text-indigo-600 dark:text-indigo-400">{g.percentage}%</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Audit Inspection Modal */}
                <AuditDetailModal
                    isOpen={isModalOpen && selectedMarker !== null}
                    onClose={handleCloseModal}
                    selectedMarker={selectedMarker}
                    currentIndex={currentIndex}
                    totalCount={scopedMarkers.length}
                    onNavigate={handleNavigate}
                    markerTypeFilter={markerTypeFilter}
                    markerTypeCounts={markerTypeCounts}
                    onTypeFilterChange={handleTypeFilterChange}
                    isSuperAdmin={isSuperAdmin}
                    canApproveTasks={canApproveTasks}
                    authUserId={authUserId}
                />

                {/* Exclusion / Holiday Request Modal */}
                <ExclusionRequestModal
                    isOpen={requestModalOpen}
                    onClose={() => setRequestModalOpen(false)}
                    taskAssignments={taskAssignments}
                    users={users}
                    selectedUserId={selectedUser?.id}
                    canManageHolidays={canManageHolidays}
                    canApproveExclusions={canApproveExclusions}
                />

                {/* Inbox Modal */}
                <InboxModal
                    isOpen={inboxOpen}
                    onClose={() => setInboxOpen(false)}
                    pendingExclusions={pendingExclusions}
                />
            </div>
        </KpiLayout>
    );
}
