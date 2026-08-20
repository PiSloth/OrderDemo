import React, { useState, useEffect } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AsideLayout from '@/Layouts/AsideLayout';
import CreateIssueModal from '@/Components/IT/CreateIssueModal';
import UserSelectModal from '@/Components/IT/UserSelectModal';
import {
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    IconButton,
    MenuItem,
    Paper,
    Select,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    TextField,
    Typography,
    Tabs,
    Tab,
    FormControl,
    InputLabel,
    Divider,
    List,
    ListItem,
    ListItemText,
    Switch,
    FormControlLabel,
    RadioGroup,
    Radio,
    Checkbox,
    Tooltip,
    Stack,
} from '@mui/material';

import {
    Add as AddIcon,
    FilterList as FilterIcon,
    Edit as EditIcon,
    Delete as DeleteIcon,
    Visibility as ViewIcon,
    CheckCircle as CheckCircleIcon,
    Cancel as CancelIcon,
    AccessTime as AccessTimeIcon,
    BarChart as BarChartIcon,
    Assessment as AssessmentIcon,
    PriorityHigh as PriorityHighIcon,
    Send as SendIcon,
    Flag as FlagIcon,
    AutoAwesome as AutoAwesomeIcon,
    DragHandle as DragHandleIcon,
    Search as SearchIcon,
    RestoreFromTrash as RestoreFromTrashIcon,
    DeleteForever as DeleteForeverIcon,
} from '@mui/icons-material';

const STATUS_STEPS = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'PENDING', 'DONE', 'CLOSED'];

export default function Index({
    auth,
    issues,
    filters,
    categories,
    priorities,
    importanceLevels,
    statuses,
    departments,
    users,
    branches,
    rootCauses = [],
    deletedCount = 0,
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [branchFilter, setBranchFilter] = useState(filters.branch_id || '');
    const [activeTab, setActiveTab] = useState(filters.tab || 'all');

    // Drag-and-drop state for sequence reordering
    const [itemList, setItemList] = useState(issues.data || []);
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);

    useEffect(() => {
        setItemList(issues.data || []);
    }, [issues.data]);

    // Create Issue Modal State (Empty Priority by Default!)
    const [openCreateModal, setOpenCreateModal] = useState(false);

    // Root Cause Modal for Close / Done
    const [rootCauseModal, setRootCauseModal] = useState({ open: false, issue: null, targetStatus: null });
    const [rootCauseForm, setRootCauseForm] = useState({ root_cause_id: '', remark: '' });

    // Reopen / Change Back Status Modal State (shown when changing from CLOSED or DONE)
    const [reopenModal, setReopenModal] = useState({ open: false, issue: null, targetStatus: null, assignedUserId: null });
    const [reopenRemark, setReopenRemark] = useState('');

    // Assign User Modal State (for Edit Form or Status Transition)
    const [assignUserModal, setAssignUserModal] = useState({ open: false, issue: null, targetStatus: null, mode: 'transition' });
    const [createForm, setCreateForm] = useState({
        title: '',
        description: '',
        issue_category_id: categories.length > 0 ? categories[0].id : '',
        issue_priority_id: '', // EMPTY DEFAULT AS REQUESTED!
        issue_importance_id: importanceLevels.length > 0 ? importanceLevels[0].id : '',
        resolution_department_id: departments.length > 0 ? departments[0].id : '',
        assigned_user_id: '',
        issue_by: '',
        is_third_party_resolver: false,
    });

    const handleOpenCreate = () => {
        setCreateForm({
            title: '',
            description: '',
            issue_category_id: categories.length > 0 ? categories[0].id : '',
            issue_priority_id: '', // EMPTY DEFAULT AS REQUESTED!
            issue_importance_id: importanceLevels.length > 0 ? importanceLevels[0].id : '',
            resolution_department_id: departments.length > 0 ? departments[0].id : '',
            assigned_user_id: '',
            issue_by: '',
            is_third_party_resolver: false,
        });
        setOpenCreateModal(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        router.post('/operations/it/issues', createForm, {
            onSuccess: () => setOpenCreateModal(false),
        });
    };

    // Native HTML5 Drag and Drop handlers
    const handleDragStart = (e, index) => {
        setDraggedIndex(index);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', index);
    };

    const handleDragOver = (e, index) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (dragOverIndex !== index) {
            setDragOverIndex(index);
        }
    };

    const handleDrop = (e, targetIndex) => {
        e.preventDefault();
        if (draggedIndex === null || draggedIndex === targetIndex) {
            setDraggedIndex(null);
            setDragOverIndex(null);
            return;
        }

        const items = Array.from(itemList);
        const [movedItem] = items.splice(draggedIndex, 1);
        items.splice(targetIndex, 0, movedItem);

        const updatedItems = items.map((item, idx) => ({
            ...item,
            resolution_sequence: idx + 1,
        }));

        setItemList(updatedItems);
        setDraggedIndex(null);
        setDragOverIndex(null);

        const orderedIds = updatedItems.map((item) => item.id);
        router.post('/operations/it/issues/reorder-sequence', { ordered_ids: orderedIds }, { preserveState: true });
    };

    // Manage Modal state
    const [manageIssue, setManageIssue] = useState(null);
    const [manageModalTab, setManageModalTab] = useState(0);
    const [manageForm, setManageForm] = useState({
        title: '',
        description: '',
        issue_category_id: '',
        issue_priority_id: '',
        issue_importance_id: '',
        assigned_user_id: '',
        resolution_department_id: '',
        proposed_solution: '',
        issue_by: '',
        is_third_party_resolver: false,
    });
    const [newMessage, setNewMessage] = useState('');
    const [isLogNote, setIsLogNote] = useState(false);

    // Severity / Priority Quick Modal
    const [severityIssue, setSeverityIssue] = useState(null);

    // Delete Modal & Override
    const [deletingIssue, setDeletingIssue] = useState(null);
    const [selectedIssueForOverride, setSelectedIssueForOverride] = useState(null);
    const [adminRemark, setAdminRemark] = useState('');

    const handleFilter = () => {
        router.get(
            '/operations/it/issues',
            { search, status: statusFilter, branch_id: branchFilter, tab: activeTab },
            { preserveState: true }
        );
    };

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
        router.get(
            '/operations/it/issues',
            { search, status: statusFilter, branch_id: branchFilter, tab: newValue },
            { preserveState: true }
        );
    };

    // Open Blade-inspired Manage Modal
    const handleOpenManageModal = (issue) => {
        setManageIssue(issue);
        setManageModalTab(0);
        setManageForm({
            title: issue.title || '',
            description: issue.description || '',
            issue_category_id: issue.issue_category_id || '',
            issue_priority_id: issue.issue_priority_id || '',
            issue_importance_id: issue.issue_importance_id || '',
            assigned_user_id: issue.assigned_user_id || '',
            resolution_department_id: issue.resolution_department_id || '',
            proposed_solution: issue.proposed_solution || '',
            issue_by: issue.issue_by || '',
            is_third_party_resolver: Boolean(issue.is_third_party_resolver),
        });
    };

    // Save Manage Modal Changes (CRUD Update)
    const handleSaveManageModal = () => {
        if (!manageIssue) return;
        router.put(`/operations/it/issues/${manageIssue.id}`, manageForm, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setManageIssue(null),
        });
    };

    // Transition Status Step
    const handleTransitionStatus = (targetStatusCode) => {
        if (!manageIssue) return;
        const targetStatus = statuses.find((s) => s.code === targetStatusCode);
        if (!targetStatus) return;

        const currentAssignedId = manageForm.assigned_user_id || manageIssue.assigned_user_id;

        // If transitioning past OPEN (e.g. ASSIGNED, IN_PROGRESS, PENDING, DONE, CLOSED) and no assigned user exists, prompt to assign user
        if (targetStatus.code !== 'OPEN' && !currentAssignedId) {
            setAssignUserModal({
                open: true,
                issue: manageIssue,
                targetStatus,
                mode: 'transition',
            });
            return;
        }

        executeStatusTransition(manageIssue, targetStatus, currentAssignedId);
    };

    const executeStatusTransition = (issue, targetStatus, assignedUserId = null) => {
        const currentCode = issue.status?.code || issue.status_code;
        const isCurrentlyClosedOrDone = currentCode === 'CLOSED' || currentCode === 'DONE';
        const isTargetClosedOrDone = targetStatus.code === 'CLOSED' || targetStatus.code === 'DONE';

        if (isTargetClosedOrDone) {
            setRootCauseModal({ open: true, issue, targetStatus, assignedUserId });
            setRootCauseForm({ root_cause_id: '', remark: '' });
            return;
        }

        if (isCurrentlyClosedOrDone && !isTargetClosedOrDone) {
            setReopenModal({ open: true, issue, targetStatus, assignedUserId });
            setReopenRemark('');
            return;
        }

        router.patch(
            `/operations/it/issues/${issue.id}/status`,
            {
                issue_status_id: targetStatus.id,
                assigned_user_id: assignedUserId || manageForm?.assigned_user_id || issue.assigned_user_id,
                proposed_solution: manageForm.proposed_solution,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setManageIssue(null),
            }
        );
    };

    const handleUserSelectedForAssign = (user) => {
        if (assignUserModal.mode === 'edit_form') {
            setManageForm((prev) => ({ ...prev, assigned_user_id: user.id }));
            setAssignUserModal({ open: false, issue: null, targetStatus: null, mode: 'edit_form' });
            return;
        }

        // Mode is 'transition'
        const { issue, targetStatus } = assignUserModal;
        setManageForm((prev) => ({ ...prev, assigned_user_id: user.id }));
        setAssignUserModal({ open: false, issue: null, targetStatus: null, mode: 'transition' });

        if (issue && targetStatus) {
            executeStatusTransition(issue, targetStatus, user.id);
        }
    };

    const handleConfirmCloseWithRootCause = () => {
        const { issue, targetStatus, assignedUserId } = rootCauseModal;
        if (!issue || !targetStatus) return;

        if (!rootCauseForm.root_cause_id) {
            alert('Please select a Root Cause before changing status to ' + (targetStatus.name || targetStatus.code));
            return;
        }

        router.patch(
            `/operations/it/issues/${issue.id}/status`,
            {
                issue_status_id: targetStatus.id,
                assigned_user_id: assignedUserId || manageForm?.assigned_user_id || issue.assigned_user_id,
                root_cause_id: rootCauseForm.root_cause_id,
                remark: rootCauseForm.remark,
                proposed_solution: manageForm?.proposed_solution || issue.proposed_solution,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setRootCauseModal({ open: false, issue: null, targetStatus: null, assignedUserId: null });
                    setManageIssue(null);
                },
            }
        );
    };

    const handleConfirmReopen = () => {
        const { issue, targetStatus, assignedUserId } = reopenModal;
        if (!issue || !targetStatus) return;

        if (!reopenRemark.trim()) {
            alert('Please provide a remark explaining the status change.');
            return;
        }

        router.patch(
            `/operations/it/issues/${issue.id}/status`,
            {
                issue_status_id: targetStatus.id,
                assigned_user_id: assignedUserId || manageForm?.assigned_user_id || issue.assigned_user_id,
                remark: reopenRemark.trim(),
                proposed_solution: manageForm?.proposed_solution || issue.proposed_solution,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setReopenModal({ open: false, issue: null, targetStatus: null, assignedUserId: null });
                    setManageIssue(null);
                },
            }
        );
    };

    // Submit Discussion Message / Log Note
    const handleAddMessage = () => {
        if (!manageIssue || !newMessage.trim()) return;
        router.post(
            `/operations/it/issues/${manageIssue.id}/messages`,
            { message: newMessage, is_log_note: isLogNote },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setNewMessage('');
                    setIsLogNote(false);
                },
            }
        );
    };

    // Quick Resolver Type Toggle
    const handleToggleResolver = (issue) => {
        router.put(
            `/operations/it/issues/${issue.id}`,
            {
                ...issue,
                is_third_party_resolver: !issue.is_third_party_resolver,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Quick Severity / Priority Update
    const handleSaveSeverity = (newPriorityId, newImportanceId) => {
        if (!severityIssue) return;
        router.put(
            `/operations/it/issues/${severityIssue.id}`,
            {
                ...severityIssue,
                issue_priority_id: newPriorityId || severityIssue.issue_priority_id,
                issue_importance_id: newImportanceId || severityIssue.issue_importance_id,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setSeverityIssue(null),
            }
        );
    };

    // Soft Delete and Restore States
    const [deleteReason, setDeleteReason] = useState('');
    const [restoreIssue, setRestoreIssue] = useState(null);

    // Submit Soft Delete
    const handleDeleteSubmit = () => {
        if (!deletingIssue || !deleteReason.trim()) return;
        router.delete(`/operations/it/issues/${deletingIssue.id}`, {
            data: { reason: deleteReason },
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setDeletingIssue(null);
                setDeleteReason('');
            },
        });
    };

    // Submit Restore
    const handleRestoreSubmit = (issue) => {
        const target = issue || restoreIssue;
        if (!target) return;
        router.post(`/operations/it/issues/${target.id}/restore`, {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setRestoreIssue(null),
        });
    };

    // Admin SLA Override
    const handleSlaOverride = () => {
        if (!selectedIssueForOverride || !adminRemark.trim()) return;
        router.post(
            `/operations/it/issues/${selectedIssueForOverride.id}/override-sla`,
            { admin_remark: adminRemark },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedIssueForOverride(null);
                    setAdminRemark('');
                },
            }
        );
    };

    const getPriorityChip = (priority) => {
        if (!priority) return <Chip label="Unassigned" size="small" color="default" />;
        const code = priority.code?.toUpperCase();
        if (code === 'P1') {
            return <Chip label="P1 - Critical (24h Clock)" size="small" color="error" icon={<PriorityHighIcon />} />;
        }
        if (code === 'P2') {
            return <Chip label="P2 - High (1 Business Day)" size="small" color="warning" />;
        }
        if (code === 'P3') {
            return <Chip label="P3 - Normal (2 Business Days)" size="small" color="info" />;
        }
        return <Chip label={`${priority.code} - ${priority.name}`} size="small" color="default" />;
    };

    const getSlaBadge = (issue) => {
        if (issue.is_sla_failed) {
            return (
                <Chip
                    label={`SLA Failed (${issue.fail_points || 1} pts)`}
                    color="error"
                    size="small"
                    variant="filled"
                    icon={<CancelIcon />}
                />
            );
        }
        if (issue.closed_date) {
            return <Chip label="SLA Met (Success)" color="success" size="small" icon={<CheckCircleIcon />} />;
        }
        if (issue.is_overdue) {
            return <Chip label="Overdue" color="warning" size="small" icon={<AccessTimeIcon />} />;
        }
        return <Chip label="On Track" color="info" size="small" variant="outlined" />;
    };

    return (
        <AsideLayout title="IT Issue Management (Drag Sequence & SLA)">
            <Head title="IT Issue Management" />

            <Box sx={{ p: 3 }}>
                {/* Header Action Navigation Bar */}
                <Card sx={{ mb: 3, background: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)', color: '#fff' }}>
                    <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                        <Box>
                            <Typography variant="h5" fontWeight="bold" sx={{ color: '#38bdf8' }}>
                                IT Issues Management & SLA Tracking
                            </Typography>
                            <Typography variant="body2" sx={{ opacity: 0.8 }}>
                                Drag sequence handles to reorder tasks. Click issue title to open manage modal.
                            </Typography>
                        </Box>

                        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                            <Button component={Link} href="/operations/it/issues/dashboard" variant="outlined" color="info" startIcon={<BarChartIcon />}>
                                Dashboard
                            </Button>
                            <Button component={Link} href="/operations/it/issues/reports" variant="outlined" color="secondary" startIcon={<AssessmentIcon />}>
                                SLA Reports
                            </Button>
                            <Button variant="contained" color="primary" startIcon={<AddIcon />} onClick={handleOpenCreate}>
                                Log New Issue Modal
                            </Button>
                        </Box>
                    </CardContent>
                </Card>

                {/* Filter Controls & Tabs */}
                <Paper sx={{ mb: 3, p: 2 }}>
                    <Tabs value={activeTab} onChange={handleTabChange} sx={{ mb: 2 }}>
                        <Tab label="All Issues" value="all" />
                        <Tab label="ERP Issues" value="erp" />
                        <Tab label="Third-Party Resolver" value="third" />
                        <Tab label={`Trash (${deletedCount})`} value="trash" />
                    </Tabs>

                    <Grid container spacing={2} alignItems="center">
                        <Grid item xs={12} sm={4} md={3}>
                            <TextField
                                label="Search Title / Description"
                                variant="outlined"
                                size="small"
                                fullWidth
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            />
                        </Grid>
                        <Grid item xs={12} sm={3} md={2}>
                            <Select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} displayEmpty size="small" fullWidth>
                                <MenuItem value="">All Statuses</MenuItem>
                                {statuses.map((s) => (
                                    <MenuItem key={s.id} value={s.code}>
                                        {s.name}
                                    </MenuItem>
                                ))}
                            </Select>
                        </Grid>
                        <Grid item xs={12} sm={3} md={2}>
                            <Select value={branchFilter} onChange={(e) => setBranchFilter(e.target.value)} displayEmpty size="small" fullWidth>
                                <MenuItem value="">All Branches</MenuItem>
                                {branches.map((b) => (
                                    <MenuItem key={b.id} value={b.id}>
                                        {b.name}
                                    </MenuItem>
                                ))}
                            </Select>
                        </Grid>
                        <Grid item xs={12} sm={2} md={2}>
                            <Button variant="contained" onClick={handleFilter} startIcon={<FilterIcon />} fullWidth>
                                Filter
                            </Button>
                        </Grid>
                    </Grid>
                </Paper>

                {/* Issue Table Container with Horizontal Scroll overflow-x: auto */}
                <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 3, overflowX: 'auto', width: '100%' }}>
                    <Table sx={{ minWidth: 1200 }}>
                        <TableHead sx={{ backgroundColor: '#f1f5f9' }}>
                            <TableRow>
                                <TableCell fontWeight="bold" sx={{ width: 120 }}>Sequence (Drag)</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 280, minHeight: 52 }}>Title & Category (Click to Manage)</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 180 }}>Priority (P-Level)</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 160 }}>Is Resolver (3rd-Party)</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 170, whiteSpace: 'nowrap' }}>Reported Date</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 170, whiteSpace: 'nowrap' }}>Due Date (SLA)</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 120 }}>Status</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 140 }}>Assigned To</TableCell>
                                <TableCell fontWeight="bold" sx={{ minWidth: 150 }}>SLA Performance</TableCell>
                                <TableCell fontWeight="bold" align="center" sx={{ minWidth: 130 }}>Actions</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {itemList.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={10} align="center" sx={{ py: 4, color: 'text.secondary' }}>
                                        No issues found matching current filters.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                itemList.map((issue, index) => (
                                    <TableRow
                                        key={issue.id}
                                        draggable
                                        onDragStart={(e) => handleDragStart(e, index)}
                                        onDragOver={(e) => handleDragOver(e, index)}
                                        onDrop={(e) => handleDrop(e, index)}
                                        hover
                                        sx={{
                                            minHeight: 52,
                                            backgroundColor: draggedIndex === index
                                                ? '#bae6fd'
                                                : (dragOverIndex === index ? '#f0f9ff' : 'inherit'),
                                            borderTop: dragOverIndex === index ? '2px solid #0284c7' : 'none',
                                            cursor: 'grab',
                                            transition: 'background-color 0.15s ease',
                                            '& td': { py: 1.8 }
                                        }}
                                    >
                                        {/* Sequence Column with Always Visible Drag Handle */}
                                        <TableCell sx={{ minHeight: 52 }}>
                                            <Tooltip title="Click & Drag to reorder sequence">
                                                <Box
                                                    sx={{
                                                        display: 'inline-flex',
                                                        alignItems: 'center',
                                                        gap: 0.8,
                                                        p: 0.5,
                                                        borderRadius: 1,
                                                        backgroundColor: '#f1f5f9',
                                                        border: '1px solid #cbd5e1',
                                                        '&:hover': {
                                                            backgroundColor: '#e2e8f0',
                                                            borderColor: '#0284c7',
                                                        },
                                                    }}
                                                >
                                                    <DragHandleIcon fontSize="small" sx={{ color: '#64748b' }} />
                                                    <Chip
                                                        label={`#${issue.resolution_sequence || index + 1}`}
                                                        size="small"
                                                        color="primary"
                                                        variant="filled"
                                                        sx={{ fontWeight: 'bold', minWidth: 32, height: 24 }}
                                                    />
                                                </Box>
                                            </Tooltip>
                                        </TableCell>

                                        {/* Title & Category - Click to Open Issue Detail / Manage Modal */}
                                        <TableCell
                                            onClick={() => handleOpenManageModal(issue)}
                                            sx={{
                                                minWidth: 280,
                                                minHeight: 52,
                                                whiteSpace: 'normal !important',
                                                cursor: 'pointer',
                                                transition: 'color 0.15s ease-in-out',
                                                '&:hover': { color: 'primary.main' },
                                            }}
                                        >
                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.8, flexWrap: 'wrap' }}>
                                                {activeTab === 'trash' && (
                                                    <Chip
                                                        label="TRASHED"
                                                        size="small"
                                                        color="error"
                                                        sx={{ height: 20, fontSize: '0.65rem', fontWeight: 'bold' }}
                                                    />
                                                )}
                                                <Typography
                                                    variant="body2"
                                                    fontWeight="bold"
                                                    sx={{
                                                        lineHeight: 1.4,
                                                        textDecoration: activeTab === 'trash' ? 'line-through' : 'underline decoration-transparent',
                                                        '&:hover': { textDecorationColor: 'inherit' },
                                                    }}
                                                >
                                                    {issue.title}
                                                </Typography>
                                            </Box>
                                            <Chip label={issue.category?.name || 'General'} size="small" variant="outlined" sx={{ mt: 0.5, pointerEvents: 'none' }} />
                                            {(() => {
                                                const delMsg = (issue.messages || []).find((m) => (m.message || '').includes('[ARCHIVED / DELETED]'));
                                                if (!delMsg) return null;
                                                const reasonText = delMsg.message.replace('[ARCHIVED / DELETED]:', '').trim();
                                                return (
                                                    <Box sx={{ mt: 0.8, p: 0.8, backgroundColor: '#fef2f2', borderLeft: '3px solid #ef4444', borderRadius: '4px' }}>
                                                        <Typography variant="caption" sx={{ fontWeight: 700, color: '#991b1b', display: 'block', fontSize: '0.72rem' }}>
                                                            📝 Archive Reason ({delMsg.creator?.name || 'User'}):
                                                        </Typography>
                                                        <Typography variant="caption" sx={{ color: '#7f1d1d', fontStyle: 'italic', fontSize: '0.75rem' }}>
                                                            "{reasonText}"
                                                        </Typography>
                                                    </Box>
                                                );
                                            })()}
                                        </TableCell>

                                        {/* Priority (P-Level) */}
                                        <TableCell>
                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                                {getPriorityChip(issue.priority)}
                                                <IconButton
                                                    size="small"
                                                    title="Quick Priority & Importance Grid"
                                                    onClick={(e) => { e.stopPropagation(); setSeverityIssue(issue); }}
                                                >
                                                    <FlagIcon fontSize="small" color="action" />
                                                </IconButton>
                                            </Box>
                                        </TableCell>

                                        {/* Resolver Type Toggle - Placed directly after Priority */}
                                        <TableCell onClick={(e) => e.stopPropagation()}>
                                            <FormControlLabel
                                                control={
                                                    <Switch
                                                        size="small"
                                                        checked={Boolean(issue.is_third_party_resolver)}
                                                        onChange={() => handleToggleResolver(issue)}
                                                    />
                                                }
                                                label={
                                                    <Typography variant="caption" fontWeight="bold" color={issue.is_third_party_resolver ? 'secondary.main' : 'text.secondary'}>
                                                        {issue.is_third_party_resolver ? '3rd-Party Dev' : 'Internal IT'}
                                                    </Typography>
                                                }
                                            />
                                        </TableCell>

                                        {/* Reported Date - Full Width */}
                                        <TableCell sx={{ whiteSpace: 'nowrap' }}>
                                            <Typography variant="body2">
                                                {issue.issue_at ? new Date(issue.issue_at).toLocaleString() : 'N/A'}
                                            </Typography>
                                        </TableCell>

                                        {/* Due Date - Full Width */}
                                        <TableCell sx={{ whiteSpace: 'nowrap' }}>
                                            <Typography
                                                variant="body2"
                                                fontWeight={issue.is_overdue ? 'bold' : 'normal'}
                                                color={issue.is_overdue ? 'error.main' : 'text.primary'}
                                            >
                                                {issue.due_date ? new Date(issue.due_date).toLocaleString() : 'Not Set'}
                                            </Typography>
                                        </TableCell>

                                        {/* Status */}
                                        <TableCell>
                                            <Chip
                                                label={issue.status?.name || 'Open'}
                                                color={issue.status?.code === 'CLOSED' ? 'success' : 'primary'}
                                                size="small"
                                                onClick={(e) => { e.stopPropagation(); handleOpenManageModal(issue); }}
                                                sx={{ cursor: 'pointer' }}
                                            />
                                        </TableCell>

                                        {/* Assigned To */}
                                        <TableCell>
                                            <Typography variant="body2">{issue.assigned_user?.name || 'Unassigned'}</Typography>
                                        </TableCell>

                                        {/* SLA Performance */}
                                        <TableCell>{getSlaBadge(issue)}</TableCell>

                                        {/* Actions */}
                                        <TableCell align="center">
                                            {activeTab === 'trash' ? (
                                                <Box sx={{ display: 'flex', justifyContent: 'center', gap: 0.5 }}>
                                                    <Button
                                                        size="small"
                                                        variant="contained"
                                                        color="success"
                                                        startIcon={<RestoreFromTrashIcon fontSize="small" />}
                                                        onClick={(e) => { e.stopPropagation(); setRestoreIssue(issue); }}
                                                        sx={{ textTransform: 'none', fontWeight: 'bold', px: 1.5, py: 0.3 }}
                                                    >
                                                        Restore
                                                    </Button>
                                                </Box>
                                            ) : (
                                                <Box sx={{ display: 'flex', justifyContent: 'center', gap: 0.5 }}>
                                                    <IconButton
                                                        size="small"
                                                        color="primary"
                                                        title="Manage Issue & Transition Workflow"
                                                        onClick={(e) => { e.stopPropagation(); handleOpenManageModal(issue); }}
                                                    >
                                                        <AutoAwesomeIcon fontSize="small" />
                                                    </IconButton>

                                                    <IconButton size="small" color="error" title="Move to Trash" onClick={(e) => { e.stopPropagation(); setDeletingIssue(issue); }}>
                                                        <DeleteIcon fontSize="small" />
                                                    </IconButton>

                                                    {issue.is_sla_failed && (
                                                        <Button
                                                            size="small"
                                                            variant="outlined"
                                                            color="success"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setSelectedIssueForOverride(issue);
                                                                setAdminRemark('');
                                                            }}
                                                        >
                                                            Override
                                                        </Button>
                                                    )}
                                                </Box>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </TableContainer>


                {/* Create Issue Modal — shared global component */}
                <CreateIssueModal
                    open={openCreateModal}
                    onClose={() => setOpenCreateModal(false)}
                    categories={categories}
                    priorities={priorities}
                    departments={departments}
                    importanceLevels={importanceLevels}
                    users={users}
                    auth={auth}
                />
                {/* Blade-Inspired Manage Issue Modal */}

                <Dialog open={Boolean(manageIssue)} onClose={() => setManageIssue(null)} maxWidth="md" fullWidth>
                    <DialogTitle sx={{ backgroundColor: '#0f172a', color: '#fff', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Typography variant="h6" fontWeight="bold">
                            Manage Issue #{manageIssue?.id}: {manageIssue?.title}
                        </Typography>
                        <Chip
                            label={manageIssue?.status?.name || 'OPEN'}
                            color={manageIssue?.status?.code === 'CLOSED' ? 'success' : 'info'}
                            size="small"
                        />
                    </DialogTitle>
                    <DialogContent dividers sx={{ p: 3 }}>
                        {manageIssue && (
                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
                                <Paper sx={{ p: 2, backgroundColor: '#f8fafc', borderRadius: 2 }}>
                                    <Typography variant="subtitle2" fontWeight="bold" sx={{ mb: 1.5 }}>
                                        Workflow Status Progress
                                    </Typography>
                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, alignItems: 'center', mb: 2 }}>
                                        {STATUS_STEPS.map((code) => {
                                            const isCurrent = manageIssue.status?.code === code;
                                            return (
                                                <Chip
                                                    key={code}
                                                    label={code.replace('_', ' ')}
                                                    color={isCurrent ? 'primary' : 'default'}
                                                    variant={isCurrent ? 'filled' : 'outlined'}
                                                    size="small"
                                                    sx={{ fontWeight: isCurrent ? 'bold' : 'normal' }}
                                                />
                                            );
                                        })}
                                    </Box>

                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, alignItems: 'center' }}>
                                        <Typography variant="caption" color="text.secondary">
                                            Transition to next step:
                                        </Typography>
                                        {STATUS_STEPS.filter((c) => c !== manageIssue.status?.code).map((toCode) => (
                                            <Button
                                                key={toCode}
                                                size="small"
                                                variant="outlined"
                                                color={toCode === 'CLOSED' ? 'success' : 'primary'}
                                                onClick={() => handleTransitionStatus(toCode)}
                                            >
                                                → {toCode.replace('_', ' ')}
                                            </Button>
                                        ))}
                                    </Box>
                                </Paper>

                                <Tabs value={manageModalTab} onChange={(e, val) => setManageModalTab(val)}>
                                    <Tab label="Edit Issue Details" />
                                    <Tab label="Discussion Notes & Log Messages" />
                                    <Tab label="Activity History" />
                                </Tabs>

                                {manageModalTab === 0 && (
                                    <Grid container spacing={2} sx={{ pt: 1 }}>
                                        <Grid item xs={12}>
                                            <TextField
                                                label="Title"
                                                required
                                                fullWidth
                                                value={manageForm.title}
                                                onChange={(e) => setManageForm({ ...manageForm, title: e.target.value })}
                                            />
                                        </Grid>
                                        <Grid item xs={12} sm={6}>
                                            <FormControl fullWidth required>
                                                <InputLabel>Category</InputLabel>
                                                <Select
                                                    value={manageForm.issue_category_id}
                                                    label="Category"
                                                    onChange={(e) => setManageForm({ ...manageForm, issue_category_id: e.target.value })}
                                                >
                                                    {categories.map((c) => (
                                                        <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>
                                                    ))}
                                                </Select>
                                            </FormControl>
                                        </Grid>
                                        <Grid item xs={12} sm={6}>
                                            <FormControl fullWidth>
                                                <InputLabel>Priority (P-Level)</InputLabel>
                                                <Select
                                                    value={manageForm.issue_priority_id}
                                                    label="Priority (P-Level)"
                                                    onChange={(e) => setManageForm({ ...manageForm, issue_priority_id: e.target.value })}
                                                >
                                                    {priorities.map((p) => (
                                                        <MenuItem key={p.id} value={p.id}>{p.code} - {p.name}</MenuItem>
                                                    ))}
                                                </Select>
                                            </FormControl>
                                        </Grid>
                                        <Grid item xs={12} sm={6}>
                                            <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
                                                <FormControl fullWidth>
                                                    <InputLabel>Assigned Staff</InputLabel>
                                                    <Select
                                                        value={manageForm.assigned_user_id}
                                                        label="Assigned Staff"
                                                        onChange={(e) => setManageForm({ ...manageForm, assigned_user_id: e.target.value })}
                                                    >
                                                        <MenuItem value=""><em>Unassigned</em></MenuItem>
                                                        {users.map((u) => (
                                                            <MenuItem key={u.id} value={u.id}>
                                                                {u.name} {u.department?.name ? `(${u.department.name})` : ''}
                                                            </MenuItem>
                                                        ))}
                                                    </Select>
                                                </FormControl>
                                                <Button
                                                    type="button"
                                                    variant="outlined"
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                        setAssignUserModal({
                                                            open: true,
                                                            issue: manageIssue,
                                                            targetStatus: null,
                                                            mode: 'edit_form',
                                                        });
                                                    }}
                                                    sx={{ minWidth: 44, height: 40, px: 1.5, borderColor: '#cbd5e1', flexShrink: 0 }}
                                                    title="Search and select user with department filter"
                                                >
                                                    <SearchIcon fontSize="small" />
                                                </Button>
                                            </Box>
                                        </Grid>
                                        <Grid item xs={12} sm={6}>
                                            <FormControl fullWidth>
                                                <InputLabel>Resolution Department</InputLabel>
                                                <Select
                                                    value={manageForm.resolution_department_id}
                                                    label="Resolution Department"
                                                    onChange={(e) => setManageForm({ ...manageForm, resolution_department_id: e.target.value })}
                                                >
                                                    {departments.map((d) => (
                                                        <MenuItem key={d.id} value={d.id}>{d.name}</MenuItem>
                                                    ))}
                                                </Select>
                                            </FormControl>
                                        </Grid>
                                        <Grid item xs={12}>
                                            <FormControlLabel
                                                control={
                                                    <Switch
                                                        checked={manageForm.is_third_party_resolver}
                                                        onChange={(e) => setManageForm({ ...manageForm, is_third_party_resolver: e.target.checked })}
                                                    />
                                                }
                                                label="Third-Party Developer Fix (Toggle External Resolver)"
                                            />
                                        </Grid>
                                        <Grid item xs={12}>
                                            <TextField
                                                label="Description"
                                                required
                                                multiline
                                                rows={3}
                                                fullWidth
                                                value={manageForm.description}
                                                onChange={(e) => setManageForm({ ...manageForm, description: e.target.value })}
                                            />
                                        </Grid>
                                        <Grid item xs={12}>
                                            <TextField
                                                label="Proposed Solution"
                                                multiline
                                                rows={2}
                                                fullWidth
                                                value={manageForm.proposed_solution}
                                                onChange={(e) => setManageForm({ ...manageForm, proposed_solution: e.target.value })}
                                            />
                                        </Grid>
                                    </Grid>
                                )}

                                {manageModalTab === 1 && (
                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                        <List sx={{ maxHeight: 300, overflow: 'auto', backgroundColor: '#f8fafc', borderRadius: 2, p: 2 }}>
                                            {manageIssue.messages && manageIssue.messages.length > 0 ? (
                                                manageIssue.messages.map((m) => (
                                                    <Box
                                                        key={m.id}
                                                        sx={{
                                                            p: 1.5,
                                                            mb: 1,
                                                            borderRadius: 1,
                                                            backgroundColor: m.is_log_note ? '#fef9c3' : '#fff',
                                                            borderLeft: m.is_log_note ? '4px solid #eab308' : '1px solid #e2e8f0',
                                                        }}
                                                    >
                                                        <Typography variant="subtitle2" fontWeight="bold" color={m.is_log_note ? 'warning.dark' : 'text.primary'}>
                                                            {m.creator?.name || 'System'}:
                                                        </Typography>
                                                        <Typography variant="body2">{m.message}</Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            {new Date(m.created_at).toLocaleString()}
                                                        </Typography>
                                                    </Box>
                                                ))
                                            ) : (
                                                <Typography variant="body2" color="text.secondary">No messages or log notes yet.</Typography>
                                            )}
                                        </List>

                                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                                            <TextField
                                                label="Add Discussion Message or System Log Note"
                                                multiline
                                                rows={2}
                                                fullWidth
                                                value={newMessage}
                                                onChange={(e) => setNewMessage(e.target.value)}
                                            />
                                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                                <FormControlLabel
                                                    control={
                                                        <Checkbox
                                                            checked={isLogNote}
                                                            onChange={(e) => setIsLogNote(e.target.checked)}
                                                        />
                                                    }
                                                    label="Flag as Yellow System Log Note"
                                                />
                                                <Button variant="contained" endIcon={<SendIcon />} onClick={handleAddMessage} disabled={!newMessage.trim()}>
                                                    Add Note / Message
                                                </Button>
                                            </Box>
                                        </Box>
                                    </Box>
                                )}

                                {manageModalTab === 2 && (
                                    <List sx={{ maxHeight: 300, overflow: 'auto', backgroundColor: '#f8fafc', borderRadius: 2, p: 2 }}>
                                        {manageIssue.activity_logs && manageIssue.activity_logs.length > 0 ? (
                                            manageIssue.activity_logs.map((log) => (
                                                <ListItem key={log.id} divider>
                                                    <ListItemText
                                                        primary={`${log.action} - ${log.description}`}
                                                        secondary={`Performer: ${log.performer?.name || 'Unknown'} • ${new Date(log.created_at).toLocaleString()}`}
                                                    />
                                                </ListItem>
                                            ))
                                        ) : (
                                            <Typography variant="body2" color="text.secondary">No activity logs recorded.</Typography>
                                        )}
                                    </List>
                                )}
                            </Box>
                        )}
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setManageIssue(null)}>Close</Button>
                        <Button variant="contained" color="primary" onClick={handleSaveManageModal}>
                            Save Changes
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* Severity Quick Grid Modal */}
                <Dialog open={Boolean(severityIssue)} onClose={() => setSeverityIssue(null)} maxWidth="sm" fullWidth>
                    <DialogTitle>Quick Priority & Importance Grid</DialogTitle>
                    <DialogContent dividers>
                        {severityIssue && (
                            <Grid container spacing={3}>
                                <Grid item xs={12} sm={6}>
                                    <Typography variant="subtitle2" fontWeight="bold" sx={{ mb: 1 }}>
                                        Priority Level (SLA)
                                    </Typography>
                                    <RadioGroup
                                        value={severityIssue.issue_priority_id}
                                        onChange={(e) => handleSaveSeverity(e.target.value, null)}
                                    >
                                        {priorities.map((p) => (
                                            <FormControlLabel key={p.id} value={p.id} control={<Radio />} label={`${p.code} - ${p.name}`} />
                                        ))}
                                    </RadioGroup>
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <Typography variant="subtitle2" fontWeight="bold" sx={{ mb: 1 }}>
                                        Importance Level
                                    </Typography>
                                    <RadioGroup
                                        value={severityIssue.issue_importance_id}
                                        onChange={(e) => handleSaveSeverity(null, e.target.value)}
                                    >
                                        {importanceLevels.map((imp) => (
                                            <FormControlLabel key={imp.id} value={imp.id} control={<Radio />} label={imp.name} />
                                        ))}
                                    </RadioGroup>
                                </Grid>
                            </Grid>
                        )}
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setSeverityIssue(null)}>Close</Button>
                    </DialogActions>
                </Dialog>

                {/* Move Issue to Trash Confirm Modal */}
                <Dialog open={Boolean(deletingIssue)} onClose={() => setDeletingIssue(null)} maxWidth="sm" fullWidth>
                    <DialogTitle sx={{ backgroundColor: '#dc2626', color: '#fff', fontWeight: 'bold' }}>Archive / Move Issue to Trash</DialogTitle>
                    <DialogContent sx={{ p: 3, pt: 3 }}>
                        <Typography variant="body1" sx={{ mt: 1, mb: 1 }}>
                            Are you sure you want to archive Issue <strong>#{deletingIssue?.id} ({deletingIssue?.title})</strong>?
                        </Typography>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Please provide a reason for archiving/deleting this issue. This will be recorded in the issue's log notes.
                        </Typography>
                        <TextField
                            label="Archive / Deletion Reason *"
                            required
                            multiline
                            rows={3}
                            fullWidth
                            size="small"
                            value={deleteReason}
                            onChange={(e) => setDeleteReason(e.target.value)}
                            placeholder="Please explain why this issue is being archived or deleted..."
                            error={deletingIssue && !deleteReason.trim()}
                            helperText={!deleteReason.trim() ? 'A reason is required to archive this issue.' : ''}
                        />
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setDeletingIssue(null)} color="inherit">Cancel</Button>
                        <Button
                            variant="contained"
                            color="error"
                            disabled={!deleteReason.trim()}
                            onClick={handleDeleteSubmit}
                            startIcon={<DeleteIcon />}
                        >
                            Archive Issue
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* Restore Issue Modal */}
                <Dialog open={Boolean(restoreIssue)} onClose={() => setRestoreIssue(null)} maxWidth="xs" fullWidth>
                    <DialogTitle sx={{ backgroundColor: '#16a34a', color: '#fff', fontWeight: 'bold' }}>Restore Issue from Trash?</DialogTitle>
                    <DialogContent sx={{ p: 3, pt: 3 }}>
                        <Typography variant="body1" sx={{ mt: 1 }}>
                            Are you sure you want to restore Issue <strong>#{restoreIssue?.id} ({restoreIssue?.title})</strong> back to active issues?
                        </Typography>
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setRestoreIssue(null)} color="inherit">Cancel</Button>
                        <Button variant="contained" color="success" onClick={() => handleRestoreSubmit(restoreIssue)} startIcon={<RestoreFromTrashIcon />}>
                            Restore Issue
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* Admin SLA Override Modal */}
                <Dialog open={Boolean(selectedIssueForOverride)} onClose={() => setSelectedIssueForOverride(null)} maxWidth="sm" fullWidth>
                    <DialogTitle color="success.main">Admin SLA Override (Change to Success)</DialogTitle>
                    <DialogContent>
                        <Typography variant="body2" sx={{ mb: 2 }}>
                            Override SLA Fail status for Issue <strong>#{selectedIssueForOverride?.id}</strong>. This resets fail points to 0 and records your remark in system log notes.
                        </Typography>
                        <TextField
                            label="Mandatory Admin Remark / Reason"
                            required
                            multiline
                            rows={3}
                            fullWidth
                            placeholder="Explain why SLA fail is waived (e.g., client hardware delay, vendor dependency)..."
                            value={adminRemark}
                            onChange={(e) => setAdminRemark(e.target.value)}
                        />
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setSelectedIssueForOverride(null)}>Cancel</Button>
                        <Button variant="contained" color="success" onClick={handleSlaOverride} disabled={!adminRemark.trim()}>
                            Override to Success
                        </Button>
                    </DialogActions>
                </Dialog>
                {/* Root Cause Modal (Triggered when user closes or marks issue as done) */}
                <Dialog
                    open={rootCauseModal.open}
                    onClose={() => setRootCauseModal({ open: false, issue: null, targetStatus: null })}
                    maxWidth="xs"
                    fullWidth
                >
                    <DialogTitle sx={{ backgroundColor: '#0f172a', color: '#fff', fontWeight: 'bold' }}>
                        Record Root Cause & {rootCauseModal.targetStatus?.name || 'Close Issue'}
                    </DialogTitle>
                    <DialogContent sx={{ p: 3, pt: 3 }}>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Root cause category is <strong>required</strong> before changing status to <strong>{rootCauseModal.targetStatus?.name || 'Closed'}</strong> for <em>{rootCauseModal.issue?.title}</em>.
                        </Typography>
                        <Stack spacing={2.5} sx={{ mt: 1 }}>
                            <FormControl fullWidth required size="small" error={!rootCauseForm.root_cause_id}>
                                <InputLabel>Root Cause Category *</InputLabel>
                                <Select
                                    value={rootCauseForm.root_cause_id}
                                    label="Root Cause Category *"
                                    onChange={(e) => setRootCauseForm({ ...rootCauseForm, root_cause_id: e.target.value })}
                                >
                                    <MenuItem value=""><em>Select Root Cause...</em></MenuItem>
                                    {rootCauses.map((rc) => (
                                        <MenuItem key={rc.id} value={rc.id}>{rc.name}</MenuItem>
                                    ))}
                                </Select>
                            </FormControl>

                            <TextField
                                label="Resolution Remarks / Notes"
                                multiline
                                rows={3}
                                fullWidth
                                size="small"
                                value={rootCauseForm.remark}
                                onChange={(e) => setRootCauseForm({ ...rootCauseForm, remark: e.target.value })}
                                placeholder="Enter details on how the issue was resolved or root cause notes..."
                            />
                        </Stack>
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setRootCauseModal({ open: false, issue: null, targetStatus: null })} color="inherit">
                            Cancel
                        </Button>
                        <Button 
                            onClick={handleConfirmCloseWithRootCause} 
                            variant="contained" 
                            color="success"
                            disabled={!rootCauseForm.root_cause_id}
                        >
                            Confirm & {rootCauseModal.targetStatus?.name || 'Close Issue'}
                        </Button>
                    </DialogActions>
                </Dialog>
                {/* Reopen / Change Back Status Modal (Mandatory Remark) */}
                <Dialog
                    open={reopenModal.open}
                    onClose={() => setReopenModal({ open: false, issue: null, targetStatus: null })}
                    maxWidth="xs"
                    fullWidth
                >
                    <DialogTitle sx={{ backgroundColor: '#1e293b', color: '#fff', fontWeight: 'bold' }}>
                        Reopen / Change Status Reason
                    </DialogTitle>
                    <DialogContent sx={{ p: 3, pt: 3 }}>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Issue <strong>#{reopenModal.issue?.id}</strong> is currently <strong>{reopenModal.issue?.status?.name || reopenModal.issue?.status_name || 'Closed/Done'}</strong>. Please provide a reason for changing status back to <strong>{reopenModal.targetStatus?.name}</strong>.
                        </Typography>
                        <TextField
                            label="Reason / Log Note *"
                            required
                            multiline
                            rows={3}
                            fullWidth
                            size="small"
                            value={reopenRemark}
                            onChange={(e) => setReopenRemark(e.target.value)}
                            placeholder="Explain why this issue is being reopened or changed back..."
                            error={!reopenRemark.trim()}
                            helperText={!reopenRemark.trim() ? 'Remark is required to record in issue logs.' : ''}
                        />
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setReopenModal({ open: false, issue: null, targetStatus: null })} color="inherit">
                            Cancel
                        </Button>
                        <Button 
                            onClick={handleConfirmReopen} 
                            variant="contained" 
                            color="warning"
                            disabled={!reopenRemark.trim()}
                        >
                            Confirm & Change to {reopenModal.targetStatus?.name}
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* Shared User Select Modal (for Assigning User with Department Filter & Real-Time Search) */}
                <UserSelectModal
                    open={assignUserModal.open}
                    onClose={() => setAssignUserModal({ open: false, issue: null, targetStatus: null, mode: 'transition' })}
                    onSelect={handleUserSelectedForAssign}
                    title={
                        assignUserModal.mode === 'transition' && assignUserModal.targetStatus
                            ? `Assign User to Advance Status to "${assignUserModal.targetStatus.name}"`
                            : 'Select Assigned Employee'
                    }
                    subtitle={
                        assignUserModal.mode === 'transition'
                            ? 'An assigned person is required for this stage. Select an employee to proceed.'
                            : 'Search by name, branch, or filter by department'
                    }
                    selectedUserId={manageForm.assigned_user_id || manageIssue?.assigned_user_id}
                    users={users}
                    departments={departments}
                    currentUserName={auth?.user?.name || ''}
                    initialDepartmentIds={
                        manageForm.resolution_department_id
                            ? [Number(manageForm.resolution_department_id)]
                            : (manageIssue?.resolution_department_id ? [Number(manageIssue.resolution_department_id)] : [])
                    }
                />
            </Box>
        </AsideLayout>
    );
}
