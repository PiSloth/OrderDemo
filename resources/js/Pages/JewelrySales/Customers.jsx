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
import { ArrowLeft, Eye } from 'lucide-react';

export default function Customers({ customers, stats = {}, regions = [], townships = [], filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [regionId, setRegionId] = useState(filters.region_id || '');
    const [townshipId, setTownshipId] = useState(filters.township_id || '');
    const [segment, setSegment] = useState(filters.segment || '');
    const [sort, setSort] = useState(filters.sort || 'total_spent');

    const filteredTownships = regionId
        ? townships.filter((t) => String(t.region_id) === String(regionId))
        : townships;

    const applyFilters = (page = 1) => {
        router.get(
            '/jewelry/sales/customers',
            {
                search: search || undefined,
                region_id: regionId || undefined,
                township_id: townshipId || undefined,
                segment: segment || undefined,
                sort,
                page,
            },
            { preserveState: true }
        );
    };

    return (
        <AsideLayout title="Customer Analysis">
            <Head title="Customer Analysis" />
            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 1440, mx: 'auto' }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                    <Box>
                        <Typography variant="h5" fontWeight={700}>
                            Customer Analysis
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Deduplicated customer directory by normalized name & phone with region/township segmentation.
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
                                <Typography variant="caption" color="text.secondary">Total Customers</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(stats.total_customers || 0).toLocaleString()}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Repeat Buyers (2+)</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(stats.repeat_customers || 0).toLocaleString()}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">With Phone</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(stats.with_phone || 0).toLocaleString()}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Region Mapped</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(stats.with_region || 0).toLocaleString()}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                <Card variant="outlined" sx={{ mb: 3 }}>
                    <CardContent>
                        <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="center">
                            <TextField
                                label="Search name, phone, address"
                                size="small"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters(1)}
                                sx={{ minWidth: 240 }}
                            />
                            <FormControl size="small" sx={{ minWidth: 150 }}>
                                <InputLabel>Region</InputLabel>
                                <Select
                                    label="Region"
                                    value={regionId}
                                    onChange={(e) => {
                                        setRegionId(e.target.value);
                                        setTownshipId('');
                                    }}
                                >
                                    <MenuItem value="">All Regions</MenuItem>
                                    {regions.map((r) => (
                                        <MenuItem key={r.id} value={r.id}>
                                            {r.name} {r.name_mm ? `(${r.name_mm})` : ''}
                                        </MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 160 }}>
                                <InputLabel>Township</InputLabel>
                                <Select label="Township" value={townshipId} onChange={(e) => setTownshipId(e.target.value)}>
                                    <MenuItem value="">All Townships</MenuItem>
                                    {filteredTownships.map((t) => (
                                        <MenuItem key={t.id} value={t.id}>
                                            {t.name} {t.name_mm ? `(${t.name_mm})` : ''}
                                        </MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 140 }}>
                                <InputLabel>Segment</InputLabel>
                                <Select label="Segment" value={segment} onChange={(e) => setSegment(e.target.value)}>
                                    <MenuItem value="">All</MenuItem>
                                    <MenuItem value="repeat">Repeat (2+)</MenuItem>
                                    <MenuItem value="one_time">One-Time</MenuItem>
                                </Select>
                            </FormControl>
                            <FormControl size="small" sx={{ minWidth: 140 }}>
                                <InputLabel>Sort By</InputLabel>
                                <Select label="Sort By" value={sort} onChange={(e) => setSort(e.target.value)}>
                                    <MenuItem value="total_spent">Total Spent</MenuItem>
                                    <MenuItem value="invoices">Invoices Count</MenuItem>
                                    <MenuItem value="weight">Total Weight</MenuItem>
                                    <MenuItem value="recent">Last Purchase</MenuItem>
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
                                        <TableCell>Customer</TableCell>
                                        <TableCell>Phone</TableCell>
                                        <TableCell>Region / Township</TableCell>
                                        <TableCell align="right">Invoices</TableCell>
                                        <TableCell align="right">Total Weight</TableCell>
                                        <TableCell align="right">Total Spent (MMK)</TableCell>
                                        <TableCell>Last Purchase</TableCell>
                                        <TableCell align="right">Action</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {customers.data.map((c) => (
                                        <TableRow key={c.id} hover>
                                            <TableCell>
                                                <Typography variant="body2" fontWeight={600}>
                                                    {c.name}
                                                </Typography>
                                                {c.address_raw && (
                                                    <Typography variant="caption" color="text.secondary">
                                                        {c.address_raw}
                                                    </Typography>
                                                )}
                                            </TableCell>
                                            <TableCell>{c.phone_normalized || c.phone_raw || '-'}</TableCell>
                                            <TableCell>
                                                {c.region?.name && <Chip label={c.region.name} size="small" sx={{ mr: 0.5 }} />}
                                                {c.township?.name && <Chip label={c.township.name} size="small" variant="outlined" />}
                                                {!c.region && !c.township && '-'}
                                            </TableCell>
                                            <TableCell align="right">{c.sales_count}</TableCell>
                                            <TableCell align="right">{Number(c.total_weight_g || 0).toFixed(3)} g</TableCell>
                                            <TableCell align="right">{Number(c.total_spent || 0).toLocaleString()}</TableCell>
                                            <TableCell>{c.last_sale_at ? String(c.last_sale_at).slice(0, 10) : '-'}</TableCell>
                                            <TableCell align="right">
                                                <Button
                                                    component={Link}
                                                    href={`/jewelry/sales/customers/${c.id}`}
                                                    size="small"
                                                    startIcon={<Eye size={14} />}
                                                >
                                                    View
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {customers.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={8} align="center">
                                                No customers found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>

                        {customers.last_page > 1 && (
                            <Stack alignItems="center" sx={{ mt: 2 }}>
                                <Pagination
                                    count={customers.last_page}
                                    page={customers.current_page}
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
