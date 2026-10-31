import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import UserSelectModal from '@/components/IT/UserSelectModal';
import EmailSelectModal from '@/components/IT/EmailSelectModal';
import {
    Box,
    Card,
    CardContent,
    Typography,
    Grid,
    Button,
    Chip,
    Tabs,
    Tab,
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
    DialogActions,
    Divider,
    IconButton,
    Tooltip,
    Alert,
    Checkbox,
    FormControlLabel,
    Popover,
    InputAdornment,
    Switch,
    Autocomplete,
    CircularProgress
} from '@mui/material';
import {
    ArrowBack as ArrowBackIcon,
    Security as SecurityIcon,
    Person as PersonIcon,
    Description as EvidenceIcon,
    RateReview as ReviewIcon,
    Devices as DevicesIcon,
    History as HistoryIcon,
    Add as AddIcon,
    Download as DownloadIcon,
    CheckCircle as CheckCircleIcon,
    Cancel as CancelIcon,
    ViewColumn as ViewColumnIcon,
    Email as EmailIcon,
    Clear as ClearIcon,
    Search as SearchIcon,
    Edit as EditIcon
} from '@mui/icons-material';

export default function AccountShow({
    account,
    definitions = [],
    availableUsers = [],
    departments = [],
    availableEmails = [],
    availableDevices = [],
    officeAssets = []
}) {
    const [currentTab, setCurrentTab] = useState(0);
    const [showInactivePics, setShowInactivePics] = useState(false);
    const [showPicHistory, setShowPicHistory] = useState(false);

    // Column visibility for Security Properties Catalog table (default hidden: code, type, policy)
    const [propColumns, setPropColumns] = useState({
        code: false,
        label: true,
        type: false,
        value: true,
        policy: false,
    });
    const [columnMenuAnchor, setColumnMenuAnchor] = useState(null);

    // Modals
    const initialAssignPicState = {
        open: false,
        userId: '',
        userName: '',
        reason: '',
        associate_device: false,
        device_mode: 'select',
        software_device_id: '',
        relationship_type: 'authorized',
        ownership_type: 'company',
        fixed_asset_id: '',
        hostname: '',
        model_name: '',
        manufacturer: '',
        serial_number: '',
        operating_system: '',
        device_status: 'active',
        notes: '',
    };
    const [editPropModal, setEditPropModal] = useState({ open: false, def: null, value: '', reason: '' });
    const [assignPicModal, setAssignPicModal] = useState(initialAssignPicState);
    const [userPickerOpen, setUserPickerOpen] = useState(false);
    const [userPickerTarget, setUserPickerTarget] = useState('pic'); // 'pic' or 'review'
    const [emailPickerOpen, setEmailPickerOpen] = useState(false);
    const [emailPickerTarget, setEmailPickerTarget] = useState('property'); // 'property' or 'linkEmail'
    const [revokePicModal, setRevokePicModal] = useState({ open: false, pic: null, reason: '' });
    const [uploadEvidenceModal, setUploadEvidenceModal] = useState({ open: false, defId: '', file: null });
    const [verifyEvidenceModal, setVerifyEvidenceModal] = useState({ open: false, evidence: null, status: 'accepted', notes: '' });
    const [scheduleReviewModal, setScheduleReviewModal] = useState({ open: false, reviewType: 'scheduled', scheduledAt: '', assignedTo: '', assignedToName: '', notes: '' });
    const [completeReviewModal, setCompleteReviewModal] = useState({ open: false, review: null, result: 'compliant', notes: '' });
    const [linkEmailModal, setLinkEmailModal] = useState({ open: false, emailId: '', purpose: 'registration', isPrimary: false });

    // Device Management Modals & State
    const [deviceModal, setDeviceModal] = useState({
        open: false,
        mode: 'select', // 'select' or 'create'
        software_device_id: '',
        user_id: '',
        relationship_type: 'authorized',
        // Create new device fields
        ownership_type: 'company',
        fixed_asset_id: '',
        hostname: '',
        model_name: '',
        manufacturer: '',
        serial_number: '',
        operating_system: '',
        device_status: 'active',
        notes: '',
    });

    // Pending property changes
    const pendingChanges = (account.property_histories || []).filter(h => h.review_status === 'pending');

    const handleUpdateProperty = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/accounts/${account.id}/properties`, {
            property_code: editPropModal.def.code,
            value: editPropModal.value,
            change_reason: editPropModal.reason,
        }, {
            onSuccess: () => setEditPropModal({ open: false, def: null, value: '', reason: '' }),
        });
    };

    const handleOpenEditPropModal = (def) => {
        const val = currentProps[def.code];
        let initialVal = val !== undefined && val !== null ? val : '';
        if (def.data_type === 'boolean') {
            initialVal = val !== undefined && val !== null ? Boolean(val) : false; // default false if null
        } else if (def.data_type === 'multi_choice') {
            initialVal = Array.isArray(val) ? val : (val ? [val] : []);
        } else if (def.data_type === 'single_choice') {
            initialVal = val !== undefined && val !== null ? String(val) : '';
        }
        setEditPropModal({
            open: true,
            def,
            value: initialVal,
            reason: '',
        });
    };

    const handleQuickToggleBoolean = (def, nextChecked) => {
        router.post(`/digital-assets/accounts/${account.id}/properties`, {
            property_code: def.code,
            value: nextChecked,
            change_reason: `Swiped ${def.label} to ${nextChecked ? 'Enabled' : 'Disabled'}`,
        }, { preserveScroll: true });
    };

    const handleApproveProperty = (historyId) => {
        router.post(`/digital-assets/property-histories/${historyId}/approve`, {}, { preserveScroll: true });
    };

    const handleRejectProperty = (historyId) => {
        const reason = prompt('Please provide reason for rejecting property change:');
        if (reason) {
            router.post(`/digital-assets/property-histories/${historyId}/reject`, { reason }, { preserveScroll: true });
        }
    };

    const handleAssignPic = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/accounts/${account.id}/pics`, {
            user_id: assignPicModal.userId,
            reason: assignPicModal.reason,
            associate_device: assignPicModal.associate_device ? 1 : 0,
            device_mode: assignPicModal.device_mode,
            software_device_id: assignPicModal.software_device_id,
            relationship_type: assignPicModal.relationship_type,
            ownership_type: assignPicModal.ownership_type,
            fixed_asset_id: assignPicModal.fixed_asset_id || null,
            hostname: assignPicModal.hostname,
            manufacturer: assignPicModal.manufacturer,
            model_name: assignPicModal.model_name,
            serial_number: assignPicModal.serial_number,
            operating_system: assignPicModal.operating_system,
            device_status: assignPicModal.device_status,
            notes: assignPicModal.notes,
        }, {
            onSuccess: () => setAssignPicModal({
                ...initialAssignPicState,
                software_device_id: availableDevices[0]?.id || '',
            }),
        });
    };

    const handleUserPicked = (user) => {
        if (!user) return;
        if (userPickerTarget === 'pic') {
            setAssignPicModal(prev => ({
                ...prev,
                userId: user.id,
                userName: user.name + (user.email ? ` (${user.email})` : ''),
            }));
        } else if (userPickerTarget === 'review') {
            setScheduleReviewModal(prev => ({
                ...prev,
                assignedTo: user.id,
                assignedToName: user.name + (user.email ? ` (${user.email})` : ''),
            }));
        }
        setUserPickerOpen(false);
    };

    const handleRevokePic = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/pics/${revokePicModal.pic.id}/revoke`, {
            reason: revokePicModal.reason,
        }, {
            onSuccess: () => setRevokePicModal({ open: false, pic: null, reason: '' }),
        });
    };

    const handleReactivatePic = (pic) => {
        if (confirm(`Reactivate ${pic.user?.name || 'this user'} as Person in Charge?`)) {
            router.post(`/digital-assets/pics/${pic.id}/reactivate`, {
                reason: 'Reactivated by user',
            }, { preserveScroll: true });
        }
    };

    const handleUploadEvidence = (e) => {
        e.preventDefault();
        const data = new FormData();
        data.append('property_definition_id', uploadEvidenceModal.defId);
        data.append('file', uploadEvidenceModal.file);

        router.post(`/digital-assets/accounts/${account.id}/evidence`, data, {
            onSuccess: () => setUploadEvidenceModal({ open: false, defId: '', file: null }),
        });
    };

    const handleVerifyEvidence = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/evidence/${verifyEvidenceModal.evidence.id}/verify`, {
            status: verifyEvidenceModal.status,
            review_notes: verifyEvidenceModal.notes,
        }, {
            onSuccess: () => setVerifyEvidenceModal({ open: false, evidence: null, status: 'accepted', notes: '' }),
        });
    };

    const handleScheduleReview = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/accounts/${account.id}/reviews`, {
            review_type: scheduleReviewModal.reviewType,
            scheduled_at: scheduleReviewModal.scheduledAt,
            assigned_to: scheduleReviewModal.assignedTo || null,
            notes: scheduleReviewModal.notes,
        }, {
            onSuccess: () => setScheduleReviewModal({ open: false, reviewType: 'scheduled', scheduledAt: '', assignedTo: '', notes: '' }),
        });
    };

    const handleCompleteReview = (e) => {
        e.preventDefault();
        setCompleteReviewModal(prev => ({ ...prev, error: '', isSubmitting: true }));
        router.post(`/digital-assets/reviews/${completeReviewModal.review.id}/complete`, {
            result: completeReviewModal.result,
            notes: completeReviewModal.notes,
        }, {
            preserveScroll: true,
            onSuccess: () => setCompleteReviewModal({ open: false, review: null, result: 'compliant', notes: '', error: '', isSubmitting: false }),
            onError: (errs) => {
                setCompleteReviewModal(prev => ({
                    ...prev,
                    isSubmitting: false,
                    error: errs.complete || errs.result || Object.values(errs)[0] || 'Failed to complete review.'
                }));
            }
        });
    };

    const handleLinkEmail = (e) => {
        e.preventDefault();
        router.post(`/digital-assets/accounts/${account.id}/emails`, {
            email_id: linkEmailModal.emailId,
            purpose: linkEmailModal.purpose,
            is_primary: linkEmailModal.isPrimary,
        }, {
            onSuccess: () => setLinkEmailModal({ open: false, emailId: '', purpose: 'registration', isPrimary: false }),
        });
    };

    const handleSaveDevice = (e) => {
        e.preventDefault();

        if (deviceModal.mode === 'select') {
            router.post(`/digital-assets/accounts/${account.id}/devices`, {
                software_device_id: deviceModal.software_device_id,
                relationship_type: deviceModal.relationship_type,
                user_id: deviceModal.user_id || null,
            }, {
                onSuccess: () => setDeviceModal(prev => ({ ...prev, open: false })),
            });
        } else {
            router.post('/digital-assets/devices', {
                ownership_type: deviceModal.ownership_type,
                fixed_asset_id: deviceModal.fixed_asset_id || null,
                hostname: deviceModal.hostname,
                model_name: deviceModal.model_name,
                manufacturer: deviceModal.manufacturer,
                serial_number: deviceModal.serial_number,
                operating_system: deviceModal.operating_system,
                device_status: deviceModal.device_status,
                notes: deviceModal.notes,
                link_account_id: account.id,
                relationship_type: deviceModal.relationship_type,
                user_id: deviceModal.user_id || null,
            }, {
                onSuccess: () => {
                    setDeviceModal(prev => ({ ...prev, open: false }));
                }
            });
        }
    };

    const handleVerifyDeviceLink = (linkId) => {
        router.post(`/digital-assets/account-devices/${linkId}/verify`, {}, { preserveScroll: true });
    };

    const currentProps = account.properties || {};
    const allPics = account.pics && account.pics.length > 0 ? account.pics : (account.active_pics || []);
    const activePics = allPics.filter(p => p.status === 'active');
    const inactivePics = (account.pics || []).filter(p => p.status !== 'active');

    return (
        <AsideLayout title={`Software Account: ${account.account_name}`}>
            <Head title={`${account.account_name} - Digital Asset`} />

            {/* Back Button & Account Header */}
            <Box sx={{ mb: 3 }}>
                <Button
                    component="a"
                    href="/digital-assets/accounts"
                    startIcon={<ArrowBackIcon />}
                    sx={{ textTransform: 'none', mb: 1.5 }}
                >
                    Back to Accounts
                </Button>

                <Card sx={{ borderRadius: 2, boxShadow: 1, p: 1 }}>
                    <CardContent>
                        <Grid container spacing={2} alignItems="center">
                            <Grid item xs={12} md={7}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, flexWrap: 'wrap' }}>
                                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                                        {account.account_name}
                                    </Typography>
                                    <Chip label={account.platform?.name} color="primary" size="small" />
                                    <Chip label={account.lifecycle_status} variant="outlined" size="small" />
                                    <Chip
                                        label={`Security: ${account.security_status}`}
                                        color={account.security_status === 'compliant' ? 'success' : account.security_status === 'at_risk' ? 'error' : 'warning'}
                                        size="small"
                                    />
                                </Box>
                                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5, fontFamily: 'monospace' }}>
                                    Login Username / Identity: <strong>{account.username}</strong>
                                </Typography>
                            </Grid>
                            <Grid item xs={12} md={5}>
                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5, alignItems: { md: 'flex-end' } }}>
                                    <Typography variant="caption" color="text.secondary">
                                        Last Reviewed: {account.last_reviewed_at ? new Date(account.last_reviewed_at).toLocaleDateString() : 'Never'}
                                    </Typography>
                                    <Typography variant="caption" color="text.secondary">
                                        Next Review Due: {account.next_review_at ? new Date(account.next_review_at).toLocaleDateString() : 'Not Scheduled'}
                                    </Typography>
                                </Box>
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>
            </Box>

            {/* Pending Property Changes Alert Banner */}
            {pendingChanges.length > 0 && (
                <Alert severity="warning" sx={{ mb: 3, borderRadius: 2 }}>
                    <strong>Pending Approval:</strong> There are {pendingChanges.length} security property change(s) awaiting authorized review.
                </Alert>
            )}

            {/* Navigation Tabs */}
            <Card sx={{ borderRadius: 2, boxShadow: 1, mb: 3 }}>
                <Box sx={{ borderBottom: 1, borderColor: 'divider', px: 2 }}>
                    <Tabs
                        value={currentTab}
                        onChange={(e, val) => setCurrentTab(val)}
                        variant="scrollable"
                        scrollButtons={false}
                        sx={{
                            '& .MuiTabs-indicator': {
                                display: 'none',
                            },
                            '& .MuiTab-root': {
                                textTransform: 'none',
                                fontWeight: 600,
                                minHeight: 48,
                                py: 1.5,
                                position: 'relative',
                                '&.Mui-selected::after': {
                                    content: '""',
                                    position: 'absolute',
                                    bottom: 0,
                                    left: 8,
                                    right: 8,
                                    height: '2.5px',
                                    backgroundColor: 'primary.main',
                                    borderRadius: '2px 2px 0 0',
                                },
                            },
                        }}
                    >
                        <Tab icon={<SecurityIcon />} iconPosition="start" label="Security Properties" />
                        <Tab icon={<PersonIcon />} iconPosition="start" label={`PICs (${activePics.length})`} />
                        <Tab icon={<EvidenceIcon />} iconPosition="start" label={`Evidence (${(account.evidence || []).length})`} />
                        <Tab icon={<ReviewIcon />} iconPosition="start" label={`Reviews (${(account.reviews || []).length})`} />
                        <Tab icon={<DevicesIcon />} iconPosition="start" label={`Devices (${(account.account_devices || []).length})`} />
                        <Tab icon={<HistoryIcon />} iconPosition="start" label="Audit Trail" />
                    </Tabs>
                </Box>

                <Box sx={{ p: 3 }}>
                    {/* TAB 0: SECURITY PROPERTIES */}
                    {currentTab === 0 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Security Properties Catalog
                                </Typography>
                                <Box sx={{ display: 'flex', gap: 1.5, alignItems: 'center' }}>
                                    <Button
                                        variant="outlined"
                                        size="small"
                                        startIcon={<ViewColumnIcon />}
                                        onClick={(e) => setColumnMenuAnchor(e.currentTarget)}
                                        sx={{ textTransform: 'none', fontWeight: 600 }}
                                    >
                                        Columns
                                    </Button>
                                    <Button
                                        variant="outlined"
                                        size="small"
                                        onClick={() => setLinkEmailModal({ ...linkEmailModal, open: true })}
                                        sx={{ textTransform: 'none' }}
                                    >
                                        Associate Email Address
                                    </Button>
                                </Box>
                            </Box>

                            {/* Column Visibility Popover Menu */}
                            <Popover
                                open={Boolean(columnMenuAnchor)}
                                anchorEl={columnMenuAnchor}
                                onClose={() => setColumnMenuAnchor(null)}
                                anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
                                transformOrigin={{ vertical: 'top', horizontal: 'right' }}
                                PaperProps={{ sx: { p: 2, width: 240, borderRadius: 2, boxShadow: 3 } }}
                            >
                                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                                    Show / Hide Columns
                                </Typography>
                                <Divider sx={{ mb: 1 }} />
                                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.25 }}>
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                size="small"
                                                checked={propColumns.code}
                                                onChange={(e) => setPropColumns(prev => ({ ...prev, code: e.target.checked }))}
                                            />
                                        }
                                        label={<Typography variant="body2">Property Code</Typography>}
                                        sx={{ mx: 0 }}
                                    />
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                size="small"
                                                checked={propColumns.label}
                                                onChange={(e) => setPropColumns(prev => ({ ...prev, label: e.target.checked }))}
                                            />
                                        }
                                        label={<Typography variant="body2">Label / Description</Typography>}
                                        sx={{ mx: 0 }}
                                    />
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                size="small"
                                                checked={propColumns.type}
                                                onChange={(e) => setPropColumns(prev => ({ ...prev, type: e.target.checked }))}
                                            />
                                        }
                                        label={<Typography variant="body2">Type</Typography>}
                                        sx={{ mx: 0 }}
                                    />
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                size="small"
                                                checked={propColumns.value}
                                                onChange={(e) => setPropColumns(prev => ({ ...prev, value: e.target.checked }))}
                                            />
                                        }
                                        label={<Typography variant="body2">Current Value</Typography>}
                                        sx={{ mx: 0 }}
                                    />
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                size="small"
                                                checked={propColumns.policy}
                                                onChange={(e) => setPropColumns(prev => ({ ...prev, policy: e.target.checked }))}
                                            />
                                        }
                                        label={<Typography variant="body2">Policy</Typography>}
                                        sx={{ mx: 0 }}
                                    />
                                </Box>
                                <Divider sx={{ my: 1 }} />
                                <Button
                                    fullWidth
                                    size="small"
                                    color="secondary"
                                    onClick={() => setPropColumns({
                                        code: false,
                                        label: true,
                                        type: false,
                                        value: true,
                                        policy: false,
                                    })}
                                    sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                                >
                                    Reset to Default
                                </Button>
                            </Popover>

                            {/* Linked Emails Summary */}
                            <Box sx={{ mb: 3, p: 2, bgcolor: 'background.default', borderRadius: 2 }}>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                                    Linked Email Addresses
                                </Typography>
                                {account.emails && account.emails.length > 0 ? (
                                    <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                                        {account.emails.map((e) => (
                                            <Chip
                                                key={e.id}
                                                size="small"
                                                label={`${e.email_record?.email} (${e.purpose}${e.is_primary ? ' • Primary' : ''})`}
                                                color={e.is_primary ? 'primary' : 'default'}
                                                variant={e.is_primary ? 'filled' : 'outlined'}
                                            />
                                        ))}
                                    </Box>
                                ) : (
                                    <Typography variant="caption" color="text.secondary">No email address associated yet.</Typography>
                                )}
                            </Box>

                            {/* Properties Table */}
                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            {propColumns.code && <TableCell sx={{ fontWeight: 700 }}>Property Code</TableCell>}
                                            {propColumns.label && <TableCell sx={{ fontWeight: 700 }}>Label / Description</TableCell>}
                                            {propColumns.type && <TableCell sx={{ fontWeight: 700 }}>Type</TableCell>}
                                            {propColumns.value && <TableCell sx={{ fontWeight: 700 }}>Current Value</TableCell>}
                                            {propColumns.policy && <TableCell sx={{ fontWeight: 700 }}>Policy</TableCell>}
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {definitions.length > 0 ? (
                                            definitions.map((def) => {
                                                const val = currentProps[def.code];
                                                const pending = pendingChanges.find(h => h.property_definition_id === def.id);

                                                return (
                                                    <TableRow key={def.id} hover>
                                                        {propColumns.code && (
                                                            <TableCell sx={{ fontFamily: 'monospace', fontWeight: 600 }}>{def.code}</TableCell>
                                                        )}
                                                        {propColumns.label && (
                                                            <TableCell>
                                                                <Typography variant="body2" sx={{ fontWeight: 600 }}>{def.label}</Typography>
                                                                {def.description && (
                                                                    <Typography variant="caption" color="text.secondary">{def.description}</Typography>
                                                                )}
                                                            </TableCell>
                                                        )}
                                                        {propColumns.type && (
                                                            <TableCell><Chip size="small" label={def.data_type} variant="outlined" /></TableCell>
                                                        )}
                                                        {propColumns.value && (
                                                            <TableCell
                                                                sx={{
                                                                    minWidth: 200,
                                                                    cursor: def.data_type !== 'boolean' ? 'pointer' : 'default',
                                                                    '&:hover': def.data_type !== 'boolean' ? {
                                                                        bgcolor: 'action.hover',
                                                                    } : {},
                                                                    transition: 'background-color 0.15s ease',
                                                                }}
                                                                onClick={() => {
                                                                    if (def.data_type !== 'boolean') {
                                                                        handleOpenEditPropModal(def);
                                                                    }
                                                                }}
                                                            >
                                                                {pending ? (
                                                                    <Box>
                                                                        <Chip
                                                                            size="small"
                                                                            color="warning"
                                                                            label={
                                                                                def.data_type === 'email_reference' && availableEmails.find(e => String(e.id) === String(pending.new_value?.value))
                                                                                    ? `Pending: ${availableEmails.find(e => String(e.id) === String(pending.new_value?.value))?.email}`
                                                                                    : `Pending: ${Array.isArray(pending.new_value?.value) ? pending.new_value.value.join(', ') : JSON.stringify(pending.new_value?.value)}`
                                                                            }
                                                                        />
                                                                        <Box sx={{ mt: 0.5, display: 'flex', gap: 0.5 }}>
                                                                            <Button size="small" color="success" onClick={(e) => { e.stopPropagation(); handleApproveProperty(pending.id); }}>Approve</Button>
                                                                            <Button size="small" color="error" onClick={(e) => { e.stopPropagation(); handleRejectProperty(pending.id); }}>Reject</Button>
                                                                        </Box>
                                                                    </Box>
                                                                ) : def.data_type === 'boolean' ? (
                                                                    /* Radio swipe style toggle switch. If default is null, show as false */
                                                                    <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 1 }}>
                                                                        <Switch
                                                                            size="small"
                                                                            checked={val !== undefined && val !== null ? Boolean(val) : false}
                                                                            onChange={(e) => handleQuickToggleBoolean(def, e.target.checked)}
                                                                            color="success"
                                                                        />
                                                                        <Typography
                                                                            variant="body2"
                                                                            sx={{
                                                                                fontWeight: 600,
                                                                                fontSize: '0.82rem',
                                                                                color: Boolean(val) ? 'success.main' : 'text.secondary',
                                                                            }}
                                                                        >
                                                                            {Boolean(val) ? 'Enabled / Yes' : 'Disabled / No'}
                                                                        </Typography>
                                                                    </Box>
                                                                ) : val !== undefined && val !== null ? (
                                                                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 1 }}>
                                                                        <Box sx={{ flex: 1 }}>
                                                                            {Array.isArray(val) ? (
                                                                                <Box sx={{ display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                                                                                    {val.length > 0 ? (
                                                                                        val.map((item, idx) => (
                                                                                            <Chip key={idx} size="small" label={String(item)} color="primary" variant="outlined" />
                                                                                        ))
                                                                                    ) : (
                                                                                        <Typography variant="caption" color="text.secondary">None selected</Typography>
                                                                                    )}
                                                                                </Box>
                                                                            ) : typeof val === 'object' ? (
                                                                                JSON.stringify(val)
                                                                            ) : def.data_type === 'email_reference' ? (
                                                                                (() => {
                                                                                    const em = availableEmails.find(e => String(e.id) === String(val));
                                                                                    return em ? (
                                                                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
                                                                                            <Chip
                                                                                                size="small"
                                                                                                icon={<EmailIcon fontSize="small" />}
                                                                                                label={em.email}
                                                                                                color="primary"
                                                                                                variant="outlined"
                                                                                            />
                                                                                            {em.user_name && (
                                                                                                <Typography variant="caption" color="text.secondary">
                                                                                                    ({em.user_name})
                                                                                                </Typography>
                                                                                            )}
                                                                                        </Box>
                                                                                    ) : (
                                                                                        <Chip size="small" icon={<EmailIcon fontSize="small" />} label={`Email #${val}`} variant="outlined" />
                                                                                    );
                                                                                })()
                                                                            ) : (
                                                                                <Typography variant="body2" sx={{ fontWeight: 500 }}>
                                                                                    {String(val)}
                                                                                </Typography>
                                                                            )}
                                                                        </Box>
                                                                        <Tooltip title="Click to edit value">
                                                                            <EditIcon sx={{ fontSize: 16, color: 'action.disabled' }} />
                                                                        </Tooltip>
                                                                    </Box>
                                                                ) : (
                                                                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 1 }}>
                                                                        <Typography
                                                                            variant="body2"
                                                                            color="text.secondary"
                                                                            sx={{
                                                                                fontStyle: 'italic',
                                                                                borderBottom: '1px dashed',
                                                                                borderColor: 'divider',
                                                                                display: 'inline-block',
                                                                            }}
                                                                        >
                                                                            Not Set (Click to set)
                                                                        </Typography>
                                                                        <Tooltip title="Click to set value">
                                                                            <EditIcon sx={{ fontSize: 16, color: 'action.disabled' }} />
                                                                        </Tooltip>
                                                                    </Box>
                                                                )}
                                                            </TableCell>
                                                        )}
                                                        {propColumns.policy && (
                                                            <TableCell>
                                                                <Box sx={{ display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                                                                    {def.is_required && <Chip size="small" label="Required" color="error" variant="outlined" />}
                                                                    {def.evidence_required && <Chip size="small" label="Evidence Req" color="info" variant="outlined" />}
                                                                    {def.requires_approval && <Chip size="small" label="Approval Req" color="warning" variant="outlined" />}
                                                                </Box>
                                                            </TableCell>
                                                        )}
                                                    </TableRow>
                                                );
                                            })
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={Object.values(propColumns).filter(Boolean).length || 1} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No security property definitions found.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </Box>
                    )}

                    {/* TAB 1: PERSONS IN CHARGE (PICS) */}
                    {currentTab === 1 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Active Persons in Charge (PICs)
                                </Typography>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, flexWrap: 'wrap' }}>
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                checked={showInactivePics}
                                                onChange={(e) => setShowInactivePics(e.target.checked)}
                                                size="small"
                                            />
                                        }
                                        label={
                                            <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                Show Inactive PICs ({inactivePics.length})
                                            </Typography>
                                        }
                                    />
                                    <FormControlLabel
                                        control={
                                            <Checkbox
                                                checked={showPicHistory}
                                                onChange={(e) => setShowPicHistory(e.target.checked)}
                                                size="small"
                                            />
                                        }
                                        label={
                                            <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                Show PIC History Log ({(account.pic_histories || []).length})
                                            </Typography>
                                        }
                                    />
                                    <Button
                                        variant="contained"
                                        size="small"
                                        startIcon={<AddIcon />}
                                        onClick={() => setAssignPicModal({
                                            ...initialAssignPicState,
                                            open: true,
                                            software_device_id: availableDevices[0]?.id || ''
                                        })}
                                        sx={{ textTransform: 'none' }}
                                    >
                                        Assign PIC
                                    </Button>
                                </Box>
                            </Box>

                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, mb: 4 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned User</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Email</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Associated Device(s)</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned At</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned By</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {activePics.length > 0 ? (
                                            activePics.map((pic) => {
                                                const picDevices = (account.account_devices || []).filter(
                                                    d => String(d.user_id) === String(pic.user_id)
                                                );
                                                return (
                                                    <TableRow key={pic.id} hover>
                                                        <TableCell sx={{ fontWeight: 600 }}>{pic.user?.name}</TableCell>
                                                        <TableCell>{pic.user?.email || '-'}</TableCell>
                                                        <TableCell>
                                                            {picDevices.length > 0 ? (
                                                                <Box sx={{ display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                                                                    {picDevices.map(pd => (
                                                                        <Chip
                                                                            key={pd.id}
                                                                            size="small"
                                                                            variant="outlined"
                                                                            color="info"
                                                                            icon={<DevicesIcon fontSize="small" />}
                                                                            label={`${pd.device?.hostname || pd.device?.model_name || 'Device'} (${pd.device?.ownership_type || 'dev'})`}
                                                                        />
                                                                    ))}
                                                                </Box>
                                                            ) : (
                                                                <Typography variant="caption" color="text.secondary">No device linked</Typography>
                                                            )}
                                                        </TableCell>
                                                        <TableCell><Chip size="small" color="success" label="Active" /></TableCell>
                                                        <TableCell>{pic.assigned_at ? new Date(pic.assigned_at).toLocaleString() : '-'}</TableCell>
                                                        <TableCell>
                                                            {pic.assigned_by_user ? (
                                                                <Box>
                                                                    <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                                        {pic.assigned_by_user.name}
                                                                    </Typography>
                                                                    {(pic.assigned_by_user.officePosition?.name || pic.assigned_by_user.office_position?.name || pic.assigned_by_user.position?.name || pic.assigned_by_user.department?.name) && (
                                                                        <Typography variant="caption" color="text.secondary" display="block">
                                                                            {[
                                                                                pic.assigned_by_user.officePosition?.name || pic.assigned_by_user.office_position?.name || pic.assigned_by_user.position?.name,
                                                                                pic.assigned_by_user.department?.name
                                                                            ].filter(Boolean).join(' • ')}
                                                                        </Typography>
                                                                    )}
                                                                </Box>
                                                            ) : (
                                                                <Typography variant="body2" color="text.secondary">-</Typography>
                                                            )}
                                                        </TableCell>
                                                        <TableCell align="right">
                                                            <Button
                                                                size="small"
                                                                color="warning"
                                                                variant="outlined"
                                                                onClick={() => setRevokePicModal({ open: true, pic, reason: '' })}
                                                                sx={{ textTransform: 'none' }}
                                                            >
                                                                Inactive
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={7} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No active PICs assigned.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>

                            {/* Inactive PICs Table (Toggled via Checkbox) */}
                            {showInactivePics && (
                                <Box sx={{ mb: 4 }}>
                                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1.5 }}>
                                        <Typography variant="subtitle1" sx={{ fontWeight: 700, color: 'text.secondary', display: 'flex', alignItems: 'center', gap: 1 }}>
                                            Inactive Persons in Charge (PICs)
                                            <Chip size="small" label={`${inactivePics.length} Inactive`} />
                                        </Typography>
                                    </Box>
                                    <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                        <Table size="small">
                                            <TableHead sx={{ bgcolor: 'action.hover' }}>
                                                <TableRow>
                                                    <TableCell sx={{ fontWeight: 700 }}>Assigned User</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Email</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Assigned At</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Inactive Created Time</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Inactive Changed By</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Reason / Notes</TableCell>
                                                    <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                                </TableRow>
                                            </TableHead>
                                            <TableBody>
                                                {inactivePics.length > 0 ? (
                                                    inactivePics.map((pic) => (
                                                        <TableRow key={pic.id} hover sx={{ bgcolor: 'action.hover' }}>
                                                            <TableCell sx={{ fontWeight: 600 }}>{pic.user?.name}</TableCell>
                                                            <TableCell>{pic.user?.email || '-'}</TableCell>
                                                            <TableCell>
                                                                <Chip size="small" color="default" variant="outlined" label="Inactive" />
                                                            </TableCell>
                                                            <TableCell>
                                                                {pic.assigned_at ? new Date(pic.assigned_at).toLocaleString() : '-'}
                                                            </TableCell>
                                                            <TableCell sx={{ fontWeight: 600, color: 'text.primary' }}>
                                                                {pic.revoked_at ? new Date(pic.revoked_at).toLocaleString() : (pic.updated_at ? new Date(pic.updated_at).toLocaleString() : '-')}
                                                            </TableCell>
                                                            <TableCell>
                                                                {pic.revoked_by_user?.name || '-'}
                                                            </TableCell>
                                                            <TableCell>{pic.revocation_reason || '-'}</TableCell>
                                                            <TableCell align="right">
                                                                <Button
                                                                    size="small"
                                                                    color="primary"
                                                                    variant="outlined"
                                                                    onClick={() => handleReactivatePic(pic)}
                                                                    sx={{ textTransform: 'none' }}
                                                                >
                                                                    Reactivate
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                ) : (
                                                    <TableRow>
                                                        <TableCell colSpan={8} align="center" sx={{ py: 3 }}>
                                                            <Typography color="text.secondary">No inactive PICs found.</Typography>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </TableContainer>
                                </Box>
                            )}

                            {/* Append-Only PIC History Log (Toggled via Checkbox) */}
                            {showPicHistory && (
                                <Box sx={{ mt: 3 }}>
                                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1.5 }}>
                                        <Typography variant="subtitle1" sx={{ fontWeight: 700, color: 'text.secondary', display: 'flex', alignItems: 'center', gap: 1 }}>
                                            Append-Only PIC History Log
                                            <Chip size="small" label={`${(account.pic_histories || []).length} Records`} />
                                        </Typography>
                                    </Box>
                                    <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                        <Table size="small">
                                            <TableHead sx={{ bgcolor: 'action.hover' }}>
                                                <TableRow>
                                                    <TableCell sx={{ fontWeight: 700 }}>Occurred At</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Action</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>User</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Performed By (Who Did This)</TableCell>
                                                    <TableCell sx={{ fontWeight: 700 }}>Reason / Notes</TableCell>
                                                </TableRow>
                                            </TableHead>
                                            <TableBody>
                                                {account.pic_histories && account.pic_histories.length > 0 ? (
                                                    account.pic_histories.map((hist) => (
                                                        <TableRow key={hist.id} hover>
                                                            <TableCell>{new Date(hist.occurred_at).toLocaleString()}</TableCell>
                                                            <TableCell>
                                                                <Chip
                                                                    size="small"
                                                                    label={hist.action}
                                                                    color={
                                                                        hist.action === 'assigned' || hist.action === 'reactivated'
                                                                            ? 'success'
                                                                            : (hist.action === 'revoked' || hist.action === 'inactivated' ? 'error' : 'info')
                                                                    }
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                                    {hist.user?.name || '-'}
                                                                </Typography>
                                                                {(hist.user?.officePosition?.name || hist.user?.office_position?.name || hist.user?.position?.name || hist.user?.department?.name) && (
                                                                    <Typography variant="caption" color="text.secondary" display="block">
                                                                        {[
                                                                            hist.user?.officePosition?.name || hist.user?.office_position?.name || hist.user?.position?.name,
                                                                            hist.user?.department?.name
                                                                        ].filter(Boolean).join(' • ')}
                                                                    </Typography>
                                                                )}
                                                            </TableCell>
                                                            <TableCell>
                                                                {hist.performed_by_user ? (
                                                                    <Box>
                                                                        <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                                            {hist.performed_by_user.name}
                                                                        </Typography>
                                                                        {(hist.performed_by_user.officePosition?.name || hist.performed_by_user.office_position?.name || hist.performed_by_user.position?.name || hist.performed_by_user.department?.name) && (
                                                                            <Typography variant="caption" color="text.secondary" display="block">
                                                                                {[
                                                                                    hist.performed_by_user.officePosition?.name || hist.performed_by_user.office_position?.name || hist.performed_by_user.position?.name,
                                                                                    hist.performed_by_user.department?.name
                                                                                ].filter(Boolean).join(' • ')}
                                                                            </Typography>
                                                                        )}
                                                                    </Box>
                                                                ) : (
                                                                    <Typography variant="body2" color="text.secondary">
                                                                        {hist.performed_by ? `User #${hist.performed_by}` : 'System'}
                                                                    </Typography>
                                                                )}
                                                            </TableCell>
                                                            <TableCell>{hist.reason || '-'}</TableCell>
                                                        </TableRow>
                                                    ))
                                                ) : (
                                                    <TableRow>
                                                        <TableCell colSpan={5} align="center" sx={{ py: 3 }}>
                                                            <Typography color="text.secondary">No history events recorded.</Typography>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </TableContainer>
                                </Box>
                            )}
                        </Box>
                    )}

                    {/* TAB 2: EVIDENCE */}
                    {currentTab === 2 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Security Verification Evidence
                                </Typography>
                                <Button
                                    variant="contained"
                                    size="small"
                                    startIcon={<AddIcon />}
                                    onClick={() => setUploadEvidenceModal({ open: true, defId: '', file: null })}
                                    sx={{ textTransform: 'none' }}
                                >
                                    Upload Evidence File
                                </Button>
                            </Box>

                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Property</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Original Filename</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>MIME Type / Size</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>SHA-256 Hash</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Uploaded By</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {account.evidence && account.evidence.length > 0 ? (
                                            account.evidence.map((ev) => (
                                                <TableRow key={ev.id} hover>
                                                    <TableCell sx={{ fontWeight: 600 }}>{ev.property_definition?.label}</TableCell>
                                                    <TableCell>{ev.original_filename}</TableCell>
                                                    <TableCell>
                                                        {ev.mime_type} • {(ev.file_size_bytes / 1024).toFixed(1)} KB
                                                    </TableCell>
                                                    <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.75rem' }}>
                                                        {ev.sha256_hash.substring(0, 16)}...
                                                    </TableCell>
                                                    <TableCell>
                                                        <Chip
                                                            size="small"
                                                            label={ev.status}
                                                            color={ev.status === 'accepted' ? 'success' : ev.status === 'rejected' ? 'error' : 'warning'}
                                                        />
                                                    </TableCell>
                                                    <TableCell>{ev.uploaded_by_user?.name}</TableCell>
                                                    <TableCell align="right">
                                                        <IconButton
                                                            size="small"
                                                            component="a"
                                                            href={`/digital-assets/evidence/${ev.id}/download`}
                                                            title="Download File"
                                                        >
                                                            <DownloadIcon fontSize="small" />
                                                        </IconButton>
                                                        {ev.status === 'pending' && (
                                                            <Button
                                                                size="small"
                                                                onClick={() => setVerifyEvidenceModal({ open: true, evidence: ev, status: 'accepted', notes: '' })}
                                                                sx={{ textTransform: 'none' }}
                                                            >
                                                                Verify
                                                            </Button>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={7} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No evidence uploaded yet.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </Box>
                    )}

                    {/* TAB 3: REVIEWS */}
                    {currentTab === 3 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Account Security Reviews
                                </Typography>
                                <Button
                                    variant="contained"
                                    size="small"
                                    startIcon={<AddIcon />}
                                    onClick={() => setScheduleReviewModal({ open: true, reviewType: 'scheduled', scheduledAt: '', assignedTo: '', notes: '' })}
                                    sx={{ textTransform: 'none' }}
                                >
                                    Schedule Review
                                </Button>
                            </Box>

                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Scheduled Date</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Type</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Result</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned To</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Completed By</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {account.reviews && account.reviews.length > 0 ? (
                                            account.reviews.map((rev) => (
                                                <TableRow key={rev.id} hover>
                                                    <TableCell>{new Date(rev.scheduled_at).toLocaleDateString()}</TableCell>
                                                    <TableCell><Chip size="small" label={rev.review_type} variant="outlined" /></TableCell>
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
                                                                color={rev.result === 'compliant' ? 'success' : 'error'}
                                                            />
                                                        ) : '-'}
                                                    </TableCell>
                                                    <TableCell>{rev.assigned_to_user?.name || 'Unassigned'}</TableCell>
                                                    <TableCell>{rev.completed_by_user?.name || '-'}</TableCell>
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
                                                                onClick={() => setCompleteReviewModal({ open: true, review: rev, result: 'compliant', notes: '' })}
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
                                                <TableCell colSpan={7} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No security reviews recorded for this account.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </Box>
                    )}

                    {/* TAB 4: DEVICES */}
                    {currentTab === 4 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Associated Hardware Devices
                                </Typography>
                                <Button
                                    variant="contained"
                                    size="small"
                                    startIcon={<AddIcon />}
                                    onClick={() => setDeviceModal({
                                        open: true,
                                        mode: availableDevices.length > 0 ? 'select' : 'create',
                                        software_device_id: availableDevices[0]?.id || '',
                                        relationship_type: 'registered',
                                        ownership_type: 'company',
                                        fixed_asset_id: '',
                                        hostname: '',
                                        model_name: '',
                                        manufacturer: '',
                                        operating_system: '',
                                        device_status: 'active',
                                        notes: '',
                                    })}
                                    sx={{ textTransform: 'none' }}
                                >
                                    Associate Device
                                </Button>
                            </Box>
                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Hostname / Model</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned PIC</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Ownership</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Fixed Asset Link</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Relationship</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Last Seen</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }} align="right">Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {account.account_devices && account.account_devices.length > 0 ? (
                                            account.account_devices.map((ad) => (
                                                <TableRow key={ad.id} hover>
                                                    <TableCell sx={{ fontWeight: 600 }}>
                                                        {ad.device?.hostname || ad.device?.model_name || 'Device'}
                                                        {ad.device?.serial_number && (
                                                            <Typography variant="caption" display="block" color="text.secondary">
                                                                S/N: {ad.device?.serial_number}
                                                            </Typography>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {ad.user ? (
                                                            <Chip
                                                                size="small"
                                                                color="primary"
                                                                variant="outlined"
                                                                icon={<PersonIcon fontSize="small" />}
                                                                label={ad.user.name}
                                                            />
                                                        ) : (
                                                            <Typography variant="caption" color="text.secondary">Shared / General</Typography>
                                                        )}
                                                    </TableCell>
                                                    <TableCell><Chip size="small" label={ad.device?.ownership_type} /></TableCell>
                                                    <TableCell>{ad.device?.fixed_asset?.name || 'Personal Device'}</TableCell>
                                                    <TableCell><Chip size="small" color="primary" variant="outlined" label={ad.relationship_type} /></TableCell>
                                                    <TableCell>
                                                        <Chip
                                                            size="small"
                                                            label={(ad.is_verified || ad.verified_at || ad.relationship_type === 'authorized') ? 'Authorized' : 'Pending Verification'}
                                                            color={(ad.is_verified || ad.verified_at || ad.relationship_type === 'authorized') ? 'success' : 'warning'}
                                                        />
                                                    </TableCell>
                                                    <TableCell>{ad.last_seen_at ? new Date(ad.last_seen_at).toLocaleDateString() : '-'}</TableCell>
                                                    <TableCell align="right">
                                                        {!(ad.is_verified || ad.verified_at || ad.relationship_type === 'authorized') && (
                                                            <Button
                                                                size="small"
                                                                color="success"
                                                                onClick={() => handleVerifyDeviceLink(ad.id)}
                                                                sx={{ textTransform: 'none' }}
                                                            >
                                                                Authorize
                                                            </Button>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={8} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No devices currently associated.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </Box>
                    )}

                    {/* TAB 5: AUDIT TRAIL */}
                    {currentTab === 5 && (
                        <Box>
                            <Typography variant="h6" sx={{ fontWeight: 700, mb: 2 }}>
                                Account Security Audit Trail
                            </Typography>
                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Timestamp</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Action</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Actor</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Outcome</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Details / Reason</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>IP Address</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {account.audit_logs && account.audit_logs.length > 0 ? (
                                            account.audit_logs.map((log) => (
                                                <TableRow key={log.id}>
                                                    <TableCell>{new Date(log.occurred_at).toLocaleString()}</TableCell>
                                                    <TableCell sx={{ fontFamily: 'monospace' }}>{log.action}</TableCell>
                                                    <TableCell>{log.actor_user?.name || 'System'}</TableCell>
                                                    <TableCell>
                                                        <Chip
                                                            size="small"
                                                            label={log.outcome}
                                                            color={log.outcome === 'success' ? 'success' : 'error'}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        {log.reason || (log.metadata?.new_status ? `Status: ${log.metadata.new_status}` : (log.metadata?.pic_user_name ? `PIC: ${log.metadata.pic_user_name}` : '-'))}
                                                    </TableCell>
                                                    <TableCell>{log.ip_address}</TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={6} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No audit logs recorded for this account.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </Box>
                    )}
                </Box>
            </Card>

            {/* MODAL: EDIT PROPERTY */}
            <Dialog open={editPropModal.open} onClose={() => setEditPropModal({ ...editPropModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleUpdateProperty}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Update Security Property</DialogTitle>
                    <DialogContent dividers>
                        {editPropModal.def && (
                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                                <Typography variant="subtitle2" sx={{ fontWeight: 600 }}>
                                    {editPropModal.def.label} ({editPropModal.def.code})
                                </Typography>

                                {editPropModal.def.data_type === 'boolean' ? (
                                    <Box sx={{ p: 2, border: 1, borderColor: 'divider', borderRadius: 2, bgcolor: 'background.default' }}>
                                        <FormControlLabel
                                            control={
                                                <Switch
                                                    checked={Boolean(editPropModal.value)}
                                                    onChange={(e) => setEditPropModal({ ...editPropModal, value: e.target.checked })}
                                                    color="success"
                                                />
                                            }
                                            label={
                                                <Box>
                                                    <Typography variant="body1" sx={{ fontWeight: 600 }}>
                                                        {Boolean(editPropModal.value) ? 'Enabled / Yes' : 'Disabled / No'}
                                                    </Typography>
                                                    <Typography variant="caption" color="text.secondary">
                                                        {editPropModal.def.description || 'Swipe switch to toggle security control'}
                                                    </Typography>
                                                </Box>
                                            }
                                        />
                                    </Box>
                                ) : editPropModal.def.data_type === 'single_choice' ? (
                                    <TextField
                                        select
                                        fullWidth
                                        label="Choice"
                                        value={editPropModal.value}
                                        onChange={(e) => setEditPropModal({ ...editPropModal, value: e.target.value })}
                                    >
                                        {(editPropModal.def.validation_rules?.options || []).map((opt) => (
                                            <MenuItem key={opt} value={opt}>{opt}</MenuItem>
                                        ))}
                                    </TextField>
                                ) : editPropModal.def.data_type === 'multi_choice' ? (
                                    <Box>
                                        <Typography variant="caption" sx={{ mb: 1, display: 'block', fontWeight: 600 }}>
                                            Select Options ({Array.isArray(editPropModal.value) ? editPropModal.value.length : 0} selected):
                                        </Typography>
                                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5, maxHeight: 220, overflowY: 'auto', p: 1.5, border: 1, borderColor: 'divider', borderRadius: 2 }}>
                                            {(editPropModal.def.validation_rules?.options || []).length > 0 ? (
                                                (editPropModal.def.validation_rules?.options || []).map((opt) => {
                                                    const currentArray = Array.isArray(editPropModal.value) ? editPropModal.value : [];
                                                    const isChecked = currentArray.includes(opt);
                                                    return (
                                                        <FormControlLabel
                                                            key={opt}
                                                            control={
                                                                <Checkbox
                                                                    size="small"
                                                                    checked={isChecked}
                                                                    onChange={(e) => {
                                                                        if (e.target.checked) {
                                                                            setEditPropModal({ ...editPropModal, value: [...currentArray, opt] });
                                                                        } else {
                                                                            setEditPropModal({ ...editPropModal, value: currentArray.filter(v => v !== opt) });
                                                                        }
                                                                    }}
                                                                />
                                                            }
                                                            label={<Typography variant="body2">{opt}</Typography>}
                                                        />
                                                    );
                                                })
                                            ) : (
                                                <Typography variant="caption" color="text.secondary">
                                                    No allowed options configured for this property definition.
                                                </Typography>
                                            )}
                                        </Box>
                                    </Box>
                                ) : editPropModal.def.data_type === 'email_reference' ? (
                                    <TextField
                                        fullWidth
                                        required={Boolean(editPropModal.def.is_required)}
                                        label="Referenced Email Master"
                                        placeholder="Click Browse to search and select corporate email..."
                                        value={
                                            (() => {
                                                const em = availableEmails.find(e => String(e.id) === String(editPropModal.value));
                                                return em ? `${em.email} (${[em.user_name, em.department?.name].filter(Boolean).join(' • ')})` : '';
                                            })()
                                        }
                                        onClick={() => {
                                            setEmailPickerTarget('property');
                                            setEmailPickerOpen(true);
                                        }}
                                        InputProps={{
                                            readOnly: true,
                                            startAdornment: (
                                                <InputAdornment position="start">
                                                    <EmailIcon color={editPropModal.value ? "primary" : "action"} fontSize="small" />
                                                </InputAdornment>
                                            ),
                                            endAdornment: (
                                                <InputAdornment position="end">
                                                    {editPropModal.value && (
                                                        <IconButton
                                                            size="small"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setEditPropModal({ ...editPropModal, value: '' });
                                                            }}
                                                            sx={{ mr: 0.5 }}
                                                        >
                                                            <ClearIcon fontSize="small" />
                                                        </IconButton>
                                                    )}
                                                    <Button
                                                        variant="outlined"
                                                        size="small"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setEmailPickerTarget('property');
                                                            setEmailPickerOpen(true);
                                                        }}
                                                        sx={{ textTransform: 'none' }}
                                                    >
                                                        Browse
                                                    </Button>
                                                </InputAdornment>
                                            )
                                        }}
                                    />
                                ) : (
                                    <TextField
                                        fullWidth
                                        label="Value"
                                        type={editPropModal.def.data_type === 'integer' || editPropModal.def.data_type === 'decimal' ? 'number' : editPropModal.def.data_type === 'date' ? 'date' : 'text'}
                                        InputLabelProps={{ shrink: true }}
                                        value={editPropModal.value}
                                        onChange={(e) => setEditPropModal({ ...editPropModal, value: e.target.value })}
                                    />
                                )}

                                <TextField
                                    fullWidth
                                    label="Reason for Change"
                                    placeholder="Explain why this security property is updated..."
                                    value={editPropModal.reason}
                                    onChange={(e) => setEditPropModal({ ...editPropModal, reason: e.target.value })}
                                />
                            </Box>
                        )}
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setEditPropModal({ ...editPropModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Save Property</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: ASSIGN PIC */}
            <Dialog open={assignPicModal.open} onClose={() => setAssignPicModal({ ...assignPicModal, open: false })} maxWidth="md" fullWidth>
                <form onSubmit={handleAssignPic}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Assign Person in Charge (PIC)</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2.5, mt: 1 }}>
                            {/* Employee Selection */}
                            <TextField
                                required
                                fullWidth
                                label="Select Employee (PIC)"
                                placeholder="Click Browse to search and select employee..."
                                value={assignPicModal.userName || (availableUsers.find(u => String(u.id) === String(assignPicModal.userId))?.name || '')}
                                onClick={() => {
                                    setUserPickerTarget('pic');
                                    setUserPickerOpen(true);
                                }}
                                InputProps={{
                                    readOnly: true,
                                    endAdornment: (
                                        <Button
                                            size="small"
                                            variant="outlined"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setUserPickerTarget('pic');
                                                setUserPickerOpen(true);
                                            }}
                                            sx={{ textTransform: 'none', whiteSpace: 'nowrap' }}
                                        >
                                            Browse...
                                        </Button>
                                    ),
                                }}
                                helperText="Search employees across departments and branches"
                            />

                            <TextField
                                fullWidth
                                label="Assignment Reason / Purpose"
                                placeholder="e.g. Lead administrator, cloud operator, primary contact..."
                                value={assignPicModal.reason}
                                onChange={(e) => setAssignPicModal({ ...assignPicModal, reason: e.target.value })}
                            />

                            {/* Device Association Section */}
                            <Box sx={{ p: 2, border: '1px solid', borderColor: 'divider', borderRadius: 2, bgcolor: 'background.default' }}>
                                <FormControlLabel
                                    control={
                                        <Checkbox
                                            checked={assignPicModal.associate_device}
                                            onChange={(e) => setAssignPicModal(prev => ({
                                                ...prev,
                                                associate_device: e.target.checked,
                                                software_device_id: prev.software_device_id || (availableDevices[0]?.id || '')
                                            }))}
                                            color="primary"
                                        />
                                    }
                                    label={
                                        <Box>
                                            <Typography variant="subtitle2" sx={{ fontWeight: 700, display: 'flex', alignItems: 'center', gap: 1 }}>
                                                <DevicesIcon fontSize="small" color="primary" />
                                                Associate Hardware Device with this PIC
                                            </Typography>
                                            <Typography variant="caption" color="text.secondary">
                                                Link the physical laptop, workstation, or mobile device used by this PIC to access the account.
                                            </Typography>
                                        </Box>
                                    }
                                />

                                {assignPicModal.associate_device && (
                                    <Box sx={{ mt: 2, display: 'flex', flexDirection: 'column', gap: 2 }}>
                                        {/* Mode Switcher Tabs */}
                                        <Box sx={{ display: 'flex', gap: 1 }}>
                                            <Button
                                                size="small"
                                                variant={assignPicModal.device_mode === 'select' ? 'contained' : 'outlined'}
                                                onClick={() => setAssignPicModal(prev => ({ ...prev, device_mode: 'select' }))}
                                                sx={{ textTransform: 'none', flex: 1 }}
                                            >
                                                Select Existing Device
                                            </Button>
                                            <Button
                                                size="small"
                                                variant={assignPicModal.device_mode === 'create' ? 'contained' : 'outlined'}
                                                onClick={() => setAssignPicModal(prev => ({ ...prev, device_mode: 'create' }))}
                                                sx={{ textTransform: 'none', flex: 1 }}
                                            >
                                                + Register New Device
                                            </Button>
                                        </Box>

                                        {assignPicModal.device_mode === 'select' ? (
                                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                                {availableDevices.length > 0 ? (
                                                    <>
                                                        <Autocomplete
                                                            size="small"
                                                            fullWidth
                                                            options={availableDevices}
                                                            value={availableDevices.find(d => String(d.id) === String(assignPicModal.software_device_id)) || null}
                                                            onChange={(event, newValue) => {
                                                                setAssignPicModal(prev => ({
                                                                    ...prev,
                                                                    software_device_id: newValue ? newValue.id : ''
                                                                }));
                                                            }}
                                                            isOptionEqualToValue={(option, val) => String(option?.id) === String(val?.id)}
                                                            getOptionLabel={(option) => {
                                                                if (!option) return '';
                                                                if (typeof option === 'string') return option;
                                                                const sn = option.serial_number ? `SN: ${option.serial_number}` : '[No S/N]';
                                                                const name = option.hostname || option.model_name || 'Device';
                                                                const owner = option.ownership_type ? `(${option.ownership_type})` : '';
                                                                const asset = option.fixed_asset?.name ? `— ${option.fixed_asset.name}` : '';
                                                                return `${sn} — ${name} ${owner} ${asset}`.trim();
                                                            }}
                                                            filterOptions={(options, { inputValue }) => {
                                                                const q = inputValue.toLowerCase().trim();
                                                                if (!q) return options;
                                                                return options.filter((d) => {
                                                                    const sn = (d.serial_number || '').toLowerCase();
                                                                    const host = (d.hostname || '').toLowerCase();
                                                                    const model = (d.model_name || '').toLowerCase();
                                                                    const mfg = (d.manufacturer || '').toLowerCase();
                                                                    const asset = (d.fixed_asset?.name || '').toLowerCase();
                                                                    return sn.includes(q) || host.includes(q) || model.includes(q) || mfg.includes(q) || asset.includes(q);
                                                                });
                                                            }}
                                                            renderOption={(props, option) => {
                                                                const { key, ...restProps } = props;
                                                                return (
                                                                    <li key={key || option.id} {...restProps}>
                                                                        <Box sx={{ display: 'flex', flexDirection: 'column', width: '100%', py: 0.5 }}>
                                                                            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 1 }}>
                                                                                <Typography
                                                                                    variant="body2"
                                                                                    sx={{
                                                                                        fontWeight: 700,
                                                                                        fontFamily: option.serial_number ? 'monospace' : 'inherit',
                                                                                        color: option.serial_number ? 'primary.main' : 'text.secondary'
                                                                                    }}
                                                                                >
                                                                                    {option.serial_number ? `SN: ${option.serial_number}` : '(No Serial Number)'}
                                                                                </Typography>
                                                                                <Chip
                                                                                    label={option.ownership_type || 'device'}
                                                                                    size="small"
                                                                                    variant="outlined"
                                                                                    sx={{ textTransform: 'capitalize', height: 20, fontSize: '0.68rem' }}
                                                                                />
                                                                            </Box>
                                                                            <Typography variant="caption" color="text.secondary">
                                                                                {option.hostname || option.model_name || 'Device'}
                                                                                {option.manufacturer ? ` • ${option.manufacturer}` : ''}
                                                                                {option.fixed_asset?.name ? ` • Asset: ${option.fixed_asset.name}` : ''}
                                                                            </Typography>
                                                                        </Box>
                                                                    </li>
                                                                );
                                                            }}
                                                            renderInput={(params) => (
                                                                <TextField
                                                                    {...params}
                                                                    required={assignPicModal.associate_device}
                                                                    label="Select Device by Serial Number *"
                                                                    placeholder="Type serial number, hostname, or model..."
                                                                    helperText="Searchable dropdown selection by serial number"
                                                                    slotProps={{
                                                                        ...params.slotProps,
                                                                        input: {
                                                                            ...params.slotProps?.input,
                                                                            startAdornment: (
                                                                                <>
                                                                                    <InputAdornment position="start">
                                                                                        <SearchIcon fontSize="small" color="action" />
                                                                                    </InputAdornment>
                                                                                    {params.slotProps?.input?.startAdornment}
                                                                                </>
                                                                            ),
                                                                        },
                                                                    }}
                                                                />
                                                            )}
                                                        />

                                                        <TextField
                                                            select
                                                            required={assignPicModal.associate_device}
                                                            fullWidth
                                                            size="small"
                                                            label="Access Relationship Type"
                                                            value={assignPicModal.relationship_type}
                                                            onChange={(e) => setAssignPicModal(prev => ({ ...prev, relationship_type: e.target.value }))}
                                                        >
                                                            <MenuItem value="authorized">Authorized (Pre-approved device)</MenuItem>
                                                            <MenuItem value="registered">Registered (Standard Workstation)</MenuItem>
                                                            <MenuItem value="observed">Observed (Detected / Audit Logged)</MenuItem>
                                                            <MenuItem value="blocked">Blocked (Unauthorized)</MenuItem>
                                                        </TextField>
                                                    </>
                                                ) : (
                                                    <Alert severity="info" sx={{ mt: 1 }}>
                                                        No devices currently registered in inventory. Switch to <strong>"+ Register New Device"</strong> to register one.
                                                    </Alert>
                                                )}
                                            </Box>
                                        ) : (
                                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                                <TextField
                                                    select
                                                    required={assignPicModal.associate_device}
                                                    fullWidth
                                                    size="small"
                                                    label="Device Ownership"
                                                    value={assignPicModal.ownership_type}
                                                    onChange={(e) => setAssignPicModal(prev => ({ ...prev, ownership_type: e.target.value }))}
                                                >
                                                    <MenuItem value="company">Company Owned</MenuItem>
                                                    <MenuItem value="personal">Personal / BYOD</MenuItem>
                                                    <MenuItem value="other">Other / Contractor</MenuItem>
                                                </TextField>

                                                {assignPicModal.ownership_type === 'company' && officeAssets.length > 0 && (
                                                    <TextField
                                                        select
                                                        fullWidth
                                                        size="small"
                                                        label="Link Office Fixed Asset (Optional)"
                                                        value={assignPicModal.fixed_asset_id}
                                                        onChange={(e) => setAssignPicModal(prev => ({ ...prev, fixed_asset_id: e.target.value }))}
                                                        helperText="Optionally link device to company office asset inventory"
                                                    >
                                                        <MenuItem value="">None / Unassigned</MenuItem>
                                                        {officeAssets.map((asset) => (
                                                            <MenuItem key={asset.id} value={asset.id}>
                                                                {asset.name} (ID: {asset.id})
                                                            </MenuItem>
                                                        ))}
                                                    </TextField>
                                                )}

                                                <TextField
                                                    required={assignPicModal.associate_device}
                                                    fullWidth
                                                    size="small"
                                                    label="Hostname / Computer Name"
                                                    placeholder="e.g. LAPTOP-DEV-01, MacBook-Pro-Design"
                                                    value={assignPicModal.hostname}
                                                    onChange={(e) => setAssignPicModal(prev => ({ ...prev, hostname: e.target.value }))}
                                                />

                                                <Grid container spacing={1.5}>
                                                    <Grid item xs={6}>
                                                        <TextField
                                                            fullWidth
                                                            size="small"
                                                            label="Manufacturer"
                                                            placeholder="e.g. Apple, Dell, Lenovo"
                                                            value={assignPicModal.manufacturer}
                                                            onChange={(e) => setAssignPicModal(prev => ({ ...prev, manufacturer: e.target.value }))}
                                                        />
                                                    </Grid>
                                                    <Grid item xs={6}>
                                                        <TextField
                                                            fullWidth
                                                            size="small"
                                                            label="Model Name"
                                                            placeholder="e.g. ThinkPad T14, MacBook Pro 14"
                                                            value={assignPicModal.model_name}
                                                            onChange={(e) => setAssignPicModal(prev => ({ ...prev, model_name: e.target.value }))}
                                                        />
                                                    </Grid>
                                                </Grid>

                                                <Grid container spacing={1.5}>
                                                    <Grid item xs={6}>
                                                        <TextField
                                                            required={assignPicModal.associate_device}
                                                            fullWidth
                                                            size="small"
                                                            label="Serial Number *"
                                                            placeholder="e.g. SN-8849202"
                                                            value={assignPicModal.serial_number}
                                                            onChange={(e) => setAssignPicModal(prev => ({ ...prev, serial_number: e.target.value }))}
                                                            helperText="Required and unique hardware serial number"
                                                        />
                                                    </Grid>
                                                    <Grid item xs={6}>
                                                        <TextField
                                                            fullWidth
                                                            size="small"
                                                            label="Operating System"
                                                            placeholder="e.g. Windows 11 Pro, macOS Sonoma"
                                                            value={assignPicModal.operating_system}
                                                            onChange={(e) => setAssignPicModal(prev => ({ ...prev, operating_system: e.target.value }))}
                                                        />
                                                    </Grid>
                                                </Grid>

                                                <TextField
                                                    fullWidth
                                                    size="small"
                                                    multiline
                                                    rows={2}
                                                    label="Notes / Device Details"
                                                    value={assignPicModal.notes}
                                                    onChange={(e) => setAssignPicModal(prev => ({ ...prev, notes: e.target.value }))}
                                                />
                                            </Box>
                                        )}
                                    </Box>
                                )}
                            </Box>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setAssignPicModal({ ...assignPicModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Confirm Assignment</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: INACTIVATE PIC */}
            <Dialog open={revokePicModal.open} onClose={() => setRevokePicModal({ ...revokePicModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleRevokePic}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Set PIC to Inactive</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <Typography variant="body2">
                                Are you sure you want to set <strong>{revokePicModal.pic?.user?.name}</strong> to inactive?
                            </Typography>
                            <TextField
                                fullWidth
                                label="Reason for Inactivating (Optional)"
                                placeholder="e.g. Role transition, project handover..."
                                value={revokePicModal.reason}
                                onChange={(e) => setRevokePicModal({ ...revokePicModal, reason: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setRevokePicModal({ ...revokePicModal, open: false })}>Cancel</Button>
                        <Button type="submit" color="warning" variant="contained">Confirm Inactive</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: UPLOAD EVIDENCE */}
            <Dialog open={uploadEvidenceModal.open} onClose={() => setUploadEvidenceModal({ ...uploadEvidenceModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleUploadEvidence}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Upload Security Evidence</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                required
                                fullWidth
                                label="Target Security Property"
                                value={uploadEvidenceModal.defId}
                                onChange={(e) => setUploadEvidenceModal({ ...uploadEvidenceModal, defId: e.target.value })}
                            >
                                {definitions.map((def) => (
                                    <MenuItem key={def.id} value={def.id}>{def.label} ({def.code})</MenuItem>
                                ))}
                            </TextField>
                            <Button variant="outlined" component="label" sx={{ py: 1.5 }}>
                                Select Evidence File (Image or PDF)
                                <input
                                    type="file"
                                    hidden
                                    required
                                    onChange={(e) => setUploadEvidenceModal({ ...uploadEvidenceModal, file: e.target.files[0] })}
                                />
                            </Button>
                            {uploadEvidenceModal.file && (
                                <Typography variant="caption" color="text.secondary">
                                    Selected: {uploadEvidenceModal.file.name} ({(uploadEvidenceModal.file.size / 1024).toFixed(1)} KB)
                                </Typography>
                            )}
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setUploadEvidenceModal({ ...uploadEvidenceModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Upload Privately</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: VERIFY EVIDENCE */}
            <Dialog open={verifyEvidenceModal.open} onClose={() => setVerifyEvidenceModal({ ...verifyEvidenceModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleVerifyEvidence}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Verify Evidence Integrity</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Verification Decision"
                                value={verifyEvidenceModal.status}
                                onChange={(e) => setVerifyEvidenceModal({ ...verifyEvidenceModal, status: e.target.value })}
                            >
                                <MenuItem value="accepted">Accept & Validate Evidence</MenuItem>
                                <MenuItem value="rejected">Reject Evidence</MenuItem>
                            </TextField>
                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                label="Reviewer Notes / Assessment"
                                value={verifyEvidenceModal.notes}
                                onChange={(e) => setVerifyEvidenceModal({ ...verifyEvidenceModal, notes: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setVerifyEvidenceModal({ ...verifyEvidenceModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Submit Verification</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: SCHEDULE REVIEW */}
            <Dialog open={scheduleReviewModal.open} onClose={() => setScheduleReviewModal({ ...scheduleReviewModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleScheduleReview}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Schedule Compliance Review</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Review Type"
                                value={scheduleReviewModal.reviewType}
                                onChange={(e) => setScheduleReviewModal({ ...scheduleReviewModal, reviewType: e.target.value })}
                            >
                                <MenuItem value="scheduled">Scheduled Routine Review</MenuItem>
                                <MenuItem value="ad_hoc">Ad-Hoc Security Audit</MenuItem>
                                <MenuItem value="incident">Incident Investigation Review</MenuItem>
                            </TextField>
                            <TextField
                                type="datetime-local"
                                required
                                fullWidth
                                label="Scheduled Date & Time"
                                InputLabelProps={{ shrink: true }}
                                value={scheduleReviewModal.scheduledAt}
                                onChange={(e) => setScheduleReviewModal({ ...scheduleReviewModal, scheduledAt: e.target.value })}
                            />
                            <TextField
                                fullWidth
                                label="Assign Auditor / Reviewer"
                                placeholder="Click to search and select auditor..."
                                value={scheduleReviewModal.assignedToName || (availableUsers.find(u => String(u.id) === String(scheduleReviewModal.assignedTo))?.name || '')}
                                onClick={() => {
                                    setUserPickerTarget('review');
                                    setUserPickerOpen(true);
                                }}
                                InputProps={{
                                    readOnly: true,
                                    endAdornment: (
                                        <Box sx={{ display: 'flex', gap: 0.5 }}>
                                            {scheduleReviewModal.assignedTo && (
                                                <Button
                                                    size="small"
                                                    color="inherit"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setScheduleReviewModal(prev => ({ ...prev, assignedTo: '', assignedToName: '' }));
                                                    }}
                                                    sx={{ textTransform: 'none', whiteSpace: 'nowrap' }}
                                                >
                                                    Clear
                                                </Button>
                                            )}
                                            <Button
                                                size="small"
                                                variant="outlined"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setUserPickerTarget('review');
                                                    setUserPickerOpen(true);
                                                }}
                                                sx={{ textTransform: 'none', whiteSpace: 'nowrap' }}
                                            >
                                                Browse...
                                            </Button>
                                        </Box>
                                    ),
                                }}
                            />
                            <TextField
                                fullWidth
                                multiline
                                rows={2}
                                label="Audit Scope / Notes"
                                value={scheduleReviewModal.notes}
                                onChange={(e) => setScheduleReviewModal({ ...scheduleReviewModal, notes: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setScheduleReviewModal({ ...scheduleReviewModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Schedule Review</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: COMPLETE REVIEW */}
            <Dialog open={completeReviewModal.open} onClose={() => !completeReviewModal.isSubmitting && setCompleteReviewModal({ ...completeReviewModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleCompleteReview}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Complete Compliance Review</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            {completeReviewModal.error && (
                                <Alert severity="error" onClose={() => setCompleteReviewModal(prev => ({ ...prev, error: '' }))}>
                                    {completeReviewModal.error}
                                </Alert>
                            )}
                            <TextField
                                select
                                fullWidth
                                label="Compliance Result"
                                value={completeReviewModal.result}
                                onChange={(e) => setCompleteReviewModal({ ...completeReviewModal, result: e.target.value })}
                                disabled={completeReviewModal.isSubmitting}
                            >
                                <MenuItem value="compliant">Compliant (All policies satisfied)</MenuItem>
                                <MenuItem value="needs_action">Needs Action (Minor issues found)</MenuItem>
                                <MenuItem value="at_risk">At Risk (Critical vulnerability or missing 2FA)</MenuItem>
                            </TextField>
                            <TextField
                                fullWidth
                                multiline
                                rows={3}
                                label="Auditor Findings & Conclusion"
                                value={completeReviewModal.notes}
                                onChange={(e) => setCompleteReviewModal({ ...completeReviewModal, notes: e.target.value })}
                                disabled={completeReviewModal.isSubmitting}
                                placeholder="Enter audit findings, observations, or remediation requirements..."
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCompleteReviewModal({ ...completeReviewModal, open: false })} disabled={completeReviewModal.isSubmitting}>Cancel</Button>
                        <Button type="submit" variant="contained" disabled={completeReviewModal.isSubmitting} startIcon={completeReviewModal.isSubmitting ? <CircularProgress size={18} color="inherit" /> : null}>
                            {completeReviewModal.isSubmitting ? 'Finalizing...' : 'Finalize Review'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: ASSOCIATE EMAIL */}
            <Dialog open={linkEmailModal.open} onClose={() => setLinkEmailModal({ ...linkEmailModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleLinkEmail}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Associate Email Address</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                required
                                fullWidth
                                label="Email Master"
                                placeholder="Click Browse to select corporate email..."
                                value={
                                    (() => {
                                        const em = availableEmails.find(e => String(e.id) === String(linkEmailModal.emailId));
                                        return em ? `${em.email} (${[em.user_name, em.department?.name].filter(Boolean).join(' • ')})` : '';
                                    })()
                                }
                                onClick={() => {
                                    setEmailPickerTarget('linkEmail');
                                    setEmailPickerOpen(true);
                                }}
                                InputProps={{
                                    readOnly: true,
                                    startAdornment: (
                                        <InputAdornment position="start">
                                            <EmailIcon color={linkEmailModal.emailId ? "primary" : "action"} fontSize="small" />
                                        </InputAdornment>
                                    ),
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            {linkEmailModal.emailId && (
                                                <IconButton
                                                    size="small"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setLinkEmailModal({ ...linkEmailModal, emailId: '' });
                                                    }}
                                                    sx={{ mr: 0.5 }}
                                                >
                                                    <ClearIcon fontSize="small" />
                                                </IconButton>
                                            )}
                                            <Button
                                                variant="outlined"
                                                size="small"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setEmailPickerTarget('linkEmail');
                                                    setEmailPickerOpen(true);
                                                }}
                                                sx={{ textTransform: 'none' }}
                                            >
                                                Browse
                                            </Button>
                                        </InputAdornment>
                                    )
                                }}
                            />
                            <TextField
                                select
                                fullWidth
                                label="Purpose"
                                value={linkEmailModal.purpose}
                                onChange={(e) => setLinkEmailModal({ ...linkEmailModal, purpose: e.target.value })}
                            >
                                <MenuItem value="registration">Registration Email</MenuItem>
                                <MenuItem value="recovery">Account Recovery Email</MenuItem>
                                <MenuItem value="contact">Contact Email</MenuItem>
                                <MenuItem value="notification">Security Notifications</MenuItem>
                                <MenuItem value="other">Other</MenuItem>
                            </TextField>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setLinkEmailModal({ ...linkEmailModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Link Email</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: ASSOCIATE OR REGISTER DEVICE */}
            <Dialog open={deviceModal.open} onClose={() => setDeviceModal({ ...deviceModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleSaveDevice}>
                    <DialogTitle sx={{ fontWeight: 700 }}>
                        {deviceModal.mode === 'select' ? 'Associate Existing Device' : 'Register & Associate New Device'}
                    </DialogTitle>
                    <DialogContent dividers>
                        {/* Mode Switcher Tabs / Buttons */}
                        <Box sx={{ display: 'flex', gap: 1, mb: 3 }}>
                            <Button
                                variant={deviceModal.mode === 'select' ? 'contained' : 'outlined'}
                                onClick={() => setDeviceModal(prev => ({ ...prev, mode: 'select' }))}
                                sx={{ textTransform: 'none', flex: 1 }}
                            >
                                Select Existing Device
                            </Button>
                            <Button
                                variant={deviceModal.mode === 'create' ? 'contained' : 'outlined'}
                                onClick={() => setDeviceModal(prev => ({ ...prev, mode: 'create' }))}
                                sx={{ textTransform: 'none', flex: 1 }}
                            >
                                + Register New Device
                            </Button>
                        </Box>

                        {deviceModal.mode === 'select' ? (
                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                {availableDevices.length > 0 ? (
                                    <>
                                        <Autocomplete
                                            size="small"
                                            fullWidth
                                            options={availableDevices}
                                            value={availableDevices.find(d => String(d.id) === String(deviceModal.software_device_id)) || null}
                                            onChange={(event, newValue) => {
                                                setDeviceModal(prev => ({
                                                    ...prev,
                                                    software_device_id: newValue ? newValue.id : ''
                                                }));
                                            }}
                                            isOptionEqualToValue={(option, val) => String(option?.id) === String(val?.id)}
                                            getOptionLabel={(option) => {
                                                if (!option) return '';
                                                if (typeof option === 'string') return option;
                                                const sn = option.serial_number ? `SN: ${option.serial_number}` : '[No S/N]';
                                                const name = option.hostname || option.model_name || 'Device';
                                                const owner = option.ownership_type ? `(${option.ownership_type})` : '';
                                                const asset = option.fixed_asset?.name ? `— ${option.fixed_asset.name}` : '';
                                                return `${sn} — ${name} ${owner} ${asset}`.trim();
                                            }}
                                            filterOptions={(options, { inputValue }) => {
                                                const q = inputValue.toLowerCase().trim();
                                                if (!q) return options;
                                                return options.filter((d) => {
                                                    const sn = (d.serial_number || '').toLowerCase();
                                                    const host = (d.hostname || '').toLowerCase();
                                                    const model = (d.model_name || '').toLowerCase();
                                                    const mfg = (d.manufacturer || '').toLowerCase();
                                                    const asset = (d.fixed_asset?.name || '').toLowerCase();
                                                    return sn.includes(q) || host.includes(q) || model.includes(q) || mfg.includes(q) || asset.includes(q);
                                                });
                                            }}
                                            renderOption={(props, option) => {
                                                const { key, ...restProps } = props;
                                                return (
                                                    <li key={key || option.id} {...restProps}>
                                                        <Box sx={{ display: 'flex', flexDirection: 'column', width: '100%', py: 0.5 }}>
                                                            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 1 }}>
                                                                <Typography
                                                                    variant="body2"
                                                                    sx={{
                                                                        fontWeight: 700,
                                                                        fontFamily: option.serial_number ? 'monospace' : 'inherit',
                                                                        color: option.serial_number ? 'primary.main' : 'text.secondary'
                                                                    }}
                                                                >
                                                                    {option.serial_number ? `SN: ${option.serial_number}` : '(No Serial Number)'}
                                                                </Typography>
                                                                <Chip
                                                                    label={option.ownership_type || 'device'}
                                                                    size="small"
                                                                    variant="outlined"
                                                                    sx={{ textTransform: 'capitalize', height: 20, fontSize: '0.68rem' }}
                                                                />
                                                            </Box>
                                                            <Typography variant="caption" color="text.secondary">
                                                                {option.hostname || option.model_name || 'Device'}
                                                                {option.manufacturer ? ` • ${option.manufacturer}` : ''}
                                                                {option.fixed_asset?.name ? ` • Asset: ${option.fixed_asset.name}` : ''}
                                                            </Typography>
                                                        </Box>
                                                    </li>
                                                );
                                            }}
                                            renderInput={(params) => (
                                                <TextField
                                                    {...params}
                                                    required
                                                    label="Select Device by Serial Number *"
                                                    placeholder="Type serial number, hostname, or model..."
                                                    helperText="Searchable dropdown selection by serial number"
                                                    slotProps={{
                                                        ...params.slotProps,
                                                        input: {
                                                            ...params.slotProps?.input,
                                                            startAdornment: (
                                                                <>
                                                                    <InputAdornment position="start">
                                                                        <SearchIcon fontSize="small" color="action" />
                                                                    </InputAdornment>
                                                                    {params.slotProps?.input?.startAdornment}
                                                                </>
                                                            ),
                                                        },
                                                    }}
                                                />
                                            )}
                                        />

                                        <TextField
                                            select
                                            required
                                            fullWidth
                                            label="Access Relationship Type"
                                            value={deviceModal.relationship_type}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, relationship_type: e.target.value }))}
                                        >
                                            <MenuItem value="registered">Registered (Standard Authorized Workstation)</MenuItem>
                                            <MenuItem value="authorized">Authorized (Pre-approved device)</MenuItem>
                                            <MenuItem value="observed">Observed (Detected / Audit Logged)</MenuItem>
                                            <MenuItem value="blocked">Blocked (Unauthorized)</MenuItem>
                                        </TextField>

                                        <TextField
                                            select
                                            fullWidth
                                            label="Assign to PIC (Optional)"
                                            value={deviceModal.user_id || ''}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, user_id: e.target.value }))}
                                            helperText="Associate this device with a specific Person in Charge (PIC)"
                                        >
                                            <MenuItem value="">Shared / General Account Device</MenuItem>
                                            {activePics.map((p) => (
                                                <MenuItem key={p.user_id} value={p.user_id}>
                                                    {p.user?.name} {p.user?.email ? `(${p.user.email})` : ''}
                                                </MenuItem>
                                            ))}
                                        </TextField>
                                    </>
                                ) : (
                                    <Alert severity="info" sx={{ mt: 1 }}>
                                        No active devices found in inventory. Switch to <strong>"+ Register New Device"</strong> to add one.
                                    </Alert>
                                )}
                            </Box>
                        ) : (
                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                <TextField
                                    select
                                    required
                                    fullWidth
                                    label="Device Ownership"
                                    value={deviceModal.ownership_type}
                                    onChange={(e) => setDeviceModal(prev => ({ ...prev, ownership_type: e.target.value }))}
                                >
                                    <MenuItem value="company">Company Owned</MenuItem>
                                    <MenuItem value="personal">Personal / BYOD</MenuItem>
                                    <MenuItem value="other">Other / Shared</MenuItem>
                                </TextField>

                                {deviceModal.ownership_type === 'company' && officeAssets.length > 0 && (
                                    <TextField
                                        select
                                        fullWidth
                                        label="Link Office Fixed Asset (Optional)"
                                        value={deviceModal.fixed_asset_id}
                                        onChange={(e) => setDeviceModal(prev => ({ ...prev, fixed_asset_id: e.target.value }))}
                                        helperText="Optionally links device to physical fixed asset inventory"
                                    >
                                        <MenuItem value="">None / Unassigned</MenuItem>
                                        {officeAssets.map((asset) => (
                                            <MenuItem key={asset.id} value={asset.id}>
                                                {asset.name} (ID: {asset.id})
                                            </MenuItem>
                                        ))}
                                    </TextField>
                                )}

                                <TextField
                                    required
                                    fullWidth
                                    label="Hostname / Computer Name"
                                    placeholder="e.g. LAPTOP-DEV-01, MacBook-Pro-Design"
                                    value={deviceModal.hostname}
                                    onChange={(e) => setDeviceModal(prev => ({ ...prev, hostname: e.target.value }))}
                                />

                                <Grid container spacing={2}>
                                    <Grid item xs={6}>
                                        <TextField
                                            fullWidth
                                            label="Manufacturer"
                                            placeholder="e.g. Apple, Dell, Lenovo"
                                            value={deviceModal.manufacturer}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, manufacturer: e.target.value }))}
                                        />
                                    </Grid>
                                    <Grid item xs={6}>
                                        <TextField
                                            fullWidth
                                            label="Model Name"
                                            placeholder="e.g. ThinkPad T14, MacBook Pro 14"
                                            value={deviceModal.model_name}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, model_name: e.target.value }))}
                                        />
                                    </Grid>
                                </Grid>

                                <Grid container spacing={2}>
                                    <Grid item xs={6}>
                                        <TextField
                                            required
                                            fullWidth
                                            label="Serial Number *"
                                            placeholder="e.g. SN-8849202"
                                            value={deviceModal.serial_number}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, serial_number: e.target.value }))}
                                            helperText="Required and unique hardware serial number"
                                        />
                                    </Grid>
                                    <Grid item xs={6}>
                                        <TextField
                                            fullWidth
                                            label="Operating System"
                                            placeholder="e.g. Windows 11 Pro, macOS Sonoma"
                                            value={deviceModal.operating_system}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, operating_system: e.target.value }))}
                                        />
                                    </Grid>
                                </Grid>

                                <TextField
                                    select
                                    fullWidth
                                    label="Assign to PIC (Optional)"
                                    value={deviceModal.user_id || ''}
                                    onChange={(e) => setDeviceModal(prev => ({ ...prev, user_id: e.target.value }))}
                                    helperText="Associate this new device with a specific Person in Charge (PIC)"
                                >
                                    <MenuItem value="">Shared / General Account Device</MenuItem>
                                    {activePics.map((p) => (
                                        <MenuItem key={p.user_id} value={p.user_id}>
                                            {p.user?.name} {p.user?.email ? `(${p.user.email})` : ''}
                                        </MenuItem>
                                    ))}
                                </TextField>

                                <TextField
                                    fullWidth
                                    multiline
                                    rows={2}
                                    label="Notes / Description"
                                    value={deviceModal.notes}
                                    onChange={(e) => setDeviceModal(prev => ({ ...prev, notes: e.target.value }))}
                                />
                            </Box>
                        )}
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setDeviceModal({ ...deviceModal, open: false })}>Cancel</Button>
                        <Button
                            type="submit"
                            variant="contained"
                            disabled={deviceModal.mode === 'select' && !deviceModal.software_device_id}
                        >
                            {deviceModal.mode === 'select' ? 'Confirm Link' : 'Register & Save'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* GLOBAL USER SELECT MODAL */}
            <UserSelectModal
                open={userPickerOpen}
                onClose={() => setUserPickerOpen(false)}
                onSelect={handleUserPicked}
                title={userPickerTarget === 'pic' ? 'Select Person in Charge (PIC)' : 'Select Review Auditor / Reviewer'}
                subtitle={
                    userPickerTarget === 'pic'
                        ? 'Search employee across departments and branches to assign as PIC for this software account'
                        : 'Search employee to assign as compliance reviewer'
                }
                users={availableUsers}
                departments={departments}
                selectedUserId={userPickerTarget === 'pic' ? assignPicModal.userId : scheduleReviewModal.assignedTo}
            />

            {/* GLOBAL EMAIL SELECT MODAL */}
            <EmailSelectModal
                open={emailPickerOpen}
                onClose={() => setEmailPickerOpen(false)}
                onSelect={(selectedEmail) => {
                    if (emailPickerTarget === 'property') {
                        setEditPropModal(prev => ({ ...prev, value: selectedEmail.id }));
                    } else if (emailPickerTarget === 'linkEmail') {
                        setLinkEmailModal(prev => ({ ...prev, emailId: selectedEmail.id }));
                    }
                    setEmailPickerOpen(false);
                }}
                selectedEmailId={
                    emailPickerTarget === 'property'
                        ? editPropModal.value
                        : linkEmailModal.emailId
                }
                emails={availableEmails}
                departments={departments}
                title={emailPickerTarget === 'property' ? 'Select Referenced Master Email' : 'Select Email to Associate'}
                subtitle="Search and select a corporate email from the central email list"
            />
        </AsideLayout>
    );
}
