import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
} from '@mui/material';
import {
    Settings as SettingsIcon,
    AccessTime as AccessTimeIcon,
    Add as AddIcon,
    Edit as EditIcon,
    Delete as DeleteIcon,
    ArrowUpward as ArrowUpwardIcon,
    ArrowDownward as ArrowDownwardIcon,
    PriorityHigh as PriorityHighIcon,
    Star as StarIcon,
    Rule as RuleIcon,
    Psychology as PsychologyIcon,
} from '@mui/icons-material';
import toast from 'react-hot-toast';

export default function Configure({ categories = [], priorities = [], importanceLevels = [], statuses = [], rootCauses = [] }) {
    const { errors = {} } = usePage().props;

    // Section ordering state to allow swapping CRUD sections on the page
    const [sectionOrder, setSectionOrder] = useState(['priority', 'importance', 'status', 'rootCause']);

    // Modal state management
    const [modalState, setModalState] = useState({
        open: false,
        type: null, // 'priority' | 'importance' | 'status' | 'rootCause'
        mode: 'create', // 'create' | 'edit' | 'delete'
        item: null,
    });

    // Form inputs state
    const [formData, setFormData] = useState({
        name: '',
        level: '',
        code: '',
        clock_type: 'office_hours',
        target_hours: '',
        fail_points: 1,
    });

    // Handle swap section order
    const handleSwapSection = (index, direction) => {
        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        if (targetIndex < 0 || targetIndex >= sectionOrder.length) return;

        const newOrder = [...sectionOrder];
        const temp = newOrder[index];
        newOrder[index] = newOrder[targetIndex];
        newOrder[targetIndex] = temp;
        setSectionOrder(newOrder);
        toast.success(`Section moved ${direction}!`);
    };

    // Modal openers
    const openCreateModal = (type) => {
        setFormData({ 
            name: '', 
            level: '', 
            code: '',
            clock_type: type === 'priority' ? 'office_hours' : 'office_hours',
            target_hours: type === 'priority' ? 8.5 : '',
            fail_points: 1,
        });
        setModalState({ open: true, type, mode: 'create', item: null });
    };

    const openEditModal = (type, item) => {
        const settings = item.settings || {};
        setFormData({
            name: item.name || '',
            level: item.level !== undefined ? item.level : '',
            code: item.code || '',
            clock_type: settings.clock_type || (item.level === 1 ? 'continuous_24h' : (item.level === 4 ? 'manual_schedule' : 'office_hours')),
            target_hours: settings.target_hours !== undefined && settings.target_hours !== null ? settings.target_hours : (item.level === 1 ? 24 : (item.level === 2 ? 8.5 : (item.level === 3 ? 17 : ''))),
            fail_points: settings.fail_points !== undefined ? settings.fail_points : (item.level === 1 ? 10 : (item.level === 2 ? 5 : 1)),
        });
        setModalState({ open: true, type, mode: 'edit', item });
    };

    const openDeleteModal = (type, item) => {
        setModalState({ open: true, type, mode: 'delete', item });
    };

    const closeModal = () => {
        setModalState({ open: false, type: null, mode: 'create', item: null });
        setFormData({ name: '', level: '', code: '', clock_type: 'office_hours', target_hours: '', fail_points: 1 });
    };

    // Submit handler for Create & Edit
    const handleSubmit = (e) => {
        e.preventDefault();
        const { type, mode, item } = modalState;

        let url = '';
        let payload = {};

        if (type === 'priority') {
            url = mode === 'create' 
                ? '/operations/it/issues/configure/priorities' 
                : `/operations/it/issues/configure/priorities/${item.id}`;
            payload = { 
                name: formData.name, 
                level: formData.level,
                clock_type: formData.clock_type,
                target_hours: formData.clock_type === 'manual_schedule' ? null : formData.target_hours,
                fail_points: formData.fail_points,
            };
        } else if (type === 'importance') {
            url = mode === 'create' 
                ? '/operations/it/issues/configure/importance' 
                : `/operations/it/issues/configure/importance/${item.id}`;
            payload = { name: formData.name, level: formData.level };
        } else if (type === 'status') {
            url = mode === 'create' 
                ? '/operations/it/issues/configure/statuses' 
                : `/operations/it/issues/configure/statuses/${item.id}`;
            payload = { name: formData.name, code: formData.code };
        } else if (type === 'rootCause') {
            url = mode === 'create' 
                ? '/operations/it/issues/configure/root-causes' 
                : `/operations/it/issues/configure/root-causes/${item.id}`;
            payload = { name: formData.name };
        }

        if (mode === 'create') {
            router.post(url, payload, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success(`${type.toUpperCase()} created successfully!`);
                    closeModal();
                },
                onError: (errs) => {
                    toast.error(errs.error || 'Failed to save item. Check validation inputs.');
                }
            });
        } else {
            router.patch(url, payload, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success(`${type.toUpperCase()} updated successfully!`);
                    closeModal();
                },
                onError: (errs) => {
                    toast.error(errs.error || 'Failed to update item.');
                }
            });
        }
    };

    // Submit handler for Delete
    const handleDelete = () => {
        const { type, item } = modalState;
        let url = '';

        if (type === 'priority') url = `/operations/it/issues/configure/priorities/${item.id}`;
        else if (type === 'importance') url = `/operations/it/issues/configure/importance/${item.id}`;
        else if (type === 'status') url = `/operations/it/issues/configure/statuses/${item.id}`;
        else if (type === 'rootCause') url = `/operations/it/issues/configure/root-causes/${item.id}`;

        router.delete(url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('Item deleted successfully!');
                closeModal();
            },
            onError: (errs) => {
                toast.error(errs.error || 'Cannot delete item as it is in use.');
            }
        });
    };

    // Row swap handler
    const handleSwapRows = (type, id1, id2) => {
        let url = '';
        if (type === 'priority') url = '/operations/it/issues/configure/priorities/swap';
        else if (type === 'importance') url = '/operations/it/issues/configure/importance/swap';
        else if (type === 'status') url = '/operations/it/issues/configure/statuses/swap';
        else if (type === 'rootCause') url = '/operations/it/issues/configure/root-causes/swap';

        router.post(url, { id1, id2 }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => toast.success('Items swapped successfully!'),
            onError: () => toast.error('Failed to swap items.')
        });
    };

    // Section title & icon configuration
    const getSectionDetails = (type) => {
        switch (type) {
            case 'priority':
                return {
                    title: 'Priority Levels & Dynamic SLA Rules Configuration',
                    icon: <PriorityHighIcon color="error" />,
                    color: '#ef4444',
                    description: 'Level 1 = Critical 24/7 Clock. Level 4 = Schedule (Manual Due Date).'
                };
            case 'importance':
                return {
                    title: 'Important Level (Severity) Configuration',
                    icon: <StarIcon color="warning" />,
                    color: '#f59e0b',
                    description: 'Configure importance & impact levels for IT issues.'
                };
            case 'status':
                return {
                    title: 'Issue Status Configuration',
                    icon: <RuleIcon color="primary" />,
                    color: '#3b82f6',
                    description: 'Manage status workflow pipeline codes and labels.'
                };
            case 'rootCause':
                return {
                    title: 'Root Cause Catalog Configuration',
                    icon: <PsychologyIcon color="secondary" />,
                    color: '#8b5cf6',
                    description: 'Configure standardized root cause reasons for issue resolution logs.'
                };
            default:
                return { title: '', icon: null, color: '#333', description: '' };
        }
    };

    // Render individual section card
    const renderSectionCard = (type, index) => {
        const details = getSectionDetails(type);
        const isFirst = index === 0;
        const isLast = index === sectionOrder.length - 1;

        return (
            <Paper 
                key={type} 
                elevation={2} 
                sx={{ 
                    p: 3, 
                    mb: 4, 
                    borderRadius: 3, 
                    borderLeft: `5px solid ${details.color}`,
                    bgcolor: 'background.paper'
                }}
            >
                {/* Section Header with Title Swap Controls */}
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                        {details.icon}
                        <Box>
                            <Typography variant="h6" fontWeight="bold">
                                {details.title}
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                                {details.description}
                            </Typography>
                        </Box>
                    </Box>

                    <Stack direction="row" spacing={1} alignItems="center">
                        {/* Section Title Swap Buttons */}
                        <Box sx={{ bgcolor: 'action.hover', borderRadius: 2, p: 0.5, display: 'flex', alignItems: 'center', gap: 0.5 }}>
                            <Tooltip title="Swap section position up">
                                <span>
                                    <IconButton 
                                        size="small" 
                                        onClick={() => handleSwapSection(index, 'up')} 
                                        disabled={isFirst}
                                        color="primary"
                                    >
                                        <ArrowUpwardIcon fontSize="small" />
                                    </IconButton>
                                </span>
                            </Tooltip>
                            <Tooltip title="Swap section position down">
                                <span>
                                    <IconButton 
                                        size="small" 
                                        onClick={() => handleSwapSection(index, 'down')} 
                                        disabled={isLast}
                                        color="primary"
                                    >
                                        <ArrowDownwardIcon fontSize="small" />
                                    </IconButton>
                                </span>
                            </Tooltip>
                        </Box>

                        <Button 
                            variant="contained" 
                            size="small" 
                            startIcon={<AddIcon />} 
                            onClick={() => openCreateModal(type)}
                            sx={{ borderRadius: 2, textTransform: 'none', fontWeight: 'bold' }}
                        >
                            Add New
                        </Button>
                    </Stack>
                </Box>

                {/* Content Table for Priority section with dynamic JSON settings */}
                {type === 'priority' && (
                    <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                        <Table size="small">
                            <TableHead sx={{ bgcolor: 'grey.100' }}>
                                <TableRow>
                                    <TableCell fontWeight="bold">Swap</TableCell>
                                    <TableCell fontWeight="bold">Level / Rank</TableCell>
                                    <TableCell fontWeight="bold">Priority Name</TableCell>
                                    <TableCell fontWeight="bold">Clock Type</TableCell>
                                    <TableCell fontWeight="bold">Target Hours</TableCell>
                                    <TableCell fontWeight="bold">Fail Points</TableCell>
                                    <TableCell fontWeight="bold" align="right">Actions</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {priorities.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} align="center">No priority levels configured.</TableCell></TableRow>
                                ) : (
                                    priorities.map((item, rowIndex) => {
                                        const settings = item.settings || {};
                                        const clockType = settings.clock_type || (item.level === 1 ? 'continuous_24h' : (item.level === 4 ? 'manual_schedule' : 'office_hours'));
                                        const targetHours = settings.target_hours !== undefined && settings.target_hours !== null ? `${settings.target_hours} hrs` : (clockType === 'manual_schedule' ? 'Manual Schedule' : (item.level === 1 ? '24 hrs' : (item.level === 2 ? '8.5 hrs' : (item.level === 3 ? '17 hrs' : 'Manual Schedule'))));
                                        const failPoints = settings.fail_points !== undefined ? settings.fail_points : (item.level === 1 ? 10 : (item.level === 2 ? 5 : 1));

                                        return (
                                            <TableRow key={item.id} hover>
                                                <TableCell width="100">
                                                    <Stack direction="row" spacing={0.5}>
                                                        <IconButton 
                                                            size="small" 
                                                            disabled={rowIndex === 0} 
                                                            onClick={() => handleSwapRows('priority', item.id, priorities[rowIndex - 1].id)}
                                                        >
                                                            <ArrowUpwardIcon fontSize="small" />
                                                        </IconButton>
                                                        <IconButton 
                                                            size="small" 
                                                            disabled={rowIndex === priorities.length - 1} 
                                                            onClick={() => handleSwapRows('priority', item.id, priorities[rowIndex + 1].id)}
                                                        >
                                                            <ArrowDownwardIcon fontSize="small" />
                                                        </IconButton>
                                                    </Stack>
                                                </TableCell>
                                                <TableCell>
                                                    <Chip 
                                                        label={`Level ${item.level}`} 
                                                        color={item.level === 1 ? 'error' : item.level === 2 ? 'warning' : item.level === 3 ? 'info' : 'default'} 
                                                        size="small" 
                                                        sx={{ fontWeight: 'bold' }} 
                                                    />
                                                </TableCell>
                                                <TableCell fontWeight="bold">{item.name}</TableCell>
                                                <TableCell>
                                                    {clockType === 'continuous_24h' && <Chip label="Continuous 24/7 Clock" color="error" variant="outlined" size="small" />}
                                                    {clockType === 'office_hours' && <Chip label="Office Hours Schedule" color="primary" variant="outlined" size="small" />}
                                                    {clockType === 'manual_schedule' && <Chip label="Manual Schedule" color="default" variant="outlined" size="small" />}
                                                </TableCell>
                                                <TableCell fontWeight="bold">{targetHours}</TableCell>
                                                <TableCell>{failPoints} Points</TableCell>
                                                <TableCell align="right">
                                                    <IconButton size="small" color="primary" onClick={() => openEditModal('priority', item)}>
                                                        <EditIcon fontSize="small" />
                                                    </IconButton>
                                                    <IconButton size="small" color="error" onClick={() => openDeleteModal('priority', item)}>
                                                        <DeleteIcon fontSize="small" />
                                                    </IconButton>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                )}

                {type === 'importance' && (
                    <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                        <Table size="small">
                            <TableHead sx={{ bgcolor: 'grey.100' }}>
                                <TableRow>
                                    <TableCell fontWeight="bold">Swap</TableCell>
                                    <TableCell fontWeight="bold">ID</TableCell>
                                    <TableCell fontWeight="bold">Importance Name</TableCell>
                                    <TableCell fontWeight="bold">Level / Rank</TableCell>
                                    <TableCell fontWeight="bold" align="right">Actions</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {importanceLevels.length === 0 ? (
                                    <TableRow><TableCell colSpan={5} align="center">No importance levels configured.</TableCell></TableRow>
                                ) : (
                                    importanceLevels.map((item, rowIndex) => (
                                        <TableRow key={item.id} hover>
                                            <TableCell width="110">
                                                <Stack direction="row" spacing={0.5}>
                                                    <IconButton 
                                                        size="small" 
                                                        disabled={rowIndex === 0} 
                                                        onClick={() => handleSwapRows('importance', item.id, importanceLevels[rowIndex - 1].id)}
                                                    >
                                                        <ArrowUpwardIcon fontSize="small" />
                                                    </IconButton>
                                                    <IconButton 
                                                        size="small" 
                                                        disabled={rowIndex === importanceLevels.length - 1} 
                                                        onClick={() => handleSwapRows('importance', item.id, importanceLevels[rowIndex + 1].id)}
                                                    >
                                                        <ArrowDownwardIcon fontSize="small" />
                                                    </IconButton>
                                                </Stack>
                                            </TableCell>
                                            <TableCell>#{item.id}</TableCell>
                                            <TableCell fontWeight="bold">{item.name}</TableCell>
                                            <TableCell>Level {item.level}</TableCell>
                                            <TableCell align="right">
                                                <IconButton size="small" color="primary" onClick={() => openEditModal('importance', item)}>
                                                    <EditIcon fontSize="small" />
                                                </IconButton>
                                                <IconButton size="small" color="error" onClick={() => openDeleteModal('importance', item)}>
                                                    <DeleteIcon fontSize="small" />
                                                </IconButton>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                )}

                {type === 'status' && (
                    <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                        <Table size="small">
                            <TableHead sx={{ bgcolor: 'grey.100' }}>
                                <TableRow>
                                    <TableCell fontWeight="bold">Swap</TableCell>
                                    <TableCell fontWeight="bold">ID</TableCell>
                                    <TableCell fontWeight="bold">Status Name</TableCell>
                                    <TableCell fontWeight="bold">System Code</TableCell>
                                    <TableCell fontWeight="bold" align="right">Actions</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {statuses.length === 0 ? (
                                    <TableRow><TableCell colSpan={5} align="center">No status codes configured.</TableCell></TableRow>
                                ) : (
                                    statuses.map((item, rowIndex) => (
                                        <TableRow key={item.id} hover>
                                            <TableCell width="110">
                                                <Stack direction="row" spacing={0.5}>
                                                    <IconButton 
                                                        size="small" 
                                                        disabled={rowIndex === 0} 
                                                        onClick={() => handleSwapRows('status', item.id, statuses[rowIndex - 1].id)}
                                                    >
                                                        <ArrowUpwardIcon fontSize="small" />
                                                    </IconButton>
                                                    <IconButton 
                                                        size="small" 
                                                        disabled={rowIndex === statuses.length - 1} 
                                                        onClick={() => handleSwapRows('status', item.id, statuses[rowIndex + 1].id)}
                                                    >
                                                        <ArrowDownwardIcon fontSize="small" />
                                                    </IconButton>
                                                </Stack>
                                            </TableCell>
                                            <TableCell>#{item.id}</TableCell>
                                            <TableCell fontWeight="bold">{item.name}</TableCell>
                                            <TableCell><Chip label={item.code} size="small" color="default" variant="outlined" /></TableCell>
                                            <TableCell align="right">
                                                <IconButton size="small" color="primary" onClick={() => openEditModal('status', item)}>
                                                    <EditIcon fontSize="small" />
                                                </IconButton>
                                                <IconButton size="small" color="error" onClick={() => openDeleteModal('status', item)}>
                                                    <DeleteIcon fontSize="small" />
                                                </IconButton>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                )}

                {type === 'rootCause' && (
                    <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                        <Table size="small">
                            <TableHead sx={{ bgcolor: 'grey.100' }}>
                                <TableRow>
                                    <TableCell fontWeight="bold">Swap</TableCell>
                                    <TableCell fontWeight="bold">ID</TableCell>
                                    <TableCell fontWeight="bold">Root Cause Description</TableCell>
                                    <TableCell fontWeight="bold" align="right">Actions</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {rootCauses.length === 0 ? (
                                    <TableRow><TableCell colSpan={4} align="center">No root causes configured.</TableCell></TableRow>
                                ) : (
                                    rootCauses.map((item, rowIndex) => (
                                        <TableRow key={item.id} hover>
                                            <TableCell width="110">
                                                <Stack direction="row" spacing={0.5}>
                                                    <IconButton 
                                                        size="small" 
                                                        disabled={rowIndex === 0} 
                                                        onClick={() => handleSwapRows('rootCause', item.id, rootCauses[rowIndex - 1].id)}
                                                    >
                                                        <ArrowUpwardIcon fontSize="small" />
                                                    </IconButton>
                                                    <IconButton 
                                                        size="small" 
                                                        disabled={rowIndex === rootCauses.length - 1} 
                                                        onClick={() => handleSwapRows('rootCause', item.id, rootCauses[rowIndex + 1].id)}
                                                    >
                                                        <ArrowDownwardIcon fontSize="small" />
                                                    </IconButton>
                                                </Stack>
                                            </TableCell>
                                            <TableCell>#{item.id}</TableCell>
                                            <TableCell fontWeight="bold">{item.name}</TableCell>
                                            <TableCell align="right">
                                                <IconButton size="small" color="primary" onClick={() => openEditModal('rootCause', item)}>
                                                    <EditIcon fontSize="small" />
                                                </IconButton>
                                                <IconButton size="small" color="error" onClick={() => openDeleteModal('rootCause', item)}>
                                                    <DeleteIcon fontSize="small" />
                                                </IconButton>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                )}
            </Paper>
        );
    };

    return (
        <AsideLayout title="IT Issues Configuration & SLA Rules">
            <Head title="IT Issue Dynamic Configuration" />

            <Box sx={{ p: { xs: 2, md: 3 } }}>
                {/* Global Error Banner */}
                {errors.error && (
                    <Alert severity="error" sx={{ mb: 3, borderRadius: 2 }}>
                        {errors.error}
                    </Alert>
                )}

                {/* Developer Office Hours Box */}
                <Card sx={{ mb: 4, backgroundColor: '#f8fafc', borderLeft: '4px solid #3b82f6', borderRadius: 3 }}>
                    <CardContent>
                        <Typography variant="h6" fontWeight="bold" sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
                            <AccessTimeIcon color="primary" /> Developer Office Hours Schedule & Level SLA Rules
                        </Typography>
                        <Grid container spacing={2}>
                            <Grid item xs={12} sm={4}>
                                <Typography variant="body2"><strong>Monday – Friday:</strong> 8:30 AM – 5:00 PM (8.5 hrs/day)</Typography>
                            </Grid>
                            <Grid item xs={12} sm={4}>
                                <Typography variant="body2"><strong>Saturday:</strong> 9:00 AM – 12:30 PM (3.5 hrs/day)</Typography>
                            </Grid>
                            <Grid item xs={12} sm={4}>
                                <Typography variant="body2"><strong>Sunday:</strong> Off (0 hrs)</Typography>
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                {/* Render Dynamic Reorderable Sections */}
                {sectionOrder.map((type, index) => renderSectionCard(type, index))}
            </Box>

            {/* Modal Dialog for Create & Edit */}
            <Dialog 
                open={modalState.open && modalState.mode !== 'delete'} 
                onClose={closeModal}
                maxWidth="xs"
                fullWidth
            >
                <form onSubmit={handleSubmit}>
                    <DialogTitle fontWeight="bold">
                        {modalState.mode === 'create' ? 'Add New' : 'Edit'}{' '}
                        {modalState.type === 'priority' && 'Priority Level (Dynamic JSON Settings)'}
                        {modalState.type === 'importance' && 'Importance Level'}
                        {modalState.type === 'status' && 'Issue Status'}
                        {modalState.type === 'rootCause' && 'Root Cause'}
                    </DialogTitle>
                    <DialogContent dividers>
                        <Stack spacing={2} sx={{ pt: 1 }}>
                            <TextField
                                label="Name / Description"
                                fullWidth
                                required
                                size="small"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            />

                            {(modalState.type === 'priority' || modalState.type === 'importance') && (
                                <TextField
                                    label="Level / Rank (1 = Highest Critical)"
                                    type="number"
                                    fullWidth
                                    size="small"
                                    value={formData.level}
                                    onChange={(e) => setFormData({ ...formData, level: e.target.value })}
                                    helperText="Level 1 is highest priority. Level 4 is Schedule."
                                />
                            )}

                            {/* Dynamic priority JSON settings fields */}
                            {modalState.type === 'priority' && (
                                <>
                                    <FormControl fullWidth size="small">
                                        <InputLabel>Clock Type / Schedule Rule</InputLabel>
                                        <Select
                                            value={formData.clock_type}
                                            label="Clock Type / Schedule Rule"
                                            onChange={(e) => setFormData({ ...formData, clock_type: e.target.value })}
                                        >
                                            <MenuItem value="continuous_24h">Continuous 24/7 Clock (No Office Hours)</MenuItem>
                                            <MenuItem value="office_hours">Office Hours Schedule (M-F 8:30-17:00, Sat 9-12:30)</MenuItem>
                                            <MenuItem value="manual_schedule">Manual Schedule (No Fixed SLA Hours)</MenuItem>
                                        </Select>
                                    </FormControl>

                                    {formData.clock_type !== 'manual_schedule' && (
                                        <TextField
                                            label="SLA Target Hours"
                                            type="number"
                                            inputProps={{ step: 'any' }}
                                            fullWidth
                                            size="small"
                                            value={formData.target_hours}
                                            onChange={(e) => setFormData({ ...formData, target_hours: e.target.value })}
                                            helperText="Target hours for completion (e.g. 24, 8.5, 17)."
                                        />
                                    )}

                                    <TextField
                                        label="Fail Points Weightage"
                                        type="number"
                                        fullWidth
                                        size="small"
                                        value={formData.fail_points}
                                        onChange={(e) => setFormData({ ...formData, fail_points: e.target.value })}
                                        helperText="SLA breach penalty multiplier."
                                    />
                                </>
                            )}

                            {modalState.type === 'status' && (
                                <TextField
                                    label="System Code (e.g. IN_PROGRESS)"
                                    fullWidth
                                    required
                                    size="small"
                                    value={formData.code}
                                    onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                                    helperText="Unique uppercase system identifier."
                                />
                            )}
                        </Stack>
                    </DialogContent>
                    <DialogActions sx={{ p: 2 }}>
                        <Button onClick={closeModal} color="inherit">Cancel</Button>
                        <Button type="submit" variant="contained" color="primary">
                            {modalState.mode === 'create' ? 'Create' : 'Save Changes'}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>

            {/* Modal Dialog for Delete Confirmation */}
            <Dialog 
                open={modalState.open && modalState.mode === 'delete'} 
                onClose={closeModal}
                maxWidth="xs"
                fullWidth
            >
                <DialogTitle fontWeight="bold" color="error.main">
                    Confirm Delete
                </DialogTitle>
                <DialogContent>
                    <Typography variant="body2">
                        Are you sure you want to delete <strong>{modalState.item?.name}</strong>?
                    </Typography>
                    <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1 }}>
                        Note: Items currently assigned to active IT issues cannot be deleted.
                    </Typography>
                </DialogContent>
                <DialogActions sx={{ p: 2 }}>
                    <Button onClick={closeModal} color="inherit">Cancel</Button>
                    <Button onClick={handleDelete} variant="contained" color="error">
                        Delete
                    </Button>
                </DialogActions>
            </Dialog>
        </AsideLayout>
    );
}
