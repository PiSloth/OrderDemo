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
    FormControl,
    InputLabel,
    Select,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Alert,
    CircularProgress,
    FormControlLabel,
    Checkbox,
    Divider,
    Tabs,
    Tab,
    Tooltip,
    InputAdornment
} from '@mui/material';
import {
    Add as AddIcon,
    CheckCircle as CheckCircleIcon,
    HourglassEmpty as HourglassEmptyIcon,
    AssignmentLate as AssignmentLateIcon,
    Launch as LaunchIcon,
    FilterAlt as FilterAltIcon,
    RestartAlt as RestartAltIcon,
    WarningAmber as WarningAmberIcon,
    AssignmentTurnedIn as AssignmentTurnedInIcon,
    Search as SearchIcon,
    Security as SecurityIcon,
    Person as PersonIcon,
    OpenInNew as OpenInNewIcon
} from '@mui/icons-material';

export default function ReviewsIndex({
    reviews,
    unalignedAccounts = [],
    unalignedCount = 0,
    reviewsCount = 0,
    kpiMetrics = {},
    accounts = [],
    reviewers = [],
    dueTimes = [],
    todoStatuses = [],
    filters = {},
    gapFilters = {},
    initialTab = 0,
}) {
    const { flash = {} } = usePage().props;

    const healthIndex = kpiMetrics.health_index !== undefined ? kpiMetrics.health_index : 100;
    const alignedAccounts = kpiMetrics.aligned_accounts ?? 0;
    const totalAccounts = kpiMetrics.total_accounts ?? 0;
    const atRiskCount = kpiMetrics.at_risk_count ?? 0;
    const orphanCount = kpiMetrics.orphan_count ?? 0;

    // Tabs: 0 = Unaligned Accounts & Compliance Gaps, 1 = Review Sessions & History
    const [currentTab, setCurrentTab] = useState(initialTab);

    // Tab 0: Gap Filters State
    const [gapSearch, setGapSearch] = useState(gapFilters.gap_search || '');
    const [gapType, setGapType] = useState(gapFilters.gap_type || '');
    const [gapTodoStatus, setGapTodoStatus] = useState(gapFilters.gap_todo_status || '');

    // Tab 1: Review Filters State
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [resultFilter, setResultFilter] = useState(filters.result || '');
    const [todoStatusFilter, setTodoStatusFilter] = useState(filters.todo_status || '');

    // Schedule Review Modal State
    const [scheduleModalOpen, setScheduleModalOpen] = useState(false);
    const [scheduleError, setScheduleError] = useState('');
    const [isScheduling, setIsScheduling] = useState(false);
    const [scheduleData, setScheduleData] = useState({
        software_account_id: '',
        review_type: 'scheduled',
        scheduled_at: '',
        assigned_to: '',
        notes: '',
    });

    // Complete Review Modal State
    const [completeModalOpen, setCompleteModalOpen] = useState(false);
    const [completeError, setCompleteError] = useState('');
    const [isFinalizing, setIsFinalizing] = useState(false);
    const [activeReview, setActiveReview] = useState(null);
    const [completeData, setCompleteData] = useState({
        result: 'compliant',
        notes: '',
        create_todo: false,
    });

    // Trace Modal State
    const [traceModalOpen, setTraceModalOpen] = useState(false);
    const [activeTrace, setActiveTrace] = useState(null);

    // ── Tab 0: Gap Auto-Filter Handlers ─────────────────────────────────────
    const triggerGapAutoFilter = (overrides = {}) => {
        const nextSearch = overrides.gap_search !== undefined ? overrides.gap_search : gapSearch;
        const nextType = overrides.gap_type !== undefined ? overrides.gap_type : gapType;
        const nextTodo = overrides.gap_todo_status !== undefined ? overrides.gap_todo_status : gapTodoStatus;

        router.get('/digital-assets/reviews', {
            ...filters,
            tab: 0,
            gap_search: nextSearch?.trim() || undefined,
            gap_type: nextType || undefined,
            gap_todo_status: nextTodo || undefined,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const handleResetGapFilters = () => {
        setGapSearch('');
        setGapType('');
        setGapTodoStatus('');
        router.get('/digital-assets/reviews', {
            ...filters,
            tab: 0,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const hasActiveGapFilters = Boolean(gapSearch || gapType || gapTodoStatus);

    // ── Tab 1: Review Auto-Filter Handlers ──────────────────────────────────
    const triggerAutoFilter = (overrides = {}) => {
        router.get('/digital-assets/reviews', {
            ...gapFilters,
            tab: 1,
            status: overrides.status !== undefined ? overrides.status : statusFilter,
            result: overrides.result !== undefined ? overrides.result : resultFilter,
            todo_status: overrides.todo_status !== undefined ? overrides.todo_status : todoStatusFilter,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const handleFilter = (e) => {
        if (e && e.preventDefault) e.preventDefault();
        triggerAutoFilter();
    };

    const handleResetFilters = () => {
        setStatusFilter('');
        setResultFilter('');
        setTodoStatusFilter('');
        router.get('/digital-assets/reviews', {
            ...gapFilters,
            tab: 1,
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const hasActiveReviewFilters = Boolean(statusFilter || resultFilter || todoStatusFilter);

    // ── Schedule Review Handler ─────────────────────────────────────────────
    const handleSchedule = (e) => {
        e.preventDefault();
        setScheduleError('');
        setIsScheduling(true);
        router.post(`/digital-assets/accounts/${scheduleData.software_account_id}/reviews`, scheduleData, {
            preserveScroll: true,
            onSuccess: () => {
                setScheduleModalOpen(false);
                setScheduleData({ software_account_id: '', review_type: 'scheduled', scheduled_at: '', assigned_to: '', notes: '' });
                setScheduleError('');
                setIsScheduling(false);
            },
            onError: (errs) => {
                setIsScheduling(false);
                setScheduleError(errs.schedule || errs.scheduled_at || Object.values(errs)[0] || 'Failed to schedule review.');
            }
        });
    };

    // ── Global ToDo Trigger: For Unaligned Accounts (Tab 0) ──────────────────
    const handleTriggerAccountTodo = (account) => {
        const defaultDue = new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0];
        const gapsList = (account.compliance_gaps || []).map(g => g.title).join(', ');
        const activePic = account.active_pics?.[0]?.user_id || '';

        window.dispatchEvent(new CustomEvent('open-create-todo-modal', {
            detail: {
                task: `Fix compliance gaps for ${account.account_name} (${account.platform?.name || 'Platform'})`,
                assignedUserId: activePic,
                dueDate: defaultDue,
                reference_type: 'App\\Models\\DigitalAsset\\SoftwareAccount',
                reference_id: account.id,
                module: 'compliance_gap',
                reason: `Account does not align with security criteria: ${gapsList}`,
                action_type: 'remediation',
                suppressVoucher: true,
                onSuccess: () => {
                    router.reload({ only: ['unalignedAccounts', 'unalignedCount'] });
                }
            }
        }));
    };

    // ── Global ToDo Trigger: For Review Follow-ups (Tab 1) ───────────────────
    const handleTriggerGlobalTodo = (rev) => {
        const defaultDue = new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0];
        window.dispatchEvent(new CustomEvent('open-create-todo-modal', {
            detail: {
                task: `Remediate ${rev.account?.account_name || 'Account'} - ${rev.notes || 'Compliance gap'}`,
                assignedUserId: rev.assigned_to || rev.completed_by || '',
                dueDate: defaultDue,
                reference_type: 'App\\Models\\DigitalAsset\\SoftwareAccountReview',
                reference_id: rev.id,
                module: 'compliance_review',
                reason: rev.notes || `Review outcome marked as ${rev.result || 'Needs Action'}`,
                action_type: 'remediation',
                suppressVoucher: true,
                onSuccess: () => {
                    router.reload({ only: ['reviews'] });
                }
            }
        }));
    };

    // ── Finalize Review Handler ─────────────────────────────────────────────
    const handleComplete = (e) => {
        e.preventDefault();
        if (!activeReview) return;
        setCompleteError('');
        setIsFinalizing(true);
        const shouldLaunchGlobalTodo = completeData.create_todo;
        const savedReview = { ...activeReview, result: completeData.result, notes: completeData.notes };

        router.post(`/digital-assets/reviews/${activeReview.id}/complete`, completeData, {
            preserveScroll: true,
            onSuccess: () => {
                setCompleteModalOpen(false);
                setActiveReview(null);
                setCompleteError('');
                setIsFinalizing(false);

                if (shouldLaunchGlobalTodo) {
                    handleTriggerGlobalTodo(savedReview);
                }
            },
            onError: (errs) => {
                setIsFinalizing(false);
                setCompleteError(errs.complete || errs.result || Object.values(errs)[0] || 'Failed to finalize review.');
            }
        });
    };

    const handleOpenCompleteModal = (rev) => {
        const initialResult = rev.result || 'needs_action';
        setActiveReview(rev);
        setCompleteData({
            result: initialResult,
            notes: rev.notes || '',
            create_todo: initialResult === 'needs_action' || initialResult === 'at_risk',
        });
        setCompleteError('');
        setCompleteModalOpen(true);
    };

    return (
        <AsideLayout title="Security Compliance Reviews">
            <Head title="Security Reviews - Digital Asset" />

            {/* Page Header */}
            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Security Compliance Reviews
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Proactive compliance radar, routine security audits, and actionable remediation ToDo task tracking.
                    </Typography>
                </Box>
                <Button
                    variant="contained"
                    startIcon={<AddIcon />}
                    onClick={() => {
                        setScheduleError('');
                        setScheduleData({ software_account_id: '', review_type: 'scheduled', scheduled_at: '', assigned_to: '', notes: '' });
                        setScheduleModalOpen(true);
                    }}
                    sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 600 }}
                >
                    Schedule Review
                </Button>
            </Box>

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
                    <Tab
                        icon={<WarningAmberIcon color={unalignedCount > 0 ? "error" : "action"} />}
                        iconPosition="start"
                        label={
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <span>Unaligned Accounts & Compliance Gaps</span>
                                {unalignedCount > 0 ? (
                                    <Chip
                                        label={unalignedCount}
                                        size="small"
                                        color="error"
                                        sx={{ height: 20, fontSize: '0.75rem', fontWeight: 700 }}
                                    />
                                ) : (
                                    <Chip
                                        label="0 Gaps"
                                        size="small"
                                        color="success"
                                        variant="outlined"
                                        sx={{ height: 20, fontSize: '0.72rem' }}
                                    />
                                )}
                            </Box>
                        }
                    />
                    <Tab
                        icon={<AssignmentTurnedInIcon color="action" />}
                        iconPosition="start"
                        label={
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <span>Review Sessions & History</span>
                                <Chip
                                    label={reviewsCount}
                                    size="small"
                                    color="default"
                                    sx={{ height: 20, fontSize: '0.75rem', fontWeight: 600 }}
                                />
                            </Box>
                        }
                    />
                </Tabs>
            </Card>

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* TAB 0: Unaligned Accounts & Compliance Gaps (Live Radar)            */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            {currentTab === 0 && (
                <>
                    {/* Calculating KPI Cards */}
                    <Grid container spacing={2.5} sx={{ mb: 3 }}>
                        {/* Card 1: Compliance Health Index */}
                        <Grid item xs={12} md={4}>
                            <Card
                                onClick={() => handleResetGapFilters()}
                                sx={{
                                    p: 2.5,
                                    borderRadius: 2.5,
                                    boxShadow: '0 2px 8px 0 rgba(0,0,0,0.06)',
                                    border: '1px solid',
                                    borderColor: healthIndex >= 95 ? 'success.light' : healthIndex >= 70 ? 'warning.light' : 'error.light',
                                    bgcolor: healthIndex >= 95 ? 'rgba(46, 125, 50, 0.04)' : healthIndex >= 70 ? 'rgba(237, 108, 2, 0.04)' : 'rgba(211, 47, 47, 0.04)',
                                    cursor: 'pointer',
                                    transition: 'all 0.2s ease-in-out',
                                    '&:hover': { transform: 'translateY(-2px)', boxShadow: 2 }
                                }}
                            >
                                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 700, color: 'text.secondary' }}>
                                        Compliance Health Index
                                    </Typography>
                                    <Chip
                                        size="small"
                                        label="ပစ်မှတ်: > ၉၅%"
                                        color={healthIndex >= 95 ? "success" : "warning"}
                                        sx={{ fontWeight: 600, fontSize: '0.72rem', height: 22 }}
                                    />
                                </Box>
                                <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1.5, my: 1 }}>
                                    <Typography variant="h4" sx={{ fontWeight: 800, color: healthIndex >= 95 ? 'success.main' : healthIndex >= 70 ? 'warning.main' : 'error.main' }}>
                                        {healthIndex}%
                                    </Typography>
                                    <Typography variant="body2" color="text.secondary" sx={{ fontWeight: 500 }}>
                                        ({alignedAccounts} / {totalAccounts} Accounts)
                                    </Typography>
                                </Box>
                                <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block', mt: 0.5, lineHeight: 1.5 }}>
                                    စုစုပေါင်း အကောင့်များအနက် သတ်မှတ်ထားသော လုံခြုံရေးစည်းမျဉ်းအားလုံး ပြည့်မီသော အကောင့် ရာခိုင်နှုန်း (ပစ်မှတ်: &gt; ၉၅%)။
                                </Typography>
                            </Card>
                        </Grid>

                        {/* Card 2: At-Risk Count */}
                        <Grid item xs={12} md={4}>
                            <Card
                                onClick={() => {
                                    setGapType('missing_property');
                                    triggerGapAutoFilter({ gap_type: 'missing_property' });
                                }}
                                sx={{
                                    p: 2.5,
                                    borderRadius: 2.5,
                                    boxShadow: '0 2px 8px 0 rgba(0,0,0,0.06)',
                                    border: '1px solid',
                                    borderColor: atRiskCount === 0 ? 'success.light' : 'error.light',
                                    bgcolor: atRiskCount === 0 ? 'rgba(46, 125, 50, 0.04)' : 'rgba(211, 47, 47, 0.04)',
                                    cursor: 'pointer',
                                    transition: 'all 0.2s ease-in-out',
                                    '&:hover': { transform: 'translateY(-2px)', boxShadow: 2 }
                                }}
                            >
                                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 700, color: 'text.secondary' }}>
                                        At-Risk Count
                                    </Typography>
                                    <Chip
                                        size="small"
                                        label="ပစ်မှတ်: ၀ ခု"
                                        color={atRiskCount === 0 ? "success" : "error"}
                                        sx={{ fontWeight: 600, fontSize: '0.72rem', height: 22 }}
                                    />
                                </Box>
                                <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1.5, my: 1 }}>
                                    <Typography variant="h4" sx={{ fontWeight: 800, color: atRiskCount === 0 ? 'success.main' : 'error.main' }}>
                                        {atRiskCount}
                                    </Typography>
                                    <Typography variant="body2" color="text.secondary" sx={{ fontWeight: 500 }}>
                                        {atRiskCount === 0 ? 'All Accounts Satisfied' : 'Requires Remediation'}
                                    </Typography>
                                </Box>
                                <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block', mt: 0.5, lineHeight: 1.5 }}>
                                    အရေးကြီး လုံခြုံရေးချို့ယွင်းနေသော အကောင့်အရေအတွက် (ပစ်မှတ်: ၀ ခု ဖြစ်စေရန် ချက်ချင်း Remediation ပြုလုပ်)။
                                </Typography>
                            </Card>
                        </Grid>

                        {/* Card 3: Zero-Orphan Policy */}
                        <Grid item xs={12} md={4}>
                            <Card
                                onClick={() => {
                                    setGapType('missing_pic');
                                    triggerGapAutoFilter({ gap_type: 'missing_pic' });
                                }}
                                sx={{
                                    p: 2.5,
                                    borderRadius: 2.5,
                                    boxShadow: '0 2px 8px 0 rgba(0,0,0,0.06)',
                                    border: '1px solid',
                                    borderColor: orphanCount === 0 ? 'success.light' : 'error.light',
                                    bgcolor: orphanCount === 0 ? 'rgba(46, 125, 50, 0.04)' : 'rgba(211, 47, 47, 0.04)',
                                    cursor: 'pointer',
                                    transition: 'all 0.2s ease-in-out',
                                    '&:hover': { transform: 'translateY(-2px)', boxShadow: 2 }
                                }}
                            >
                                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 700, color: 'text.secondary' }}>
                                        Zero-Orphan Policy
                                    </Typography>
                                    <Chip
                                        size="small"
                                        label="၁၀၀% Assigned ဖြစ်ရမည်"
                                        color={orphanCount === 0 ? "success" : "error"}
                                        sx={{ fontWeight: 600, fontSize: '0.72rem', height: 22 }}
                                    />
                                </Box>
                                <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1.5, my: 1 }}>
                                    <Typography variant="h4" sx={{ fontWeight: 800, color: orphanCount === 0 ? 'success.main' : 'error.main' }}>
                                        {orphanCount}
                                    </Typography>
                                    <Typography variant="body2" color="text.secondary" sx={{ fontWeight: 500 }}>
                                        {orphanCount === 0 ? 'All Accounts Assigned' : 'Unowned Accounts'}
                                    </Typography>
                                </Box>
                                <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block', mt: 0.5, lineHeight: 1.5 }}>
                                    PIC (တာဝန်ခံဝန်ထမ်း) သတ်မှတ်ထားခြင်းမရှိသော အကောင့်အရေအတွက် (၁၀၀% Assigned ဖြစ်ရမည်)။
                                </Typography>
                            </Card>
                        </Grid>
                    </Grid>

                    {/* Gap Filters Bar */}
                    <Card sx={{ mb: 3, borderRadius: 2.5, border: '1px solid', borderColor: 'divider', boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)' }}>
                        <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
                            <Box
                                sx={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    alignItems: 'center',
                                    gap: 2,
                                }}
                            >
                                {/* Search by account name, username, platform */}
                                <TextField
                                    size="small"
                                    placeholder="Search by account name, username, platform..."
                                    value={gapSearch}
                                    onChange={(e) => setGapSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && triggerGapAutoFilter({ gap_search: e.target.value })}
                                    sx={{
                                        flex: { xs: '1 1 100%', md: '1 1 320px' },
                                        minWidth: { xs: '100%', sm: 260 },
                                    }}
                                    InputProps={{
                                        startAdornment: (
                                            <InputAdornment position="start">
                                                <SearchIcon fontSize="small" color="action" />
                                            </InputAdornment>
                                        ),
                                    }}
                                />

                                {/* Gap Category Filter */}
                                <FormControl size="small" sx={{ width: { xs: '100%', sm: 230 }, minWidth: 220, flexShrink: 0 }}>
                                    <InputLabel id="gap-type-label">Gap Category</InputLabel>
                                    <Select
                                        labelId="gap-type-label"
                                        label="Gap Category"
                                        value={gapType}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setGapType(val);
                                            triggerGapAutoFilter({ gap_type: val });
                                        }}
                                    >
                                        <MenuItem value="">All Compliance Gaps</MenuItem>
                                        <MenuItem value="missing_property">🔒 Missing Mandatory Property</MenuItem>
                                        <MenuItem value="missing_evidence">📎 Missing Verified Evidence</MenuItem>
                                        <MenuItem value="missing_pic">👤 No Active PIC Assigned</MenuItem>
                                    </Select>
                                </FormControl>

                                {/* ToDo Task Status Filter */}
                                <FormControl size="small" sx={{ width: { xs: '100%', sm: 250 }, minWidth: 240, flexShrink: 0 }}>
                                    <InputLabel id="gap-todo-status-label">Remediation ToDo</InputLabel>
                                    <Select
                                        labelId="gap-todo-status-label"
                                        label="Remediation ToDo"
                                        value={gapTodoStatus}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setGapTodoStatus(val);
                                            triggerGapAutoFilter({ gap_todo_status: val });
                                        }}
                                    >
                                        <MenuItem value="">All Accounts</MenuItem>
                                        <MenuItem value="no_todo">⚠️ Needs Remediation ToDo (Urgent)</MenuItem>
                                        <MenuItem value="pending">⏳ Has Active ToDo Task</MenuItem>
                                        <MenuItem value="completed">✅ Resolved / Completed ToDo</MenuItem>
                                        <MenuItem value="has_todo">📋 Has Any ToDo Task</MenuItem>
                                    </Select>
                                </FormControl>

                                {/* Reset Filter Button */}
                                {hasActiveGapFilters && (
                                    <Button
                                        type="button"
                                        variant="outlined"
                                        color="inherit"
                                        startIcon={<RestartAltIcon />}
                                        onClick={handleResetGapFilters}
                                        sx={{ height: 40, px: 2, textTransform: 'none', borderRadius: 2, flexShrink: 0 }}
                                    >
                                        Reset
                                    </Button>
                                )}
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Unaligned Accounts Table */}
                    <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                        <Table size="small">
                            <TableHead sx={{ bgcolor: 'action.hover' }}>
                                <TableRow>
                                    <TableCell sx={{ fontWeight: 700 }}>Account & Platform</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Responsible PIC</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Compliance Gaps Detected</TableCell>
                                    <TableCell sx={{ fontWeight: 700 }}>Remediation ToDo</TableCell>
                                    <TableCell align="right" sx={{ fontWeight: 700 }}>Actions</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {unalignedAccounts.length > 0 ? (
                                    unalignedAccounts.map((account) => {
                                        const hasActiveTodo = account.has_pending_todo;
                                        const activeTodoRef = account.active_todo_reference;

                                        return (
                                            <TableRow key={account.id} hover>
                                                {/* Account & Platform */}
                                                <TableCell sx={{ verticalAlign: 'top', py: 1.5 }}>
                                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                                                        <Button
                                                            component="a"
                                                            href={`/digital-assets/accounts/${account.id}`}
                                                            sx={{ textTransform: 'none', p: 0, justifyContent: 'flex-start', fontWeight: 700, fontSize: '0.88rem' }}
                                                        >
                                                            {account.account_name}
                                                        </Button>
                                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
                                                            <Chip
                                                                size="small"
                                                                label={account.platform?.name || 'Platform'}
                                                                variant="outlined"
                                                                sx={{ height: 20, fontSize: '0.72rem' }}
                                                            />
                                                            {account.username && (
                                                                <Typography variant="caption" color="text.secondary">
                                                                    ID: {account.username}
                                                                </Typography>
                                                            )}
                                                        </Box>
                                                    </Box>
                                                </TableCell>

                                                {/* Responsible PIC */}
                                                <TableCell sx={{ verticalAlign: 'top', py: 1.5 }}>
                                                    {account.active_pics && account.active_pics.length > 0 ? (
                                                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                                                            {account.active_pics.map((pic) => (
                                                                <Typography key={pic.id} variant="body2" sx={{ fontWeight: 500 }}>
                                                                    👤 {pic.user?.name || 'Assigned PIC'}
                                                                </Typography>
                                                            ))}
                                                        </Box>
                                                    ) : (
                                                        <Chip
                                                            size="small"
                                                            color="error"
                                                            label="No Active PIC"
                                                            icon={<WarningAmberIcon sx={{ fontSize: '14px !important' }} />}
                                                            sx={{ fontWeight: 600, fontSize: '0.72rem' }}
                                                        />
                                                    )}
                                                </TableCell>

                                                {/* Compliance Gaps Detected */}
                                                <TableCell sx={{ verticalAlign: 'top', py: 1.5 }}>
                                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.75 }}>
                                                        {(account.compliance_gaps || []).map((gap, idx) => (
                                                            <Tooltip key={idx} title={gap.description} arrow>
                                                                <Chip
                                                                    size="small"
                                                                    label={gap.title}
                                                                    color={gap.severity === 'critical' ? 'error' : 'warning'}
                                                                    variant={gap.type === 'missing_pic' ? 'filled' : 'outlined'}
                                                                    sx={{
                                                                        fontWeight: 600,
                                                                        fontSize: '0.72rem',
                                                                        height: 22,
                                                                        width: 'fit-content',
                                                                    }}
                                                                />
                                                            </Tooltip>
                                                        ))}
                                                    </Box>
                                                </TableCell>

                                                {/* Remediation ToDo Task */}
                                                <TableCell sx={{ verticalAlign: 'top', py: 1.5 }}>
                                                    {activeTodoRef ? (
                                                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                                                            <Chip
                                                                size="small"
                                                                icon={<HourglassEmptyIcon sx={{ fontSize: '14px !important' }} />}
                                                                label={`ToDo #${activeTodoRef.todo_list_id}: ${activeTodoRef.todo?.status?.status || 'Pending'}`}
                                                                color="warning"
                                                                onClick={() => {
                                                                    setActiveTrace({ account, reference: activeTodoRef });
                                                                    setTraceModalOpen(true);
                                                                }}
                                                                sx={{ cursor: 'pointer', fontWeight: 600, fontSize: '0.72rem' }}
                                                            />
                                                            {activeTodoRef.todo?.assigned_user && (
                                                                <Typography variant="caption" color="text.secondary">
                                                                    Assignee: {activeTodoRef.todo.assigned_user.name}
                                                                </Typography>
                                                            )}
                                                        </Box>
                                                    ) : account.has_completed_todo ? (
                                                        <Chip
                                                            size="small"
                                                            icon={<CheckCircleIcon sx={{ fontSize: '14px !important' }} />}
                                                            label="Last ToDo Resolved"
                                                            color="success"
                                                            variant="outlined"
                                                            sx={{ fontWeight: 600, fontSize: '0.72rem' }}
                                                        />
                                                    ) : (
                                                        <Button
                                                            size="small"
                                                            variant="contained"
                                                            color="warning"
                                                            startIcon={<AssignmentLateIcon sx={{ fontSize: '14px !important' }} />}
                                                            onClick={() => handleTriggerAccountTodo(account)}
                                                            sx={{ textTransform: 'none', py: 0.4, px: 1.5, fontSize: '0.75rem', fontWeight: 600, whiteSpace: 'nowrap' }}
                                                        >
                                                            + Create ToDo
                                                        </Button>
                                                    )}
                                                </TableCell>

                                                {/* Actions */}
                                                <TableCell align="right" sx={{ verticalAlign: 'top', py: 1.5 }}>
                                                    <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
                                                        <Button
                                                            size="small"
                                                            variant="outlined"
                                                            color="inherit"
                                                            component="a"
                                                            href={`/digital-assets/accounts/${account.id}`}
                                                            endIcon={<OpenInNewIcon sx={{ fontSize: '14px !important' }} />}
                                                            sx={{ textTransform: 'none', fontSize: '0.75rem', whiteSpace: 'nowrap' }}
                                                        >
                                                            Fix Account
                                                        </Button>
                                                        <Button
                                                            size="small"
                                                            variant="outlined"
                                                            color="primary"
                                                            onClick={() => {
                                                                setScheduleData({
                                                                    software_account_id: account.id,
                                                                    review_type: 'ad_hoc',
                                                                    scheduled_at: new Date().toISOString().split('T')[0],
                                                                    assigned_to: '',
                                                                    notes: `Review to verify compliance gaps for ${account.account_name}`,
                                                                });
                                                                setScheduleError('');
                                                                setScheduleModalOpen(true);
                                                            }}
                                                            sx={{ textTransform: 'none', fontSize: '0.75rem', whiteSpace: 'nowrap' }}
                                                        >
                                                            Schedule Review
                                                        </Button>
                                                    </Box>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={5} align="center" sx={{ py: 4 }}>
                                            <Typography variant="body1" color="text.secondary">
                                                No unaligned accounts found matching the current filters.
                                            </Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </>
            )}

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* TAB 1: Review Sessions & History                                    */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            {currentTab === 1 && (
                <>
                    {/* Review Filters Bar */}
                    <Card sx={{ mb: 3, borderRadius: 2.5, border: '1px solid', borderColor: 'divider', boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)' }}>
                        <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
                            <Box
                                component="form"
                                onSubmit={handleFilter}
                                sx={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    alignItems: 'center',
                                    gap: 2,
                                }}
                            >
                                {/* Review Status (Fixed Width: 200px) */}
                                <FormControl size="small" sx={{ width: { xs: '100%', sm: 200 }, minWidth: 200, flexShrink: 0 }}>
                                    <InputLabel id="review-status-label">Review Status</InputLabel>
                                    <Select
                                        labelId="review-status-label"
                                        label="Review Status"
                                        value={statusFilter}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setStatusFilter(val);
                                            triggerAutoFilter({ status: val });
                                        }}
                                    >
                                        <MenuItem value="">All Review Statuses</MenuItem>
                                        <MenuItem value="scheduled">Scheduled</MenuItem>
                                        <MenuItem value="in_progress">In Progress</MenuItem>
                                        <MenuItem value="completed">Completed</MenuItem>
                                    </Select>
                                </FormControl>

                                {/* Compliance Result (Fixed Width: 200px) */}
                                <FormControl size="small" sx={{ width: { xs: '100%', sm: 200 }, minWidth: 200, flexShrink: 0 }}>
                                    <InputLabel id="compliance-result-label">Compliance Result</InputLabel>
                                    <Select
                                        labelId="compliance-result-label"
                                        label="Compliance Result"
                                        value={resultFilter}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setResultFilter(val);
                                            triggerAutoFilter({ result: val });
                                        }}
                                    >
                                        <MenuItem value="">All Results</MenuItem>
                                        <MenuItem value="compliant">Compliant</MenuItem>
                                        <MenuItem value="needs_action">Needs Action</MenuItem>
                                        <MenuItem value="at_risk">At Risk</MenuItem>
                                    </Select>
                                </FormControl>

                                {/* Action ToDo Status Filter (Fixed Width: 270px) */}
                                <FormControl size="small" sx={{ width: { xs: '100%', sm: 270 }, minWidth: 270, flexShrink: 0 }}>
                                    <InputLabel id="todo-status-label">Action ToDo Status</InputLabel>
                                    <Select
                                        labelId="todo-status-label"
                                        label="Action ToDo Status"
                                        value={todoStatusFilter}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            setTodoStatusFilter(val);
                                            triggerAutoFilter({ todo_status: val });
                                        }}
                                    >
                                        <MenuItem value="">All ToDo Statuses</MenuItem>
                                        <MenuItem value="pending">⏳ Pending / In Progress ToDo</MenuItem>
                                        <MenuItem value="completed">✅ Resolved / Completed ToDo</MenuItem>
                                        <MenuItem value="needs_todo">⚠️ Needs Action (No ToDo Created)</MenuItem>
                                        <MenuItem value="has_todo">📋 Has Any ToDo Task</MenuItem>
                                        <MenuItem value="no_todo">➖ No ToDo Required</MenuItem>
                                    </Select>
                                </FormControl>

                                {/* Reset Filter Button (Only when active) */}
                                {hasActiveReviewFilters && (
                                    <Button
                                        type="button"
                                        variant="outlined"
                                        color="inherit"
                                        startIcon={<RestartAltIcon />}
                                        onClick={handleResetFilters}
                                        sx={{ height: 40, px: 2, textTransform: 'none', borderRadius: 2, flexShrink: 0 }}
                                    >
                                        Reset
                                    </Button>
                                )}
                            </Box>
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
                                    <TableCell sx={{ fontWeight: 700 }}>Action ToDo / Trace</TableCell>
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
                                            
                                            {/* Action ToDo Trace Column */}
                                            <TableCell>
                                                {rev.todo_references && rev.todo_references.length > 0 ? (
                                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                                                        {rev.todo_references.map((ref) => {
                                                            const isResolved = Boolean(ref.todo?.closed_at || (ref.todo?.status && /complete|done|close|finish|success/i.test(ref.todo.status.status)));
                                                            const statusLabel = ref.todo?.status?.status || (isResolved ? 'Finished' : 'Pending');
                                                            return (
                                                                <Chip
                                                                    key={ref.id}
                                                                    size="small"
                                                                    icon={isResolved ? <CheckCircleIcon sx={{ fontSize: '14px !important' }} /> : <HourglassEmptyIcon sx={{ fontSize: '14px !important' }} />}
                                                                    label={`ToDo #${ref.todo_list_id}: ${statusLabel}`}
                                                                    color={isResolved ? 'success' : 'warning'}
                                                                    variant={isResolved ? 'outlined' : 'filled'}
                                                                    onClick={() => {
                                                                        setActiveTrace({ review: rev, reference: ref });
                                                                        setTraceModalOpen(true);
                                                                    }}
                                                                    sx={{ cursor: 'pointer', fontWeight: 600, fontSize: '0.72rem' }}
                                                                />
                                                            );
                                                        })}
                                                    </Box>
                                                ) : (rev.result === 'needs_action' || rev.result === 'at_risk') ? (
                                                    <Button
                                                        size="small"
                                                        variant="outlined"
                                                        color="warning"
                                                        startIcon={<AssignmentLateIcon sx={{ fontSize: '14px !important' }} />}
                                                        onClick={() => handleTriggerGlobalTodo(rev)}
                                                        sx={{ textTransform: 'none', py: 0.2, px: 1, fontSize: '0.72rem', whiteSpace: 'nowrap' }}
                                                    >
                                                        + Create ToDo
                                                    </Button>
                                                ) : (
                                                    <Typography variant="caption" color="text.secondary">-</Typography>
                                                )}
                                            </TableCell>

                                            <TableCell>{rev.assigned_to_user?.name || rev.completed_by_user?.name || '-'}</TableCell>
                                            <TableCell align="right">
                                                {rev.status !== 'completed' && (
                                                    <Button
                                                        size="small"
                                                        variant="outlined"
                                                        onClick={() => handleOpenCompleteModal(rev)}
                                                        sx={{ textTransform: 'none', fontWeight: 600 }}
                                                    >
                                                        Finalize
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} align="center" sx={{ py: 3 }}>
                                            No reviews found matching the selected criteria.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>

                    {/* Pagination */}
                    {reviews.links && reviews.links.length > 3 && (
                        <Box sx={{ mt: 2, display: 'flex', justifyContent: 'center', gap: 1 }}>
                            {reviews.links.map((link, idx) => (
                                <Button
                                    key={idx}
                                    size="small"
                                    variant={link.active ? 'contained' : 'outlined'}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true, replace: true, preserveScroll: true })}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    sx={{ minWidth: 36, textTransform: 'none' }}
                                />
                            ))}
                        </Box>
                    )}
                </>
            )}

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* Modal: Schedule Review                                              */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            <Dialog open={scheduleModalOpen} onClose={() => setScheduleModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleSchedule}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Schedule Security Review</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            {scheduleError && (
                                <Alert severity="error" onClose={() => setScheduleError('')}>
                                    {scheduleError}
                                </Alert>
                            )}

                            <TextField
                                select
                                fullWidth
                                label="Software Account"
                                value={scheduleData.software_account_id}
                                onChange={(e) => setScheduleData({ ...scheduleData, software_account_id: e.target.value })}
                                required
                                disabled={isScheduling}
                            >
                                <MenuItem value="">Select Account</MenuItem>
                                {accounts.map((acc) => (
                                    <MenuItem key={acc.id} value={acc.id}>
                                        {acc.account_name} ({acc.username || 'No ID'})
                                    </MenuItem>
                                ))}
                            </TextField>

                            <TextField
                                select
                                fullWidth
                                label="Review Type"
                                value={scheduleData.review_type}
                                onChange={(e) => setScheduleData({ ...scheduleData, review_type: e.target.value })}
                                required
                                disabled={isScheduling}
                            >
                                <MenuItem value="scheduled">Scheduled Routine Review</MenuItem>
                                <MenuItem value="ad_hoc">Ad-hoc Security Check</MenuItem>
                                <MenuItem value="incident">Incident-driven Verification</MenuItem>
                            </TextField>

                            <TextField
                                fullWidth
                                type="date"
                                label="Scheduled Date"
                                InputLabelProps={{ shrink: true }}
                                value={scheduleData.scheduled_at}
                                onChange={(e) => setScheduleData({ ...scheduleData, scheduled_at: e.target.value })}
                                required
                                disabled={isScheduling}
                            />

                            <TextField
                                select
                                fullWidth
                                label="Assign Auditor (Reviewer)"
                                value={scheduleData.assigned_to}
                                onChange={(e) => setScheduleData({ ...scheduleData, assigned_to: e.target.value })}
                                disabled={isScheduling}
                            >
                                <MenuItem value="">Unassigned</MenuItem>
                                {reviewers.map((rev) => (
                                    <MenuItem key={rev.id} value={rev.id}>
                                        {rev.name} ({rev.email})
                                    </MenuItem>
                                ))}
                            </TextField>

                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                label="Review Notes / Objective"
                                value={scheduleData.notes}
                                onChange={(e) => setScheduleData({ ...scheduleData, notes: e.target.value })}
                                disabled={isScheduling}
                                placeholder="State review scope, e.g. 2FA verification, password rotation check..."
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setScheduleModalOpen(false)} disabled={isScheduling}>Cancel</Button>
                        <Button type="submit" variant="contained" disabled={isScheduling} startIcon={isScheduling ? <CircularProgress size={18} color="inherit" /> : null}>
                            {isScheduling ? 'Scheduling...' : 'Confirm Schedule'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* Modal: Finalize Review                                              */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            <Dialog open={completeModalOpen} onClose={() => setCompleteModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleComplete}>
                    <DialogTitle sx={{ fontWeight: 700 }}>
                        Finalize Security Review
                        {activeReview && (
                            <Typography variant="body2" color="text.secondary">
                                Account: {activeReview.account?.account_name}
                            </Typography>
                        )}
                    </DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            {completeError && (
                                <Alert severity="error" onClose={() => setCompleteError('')}>
                                    {completeError}
                                </Alert>
                            )}

                            <TextField
                                select
                                fullWidth
                                label="Result"
                                value={completeData.result}
                                onChange={(e) => {
                                    const nextResult = e.target.value;
                                    setCompleteData({
                                        ...completeData,
                                        result: nextResult,
                                        create_todo: nextResult === 'needs_action' || nextResult === 'at_risk',
                                    });
                                }}
                                disabled={isFinalizing}
                            >
                                <MenuItem value="compliant">Compliant (All policies satisfied)</MenuItem>
                                <MenuItem value="needs_action">Needs Action (Remediation required)</MenuItem>
                                <MenuItem value="at_risk">At Risk (Critical vulnerability or missing 2FA)</MenuItem>
                            </TextField>
                            
                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                label="Findings & Notes"
                                value={completeData.notes}
                                onChange={(e) => setCompleteData({ ...completeData, notes: e.target.value })}
                                disabled={isFinalizing}
                                placeholder="Enter audit findings, observations, or remediation requirements..."
                            />

                            {/* Collapsible Follow-Up ToDo Section using Global ToDo Form */}
                            {(completeData.result === 'needs_action' || completeData.result === 'at_risk') && (
                                <Box sx={{ border: '1px solid', borderColor: 'warning.light', borderRadius: 2, p: 2, bgcolor: 'action.hover' }}>
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                checked={completeData.create_todo}
                                                onChange={(e) => setCompleteData({ ...completeData, create_todo: e.target.checked })}
                                                disabled={isFinalizing}
                                                color="warning"
                                            />
                                        }
                                        label={
                                            <Box>
                                                <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                                                    Launch Global ToDo Task Creation Form
                                                </Typography>
                                                <Typography variant="caption" color="text.secondary">
                                                    Automatically opens the official Global ToDo Form on finalization to schedule remediation and trace status.
                                                </Typography>
                                            </Box>
                                        }
                                    />

                                    <Box sx={{ mt: 1.5, display: 'flex', gap: 1 }}>
                                        <Button
                                            size="small"
                                            variant="outlined"
                                            color="warning"
                                            startIcon={<AssignmentLateIcon sx={{ fontSize: '15px !important' }} />}
                                            onClick={() => {
                                                handleTriggerGlobalTodo({
                                                    ...activeReview,
                                                    result: completeData.result,
                                                    notes: completeData.notes,
                                                });
                                            }}
                                            sx={{ textTransform: 'none', borderRadius: 1.5, fontSize: '0.75rem' }}
                                        >
                                            Open Global ToDo Form Now
                                        </Button>
                                    </Box>
                                </Box>
                            )}
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCompleteModalOpen(false)} disabled={isFinalizing}>Cancel</Button>
                        <Button type="submit" variant="contained" disabled={isFinalizing} startIcon={isFinalizing ? <CircularProgress size={18} color="inherit" /> : null}>
                            {isFinalizing ? 'Finalizing...' : 'Finalize Review'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* ═══════════════════════════════════════════════════════════════════ */}
            {/* Modal: Action ToDo Trace Detail                                     */}
            {/* ═══════════════════════════════════════════════════════════════════ */}
            <Dialog open={traceModalOpen} onClose={() => setTraceModalOpen(false)} maxWidth="sm" fullWidth>
                <DialogTitle sx={{ fontWeight: 700 }}>
                    Action ToDo Trace Record
                </DialogTitle>
                <DialogContent dividers>
                    {activeTrace && (
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            {/* Card: Trigger Reason */}
                            <Card variant="outlined" sx={{ bgcolor: 'action.hover' }}>
                                <CardContent sx={{ pb: '16px !important' }}>
                                    <Typography variant="overline" color="text.secondary" sx={{ fontWeight: 700 }}>
                                        Why This ToDo Happened (Audit Finding)
                                    </Typography>
                                    <Typography variant="body1" sx={{ mt: 0.5, fontWeight: 500 }}>
                                        {activeTrace.reference.reason || 'No specific finding context provided.'}
                                    </Typography>
                                    <Box sx={{ mt: 1.5, display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                                        <Chip
                                            size="small"
                                            label={`Account: ${activeTrace.review?.account?.account_name || activeTrace.account?.account_name || 'Account'}`}
                                        />
                                        {activeTrace.review?.result && (
                                            <Chip
                                                size="small"
                                                label={`Result: ${activeTrace.review.result}`}
                                                color={activeTrace.review.result === 'at_risk' ? 'error' : 'warning'}
                                            />
                                        )}
                                        <Chip size="small" label={`Module: ${activeTrace.reference.module || 'compliance'}`} variant="outlined" />
                                    </Box>
                                </CardContent>
                            </Card>

                            {/* Card: Live Task Status */}
                            <Card variant="outlined">
                                <CardContent sx={{ pb: '16px !important' }}>
                                    {(() => {
                                        const isResolved = Boolean(activeTrace.reference.todo?.closed_at || (activeTrace.reference.todo?.status && /complete|done|close|finish|success/i.test(activeTrace.reference.todo.status.status)));
                                        const statusText = activeTrace.reference.todo?.status?.status || (isResolved ? 'Finished / Resolved' : 'Pending Action');
                                        return (
                                            <>
                                                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                                                    <Typography variant="overline" color="text.secondary" sx={{ fontWeight: 700 }}>
                                                        Live ToDo Task Status
                                                    </Typography>
                                                    <Chip
                                                        size="small"
                                                        icon={isResolved ? <CheckCircleIcon /> : <HourglassEmptyIcon />}
                                                        label={statusText}
                                                        color={isResolved ? 'success' : 'warning'}
                                                    />
                                                </Box>

                                                <Typography variant="h6" sx={{ fontSize: '1rem', fontWeight: 600 }}>
                                                    {activeTrace.reference.todo?.task || `Task #${activeTrace.reference.todo_list_id}`}
                                                </Typography>

                                                <Grid container spacing={1} sx={{ mt: 1 }}>
                                                    <Grid item xs={6}>
                                                        <Typography variant="caption" color="text.secondary">Assigned To:</Typography>
                                                        <Typography variant="body2" sx={{ fontWeight: 500 }}>
                                                            {activeTrace.reference.todo?.assigned_user?.name || 'Unassigned'}
                                                        </Typography>
                                                    </Grid>
                                                    <Grid item xs={6}>
                                                        <Typography variant="caption" color="text.secondary">Due Date:</Typography>
                                                        <Typography variant="body2" sx={{ fontWeight: 500 }}>
                                                            {activeTrace.reference.todo?.due_date ? new Date(activeTrace.reference.todo.due_date).toLocaleDateString() : 'None'}
                                                        </Typography>
                                                    </Grid>
                                                    {activeTrace.reference.todo?.created_by_user && (
                                                        <Grid item xs={6}>
                                                            <Typography variant="caption" color="text.secondary">Created By:</Typography>
                                                            <Typography variant="body2" sx={{ fontWeight: 500 }}>
                                                                {activeTrace.reference.todo.created_by_user.name}
                                                            </Typography>
                                                        </Grid>
                                                    )}
                                                    {activeTrace.reference.todo?.closed_at && (
                                                        <Grid item xs={6}>
                                                            <Typography variant="caption" color="text.secondary">Resolved (Closed) At:</Typography>
                                                            <Typography variant="body2" sx={{ fontWeight: 500, color: 'success.main' }}>
                                                                {new Date(activeTrace.reference.todo.closed_at).toLocaleString()} by {activeTrace.reference.todo.closed_by_user?.name || 'Authorized User'}
                                                            </Typography>
                                                        </Grid>
                                                    )}
                                                </Grid>
                                            </>
                                        );
                                    })()}
                                </CardContent>
                            </Card>
                        </Box>
                    )}
                </DialogContent>
                <DialogActions sx={{ p: 2, justifyContent: 'space-between' }}>
                    <Button
                        component="a"
                        href="/todo"
                        target="_blank"
                        endIcon={<LaunchIcon />}
                        sx={{ textTransform: 'none' }}
                    >
                        Open ToDo System
                    </Button>
                    <Button onClick={() => setTraceModalOpen(false)}>Close</Button>
                </DialogActions>
            </Dialog>
        </AsideLayout>
    );
}
