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
    TablePagination
} from '@mui/material';

export default function AuditLogsIndex({ logs, accounts = [], users = [], filters = {} }) {
    const [action, setAction] = useState(filters.action || '');
    const [outcome, setOutcome] = useState(filters.outcome || '');
    const [accountId, setAccountId] = useState(filters.software_account_id || '');
    const [actorId, setActorId] = useState(filters.actor_user_id || '');

    const triggerAutoFilter = (overrides = {}) => {
        const nextAction = overrides.action !== undefined ? overrides.action : action;
        const nextOutcome = overrides.outcome !== undefined ? overrides.outcome : outcome;
        const nextAccount = overrides.software_account_id !== undefined ? overrides.software_account_id : accountId;
        const nextActor = overrides.actor_user_id !== undefined ? overrides.actor_user_id : actorId;

        router.get('/digital-assets/audit-logs', {
            action: nextAction?.trim() || undefined,
            outcome: nextOutcome || undefined,
            software_account_id: nextAccount || undefined,
            actor_user_id: nextActor || undefined,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const handleReset = () => {
        setAction('');
        setOutcome('');
        setAccountId('');
        setActorId('');
        router.get('/digital-assets/audit-logs', {}, { preserveState: true, replace: true, preserveScroll: true });
    };

    const hasActiveFilters = Boolean(action || outcome || accountId || actorId);

    return (
        <AsideLayout title="Security Audit Logs">
            <Head title="Audit Logs - Digital Asset" />

            <Box sx={{ mb: 3 }}>
                <Typography variant="h5" sx={{ fontWeight: 700 }}>
                    Security Audit Trail
                </Typography>
                <Typography variant="body2" color="text.secondary">
                    Append-only security log for account changes, property modifications, evidence verification, and reviews.
                </Typography>
            </Box>

            {/* Filter Bar */}
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
                            placeholder="Filter action (e.g. property, pic, evidence)..."
                            value={action}
                            onChange={(e) => setAction(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && triggerAutoFilter({ action: e.target.value })}
                            sx={{
                                flex: { xs: '1 1 100%', md: '1 1 260px' },
                                minWidth: { xs: '100%', sm: 260 },
                            }}
                        />

                        <TextField
                            select
                            size="small"
                            label="Outcome"
                            value={outcome}
                            onChange={(e) => {
                                setOutcome(e.target.value);
                                triggerAutoFilter({ outcome: e.target.value });
                            }}
                            sx={{
                                width: { xs: '100%', sm: 180, md: 200 },
                                minWidth: 170,
                            }}
                        >
                            <MenuItem value="">All Outcomes</MenuItem>
                            <MenuItem value="success">Success</MenuItem>
                            <MenuItem value="denied">Denied</MenuItem>
                            <MenuItem value="failure">Failure</MenuItem>
                        </TextField>

                        <TextField
                            select
                            size="small"
                            label="Account"
                            value={accountId}
                            onChange={(e) => {
                                setAccountId(e.target.value);
                                triggerAutoFilter({ software_account_id: e.target.value });
                            }}
                            sx={{
                                width: { xs: '100%', sm: 260, md: 280 },
                                minWidth: 240,
                                flex: { xs: '1 1 100%', sm: '1 1 240px' },
                            }}
                        >
                            <MenuItem value="">All Accounts</MenuItem>
                            {accounts.map((acc) => (
                                <MenuItem key={acc.id} value={acc.id}>{acc.account_name}</MenuItem>
                            ))}
                        </TextField>

                        <TextField
                            select
                            size="small"
                            label="Actor"
                            value={actorId}
                            onChange={(e) => {
                                setActorId(e.target.value);
                                triggerAutoFilter({ actor_user_id: e.target.value });
                            }}
                            sx={{
                                width: { xs: '100%', sm: 260, md: 280 },
                                minWidth: 240,
                                flex: { xs: '1 1 100%', sm: '1 1 240px' },
                            }}
                        >
                            <MenuItem value="">All Actors</MenuItem>
                            {users.map((u) => (
                                <MenuItem key={u.id} value={u.id}>{u.name}</MenuItem>
                            ))}
                        </TextField>

                        {hasActiveFilters && (
                            <Box sx={{ ml: { md: 'auto' } }}>
                                <Button
                                    variant="outlined"
                                    color="inherit"
                                    onClick={handleReset}
                                    sx={{
                                        height: 40,
                                        px: 2.5,
                                        textTransform: 'none',
                                        minWidth: 90,
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

            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                <Table size="small">
                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                        <TableRow>
                            <TableCell sx={{ fontWeight: 700 }}>Timestamp</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Action</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Account</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Actor</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Outcome</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Reason / Details</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>IP Address</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Request ID</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {logs.data && logs.data.length > 0 ? (
                            logs.data.map((log) => (
                                <TableRow key={log.id} hover>
                                    <TableCell>{new Date(log.occurred_at).toLocaleString()}</TableCell>
                                    <TableCell sx={{ fontFamily: 'monospace', fontWeight: 600 }}>{log.action}</TableCell>
                                    <TableCell>{log.account ? log.account.account_name : '-'}</TableCell>
                                    <TableCell>{log.actor_user ? log.actor_user.name : 'System'}</TableCell>
                                    <TableCell>
                                        <Chip
                                            size="small"
                                            label={log.outcome}
                                            color={log.outcome === 'success' ? 'success' : log.outcome === 'denied' ? 'warning' : 'error'}
                                        />
                                    </TableCell>
                                    <TableCell>{log.reason || '-'}</TableCell>
                                    <TableCell>{log.ip_address || '-'}</TableCell>
                                    <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.75rem' }}>
                                        {log.request_id ? log.request_id.substring(0, 8) + '...' : '-'}
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 3 }}>
                                    <Typography color="text.secondary">No security audit logs found.</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                {logs?.total > (logs?.per_page || 20) && (
                    <TablePagination
                        component="div"
                        count={logs.total}
                        page={(logs.current_page || 1) - 1}
                        rowsPerPage={logs.per_page || 20}
                        rowsPerPageOptions={[]}
                        onPageChange={(e, newPage) => {
                            router.get('/digital-assets/audit-logs', {
                                action,
                                outcome,
                                software_account_id: accountId,
                                actor_user_id: actorId,
                                page: newPage + 1
                            }, { preserveState: true });
                        }}
                    />
                )}
            </TableContainer>
        </AsideLayout>
    );
}
