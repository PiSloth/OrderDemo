import React, { useState, useEffect, useRef, useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import KpiLayout from '@/Layouts/KpiLayout';
import SubmissionDetailModal from './Components/SubmissionDetailModal';
import PhotoCarouselModal from './Components/PhotoCarouselModal';
import SearchableEmployeeSelect from './Components/SearchableEmployeeSelect';

export default function Certificate({ month, users, selectedUser, certificate, appendixRows, passedEvidenceRows, selectedSubmission: initialSubmission, canManageTemplates }) {
    const [selectedSubmission, setSelectedSubmission] = useState(initialSubmission || null);
    const [lightboxImages, setLightboxImages] = useState([]);
    const [lightboxIndex, setLightboxIndex] = useState(null);
    const [copied, setCopied] = useState(false);
    const copyTimeoutRef = useRef(null);

    // Sync submission from Inertia reload
    useEffect(() => {
        setSelectedSubmission(initialSubmission || null);
    }, [initialSubmission]);

    // Body scroll lock when submission detail is open
    useEffect(() => {
        document.body.style.overflow = selectedSubmission ? 'hidden' : '';
        return () => { document.body.style.overflow = ''; };
    }, [selectedSubmission]);

    const openSubmission = (submissionId) => {
        router.get('/kpi/certificate', { month, user_id: selectedUser?.id, submission_id: submissionId }, { preserveState: true, preserveScroll: true });
    };

    const closeSubmission = () => {
        setSelectedSubmission(null);
        router.get('/kpi/certificate', { month, user_id: selectedUser?.id }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const openLightbox = (images, idx = 0) => {
        setLightboxImages(images);
        setLightboxIndex(idx);
    };

    const handleMonthChange = (e) => {
        router.get('/kpi/certificate', { month: e.target.value, user_id: selectedUser?.id || '' }, { preserveScroll: true });
    };

    const handleUserChange = (e) => {
        router.get('/kpi/certificate', { month, user_id: e.target.value }, { preserveScroll: true });
    };

    const copyLink = () => {
        const url = `${window.location.origin}/kpi/certificate?month=${month}&user_id=${selectedUser?.id || ''}`;
        navigator.clipboard.writeText(url).then(() => {
            setCopied(true);
            clearTimeout(copyTimeoutRef.current);
            copyTimeoutRef.current = setTimeout(() => setCopied(false), 1500);
        });
    };

    const overall = certificate?.overall || {};
    const kpiScore = overall.kpi_score ?? 0;
    const groups = certificate?.groups || [];
    const groupPassCount = groups.filter(g => g.group_result === 'Pass').length;
    const chartColor = kpiScore >= 50 ? '#16a34a' : '#dc2626';

    return (
        <KpiLayout title="KPI Certificate">
            {/* Print Styles */}
            <style>{`
                @media print {
                    body * { visibility: hidden; }
                    #kpi-certificate-print, #kpi-certificate-print * { visibility: visible; }
                    #kpi-certificate-print { position: absolute; left: 0; top: 0; width: 100%; }
                    @page { size: A4; margin: 12mm; }
                    .no-print { display: none !important; }
                    .print-break { page-break-after: always; }
                }
            `}</style>

            <div className="space-y-4">
                {/* Controls Bar */}
                <div className="no-print flex flex-wrap items-center gap-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-5 py-3 shadow-sm">
                    {canManageTemplates && (
                        <>
                            <div className="flex items-center gap-2">
                                <label className="text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Month</label>
                                <input
                                    type="month"
                                    value={month}
                                    onChange={handleMonthChange}
                                    className="h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <label className="text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Employee</label>
                                <SearchableEmployeeSelect
                                    users={users}
                                    value={selectedUser?.id || ''}
                                    onChange={(id) => router.get('/kpi/certificate', { month, user_id: id }, { preserveScroll: true })}
                                />
                            </div>
                        </>
                    )}
                    <div className="ml-auto flex items-center gap-2">
                        <button
                            onClick={copyLink}
                            className="no-print flex items-center gap-1.5 h-9 px-4 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-sm hover:bg-slate-50 dark:hover:bg-slate-600 transition cursor-pointer"
                        >
                            {copied ? (
                                <>
                                    <svg className="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" /></svg>
                                    <span className="text-emerald-600 font-medium">Copied!</span>
                                </>
                            ) : (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    Copy Link
                                </>
                            )}
                        </button>
                        <button
                            onClick={() => window.print()}
                            className="no-print flex items-center gap-1.5 h-9 px-4 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm hover:bg-slate-700 dark:hover:bg-slate-100 transition font-medium cursor-pointer"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Print
                        </button>
                    </div>
                </div>

                {/* Empty state */}
                {(!selectedUser || !certificate) && (
                    <div className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center text-slate-400 text-sm">
                        No certificate data available.
                    </div>
                )}

                {selectedUser && certificate && (
                    <div id="kpi-certificate-print" className="max-w-[210mm] mx-auto space-y-4">
                        {/* Certificate Card */}
                        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
                            {/* Direct Report Link */}
                            <div className="no-print flex items-center justify-between gap-4 px-6 py-3 bg-indigo-50 dark:bg-indigo-950/30 border-b border-indigo-100 dark:border-indigo-900/50">
                                <span className="text-xs font-medium text-indigo-600 dark:text-indigo-400">Direct Report Link</span>
                                <span className="text-xs text-indigo-500 dark:text-indigo-400 font-mono truncate">
                                    {`${window.location.origin}/kpi/certificate?month=${month}&user_id=${selectedUser.id}`}
                                </span>
                            </div>

                            {/* Header: Employee Info */}
                            <div className="px-6 py-5 flex items-center justify-between border-b border-slate-100 dark:border-slate-700 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800 dark:to-slate-900">
                                <div>
                                    <p className="text-xs font-medium text-slate-400 uppercase tracking-widest mb-1">KPI Certificate</p>
                                    <h1 className="text-xl font-bold text-slate-800 dark:text-white">{selectedUser.name}</h1>
                                    {selectedUser.department && (
                                        <p className="text-sm text-slate-500 dark:text-slate-400">{selectedUser.department.name}</p>
                                    )}
                                    <p className="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                                        As Of Certificate — {certificate.month}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    <img src="/images/logo.png" alt="Company Logo" className="h-14 w-auto object-contain opacity-80" onError={(e) => { e.target.style.display = 'none'; }} />
                                </div>
                            </div>

                            {/* Metric Cards */}
                            <div className="grid grid-cols-2 gap-px bg-slate-100 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-700">
                                {/* Overhaul % Chart */}
                                <div className="bg-white dark:bg-slate-900 p-4 flex flex-col items-center">
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Overhaul Percentage</p>
                                    <div className="w-full max-w-[200px]">
                                        <ApexHalfDonut value={kpiScore} color={chartColor} />
                                    </div>
                                </div>
                                {/* KPI Score */}
                                <div className="bg-white dark:bg-slate-900 p-4 flex flex-col items-center justify-center">
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">KPI Score</p>
                                    <p className={`text-5xl font-black ${kpiScore >= 50 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                        {kpiScore.toFixed(2)}%
                                    </p>
                                    <p className={`text-sm font-bold mt-2 px-4 py-1 rounded-full ${kpiScore >= 50 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                                        {kpiScore >= 50 ? 'On Track' : 'Below Target'}
                                    </p>
                                </div>
                            </div>

                            {/* Main KPI Table */}
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                            <th className="px-3 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-10">No</th>
                                            <th className="px-3 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Title</th>
                                            <th className="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Late</th>
                                            <th className="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Absent</th>
                                            <th className="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Score</th>
                                            <th className="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {groups.map((group, gIdx) => (
                                            <React.Fragment key={gIdx}>
                                                {group.show_group_result ? (
                                                    <>
                                                        {/* Group header row */}
                                                        <tr className="bg-slate-50 dark:bg-slate-800/70 border-b border-slate-200 dark:border-slate-700 font-semibold">
                                                            <td rowSpan={group.template_count + 1} className="px-3 py-2 text-center text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-700 align-middle">
                                                                {group.no}
                                                            </td>
                                                            <td className="px-3 py-2 text-slate-700 dark:text-slate-200">{group.group_name}</td>
                                                            <td className="px-3 py-2 text-center text-slate-600 dark:text-slate-300">{group.summary.late_count}</td>
                                                            <td className="px-3 py-2 text-center text-slate-600 dark:text-slate-300">{group.summary.absent_count}</td>
                                                            <td className="px-3 py-2 text-center text-slate-600 dark:text-slate-300">{group.summary.score?.toFixed(2)}%</td>
                                                            <td className="px-3 py-2 text-center">
                                                                <ResultBadge result={group.group_result} />
                                                            </td>
                                                        </tr>
                                                        {/* Template rows */}
                                                        {group.templates.map((tpl, tIdx) => (
                                                            <tr key={tIdx} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                                                <td className="px-3 py-2 pl-6 text-slate-600 dark:text-slate-300 italic text-xs">{tpl.title}</td>
                                                                <td className="px-3 py-2 text-center text-slate-500">{tpl.summary.late_count}</td>
                                                                <td className="px-3 py-2 text-center text-slate-500">{tpl.summary.absent_count}</td>
                                                                <td className="px-3 py-2 text-center text-slate-500">{tpl.summary.score?.toFixed(2)}%</td>
                                                                <td className="px-3 py-2 text-center">
                                                                    <ResultBadge result={tpl.result} small />
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </>
                                                ) : (
                                                    /* Single template group */
                                                    group.templates.map((tpl, tIdx) => (
                                                        <tr key={tIdx} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                                            {tIdx === 0 && (
                                                                <td rowSpan={group.templates.length} className="px-3 py-2 text-center text-slate-600 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800 align-middle">
                                                                    {group.no}
                                                                </td>
                                                            )}
                                                            <td className="px-3 py-2 text-slate-700 dark:text-slate-200">{tpl.title}</td>
                                                            <td className="px-3 py-2 text-center text-slate-500">{tpl.summary.late_count}</td>
                                                            <td className="px-3 py-2 text-center text-slate-500">{tpl.summary.absent_count}</td>
                                                            <td className="px-3 py-2 text-center text-slate-500">{tpl.summary.score?.toFixed(2)}%</td>
                                                            <td className="px-3 py-2 text-center">
                                                                <ResultBadge result={tpl.result} />
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                                {/* Group separator */}
                                                <tr><td colSpan={6} className="border-b-2 border-slate-200 dark:border-slate-700 p-0 h-0" /></tr>
                                            </React.Fragment>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Master Report Summary */}
                            <div className="px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300">
                                Master Report: Final KPI Result Pass:{' '}
                                <span className="text-emerald-600 font-bold">{groupPassCount}</span>
                                {' '}/ {groups.length}
                            </div>
                        </div>

                        {/* Passed Evidence Section */}
                        {passedEvidenceRows && passedEvidenceRows.length > 0 && (
                            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
                                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60">
                                    <h2 className="text-sm font-semibold text-slate-700 dark:text-slate-200">Evidence of Passed KPI Group</h2>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Group</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Template</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Freq</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Remark</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Photos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {passedEvidenceRows.flatMap((group) =>
                                                group.templates.flatMap((tpl, tIdx) =>
                                                    tpl.rows.map((row, rIdx) => (
                                                        <tr key={`${group.group_name}-${tIdx}-${rIdx}`} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 align-top">
                                                            {tIdx === 0 && rIdx === 0 && (
                                                                <td rowSpan={group.templates.reduce((sum, t) => sum + t.rows.length, 0)} className="px-4 py-3 text-slate-700 dark:text-slate-200 font-medium border-r border-slate-100 dark:border-slate-800 align-top">
                                                                    {group.group_name}
                                                                </td>
                                                            )}
                                                            {rIdx === 0 && (
                                                                <td rowSpan={tpl.rows.length} className="px-4 py-3 text-slate-600 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800 align-top">
                                                                    {tpl.template_title}
                                                                </td>
                                                            )}
                                                            <td className="px-4 py-3 align-top">
                                                                <FreqBadge freq={row.frequency} />
                                                            </td>
                                                            <td className="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap align-top text-xs">
                                                                {row.requested_date}
                                                            </td>
                                                            <td className="px-4 py-3 text-slate-500 dark:text-slate-400 align-top text-xs max-w-[160px]">
                                                                {row.approve_remark}
                                                            </td>
                                                            <td className="px-4 py-3 align-top">
                                                                {row.images && row.images.length > 0 ? (
                                                                    <div className="flex flex-wrap gap-1.5">
                                                                        {row.images.map((img, iIdx) => (
                                                                            <button
                                                                                key={iIdx}
                                                                                type="button"
                                                                                onClick={() => openLightbox(row.images, iIdx)}
                                                                                className="w-12 h-10 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 hover:border-indigo-400 transition group cursor-pointer bg-slate-100"
                                                                            >
                                                                                <img src={img.url} alt={img.title} className="w-full h-full object-cover group-hover:scale-110 transition" />
                                                                            </button>
                                                                        ))}
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-xs text-slate-300">—</span>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))
                                                )
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Signature Block */}
                        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-6">
                            <div className="grid grid-cols-2 gap-8">
                                {['Checked by', 'Acknowledged by'].map((label) => (
                                    <div key={label} className="text-center">
                                        <div className="h-16 border-b-2 border-slate-300 dark:border-slate-600 mb-2" />
                                        <p className="text-xs font-medium text-slate-500 dark:text-slate-400">{label}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Appendix: Approver Remarks */}
                        {appendixRows && appendixRows.length > 0 && (
                            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
                                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60">
                                    <h2 className="text-sm font-semibold text-slate-700 dark:text-slate-200">Appendix: Approver Remarks</h2>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Template</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Remark</th>
                                                <th className="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Remark By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {appendixRows.map((group, gIdx) =>
                                                group.rows.map((row, rIdx) => (
                                                    <tr key={`${gIdx}-${rIdx}`} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                                        {rIdx === 0 && (
                                                            <td rowSpan={group.rowspan} className="px-4 py-3 text-slate-700 dark:text-slate-200 font-medium border-r border-slate-100 dark:border-slate-800 align-top">
                                                                {group.template_title}
                                                            </td>
                                                        )}
                                                        <td className="px-4 py-3">
                                                            <button
                                                                type="button"
                                                                onClick={() => openSubmission(row.submission_id)}
                                                                className={`text-left underline underline-offset-2 decoration-dashed text-sm cursor-pointer hover:opacity-75 transition ${row.is_rejected ? 'text-rose-600 dark:text-rose-400' : 'text-slate-600 dark:text-slate-300'}`}
                                                            >
                                                                {row.remark}
                                                            </button>
                                                        </td>
                                                        <td className="px-4 py-3 text-slate-500 dark:text-slate-400 text-sm">{row.remark_by}</td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Submission Detail Modal */}
            <SubmissionDetailModal
                submission={selectedSubmission}
                onClose={closeSubmission}
            />

            {/* Lightbox (for passed evidence photos) */}
            <PhotoCarouselModal
                isOpen={lightboxIndex !== null}
                images={lightboxImages}
                selectedIndex={lightboxIndex ?? 0}
                onClose={() => { setLightboxIndex(null); setLightboxImages([]); }}
                onSelectIndex={setLightboxIndex}
            />
        </KpiLayout>
    );
}

// Small result badge component
function ResultBadge({ result, small = false }) {
    const pass = result === 'Pass';
    return (
        <span className={`inline-flex items-center justify-center px-2 py-0.5 rounded-full font-semibold ${small ? 'text-xs' : 'text-xs'} ${pass ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400'}`}>
            {pass ? '✓ Pass' : '✗ Fail'}
        </span>
    );
}

function FreqBadge({ freq }) {
    const colors = { daily: 'bg-emerald-100 text-emerald-700', weekly: 'bg-blue-100 text-blue-700' };
    const cls = colors[freq] || 'bg-purple-100 text-purple-700';
    return (
        <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium capitalize ${cls}`}>{freq}</span>
    );
}

// Native ApexCharts half-donut using apexcharts package directly
function ApexHalfDonut({ value, color }) {
    const containerRef = useRef(null);
    const chartRef = useRef(null);

    useEffect(() => {
        if (!containerRef.current) return;

        const ApexCharts = window.ApexCharts;
        if (!ApexCharts) {
            // Dynamically import apexcharts
            import('apexcharts').then(({ default: AC }) => {
                if (chartRef.current) { chartRef.current.destroy(); }
                const chart = new AC(containerRef.current, buildOptions(value, color));
                chart.render();
                chartRef.current = chart;
            });
            return;
        }

        if (chartRef.current) {
            chartRef.current.updateOptions(buildOptions(value, color), true);
            chartRef.current.updateSeries([value]);
        } else {
            const chart = new ApexCharts(containerRef.current, buildOptions(value, color));
            chart.render();
            chartRef.current = chart;
        }

        return () => { if (chartRef.current) { chartRef.current.destroy(); chartRef.current = null; } };
    }, [value, color]);

    return <div ref={containerRef} />;
}

function buildOptions(value, color) {
    return {
        chart: { type: 'radialBar', height: 180, offsetY: -10, sparkline: { enabled: true } },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: { background: '#e2e8f0', strokeWidth: '97%', margin: 5 },
                dataLabels: {
                    name: { show: false },
                    value: { offsetY: -2, fontSize: '22px', fontWeight: 700, color, formatter: (v) => `${parseFloat(v).toFixed(2)}%` },
                },
            },
        },
        colors: [color],
        series: [value],
        grid: { padding: { top: -10 } },
        fill: { type: 'solid' },
        labels: ['KPI Overhaul'],
    };
}
