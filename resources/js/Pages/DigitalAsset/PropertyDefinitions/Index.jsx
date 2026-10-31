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
    DialogActions,
    FormControlLabel,
    Checkbox,
    IconButton,
    Tooltip
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon } from '@mui/icons-material';

export default function PropertyDefinitionsIndex({
    definitions = [],
    platforms = [],
    supportedDataTypes = [],
    canCreate = false,
    canUpdate = false
}) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [selectedDefinition, setSelectedDefinition] = useState(null);
    const [createOptionsInput, setCreateOptionsInput] = useState('');
    const [editOptionsInput, setEditOptionsInput] = useState('');

    const [createFormData, setCreateFormData] = useState({
        software_platform_id: '',
        code: '',
        label: '',
        data_type: 'boolean',
        description: '',
        is_required: false,
        evidence_required: false,
        requires_approval: false,
        review_on_change: true,
        sort_order: 0,
        is_active: true,
    });

    const [editFormData, setEditFormData] = useState({
        label: '',
        description: '',
        is_required: false,
        evidence_required: false,
        requires_approval: false,
        review_on_change: true,
        sort_order: 0,
        is_active: true,
    });

    const handleCreate = (e) => {
        e.preventDefault();
        const isChoice = createFormData.data_type === 'single_choice' || createFormData.data_type === 'multi_choice';
        const options = isChoice
            ? createOptionsInput.split(',').map(s => s.trim()).filter(Boolean)
            : null;

        router.post('/digital-assets/property-definitions', {
            ...createFormData,
            software_platform_id: createFormData.software_platform_id || null,
            validation_rules: options && options.length > 0 ? { options } : null,
        }, {
            onSuccess: () => {
                setCreateModalOpen(false);
                setCreateOptionsInput('');
                setCreateFormData({
                    software_platform_id: '',
                    code: '',
                    label: '',
                    data_type: 'boolean',
                    description: '',
                    is_required: false,
                    evidence_required: false,
                    requires_approval: false,
                    review_on_change: true,
                    sort_order: 0,
                    is_active: true,
                });
            }
        });
    };

    const openEdit = (def) => {
        setSelectedDefinition(def);
        const isChoice = def.data_type === 'single_choice' || def.data_type === 'multi_choice';
        setEditOptionsInput(isChoice && def.validation_rules?.options ? def.validation_rules.options.join(', ') : '');
        setEditFormData({
            label: def.label || '',
            description: def.description || '',
            is_required: !!def.is_required,
            evidence_required: !!def.evidence_required,
            requires_approval: !!def.requires_approval,
            review_on_change: !!def.review_on_change,
            sort_order: def.sort_order ?? 0,
            is_active: !!def.is_active,
        });
        setEditModalOpen(true);
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!selectedDefinition) return;
        const isChoice = selectedDefinition.data_type === 'single_choice' || selectedDefinition.data_type === 'multi_choice';
        const options = isChoice
            ? editOptionsInput.split(',').map(s => s.trim()).filter(Boolean)
            : null;

        router.put(`/digital-assets/property-definitions/${selectedDefinition.id}`, {
            ...editFormData,
            validation_rules: options && options.length > 0 ? { options } : null,
        }, {
            onSuccess: () => {
                setEditModalOpen(false);
                setSelectedDefinition(null);
            }
        });
    };

    return (
        <AsideLayout title="Security Property Definitions">
            <Head title="Property Definitions - Digital Asset" />

            <Box sx={{ mb: 3, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Box>
                    <Typography variant="h5" sx={{ fontWeight: 700 }}>
                        Security Property Definitions
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Configure extensible platform-specific and global security properties without database migrations.
                    </Typography>
                </Box>
                {canCreate && (
                    <Button
                        variant="contained"
                        startIcon={<AddIcon />}
                        onClick={() => setCreateModalOpen(true)}
                        sx={{ textTransform: 'none', borderRadius: 2 }}
                    >
                        Define Property
                    </Button>
                )}
            </Box>

            <TableContainer component={Paper} sx={{ borderRadius: 2, boxShadow: 1 }}>
                <Table size="small">
                    <TableHead sx={{ bgcolor: 'action.hover' }}>
                        <TableRow>
                            <TableCell sx={{ fontWeight: 700 }}>Scope</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Property Code</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Display Label</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Data Type</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Rules & Compliance Policy</TableCell>
                            <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                            <TableCell sx={{ fontWeight: 700 }} align="right">Actions</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {definitions.length > 0 ? (
                            definitions.map((def) => (
                                <TableRow key={def.id} hover>
                                    <TableCell>
                                        {def.platform ? (
                                            <Chip size="small" color="primary" label={def.platform.name} />
                                        ) : (
                                            <Chip size="small" variant="outlined" label="Global (All Platforms)" />
                                        )}
                                    </TableCell>
                                    <TableCell sx={{ fontFamily: 'monospace', fontWeight: 600 }}>{def.code}</TableCell>
                                    <TableCell>
                                        <Typography variant="body2" sx={{ fontWeight: 600 }}>{def.label}</Typography>
                                        {def.description && (
                                            <Typography variant="caption" color="text.secondary">{def.description}</Typography>
                                        )}
                                    </TableCell>
                                    <TableCell><Chip size="small" label={def.data_type} variant="outlined" /></TableCell>
                                    <TableCell>
                                        <Box sx={{ display: 'flex', gap: 0.5, flexWrap: 'wrap' }}>
                                            {def.is_required && <Chip size="small" label="Required" color="error" variant="outlined" />}
                                            {def.evidence_required && <Chip size="small" label="Evidence Required" color="info" variant="outlined" />}
                                            {def.requires_approval && <Chip size="small" label="Requires Approval" color="warning" variant="outlined" />}
                                            {def.review_on_change && <Chip size="small" label="Review On Change" variant="outlined" />}
                                        </Box>
                                    </TableCell>
                                    <TableCell>
                                        <Chip
                                            size="small"
                                            label={def.is_active ? 'Active' : 'Disabled'}
                                            color={def.is_active ? 'success' : 'default'}
                                        />
                                    </TableCell>
                                    <TableCell align="right">
                                        {canUpdate ? (
                                            <Tooltip title="Edit Property Definition">
                                                <IconButton size="small" color="primary" onClick={() => openEdit(def)}>
                                                    <EditIcon fontSize="small" />
                                                </IconButton>
                                            </Tooltip>
                                        ) : (
                                            <Typography variant="caption" color="text.secondary">—</Typography>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={7} align="center" sx={{ py: 3 }}>
                                    <Typography color="text.secondary">No property definitions configured.</Typography>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Modal: Define Property */}
            <Dialog open={createModalOpen} onClose={() => setCreateModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleCreate}>
                    <DialogTitle sx={{ fontWeight: 700 }}>Create Security Property Definition</DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                select
                                fullWidth
                                label="Applicable Scope"
                                value={createFormData.software_platform_id}
                                onChange={(e) => setCreateFormData({ ...createFormData, software_platform_id: e.target.value })}
                            >
                                <MenuItem value="">Global (All Software Platforms)</MenuItem>
                                {platforms.map((p) => (
                                    <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>
                                ))}
                            </TextField>

                            <TextField
                                required
                                fullWidth
                                label="Property Code (snake_case)"
                                placeholder="e.g. two_factor_enabled, last_password_changed_at"
                                value={createFormData.code}
                                onChange={(e) => setCreateFormData({ ...createFormData, code: e.target.value.toLowerCase().replace(/\s+/g, '_') })}
                            />

                            <TextField
                                required
                                fullWidth
                                label="Display Label"
                                placeholder="e.g. 2FA Enabled, Password Last Changed"
                                value={createFormData.label}
                                onChange={(e) => setCreateFormData({ ...createFormData, label: e.target.value })}
                            />

                            <TextField
                                select
                                fullWidth
                                label="Data Type"
                                value={createFormData.data_type}
                                onChange={(e) => setCreateFormData({ ...createFormData, data_type: e.target.value })}
                            >
                                {supportedDataTypes.map((t) => (
                                    <MenuItem key={t} value={t}>{t}</MenuItem>
                                ))}
                            </TextField>

                            {(createFormData.data_type === 'single_choice' || createFormData.data_type === 'multi_choice') && (
                                <TextField
                                    required
                                    fullWidth
                                    label="Allowed Choices / Options (comma-separated)"
                                    placeholder="e.g. SMS, Authenticator App, Security Key, WhatsApp"
                                    value={createOptionsInput}
                                    onChange={(e) => setCreateOptionsInput(e.target.value)}
                                    helperText="Type the allowed choices separated by commas"
                                />
                            )}

                            <TextField
                                fullWidth
                                multiline
                                rows={2}
                                label="Description / Security Guidelines"
                                value={createFormData.description}
                                onChange={(e) => setCreateFormData({ ...createFormData, description: e.target.value })}
                            />

                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                                <FormControlLabel
                                    control={<Checkbox checked={createFormData.is_required} onChange={(e) => setCreateFormData({ ...createFormData, is_required: e.target.checked })} />}
                                    label="Mandatory Property (Required for compliance review completion)"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={createFormData.evidence_required} onChange={(e) => setCreateFormData({ ...createFormData, evidence_required: e.target.checked })} />}
                                    label="Requires Verified Evidence (Screenshot/Document proof)"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={createFormData.requires_approval} onChange={(e) => setCreateFormData({ ...createFormData, requires_approval: e.target.checked })} />}
                                    label="Requires Authorized Approval before applying changes"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={createFormData.review_on_change} onChange={(e) => setCreateFormData({ ...createFormData, review_on_change: e.target.checked })} />}
                                    label="Trigger Review on Change"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={createFormData.is_active} onChange={(e) => setCreateFormData({ ...createFormData, is_active: e.target.checked })} />}
                                    label="Active"
                                />
                            </Box>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setCreateModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained">Create Definition</Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Modal: Edit Property Definition */}
            <Dialog open={editModalOpen} onClose={() => setEditModalOpen(false)} maxWidth="sm" fullWidth>
                <form onSubmit={handleUpdate}>
                    <DialogTitle sx={{ fontWeight: 700 }}>
                        Edit Property Definition: {selectedDefinition?.code}
                    </DialogTitle>
                    <DialogContent dividers>
                        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                            <TextField
                                disabled
                                fullWidth
                                label="Property Code"
                                value={selectedDefinition?.code || ''}
                                helperText="Property code and data type cannot be changed once defined"
                            />

                            <TextField
                                disabled
                                fullWidth
                                label="Data Type"
                                value={selectedDefinition?.data_type || ''}
                            />

                            {(selectedDefinition?.data_type === 'single_choice' || selectedDefinition?.data_type === 'multi_choice') && (
                                <TextField
                                    fullWidth
                                    label="Allowed Choices / Options (comma-separated)"
                                    placeholder="e.g. SMS, Authenticator App, Security Key, WhatsApp"
                                    value={editOptionsInput}
                                    onChange={(e) => setEditOptionsInput(e.target.value)}
                                    helperText="Type the allowed choices separated by commas"
                                />
                            )}

                            <TextField
                                required
                                fullWidth
                                label="Display Label"
                                value={editFormData.label}
                                onChange={(e) => setEditFormData({ ...editFormData, label: e.target.value })}
                            />

                            <TextField
                                fullWidth
                                multiline
                                rows={2}
                                label="Description / Security Guidelines"
                                value={editFormData.description}
                                onChange={(e) => setEditFormData({ ...editFormData, description: e.target.value })}
                            />

                            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.5 }}>
                                <FormControlLabel
                                    control={<Checkbox checked={editFormData.is_required} onChange={(e) => setEditFormData({ ...editFormData, is_required: e.target.checked })} />}
                                    label="Mandatory Property (Required for compliance review completion)"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={editFormData.evidence_required} onChange={(e) => setEditFormData({ ...editFormData, evidence_required: e.target.checked })} />}
                                    label="Requires Verified Evidence (Screenshot/Document proof)"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={editFormData.requires_approval} onChange={(e) => setEditFormData({ ...editFormData, requires_approval: e.target.checked })} />}
                                    label="Requires Authorized Approval before applying changes"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={editFormData.review_on_change} onChange={(e) => setEditFormData({ ...editFormData, review_on_change: e.target.checked })} />}
                                    label="Trigger Review on Change"
                                />
                                <FormControlLabel
                                    control={<Checkbox checked={editFormData.is_active} onChange={(e) => setEditFormData({ ...editFormData, is_active: e.target.checked })} />}
                                    label="Active"
                                />
                            </Box>
                        </Box>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={() => setEditModalOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="contained" color="primary">Save Changes</Button>
                    </DialogActions>
                </form>
            </Dialog>
        </AsideLayout>
    );
}
