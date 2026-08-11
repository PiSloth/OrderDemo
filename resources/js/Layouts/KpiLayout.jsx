import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import CreatePromoteActionModal from '../components/CreatePromoteActionModal';

export default function KpiLayout({ children, title }) {
    const { auth = {}, url = '' } = usePage().props;
    const currentUrl = usePage().url || window.location.pathname;
    const user = auth?.user;
    const can = auth?.can || {};
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const isCurrentUrl = (href) => {
        if (href === '/kpi' || href === '/kpi/dashboard') {
            return currentUrl === '/kpi' || currentUrl.startsWith('/kpi/dashboard');
        }
        return currentUrl.startsWith(href);
    };

    const navItems = [
        { label: 'Dashboard', href: '/kpi/dashboard' },
        { label: 'My Tasks', href: '/kpi/tasks' },
        { label: 'Audit', href: '/kpi/audit' },
        { label: 'Certificate', href: '/kpi/certificate' },
        { label: 'Exclusions', href: '/kpi/exclusions' },
        { label: 'Approvals', href: '/kpi/approvals' },
        { label: 'Associate Tasks', href: '/kpi/associate-tasks' },
        { label: 'Holidays', href: '/kpi/holidays', show: can.kpiManageHolidays },
        { label: 'Templates', href: '/kpi/templates', show: can.kpiManageTemplates },
        { label: 'Assignments', href: '/kpi/assignments', show: can.kpiManageAssignments },
        { label: 'KPI Manual', href: '/kpi/manual', show: can.kpiManageAssignments },
        { label: 'Import / Export', href: '/kpi/import-export', show: can.kpiManageImports },
        { label: 'Leaderboard', href: '/kpi/leaderboard' },
    ];

    return (
        <div className="min-h-screen bg-slate-100 dark:bg-slate-950 lg:flex">
            {/* Mobile backdrop */}
            {mobileMenuOpen && (
                <div
                    className="fixed inset-0 z-40 bg-slate-900/40 dark:bg-slate-950/70 lg:hidden"
                    onClick={() => setMobileMenuOpen(false)}
                />
            )}

            {/* Sidebar Navigation */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-200 bg-white transition-transform duration-200 ease-out dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 ${
                    mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-full flex-col justify-between p-4">
                    <div>
                        {/* Logo / Header */}
                        <div className="flex items-center justify-between px-3 py-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <a href="/kpi" className="flex items-center gap-2">
                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 font-bold text-white dark:bg-slate-100 dark:text-slate-900">
                                    K
                                </span>
                                <div>
                                    <span className="font-semibold text-slate-900 dark:text-slate-100">KPI Manager</span>
                                    <span className="block text-xs text-slate-500">OrderDemo</span>
                                </div>
                            </a>
                            <button
                                type="button"
                                onClick={() => setMobileMenuOpen(false)}
                                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden"
                            >
                                ✕
                            </button>
                        </div>

                        {/* Navigation Links */}
                        <nav className="mt-4 space-y-1 overflow-y-auto max-h-[calc(100vh-140px)]">
                            {navItems.map((item, idx) => {
                                if (item.show === false) return null;
                                const active = isCurrentUrl(item.href);
                                return (
                                    <a
                                        key={idx}
                                        href={item.href}
                                        className={`flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                            active
                                                ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                                        }`}
                                    >
                                        {item.label}
                                    </a>
                                );
                            })}
                        </nav>
                    </div>

                    {/* User profile section */}
                    {user && (
                        <div className="border-t border-slate-100 p-3 dark:border-slate-800">
                            <div className="text-xs font-semibold text-slate-900 dark:text-slate-100">{user.name}</div>
                            <div className="text-xs text-slate-500 dark:text-slate-400">{user.email}</div>
                        </div>
                    )}
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col min-w-0">
                {/* Mobile Top Header */}
                <header className="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 lg:hidden">
                    <button
                        type="button"
                        onClick={() => setMobileMenuOpen(true)}
                        className="rounded-lg border border-slate-300 p-2 text-slate-600 dark:border-slate-700 dark:text-slate-300"
                    >
                        ☰ Menu
                    </button>
                    <span className="font-semibold text-slate-900 dark:text-slate-100">{title || 'KPI Manager'}</span>
                </header>

                <main className="flex-1 p-6">{children}</main>
            </div>
            <CreatePromoteActionModal />
        </div>
    );
}
