import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import AsideLayout from '@/Layouts/AsideLayout';
import toast from 'react-hot-toast';

// Material UI Imports
import {
    Box,
    Card,
    CardContent,
    Typography,
    Button,
    Grid,
    Chip,
    Avatar,
    LinearProgress,
    TextField,
    MenuItem,
    Select,
    FormControl,
    InputLabel,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Switch,
    FormControlLabel,
    IconButton,
    Tooltip
} from '@mui/material';

import {
    Star as StarIcon,
    Add as AddIcon,
    FileDownload as ExportIcon,
    PictureAsPdf as PdfIcon,
    ThumbUp as ThumbUpIcon,
    Warning as WarningIcon,
    EventNote as EventIcon,
    CheckCircle as CheckCircleIcon,
    Cancel as CancelIcon,
    Edit as EditIcon,
    Delete as DeleteIcon,
    Search as SearchIcon,
    FilterList as FilterIcon
} from '@mui/icons-material';

import { exportSatisfactionPdf } from '../../../../utils/itSatisfactionPdfExport';

export default function SatisfactionIndex({
    campaigns = [],
    selectedCampaign = null,
    departments = [],
    positions = [],
    ratings = { data: [], links: [] },
    filters = {},
    analytics = {},
    permissions = { can_view: true, can_create: true, can_update: true, can_delete: true, can_export: true }
}) {
    // Campaign Modal State
    const [openCampaignModal, setOpenCampaignModal] = useState(false);
    const [editingCampaign, setEditingCampaign] = useState(null);
    const [campaignForm, setCampaignForm] = useState({
        title: '',
        description: '',
        badge_text: 'IT Satisfaction Survey',
        start_date: new Date().toISOString().slice(0, 10),
        end_date: new Date(Date.now() + 86400000).toISOString().slice(0, 10),
        is_active: true,
        is_mandatory: true,
        target_scope: {
            excluded_department_ids: [1, 10],
            excluded_department_names: ['IT Department', 'IT & Systems'],
            target_department_ids: [],
            target_office_position_ids: [],
            excluded_office_position_ids: [],
            target_roles: [],
            excluded_roles: [],
        },
        criteria: [
            { key: 'speed', label: 'Support Response Speed' },
            { key: 'helpfulness', label: 'Helpfulness & Communication' },
            { key: 'stability', label: 'Network & System Stability' },
        ]
    });

    const [newCriteriaLabel, setNewCriteriaLabel] = useState('');

    // Filter states
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCampaignId, setSelectedCampaignId] = useState(selectedCampaign?.id || '');
    const [ratingFilter, setRatingFilter] = useState(filters.rating || '');
    const [isExportingPdf, setIsExportingPdf] = useState(false);

    const handleExportPdf = async () => {
        setIsExportingPdf(true);
        try {
            const response = await axios.get('/operations/it/satisfaction/report-data', {
                params: { survey_id: selectedCampaignId || undefined }
            });
            await exportSatisfactionPdf(response.data);
            toast.success('IT Satisfaction PDF report generated successfully.');
        } catch (err) {
            console.error('Failed to export PDF:', err);
            toast.error(err.response?.data?.message || 'Failed to generate PDF report.');
        } finally {
            setIsExportingPdf(false);
        }
    };

    const handleOpenCreateCampaign = () => {
        setEditingCampaign(null);
        setCampaignForm({
            title: 'IT Department Satisfaction Survey',
            description: 'Please rate your overall experience and satisfaction with IT Department services.',
            badge_text: 'IT Satisfaction Survey',
            start_date: new Date().toISOString().slice(0, 10),
            end_date: new Date(Date.now() + 86400000).toISOString().slice(0, 10),
            is_active: true,
            is_mandatory: true,
            target_scope: {
                excluded_department_ids: [1, 10],
                excluded_department_names: ['IT Department', 'IT & Systems'],
                target_department_ids: [],
                target_office_position_ids: [],
                excluded_office_position_ids: [],
                target_roles: [],
                excluded_roles: [],
            },
            criteria: [
                { key: 'speed', label: 'Support Response Speed' },
                { key: 'helpfulness', label: 'Helpfulness & Communication' },
                { key: 'stability', label: 'Network & System Stability' },
            ]
        });
        setNewCriteriaLabel('');
        setOpenCampaignModal(true);
    };

    const handleOpenEditCampaign = (c) => {
        setEditingCampaign(c);
        setCampaignForm({
            title: c.title,
            description: c.description || '',
            badge_text: c.badge_text || 'IT Satisfaction Survey',
            start_date: c.start_date,
            end_date: c.end_date,
            is_active: Boolean(c.is_active),
            is_mandatory: Boolean(c.is_mandatory),
            target_scope: c.target_scope || {
                excluded_department_ids: [1, 10],
                excluded_department_names: ['IT Department', 'IT & Systems'],
                target_department_ids: [],
                target_office_position_ids: [],
                excluded_office_position_ids: [],
                target_roles: [],
                excluded_roles: [],
            },
            criteria: c.criteria || [
                { key: 'speed', label: 'Support Response Speed' },
                { key: 'helpfulness', label: 'Helpfulness & Communication' },
                { key: 'stability', label: 'Network & System Stability' },
            ]
        });
        setNewCriteriaLabel('');
        setOpenCampaignModal(true);
    };

    const handleSaveCampaign = (e) => {
        e.preventDefault();

        if (editingCampaign) {
            router.patch(`/operations/it/satisfaction/campaigns/${editingCampaign.id}`, campaignForm, {
                onSuccess: () => {
                    toast.success('Survey campaign updated successfully');
                    setOpenCampaignModal(false);
                },
                onError: (err) => {
                    const firstErr = Object.values(err)[0];
                    toast.error(firstErr || 'Failed to update campaign');
                }
            });
        } else {
            router.post('/operations/it/satisfaction/campaigns', campaignForm, {
                onSuccess: () => {
                    toast.success('Survey campaign created successfully');
                    setOpenCampaignModal(false);
                },
                onError: (err) => {
                    const firstErr = Object.values(err)[0];
                    toast.error(firstErr || 'Failed to create campaign');
                }
            });
        }
    };

    const handleToggleCampaign = (id) => {
        router.post(`/operations/it/satisfaction/campaigns/${id}/toggle`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Campaign status updated'),
        });
    };

    const handleDeleteCampaign = (id) => {
        if (confirm('Are you sure you want to delete this survey campaign and all its ratings?')) {
            router.delete(`/operations/it/satisfaction/campaigns/${id}`, {
                preserveScroll: true,
                onSuccess: () => toast.success('Campaign deleted successfully'),
            });
        }
    };

    const handleFilterChange = (newFilters) => {
        router.get('/operations/it/satisfaction', {
            survey_id: newFilters.survey_id ?? selectedCampaignId,
            rating: newFilters.rating ?? ratingFilter,
            search: newFilters.search ?? search,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleExportExcel = () => {
        const url = `/operations/it/satisfaction/export${selectedCampaignId ? `?survey_id=${selectedCampaignId}` : ''}`;
        const a = document.createElement('a');
        a.href = url;
        a.download = `it_satisfaction_summary_${new Date().toISOString().slice(0, 10)}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        toast.success('Downloading Excel summary...');
    };

    const getScoreBadge = (score) => {
        if (score >= 4) {
            return {
                color: 'success',
                label: `${score} ★ (Satisfied)`,
                emoji: score === 5 ? '🤩' : '🙂',
                bg: 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
            };
        } else if (score === 3) {
            return {
                color: 'warning',
                label: '3 ★ (Neutral)',
                emoji: '😐',
                bg: 'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
            };
        } else {
            return {
                color: 'error',
                label: `${score} ★ (Under 3 - Dissatisfied)`,
                emoji: score === 1 ? '😠' : '🙁',
                bg: 'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
            };
        }
    };

    return (
        <AsideLayout title="IT Department Satisfaction Surveys">
            <Head title="IT Satisfaction Surveys" />

            <div className="space-y-6 max-w-7xl mx-auto">
                {/* Header with Title and Actions */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                                <StarIcon fontSize="medium" />
                            </span>
                            <div>
                                <h1 className="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100">
                                    IT Department Satisfaction Score & Ratings
                                </h1>
                                <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                    Track employee satisfaction ratings (1 to 5), manage survey active date windows, and resolve low-score feedback.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {permissions.can_export && (
                            <>
                                <Button
                                    variant="outlined"
                                    startIcon={<ExportIcon />}
                                    onClick={handleExportExcel}
                                    sx={{
                                        textTransform: 'none',
                                        borderRadius: 3,
                                        fontWeight: 700,
                                    }}
                                >
                                    Export Excel
                                </Button>

                                <Button
                                    variant="outlined"
                                    color="secondary"
                                    startIcon={<PdfIcon />}
                                    onClick={handleExportPdf}
                                    disabled={isExportingPdf}
                                    sx={{
                                        textTransform: 'none',
                                        borderRadius: 3,
                                        fontWeight: 700,
                                        borderColor: '#6366f1',
                                        color: '#6366f1',
                                        '&:hover': {
                                            borderColor: '#4f46e5',
                                            bgcolor: 'rgba(99, 102, 241, 0.04)'
                                        }
                                    }}
                                >
                                    {isExportingPdf ? 'Generating PDF...' : 'Export PDF'}
                                </Button>
                            </>
                        )}

                        {permissions.can_create && (
                            <Button
                                variant="contained"
                                startIcon={<AddIcon />}
                                onClick={handleOpenCreateCampaign}
                                sx={{
                                    textTransform: 'none',
                                    borderRadius: 3,
                                    fontWeight: 700,
                                    bgcolor: '#4f46e5',
                                    '&:hover': { bgcolor: '#4338ca' }
                                }}
                            >
                                New Survey Campaign
                            </Button>
                        )}
                    </div>
                </div>

                {/* Analytics KPI Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Average Score */}
                    <div className="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Average Score</span>
                            <span className="text-amber-400 text-lg">★</span>
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-slate-900 dark:text-slate-100">
                                {analytics.average_score || '0.0'}
                            </span>
                            <span className="text-sm font-semibold text-slate-400">/ 5.0</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            Based on {analytics.total_responses || 0} user submission(s)
                        </p>
                    </div>

                    {/* Total Responses */}
                    <div className="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Total Submissions</span>
                            <span className="text-indigo-600 dark:text-indigo-400">👥</span>
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-slate-900 dark:text-slate-100">
                                {analytics.total_responses || 0}
                            </span>
                            <span className="text-xs text-emerald-600 font-semibold">Recorded</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            Distinct users who submitted score
                        </p>
                    </div>

                    {/* Positive Satisfaction Rate */}
                    <div className="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Satisfaction Rate</span>
                            <ThumbUpIcon className="text-emerald-500" fontSize="small" />
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-emerald-600 dark:text-emerald-400">
                                {analytics.satisfaction_rate || 0}%
                            </span>
                            <span className="text-xs text-slate-400 font-medium">({analytics.high_score_count || 0} users)</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            Rated 4 or 5 stars (Satisfied)
                        </p>
                    </div>

                    {/* Low Score Alerts (< 3) */}
                    <div className="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Attention Needed</span>
                            <WarningIcon className="text-rose-500" fontSize="small" />
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-rose-600 dark:text-rose-400">
                                {analytics.low_score_count || 0}
                            </span>
                            <span className="text-xs text-rose-500 font-medium">Under 3 Stars</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            Requires review & feedback resolution
                        </p>
                    </div>
                </div>

                {/* Star Rating Breakdown & Active Campaign Overview */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Star Breakdown */}
                    <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-4">
                            Rating Distribution (1 to 5 Stars)
                        </h3>

                        <div className="space-y-3">
                            {[5, 4, 3, 2, 1].map((star) => {
                                const data = analytics.star_breakdown?.[star] || { count: 0, percentage: 0 };
                                return (
                                    <div key={star} className="flex items-center gap-3 text-xs">
                                        <div className="w-12 font-bold flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                            <span>{star}</span>
                                            <span className="text-amber-400">★</span>
                                        </div>
                                        <div className="flex-1">
                                            <LinearProgress
                                                variant="determinate"
                                                value={data.percentage}
                                                sx={{
                                                    height: 8,
                                                    borderRadius: 4,
                                                    bgcolor: 'rgba(0,0,0,0.05)',
                                                    '& .MuiLinearProgress-bar': {
                                                        bgcolor: star >= 4 ? '#10b981' : star === 3 ? '#f59e0b' : '#f43f5e',
                                                        borderRadius: 4,
                                                    }
                                                }}
                                            />
                                        </div>
                                        <div className="w-16 text-right font-semibold text-slate-500">
                                            {data.count} ({data.percentage}%)
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Active & Configured Campaigns */}
                    <div className="lg:col-span-2 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Survey Campaigns & Date Windows
                                </h3>
                                <span className="text-xs text-slate-500 font-medium">
                                    {campaigns.length} Campaign(s) configured
                                </span>
                            </div>

                            <div className="space-y-3 max-h-60 overflow-y-auto pr-1">
                                {campaigns.map((c) => {
                                    const isCurrentSelected = selectedCampaignId === c.id;
                                    return (
                                        <div
                                            key={c.id}
                                            className={`p-3.5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 ${
                                                isCurrentSelected
                                                    ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-950/30 ring-1 ring-indigo-500'
                                                    : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 hover:border-slate-300'
                                            }`}
                                        >
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-bold text-sm text-slate-900 dark:text-slate-100">
                                                        {c.title}
                                                    </span>
                                                    {c.is_active ? (
                                                        <Chip label="Active" color="success" size="small" sx={{ height: 20, fontSize: '0.65rem', fontWeight: 700 }} />
                                                    ) : (
                                                        <Chip label="Inactive" color="default" size="small" sx={{ height: 20, fontSize: '0.65rem' }} />
                                                    )}
                                                </div>

                                                <div className="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                    <span className="inline-flex items-center gap-1 font-semibold text-indigo-600 dark:text-indigo-400">
                                                        <EventIcon sx={{ fontSize: 14 }} />
                                                        {c.start_date_formatted} to {c.end_date_formatted}
                                                    </span>
                                                    <span>• Responses: <strong>{c.ratings_count}</strong></span>
                                                    <span>• Avg: <strong>{c.avg_rating} ★</strong></span>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-1 self-end sm:self-center">
                                                <Tooltip title="View results for this campaign">
                                                    <Button
                                                        size="small"
                                                        variant={isCurrentSelected ? "contained" : "outlined"}
                                                        onClick={() => {
                                                            setSelectedCampaignId(c.id);
                                                            handleFilterChange({ survey_id: c.id });
                                                        }}
                                                        sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem', py: 0.2 }}
                                                    >
                                                        {isCurrentSelected ? 'Viewing' : 'Select'}
                                                    </Button>
                                                </Tooltip>

                                                {permissions.can_update && (
                                                    <Tooltip title="Toggle Active / Inactive">
                                                        <IconButton size="small" onClick={() => handleToggleCampaign(c.id)}>
                                                            {c.is_active ? <CheckCircleIcon color="success" fontSize="small" /> : <CancelIcon color="disabled" fontSize="small" />}
                                                        </IconButton>
                                                    </Tooltip>
                                                )}

                                                {permissions.can_update && (
                                                    <Tooltip title="Edit Campaign Window">
                                                        <IconButton size="small" onClick={() => handleOpenEditCampaign(c)}>
                                                            <EditIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                )}

                                                {permissions.can_delete && (
                                                    <Tooltip title="Delete Campaign">
                                                        <IconButton size="small" color="error" onClick={() => handleDeleteCampaign(c.id)}>
                                                            <DeleteIcon fontSize="small" />
                                                        </IconButton>
                                                    </Tooltip>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}

                                {campaigns.length === 0 && (
                                    <div className="text-center py-6 text-slate-400 text-xs">
                                        No survey campaigns created yet. Click "New Survey Campaign" to add one.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Submissions & Ratings Filterable Table */}
                <div className="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div className="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
                                Recorded Ratings & Feedback
                            </h3>
                            <p className="text-xs text-slate-500">
                                User responses submitted via the dynamic login screen.
                            </p>
                        </div>

                        {/* Search & Rating Filter Controls */}
                        <div className="flex flex-wrap items-center gap-3">
                            <TextField
                                size="small"
                                placeholder="Search by user or feedback..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') handleFilterChange({ search });
                                }}
                                InputProps={{
                                    startAdornment: <SearchIcon fontSize="small" sx={{ mr: 1, color: 'text.secondary' }} />
                                }}
                                sx={{ width: 220 }}
                            />

                            <FormControl size="small" sx={{ width: 140 }}>
                                <InputLabel>Score Filter</InputLabel>
                                <Select
                                    label="Score Filter"
                                    value={ratingFilter}
                                    onChange={(e) => {
                                        setRatingFilter(e.target.value);
                                        handleFilterChange({ rating: e.target.value });
                                    }}
                                >
                                    <MenuItem value="">All Scores</MenuItem>
                                    <MenuItem value="5">5 ★ (Very Satisfied)</MenuItem>
                                    <MenuItem value="4">4 ★ (Satisfied)</MenuItem>
                                    <MenuItem value="3">3 ★ (Neutral)</MenuItem>
                                    <MenuItem value="2">2 ★ (Dissatisfied)</MenuItem>
                                    <MenuItem value="1">1 ★ (Very Dissatisfied)</MenuItem>
                                </Select>
                            </FormControl>

                            <Button
                                variant="contained"
                                onClick={() => handleFilterChange({ search, rating: ratingFilter })}
                                sx={{ textTransform: 'none', borderRadius: 2, bgcolor: '#4f46e5' }}
                            >
                                Apply Filter
                            </Button>
                        </div>
                    </div>

                    {/* Table Body */}
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200/80 dark:border-slate-800">
                                <tr>
                                    <th className="py-3.5 px-4 font-bold">User Name</th>
                                    <th className="py-3.5 px-4 font-bold">Satisfaction Rating (1-5)</th>
                                    <th className="py-3.5 px-4 font-bold">Criteria Breakdown</th>
                                    <th className="py-3.5 px-4 font-bold">Feedback / Comments</th>
                                    <th className="py-3.5 px-4 font-bold">Submission Date</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {ratings.data.map((r) => {
                                    const badge = getScoreBadge(r.rating);
                                    const isUnder3 = r.rating < 3;

                                    return (
                                        <tr key={r.id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                            {/* User Name & Avatar */}
                                            <td className="py-4 px-4">
                                                <div className="flex items-center gap-2.5">
                                                    <Avatar sx={{ width: 32, height: 32, bgcolor: isUnder3 ? '#f43f5e' : '#4f46e5', fontSize: '0.8rem', fontWeight: 'bold' }}>
                                                        {r.user_name ? r.user_name.charAt(0).toUpperCase() : 'U'}
                                                    </Avatar>
                                                    <div>
                                                        <div className="font-bold text-slate-900 dark:text-slate-100">
                                                            {r.user_name}
                                                        </div>
                                                        {r.user_email && (
                                                            <div className="text-[11px] text-slate-400">
                                                                {r.user_email}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Rating Score Badge */}
                                            <td className="py-4 px-4">
                                                <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border ${badge.bg}`}>
                                                    <span>{badge.emoji}</span>
                                                    <span>{badge.label}</span>
                                                </span>
                                            </td>

                                            {/* Criteria Aspects */}
                                            <td className="py-4 px-4">
                                                {r.aspect_ratings ? (
                                                    <div className="space-y-1 text-[11px]">
                                                        {r.aspect_ratings.speed > 0 && (
                                                            <div className="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                                                <span className="w-16">Speed:</span>
                                                                <span className="font-bold text-amber-500">{'★'.repeat(r.aspect_ratings.speed)}</span>
                                                            </div>
                                                        )}
                                                        {r.aspect_ratings.helpfulness > 0 && (
                                                            <div className="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                                                <span className="w-16">Helpful:</span>
                                                                <span className="font-bold text-amber-500">{'★'.repeat(r.aspect_ratings.helpfulness)}</span>
                                                            </div>
                                                        )}
                                                        {r.aspect_ratings.stability > 0 && (
                                                            <div className="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                                                <span className="w-16">Stability:</span>
                                                                <span className="font-bold text-amber-500">{'★'.repeat(r.aspect_ratings.stability)}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-slate-400 italic">Overall Only</span>
                                                )}
                                            </td>

                                            {/* Feedback / Comments */}
                                            <td className="py-4 px-4 max-w-xs">
                                                {r.feedback ? (
                                                    <div className={`p-2.5 rounded-xl text-xs ${
                                                        isUnder3
                                                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-900 font-medium'
                                                            : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300'
                                                    }`}>
                                                        {r.feedback}
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-slate-400 italic">No comments</span>
                                                )}
                                            </td>

                                            {/* Timestamp */}
                                            <td className="py-4 px-4 text-xs text-slate-500 font-medium whitespace-nowrap">
                                                {r.submitted_at}
                                            </td>
                                        </tr>
                                    );
                                })}

                                {ratings.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="py-10 text-center text-slate-400 text-sm">
                                            No rating submissions found for the selected criteria.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Campaign Create/Edit Dialog */}
            <Dialog 
                open={openCampaignModal} 
                onClose={() => setOpenCampaignModal(false)} 
                maxWidth="sm" 
                fullWidth
                PaperProps={{ sx: { borderRadius: 4, p: 1 } }}
            >
                <form onSubmit={handleSaveCampaign}>
                    <DialogTitle sx={{ fontWeight: 800 }}>
                        {editingCampaign ? 'Edit Survey Campaign' : 'Create New IT Satisfaction Survey'}
                    </DialogTitle>

                    <DialogContent dividers sx={{ display: 'flex', flexDirection: 'column', gap: 2.5, pt: 2 }}>
                        <TextField
                            label="Campaign Title"
                            fullWidth
                            required
                            value={campaignForm.title}
                            onChange={(e) => setCampaignForm({ ...campaignForm, title: e.target.value })}
                            placeholder="e.g. IT Department Satisfaction Survey - September"
                        />

                        <TextField
                            label="Badge Text"
                            fullWidth
                            value={campaignForm.badge_text}
                            onChange={(e) => setCampaignForm({ ...campaignForm, badge_text: e.target.value })}
                            placeholder="e.g. IT Satisfaction Survey"
                        />

                        <div className="grid grid-cols-2 gap-3">
                            <TextField
                                label="Start Date"
                                type="date"
                                fullWidth
                                required
                                InputLabelProps={{ shrink: true }}
                                value={campaignForm.start_date}
                                onChange={(e) => setCampaignForm({ ...campaignForm, start_date: e.target.value })}
                            />
                            <TextField
                                label="End Date"
                                type="date"
                                fullWidth
                                required
                                InputLabelProps={{ shrink: true }}
                                value={campaignForm.end_date}
                                onChange={(e) => setCampaignForm({ ...campaignForm, end_date: e.target.value })}
                            />
                        </div>

                        <TextField
                            label="Survey Description / Prompt"
                            fullWidth
                            multiline
                            rows={3}
                            value={campaignForm.description}
                            onChange={(e) => setCampaignForm({ ...campaignForm, description: e.target.value })}
                            placeholder="Please rate your overall experience and satisfaction with IT Department services."
                        />

                        <div className="flex flex-col gap-2 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <div>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                                    Excluded Departments (Dynamic JSON Scope)
                                </Typography>
                                <Typography variant="caption" sx={{ color: 'text.secondary' }}>
                                    Users in selected departments will be exempted and will never see the rating modal.
                                </Typography>
                            </div>
                            <div className="flex flex-wrap gap-1.5 mt-1 max-h-36 overflow-y-auto p-1">
                                {departments.map((dept) => {
                                    const excludedIds = campaignForm.target_scope?.excluded_department_ids || [];
                                    const isExcluded = excludedIds.includes(dept.id);
                                    return (
                                        <Chip
                                            key={dept.id}
                                            label={dept.name}
                                            size="small"
                                            clickable
                                            color={isExcluded ? "error" : "default"}
                                            variant={isExcluded ? "filled" : "outlined"}
                                            onClick={() => {
                                                const currentScope = campaignForm.target_scope || {};
                                                const newExcluded = isExcluded
                                                    ? excludedIds.filter(id => id !== dept.id)
                                                    : [...excludedIds, dept.id];
                                                setCampaignForm({
                                                    ...campaignForm,
                                                    target_scope: {
                                                        ...currentScope,
                                                        excluded_department_ids: newExcluded,
                                                    }
                                                });
                                            }}
                                            sx={{ fontSize: '0.75rem', fontWeight: isExcluded ? 700 : 400 }}
                                        />
                                    );
                                })}
                            </div>
                        </div>

                        {/* Detail Evaluation Criteria Builder */}
                        <div className="flex flex-col gap-2 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <div>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                                    Detailed Evaluation Criteria (Survey Form Aspects)
                                </Typography>
                                <Typography variant="caption" sx={{ color: 'text.secondary' }}>
                                    Define the specific service aspects users can rate (1 to 5 stars) in the popup modal.
                                </Typography>
                            </div>

                            <div className="space-y-2 mt-1">
                                {(campaignForm.criteria || []).map((item, idx) => (
                                    <div key={idx} className="flex items-center justify-between gap-2 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-bold text-indigo-600 dark:text-indigo-400">#{idx + 1}</span>
                                            <span className="text-xs font-semibold text-slate-800 dark:text-slate-200">{item.label}</span>
                                        </div>
                                        <IconButton
                                            size="small"
                                            color="error"
                                            onClick={() => {
                                                const updated = campaignForm.criteria.filter((_, i) => i !== idx);
                                                setCampaignForm({ ...campaignForm, criteria: updated });
                                            }}
                                        >
                                            <DeleteIcon sx={{ fontSize: 16 }} />
                                        </IconButton>
                                    </div>
                                ))}

                                {(!campaignForm.criteria || campaignForm.criteria.length === 0) && (
                                    <div className="text-xs text-slate-400 text-center py-2">
                                        No criteria added yet. Add at least 1 criteria below.
                                    </div>
                                )}
                            </div>

                            <div className="flex items-center gap-2 mt-2">
                                <TextField
                                    size="small"
                                    fullWidth
                                    placeholder="e.g. Ticket Resolution Time, Staff Communication"
                                    value={newCriteriaLabel}
                                    onChange={(e) => setNewCriteriaLabel(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            if (newCriteriaLabel.trim()) {
                                                const key = newCriteriaLabel.trim().toLowerCase().replace(/[^a-z0-9]/g, '_');
                                                const updated = [...(campaignForm.criteria || []), { key, label: newCriteriaLabel.trim() }];
                                                setCampaignForm({ ...campaignForm, criteria: updated });
                                                setNewCriteriaLabel('');
                                            }
                                        }
                                    }}
                                />
                                <Button
                                    size="small"
                                    variant="contained"
                                    onClick={() => {
                                        if (newCriteriaLabel.trim()) {
                                            const key = newCriteriaLabel.trim().toLowerCase().replace(/[^a-z0-9]/g, '_');
                                            const updated = [...(campaignForm.criteria || []), { key, label: newCriteriaLabel.trim() }];
                                            setCampaignForm({ ...campaignForm, criteria: updated });
                                            setNewCriteriaLabel('');
                                        }
                                    }}
                                    sx={{ textTransform: 'none', borderRadius: 2, whiteSpace: 'nowrap', bgcolor: '#4f46e5' }}
                                >
                                    + Add
                                </Button>
                            </div>
                        </div>

                        <div className="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <div>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                                    Active Status
                                </Typography>
                                <Typography variant="caption" sx={{ color: 'text.secondary' }}>
                                    Only active surveys within the date window pop up on login
                                </Typography>
                            </div>
                            <Switch
                                checked={campaignForm.is_active}
                                onChange={(e) => setCampaignForm({ ...campaignForm, is_active: e.target.checked })}
                                color="primary"
                            />
                        </div>
                    </DialogContent>

                    <DialogActions sx={{ p: 2 }}>
                        <Button 
                            onClick={() => setOpenCampaignModal(false)}
                            sx={{ textTransform: 'none', borderRadius: 2 }}
                        >
                            Cancel
                        </Button>
                        <Button 
                            type="submit" 
                            variant="contained"
                            sx={{ textTransform: 'none', borderRadius: 2, bgcolor: '#4f46e5' }}
                        >
                            {editingCampaign ? 'Save Changes' : 'Create Campaign'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
