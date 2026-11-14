import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import {
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Grid,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from '@mui/material';
import { ArrowLeft } from 'lucide-react';

export default function CustomerShow({ customer, sales = [], summary = {} }) {
    return (
        <AsideLayout title={`Customer: ${customer.name}`}>
            <Head title={`Customer: ${customer.name}`} />
            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 1280, mx: 'auto' }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                    <Box>
                        <Typography variant="h5" fontWeight={700}>
                            {customer.name}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Phone: {customer.phone_normalized || customer.phone_raw || '-'} | Address: {customer.address_raw || '-'}
                            {customer.region?.name ? ` | ${customer.region.name}` : ''}
                            {customer.township?.name ? ` / ${customer.township.name}` : ''}
                        </Typography>
                    </Box>
                    <Button component={Link} href="/jewelry/sales/customers" variant="outlined" size="small" startIcon={<ArrowLeft size={16} />}>
                        Back to Customers
                    </Button>
                </Stack>

                <Grid container spacing={2} sx={{ mb: 3 }}>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Invoices</Typography>
                                <Typography variant="h6" fontWeight={700}>{summary.invoices_count || 0}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Spent</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(summary.total_spent || 0).toLocaleString()} MMK</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Total Weight</Typography>
                                <Typography variant="h6" fontWeight={700}>{Number(summary.total_weight_g || 0).toFixed(3)} g</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                    <Grid item xs={6} sm={3}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="caption" color="text.secondary">Last Purchase</Typography>
                                <Typography variant="h6" fontWeight={700}>
                                    {summary.last_sale_at ? String(summary.last_sale_at).slice(0, 10) : '-'}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                {sales.map((sale) => (
                    <Card key={sale.id} variant="outlined" sx={{ mb: 2 }}>
                        <CardContent>
                            <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" sx={{ mb: 1.5 }}>
                                <Stack direction="row" spacing={1} alignItems="center">
                                    <Typography variant="subtitle1" fontWeight={700}>
                                        {sale.sale_number}
                                    </Typography>
                                    {sale.branch_name_raw && <Chip label={sale.branch_name_raw} size="small" />}
                                    <Typography variant="caption" color="text.secondary">
                                        {String(sale.sale_at).replace('T', ' ').slice(0, 19)}
                                    </Typography>
                                </Stack>
                                <Typography variant="subtitle1" fontWeight={700} color="primary">
                                    {Number(sale.invoice_total || 0).toLocaleString()} MMK ({Number(sale.invoice_total_weight_g || 0).toFixed(3)} g)
                                </Typography>
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
                                            <TableCell align="right">Total Wt (g)</TableCell>
                                            <TableCell align="right">Amount (MMK)</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {(sale.items || []).map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>{item.barcode || '-'}</TableCell>
                                                <TableCell>{item.product?.product_name || '-'}</TableCell>
                                                <TableCell>{item.product?.gold_type || '-'}</TableCell>
                                                <TableCell>{item.product?.goldsmith || '-'}</TableCell>
                                                <TableCell align="right">{item.quantity}</TableCell>
                                                <TableCell align="right">{Number(item.item_total_weight_g || 0).toFixed(4)}</TableCell>
                                                <TableCell align="right">{Number(item.item_total_amount || 0).toLocaleString()}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>

                            {sale.remark && (
                                <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1 }}>
                                    Remark: {sale.remark}
                                </Typography>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </Box>
        </AsideLayout>
    );
}
