import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import ApexCharts from 'apexcharts';
import { LayoutGrid, RefreshCw, AlertCircle } from 'lucide-react';

export default function BranchHeatmapMatrix({ defaultFrom, defaultTo, selectedBranches = [] }) {
    const [days, setDays] = useState(7);
    const [metric, setMetric] = useState('gram');
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
            const res = await axios.get('/sale-kpi/heatmap-data', {
                params: {
                    days,
                    metric,
                    branch_ids: branchIds
                }
            });
            setData(res.data);
        } catch (err) {
            console.error('Error fetching heatmap data:', err);
            setError(err.response?.data?.message || 'Failed to load heatmap data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [days, metric, JSON.stringify(selectedBranches)]);

    useEffect(() => {
        if (!data || !chartRef.current) return;

        if (chartInstanceRef.current) {
            chartInstanceRef.current.destroy();
        }

        const options = {
            chart: {
                type: 'heatmap',
                height: Math.max(320, (data.series?.length || 5) * 38),
                toolbar: { show: false }, // Avoid clutter and overlap
                fontFamily: 'Inter, system-ui, sans-serif',
                animations: { enabled: true },
            },
            plotOptions: {
                heatmap: {
                    shadeIntensity: 0.5,
                    radius: 4,
                    useWarmShades: false,
                    colorScale: {
                        ranges: [
                            {
                                from: 0,
                                to: 49.9,
                                name: '< 50% (Under)',
                                color: '#EF4444'
                            },
                            {
                                from: 50,
                                to: 79.9,
                                name: '50% - 80% (Moderate)',
                                color: '#F59E0B'
                            },
                            {
                                from: 80,
                                to: 99.9,
                                name: '80% - 100% (On Pace)',
                                color: '#10B981'
                            },
                            {
                                from: 100,
                                to: 99999,
                                name: '>= 100% (Target Met)',
                                color: '#059669'
                            }
                        ]
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => val ? `${Math.round(val)}%` : '-',
                style: {
                    colors: ['#ffffff'],
                    fontSize: '11px',
                    fontWeight: 700,
                }
            },
            series: data.series || [],
            xaxis: {
                labels: {
                    style: { colors: '#64748B', fontSize: '11px', fontWeight: 600 }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: '#1E293B', fontSize: '12px', fontWeight: 600 }
                }
            },
            tooltip: {
                theme: 'light',
                custom: function({ series, seriesIndex, dataPointIndex, w }) {
                    const point = w.config.series[seriesIndex]?.data[dataPointIndex];
                    if (!point) return '';
                    const branchName = w.config.series[seriesIndex]?.name;
                    const date = point.x;
                    const pct = point.y;
                    const target = point.target || 0;
                    const actual = point.actual || 0;
                    const unit = metric === 'pcs' ? 'pcs' : 'g';

                    return `
                        <div style="padding: 10px 14px; font-size: 12px; font-family: inherit; line-height: 1.5; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">${branchName} (${date})</div>
                            <div style="color: #64748b;">Target: <strong style="color: #1e293b;">${Number(target).toLocaleString()} ${unit}</strong></div>
                            <div style="color: #64748b;">Actual: <strong style="color: #1e293b;">${Number(actual).toLocaleString()} ${unit}</strong></div>
                            <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #e2e8f0; font-weight: 700; color: ${pct >= 100 ? '#059669' : pct >= 80 ? '#10b981' : pct >= 50 ? '#f59e0b' : '#ef4444'};">
                                Achievement: ${pct !== null ? `${pct}%` : 'No Target Set'}
                            </div>
                        </div>
                    `;
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '12px',
                fontWeight: 500,
                labels: { colors: '#475569' },
                itemMargin: { horizontal: 10, vertical: 4 }
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

    return (
        <div className="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs transition-all mb-6">
            {/* Header & Controls */}
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-xs">
                        <LayoutGrid className="w-5 h-5" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold text-slate-900 text-base">Branch Daily Achievement Heatmap</h3>
                            <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                Target Run-rate Matrix
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Color-coded achievement percentage matrix across branches
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

                    {/* Days Horizon Toggle */}
                    <div className="flex items-center bg-slate-100/80 p-0.5 rounded-lg border border-slate-200/60 text-xs font-medium">
                        <button
                            type="button"
                            onClick={() => setDays(7)}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                days === 7
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            7 Days
                        </button>
                        <button
                            type="button"
                            onClick={() => setDays(14)}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                days === 14
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            14 Days
                        </button>
                        <button
                            type="button"
                            onClick={() => setDays(30)}
                            className={`px-3 py-1.5 rounded-md transition-all ${
                                days === 30
                                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            30 Days
                        </button>
                    </div>

                    {/* Refresh */}
                    <button
                        type="button"
                        onClick={fetchData}
                        disabled={loading}
                        className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all disabled:opacity-50"
                        title="Refresh heatmap"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Chart Container */}
            <div className="relative min-h-[320px] mt-4">
                {loading && (
                    <div className="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10 rounded-xl">
                        <div className="flex items-center gap-2.5 text-xs font-medium text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-xs">
                            <RefreshCw className="w-4 h-4 animate-spin text-emerald-600" />
                            Loading heatmap matrix...
                        </div>
                    </div>
                )}

                {error ? (
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <AlertCircle className="w-8 h-8 text-rose-500 mb-2" />
                        <p className="text-sm font-medium text-slate-800">Failed to render heatmap</p>
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
