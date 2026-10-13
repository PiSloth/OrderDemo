import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import EmployeeSkillMatrixInline from '../../../components/Training/EmployeeSkillMatrixInline';
import {
    Award,
    Building2,
    Store,
    MapPin,
    Users,
    Star,
    Sparkles,
    CheckCircle2,
    XCircle,
    Clock,
    Search,
    Filter,
    ExternalLink,
    ChevronRight,
    BarChart3,
    TrendingUp,
    ShieldCheck,
    Briefcase,
    BookOpen,
    Eye,
    LayoutGrid,
    ListFilter,
    RefreshCw,
    UserCheck,
    Layers
} from 'lucide-react';

export default function SkillMatrixIndex({
    departments = [],
    branches = [],
    locations = [],
    employees = [],
    summary = {},
    current_user_id = null,
    can_view_all = true,
    initialFilters = {}
}) {
    // Current Active Tab: 'department' | 'branch' | 'employee'
    const [activeTab, setActiveTab] = useState(initialFilters.tab || 'department');

    // Tab 1 (Department) Filters
    const [selectedDepartmentId, setSelectedDepartmentId] = useState(initialFilters.department_id || 'ALL');
    const [deptSearchQuery, setDeptSearchQuery] = useState('');
    const [deptTierFilter, setDeptTierFilter] = useState('ALL'); // ALL, STAR, PASSED, PENDING

    // Tab 2 (Branch) Filters
    const [selectedBranchId, setSelectedBranchId] = useState(initialFilters.branch_id || 'ALL');
    const [selectedLocationId, setSelectedLocationId] = useState(initialFilters.location_id || 'ALL');
    const [branchSearchQuery, setBranchSearchQuery] = useState('');
    const [branchTierFilter, setBranchTierFilter] = useState('ALL');

    // Tab 3 (Inline Employee View) Selection
    const [selectedInlineUserId, setSelectedInlineUserId] = useState(current_user_id || (employees[0]?.id || null));

    // View mode (table or grid)
    const [viewMode, setViewMode] = useState('table');

    // Trigger global modal for an employee
    const handleOpenEmployeeModal = (userId) => {
        if (!userId) return;
        window.dispatchEvent(new CustomEvent('open-employee-skill-matrix', { detail: { userId } }));
    };

    // Switch to Tab 3 with specific employee selected
    const handleViewEmployeeInline = (userId) => {
        setSelectedInlineUserId(userId);
        setActiveTab('employee');
    };

    // Filtered employees for Department tab
    const departmentFilteredEmployees = useMemo(() => {
        return employees.filter((emp) => {
            // Department filter
            if (selectedDepartmentId !== 'ALL' && String(emp.department_id) !== String(selectedDepartmentId)) {
                return false;
            }

            // Search query filter
            if (deptSearchQuery.trim()) {
                const q = deptSearchQuery.toLowerCase();
                const matchName = emp.name.toLowerCase().includes(q);
                const matchEmail = emp.email && emp.email.toLowerCase().includes(q);
                const matchPos = emp.office_position?.name && emp.office_position.name.toLowerCase().includes(q);
                const matchDept = emp.department?.name && emp.department.name.toLowerCase().includes(q);
                if (!matchName && !matchEmail && !matchPos && !matchDept) {
                    return false;
                }
            }

            // Tier filter
            if (deptTierFilter === 'STAR' && !emp.is_star_person) return false;
            if (deptTierFilter === 'PASSED' && (!emp.has_attempts || emp.matrix_score < 80)) return false;
            if (deptTierFilter === 'PENDING' && emp.has_attempts) return false;

            return true;
        });
    }, [employees, selectedDepartmentId, deptSearchQuery, deptTierFilter]);

    // Department Stats for selected filter
    const departmentStats = useMemo(() => {
        const list = departmentFilteredEmployees;
        const total = list.length;
        const evaluated = list.filter((e) => e.has_attempts).length;
        const avgScore = evaluated > 0
            ? Math.round((list.filter((e) => e.has_attempts).reduce((acc, curr) => acc + (curr.matrix_score || 0), 0) / evaluated) * 10) / 10
            : 0;
        const starCount = list.filter((e) => e.is_star_person).length;
        const passedSkills = list.reduce((acc, curr) => acc + (curr.passed_skills || 0), 0);
        const assignedSkills = list.reduce((acc, curr) => acc + (curr.total_skills || 0), 0);
        const passRate = assignedSkills > 0 ? Math.round((passedSkills / assignedSkills) * 100) : 0;

        return { total, evaluated, avgScore, starCount, passRate, passedSkills, assignedSkills };
    }, [departmentFilteredEmployees]);

    // Filtered employees for Branch tab
    const branchFilteredEmployees = useMemo(() => {
        return employees.filter((emp) => {
            // Branch filter
            if (selectedBranchId !== 'ALL' && String(emp.branch_id) !== String(selectedBranchId)) {
                return false;
            }

            // Location filter
            if (selectedLocationId !== 'ALL' && String(emp.location_id) !== String(selectedLocationId)) {
                return false;
            }

            // Search query filter
            if (branchSearchQuery.trim()) {
                const q = branchSearchQuery.toLowerCase();
                const matchName = emp.name.toLowerCase().includes(q);
                const matchEmail = emp.email && emp.email.toLowerCase().includes(q);
                const matchBranch = emp.branch?.name && emp.branch.name.toLowerCase().includes(q);
                const matchLocation = emp.location?.name && emp.location.name.toLowerCase().includes(q);
                const matchPos = emp.office_position?.name && emp.office_position.name.toLowerCase().includes(q);
                if (!matchName && !matchEmail && !matchBranch && !matchLocation && !matchPos) {
                    return false;
                }
            }

            // Tier filter
            if (branchTierFilter === 'STAR' && !emp.is_star_person) return false;
            if (branchTierFilter === 'PASSED' && (!emp.has_attempts || emp.matrix_score < 80)) return false;
            if (branchTierFilter === 'PENDING' && emp.has_attempts) return false;

            return true;
        });
    }, [employees, selectedBranchId, selectedLocationId, branchSearchQuery, branchTierFilter]);

    // Branch Stats for selected filters
    const branchStats = useMemo(() => {
        const list = branchFilteredEmployees;
        const total = list.length;
        const evaluated = list.filter((e) => e.has_attempts).length;
        const avgScore = evaluated > 0
            ? Math.round((list.filter((e) => e.has_attempts).reduce((acc, curr) => acc + (curr.matrix_score || 0), 0) / evaluated) * 10) / 10
            : 0;
        const starCount = list.filter((e) => e.is_star_person).length;
        const passedSkills = list.reduce((acc, curr) => acc + (curr.passed_skills || 0), 0);
        const assignedSkills = list.reduce((acc, curr) => acc + (curr.total_skills || 0), 0);
        const passRate = assignedSkills > 0 ? Math.round((passedSkills / assignedSkills) * 100) : 0;

        return { total, evaluated, avgScore, starCount, passRate, passedSkills, assignedSkills };
    }, [branchFilteredEmployees]);

    // Helper for score badge colors
    const getScoreBadge = (score, hasAttempts = true) => {
        if (!hasAttempts || score === null || score === undefined || score === 0) {
            return {
                bg: 'bg-slate-100 dark:bg-slate-800',
                text: 'text-slate-500 dark:text-slate-400',
                border: 'border-slate-200 dark:border-slate-700',
                bar: 'bg-slate-300 dark:bg-slate-700',
                label: 'Pending'
            };
        }
        if (score >= 90) {
            return {
                bg: 'bg-amber-50 dark:bg-amber-950/40',
                text: 'text-amber-600 dark:text-amber-300 font-black',
                border: 'border-amber-300 dark:border-amber-600/50',
                bar: 'bg-amber-500',
                label: `${score}% ★`
            };
        }
        if (score >= 80) {
            return {
                bg: 'bg-emerald-50 dark:bg-emerald-950/40',
                text: 'text-emerald-600 dark:text-emerald-400 font-bold',
                border: 'border-emerald-300 dark:border-emerald-600/40',
                bar: 'bg-emerald-500',
                label: `${score}%`
            };
        }
        if (score >= 70) {
            return {
                bg: 'bg-sky-50 dark:bg-sky-950/40',
                text: 'text-sky-600 dark:text-sky-400 font-bold',
                border: 'border-sky-300 dark:border-sky-600/40',
                bar: 'bg-sky-500',
                label: `${score}%`
            };
        }
        if (score >= 60) {
            return {
                bg: 'bg-orange-50 dark:bg-orange-950/40',
                text: 'text-orange-600 dark:text-orange-400 font-bold',
                border: 'border-orange-300 dark:border-orange-600/40',
                bar: 'bg-orange-500',
                label: `${score}%`
            };
        }
        return {
            bg: 'bg-rose-50 dark:bg-rose-950/40',
            text: 'text-rose-600 dark:text-rose-400 font-bold',
            border: 'border-rose-300 dark:border-rose-600/40',
            bar: 'bg-rose-500',
            label: `${score}%`
        };
    };

    return (
        <AsideLayout>
            <Head title="Skill Matrix & Competency Management" />

            <div className="max-w-7xl mx-auto space-y-6 pb-12">
                {/* PAGE HEADER */}
                <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800 relative overflow-hidden">
                    {/* Background Ambient Glow */}
                    <div className="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none" />
                    <div className="absolute bottom-0 left-1/3 -mb-20 w-60 h-60 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />

                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                <Sparkles className="w-3.5 h-3.5 text-amber-400" />
                                <span>Reinforcement Learning • Strategic Analytics</span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl md:text-4xl font-black tracking-tight text-white flex items-center gap-3">
                                <span>Skill Matrix Management</span>
                            </h1>
                            <p className="text-sm text-slate-300 max-w-2xl leading-relaxed">
                                Monitor company-wide employee competency, evaluate departmental and branch qualification scores, and review operational reliance synthesis.
                            </p>
                        </div>

                        {/* Top Global KPI Summary Pills */}
                        <div className="flex flex-wrap items-center gap-3 shrink-0">
                            <div className="px-4 py-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 flex flex-col items-center min-w-[100px]">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-300">Total Staff</span>
                                <span className="text-2xl font-black text-white">{summary.total_employees || employees.length}</span>
                            </div>

                            <div className="px-4 py-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 flex flex-col items-center min-w-[100px]">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-300">Avg Matrix</span>
                                <span className="text-2xl font-black text-indigo-300">
                                    {summary.average_score ? `${summary.average_score}%` : '0%'}
                                </span>
                            </div>

                            <div className="px-4 py-3 rounded-2xl bg-amber-500/20 backdrop-blur-md border border-amber-400/30 flex flex-col items-center min-w-[110px]">
                                <span className="text-[10px] font-black uppercase tracking-wider text-amber-300 flex items-center gap-1">
                                    <Star className="w-3 h-3 fill-amber-300" />
                                    Star Persons
                                </span>
                                <span className="text-2xl font-black text-amber-300">
                                    {summary.star_performers_count || 0}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* THREE-TAB NAVIGATION BAR */}
                <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-2 shadow-sm">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        {/* Tab 1: Department Skill Matrix */}
                        <button
                            type="button"
                            onClick={() => setActiveTab('department')}
                            className={`flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                                activeTab === 'department'
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            }`}
                        >
                            <Building2 className="w-4 h-4" />
                            <span>Department Skill Matrix</span>
                            <span className={`text-[11px] px-2 py-0.5 rounded-full font-mono ${
                                activeTab === 'department'
                                    ? 'bg-indigo-700 text-white'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                            }`}>
                                {departments.length}
                            </span>
                        </button>

                        {/* Tab 2: Branch Matrix */}
                        <button
                            type="button"
                            onClick={() => setActiveTab('branch')}
                            className={`flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                                activeTab === 'branch'
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            }`}
                        >
                            <Store className="w-4 h-4" />
                            <span>Branch Matrix</span>
                            <span className={`text-[11px] px-2 py-0.5 rounded-full font-mono ${
                                activeTab === 'branch'
                                    ? 'bg-indigo-700 text-white'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                            }`}>
                                {branches.length}
                            </span>
                        </button>

                        {/* Tab 3: Employee Matrix (Inline View) */}
                        <button
                            type="button"
                            onClick={() => setActiveTab('employee')}
                            className={`flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl text-sm font-bold transition-all ${
                                activeTab === 'employee'
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            }`}
                        >
                            <Users className="w-4 h-4" />
                            <span>Employee Matrix (Inline View)</span>
                            <span className={`text-[11px] px-2 py-0.5 rounded-full font-mono ${
                                activeTab === 'employee'
                                    ? 'bg-indigo-700 text-white'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                            }`}>
                                {employees.length}
                            </span>
                        </button>
                    </div>
                </div>

                {/* ============================================================== */}
                {/* TAB 1: DEPARTMENT SKILL MATRIX                                  */}
                {/* ============================================================== */}
                {activeTab === 'department' && (
                    <div className="space-y-6">
                        {/* Filters & Controls Bar */}
                        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 className="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                                        <Building2 className="w-5 h-5 text-indigo-500" />
                                        <span>Department Skill Evaluation</span>
                                    </h3>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        Select a department to analyze team performance and view employee overhaul matrix scores.
                                    </p>
                                </div>

                                <div className="flex items-center gap-2">
                                    {/* View Mode Toggle */}
                                    <div className="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                                        <button
                                            type="button"
                                            onClick={() => setViewMode('table')}
                                            className={`p-1.5 rounded-lg text-xs font-bold transition ${
                                                viewMode === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500'
                                            }`}
                                            title="Table View"
                                        >
                                            <ListFilter className="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setViewMode('grid')}
                                            className={`p-1.5 rounded-lg text-xs font-bold transition ${
                                                viewMode === 'grid' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500'
                                            }`}
                                            title="Cards Grid View"
                                        >
                                            <LayoutGrid className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Filters Row */}
                            <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                                {/* Department Filter */}
                                <div className="sm:col-span-5">
                                    <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Department Filter
                                    </label>
                                    <select
                                        value={selectedDepartmentId}
                                        onChange={(e) => setSelectedDepartmentId(e.target.value)}
                                        className="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="ALL">All Departments ({employees.length} employees)</option>
                                        {departments.map((dept) => {
                                            const count = employees.filter((e) => String(e.department_id) === String(dept.id)).length;
                                            return (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name} ({count} employees)
                                                </option>
                                            );
                                        })}
                                    </select>
                                </div>

                                {/* Tier Filter Pills */}
                                <div className="sm:col-span-4">
                                    <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Performance Tier
                                    </label>
                                    <select
                                        value={deptTierFilter}
                                        onChange={(e) => setDeptTierFilter(e.target.value)}
                                        className="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="ALL">All Competency Tiers</option>
                                        <option value="STAR">★ Star Persons Only (≥90%)</option>
                                        <option value="PASSED">Passed / Proficient (≥80%)</option>
                                        <option value="PENDING">Pending Assessment</option>
                                    </select>
                                </div>

                                {/* Search Input */}
                                <div className="sm:col-span-3">
                                    <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Search Employee
                                    </label>
                                    <div className="relative">
                                        <input
                                            type="text"
                                            placeholder="Name, role, email..."
                                            value={deptSearchQuery}
                                            onChange={(e) => setDeptSearchQuery(e.target.value)}
                                            className="w-full text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                                        />
                                        <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Department KPI Stats Cards */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <TrendingUp className="w-3.5 h-3.5 text-indigo-500" />
                                    Dept Avg Matrix
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                                    {departmentStats.avgScore}%
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    Across {departmentStats.evaluated} assessed staff
                                </p>
                            </div>

                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <Star className="w-3.5 h-3.5 text-amber-500" />
                                    Star Performers
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-amber-500">
                                    {departmentStats.starCount}
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    Mastery across all skills (≥90%)
                                </p>
                            </div>

                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                                    Modules Pass Rate
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">
                                    {departmentStats.passRate}%
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    {departmentStats.passedSkills} of {departmentStats.assignedSkills} passed
                                </p>
                            </div>

                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <Users className="w-3.5 h-3.5 text-sky-500" />
                                    Employees
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                                    {departmentStats.total}
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    Filtered department staff
                                </p>
                            </div>
                        </div>

                        {/* Department Employees Table / Grid */}
                        {departmentFilteredEmployees.length === 0 ? (
                            <div className="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400">
                                <Users className="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600" />
                                <h4 className="text-sm font-bold text-slate-700 dark:text-slate-300">No employees found</h4>
                                <p className="text-xs text-slate-400 mt-1">
                                    Try adjusting your department filter, performance tier, or search query.
                                </p>
                            </div>
                        ) : viewMode === 'table' ? (
                            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr className="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                <th className="py-3.5 px-4">Employee</th>
                                                <th className="py-3.5 px-3">Department & Position</th>
                                                <th className="py-3.5 px-3 text-center">Overhaul Matrix Score</th>
                                                <th className="py-3.5 px-3">Proficiency / Reliance</th>
                                                <th className="py-3.5 px-3 text-center">Passed / Total</th>
                                                <th className="py-3.5 px-4">Skillful Domains</th>
                                                <th className="py-3.5 px-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                            {departmentFilteredEmployees.map((emp) => {
                                                const scoreBadge = getScoreBadge(emp.matrix_score, emp.has_attempts);
                                                return (
                                                    <tr
                                                        key={emp.id}
                                                        onClick={() => handleOpenEmployeeModal(emp.id)}
                                                        className="hover:bg-indigo-50/40 dark:hover:bg-slate-800/40 transition-colors cursor-pointer group"
                                                    >
                                                        {/* Employee info */}
                                                        <td className="py-3.5 px-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="relative">
                                                                    <div className="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex items-center justify-center font-bold text-slate-700 dark:text-slate-200">
                                                                        {emp.profile_photo_url ? (
                                                                            <img src={emp.profile_photo_url} alt="" className="w-full h-full object-cover" />
                                                                        ) : (
                                                                            emp.name.charAt(0)
                                                                        )}
                                                                    </div>
                                                                    {emp.is_star_person && (
                                                                        <div className="absolute -bottom-1 -right-1 w-4 h-4 bg-amber-400 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-bold border border-white dark:border-slate-900">
                                                                            ★
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                <div>
                                                                    <div className="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                                                        <span>{emp.name}</span>
                                                                        {emp.is_star_person && <span>🌟</span>}
                                                                    </div>
                                                                    <div className="text-[11px] text-slate-400 font-mono">
                                                                        {emp.email}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Department & Position */}
                                                        <td className="py-3.5 px-3">
                                                            <div className="space-y-0.5">
                                                                <div className="font-semibold text-slate-800 dark:text-slate-200">
                                                                    {emp.department?.name || 'Unassigned Dept'}
                                                                </div>
                                                                <div className="text-[11px] text-slate-400 flex items-center gap-1">
                                                                    <Briefcase className="w-3 h-3 text-slate-400" />
                                                                    <span>{emp.office_position?.name || 'General Staff'}</span>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Overhaul Matrix Score */}
                                                        <td className="py-3.5 px-3 text-center">
                                                            <div className="inline-flex flex-col items-center">
                                                                <span className={`px-2.5 py-1 rounded-xl text-xs font-bold border ${scoreBadge.bg} ${scoreBadge.text} ${scoreBadge.border}`}>
                                                                    {scoreBadge.label}
                                                                </span>
                                                                {emp.has_attempts && (
                                                                    <div className="w-16 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden mt-1.5">
                                                                        <div
                                                                            className={`h-full rounded-full ${scoreBadge.bar}`}
                                                                            style={{ width: `${Math.min(100, emp.matrix_score)}%` }}
                                                                        />
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </td>

                                                        {/* Proficiency / Reliance Status */}
                                                        <td className="py-3.5 px-3">
                                                            <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold ${
                                                                emp.reliance_color === 'amber'
                                                                    ? 'bg-amber-100 dark:bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-500/40'
                                                                    : emp.reliance_color === 'emerald'
                                                                    ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-600/30'
                                                                    : emp.reliance_color === 'blue'
                                                                    ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-600/30'
                                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                                                            }`}>
                                                                {emp.is_star_person ? <Star className="w-3 h-3 fill-amber-400" /> : <ShieldCheck className="w-3 h-3" />}
                                                                <span>{emp.reliance_label}</span>
                                                            </span>
                                                        </td>

                                                        {/* Passed / Total */}
                                                        <td className="py-3.5 px-3 text-center">
                                                            <div className="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                                                {emp.passed_skills} <span className="text-slate-400">/ {emp.total_skills}</span>
                                                            </div>
                                                            {emp.star_skills > 0 && (
                                                                <span className="text-[10px] text-amber-500 font-semibold">
                                                                    ★ {emp.star_skills} star
                                                                </span>
                                                            )}
                                                        </td>

                                                        {/* Skillful Domains */}
                                                        <td className="py-3.5 px-4">
                                                            {emp.skillful_jobs && emp.skillful_jobs.length > 0 ? (
                                                                <div className="flex flex-wrap gap-1 max-w-xs">
                                                                    {emp.skillful_jobs.slice(0, 2).map((job, jIdx) => (
                                                                        <span
                                                                            key={jIdx}
                                                                            className="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                                                        >
                                                                            {job.title} ({job.score}%)
                                                                        </span>
                                                                    ))}
                                                                    {emp.skillful_jobs.length > 2 && (
                                                                        <span className="text-[10px] text-slate-400 font-bold self-center">
                                                                            +{emp.skillful_jobs.length - 2} more
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            ) : (
                                                                <span className="text-[11px] text-slate-400 italic">—</span>
                                                            )}
                                                        </td>

                                                        {/* Actions */}
                                                        <td className="py-3.5 px-4 text-right" onClick={(e) => e.stopPropagation()}>
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleViewEmployeeInline(emp.id)}
                                                                    className="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                                                    title="View in Tab 3 (Inline View)"
                                                                >
                                                                    Inline
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleOpenEmployeeModal(emp.id)}
                                                                    className="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center gap-1 shadow-sm"
                                                                    title="Open Employee Skill Matrix Modal"
                                                                >
                                                                    <Eye className="w-3.5 h-3.5" />
                                                                    <span>Modal</span>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ) : (
                            /* Cards Grid View */
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                {departmentFilteredEmployees.map((emp) => {
                                    const scoreBadge = getScoreBadge(emp.matrix_score, emp.has_attempts);
                                    return (
                                        <div
                                            key={emp.id}
                                            onClick={() => handleOpenEmployeeModal(emp.id)}
                                            className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-600 transition cursor-pointer flex flex-col justify-between space-y-4 group"
                                        >
                                            <div className="space-y-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex items-center justify-center font-bold text-slate-700 dark:text-slate-200 shrink-0">
                                                            {emp.profile_photo_url ? (
                                                                <img src={emp.profile_photo_url} alt="" className="w-full h-full object-cover" />
                                                            ) : (
                                                                emp.name.charAt(0)
                                                            )}
                                                        </div>
                                                        <div>
                                                            <h4 className="text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition flex items-center gap-1">
                                                                <span>{emp.name}</span>
                                                                {emp.is_star_person && <span>🌟</span>}
                                                            </h4>
                                                            <p className="text-[11px] text-slate-400 font-mono line-clamp-1">{emp.email}</p>
                                                        </div>
                                                    </div>

                                                    <span className={`px-2.5 py-1 rounded-xl text-xs font-black border ${scoreBadge.bg} ${scoreBadge.text} ${scoreBadge.border}`}>
                                                        {scoreBadge.label}
                                                    </span>
                                                </div>

                                                <div className="text-xs text-slate-600 dark:text-slate-300 space-y-1">
                                                    <div className="flex items-center gap-1.5 font-medium">
                                                        <Building2 className="w-3.5 h-3.5 text-indigo-500" />
                                                        <span>{emp.department?.name || 'Unassigned'}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1.5 text-slate-400 text-[11px]">
                                                        <Briefcase className="w-3.5 h-3.5 text-slate-400" />
                                                        <span>{emp.office_position?.name || 'General Staff'}</span>
                                                    </div>
                                                </div>

                                                <div className="pt-2 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs">
                                                    <span className="text-[11px] text-slate-400">Modules Completed:</span>
                                                    <span className="font-mono font-bold text-slate-900 dark:text-white">
                                                        {emp.passed_skills} / {emp.total_skills}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between gap-2">
                                                <span className={`text-[10px] font-bold px-2 py-0.5 rounded-lg ${
                                                    emp.is_star_person ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                                                }`}>
                                                    {emp.reliance_label}
                                                </span>

                                                <div className="flex items-center gap-1.5" onClick={(e) => e.stopPropagation()}>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleViewEmployeeInline(emp.id)}
                                                        className="px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200"
                                                    >
                                                        Inline
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenEmployeeModal(emp.id)}
                                                        className="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-1"
                                                    >
                                                        <Eye className="w-3 h-3" />
                                                        <span>Modal</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                )}

                {/* ============================================================== */}
                {/* TAB 2: BRANCH MATRIX                                           */}
                {/* ============================================================== */}
                {activeTab === 'branch' && (
                    <div className="space-y-6">
                        {/* Filters Bar: Branch Filter + Location Filter */}
                        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 className="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                                        <Store className="w-5 h-5 text-indigo-500" />
                                        <span>Branch & Location Competency Matrix</span>
                                    </h3>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        Filter employees by Branch and Location to assess operational compliance and overhaul score across work sites.
                                    </p>
                                </div>

                                {/* View Mode Toggle */}
                                <div className="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                                    <button
                                        type="button"
                                        onClick={() => setViewMode('table')}
                                        className={`p-1.5 rounded-lg text-xs font-bold transition ${
                                            viewMode === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500'
                                        }`}
                                        title="Table View"
                                    >
                                        <ListFilter className="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setViewMode('grid')}
                                        className={`p-1.5 rounded-lg text-xs font-bold transition ${
                                            viewMode === 'grid' ? 'bg-white dark:bg-slate-700 shadow-sm text-indigo-600 dark:text-white' : 'text-slate-500'
                                        }`}
                                        title="Cards Grid View"
                                    >
                                        <LayoutGrid className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {/* Dual Filters: Branch Filter & Location Filter */}
                            <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                                {/* 1. Branch Filter */}
                                <div className="sm:col-span-4">
                                    <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Branch Filter
                                    </label>
                                    <select
                                        value={selectedBranchId}
                                        onChange={(e) => setSelectedBranchId(e.target.value)}
                                        className="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="ALL">All Branches ({branches.length} branches)</option>
                                        {branches.map((b) => {
                                            const count = employees.filter((e) => String(e.branch_id) === String(b.id)).length;
                                            return (
                                                <option key={b.id} value={b.id}>
                                                    {b.name} ({count} staff)
                                                </option>
                                            );
                                        })}
                                    </select>
                                </div>

                                {/* 2. Location Filter */}
                                <div className="sm:col-span-4">
                                    <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Location Filter
                                    </label>
                                    <select
                                        value={selectedLocationId}
                                        onChange={(e) => setSelectedLocationId(e.target.value)}
                                        className="w-full text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="ALL">All Locations ({locations.length} locations)</option>
                                        {locations.map((loc) => {
                                            const count = employees.filter((e) => String(e.location_id) === String(loc.id)).length;
                                            return (
                                                <option key={loc.id} value={loc.id}>
                                                    {loc.name} ({count} staff)
                                                </option>
                                            );
                                        })}
                                    </select>
                                </div>

                                {/* Search Filter */}
                                <div className="sm:col-span-4">
                                    <label className="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Search Employee
                                    </label>
                                    <div className="relative">
                                        <input
                                            type="text"
                                            placeholder="Employee, branch, site..."
                                            value={branchSearchQuery}
                                            onChange={(e) => setBranchSearchQuery(e.target.value)}
                                            className="w-full text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                                        />
                                        <Search className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Branch KPI Stats Cards */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <TrendingUp className="w-3.5 h-3.5 text-indigo-500" />
                                    Branch Avg Score
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                                    {branchStats.avgScore}%
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    Across {branchStats.evaluated} evaluated staff
                                </p>
                            </div>

                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <Star className="w-3.5 h-3.5 text-amber-500" />
                                    Branch Star Persons
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-amber-500">
                                    {branchStats.starCount}
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    Elite mastery across site
                                </p>
                            </div>

                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                                    Site Pass Rate
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">
                                    {branchStats.passRate}%
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    {branchStats.passedSkills} of {branchStats.assignedSkills} passed
                                </p>
                            </div>

                            <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <Users className="w-3.5 h-3.5 text-sky-500" />
                                    Branch Employees
                                </span>
                                <div className="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                                    {branchStats.total}
                                </div>
                                <p className="text-[11px] text-slate-400">
                                    Filtered branch & location staff
                                </p>
                            </div>
                        </div>

                        {/* Branch Employees Table / Grid */}
                        {branchFilteredEmployees.length === 0 ? (
                            <div className="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400">
                                <Store className="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600" />
                                <h4 className="text-sm font-bold text-slate-700 dark:text-slate-300">No staff matching branch & location filters</h4>
                                <p className="text-xs text-slate-400 mt-1">
                                    Try selecting a different branch, location, or search keyword.
                                </p>
                            </div>
                        ) : viewMode === 'table' ? (
                            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr className="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                <th className="py-3.5 px-4">Employee</th>
                                                <th className="py-3.5 px-3">Branch & Location</th>
                                                <th className="py-3.5 px-3">Department & Position</th>
                                                <th className="py-3.5 px-3 text-center">Overhaul Matrix Score</th>
                                                <th className="py-3.5 px-3">Proficiency / Reliance</th>
                                                <th className="py-3.5 px-3 text-center">Passed / Total</th>
                                                <th className="py-3.5 px-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                            {branchFilteredEmployees.map((emp) => {
                                                const scoreBadge = getScoreBadge(emp.matrix_score, emp.has_attempts);
                                                return (
                                                    <tr
                                                        key={emp.id}
                                                        onClick={() => handleOpenEmployeeModal(emp.id)}
                                                        className="hover:bg-indigo-50/40 dark:hover:bg-slate-800/40 transition-colors cursor-pointer group"
                                                    >
                                                        {/* Employee */}
                                                        <td className="py-3.5 px-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className="relative">
                                                                    <div className="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex items-center justify-center font-bold text-slate-700 dark:text-slate-200">
                                                                        {emp.profile_photo_url ? (
                                                                            <img src={emp.profile_photo_url} alt="" className="w-full h-full object-cover" />
                                                                        ) : (
                                                                            emp.name.charAt(0)
                                                                        )}
                                                                    </div>
                                                                    {emp.is_star_person && (
                                                                        <div className="absolute -bottom-1 -right-1 w-4 h-4 bg-amber-400 rounded-full flex items-center justify-center text-[10px] text-slate-950 font-bold border border-white dark:border-slate-900">
                                                                            ★
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                <div>
                                                                    <div className="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                                                        <span>{emp.name}</span>
                                                                        {emp.is_star_person && <span>🌟</span>}
                                                                    </div>
                                                                    <div className="text-[11px] text-slate-400 font-mono">
                                                                        {emp.email}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Branch & Location */}
                                                        <td className="py-3.5 px-3">
                                                            <div className="space-y-1">
                                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                                    <Store className="w-3 h-3" />
                                                                    <span>{emp.branch?.name || 'No Branch'}</span>
                                                                </span>
                                                                {emp.location?.name && (
                                                                    <div className="text-[11px] text-slate-400 flex items-center gap-1">
                                                                        <MapPin className="w-3 h-3 text-slate-400" />
                                                                        <span>{emp.location.name}</span>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </td>

                                                        {/* Department & Position */}
                                                        <td className="py-3.5 px-3">
                                                            <div className="space-y-0.5">
                                                                <div className="font-semibold text-slate-800 dark:text-slate-200">
                                                                    {emp.department?.name || 'Unassigned Dept'}
                                                                </div>
                                                                <div className="text-[11px] text-slate-400">
                                                                    {emp.office_position?.name || 'General Staff'}
                                                                </div>
                                                            </div>
                                                        </td>

                                                        {/* Overhaul Matrix Score */}
                                                        <td className="py-3.5 px-3 text-center">
                                                            <div className="inline-flex flex-col items-center">
                                                                <span className={`px-2.5 py-1 rounded-xl text-xs font-bold border ${scoreBadge.bg} ${scoreBadge.text} ${scoreBadge.border}`}>
                                                                    {scoreBadge.label}
                                                                </span>
                                                                {emp.has_attempts && (
                                                                    <div className="w-16 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden mt-1.5">
                                                                        <div
                                                                            className={`h-full rounded-full ${scoreBadge.bar}`}
                                                                            style={{ width: `${Math.min(100, emp.matrix_score)}%` }}
                                                                        />
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </td>

                                                        {/* Proficiency / Reliance Status */}
                                                        <td className="py-3.5 px-3">
                                                            <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold ${
                                                                emp.reliance_color === 'amber'
                                                                    ? 'bg-amber-100 dark:bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-500/40'
                                                                    : emp.reliance_color === 'emerald'
                                                                    ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-600/30'
                                                                    : emp.reliance_color === 'blue'
                                                                    ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-600/30'
                                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                                                            }`}>
                                                                {emp.is_star_person ? <Star className="w-3 h-3 fill-amber-400" /> : <ShieldCheck className="w-3 h-3" />}
                                                                <span>{emp.reliance_label}</span>
                                                            </span>
                                                        </td>

                                                        {/* Passed / Total */}
                                                        <td className="py-3.5 px-3 text-center">
                                                            <div className="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                                                {emp.passed_skills} <span className="text-slate-400">/ {emp.total_skills}</span>
                                                            </div>
                                                            {emp.star_skills > 0 && (
                                                                <span className="text-[10px] text-amber-500 font-semibold">
                                                                    ★ {emp.star_skills} star
                                                                </span>
                                                            )}
                                                        </td>

                                                        {/* Actions */}
                                                        <td className="py-3.5 px-4 text-right" onClick={(e) => e.stopPropagation()}>
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleViewEmployeeInline(emp.id)}
                                                                    className="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                                                    title="View in Tab 3 (Inline View)"
                                                                >
                                                                    Inline
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleOpenEmployeeModal(emp.id)}
                                                                    className="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center gap-1 shadow-sm"
                                                                    title="Open Employee Skill Matrix Modal"
                                                                >
                                                                    <Eye className="w-3.5 h-3.5" />
                                                                    <span>Modal</span>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ) : (
                            /* Cards Grid View */
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                {branchFilteredEmployees.map((emp) => {
                                    const scoreBadge = getScoreBadge(emp.matrix_score, emp.has_attempts);
                                    return (
                                        <div
                                            key={emp.id}
                                            onClick={() => handleOpenEmployeeModal(emp.id)}
                                            className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-600 transition cursor-pointer flex flex-col justify-between space-y-4 group"
                                        >
                                            <div className="space-y-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden flex items-center justify-center font-bold text-slate-700 dark:text-slate-200 shrink-0">
                                                            {emp.profile_photo_url ? (
                                                                <img src={emp.profile_photo_url} alt="" className="w-full h-full object-cover" />
                                                            ) : (
                                                                emp.name.charAt(0)
                                                            )}
                                                        </div>
                                                        <div>
                                                            <h4 className="text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition flex items-center gap-1">
                                                                <span>{emp.name}</span>
                                                                {emp.is_star_person && <span>🌟</span>}
                                                            </h4>
                                                            <p className="text-[11px] text-slate-400 font-mono line-clamp-1">{emp.email}</p>
                                                        </div>
                                                    </div>

                                                    <span className={`px-2.5 py-1 rounded-xl text-xs font-black border ${scoreBadge.bg} ${scoreBadge.text} ${scoreBadge.border}`}>
                                                        {scoreBadge.label}
                                                    </span>
                                                </div>

                                                <div className="text-xs text-slate-600 dark:text-slate-300 space-y-1.5">
                                                    <div className="flex items-center gap-1.5 font-medium">
                                                        <Store className="w-3.5 h-3.5 text-indigo-500" />
                                                        <span>{emp.branch?.name || 'No Branch'}</span>
                                                        {emp.location?.name && (
                                                            <span className="text-slate-400">({emp.location.name})</span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-1.5 text-slate-400 text-[11px]">
                                                        <Building2 className="w-3.5 h-3.5 text-slate-400" />
                                                        <span>{emp.department?.name || 'Unassigned'} • {emp.office_position?.name || 'Staff'}</span>
                                                    </div>
                                                </div>

                                                <div className="pt-2 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs">
                                                    <span className="text-[11px] text-slate-400">Modules Completed:</span>
                                                    <span className="font-mono font-bold text-slate-900 dark:text-white">
                                                        {emp.passed_skills} / {emp.total_skills}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between gap-2">
                                                <span className={`text-[10px] font-bold px-2 py-0.5 rounded-lg ${
                                                    emp.is_star_person ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                                                }`}>
                                                    {emp.reliance_label}
                                                </span>

                                                <div className="flex items-center gap-1.5" onClick={(e) => e.stopPropagation()}>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleViewEmployeeInline(emp.id)}
                                                        className="px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200"
                                                    >
                                                        Inline
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenEmployeeModal(emp.id)}
                                                        className="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-1"
                                                    >
                                                        <Eye className="w-3 h-3" />
                                                        <span>Modal</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                )}

                {/* ============================================================== */}
                {/* TAB 3: EMPLOYEE MATRIX (INLINE VIEW)                            */}
                {/* ============================================================== */}
                {activeTab === 'employee' && (
                    <div className="space-y-6">
                        <EmployeeSkillMatrixInline
                            selectedUserId={selectedInlineUserId}
                            onSelectUser={(newId) => setSelectedInlineUserId(newId)}
                            employees={employees}
                            departments={departments}
                            onOpenModal={(userId) => handleOpenEmployeeModal(userId)}
                        />
                    </div>
                )}
            </div>
        </AsideLayout>
    );
}
