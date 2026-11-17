import React, { useState, useEffect, useRef, useMemo } from 'react';
import axios from 'axios';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Tooltip, Menu, MenuItem } from '@mui/material';
import {
    Table,
    Calendar,
    RefreshCw,
    AlertCircle,
    Layers,
    ArrowUpRight,
    ArrowDownRight,
    Minus,
    Plus,
    Pencil,
    Download,
    Info,
} from 'lucide-react';
import CanonicalTemplateModal from './CanonicalTemplateModal';

const sumMatchedTypes = (label, matchedTypes, branches, hasCompare) => {
    const values = {};
    const compare_values = {};
    let total = 0;
    let compare_total = 0;

    branches.forEach((b) => {
        let sum = 0;
        let compSum = 0;
        matchedTypes.forEach((t) => {
            sum += Number(t.values?.[b] || 0);
            if (hasCompare) {
                compSum += Number(t.compare_values?.[b] || 0);
            }
        });
        values[b] = Number(sum.toFixed(2));
        total += sum;
        if (hasCompare) {
            compare_values[b] = Number(compSum.toFixed(2));
            compare_total += compSum;
        }
    });

    return {
        id: `canonical-${label}`,
        name: label,
        isCanonical: true,
        sourceCount: matchedTypes.length,
        sourceTypes: matchedTypes.map((t) => ({
            id: t.id,
            name: t.name,
            total: t.total,
        })),
        values,
        total: Number(total.toFixed(2)),
        ...(hasCompare
            ? {
                  compare_values,
                  compare_total: Number(compare_total.toFixed(2)),
              }
            : {}),
    };
};

const formatNum = (val) => {
    const num = Number(val || 0);
    if (Number.isInteger(num)) {
        return num.toLocaleString('en-US');
    }
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const renderDeltaBadge = (current, previous) => {
    const curr = Number(current || 0);
    const prev = Number(previous || 0);
    if (prev === 0 && curr === 0) {
        return (
            <span className="inline-flex items-center gap-0.5 text-[10px] font-medium text-slate-400">
                <Minus className="w-2.5 h-2.5" /> 0%
            </span>
        );
    }
    const pct = prev === 0 ? (curr > 0 ? 100 : 0) : ((curr - prev) / prev) * 100;
    const isUp = pct > 0;
    const isDown = pct < 0;

    return (
        <span
            className={`inline-flex items-center gap-0.5 text-[10px] font-semibold ${
                isUp
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : isDown
                    ? 'text-rose-600 dark:text-rose-400'
                    : 'text-slate-400'
            }`}
        >
            {isUp && <ArrowUpRight className="w-2.5 h-2.5" />}
            {isDown && <ArrowDownRight className="w-2.5 h-2.5" />}
            {isUp ? '+' : ''}
            {pct.toFixed(1)}%
        </span>
    );
};

export default function DailyReportTypesSummaryTable({
    defaultFrom,
    defaultTo,
    selectedBranches = [],
}) {
    const now = new Date();
    const monthStart = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
    const monthEndDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const monthEnd = `${monthEndDate.getFullYear()}-${String(monthEndDate.getMonth() + 1).padStart(2, '0')}-${String(monthEndDate.getDate()).padStart(2, '0')}`;

    const [primaryFrom, setPrimaryFrom] = useState(defaultFrom || monthStart);
    const [primaryTo, setPrimaryTo] = useState(defaultTo || monthEnd);
    const [enableCompare, setEnableCompare] = useState(false);
    const [compareFrom, setCompareFrom] = useState('');
    const [compareTo, setCompareTo] = useState('');
    const [canonicalMode, setCanonicalMode] = useState('all');
    const [templates, setTemplates] = useState([]);
    const [templateModalOpen, setTemplateModalOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState(null);
    const [exportAnchorEl, setExportAnchorEl] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [reportData, setReportData] = useState(null);

    const primaryRangeInputRef = useRef(null);
    const compareRangeInputRef = useRef(null);
    const primaryPickerRef = useRef(null);
    const comparePickerRef = useRef(null);

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
            },
        });

        return () => {
            if (primaryPickerRef.current) {
                primaryPickerRef.current.destroy();
            }
        };
    }, []);

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
            const pStart = new Date(primaryFrom);
            const pEnd = new Date(primaryTo);
            const defaultCompStart =
                compareFrom ||
                new Date(pStart.setMonth(pStart.getMonth() - 1)).toISOString().split('T')[0];
            const defaultCompEnd =
                compareTo ||
                new Date(pEnd.setMonth(pEnd.getMonth() - 1)).toISOString().split('T')[0];

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
                },
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
            };
            if (enableCompare && compareFrom && compareTo) {
                params.compare_start_date = compareFrom;
                params.compare_end_date = compareTo;
            }
            if (selectedBranches && selectedBranches.length > 0) {
                params.branch_ids = selectedBranches.join(',');
            }

            const res = await axios.get('/sale-kpi/daily-report-types-summary', { params });
            setReportData(res.data);
        } catch (err) {
            console.error('Error fetching daily report types summary:', err);
            setError(err.response?.data?.message || 'Failed to load daily report types summary');
        } finally {
            setLoading(false);
        }
    };

    const fetchTemplates = async () => {
        try {
            const res = await axios.get('/sale-kpi/canonical-templates');
            setTemplates(res.data?.templates || []);
        } catch (err) {
            console.error('Error fetching canonical templates:', err);
        }
    };

    useEffect(() => {
        fetchTemplates();
    }, []);

    useEffect(() => {
        fetchData();
    }, [primaryFrom, primaryTo, compareFrom, compareTo, enableCompare, JSON.stringify(selectedBranches)]);

    const branches = reportData?.branches || [];
    const rawTypes = reportData?.types || [];
    const hasCompare = Boolean(reportData?.has_compare);

    const canonicalRows = useMemo(() => {
        if (!rawTypes.length) return [];

        const activeTemplate = templates.find((t) => t.id === canonicalMode);
        if (activeTemplate) {
            return (activeTemplate.rows || []).map((row) => {
                const typeIds = Array.isArray(row.type_ids) ? row.type_ids.map(Number) : [];
                const matched = rawTypes.filter((t) => typeIds.includes(Number(t.id)));
                return sumMatchedTypes(
                    row.label || 'Untitled Row',
                    matched,
                    branches,
                    hasCompare
                );
            });
        }

        return rawTypes;
    }, [rawTypes, canonicalMode, templates, branches, hasCompare]);

    const escapeCsvCell = (val) => {
        const str = String(val ?? '');
        if (str.includes(',') || str.includes('"') || str.includes('\n')) {
            return `"${str.replace(/"/g, '""')}"`;
        }
        return str;
    };

    const escapeHtml = (val) =>
        String(val ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

    const getExportFilename = (ext) => {
        const activeTpl = templates.find((t) => t.id === canonicalMode);
        const modeSlug = activeTpl
            ? activeTpl.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'template'
            : 'all-types';
        return `daily-report-summary-${modeSlug}-${primaryFrom}-to-${primaryTo}.${ext}`;
    };

    const triggerBlobDownload = (blob, filename) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    const handleDownloadCsv = () => {
        setExportAnchorEl(null);
        const headers = ['Type Name'];
        branches.forEach((b) => {
            headers.push(b);
            if (hasCompare) headers.push(`${b} (Prev)`);
        });
        headers.push('Total');
        if (hasCompare) headers.push('Total (Prev)');

        const lines = [headers.map(escapeCsvCell).join(',')];
        canonicalRows.forEach((row) => {
            const cells = [row.name];
            branches.forEach((b) => {
                cells.push(row.values?.[b] ?? 0);
                if (hasCompare) cells.push(row.compare_values?.[b] ?? 0);
            });
            cells.push(row.total ?? 0);
            if (hasCompare) cells.push(row.compare_total ?? 0);
            lines.push(cells.map(escapeCsvCell).join(','));
        });

        const csvContent = '\uFEFF' + lines.join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        triggerBlobDownload(blob, getExportFilename('csv'));
    };

    const handleDownloadExcel = () => {
        setExportAnchorEl(null);
        const headers = ['Type Name'];
        branches.forEach((b) => {
            headers.push(b);
            if (hasCompare) headers.push(`${b} (Prev)`);
        });
        headers.push('Total');
        if (hasCompare) headers.push('Total (Prev)');

        const thead = `<tr>${headers.map((h) => `<th style="background:#f1f5f9;font-weight:bold;border:1px solid #cbd5e1;padding:6px;">${escapeHtml(h)}</th>`).join('')}</tr>`;
        const tbody = canonicalRows
            .map((row) => {
                const cells = [`<td style="border:1px solid #cbd5e1;padding:6px;">${escapeHtml(row.name)}</td>`];
                branches.forEach((b) => {
                    cells.push(`<td style="border:1px solid #cbd5e1;padding:6px;text-align:right;">${escapeHtml(row.values?.[b] ?? 0)}</td>`);
                    if (hasCompare) {
                        cells.push(`<td style="border:1px solid #cbd5e1;padding:6px;text-align:right;">${escapeHtml(row.compare_values?.[b] ?? 0)}</td>`);
                    }
                });
                cells.push(`<td style="border:1px solid #cbd5e1;padding:6px;text-align:right;font-weight:bold;">${escapeHtml(row.total ?? 0)}</td>`);
                if (hasCompare) {
                    cells.push(`<td style="border:1px solid #cbd5e1;padding:6px;text-align:right;">${escapeHtml(row.compare_total ?? 0)}</td>`);
                }
                return `<tr>${cells.join('')}</tr>`;
            })
            .join('');

        const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8" /></head><body><table><thead>${thead}</thead><tbody>${tbody}</tbody></table></body></html>`;
        const blob = new Blob(['\uFEFF' + html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        triggerBlobDownload(blob, getExportFilename('xls'));
    };

    return (
        <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-5">
            {/* Header & Controls */}
            <div className="flex flex-col xl:flex-row xl:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div className="flex items-start gap-3">
                    <div className="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                        <Table className="w-5 h-5" />
                    </div>
                    <div>
                        <h3 className="font-bold text-slate-900 dark:text-slate-100 text-base">
                            Daily Report Summary by Type
                        </h3>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Report at - {reportData?.period?.from || primaryFrom} &amp;{' '}
                            {reportData?.period?.to || primaryTo}
                            {hasCompare && reportData?.compare_period
                                ? ` · Comparing vs ${reportData.compare_period.from} & ${reportData.compare_period.to}`
                                : ''}
                        </p>
                    </div>
                </div>

                {/* Date Range, Compare Toggle, Refresh */}
                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700">
                        <Calendar className="w-4 h-4 text-slate-400 shrink-0" />
                        <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">Range:</span>
                        <input
                            ref={primaryRangeInputRef}
                            type="text"
                            readOnly
                            placeholder="Select date range"
                            className="bg-transparent text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none w-[170px] cursor-pointer"
                        />
                    </div>

                    <button
                        type="button"
                        onClick={() => setEnableCompare((prev) => !prev)}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all ${
                            enableCompare
                                ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 shadow-2xs'
                                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'
                        }`}
                    >
                        <Layers className="w-3.5 h-3.5" />
                        <span>Compare</span>
                    </button>

                    {enableCompare && (
                        <div className="flex items-center gap-2 bg-indigo-50/50 dark:bg-indigo-950/30 px-3 py-1.5 rounded-xl border border-indigo-200/80 dark:border-indigo-800">
                            <Calendar className="w-4 h-4 text-indigo-500 shrink-0" />
                            <span className="text-xs font-semibold text-indigo-600 dark:text-indigo-300">Prev:</span>
                            <input
                                ref={compareRangeInputRef}
                                type="text"
                                readOnly
                                placeholder="Compare range"
                                className="bg-transparent text-xs font-semibold text-indigo-900 dark:text-indigo-100 outline-none w-[170px] cursor-pointer"
                            />
                        </div>
                    )}

                    <button
                        type="button"
                        onClick={(e) => setExportAnchorEl(e.currentTarget)}
                        disabled={loading || canonicalRows.length === 0}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50"
                        title="Download as CSV or Excel"
                    >
                        <Download className="w-3.5 h-3.5" />
                        <span>Export</span>
                    </button>

                    <Menu
                        anchorEl={exportAnchorEl}
                        open={Boolean(exportAnchorEl)}
                        onClose={() => setExportAnchorEl(null)}
                    >
                        <MenuItem onClick={handleDownloadCsv} sx={{ fontSize: '0.8rem' }}>
                            Download CSV (.csv)
                        </MenuItem>
                        <MenuItem onClick={handleDownloadExcel} sx={{ fontSize: '0.8rem' }}>
                            Download Excel (.xls)
                        </MenuItem>
                    </Menu>

                    <button
                        type="button"
                        onClick={fetchData}
                        disabled={loading}
                        className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                        title="Refresh report"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Canonical Templates Bar (Horizontally Scrollable) */}
            <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2 overflow-x-auto pb-1.5 min-w-0 flex-1 whitespace-nowrap">
                    <button
                        type="button"
                        onClick={() => {
                            setEditingTemplate(null);
                            setTemplateModalOpen(true);
                        }}
                        className="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold border border-dashed border-indigo-400 dark:border-indigo-600 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition"
                    >
                        <Plus className="w-3.5 h-3.5" />
                        <span>New Template</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setCanonicalMode('all')}
                        className={`shrink-0 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border ${
                            canonicalMode === 'all'
                                ? 'bg-[#FEF08A] border-amber-300 text-slate-900 shadow-2xs'
                                : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                        }`}
                    >
                        All Types
                    </button>

                    {templates.map((tpl) => (
                        <div
                            key={tpl.id}
                            className={`shrink-0 inline-flex items-center rounded-xl border transition-all ${
                                canonicalMode === tpl.id
                                    ? 'bg-indigo-100 dark:bg-indigo-900/50 border-indigo-400 dark:border-indigo-600 text-indigo-950 dark:text-indigo-100 shadow-2xs'
                                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700'
                            }`}
                        >
                            <button
                                type="button"
                                onClick={() => setCanonicalMode(tpl.id)}
                                className="px-3 py-1.5 text-xs font-semibold"
                            >
                                {tpl.name}
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setEditingTemplate(tpl);
                                    setTemplateModalOpen(true);
                                }}
                                className="pr-2.5 pl-1 py-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors"
                                title={`Edit "${tpl.name}"`}
                            >
                                <Pencil className="w-3 h-3" />
                            </button>
                        </div>
                    ))}
                </div>
            </div>

            {/* Table Area */}
            <div className="relative min-h-[200px]">
                {loading && (
                    <div className="absolute inset-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center z-10 rounded-2xl">
                        <div className="flex items-center gap-2.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <RefreshCw className="w-4 h-4 animate-spin text-indigo-600 dark:text-indigo-400" />
                            Loading report data...
                        </div>
                    </div>
                )}

                {error ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <AlertCircle className="w-8 h-8 text-rose-500 mb-2" />
                        <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            Unable to load report
                        </p>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm">{error}</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-2xl border border-slate-150 dark:border-slate-800">
                        <table className="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr className="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200/80 dark:border-slate-800 text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                                    <th className="py-3 px-4 min-w-[210px] sticky left-0 bg-slate-50 dark:bg-slate-800/95 z-10">
                                        Type Name
                                    </th>
                                    {branches.map((branchName) => (
                                        <th key={branchName} className="py-3 px-3 text-right whitespace-nowrap">
                                            {branchName}
                                        </th>
                                    ))}
                                    <th className="py-3 px-4 text-right whitespace-nowrap bg-amber-50/60 dark:bg-amber-950/20 text-amber-900 dark:text-amber-300">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                {canonicalRows.map((row) => {
                                    const isCanonical = Boolean(row.isCanonical);
                                    const sourceList = row.sourceTypes || [];

                                    const rowElement = (
                                        <tr
                                            key={row.id}
                                            className={
                                                isCanonical
                                                    ? 'bg-amber-50/70 dark:bg-amber-950/30 font-bold text-slate-900 dark:text-amber-100 border-b border-amber-200/80 dark:border-amber-800/60 hover:bg-amber-100/60 dark:hover:bg-amber-950/50 transition-colors'
                                                    : 'odd:bg-white even:bg-slate-50/60 dark:odd:bg-slate-900 dark:even:bg-slate-800/40 hover:bg-slate-100/70 dark:hover:bg-slate-800/70 transition-colors'
                                            }
                                        >
                                            <td
                                                className={`py-2.5 px-4 sticky left-0 z-10 ${
                                                    isCanonical
                                                        ? 'bg-amber-50 dark:bg-amber-950/90'
                                                        : 'bg-inherit'
                                                }`}
                                            >
                                                <div className="flex items-center gap-1.5">
                                                    <span className={isCanonical ? 'font-bold' : 'font-medium text-slate-800 dark:text-slate-200'}>
                                                        {row.name}
                                                    </span>
                                                    {isCanonical && (
                                                        <Info className="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" />
                                                    )}
                                                </div>
                                            </td>

                                            {branches.map((b) => (
                                                <td key={b} className="py-2.5 px-3 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                                    <div>{formatNum(row.values?.[b])}</div>
                                                    {hasCompare && (
                                                        <div className="text-[10px] text-slate-400 flex items-center justify-end gap-1">
                                                            <span>{formatNum(row.compare_values?.[b])}</span>
                                                            {renderDeltaBadge(row.values?.[b], row.compare_values?.[b])}
                                                        </div>
                                                    )}
                                                </td>
                                            ))}

                                            <td
                                                className={`py-2.5 px-4 text-right tabular-nums font-bold ${
                                                    isCanonical
                                                        ? 'bg-amber-100/80 dark:bg-amber-900/40 text-amber-950 dark:text-amber-100'
                                                        : 'bg-amber-50/40 dark:bg-amber-950/10 text-slate-900 dark:text-white'
                                                }`}
                                            >
                                                <div>{formatNum(row.total)}</div>
                                                {hasCompare && (
                                                    <div className="text-[10px] font-normal flex items-center justify-end gap-1">
                                                        <span>{formatNum(row.compare_total)}</span>
                                                        {renderDeltaBadge(row.total, row.compare_total)}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    );

                                    if (!isCanonical) {
                                        return rowElement;
                                    }

                                    return (
                                        <Tooltip
                                            key={row.id}
                                            arrow
                                            placement="top-start"
                                            title={
                                                <div className="p-1 space-y-1 text-xs">
                                                    <div className="font-bold border-b border-white/20 pb-1">
                                                        Canonical Items ({sourceList.length})
                                                    </div>
                                                    {sourceList.length === 0 ? (
                                                        <div className="text-slate-300">No items selected</div>
                                                    ) : (
                                                        sourceList.map((st) => (
                                                            <div key={st.id} className="flex items-center justify-between gap-4">
                                                                <span>• {st.name}</span>
                                                                <span className="font-semibold tabular-nums">
                                                                    {formatNum(st.total)}
                                                                </span>
                                                            </div>
                                                        ))
                                                    )}
                                                </div>
                                            }
                                        >
                                            {rowElement}
                                        </Tooltip>
                                    );
                                })}

                                {!loading && canonicalRows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={branches.length + 2}
                                            className="py-10 text-center text-xs text-slate-500 dark:text-slate-400"
                                        >
                                            No daily report records found for the selected range.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <CanonicalTemplateModal
                isOpen={templateModalOpen}
                onClose={() => {
                    setTemplateModalOpen(false);
                    setEditingTemplate(null);
                }}
                template={editingTemplate}
                availableTypes={rawTypes}
                onSaved={(savedTemplate, allTemplates) => {
                    setTemplates(allTemplates || []);
                    if (savedTemplate?.id) {
                        setCanonicalMode(savedTemplate.id);
                    }
                }}
                onDeleted={(deletedId, allTemplates) => {
                    setTemplates(allTemplates || []);
                    if (canonicalMode === deletedId) {
                        setCanonicalMode('all');
                    }
                }}
            />
        </div>
    );
}
