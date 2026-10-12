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
    DialogActions
} from '@mui/material';
import { Add as AddIcon, Devices as DevicesIcon } from '@mui/icons-material';

export default function DevicesIndex({ devices, officeAssets = [], accounts = [], filters = {} }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [formData, setFormData] = useState({
        ownership_type: 'company',
        fixed_asset_id: '',
        manufacturer: '',
        model_name: '',
        hostname: '',
        operating_system: '',
        device_status: 'active',
        notes: '',
    });

    const [linkModalOpen, setLinkModalOpen] = useState(false);
    const [selectedDevice, setSelectedDevice] = useState(null);
    const [linkData, setLinkData] = useState({
        software_account_id: '',
        relationship_type: 'registered',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        router.post('/digital-assets/devices', {
            ...formData,
            fixed_asset_id: formData.fixed_asset_id || null,
        }, {
            onSuccess: () => {
                setCreateModalOpen(false);
                setFormData({
                    ownership_type: 'company',
                    fixed_asset_id: '',
                    manufacturer: '',
                    model_name: '',
                    hostname: '',
                    operating_system: '',
                    device_status: 'active',
                    notes: '',
                });
            }
        });
    };

    const handleLink = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/accounts/${linkData.software_account_id}/devices`, {
            software_device_id: selectedDevice.id,
            relationship_type: linkData.relationship_type,
        }, {
            onSuccess: () => {
                setLinkModalOpen(false);
                setSelectedDevice(null);
            }
        });
    };

    return (
        <AsideLayout title="Hardware Device Inventory">
            <Head title="Devices - Digital Asset" />

            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Hardware Device Inventory
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Devices linked to software accounts, tracking company-owned assets and personal workstations.
                    </Typography>
                </Box>
                <Button
                    variant="contained"
                    startIcon={<AddIcon />}
                    onClick={() => setCreateModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                >
                    Register Device
                </Button>
            </Box>

            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                <Table size="small">
                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                        <TableRow>
                            <TableCell sx={{ fontWeight: 700 }}>Hostname</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Model / Manufacturer</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Ownership</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Fixed Asset Link</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Operating System</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Linked Accounts</TableCell>
                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {devices.data && devices.data.length > 0 ? (
                            devices.data.map((dev) => (
                                <TableRow key={dev.id} hover>
                                    <TableCell sx={{ fontWeight: 600 }}>{dev.hostname || 'Unnamed Device'}</TableCell>
                                    <TableCell>{dev.manufacturer} {dev.model_name}</TableCell>
                                    <TableCell><Chip size="small" label={dev.ownership_type} /></TableCell>
                                    <TableCell>{dev.fixed_asset ? dev.fixed_asset.name : 'Personal'}</TableCell>
                                    <TableCell>{dev.operating_system || '-'}</TableCell>
                                    <TableCell>
                                        <Chip
                                            size="small"
                                            label={dev.device_status}
                                            color={dev.device_status === 'active' ? 'success' : 'default'}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        {dev.account_devices && dev.account_devices.length > 0 ? (
                                            dev.account_devices.map((ad) => (
                                                <Chip
                                                    key={ad.id}
                                                    size="small"
                                                    label={ad.account?.account_name}
                                                    sx={{ mr: 0.5, mb: 0.5 }}
                                                />
                                            ))
                                        ) : (
                                            <Typography variant="caption" color="text.secondary">None</Typography>
                                        )}
                                    </TableCell>
                                    <TableCell align="right">
                                        <Button
                                            size="small"
                                            onClick={() => {
                                                setSelectedDevice(dev);
                                                setLinkModalOpen(true);
                                            }}
                                            sx={{ textTransform: 'none' }}
                                        >
                                            Link Account
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 3 }}>
                                    <Typography color="text.secondary">No hardware devices registered.</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Modal: Register Device */}
            <Dialog open={createModalOpen} onClose={() => setCreateModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleCreate}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Register Hardware Device</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Ownership Type"
                                value={formData.ownership_type}
                                onChange={(e) => setFormData({ ...formData, ownership_type: e.target.value })}
                            >
                                <MenuItem value="company">Company-Owned Device</MenuItem>
                                <MenuItem value="personal">Personal Device (BYOD)</MenuItem>
                                <MenuItem value="other">Other / Contractor Device</MenuItem>
                            </TextField>

                            {formData.ownership_type === 'company' && (
                                <TextField
                                    select
                                    fullWidth
                                    label="Link to Office Fixed Asset Master"
                                    value={formData.fixed_asset_id}
                                    onChange={(e) => setFormData({ ...formData, fixed_asset_id: e.target.value })}
                                >
                                    <MenuItem value="">Select Fixed Asset (Optional)</MenuItem>
                                    {officeAssets.map((asset) => (
                                        <MenuItem key={asset.id} value={asset.id}>{asset.name}</MenuItem>
                                    ))}
                                </TextField>
                            )}

                            <TextField
                                required
                                fullWidth
                                label="Hostname"
                                placeholder="e.g. IT-SEC-LAPTOP-01"
                                value={formData.hostname}
                                onChange={(e) => setFormData({ ...formData, hostname: e.target.value })}
                            />

                            <Grid container spacing={2}>
                                <Grid item xs={6}>
                                    <TextField
                                        fullWidth
                                        label="Manufacturer"
                                        placeholder="e.g. Apple, Dell, Lenovo"
                                        value={formData.manufacturer}
                                        onChange={(e) => setFormData({ ...formData, manufacturer: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={6}>
                                    <TextField
                                        fullWidth
                                        label="Model Name"
                                        placeholder="e.g. MacBook Pro 16, ThinkPad X1"
                                        value={formData.model_name}
                                        onChange={(e) => setFormData({ ...formData, model_name: e.target.value })}
                                    />
                                </Grid>
                            </Grid>

                            <TextField
                                fullWidth
                                label="Operating System"
                                placeholder="e.g. macOS Sonoma, Windows 11 Pro, Ubuntu 24.04"
                                value={formData.operating_system}
                                onChange={(e) => setFormData({ ...formData, operating_system: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCreateModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Register Device</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Modal: Link to Account */}
            <Dialog open={linkModalOpen} onClose={() => setLinkModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleLink}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Associate Device with Software Account</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <Typography variant="body2">
                                Linking device: <strong>{selectedDevice?.hostname}</strong>
                            </Typography>
                            <TextField
                                select
                                required
                                fullWidth
                                label="Software Account"
                                value={linkData.software_account_id}
                                onChange={(e) => setLinkData({ ...linkData, software_account_id: e.target.value })}
                            >
                                {accounts.map((acc) => (
                                    <MenuItem key={acc.id} value={acc.id}>{acc.account_name} ({acc.username})</MenuItem>
                                ))}
                            </TextField>
                            <TextField
                                select
                                fullWidth
                                label="Relationship Type"
                                value={linkData.relationship_type}
                                onChange={(e) => setLinkData({ ...linkData, relationship_type: e.target.value })}
                            >
                                <MenuItem value="authorized">Authorized Device</MenuItem>
                                <MenuItem value="observed">Observed Device</MenuItem>
                                <MenuItem value="registered">Registered Device</MenuItem>
                                <MenuItem value="blocked">Blocked Device</MenuItem>
                            </TextField>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setLinkModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Confirm Link</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
