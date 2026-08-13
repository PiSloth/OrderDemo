import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AsideLayout from '@/Layouts/AsideLayout';
import {
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Grid,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from '@mui/material';
import {
    BarChart as BarChartIcon,
    Assessment as AssessmentIcon,
    Add as AddIcon,
    CheckCircle as CheckCircleIcon,
    Cancel as CancelIcon,
    AccessTime as AccessTimeIcon,
    Warning as WarningIcon,
} from '@mui/icons-material';

export default function Dashboard({ metrics, recentIssues }) {
    return (
        <AsideLayout title="IT Issue & SLA Dashboard">
            <Head title="IT Issue & SLA Dashboard" />

            <Box sx={{ p: 3 }}>
                {/* Banner Header */}
                <Card sx={{ mb: 3, background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)', color: '#fff' }}>
                    <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                        <Box>
                            <Typography variant="h5" fontWeight="bold" sx={{ color: '#38bdf8' }}>
                                IT Issue Tracking & SLA Dashboard
                            </Typography>
                            <Typography variant="body2" sx={{ opacity: 0.8 }}>
                                Overview of active issues, response SLAs, and weekly resolution metrics.
                            </Typography>
                        </Box>

                        <Box sx={{ display: 'flex', gap: 1 }}>
                            <Button component={Link} href="/operations/it/issues" variant="outlined" color="info">
                                View Issues List
                            </Button>
                            <Button component={Link} href="/operations/it/issues/reports" variant="contained" color="secondary" startIcon={<AssessmentIcon />}>
                                Weekly & Monthly Reports
                            </Button>
                        </Box>
                    </CardContent>
                </Card>

                {/* Metrics Grid */}
                <Grid container spacing={3} sx={{ mb: 3 }}>
                    <Grid item xs={12} sm={6} md={2.4}>
                        <Card sx={{ backgroundColor: '#f8fafc', borderTop: '4px solid #64748b' }}>
                            <CardContent>
                                <Typography variant="overline" color="text.secondary">Total Issues</Typography>
                                <Typography variant="h4" fontWeight="bold">{metrics.total}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={2.4}>
                        <Card sx={{ backgroundColor: '#eff6ff', borderTop: '4px solid #3b82f6' }}>
                            <CardContent>
                                <Typography variant="overline" color="primary.main">Open Issues</Typography>
                                <Typography variant="h4" fontWeight="bold" color="primary.main">{metrics.open}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={2.4}>
                        <Card sx={{ backgroundColor: '#fefce8', borderTop: '4px solid #eab308' }}>
                            <CardContent>
                                <Typography variant="overline" color="warning.main">In Progress</Typography>
                                <Typography variant="h4" fontWeight="bold" color="warning.main">{metrics.in_progress}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={2.4}>
                        <Card sx={{ backgroundColor: '#f0fdf4', borderTop: '4px solid #22c55e' }}>
                            <CardContent>
                                <Typography variant="overline" color="success.main">Closed</Typography>
                                <Typography variant="h4" fontWeight="bold" color="success.main">{metrics.closed}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={2.4}>
                        <Card sx={{ backgroundColor: '#fef2f2', borderTop: '4px solid #ef4444' }}>
                            <CardContent>
                                <Typography variant="overline" color="error.main">SLA Failures</Typography>
                                <Typography variant="h4" fontWeight="bold" color="error.main">{metrics.failed}</Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                {/* Recent Issues Table */}
                <Paper sx={{ p: 3, borderRadius: 2 }}>
                    <Typography variant="h6" fontWeight="bold" sx={{ mb: 2 }}>
                        Recent Issues & Live Status
                    </Typography>

                    <TableContainer>
                        <Table>
                            <TableHead sx={{ backgroundColor: '#f1f5f9' }}>
                                <TableRow>
                                    <TableCell fontWeight="bold">ID</TableCell>
                                    <TableCell fontWeight="bold">Title</TableCell>
                                    <TableCell fontWeight="bold">Category</TableCell>
                                    <TableCell fontWeight="bold">Priority</TableCell>
                                    <TableCell fontWeight="bold">Status</TableCell>
                                    <TableCell fontWeight="bold">Assigned To</TableCell>
                                    <TableCell fontWeight="bold">Created At</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {recentIssues.map((issue) => (
                                    <TableRow key={issue.id} hover>
                                        <TableCell fontStyle="bold">#{issue.id}</TableCell>
                                        <TableCell>{issue.title}</TableCell>
                                        <TableCell>{issue.category?.name}</TableCell>
                                        <TableCell>
                                            <Chip label={issue.priority?.code || 'P3'} size="small" color={issue.priority?.code === 'P1' ? 'error' : issue.priority?.code === 'P2' ? 'warning' : 'info'} />
                                        </TableCell>
                                        <TableCell>{issue.status?.name}</TableCell>
                                        <TableCell>{issue.assigned_user?.name || 'Unassigned'}</TableCell>
                                        <TableCell>{issue.issue_at ? new Date(issue.issue_at).toLocaleString() : 'N/A'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </Paper>
            </Box>
        </AsideLayout>
    );
}
