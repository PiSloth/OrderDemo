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
    MenuItem
} from '@mui/material';

export default function AuditLogsIndex({ logs, accounts = [], users = [], filters = {} }) {
    const [action, setAction] = useState(filters.action || '');
    const [outcome, setOutcome] = useState(filters.outcome || '');
    const [accountId, setAccountId] = useState(filters.software_account_id || '');
    const [actorId, setActorId] = useState(filters.actor_user_id || '');

    const handleFilter = () => {
        router.get('/digital-assets/audit-logs', {
            action,
            outcome,
            software_account_id: accountId,
            actor_user_id: actorId,
        }, { preserveState: true, replace: true });
    };

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
                <CardContent sx={{ pb: '16px !important' }}>
                    <Grid container spacing={2} alignItems="center">
                        <Grid item xs={12} sm={3}>
                            <TextField
                                fullWidth
                                size="small"
                                placeholder="Filter action (e.g. property, pic, evidence)..."
                                value={action}
                                onChange={(e) => setAction(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            />
                        </Grid>
                        <Grid item xs={12} sm={2}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Outcome"
                                value={outcome}
                                onChange={(e) => setOutcome(e.target.value)}
                            >
                                <MenuItem value="">All Outcomes</MenuItem>
                                <MenuItem value="success">Success</MenuItem>
                                <MenuItem value="denied">Denied</MenuItem>
                                <MenuItem value="failure">Failure</MenuItem>
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={3}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Account"
                                value={accountId}
                                onChange={(e) => setAccountId(e.target.value)}
                            >
                                <MenuItem value="">All Accounts</MenuItem>
                                {accounts.map((acc) => (
                                    <MenuItem key={acc.id} value={acc.id}>{acc.account_name}</MenuItem>
                                ))}
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={3}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Actor"
                                value={actorId}
                                onChange={(e) => setActorId(e.target.value)}
                            >
                                <MenuItem value="">All Actors</MenuItem>
                                {users.map((u) => (
                                    <MenuItem key={u.id} value={u.id}>{u.name}</MenuItem>
                                ))}
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={1}>
                            <Button fullWidth variant="outlined" onClick={handleFilter} sx={{ height: 40, textTransform: 'none' }}>
                                Filter
                            </Button>
                        </Grid>
                    </Grid>
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
            </TableContainer>
        </AsideLayout>
    );
}
