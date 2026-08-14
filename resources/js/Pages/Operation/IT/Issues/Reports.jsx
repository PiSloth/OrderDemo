import React, { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import AsideLayout from '@/Layouts/AsideLayout';
import CreateIssueModal from '@/Components/IT/CreateIssueModal';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { exportDetailedIssuePDF, exportCategorySummaryPDF } from '@/utils/pdfExportHelper';
import StatusStepper from '@/Components/IT/StatusStepper';
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
    TextField,
    Select,
    MenuItem,
    MenuList,
    FormControl,
    InputLabel,
    Typography,
    ToggleButtonGroup,
    ToggleButton,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    IconButton,
    Tabs,
    Tab,
    List,
    ListItem,
    ListItemText,
    FormControlLabel,
    Checkbox,
    OutlinedInput,
    Pagination,
    Popover,
    Popper,
    ClickAwayListener,
    Stack,
    Badge,
    Divider,
} from '@mui/material';

import {
    FileDownload as FileDownloadIcon,
    PictureAsPdf as PdfIcon,
    CheckCircle as CheckCircleIcon,
    Cancel as CancelIcon,
    Edit as EditIcon,
    AutoAwesome as AutoAwesomeIcon,
    Send as SendIcon,
    Add as AddIcon,
    People as PeopleIcon,
    Business as BusinessIcon,
    AllInclusive as AllIcon,
    LinearScale as StepperIcon,
    FolderOpen as FolderOpenIcon,
    AssignmentInd as AssignmentIndIcon,
    Autorenew as AutorenewIcon,
    PendingActions as PendingActionsIcon,
    DoneAll as DoneAllIcon,
    Lock as LockIcon,
    Task as TaskIcon,
    ViewList as ViewListIcon,
    Chat as ChatIcon,
    Title as TitleIcon,
    FolderOutlined as FolderOutlinedIcon,
    LowPriority as LowPriorityIcon,
    PersonOutlined as PersonOutlineIcon,
    AccessTime as AccessTimeIcon,
} from '@mui/icons-material';

import { styled } from '@mui/material/styles';
import FormGroup from '@mui/material/FormGroup';
// import FormControlLabel from '@mui/material/FormControlLabel';
import Switch from '@mui/material/Switch';

const MaterialUISwitch = styled(Switch)(({ theme }) => ({
    width: 62,
    height: 34,
    padding: 7,
    '& .MuiSwitch-switchBase': {
        margin: 1,
        padding: 0,
        transform: 'translateX(6px)',
        '&.Mui-checked': {
            color: '#fff',
            transform: 'translateX(22px)',
            '& .MuiSwitch-thumb': {
                backgroundColor: '#7c3aed', // Purple thumb for 3rd-Party Clock
            },
            '& .MuiSwitch-thumb:before': {
                backgroundImage: `url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" viewBox="0 0 24 24"><path fill="${encodeURIComponent(
                    '#fff',
                )}" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>')`,
            },
            '& + .MuiSwitch-track': {
                opacity: 1,
                backgroundColor: '#ddd6fe',
            },
        },
    },
    '& .MuiSwitch-thumb': {
        backgroundColor: '#d97706', // Amber thumb for Internal IT Thunder
        width: 32,
        height: 32,
        boxShadow: '0 2px 4px rgba(0,0,0,0.2)',
        '&::before': {
            content: "''",
            position: 'absolute',
            width: '100%',
            height: '100%',
            left: 0,
            top: 0,
            backgroundRepeat: 'no-repeat',
            backgroundPosition: 'center',
            backgroundImage: `url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" viewBox="0 0 24 24"><path fill="${encodeURIComponent(
                '#fff',
            )}" d="M11 15H6l7-14v7h5l-7 14v-7z"/></svg>')`,
        },
    },
    '& .MuiSwitch-track': {
        opacity: 1,
        backgroundColor: '#fef3c7',
        borderRadius: 20 / 2,
    },
}));

const STATUS_STEPS = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'PENDING', 'DONE', 'CLOSED'];

const ALL_COLUMNS = [
    { key: 'sequence', label: 'Sequence' },
    { key: 'title', label: 'Title & Category' },
    { key: 'priority', label: 'Priority' },
    { key: 'status', label: 'Workflow Status' },
    { key: 'resolver_type', label: 'Is Resolver (3rd-Party)' },
    { key: 'assigned_user', label: 'Assigned To' },
    { key: 'reported_date', label: 'Reported Date' },
    { key: 'due_date', label: 'Due Date' },
    { key: 'closed_date', label: 'Closed Date' },
    { key: 'sla_status', label: 'SLA Status' },
    { key: 'fail_points', label: 'Fail Points' },
    { key: 'actions', label: 'Actions (Edit / Manage)' },
];

export default function Reports({ report, filters, categories = [], priorities = [], importanceLevels = [], statuses = [], departments = [], users = [], rootCauses = [], app_name = 'Our Company', auth_user = {} }) {
    const [periodType, setPeriodType] = useState(filters.period_type || 'weekly');
    const [startDate, setStartDate] = useState(filters.start_date || report.start_date);
    const [endDate, setEndDate] = useState(filters.end_date || report.end_date);
    const [resolverType, setResolverType] = useState(filters.resolver_type || 'all');

    // ── Table Column Visibility & Browser Storage Persistence ──────────────────
    const [visibleColumns, setVisibleColumns] = useState(() => {
        try {
            const saved = localStorage.getItem('it_reports_visible_columns');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed;
                }
            }
        } catch (e) { }
        return ALL_COLUMNS.map((c) => c.key);
    });

    const [columnMenuAnchor, setColumnMenuAnchor] = useState(null);

    const handleToggleColumn = (columnKey) => {
        let updated;
        if (visibleColumns.includes(columnKey)) {
            if (visibleColumns.length === 1) return;
            updated = visibleColumns.filter((k) => k !== columnKey);
        } else {
            updated = [...visibleColumns, columnKey];
        }
        setVisibleColumns(updated);
        try {
            localStorage.setItem('it_reports_visible_columns', JSON.stringify(updated));
        } catch (e) { }
    };

    const isColumnVisible = (columnKey) => visibleColumns.includes(columnKey);

    const initialCategoryIds = filters.category_ids
        ? (Array.isArray(filters.category_ids) ? filters.category_ids.map(Number) : filters.category_ids.toString().split(',').map(Number).filter(Boolean))
        : [];
    const [selectedCategoryIds, setSelectedCategoryIds] = useState(initialCategoryIds);

    const initialStatusCodes = filters.status_codes
        ? (Array.isArray(filters.status_codes) ? filters.status_codes : filters.status_codes.toString().split(',').filter(Boolean))
        : [];
    const [selectedStatusCodes, setSelectedStatusCodes] = useState(initialStatusCodes);

    // Flatpickr ref
    const dateRangeRef = useRef(null);
    const fpInstance = useRef(null);

    // ── Pagination State (6 items per page) ──────────────────────────────────
    const [page, setPage] = useState(1);
    const pageSize = 6;

    useEffect(() => {
        setPage(1);
    }, [report.items, periodType, resolverType, startDate, endDate, selectedCategoryIds, selectedStatusCodes]);

    const totalPages = Math.ceil((report.items || []).length / pageSize);
    const paginatedItems = (report.items || []).slice((page - 1) * pageSize, page * pageSize);

    // ── Date Formatting Helpers ──────────────────────────────────────────────
    const formatDateCustom = (dateStr) => {
        if (!dateStr) return 'N/A';
        if (typeof dateStr === 'string' && dateStr.includes(',')) return dateStr;
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const day = d.getDate();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const year = String(d.getFullYear()).slice(-2);
        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return `${day} ${month} ${year}, ${hours}:${minutes} ${ampm}`;
    };

    // ── Status Icon & Color Helper ───────────────────────────────────────────
    const getStatusConfig = (code) => {
        switch (code) {
            case 'OPEN':
                return {
                    label: 'OPEN',
                    icon: <FolderOpenIcon sx={{ fontSize: 16 }} />,
                    bg: '#2563eb', // Vibrant Blue
                    hoverBg: '#1d4ed8',
                };
            case 'ASSIGNED':
                return {
                    label: 'ASSIGNED',
                    icon: <AssignmentIndIcon sx={{ fontSize: 16 }} />,
                    bg: '#7c3aed', // Purple
                    hoverBg: '#6d28d9',
                };
            case 'IN_PROGRESS':
                return {
                    label: 'IN PROGRESS',
                    icon: <AutorenewIcon sx={{ fontSize: 16 }} />,
                    bg: '#d97706', // Amber / Orange
                    hoverBg: '#b45309',
                };
            case 'PENDING':
            case 'PENDING_INFO':
                return {
                    label: 'PENDING',
                    icon: <PendingActionsIcon sx={{ fontSize: 16 }} />,
                    bg: '#ea580c', // Deep Orange
                    hoverBg: '#c2410c',
                };
            case 'DONE':
                return {
                    label: 'DONE',
                    icon: <DoneAllIcon sx={{ fontSize: 16 }} />,
                    bg: '#059669', // Emerald Green
                    hoverBg: '#047857',
                };
            case 'CLOSED':
                return {
                    label: 'CLOSED',
                    icon: <LockIcon sx={{ fontSize: 16 }} />,
                    bg: '#334155', // Slate Grey
                    hoverBg: '#1e293b',
                };
            default:
                return {
                    label: code || 'STATUS',
                    icon: <TaskIcon sx={{ fontSize: 16 }} />,
                    bg: '#2563eb',
                    hoverBg: '#1d4ed8',
                };
        }
    };

    const formatDateShort = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const day = d.getDate();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const year = String(d.getFullYear()).slice(-2);
        return `${day} ${month} ${year}`;
    };

    // ── Auto-apply helper ────────────────────────────────────────────────────
    const applyFilters = (overrides = {}) => {
        const catIds = overrides.category_ids !== undefined ? overrides.category_ids : selectedCategoryIds;
        const catStr = Array.isArray(catIds) ? catIds.join(',') : catIds;

        const stCodes = overrides.status_codes !== undefined ? overrides.status_codes : selectedStatusCodes;
        const stStr = Array.isArray(stCodes) ? stCodes.join(',') : stCodes;

        const params = {
            period_type: periodType,
            start_date: startDate,
            end_date: endDate,
            resolver_type: resolverType,
            category_ids: catStr,
            status_codes: stStr,
            ...overrides,
        };
        router.get('/operations/it/issues/reports', params, { preserveState: true });
    };

    // Category Multi-Select Handler
    const handleCategoryChange = (event) => {
        const { target: { value } } = event;
        const newCatIds = typeof value === 'string' ? value.split(',') : value;
        setSelectedCategoryIds(newCatIds);
        applyFilters({ category_ids: newCatIds });
    };

    // Status Multi-Select Handler
    const handleStatusChange = (event) => {
        const { target: { value } } = event;
        const newStatusCodes = typeof value === 'string' ? value.split(',') : value;
        setSelectedStatusCodes(newStatusCodes);
        applyFilters({ status_codes: newStatusCodes });
    };

    // ── Flatpickr date-range initialisation ─────────────────────────────────
    useEffect(() => {
        if (!dateRangeRef.current) return;
        fpInstance.current = flatpickr(dateRangeRef.current, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [startDate, endDate].filter(Boolean),
            allowInput: true,
            onChange: (selectedDates) => {
                if (selectedDates.length === 2) {
                    const fmt = (d) => d.toISOString().slice(0, 10);
                    const sd = fmt(selectedDates[0]);
                    const ed = fmt(selectedDates[1]);
                    setStartDate(sd);
                    setEndDate(ed);
                    applyFilters({ start_date: sd, end_date: ed });
                }
            },
        });
        return () => fpInstance.current?.destroy();
    }, []);

    // Create Issue Modal State
    const [openCreateModal, setOpenCreateModal] = useState(false);

    // Manage Issue Modal
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

    // Admin Override Modal
    const [selectedIssueForOverride, setSelectedIssueForOverride] = useState(null);
    const [adminRemark, setAdminRemark] = useState('');

    // Popover Stepper State
    const [popoverAnchor, setPopoverAnchor] = useState(null);
    const [popoverIssue, setPopoverIssue] = useState(null);

    // Root Cause Modal State (shown when closing issue)
    const [rootCauseModal, setRootCauseModal] = useState({ open: false, issue: null, targetStatus: null });
    const [rootCauseForm, setRootCauseForm] = useState({ root_cause_id: '', remark: '' });

    // Reopen / Change Back Status Modal State (shown when changing from CLOSED or DONE)
    const [reopenModal, setReopenModal] = useState({ open: false, issue: null, targetStatus: null });
    const [reopenRemark, setReopenRemark] = useState('');

    // Dedicated Discussion & Log Note Modal State
    const [discussionIssue, setDiscussionIssue] = useState(null);
    const [discussionMessage, setDiscussionMessage] = useState('');
    const [discussionIsLogNote, setDiscussionIsLogNote] = useState(false);

    // Edit Reported Date Modal State
    const [editDateIssue, setEditDateIssue] = useState(null);
    const [editDateValue, setEditDateValue] = useState('');
    const [editDateRemark, setEditDateRemark] = useState('');

    // Edit Manual Schedule Due Date Modal State
    const [editDueDateIssue, setEditDueDateIssue] = useState(null);
    const [editDueDateValue, setEditDueDateValue] = useState('');
    const [editDueDateRemark, setEditDueDateRemark] = useState('');

    const getDatetimeLocalString = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };

    const handleOpenEditDateModal = (item) => {
        setEditDateIssue(item);
        const rawDate = item.issue_at_raw || item.issue_at;
        setEditDateValue(getDatetimeLocalString(rawDate));
        setEditDateRemark('');
    };

    const handleSaveReportedDate = () => {
        if (!editDateIssue || !editDateValue || !editDateRemark.trim()) return;
        router.patch(
            `/operations/it/issues/${editDateIssue.id}/reported-date`,
            {
                issue_at: editDateValue,
                remark: editDateRemark,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setEditDateIssue(null);
                    setEditDateValue('');
                    setEditDateRemark('');
                },
            }
        );
    };

    const handleOpenEditDueDateModal = (item) => {
        setEditDueDateIssue(item);
        const rawDate = item.due_date_raw || item.due_date;
        setEditDueDateValue(getDatetimeLocalString(rawDate));
        setEditDueDateRemark('');
    };

    const handleSaveDueDate = () => {
        if (!editDueDateIssue || !editDueDateValue || !editDueDateRemark.trim()) {
            alert('Please select a Due Date and provide a remark.');
            return;
        }
        router.patch(
            `/operations/it/issues/${editDueDateIssue.id}/due-date`,
            {
                due_date: editDueDateValue,
                remark: editDueDateRemark,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setEditDueDateIssue(null);
                    setEditDueDateValue('');
                    setEditDueDateRemark('');
                },
            }
        );
    };

    const handleToggleResolver = (item) => {
        router.put(
            `/operations/it/issues/${item.id}`,
            {
                title: item.title,
                description: item.description || item.title || 'N/A',
                issue_category_id: item.issue_category_id,
                issue_priority_id: item.issue_priority_id,
                issue_importance_id: item.issue_importance_id,
                assigned_user_id: item.assigned_user_id,
                resolution_department_id: item.resolution_department_id,
                proposed_solution: item.proposed_solution,
                issue_by: item.issue_by,
                is_third_party_resolver: !item.is_third_party_resolver,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleSaveDiscussionMessage = () => {
        if (!discussionIssue || !discussionMessage.trim()) return;
        router.post(
            `/operations/it/issues/${discussionIssue.id}/messages`,
            {
                message: discussionMessage,
                is_log_note: discussionIsLogNote,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setDiscussionMessage('');
                    setDiscussionIsLogNote(false);
                    setDiscussionIssue(null);
                },
            }
        );
    };

    const handleOpenStatusPopover = (event, item) => {
        setPopoverAnchor(event.currentTarget);
        setPopoverIssue(item);
    };

    const handleCloseStatusPopover = () => {
        setPopoverAnchor(null);
        setPopoverIssue(null);
    };

    const handleStatusStepClick = (issue, targetStatus) => {
        handleCloseStatusPopover();
        const currentCode = issue.status?.code || issue.status_code;
        const isCurrentlyClosedOrDone = currentCode === 'CLOSED' || currentCode === 'DONE';
        const isTargetClosedOrDone = targetStatus.code === 'CLOSED' || targetStatus.code === 'DONE';

        // 1. Moving to CLOSED or DONE -> Requires Root Cause
        if (isTargetClosedOrDone) {
            setRootCauseModal({ open: true, issue, targetStatus });
            setRootCauseForm({ root_cause_id: '', remark: '' });
            return;
        }

        // 2. Moving back from CLOSED or DONE -> Requires Mandatory Reason / Remark
        if (isCurrentlyClosedOrDone && !isTargetClosedOrDone) {
            setReopenModal({ open: true, issue, targetStatus });
            setReopenRemark('');
            return;
        }

        // Normal transition
        router.patch(
            `/operations/it/issues/${issue.id}/status`,
            { issue_status_id: targetStatus.id },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setManageIssue(null),
            }
        );
    };

    const handleConfirmCloseWithRootCause = () => {
        const { issue, targetStatus } = rootCauseModal;
        if (!issue || !targetStatus) return;

        if (!rootCauseForm.root_cause_id) {
            alert('Please select a Root Cause before changing status to ' + (targetStatus.name || targetStatus.code));
            return;
        }

        router.patch(
            `/operations/it/issues/${issue.id}/status`,
            {
                issue_status_id: targetStatus.id,
                root_cause_id: rootCauseForm.root_cause_id,
                remark: rootCauseForm.remark,
                proposed_solution: manageForm?.proposed_solution || issue.proposed_solution,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setRootCauseModal({ open: false, issue: null, targetStatus: null });
                    setManageIssue(null);
                },
            }
        );
    };

    const handleConfirmReopen = () => {
        const { issue, targetStatus } = reopenModal;
        if (!issue || !targetStatus) return;

        if (!reopenRemark.trim()) {
            alert('Please provide a remark explaining the status change.');
            return;
        }

        router.patch(
            `/operations/it/issues/${issue.id}/status`,
            {
                issue_status_id: targetStatus.id,
                remark: reopenRemark.trim(),
                proposed_solution: manageForm?.proposed_solution || issue.proposed_solution,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setReopenModal({ open: false, issue: null, targetStatus: null });
                    setManageIssue(null);
                },
            }
        );
    };

    // Period toggle — auto applies immediately
    const handlePeriodChange = (event, newPeriod) => {
        if (!newPeriod) return;
        setPeriodType(newPeriod);
        applyFilters({ period_type: newPeriod });
    };

    // Resolver toggle — auto applies immediately
    const handleResolverChange = (val) => {
        setResolverType(val);
        applyFilters({ resolver_type: val });
    };

    const handleExport = () => {
        const catStr = selectedCategoryIds.join(',');
        const stStr = selectedStatusCodes.join(',');
        const url = `/operations/it/issues/reports/export?period_type=${periodType}&start_date=${startDate || ''}&end_date=${endDate || ''}&resolver_type=${resolverType}&category_ids=${catStr}&status_codes=${stStr}`;
        window.open(url, '_blank');
    };


    // ── PDF Category Summary Export ──────────────────────────────────────
    const handleExportPDF = async () => {
        await exportCategorySummaryPDF({
            report,
            filters: {
                periodType,
                startDate,
                endDate,
                resolverType,
            },
            app_name,
        });
    };

    // ── PDF Detailed Issue List Export ──────────────────────────────────────
    const handleExportDetailedPDF = async () => {
        await exportDetailedIssuePDF({
            report,
            filters: {
                periodType,
                startDate,
                endDate,
                resolverType,
                selectedCategoryIds,
            },
            auth_user,
            app_name,
            categories,
            formatDateShort,
            formatDateCustom,
        });
    };


    const handleOpenManage = (item) => {
        setManageIssue(item);
        setManageModalTab(0);
        setManageForm({
            title: item.title || '',
            description: item.description || '',
            issue_category_id: item.issue_category_id || '',
            issue_priority_id: item.issue_priority_id || '',
            issue_importance_id: item.issue_importance_id || '',
            assigned_user_id: item.assigned_user_id || '',
            resolution_department_id: item.resolution_department_id || '',
            proposed_solution: item.proposed_solution || '',
            issue_by: item.issue_by || '',
            is_third_party_resolver: Boolean(item.is_third_party_resolver),
        });
    };

    const handleSaveManageModal = () => {
        if (!manageIssue) return;
        router.put(`/operations/it/issues/${manageIssue.id}`, manageForm, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setManageIssue(null),
        });
    };

    const handleTransitionStatus = (targetStatusCode) => {
        if (!manageIssue) return;
        const targetStatus = statuses.find((s) => s.code === targetStatusCode);
        if (!targetStatus) return;

        const currentCode = manageIssue.status?.code || manageIssue.status_code;
        const isCurrentlyClosedOrDone = currentCode === 'CLOSED' || currentCode === 'DONE';
        const isTargetClosedOrDone = targetStatus.code === 'CLOSED' || targetStatus.code === 'DONE';

        if (isTargetClosedOrDone) {
            setRootCauseModal({ open: true, issue: manageIssue, targetStatus });
            setRootCauseForm({ root_cause_id: '', remark: '' });
            return;
        }

        if (isCurrentlyClosedOrDone && !isTargetClosedOrDone) {
            setReopenModal({ open: true, issue: manageIssue, targetStatus });
            setReopenRemark('');
            return;
        }

        router.patch(
            `/operations/it/issues/${manageIssue.id}/status`,
            {
                issue_status_id: targetStatus.id,
                proposed_solution: manageForm.proposed_solution,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setManageIssue(null),
            }
        );
    };

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

    const summary = report.summary;

    return (
        <AsideLayout title="SLA & Service Credit Weekly/Monthly Reports">
            <Head title="SLA & Service Credit Reports" />

            <Box sx={{ p: 3 }}>
                {/* Header Banner */}
                <Card sx={{ mb: 3, background: 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%)', color: '#fff' }}>
                    <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
                        <Box>
                            <Typography variant="h5" fontWeight="bold" sx={{ color: '#a5b4fc' }}>
                                SLA & Service Credit Weekly / Monthly Report
                            </Typography>
                            <Typography variant="body2" sx={{ opacity: 0.8 }}>
                                {report.period_label} | Fail Point Weightage: P1 = 10 pts, P2 = 5 pts, P3/P4 = 1 pt
                            </Typography>
                        </Box>

                        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                            <Button
                                variant="contained"
                                color="primary"
                                startIcon={<AddIcon />}
                                onClick={() => setOpenCreateModal(true)}
                            >
                                Log New Issue
                            </Button>
                            <Button
                                variant="contained"
                                color="secondary"
                                startIcon={<FileDownloadIcon />}
                                onClick={handleExport}
                            >
                                Export Excel
                            </Button>
                            <Button
                                variant="contained"
                                startIcon={<PdfIcon />}
                                onClick={handleExportPDF}
                                sx={{
                                    background: 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)',
                                    color: '#fff',
                                    fontWeight: 700,
                                    '&:hover': { background: 'linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%)' },
                                }}
                            >
                                Category Summary PDF
                            </Button>
                            <Button
                                variant="contained"
                                startIcon={<PdfIcon />}
                                onClick={handleExportDetailedPDF}
                                sx={{
                                    background: 'linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%)',
                                    color: '#fff',
                                    fontWeight: 700,
                                    '&:hover': { background: 'linear-gradient(135deg, #6d28d9 0%, #3b0764 100%)' },
                                }}
                            >
                                Issue Detail PDF
                            </Button>
                        </Box>
                    </CardContent>
                </Card>

                {/* Filter Toolbar — auto-apply, no Apply button */}
                <Paper
                    sx={{
                        p: 2.5,
                        mb: 3,
                        borderRadius: 3,
                        border: '1px solid #e2e8f0',
                        background: 'linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%)',
                    }}
                >
                    <Grid container spacing={2} alignItems="center">

                        {/* Period Toggle */}
                        <Grid item xs={12} sm="auto">
                            <ToggleButtonGroup
                                value={periodType}
                                exclusive
                                onChange={handlePeriodChange}
                                size="small"
                                sx={{
                                    '& .MuiToggleButton-root': {
                                        fontWeight: 700,
                                        fontSize: '0.78rem',
                                        px: 2.5,
                                        borderRadius: '8px !important',
                                        border: '1.5px solid #c7d2fe !important',
                                        color: '#4f46e5',
                                        '&.Mui-selected': {
                                            backgroundColor: '#4f46e5',
                                            color: '#fff',
                                            '&:hover': { backgroundColor: '#4338ca' },
                                        },
                                    },
                                }}
                            >
                                <ToggleButton value="weekly">WEEKLY REPORT</ToggleButton>
                                <ToggleButton value="monthly">MONTHLY REPORT</ToggleButton>
                            </ToggleButtonGroup>
                        </Grid>

                        {/* Flatpickr Date Range */}
                        <Grid item xs={12} sm>
                            <Box sx={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                                <Typography
                                    variant="caption"
                                    fontWeight="700"
                                    sx={{
                                        position: 'absolute',
                                        top: -9,
                                        left: 12,
                                        backgroundColor: '#f8faff',
                                        px: 0.7,
                                        color: '#4f46e5',
                                        zIndex: 1,
                                        textTransform: 'uppercase',
                                        fontSize: '0.68rem',
                                        letterSpacing: '0.05em',
                                    }}
                                >
                                    Date Range
                                </Typography>
                                <input
                                    ref={dateRangeRef}
                                    readOnly
                                    placeholder="Select date range..."
                                    style={{
                                        width: '100%',
                                        border: '1.5px solid #c7d2fe',
                                        borderRadius: '10px',
                                        padding: '9px 14px',
                                        fontSize: '0.875rem',
                                        fontWeight: 600,
                                        color: '#1e293b',
                                        background: '#ffffff',
                                        cursor: 'pointer',
                                        outline: 'none',
                                        fontFamily: 'inherit',
                                        boxSizing: 'border-box',
                                    }}
                                />
                            </Box>
                        </Grid>

                        {/* Category Multi-Select Filter */}
                        <Grid item xs={12} sm={4} md={3}>
                            <FormControl fullWidth size="small" sx={{ minWidth: 200 }}>
                                <InputLabel
                                    sx={{
                                        fontWeight: 700,
                                        color: '#4f46e5',
                                        fontSize: '0.82rem',
                                        '&.Mui-focused': { color: '#4f46e5' },
                                    }}
                                >
                                    Filter by Category
                                </InputLabel>
                                <Select
                                    multiple
                                    value={selectedCategoryIds}
                                    onChange={handleCategoryChange}
                                    input={<OutlinedInput label="Filter by Category" />}
                                    renderValue={(selected) => {
                                        if (!selected.length) return <em style={{ color: '#94a3b8', fontStyle: 'normal' }}>All Categories</em>;
                                        return categories
                                            .filter(c => selected.includes(c.id))
                                            .map(c => c.name)
                                            .join(', ');
                                    }}
                                    sx={{
                                        borderRadius: 2.5,
                                        fontWeight: 600,
                                        fontSize: '0.85rem',
                                        '& .MuiOutlinedInput-notchedOutline': {
                                            borderColor: '#c7d2fe',
                                            borderWidth: '1.5px',
                                        },
                                        '&:hover .MuiOutlinedInput-notchedOutline': { borderColor: '#4f46e5' },
                                        '&.Mui-focused .MuiOutlinedInput-notchedOutline': { borderColor: '#4f46e5' },
                                    }}
                                >
                                    {categories.map((cat) => (
                                        <MenuItem key={cat.id} value={cat.id}>
                                            <Checkbox
                                                checked={selectedCategoryIds.includes(cat.id)}
                                                size="small"
                                                sx={{ p: 0.5, mr: 1, color: '#4f46e5', '&.Mui-checked': { color: '#4f46e5' } }}
                                            />
                                            {cat.name}
                                        </MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                        </Grid>

                        {/* Status Multi-Select Filter */}
                        <Grid item xs={12} sm={4} md={3}>
                            <FormControl fullWidth size="small" sx={{ minWidth: 180 }}>
                                <InputLabel
                                    sx={{
                                        fontWeight: 700,
                                        color: '#4f46e5',
                                        fontSize: '0.82rem',
                                        '&.Mui-focused': { color: '#4f46e5' },
                                    }}
                                >
                                    Filter by Status
                                </InputLabel>
                                <Select
                                    multiple
                                    value={selectedStatusCodes}
                                    onChange={handleStatusChange}
                                    input={<OutlinedInput label="Filter by Status" />}
                                    renderValue={(selected) => {
                                        if (!selected.length) return <em style={{ color: '#94a3b8', fontStyle: 'normal' }}>All Statuses</em>;
                                        return statuses
                                            .filter(s => selected.includes(s.code))
                                            .map(s => s.name || s.code)
                                            .join(', ');
                                    }}
                                    sx={{
                                        borderRadius: 2.5,
                                        fontWeight: 600,
                                        fontSize: '0.85rem',
                                        '& .MuiOutlinedInput-notchedOutline': {
                                            borderColor: '#c7d2fe',
                                            borderWidth: '1.5px',
                                        },
                                        '&:hover .MuiOutlinedInput-notchedOutline': { borderColor: '#4f46e5' },
                                        '&.Mui-focused .MuiOutlinedInput-notchedOutline': { borderColor: '#4f46e5' },
                                    }}
                                >
                                    {statuses.map((st) => (
                                        <MenuItem key={st.id || st.code} value={st.code}>
                                            <Checkbox
                                                checked={selectedStatusCodes.includes(st.code)}
                                                size="small"
                                                sx={{ p: 0.5, mr: 1, color: '#4f46e5', '&.Mui-checked': { color: '#4f46e5' } }}
                                            />
                                            {st.name || st.code}
                                        </MenuItem>
                                    ))}
                                </Select>
                            </FormControl>
                        </Grid>
                    </Grid>
                </Paper>

                {/* Summary Metric Cards */}
                <Grid container spacing={3} sx={{ mb: 3 }}>
                    <Grid item xs={12} sm={6} md={3}>
                        <Card sx={{ backgroundColor: '#eff6ff', borderLeft: '5px solid #3b82f6' }}>
                            <CardContent>
                                <Typography variant="overline" color="text.secondary">Total Issues</Typography>
                                <Typography variant="h4" fontWeight="bold" color="primary.main">{summary.total_issues}</Typography>
                                <Typography variant="caption" color="text.secondary">
                                    Passed: {summary.passed_issues || 0} | On Track: {summary.on_track_issues || 0} | Fail: {summary.failed_issues || 0} | Lack: {summary.lack_track_issues || 0}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={3}>
                        <Card sx={{ backgroundColor: '#fef2f2', borderLeft: '5px solid #ef4444' }}>
                            <CardContent>
                                <Typography variant="overline" color="error.main">Weekly Fail Points</Typography>
                                <Typography variant="h4" fontWeight="bold" color="error.main">{summary.total_fail_points} pts</Typography>
                                <Typography variant="caption" color="text.secondary">
                                    P1: {summary.p1_fail_count} (×10) | P2: {summary.p2_fail_count} (×5) | P3: {summary.p3_fail_count} (×1)
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={3}>
                        <Card sx={{ backgroundColor: '#f0fdf4', borderLeft: '5px solid #22c55e' }}>
                            <CardContent>
                                <Typography variant="overline" color="success.main">SLA Resolution Rate</Typography>
                                <Typography variant="h4" fontWeight="bold" color="success.main">{summary.resolution_rate}%</Typography>
                                <Typography variant="caption" color="text.secondary">
                                    Target Resolution Performance
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>

                    <Grid item xs={12} sm={6} md={3}>
                        <Card sx={{ backgroundColor: '#fdf4ff', borderLeft: '5px solid #a855f7' }}>
                            <CardContent>
                                <Typography variant="overline" color="secondary.main">Service Credit Refund</Typography>
                                <Typography variant="h4" fontWeight="bold" color="secondary.main">{summary.service_credit_pct}%</Typography>
                                <Typography variant="caption" color="text.secondary">
                                    {summary.service_credit_pct > 0 ? 'Maintenance Credit Refund Due' : 'No Credit Refund Due'}
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>

                {/* Report Table */}
                <Paper sx={{ borderRadius: 2, p: 2 }}>
                    {/* Table header row: title left, column toggle & resolver filter right */}
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 1.5, mb: 2 }}>
                        <Typography variant="h6" fontWeight="bold">
                            Issue Resolution & SLA Performance Detail ({report.items.length} Records)
                        </Typography>

                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                            {/* Table Column Toggle Button */}
                            <ToggleButton
                                value="list"
                                aria-label="list"
                                selected={Boolean(columnMenuAnchor)}
                                onClick={(e) => setColumnMenuAnchor(columnMenuAnchor ? null : e.currentTarget)}
                                size="small"
                                title="Show / Hide Table Columns"
                                sx={{
                                    borderRadius: 2,
                                    px: 1.2,
                                    py: 0.5,
                                    borderColor: '#cbd5e1',
                                    backgroundColor: columnMenuAnchor ? '#e0e7ff' : '#fff',
                                    color: '#4f46e5',
                                    '&.Mui-selected': {
                                        backgroundColor: '#4f46e5',
                                        color: '#fff',
                                        '&:hover': { backgroundColor: '#4338ca' }
                                    }
                                }}
                            >
                                <ViewListIcon fontSize="small" />
                            </ToggleButton>

                            {/* Resolver Filter Pills */}
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <Typography variant="caption" fontWeight="700" sx={{ color: '#64748b', textTransform: 'uppercase', fontSize: '0.67rem', letterSpacing: '0.05em' }}>
                                    Resolver:
                                </Typography>
                                <Box sx={{ display: 'flex', gap: 0.8 }}>
                                    {[
                                        { value: 'all', label: 'All', icon: <AllIcon sx={{ fontSize: 15 }} /> },
                                        { value: 'internal', label: 'Internal IT', icon: <BusinessIcon sx={{ fontSize: 15 }} /> },
                                        { value: 'third_party', label: '3rd-Party', icon: <PeopleIcon sx={{ fontSize: 15 }} /> },
                                    ].map(({ value, label, icon }) => (
                                        <Box
                                            key={value}
                                            onClick={() => handleResolverChange(value)}
                                            sx={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 0.5,
                                                px: 1.4,
                                                py: 0.55,
                                                borderRadius: '20px',
                                                border: resolverType === value ? '2px solid #0d9488' : '1.5px solid #cbd5e1',
                                                backgroundColor: resolverType === value ? '#0d9488' : '#f8fafc',
                                                color: resolverType === value ? '#ffffff' : '#475569',
                                                cursor: 'pointer',
                                                fontWeight: 700,
                                                fontSize: '0.73rem',
                                                fontFamily: 'inherit',
                                                userSelect: 'none',
                                                transition: 'all 0.18s ease',
                                                '&:hover': {
                                                    borderColor: '#0d9488',
                                                    color: resolverType === value ? '#ffffff' : '#0d9488',
                                                },
                                            }}
                                        >
                                            {icon}
                                            {label}
                                        </Box>
                                    ))}
                                </Box>
                            </Box>
                        </Box>
                    </Box>

                    {/* Column Visibility Selector Menu */}
                    <Popover
                        open={Boolean(columnMenuAnchor)}
                        anchorEl={columnMenuAnchor}
                        onClose={() => setColumnMenuAnchor(null)}
                        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
                        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
                        PaperProps={{
                            sx: {
                                p: 1.5,
                                width: 260,
                                borderRadius: 3,
                                boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.15)',
                                border: '1px solid #cbd5e1',
                            }
                        }}
                    >
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1, pb: 1, borderBottom: '1px solid #e2e8f0' }}>
                            <Typography variant="subtitle2" fontWeight="bold" color="text.primary">
                                Table Columns ({visibleColumns.length}/{ALL_COLUMNS.length})
                            </Typography>
                            <Button
                                size="small"
                                sx={{ fontSize: '0.7rem', textTransform: 'none' }}
                                onClick={() => {
                                    const allKeys = ALL_COLUMNS.map(c => c.key);
                                    setVisibleColumns(allKeys);
                                    try {
                                        localStorage.setItem('it_reports_visible_columns', JSON.stringify(allKeys));
                                    } catch (e) { }
                                }}
                            >
                                Reset All
                            </Button>
                        </Box>
                        <Box sx={{ maxHeight: 300, overflowY: 'auto' }}>
                            <MenuList disablePadding>
                                {ALL_COLUMNS.map((col) => {
                                    const checked = isColumnVisible(col.key);
                                    return (
                                        <MenuItem
                                            key={col.key}
                                            onClick={() => handleToggleColumn(col.key)}
                                            dense
                                            sx={{ borderRadius: 1.5, mb: 0.2 }}
                                        >
                                            <Checkbox
                                                checked={checked}
                                                size="small"
                                                sx={{ p: 0.5, mr: 1, color: '#4f46e5', '&.Mui-checked': { color: '#4f46e5' } }}
                                            />
                                            <Typography variant="body2" fontWeight={checked ? 600 : 400}>
                                                {col.label}
                                            </Typography>
                                        </MenuItem>
                                    );
                                })}
                            </MenuList>
                        </Box>
                    </Popover>

                    <TableContainer sx={{ overflowX: 'auto', width: '100%' }}>
                        <Table sx={{ minWidth: 1000 }}>
                            <TableHead sx={{ backgroundColor: '#f8fafc' }}>
                                <TableRow>
                                    {isColumnVisible('sequence') && <TableCell fontWeight="bold">Sequence</TableCell>}
                                    {isColumnVisible('title') && <TableCell fontWeight="bold" sx={{ minWidth: 280, minHeight: 52 }}>Title & Category</TableCell>}
                                    {isColumnVisible('priority') && <TableCell fontWeight="bold">Priority</TableCell>}
                                    {isColumnVisible('status') && <TableCell fontWeight="bold" sx={{ minWidth: 160 }}>Workflow Status</TableCell>}
                                    {isColumnVisible('resolver_type') && <TableCell fontWeight="bold" sx={{ minWidth: 160 }}>Is Resolver (3rd-Party)</TableCell>}
                                    {isColumnVisible('assigned_user') && <TableCell fontWeight="bold">Assigned To</TableCell>}
                                    {isColumnVisible('reported_date') && <TableCell fontWeight="bold" sx={{ whiteSpace: 'nowrap' }}>Reported Date</TableCell>}
                                    {isColumnVisible('due_date') && <TableCell fontWeight="bold" sx={{ whiteSpace: 'nowrap' }}>Due Date</TableCell>}
                                    {isColumnVisible('closed_date') && <TableCell fontWeight="bold" sx={{ whiteSpace: 'nowrap' }}>Closed Date</TableCell>}
                                    {isColumnVisible('sla_status') && <TableCell fontWeight="bold">SLA Status</TableCell>}
                                    {isColumnVisible('fail_points') && <TableCell fontWeight="bold">Fail Points</TableCell>}
                                    {isColumnVisible('actions') && <TableCell fontWeight="bold" align="center">Actions (Edit / Manage)</TableCell>}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {report.items.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={visibleColumns.length} align="center" sx={{ py: 4 }}>
                                            No issues recorded in this period.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    paginatedItems.map((item, idx) => (
                                        <TableRow key={item.id} hover sx={{ minHeight: 52, '& td': { py: 1.8 } }}>
                                            {isColumnVisible('sequence') && (
                                                <TableCell fontWeight="bold" sx={{ minHeight: 52 }}>
                                                    <Chip label={`#${item.resolution_sequence || (page - 1) * pageSize + idx + 1}`} color="primary" size="small" />
                                                </TableCell>
                                            )}
                                            {isColumnVisible('title') && (
                                                <TableCell
                                                    onClick={() => handleOpenManage(item)}
                                                    sx={{
                                                        minWidth: 280,
                                                        minHeight: 52,
                                                        whiteSpace: 'normal !important',
                                                        cursor: 'pointer',
                                                        transition: 'color 0.15s ease-in-out',
                                                        '&:hover': { color: 'primary.main' },
                                                    }}
                                                >
                                                    <Typography variant="body2" fontWeight="bold" sx={{ lineHeight: 1.4 }}>{item.title}</Typography>
                                                    <Typography variant="caption" color="text.secondary">{item.category_name} ({item.resolver_label})</Typography>
                                                </TableCell>
                                            )}
                                            {isColumnVisible('priority') && (
                                                <TableCell>
                                                    <Chip label={`${item.priority_code}`} size="small" color={item.priority_code === 'P1' ? 'error' : item.priority_code === 'P2' ? 'warning' : 'info'} />
                                                </TableCell>
                                            )}
                                            {isColumnVisible('status') && (
                                                <TableCell onClick={(e) => e.stopPropagation()}>
                                                    {(() => {
                                                        const cfg = getStatusConfig(item.status_code);
                                                        return (
                                                            <Button
                                                                variant="contained"
                                                                size="small"
                                                                startIcon={cfg.icon}
                                                                onClick={(e) => handleOpenStatusPopover(e, item)}
                                                                sx={{
                                                                    backgroundColor: cfg.bg,
                                                                    color: '#ffffff',
                                                                    fontWeight: 700,
                                                                    fontSize: '0.72rem',
                                                                    textTransform: 'uppercase',
                                                                    px: 1.6,
                                                                    py: 0.5,
                                                                    borderRadius: 2,
                                                                    boxShadow: '0 2px 5px rgba(0,0,0,0.12)',
                                                                    whiteSpace: 'nowrap',
                                                                    '&:hover': {
                                                                        backgroundColor: cfg.hoverBg,
                                                                        boxShadow: '0 4px 8px rgba(0,0,0,0.2)',
                                                                    },
                                                                }}
                                                            >
                                                                {item.status_name || cfg.label}
                                                            </Button>
                                                        );
                                                    })()}
                                                </TableCell>
                                            )}
                                            {isColumnVisible('resolver_type') && (
                                                <TableCell onClick={(e) => e.stopPropagation()}>
                                                    <FormControlLabel
                                                        control={
                                                            <MaterialUISwitch
                                                                checked={Boolean(item.is_third_party_resolver)}
                                                                onChange={() => handleToggleResolver(item)}
                                                            />
                                                        }
                                                        label={
                                                            <Typography variant="caption" fontWeight="bold" color={item.is_third_party_resolver ? 'secondary.main' : 'text.secondary'}>
                                                                {item.is_third_party_resolver ? '3rd-Party' : 'Internal IT'}
                                                            </Typography>
                                                        }
                                                    />
                                                </TableCell>
                                            )}
                                            {isColumnVisible('assigned_user') && <TableCell>{item.assigned_user_name}</TableCell>}
                                            {isColumnVisible('reported_date') && (
                                                <TableCell
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleOpenEditDateModal(item);
                                                    }}
                                                    sx={{
                                                        whiteSpace: 'nowrap',
                                                        cursor: 'pointer',
                                                        color: '#2563eb',
                                                        fontWeight: 600,
                                                        transition: 'all 0.15s ease',
                                                        '&:hover': {
                                                            color: '#1d4ed8',
                                                            backgroundColor: '#eff6ff',
                                                            borderRadius: 1,
                                                        },
                                                    }}
                                                    title="Click to edit Reported Date & auto-recalculate Due Date"
                                                >
                                                    <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.8 }}>
                                                        <span>{formatDateCustom(item.issue_at)}</span>
                                                        <EditIcon sx={{ fontSize: 14, opacity: 0.6 }} />
                                                    </Box>
                                                </TableCell>
                                            )}
                                            {isColumnVisible('due_date') && (
                                                <TableCell
                                                    onClick={(e) => {
                                                        if (item.priority_is_manual_schedule || item.priority_clock_type === 'manual_schedule') {
                                                            e.stopPropagation();
                                                            handleOpenEditDueDateModal(item);
                                                        }
                                                    }}
                                                    sx={
                                                        (item.priority_is_manual_schedule || item.priority_clock_type === 'manual_schedule')
                                                            ? {
                                                                whiteSpace: 'nowrap',
                                                                cursor: 'pointer',
                                                                color: '#7c3aed',
                                                                fontWeight: 600,
                                                                transition: 'all 0.15s ease',
                                                                '&:hover': {
                                                                    backgroundColor: '#f5f3ff',
                                                                    borderRadius: 1,
                                                                },
                                                            }
                                                            : { whiteSpace: 'nowrap' }
                                                    }
                                                    title={
                                                        (item.priority_is_manual_schedule || item.priority_clock_type === 'manual_schedule')
                                                            ? 'Manual Schedule Priority: Click to set Due Date & add log remark'
                                                            : 'SLA Target Due Date'
                                                    }
                                                >
                                                    {(item.priority_is_manual_schedule || item.priority_clock_type === 'manual_schedule') ? (
                                                        <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.8 }}>
                                                            <Chip
                                                                label={item.due_date ? formatDateCustom(item.due_date) : 'Set Due Date'}
                                                                size="small"
                                                                color={item.due_date ? 'secondary' : 'warning'}
                                                                variant={item.due_date ? 'outlined' : 'filled'}
                                                                icon={<EditIcon sx={{ fontSize: 13 }} />}
                                                                sx={{ fontWeight: 700, cursor: 'pointer' }}
                                                            />
                                                        </Box>
                                                    ) : (
                                                        formatDateCustom(item.due_date)
                                                    )}
                                                </TableCell>
                                            )}
                                            {isColumnVisible('closed_date') && <TableCell sx={{ whiteSpace: 'nowrap' }}>{formatDateCustom(item.closed_date)}</TableCell>}
                                            {isColumnVisible('sla_status') && (
                                                <TableCell>
                                                    {item.sla_status_code === 'LACK_TRACK' ? (
                                                        <Chip label="LACK TRACK" size="small" sx={{ bgcolor: '#cbd5e1', color: '#334155', fontWeight: 'bold' }} />
                                                    ) : item.sla_status_code === 'PASSED' ? (
                                                        <Chip label="PASSED" color="success" size="small" icon={<CheckCircleIcon />} sx={{ fontWeight: 'bold' }} />
                                                    ) : item.sla_status_code === 'ON_TRACK' ? (
                                                        <Chip label="ON TRACK" color="info" size="small" variant="outlined" icon={<CheckCircleIcon />} sx={{ fontWeight: 'bold' }} />
                                                    ) : (
                                                        <Chip label="FAIL" color="error" size="small" variant="outlined" icon={<CancelIcon />} sx={{ fontWeight: 'bold' }} />
                                                    )}
                                                </TableCell>
                                            )}
                                            {isColumnVisible('fail_points') && (
                                                <TableCell fontWeight="bold" color={item.fail_points > 0 ? 'error.main' : 'text.primary'}>
                                                    {item.fail_points} pts
                                                </TableCell>
                                            )}
                                            {isColumnVisible('actions') && (
                                                <TableCell align="center">
                                                    <Box sx={{ display: 'flex', gap: 1, justifyContent: 'center', alignItems: 'center' }}>
                                                        <IconButton
                                                            size="small"
                                                            color="info"
                                                            title="Discussion Messages & Log Notes"
                                                            onClick={() => {
                                                                setDiscussionIssue(item);
                                                                setDiscussionMessage('');
                                                                setDiscussionIsLogNote(false);
                                                            }}
                                                        >
                                                            <Badge badgeContent={item.messages_count ?? item.messages?.length ?? 0} color="primary" max={99}>
                                                                <ChatIcon fontSize="small" />
                                                            </Badge>
                                                        </IconButton>

                                                        <IconButton
                                                            size="small"
                                                            color="primary"
                                                            title="Manage / Edit Issue"
                                                            onClick={() => handleOpenManage(item)}
                                                        >
                                                            <EditIcon fontSize="small" />
                                                        </IconButton>

                                                        {item.is_sla_failed && (
                                                            <Button
                                                                size="small"
                                                                variant="outlined"
                                                                color="success"
                                                                onClick={() => {
                                                                    setSelectedIssueForOverride(item);
                                                                    setAdminRemark('');
                                                                }}
                                                            >
                                                                Override
                                                            </Button>
                                                        )}
                                                    </Box>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>

                    {/* MUI Pagination Controls (6 items per page) */}
                    {totalPages > 1 && (
                        <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', pt: 3, pb: 1 }}>
                            <Pagination
                                count={totalPages}
                                page={page}
                                onChange={(event, value) => setPage(value)}
                                color="primary"
                                shape="rounded"
                                showFirstButton
                                showLastButton
                            />
                        </Box>
                    )}
                </Paper>

                {/* Create Issue Modal — shared global component */}
                <CreateIssueModal
                    open={openCreateModal}
                    onClose={() => setOpenCreateModal(false)}
                    categories={categories}
                    priorities={priorities}
                    departments={departments}
                    importanceLevels={importanceLevels}
                    users={users}
                    auth={{ user: auth_user }}
                />

                {/* Manage Issue Modal in Reports View */}

                <Dialog open={Boolean(manageIssue)} onClose={() => setManageIssue(null)} maxWidth="md" fullWidth>
                    <DialogTitle sx={{ backgroundColor: '#0f172a', color: '#fff', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Typography variant="h6" fontWeight="bold">
                            Edit / Manage Issue #{manageIssue?.id}: {manageIssue?.title}
                        </Typography>
                        <Chip
                            label={manageIssue?.status_name || 'OPEN'}
                            color={manageIssue?.status_code === 'CLOSED' ? 'success' : 'info'}
                            size="small"
                        />
                    </DialogTitle>
                    <DialogContent dividers sx={{ p: 3 }}>
                        {manageIssue && (
                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
                                {/* Status Workflow Progress Stepper (Read-only progress display) */}
                                <Paper sx={{ p: 2, backgroundColor: '#f8fafc', borderRadius: 3, border: '1px solid #e2e8f0' }}>
                                    <Typography variant="subtitle2" fontWeight="bold" sx={{ mb: 0.5 }}>
                                        Issue Resolution Progress
                                    </Typography>
                                    <StatusStepper
                                        statuses={statuses}
                                        currentStatusCode={manageIssue.status_code}
                                        interactive={false}
                                    />
                                </Paper>

                                <Tabs value={manageModalTab} onChange={(e, val) => setManageModalTab(val)}>
                                    <Tab label="Edit Fields" />
                                    <Tab label="Discussion Notes & Messages" />
                                </Tabs>

                                {manageModalTab === 0 && (
                                    // <Grid container spacing={2.5} sx={{ pt: 1 }}>
                                    //     {/* Row 1: Title (3/4 = 9/12) & Category (1/4 = 3/12) on laptop screen; full row each on smaller screen */}
                                    //     <Grid item xs={12} md={9}>
                                    //         <TextField
                                    //             label="Title"
                                    //             required
                                    //             fullWidth
                                    //             value={manageForm.title}
                                    //             onChange={(e) => setManageForm({ ...manageForm, title: e.target.value })}
                                    //         />
                                    //     </Grid>
                                    //     <Grid item xs={12} md={3}>
                                    //         <FormControl fullWidth required>
                                    //             <InputLabel>Category</InputLabel>
                                    //             <Select
                                    //                 value={manageForm.issue_category_id}
                                    //                 label="Category"
                                    //                 onChange={(e) => setManageForm({ ...manageForm, issue_category_id: e.target.value })}
                                    //             >
                                    //                 {categories.map((c) => (
                                    //                     <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>
                                    //                 ))}
                                    //             </Select>
                                    //         </FormControl>
                                    //     </Grid>

                                    //     {/* Row 2: Flex row with Priority (28%), Assign Person (28%), and Resolver Type toggle button */}
                                    //     <Grid item xs={12}>
                                    //         <Box sx={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 2 }}>
                                    //             {/* Priority Selection (28% width on desktop) */}
                                    //             <FormControl sx={{ flex: { xs: '1 1 100%', sm: '0 0 28%' }, minWidth: 160 }}>
                                    //                 <InputLabel>Priority (P-Level)</InputLabel>
                                    //                 <Select
                                    //                     value={manageForm.issue_priority_id}
                                    //                     label="Priority (P-Level)"
                                    //                     onChange={(e) => setManageForm({ ...manageForm, issue_priority_id: e.target.value })}
                                    //                 >
                                    //                     {priorities.map((p) => (
                                    //                         <MenuItem key={p.id} value={p.id}>{p.code} - {p.name}</MenuItem>
                                    //                     ))}
                                    //                 </Select>
                                    //             </FormControl>

                                    //             {/* Assign Person (28% width on desktop) */}
                                    //             <FormControl sx={{ flex: { xs: '1 1 100%', sm: '0 0 28%' }, minWidth: 160 }}>
                                    //                 <InputLabel>Assigned Person</InputLabel>
                                    //                 <Select
                                    //                     value={manageForm.assigned_user_id}
                                    //                     label="Assigned Person"
                                    //                     onChange={(e) => setManageForm({ ...manageForm, assigned_user_id: e.target.value })}
                                    //                 >
                                    //                     <MenuItem value=""><em>Unassigned</em></MenuItem>
                                    //                     {users.map((u) => (
                                    //                         <MenuItem key={u.id} value={u.id}>{u.name}</MenuItem>
                                    //                     ))}
                                    //                 </Select>
                                    //             </FormControl>

                                    //             {/* Resolver Type Button with Toggle */}
                                    //             <Box
                                    //                 sx={{
                                    //                     display: 'flex',
                                    //                     alignItems: 'center',
                                    //                     gap: 1.5,
                                    //                     px: 2,
                                    //                     py: 0.8,
                                    //                     borderRadius: 2.5,
                                    //                     backgroundColor: '#f8fafc',
                                    //                     border: '1.5px solid #cbd5e1',
                                    //                     flex: { xs: '1 1 100%', sm: '1 1 auto' },
                                    //                     justifyContent: 'space-between',
                                    //                 }}
                                    //             >
                                    //                 <Typography variant="body2" fontWeight="bold" color="text.secondary">
                                    //                     Resolver Type:
                                    //                 </Typography>
                                    //                 <FormControlLabel
                                    //                     control={
                                    //                         <MaterialUISwitch
                                    //                             checked={Boolean(manageForm.is_third_party_resolver)}
                                    //                             onChange={(e) => setManageForm({ ...manageForm, is_third_party_resolver: e.target.checked })}
                                    //                         />
                                    //                     }
                                    //                     label={
                                    //                         <Typography variant="body2" fontWeight="bold" color={manageForm.is_third_party_resolver ? 'secondary.main' : 'text.primary'} sx={{ ml: 0.5 }}>
                                    //                             {manageForm.is_third_party_resolver ? '3rd-Party Dev' : 'Internal IT'}
                                    //                         </Typography>
                                    //                     }
                                    //                     sx={{ m: 0 }}
                                    //                 />
                                    //             </Box>
                                    //         </Box>
                                    //     </Grid>

                                    //     {/* Row 3: Description - 1 row full width text area style */}
                                    //     <Grid item xs={12}>
                                    //         <TextField
                                    //             label="Description"
                                    //             required
                                    //             multiline
                                    //             rows={4}
                                    //             fullWidth
                                    //             value={manageForm.description}
                                    //             onChange={(e) => setManageForm({ ...manageForm, description: e.target.value })}
                                    //             placeholder="Enter detailed description of the issue..."
                                    //         />
                                    //     </Grid>

                                    //     {/* Row 4: Proposed Solution - 1 row full width text area style */}
                                    //     <Grid item xs={12}>
                                    //         <TextField
                                    //             label="Proposed Solution"
                                    //             multiline
                                    //             rows={3}
                                    //             fullWidth
                                    //             value={manageForm.proposed_solution}
                                    //             onChange={(e) => setManageForm({ ...manageForm, proposed_solution: e.target.value })}
                                    //             placeholder="Enter proposed solution or resolution steps..."
                                    //         />
                                    //     </Grid>
                                    // </Grid>
                                    <Box
                                        sx={{
                                            display: 'flex',
                                            flexDirection: { xs: 'column', md: 'row' }, // Stack on mobile, side-by-side on desktop
                                            width: '100%',
                                            gap: 2
                                        }}
                                    >
                                        {/* LEFT COLUMN: Main Text Areas (Description & Proposed Solution) */}
                                        <Box sx={{ flex: { xs: '1 1 100%', md: '0 0 65%' }, width: '100%' }}>
                                            <Paper
                                                variant="outlined"
                                                sx={{
                                                    p: 2.5,
                                                    borderRadius: 2,
                                                    backgroundColor: '#ffffff',
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: 2.5,
                                                }}
                                            >
                                                {/* Description Section */}
                                                <Box>
                                                    <Typography
                                                        variant="caption"
                                                        fontWeight="bold"
                                                        sx={{
                                                            display: 'inline-block',
                                                            px: 1,
                                                            py: 0.3,
                                                            mb: 1,
                                                            borderRadius: 1,
                                                            backgroundColor: 'grey.200',
                                                            color: 'text.secondary',
                                                        }}
                                                    >
                                                        Description *
                                                    </Typography>
                                                    <TextField
                                                        multiline
                                                        rows={5}
                                                        fullWidth
                                                        variant="outlined"
                                                        value={manageForm.description}
                                                        onChange={(e) =>
                                                            setManageForm({ ...manageForm, description: e.target.value })
                                                        }
                                                        placeholder="Configure new Honeywell Bluetooth barcode scanner for POS counter 2..."
                                                    />
                                                </Box>

                                                <Divider />

                                                {/* Proposed Solution Section */}
                                                <Box>
                                                    <Typography
                                                        variant="caption"
                                                        fontWeight="bold"
                                                        sx={{
                                                            display: 'inline-block',
                                                            px: 1,
                                                            py: 0.3,
                                                            mb: 1,
                                                            borderRadius: 1,
                                                            backgroundColor: 'grey.200',
                                                            color: 'text.secondary',
                                                        }}
                                                    >
                                                        Proposed Solution
                                                    </Typography>
                                                    <TextField
                                                        multiline
                                                        rows={4}
                                                        fullWidth
                                                        variant="outlined"
                                                        value={manageForm.proposed_solution}
                                                        onChange={(e) =>
                                                            setManageForm({ ...manageForm, proposed_solution: e.target.value })
                                                        }
                                                        placeholder="Enter proposed solution or resolution steps..."
                                                    />
                                                </Box>
                                            </Paper>
                                        </Box>

                                        {/* RIGHT COLUMN: Sidebar Metadata Cards */}
                                        <Box sx={{ flex: { xs: '1 1 100%', md: '0 0 35%' }, width: '100%' }}>
                                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                                                {/* Title Input Card */}
                                                <Paper
                                                    variant="outlined"
                                                    sx={{
                                                        p: 1.5,
                                                        borderRadius: 2,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 1.5,
                                                    }}
                                                >
                                                    <TitleIcon color="action" />
                                                    <TextField
                                                        label="Title"
                                                        required
                                                        fullWidth
                                                        size="small"
                                                        variant="standard"
                                                        value={manageForm.title}
                                                        onChange={(e) =>
                                                            setManageForm({ ...manageForm, title: e.target.value })
                                                        }
                                                    />
                                                </Paper>

                                                {/* Category Card */}
                                                <Paper
                                                    variant="outlined"
                                                    sx={{
                                                        p: 1.5,
                                                        borderRadius: 2,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 1.5,
                                                    }}
                                                >
                                                    <FolderOutlinedIcon color="action" />
                                                    <FormControl fullWidth required size="small" variant="standard">
                                                        <InputLabel>Category</InputLabel>
                                                        <Select
                                                            value={manageForm.issue_category_id}
                                                            label="Category"
                                                            onChange={(e) =>
                                                                setManageForm({
                                                                    ...manageForm,
                                                                    issue_category_id: e.target.value,
                                                                })
                                                            }
                                                        >
                                                            {categories.map((c) => (
                                                                <MenuItem key={c.id} value={c.id}>
                                                                    {c.name}
                                                                </MenuItem>
                                                            ))}
                                                        </Select>
                                                    </FormControl>
                                                </Paper>

                                                {/* Priority Card */}
                                                <Paper
                                                    variant="outlined"
                                                    sx={{
                                                        p: 1.5,
                                                        borderRadius: 2,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 1.5,
                                                    }}
                                                >
                                                    <LowPriorityIcon color="action" />
                                                    <FormControl fullWidth size="small" variant="standard">
                                                        <InputLabel>Priority</InputLabel>
                                                        <Select
                                                            value={manageForm.issue_priority_id}
                                                            label="Priority"
                                                            onChange={(e) =>
                                                                setManageForm({
                                                                    ...manageForm,
                                                                    issue_priority_id: e.target.value,
                                                                })
                                                            }
                                                        >
                                                            {priorities.map((p) => (
                                                                <MenuItem key={p.id} value={p.id}>
                                                                    {p.code} - {p.name}
                                                                </MenuItem>
                                                            ))}
                                                        </Select>
                                                    </FormControl>
                                                </Paper>

                                                {/* Assigned Person Card */}
                                                <Paper
                                                    variant="outlined"
                                                    sx={{
                                                        p: 1.5,
                                                        borderRadius: 2,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 1.5,
                                                    }}
                                                >
                                                    <PersonOutlineIcon color="action" />
                                                    <FormControl fullWidth size="small" variant="standard">
                                                        <InputLabel>Assigned To</InputLabel>
                                                        <Select
                                                            value={manageForm.assigned_user_id}
                                                            label="Assigned To"
                                                            onChange={(e) =>
                                                                setManageForm({
                                                                    ...manageForm,
                                                                    assigned_user_id: e.target.value,
                                                                })
                                                            }
                                                        >
                                                            <MenuItem value="">
                                                                <em>Unassigned</em>
                                                            </MenuItem>
                                                            {users.map((u) => (
                                                                <MenuItem key={u.id} value={u.id}>
                                                                    {u.name}
                                                                </MenuItem>
                                                            ))}
                                                        </Select>
                                                    </FormControl>
                                                </Paper>

                                                {/* Resolver Type Toggle Card */}
                                                <Paper
                                                    variant="outlined"
                                                    sx={{
                                                        p: 1.5,
                                                        borderRadius: 2,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'space-between',
                                                        backgroundColor: '#eef6ff',
                                                        borderColor: '#b6d4fe',
                                                    }}
                                                >
                                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                                        <AccessTimeIcon color="primary" />
                                                        <Box>
                                                            <Typography variant="caption" color="text.secondary" display="block">
                                                                Resolver Type
                                                            </Typography>
                                                            <Typography variant="body2" fontWeight="bold" color="primary.main">
                                                                {manageForm.is_third_party_resolver ? '3rd-Party Dev' : 'Internal IT'}
                                                            </Typography>
                                                        </Box>
                                                    </Box>
                                                    <Switch
                                                        checked={Boolean(manageForm.is_third_party_resolver)}
                                                        onChange={(e) =>
                                                            setManageForm({
                                                                ...manageForm,
                                                                is_third_party_resolver: e.target.checked,
                                                            })
                                                        }
                                                        color="primary"
                                                    />
                                                </Paper>
                                            </Box>
                                        </Box>
                                    </Box>
                                )}

                                {manageModalTab === 1 && (
                                    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                        <List sx={{ maxHeight: 250, overflow: 'auto', backgroundColor: '#f8fafc', borderRadius: 2, p: 2 }}>
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
                            </Box>
                        )}
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setManageIssue(null)}>Cancel</Button>
                        <Button variant="contained" color="primary" onClick={handleSaveManageModal}>
                            Save Changes
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

                {/* Status Stepper Popper Div (MUI Popper floating div) */}
                <Popper
                    open={Boolean(popoverAnchor && popoverIssue)}
                    anchorEl={popoverAnchor}
                    placement="bottom"
                    modifiers={[
                        {
                            name: 'offset',
                            options: {
                                offset: [0, 8],
                            },
                        },
                    ]}
                    style={{ zIndex: 1300 }}
                >
                    <ClickAwayListener onClickAway={handleCloseStatusPopover}>
                        <Paper
                            elevation={8}
                            sx={{
                                p: 2.5,
                                minWidth: 640,
                                maxWidth: '95vw',
                                borderRadius: 3,
                                border: '1.5px solid #cbd5e1',
                                backgroundColor: '#ffffff',
                                boxShadow: '0 12px 35px -5px rgba(15, 23, 42, 0.25)',
                                overflow: 'visible',
                            }}
                        >
                            {popoverIssue && (
                                <Box>
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                                        <Typography variant="subtitle2" fontWeight="bold">
                                            Update Issue Status #{popoverIssue.id}: {popoverIssue.title}
                                        </Typography>
                                        <Chip label={popoverIssue.status_name} color="primary" size="small" sx={{ fontWeight: 'bold' }} />
                                    </Box>
                                    <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 1.5 }}>
                                        Click any step on the progress stepper to transition status:
                                    </Typography>
                                    <StatusStepper
                                        statuses={statuses}
                                        currentStatusCode={popoverIssue.status_code}
                                        onSelectStatus={(targetStatus) => handleStatusStepClick(popoverIssue, targetStatus)}
                                    />
                                </Box>
                            )}
                        </Paper>
                    </ClickAwayListener>
                </Popper>

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

                {/* Dedicated Discussion Messages & System Log Modal */}
                <Dialog
                    open={Boolean(discussionIssue)}
                    onClose={() => setDiscussionIssue(null)}
                    maxWidth="sm"
                    fullWidth
                >
                    <DialogTitle sx={{ backgroundColor: '#0f172a', color: '#fff', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Typography variant="h6" fontWeight="bold">
                            Issue #{discussionIssue?.id} Discussion & System Logs
                        </Typography>
                        <Chip label={`${discussionIssue?.messages_count ?? discussionIssue?.messages?.length ?? 0} Messages`} color="primary" size="small" />
                    </DialogTitle>
                    <DialogContent dividers sx={{ p: 3 }}>
                        {discussionIssue && (
                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                <Typography variant="subtitle2" fontWeight="bold" color="text.secondary">
                                    Issue Title: {discussionIssue.title}
                                </Typography>

                                <List sx={{ maxHeight: 320, overflow: 'auto', backgroundColor: '#f8fafc', borderRadius: 2, p: 2 }}>
                                    {discussionIssue.messages && discussionIssue.messages.length > 0 ? (
                                        discussionIssue.messages.map((m) => (
                                            <Box
                                                key={m.id}
                                                sx={{
                                                    p: 1.5,
                                                    mb: 1.2,
                                                    borderRadius: 2,
                                                    backgroundColor: m.is_log_note ? '#fef9c3' : '#ffffff',
                                                    borderLeft: m.is_log_note ? '4px solid #eab308' : '4px solid #3b82f6',
                                                    boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
                                                }}
                                            >
                                                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 0.5 }}>
                                                    <Typography variant="subtitle2" fontWeight="bold" color={m.is_log_note ? 'warning.dark' : 'primary.dark'}>
                                                        {m.creator?.name || 'System'}
                                                    </Typography>
                                                    <Chip
                                                        label={m.is_log_note ? 'LOG NOTE' : 'MESSAGE'}
                                                        size="small"
                                                        color={m.is_log_note ? 'warning' : 'default'}
                                                        sx={{ height: 18, fontSize: '0.65rem' }}
                                                    />
                                                </Box>
                                                <Typography variant="body2" sx={{ color: '#334155', whiteSpace: 'pre-line' }}>{m.message}</Typography>
                                                <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 0.5, textAlign: 'right' }}>
                                                    {new Date(m.created_at).toLocaleString()}
                                                </Typography>
                                            </Box>
                                        ))
                                    ) : (
                                        <Typography variant="body2" color="text.secondary" align="center" sx={{ py: 3 }}>
                                            No discussion messages or system log notes recorded yet.
                                        </Typography>
                                    )}
                                </List>

                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5, pt: 1 }}>
                                    <TextField
                                        label="Add Discussion Message or System Log Note"
                                        multiline
                                        rows={3}
                                        fullWidth
                                        placeholder="Type your message or system log note here..."
                                        value={discussionMessage}
                                        onChange={(e) => setDiscussionMessage(e.target.value)}
                                    />
                                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <FormControlLabel
                                            control={
                                                <Checkbox
                                                    checked={discussionIsLogNote}
                                                    onChange={(e) => setDiscussionIsLogNote(e.target.checked)}
                                                />
                                            }
                                            label="Flag as Yellow System Log Note"
                                        />
                                        <Button
                                            variant="contained"
                                            color="primary"
                                            endIcon={<SendIcon />}
                                            onClick={handleSaveDiscussionMessage}
                                            disabled={!discussionMessage.trim()}
                                        >
                                            Add Message
                                        </Button>
                                    </Box>
                                </Box>
                            </Box>
                        )}
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setDiscussionIssue(null)}>Close</Button>
                    </DialogActions>
                </Dialog>

                {/* Edit Reported Date Modal */}
                <Dialog
                    open={Boolean(editDateIssue)}
                    onClose={() => setEditDateIssue(null)}
                    maxWidth="xs"
                    fullWidth
                >
                    <DialogTitle sx={{ backgroundColor: '#0f172a', color: '#fff', fontWeight: 'bold' }}>
                        Edit Reported Date (Issue #{editDateIssue?.id})
                    </DialogTitle>
                    <DialogContent sx={{ p: 3, pt: 3 }}>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Updating the reported date for <strong>{editDateIssue?.title}</strong> will automatically recalculate the SLA due date based on its priority schedule.
                        </Typography>
                        <Stack spacing={2.5} sx={{ mt: 1 }}>
                            <TextField
                                label="New Reported Date & Time"
                                type="datetime-local"
                                fullWidth
                                size="small"
                                value={editDateValue}
                                onChange={(e) => setEditDateValue(e.target.value)}
                                InputLabelProps={{ shrink: true }}
                            />
                            <TextField
                                label="Remark / Reason for Change"
                                required
                                multiline
                                rows={3}
                                fullWidth
                                size="small"
                                value={editDateRemark}
                                onChange={(e) => setEditDateRemark(e.target.value)}
                                placeholder="Explain why the reported date is being modified (e.g. client report delay, timezone correction)..."
                            />
                        </Stack>
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setEditDateIssue(null)} color="inherit">
                            Cancel
                        </Button>
                        <Button
                            onClick={handleSaveReportedDate}
                            variant="contained"
                            color="primary"
                            disabled={!editDateValue || !editDateRemark.trim()}
                        >
                            Save Date & Recalculate SLA
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* Set Schedule Due Date Modal */}
                <Dialog
                    open={Boolean(editDueDateIssue)}
                    onClose={() => setEditDueDateIssue(null)}
                    maxWidth="xs"
                    fullWidth
                >
                    <DialogTitle sx={{ backgroundColor: '#4c1d95', color: '#fff', fontWeight: 'bold' }}>
                        Set Manual Schedule Due Date (Issue #{editDueDateIssue?.id})
                    </DialogTitle>
                    <DialogContent sx={{ p: 3, pt: 3 }}>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Priority for <strong>{editDueDateIssue?.title}</strong> is set to a manual schedule type. Set the manual target due date and enter a log note remark.
                        </Typography>
                        <Stack spacing={2.5} sx={{ mt: 1 }}>
                            <TextField
                                label="Target Due Date & Time"
                                type="datetime-local"
                                fullWidth
                                size="small"
                                value={editDueDateValue}
                                onChange={(e) => setEditDueDateValue(e.target.value)}
                                InputLabelProps={{ shrink: true }}
                            />
                            <TextField
                                label="Remark / Log Note Reason"
                                required
                                multiline
                                rows={3}
                                fullWidth
                                size="small"
                                value={editDueDateRemark}
                                onChange={(e) => setEditDueDateRemark(e.target.value)}
                                placeholder="Enter reason for setting/changing manual schedule due date..."
                            />
                        </Stack>
                    </DialogContent>
                    <DialogActions sx={{ p: 2, backgroundColor: '#f8fafc' }}>
                        <Button onClick={() => setEditDueDateIssue(null)} color="inherit">
                            Cancel
                        </Button>
                        <Button
                            onClick={handleSaveDueDate}
                            variant="contained"
                            sx={{ backgroundColor: '#7c3aed', color: '#fff', '&:hover': { backgroundColor: '#6d28d9' } }}
                            disabled={!editDueDateValue || !editDueDateRemark.trim()}
                        >
                            Save Due Date & Log Note
                        </Button>
                    </DialogActions>
                </Dialog>
            </Box>
        </AsideLayout>
    );
}
