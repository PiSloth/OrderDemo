import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import ApexCharts from 'apexcharts';
import { Target, RefreshCw, AlertCircle, HelpCircle, X } from 'lucide-react';

const formatTwoDecimals = (val) =>
    Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

export default function RemainingDailyTargetCard({ branches = [], selectedBranches = [] }) {
    const now = new Date();
    const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

    const [monthValue, setMonthValue] = useState(defaultMonth);
    const [branchId, setBranchId] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [data, setData] = useState(null);
    const [helpOpen, setHelpOpen] = useState(false);

    const chartRef = useRef(null);
    const chartInstanceRef = useRef(null);

    const fetchData = async () => {
        setLoading(true);
        setError(null);
        try {
            const [yearStr, monthStr] = monthValue.split('-');
            const params = {
                year: Number(yearStr),
                month: Number(monthStr),
            };
            if (branchId) {
                params.branch_id = branchId;
            } else if (selectedBranches && selectedBranches.length > 0) {
                params.branch_ids = selectedBranches.join(',');
            }

            const res = await axios.get('/sale-kpi/remaining-daily-target', { params });
            setData(res.data);
        } catch (err) {
            console.error('Error fetching remaining daily target:', err);
            setError(err.response?.data?.message || 'Failed to load remaining daily target data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [monthValue, branchId, JSON.stringify(selectedBranches)]);

    useEffect(() => {
        if (!data?.chart || !chartRef.current) return;

        if (chartInstanceRef.current) {
            chartInstanceRef.current.destroy();
        }

        const isDark = document.documentElement.classList.contains('dark');

        const options = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'Inter, system-ui, sans-serif',
                foreColor: isDark ? '#94a3b8' : '#475569',
            },
            plotOptions: {
                bar: {
                    columnWidth: '55%',
                    borderRadius: 4,
                },
            },
            colors: ['#0ea5e9', '#f59e0b'],
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
            },
            series: data.chart.series || [],
            xaxis: {
                categories: data.chart.categories || ['Gram (g)', 'Pcs'],
                labels: { rotate: 0 },
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: formatTwoDecimals,
                },
            },
            grid: {
                strokeDashArray: 4,
                borderColor: isDark ? '#334155' : '#e2e8f0',
            },
            tooltip: {
                shared: true,
                intersect: false,
                theme: isDark ? 'dark' : 'light',
                y: {
                    formatter: formatTwoDecimals,
                },
            },
        };

        chartInstanceRef.current = new ApexCharts(chartRef.current, options);
        chartInstanceRef.current.render();

        return () => {
            if (chartInstanceRef.current) {
                chartInstanceRef.current.destroy();
                chartInstanceRef.current = null;
            }
        };
    }, [data]);

    const metrics = data?.metrics || {};

    return (
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="p-2 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                            <Target className="w-5 h-5" />
                        </div>
                        <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                            Remaining Daily Target (Avg)
                        </h3>
                        <button
                            type="button"
                            onClick={() => setHelpOpen(true)}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            <HelpCircle className="w-3.5 h-3.5" />
                            <span>အသုံးပြုပုံ (မြန်မာ)</span>
                        </button>
                    </div>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {metrics.month_label || monthValue}
                        {metrics.as_of ? ` · As of ${metrics.as_of}` : ''}
                    </p>
                </div>

                <div className="flex flex-wrap items-end gap-3">
                    <div>
                        <label className="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            Month
                        </label>
                        <input
                            type="month"
                            value={monthValue}
                            onChange={(e) => setMonthValue(e.target.value || defaultMonth)}
                            className="h-9 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div className="min-w-[200px]">
                        <label className="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
                            Branch
                        </label>
                        <select
                            value={branchId}
                            onChange={(e) => setBranchId(e.target.value)}
                            className="w-full h-9 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">
                                {selectedBranches?.length > 0 ? 'Selected Branches (Global)' : 'All jewelry branches'}
                            </option>
                            {branches.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <button
                        type="button"
                        onClick={fetchData}
                        disabled={loading}
                        className="h-9 px-3 inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50 transition"
                    >
                        <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            {error && (
                <div className="mt-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 flex-shrink-0" />
                    <span>{error}</span>
                </div>
            )}

            <div className="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80">
                    <div className="text-xs text-slate-500 dark:text-slate-400">Month Target (g)</div>
                    <div className="mt-1 text-base font-bold text-slate-900 dark:text-white">
                        {formatTwoDecimals(metrics.month_target_gram)}
                    </div>
                </div>
                <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80">
                    <div className="text-xs text-slate-500 dark:text-slate-400">Month Target (pcs)</div>
                    <div className="mt-1 text-base font-bold text-slate-900 dark:text-white">
                        {formatTwoDecimals(metrics.month_target_pcs)}
                    </div>
                </div>
                <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80">
                    <div className="text-xs text-slate-500 dark:text-slate-400">Sold To Date (g / pcs)</div>
                    <div className="mt-1 text-base font-bold text-slate-900 dark:text-white">
                        {formatTwoDecimals(metrics.actual_to_date_gram)} / {formatTwoDecimals(metrics.actual_to_date_pcs)}
                    </div>
                </div>
                <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80">
                    <div className="text-xs text-slate-500 dark:text-slate-400">Days Remaining</div>
                    <div className="mt-1 text-base font-bold text-slate-900 dark:text-white">
                        {Number(metrics.days_remaining || 0)}
                    </div>
                </div>
            </div>

            <div className="mt-5 relative min-h-[320px]">
                {loading && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-slate-900/70 rounded-xl">
                        <RefreshCw className="w-6 h-6 animate-spin text-indigo-600" />
                    </div>
                )}
                <div ref={chartRef} className="w-full" />
            </div>

            {helpOpen && (
                <div
                    className="fixed inset-0 z-[9500] bg-black/50 p-4 flex items-center justify-center backdrop-blur-sm"
                    onClick={() => setHelpOpen(false)}
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        className="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden"
                        style={{ maxHeight: 'calc(100vh - 2rem)' }}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                    Remaining Daily Target (Avg) ရဲ့ အဓိပ္ပါယ်
                                </h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    ဒီလအတွက် လက်ကျန် Target ကို နေ့စဉ်အလိုက် ဘယ်လောက်လုပ်ရမလဲ ဆိုတာကို တွက်ပြပါတယ်
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setHelpOpen(false)}
                                className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                <X className="w-4 h-4" />
                                <span>ပိတ်မယ်</span>
                            </button>
                        </div>

                        <div className="px-6 py-4 overflow-y-auto space-y-4 text-sm text-slate-700 dark:text-slate-200" style={{ maxHeight: 'calc(100vh - 10rem)' }}>
                            <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <div className="font-semibold mb-1">ဒီ report က ဘာကိုတွက်ပြတာလဲ?</div>
                                <div className="space-y-2">
                                    <div>
                                        ဒီလအတွက် သတ်မှတ်ထားတဲ့ <span className="font-medium">Target (Gram / Pcs)</span> ကို
                                        ယနေ့အထိ ရောင်းပြီးသားပမာဏနဲ့ နှိုင်းပြီး <span className="font-medium">လက်ကျန်</span> ကိုတွက်ပါတယ်။
                                    </div>
                                    <div>
                                        နောက်ကျန်နေတဲ့ ရက်တွေကို ထပ်တူမျှဝေပြီး{' '}
                                        <span className="font-medium">နေ့စဉ်လိုအပ်တဲ့ Avg Target</span> ကိုပြပါတယ်။
                                    </div>
                                </div>
                            </div>

                            <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <div className="font-semibold mb-1">တွက်နည်း (Step by step)</div>
                                <div className="space-y-2">
                                    <div>
                                        <span className="font-medium">(1) Month Target</span> = ဒီလအတွင်း ထည့်ထားတဲ့ daily target တွေကို စုစုပေါင်း (Gram / Pcs) လုပ်ပါတယ်။
                                    </div>
                                    <div>
                                        <span className="font-medium">(2) Sold To Date</span> = ဒီလအစကနေ ယနေ့အထိ ရောင်းပြီးသား (Gram / Pcs) ကို စုပါတယ်။
                                    </div>
                                    <div>
                                        <span className="font-medium">(3) Remaining</span> = Month Target − Sold To Date. အကယ်လို့ အနုတ်ဖြစ်သွားရင် 0 လို့ယူပါတယ်။
                                    </div>
                                    <div>
                                        <span className="font-medium">(4) Days Remaining</span> = ဒီလအတွက် ယနေ့နောက်ကျန်တဲ့ ရက်အရေအတွက် (မနက်ဖြန်ကနေ လကုန်အထိ) ပါ။
                                    </div>
                                    <div>
                                        <span className="font-medium">(5) Required Avg/Day (Remaining)</span> = Remaining ÷ Days Remaining. Days Remaining = 0 ဖြစ်ရင် 0 ပြပါတယ်။
                                    </div>
                                </div>
                            </div>

                            <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <div className="font-semibold mb-1">ဥပမာ</div>
                                <div className="space-y-1">
                                    <div>ဒီလ Target = 100g / 100pcs</div>
                                    <div>ယနေ့အထိ 10 ရက်အတွင်း ရောင်းပြီး = 20g / 20pcs</div>
                                    <div>လက်ကျန် = 80g / 80pcs</div>
                                    <div>နောက်ကျန်ရက် = 20 ရက်ဆိုရင် Required Avg/Day = 80 ÷ 20 = 4g / 4pcs</div>
                                </div>
                            </div>

                            <div className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <div className="font-semibold mb-1">Branch filter</div>
                                <div className="space-y-1">
                                    <div>Branch ကိုရွေးရင် အဲဒီ Branch တစ်ခုအတွက်ပဲတွက်ပြပါတယ်။</div>
                                    <div>&quot;All jewelry branches&quot; ဆိုရင် Jewelry branch အားလုံးကို စုပေါင်းတွက်ပြပါတယ်။</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
