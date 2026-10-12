import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
    Tooltip
} from '@mui/material';
import {
    Add as AddIcon,
    Visibility as VisibilityIcon,
    Security as SecurityIcon,
    CheckCircle as CheckCircleIcon,
    Warning as WarningIcon,
    Error as ErrorIcon
} from '@mui/icons-material';

export default function AccountsIndex({ accounts, platforms, filters = {}, stats = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [platformId, setPlatformId] = useState(filters.platform_id || '');
    const [lifecycleStatus, setLifecycleStatus] = useState(filters.lifecycle_status || '');
    const [securityStatus, setSecurityStatus] = useState(filters.security_status || '');

    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [formData, setFormData] = useState({
        software_platform_id: '',
        account_name: '',
        username: '',
    });

    const handleFilter = () => {
        router.get('/digital-assets/accounts', {
            search,
            platform_id: platformId,
            lifecycle_status: lifecycleStatus,
            security_status: securityStatus,
        }, { preserveState: true, replace: true });
    };

    const handleCreate = (e) => {
        e.preventDefault();
        router.post('/digital-assets/accounts', formData, {
            onSuccess: () => {
                setCreateModalOpen(false);
                setFormData({ software_platform_id: '', account_name: '', username: '' });
            }
        });
    };

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
                return <Chip size="small" label={status} />;
        }
    };

    return (
        <AsideLayout title="Software Accounts">
            <Head title="Software Accounts - Digital Asset" />

            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Company Software Accounts
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Inventory and security compliance tracking for third-party platforms and software systems.
                    </Typography>
                </Box>
                <Button
                    variant="contained"
                    startIcon={<AddIcon />}
                    onClick={() => setCreateModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                >
                    Register Account
                </Button>
            </Box>

            {/* Metrics Overview */}
            <Grid container spacing={2} sx={{ mb: 3 }}>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary">TOTAL ACCOUNTS</Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5 }}>{stats.total_accounts || 0}</Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary">ACTIVE INVENTORY</Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'primary.main' }}>{stats.active_accounts || 0}</Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary">COMPLIANT ACCOUNTS</Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'success.main' }}>{stats.compliant_accounts || 0}</Typography>
                        </CardContent>
                    </Card>
                </Grid>
                <Grid item xs={12} sm={6} md={3}>
                    <Card sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <CardContent>
                            <Typography variant="caption" color="text.secondary">NEEDS REVIEW / AT RISK</Typography>
                            <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: 'error.main' }}>
                                {(stats.needs_review_accounts || 0) + (stats.at_risk_accounts || 0)}
                            </Typography>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>

            {/* Search & Filters */}
            <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1 }}>
                <CardContent sx={{ pb: '16px !important' }}>
                    <Grid container spacing={2} alignItems="center">
                        <Grid item xs={12} sm={4}>
                            <TextField
                                fullWidth
                                size="small"
                                placeholder="Search by account name or username..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            />
                        </Grid>
                        <Grid item xs={12} sm={3}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Platform"
                                value={platformId}
                                onChange={(e) => setPlatformId(e.target.value)}
                            >
                                <MenuItem value="">All Platforms</MenuItem>
                                {platforms.map((p) => (
                                    <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>
                                ))}
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={2}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Lifecycle"
                                value={lifecycleStatus}
                                onChange={(e) => setLifecycleStatus(e.target.value)}
                            >
                                <MenuItem value="">All Statuses</MenuItem>
                                <MenuItem value="active">Active</MenuItem>
                                <MenuItem value="suspended">Suspended</MenuItem>
                                <MenuItem value="retired">Retired</MenuItem>
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={2}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Security"
                                value={securityStatus}
                                onChange={(e) => setSecurityStatus(e.target.value)}
                            >
                                <MenuItem value="">All Security</MenuItem>
                                <MenuItem value="compliant">Compliant</MenuItem>
                                <MenuItem value="needs_review">Needs Review</MenuItem>
                                <MenuItem value="at_risk">At Risk</MenuItem>
                                <MenuItem value="unknown">Unknown</MenuItem>
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={1}>
                            <Button
                                fullWidth
                                variant="outlined"
                                onClick={handleFilter}
                                sx={{ height: 40, textTransform: 'none' }}
                            >
                                Filter
                            </Button>
                        </Grid>
                    </Grid>
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
                                            account.active_pics.map((p) => (
                                                <Chip
                                                    key={p.id}
                                                    size="small"
                                                    label={p.user?.name || 'User'}
                                                    sx={{ mr: 0.5, mb: 0.5 }}
                                                />
                                            ))
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
            </TableContainer>

            {/* Modal: Register New Account */}
            <Dialog open={createModalOpen} onClose={() => setCreateModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleCreate}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Register Software Account</DialogTitle>
                    <DialogContent dividers>
                        <Grid container spacing={2} sx={{ mt: 0.5 }}>
                            <Grid item xs={12}>
                                <TextField
                                    select
                                    required
                                    fullWidth
                                    label="Platform"
                                    value={formData.software_platform_id}
                                    onChange={(e) => setFormData({ ...formData, software_platform_id: e.target.value })}
                                >
                                    {platforms.map((p) => (
                                        <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>
                                    ))}
                                </TextField>
                            </Grid>
                            <Grid item xs={12}>
                                <TextField
                                    required
                                    fullWidth
                                    label="Account Name / Label"
                                    placeholder="e.g. Corporate Google Workspace Admin"
                                    value={formData.account_name}
                                    onChange={(e) => setFormData({ ...formData, account_name: e.target.value })}
                                />
                            </Grid>
                            <Grid item xs={12}>
                                <TextField
                                    required
                                    fullWidth
                                    label="Username / Login Identifier"
                                    placeholder="e.g. it-security@company.com"
                                    value={formData.username}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                />
                            </Grid>
                        </Grid>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCreateModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Register Account</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
