import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import ApexCharts from 'apexcharts';
import { TrendingUp, RefreshCw, AlertCircle } from 'lucide-react';

export default function SaleComparisonChart({ defaultFrom, defaultTo, selectedBranches = [] }) {
    const [compareMode, setCompareMode] = useState('prev_period'); // prev_period | yoy | none
    const [metric, setMetric] = useState('gram'); // gram | pcs
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [data, setData] = useState(null);

    const chartRef = useRef(null);
    const chartInstanceRef = useRef(null);

    const fetchData = async () => {
        setLoading(true);
        setError(null);
        try {
            const branchIds = selectedBranches && selectedBranches.length > 0 ? selectedBranches.join(',') : '';
            const res = await axios.get('/sale-kpi/comparison-data', {
                params: {
                    start_date: defaultFrom,
                    end_date: defaultTo,
                    branch_ids: branchIds,
                    metric,
                    compare_mode: compareMode
                }
            });
            setData(res.data);
        } catch (err) {
            console.error('Error fetching comparison data:', err);
            setError(err.response?.data?.message || 'Failed to load comparison data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [defaultFrom, defaultTo, compareMode, metric, JSON.stringify(selectedBranches)]);

    const formatNum = (num) => {
        if (!num) return '0';
        return Number(num).toLocaleString('en-US', { maximumFractionDigits: 1 });
    };

    useEffect(() => {
        if (!data || !chartRef.current) return;

        if (chartInstanceRef.current) {
            chartInstanceRef.current.destroy();
        }

        const options = {
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: false, // Prevents toolbar from overlapping legend
                },
                fontFamily: 'Inter, system-ui, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 400 },
            },
            colors: ['#4F46E5', '#94A3B8'],
            stroke: {
                curve: 'smooth',
                width: [2.5, 2],
                dashArray: [0, 4]
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.25,
                    opacityTo: 0.02,
                    stops: [0, 90, 100]
                }
            },
            markers: {
                size: [3, 0],
                strokeColors: '#ffffff',
                strokeWidth: 2,
            },
            dataLabels: { enabled: false },
            series: data.series || [],
            xaxis: {
                categories: data.categories || [],
                labels: {
                    style: { colors: '#64748B', fontSize: '11px', fontWeight: 500 },
                    rotate: -45,
                    rotateAlways: false,
                },
                axisBorder: { color: '#E2E8F0' },
                axisTicks: { color: '#E2E8F0' },
            },
            yaxis: {
                labels: {
                    formatter: (val) => `${formatNum(val)}`,
                    style: { colors: '#64748B', fontSize: '11px' }
                }
            },
            tooltip: {
                theme: 'light',
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => `${formatNum(val)} ${metric === 'pcs' ? 'pcs' : 'g'}`
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                fontWeight: 500,
                labels: { colors: '#475569' },
                markers: {
                    width: 10,
                    height: 10,
                    radius: 12,
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 4,
                },
            },
            grid: {
                borderColor: '#F1F5F9',
                strokeDashArray: 3,
                padding: {
                    top: 10,
                    left: 10,
                    right: 10,
                }
            }
        };

        const chart = new ApexCharts(chartRef.current, options);
        chart.render();
        chartInstanceRef.current = chart;

        return () => {
            if (chartInstanceRef.current) {
                chartInstanceRef.current.destroy();
                chartInstanceRef.current = null;
            }
        };
    }, [data, metric]);

    const summary = data?.summary || {};
    const isGrowthPositive = (summary.growth_pct || 0) >= 0;

    return (
        <div className="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs transition-all mb-6">
            {/* Header & Controls with Untitled UI styling */}
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-xs">
                        <TrendingUp className="w-5 h-5" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold text-slate-900 text-base">Sales Period Comparison</h3>
                            <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                Trend Analysis
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Compare current performance with previous period or Year-over-Year (YoY)
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                    {/* Metric Toggle */}
                    <div className="flex items-center bg-slate-100/80 p-0.5 rounded-lg border border-slate-200/60 text-xs font-medium">
                        <button
                            type="button"
                            onClick={() => setMetric('gram')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                metric === 'gram'
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Weight (g)
                        </button>
                        <button
                            type="button"
                            onClick={() => setMetric('pcs')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                metric === 'pcs'
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Quantity (pcs)
                        </button>
                    </div>

                    {/* Mode Toggle */}
                    <div className="flex items-center bg-slate-100/80 p-0.5 rounded-lg border border-slate-200/60 text-xs font-medium">
                        <button
                            type="button"
                            onClick={() => setCompareMode('prev_period')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                compareMode === 'prev_period'
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            vs. Previous Period
                        </button>
                        <button
                            type="button"
                            onClick={() => setCompareMode('yoy')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                compareMode === 'yoy'
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            vs. YoY (Last Year)
                        </button>
                        <button
                            type="button"
                            onClick={() => setCompareMode('none')}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                compareMode === 'none'
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            None
                        </button>
                    </div>

                    {/* Refresh Button */}
                    <button
                        type="button"
                        onClick={fetchData}
                        disabled={loading}
                        className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all disabled:opacity-50"
                        title="Refresh comparison data"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Quick KPI Stat Cards */}
            {summary && compareMode !== 'none' && (
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 my-5">
                    <div className="bg-slate-50/70 border border-slate-200/70 rounded-xl p-3.5 flex flex-col justify-between">
                        <span className="text-xs font-medium text-slate-500">Current Period</span>
                        <div className="flex items-baseline gap-1.5 mt-1">
                            <span className="text-xl font-bold text-slate-800">
                                {formatNum(summary.base_total)}
                            </span>
                            <span className="text-xs text-slate-500 font-medium">{summary.metric}</span>
                        </div>
                    </div>

                    <div className="bg-slate-50/70 border border-slate-200/70 rounded-xl p-3.5 flex flex-col justify-between">
                        <span className="text-xs font-medium text-slate-500">Comparison Period</span>
                        <div className="flex items-baseline gap-1.5 mt-1">
                            <span className="text-xl font-bold text-slate-600">
                                {formatNum(summary.compare_total)}
                            </span>
                            <span className="text-xs text-slate-500 font-medium">{summary.metric}</span>
                        </div>
                    </div>

                    <div className={`rounded-xl p-3.5 border flex flex-col justify-between ${
                        isGrowthPositive 
                            ? 'bg-emerald-50/60 border-emerald-200/70' 
                            : 'bg-rose-50/60 border-rose-200/70'
                    }`}>
                        <span className={`text-xs font-medium ${isGrowthPositive ? 'text-emerald-700' : 'text-rose-700'}`}>
                            Growth Rate
                        </span>
                        <div className="flex items-baseline gap-1.5 mt-1">
                            <span className={`text-xl font-bold ${isGrowthPositive ? 'text-emerald-700' : 'text-rose-700'}`}>
                                {isGrowthPositive ? `+${summary.growth_pct}%` : `${summary.growth_pct}%`}
                            </span>
                            <span className="text-xs opacity-75">vs previous</span>
                        </div>
                    </div>
                </div>
            )}

            {/* Chart Container */}
            <div className="relative min-h-[350px]">
                {loading && (
                    <div className="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10 rounded-xl">
                        <div className="flex items-center gap-2.5 text-xs font-medium text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-xs">
                            <RefreshCw className="w-4 h-4 animate-spin text-indigo-600" />
                            Loading comparison chart...
                        </div>
                    </div>
                )}

                {error ? (
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <AlertCircle className="w-8 h-8 text-rose-500 mb-2" />
                        <p className="text-sm font-medium text-slate-800">Failed to render comparison</p>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm">{error}</p>
                        <button
                            type="button"
                            onClick={fetchData}
                            className="mt-4 px-3 py-1.5 text-xs font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors"
                        >
                            Try again
                        </button>
                    </div>
                ) : (
                    <div ref={chartRef} className="w-full" />
                )}
            </div>
        </div>
    );
}
