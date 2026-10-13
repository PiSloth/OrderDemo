import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import {
    Table,
    TrendingUp,
    TrendingDown,
    Calendar,
    RefreshCw,
    AlertCircle,
    ArrowUpRight,
    ArrowDownRight,
    Minus,
    Layers,
    Filter
} from 'lucide-react';

export default function TargetVsActualSummaryTable({
    defaultFrom,
    defaultTo,
    branches = [],
    selectedBranches = []
}) {
    const [primaryFrom, setPrimaryFrom] = useState(defaultFrom);
    const [primaryTo, setPrimaryTo] = useState(defaultTo);
    const [enableCompare, setEnableCompare] = useState(false);
    const [compareFrom, setCompareFrom] = useState('');
    const [compareTo, setCompareTo] = useState('');
    const [metric, setMetric] = useState('gram');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [summaryData, setSummaryData] = useState(null);

    const primaryRangeInputRef = useRef(null);
    const compareRangeInputRef = useRef(null);
    const primaryPickerRef = useRef(null);
    const comparePickerRef = useRef(null);

    // Initialize Flatpickr for Primary Range
    useEffect(() => {
        if (!primaryRangeInputRef.current) return;

        primaryPickerRef.current = flatpickr(primaryRangeInputRef.current, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [primaryFrom, primaryTo],
            static: true,
            onChange: (selectedDates, dateStr, instance) => {
                if (selectedDates && selectedDates.length === 2) {
                    setPrimaryFrom(instance.formatDate(selectedDates[0], 'Y-m-d'));
                    setPrimaryTo(instance.formatDate(selectedDates[1], 'Y-m-d'));
                }
            }
        });

        return () => {
            if (primaryPickerRef.current) {
                primaryPickerRef.current.destroy();
            }
        };
    }, []);

    // Initialize or toggle Flatpickr for Comparison Range
    useEffect(() => {
        if (!enableCompare) {
            if (comparePickerRef.current) {
                comparePickerRef.current.destroy();
                comparePickerRef.current = null;
            }
            setCompareFrom('');
            setCompareTo('');
            return;
        }

        if (enableCompare && compareRangeInputRef.current) {
            // Auto default comparison to previous month or year-over-year
            const defaultCompStart = compareFrom || new Date(new Date(primaryFrom).setMonth(new Date(primaryFrom).getMonth() - 1)).toISOString().split('T')[0];
            const defaultCompEnd = compareTo || new Date(new Date(primaryTo).setMonth(new Date(primaryTo).getMonth() - 1)).toISOString().split('T')[0];
            
            setCompareFrom(defaultCompStart);
            setCompareTo(defaultCompEnd);

            comparePickerRef.current = flatpickr(compareRangeInputRef.current, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: [defaultCompStart, defaultCompEnd],
                static: true,
                onChange: (selectedDates, dateStr, instance) => {
                    if (selectedDates && selectedDates.length === 2) {
                        setCompareFrom(instance.formatDate(selectedDates[0], 'Y-m-d'));
                        setCompareTo(instance.formatDate(selectedDates[1], 'Y-m-d'));
                    }
                }
            });
        }

        return () => {
            if (comparePickerRef.current) {
                comparePickerRef.current.destroy();
            }
        };
    }, [enableCompare]);

    const fetchData = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = {
                start_date: primaryFrom,
                end_date: primaryTo,
                metric,
            };

            if (enableCompare && compareFrom && compareTo) {
                params.compare_start_date = compareFrom;
                params.compare_end_date = compareTo;
            }

            if (selectedBranches && selectedBranches.length > 0) {
                params.branch_ids = selectedBranches.join(',');
            }

            const res = await axios.get('/sale-kpi/target-actual-summary', { params });
            setSummaryData(res.data);
        } catch (err) {
            console.error('Error fetching target vs actual summary:', err);
            setError(err.response?.data?.message || 'Failed to load target vs actual summary');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [primaryFrom, primaryTo, compareFrom, compareTo, enableCompare, metric, JSON.stringify(selectedBranches)]);

    const formatNum = (num) => {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString('en-US', {
            minimumFractionDigits: metric === 'pcs' ? 0 : 1,
            maximumFractionDigits: metric === 'pcs' ? 0 : 2
        });
    };

    const totals = summaryData?.totals || {};
    const rows = summaryData?.rows || [];
    const hasCompare = Boolean(summaryData?.has_comparison);
    const metricUnit = metric === 'pcs' ? 'pcs' : 'g';

    return (
        <div className="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs transition-all space-y-5">
            {/* Header & Controls */}
            <div className="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 flex items-center justify-center text-slate-800 dark:text-slate-200 shadow-2xs">
                        <Table className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-bold text-slate-900 dark:text-slate-100 text-base">Monthly Target vs Actual Summary</h3>
                            <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                Gap & Shortfall
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Branch-level targets, actual delivered units, shortfall variance, and comparative period tracking
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                    {/* Primary Date Range Flatpickr */}
                    <div className="flex items-center gap-1.5">
                        <input
                            ref={primaryRangeInputRef}
                            type="text"
                            placeholder="Primary Period"
                            className="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-slate-400 w-48 cursor-pointer shadow-xs"
                        />
                    </div>

                    {/* Compare Date Range Flatpickr (Toggleable) */}
                    {enableCompare ? (
                        <div className="flex items-center gap-1.5 animate-fadeIn">
                            <input
                                ref={compareRangeInputRef}
                                type="text"
                                placeholder="Compare Period"
                                className="h-8 px-3 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-slate-800 text-indigo-900 dark:text-indigo-200 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-400 w-48 cursor-pointer shadow-xs"
                            />
                            <button
                                type="button"
                                onClick={() => setEnableCompare(false)}
                                className="text-xs font-semibold text-rose-500 hover:text-rose-700 px-2 py-1 rounded-md hover:bg-rose-50 transition-colors"
                            >
                                Remove
                            </button>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setEnableCompare(true)}
                            className="h-8 px-3 text-xs font-semibold rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all flex items-center gap-1.5 shadow-2xs"
                        >
                            <Calendar className="w-3.5 h-3.5" />
                            <span>+ Compare Period</span>
                        </button>
                    )}

                    {/* Metric Selector */}
                    <div className="flex items-center bg-slate-100/80 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200/60 dark:border-slate-700 text-xs font-medium">
                        <button
                            type="button"
                            onClick={() => setMetric('gram')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                metric === 'gram'
                                    ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs font-semibold'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            }`}
                        >
                            Weight (g)
                        </button>
                        <button
                            type="button"
                            onClick={() => setMetric('pcs')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                metric === 'pcs'
                                    ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs font-semibold'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            }`}
                        >
                            Quantity (pcs)
                        </button>
                    </div>

                    {/* Refresh */}
                    <button
                        type="button"
                        onClick={fetchData}
                        disabled={loading}
                        className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                        title="Refresh summary data"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Table Area */}
            <div className="relative min-h-[180px]">
                {loading && (
                    <div className="absolute inset-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center z-10 rounded-2xl">
                        <div className="flex items-center gap-2.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <RefreshCw className="w-4 h-4 animate-spin text-indigo-600 dark:text-indigo-400" />
                            Calculating Target vs Actual metrics...
                        </div>
                    </div>
                )}

                {error ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <AlertCircle className="w-8 h-8 text-rose-500 mb-2" />
                        <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">Unable to load summary</p>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm">{error}</p>
                        <button
                            type="button"
                            onClick={fetchData}
                            className="mt-4 px-3 py-1.5 text-xs font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors"
                        >
                            Retry
                        </button>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-2xl border border-slate-150 dark:border-slate-800">
                        <table className="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr className="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-800 text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold select-none">
                                    <th className="py-3 px-4">Branch Name</th>
                                    <th className="py-3 px-4 text-right">Target ({metricUnit})</th>
                                    <th className="py-3 px-4 text-right">Actual ({metricUnit})</th>
                                    <th className="py-3 px-4 text-right">Gap ({metricUnit})</th>
                                    <th className="py-3 px-4 text-right">Shortfall %</th>
                                    {hasCompare && (
                                        <th className="py-3 px-4 text-right bg-indigo-50/40 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-300">
                                            Period Change (vs Comp)
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                                {/* Grand Total Row (Prominently styled) */}
                                <tr className="bg-slate-100/70 dark:bg-slate-800/90 font-bold text-slate-900 dark:text-white border-b-2 border-slate-200 dark:border-slate-700">
                                    <td className="py-3.5 px-4 text-xs font-black tracking-wide">
                                        GRAND TOTAL
                                    </td>
                                    <td className="py-3.5 px-4 text-right font-bold">
                                        {formatNum(totals.target)}
                                        {hasCompare && totals.compare && (
                                            <div className="text-[10px] text-slate-500 font-normal">
                                                Comp: {formatNum(totals.compare.target)}
                                            </div>
                                        )}
                                    </td>
                                    <td className="py-3.5 px-4 text-right font-bold text-indigo-600 dark:text-indigo-400">
                                        {formatNum(totals.actual)}
                                        {hasCompare && totals.compare && (
                                            <div className="text-[10px] text-slate-500 font-normal">
                                                Comp: {formatNum(totals.compare.actual)}
                                            </div>
                                        )}
                                    </td>
                                    <td className={`py-3.5 px-4 text-right font-bold ${totals.gap >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                        {formatNum(totals.gap)}
                                    </td>
                                    <td className="py-3.5 px-4 text-right">
                                        {totals.percent !== null && totals.percent !== undefined ? (
                                            <span className={`inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-bold ${
                                                totals.percent >= 0
                                                    ? 'bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                    : 'bg-rose-100/80 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'
                                            }`}>
                                                {totals.percent >= 0 ? '↑' : '↓'} {Math.abs(totals.percent)}%
                                            </span>
                                        ) : (
                                            <span className="text-slate-400">—</span>
                                        )}
                                    </td>
                                    {hasCompare && (
                                        <td className="py-3.5 px-4 text-right bg-indigo-50/40 dark:bg-indigo-950/30">
                                            {totals.compare?.actual_change_pct !== null && totals.compare?.actual_change_pct !== undefined ? (
                                                <div className="flex flex-col items-end">
                                                    <span className={`inline-flex items-center gap-0.5 text-xs font-black ${
                                                        totals.compare.actual_change_pct >= 0 ? 'text-emerald-600' : 'text-rose-600'
                                                    }`}>
                                                        {totals.compare.actual_change_pct >= 0 ? <TrendingUp className="w-3.5 h-3.5" /> : <TrendingDown className="w-3.5 h-3.5" />}
                                                        {totals.compare.actual_change_pct >= 0 ? `+${totals.compare.actual_change_pct}%` : `${totals.compare.actual_change_pct}%`}
                                                    </span>
                                                    <span className="text-[9px] text-slate-400 uppercase tracking-tighter">Actual Shift</span>
                                                </div>
                                            ) : (
                                                <span className="text-slate-400">—</span>
                                            )}
                                        </td>
                                    )}
                                </tr>

                                {/* Branch Rows */}
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={hasCompare ? 6 : 5} className="py-8 text-center text-slate-400 italic">
                                            No targets or sale records found for the selected period.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((row) => {
                                        const gapIsPositive = (row.gap || 0) >= 0;
                                        const comp = row.compare;

                                        return (
                                            <tr key={row.branch_id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                                {/* Branch Name */}
                                                <td className="py-3 px-4 font-semibold text-slate-900 dark:text-slate-200">
                                                    {row.branch_name}
                                                </td>

                                                {/* Target */}
                                                <td className="py-3 px-4 text-right text-slate-700 dark:text-slate-300">
                                                    <span>{formatNum(row.target)}</span>
                                                    {hasCompare && comp && (
                                                        <div className="text-[10px] text-slate-400">
                                                            Comp: {formatNum(comp.target)}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Actual */}
                                                <td className="py-3 px-4 text-right font-semibold text-slate-900 dark:text-white">
                                                    <span>{formatNum(row.actual)}</span>
                                                    {hasCompare && comp && (
                                                        <div className="text-[10px] text-slate-400">
                                                            Comp: {formatNum(comp.actual)}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Gap */}
                                                <td className={`py-3 px-4 text-right font-bold ${
                                                    gapIsPositive ? 'text-emerald-600' : 'text-rose-600'
                                                }`}>
                                                    <span>{formatNum(row.gap)}</span>
                                                    {hasCompare && comp && (
                                                        <div className="text-[10px] font-medium text-slate-400">
                                                            Comp: {formatNum(comp.gap)}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Shortfall Percentage */}
                                                <td className="py-3 px-4 text-right">
                                                    {row.percent !== null && row.percent !== undefined ? (
                                                        <div className="flex flex-col items-end">
                                                            <span className={`inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                                                row.percent >= 0
                                                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                                                            }`}>
                                                                {row.percent >= 0 ? '↑' : '↓'} {Math.abs(row.percent)}%
                                                            </span>
                                                            {hasCompare && comp && comp.percent !== null && (
                                                                <span className="text-[10px] text-slate-400 mt-0.5">
                                                                    Comp: {comp.percent >= 0 ? '↑' : '↓'} {Math.abs(comp.percent)}%
                                                                </span>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </td>

                                                {/* Compare Column (Two-period comparison) */}
                                                {hasCompare && (
                                                    <td className="py-3 px-4 text-right bg-indigo-50/20 dark:bg-indigo-950/15">
                                                        {comp?.actual_change_pct !== null && comp?.actual_change_pct !== undefined ? (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <span className={`inline-flex items-center gap-0.5 text-xs font-bold ${
                                                                    comp.actual_change_pct >= 0 ? 'text-emerald-600' : 'text-rose-600'
                                                                }`}>
                                                                    {comp.actual_change_pct >= 0 ? (
                                                                        <ArrowUpRight className="w-3.5 h-3.5" />
                                                                    ) : (
                                                                        <ArrowDownRight className="w-3.5 h-3.5" />
                                                                    )}
                                                                    {comp.actual_change_pct >= 0 ? `+${comp.actual_change_pct}%` : `${comp.actual_change_pct}%`}
                                                                </span>
                                                            </div>
                                                        ) : (
                                                            <span className="text-slate-400 text-xs">—</span>
                                                        )}
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
