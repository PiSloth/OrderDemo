import React, { useState, useEffect, useMemo } from 'react';
import axios from 'axios';
import {
    Star,
    Award,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Clock,
    AlertCircle,
    Briefcase,
    Building2,
    Sparkles,
    Search,
    ChevronDown,
    ExternalLink,
    RefreshCw,
    Maximize2,
    BookOpen,
    Users,
    Filter
} from 'lucide-react';

export default function EmployeeSkillMatrixInline({
    selectedUserId: propUserId = null,
    onSelectUser = null,
    employees = [],
    departments = [],
    onOpenModal = null
}) {
    const [selectedUserId, setSelectedUserId] = useState(propUserId || (employees[0]?.id || ''));
    const [matrixData, setMatrixData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const [selectedCategoryFilter, setSelectedCategoryFilter] = useState('ALL');
    const [userSearchTerm, setUserSearchTerm] = useState('');
    const [selectedDeptFilter, setSelectedDeptFilter] = useState('ALL');

    // Sync propUserId if it changes from outside
    useEffect(() => {
        if (propUserId && String(propUserId) !== String(selectedUserId)) {
            setSelectedUserId(propUserId);
        }
    }, [propUserId]);

    // Handle user selection
    const handleUserChange = (id) => {
        setSelectedUserId(id);
        if (onSelectUser) {
            onSelectUser(id);
        }
    };

    // Trigger modal for selected user
    const handleTriggerModal = (userIdToOpen = null) => {
        const id = userIdToOpen || selectedUserId;
        if (!id) return;
        if (onOpenModal) {
            onOpenModal(id);
        } else {
            window.dispatchEvent(new CustomEvent('open-employee-skill-matrix', { detail: { userId: id } }));
        }
    };

    // Load detailed matrix data whenever selectedUserId changes
    useEffect(() => {
        if (!selectedUserId) return;

        setLoading(true);
        setErrorMessage('');
        axios.get(`/training/skill-matrix/employees/${selectedUserId}`)
            .then((res) => {
                setMatrixData(res.data);
            })
            .catch((err) => {
                console.error('Failed to load employee skill matrix', err);
                setErrorMessage(err?.response?.data?.error || 'Failed to retrieve skill matrix for this employee.');
                setMatrixData(null);
            })
            .finally(() => {
                setLoading(false);
            });
    }, [selectedUserId]);

    // Current selected user info from employees list or matrixData
    const selectedUser = useMemo(() => {
        return employees.find((e) => String(e.id) === String(selectedUserId)) || matrixData?.employee || null;
    }, [employees, selectedUserId, matrixData]);

    // Filter employees for selector
    const filteredEmployees = useMemo(() => {
        return employees.filter((e) => {
            const matchesSearch = !userSearchTerm.trim() ||
                e.name.toLowerCase().includes(userSearchTerm.toLowerCase()) ||
                (e.email && e.email.toLowerCase().includes(userSearchTerm.toLowerCase())) ||
                (e.office_position?.name && e.office_position.name.toLowerCase().includes(userSearchTerm.toLowerCase()));

            const matchesDept = selectedDeptFilter === 'ALL' || String(e.department_id) === String(selectedDeptFilter);

            return matchesSearch && matchesDept;
        });
    }, [employees, userSearchTerm, selectedDeptFilter]);

    // Unique categories from skills
    const categories = useMemo(() => {
        if (!matrixData?.skills) return [];
        const set = new Set();
        matrixData.skills.forEach((s) => {
            if (s.category_name) set.add(s.category_name);
        });
        return Array.from(set);
    }, [matrixData]);

    const filteredSkills = useMemo(() => {
        if (!matrixData?.skills) return [];
        if (selectedCategoryFilter === 'ALL') return matrixData.skills;
        return matrixData.skills.filter((s) => s.category_name === selectedCategoryFilter);
    }, [matrixData, selectedCategoryFilter]);

    const summary = matrixData?.summary || null;
    const isStarPerson = summary?.is_star_person || selectedUser?.is_star_person || false;
    const skillfulJobs = matrixData?.skillful_jobs || [];

    const employeeName = matrixData?.employee?.name || selectedUser?.name || 'Selected Employee';
    const employeeDept = matrixData?.employee?.department || selectedUser?.department?.name || 'Unassigned Department';
    const employeePosition = matrixData?.employee?.office_position || selectedUser?.office_position?.name || 'General Staff';
    const employeeEmail = matrixData?.employee?.email || selectedUser?.email || '';
    const employeePhoto = matrixData?.employee?.profile_photo_url || selectedUser?.profile_photo_url || null;

    return (
        <div className="space-y-6">
            {/* EMPLOYEE SELECTOR BAR */}
            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <Users className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-slate-900 dark:text-white">
                                Select Employee for Inline Matrix
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Switch employees to view their detailed skill scores and reliance analysis inline
                            </p>
                        </div>
                    </div>

                    {/* Quick Button to Launch Fullscreen Modal */}
                    <button
                        type="button"
                        onClick={() => handleTriggerModal()}
                        className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all"
                        title="Open this employee matrix in modal view"
                    >
                        <Maximize2 className="w-3.5 h-3.5" />
                        <span>Open Modal View</span>
                    </button>
                </div>

                {/* Filters & Selector row */}
                <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    {/* Employee Dropdown */}
                    <div className="sm:col-span-5">
                        <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                            Employee
                        </label>
                        <div className="relative">
                            <select
                                value={selectedUserId}
                                onChange={(e) => handleUserChange(e.target.value)}
                                className="w-full text-xs font-medium bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 pr-8 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer"
                            >
                                {employees.map((emp) => (
                                    <option key={emp.id} value={emp.id}>
                                        {emp.name} {emp.office_position?.name ? `(${emp.office_position.name})` : ''} - {emp.matrix_score > 0 ? `${emp.matrix_score}%` : 'Pending'} {emp.is_star_person ? '★' : ''}
                                    </option>
                                ))}
                            </select>
                            <ChevronDown className="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        </div>
                    </div>

                    {/* Department Filter for Search */}
                    <div className="sm:col-span-4">
                        <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                            Filter by Department
                        </label>
                        <div className="relative">
                            <select
                                value={selectedDeptFilter}
                                onChange={(e) => setSelectedDeptFilter(e.target.value)}
                                className="w-full text-xs font-medium bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 pr-8 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 appearance-none cursor-pointer"
                            >
                                <option value="ALL">All Departments ({employees.length})</option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>
                                        {dept.name}
                                    </option>
                                ))}
                            </select>
                            <ChevronDown className="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        </div>
                    </div>

                    {/* Search Input */}
                    <div className="sm:col-span-3">
                        <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                            Search
                        </label>
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="Search employee..."
                                value={userSearchTerm}
                                onChange={(e) => setUserSearchTerm(e.target.value)}
                                className="w-full text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            />
                            <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        </div>
                    </div>
                </div>

                {/* Quick Employee Badges Strip (Click to switch employee or click to view modal) */}
                {filteredEmployees.length > 0 && (
                    <div className="pt-2 border-t border-slate-100 dark:border-slate-800/60">
                        <div className="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>Quick Switch (Click to view inline, or double-click to view modal):</span>
                            <span>{filteredEmployees.length} matching</span>
                        </div>
                        <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                            {filteredEmployees.map((emp) => {
                                const isSelected = String(emp.id) === String(selectedUserId);
                                return (
                                    <button
                                        key={emp.id}
                                        type="button"
                                        onClick={() => handleUserChange(emp.id)}
                                        onDoubleClick={() => handleTriggerModal(emp.id)}
                                        className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold shrink-0 transition-all border ${
                                            isSelected
                                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-md ring-2 ring-indigo-500/30'
                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-indigo-300'
                                        }`}
                                    >
                                        <div className="w-5 h-5 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-bold">
                                            {emp.profile_photo_url ? (
                                                <img src={emp.profile_photo_url} alt="" className="w-full h-full object-cover" />
                                            ) : (
                                                emp.name.charAt(0)
                                            )}
                                        </div>
                                        <span>{emp.name}</span>
                                        {emp.is_star_person && <span>★</span>}
                                        <span className={`text-[10px] px-1.5 py-0.2 rounded-md font-mono ${
                                            isSelected ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                                        }`}>
                                            {emp.matrix_score > 0 ? `${emp.matrix_score}%` : '0%'}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>

            {/* INLINE SKILL MATRIX CARD */}
            <div className="relative rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl overflow-hidden">
                {/* Subtle Star Person Glow Effect */}
                {isStarPerson && (
                    <div className="absolute -top-32 left-1/2 -translate-x-1/2 w-[32rem] h-60 bg-amber-500/15 rounded-full blur-3xl pointer-events-none" />
                )}

                {/* CARD TOP HEADER */}
                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/90 flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center shadow-inner ${
                            isStarPerson
                                ? 'bg-gradient-to-tr from-amber-500 to-yellow-300 text-slate-950 shadow-amber-500/20'
                                : 'bg-gradient-to-tr from-indigo-600 to-sky-500 text-white shadow-indigo-500/20'
                        }`}>
                            {isStarPerson ? (
                                <Star className="w-5 h-5 fill-current animate-pulse" />
                            ) : (
                                <Sparkles className="w-5 h-5" />
                            )}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-[10px] font-extrabold tracking-wider uppercase text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800/60 px-2 py-0.5 rounded-md">
                                    Inline Employee View
                                </span>
                                {isStarPerson && (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-black tracking-wide uppercase text-amber-300 bg-amber-950/80 border border-amber-600/50 px-2.5 py-0.5 rounded-full shadow-sm animate-pulse">
                                        <Sparkles className="w-3 h-3 fill-amber-400 text-amber-400" />
                                        Star Person (≥90% Mastery)
                                    </span>
                                )}
                            </div>
                            <h2 className="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">
                                {employeeName} • Competency Profile
                            </h2>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={() => handleTriggerModal()}
                        className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-indigo-600 hover:text-white text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-all shadow-sm"
                        title="Click to pop out the modal dialog for this employee"
                    >
                        <ExternalLink className="w-3.5 h-3.5" />
                        <span>Show Employee Matrix Modal</span>
                    </button>
                </div>

                {/* CENTERED HERO PROFILE SECTION */}
                <div className="relative pt-6 pb-6 px-6 bg-gradient-to-b from-slate-100 via-slate-50 to-white dark:from-slate-950/80 dark:via-slate-900/60 dark:to-slate-900 border-b border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center text-center">
                    {/* Interactive Profile Avatar */}
                    <div
                        className="relative group cursor-pointer"
                        onClick={() => handleTriggerModal()}
                        title="Click to open full modal"
                    >
                        <div className={`absolute -inset-1.5 rounded-full blur-md opacity-75 group-hover:opacity-100 transition duration-300 ${
                            isStarPerson ? 'bg-gradient-to-r from-amber-500 to-yellow-300' : 'bg-gradient-to-r from-indigo-500 to-sky-400'
                        }`} />

                        <div className="relative w-24 h-24 rounded-full ring-4 ring-white dark:ring-slate-900 bg-slate-200 dark:bg-slate-800 shadow-2xl overflow-hidden flex items-center justify-center">
                            {employeePhoto ? (
                                <img
                                    src={employeePhoto}
                                    alt={employeeName}
                                    className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                />
                            ) : (
                                <div className="w-full h-full bg-gradient-to-br from-indigo-700 via-indigo-900 to-slate-900 flex items-center justify-center text-4xl font-black text-white uppercase select-none">
                                    {employeeName.charAt(0)}
                                </div>
                            )}

                            {/* Hover overlay hint */}
                            <div className="absolute inset-0 bg-slate-950/70 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center transition-opacity duration-200 text-white p-1">
                                <ExternalLink className="w-5 h-5 mb-0.5 text-indigo-300" />
                                <span className="text-[10px] font-extrabold uppercase tracking-wider text-slate-200">
                                    Open Modal
                                </span>
                            </div>
                        </div>

                        {/* Corner badge */}
                        <div className={`absolute bottom-0 right-0 w-8 h-8 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-md ${
                            isStarPerson
                                ? 'bg-amber-400 text-slate-950'
                                : 'bg-indigo-600 text-white'
                        }`}>
                            {isStarPerson ? <Star className="w-4 h-4 fill-current" /> : <ShieldCheck className="w-4 h-4" />}
                        </div>
                    </div>

                    {/* Name & Email */}
                    <div className="mt-3.5 space-y-1">
                        <h3
                            onClick={() => handleTriggerModal()}
                            className="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center justify-center gap-2 cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                        >
                            <span>{employeeName}</span>
                            {isStarPerson && <span>🌟</span>}
                        </h3>
                        {employeeEmail && (
                            <p className="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                {employeeEmail}
                            </p>
                        )}
                    </div>

                    {/* Position & Department */}
                    <div className="mt-3.5 flex flex-wrap items-center justify-center gap-2.5 sm:gap-3 text-xs">
                        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700/80 shadow-sm text-slate-700 dark:text-slate-200">
                            <Briefcase className="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 shrink-0" />
                            <span className="text-slate-500 dark:text-slate-400 text-[11px] font-medium uppercase tracking-wider">Position:</span>
                            <span className="font-bold text-slate-900 dark:text-white">{employeePosition}</span>
                        </div>

                        <span className="text-slate-300 dark:text-slate-600 hidden sm:inline">•</span>

                        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700/80 shadow-sm text-slate-700 dark:text-slate-200">
                            <Building2 className="w-3.5 h-3.5 text-sky-500 dark:text-sky-400 shrink-0" />
                            <span className="text-slate-500 dark:text-slate-400 text-[11px] font-medium uppercase tracking-wider">Department:</span>
                            <span className="font-bold text-slate-900 dark:text-white">{employeeDept}</span>
                        </div>
                    </div>
                </div>

                {/* MAIN CONTENT BODY */}
                <div className="p-6 sm:p-8 space-y-6">
                    {/* Error message */}
                    {errorMessage && (
                        <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-200 text-sm flex items-center gap-3">
                            <AlertCircle className="w-5 h-5 text-rose-500 dark:text-rose-400 shrink-0" />
                            <span>{errorMessage}</span>
                        </div>
                    )}

                    {/* Loading spinner */}
                    {loading && (
                        <div className="py-16 flex flex-col items-center justify-center text-slate-500 dark:text-slate-400 space-y-3">
                            <RefreshCw className="w-9 h-9 animate-spin text-indigo-500 dark:text-indigo-400" />
                            <p className="text-sm font-medium">Computing competency scores and reliance matrix...</p>
                        </div>
                    )}

                    {!loading && matrixData && (
                        <>
                            {/* OVERALL SYNTHESIS: HOW WE SHOULD RELY ON THIS PERSON */}
                            <div className={`relative p-5 sm:p-6 rounded-3xl border transition-all ${
                                isStarPerson
                                    ? 'bg-gradient-to-r from-amber-50 via-yellow-50 to-white dark:from-amber-950/40 dark:via-yellow-950/20 dark:to-slate-900/80 border-amber-200 dark:border-amber-500/40 shadow-lg shadow-amber-500/5'
                                    : 'bg-gradient-to-r from-white via-slate-50 to-indigo-50/60 dark:from-slate-900 dark:via-slate-900/90 dark:to-indigo-950/30 border-slate-200 dark:border-slate-800'
                            }`}>
                                <div className="flex flex-col md:flex-row md:items-start justify-between gap-5">
                                    <div className="space-y-2 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="text-[11px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                                Strategic Workforce Assessment
                                            </span>
                                            {isStarPerson ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-amber-100 dark:bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-400/40">
                                                    🌟 Star Person (All Skills ≥90%)
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                    Standard Operational Matrix
                                                </span>
                                            )}
                                        </div>

                                        <h3 className="text-lg sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                                            <span>How should we rely on {employeeName}?</span>
                                        </h3>

                                        <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 space-y-1.5">
                                            <div className="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                                                <ShieldCheck className={`w-4 h-4 ${isStarPerson ? 'text-amber-500 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'}`} />
                                                <span>{summary?.overall_reliance}</span>
                                            </div>
                                            <p className="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                                {summary?.overall_description}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Quick Metrics Cards */}
                                    <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-2 gap-2.5 shrink-0 min-w-[260px]">
                                        <div className="p-3 rounded-2xl bg-white dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 flex flex-col justify-between">
                                            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Avg Score</span>
                                            <span className={`text-xl font-black ${
                                                summary?.average_score >= 90 ? 'text-amber-500 dark:text-amber-400' : summary?.average_score >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-sky-600 dark:text-sky-400'
                                            }`}>
                                                {summary?.average_score}%
                                            </span>
                                        </div>

                                        <div className="p-3 rounded-2xl bg-white dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 flex flex-col justify-between">
                                            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Star Skills (≥90%)</span>
                                            <span className="text-xl font-black text-amber-500 dark:text-amber-400 flex items-center gap-1">
                                                <Star className="w-4 h-4 fill-amber-400" />
                                                {summary?.star_skills} <span className="text-xs font-normal text-slate-400 dark:text-slate-500">/ {summary?.total_skills}</span>
                                            </span>
                                        </div>

                                        <div className="p-3 rounded-2xl bg-white dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 flex flex-col justify-between">
                                            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Skills Passed</span>
                                            <span className="text-xl font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                <CheckCircle2 className="w-4 h-4" />
                                                {summary?.passed_skills} <span className="text-xs font-normal text-slate-400 dark:text-slate-500">/ {summary?.total_skills}</span>
                                            </span>
                                        </div>

                                        <div className="p-3 rounded-2xl bg-white dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 flex flex-col justify-between">
                                            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Courses</span>
                                            <span className="text-xl font-black text-slate-900 dark:text-white">
                                                {summary?.total_skills}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* SKILLFUL JOBS DOMAINS */}
                                {skillfulJobs.length > 0 && (
                                    <div className="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800/80">
                                        <div className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2.5 flex items-center gap-1.5">
                                            <Award className="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" />
                                            <span>Demonstrated High Competencies (Skillful in these Domains/Jobs):</span>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {skillfulJobs.map((job, idx) => (
                                                <div
                                                    key={idx}
                                                    className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold border ${
                                                        job.is_star
                                                            ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-200 border-amber-200 dark:border-amber-600/40 shadow-sm'
                                                            : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-200 border-emerald-200 dark:border-emerald-600/30'
                                                    }`}
                                                >
                                                    {job.is_star && <Star className="w-3 h-3 fill-amber-400 text-amber-400" />}
                                                    <span>{job.title}</span>
                                                    <span className="text-[10px] px-1.5 py-0.2 rounded-md bg-slate-900 dark:bg-slate-900/80 text-white font-mono">
                                                        {job.score}%
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* SKILL MATRIX TABLE SECTION */}
                            <div className="space-y-4">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <h4 className="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                            <BookOpen className="w-4 h-4 text-indigo-500 dark:text-indigo-400" />
                                            <span>Training Modules & Last Past Scores</span>
                                        </h4>
                                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            Detailed breakdown of assigned modules, required passing threshold, and last scored assessment.
                                        </p>
                                    </div>

                                    {/* Category Filter Pills */}
                                    {categories.length > 0 && (
                                        <div className="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                                            <button
                                                type="button"
                                                onClick={() => setSelectedCategoryFilter('ALL')}
                                                className={`px-3 py-1 rounded-xl text-xs font-bold transition ${
                                                    selectedCategoryFilter === 'ALL'
                                                        ? 'bg-indigo-600 text-white shadow-sm'
                                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                                }`}
                                            >
                                                All ({matrixData.skills.length})
                                            </button>
                                            {categories.map((cat) => (
                                                <button
                                                    key={cat}
                                                    type="button"
                                                    onClick={() => setSelectedCategoryFilter(cat)}
                                                    className={`px-3 py-1 rounded-xl text-xs font-bold transition whitespace-nowrap ${
                                                        selectedCategoryFilter === cat
                                                            ? 'bg-indigo-600 text-white shadow-sm'
                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                                    }`}
                                                >
                                                    {cat}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                {/* Custom Responsive Matrix Table */}
                                <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 overflow-hidden shadow-inner">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-xs border-collapse">
                                            <thead>
                                                <tr className="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    <th className="py-3.5 px-4">Training Skill Title</th>
                                                    <th className="py-3.5 px-3">Category</th>
                                                    <th className="py-3.5 px-3 text-center">Req. Passing</th>
                                                    <th className="py-3.5 px-3 text-center">Last Past Score</th>
                                                    <th className="py-3.5 px-3">Proficiency / Star Level</th>
                                                    <th className="py-3.5 px-4">Operational Reliance Guidance</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-200 dark:divide-slate-800/60">
                                                {filteredSkills.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={6} className="py-8 text-center text-slate-400 dark:text-slate-500">
                                                            No training modules found for this employee under the selected category.
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    filteredSkills.map((skill) => {
                                                        const hasAttempt = skill.last_score !== null;
                                                        const isStar = skill.is_star;

                                                        return (
                                                            <tr
                                                                key={skill.assignment_id}
                                                                className={`hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors ${
                                                                    isStar ? 'bg-amber-50 dark:bg-amber-950/10' : ''
                                                                }`}
                                                            >
                                                                {/* Training Title & Code */}
                                                                <td className="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                                    <div className="flex items-start gap-2">
                                                                        {isStar ? (
                                                                            <Star className="w-4 h-4 text-amber-500 dark:text-amber-400 fill-amber-400 shrink-0 mt-0.5" />
                                                                        ) : skill.is_passed ? (
                                                                            <CheckCircle2 className="w-4 h-4 text-emerald-500 dark:text-emerald-400 shrink-0 mt-0.5" />
                                                                        ) : hasAttempt ? (
                                                                            <XCircle className="w-4 h-4 text-rose-500 dark:text-rose-400 shrink-0 mt-0.5" />
                                                                        ) : (
                                                                            <Clock className="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0 mt-0.5" />
                                                                        )}
                                                                        <div>
                                                                            <div className="text-slate-900 dark:text-slate-100 font-bold leading-snug">
                                                                                {skill.training_title}
                                                                            </div>
                                                                            <span className="text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                                                                {skill.training_code || 'TR-MOD'}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </td>

                                                                {/* Category */}
                                                                <td className="py-3.5 px-3">
                                                                    <span className="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                                                        {skill.category_name}
                                                                    </span>
                                                                </td>

                                                                {/* Required Passing Score */}
                                                                <td className="py-3.5 px-3 text-center font-mono text-slate-600 dark:text-slate-300 font-bold">
                                                                    {skill.passing_score}%
                                                                </td>

                                                                {/* Last Score */}
                                                                <td className="py-3.5 px-3 text-center">
                                                                    {hasAttempt ? (
                                                                        <div className="inline-flex flex-col items-center">
                                                                            <div className="flex items-center gap-1.5">
                                                                                <span className={`text-sm font-black font-mono ${
                                                                                    isStar
                                                                                        ? 'text-amber-600 dark:text-amber-300'
                                                                                        : skill.is_passed
                                                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                                                        : 'text-rose-500 dark:text-rose-400'
                                                                                }`}>
                                                                                    {skill.last_score}%
                                                                                </span>
                                                                                {isStar && (
                                                                                    <span className="text-[10px] text-amber-500 dark:text-amber-400 font-extrabold uppercase">
                                                                                        ★
                                                                                    </span>
                                                                                )}
                                                                            </div>
                                                                            {/* Mini visual bar */}
                                                                            <div className="w-16 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden mt-1">
                                                                                <div
                                                                                    className={`h-full rounded-full ${
                                                                                        isStar
                                                                                            ? 'bg-amber-400'
                                                                                            : skill.is_passed
                                                                                            ? 'bg-emerald-500'
                                                                                            : 'bg-rose-500'
                                                                                    }`}
                                                                                    style={{ width: `${Math.min(100, skill.last_score)}%` }}
                                                                                />
                                                                            </div>
                                                                        </div>
                                                                    ) : (
                                                                        <span className="text-[11px] text-slate-400 dark:text-slate-500 italic">
                                                                            Pending
                                                                        </span>
                                                                    )}
                                                                </td>

                                                                {/* Proficiency / Star Level Tag */}
                                                                <td className="py-3.5 px-3">
                                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-extrabold tracking-wide ${
                                                                        isStar
                                                                            ? 'bg-amber-100 dark:bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-500/40 shadow-sm'
                                                                            : skill.is_passed
                                                                            ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-600/30'
                                                                            : hasAttempt
                                                                            ? 'bg-rose-50 dark:bg-rose-500/15 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-600/30'
                                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700/60'
                                                                    }`}>
                                                                        {isStar && <Star className="w-3 h-3 fill-amber-400" />}
                                                                        <span>{skill.status_label}</span>
                                                                    </span>
                                                                </td>

                                                                {/* Operational Reliance Guidance */}
                                                                <td className="py-3.5 px-4">
                                                                    <div className="max-w-xs space-y-0.5">
                                                                        <div className="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                                                            {skill.rely_rating}
                                                                        </div>
                                                                        <p className="text-[11px] text-slate-500 dark:text-slate-400 leading-snug line-clamp-2">
                                                                            {skill.rely_description}
                                                                        </p>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        );
                                                    })
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
