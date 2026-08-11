import React, { useState, useEffect, useRef, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import axios from 'axios';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

export default function SaleKpi({ branches = [], departments = [], defaultFrom, defaultTo }) {
    const [startDate, setStartDate] = useState(defaultFrom);
    const [endDate, setEndDate] = useState(defaultTo);
    const [selectedBranches, setSelectedBranches] = useState([]);
    const [viewType, setViewType] = useState('daily'); // 'daily' or 'monthly'
    const [activeMetric, setActiveMetric] = useState('weight'); // 'weight', 'quantity', 'customer'
    
    // API Data
    const [dashboardData, setDashboardData] = useState(null);
    const [loading, setLoading] = useState(true);

    // Branch Dropdown states
    const [branchDropdownOpen, setBranchDropdownOpen] = useState(false);
    const branchDropdownRef = useRef(null);

    // Click outside handler for branch dropdown
    useEffect(() => {
        const handleOutsideClick = (e) => {
            if (branchDropdownRef.current && !branchDropdownRef.current.contains(e.target)) {
                setBranchDropdownOpen(false);
            }
        };
        window.addEventListener('click', handleOutsideClick);
        return () => window.removeEventListener('click', handleOutsideClick);
    }, []);

    // Rewards Table sorting
    const [selectedRewardsColumn, setSelectedRewardsColumn] = useState('pcs_ratio');

    // Line Chart detail modal
    const [showLineDetailModal, setShowLineDetailModal] = useState(false);
    const [lineDetailLabel, setLineDetailLabel] = useState('');
    const [activePromoteActions, setActivePromoteActions] = useState([]);

    // Promote Actions List Modal
    const [showPromoteActionsListModal, setShowPromoteActionsListModal] = useState(false);

    // Chart refs
    const gramChartRef = useRef(null);
    const pcsChartRef = useRef(null);
    const lineChartRef = useRef(null);

    // Flatpickr ref
    const dateRangeInputRef = useRef(null);

    // Fetch dashboard data
    const fetchDashboardData = () => {
        setLoading(true);
        axios.get('/kpi/sale-kpi/data', {
            params: {
                start_date: startDate,
                end_date: endDate,
                branch_ids: selectedBranches.join(','),
                view_type: viewType
            }
        })
        .then((res) => {
            setDashboardData(res.data);
        })
        .catch((err) => {
            console.error('Error fetching dashboard data:', err);
        })
        .finally(() => {
            setLoading(false);
        });
    };

    // Refetch when filters change
    useEffect(() => {
        fetchDashboardData();
    }, [startDate, endDate, selectedBranches, viewType]);

    // Initialize Flatpickr
    useEffect(() => {
        if (!dateRangeInputRef.current) return;
        const fp = flatpickr(dateRangeInputRef.current, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [startDate, endDate],
            onChange: (selectedDates) => {
                if (selectedDates.length === 2) {
                    const [start, end] = selectedDates;
                    const startStr = start.getFullYear() + '-' + String(start.getMonth() + 1).padStart(2, '0') + '-' + String(start.getDate()).padStart(2, '0');
                    const endStr = end.getFullYear() + '-' + String(end.getMonth() + 1).padStart(2, '0') + '-' + String(end.getDate()).padStart(2, '0');
                    setStartDate(startStr);
                    setEndDate(endStr);
                }
            }
        });
        return () => fp.destroy();
    }, []);

    // Render Column Charts
    useEffect(() => {
        if (!dashboardData || loading) return;

        const ApexCharts = window.ApexCharts;
        if (!ApexCharts) return;

        // 1. Gram Column Chart
        const gramData = dashboardData.gram_chart || [];
        const gramOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false },
                background: 'transparent'
            },
            title: {
                text: 'Actual vs Target (Grams)',
                align: 'left',
                style: { fontSize: '16px', fontWeight: 'bold', color: document.documentElement.classList.contains('dark') ? '#FAF9F6' : '#1E293B' }
            },
            colors: ['#FEF08A', '#94A3B8'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 6
                }
            },
            dataLabels: { enabled: false },
            series: [
                { name: 'Actual Sale (g)', data: gramData.map(d => d.actual) },
                { name: 'Target (g)', data: gramData.map(d => d.target) }
            ],
            xaxis: {
                categories: gramData.map(d => d.branch_name),
                labels: { style: { colors: '#64748B' } }
            },
            yaxis: {
                title: { text: 'Grams (g)' }
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} g`
                }
            },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };

        if (gramChartRef.current) {
            gramChartRef.current.innerHTML = '';
            const chart = new ApexCharts(gramChartRef.current, gramOptions);
            chart.render();
        }

        // 2. Pcs Column Chart
        const pcsData = dashboardData.pcs_chart || [];
        const pcsOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false },
                background: 'transparent'
            },
            title: {
                text: 'Actual vs Target (Pieces)',
                align: 'left',
                style: { fontSize: '16px', fontWeight: 'bold', color: document.documentElement.classList.contains('dark') ? '#FAF9F6' : '#1E293B' }
            },
            colors: ['#99F6E4', '#94A3B8'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 6
                }
            },
            dataLabels: { enabled: false },
            series: [
                { name: 'Actual Sale (pcs)', data: pcsData.map(d => d.actual) },
                { name: 'Target (pcs)', data: pcsData.map(d => d.target) }
            ],
            xaxis: {
                categories: pcsData.map(d => d.branch_name),
                labels: { style: { colors: '#64748B' } }
            },
            yaxis: {
                title: { text: 'Pieces' }
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} pcs`
                }
            },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };

        if (pcsChartRef.current) {
            pcsChartRef.current.innerHTML = '';
            const chart = new ApexCharts(pcsChartRef.current, pcsOptions);
            chart.render();
        }

    }, [dashboardData, loading]);

    // Render Line Chart
    useEffect(() => {
        if (!dashboardData || loading) return;

        const ApexCharts = window.ApexCharts;
        if (!ApexCharts) return;

        const lc = dashboardData.line_chart || {};
        const labels = lc.labels || [];
        const overlaps = lc.overlap_counts || [];
        
        // Select active line values
        let rawValues = [];
        if (activeMetric === 'weight') rawValues = lc.weight || [];
        else if (activeMetric === 'quantity') rawValues = lc.quantity || [];
        else rawValues = lc.customer || [];

        // Build segment series
        const s0 = Array(rawValues.length).fill(null); // No PA (gray, dotted)
        const s1 = Array(rawValues.length).fill(null); // 1 PA (green)
        const s2 = Array(rawValues.length).fill(null); // 2 PA (blue)
        const s3 = Array(rawValues.length).fill(null); // 3 PA (yellow)
        const s4 = Array(rawValues.length).fill(null); // >3 PA (red)

        for (let i = 0; i < rawValues.length; i++) {
            const val = rawValues[i];
            const count = overlaps[i];

            if (count === 0) s0[i] = val;
            else if (count === 1) s1[i] = val;
            else if (count === 2) s2[i] = val;
            else if (count === 3) s3[i] = val;
            else s4[i] = val;

            // Connect boundary segments
            if (i > 0) {
                const prevCount = overlaps[i - 1];
                if (prevCount !== count) {
                    if (prevCount === 0) s0[i] = val;
                    else if (prevCount === 1) s1[i] = val;
                    else if (prevCount === 2) s2[i] = val;
                    else if (prevCount === 3) s3[i] = val;
                    else s4[i] = val;
                }
            }
        }

        const lineOptions = {
            chart: {
                type: 'line',
                height: 400,
                toolbar: { show: false },
                events: {
                    markerClick: (event, chartContext, { dataPointIndex }) => {
                        const label = labels[dataPointIndex];
                        const details = lc.overlap_details?.[label] || [];
                        setLineDetailLabel(label);
                        setActivePromoteActions(details);
                        setShowLineDetailModal(true);
                    }
                }
            },
            series: [
                { name: 'No Campaign (Gray Dotted)', data: s0 },
                { name: '1 Promote Action (Green)', data: s1 },
                { name: '2 Promote Actions (Blue)', data: s2 },
                { name: '3 Promote Actions (Yellow)', data: s3 },
                { name: '3+ Promote Actions (Red)', data: s4 }
            ],
            stroke: {
                width: 3,
                curve: 'smooth',
                dashArray: [5, 0, 0, 0, 0] // 5 for s0 (dotted), others solid
            },
            colors: ['#94A3B8', '#FEF08A', '#99F6E4', '#EAB308', '#0D9488'],
            markers: {
                size: 5,
                hover: { size: 7 }
            },
            xaxis: {
                categories: labels,
                labels: { style: { colors: '#64748B' } }
            },
            yaxis: {
                title: { text: activeMetric.toUpperCase() }
            },
            tooltip: {
                shared: true,
                intersect: false
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            }
        };

        if (lineChartRef.current) {
            lineChartRef.current.innerHTML = '';
            const chart = new ApexCharts(lineChartRef.current, lineOptions);
            chart.render();
        }

    }, [dashboardData, loading, activeMetric]);

    // Handle open global modal
    const openCreatePromoteModal = () => {
        const event = new CustomEvent('show-promote-action-modal', {
            detail: {
                branches,
                departments,
                onSuccess: (newPA) => {
                    fetchDashboardData();
                }
            }
        });
        window.dispatchEvent(event);
    };

    // Rewards Table column mapping and positioning
    const dynamicCols = useMemo(() => {
        const defaultRatioCols = [
            { key: 'pcs_ratio', label: 'Pcs Ratio' },
            { key: 'gram_ratio', label: 'Gram Ratio' },
            { key: 'pcs_per_staff', label: 'Pcs Per Staff' },
            { key: 'customer_per_staff', label: 'Customer Per Staff' }
        ];
        return [
            { key: 'branch_name', label: 'Branch Name' },
            ...defaultRatioCols.filter(c => c.key === selectedRewardsColumn),
            ...defaultRatioCols.filter(c => c.key !== selectedRewardsColumn)
        ];
    }, [selectedRewardsColumn]);

    const sortedRewardsRows = useMemo(() => {
        const rewardsRows = dashboardData?.rewards_table || [];
        return [...rewardsRows].sort((a, b) => b[selectedRewardsColumn] - a[selectedRewardsColumn]);
    }, [dashboardData, selectedRewardsColumn]);

    return (
        <AsideLayout title="Sale KPI Report Dashboard">
            <Head title="Sale KPI Report Dashboard" />

            <div className="space-y-6 text-slate-800 dark:text-slate-100">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">Sale KPI Dashboard</h1>
                        <p className="text-sm text-slate-500">Analyze actual sale grams and quantities compared with target thresholds</p>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        {/* Branch Filters - Custom Dropdown Checkbox */}
                        <div className="relative" ref={branchDropdownRef}>
                            <button
                                type="button"
                                onClick={() => setBranchDropdownOpen(!branchDropdownOpen)}
                                className="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-205 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-slate-500 transition flex items-center justify-between gap-2 min-w-44"
                            >
                                <span className="truncate">
                                    {selectedBranches.length === 0 
                                        ? 'All Branches' 
                                        : branches
                                            .filter(b => selectedBranches.includes(b.id))
                                            .map(b => b.name)
                                            .join(', ')
                                    }
                                </span>
                                <span className="text-[10px] text-slate-400">▼</span>
                            </button>

                            {branchDropdownOpen && (
                                <div className="absolute left-0 mt-1 w-56 max-h-60 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg z-50 p-2 space-y-0.5">
                                    {branches.map((b) => {
                                        const isChecked = selectedBranches.includes(b.id);
                                        return (
                                            <label
                                                key={b.id}
                                                className="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg cursor-pointer transition select-none"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={isChecked}
                                                    onChange={() => {
                                                        if (isChecked) {
                                                            setSelectedBranches(selectedBranches.filter(id => id !== b.id));
                                                        } else {
                                                            setSelectedBranches([...selectedBranches, b.id]);
                                                        }
                                                    }}
                                                    className="w-4 h-4 rounded border-slate-350 dark:border-slate-650 text-slate-900 focus:ring-slate-500 cursor-pointer"
                                                />
                                                <span className="text-xs text-slate-700 dark:text-slate-350 font-medium">
                                                    {b.name}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* Date Range Picker */}
                        <input
                                    ref={dateRangeInputRef}
                                    type="text"
                                    placeholder="Select Date Range"
                                    className="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-slate-500"
                                />

                        {/* Reset Filter Button */}
                        <button
                            onClick={() => {
                                setStartDate(defaultFrom);
                                setEndDate(defaultTo);
                                setSelectedBranches([]);
                                if (dateRangeInputRef.current && dateRangeInputRef.current._flatpickr) {
                                    dateRangeInputRef.current._flatpickr.setDate([defaultFrom, defaultTo]);
                                }
                            }}
                            className="h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-55 dark:hover:bg-slate-800 text-sm"
                        >
                            Reset
                        </button>

                        {/* View Promote Actions Trigger Button */}
                        <button
                            onClick={() => setShowPromoteActionsListModal(true)}
                            className="h-10 px-4 rounded-xl bg-[#FEF08A] hover:bg-[#FDE047] text-slate-800 text-sm font-semibold transition flex items-center gap-2 shadow-sm"
                        >
                            <span>📢</span>
                            <span>
                                Promote Actions ({dashboardData?.promote_actions ? dashboardData.promote_actions.length : 0})
                            </span>
                        </button>
                    </div>
                </div>

                {loading && (
                    <div className="flex justify-center items-center py-12">
                        <div className="w-8 h-8 border-4 border-slate-900 dark:border-slate-100 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                )}

                {/* Section 1: Column Charts */}
                {!loading && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm">
                            <div ref={gramChartRef} />
                        </div>
                        <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm">
                            <div ref={pcsChartRef} />
                        </div>
                    </div>
                )}
                {/* Section 3: Line Chart */}
                {!loading && (
                    <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Trend & Campaign Overlap Chart</h3>
                                <p className="text-xs text-slate-500">Tracks performance overlap during active campaign periods (Dotted: No PA, Yellow: 1 PA, Teal: 2 PA, Dark Yellow: 3 PA, Dark Teal: 3+ PA)</p>
                            </div>
 
                            {/* Controls */}
                            <div className="flex items-center gap-3 self-end">
                                {/* Metric Toggle */}
                                <div className="flex bg-slate-100 dark:bg-slate-850 p-1 rounded-xl">
                                    {['weight', 'quantity', 'customer'].map((m) => (
                                        <button
                                            key={m}
                                            onClick={() => setActiveMetric(m)}
                                            className={`px-3 py-1 text-xs font-semibold rounded-lg capitalize transition ${
                                                activeMetric === m 
                                                    ? 'bg-[#FEF08A] shadow text-slate-900 font-bold' 
                                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-350'
                                            }`}
                                        >
                                            {m}
                                        </button>
                                    ))}
                                </div>
 
                                {/* Date View Mode (Daily vs Monthly) */}
                                <div className="flex bg-slate-100 dark:bg-slate-850 p-1 rounded-xl">
                                    {['daily', 'monthly'].map((v) => (
                                        <button
                                            key={v}
                                            onClick={() => setViewType(v)}
                                            className={`px-3 py-1 text-xs font-semibold rounded-lg capitalize transition ${
                                                viewType === v 
                                                    ? 'bg-[#FEF08A] shadow text-slate-900 font-bold' 
                                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-350'
                                            }`}
                                        >
                                            {v}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
 
                        <div ref={lineChartRef} />
                    </div>
                )}                {/* Section 4: Rewards Metal Table */}
                {!loading && (
                    <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-4">
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Rewards Metal Table</h3>
                            <p className="text-xs text-slate-500">Sorted automatically by selected KPI ratio column</p>
                        </div>
 
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-350 text-xs uppercase font-semibold">
                                    <tr>
                                        {dynamicCols.map((col) => {
                                            const isSortable = col.key !== 'branch_name';
                                            return (
                                                <th key={col.key} className="px-6 py-3">
                                                    <div className="flex items-center gap-2">
                                                        {isSortable && (
                                                            <input
                                                                type="radio"
                                                                name="rewards_sort"
                                                                checked={selectedRewardsColumn === col.key}
                                                                onChange={() => setSelectedRewardsColumn(col.key)}
                                                                className="w-4 h-4 text-slate-900 border-slate-300 dark:border-slate-650 focus:ring-slate-500 cursor-pointer"
                                                            />
                                                        )}
                                                        <span className={isSortable ? 'cursor-pointer' : ''} onClick={() => isSortable && setSelectedRewardsColumn(col.key)}>
                                                            {col.label}
                                                        </span>
                                                    </div>
                                                </th>
                                            );
                                        })}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {sortedRewardsRows.length === 0 ? (
                                        <tr>
                                            <td colSpan={dynamicCols.length} className="px-6 py-8 text-center text-slate-400">
                                                No rewards data available.
                                            </td>
                                        </tr>
                                    ) : (
                                        sortedRewardsRows.map((row) => (
                                            <tr key={row.branch_id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                {dynamicCols.map((col) => {
                                                    let val = row[col.key];
                                                    const isHighlighted = col.key === selectedRewardsColumn;
                                                    
                                                    return (
                                                        <td 
                                                            key={col.key} 
                                                            className={`px-6 py-4 ${
                                                                col.key === 'branch_name' 
                                                                    ? 'font-medium text-slate-900 dark:text-slate-200' 
                                                                    : 'font-semibold'
                                                            } ${
                                                                isHighlighted ? 'bg-indigo-50/40 dark:bg-indigo-950/10 text-indigo-600 dark:text-indigo-400' : ''
                                                            }`}
                                                        >
                                                            {col.key === 'branch_name' ? val : val}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>

            {/* Line Chart marker details Modal */}
            {showLineDetailModal && (
                <div 
                    style={{ position: 'fixed', inset: 0, zIndex: 9500, backgroundColor: 'rgba(15,23,42,0.6)' }}
                    className="flex items-center justify-center p-4 backdrop-blur-sm"
                    onClick={() => setShowLineDetailModal(false)}
                >
                    <div 
                        className="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-xl p-6 border border-slate-200 dark:border-slate-800"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex justify-between items-center mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
                                Promote Actions on {lineDetailLabel}
                            </h3>
                            <button 
                                onClick={() => setShowLineDetailModal(false)} 
                                className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                            >
                                ✕
                            </button>
                        </div>
                        
                        <div className="max-h-60 overflow-y-auto space-y-3 pr-1">
                            {activePromoteActions.length === 0 ? (
                                <p className="text-xs text-slate-500 text-center py-4">
                                    No active promote actions on this date.
                                </p>
                            ) : (
                                activePromoteActions.map((pa) => (
                                    <div 
                                        key={pa.id} 
                                        className="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-100 dark:border-slate-800"
                                    >
                                        <h4 className="font-semibold text-xs text-slate-800 dark:text-slate-250">
                                            {pa.name}
                                        </h4>
                                        <div className="text-[10px] text-slate-500 mt-2 space-y-1">
                                            <div>Duration: {pa.start_at} to {pa.end_at}</div>
                                            <div>Department: {pa.department}</div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        <div className="flex justify-end pt-4 mt-2 border-t border-slate-100 dark:border-slate-800">
                            <button
                                onClick={() => setShowLineDetailModal(false)}
                                className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-850 text-slate-700 dark:text-slate-350 text-xs font-semibold hover:opacity-90"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Promote Actions Table Modal */}
            {showPromoteActionsListModal && (
                <div 
                    style={{ position: 'fixed', inset: 0, zIndex: 8500, backgroundColor: 'rgba(15,23,42,0.6)' }}
                    className="flex items-center justify-center p-4 backdrop-blur-sm"
                    onClick={() => setShowPromoteActionsListModal(false)}
                >
                    <div 
                        className="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-3xl shadow-xl p-6 border border-slate-200 dark:border-slate-800"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex justify-between items-center mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                                    Promote Actions List
                                </h3>
                                <p className="text-xs text-slate-500">Ongoing active campaigns in date scope</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <button
                                    onClick={() => {
                                        setShowPromoteActionsListModal(false);
                                        openCreatePromoteModal();
                                    }}
                                    className="h-9 px-4 bg-[#FEF08A] hover:bg-[#FDE047] text-slate-800 rounded-xl text-xs font-semibold shadow-sm transition"
                                >
                                    + Create Promotion
                                </button>
                                <button 
                                    onClick={() => setShowPromoteActionsListModal(false)} 
                                    className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        
                        <div className="overflow-x-auto max-h-[55vh] overflow-y-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="bg-slate-55 dark:bg-slate-800 text-slate-700 dark:text-slate-350 text-xs uppercase font-semibold">
                                    <tr>
                                        <th className="px-6 py-3">Promotion Name</th>
                                        <th className="px-6 py-3">Target Branch</th>
                                        <th className="px-6 py-3">Action By</th>
                                        <th className="px-6 py-3">Start Date</th>
                                        <th className="px-6 py-3">End Date</th>
                                        <th className="px-6 py-3">References</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {(dashboardData?.promote_actions || []).length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-8 text-center text-slate-400">
                                                No promote actions registered in this period.
                                            </td>
                                        </tr>
                                    ) : (
                                        (dashboardData.promote_actions).map((pa) => (
                                            <tr key={pa.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td className="px-6 py-4 font-medium text-slate-900 dark:text-slate-200">{pa.name}</td>
                                                <td className="px-6 py-4">{pa.target_branch}</td>
                                                <td className="px-6 py-4">{pa.action_by_dept}</td>
                                                <td className="px-6 py-4">{pa.start_at}</td>
                                                <td className="px-6 py-4">{pa.end_at}</td>
                                                <td className="px-6 py-4 text-xs font-mono">
                                                    {pa.reference ? JSON.stringify(pa.reference) : 'None'}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="flex justify-end pt-4 mt-4 border-t border-slate-100 dark:border-slate-800">
                            <button
                                onClick={() => setShowPromoteActionsListModal(false)}
                                className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-850 text-slate-700 dark:text-slate-350 text-xs font-semibold hover:opacity-90"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AsideLayout>
    );
}
