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
import { Add as AddIcon } from '@mui/icons-material';

export default function ReviewsIndex({ reviews, accounts = [], reviewers = [], filters = {} }) {
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [resultFilter, setResultFilter] = useState(filters.result || '');

    const [scheduleModalOpen, setScheduleModalOpen] = useState(false);
    const [scheduleData, setScheduleData] = useState({
        software_account_id: '',
        review_type: 'scheduled',
        scheduled_at: '',
        assigned_to: '',
        notes: '',
    });

    const [completeModalOpen, setCompleteModalOpen] = useState(false);
    const [activeReview, setActiveReview] = useState(null);
    const [completeData, setCompleteData] = useState({
        result: 'compliant',
        notes: '',
    });

    const handleFilter = () => {
        router.get('/digital-assets/reviews', {
            status: statusFilter,
            result: resultFilter,
        }, { preserveState: true, replace: true });
    };

    const handleSchedule = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/accounts/${scheduleData.software_account_id}/reviews`, scheduleData, {
            onSuccess: () => {
                setScheduleModalOpen(false);
                setScheduleData({ software_account_id: '', review_type: 'scheduled', scheduledAt: '', assignedTo: '', notes: '' });
            }
        });
    };

    const handleComplete = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/reviews/${activeReview.id}/complete`, completeData, {
            onSuccess: () => {
                setCompleteModalOpen(false);
                setActiveReview(null);
            }
        });
    };

    return (
        <AsideLayout title="Security Compliance Reviews">
            <Head title="Security Reviews - Digital Asset" />

            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Security Compliance Reviews
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Scheduled routine reviews, ad-hoc audits, and incident verifications.
                    </Typography>
                </Box>
                <Button
                    variant="contained"
                    startIcon={<AddIcon />}
                    onClick={() => setScheduleModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                >
                    Schedule Review
                </Button>
            </Box>

            {/* Filter Bar */}
            <Card sx={{ mb: 3, borderRadius: 2, boxShadow: 1 }}>
                <CardContent sx={{ pb: '16px !important' }}>
                    <Grid container spacing={2} alignItems="center">
                        <Grid item xs={12} sm={5}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Review Status"
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                            >
                                <MenuItem value="">All Statuses</MenuItem>
                                <MenuItem value="scheduled">Scheduled</MenuItem>
                                <MenuItem value="in_progress">In Progress</MenuItem>
                                <MenuItem value="completed">Completed</MenuItem>
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={5}>
                            <TextField
                                select
                                fullWidth
                                size="small"
                                label="Compliance Result"
                                value={resultFilter}
                                onChange={(e) => setResultFilter(e.target.value)}
                            >
                                <MenuItem value="">All Results</MenuItem>
                                <MenuItem value="compliant">Compliant</MenuItem>
                                <MenuItem value="needs_action">Needs Action</MenuItem>
                                <MenuItem value="at_risk">At Risk</MenuItem>
                            </TextField>
                        </Grid>
                        <Grid item xs={12} sm={2}>
                            <Button fullWidth variant="outlined" onClick={handleFilter} sx={{ height: 40, textTransform: 'none' }}>
                                Filter
                            </Button>
                        </Grid>
                    </Grid>
                </CardContent>
            </Card>

            {/* Reviews Table */}
            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                <Table size="small">
                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                        <TableRow>
                            <TableCell sx={{ fontWeight: 700 }}>Account</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Platform</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Review Type</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Scheduled Date</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Result</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Auditor / Reviewer</TableCell>
                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {reviews.data && reviews.data.length > 0 ? (
                            reviews.data.map((rev) => (
                                <TableRow key={rev.id} hover>
                                    <TableCell sx={{ fontWeight: 600 }}>
                                        <Button
                                            component="a"
                                            href={`/digital-assets/accounts/${rev.software_account_id}`}
                                            sx={{ textTransform: 'none', p: 0, fontWeight: 600 }}
                                        >
                                            {rev.account?.account_name}
                                        </Button>
                                    </TableCell>
                                    <TableCell>{rev.account?.platform?.name}</TableCell>
                                    <TableCell><Chip size="small" label={rev.review_type} variant="outlined" /></TableCell>
                                    <TableCell>{new Date(rev.scheduled_at).toLocaleDateString()}</TableCell>
                                    <TableCell>
                                        <Chip
                                            size="small"
                                            label={rev.status}
                                            color={rev.status === 'completed' ? 'success' : rev.status === 'in_progress' ? 'info' : 'default'}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        {rev.result ? (
                                            <Chip
                                                size="small"
                                                label={rev.result}
                                                color={rev.result === 'compliant' ? 'success' : rev.result === 'at_risk' ? 'error' : 'warning'}
                                            />
                                        ) : '-'}
                                    </TableCell>
                                    <TableCell>{rev.assigned_to_user?.name || rev.completed_by_user?.name || 'Unassigned'}</TableCell>
                                    <TableCell align="right">
                                        {rev.status === 'scheduled' && (
                                            <Button
                                                size="small"
                                                onClick={() => router.post(`/digital-assets/reviews/${rev.id}/start`, {}, { preserveScroll: true })}
                                                sx={{ textTransform: 'none' }}
                                            >
                                                Start
                                            </Button>
                                        )}
                                        {rev.status === 'in_progress' && (
                                            <Button
                                                size="small"
                                                color="success"
                                                onClick={() => {
                                                    setActiveReview(rev);
                                                    setCompleteModalOpen(true);
                                                }}
                                                sx={{ textTransform: 'none' }}
                                            >
                                                Complete
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={8} align="center" sx={{ py: 3 }}>
                                    <Typography color="text.secondary">No security reviews found.</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Modal: Schedule Review */}
            <Dialog open={scheduleModalOpen} onClose={() => setScheduleModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleSchedule}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Schedule Compliance Review</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                required
                                fullWidth
                                label="Target Account"
                                value={scheduleData.software_account_id}
                                onChange={(e) => setScheduleData({ ...scheduleData, software_account_id: e.target.value })}
                            >
                                {accounts.map((acc) => (
                                    <MenuItem key={acc.id} value={acc.id}>{acc.account_name} ({acc.username})</MenuItem>
                                ))}
                            </TextField>

                            <TextField
                                select
                                fullWidth
                                label="Review Type"
                                value={scheduleData.review_type}
                                onChange={(e) => setScheduleData({ ...scheduleData, review_type: e.target.value })}
                            >
                                <MenuItem value="scheduled">Scheduled Routine Review</MenuItem>
                                <MenuItem value="ad_hoc">Ad-Hoc Review</MenuItem>
                                <MenuItem value="incident">Incident Investigation</MenuItem>
                            </TextField>

                            <TextField
                                type="datetime-local"
                                required
                                fullWidth
                                label="Scheduled Date & Time"
                                InputLabelProps={{ shrink: true }}
                                value={scheduleData.scheduled_at}
                                onChange={(e) => setScheduleData({ ...scheduleData, scheduled_at: e.target.value })}
                            />

                            <TextField
                                select
                                fullWidth
                                label="Assigned Reviewer"
                                value={scheduleData.assigned_to}
                                onChange={(e) => setScheduleData({ ...scheduleData, assigned_to: e.target.value })}
                            >
                                <MenuItem value="">Unassigned</MenuItem>
                                {reviewers.map((u) => (
                                    <MenuItem key={u.id} value={u.id}>{u.name}</MenuItem>
                                ))}
                            </TextField>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setScheduleModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Schedule</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Modal: Complete Review */}
            <Dialog open={completeModalOpen} onClose={() => setCompleteModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleComplete}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Finalize Compliance Review</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Result"
                                value={completeData.result}
                                onChange={(e) => setCompleteData({ ...completeData, result: e.target.value })}
                            >
                                <MenuItem value="compliant">Compliant</MenuItem>
                                <MenuItem value="needs_action">Needs Action</MenuItem>
                                <MenuItem value="at_risk">At Risk</MenuItem>
                            </TextField>
                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                label="Findings & Notes"
                                value={completeData.notes}
                                onChange={(e) => setCompleteData({ ...completeData, notes: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCompleteModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Finalize</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
