import React, { useEffect, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import {
    Alert,
    AlertTitle,
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    LinearProgress,
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
import { Upload, ArrowLeft, AlertCircle, GitMerge } from 'lucide-react';

export default function Import({
    batches = { data: [] },
    branches = [],
    branchMappings = [],
    unmatchedBranches = [],
}) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
    });
    const [selectedErrors, setSelectedErrors] = useState(null);
    const [mappingModalOpen, setMappingModalOpen] = useState(unmatchedBranches.length > 0);
    const [mappingValues, setMappingValues] = useState({});
    const [savingMappings, setSavingMappings] = useState(false);

    useEffect(() => {
        if (unmatchedBranches.length > 0) {
            setMappingValues((prev) => {
                const next = { ...prev };
                unmatchedBranches.forEach((name) => {
                    if (!(name in next)) {
                        next[name] = '';
                    }
                });
                return next;
            });
            setMappingModalOpen(true);
        }
    }, [unmatchedBranches]);

    const openMappingsModal = () => {
        const next = {};
        branchMappings.forEach((m) => {
            next[m.alias] = m.branch_id;
        });
        unmatchedBranches.forEach((name) => {
            if (!(name in next)) {
                next[name] = '';
            }
        });
        setMappingValues(next);
        setMappingModalOpen(true);
    };

    const handleSaveMappings = () => {
        const payload = Object.entries(mappingValues)
            .filter(([, branchId]) => branchId !== '' && branchId !== null && branchId !== undefined)
            .map(([alias, branchId]) => ({
                alias,
                branch_id: Number(branchId),
            }));

        if (payload.length === 0) return;

        setSavingMappings(true);
        router.post(
            '/jewelry/sales/import/branch-mappings',
            { mappings: payload },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setMappingModalOpen(false);
                },
                onFinish: () => setSavingMappings(false),
            }
        );
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!data.file) return;
        post('/jewelry/sales/import', {
            forceFormData: true,
            onSuccess: () => reset('file'),
        });
    };

    const statusColor = (status) => {
        if (status === 'completed') return 'success';
        if (status === 'failed') return 'error';
        if (status === 'processing') return 'warning';
        return 'default';
    };

    return (
        <AsideLayout title="Import Jewelry Sales Data">
            <Head title="Import Jewelry Sales Data" />
            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 1280, mx: 'auto' }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                    <Box>
                        <Typography variant="h5" fontWeight={700}>
                            Import Sales Master File (XLSX / CSV)
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            Upload POS sales exports. Re-importing overlapping invoices updates existing records idempotently without duplicates.
                        </Typography>
                    </Box>
                    <Stack direction="row" spacing={1}>
                        <Button variant="outlined" size="small" startIcon={<GitMerge size={16} />} onClick={openMappingsModal}>
                            Branch Mappings ({branchMappings.length})
                        </Button>
                        <Button component={Link} href="/jewelry/sales" variant="outlined" size="small" startIcon={<ArrowLeft size={16} />}>
                            Back to Analytics
                        </Button>
                    </Stack>
                </Stack>

                {flash?.success && (
                    <Alert severity="success" sx={{ mb: 2 }}>
                        {flash.success}
                    </Alert>
                )}

                {(flash?.error || errors.file) && (
                    <Alert
                        severity="error"
                        sx={{ mb: 2 }}
                        action={
                            unmatchedBranches.length > 0 ? (
                                <Button color="inherit" size="small" onClick={() => setMappingModalOpen(true)}>
                                    Map Branches
                                </Button>
                            ) : null
                        }
                    >
                        <AlertTitle>Import Not Started</AlertTitle>
                        {flash?.error || errors.file}
                    </Alert>
                )}

                <Card variant="outlined" sx={{ mb: 3 }}>
                    <CardContent>
                        <form onSubmit={handleSubmit}>
                            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems="center">
                                <Button variant="outlined" component="label" startIcon={<Upload size={16} />}>
                                    Choose XLSX / CSV File
                                    <input
                                        type="file"
                                        hidden
                                        accept=".xlsx,.xls,.csv,.txt"
                                        onChange={(e) => setData('file', e.target.files?.[0] || null)}
                                    />
                                </Button>
                                <Typography variant="body2" color="text.secondary" sx={{ flex: 1 }}>
                                    {data.file ? `${data.file.name} (${(data.file.size / 1024).toFixed(1)} KB)` : 'No file selected'}
                                </Typography>
                                <Button type="submit" variant="contained" disabled={!data.file || processing}>
                                    {processing ? 'Importing...' : 'Start Import'}
                                </Button>
                            </Stack>
                            {processing && <LinearProgress sx={{ mt: 2 }} />}
                        </form>
                    </CardContent>
                </Card>

                <Card variant="outlined">
                    <CardContent>
                        <Typography variant="subtitle1" fontWeight={700} sx={{ mb: 2 }}>
                            Import Batch History
                        </Typography>
                        <TableContainer>
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>#</TableCell>
                                        <TableCell>File Name</TableCell>
                                        <TableCell>Uploaded By</TableCell>
                                        <TableCell>Status</TableCell>
                                        <TableCell align="right">Rows</TableCell>
                                        <TableCell align="right">New Sales</TableCell>
                                        <TableCell align="right">Overwritten Sales</TableCell>
                                        <TableCell align="right">New Items</TableCell>
                                        <TableCell align="right">Overwritten Items</TableCell>
                                        <TableCell align="right">Errors</TableCell>
                                        <TableCell>Finished At</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {batches.data.map((batch) => (
                                        <TableRow key={batch.id}>
                                            <TableCell>{batch.id}</TableCell>
                                            <TableCell>{batch.file_name}</TableCell>
                                            <TableCell>{batch.uploader?.name || '-'}</TableCell>
                                            <TableCell>
                                                <Chip label={batch.status} size="small" color={statusColor(batch.status)} />
                                            </TableCell>
                                            <TableCell align="right">{batch.total_rows}</TableCell>
                                            <TableCell align="right">{batch.inserted_sales}</TableCell>
                                            <TableCell align="right">{batch.updated_sales}</TableCell>
                                            <TableCell align="right">{batch.inserted_items}</TableCell>
                                            <TableCell align="right">{batch.updated_items ?? 0}</TableCell>
                                            <TableCell align="right">
                                                {batch.error_count > 0 ? (
                                                    <Button
                                                        size="small"
                                                        color="error"
                                                        startIcon={<AlertCircle size={14} />}
                                                        onClick={() => setSelectedErrors(batch)}
                                                    >
                                                        {batch.error_count}
                                                    </Button>
                                                ) : (
                                                    0
                                                )}
                                            </TableCell>
                                            <TableCell>{batch.finished_at ? new Date(batch.finished_at).toLocaleString() : '-'}</TableCell>
                                        </TableRow>
                                    ))}
                                    {batches.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={11} align="center">
                                                No import batches yet.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </CardContent>
                </Card>

                <Dialog open={Boolean(selectedErrors)} onClose={() => setSelectedErrors(null)} maxWidth="md" fullWidth>
                    <DialogTitle>Import Errors — {selectedErrors?.file_name}</DialogTitle>
                    <DialogContent dividers>
                        {(selectedErrors?.errors_summary || []).map((err, idx) => (
                            <Typography key={idx} variant="body2" color="error" sx={{ mb: 0.5 }}>
                                {err}
                            </Typography>
                        ))}
                    </DialogContent>
                </Dialog>

                <Dialog open={mappingModalOpen} onClose={() => setMappingModalOpen(false)} maxWidth="sm" fullWidth>
                    <DialogTitle>Map Branch Names to Existing Branches</DialogTitle>
                    <DialogContent dividers>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Select the existing branch for each POS/Excel branch name. Mappings are saved in JSON so future imports resolve automatically.
                        </Typography>
                        <Stack spacing={2}>
                            {Object.keys(mappingValues).length === 0 ? (
                                <Typography variant="body2" color="text.secondary">
                                    No custom branch mappings saved yet. Upload a file to detect any unmatched branch names.
                                </Typography>
                            ) : (
                                Object.keys(mappingValues).map((alias) => (
                                    <Stack key={alias} direction="row" spacing={2} alignItems="center">
                                        <Chip
                                            label={alias}
                                            color={unmatchedBranches.includes(alias) ? 'warning' : 'default'}
                                            sx={{ minWidth: 140, justifyContent: 'flex-start', fontWeight: 600 }}
                                        />
                                        <TextField
                                            select
                                            fullWidth
                                            size="small"
                                            label="Target Branch"
                                            slotProps={{ select: { native: true }, inputLabel: { shrink: true } }}
                                            value={mappingValues[alias] ?? ''}
                                            onChange={(e) =>
                                                setMappingValues((prev) => ({
                                                    ...prev,
                                                    [alias]: e.target.value,
                                                }))
                                            }
                                        >
                                            <option value="">-- Select Branch --</option>
                                            {branches.map((b) => (
                                                <option key={b.id} value={b.id}>
                                                    {b.name}
                                                </option>
                                            ))}
                                        </TextField>
                                    </Stack>
                                ))
                            )}
                        </Stack>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setMappingModalOpen(false)} disabled={savingMappings}>
                            Close
                        </Button>
                        <Button
                            variant="contained"
                            onClick={handleSaveMappings}
                            disabled={
                                savingMappings ||
                                Object.values(mappingValues).every((v) => v === '' || v === null || v === undefined)
                            }
                        >
                            {savingMappings ? 'Saving...' : 'Save Mappings'}
                        </Button>
                    </DialogActions>
                </Dialog>
            </Box>
        </AsideLayout>
    );
}
