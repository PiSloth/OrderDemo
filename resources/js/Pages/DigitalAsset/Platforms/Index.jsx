import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
    Box,
    Card,
    CardContent,
    Typography,
    Grid,
    Button,
    Chip,
    Table,
    TableHead,
    TableBody,
    TableRow,
    TableCell,
    TableContainer,
    Paper,
    TextField,
    MenuItem,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    IconButton,
    Tooltip,
    Alert,
    Snackbar
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Hub as HubIcon, Close as CloseIcon } from '@mui/icons-material';

export default function PlatformsIndex({ platforms = [], categories = [] }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [selectedPlatform, setSelectedPlatform] = useState(null);
    const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });

    const [createFormData, setCreateFormData] = useState({
        code: '',
        name: '',
        category: 'cloud',
        status: 'active',
    });

    const [editFormData, setEditFormData] = useState({
        code: '',
        name: '',
        category: 'cloud',
        status: 'active',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        router.post('/digital-assets/platforms', createFormData, {
            onSuccess: () => {
                setCreateModalOpen(false);
                setCreateFormData({ code: '', name: '', category: 'cloud', status: 'active' });
                setSnackbar({ open: true, message: 'Platform created successfully.', severity: 'success' });
            },
            onError: (errors) => {
                const msg = Object.values(errors)[0] || 'Failed to create platform.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    const openEdit = (platform) => {
        setSelectedPlatform(platform);
        setEditFormData({
            code: platform.code || '',
            name: platform.name || '',
            category: platform.category || 'other',
            status: platform.status || 'active',
        });
        setEditModalOpen(true);
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!selectedPlatform) return;
        router.put(`/digital-assets/platforms/${selectedPlatform.id}`, editFormData, {
            onSuccess: () => {
                setEditModalOpen(false);
                setSelectedPlatform(null);
                setSnackbar({ open: true, message: 'Platform updated successfully.', severity: 'success' });
            },
            onError: (errors) => {
                const msg = Object.values(errors)[0] || 'Failed to update platform.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    return (
        <AsideLayout title="Software Platforms Catalog">
            <Head title="Platforms - Digital Asset" />

            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Software Platforms Catalog
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Catalog of supported software platforms, business systems, and online services.
                    </Typography>
                </Box>
                <Button
                    variant="contained"
                    startIcon={<AddIcon />}
                    onClick={() => setCreateModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                >
                    Add Platform
                </Button>
            </Box>

            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                <Table size="small">
                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                        <TableRow>
                            <TableCell sx={{ fontWeight: 700 }}>Platform Code</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Display Name</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Category</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Active Accounts</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Created Date</TableCell>
                            <TableCell sx={{ fontWeight: 700 }} align="right">Actions</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {platforms.length > 0 ? (
                            platforms.map((p) => (
                                <TableRow key={p.id} hover>
                                    <TableCell sx={{ fontFamily: 'monospace', fontWeight: 600 }}>{p.code}</TableCell>
                                    <TableCell sx={{ fontWeight: 600 }}>{p.name}</TableCell>
                                    <TableCell><Chip size="small" label={p.category || 'Other'} variant="outlined" /></TableCell>
                                    <TableCell>
                                        <Chip
                                            size="small"
                                            color={(p.active_accounts_count ?? p.accounts_count ?? 0) > 0 ? "primary" : "default"}
                                            label={`${p.active_accounts_count ?? p.accounts_count ?? 0} Accounts`}
                                            variant={(p.active_accounts_count ?? p.accounts_count ?? 0) > 0 ? "filled" : "outlined"}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Chip
                                            size="small"
                                            label={p.status}
                                            color={p.status === 'active' ? 'success' : 'default'}
                                        />
                                    </TableCell>
                                    <TableCell>{new Date(p.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell align="right">
                                        <Tooltip title="Edit Platform">
                                            <IconButton size="small" color="primary" onClick={() => openEdit(p)}>
                                                <EditIcon fontSize="small" />
                                            </IconButton>
                                        </Tooltip>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 3 }}>
                                    <Typography color="text.secondary">No software platforms found.</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Modal: Add Platform */}
            <Dialog open={createModalOpen} onClose={() => setCreateModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleCreate}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Add Software Platform</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                required
                                fullWidth
                                label="Platform Code (lowercase, snake_case)"
                                placeholder="e.g. google_workspace, github_org, aws_iam"
                                value={createFormData.code}
                                onChange={(e) => setCreateFormData({ ...createFormData, code: e.target.value.toLowerCase().replace(/\s+/g, '_') })}
                            />
                            <TextField
                                required
                                fullWidth
                                label="Platform Name"
                                placeholder="e.g. Google Workspace, GitHub"
                                value={createFormData.name}
                                onChange={(e) => setCreateFormData({ ...createFormData, name: e.target.value })}
                            />
                            <TextField
                                select
                                fullWidth
                                label="Category"
                                value={createFormData.category}
                                onChange={(e) => setCreateFormData({ ...createFormData, category: e.target.value })}
                            >
                                {categories.map((c) => (
                                    <MenuItem key={c} value={c}>{c}</MenuItem>
                                ))}
                            </TextField>
                            <TextField
                                select
                                fullWidth
                                label="Status"
                                value={createFormData.status}
                                onChange={(e) => setCreateFormData({ ...createFormData, status: e.target.value })}
                            >
                                <MenuItem value="active">Active</MenuItem>
                                <MenuItem value="inactive">Inactive</MenuItem>
                            </TextField>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCreateModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Create Platform</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Modal: Edit Platform */}
            {(() => {
                const activeCount = selectedPlatform?.active_accounts_count ?? selectedPlatform?.accounts_count ?? 0;
                const hasActiveAccounts = activeCount > 0;

                return (
                    <Dialog open={editModalOpen} onClose={() => setEditModalOpen(false)} maxWidth="sm" fullWidth>
                        <form onSubmit={handleUpdate}>
                            <DialogTitle sx={{ fontWeight: 700, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                    <EditIcon color="primary" />
                                    <span>Edit Platform: {selectedPlatform?.name || selectedPlatform?.code}</span>
                                </Box>
                                <IconButton size="small" onClick={() => setEditModalOpen(false)}>
                                    <CloseIcon fontSize="small" />
                                </IconButton>
                            </DialogTitle>
                            <DialogContent dividers>
                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2.5, mt: 1 }}>
                                    {hasActiveAccounts && (
                                        <Alert severity="warning" sx={{ borderRadius: 2 }}>
                                            This platform currently has <strong>{activeCount} active account(s)</strong>. It cannot be set to <strong>Inactive</strong> until all active accounts are retired or reassigned.
                                        </Alert>
                                    )}

                                    <TextField
                                        required
                                        fullWidth
                                        label="Platform Code (lowercase, snake_case)"
                                        placeholder="e.g. google_workspace, github_org, aws_iam"
                                        value={editFormData.code}
                                        onChange={(e) => setEditFormData({ ...editFormData, code: e.target.value.toLowerCase().replace(/\s+/g, '_') })}
                                        helperText="Unique system identifier code for this platform"
                                    />
                                    <TextField
                                        required
                                        fullWidth
                                        label="Platform Name"
                                        placeholder="e.g. Google Workspace, GitHub"
                                        value={editFormData.name}
                                        onChange={(e) => setEditFormData({ ...editFormData, name: e.target.value })}
                                        helperText="Display name shown in lists and catalogs"
                                    />
                                    <TextField
                                        select
                                        fullWidth
                                        label="Category"
                                        value={editFormData.category}
                                        onChange={(e) => setEditFormData({ ...editFormData, category: e.target.value })}
                                    >
                                        {categories.map((c) => (
                                            <MenuItem key={c} value={c}>{c}</MenuItem>
                                        ))}
                                    </TextField>
                                    <TextField
                                        select
                                        fullWidth
                                        label="Status"
                                        value={editFormData.status}
                                        onChange={(e) => {
                                            if (e.target.value === 'inactive' && hasActiveAccounts) {
                                                return;
                                            }
                                            setEditFormData({ ...editFormData, status: e.target.value });
                                        }}
                                        helperText={
                                            hasActiveAccounts
                                                ? "Deactivation blocked: Active accounts are currently assigned to this platform."
                                                : "Active platforms are available when registering new software accounts."
                                        }
                                    >
                                        <MenuItem value="active">Active</MenuItem>
                                        <MenuItem value="inactive" disabled={hasActiveAccounts}>
                                            Inactive {hasActiveAccounts ? '(Disabled — Active Accounts Exist)' : ''}
                                        </MenuItem>
                                    </TextField>
                                </Box>
                            </DialogContent>
                            <DialogActions sx={{ p: 2 }}>
                                <Button onClick={() => setEditModalOpen(false)}>Cancel</Button>
                                <Button type="submit" variant="contained" color="primary">Save Changes</Button>
                            </DialogActions>
                        </form>
                    </Dialog>
                );
            })()}

            {/* Notification Snackbar */}
            <Snackbar
                open={snackbar.open}
                autoHideDuration={5000}
                onClose={() => setSnackbar((prev) => ({ ...prev, open: false }))}
                anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            >
                <Alert
                    onClose={() => setSnackbar((prev) => ({ ...prev, open: false }))}
                    severity={snackbar.severity}
                    variant="filled"
                    sx={{ width: '100%' }}
                >
                    {snackbar.message}
                </Alert>
            </Snackbar>
        </AsideLayout>
    );
}
