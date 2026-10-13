import React, { useState, useEffect, useMemo, useRef } from 'react';
import { createPortal } from 'react-dom';
import axios from 'axios';
import UserSelectModal from '../IT/UserSelectModal';
import {
    X,
    Star,
    Award,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Clock,
    AlertCircle,
    User as UserIcon,
    Briefcase,
    Building2,
    Sparkles,
    Search,
    ChevronDown,
    ExternalLink,
    RefreshCw,
    TrendingUp,
    Info,
    HelpCircle,
    BarChart3,
    BookOpen,
    Users
} from 'lucide-react';

/**
 * EmployeeSkillMatrixModal
 *
 * Global Modal for inspecting an employee's skill matrix:
 * - Fullscreen / Full-height modal layout
 * - Centered Hero Profile Section:
 *     - Circular Avatar Profile image / initials with interactive hover state
 *     - Clicking Avatar triggers global UserSelectModal component
 *     - User Name displayed directly below the avatar
 *     - Office Position & Department displayed side by side beneath the name
 * - Displays related training titles, pass requirements, and their last past/attempted score
 * - Highlights mastery & identifies if employee has >= 90% across skills ("Star Person" badge)
 * - Synthesizes jobs they excel in and practical recommendations on how to rely on them
 * - Designed with custom modern UI (Tailwind CSS, clean glassmorphism dark theme)
 *
 * Triggerable via:
 * 1. window.dispatchEvent(new CustomEvent('open-employee-skill-matrix', { detail: { userId: 123 } }))
 * 2. Directly via props `open` and `onClose`
 */
export default function EmployeeSkillMatrixModal({
    open: propOpen = false,
    onClose: propOnClose = null,
    initialUserId = null,
}) {
    const [isOpen, setIsOpen] = useState(propOpen);
    const [users, setUsers] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [selectedUserId, setSelectedUserId] = useState(initialUserId || '');
    const [userSearchTerm, setUserSearchTerm] = useState('');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [isUserSelectModalOpen, setIsUserSelectModalOpen] = useState(false);

    // Matrix state
    const [matrixData, setMatrixData] = useState(null);
    const [loadingUsers, setLoadingUsers] = useState(false);
    const [loadingMatrix, setLoadingMatrix] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const [selectedCategoryFilter, setSelectedCategoryFilter] = useState('ALL');

    const dropdownRef = useRef(null);

    // Sync propOpen
    useEffect(() => {
        setIsOpen(propOpen);
        if (propOpen && initialUserId) {
            setSelectedUserId(initialUserId);
        }
    }, [propOpen, initialUserId]);

    // Global custom event listener
    useEffect(() => {
        const handleGlobalOpen = (e) => {
            const targetUserId = e?.detail?.userId || null;
            if (targetUserId) {
                setSelectedUserId(targetUserId);
            }
            setIsOpen(true);
        };

        window.addEventListener('open-employee-skill-matrix', handleGlobalOpen);
        return () => window.removeEventListener('open-employee-skill-matrix', handleGlobalOpen);
    }, []);

    // Handle ESC key to close (only if inner user select modal is closed)
    useEffect(() => {
        if (!isOpen || isUserSelectModalOpen) return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                handleCloseModal();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, isUserSelectModalOpen]);

    // Close dropdown on outside click
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setIsDropdownOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Load available employees list
    useEffect(() => {
        if (!isOpen) return;

        setLoadingUsers(true);
        axios.get('/training/skill-matrix/users')
            .then((res) => {
                const list = res.data?.users || [];
                setUsers(list);
                if (res.data?.departments) {
                    setDepartments(res.data.departments);
                }
                if (!selectedUserId && list.length > 0) {
                    const defaultId = res.data?.current_user_id || list[0].id;
                    setSelectedUserId(defaultId);
                }
            })
            .catch((err) => {
                console.error('Failed to load skill matrix users', err);
                setErrorMessage('Unable to load authorized employee list.');
            })
            .finally(() => {
                setLoadingUsers(false);
            });
    }, [isOpen]);

    // Load matrix data whenever selectedUserId changes
    useEffect(() => {
        if (!isOpen || !selectedUserId) return;

        setLoadingMatrix(true);
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
                setLoadingMatrix(false);
            });
    }, [isOpen, selectedUserId]);

    const handleCloseModal = () => {
        setIsOpen(false);
        setIsDropdownOpen(false);
        setIsUserSelectModalOpen(false);
        if (propOnClose) propOnClose();
    };

    // Current selected user object
    const selectedUser = useMemo(() => {
        return users.find((u) => String(u.id) === String(selectedUserId)) || matrixData?.employee || null;
    }, [users, selectedUserId, matrixData]);

    // Filtered users for search dropdown
    const filteredUsers = useMemo(() => {
        if (!userSearchTerm.trim()) return users;
        const q = userSearchTerm.toLowerCase();
        return users.filter((u) =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            (u.department?.name && u.department.name.toLowerCase().includes(q)) ||
            (u.office_position?.name && u.office_position.name.toLowerCase().includes(q))
        );
    }, [users, userSearchTerm]);

    // Filter skills by category
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

    if (!isOpen) return null;

    const summary = matrixData?.summary || null;
    const isStarPerson = summary?.is_star_person || false;
    const skillfulJobs = matrixData?.skillful_jobs || [];

    const employeeName = matrixData?.employee?.name || selectedUser?.name || 'Selected Employee';
    const employeeDept = matrixData?.employee?.department || selectedUser?.department?.name || 'Unassigned Department';
    const employeePosition = matrixData?.employee?.office_position || selectedUser?.office_position?.name || 'General Staff';
    const employeeEmail = matrixData?.employee?.email || selectedUser?.email || '';
    const employeePhoto = matrixData?.employee?.profile_photo_url || selectedUser?.profile_photo_url || null;

    const modalContent = (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-0 md:p-3 overflow-hidden">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity animate-in fade-in duration-200"
                onClick={handleCloseModal}
            />

            {/* FULL-HEIGHT MODAL SHELL */}
            <div
                className="relative z-10 w-full h-full md:max-w-6xl md:h-[96vh] md:rounded-3xl border border-slate-800 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-slate-100 shadow-[0_25px_70px_-15px_rgba(0,0,0,0.9)] overflow-hidden flex flex-col animate-in zoom-in-95 duration-200"
                role="dialog"
                aria-modal="true"
            >
                {/* Top Subtle Star Person Glow Effect */}
                {isStarPerson && (
                    <div className="absolute -top-32 left-1/2 -translate-x-1/2 w-[32rem] h-60 bg-amber-500/20 rounded-full blur-3xl pointer-events-none" />
                )}

                {/* MODAL HEADER */}
                <div className="px-6 py-4 border-b border-slate-800/80 bg-slate-900/90 backdrop-blur-md flex items-center justify-between gap-4 shrink-0 relative z-10">
                    <div className="flex items-center gap-3">
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center shadow-inner ${
                            isStarPerson
                                ? 'bg-gradient-to-tr from-amber-500 to-yellow-300 text-slate-950 shadow-amber-500/20'
                                : 'bg-gradient-to-tr from-indigo-600 to-sky-500 text-white shadow-indigo-500/20'
                        }`}>
                            {isStarPerson ? (
                                <Star className="w-5 h-5 fill-current animate-pulse" />
                            ) : (
                                <BarChart3 className="w-5 h-5" />
                            )}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-[10px] font-extrabold tracking-wider uppercase text-indigo-400 bg-indigo-950/70 border border-indigo-800/60 px-2 py-0.5 rounded-md">
                                    Reinforcement Learning • Skill Matrix
                                </span>
                                {isStarPerson && (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-black tracking-wide uppercase text-amber-300 bg-amber-950/80 border border-amber-600/50 px-2.5 py-0.5 rounded-full shadow-sm animate-pulse">
                                        <Sparkles className="w-3 h-3 fill-amber-400 text-amber-400" />
                                        Star Person (≥90% Mastery)
                                    </span>
                                )}
                            </div>
                            <h2 className="text-base sm:text-lg font-black text-white tracking-tight">
                                Employee Skill Matrix & Reliability
                            </h2>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Switch employee button */}
                        <button
                            type="button"
                            onClick={() => setIsUserSelectModalOpen(true)}
                            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800/90 hover:bg-indigo-600 hover:text-white text-slate-300 border border-slate-700/80 transition-all shadow-sm"
                            title="Open Global User Selection dialog"
                        >
                            <Users className="w-3.5 h-3.5" />
                            <span>Switch Employee</span>
                        </button>

                        <button
                            type="button"
                            onClick={handleCloseModal}
                            className="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors border border-transparent hover:border-slate-700"
                            title="Close modal (Esc)"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {/* SCROLLABLE MAIN CONTENT AREA */}
                <div className="flex-1 overflow-y-auto custom-scrollbar flex flex-col">
                    {/* CENTERED HERO PROFILE SECTION */}
                    <div className="relative pt-7 pb-6 px-6 bg-gradient-to-b from-slate-950/80 via-slate-900/60 to-slate-900 border-b border-slate-800/80 flex flex-col items-center justify-center text-center shrink-0">
                        {/* Interactive Circular Profile Avatar */}
                        <div className="relative group cursor-pointer" onClick={() => setIsUserSelectModalOpen(true)}>
                            {/* Glowing rings */}
                            <div className={`absolute -inset-1.5 rounded-full blur-md opacity-75 group-hover:opacity-100 transition duration-300 ${
                                isStarPerson ? 'bg-gradient-to-r from-amber-500 to-yellow-300' : 'bg-gradient-to-r from-indigo-500 to-sky-400'
                            }`} />

                            <div className="relative w-24 h-24 sm:w-28 sm:h-28 rounded-full ring-4 ring-slate-900 bg-slate-800 shadow-2xl overflow-hidden flex items-center justify-center">
                                {employeePhoto ? (
                                    <img
                                        src={employeePhoto}
                                        alt={employeeName}
                                        className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    />
                                ) : (
                                    <div className="w-full h-full bg-gradient-to-br from-indigo-700 via-indigo-900 to-slate-900 flex items-center justify-center text-3xl sm:text-4xl font-black text-white uppercase select-none">
                                        {employeeName.charAt(0)}
                                    </div>
                                )}

                                {/* Hover Overlay pill hinting to switch user */}
                                <div className="absolute inset-0 bg-slate-950/70 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center transition-opacity duration-200 text-white p-1">
                                    <Users className="w-5 h-5 mb-0.5 text-indigo-300" />
                                    <span className="text-[10px] font-extrabold uppercase tracking-wider text-slate-200">
                                        Change
                                    </span>
                                </div>
                            </div>

                            {/* Badge icon at bottom-right corner */}
                            <div className={`absolute bottom-0 right-0 w-8 h-8 rounded-full border-2 border-slate-900 flex items-center justify-center shadow-md ${
                                isStarPerson
                                    ? 'bg-amber-400 text-slate-950'
                                    : 'bg-indigo-600 text-white'
                            }`}>
                                {isStarPerson ? <Star className="w-4 h-4 fill-current" /> : <ShieldCheck className="w-4 h-4" />}
                            </div>
                        </div>

                        {/* Name directly below Avatar */}
                        <div className="mt-3.5 space-y-1">
                            <h3 className="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center justify-center gap-2">
                                <span>{employeeName}</span>
                                {isStarPerson && (
                                    <span title="Star Performer (≥90% across skills)">
                                        🌟
                                    </span>
                                )}
                            </h3>
                            {employeeEmail && (
                                <p className="text-xs text-slate-400 font-mono">
                                    {employeeEmail}
                                </p>
                            )}
                        </div>

                        {/* Office Position & Department Side by Side */}
                        <div className="mt-3.5 flex flex-wrap items-center justify-center gap-2.5 sm:gap-3 text-xs">
                            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-800/90 border border-slate-700/80 shadow-sm text-slate-200">
                                <Briefcase className="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                                <span className="text-slate-400 text-[11px] font-medium uppercase tracking-wider">Position:</span>
                                <span className="font-bold text-white">{employeePosition}</span>
                            </div>

                            <span className="text-slate-600 hidden sm:inline">•</span>

                            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-800/90 border border-slate-700/80 shadow-sm text-slate-200">
                                <Building2 className="w-3.5 h-3.5 text-sky-400 shrink-0" />
                                <span className="text-slate-400 text-[11px] font-medium uppercase tracking-wider">Department:</span>
                                <span className="font-bold text-white">{employeeDept}</span>
                            </div>
                        </div>

                        {/* Quick switch helper button for mobile */}
                        <div className="mt-3 sm:hidden">
                            <button
                                type="button"
                                onClick={() => setIsUserSelectModalOpen(true)}
                                className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700"
                            >
                                <Users className="w-3 h-3 text-indigo-400" />
                                <span>Switch Employee</span>
                            </button>
                        </div>
                    </div>

                    {/* MAIN CONTENT BODY */}
                    <div className="p-5 sm:p-7 space-y-6 flex-1">
                        {/* Error Banner */}
                        {errorMessage && (
                            <div className="p-4 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-200 text-sm flex items-center gap-3">
                                <AlertCircle className="w-5 h-5 text-rose-400 shrink-0" />
                                <span>{errorMessage}</span>
                            </div>
                        )}

                        {/* Loading Skeleton */}
                        {loadingMatrix && (
                            <div className="py-16 flex flex-col items-center justify-center text-slate-400 space-y-3">
                                <RefreshCw className="w-9 h-9 animate-spin text-indigo-400" />
                                <p className="text-sm font-medium">Computing competency scores and reliance matrix...</p>
                            </div>
                        )}

                        {!loadingMatrix && matrixData && (
                            <>
                                {/* OVERALL SYNTHESIS: HOW WE SHOULD RELY ON THIS PERSON & STAR PERSON CALLOUT */}
                                <div className={`relative p-5 sm:p-6 rounded-3xl border transition-all ${
                                    isStarPerson
                                        ? 'bg-gradient-to-r from-amber-950/40 via-yellow-950/20 to-slate-900/80 border-amber-500/40 shadow-lg shadow-amber-500/5'
                                        : 'bg-gradient-to-r from-slate-900 via-slate-900/90 to-indigo-950/30 border-slate-800'
                                }`}>
                                    <div className="flex flex-col md:flex-row md:items-start justify-between gap-5">
                                        <div className="space-y-2 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-[11px] font-extrabold uppercase tracking-widest text-slate-400">
                                                    Strategic Workforce Assessment
                                                </span>
                                                {isStarPerson ? (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase bg-amber-400/20 text-amber-300 border border-amber-400/40">
                                                        🌟 Star Person (All Skills ≥90%)
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">
                                                        Standard Operational Matrix
                                                    </span>
                                                )}
                                            </div>

                                            <h3 className="text-lg sm:text-xl font-black text-white flex items-center gap-2">
                                                <span>How should we rely on {employeeName}?</span>
                                            </h3>

                                            <div className="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-1.5">
                                                <div className="flex items-center gap-2 text-sm font-bold text-slate-100">
                                                    <ShieldCheck className={`w-4 h-4 ${isStarPerson ? 'text-amber-400' : 'text-emerald-400'}`} />
                                                    <span>{summary?.overall_reliance}</span>
                                                </div>
                                                <p className="text-xs text-slate-300 leading-relaxed">
                                                    {summary?.overall_description}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Quick Metrics Cards */}
                                        <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-2 gap-2.5 shrink-0 min-w-[260px]">
                                            <div className="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Avg Score</span>
                                                <span className={`text-xl font-black ${
                                                    summary?.average_score >= 90 ? 'text-amber-400' : summary?.average_score >= 80 ? 'text-emerald-400' : 'text-sky-400'
                                                }`}>
                                                    {summary?.average_score}%
                                                </span>
                                            </div>

                                            <div className="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Star Skills (≥90%)</span>
                                                <span className="text-xl font-black text-amber-400 flex items-center gap-1">
                                                    <Star className="w-4 h-4 fill-amber-400" />
                                                    {summary?.star_skills} <span className="text-xs font-normal text-slate-500">/ {summary?.total_skills}</span>
                                                </span>
                                            </div>

                                            <div className="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Skills Passed</span>
                                                <span className="text-xl font-black text-emerald-400 flex items-center gap-1">
                                                    <CheckCircle2 className="w-4 h-4" />
                                                    {summary?.passed_skills} <span className="text-xs font-normal text-slate-500">/ {summary?.total_skills}</span>
                                                </span>
                                            </div>

                                            <div className="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Courses</span>
                                                <span className="text-xl font-black text-white">
                                                    {summary?.total_skills}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* SKILLFUL JOBS SUMMARY (IN WHICH JOBS IS HE SKILLFUL) */}
                                    {skillfulJobs.length > 0 && (
                                        <div className="mt-4 pt-4 border-t border-slate-800/80">
                                            <div className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5 flex items-center gap-1.5">
                                                <Award className="w-3.5 h-3.5 text-amber-400" />
                                                <span>Demonstrated High Competencies (Skillful in these Domains/Jobs):</span>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {skillfulJobs.map((job, idx) => (
                                                    <div
                                                        key={idx}
                                                        className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold border ${
                                                            job.is_star
                                                                ? 'bg-amber-950/50 text-amber-200 border-amber-600/40 shadow-sm'
                                                                : 'bg-emerald-950/40 text-emerald-200 border-emerald-600/30'
                                                        }`}
                                                    >
                                                        {job.is_star && <Star className="w-3 h-3 fill-amber-400 text-amber-400" />}
                                                        <span>{job.title}</span>
                                                        <span className="text-[10px] px-1.5 py-0.2 rounded-md bg-slate-900/80 text-white font-mono">
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
                                            <h4 className="text-base font-extrabold text-white flex items-center gap-2">
                                                <BookOpen className="w-4 h-4 text-indigo-400" />
                                                <span>Training Modules & Last Past Scores</span>
                                            </h4>
                                            <p className="text-xs text-slate-400 mt-0.5">
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
                                                            : 'bg-slate-800 text-slate-400 hover:text-white'
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
                                                                : 'bg-slate-800 text-slate-400 hover:text-white'
                                                        }`}
                                                    >
                                                        {cat}
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    {/* Custom Responsive Matrix Table */}
                                    <div className="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-inner">
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left text-xs border-collapse">
                                                <thead>
                                                    <tr className="border-b border-slate-800 bg-slate-950/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                                                        <th className="py-3.5 px-4">Training Skill Title</th>
                                                        <th className="py-3.5 px-3">Category</th>
                                                        <th className="py-3.5 px-3 text-center">Req. Passing</th>
                                                        <th className="py-3.5 px-3 text-center">Last Past Score</th>
                                                        <th className="py-3.5 px-3">Proficiency / Star Level</th>
                                                        <th className="py-3.5 px-4">Operational Reliance Guidance</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-800/60">
                                                    {filteredSkills.length === 0 ? (
                                                        <tr>
                                                            <td colSpan={6} className="py-8 text-center text-slate-500">
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
                                                                    className={`hover:bg-slate-800/40 transition-colors ${
                                                                        isStar ? 'bg-amber-950/10' : ''
                                                                    }`}
                                                                >
                                                                    {/* Training Title & Code */}
                                                                    <td className="py-3.5 px-4 font-semibold text-white">
                                                                        <div className="flex items-start gap-2">
                                                                            {isStar ? (
                                                                                <Star className="w-4 h-4 text-amber-400 fill-amber-400 shrink-0 mt-0.5" />
                                                                            ) : skill.is_passed ? (
                                                                                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                                                                            ) : hasAttempt ? (
                                                                            <XCircle className="w-4 h-4 text-rose-400 shrink-0 mt-0.5" />
                                                                            ) : (
                                                                                <Clock className="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
                                                                            )}
                                                                            <div>
                                                                                <div className="text-slate-100 font-bold leading-snug">
                                                                                    {skill.training_title}
                                                                                </div>
                                                                                <span className="text-[10px] text-slate-400 font-mono">
                                                                                    {skill.training_code || 'TR-MOD'}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </td>

                                                                    {/* Category */}
                                                                    <td className="py-3.5 px-3">
                                                                        <span className="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700/60">
                                                                            {skill.category_name}
                                                                        </span>
                                                                    </td>

                                                                    {/* Required Passing Score */}
                                                                    <td className="py-3.5 px-3 text-center font-mono text-slate-300 font-bold">
                                                                        {skill.passing_score}%
                                                                    </td>

                                                                    {/* Last Score (Score Card & Progress Bar) */}
                                                                    <td className="py-3.5 px-3 text-center">
                                                                        {hasAttempt ? (
                                                                            <div className="inline-flex flex-col items-center">
                                                                                <div className="flex items-center gap-1.5">
                                                                                    <span className={`text-sm font-black font-mono ${
                                                                                        isStar
                                                                                            ? 'text-amber-300'
                                                                                            : skill.is_passed
                                                                                            ? 'text-emerald-400'
                                                                                            : 'text-rose-400'
                                                                                    }`}>
                                                                                        {skill.last_score}%
                                                                                    </span>
                                                                                    {isStar && (
                                                                                        <span className="text-[10px] text-amber-400 font-extrabold uppercase">
                                                                                            ★
                                                                                        </span>
                                                                                    )}
                                                                                </div>
                                                                                {/* Mini visual bar */}
                                                                                <div className="w-16 h-1.5 bg-slate-800 rounded-full overflow-hidden mt-1">
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
                                                                            <span className="text-[11px] text-slate-500 italic">
                                                                                Pending
                                                                            </span>
                                                                        )}
                                                                    </td>

                                                                    {/* Proficiency / Star Level Tag */}
                                                                    <td className="py-3.5 px-3">
                                                                        <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-extrabold tracking-wide ${
                                                                            isStar
                                                                                ? 'bg-amber-400/20 text-amber-300 border border-amber-500/40 shadow-sm'
                                                                                : skill.is_passed
                                                                                ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-600/30'
                                                                                : hasAttempt
                                                                                ? 'bg-rose-500/15 text-rose-300 border border-rose-600/30'
                                                                                : 'bg-slate-800 text-slate-400 border border-slate-700/60'
                                                                        }`}>
                                                                            {isStar && <Star className="w-3 h-3 fill-amber-400" />}
                                                                            <span>{skill.status_label}</span>
                                                                        </span>
                                                                    </td>

                                                                    {/* How We Should Rely On This Person (Per-Skill) */}
                                                                    <td className="py-3.5 px-4">
                                                                        <div className="max-w-xs space-y-0.5">
                                                                            <div className="font-bold text-slate-200 text-xs">
                                                                                {skill.rely_rating}
                                                                            </div>
                                                                            <p className="text-[11px] text-slate-400 leading-snug line-clamp-2">
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

                {/* MODAL FOOTER */}
                <div className="px-6 py-4 bg-slate-950 border-t border-slate-800/90 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400 shrink-0">
                    <div className="flex items-center gap-2">
                        <Info className="w-4 h-4 text-indigo-400" />
                        <span>
                            Click on the <strong className="text-white">Profile Avatar</strong> to quickly select or switch another scoped employee.
                        </span>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setIsUserSelectModalOpen(true)}
                            className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition shadow-sm flex items-center gap-1.5"
                        >
                            <Users className="w-3.5 h-3.5" />
                            <span>Select Employee</span>
                        </button>

                        <button
                            type="button"
                            onClick={handleCloseModal}
                            className="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold transition border border-slate-700 shadow-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>

            {/* GLOBAL USER SELECT MODAL SUB-COMPONENT */}
            <UserSelectModal
                open={isUserSelectModalOpen}
                onClose={() => setIsUserSelectModalOpen(false)}
                onSelect={(selected) => {
                    if (selected && selected.id) {
                        setSelectedUserId(selected.id);
                    }
                    setIsUserSelectModalOpen(false);
                }}
                title="Select Employee for Skill Matrix"
                subtitle="Search and select an employee based on your permission scope"
                selectedUserId={selectedUserId}
                selectedUserName={employeeName}
                users={users}
                departments={departments}
            />
        </div>
    );

    return createPortal(modalContent, document.body);
}
