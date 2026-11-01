import React, { useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Toaster } from 'react-hot-toast';
import AsideLayout from './AsideLayout';
import { PanelLeftOpen } from 'lucide-react';

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
        <AsideLayout title={title || "KPI Manager"} hideTopNav={true} hideFloatingTrigger={true} noPadding={true}>
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
                    className={`fixed inset-y-0 left-0 z-40 w-72 shrink-0 border-r border-slate-200 bg-white transition-transform duration-200 ease-out dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-0 lg:z-0 lg:h-screen lg:translate-x-0 ${
                        mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="flex h-full flex-col justify-between p-4">
                        <div>
                            {/* Logo / Header */}
                            <div className="flex items-center justify-between px-3 py-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                                <Link href="/kpi" className="flex items-center gap-2">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 font-bold text-white dark:bg-slate-100 dark:text-slate-900">
                                        K
                                    </span>
                                    <div>
                                        <span className="font-semibold text-slate-900 dark:text-slate-100">KPI Manager</span>
                                        <span className="block text-xs text-slate-500">OrderDemo</span>
                                    </div>
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden"
                                >
                                    ✕
                                </button>
                            </div>

                            {/* Main Navigation Sidebar Trigger (Obsidian & Gold Luxury Tab as seen on Sale KPI) */}
                            <div className="pt-3 pb-1">
                                <button
                                    type="button"
                                    onClick={() => window.dispatchEvent(new CustomEvent('open-main-aside'))}
                                    onMouseEnter={() => window.dispatchEvent(new CustomEvent('open-main-aside'))}
                                    className="group flex w-full items-center justify-between gap-2.5 bg-slate-900/95 dark:bg-slate-950/95 backdrop-blur-md border border-amber-500/40 dark:border-amber-400/50 shadow-md shadow-slate-900/20 rounded-2xl py-2 px-3 text-white transition-all duration-300 ease-in-out hover:bg-slate-800 hover:shadow-xl hover:border-amber-400 hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
                                    title="Open Main Navigation Sidebar"
                                    aria-label="Open Main Navigation Sidebar"
                                >
                                    <div className="flex items-center gap-2">
                                        {/* Glowing Gold Accent Line */}
                                        <span className="w-1 h-4 rounded-full bg-gradient-to-b from-amber-300 via-amber-400 to-yellow-500 shadow-[0_0_8px_rgba(251,191,36,0.7)]" />

                                        {/* Gold Panel Icon */}
                                        <PanelLeftOpen size={18} className="text-amber-400 group-hover:scale-110 group-hover:text-amber-300 transition-all duration-200" />

                                        {/* Distinct Crisp Typography */}
                                        <span className="text-xs font-bold tracking-wider uppercase text-amber-100/90 group-hover:text-white transition-colors">
                                            Menu
                                        </span>
                                    </div>

                                    {/* Subtle micro jewel dot */}
                                    <span className="w-1.5 h-1.5 rounded-full bg-amber-400/80 group-hover:bg-amber-300 shadow-[0_0_6px_rgba(251,191,36,0.9)] animate-pulse" />
                                </button>
                            </div>

                            {/* Navigation Links */}
                            <nav className="mt-3 space-y-1 overflow-y-auto max-h-[calc(100vh-210px)]">
                                {navItems.map((item, idx) => {
                                    if (item.show === false) return null;
                                    const active = isCurrentUrl(item.href);
                                    return (
                                        <Link
                                            key={idx}
                                            href={item.href}
                                            className={`flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                                active
                                                    ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                                            }`}
                                        >
                                            {item.label}
                                        </Link>
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
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => window.dispatchEvent(new CustomEvent('open-main-aside'))}
                                className="flex items-center gap-1.5 bg-slate-900/95 dark:bg-slate-950/95 border border-amber-500/40 rounded-xl py-1.5 px-2.5 text-white shadow-xs text-xs font-bold"
                                title="Open Main Navigation Sidebar"
                            >
                                <span className="w-1 h-3 rounded-full bg-gradient-to-b from-amber-300 to-amber-500 shadow-[0_0_6px_rgba(251,191,36,0.7)]" />
                                <PanelLeftOpen size={15} className="text-amber-400" />
                                <span className="text-[11px] uppercase tracking-wider text-amber-100">Menu</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setMobileMenuOpen(true)}
                                className="rounded-xl border border-slate-300 px-2.5 py-1.5 text-slate-600 dark:border-slate-700 dark:text-slate-300 text-xs font-semibold"
                            >
                                KPI Menu
                            </button>
                        </div>
                        <span className="font-semibold text-slate-900 dark:text-slate-100">{title || 'KPI Manager'}</span>
                    </header>

                    <main className="flex-1 p-6">{children}</main>
                </div>
            </div>
            <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
        </AsideLayout>
    );
}
