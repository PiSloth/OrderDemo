import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Award, Package, Calendar, RefreshCw, AlertCircle, TrendingUp } from 'lucide-react';

export default function PopularProductsTable({ branches = [] }) {
    const today = new Date();
    const oneMonthAgo = new Date();
    oneMonthAgo.setDate(today.getDate() - 30);

    const [startDate, setStartDate] = useState(oneMonthAgo.toISOString().split('T')[0]);
    const [endDate, setEndDate] = useState(today.toISOString().split('T')[0]);
    const [branchId, setBranchId] = useState('');
    const [limit, setLimit] = useState(10);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [products, setProducts] = useState([]);

    const fetchProducts = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = {
                start_date: startDate,
                end_date: endDate,
                limit,
            };
            if (branchId) {
                params.branch_id = branchId;
            }

            const res = await axios.get('/sale-kpi/popular-products', { params });
            setProducts(res.data || []);
        } catch (err) {
            console.error('Error fetching popular products:', err);
            setError(err.response?.data?.message || 'Failed to load popular products');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchProducts();
    }, [startDate, endDate, branchId, limit]);

    const formatNum = (num) => {
        if (!num) return '0';
        return Number(num).toLocaleString('en-US');
    };

    const maxSale = products.length > 0 ? Math.max(...products.map((p) => Number(p.total_sale || 0))) : 1;

    return (
        <div className="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs transition-all mb-6">
            {/* Header & Controls */}
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shadow-xs">
                        <Award className="w-5 h-5" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold text-slate-900 text-base">Top Performing Products</h3>
                            <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                Best Sellers
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Highest selling designs, lengths, and weights across jewelry branches
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                    {/* Branch Filter */}
                    <select
                        value={branchId}
                        onChange={(e) => setBranchId(e.target.value)}
                        className="bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 py-1.5 px-2.5 focus:ring-1 focus:ring-slate-400 cursor-pointer"
                    >
                        <option value="">All Branches</option>
                        {branches.map((b) => (
                            <option key={b.id} value={b.id}>
                                {b.name}
                            </option>
                        ))}
                    </select>

                    {/* Date Range */}
                    <div className="flex items-center bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 gap-1.5 text-xs text-slate-600">
                        <Calendar className="w-3.5 h-3.5 text-slate-400" />
                        <input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="bg-transparent border-none text-xs p-0 text-slate-700 focus:ring-0"
                        />
                        <span className="text-slate-400">to</span>
                        <input
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="bg-transparent border-none text-xs p-0 text-slate-700 focus:ring-0"
                        />
                    </div>

                    {/* Limit Selector */}
                    <select
                        value={limit}
                        onChange={(e) => setLimit(Number(e.target.value))}
                        className="bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 py-1.5 px-2 focus:ring-1 focus:ring-slate-400 cursor-pointer"
                    >
                        <option value={5}>Top 5</option>
                        <option value={10}>Top 10</option>
                        <option value={20}>Top 20</option>
                    </select>

                    {/* Refresh */}
                    <button
                        type="button"
                        onClick={fetchProducts}
                        disabled={loading}
                        className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all disabled:opacity-50"
                        title="Refresh data"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Table Area */}
            <div className="mt-5 relative min-h-[200px]">
                {loading && (
                    <div className="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10 rounded-xl">
                        <div className="flex items-center gap-2.5 text-xs font-medium text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-xs">
                            <RefreshCw className="w-4 h-4 animate-spin text-amber-500" />
                            Loading top products...
                        </div>
                    </div>
                )}

                {error ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <AlertCircle className="w-8 h-8 text-rose-500 mb-2" />
                        <p className="text-sm font-medium text-slate-800">Failed to load top products</p>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm">{error}</p>
                        <button
                            type="button"
                            onClick={fetchProducts}
                            className="mt-4 px-3 py-1.5 text-xs font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors"
                        >
                            Try again
                        </button>
                    </div>
                ) : products.length === 0 ? (
                    <div className="py-12 text-center">
                        <Package className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                        <p className="text-xs font-medium text-slate-600">No sale records found</p>
                        <p className="text-[11px] text-slate-400 mt-0.5">Try widening the date range or choosing a different branch</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                                    <th className="py-2.5 px-3 w-12 text-center">#</th>
                                    <th className="py-2.5 px-3">Shape / Design</th>
                                    <th className="py-2.5 px-3">Length</th>
                                    <th className="py-2.5 px-3">Weight</th>
                                    <th className="py-2.5 px-3">UOM</th>
                                    <th className="py-2.5 px-3 text-right">Units Sold</th>
                                    <th className="py-2.5 px-3 w-40">Relative Share</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50 text-xs">
                                {products.map((item, idx) => {
                                    const pct = maxSale > 0 ? (Number(item.total_sale || 0) / maxSale) * 100 : 0;
                                    return (
                                        <tr key={idx} className="hover:bg-slate-50/70 transition-colors">
                                            <td className="py-3 px-3 text-center">
                                                {idx === 0 ? (
                                                    <span className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 font-bold text-[10px]">
                                                        1
                                                    </span>
                                                ) : idx === 1 ? (
                                                    <span className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px]">
                                                        2
                                                    </span>
                                                ) : idx === 2 ? (
                                                    <span className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-50 text-amber-800 font-bold text-[10px]">
                                                        3
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400 font-medium text-[11px]">
                                                        {idx + 1}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 px-3 font-medium text-slate-900">
                                                {item.shape || <span className="text-slate-400 italic">Unspecified</span>}
                                            </td>
                                            <td className="py-3 px-3 text-slate-600">
                                                {item.length || '—'}
                                            </td>
                                            <td className="py-3 px-3 text-slate-600">
                                                {item.weight || '—'}
                                            </td>
                                            <td className="py-3 px-3 text-slate-500">
                                                {item.uom || '—'}
                                            </td>
                                            <td className="py-3 px-3 text-right font-semibold text-slate-900">
                                                {formatNum(item.total_sale)}
                                            </td>
                                            <td className="py-3 px-3">
                                                <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                    <div
                                                        className="bg-amber-500 h-1.5 rounded-full transition-all duration-500"
                                                        style={{ width: `${Math.min(100, Math.max(5, pct))}%` }}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
