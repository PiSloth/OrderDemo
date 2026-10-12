import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import UserSelectModal from '@/components/IT/UserSelectModal';
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
    Alert
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
    Cancel as CancelIcon
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

    // Modals
    const [editPropModal, setEditPropModal] = useState({ open: false, def: null, value: '', reason: '' });
    const [assignPicModal, setAssignPicModal] = useState({ open: false, userId: '', reason: '', userName: '' });
    const [userPickerOpen, setUserPickerOpen] = useState(false);
    const [userPickerTarget, setUserPickerTarget] = useState('pic'); // 'pic' or 'review'
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
        relationship_type: 'registered',
        // Create new device fields
        ownership_type: 'company',
        fixed_asset_id: '',
        hostname: '',
        model_name: '',
        manufacturer: '',
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
        }, {
            onSuccess: () => setAssignPicModal({ open: false, userId: '', reason: '', userName: '' }),
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
        router.post(`/digital-assets/reviews/${completeReviewModal.review.id}/complete`, {
            result: completeReviewModal.result,
            notes: completeReviewModal.notes,
        }, {
            onSuccess: () => setCompleteReviewModal({ open: false, review: null, result: 'compliant', notes: '' }),
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
            }, {
                onSuccess: () => setDeviceModal(prev => ({ ...prev, open: false })),
            });
        } else {
            // Create device first, then link
            router.post('/digital-assets/devices', {
                ownership_type: deviceModal.ownership_type,
                fixed_asset_id: deviceModal.fixed_asset_id || null,
                hostname: deviceModal.hostname,
                model_name: deviceModal.model_name,
                manufacturer: deviceModal.manufacturer,
                operating_system: deviceModal.operating_system,
                device_status: deviceModal.device_status,
                notes: deviceModal.notes,
            }, {
                onSuccess: () => {
                    // Refresh and close, or link will be done on redirect
                    setDeviceModal(prev => ({ ...prev, open: false }));
                }
            });
        }
    };

    const handleVerifyDeviceLink = (linkId) => {
        router.post(`/digital-assets/account-devices/${linkId}/verify`, {}, { preserveScroll: true });
    };

    const currentProps = account.properties || {};

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
                <Tabs
                    value={currentTab}
                    onChange={(e, val) => setCurrentTab(val)}
                    variant="scrollable"
                    scrollButtons="auto"
                    sx={{ borderBottom: 1, borderColor: 'divider', px: 2 }}
                >
                    <Tab icon={<SecurityIcon />} iconPosition="start" label="Security Properties" sx={{ textTransform: 'none' }} />
                    <Tab icon={<PersonIcon />} iconPosition="start" label={`PICs (${(account.active_pics || []).length})`} sx={{ textTransform: 'none' }} />
                    <Tab icon={<EvidenceIcon />} iconPosition="start" label={`Evidence (${(account.evidence || []).length})`} sx={{ textTransform: 'none' }} />
                    <Tab icon={<ReviewIcon />} iconPosition="start" label={`Reviews (${(account.reviews || []).length})`} sx={{ textTransform: 'none' }} />
                    <Tab icon={<DevicesIcon />} iconPosition="start" label={`Devices (${(account.account_devices || []).length})`} sx={{ textTransform: 'none' }} />
                    <Tab icon={<HistoryIcon />} iconPosition="start" label="Audit Trail" sx={{ textTransform: 'none' }} />
                </Tabs>

                <Box sx={{ p: 3 }}>
                    {/* TAB 0: SECURITY PROPERTIES */}
                    {currentTab === 0 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Security Properties Catalog
                                </Typography>
                                <Button
                                    variant="outlined"
                                    size="small"
                                    onClick={() => setLinkEmailModal({ ...linkEmailModal, open: true })}
                                    sx={{ textTransform: 'none' }}
                                >
                                    Associate Email Address
                                </Button>
                            </Box>

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
                                            <TableCell sx={{ fontWeight: 700 }}>Property Code</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Label / Description</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Type</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Current Value</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Policy</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {definitions.map((def) => {
                                            const val = currentProps[def.code];
                                            const pending = pendingChanges.find(h => h.property_definition_id === def.id);

                                            return (
                                                <TableRow key={def.id} hover>
                                                    <TableCell sx={{ fontFamily: 'monospace', fontWeight: 600 }}>{def.code}</TableCell>
                                                    <TableCell>
                                                        <Typography variant="body2" sx={{ fontWeight: 600 }}>{def.label}</Typography>
                                                        {def.description && (
                                                            <Typography variant="caption" color="text.secondary">{def.description}</Typography>
                                                        )}
                                                    </TableCell>
                                                    <TableCell><Chip size="small" label={def.data_type} variant="outlined" /></TableCell>
                                                    <TableCell>
                                                        {pending ? (
                                                            <Box>
                                                                <Chip size="small" color="warning" label={`Pending: ${JSON.stringify(pending.new_value?.value)}`} />
                                                                <Box sx={{ mt: 0.5, display: 'flex', gap: 0.5 }}>
                                                                    <Button size="small" color="success" onClick={() => handleApproveProperty(pending.id)}>Approve</Button>
                                                                    <Button size="small" color="error" onClick={() => handleRejectProperty(pending.id)}>Reject</Button>
                                                                </Box>
                                                            </Box>
                                                        ) : val !== undefined && val !== null ? (
                                                            typeof val === 'boolean' ? (
                                                                val ? <Chip size="small" color="success" label="Enabled / Yes" /> : <Chip size="small" color="default" label="Disabled / No" />
                                                            ) : typeof val === 'object' ? (
                                                                JSON.stringify(val)
                                                            ) : (
                                                                String(val)
                                                            )
                                                        ) : (
                                                            <Typography variant="caption" color="text.secondary">Not Set</Typography>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Box sx={{ display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                                                            {def.is_required && <Chip size="small" label="Required" color="error" variant="outlined" />}
                                                            {def.evidence_required && <Chip size="small" label="Evidence Req" color="info" variant="outlined" />}
                                                            {def.requires_approval && <Chip size="small" label="Approval Req" color="warning" variant="outlined" />}
                                                        </Box>
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        <Button
                                                            size="small"
                                                            onClick={() => setEditPropModal({
                                                                open: true,
                                                                def,
                                                                value: val !== undefined && val !== null ? val : '',
                                                                reason: ''
                                                            })}
                                                            sx={{ textTransform: 'none' }}
                                                        >
                                                            Edit
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </Box>
                    )}

                    {/* TAB 1: PERSONS IN CHARGE (PICS) */}
                    {currentTab === 1 && (
                        <Box>
                            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                                <Typography variant="h6" sx={{ fontWeight: 700 }}>
                                    Active Persons in Charge (PICs)
                                </Typography>
                                <Button
                                    variant="contained"
                                    size="small"
                                    startIcon={<AddIcon />}
                                    onClick={() => setAssignPicModal({ open: true, userId: '', reason: '' })}
                                    sx={{ textTransform: 'none' }}
                                >
                                    Assign PIC
                                </Button>
                            </Box>

                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, mb: 4 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned User</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Email</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Assigned At</TableCell>
                                            <TableCell align="right" sx={{ fontWeight: 700 }}>Action</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {account.active_pics && account.active_pics.length > 0 ? (
                                            account.active_pics.map((pic) => (
                                                <TableRow key={pic.id} hover>
                                                    <TableCell sx={{ fontWeight: 600 }}>{pic.user?.name}</TableCell>
                                                    <TableCell>{pic.user?.email}</TableCell>
                                                    <TableCell><Chip size="small" color="success" label="Active" /></TableCell>
                                                    <TableCell>{new Date(pic.assigned_at).toLocaleString()}</TableCell>
                                                    <TableCell align="right">
                                                        <Button
                                                            size="small"
                                                            color="error"
                                                            onClick={() => setRevokePicModal({ open: true, pic, reason: '' })}
                                                            sx={{ textTransform: 'none' }}
                                                        >
                                                            Revoke
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={5} align="center" sx={{ py: 3 }}>
                                                    <Typography color="text.secondary">No active PICs assigned.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>

                            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                                Append-Only PIC History Log
                            </Typography>
                            <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                                <Table size="small">
                                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                                        <TableRow>
                                            <TableCell sx={{ fontWeight: 700 }}>Occurred At</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Action</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>User</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Performed By</TableCell>
                                            <TableCell sx={{ fontWeight: 700 }}>Reason / Notes</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {account.pic_histories && account.pic_histories.length > 0 ? (
                                            account.pic_histories.map((hist) => (
                                                <TableRow key={hist.id}>
                                                    <TableCell>{new Date(hist.occurred_at).toLocaleString()}</TableCell>
                                                    <TableCell>
                                                        <Chip
                                                            size="small"
                                                            label={hist.action}
                                                            color={hist.action === 'assigned' ? 'success' : hist.action === 'revoked' ? 'error' : 'info'}
                                                        />
                                                    </TableCell>
                                                    <TableCell>{hist.user?.name}</TableCell>
                                                    <TableCell>{hist.performed_by_user?.name}</TableCell>
                                                    <TableCell>{hist.reason || '-'}</TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={5} align="center" sx={{ py: 2 }}>
                                                    <Typography color="text.secondary">No history events recorded.</Typography>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </TableContainer>
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
                                                    <TableCell><Chip size="small" label={ad.device?.ownership_type} /></TableCell>
                                                    <TableCell>{ad.device?.fixed_asset?.name || 'Personal Device'}</TableCell>
                                                    <TableCell><Chip size="small" color="primary" variant="outlined" label={ad.relationship_type} /></TableCell>
                                                    <TableCell>
                                                        <Chip
                                                            size="small"
                                                            label={ad.is_verified ? 'Authorized' : 'Pending Verification'}
                                                            color={ad.is_verified ? 'success' : 'warning'}
                                                        />
                                                    </TableCell>
                                                    <TableCell>{ad.last_seen_at ? new Date(ad.last_seen_at).toLocaleDateString() : '-'}</TableCell>
                                                    <TableCell align="right">
                                                        {!ad.is_verified && (
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
                                                <TableCell colSpan={7} align="center" sx={{ py: 3 }}>
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
                                                    <TableCell>{log.ip_address}</TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={5} align="center" sx={{ py: 3 }}>
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
                                    <TextField
                                        select
                                        fullWidth
                                        label="Value"
                                        value={editPropModal.value}
                                        onChange={(e) => setEditPropModal({ ...editPropModal, value: e.target.value === 'true' })}
                                    >
                                        <MenuItem value="true">Enabled / Yes</MenuItem>
                                        <MenuItem value="false">Disabled / No</MenuItem>
                                    </TextField>
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
                                ) : editPropModal.def.data_type === 'email_reference' ? (
                                    <TextField
                                        select
                                        fullWidth
                                        label="Referenced Email Master"
                                        value={editPropModal.value}
                                        onChange={(e) => setEditPropModal({ ...editPropModal, value: e.target.value })}
                                    >
                                        {availableEmails.map((em) => (
                                            <MenuItem key={em.id} value={em.id}>{em.email} ({em.user_name})</MenuItem>
                                        ))}
                                    </TextField>
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
            <Dialog open={assignPicModal.open} onClose={() => setAssignPicModal({ ...assignPicModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleAssignPic}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Assign Person in Charge (PIC)</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                required
                                fullWidth
                                label="Select Employee (PIC)"
                                placeholder="Click to search and select employee..."
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
                                helperText="Click Browse to search employees across departments and branches"
                            />
                            <TextField
                                fullWidth
                                label="Assignment Reason / Purpose"
                                value={assignPicModal.reason}
                                onChange={(e) => setAssignPicModal({ ...assignPicModal, reason: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setAssignPicModal({ ...assignPicModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Confirm Assignment</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* MODAL: REVOKE PIC */}
            <Dialog open={revokePicModal.open} onClose={() => setRevokePicModal({ ...revokePicModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleRevokePic}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Revoke PIC Assignment</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <Typography variant="body2">
                                Are you sure you want to revoke PIC responsibility for <strong>{revokePicModal.pic?.user?.name}</strong>?
                            </Typography>
                            <TextField
                                required
                                fullWidth
                                label="Revocation Reason"
                                placeholder="e.g. Role transition, project handover..."
                                value={revokePicModal.reason}
                                onChange={(e) => setRevokePicModal({ ...revokePicModal, reason: e.target.value })}
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setRevokePicModal({ ...revokePicModal, open: false })}>Cancel</Button>
                        <Button type="submit" color="error" variant="contained">Revoke PIC</Button>
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
            <Dialog open={completeReviewModal.open} onClose={() => setCompleteReviewModal({ ...completeReviewModal, open: false })} maxWidth="sm" fullWidth>
                <form onSubmit={handleCompleteReview}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Complete Compliance Review</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Compliance Result"
                                value={completeReviewModal.result}
                                onChange={(e) => setCompleteReviewModal({ ...completeReviewModal, result: e.target.value })}
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
                            />
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCompleteReviewModal({ ...completeReviewModal, open: false })}>Cancel</Button>
                        <Button type="submit" variant="contained">Finalize Review</Button>
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
                                select
                                required
                                fullWidth
                                label="Email Master"
                                value={linkEmailModal.emailId}
                                onChange={(e) => setLinkEmailModal({ ...linkEmailModal, emailId: e.target.value })}
                            >
                                {availableEmails.map((em) => (
                                    <MenuItem key={em.id} value={em.id}>{em.email} ({em.user_name})</MenuItem>
                                ))}
                            </TextField>
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
                                        <TextField
                                            select
                                            required
                                            fullWidth
                                            label="Select Hardware Device"
                                            value={deviceModal.software_device_id}
                                            onChange={(e) => setDeviceModal(prev => ({ ...prev, software_device_id: e.target.value }))}
                                        >
                                            {availableDevices.map((d) => (
                                                <MenuItem key={d.id} value={d.id}>
                                                    {d.hostname || d.model_name || 'Device'} ({d.ownership_type})
                                                    {d.fixed_asset?.name ? ` — ${d.fixed_asset.name}` : ''}
                                                </MenuItem>
                                            ))}
                                        </TextField>

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

                                <TextField
                                    fullWidth
                                    label="Operating System"
                                    placeholder="e.g. Windows 11 Pro, macOS Sonoma, Ubuntu 24.04"
                                    value={deviceModal.operating_system}
                                    onChange={(e) => setDeviceModal(prev => ({ ...prev, operating_system: e.target.value }))}
                                />

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
        </AsideLayout>
    );
}
