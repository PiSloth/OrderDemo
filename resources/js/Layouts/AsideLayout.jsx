import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import CreatePromoteActionModal from '../components/CreatePromoteActionModal';

export default function AsideLayout({ children, title }) {
    const { auth = {}, url = '' } = usePage().props;
    const currentUrl = usePage().url || window.location.pathname;
    const user = auth?.user;

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState({
        performance: false,
        order: false,
        psi: false,
        todo: false,
        operation: false,
        kpi: false,
        whiteboard: false,
        officeAsset: false,
        calendar: false,
    });

    // Auto-open group based on current URL path
    useEffect(() => {
        const path = currentUrl;
        const newGroups = { ...openGroups };
        
        if (path.startsWith('/performance')) newGroups.performance = true;
        else if (path.startsWith('/order')) newGroups.order = true;
        else if (path.startsWith('/psi')) newGroups.psi = true;
        else if (path.startsWith('/todo')) newGroups.todo = true;
        else if (path.startsWith('/operation')) newGroups.operation = true;
        else if (path.startsWith('/kpi') && !path.startsWith('/kpi/sale-kpi')) newGroups.kpi = true;
        else if (path.startsWith('/whiteboard')) newGroups.whiteboard = true;
        else if (path.startsWith('/office-asset')) newGroups.officeAsset = true;
        else if (path.startsWith('/calendar')) newGroups.calendar = true;

        setOpenGroups(newGroups);
    }, [currentUrl]);

    const isCurrentUrl = (href) => {
        return currentUrl === href || currentUrl.startsWith(href + '/');
    };

    const toggleGroup = (groupKey) => {
        setOpenGroups(prev => ({
            ...prev,
            [groupKey]: !prev[groupKey]
        }));
    };

    const linkBase = "flex items-center p-2 text-sm font-medium rounded-xl transition-all group";
    const linkActive = "bg-[#FEF08A] text-slate-900 font-bold shadow-sm";
    const linkInactive = "text-slate-650 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-350 dark:hover:bg-slate-800/70 dark:hover:text-slate-100";
 
    const dropdownHeaderCls = "w-full flex items-center justify-between p-2.5 text-sm font-semibold rounded-xl text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800 transition-all";

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 lg:flex">
            {/* Mobile backdrop */}
            {mobileMenuOpen && (
                <div
                    className="fixed inset-0 z-40 bg-slate-900/40 dark:bg-slate-950/70 lg:hidden backdrop-blur-sm transition-all"
                    onClick={() => setMobileMenuOpen(false)}
                />
            )}

            {/* Sidebar Navigation */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-200 bg-white transition-transform duration-200 ease-out dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 overflow-y-auto ${
                    mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-full flex-col justify-between p-4">
                    <div>
                        {/* ShweTatar Brand Header */}
                        <div className="flex items-center justify-between px-3 py-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <a href="/order" className="flex items-center gap-3">
                                <img src="/images/logo.png" alt="STT Logo" className="w-10 h-8 bg-white rounded-md p-0.5 shadow-sm" />
                                <div>
                                    <span className="block font-bold text-slate-800 dark:text-white leading-tight">ShweTatar</span>
                                    <span className="block text-[10px] text-slate-500 font-medium">Gold & Jewellery</span>
                                </div>
                            </a>
                            <div className="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        {/* Hot Create Todo Button */}
                        <div className="my-4 px-1">
                            <button
                                type="button"
                                onClick={() => {
                                    window.location.href = '/todo/list?createTask=1';
                                }}
                                className="w-full flex items-center justify-center gap-2 rounded-2xl bg-[#FEF08A] hover:bg-[#FDE047] py-2.5 px-3 text-xs font-bold text-slate-800 shadow-md hover:scale-[1.02] active:scale-98 transition-all"
                            >
                                <span className="text-sm">+</span>
                                <span>Create Todo Task</span>
                            </button>
                        </div>

                        {/* Navigation Items */}
                        <nav className="space-y-1">
                            <ul className="space-y-1">
                                {/* Performance Dropdown */}
                                <li>
                                    <button onClick={() => toggleGroup('performance')} className={dropdownHeaderCls}>
                                        <span className="flex items-center">
                                            <span className="mr-3 text-base">📈</span>
                                            <span>Performance</span>
                                        </span>
                                        <span className={`transform transition-transform ${openGroups.performance ? 'rotate-180' : ''}`}>▼</span>
                                    </button>
                                    {openGroups.performance && (
                                        <ul className="mt-1 pl-6 space-y-1">
                                            <li>
                                                <a href="/performance/branch-score" className={`${linkBase} ${isCurrentUrl('/performance/branch-score') ? linkActive : linkInactive}`}>
                                                    <span>Daily Scores</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="/performance/sale-dashboard" className={`${linkBase} ${isCurrentUrl('/performance/sale-dashboard') ? linkActive : linkInactive}`}>
                                                    <span>Sale</span>
                                                </a>
                                            </li>
                                        </ul>
                                    )}
                                </li>

                                {/* Sale KPI Top Level Link */}
                                <li>
                                    <a href="/sale-kpi" className={`${linkBase} ${isCurrentUrl('/sale-kpi') ? linkActive : linkInactive} py-2.5`}>
                                        <span className="mr-3 text-base">📊</span>
                                        <span className="font-semibold text-slate-800 dark:text-slate-200">Sale KPI</span>
                                    </a>
                                </li>

                                {/* KPI Tasks Dropdown */}
                                <li>
                                    <button onClick={() => toggleGroup('kpi')} className={dropdownHeaderCls}>
                                        <span className="flex items-center">
                                            <span className="mr-3 text-base">📋</span>
                                            <span>KPI Tasks</span>
                                        </span>
                                        <span className={`transform transition-transform ${openGroups.kpi ? 'rotate-180' : ''}`}>▼</span>
                                    </button>
                                    {openGroups.kpi && (
                                        <ul className="mt-1 pl-6 space-y-1">
                                            <li>
                                                <a href="/kpi/dashboard" className={`${linkBase} ${isCurrentUrl('/kpi/dashboard') ? linkActive : linkInactive}`}>
                                                    <span>Dashboard</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="/kpi/tasks" className={`${linkBase} ${isCurrentUrl('/kpi/tasks') ? linkActive : linkInactive}`}>
                                                    <span>My Tasks</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="/kpi/approvals" className={`${linkBase} ${isCurrentUrl('/kpi/approvals') ? linkActive : linkInactive}`}>
                                                    <span>Approvals</span>
                                                </a>
                                            </li>
                                        </ul>
                                    )}
                                </li>

                                {/* Todo Dropdown */}
                                <li>
                                    <button onClick={() => toggleGroup('todo')} className={dropdownHeaderCls}>
                                        <span className="flex items-center">
                                            <span className="mr-3 text-base">📝</span>
                                            <span>Todo Lists</span>
                                        </span>
                                        <span className={`transform transition-transform ${openGroups.todo ? 'rotate-180' : ''}`}>▼</span>
                                    </button>
                                    {openGroups.todo && (
                                        <ul className="mt-1 pl-6 space-y-1">
                                            <li>
                                                <a href="/todo/dashboard" className={`${linkBase} ${isCurrentUrl('/todo/dashboard') ? linkActive : linkInactive}`}>
                                                    <span>Dashboard</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="/todo/list" className={`${linkBase} ${isCurrentUrl('/todo/list') ? linkActive : linkInactive}`}>
                                                    <span>Task List</span>
                                                </a>
                                            </li>
                                        </ul>
                                    )}
                                </li>

                                {/* Whiteboard */}
                                <li>
                                    <a href="/whiteboard/config" className={`${linkBase} ${isCurrentUrl('/whiteboard/config') ? linkActive : linkInactive}`}>
                                        <span className="mr-3 text-base">🖥️</span>
                                        <span>Whiteboard Config</span>
                                    </a>
                                </li>

                                {/* Calendar */}
                                <li>
                                    <a href="/calendar/index" className={`${linkBase} ${isCurrentUrl('/calendar/index') ? linkActive : linkInactive}`}>
                                        <span className="mr-3 text-base">📅</span>
                                        <span>Calendar</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    {/* User profile section */}
                    {user && (
                        <div className="border-t border-slate-100 dark:border-slate-850 p-3">
                            <div className="text-xs font-semibold text-slate-800 dark:text-slate-205">{user.name}</div>
                            <div className="text-xs text-slate-500 dark:text-slate-400">{user.email}</div>
                        </div>
                    )}
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col min-w-0">
                <main className="flex-1 p-6">{children}</main>
            </div>
            <CreatePromoteActionModal />
        </div>
    );
}
