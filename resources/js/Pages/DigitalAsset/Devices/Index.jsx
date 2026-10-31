import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
    Pagination,
    Snackbar,
    Alert,
    Divider,
    Stack,
    InputAdornment
} from '@mui/material';
import {
    Add as AddIcon,
    Edit as EditIcon,
    Delete as DeleteIcon,
    Visibility as VisibilityIcon,
    Link as LinkIcon,
    LinkOff as LinkOffIcon,
    Search as SearchIcon,
    Clear as ClearIcon,
    Devices as DevicesIcon,
    Computer as ComputerIcon,
    CheckCircle as CheckCircleIcon,
    Warning as WarningIcon
} from '@mui/icons-material';

export default function DevicesIndex({ devices, officeAssets = [], accounts = [], filters = {}, stats = {} }) {
    const { auth = {} } = usePage().props;
    const can = auth?.can || {};

    // ── Search & Filter State ───────────────────────────────────────────────
    const [search, setSearch] = useState(filters.search || '');
    const [ownershipType, setOwnershipType] = useState(filters.ownership_type || '');
    const [deviceStatus, setDeviceStatus] = useState(filters.device_status || '');

    // ── Modal Visibility States ─────────────────────────────────────────────
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [viewModalOpen, setViewModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [linkModalOpen, setLinkModalOpen] = useState(false);
    const [unlinkModalOpen, setUnlinkModalOpen] = useState(false);

    // ── Selected Entities ───────────────────────────────────────────────────
    const [selectedDevice, setSelectedDevice] = useState(null);
    const [deviceToEdit, setDeviceToEdit] = useState(null);
    const [deviceToDelete, setDeviceToDelete] = useState(null);
    const [linkToUnlink, setLinkToUnlink] = useState(null);

    // ── Form States ─────────────────────────────────────────────────────────
    const initialCreateForm = {
        ownership_type: 'company',
        fixed_asset_id: '',
        hostname: '',
        manufacturer: '',
        model_name: '',
        model_number: '',
        serial_number: '',
        imei: '',
        operating_system: '',
        operating_system_version: '',
        device_status: 'active',
        notes: '',
        link_account_id: '',
        relationship_type: 'registered',
    };
    const [createFormData, setCreateFormData] = useState(initialCreateForm);

    const [editFormData, setEditFormData] = useState({
        ownership_type: 'company',
        fixed_asset_id: '',
        hostname: '',
        manufacturer: '',
        model_name: '',
        model_number: '',
        serial_number: '',
        imei: '',
        operating_system: '',
        operating_system_version: '',
        device_status: 'active',
        notes: '',
    });

    const [linkData, setLinkData] = useState({
        software_account_id: '',
        relationship_type: 'registered',
    });

    // ── Loading & Feedback Notification State ───────────────────────────────
    const [submitting, setSubmitting] = useState(false);
    const [snackbar, setSnackbar] = useState({
        open: false,
        message: '',
        severity: 'success',
    });

    const handleCloseSnackbar = () => {
        setSnackbar((prev) => ({ ...prev, open: false }));
    };

    // ── Filter Handlers ─────────────────────────────────────────────────────
    const triggerAutoFilter = (overrides = {}) => {
        const nextSearch = overrides.search !== undefined ? overrides.search : search;
        const nextOwnership = overrides.ownership_type !== undefined ? overrides.ownership_type : ownershipType;
        const nextStatus = overrides.device_status !== undefined ? overrides.device_status : deviceStatus;

        router.get('/digital-assets/devices', {
            search: nextSearch?.trim() || undefined,
            ownership_type: nextOwnership || undefined,
            device_status: nextStatus || undefined,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const handleResetFilters = () => {
        setSearch('');
        setOwnershipType('');
        setDeviceStatus('');
        router.get('/digital-assets/devices', {}, { preserveState: true, replace: true, preserveScroll: true });
    };

    const hasActiveFilters = Boolean(search || ownershipType || deviceStatus);

    const handlePageChange = (event, page) => {
        router.get('/digital-assets/devices', {
            search: search.trim() || undefined,
            ownership_type: ownershipType || undefined,
            device_status: deviceStatus || undefined,
            page,
        }, { preserveState: true, replace: true });
    };

    // ── CRUD: Create ────────────────────────────────────────────────────────
    const handleCreate = (e) => {
        e.preventDefault();
        setSubmitting(true);
        router.post('/digital-assets/devices', {
            ...createFormData,
            fixed_asset_id: createFormData.ownership_type === 'company' && createFormData.fixed_asset_id
                ? createFormData.fixed_asset_id
                : null,
            link_account_id: createFormData.link_account_id || null,
        }, {
            onSuccess: () => {
                setSubmitting(false);
                setCreateModalOpen(false);
                setCreateFormData(initialCreateForm);
                setSnackbar({ open: true, message: 'Hardware device registered successfully.', severity: 'success' });
            },
            onError: (errors) => {
                setSubmitting(false);
                const msg = Object.values(errors)[0] || 'Failed to register device.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── CRUD: Read / View ───────────────────────────────────────────────────
    const openViewModal = (device) => {
        setSelectedDevice(device);
        setViewModalOpen(true);
    };

    // ── CRUD: Update ────────────────────────────────────────────────────────
    const openEditModal = (device) => {
        setDeviceToEdit(device);
        setEditFormData({
            ownership_type: device.ownership_type || 'company',
            fixed_asset_id: device.fixed_asset_id || '',
            hostname: device.hostname || '',
            manufacturer: device.manufacturer || '',
            model_name: device.model_name || '',
            model_number: device.model_number || '',
            serial_number: device.serial_number || '',
            imei: device.imei || '',
            operating_system: device.operating_system || '',
            operating_system_version: device.operating_system_version || '',
            device_status: device.device_status || 'active',
            notes: device.notes || '',
        });
        setEditModalOpen(true);
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!deviceToEdit) return;
        setSubmitting(true);
        router.put(`/digital-assets/devices/${deviceToEdit.id}`, {
            ...editFormData,
            fixed_asset_id: editFormData.ownership_type === 'company' && editFormData.fixed_asset_id
                ? editFormData.fixed_asset_id
                : null,
        }, {
            onSuccess: () => {
                setSubmitting(false);
                setEditModalOpen(false);
                setDeviceToEdit(null);
                if (selectedDevice && selectedDevice.id === deviceToEdit.id) {
                    // Update the currently viewed device snapshot if modal is open
                    setSelectedDevice((prev) => ({
                        ...prev,
                        ...editFormData,
                        fixed_asset: officeAssets.find((a) => a.id === editFormData.fixed_asset_id) || null,
                    }));
                }
                setSnackbar({ open: true, message: 'Device information updated successfully.', severity: 'success' });
            },
            onError: (errors) => {
                setSubmitting(false);
                const msg = Object.values(errors)[0] || 'Failed to update device.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── CRUD: Delete ────────────────────────────────────────────────────────
    const openDeleteDialog = (device) => {
        setDeviceToDelete(device);
        setDeleteModalOpen(true);
    };

    const handleDelete = () => {
        if (!deviceToDelete) return;
        setSubmitting(true);
        router.delete(`/digital-assets/devices/${deviceToDelete.id}`, {
            onSuccess: () => {
                setSubmitting(false);
                setDeleteModalOpen(false);
                if (selectedDevice && selectedDevice.id === deviceToDelete.id) {
                    setViewModalOpen(false);
                    setSelectedDevice(null);
                }
                setDeviceToDelete(null);
                setSnackbar({ open: true, message: 'Hardware device deleted from inventory.', severity: 'success' });
            },
            onError: (errors) => {
                setSubmitting(false);
                const msg = Object.values(errors)[0] || 'Failed to delete device.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── Account Association: Link ───────────────────────────────────────────
    const openLinkModal = (device) => {
        setSelectedDevice(device);
        setLinkData({
            software_account_id: '',
            relationship_type: 'registered',
        });
        setLinkModalOpen(true);
    };

    const handleLink = (e) => {
        e.preventDefault();
        if (!selectedDevice || !linkData.software_account_id) return;
        setSubmitting(true);
        router.post(`/digital-assets/accounts/${linkData.software_account_id}/devices`, {
            software_device_id: selectedDevice.id,
            relationship_type: linkData.relationship_type,
        }, {
            onSuccess: () => {
                setSubmitting(false);
                setLinkModalOpen(false);
                setSnackbar({ open: true, message: 'Software account successfully linked to device.', severity: 'success' });
            },
            onError: (errors) => {
                setSubmitting(false);
                const msg = Object.values(errors)[0] || 'Failed to link account.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── Account Association: Unlink ─────────────────────────────────────────
    const openUnlinkModal = (link, device = null) => {
        if (device) setSelectedDevice(device);
        setLinkToUnlink(link);
        setUnlinkModalOpen(true);
    };

    const handleUnlink = () => {
        if (!linkToUnlink) return;
        setSubmitting(true);
        router.delete(`/digital-assets/account-devices/${linkToUnlink.id}`, {
            onSuccess: () => {
                setSubmitting(false);
                setUnlinkModalOpen(false);
                setLinkToUnlink(null);
                if (selectedDevice && selectedDevice.account_devices) {
                    setSelectedDevice((prev) => ({
                        ...prev,
                        account_devices: prev.account_devices.filter((ad) => ad.id !== linkToUnlink.id),
                    }));
                }
                setSnackbar({ open: true, message: 'Account unlinked from device.', severity: 'success' });
            },
            onError: (errors) => {
                setSubmitting(false);
                const msg = Object.values(errors)[0] || 'Failed to unlink account.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── Helper Badge Renderers ──────────────────────────────────────────────
    const getStatusChip = (status) => {
        switch (status) {
            case 'active':
                return <Chip size="small" label="active" color="success" sx={{ textTransform: 'capitalize' }} />;
            case 'lost':
                return <Chip size="small" label="lost" color="error" sx={{ textTransform: 'capitalize' }} />;
            case 'retired':
                return <Chip size="small" label="retired" variant="outlined" sx={{ textTransform: 'capitalize' }} />;
            case 'blocked':
                return <Chip size="small" label="blocked" color="error" variant="outlined" sx={{ textTransform: 'capitalize' }} />;
            default:
                return <Chip size="small" label={status || 'unknown'} color="warning" sx={{ textTransform: 'capitalize' }} />;
        }
    };

    const getOwnershipChip = (type) => {
        switch (type) {
            case 'company':
                return <Chip size="small" label="company" color="primary" variant="outlined" />;
            case 'personal':
                return <Chip size="small" label="personal" color="secondary" variant="outlined" />;
            default:
                return <Chip size="small" label={type || 'other'} variant="outlined" />;
        }
    };

    const deviceList = devices?.data || [];

    return (
        <AsideLayout title="Hardware Device Inventory">
            <Head title="Devices - Digital Asset" />

            {/* Header Section */}
            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Hardware Device Inventory
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Devices linked to software accounts, tracking company-owned assets and personal workstations.
                    </Typography>
                </Box>
                {can?.hardwareDeviceCreate !== false && (
                    <Button
                        variant="contained"
                        startIcon={<AddIcon />}
                        onClick={() => setCreateModalOpen(true)}
                        sx={{ textTransform: 'none', borderRadius: 2, px: 2.5, fontWeight: 600 }}
                    >
                        Register Device
                    </Button>
                )}
            </Box>

            {/* Metrics Overview Cards */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                TOTAL REGISTERED
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5 }}>
                                {stats?.total ?? devices?.total ?? 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                COMPANY OWNED
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'primary.main' }}>
                                {stats?.company ?? 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                BYOD / PERSONAL
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'secondary.main' }}>
                                {stats?.personal ?? 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                ACTIVE DEVICES
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'success.main' }}>
                                {stats?.active ?? 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Search & Filter Bar */}
            <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1 }}>
                <CardContent sx={{ p: 2.5, pb: '20px !important' }}>
                    <Box
                        sx={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: 2,
                            alignItems: 'center',
                        }}
                    >
                        <TextField
                            size="small"
                            placeholder="Search by hostname, manufacturer, model, serial #..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && triggerAutoFilter({ search: e.target.value })}
                            sx={{
                                flex: { xs: '1 1 100%', md: '1 1 320px' },
                                minWidth: { xs: '100%', sm: 280 },
                            }}
                            InputProps={{
                                startAdornment: (
                                    <InputAdornment position="start">
                                        <SearchIcon fontSize="small" color="action" />
                                    </InputAdornment>
                                ),
                            }}
                        />

                        <TextField
                            select
                            size="small"
                            label="Ownership"
                            value={ownershipType}
                            onChange={(e) => {
                                setOwnershipType(e.target.value);
                                triggerAutoFilter({ ownership_type: e.target.value });
                            }}
                            sx={{
                                width: { xs: '100%', sm: 220, md: 240 },
                                minWidth: 200,
                            }}
                        >
                            <MenuItem value="">All Ownership Types</MenuItem>
                            <MenuItem value="company">Company-Owned</MenuItem>
                            <MenuItem value="personal">Personal (BYOD)</MenuItem>
                            <MenuItem value="other">Other / Contractor</MenuItem>
                        </TextField>

                        <TextField
                            select
                            size="small"
                            label="Status"
                            value={deviceStatus}
                            onChange={(e) => {
                                setDeviceStatus(e.target.value);
                                triggerAutoFilter({ device_status: e.target.value });
                            }}
                            sx={{
                                width: { xs: '100%', sm: 180, md: 200 },
                                minWidth: 170,
                            }}
                        >
                            <MenuItem value="">All Statuses</MenuItem>
                            <MenuItem value="active">Active</MenuItem>
                            <MenuItem value="lost">Lost</MenuItem>
                            <MenuItem value="retired">Retired</MenuItem>
                            <MenuItem value="blocked">Blocked</MenuItem>
                            <MenuItem value="unknown">Unknown</MenuItem>
                        </TextField>

                        {hasActiveFilters && (
                            <Box sx={{ ml: { md: 'auto' } }}>
                                <Button
                                    variant="outlined"
                                    size="small"
                                    color="inherit"
                                    onClick={handleResetFilters}
                                    sx={{
                                        height: 40,
                                        px: 2.5,
                                        textTransform: 'none',
                                        minWidth: 85,
                                        borderColor: 'divider',
                                    }}
                                >
                                    Reset
                                </Button>
                            </Box>
                        )}
                    </Box>
                </CardContent>
            </Card>

            {/* Inventory Table */}
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
                            <TableCell align="right" sx={{ fontWeight: 700, minWidth: 160 }}>Action</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {deviceList.length > 0 ? (
                            deviceList.map((dev) => (
                                <TableRow key={dev.id} hover>
                                    <TableCell>
                                        <Typography
                                            variant="body2"
                                            onClick={() => openViewModal(dev)}
                                            sx={{
                                                fontWeight: 600,
                                                cursor: 'pointer',
                                                '&:hover': { color: 'primary.main', textDecoration: 'underline' }
                                            }}
                                        >
                                            {dev.hostname || 'Unnamed Device'}
                                        </Typography>
                                        {dev.serial_number && (
                                            <Typography variant="caption" color="text.secondary" display="block">
                                                S/N: {dev.serial_number}
                                            </Typography>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2">
                                            {[dev.manufacturer, dev.model_name].filter(Boolean).join(' ') || '-'}
                                        </Typography>
                                        {dev.model_number && (
                                            <Typography variant="caption" color="text.secondary">
                                                ({dev.model_number})
                                            </Typography>
                                        )}
                                    </TableCell>
                                    <TableCell>{getOwnershipChip(dev.ownership_type)}</TableCell>
                                    <TableCell>
                                        {dev.fixed_asset ? (
                                            <Typography variant="body2" sx={{ fontWeight: 500 }}>
                                                {dev.fixed_asset.name}
                                            </Typography>
                                        ) : dev.ownership_type === 'personal' ? (
                                            <Typography variant="body2" color="text.secondary">Personal</Typography>
                                        ) : (
                                            <Typography variant="body2" color="text.secondary">Unlinked</Typography>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2">
                                            {[dev.operating_system, dev.operating_system_version].filter(Boolean).join(' ') || '-'}
                                        </Typography>
                                    </TableCell>
                                    <TableCell>{getStatusChip(dev.device_status)}</TableCell>
                                    <TableCell>
                                        {dev.account_devices && dev.account_devices.length > 0 ? (
                                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                                {dev.account_devices.map((ad) => (
                                                    <Chip
                                                        key={ad.id}
                                                        size="small"
                                                        label={ad.account?.account_name || 'Account'}
                                                        onDelete={() => openUnlinkModal(ad, dev)}
                                                        deleteIcon={
                                                            <Tooltip title="Unlink this account">
                                                                <ClearIcon fontSize="small" />
                                                            </Tooltip>
                                                        }
                                                        sx={{ mb: 0.5 }}
                                                    />
                                                ))}
                                            </Box>
                                        ) : (
                                            <Typography variant="caption" color="text.secondary">None</Typography>
                                        )}
                                    </TableCell>
                                    <TableCell align="right">
                                        <Stack direction="row" spacing={0.5} justifyContent="flex-end" alignItems="center">
                                            <Tooltip title="View Details">
                                                <IconButton
                                                    size="small"
                                                    color="info"
                                                    onClick={() => openViewModal(dev)}
                                                >
                                                    <VisibilityIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                            <Tooltip title="Link Account">
                                                <IconButton
                                                    size="small"
                                                    color="primary"
                                                    onClick={() => openLinkModal(dev)}
                                                >
                                                    <LinkIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                            {can?.hardwareDeviceEdit !== false && (
                                                <Tooltip title="Edit Device">
                                                    <IconButton
                                                        size="small"
                                                        color="warning"
                                                        onClick={() => openEditModal(dev)}
                                                    >
                                                        <EditIcon fontSize="small" />
                                                    </IconButton>
                                                </Tooltip>
                                            )}
                                            {can?.hardwareDeviceDelete !== false && (
                                                <Tooltip title="Delete Device">
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        onClick={() => openDeleteDialog(dev)}
                                                    >
                                                        <DeleteIcon fontSize="small" />
                                                    </IconButton>
                                                </Tooltip>
                                            )}
                                        </Stack>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 4 }}>
                                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 1 }}>
                                        <ComputerIcon sx={{ fontSize: 44, color: 'text.disabled' }} />
                                        <Typography color="text.secondary" sx={{ fontWeight: 500 }}>
                                            No hardware devices found matching your criteria.
                                        </Typography>
                                        {(search || ownershipType || deviceStatus) && (
                                            <Button size="small" onClick={handleResetFilters} sx={{ textTransform: 'none' }}>
                                                Clear filter conditions
                                            </Button>
                                        )}
                                    </Box>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                {/* Pagination Footer */}
                {devices && (
                    <Box sx={{ p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 1, borderTop: 1, borderColor: 'divider' }}>
                        <Typography variant="body2" color="text.secondary">
                            Showing {devices.from || 0} to {devices.to || 0} of {devices.total || 0} hardware devices
                        </Typography>
                        {devices.last_page > 1 && (
                            <Pagination
                                count={devices.last_page}
                                page={devices.current_page}
                                onChange={handlePageChange}
                                color="primary"
                                shape="rounded"
                                size="small"
                            />
                        )}
                    </Box>
                )}
            </TableContainer>

            {/* ── MODAL 1: Register Device (Create) ─────────────────────────────────── */}
            <Dialog open={createModalOpen} onClose={() => !submitting && setCreateModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleCreate}>
                    <DialogTitle sx={{ fontWeight: 700, display: 'flex', alignItems: 'center', gap: 1 }}>
                        <AddIcon color="primary" /> Register Hardware Device
                    </DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Ownership Type"
                                value={createFormData.ownership_type}
                                onChange={(e) => setCreateFormData({ ...createFormData, ownership_type: e.target.value })}
                            >
                                <MenuItem value="company">Company-Owned Device</MenuItem>
                                <MenuItem value="personal">Personal Device (BYOD)</MenuItem>
                                <MenuItem value="other">Other / Contractor Device</MenuItem>
                            </TextField>

                            {createFormData.ownership_type === 'company' && (
                                <TextField
                                    select
                                    fullWidth
                                    label="Link to Office Fixed Asset Master"
                                    value={createFormData.fixed_asset_id}
                                    onChange={(e) => setCreateFormData({ ...createFormData, fixed_asset_id: e.target.value })}
                                    helperText="Optional reference to physical asset inventory register"
                                >
                                    <MenuItem value="">None / Not Linked</MenuItem>
                                    {officeAssets.map((asset) => (
                                        <MenuItem key={asset.id} value={asset.id}>
                                            {asset.name} {asset.cost ? `($${asset.cost})` : ''}
                                        </MenuItem>
                                    ))}
                                </TextField>
                            )}

                            <TextField
                                required
                                fullWidth
                                label="Hostname / Device Name"
                                placeholder="e.g. IT-SEC-LAPTOP-01 or Ko Nay Lwin"
                                value={createFormData.hostname}
                                onChange={(e) => setCreateFormData({ ...createFormData, hostname: e.target.value })}
                            />

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Manufacturer"
                                        placeholder="e.g. Apple, Dell, Lenovo"
                                        value={createFormData.manufacturer}
                                        onChange={(e) => setCreateFormData({ ...createFormData, manufacturer: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Model Name"
                                        placeholder="e.g. Apple IP Pro, MacBook Pro 16"
                                        value={createFormData.model_name}
                                        onChange={(e) => setCreateFormData({ ...createFormData, model_name: e.target.value })}
                                    />
                                </Grid>
                            </Grid>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Model Number"
                                        placeholder="e.g. A2485"
                                        value={createFormData.model_number}
                                        onChange={(e) => setCreateFormData({ ...createFormData, model_number: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        required
                                        fullWidth
                                        label="Serial Number *"
                                        placeholder="e.g. C02G41ABMD6M"
                                        value={createFormData.serial_number}
                                        onChange={(e) => setCreateFormData({ ...createFormData, serial_number: e.target.value })}
                                        helperText="Required and unique hardware serial number"
                                    />
                                </Grid>
                            </Grid>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="IMEI (Mobile / Cellular)"
                                        placeholder="e.g. 352099001761481"
                                        value={createFormData.imei}
                                        onChange={(e) => setCreateFormData({ ...createFormData, imei: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        select
                                        fullWidth
                                        label="Device Status"
                                        value={createFormData.device_status}
                                        onChange={(e) => setCreateFormData({ ...createFormData, device_status: e.target.value })}
                                    >
                                        <MenuItem value="active">Active</MenuItem>
                                        <MenuItem value="lost">Lost</MenuItem>
                                        <MenuItem value="retired">Retired</MenuItem>
                                        <MenuItem value="blocked">Blocked</MenuItem>
                                        <MenuItem value="unknown">Unknown</MenuItem>
                                    </TextField>
                                </Grid>
                            </Grid>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Operating System"
                                        placeholder="e.g. macOS, Windows 11, iOS"
                                        value={createFormData.operating_system}
                                        onChange={(e) => setCreateFormData({ ...createFormData, operating_system: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="OS Version"
                                        placeholder="e.g. 15.1, 23H2"
                                        value={createFormData.operating_system_version}
                                        onChange={(e) => setCreateFormData({ ...createFormData, operating_system_version: e.target.value })}
                                    />
                                </Grid>
                            </Grid>

                            <Divider sx={{ my: 0.5 }}>
                                <Typography variant="caption" color="text.secondary">
                                    Initial Account Association (Optional)
                                </Typography>
                            </Divider>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={7}>
                                    <TextField
                                        select
                                        fullWidth
                                        label="Link to Software Account"
                                        value={createFormData.link_account_id}
                                        onChange={(e) => setCreateFormData({ ...createFormData, link_account_id: e.target.value })}
                                    >
                                        <MenuItem value="">Do not link an account initially</MenuItem>
                                        {accounts.map((acc) => (
                                            <MenuItem key={acc.id} value={acc.id}>
                                                {acc.account_name} ({acc.username})
                                            </MenuItem>
                                        ))}
                                    </TextField>
                                </Grid>
                                <Grid item xs={12} sm={5}>
                                    <TextField
                                        select
                                        fullWidth
                                        disabled={!createFormData.link_account_id}
                                        label="Relationship"
                                        value={createFormData.relationship_type}
                                        onChange={(e) => setCreateFormData({ ...createFormData, relationship_type: e.target.value })}
                                    >
                                        <MenuItem value="authorized">Authorized</MenuItem>
                                        <MenuItem value="registered">Registered</MenuItem>
                                        <MenuItem value="observed">Observed</MenuItem>
                                        <MenuItem value="blocked">Blocked</MenuItem>
                                    </TextField>
                                </Grid>
                            </Grid>

                            <TextField
                                fullWidth
                                multiline
                                rows={2}
                                label="Notes"
                                placeholder="Device location, assigned user, or special security conditions..."
                                value={createFormData.notes}
                                onChange={(e) => setCreateFormData({ ...createFormData, notes: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCreateModalOpen(false)} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="contained" disabled={submitting}>
                            {submitting ? 'Registering...' : 'Register Device'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ── MODAL 2: Edit Device (Update) ─────────────────────────────────────── */}
            <Dialog open={editModalOpen} onClose={() => !submitting && setEditModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleUpdate}>
                    <DialogTitle sx={{ fontWeight: 700, display: 'flex', alignItems: 'center', gap: 1 }}>
                        <EditIcon color="warning" /> Edit Device: {deviceToEdit?.hostname}
                    </DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Ownership Type"
                                value={editFormData.ownership_type}
                                onChange={(e) => setEditFormData({ ...editFormData, ownership_type: e.target.value })}
                            >
                                <MenuItem value="company">Company-Owned Device</MenuItem>
                                <MenuItem value="personal">Personal Device (BYOD)</MenuItem>
                                <MenuItem value="other">Other / Contractor Device</MenuItem>
                            </TextField>

                            {editFormData.ownership_type === 'company' && (
                                <TextField
                                    select
                                    fullWidth
                                    label="Link to Office Fixed Asset Master"
                                    value={editFormData.fixed_asset_id}
                                    onChange={(e) => setEditFormData({ ...editFormData, fixed_asset_id: e.target.value })}
                                    helperText="Reference to physical asset inventory register"
                                >
                                    <MenuItem value="">None / Not Linked</MenuItem>
                                    {officeAssets.map((asset) => (
                                        <MenuItem key={asset.id} value={asset.id}>
                                            {asset.name} {asset.cost ? `($${asset.cost})` : ''}
                                        </MenuItem>
                                    ))}
                                </TextField>
                            )}

                            <TextField
                                required
                                fullWidth
                                label="Hostname / Device Name"
                                value={editFormData.hostname}
                                onChange={(e) => setEditFormData({ ...editFormData, hostname: e.target.value })}
                            />

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Manufacturer"
                                        value={editFormData.manufacturer}
                                        onChange={(e) => setEditFormData({ ...editFormData, manufacturer: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Model Name"
                                        value={editFormData.model_name}
                                        onChange={(e) => setEditFormData({ ...editFormData, model_name: e.target.value })}
                                    />
                                </Grid>
                            </Grid>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Model Number"
                                        value={editFormData.model_number}
                                        onChange={(e) => setEditFormData({ ...editFormData, model_number: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        required
                                        fullWidth
                                        label="Serial Number *"
                                        value={editFormData.serial_number}
                                        onChange={(e) => setEditFormData({ ...editFormData, serial_number: e.target.value })}
                                        helperText="Required and unique hardware serial number"
                                    />
                                </Grid>
                            </Grid>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="IMEI (Mobile / Cellular)"
                                        value={editFormData.imei}
                                        onChange={(e) => setEditFormData({ ...editFormData, imei: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        select
                                        fullWidth
                                        label="Device Status"
                                        value={editFormData.device_status}
                                        onChange={(e) => setEditFormData({ ...editFormData, device_status: e.target.value })}
                                    >
                                        <MenuItem value="active">Active</MenuItem>
                                        <MenuItem value="lost">Lost</MenuItem>
                                        <MenuItem value="retired">Retired</MenuItem>
                                        <MenuItem value="blocked">Blocked</MenuItem>
                                        <MenuItem value="unknown">Unknown</MenuItem>
                                    </TextField>
                                </Grid>
                            </Grid>

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="Operating System"
                                        value={editFormData.operating_system}
                                        onChange={(e) => setEditFormData({ ...editFormData, operating_system: e.target.value })}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        fullWidth
                                        label="OS Version"
                                        value={editFormData.operating_system_version}
                                        onChange={(e) => setEditFormData({ ...editFormData, operating_system_version: e.target.value })}
                                    />
                                </Grid>
                            </Grid>

                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                label="Notes"
                                value={editFormData.notes}
                                onChange={(e) => setEditFormData({ ...editFormData, notes: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setEditModalOpen(false)} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="contained" color="warning" disabled={submitting}>
                            {submitting ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ── MODAL 3: View Device Details (Read) ───────────────────────────────── */}
            <Dialog open={viewModalOpen} onClose={() => setViewModalOpen(false)} maxWidth="md" fullWidth>
                {selectedDevice && (
                    <>
                        <DialogTitle sx={{ fontWeight: 700, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <DevicesIcon color="primary" />
                                <Box>
                                    <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                        {selectedDevice.hostname || 'Device Details'}
                                    </Typography>
                                    <Typography variant="caption" color="text.secondary">
                                        ID #{selectedDevice.id} • Registered {new Date(selectedDevice.created_at).toLocaleDateString()}
                                    </Typography>
                                </Box>
                            </Box>
                            <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
                                {getOwnershipChip(selectedDevice.ownership_type)}
                                {getStatusChip(selectedDevice.device_status)}
                            </Box>
                        </DialogTitle>
                        <DialogContent dividers>
                            <Grid container spacing={3}>
                                {/* Device Hardware & Specs */}
                                <Grid item xs={12} md={6}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.5, color: 'primary.main' }}>
                                        HARDWARE SPECIFICATIONS
                                    </Typography>
                                    <Table size="small">
                                        <TableBody>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600, width: 140 }}>Manufacturer</TableCell>
                                                <TableCell>{selectedDevice.manufacturer || '-'}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Model Name</TableCell>
                                                <TableCell>{selectedDevice.model_name || '-'}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Model Number</TableCell>
                                                <TableCell>{selectedDevice.model_number || '-'}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Serial Number</TableCell>
                                                <TableCell sx={{ fontFamily: 'monospace' }}>{selectedDevice.serial_number || '-'}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>IMEI</TableCell>
                                                <TableCell sx={{ fontFamily: 'monospace' }}>{selectedDevice.imei || '-'}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Operating System</TableCell>
                                                <TableCell>
                                                    {[selectedDevice.operating_system, selectedDevice.operating_system_version].filter(Boolean).join(' ') || '-'}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </Grid>

                                {/* Asset Ownership & Inventory Details */}
                                <Grid item xs={12} md={6}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.5, color: 'primary.main' }}>
                                        ASSET & ASSIGNMENT DETAILS
                                    </Typography>
                                    <Table size="small">
                                        <TableBody>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600, width: 140 }}>Ownership</TableCell>
                                                <TableCell sx={{ textTransform: 'capitalize' }}>{selectedDevice.ownership_type}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Fixed Asset Link</TableCell>
                                                <TableCell>
                                                    {selectedDevice.fixed_asset ? selectedDevice.fixed_asset.name : 'Not Linked / Personal'}
                                                </TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Asset Code Snapshot</TableCell>
                                                <TableCell>{selectedDevice.asset_code_snapshot || '-'}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>First Verified</TableCell>
                                                <TableCell>
                                                    {selectedDevice.first_verified_at ? new Date(selectedDevice.first_verified_at).toLocaleString() : 'Not recorded'}
                                                </TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell sx={{ fontWeight: 600 }}>Last Updated</TableCell>
                                                <TableCell>
                                                    {new Date(selectedDevice.updated_at).toLocaleString()}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>

                                    {selectedDevice.notes && (
                                        <Box sx={{ mt: 2, p: 1.5, bgcolor: 'action.hover', borderRadius: 1 }}>
                                            <Typography variant="caption" sx={{ fontWeight: 700, display: 'block', mb: 0.5 }}>
                                                Notes
                                            </Typography>
                                            <Typography variant="body2" sx={{ whiteSpace: 'pre-line' }}>
                                                {selectedDevice.notes}
                                            </Typography>
                                        </Box>
                                    )}
                                </Grid>

                                {/* Linked Software Accounts Section */}
                                <Grid item xs={12}>
                                    <Divider sx={{ my: 1 }} />
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1.5, mt: 1 }}>
                                        <Typography variant="subtitle2" sx={{ fontWeight: 700, color: 'primary.main' }}>
                                            ASSOCIATED SOFTWARE ACCOUNTS ({selectedDevice.account_devices?.length || 0})
                                        </Typography>
                                        <Button
                                            size="small"
                                            startIcon={<LinkIcon />}
                                            onClick={() => openLinkModal(selectedDevice)}
                                            sx={{ textTransform: 'none' }}
                                        >
                                            Link Account
                                        </Button>
                                    </Box>

                                    {selectedDevice.account_devices && selectedDevice.account_devices.length > 0 ? (
                                        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 1.5 }}>
                                            <Table size="small">
                                                <TableHead sx={{ bgcolor: 'action.hover' }}>
                                                    <TableRow>
                                                        <TableCell sx={{ fontWeight: 700 }}>Software Account</TableCell>
                                                        <TableCell sx={{ fontWeight: 700 }}>Platform</TableCell>
                                                        <TableCell sx={{ fontWeight: 700 }}>Relationship</TableCell>
                                                        <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                                        <TableCell sx={{ fontWeight: 700 }}>Linked Date</TableCell>
                                                        <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {selectedDevice.account_devices.map((ad) => (
                                                        <TableRow key={ad.id} hover>
                                                            <TableCell sx={{ fontWeight: 600 }}>
                                                                {ad.account?.account_name}
                                                                <Typography variant="caption" color="text.secondary" display="block">
                                                                    {ad.account?.username}
                                                                </Typography>
                                                            </TableCell>
                                                            <TableCell>{ad.account?.platform?.name || '-'}</TableCell>
                                                            <TableCell>
                                                                <Chip size="small" label={ad.relationship_type} variant="outlined" />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Chip
                                                                    size="small"
                                                                    label={ad.status || 'active'}
                                                                    color={ad.status === 'active' ? 'success' : 'default'}
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                {ad.created_at ? new Date(ad.created_at).toLocaleDateString() : '-'}
                                                            </TableCell>
                                                            <TableCell align="right">
                                                                <Tooltip title="Unlink this account">
                                                                    <IconButton
                                                                        size="small"
                                                                        color="error"
                                                                        onClick={() => openUnlinkModal(ad, selectedDevice)}
                                                                    >
                                                                        <LinkOffIcon fontSize="small" />
                                                                    </IconButton>
                                                                </Tooltip>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                    ) : (
                                        <Box sx={{ p: 2, textAlign: 'center', bgcolor: 'action.hover', borderRadius: 1 }}>
                                            <Typography variant="body2" color="text.secondary">
                                                No software accounts are currently linked to this hardware device.
                                            </Typography>
                                        </Box>
                                    )}
                                </Grid>
                            </Grid>
                        </DialogContent>
                        <DialogActions sx={{ p: 2, justifyContent: 'space-between' }}>
                            <Button
                                color="error"
                                startIcon={<DeleteIcon />}
                                onClick={() => {
                                    openDeleteDialog(selectedDevice);
                                }}
                            >
                                Delete Device
                            </Button>
                            <Box sx={{ display: 'flex', gap: 1 }}>
                                <Button
                                    variant="outlined"
                                    color="warning"
                                    startIcon={<EditIcon />}
                                    onClick={() => {
                                        openEditModal(selectedDevice);
                                    }}
                                >
                                    Edit Device
                                </Button>
                                <Button onClick={() => setViewModalOpen(false)}>
                                    Close
                                </Button>
                            </Box>
                        </DialogActions>
                    </>
                )}
            </Dialog>

            {/* ── MODAL 4: Delete Confirmation (Delete) ─────────────────────────────── */}
            <Dialog open={deleteModalOpen} onClose={() => !submitting && setDeleteModalOpen(false)} maxWidth="xs" fullWidth>
                <DialogTitle sx={{ fontWeight: 700, color: 'error.main', display: 'flex', alignItems: 'center', gap: 1 }}>
                    <WarningIcon color="error" /> Delete Hardware Device
                </DialogTitle>
                <DialogContent dividers>
                    <Typography variant="body1" sx={{ mb: 1 }}>
                        Are you sure you want to delete hardware device <strong>{deviceToDelete?.hostname || 'this device'}</strong>?
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        This will remove the device from inventory and cleanly unlink any associated software accounts. This action cannot be undone.
                    </Typography>
                </DialogContent>
                <DialogActions sx={{ p: 2 }}>
                    <Button onClick={() => setDeleteModalOpen(false)} disabled={submitting}>
                        Cancel
                    </Button>
                    <Button onClick={handleDelete} variant="contained" color="error" disabled={submitting}>
                        {submitting ? 'Deleting...' : 'Delete Device'}
                    </Button>
                </DialogActions>
            </Dialog>

            {/* ── MODAL 5: Link Account ─────────────────────────────────────────────── */}
            <Dialog open={linkModalOpen} onClose={() => !submitting && setLinkModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleLink}>
                    <DialogTitle sx={{ fontWeight: 700, display: 'flex', alignItems: 'center', gap: 1 }}>
                        <LinkIcon color="primary" /> Associate Device with Software Account
                    </DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <Box sx={{ p: 1.5, bgcolor: 'action.hover', borderRadius: 1.5 }}>
                                <Typography variant="caption" color="text.secondary" display="block">
                                    DEVICE TARGET
                                </Typography>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                                    {selectedDevice?.hostname || 'Selected Device'}
                                </Typography>
                                <Typography variant="caption" color="text.secondary">
                                    {[selectedDevice?.manufacturer, selectedDevice?.model_name].filter(Boolean).join(' ')}
                                </Typography>
                            </Box>

                            <TextField
                                select
                                required
                                fullWidth
                                label="Software Account"
                                value={linkData.software_account_id}
                                onChange={(e) => setLinkData({ ...linkData, software_account_id: e.target.value })}
                            >
                                <MenuItem value="">Select an active software account</MenuItem>
                                {accounts.map((acc) => (
                                    <MenuItem key={acc.id} value={acc.id}>
                                        {acc.account_name} ({acc.username})
                                    </MenuItem>
                                ))}
                            </TextField>

                            <TextField
                                select
                                fullWidth
                                label="Relationship Type"
                                value={linkData.relationship_type}
                                onChange={(e) => setLinkData({ ...linkData, relationship_type: e.target.value })}
                            >
                                <MenuItem value="authorized">Authorized Device (Verified corporate workstation)</MenuItem>
                                <MenuItem value="registered">Registered Device (Self-enrolled / BYOD)</MenuItem>
                                <MenuItem value="observed">Observed Device (Seen accessing service)</MenuItem>
                                <MenuItem value="blocked">Blocked Device (Access forbidden)</MenuItem>
                            </TextField>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setLinkModalOpen(false)} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="contained" disabled={submitting || !linkData.software_account_id}>
                            {submitting ? 'Linking...' : 'Confirm Link'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ── MODAL 6: Unlink Account Confirmation ──────────────────────────────── */}
            <Dialog open={unlinkModalOpen} onClose={() => !submitting && setUnlinkModalOpen(false)} maxWidth="xs" fullWidth>
                <DialogTitle sx={{ fontWeight: 700, color: 'warning.main', display: 'flex', alignItems: 'center', gap: 1 }}>
                    <LinkOffIcon color="warning" /> Unlink Software Account
                </DialogTitle>
                <DialogContent dividers>
                    <Typography variant="body1" sx={{ mb: 1 }}>
                        Are you sure you want to remove the link to software account <strong>{linkToUnlink?.account?.account_name || 'this account'}</strong> from this device?
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        The account and hardware device will both remain in the system, but their access association will be severed.
                    </Typography>
                </DialogContent>
                <DialogActions sx={{ p: 2 }}>
                    <Button onClick={() => setUnlinkModalOpen(false)} disabled={submitting}>
                        Cancel
                    </Button>
                    <Button onClick={handleUnlink} variant="contained" color="warning" disabled={submitting}>
                        {submitting ? 'Unlinking...' : 'Confirm Unlink'}
                    </Button>
                </DialogActions>
            </Dialog>

            {/* Notification Snackbar */}
            <Snackbar
                open={snackbar.open}
                autoHideDuration={4000}
                onClose={handleCloseSnackbar}
                anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            >
                <Alert onClose={handleCloseSnackbar} severity={snackbar.severity} variant="filled" sx={{ width: '100%' }}>
                    {snackbar.message}
                </Alert>
            </Snackbar>
        </AsideLayout>
    );
}
