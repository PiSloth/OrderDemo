import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
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
    Divider,
    IconButton,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    TextField,
    Alert,
    Tooltip,
    Stack,
    MenuItem,
    FormControl,
    InputLabel,
    Select,
    Pagination,
    Tabs,
    Tab,
    Accordion,
    AccordionSummary,
    AccordionDetails,
    Collapse,
    InputAdornment,
    Badge,
    Autocomplete,
} from '@mui/material';
import {
    ExpandMore as ExpandMoreIcon,
    Edit as EditIcon,
    Add as AddIcon,
    FilterList as FilterListIcon,
    Search as SearchIcon,
    Clear as ClearIcon,
    CheckCircle as CheckCircleIcon,
    Category as CategoryIcon,
    BugReport as BugReportIcon,
    Psychology as PsychologyIcon,
    CalendarToday as CalendarTodayIcon,
    Notes as NotesIcon,
    UnfoldMore as UnfoldMoreIcon,
    UnfoldLess as UnfoldLessIcon,
    Close as CloseIcon,
    Save as SaveIcon,
    ListAlt as ListAltIcon,
    PieChart as PieChartIcon,
} from '@mui/icons-material';
import toast from 'react-hot-toast';
import axios from 'axios';

export default function RootCauses({
    issues = { data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, links: [] },
    summary = [],
    rootCauses: initialRootCauses = [],
    suggestedCategories = [],
    filters = {},
    totalIssuesCount = 0,
    totalLoggedCount = 0,
}) {
    // Active Tab: 'list' or 'summary'
    const [activeTab, setActiveTab] = useState(filters.tab || 'list');

    // Filter states
    const [filterDate, setFilterDate] = useState(filters.root_cause_date || '');
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    // Root causes master state (can be updated dynamically when new one is created)
    const [rootCausesList, setRootCausesList] = useState(initialRootCauses);

    // Edit modal state
    const [editModal, setEditModal] = useState({
        open: false,
        issue: null,
        root_cause_id: '',
        remark: '',
        saving: false,
    });

    // Inline "Create New Root Cause" state
    const [showNewRootCauseForm, setShowNewRootCauseForm] = useState(false);
    const [newRootCause, setNewRootCause] = useState({
        name: '',
        category: 'Software',
        creating: false,
    });

    // Collapsed state for Tab 2 Issue Categories
    // Default: ALL issue categories are collapsed by default!
    const [expandedCategories, setExpandedCategories] = useState({});

    // Description read-more toggle map
    const [expandedDescriptions, setExpandedDescriptions] = useState({});

    // Handle filter submission
    const applyFilters = (dateVal = filterDate, searchVal = searchQuery) => {
        router.get(
            route('operation.it.issues.root-causes.index'),
            {
                root_cause_date: dateVal || undefined,
                search: searchVal || undefined,
                tab: activeTab,
                page: 1, // reset to page 1 on filter
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Handle quick preset filter
    const handleQuickPreset = (preset) => {
        const today = new Date();
        let formatted = '';

        if (preset === 'today') {
            formatted = today.toISOString().split('T')[0];
        } else if (preset === 'yesterday') {
            const y = new Date();
            y.setDate(today.getDate() - 1);
            formatted = y.toISOString().split('T')[0];
        } else if (preset === 'clear') {
            formatted = '';
        }

        setFilterDate(formatted);
        applyFilters(formatted, searchQuery);
    };

    // Handle pagination change (5 items per page)
    const handlePageChange = (event, value) => {
        router.get(
            route('operation.it.issues.root-causes.index'),
            {
                root_cause_date: filterDate || undefined,
                search: searchQuery || undefined,
                tab: activeTab,
                page: value,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Handle Tab change
    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
        // update query param silently
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newValue);
        window.history.replaceState({}, '', url);
    };

    // Open Edit Dialog for an issue
    const openEditDialog = (issue) => {
        const currentRootCause = issue.root_causes && issue.root_causes.length > 0 ? issue.root_causes[0] : null;
        setEditModal({
            open: true,
            issue: issue,
            root_cause_id: currentRootCause ? currentRootCause.id : '',
            remark: issue.root_cause_remark || currentRootCause?.remark || currentRootCause?.pivot?.remark || '',
            saving: false,
        });
        setShowNewRootCauseForm(false);
        setNewRootCause({ name: '', category: 'Software', creating: false });
    };

    // Close Edit Dialog
    const closeEditDialog = () => {
        setEditModal({
            open: false,
            issue: null,
            root_cause_id: '',
            remark: '',
            saving: false,
        });
        setShowNewRootCauseForm(false);
    };

    // Save issue's root cause and remark
    const handleSaveIssueRootCause = (e) => {
        e.preventDefault();
        if (!editModal.root_cause_id) {
            toast.error('Please select or create a root cause.');
            return;
        }

        setEditModal((prev) => ({ ...prev, saving: true }));

        router.post(
            route('operation.it.issues.root-cause.update', editModal.issue.id),
            {
                root_cause_id: editModal.root_cause_id,
                remark: editModal.remark,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Root cause and remark updated successfully!');
                    closeEditDialog();
                },
                onError: (errs) => {
                    const firstErr = Object.values(errs)[0];
                    toast.error(firstErr || 'Failed to update root cause.');
                    setEditModal((prev) => ({ ...prev, saving: false }));
                },
            }
        );
    };

    // Inline create a new root cause
    const handleCreateNewRootCause = async () => {
        if (!newRootCause.name.trim()) {
            toast.error('Root cause name is required.');
            return;
        }
        if (!newRootCause.category.trim()) {
            toast.error('Root cause category is required.');
            return;
        }

        setNewRootCause((prev) => ({ ...prev, creating: true }));

        try {
            const response = await axios.post(
                route('operation.it.issues.root-causes.store'),
                {
                    name: newRootCause.name.trim(),
                    category: newRootCause.category.trim(),
                },
                {
                    headers: { 'Accept': 'application/json' },
                }
            );

            if (response.data?.success && response.data?.rootCause) {
                const created = response.data.rootCause;
                // Add to local list
                setRootCausesList((prev) => [...prev, created]);
                // Automatically select newly created root cause
                setEditModal((prev) => ({ ...prev, root_cause_id: created.id }));
                // Close creation form
                setShowNewRootCauseForm(false);
                setNewRootCause({ name: '', category: 'Software', creating: false });
                toast.success(`Root cause "${created.name}" created and selected!`);
            }
        } catch (error) {
            const msg = error.response?.data?.errors?.name?.[0] || error.response?.data?.message || 'Failed to create root cause.';
            toast.error(msg);
            setNewRootCause((prev) => ({ ...prev, creating: false }));
        }
    };

    // Toggle accordion state in Tab 2
    const toggleAccordion = (key) => {
        setExpandedCategories((prev) => ({
            ...prev,
            [key]: !prev[key],
        }));
    };

    // Expand all or Collapse all in Tab 2
    const handleToggleAllAccordions = (expandAll) => {
        const nextState = {};
        summary.forEach((rcGroup, rcIdx) => {
            rcGroup.issue_categories.forEach((icGroup, icIdx) => {
                const key = `${rcIdx}-${icIdx}`;
                nextState[key] = expandAll;
            });
        });
        setExpandedCategories(nextState);
    };

    return (
        <AsideLayout>
            <Head title="IT Issue Root Cause Analysis" />

            <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: '1400px', mx: 'auto' }}>
                {/* Header Banner */}
                <Box sx={{ mb: 3, display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: 2 }}>
                    <Box>
                        <Typography variant="h5" fontWeight="bold" sx={{ color: '#1e293b', display: 'flex', alignItems: 'center', gap: 1.5 }}>
                            <PsychologyIcon sx={{ color: '#2563eb', fontSize: '2rem' }} />
                            IT Issue Root Cause Analysis
                        </Typography>
                        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                            Investigate issue root causes, track diagnostic remarks, and analyze category breakdowns.
                        </Typography>
                    </Box>

                    {/* Quick Stats Badges */}
                    <Stack direction="row" spacing={1.5} alignItems="center">
                        <Chip
                            icon={<BugReportIcon fontSize="small" />}
                            label={`Total Issues: ${totalIssuesCount}`}
                            variant="outlined"
                            sx={{ fontWeight: 600, borderColor: '#cbd5e1', bgcolor: '#ffffff' }}
                        />
                        <Chip
                            icon={<CheckCircleIcon fontSize="small" />}
                            label={`Logged Root Causes: ${totalLoggedCount}`}
                            color="primary"
                            sx={{ fontWeight: 600 }}
                        />
                    </Stack>
                </Box>

                {/* Top Filter Bar */}
                <Card sx={{ mb: 3, borderRadius: 2.5, boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                    <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
                        <Grid container spacing={2} alignItems="center">
                            {/* Filter by Root Cause Created Date */}
                            <Grid item xs={12} sm={6} md={4}>
                                <TextField
                                    fullWidth
                                    size="small"
                                    type="date"
                                    label="Filter by Root Cause Created Date"
                                    InputLabelProps={{ shrink: true }}
                                    value={filterDate}
                                    onChange={(e) => {
                                        setFilterDate(e.target.value);
                                        applyFilters(e.target.value, searchQuery);
                                    }}
                                    InputProps={{
                                        startAdornment: (
                                            <InputAdornment position="start">
                                                <CalendarTodayIcon fontSize="small" color="action" />
                                            </InputAdornment>
                                        ),
                                        endAdornment: filterDate ? (
                                            <InputAdornment position="end">
                                                <IconButton
                                                    size="small"
                                                    onClick={() => {
                                                        setFilterDate('');
                                                        applyFilters('', searchQuery);
                                                    }}
                                                >
                                                    <ClearIcon fontSize="small" />
                                                </IconButton>
                                            </InputAdornment>
                                        ) : null,
                                    }}
                                />
                            </Grid>

                            {/* Search Filter */}
                            <Grid item xs={12} sm={6} md={4}>
                                <TextField
                                    fullWidth
                                    size="small"
                                    placeholder="Search title, description, or remark..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            applyFilters(filterDate, searchQuery);
                                        }
                                    }}
                                    InputProps={{
                                        startAdornment: (
                                            <InputAdornment position="start">
                                                <SearchIcon fontSize="small" color="action" />
                                            </InputAdornment>
                                        ),
                                        endAdornment: searchQuery ? (
                                            <InputAdornment position="end">
                                                <IconButton
                                                    size="small"
                                                    onClick={() => {
                                                        setSearchQuery('');
                                                        applyFilters(filterDate, '');
                                                    }}
                                                >
                                                    <ClearIcon fontSize="small" />
                                                </IconButton>
                                            </InputAdornment>
                                        ) : null,
                                    }}
                                />
                            </Grid>

                            {/* Quick Presets & Actions */}
                            <Grid item xs={12} md={4}>
                                <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap">
                                    <Button
                                        size="small"
                                        variant={filterDate ? 'outlined' : 'contained'}
                                        color="inherit"
                                        onClick={() => handleQuickPreset('clear')}
                                        sx={{ textTransform: 'none', borderRadius: 2 }}
                                    >
                                        All Dates
                                    </Button>
                                    <Button
                                        size="small"
                                        variant="outlined"
                                        onClick={() => handleQuickPreset('today')}
                                        sx={{ textTransform: 'none', borderRadius: 2 }}
                                    >
                                        Today
                                    </Button>
                                    <Button
                                        size="small"
                                        variant="outlined"
                                        onClick={() => handleQuickPreset('yesterday')}
                                        sx={{ textTransform: 'none', borderRadius: 2 }}
                                    >
                                        Yesterday
                                    </Button>
                                    <Button
                                        size="small"
                                        variant="contained"
                                        color="primary"
                                        onClick={() => applyFilters(filterDate, searchQuery)}
                                        startIcon={<FilterListIcon />}
                                        sx={{ textTransform: 'none', borderRadius: 2 }}
                                    >
                                        Filter
                                    </Button>
                                </Stack>
                            </Grid>
                        </Grid>

                        {/* Active Filter Indicator */}
                        {filterDate && (
                            <Box sx={{ mt: 1.5, display: 'flex', alignItems: 'center', gap: 1 }}>
                                <Typography variant="caption" color="text.secondary">
                                    Active Date Filter:
                                </Typography>
                                <Chip
                                    size="small"
                                    color="info"
                                    label={`Root Cause Created Date: ${filterDate}`}
                                    onDelete={() => {
                                        setFilterDate('');
                                        applyFilters('', searchQuery);
                                    }}
                                />
                            </Box>
                        )}
                    </CardContent>
                </Card>

                {/* Tabs Switcher */}
                <Paper sx={{ mb: 3, borderRadius: 2.5, overflow: 'hidden', borderBottom: '1px solid #e2e8f0' }}>
                    <Tabs
                        value={activeTab}
                        onChange={handleTabChange}
                        textColor="primary"
                        indicatorColor="primary"
                        sx={{
                            bgcolor: '#f8fafc',
                            px: 2,
                            '& .MuiTab-root': {
                                textTransform: 'none',
                                fontWeight: 600,
                                fontSize: '0.95rem',
                                minHeight: 48,
                            },
                        }}
                    >
                        <Tab
                            value="list"
                            icon={<ListAltIcon />}
                            iconPosition="start"
                            label={`Issues Root Cause List (${issues.total || 0})`}
                        />
                        <Tab
                            value="summary"
                            icon={<PieChartIcon />}
                            iconPosition="start"
                            label={`Root Cause Summary (${summary.length} Categories)`}
                        />
                    </Tabs>
                </Paper>

                {/* TAB 1: Issues List View (5 items per page) */}
                {activeTab === 'list' && (
                    <Box>
                        <Card sx={{ borderRadius: 2.5, boxShadow: '0 1px 3px rgba(0,0,0,0.06)', overflow: 'hidden' }}>
                            <TableContainer>
                                <Table sx={{ minWidth: 750 }}>
                                    <TableHead sx={{ bgcolor: '#f1f5f9' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700, width: '22%' }}>Issue Title</TableCell>
                                            <TableCell sx={{ fontWeight: 700, width: '26%' }}>Issue Description</TableCell>
                                            <TableCell sx={{ fontWeight: 700, width: '20%' }}>Root Cause</TableCell>
                                            <TableCell sx={{ fontWeight: 700, width: '22%' }}>Root Cause Remark</TableCell>
                                            <TableCell align="center" sx={{ fontWeight: 700, width: '10%' }}>Actions</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {issues.data && issues.data.length > 0 ? (
                                            issues.data.map((issue) => {
                                                const assignedRootCause = issue.root_causes && issue.root_causes.length > 0
                                                    ? issue.root_causes[0]
                                                    : null;
                                                const remark = issue.root_cause_remark || assignedRootCause?.remark || assignedRootCause?.pivot?.remark;
                                                const loggedDate = assignedRootCause?.pivot?.created_at
                                                    ? new Date(assignedRootCause.pivot.created_at).toLocaleDateString()
                                                    : null;

                                                const isDescExpanded = !!expandedDescriptions[issue.id];
                                                const isLongDesc = (issue.description || '').length > 130;

                                                return (
                                                    <TableRow
                                                        key={issue.id}
                                                        hover
                                                        sx={{
                                                            '&:last-child td, &:last-child th': { border: 0 },
                                                            transition: 'background-color 0.15s ease',
                                                        }}
                                                    >
                                                        {/* Issue Title & Meta */}
                                                        <TableCell verticalAlign="top">
                                                            <Box>
                                                                <Typography variant="subtitle2" fontWeight="700" color="#0f172a">
                                                                    #{issue.id} {issue.title}
                                                                </Typography>
                                                                <Stack direction="row" spacing={1} sx={{ mt: 0.75 }} flexWrap="wrap">
                                                                    {issue.category && (
                                                                        <Chip
                                                                            size="small"
                                                                            label={issue.category.name}
                                                                            sx={{
                                                                                fontSize: '0.72rem',
                                                                                height: 22,
                                                                                bgcolor: issue.category.is_erp ? '#eff6ff' : '#f1f5f9',
                                                                                color: issue.category.is_erp ? '#1d4ed8' : '#475569',
                                                                                fontWeight: 600,
                                                                            }}
                                                                        />
                                                                    )}
                                                                    {issue.status && (
                                                                        <Chip
                                                                            size="small"
                                                                            label={issue.status.name}
                                                                            sx={{
                                                                                fontSize: '0.72rem',
                                                                                height: 22,
                                                                                bgcolor: issue.status.code === 'CLOSED' ? '#ecfdf5' : '#fef3c7',
                                                                                color: issue.status.code === 'CLOSED' ? '#047857' : '#b45309',
                                                                                fontWeight: 600,
                                                                            }}
                                                                        />
                                                                    )}
                                                                </Stack>
                                                            </Box>
                                                        </TableCell>

                                                        {/* Issue Description */}
                                                        <TableCell verticalAlign="top">
                                                            <Box>
                                                                <Typography
                                                                    variant="body2"
                                                                    color="text.secondary"
                                                                    sx={{
                                                                        whiteSpace: 'pre-wrap',
                                                                        wordBreak: 'break-word',
                                                                        display: isDescExpanded ? 'block' : '-webkit-box',
                                                                        WebkitLineClamp: isDescExpanded ? 'unset' : 3,
                                                                        WebkitBoxOrient: 'vertical',
                                                                        overflow: 'hidden',
                                                                    }}
                                                                >
                                                                    {issue.description || '—'}
                                                                </Typography>
                                                                {isLongDesc && (
                                                                    <Button
                                                                        size="small"
                                                                        variant="text"
                                                                        onClick={() =>
                                                                            setExpandedDescriptions((prev) => ({
                                                                                ...prev,
                                                                                [issue.id]: !prev[issue.id],
                                                                            }))
                                                                        }
                                                                        sx={{
                                                                            p: 0,
                                                                            mt: 0.5,
                                                                            fontSize: '0.75rem',
                                                                            textTransform: 'none',
                                                                            fontWeight: 600,
                                                                        }}
                                                                    >
                                                                        {isDescExpanded ? 'Show less' : 'Read more...'}
                                                                    </Button>
                                                                )}
                                                            </Box>
                                                        </TableCell>

                                                        {/* Root Cause Type & Category */}
                                                        <TableCell verticalAlign="top">
                                                            {assignedRootCause ? (
                                                                <Box>
                                                                    <Typography variant="body2" fontWeight="700" color="#1e293b">
                                                                        {assignedRootCause.name}
                                                                    </Typography>
                                                                    <Stack direction="row" spacing={0.75} alignItems="center" sx={{ mt: 0.5 }}>
                                                                        <Chip
                                                                            size="small"
                                                                            icon={<CategoryIcon style={{ fontSize: '0.75rem' }} />}
                                                                            label={assignedRootCause.category || 'General'}
                                                                            sx={{
                                                                                height: 20,
                                                                                fontSize: '0.7rem',
                                                                                bgcolor: '#ede9fe',
                                                                                color: '#6d28d9',
                                                                                fontWeight: 600,
                                                                            }}
                                                                        />
                                                                        {loggedDate && (
                                                                            <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.7rem' }}>
                                                                                ({loggedDate})
                                                                            </Typography>
                                                                        )}
                                                                    </Stack>
                                                                </Box>
                                                            ) : (
                                                                <Chip
                                                                    size="small"
                                                                    label="Not assigned"
                                                                    variant="outlined"
                                                                    sx={{ color: '#94a3b8', borderColor: '#cbd5e1', fontSize: '0.75rem' }}
                                                                />
                                                            )}
                                                        </TableCell>

                                                        {/* Root Cause Remark */}
                                                        <TableCell verticalAlign="top">
                                                            {remark ? (
                                                                <Box
                                                                    sx={{
                                                                        p: 1.25,
                                                                        borderRadius: 1.5,
                                                                        bgcolor: '#f8fafc',
                                                                        borderLeft: '3px solid #3b82f6',
                                                                    }}
                                                                >
                                                                    <Typography variant="body2" color="#334155" sx={{ fontStyle: 'italic', wordBreak: 'break-word' }}>
                                                                        "{remark}"
                                                                    </Typography>
                                                                </Box>
                                                            ) : (
                                                                <Typography variant="body2" color="#94a3b8">
                                                                    — No remark —
                                                                </Typography>
                                                            )}
                                                        </TableCell>

                                                        {/* Edit Action Button */}
                                                        <TableCell align="center" verticalAlign="middle">
                                                            <Tooltip title="Edit Root Cause & Remark">
                                                                <Button
                                                                    size="small"
                                                                    variant="outlined"
                                                                    color="primary"
                                                                    startIcon={<EditIcon fontSize="small" />}
                                                                    onClick={() => openEditDialog(issue)}
                                                                    sx={{
                                                                        borderRadius: 2,
                                                                        textTransform: 'none',
                                                                        fontSize: '0.75rem',
                                                                        fontWeight: 600,
                                                                        whiteSpace: 'nowrap',
                                                                    }}
                                                                >
                                                                    Edit
                                                                </Button>
                                                            </Tooltip>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={5} align="center" sx={{ py: 6 }}>
                                                    <PsychologyIcon sx={{ fontSize: 48, color: '#cbd5e1', mb: 1 }} />
                                                    <Typography variant="subtitle1" fontWeight="bold" color="text.secondary">
                                                        No issues found matching your filters.
                                                    </Typography>
                                                    <Typography variant="body2" color="text.secondary">
                                                        Try adjusting the root cause created date or clearing the search keyword.
                                                    </Typography>
                                                    {filterDate && (
                                                        <Button
                                                            size="small"
                                                            variant="text"
                                                            onClick={() => {
                                                                setFilterDate('');
                                                                applyFilters('', searchQuery);
                                                            }}
                                                            sx={{ mt: 1, textTransform: 'none' }}
                                                        >
                                                            Clear Date Filter
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>

                            {/* Pagination (5 items per page) */}
                            {issues.last_page > 1 && (
                                <Box
                                    sx={{
                                        p: 2,
                                        display: 'flex',
                                        flexWrap: 'wrap',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        gap: 2,
                                        bgcolor: '#f8fafc',
                                        borderTop: '1px solid #e2e8f0',
                                    }}
                                >
                                    <Typography variant="body2" color="text.secondary">
                                        Showing <strong>{issues.from || 0}</strong> to <strong>{issues.to || 0}</strong> of <strong>{issues.total}</strong> issues (5 items per page)
                                    </Typography>
                                    <Pagination
                                        count={issues.last_page}
                                        page={issues.current_page}
                                        onChange={handlePageChange}
                                        color="primary"
                                        shape="rounded"
                                        size="medium"
                                        showFirstButton
                                        showLastButton
                                    />
                                </Box>
                            )}
                        </Card>
                    </Box>
                )}

                {/* TAB 2: Root Cause Summary View */}
                {activeTab === 'summary' && (
                    <Box>
                        {/* Summary Header & Global Expand/Collapse Toggle */}
                        <Box sx={{ mb: 2.5, display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 1.5 }}>
                            <Box>
                                <Typography variant="h6" fontWeight="bold" color="#1e293b">
                                    Root Cause Breakdown by Category
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    Grouped by <strong>Root Cause Category</strong>, then by <strong>Issue Category</strong> (collapsed by default).
                                </Typography>
                            </Box>

                            <Stack direction="row" spacing={1}>
                                <Button
                                    size="small"
                                    variant="outlined"
                                    startIcon={<UnfoldMoreIcon />}
                                    onClick={() => handleToggleAllAccordions(true)}
                                    sx={{ textTransform: 'none', borderRadius: 2 }}
                                >
                                    Expand All
                                </Button>
                                <Button
                                    size="small"
                                    variant="outlined"
                                    startIcon={<UnfoldLessIcon />}
                                    onClick={() => handleToggleAllAccordions(false)}
                                    sx={{ textTransform: 'none', borderRadius: 2 }}
                                >
                                    Collapse All
                                </Button>
                            </Stack>
                        </Box>

                        {/* Summary Groups */}
                        {summary && summary.length > 0 ? (
                            <Stack spacing={3}>
                                {summary.map((rcCatGroup, rcIdx) => (
                                    <Card
                                        key={rcCatGroup.root_cause_category}
                                        sx={{
                                            borderRadius: 2.5,
                                            boxShadow: '0 2px 6px rgba(0,0,0,0.06)',
                                            border: '1px solid #e2e8f0',
                                            overflow: 'hidden',
                                        }}
                                    >
                                        {/* Level 1: Root Cause Category Header */}
                                        <Box
                                            sx={{
                                                p: 2,
                                                bgcolor: '#f8fafc',
                                                borderBottom: '1px solid #e2e8f0',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'space-between',
                                                flexWrap: 'wrap',
                                                gap: 1.5,
                                            }}
                                        >
                                            <Stack direction="row" spacing={1.5} alignItems="center">
                                                <Box
                                                    sx={{
                                                        width: 38,
                                                        height: 38,
                                                        borderRadius: 2,
                                                        bgcolor: '#dbeafe',
                                                        color: '#1d4ed8',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                    }}
                                                >
                                                    <CategoryIcon fontSize="small" />
                                                </Box>
                                                <Box>
                                                    <Typography variant="subtitle1" fontWeight="bold" color="#0f172a">
                                                        Root Cause Category: {rcCatGroup.root_cause_category}
                                                    </Typography>
                                                    <Typography variant="caption" color="text.secondary">
                                                        {rcCatGroup.issue_categories.length} issue categories affected
                                                    </Typography>
                                                </Box>
                                            </Stack>

                                            <Chip
                                                label={`${rcCatGroup.total_count} Issues`}
                                                color="primary"
                                                sx={{ fontWeight: 700, fontSize: '0.825rem' }}
                                            />
                                        </Box>

                                        {/* Level 2: Nested Issue Categories (Collapsed by Default) */}
                                        <CardContent sx={{ p: 2, '&:last-child': { pb: 2 } }}>
                                            <Stack spacing={1.5}>
                                                {rcCatGroup.issue_categories.map((icGroup, icIdx) => {
                                                    const accordionKey = `${rcIdx}-${icIdx}`;
                                                    const isExpanded = !!expandedCategories[accordionKey];

                                                    return (
                                                        <Accordion
                                                            key={icGroup.issue_category_name}
                                                            expanded={isExpanded}
                                                            onChange={() => toggleAccordion(accordionKey)}
                                                            disableGutters
                                                            elevation={0}
                                                            sx={{
                                                                border: '1px solid #e2e8f0',
                                                                borderRadius: '8px !important',
                                                                '&:before': { display: 'none' },
                                                                overflow: 'hidden',
                                                            }}
                                                        >
                                                            {/* Issue Category Summary Header */}
                                                            <AccordionSummary
                                                                expandIcon={<ExpandMoreIcon />}
                                                                sx={{
                                                                    bgcolor: isExpanded ? '#f1f5f9' : '#ffffff',
                                                                    transition: 'background-color 0.2s',
                                                                    '&:hover': { bgcolor: '#f8fafc' },
                                                                    px: 2,
                                                                    minHeight: 48,
                                                                }}
                                                            >
                                                                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%', pr: 1.5 }}>
                                                                    <Stack direction="row" spacing={1.5} alignItems="center">
                                                                        <Typography variant="body2" fontWeight="700" color="#334155">
                                                                            {icGroup.issue_category_name}
                                                                        </Typography>
                                                                        <Chip
                                                                            size="small"
                                                                            label={`${icGroup.root_causes.length} distinct root causes`}
                                                                            sx={{ height: 20, fontSize: '0.7rem', bgcolor: '#f1f5f9' }}
                                                                        />
                                                                    </Stack>

                                                                    <Chip
                                                                        size="small"
                                                                        label={`${icGroup.total_count} issue${icGroup.total_count === 1 ? '' : 's'}`}
                                                                        sx={{
                                                                            height: 22,
                                                                            fontWeight: 700,
                                                                            fontSize: '0.75rem',
                                                                            bgcolor: isExpanded ? '#e0e7ff' : '#f1f5f9',
                                                                            color: isExpanded ? '#3730a3' : '#475569',
                                                                        }}
                                                                    />
                                                                </Box>
                                                            </AccordionSummary>

                                                            {/* Level 3: Root causes and counts inside this issue category */}
                                                            <AccordionDetails sx={{ p: 2, bgcolor: '#fafafa', borderTop: '1px solid #e2e8f0' }}>
                                                                <Table size="small">
                                                                    <TableHead>
                                                                        <TableRow>
                                                                            <TableCell sx={{ fontWeight: 600, color: '#64748b' }}>Root Cause Name</TableCell>
                                                                            <TableCell align="right" sx={{ fontWeight: 600, color: '#64748b' }}>Issue Count</TableCell>
                                                                            <TableCell align="right" sx={{ fontWeight: 600, color: '#64748b', width: '25%' }}>Share in Category</TableCell>
                                                                        </TableRow>
                                                                    </TableHead>
                                                                    <TableBody>
                                                                        {icGroup.root_causes.map((rcItem) => {
                                                                            const percent = icGroup.total_count > 0
                                                                                ? Math.round((rcItem.count / icGroup.total_count) * 100)
                                                                                : 0;

                                                                            return (
                                                                                <TableRow key={rcItem.name} hover>
                                                                                    <TableCell sx={{ fontWeight: 600, color: '#1e293b' }}>
                                                                                        <Stack direction="row" spacing={1} alignItems="center">
                                                                                            <BugReportIcon sx={{ fontSize: '1rem', color: '#64748b' }} />
                                                                                            <span>{rcItem.name}</span>
                                                                                        </Stack>
                                                                                    </TableCell>
                                                                                    <TableCell align="right">
                                                                                        <Chip
                                                                                            size="small"
                                                                                            label={rcItem.count}
                                                                                            color="primary"
                                                                                            variant="outlined"
                                                                                            sx={{ fontWeight: 700, height: 20, minWidth: 28 }}
                                                                                        />
                                                                                    </TableCell>
                                                                                    <TableCell align="right">
                                                                                        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 1 }}>
                                                                                            <Box sx={{ width: 80, bgcolor: '#e2e8f0', borderRadius: 1, height: 6, overflow: 'hidden' }}>
                                                                                                <Box
                                                                                                    sx={{
                                                                                                        width: `${percent}%`,
                                                                                                        bgcolor: '#3b82f6',
                                                                                                        height: '100%',
                                                                                                        borderRadius: 1,
                                                                                                    }}
                                                                                                />
                                                                                            </Box>
                                                                                            <Typography variant="caption" fontWeight="bold" color="text.secondary">
                                                                                                {percent}%
                                                                                            </Typography>
                                                                                        </Box>
                                                                                    </TableCell>
                                                                                </TableRow>
                                                                            );
                                                                        })}
                                                                    </TableBody>
                                                                </Table>
                                                            </AccordionDetails>
                                                        </Accordion>
                                                    );
                                                })}
                                            </Stack>
                                        </CardContent>
                                    </Card>
                                ))}
                            </Stack>
                        ) : (
                            <Card sx={{ p: 6, textAlign: 'center', borderRadius: 2.5 }}>
                                <PieChartIcon sx={{ fontSize: 48, color: '#cbd5e1', mb: 1 }} />
                                <Typography variant="subtitle1" fontWeight="bold" color="text.secondary">
                                    No root cause logs recorded yet.
                                </Typography>
                                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                                    Assign root causes to issues in the Issues List tab to see category analytics here.
                                </Typography>
                            </Card>
                        )}
                    </Box>
                )}
            </Box>

            {/* EDIT ROOT CAUSE & REMARK MODAL */}
            <Dialog
                open={editModal.open}
                onClose={closeEditDialog}
                maxWidth="sm"
                fullWidth
                PaperProps={{ sx: { borderRadius: 3 } }}
            >
                <form onSubmit={handleSaveIssueRootCause}>
                    <DialogTitle sx={{ m: 0, p: 2.5, bgcolor: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                            <Box>
                                <Typography variant="h6" fontWeight="bold" color="#0f172a">
                                    Edit Root Cause & Remark
                                </Typography>
                                {editModal.issue && (
                                    <Typography variant="caption" color="text.secondary">
                                        Issue #{editModal.issue.id} &bull; {editModal.issue.title}
                                    </Typography>
                                )}
                            </Box>
                            <IconButton onClick={closeEditDialog} size="small">
                                <CloseIcon fontSize="small" />
                            </IconButton>
                        </Box>
                    </DialogTitle>

                    <DialogContent dividers sx={{ p: 3 }}>
                        <Stack spacing={2.5}>
                            {/* Issue Context Box */}
                            {editModal.issue && (
                                <Box sx={{ p: 2, bgcolor: '#f1f5f9', borderRadius: 2 }}>
                                    <Typography variant="caption" color="text.secondary" fontWeight="bold">
                                        ISSUE DETAILS
                                    </Typography>
                                    <Typography variant="body2" fontWeight="600" color="#1e293b" sx={{ mt: 0.5 }}>
                                        {editModal.issue.title}
                                    </Typography>
                                    <Typography
                                        variant="caption"
                                        color="text.secondary"
                                        sx={{
                                            display: '-webkit-box',
                                            WebkitLineClamp: 2,
                                            WebkitBoxOrient: 'vertical',
                                            overflow: 'hidden',
                                            mt: 0.5,
                                        }}
                                    >
                                        {editModal.issue.description}
                                    </Typography>
                                </Box>
                            )}

                            {/* Root Cause Dropdown & Create Option */}
                            <Box>
                                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1 }}>
                                    <Typography variant="body2" fontWeight="700" color="#334155">
                                        Root Cause Type <span style={{ color: '#ef4444' }}>*</span>
                                    </Typography>

                                    {/* Option to create new root cause if not found */}
                                    <Button
                                        size="small"
                                        variant="text"
                                        startIcon={showNewRootCauseForm ? <CloseIcon /> : <AddIcon />}
                                        onClick={() => setShowNewRootCauseForm((prev) => !prev)}
                                        sx={{ textTransform: 'none', fontSize: '0.8rem', fontWeight: 600 }}
                                    >
                                        {showNewRootCauseForm ? 'Cancel New Root Cause' : '+ Create New Root Cause'}
                                    </Button>
                                </Box>

                                {/* Autocomplete root cause selection */}
                                <Autocomplete
                                    id="root-cause-select-autocomplete"
                                    options={rootCausesList}
                                    getOptionLabel={(option) => {
                                        if (!option) return '';
                                        if (typeof option === 'string') return option;
                                        return option.name || '';
                                    }}
                                    value={rootCausesList.find((rc) => String(rc.id) === String(editModal.root_cause_id)) || null}
                                    onChange={(event, newValue) => {
                                        setEditModal((prev) => ({
                                            ...prev,
                                            root_cause_id: newValue ? newValue.id : '',
                                        }));
                                    }}
                                    isOptionEqualToValue={(option, value) => String(option?.id) === String(value?.id)}
                                    renderInput={(params) => (
                                        <TextField
                                            {...params}
                                            size="small"
                                            placeholder="Click or type to search root cause..."
                                            error={!editModal.root_cause_id && editModal.saving}
                                            helperText={!editModal.root_cause_id && editModal.saving ? 'Root cause is required' : ''}
                                        />
                                    )}
                                    renderOption={(props, option) => {
                                        const { key, ...otherProps } = props;
                                        return (
                                            <li key={key || option.id} {...otherProps}>
                                                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%', py: 0.5 }}>
                                                    <Typography variant="body2" fontWeight="600" color="#1e293b">
                                                        {option.name}
                                                    </Typography>
                                                    <Chip
                                                        size="small"
                                                        label={option.category || 'General'}
                                                        sx={{
                                                            ml: 1.5,
                                                            height: 20,
                                                            fontSize: '0.7rem',
                                                            bgcolor: '#eff6ff',
                                                            color: '#1d4ed8',
                                                            fontWeight: 600,
                                                            pointerEvents: 'none',
                                                        }}
                                                    />
                                                </Box>
                                            </li>
                                        );
                                    }}
                                    noOptionsText={
                                        <Box sx={{ py: 1, textAlign: 'center' }}>
                                            <Typography variant="body2" color="text.secondary">
                                                No matching root cause found.
                                            </Typography>
                                            <Button
                                                size="small"
                                                variant="text"
                                                startIcon={<AddIcon />}
                                                onClick={() => {
                                                    setShowNewRootCauseForm(true);
                                                }}
                                                sx={{ mt: 0.5, textTransform: 'none', fontWeight: 600 }}
                                            >
                                                + Create New Root Cause
                                            </Button>
                                        </Box>
                                    }
                                    fullWidth
                                />
                            </Box>

                            {/* Collapsible Inline "Create New Root Cause" Form */}
                            <Collapse in={showNewRootCauseForm}>
                                <Card sx={{ p: 2, bgcolor: '#f8fafc', border: '1px dashed #3b82f6', borderRadius: 2 }}>
                                    <Typography variant="subtitle2" fontWeight="bold" color="#1d4ed8" sx={{ mb: 1.5 }}>
                                        Create New Root Cause
                                    </Typography>
                                    <Stack spacing={2}>
                                        <TextField
                                            size="small"
                                            label="Root Cause Name"
                                            placeholder="e.g. Memory Leak, Database Timeout, Human Configuration Error"
                                            fullWidth
                                            required
                                            value={newRootCause.name}
                                            onChange={(e) => setNewRootCause({ ...newRootCause, name: e.target.value })}
                                        />

                                        <Box>
                                            <TextField
                                                size="small"
                                                label="Root Cause Category"
                                                placeholder="e.g. Software, Hardware, Human Error, Network"
                                                fullWidth
                                                required
                                                value={newRootCause.category}
                                                onChange={(e) => setNewRootCause({ ...newRootCause, category: e.target.value })}
                                            />
                                            {/* Quick Category Suggestions */}
                                            <Box sx={{ mt: 1, display: 'flex', flexWrap: 'wrap', gap: 0.75 }}>
                                                {suggestedCategories.map((cat) => (
                                                    <Chip
                                                        key={cat}
                                                        size="small"
                                                        label={cat}
                                                        clickable
                                                        onClick={() => setNewRootCause({ ...newRootCause, category: cat })}
                                                        color={newRootCause.category === cat ? 'primary' : 'default'}
                                                        sx={{ fontSize: '0.72rem', height: 22 }}
                                                    />
                                                ))}
                                            </Box>
                                        </Box>

                                        <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
                                            <Button
                                                size="small"
                                                variant="outlined"
                                                color="inherit"
                                                onClick={() => setShowNewRootCauseForm(false)}
                                                sx={{ textTransform: 'none' }}
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                size="small"
                                                variant="contained"
                                                color="primary"
                                                disabled={newRootCause.creating || !newRootCause.name.trim()}
                                                onClick={handleCreateNewRootCause}
                                                startIcon={<SaveIcon fontSize="small" />}
                                                sx={{ textTransform: 'none', fontWeight: 600 }}
                                            >
                                                {newRootCause.creating ? 'Saving...' : 'Save & Select'}
                                            </Button>
                                        </Box>
                                    </Stack>
                                </Card>
                            </Collapse>

                            {/* Root Cause Remark */}
                            <Box>
                                <Typography variant="body2" fontWeight="700" color="#334155" sx={{ mb: 1 }}>
                                    Root Cause Remark
                                </Typography>
                                <TextField
                                    multiline
                                    rows={3}
                                    fullWidth
                                    size="small"
                                    placeholder="Enter root cause remarks, diagnostic findings, or resolution notes..."
                                    value={editModal.remark}
                                    onChange={(e) => setEditModal({ ...editModal, remark: e.target.value })}
                                />
                            </Box>
                        </Stack>
                    </DialogContent>

                    <DialogActions sx={{ p: 2, bgcolor: '#f8fafc', borderTop: '1px solid #e2e8f0' }}>
                        <Button onClick={closeEditDialog} color="inherit" sx={{ textTransform: 'none' }}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="contained"
                            color="primary"
                            disabled={editModal.saving || !editModal.root_cause_id}
                            startIcon={<SaveIcon />}
                            sx={{ textTransform: 'none', fontWeight: 600, px: 3, borderRadius: 2 }}
                        >
                            {editModal.saving ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
