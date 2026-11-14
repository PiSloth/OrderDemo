import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
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
    Pagination,
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
import { ArrowLeft } from 'lucide-react';

export default function Products({ products, totals = {}, filterOptions = {}, filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [goldType, setGoldType] = useState(filters.gold_type || '');
    const [itemName, setItemName] = useState(filters.item_name || '');
    const [productCategoryId, setProductCategoryId] = useState(filters.product_category_id || '');
    const [goldsmith, setGoldsmith] = useState(filters.goldsmith || '');
    const [branch, setBranch] = useState(filters.branch || '');

    const applyFilters = (page = 1) => {
        router.get(
            '/jewelry/sales/products',
            {
                search: search || undefined,
                from: from || undefined,
                to: to || undefined,
                gold_type: goldType || undefined,
                item_name: itemName || undefined,
                product_category_id: productCategoryId || undefined,
                goldsmith: goldsmith || undefined,
                branch: branch || undefined,
                page,
            },
            { preserveState: true }
        );
    };

    return (
        <AsideLayout title="Product Sale Data Analysis">
            <Head title="Product Sale Data Analysis" />
            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 1440, mx: 'auto' }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                    <Box>
                        <Typography variant="h5" fontWeight={700}>
                            Product Sale Data Analysis
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Analyze sold quantities, weights, gold value, labor fees, and profit/loss across product attributes.
                        </Typography>
                    </Box>
                    <Button component={Link} href="/jewelry/sales" variant="outlined" size="small" startIcon={<ArrowLeft size={16} />}>
                        Sales Analytics
                    </Button>
                </Stack>

                <Grid container spacing={2} sx={{ mb: 3 }}>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Qty Sold</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(totals?.total_qty || 0).toLocaleString()}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Weight</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(totals?.total_weight_g || 0).toFixed(3)} g</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Labor Fees</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(totals?.total_labor_fee || 0).toLocaleString()} MMK</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Sale Amount</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(totals?.total_amount || 0).toLocaleString()} MMK</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                <Card variant="outlined" sx={{ mb: 3 }}>
                    <CardContent>
                        <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} flexWrap="wrap" useFlexGap alignItems="center">
                            <TextField
                                label="Search product / length"
                                size="small"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters(1)}
                            />
                            <TextField
                                label="From"
                                type="date"
                                size="small"
                                InputLabelProps={{ shrink: true }}
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                            />
                            <TextField
                                label="To"
                                type="date"
                                size="small"
                                InputLabelProps={{ shrink: true }}
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                            />
                            <FormControl size="small" sx={{ minWidth: 130 }}>
                                <InputLabel>Branch</InputLabel>
                                <Select label="Branch" value={branch} onChange={(e) => setBranch(e.target.value)}>
                                    <MenuItem value="">All</MenuItem>
                                    {(filterOptions.branches || []).map((b) => (
                                        <MenuItem key={b} value={b}>{b}</MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 130 }}>
                                <InputLabel>Gold Type</InputLabel>
                                <Select label="Gold Type" value={goldType} onChange={(e) => setGoldType(e.target.value)}>
                                    <MenuItem value="">All</MenuItem>
                                    {(filterOptions.gold_types || []).map((g) => (
                                        <MenuItem key={g} value={g}>{g}</MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 140 }}>
                                <InputLabel>Item Name</InputLabel>
                                <Select label="Item Name" value={itemName} onChange={(e) => setItemName(e.target.value)}>
                                    <MenuItem value="">All</MenuItem>
                                    {(filterOptions.item_names || []).map((i) => (
                                        <MenuItem key={i} value={i}>{i}</MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 160 }}>
                                <InputLabel>Category (ပစ္စည်းအမျိုးအစား)</InputLabel>
                                <Select
                                    label="Category (ပစ္စည်းအမျိုးအစား)"
                                    value={productCategoryId}
                                    onChange={(e) => setProductCategoryId(e.target.value)}
                                >
                                    <MenuItem value="">All</MenuItem>
                                    {(filterOptions.product_categories || []).map((c) => (
                                        <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 120 }}>
                                <InputLabel>Goldsmith</InputLabel>
                                <Select label="Goldsmith" value={goldsmith} onChange={(e) => setGoldsmith(e.target.value)}>
                                    <MenuItem value="">All</MenuItem>
                                    {(filterOptions.goldsmiths || []).map((gs) => (
                                        <MenuItem key={gs} value={gs}>{gs}</MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <Button variant="contained" size="small" onClick={() => applyFilters(1)}>
                                Filter
                            </Button>
                        </Stack>
                    </CardContent>
                </Card>

                <Card variant="outlined">
                    <CardContent>
                        <TableContainer>
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Product Name</TableCell>
                                        <TableCell>Category</TableCell>
                                        <TableCell>Gold Type</TableCell>
                                        <TableCell>Item Type</TableCell>
                                        <TableCell>Goldsmith</TableCell>
                                        <TableCell>Length / Spec</TableCell>
                                        <TableCell align="right">Qty</TableCell>
                                        <TableCell align="right">Total Wt (g)</TableCell>
                                        <TableCell align="right">Gold Amt</TableCell>
                                        <TableCell align="right">Labor Fee</TableCell>
                                        <TableCell align="right">Total Amt (MMK)</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {products.data.map((p, idx) => (
                                        <TableRow key={p.product_id || idx} hover>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight={600}>{p.product_name}</Typography>
                                            </TableCell>
                                            <TableCell>{p.category_name || '-'}</TableCell>
                                            <TableCell>{p.gold_type ? <Chip label={p.gold_type} size="small" /> : '-'}</TableCell>
                                            <TableCell>{p.item_name || '-'}</TableCell>
                                            <TableCell>{p.goldsmith || '-'}</TableCell>
                                            <TableCell>{p.length || '-'}</TableCell>
                                            <TableCell align="right">{p.total_qty}</TableCell>
                                            <TableCell align="right">{Number(p.total_weight_g || 0).toFixed(4)}</TableCell>
                                            <TableCell align="right">{Number(p.total_gold_amount || 0).toLocaleString()}</TableCell>
                                            <TableCell align="right">{Number(p.total_labor_fee || 0).toLocaleString()}</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 600 }}>
                                                {Number(p.total_amount || 0).toLocaleString()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {products.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={11} align="center">
                                                No product sales data found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>

                        {products.last_page > 1 && (
                            <Stack alignItems="center" sx={{ mt: 2 }}>
                                <Pagination
                                    count={products.last_page}
                                    page={products.current_page}
                                    onChange={(_, page) => applyFilters(page)}
                                />
                            </Stack>
                        )}
                    </CardContent>
                </Card>
            </Box>
        </AsideLayout>
    );
}
