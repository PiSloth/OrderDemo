import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import ApexCharts from 'apexcharts';
import { Target, TrendingUp, AlertCircle, RefreshCw, Calendar, CheckCircle2 } from 'lucide-react';

export default function TargetVsActualTrajectory({ branches = [], selectedBranches = [] }) {
    const today = new Date();
    const [year, setYear] = useState(today.getFullYear());
    const [month, setMonth] = useState(today.getMonth() + 1);
    const [metric, setMetric] = useState('gram');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [data, setData] = useState({
        categories: [],
        series: [],
        total_target: 0,
        total_actual: 0,
        achievement_pct: 0,
    });

    const chartRef = useRef(null);
    const chartInstanceRef = useRef(null);

    const months = [
        { value: 1, label: 'January' },
        { value: 2, label: 'February' },
        { value: 3, label: 'March' },
        { value: 4, label: 'April' },
        { value: 5, label: 'May' },
        { value: 6, label: 'June' },
        { value: 7, label: 'July' },
        { value: 8, label: 'August' },
        { value: 9, label: 'September' },
        { value: 10, label: 'October' },
        { value: 11, label: 'November' },
        { value: 12, label: 'December' },
    ];

    const currentYear = today.getFullYear();
    const years = [currentYear - 2, currentYear - 1, currentYear, currentYear + 1];

    const fetchData = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = {
                year,
                month,
                metric,
            };
            if (selectedBranches && selectedBranches.length > 0) {
                params.branch_ids = selectedBranches.join(',');
            }

            const res = await axios.get('/sale-kpi/trajectory-data', { params });
            setData(res.data);
        } catch (err) {
            console.error('Error fetching trajectory data:', err);
            setError(err.response?.data?.message || 'Failed to load trajectory data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [year, month, metric, JSON.stringify(selectedBranches)]);

    const formatNum = (num) => {
        if (!num) return '0';
        return Number(num).toLocaleString('en-US', { maximumFractionDigits: 1 });
    };

    useEffect(() => {
        if (!chartRef.current || !data.series || data.series.length === 0) return;

        if (chartInstanceRef.current) {
            chartInstanceRef.current.destroy();
        }

        const options = {
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false,
                    },
                },
                fontFamily: 'Inter, system-ui, sans-serif',
            },
            stroke: {
                curve: 'smooth',
                width: [2.5, 3],
                dashArray: [5, 0], // Target dashed, Actual solid
            },
            colors: ['#94A3B8', '#6366F1'],
            markers: {
                size: [0, 3],
                strokeColors: '#ffffff',
                strokeWidth: 2,
                hover: {
                    size: 6,
                },
            },
            xaxis: {
                categories: data.categories || [],
                labels: {
                    style: {
                        colors: '#64748B',
                        fontSize: '11px',
                        fontWeight: 500,
                    },
                    rotate: -45,
                    rotateAlways: false,
                },
                axisBorder: {
                    color: '#E2E8F0',
                },
                axisTicks: {
                    color: '#E2E8F0',
                },
            },
            yaxis: {
                labels: {
                    formatter: (val) => formatNum(val),
                    style: {
                        colors: '#64748B',
                        fontSize: '11px',
                    },
                },
            },
            grid: {
                borderColor: '#F1F5F9',
                strokeDashArray: 3,
                padding: {
                    left: 10,
                    right: 10,
                },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontWeight: 500,
                fontSize: '12px',
                labels: {
                    colors: '#475569',
                },
                markers: {
                    width: 10,
                    height: 10,
                    radius: 12,
                },
            },
            tooltip: {
                theme: 'light',
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => `${formatNum(val)} ${metric === 'pcs' ? 'pcs' : 'g'}`,
                },
            },
            series: data.series || [],
        };

        const chart = new ApexCharts(chartRef.current, options);
        chartInstanceRef.current = chart;
        chart.render();

        return () => {
            if (chartInstanceRef.current) {
                chartInstanceRef.current.destroy();
                chartInstanceRef.current = null;
            }
        };
    }, [data, metric]);

    return (
        <div className="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs transition-all mb-6">
            {/* Header & Controls */}
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center text-violet-600 shadow-xs">
                        <Target className="w-5 h-5" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold text-slate-900 text-base">Monthly Target vs Actual Trajectory</h3>
                            <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                Day-by-Day Pace
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Daily trajectory and cumulative run-rate against branch goals
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                    {/* Month Selector */}
                    <div className="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-0.5">
                        <select
                            value={month}
                            onChange={(e) => setMonth(Number(e.target.value))}
                            className="bg-transparent text-xs font-medium text-slate-700 py-1.5 px-2.5 border-none focus:ring-0 cursor-pointer"
                        >
                            {months.map((m) => (
                                <option key={m.value} value={m.value}>
                                    {m.label}
                                </option>
                            ))}
                        </select>
                        <select
                            value={year}
                            onChange={(e) => setYear(Number(e.target.value))}
                            className="bg-transparent text-xs font-medium text-slate-700 py-1.5 px-2 border-none focus:ring-0 cursor-pointer border-l border-slate-200"
                        >
                            {years.map((y) => (
                                <option key={y} value={y}>
                                    {y}
                                </option>
                            ))}
                        </select>
                    </div>

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

                    {/* Refresh */}
                    <button
                        type="button"
                        onClick={fetchData}
                        disabled={loading}
                        className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all disabled:opacity-50"
                        title="Refresh data"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Stat Cards Summary */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 my-5">
                <div className="bg-slate-50/70 border border-slate-200/70 rounded-xl p-3.5 flex flex-col justify-between">
                    <span className="text-xs font-medium text-slate-500">Monthly Target</span>
                    <div className="flex items-baseline gap-1.5 mt-1">
                        <span className="text-xl font-bold text-slate-800">
                            {formatNum(data.total_target)}
                        </span>
                        <span className="text-xs text-slate-500 font-medium">{metric === 'pcs' ? 'pcs' : 'g'}</span>
                    </div>
                </div>

                <div className="bg-slate-50/70 border border-slate-200/70 rounded-xl p-3.5 flex flex-col justify-between">
                    <span className="text-xs font-medium text-slate-500">Actual Delivered</span>
                    <div className="flex items-baseline gap-1.5 mt-1">
                        <span className="text-xl font-bold text-indigo-600">
                            {formatNum(data.total_actual)}
                        </span>
                        <span className="text-xs text-indigo-500 font-medium">{metric === 'pcs' ? 'pcs' : 'g'}</span>
                    </div>
                </div>

                <div className="bg-slate-50/70 border border-slate-200/70 rounded-xl p-3.5 flex flex-col justify-between">
                    <span className="text-xs font-medium text-slate-500">Achievement Rate</span>
                    <div className="flex items-baseline gap-2 mt-1">
                        <span className={`text-xl font-bold ${data.achievement_pct >= 100 ? 'text-emerald-600' : data.achievement_pct >= 80 ? 'text-blue-600' : 'text-amber-600'}`}>
                            {data.achievement_pct}%
                        </span>
                        <span className="text-xs text-slate-400">of goal</span>
                    </div>
                </div>
            </div>

            {/* Chart Area */}
            <div className="relative min-h-[350px]">
                {loading && (
                    <div className="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10 rounded-xl">
                        <div className="flex items-center gap-2.5 text-xs font-medium text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-xs">
                            <RefreshCw className="w-4 h-4 animate-spin text-indigo-600" />
                            Loading trajectory timeline...
                        </div>
                    </div>
                )}

                {error ? (
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <AlertCircle className="w-8 h-8 text-rose-500 mb-2" />
                        <p className="text-sm font-medium text-slate-800">Failed to render chart</p>
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
                    <div ref={chartRef} />
                )}
            </div>
        </div>
    );
}
