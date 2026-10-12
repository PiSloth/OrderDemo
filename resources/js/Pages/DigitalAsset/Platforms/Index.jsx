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
    Tooltip
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Hub as HubIcon } from '@mui/icons-material';

export default function PlatformsIndex({ platforms = [], categories = [] }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [selectedPlatform, setSelectedPlatform] = useState(null);

    const [createFormData, setCreateFormData] = useState({
        code: '',
        name: '',
        category: 'cloud',
        status: 'active',
    });

    const [editFormData, setEditFormData] = useState({
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
            }
        });
    };

    const openEdit = (platform) => {
        setSelectedPlatform(platform);
        setEditFormData({
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
                                        <Chip size="small" color="primary" label={`${p.accounts_count || 0} Accounts`} />
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
            <Dialog open={editModalOpen} onClose={() => setEditModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleUpdate}>
                    <DialogTitle sx={{ fontWeight: 700 }}>
                        Edit Platform: {selectedPlatform?.code}
                    </DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                disabled
                                fullWidth
                                label="Platform Code"
                                value={selectedPlatform?.code || ''}
                                helperText="Platform code cannot be changed once created"
                            />
                            <TextField
                                required
                                fullWidth
                                label="Platform Name"
                                value={editFormData.name}
                                onChange={(e) => setEditFormData({ ...editFormData, name: e.target.value })}
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
                                onChange={(e) => setEditFormData({ ...editFormData, status: e.target.value })}
                                helperText="Platforms with active accounts cannot be deactivated"
                            >
                                <MenuItem value="active">Active</MenuItem>
                                <MenuItem value="inactive">Inactive</MenuItem>
                            </TextField>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setEditModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained" color="primary">Save Changes</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
