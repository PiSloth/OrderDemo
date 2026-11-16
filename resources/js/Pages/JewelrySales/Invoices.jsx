import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import axios from 'axios';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import {
    Badge,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Pagination,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import { ArrowLeft, Eye, FileText, Printer, SlidersHorizontal } from 'lucide-react';
import GoldVoucherTemplate from '../../Components/JewelrySales/GoldVoucherTemplate';
import Voucher18KTemplate from '../../Components/JewelrySales/Voucher18KTemplate';

export default function Invoices({ invoices, filterOptions = {}, filters = {} }) {
    const [customer, setCustomer] = useState(filters.customer || filters.customer_name || filters.phone || '');
    const [productName, setProductName] = useState(filters.product_name || '');
    const [weightMin, setWeightMin] = useState(filters.weight_min ?? '');
    const [weightMax, setWeightMax] = useState(filters.weight_max ?? '');
    const [search, setSearch] = useState(filters.search || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [branch, setBranch] = useState(filters.branch || '');
    const [dutyStaffId, setDutyStaffId] = useState(filters.duty_staff_id || '');
    const [inputStaffId, setInputStaffId] = useState(filters.input_staff_id || '');
    const [selectedSale, setSelectedSale] = useState(null);
    const [selectedVoucher, setSelectedVoucher] = useState(null); // { sale, type: 'gold' | '18k' }
    const [moreFiltersOpen, setMoreFiltersOpen] = useState(false);
    const voucherRef = useRef(null);

    const [dateInputEl, setDateInputEl] = useState(null);
    const flatpickrInstanceRef = useRef(null);

    const activeAdvancedCount = [search, from || to, branch, dutyStaffId, inputStaffId].filter(Boolean).length;

    useEffect(() => {
        if (!dateInputEl) return;

        const defaultDates = [from, to].filter(Boolean);
        const fp = flatpickr(dateInputEl, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: defaultDates,
            onReady: (_, __, instance) => {
                if (instance.calendarContainer) {
                    instance.calendarContainer.style.zIndex = '1500';
                }
            },
            onChange: (selectedDates, _, instance) => {
                if (selectedDates.length === 2) {
                    setFrom(instance.formatDate(selectedDates[0], 'Y-m-d'));
                    setTo(instance.formatDate(selectedDates[1], 'Y-m-d'));
                } else if (selectedDates.length === 1) {
                    const single = instance.formatDate(selectedDates[0], 'Y-m-d');
                    setFrom(single);
                    setTo(single);
                } else {
                    setFrom('');
                    setTo('');
                }
            },
        });
        flatpickrInstanceRef.current = fp;

        return () => {
            fp.destroy();
            flatpickrInstanceRef.current = null;
        };
    }, [dateInputEl]);

    const applyFilters = (page = 1, overrides = {}) => {
        const params = {
            customer: customer || undefined,
            product_name: productName || undefined,
            weight_min: weightMin !== '' ? weightMin : undefined,
            weight_max: weightMax !== '' ? weightMax : undefined,
            search: search || undefined,
            from: from || undefined,
            to: to || undefined,
            branch: branch || undefined,
            duty_staff_id: dutyStaffId || undefined,
            input_staff_id: inputStaffId || undefined,
            page,
            ...overrides,
        };

        router.get('/jewelry/sales/invoices', params, { preserveState: true });
    };

    const resetFilters = () => {
        setCustomer('');
        setProductName('');
        setWeightMin('');
        setWeightMax('');
        setSearch('');
        setFrom('');
        setTo('');
        setBranch('');
        setDutyStaffId('');
        setInputStaffId('');
        if (flatpickrInstanceRef.current) {
            flatpickrInstanceRef.current.clear();
        }
        router.get('/jewelry/sales/invoices');
    };

    const openInvoice = async (saleId) => {
        const res = await axios.get(`/jewelry/sales/invoices/${saleId}`);
        setSelectedSale(res.data.sale);
    };

    const openVoucher = async (saleId, type) => {
        const res = await axios.get(`/jewelry/sales/invoices/${saleId}`);
        setSelectedVoucher({ sale: res.data.sale, type });
    };

    const printVoucher = () => {
        const el = voucherRef.current;
        if (!el) return;

        const printWindow = window.open('', '_blank', 'width=900,height=1100');
        if (!printWindow) {
            window.print();
            return;
        }

        const styleSheets = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
            .map((node) => node.outerHTML)
            .join('\n');

        const docTitle = selectedVoucher?.type === '18k' ? '18K Voucher' : 'Gold Voucher';

        printWindow.document.write(`<!doctype html>
<html lang="my">
<head>
  <meta charset="utf-8" />
  <title>${docTitle} - ${selectedVoucher?.sale?.sale_number || ''}</title>
  ${styleSheets}
  <style>
    @page { size: A4; margin: 12mm; }
    body { margin: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    section { width: 100% !important; min-height: auto !important; padding: 0 !important; border: 0 !important; box-shadow: none !important; }
  </style>
</head>
<body>
  ${el.outerHTML}
</body>
</html>`);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    };

    return (
        <AsideLayout title="Invoice Research Management">
            <Head title="Invoice Research Management" />
            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 1440, mx: 'auto' }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                    <Box>
                        <Typography variant="h5" fontWeight={700}>
                            Invoice Research Management
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Search invoices by customer name, phone number, product name, item total weight interval (g), or sale number.
                        </Typography>
                    </Box>
                    <Button component={Link} href="/jewelry/sales" variant="outlined" size="small" startIcon={<ArrowLeft size={16} />}>
                        Sales Analytics
                    </Button>
                </Stack>

                <Card
                    variant="outlined"
                    sx={{
                        mb: 3,
                        position: 'sticky',
                        top: { xs: 56, sm: 64 },
                        zIndex: 10,
                        bgcolor: 'background.paper',
                        boxShadow: 1,
                    }}
                >
                    <CardContent sx={{ py: 1.5, '&:last-child': { pb: 1.5 } }}>
                        <Box
                            sx={{
                                display: 'grid',
                                gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: '2fr 2fr 1.6fr auto' },
                                gap: 1.5,
                                alignItems: 'center',
                            }}
                        >
                            <TextField
                                fullWidth
                                label="Customer Name / Phone"
                                size="small"
                                value={customer}
                                onChange={(e) => setCustomer(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters(1)}
                            />
                            <TextField
                                fullWidth
                                label="Product Name"
                                size="small"
                                value={productName}
                                onChange={(e) => setProductName(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters(1)}
                            />
                            <Stack direction="row" spacing={1}>
                                <TextField
                                    fullWidth
                                    label="Min Wt (g)"
                                    type="number"
                                    size="small"
                                    inputProps={{ step: '0.01', min: '0' }}
                                    value={weightMin}
                                    onChange={(e) => setWeightMin(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters(1)}
                                />
                                <TextField
                                    fullWidth
                                    label="Max Wt (g)"
                                    type="number"
                                    size="small"
                                    inputProps={{ step: '0.01', min: '0' }}
                                    value={weightMax}
                                    onChange={(e) => setWeightMax(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters(1)}
                                />
                            </Stack>
                            <Stack direction="row" spacing={1} alignItems="center" justifyContent="flex-end">
                                <Tooltip title="More Filters (Date Range, Branch, Staff, Invoice #)">
                                    <IconButton
                                        color={activeAdvancedCount > 0 ? 'primary' : 'default'}
                                        onClick={() => setMoreFiltersOpen(true)}
                                        sx={{ border: 1, borderColor: 'divider', borderRadius: 1.5 }}
                                        size="small"
                                    >
                                        <Badge badgeContent={activeAdvancedCount} color="primary">
                                            <SlidersHorizontal size={18} />
                                        </Badge>
                                    </IconButton>
                                </Tooltip>
                                <Button variant="text" size="small" onClick={resetFilters}>
                                    Reset
                                </Button>
                                <Button variant="contained" size="small" onClick={() => applyFilters(1)}>
                                    Search
                                </Button>
                            </Stack>
                        </Box>
                    </CardContent>
                </Card>

                <Card variant="outlined">
                    <CardContent>
                        <TableContainer>
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Sale Number</TableCell>
                                        <TableCell>Sale Date</TableCell>
                                        <TableCell>Customer</TableCell>
                                        <TableCell>Branch</TableCell>
                                        <TableCell>Duty / Input Staff</TableCell>
                                        <TableCell align="right">Items</TableCell>
                                        <TableCell align="right">Total Wt (g)</TableCell>
                                        <TableCell align="right">Invoice Total</TableCell>
                                        <TableCell align="right">Inspect</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {invoices.data.map((inv) => {
                                        const voucherType = /18k|w/i.test(String(inv.branch_name_raw || '')) ? '18k' : 'gold';
                                        return (
                                        <TableRow
                                            key={inv.id}
                                            hover
                                            onClick={() => openVoucher(inv.id, voucherType)}
                                            sx={{ cursor: 'pointer' }}
                                        >
                                            <TableCell sx={{ fontWeight: 600 }}>{inv.sale_number}</TableCell>
                                            <TableCell>{String(inv.sale_at).replace('T', ' ').slice(0, 16)}</TableCell>
                                            <TableCell>
                                                {inv.customer ? (
                                                    <Link
                                                        href={`/jewelry/sales/customers/${inv.customer.id}`}
                                                        onClick={(e) => e.stopPropagation()}
                                                        style={{ textDecoration: 'none', color: '#1976d2', fontWeight: 600 }}
                                                    >
                                                        {inv.customer_name_snapshot || inv.customer.name}
                                                    </Link>
                                                ) : (
                                                    inv.customer_name_snapshot || '-'
                                                )}
                                                {inv.customer_phone_snapshot && (
                                                    <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                                                        {inv.customer_phone_snapshot}
                                                    </Typography>
                                                )}
                                            </TableCell>
                                            <TableCell>{inv.branch_name_raw ? <Chip label={inv.branch_name_raw} size="small" /> : '-'}</TableCell>
                                            <TableCell>
                                                <Typography variant="caption" display="block">
                                                    {inv.duty_staff?.name || '-'} | {inv.input_staff?.name || '-'}
                                                </Typography>
                                            </TableCell>
                                            <TableCell align="right">{inv.items_count}</TableCell>
                                            <TableCell align="right">{Number(inv.invoice_total_weight_g || 0).toFixed(4)} g</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 600 }}>
                                                {Number(inv.invoice_total || 0).toLocaleString()} MMK
                                            </TableCell>
                                            <TableCell align="right" onClick={(e) => e.stopPropagation()}>
                                                <Stack direction="row" spacing={0.5} justifyContent="flex-end">
                                                    <Button size="small" startIcon={<Eye size={14} />} onClick={() => openInvoice(inv.id)}>
                                                        Items
                                                    </Button>
                                                    {voucherType === '18k' ? (
                                                        <Button
                                                            size="small"
                                                            variant="outlined"
                                                            color="secondary"
                                                            startIcon={<FileText size={14} />}
                                                            onClick={() => openVoucher(inv.id, '18k')}
                                                        >
                                                            18K Inv
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            size="small"
                                                            variant="outlined"
                                                            color="warning"
                                                            startIcon={<FileText size={14} />}
                                                            onClick={() => openVoucher(inv.id, 'gold')}
                                                        >
                                                            Gold Inv
                                                        </Button>
                                                    )}
                                                </Stack>
                                            </TableCell>
                                        </TableRow>
                                        );
                                    })}
                                    {invoices.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={9} align="center">
                                                No invoices found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>

                        {invoices.last_page > 1 && (
                            <Stack alignItems="center" sx={{ mt: 2 }}>
                                <Pagination
                                    count={invoices.last_page}
                                    page={invoices.current_page}
                                    onChange={(_, page) => applyFilters(page)}
                                />
                            </Stack>
                        )}
                    </CardContent>
                </Card>

                <Dialog open={Boolean(selectedSale)} onClose={() => setSelectedSale(null)} maxWidth="lg" fullWidth>
                    <DialogTitle>
                        Invoice {selectedSale?.sale_number} — {selectedSale?.customer_name_snapshot || 'Walk-in'}
                    </DialogTitle>
                    <DialogContent dividers>
                        {selectedSale && (
                            <Box>
                                <Stack direction="row" spacing={3} flexWrap="wrap" sx={{ mb: 2 }}>
                                    <Typography variant="body2"><strong>Date:</strong> {String(selectedSale.sale_at).replace('T', ' ').slice(0, 19)}</Typography>
                                    <Typography variant="body2"><strong>Phone:</strong> {selectedSale.customer_phone_snapshot || '-'}</Typography>
                                    <Typography variant="body2"><strong>Address:</strong> {selectedSale.customer_address_snapshot || '-'}</Typography>
                                    <Typography variant="body2"><strong>Branch:</strong> {selectedSale.branch_name_raw || '-'}</Typography>
                                    <Typography variant="body2"><strong>Gold Price:</strong> {Number(selectedSale.gold_price || 0).toLocaleString()}</Typography>
                                </Stack>

                                <TableContainer>
                                    <Table size="small">
                                        <TableHead>
                                            <TableRow>
                                                <TableCell>Barcode</TableCell>
                                                <TableCell>Product</TableCell>
                                                <TableCell>Gold Type</TableCell>
                                                <TableCell>Goldsmith</TableCell>
                                                <TableCell align="right">Qty</TableCell>
                                                <TableCell align="right">Item Wt</TableCell>
                                                <TableCell align="right">Stone Wt</TableCell>
                                                <TableCell align="right">Decrease</TableCell>
                                                <TableCell align="right">Total Wt</TableCell>
                                                <TableCell align="right">Labor</TableCell>
                                                <TableCell align="right">Total Amt</TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {(selectedSale.items || []).map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell>{item.barcode || '-'}</TableCell>
                                                    <TableCell>{item.product?.product_name || '-'}</TableCell>
                                                    <TableCell>{item.product?.gold_type || '-'}</TableCell>
                                                    <TableCell>{item.product?.goldsmith || '-'}</TableCell>
                                                    <TableCell align="right">{item.quantity}</TableCell>
                                                    <TableCell align="right">{Number(item.item_weight_g || 0).toFixed(4)}</TableCell>
                                                    <TableCell align="right">{Number(item.stone_weight_g || 0).toFixed(4)}</TableCell>
                                                    <TableCell align="right">{Number(item.decrease_weight_g || 0).toFixed(4)}</TableCell>
                                                    <TableCell align="right">{Number(item.item_total_weight_g || 0).toFixed(4)}</TableCell>
                                                    <TableCell align="right">{Number(item.labor_fee || 0).toLocaleString()}</TableCell>
                                                    <TableCell align="right" sx={{ fontWeight: 600 }}>
                                                        {Number(item.item_total_amount || 0).toLocaleString()}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </TableContainer>

                                {selectedSale.remark && (
                                    <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
                                        <strong>Remark:</strong> {selectedSale.remark}
                                    </Typography>
                                )}
                            </Box>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Voucher Modal (Gold / 18K) */}
                <Dialog
                    open={Boolean(selectedVoucher)}
                    onClose={() => setSelectedVoucher(null)}
                    maxWidth="lg"
                    fullWidth
                >
                    <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 2 }}>
                        <Box component="span" sx={{ flex: 1, minWidth: 0 }}>
                            {selectedVoucher?.type === '18k' ? '18K Voucher' : 'Gold Voucher'} —{' '}
                            {selectedVoucher?.sale?.sale_number} (
                            {selectedVoucher?.sale?.customer_name_snapshot || 'Walk-in'})
                        </Box>
                        <Button
                            variant="contained"
                            color="error"
                            size="medium"
                            startIcon={<Printer size={18} />}
                            onClick={printVoucher}
                            sx={{
                                minWidth: { xs: 140, sm: 200 },
                                px: 4,
                                fontWeight: 700,
                                flexShrink: 0,
                                ml: 'auto',
                            }}
                        >
                            Print
                        </Button>
                    </DialogTitle>
                    <DialogContent dividers sx={{ bgcolor: '#f5f5f5', overflowX: 'auto' }}>
                        {selectedVoucher?.type === '18k' ? (
                            <Voucher18KTemplate sale={selectedVoucher.sale} voucherRef={voucherRef} />
                        ) : (
                            <GoldVoucherTemplate sale={selectedVoucher?.sale} voucherRef={voucherRef} />
                        )}
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setSelectedVoucher(null)}>Close</Button>
                    </DialogActions>
                </Dialog>

                {/* More Filters Modal */}
                <Dialog
                    open={moreFiltersOpen}
                    onClose={() => setMoreFiltersOpen(false)}
                    disableEnforceFocus
                    maxWidth="sm"
                    fullWidth
                >
                    <DialogTitle>More Invoice Filters</DialogTitle>
                    <DialogContent dividers>
                        <Stack spacing={2} sx={{ pt: 0.5 }}>
                            <TextField
                                fullWidth
                                label="Sale Date Range"
                                placeholder="Select date range..."
                                size="small"
                                inputRef={setDateInputEl}
                                slotProps={{ inputLabel: { shrink: true }, htmlInput: { readOnly: true } }}
                            />
                            <TextField
                                fullWidth
                                label="Invoice # / Barcode / Remark"
                                size="small"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Branch"
                                slotProps={{ select: { native: true }, inputLabel: { shrink: true } }}
                                value={branch}
                                onChange={(e) => setBranch(e.target.value)}
                            >
                                <option value="">All</option>
                                {(filterOptions.branches || []).map((b) => (
                                    <option key={b} value={b}>{b}</option>
                                ))}
                            </TextField>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Duty Staff"
                                slotProps={{ select: { native: true }, inputLabel: { shrink: true } }}
                                value={dutyStaffId}
                                onChange={(e) => setDutyStaffId(e.target.value)}
                            >
                                <option value="">All</option>
                                {(filterOptions.staff || []).map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </TextField>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Input Staff"
                                slotProps={{ select: { native: true }, inputLabel: { shrink: true } }}
                                value={inputStaffId}
                                onChange={(e) => setInputStaffId(e.target.value)}
                            >
                                <option value="">All</option>
                                {(filterOptions.staff || []).map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </TextField>
                        </Stack>
                    </DialogContent>
                    <DialogActions>
                        <Button
                            onClick={() => {
                                setSearch('');
                                setFrom('');
                                setTo('');
                                setBranch('');
                                setDutyStaffId('');
                                setInputStaffId('');
                                if (flatpickrInstanceRef.current) {
                                    flatpickrInstanceRef.current.clear();
                                }
                            }}
                        >
                            Clear
                        </Button>
                        <Button onClick={() => setMoreFiltersOpen(false)}>Close</Button>
                        <Button
                            variant="contained"
                            onClick={() => {
                                setMoreFiltersOpen(false);
                                applyFilters(1);
                            }}
                        >
                            Apply Filters
                        </Button>
                    </DialogActions>
                </Dialog>
            </Box>
        </AsideLayout>
    );
}
