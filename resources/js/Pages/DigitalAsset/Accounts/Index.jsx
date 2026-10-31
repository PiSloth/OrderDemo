import React, { useState, useMemo, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
    Box,
    Card,
    CardContent,
    Typography,
    Grid,
    TextField,
    MenuItem,
    Button,
    Chip,
    Table,
    TableHead,
    TableBody,
    TableRow,
    TableCell,
    TableContainer,
    Paper,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    IconButton,
    Tooltip,
    Tabs,
    Tab,
    Pagination,
    Snackbar,
    Alert,
    Divider,
    Stack,
    InputAdornment,
    Autocomplete,
    Avatar
} from '@mui/material';
import {
    Add as AddIcon,
    Visibility as VisibilityIcon,
    Security as SecurityIcon,
    CheckCircle as CheckCircleIcon,
    Warning as WarningIcon,
    Error as ErrorIcon,
    Person as PersonIcon,
    Hub as HubIcon,
    PictureAsPdf as PictureAsPdfIcon,
    Search as SearchIcon,
    Clear as ClearIcon,
    Close as CloseIcon,
    Block as BlockIcon,
    LockOpen as LockOpenIcon,
    AccountCircle as AccountCircleIcon,
    Email as EmailIcon,
    Phone as PhoneIcon,
    Business as BusinessIcon,
    Badge as BadgeIcon,
    Star as StarIcon,
    Edit as EditIcon,
    Delete as DeleteIcon
} from '@mui/icons-material';
import UserSelectModal from '@/components/IT/UserSelectModal';
import {
    exportPicAccessReportPdf,
    exportAccountHoldersReportPdf
} from '../../../utils/digitalAssetPicPdfExport';

const selectMenuProps = {
    anchorOrigin: {
        vertical: 'bottom',
        horizontal: 'left',
    },
    transformOrigin: {
        vertical: 'top',
        horizontal: 'left',
    },
    PaperProps: {
        sx: {
            mt: 0.5,
            boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.15)',
        }
    }
};

export default function AccountsIndex({
    accounts,
    platforms = [],
    filters = {},
    stats = {},
    users = [],
    departments = [],
    allAccounts = [],
    allPics = [],
    permissions = {},
}) {
    const { auth = {} } = usePage().props;
    const authCan = auth?.can || {};
    const canCreate = permissions?.canCreate ?? authCan?.accountCreate ?? true;
    const canEdit = permissions?.canEdit ?? authCan?.accountEdit ?? true;
    const canDelete = permissions?.canDelete ?? authCan?.accountDelete ?? true;

    // ── Tab Navigation State ────────────────────────────────────────────────
    const [currentTab, setCurrentTab] = useState(0);

    // ── Tab 0: Accounts Catalog Filter State ────────────────────────────────
    const [search, setSearch] = useState(filters.search || '');
    const [platformId, setPlatformId] = useState(filters.platform_id || '');
    const [lifecycleStatus, setLifecycleStatus] = useState(filters.lifecycle_status || '');
    const [securityStatus, setSecurityStatus] = useState(filters.security_status || '');

    // ── Tab 1: PIC Access Report (By Person) State ──────────────────────────
    const [selectedPicUserId, setSelectedPicUserId] = useState('');
    const [userPickerOpen, setUserPickerOpen] = useState(false);
    const [picRecordStatusFilter, setPicRecordStatusFilter] = useState('all');

    // ── Tab 2: Account Custodians Report (By Account) State ──────────────────
    const [selectedAccountId, setSelectedAccountId] = useState('');
    const [accountPicStatusFilter, setAccountPicStatusFilter] = useState('all');

    // ── Dialog & Interaction States ─────────────────────────────────────────
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [formData, setFormData] = useState({
        software_platform_id: platforms.length > 0 ? platforms[0].id : '',
        account_name: '',
        username: '',
    });

    // ── Edit Account State ──────────────────────────────────────────────────
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [accountToEdit, setAccountToEdit] = useState(null);
    const [editFormData, setEditFormData] = useState({
        account_name: '',
        username: '',
        lifecycle_status: 'active',
    });
    const [updatingAccount, setUpdatingAccount] = useState(false);

    const handleOpenEditModal = (account) => {
        setAccountToEdit(account);
        setEditFormData({
            account_name: account.account_name || '',
            username: account.username || '',
            lifecycle_status: account.lifecycle_status || 'active',
        });
        setEditModalOpen(true);
    };

    const handleUpdateAccount = (e) => {
        e.preventDefault();
        if (!accountToEdit) return;
        setUpdatingAccount(true);
        router.put(`/digital-assets/accounts/${accountToEdit.id}`, editFormData, {
            onSuccess: () => {
                setUpdatingAccount(false);
                setEditModalOpen(false);
                setAccountToEdit(null);
                setSnackbar({
                    open: true,
                    message: 'Software account updated successfully.',
                    severity: 'success',
                });
            },
            onError: (err) => {
                setUpdatingAccount(false);
                setSnackbar({
                    open: true,
                    message: Object.values(err)[0] || 'Failed to update account.',
                    severity: 'error',
                });
            },
        });
    };

    // ── Delete Account State ────────────────────────────────────────────────
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [accountToDelete, setAccountToDelete] = useState(null);
    const [deletingAccount, setDeletingAccount] = useState(false);

    const handleOpenDeleteModal = (account) => {
        setAccountToDelete(account);
        setDeleteModalOpen(true);
    };

    const handleDeleteAccount = () => {
        if (!accountToDelete) return;
        setDeletingAccount(true);
        router.delete(`/digital-assets/accounts/${accountToDelete.id}`, {
            onSuccess: () => {
                setDeletingAccount(false);
                setDeleteModalOpen(false);
                setAccountToDelete(null);
                setSnackbar({
                    open: true,
                    message: 'Software account deleted successfully.',
                    severity: 'success',
                });
            },
            onError: (err) => {
                setDeletingAccount(false);
                setSnackbar({
                    open: true,
                    message: Object.values(err)[0] || 'Failed to delete account.',
                    severity: 'error',
                });
            },
        });
    };

    useEffect(() => {
        if (platforms.length > 0 && !formData.software_platform_id) {
            setFormData((prev) => ({
                ...prev,
                software_platform_id: platforms[0].id,
            }));
        }
    }, [platforms]);

    const handleOpenCreateModal = () => {
        setFormData({
            software_platform_id: platforms.length > 0 ? platforms[0].id : '',
            account_name: '',
            username: '',
        });
        setCreateModalOpen(true);
    };

    const [revokeDialogOpen, setRevokeDialogOpen] = useState(false);
    const [picToRevoke, setPicToRevoke] = useState(null);
    const [revokeReason, setRevokeReason] = useState('');
    const [revoking, setRevoking] = useState(false);

    // ── PIC Detail Dialog State ─────────────────────────────────────────────
    const [picDetailModalOpen, setPicDetailModalOpen] = useState(false);
    const [selectedAccountForPics, setSelectedAccountForPics] = useState(null);
    const [picSearchQuery, setPicSearchQuery] = useState('');

    const [exportingPdf, setExportingPdf] = useState(false);
    const [snackbar, setSnackbar] = useState({
        open: false,
        message: '',
        severity: 'success',
    });

    const handleCloseSnackbar = () => {
        setSnackbar((prev) => ({ ...prev, open: false }));
    };

    // ── Filter Handlers for Tab 0 ───────────────────────────────────────────
    const triggerAutoFilter = (overrides = {}) => {
        const nextSearch = overrides.search !== undefined ? overrides.search : search;
        const nextPlatform = overrides.platform_id !== undefined ? overrides.platform_id : platformId;
        const nextLifecycle = overrides.lifecycle_status !== undefined ? overrides.lifecycle_status : lifecycleStatus;
        const nextSecurity = overrides.security_status !== undefined ? overrides.security_status : securityStatus;

        router.get('/digital-assets/accounts', {
            search: nextSearch?.trim() || undefined,
            platform_id: nextPlatform || undefined,
            lifecycle_status: nextLifecycle || undefined,
            security_status: nextSecurity || undefined,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const handleFilter = () => {
        triggerAutoFilter();
    };

    const handleResetFilters = () => {
        setSearch('');
        setPlatformId('');
        setLifecycleStatus('');
        setSecurityStatus('');
        router.get('/digital-assets/accounts', {}, { preserveState: true, replace: true });
    };

    const handlePageChange = (e, page) => {
        router.get('/digital-assets/accounts', {
            search: search.trim() || undefined,
            platform_id: platformId || undefined,
            lifecycle_status: lifecycleStatus || undefined,
            security_status: securityStatus || undefined,
            page,
        }, { preserveState: true, replace: true });
    };

    // ── Create Account Handler ──────────────────────────────────────────────
    const handleCreate = (e) => {
        e.preventDefault();
        router.post('/digital-assets/accounts', formData, {
            onSuccess: () => {
                setCreateModalOpen(false);
                setFormData({
                    software_platform_id: platforms.length > 0 ? platforms[0].id : '',
                    account_name: '',
                    username: ''
                });
                setSnackbar({
                    open: true,
                    message: 'Software account registered successfully.',
                    severity: 'success',
                });
            },
            onError: (errors) => {
                const msg = Object.values(errors)[0] || 'Failed to create account.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── Quick Revoke Handlers ───────────────────────────────────────────────
    const openRevokeModal = (pic) => {
        setPicToRevoke(pic);
        setRevokeReason('');
        setRevokeDialogOpen(true);
    };

    const handleConfirmRevoke = () => {
        if (!picToRevoke) return;
        setRevoking(true);
        router.post(`/digital-assets/pics/${picToRevoke.id}/revoke`, {
            reason: revokeReason || null,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setRevoking(false);
                setRevokeDialogOpen(false);
                setPicToRevoke(null);
                setRevokeReason('');
                setSnackbar({
                    open: true,
                    message: 'PIC status has been set to inactive / revoked.',
                    severity: 'success',
                });
            },
            onError: (errors) => {
                setRevoking(false);
                const msg = Object.values(errors)[0] || 'Failed to revoke PIC access.';
                setSnackbar({ open: true, message: msg, severity: 'error' });
            }
        });
    };

    // ── Computed Lists for Tab 1 (PIC Access Audit) ─────────────────────────
    const selectedPicUser = useMemo(() => {
        if (!selectedPicUserId) return null;
        return users.find((u) => u.id === Number(selectedPicUserId)) || null;
    }, [selectedPicUserId, users]);

    const userPicRecords = useMemo(() => {
        if (!selectedPicUserId) return [];
        return allPics.filter((p) => {
            const matchesUser = p.user_id === Number(selectedPicUserId);
            if (!matchesUser) return false;
            if (picRecordStatusFilter === 'active') return p.status === 'active';
            if (picRecordStatusFilter === 'revoked') return p.status !== 'active';
            return true;
        });
    }, [selectedPicUserId, allPics, picRecordStatusFilter]);

    // ── Computed Lists for Tab 2 (Account Custodians) ────────────────────────
    const selectedAccount = useMemo(() => {
        if (!selectedAccountId) return null;
        return allAccounts.find((a) => a.id === Number(selectedAccountId)) || null;
    }, [selectedAccountId, allAccounts]);

    const accountCustodians = useMemo(() => {
        if (!selectedAccountId) return [];
        return allPics.filter((p) => {
            const matchesAccount = p.software_account_id === Number(selectedAccountId);
            if (!matchesAccount) return false;
            if (accountPicStatusFilter === 'active') return p.status === 'active';
            if (accountPicStatusFilter === 'revoked') return p.status !== 'active';
            return true;
        });
    }, [selectedAccountId, allPics, accountPicStatusFilter]);

    // ── PDF Export Handlers ─────────────────────────────────────────────────
    const handleExportPicPdf = async () => {
        if (!selectedPicUser || userPicRecords.length === 0) return;
        setExportingPdf(true);
        try {
            await exportPicAccessReportPdf({
                user: selectedPicUser,
                records: userPicRecords,
                authUser: auth?.user,
            });
            setSnackbar({
                open: true,
                message: 'PIC access report exported to PDF.',
                severity: 'success',
            });
        } catch (err) {
            console.error('PDF export error:', err);
            setSnackbar({
                open: true,
                message: 'Failed to generate PDF report.',
                severity: 'error',
            });
        } finally {
            setExportingPdf(false);
        }
    };

    const handleExportAccountPdf = async () => {
        if (!selectedAccount || accountCustodians.length === 0) return;
        setExportingPdf(true);
        try {
            await exportAccountHoldersReportPdf({
                account: selectedAccount,
                records: accountCustodians,
                authUser: auth?.user,
            });
            setSnackbar({
                open: true,
                message: 'Account custodians report exported to PDF.',
                severity: 'success',
            });
        } catch (err) {
            console.error('PDF export error:', err);
            setSnackbar({
                open: true,
                message: 'Failed to generate PDF report.',
                severity: 'error',
            });
        } finally {
            setExportingPdf(false);
        }
    };

    // ── Badge Helpers ───────────────────────────────────────────────────────
    const getSecurityBadge = (status) => {
        switch (status) {
            case 'compliant':
                return <Chip size="small" icon={<CheckCircleIcon fontSize="small" />} label="Compliant" color="success" />;
            case 'at_risk':
                return <Chip size="small" icon={<ErrorIcon fontSize="small" />} label="At Risk" color="error" />;
            case 'needs_review':
                return <Chip size="small" icon={<WarningIcon fontSize="small" />} label="Needs Review" color="warning" />;
            default:
                return <Chip size="small" label="Unknown" variant="outlined" />;
        }
    };

    const getLifecycleBadge = (status) => {
        switch (status) {
            case 'active':
                return <Chip size="small" label="Active" color="primary" variant="outlined" />;
            case 'suspended':
                return <Chip size="small" label="Suspended" color="warning" variant="outlined" />;
            case 'retired':
                return <Chip size="small" label="Retired" color="default" variant="outlined" />;
            default:
                return <Chip size="small" label={status || 'Unknown'} />;
        }
    };

    const getPicStatusChip = (status) => {
        if (status === 'active') {
            return <Chip size="small" label="Active PIC" color="success" />;
        }
        return <Chip size="small" label="Inactive / Revoked" color="default" variant="outlined" />;
    };

    return (
        <AsideLayout title="Software Accounts">
            <Head title="Software Accounts - Digital Asset" />

            {/* Header Section */}
            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Company Software Accounts
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Inventory and security compliance tracking for third-party platforms and software systems.
                    </Typography>
                </Box>
                {canCreate && (
                    <Button
                        variant="contained"
                        startIcon={<AddIcon />}
                        onClick={handleOpenCreateModal}
                        sx={{ textTransform: 'none', borderRadius: 2, px: 2.5, fontWeight: 600 }}
                    >
                        Register Account
                    </Button>
                )}
            </Box>

            {/* Metrics Overview Cards */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                TOTAL ACCOUNTS
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5 }}>
                                {stats.total_accounts || 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                ACTIVE INVENTORY
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'primary.main' }}>
                                {stats.active_accounts || 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                COMPLIANT ACCOUNTS
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'success.main' }}>
                                {stats.compliant_accounts || 0}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                NEEDS REVIEW / AT RISK
                            </Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'error.main' }}>
                                {(stats.needs_review_accounts || 0) + (stats.at_risk_accounts || 0)}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Tab Navigation */}
            <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1 }}>
                <Tabs
                    value={currentTab}
                    onChange={(e, val) => setCurrentTab(val)}
                    indicatorColor="primary"
                    textColor="primary"
                    sx={{
                        px: 2,
                        borderBottom: 1,
                        borderColor: 'divider',
                        '& .MuiTab-root': { textTransform: 'none', fontWeight: 600, minHeight: 48 }
                    }}
                >
                    <Tab icon={<SecurityIcon />} iconPosition="start" label="Accounts Catalog" />
                    <Tab icon={<PersonIcon />} iconPosition="start" label="PIC Access Audit (By Person)" />
                    <Tab icon={<HubIcon />} iconPosition="start" label="Account Custodians (By Account)" />
                </Tabs>
            </Card>

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* TAB 0: Accounts Catalog                                            */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            {currentTab === 0 && (
                <>
                    {/* Search & Filters with proper, comfortable default widths */}
                    <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1, overflow: 'visible' }}>
                        <CardContent sx={{ pb: '16px !important', overflow: 'visible' }}>
                            <Box
                                sx={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: 2,
                                    alignItems: 'center',
                                    width: '100%',
                                    position: 'relative',
                                }}
                            >
                                <TextField
                                    size="small"
                                    placeholder="Search by account name or username..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                    InputProps={{
                                        startAdornment: (
                                            <InputAdornment position="start">
                                                <SearchIcon fontSize="small" color="action" />
                                            </InputAdornment>
                                        ),
                                    }}
                                    sx={{
                                        flex: '2 1 260px',
                                        minWidth: { xs: '100%', sm: 260 },
                                    }}
                                />

                                <Autocomplete
                                    size="small"
                                    options={[{ id: '', name: 'All Platforms' }, ...platforms]}
                                    getOptionLabel={(option) => {
                                        if (!option) return '';
                                        if (typeof option === 'string') return option;
                                        return option.name || '';
                                    }}
                                    value={
                                        platforms.find((p) => String(p.id) === String(platformId)) ||
                                        (platformId === '' ? { id: '', name: 'All Platforms' } : null)
                                    }
                                    onChange={(event, newValue) => {
                                        const nextId = newValue ? (newValue.id || '') : '';
                                        setPlatformId(nextId);
                                        triggerAutoFilter({ platform_id: nextId });
                                    }}
                                    isOptionEqualToValue={(option, value) => String(option?.id) === String(value?.id)}
                                    renderInput={(params) => (
                                        <TextField
                                            {...params}
                                            size="small"
                                            label="Platform"
                                            placeholder="Type to search platform..."
                                        />
                                    )}
                                    slotProps={{
                                        popper: {
                                            sx: {
                                                zIndex: 1300,
                                            }
                                        }
                                    }}
                                    sx={{
                                        flex: '1 1 240px',
                                        minWidth: 220,
                                    }}
                                />

                                <TextField
                                    select
                                    size="small"
                                    label="Lifecycle Status"
                                    value={lifecycleStatus}
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        setLifecycleStatus(next);
                                        triggerAutoFilter({ lifecycle_status: next });
                                    }}
                                    SelectProps={{ MenuProps: selectMenuProps }}
                                    sx={{
                                        flex: '0 1 180px',
                                        minWidth: 160,
                                    }}
                                >
                                    <MenuItem value="">All Statuses</MenuItem>
                                    <MenuItem value="active">Active</MenuItem>
                                    <MenuItem value="suspended">Suspended</MenuItem>
                                    <MenuItem value="retired">Retired</MenuItem>
                                </TextField>

                                <TextField
                                    select
                                    size="small"
                                    label="Security Status"
                                    value={securityStatus}
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        setSecurityStatus(next);
                                        triggerAutoFilter({ security_status: next });
                                    }}
                                    SelectProps={{ MenuProps: selectMenuProps }}
                                    sx={{
                                        flex: '0 1 180px',
                                        minWidth: 160,
                                    }}
                                >
                                    <MenuItem value="">All Security</MenuItem>
                                    <MenuItem value="compliant">Compliant</MenuItem>
                                    <MenuItem value="needs_review">Needs Review</MenuItem>
                                    <MenuItem value="at_risk">At Risk</MenuItem>
                                    <MenuItem value="unknown">Unknown</MenuItem>
                                </TextField>

                                {(search || platformId || lifecycleStatus || securityStatus) && (
                                    <Button
                                        variant="outlined"
                                        color="inherit"
                                        onClick={handleResetFilters}
                                        startIcon={<ClearIcon />}
                                        sx={{ height: 40, px: 2, textTransform: 'none', borderRadius: 2 }}
                                    >
                                        Reset
                                    </Button>
                                )}
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Accounts Table */}
                    <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <Table size="small">
                            <TableHead sx={{ bgcolor: 'action.hover' }}>
                                <TableRow>
                                    <TableCell sx={{ fontWeight: 700 }}>Platform</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Account Name</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Username / Identity</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Active PIC(s)</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Lifecycle</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Security Status</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Next Review</TableCell>
                                    <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {accounts.data && accounts.data.length > 0 ? (
                                    accounts.data.map((account) => (
                                        <TableRow key={account.id} hover>
                                            <TableCell sx={{ fontWeight: 600 }}>{account.platform?.name || '-'}</TableCell>
                                            <TableCell>{account.account_name}</TableCell>
                                            <TableCell>
                                                <Typography variant="body2" sx={{ fontFamily: 'monospace' }}>
                                                    {account.username}
                                                </Typography>
                                            </TableCell>
                                            <TableCell>
                                                {account.active_pics && account.active_pics.length > 0 ? (
                                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5, alignItems: 'center' }}>
                                                        {account.active_pics.slice(0, 3).map((p) => (
                                                            <Chip
                                                                key={p.id}
                                                                size="small"
                                                                icon={<PersonIcon sx={{ fontSize: '15px !important' }} />}
                                                                label={p.user?.name || 'User'}
                                                                clickable
                                                                onClick={() => {
                                                                    setSelectedAccountForPics(account);
                                                                    setPicSearchQuery('');
                                                                    setPicDetailModalOpen(true);
                                                                }}
                                                                sx={{
                                                                    cursor: 'pointer',
                                                                    '&:hover': { bgcolor: 'primary.50', borderColor: 'primary.main' }
                                                                }}
                                                            />
                                                        ))}
                                                        {account.active_pics.length > 3 && (
                                                            <Tooltip title={`Click to view all ${account.active_pics.length} PICs with user details`}>
                                                                <Chip
                                                                    size="small"
                                                                    color="primary"
                                                                    variant="outlined"
                                                                    label={`+${account.active_pics.length - 3} more`}
                                                                    clickable
                                                                    onClick={() => {
                                                                        setSelectedAccountForPics(account);
                                                                        setPicSearchQuery('');
                                                                        setPicDetailModalOpen(true);
                                                                    }}
                                                                    sx={{ fontWeight: 700, cursor: 'pointer' }}
                                                                />
                                                            </Tooltip>
                                                        )}
                                                    </Box>
                                                ) : (
                                                    <Typography variant="caption" color="text.secondary">Unassigned</Typography>
                                                )}
                                            </TableCell>
                                            <TableCell>{getLifecycleBadge(account.lifecycle_status)}</TableCell>
                                            <TableCell>{getSecurityBadge(account.security_status)}</TableCell>
                                            <TableCell>
                                                {account.next_review_at
                                                    ? new Date(account.next_review_at).toLocaleDateString()
                                                    : <Typography variant="caption" color="text.secondary">Not Scheduled</Typography>}
                                            </TableCell>
                                            <TableCell align="right">
                                                <Stack direction="row" spacing={0.5} justifyContent="flex-end">
                                                    <Tooltip title="View Security Details">
                                                        <IconButton
                                                            size="small"
                                                            component="a"
                                                            href={`/digital-assets/accounts/${account.id}`}
                                                            color="primary"
                                                        >
                                                            <VisibilityIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                    {canEdit && (
                                                        <Tooltip title="Edit Account">
                                                            <IconButton
                                                                size="small"
                                                                onClick={() => handleOpenEditModal(account)}
                                                                color="info"
                                                            >
                                                                <EditIcon fontSize="small" />
                                                            </IconButton>
                                                        </Tooltip>
                                                    )}
                                                    {canDelete && (
                                                        <Tooltip title="Delete Account">
                                                            <IconButton
                                                                size="small"
                                                                onClick={() => handleOpenDeleteModal(account)}
                                                                color="error"
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
                                            <Typography color="text.secondary">No software accounts found matching criteria.</Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination Footer */}
                        {accounts && (
                            <Box sx={{ p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 1, borderTop: 1, borderColor: 'divider' }}>
                                <Typography variant="body2" color="text.secondary">
                                    Showing {accounts.from || 0} to {accounts.to || 0} of {accounts.total || 0} software accounts
                                </Typography>
                                {accounts.last_page > 1 && (
                                    <Pagination
                                        count={accounts.last_page}
                                        page={accounts.current_page}
                                        onChange={handlePageChange}
                                        color="primary"
                                        shape="rounded"
                                        size="small"
                                    />
                                )}
                            </Box>
                        )}
                    </TableContainer>
                </>
            )}

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* TAB 1: PIC Access Report (By Person)                               */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            {currentTab === 1 && (
                <Box>
                    {/* PIC Selector & Export Bar */}
                    <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1 }}>
                        <CardContent sx={{ pb: '16px !important' }}>
                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, alignItems: 'center' }}>
                                <Box sx={{ minWidth: 320, flex: '2 1 340px' }}>
                                    <TextField
                                        fullWidth
                                        size="small"
                                        label="Person in Charge (PIC)"
                                        placeholder="Click to browse & select employee..."
                                        value={
                                            selectedPicUser
                                                ? `${selectedPicUser.name} (${[selectedPicUser.department?.name, selectedPicUser.email].filter(Boolean).join(' • ')})`
                                                : ''
                                        }
                                        onClick={() => setUserPickerOpen(true)}
                                        InputProps={{
                                            readOnly: true,
                                            startAdornment: (
                                                <InputAdornment position="start">
                                                    <PersonIcon color={selectedPicUser ? "primary" : "action"} fontSize="small" />
                                                </InputAdornment>
                                            ),
                                            endAdornment: (
                                                <InputAdornment position="end">
                                                    {selectedPicUser && (
                                                        <Tooltip title="Clear selection">
                                                            <IconButton
                                                                size="small"
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    setSelectedPicUserId('');
                                                                }}
                                                                edge="end"
                                                                sx={{ mr: 0.5 }}
                                                            >
                                                                <ClearIcon fontSize="small" />
                                                            </IconButton>
                                                        </Tooltip>
                                                    )}
                                                    <Button
                                                        size="small"
                                                        variant="outlined"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setUserPickerOpen(true);
                                                        }}
                                                        sx={{ textTransform: 'none', whiteSpace: 'nowrap', fontWeight: 600 }}
                                                    >
                                                        Browse Employee...
                                                    </Button>
                                                </InputAdornment>
                                            ),
                                        }}
                                        helperText={selectedPicUser ? (
                                            `${allPics.filter(p => p.user_id === selectedPicUser.id && p.status === 'active').length} active platform account(s)`
                                        ) : "Search employee across departments and branches"}
                                        sx={{ cursor: 'pointer', '& input': { cursor: 'pointer' } }}
                                    />
                                </Box>

                                <TextField
                                    select
                                    size="small"
                                    label="Assignment Status"
                                    value={picRecordStatusFilter}
                                    onChange={(e) => setPicRecordStatusFilter(e.target.value)}
                                    SelectProps={{ MenuProps: selectMenuProps }}
                                    sx={{ minWidth: 180, flex: '1 1 180px' }}
                                >
                                    <MenuItem value="all">All Assignments</MenuItem>
                                    <MenuItem value="active">Active Access Only</MenuItem>
                                    <MenuItem value="revoked">Inactive / Revoked Only</MenuItem>
                                </TextField>

                                <Box sx={{ ml: 'auto', display: 'flex', gap: 1 }}>
                                    <Button
                                        variant="contained"
                                        color="error"
                                        startIcon={<PictureAsPdfIcon />}
                                        onClick={handleExportPicPdf}
                                        disabled={!selectedPicUser || userPicRecords.length === 0 || exportingPdf}
                                        sx={{ height: 40, px: 2.5, textTransform: 'none', fontWeight: 600 }}
                                    >
                                        {exportingPdf ? 'Exporting...' : 'Export Report (PDF)'}
                                    </Button>
                                </Box>
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Selected PIC Overview & Records Table */}
                    {selectedPicUser ? (
                        <>
                            {/* Summary Metadata Card */}
                            <Card sx={{ mb: 3, borderRadius: 2, bgcolor: 'action.hover', border: 1, borderColor: 'divider' }}>
                                <CardContent sx={{ py: 2 }}>
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                            <AccountCircleIcon sx={{ fontSize: 44, color: 'primary.main' }} />
                                            <Box>
                                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                                    {selectedPicUser.name}
                                                </Typography>
                                                <Typography variant="body2" color="text.secondary">
                                                    {selectedPicUser.email}
                                                </Typography>
                                            </Box>
                                        </Box>
                                        <Box sx={{ display: 'flex', gap: 2 }}>
                                            <Box sx={{ textAlign: 'center', px: 2 }}>
                                                <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                                    TOTAL ASSIGNED
                                                </Typography>
                                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                                    {allPics.filter(p => p.user_id === selectedPicUser.id).length}
                                                </Typography>
                                            </Box>
                                            <Divider orientation="vertical" flexItem />
                                            <Box sx={{ textAlign: 'center', px: 2 }}>
                                                <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                                    ACTIVE ACCESS
                                                </Typography>
                                                <Typography variant="h6" sx={{ fontWeight: 700, color: 'success.main' }}>
                                                    {allPics.filter(p => p.user_id === selectedPicUser.id && p.status === 'active').length}
                                                </Typography>
                                            </Box>
                                            <Divider orientation="vertical" flexItem />
                                            <Box sx={{ textAlign: 'center', px: 2 }}>
                                                <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                                    REVOKED / INACTIVE
                                                </Typography>
                                                <Typography variant="h6" sx={{ fontWeight: 700, color: 'text.secondary' }}>
                                                    {allPics.filter(p => p.user_id === selectedPicUser.id && p.status !== 'active').length}
                                                </Typography>
                                            </Box>
                                        </Box>
                                    </Box>
                                </CardContent>
                            </Card>

                            {/* Table of Accounts Held */}
                            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700, width: 50 }}>#</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Platform</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Account Name</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Login Identity / Username</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Account Lifecycle</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>PIC Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned Date</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Revoked Info</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700, minWidth: 160 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {userPicRecords.length > 0 ? (
                                            userPicRecords.map((pic, idx) => (
                                                <TableRow key={pic.id} hover>
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 600 }}>{idx + 1}</TableCell>
                                                    <TableCell sx={{ fontWeight: 600 }}>{pic.account?.platform?.name || '-'}</TableCell>
                                                    <TableCell>
                                                        <Typography
                                                            variant="body2"
                                                            component="a"
                                                            href={`/digital-assets/accounts/${pic.account?.id}`}
                                                            sx={{ fontWeight: 600, color: 'primary.main', textDecoration: 'none', '&:hover': { textDecoration: 'underline' } }}
                                                        >
                                                            {pic.account?.account_name || 'Account'}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell sx={{ fontFamily: 'monospace' }}>{pic.account?.username || '-'}</TableCell>
                                                    <TableCell>{getLifecycleBadge(pic.account?.lifecycle_status)}</TableCell>
                                                    <TableCell>{getPicStatusChip(pic.status)}</TableCell>
                                                    <TableCell>
                                                        {pic.assigned_at ? new Date(pic.assigned_at).toLocaleDateString() : '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {pic.status !== 'active' ? (
                                                            <Box>
                                                                <Typography variant="caption" display="block">
                                                                    {pic.revoked_at ? new Date(pic.revoked_at).toLocaleDateString() : '-'}
                                                                </Typography>
                                                                {pic.revocation_reason && (
                                                                    <Typography variant="caption" color="text.secondary">
                                                                        ({pic.revocation_reason})
                                                                    </Typography>
                                                                )}
                                                            </Box>
                                                        ) : (
                                                            <Typography variant="caption" color="text.secondary">-</Typography>
                                                        )}
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        {pic.status === 'active' ? (
                                                            <Button
                                                                size="small"
                                                                variant="outlined"
                                                                color="error"
                                                                startIcon={<BlockIcon fontSize="small" />}
                                                                onClick={() => openRevokeModal(pic)}
                                                                sx={{ textTransform: 'none', fontWeight: 600 }}
                                                            >
                                                                Revoke (Inactive)
                                                            </Button>
                                                        ) : (
                                                            <Chip size="small" label="Revoked" variant="outlined" />
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={9} align="center" sx={{ py: 4 }}>
                                                    <Typography color="text.secondary">
                                                        No platform accounts found for this person matching the filter.
                                                    </Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </>
                    ) : (
                        <Card sx={{ p: 5, textAlign: 'center', borderRadius: 2, boxShadow: 1 }}>
                            <PersonIcon sx={{ fontSize: 56, color: 'text.disabled', mb: 1 }} />
                            <Typography variant="h6" color="text.secondary" sx={{ fontWeight: 600 }}>
                                Select a Person in Charge (PIC)
                            </Typography>
                            <Typography variant="body2" color="text.secondary" sx={{ maxWidth: 460, mx: 'auto', mt: 0.5, mb: 2 }}>
                                Choose an employee to view all their holding access platform accounts, inspect custodianship dates, perform quick revocation, or export a certified PDF audit report.
                            </Typography>
                            <Button
                                variant="contained"
                                startIcon={<PersonIcon />}
                                onClick={() => setUserPickerOpen(true)}
                                sx={{ textTransform: 'none', fontWeight: 600 }}
                            >
                                Browse Employee Directory
                            </Button>
                        </Card>
                    )}
                </Box>
            )}

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* TAB 2: Account Custodians (By Account)                             */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            {currentTab === 2 && (
                <Box>
                    {/* Account Selector & Export Bar */}
                    <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1, overflow: 'visible' }}>
                        <CardContent sx={{ pb: '16px !important', overflow: 'visible' }}>
                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, alignItems: 'center', position: 'relative' }}>
                                <Autocomplete
                                    size="small"
                                    options={allAccounts}
                                    getOptionLabel={(acc) => {
                                        if (!acc) return '';
                                        if (typeof acc === 'string') return acc;
                                        return `${acc.platform?.name || 'Platform'} — ${acc.account_name} (${acc.username})`;
                                    }}
                                    value={allAccounts.find((a) => String(a.id) === String(selectedAccountId)) || null}
                                    onChange={(event, newValue) => {
                                        setSelectedAccountId(newValue ? newValue.id : '');
                                    }}
                                    isOptionEqualToValue={(option, value) => String(option?.id) === String(value?.id)}
                                    renderOption={(props, acc) => {
                                        const picCount = allPics.filter((p) => p.software_account_id === acc.id && p.status === 'active').length;
                                        return (
                                            <li {...props} key={acc.id}>
                                                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%', py: 0.5 }}>
                                                    <Box>
                                                        <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                            {acc.platform?.name || 'Platform'} — {acc.account_name}
                                                        </Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            {acc.username}
                                                        </Typography>
                                                    </Box>
                                                    {picCount > 0 && (
                                                        <Chip
                                                            label={`${picCount} Active PICs`}
                                                            size="small"
                                                            color="primary"
                                                            variant="outlined"
                                                            sx={{ fontSize: '0.72rem', height: 22, ml: 1 }}
                                                        />
                                                    )}
                                                </Box>
                                            </li>
                                        );
                                    }}
                                    renderInput={(params) => (
                                        <TextField
                                            {...params}
                                            label="Select Platform Software Account"
                                            placeholder="Type to search account by platform, label, or username..."
                                        />
                                    )}
                                    sx={{ minWidth: 360, flex: '2 1 360px' }}
                                />

                                <TextField
                                    select
                                    size="small"
                                    label="Custodian Status"
                                    value={accountPicStatusFilter}
                                    onChange={(e) => setAccountPicStatusFilter(e.target.value)}
                                    SelectProps={{ MenuProps: selectMenuProps }}
                                    sx={{ minWidth: 180, flex: '1 1 180px' }}
                                >
                                    <MenuItem value="all">All Custodians</MenuItem>
                                    <MenuItem value="active">Active PICs Only</MenuItem>
                                    <MenuItem value="revoked">Past / Revoked Only</MenuItem>
                                </TextField>

                                <Box sx={{ ml: 'auto', display: 'flex', gap: 1 }}>
                                    <Button
                                        variant="contained"
                                        color="error"
                                        startIcon={<PictureAsPdfIcon />}
                                        onClick={handleExportAccountPdf}
                                        disabled={!selectedAccount || accountCustodians.length === 0 || exportingPdf}
                                        sx={{ height: 40, px: 2.5, textTransform: 'none', fontWeight: 600 }}
                                    >
                                        {exportingPdf ? 'Exporting...' : 'Export Report (PDF)'}
                                    </Button>
                                </Box>
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Selected Account Overview & Custodians Table */}
                    {selectedAccount ? (
                        <>
                            {/* Summary Metadata Card */}
                            <Card sx={{ mb: 3, borderRadius: 2, bgcolor: 'action.hover', border: 1, borderColor: 'divider' }}>
                                <CardContent sx={{ py: 2 }}>
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                            <HubIcon sx={{ fontSize: 44, color: 'primary.main' }} />
                                            <Box>
                                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                                    {selectedAccount.account_name}
                                                </Typography>
                                                <Typography variant="body2" color="text.secondary">
                                                    {selectedAccount.platform?.name || 'Platform'} • {selectedAccount.username}
                                                </Typography>
                                            </Box>
                                        </Box>
                                        <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
                                            {getLifecycleBadge(selectedAccount.lifecycle_status)}
                                            <Divider orientation="vertical" flexItem />
                                            <Box sx={{ textAlign: 'center', px: 1 }}>
                                                <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                                    TOTAL PICs
                                                </Typography>
                                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                                    {allPics.filter(p => p.software_account_id === selectedAccount.id).length}
                                                </Typography>
                                            </Box>
                                            <Divider orientation="vertical" flexItem />
                                            <Box sx={{ textAlign: 'center', px: 1 }}>
                                                <Typography variant="caption" color="text.secondary" sx={{ fontWeight: 700 }}>
                                                    ACTIVE CUSTODIANS
                                                </Typography>
                                                <Typography variant="h6" sx={{ fontWeight: 700, color: 'success.main' }}>
                                                    {allPics.filter(p => p.software_account_id === selectedAccount.id && p.status === 'active').length}
                                                </Typography>
                                            </Box>
                                        </Box>
                                    </Box>
                                </CardContent>
                            </Card>

                            {/* Table of Custodians */}
                            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700, width: 50 }}>#</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Person in Charge (PIC)</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Email Address</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Access Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned Date</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Revoked Date</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Revocation Reason</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700, minWidth: 160 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {accountCustodians.length > 0 ? (
                                            accountCustodians.map((pic, idx) => (
                                                <TableRow key={pic.id} hover>
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 600 }}>{idx + 1}</TableCell>
                                                    <TableCell sx={{ fontWeight: 600 }}>{pic.user?.name || 'Unknown User'}</TableCell>
                                                    <TableCell>{pic.user?.email || '-'}</TableCell>
                                                    <TableCell>{getPicStatusChip(pic.status)}</TableCell>
                                                    <TableCell>
                                                        {pic.assigned_at ? new Date(pic.assigned_at).toLocaleDateString() : '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {pic.revoked_at ? new Date(pic.revoked_at).toLocaleDateString() : '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Typography variant="body2" color={pic.revocation_reason ? 'text.primary' : 'text.secondary'}>
                                                            {pic.revocation_reason || '-'}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        {pic.status === 'active' ? (
                                                            <Button
                                                                size="small"
                                                                variant="outlined"
                                                                color="error"
                                                                startIcon={<BlockIcon fontSize="small" />}
                                                                onClick={() => openRevokeModal(pic)}
                                                                sx={{ textTransform: 'none', fontWeight: 600 }}
                                                            >
                                                                Revoke (Inactive)
                                                            </Button>
                                                        ) : (
                                                            <Chip size="small" label="Revoked" variant="outlined" />
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={8} align="center" sx={{ py: 4 }}>
                                                    <Typography color="text.secondary">
                                                        No PIC custodians assigned to this account matching the filter.
                                                    </Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </>
                    ) : (
                        <Card sx={{ p: 5, textAlign: 'center', borderRadius: 2, boxShadow: 1 }}>
                            <HubIcon sx={{ fontSize: 56, color: 'text.disabled', mb: 1 }} />
                            <Typography variant="h6" color="text.secondary" sx={{ fontWeight: 600 }}>
                                Select a Software Platform Account
                            </Typography>
                            <Typography variant="body2" color="text.secondary" sx={{ maxWidth: 460, mx: 'auto', mt: 0.5 }}>
                                Choose an account from the dropdown above to view all employees who hold or have held access to it, manage active custodianship, perform instant revocation, or export a certified PDF audit report.
                            </Typography>
                        </Card>
                    )}
                </Box>
            )}

            {/* ── MODAL: Register New Account ───────────────────────────────── */}
            <Dialog
                open={createModalOpen}
                onClose={() => setCreateModalOpen(false)}
                maxWidth="sm"
                fullWidth
                PaperProps={{
                    sx: {
                        borderRadius: 3,
                        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        overflow: 'hidden'
                    }
                }}
            >
                <form onSubmit={handleCreate}>
                    <DialogTitle
                        sx={{
                            p: 2.5,
                            pb: 2,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            borderBottom: 1,
                            borderColor: 'divider'
                        }}
                    >
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                            <Box
                                sx={{
                                    bgcolor: 'primary.50',
                                    color: 'primary.main',
                                    p: 1,
                                    borderRadius: 2,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center'
                                }}
                            >
                                <AddIcon fontSize="medium" />
                            </Box>
                            <Box>
                                <Typography variant="h6" sx={{ fontWeight: 700, lineHeight: 1.2 }}>
                                    Register Software Account
                                </Typography>
                                <Typography variant="caption" color="text.secondary">
                                    Catalog a new software account or service credential to track custody and compliance
                                </Typography>
                            </Box>
                        </Box>
                        <IconButton
                            onClick={() => setCreateModalOpen(false)}
                            size="small"
                            sx={{ color: 'text.secondary' }}
                        >
                            <CloseIcon fontSize="small" />
                        </IconButton>
                    </DialogTitle>

                    <DialogContent sx={{ p: 3 }}>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2.5, width: '100%', pt: 1, pb: 0.5 }}>
                            {/* Row 1: Platform Selection (Searchable Autocomplete, Full Wide Width, Auto-selected) */}
                            <Box sx={{ width: '100%' }}>
                                <Typography variant="body2" sx={{ fontWeight: 600, mb: 0.8, color: 'text.primary' }}>
                                    Platform / Service Provider <span style={{ color: '#d32f2f' }}>*</span>
                                </Typography>
                                <Autocomplete
                                    fullWidth
                                    options={platforms}
                                    getOptionLabel={(option) => {
                                        if (!option) return '';
                                        if (typeof option === 'string') return option;
                                        return option.name ? `${option.name}${option.code ? ` (${option.code})` : ''}` : '';
                                    }}
                                    value={platforms.find((p) => String(p.id) === String(formData.software_platform_id)) || (platforms.length > 0 ? platforms[0] : null)}
                                    onChange={(event, newValue) => {
                                        setFormData({ ...formData, software_platform_id: newValue ? newValue.id : '' });
                                    }}
                                    isOptionEqualToValue={(option, value) => String(option?.id) === String(value?.id)}
                                    renderOption={(props, option) => (
                                        <li {...props} key={option.id}>
                                            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%', py: 0.5 }}>
                                                <Typography variant="body2" sx={{ fontWeight: 500 }}>
                                                    {option.name}
                                                </Typography>
                                                {option.code && (
                                                    <Chip
                                                        label={option.code}
                                                        size="small"
                                                        variant="outlined"
                                                        sx={{ fontSize: '0.72rem', height: 22, color: 'text.secondary', ml: 1 }}
                                                    />
                                                )}
                                            </Box>
                                        </li>
                                    )}
                                    renderInput={(params) => (
                                        <TextField
                                            {...params}
                                            required
                                            size="medium"
                                            placeholder="Type or click to search platform..."
                                            helperText="Search and select the platform or service provider for this account"
                                        />
                                    )}
                                    slotProps={{
                                        popper: {
                                            sx: {
                                                zIndex: 1400,
                                            },
                                        },
                                        paper: {
                                            sx: {
                                                mt: 0.5,
                                                boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.15)',
                                            },
                                        },
                                    }}
                                />
                            </Box>

                            {/* Row 2: Account Name / Label */}
                            <Box sx={{ width: '100%' }}>
                                <Typography variant="body2" sx={{ fontWeight: 600, mb: 0.8, color: 'text.primary' }}>
                                    Account Name / Label <span style={{ color: '#d32f2f' }}>*</span>
                                </Typography>
                                <TextField
                                    required
                                    fullWidth
                                    size="medium"
                                    placeholder="e.g. Corporate Google Workspace Admin"
                                    value={formData.account_name}
                                    onChange={(e) => setFormData({ ...formData, account_name: e.target.value })}
                                    helperText="A descriptive title used to identify this account in the organization"
                                />
                            </Box>

                            {/* Row 3: Username / Login Identifier */}
                            <Box sx={{ width: '100%' }}>
                                <Typography variant="body2" sx={{ fontWeight: 600, mb: 0.8, color: 'text.primary' }}>
                                    Username / Login Identifier <span style={{ color: '#d32f2f' }}>*</span>
                                </Typography>
                                <TextField
                                    required
                                    fullWidth
                                    size="medium"
                                    placeholder="e.g. it-security@company.com or admin_finance"
                                    value={formData.username}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                    helperText="Primary email, username, or login ID used to authenticate"
                                />
                            </Box>
                        </Box>
                    </DialogContent>

                    <DialogActions
                        sx={{
                            px: 3,
                            py: 2,
                            borderTop: 1,
                            borderColor: 'divider',
                            bgcolor: 'grey.50',
                            gap: 1.5
                        }}
                    >
                        <Button
                            onClick={() => setCreateModalOpen(false)}
                            variant="outlined"
                            color="inherit"
                            sx={{ textTransform: 'none', borderRadius: 2, px: 2.5 }}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="contained"
                            startIcon={<AddIcon />}
                            sx={{ textTransform: 'none', borderRadius: 2, px: 3, fontWeight: 600 }}
                        >
                            Register Account
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ── MODAL: Quick Revoke PIC Access ────────────────────────────── */}
            <Dialog open={revokeDialogOpen} onClose={() => !revoking && setRevokeDialogOpen(false)} maxWidth="xs" fullWidth>
                <DialogTitle sx={{ fontWeight: 700, color: 'error.main', display: 'flex', alignItems: 'center', gap: 1 }}>
                    <BlockIcon color="error" /> Revoke PIC Access
                </DialogTitle>
                <DialogContent dividers>
                    <Typography variant="body1" sx={{ mb: 1.5 }}>
                        Are you sure you want to revoke access for <strong>{picToRevoke?.user?.name || 'this employee'}</strong> from <strong>{picToRevoke?.account?.account_name || 'this account'}</strong>?
                    </Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                        This will immediately set the assignment status to <strong>Inactive / Revoked</strong> and log the security audit timestamp.
                    </Typography>
                    <TextField
                        fullWidth
                        multiline
                        rows={2}
                        label="Revocation Reason (Optional)"
                        placeholder="e.g. Role transfer, employee offboarding, or credential rotation..."
                        value={revokeReason}
                        onChange={(e) => setRevokeReason(e.target.value)}
                    />
                </DialogContent>
                <DialogActions sx={{ p: 2 }}>
                    <Button onClick={() => setRevokeDialogOpen(false)} disabled={revoking}>
                        Cancel
                    </Button>
                    <Button
                        variant="contained"
                        color="error"
                        onClick={handleConfirmRevoke}
                        disabled={revoking}
                    >
                        {revoking ? 'Revoking...' : 'Revoke (Set Inactive)'}
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

            {/* GLOBAL EMPLOYEE SELECTION MODAL */}
            <UserSelectModal
                open={userPickerOpen}
                onClose={() => setUserPickerOpen(false)}
                onSelect={(user) => {
                    setSelectedPicUserId(user.id);
                    setUserPickerOpen(false);
                }}
                title="Select Person in Charge (PIC)"
                subtitle="Search employee across departments and branches to inspect their platform accounts"
                users={users}
                departments={departments}
                selectedUserId={selectedPicUserId}
                selectedUserName={selectedPicUser?.name}
                currentUserName={auth?.user?.name}
            />

            {/* ── MODAL: PIC Custodian & User Details ───────────────────────── */}
            <Dialog
                open={picDetailModalOpen}
                onClose={() => setPicDetailModalOpen(false)}
                maxWidth="md"
                fullWidth
                PaperProps={{
                    sx: {
                        borderRadius: 3,
                        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        overflow: 'hidden'
                    }
                }}
            >
                <DialogTitle
                    sx={{
                        p: 2.5,
                        pb: 2,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        borderBottom: 1,
                        borderColor: 'divider'
                    }}
                >
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                        <Box
                            sx={{
                                bgcolor: 'primary.50',
                                color: 'primary.main',
                                p: 1,
                                borderRadius: 2,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}
                        >
                            <PersonIcon fontSize="medium" />
                        </Box>
                        <Box>
                            <Typography variant="h6" sx={{ fontWeight: 700, lineHeight: 1.2 }}>
                                Active PICs & Custodian Details
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                                {selectedAccountForPics?.account_name} ({selectedAccountForPics?.platform?.name || 'Platform'}) • {selectedAccountForPics?.username}
                            </Typography>
                        </Box>
                    </Box>
                    <IconButton
                        onClick={() => setPicDetailModalOpen(false)}
                        size="small"
                        sx={{ color: 'text.secondary' }}
                    >
                        <CloseIcon fontSize="small" />
                    </IconButton>
                </DialogTitle>

                <DialogContent sx={{ p: 3 }}>
                    {/* Header Controls */}
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2.5, flexWrap: 'wrap', gap: 1.5 }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Typography variant="subtitle2" sx={{ fontWeight: 600 }}>
                                Assigned Active Custodians:
                            </Typography>
                            <Chip
                                size="small"
                                color="primary"
                                label={`${selectedAccountForPics?.active_pics?.length || 0} Employees`}
                                sx={{ fontWeight: 700 }}
                            />
                        </Box>

                        {selectedAccountForPics?.active_pics?.length > 2 && (
                            <TextField
                                size="small"
                                placeholder="Search PICs by name, department, role..."
                                value={picSearchQuery}
                                onChange={(e) => setPicSearchQuery(e.target.value)}
                                InputProps={{
                                    startAdornment: (
                                        <InputAdornment position="start">
                                            <SearchIcon fontSize="small" color="action" />
                                        </InputAdornment>
                                    ),
                                    endAdornment: picSearchQuery && (
                                        <InputAdornment position="end">
                                            <IconButton size="small" onClick={() => setPicSearchQuery('')}>
                                                <ClearIcon fontSize="small" />
                                            </IconButton>
                                        </InputAdornment>
                                    )
                                }}
                                sx={{ minWidth: 280 }}
                            />
                        )}
                    </Box>

                    {/* PIC Cards List */}
                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                        {(() => {
                            const activePics = selectedAccountForPics?.active_pics || [];
                            const filteredPics = activePics.filter((p) => {
                                if (!picSearchQuery.trim()) return true;
                                const q = picSearchQuery.toLowerCase();
                                const userName = (p.user?.name || '').toLowerCase();
                                const deptName = (p.user?.department?.name || '').toLowerCase();
                                const branchName = (p.user?.branch?.name || '').toLowerCase();
                                const posName = (p.user?.officePosition?.name || p.user?.office_position?.name || p.user?.position?.name || '').toLowerCase();
                                const email = (p.user?.email || '').toLowerCase();
                                const code = (p.user?.employee_id || p.user?.code || '').toLowerCase();
                                return (
                                    userName.includes(q) ||
                                    deptName.includes(q) ||
                                    branchName.includes(q) ||
                                    posName.includes(q) ||
                                    email.includes(q) ||
                                    code.includes(q)
                                );
                            });

                            if (filteredPics.length === 0) {
                                return (
                                    <Paper sx={{ p: 4, textAlign: 'center', bgcolor: 'grey.50', borderRadius: 2 }}>
                                        <Typography variant="body2" color="text.secondary">
                                            {picSearchQuery ? 'No assigned PICs matching your search.' : 'No active PICs assigned to this account.'}
                                        </Typography>
                                    </Paper>
                                );
                            }

                            return filteredPics.map((p) => (
                                <Card
                                    key={p.id}
                                    variant="outlined"
                                    sx={{
                                        p: 2,
                                        borderRadius: 2,
                                        borderColor: p.is_primary ? 'warning.light' : 'divider',
                                        bgcolor: p.is_primary ? '#fffdf5' : 'background.paper',
                                        transition: 'all 0.2s',
                                        '&:hover': {
                                            boxShadow: 2,
                                            borderColor: 'primary.light'
                                        }
                                    }}
                                >
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 1.5 }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                            <Avatar
                                                sx={{
                                                    bgcolor: p.is_primary ? 'warning.main' : 'primary.main',
                                                    width: 44,
                                                    height: 44,
                                                    fontWeight: 700,
                                                    fontSize: '1.1rem'
                                                }}
                                            >
                                                {p.user?.name ? p.user.name.charAt(0).toUpperCase() : 'U'}
                                            </Avatar>
                                            <Box>
                                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
                                                    <Typography variant="subtitle1" sx={{ fontWeight: 700, color: 'text.primary' }}>
                                                        {p.user?.name || 'Unnamed Employee'}
                                                    </Typography>
                                                    {p.is_primary && (
                                                        <Chip
                                                            label="Primary PIC"
                                                            size="small"
                                                            color="warning"
                                                            icon={<StarIcon sx={{ fontSize: '14px !important' }} />}
                                                            sx={{ fontWeight: 700, height: 22 }}
                                                        />
                                                    )}
                                                    <Chip
                                                        label={p.status || 'Active'}
                                                        size="small"
                                                        color="success"
                                                        sx={{ textTransform: 'capitalize', height: 22 }}
                                                    />
                                                </Box>
                                                <Typography variant="caption" color="text.secondary">
                                                    Employee ID: {p.user?.employee_id || p.user?.code || `#${p.user?.id || p.user_id}`}
                                                </Typography>
                                            </Box>
                                        </Box>

                                        <Box sx={{ display: 'flex', gap: 1 }}>
                                            <Button
                                                size="small"
                                                variant="outlined"
                                                color="primary"
                                                startIcon={<VisibilityIcon fontSize="small" />}
                                                onClick={() => {
                                                    setPicDetailModalOpen(false);
                                                    setCurrentTab(1);
                                                    setSelectedPicUserId(p.user_id);
                                                }}
                                                sx={{ textTransform: 'none', borderRadius: 1.5, fontSize: '0.8rem' }}
                                            >
                                                View Access
                                            </Button>
                                            <Button
                                                size="small"
                                                variant="outlined"
                                                color="error"
                                                startIcon={<BlockIcon fontSize="small" />}
                                                onClick={() => {
                                                    setPicDetailModalOpen(false);
                                                    openRevokeModal(p);
                                                }}
                                                sx={{ textTransform: 'none', borderRadius: 1.5, fontSize: '0.8rem' }}
                                            >
                                                Revoke
                                            </Button>
                                        </Box>
                                    </Box>

                                    <Divider sx={{ my: 1.5 }} />

                                    <Grid container spacing={2}>
                                        <Grid item xs={12} sm={4}>
                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                                <BusinessIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
                                                <Box>
                                                    <Typography variant="caption" color="text.secondary" display="block">
                                                        Department / Branch
                                                    </Typography>
                                                    <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                        {p.user?.department?.name || 'General Dept'}
                                                    </Typography>
                                                    <Typography variant="caption" color="text.secondary">
                                                        {p.user?.branch?.name || 'Main Office'}
                                                    </Typography>
                                                </Box>
                                            </Box>
                                        </Grid>

                                        <Grid item xs={12} sm={4}>
                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                                <BadgeIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
                                                <Box>
                                                    <Typography variant="caption" color="text.secondary" display="block">
                                                        Office Position
                                                    </Typography>
                                                    <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                        {p.user?.officePosition?.name || p.user?.office_position?.name || p.user?.position?.name || 'Staff'}
                                                    </Typography>
                                                    <Typography variant="caption" color="text.secondary">
                                                        Assigned: {p.assigned_at ? new Date(p.assigned_at).toLocaleDateString() : 'N/A'}
                                                    </Typography>
                                                </Box>
                                            </Box>
                                        </Grid>

                                        <Grid item xs={12} sm={4}>
                                            <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1 }}>
                                                <EmailIcon sx={{ fontSize: 18, color: 'text.secondary', mt: 0.3 }} />
                                                <Box>
                                                    <Typography variant="caption" color="text.secondary" display="block">
                                                        Contact
                                                    </Typography>
                                                    <Typography variant="body2" sx={{ fontWeight: 600, wordBreak: 'break-all' }}>
                                                        {p.user?.email || 'No email registered'}
                                                    </Typography>
                                                    {p.user?.phone && (
                                                        <Typography variant="caption" color="text.secondary">
                                                            {p.user.phone}
                                                        </Typography>
                                                    )}
                                                </Box>
                                            </Box>
                                        </Grid>
                                    </Grid>

                                    {p.notes && (
                                        <Box sx={{ mt: 1.5, p: 1, bgcolor: 'action.hover', borderRadius: 1.5 }}>
                                            <Typography variant="caption" color="text.secondary">
                                                <strong>Assignment Notes:</strong> {p.notes}
                                            </Typography>
                                        </Box>
                                    )}
                                </Card>
                            ));
                        })()}
                    </Box>
                </DialogContent>

                <DialogActions sx={{ px: 3, py: 2, borderTop: 1, borderColor: 'divider', bgcolor: 'grey.50' }}>
                    <Button
                        onClick={() => setPicDetailModalOpen(false)}
                        variant="contained"
                        sx={{ textTransform: 'none', borderRadius: 2, px: 3 }}
                    >
                        Close
                    </Button>
                </DialogActions>
            </Dialog>

            {/* ── MODAL: Edit Software Account ──────────────────────────────── */}
            <Dialog open={editModalOpen} onClose={() => !updatingAccount && setEditModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleUpdateAccount}>
                    <DialogTitle
                        sx={{
                            p: 2.5,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            borderBottom: 1,
                            borderColor: 'divider',
                        }}
                    >
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                            <Box
                                sx={{
                                    bgcolor: 'info.50',
                                    color: 'info.main',
                                    p: 1,
                                    borderRadius: 2,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                }}
                            >
                                <EditIcon fontSize="medium" />
                            </Box>
                            <Box>
                                <Typography variant="h6" sx={{ fontWeight: 700, lineHeight: 1.2 }}>
                                    Edit Software Account
                                </Typography>
                                <Typography variant="caption" color="text.secondary">
                                    Update title, login identity, and lifecycle status for {accountToEdit?.platform?.name || 'this account'}
                                </Typography>
                            </Box>
                        </Box>
                        <IconButton
                            onClick={() => setEditModalOpen(false)}
                            size="small"
                            sx={{ color: 'text.secondary' }}
                        >
                            <CloseIcon fontSize="small" />
                        </IconButton>
                    </DialogTitle>

                    <DialogContent sx={{ p: 3 }}>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2.5, width: '100%', pt: 1, pb: 0.5 }}>
                            <Box sx={{ width: '100%' }}>
                                <Typography variant="body2" sx={{ fontWeight: 600, mb: 0.8, color: 'text.primary' }}>
                                    Account Name / Label <span style={{ color: '#d32f2f' }}>*</span>
                                </Typography>
                                <TextField
                                    required
                                    fullWidth
                                    size="medium"
                                    value={editFormData.account_name}
                                    onChange={(e) => setEditFormData({ ...editFormData, account_name: e.target.value })}
                                />
                            </Box>

                            <Box sx={{ width: '100%' }}>
                                <Typography variant="body2" sx={{ fontWeight: 600, mb: 0.8, color: 'text.primary' }}>
                                    Username / Login Identifier <span style={{ color: '#d32f2f' }}>*</span>
                                </Typography>
                                <TextField
                                    required
                                    fullWidth
                                    size="medium"
                                    value={editFormData.username}
                                    onChange={(e) => setEditFormData({ ...editFormData, username: e.target.value })}
                                />
                            </Box>

                            <Box sx={{ width: '100%' }}>
                                <Typography variant="body2" sx={{ fontWeight: 600, mb: 0.8, color: 'text.primary' }}>
                                    Lifecycle Status <span style={{ color: '#d32f2f' }}>*</span>
                                </Typography>
                                <TextField
                                    select
                                    required
                                    fullWidth
                                    size="medium"
                                    value={editFormData.lifecycle_status}
                                    onChange={(e) => setEditFormData({ ...editFormData, lifecycle_status: e.target.value })}
                                    SelectProps={{ MenuProps: selectMenuProps }}
                                >
                                    <MenuItem value="active">Active</MenuItem>
                                    <MenuItem value="suspended">Suspended</MenuItem>
                                    <MenuItem value="retired">Retired</MenuItem>
                                </TextField>
                            </Box>
                        </Box>
                    </DialogContent>

                    <DialogActions
                        sx={{
                            px: 3,
                            py: 2,
                            borderTop: 1,
                            borderColor: 'divider',
                            bgcolor: 'grey.50',
                            gap: 1.5,
                        }}
                    >
                        <Button
                            onClick={() => setEditModalOpen(false)}
                            variant="outlined"
                            color="inherit"
                            disabled={updatingAccount}
                            sx={{ textTransform: 'none', borderRadius: 2, px: 2.5 }}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="contained"
                            disabled={updatingAccount}
                            sx={{ textTransform: 'none', borderRadius: 2, px: 3, fontWeight: 600 }}
                        >
                            {updatingAccount ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ── MODAL: Delete Software Account Confirmation ───────────────── */}
            <Dialog open={deleteModalOpen} onClose={() => !deletingAccount && setDeleteModalOpen(false)} maxWidth="xs" fullWidth>
                <DialogTitle sx={{ fontWeight: 700, color: 'error.main', display: 'flex', alignItems: 'center', gap: 1 }}>
                    <DeleteIcon color="error" /> Delete Software Account
                </DialogTitle>
                <DialogContent dividers>
                    <Typography variant="body1" sx={{ mb: 1.5 }}>
                        Are you sure you want to permanently delete <strong>{accountToDelete?.account_name}</strong> ({accountToDelete?.platform?.name})?
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        This action will remove the account record and log a security audit entry. All past PIC and property history for this account will be cleaned up.
                    </Typography>
                </DialogContent>
                <DialogActions sx={{ p: 2, gap: 1 }}>
                    <Button onClick={() => setDeleteModalOpen(false)} disabled={deletingAccount}>
                        Cancel
                    </Button>
                    <Button
                        variant="contained"
                        color="error"
                        onClick={handleDeleteAccount}
                        disabled={deletingAccount}
                    >
                        {deletingAccount ? 'Deleting...' : 'Delete Account'}
                    </Button>
                </DialogActions>
            </Dialog>
        </AsideLayout>
    );
}
