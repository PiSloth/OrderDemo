import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import toast, { Toaster } from 'react-hot-toast';

export default function TodoLayout({ children, title }) {
    const { auth = {}, flash = {} } = usePage().props;
    const currentUrl = usePage().url || window.location.pathname;
    const user = auth?.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Auto trigger react-hot-toast on Inertia flash messages
    useEffect(() => {
        if (flash?.message) {
            toast.success(flash.message, { id: 'flash-success' });
        }
        if (flash?.error) {
            toast.error(flash.error, { id: 'flash-error' });
        }
    }, [flash]);

    const isCurrentUrl = (href) => {
        if (href === '/todo' || href === '/todo/list') {
            return currentUrl === '/todo' || currentUrl.startsWith('/todo/list');
        }
        return currentUrl.startsWith(href);
    };

    const navItems = [
        { label: 'Task List', href: '/todo/list', icon: '📋' },
        { label: 'Dashboard', href: '/todo/dashboard', icon: '📊' },
        { label: 'Configuration', href: '/todo/config', icon: '⚙️' },
        { label: 'Notifications', href: '/todo/notifications', icon: '🔔' },
    ];

    // Trigger global create task modal
    const handleGlobalCreateTask = () => {
        window.dispatchEvent(new CustomEvent('open-create-todo-modal'));
        setMobileMenuOpen(false);
    };

    // User initials helper
    const getUserInitials = (name = '') => {
        if (!name) return 'U';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.slice(0, 2).toUpperCase();
    };

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased">
            <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
            {/* Top Navigation Bar with Glassmorphism */}
            <nav className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-900/85 transition-all">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        {/* Left Side: Brand Logo & Desktop Nav Links */}
                        <div className="flex items-center gap-6 sm:gap-8 lg:gap-12">
                            {/* Brand Header */}
                            <Link
                                href="/dashboard"
                                className="group flex items-center gap-3 transition shrink-0"
                            >
                                <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 font-extrabold text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </span>
                                <div>
                                    <div className="flex items-center gap-1.5">
                                        <span className="text-lg font-black tracking-tight text-slate-900 dark:text-white">Todo</span>
                                        <span className="rounded-md bg-indigo-100 px-1.5 py-0.5 text-[10px] font-extrabold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">HUB</span>
                                    </div>
                                    <span className="block text-[10px] font-semibold text-slate-400 dark:text-slate-500 -mt-0.5">Task Management System</span>
                                </div>
                            </Link>

                            {/* Desktop Nav Items (Clean text with bottom indicator bar for active link) */}
                            <div className="hidden sm:flex sm:items-center sm:gap-6 lg:gap-8 h-16">
                                {navItems.map((item, idx) => {
                                    const active = isCurrentUrl(item.href);
                                    return (
                                        <Link
                                            key={idx}
                                            href={item.href}
                                            preserveScroll
                                            className={`flex items-center gap-2 h-full text-xs font-bold transition-all border-b-2 ${
                                                active
                                                    ? 'text-indigo-600 dark:text-indigo-400 border-indigo-600 dark:border-indigo-400 font-extrabold'
                                                    : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 border-transparent font-medium'
                                            }`}
                                        >
                                            <span className="text-sm">{item.icon}</span>
                                            <span>{item.label}</span>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Right Side: Global Create Task + User Profile & Mobile Toggle */}
                        <div className="flex items-center gap-3">
                            {/* Global Create Task Action Button */}
                            <button
                                type="button"
                                onClick={handleGlobalCreateTask}
                                className="hidden sm:inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-xs font-extrabold text-white shadow-md shadow-emerald-500/20 transition hover:from-emerald-700 hover:to-teal-700 active:scale-95"
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ New Task</span>
                            </button>

                            {/* User Profile Avatar */}
                            {user && (
                                <div className="hidden sm:flex items-center gap-2.5 border-l border-slate-200 dark:border-slate-800 pl-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 shadow-xs dark:bg-indigo-950 dark:text-indigo-300">
                                        {getUserInitials(user.name)}
                                    </div>
                                    <div className="hidden lg:flex flex-col text-left">
                                        <span className="text-xs font-extrabold text-slate-900 dark:text-slate-100 leading-snug">{user.name}</span>
                                        <span className="text-[10px] font-medium text-slate-400 dark:text-slate-500 max-w-[120px] truncate">{user.email}</span>
                                    </div>
                                </div>
                            )}

                            {/* Mobile Hamburger Button (Only on Mobile < 640px) */}
                            <button
                                type="button"
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                className="rounded-2xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 sm:hidden transition active:scale-95"
                                aria-label="Toggle navigation menu"
                            >
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2.5"
                                        d={mobileMenuOpen ? "M6 18L18 6M6 6l12 12" : "M4 6h16M4 12h16M4 18h16"}
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Menu Dropdown Panel */}
                {mobileMenuOpen && (
                    <div className="border-b border-slate-200 bg-white/95 backdrop-blur-xl px-4 pt-3 pb-5 dark:border-slate-800 dark:bg-slate-900/95 sm:hidden space-y-3 animate-in slide-in-from-top duration-200">
                        {/* Mobile User Info Header */}
                        {user && (
                            <div className="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/60 mb-2">
                                <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-600 font-extrabold text-white shadow-xs">
                                    {getUserInitials(user.name)}
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-xs font-extrabold text-slate-900 dark:text-white">{user.name}</span>
                                    <span className="text-[11px] text-slate-400 dark:text-slate-400">{user.email}</span>
                                </div>
                            </div>
                        )}

                        {/* Mobile Create Task Button */}
                        <button
                            type="button"
                            onClick={handleGlobalCreateTask}
                            className="w-full flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 py-3 text-xs font-extrabold text-white shadow-md active:scale-95 transition"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>+ Create New Task</span>
                        </button>

                        {/* Mobile Nav Links */}
                        <div className="space-y-1 pt-1">
                            {navItems.map((item, idx) => {
                                const active = isCurrentUrl(item.href);
                                return (
                                    <Link
                                        key={idx}
                                        href={item.href}
                                        preserveScroll
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={`flex items-center gap-3 rounded-2xl px-4 py-3 text-xs font-bold transition ${
                                            active
                                                ? 'bg-indigo-600 text-white shadow-md'
                                                : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                                        }`}
                                    >
                                        <span className="text-base">{item.icon}</span>
                                        <span>{item.label}</span>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                )}
            </nav>

            {/* Main Content Area */}
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.message && (
                    <div className="mb-6 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-bold text-emerald-800 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <div className="flex items-center gap-2">
                            <span>✅</span>
                            <span>{flash.message}</span>
                        </div>
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs font-bold text-rose-800 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
                        <div className="flex items-center gap-2">
                            <span>⚠️</span>
                            <span>{flash.error}</span>
                        </div>
                    </div>
                )}
                {children}
            </main>
        </div>
    );
}
