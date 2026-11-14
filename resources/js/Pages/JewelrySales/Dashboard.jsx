import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import ApexCharts from 'apexcharts';
import {
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    FormControl,
    Grid,
    InputLabel,
    MenuItem,
    Select,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    TextField,
    Typography,
} from '@mui/material';
import { Upload, Users, Package, FileText, TrendingUp, Scale, Repeat } from 'lucide-react';

const formatCurrency = (val) =>
    Number(val || 0).toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' MMK';

const formatWeight = (val) =>
    Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 3, maximumFractionDigits: 4 }) + ' g';

export default function Dashboard({
    summary = {},
    dailyTrend = [],
    byBranch = [],
    byGoldType = [],
    byGoldsmith = [],
    byCategory = [],
    branches = [],
    latestBatch = null,
    filters = {},
}) {
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [branch, setBranch] = useState(filters.branch || '');
    const trendChartRef = useRef(null);

    const applyFilters = () => {
        router.get(
            '/jewelry/sales',
            { from: from || undefined, to: to || undefined, branch: branch || undefined },
            { preserveState: true }
        );
    };

    const resetFilters = () => {
        setFrom('');
        setTo('');
        setBranch('');
        router.get('/jewelry/sales');
    };

    useEffect(() => {
        if (!trendChartRef.current) return;
        trendChartRef.current.innerHTML = '';

        if (!dailyTrend.length) return;

        const chart = new ApexCharts(trendChartRef.current, {
            chart: { type: 'area', height: 280, toolbar: { show: false } },
            series: [
                {
                    name: 'Revenue (MMK)',
                    data: dailyTrend.map((d) => Number(d.total_amount || 0)),
                },
                {
                    name: 'Weight (g)',
                    data: dailyTrend.map((d) => Number(d.total_weight_g || 0)),
                },
            ],
            xaxis: {
                categories: dailyTrend.map((d) => d.sale_date),
            },
            yaxis: [
                {
                    title: { text: 'Revenue (MMK)' },
                    labels: {
                        formatter: (v) => Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 }),
                    },
                },
                {
                    opposite: true,
                    title: { text: 'Weight (g)' },
                    labels: {
                        formatter: (v) => Number(v).toFixed(2) + 'g',
                    },
                },
            ],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#d97706', '#0284c7'],
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
        });

        chart.render();
        return () => chart.destroy();
    }, [dailyTrend]);

    const kpis = [
        {
            label: 'Total Revenue',
            value: formatCurrency(summary.total_revenue),
            sub: `Avg ${formatCurrency(summary.avg_invoice_value)} / invoice`,
            icon: TrendingUp,
            color: '#d97706',
        },
        {
            label: 'Total Invoices',
            value: Number(summary.total_invoices || 0).toLocaleString(),
            sub: `${Number(summary.total_items || 0).toLocaleString()} items sold`,
            icon: FileText,
            color: '#2563eb',
        },
        {
            label: 'Total Weight',
            value: formatWeight(summary.total_weight_g),
            sub: 'Sum of item total weight',
            icon: Scale,
            color: '#059669',
        },
        {
            label: 'Unique Customers',
            value: Number(summary.unique_customers || 0).toLocaleString(),
            sub: `${summary.repeat_customers || 0} repeat (${summary.repeat_rate || 0}%)`,
            icon: Repeat,
            color: '#7c3aed',
        },
    ];

    return (
        <AsideLayout title="Jewelry Sales Analytics">
            <Head title="Jewelry Sales Analytics" />
            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 1440, mx: 'auto' }}>
                <Stack
                    direction={{ xs: 'column', md: 'row' }}
                    justifyContent="space-between"
                    alignItems={{ xs: 'flex-start', md: 'center' }}
                    spacing={2}
                    sx={{ mb: 3 }}
                >
                    <Box>
                        <Typography variant="h5" fontWeight={700}>
                            Jewelry Sales & Master Analytics
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Customer analysis, product sale performance, and invoice research from imported POS master files.
                            {latestBatch && (
                                <> Latest import: <strong>{latestBatch.file_name}</strong> ({latestBatch.status})</>
                            )}
                        </Typography>
                    </Box>

                    <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                        <Button component={Link} href="/jewelry/sales/customers" variant="outlined" size="small" startIcon={<Users size={16} />}>
                            Customers
                        </Button>
                        <Button component={Link} href="/jewelry/sales/products" variant="outlined" size="small" startIcon={<Package size={16} />}>
                            Products
                        </Button>
                        <Button component={Link} href="/jewelry/sales/invoices" variant="outlined" size="small" startIcon={<FileText size={16} />}>
                            Invoices
                        </Button>
                        <Button component={Link} href="/jewelry/sales/import" variant="contained" size="small" startIcon={<Upload size={16} />}>
                            Import XLSX/CSV
                        </Button>
                    </Stack>
                </Stack>

                {/* Filter Bar */}
                <Card variant="outlined" sx={{ mb: 3 }}>
                    <CardContent sx={{ py: 2 }}>
                        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems="center">
                            <TextField
                                label="From Date"
                                type="date"
                                size="small"
                                InputLabelProps={{ shrink: true }}
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                            />
                            <TextField
                                label="To Date"
                                type="date"
                                size="small"
                                InputLabelProps={{ shrink: true }}
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                            />
                            <FormControl size="small" sx={{ minWidth: 180 }}>
                                <InputLabel>Branch</InputLabel>
                                <Select label="Branch" value={branch} onChange={(e) => setBranch(e.target.value)}>
                                    <MenuItem value="">All Branches</MenuItem>
                                    {branches.map((b) => (
                                        <MenuItem key={b} value={b}>
                                            {b}
                                        </MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <Button variant="contained" size="small" onClick={applyFilters}>
                                Apply
                            </Button>
                            <Button variant="text" size="small" onClick={resetFilters}>
                                Reset
                            </Button>
                        </Stack>
                    </CardContent>
                </Card>

                {/* KPI Cards */}
                <Grid container spacing={2} sx={{ mb: 3 }}>
                    {kpis.map((kpi) => {
                        const Icon = kpi.icon;
                        return (
                            <Grid item xs={12} sm={6} md={3} key={kpi.label}>
                                <Card variant="outlined" sx={{ height: '100%' }}>
                                    <CardContent>
                                        <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                                            <Typography variant="caption" color="text.secondary" fontWeight={600}>
                                                {kpi.label}
                                            </Typography>
                                            <Box sx={{ p: 0.75, borderRadius: 1.5, bgcolor: `${kpi.color}18`, color: kpi.color }}>
                                                <Icon size={18} />
                                            </Box>
                                        </Stack>
                                        <Typography variant="h6" fontWeight={700}>
                                            {kpi.value}
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary">
                                            {kpi.sub}
                                        </Typography>
                                    </CardContent>
                                </Card>
                            </Grid>
                        );
                    })}
                </Grid>

                {/* Daily Trend */}
                <Card variant="outlined" sx={{ mb: 3 }}>
                    <CardContent>
                        <Typography variant="subtitle1" fontWeight={700} sx={{ mb: 1 }}>
                            Daily Revenue & Weight Trend
                        </Typography>
                        {dailyTrend.length === 0 ? (
                            <Typography variant="body2" color="text.secondary" sx={{ py: 6, textAlign: 'center' }}>
                                No sales data yet. Import a POS XLSX/CSV file to populate analytics.
                            </Typography>
                        ) : (
                            <Box ref={trendChartRef} />
                        )}
                    </CardContent>
                </Card>

                {/* Breakdowns */}
                <Grid container spacing={2}>
                    <Grid item xs={12} md={4}>
                        <Card variant="outlined" sx={{ height: '100%' }}>
                            <CardContent>
                                <Typography variant="subtitle1" fontWeight={700} sx={{ mb: 1.5 }}>
                                    By Branch
                                </Typography>
                                <TableContainer>
                                    <Table size="small">
                                        <TableHead>
                                            <TableRow>
                                                <TableCell>Branch</TableCell>
                                                <TableCell align="right">Invoices</TableCell>
                                                <TableCell align="right">Weight</TableCell>
                                                <TableCell align="right">Amount</TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {byBranch.map((row) => (
                                                <TableRow key={row.branch_name}>
                                                    <TableCell>
                                                        <Chip label={row.branch_name} size="small" />
                                                    </TableCell>
                                                    <TableCell align="right">{row.invoices_count}</TableCell>
                                                    <TableCell align="right">{Number(row.total_weight_g).toFixed(2)}g</TableCell>
                                                    <TableCell align="right">{Number(row.total_amount).toLocaleString()}</TableCell>
                                                </TableRow>
                                            ))}
                                            {byBranch.length === 0 && (
                                                <TableRow>
                                                    <TableCell colSpan={4} align="center">
                                                        No data
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </TableContainer>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} md={4}>
                        <Card variant="outlined" sx={{ height: '100%' }}>
                            <CardContent>
                                <Typography variant="subtitle1" fontWeight={700} sx={{ mb: 1.5 }}>
                                    By Gold Type
                                </Typography>
                                <TableContainer>
                                    <Table size="small">
                                        <TableHead>
                                            <TableRow>
                                                <TableCell>Gold Type</TableCell>
                                                <TableCell align="right">Qty</TableCell>
                                                <TableCell align="right">Weight</TableCell>
                                                <TableCell align="right">Amount</TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {byGoldType.map((row) => (
                                                <TableRow key={row.gold_type}>
                                                    <TableCell>{row.gold_type}</TableCell>
                                                    <TableCell align="right">{row.total_qty}</TableCell>
                                                    <TableCell align="right">{Number(row.total_weight_g).toFixed(2)}g</TableCell>
                                                    <TableCell align="right">{Number(row.total_amount).toLocaleString()}</TableCell>
                                                </TableRow>
                                            ))}
                                            {byGoldType.length === 0 && (
                                                <TableRow>
                                                    <TableCell colSpan={4} align="center">
                                                        No data
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </TableContainer>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} md={4}>
                        <Card variant="outlined" sx={{ height: '100%' }}>
                            <CardContent>
                                <Typography variant="subtitle1" fontWeight={700} sx={{ mb: 1.5 }}>
                                    Top Goldsmiths
                                </Typography>
                                <TableContainer>
                                    <Table size="small">
                                        <TableHead>
                                            <TableRow>
                                                <TableCell>Goldsmith</TableCell>
                                                <TableCell align="right">Qty</TableCell>
                                                <TableCell align="right">Labor Fee</TableCell>
                                                <TableCell align="right">Amount</TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {byGoldsmith.map((row) => (
                                                <TableRow key={row.goldsmith}>
                                                    <TableCell>{row.goldsmith}</TableCell>
                                                    <TableCell align="right">{row.total_qty}</TableCell>
                                                    <TableCell align="right">{Number(row.total_labor_fee).toLocaleString()}</TableCell>
                                                    <TableCell align="right">{Number(row.total_amount).toLocaleString()}</TableCell>
                                                </TableRow>
                                            ))}
                                            {byGoldsmith.length === 0 && (
                                                <TableRow>
                                                    <TableCell colSpan={4} align="center">
                                                        No data
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </TableContainer>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="subtitle1" fontWeight={700} sx={{ mb: 1.5 }}>
                                    By Category (ပစ္စည်းအမျိုးအစား)
                                </Typography>
                                <TableContainer>
                                    <Table size="small">
                                        <TableHead>
                                            <TableRow>
                                                <TableCell>Category (ပစ္စည်းအမျိုးအစား)</TableCell>
                                                <TableCell align="right">Qty</TableCell>
                                                <TableCell align="right">Total Weight (g)</TableCell>
                                                <TableCell align="right">Total Amount (MMK)</TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {byCategory.map((row) => (
                                                <TableRow key={row.category_name}>
                                                    <TableCell>{row.category_name}</TableCell>
                                                    <TableCell align="right">{row.total_qty}</TableCell>
                                                    <TableCell align="right">{Number(row.total_weight_g || 0).toFixed(4)}g</TableCell>
                                                    <TableCell align="right">{Number(row.total_amount || 0).toLocaleString()}</TableCell>
                                                </TableRow>
                                            ))}
                                            {byCategory.length === 0 && (
                                                <TableRow>
                                                    <TableCell colSpan={4} align="center">
                                                        No data
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </TableContainer>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>
            </Box>
        </AsideLayout>
    );
}
