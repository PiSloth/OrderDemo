import React, { useState, useMemo, useEffect } from 'react';
import {
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Box,
    Typography,
    TextField,
    InputAdornment,
    IconButton,
    Button,
    Table,
    TableHead,
    TableBody,
    TableRow,
    TableCell,
    TableContainer,
    Paper,
    Chip,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
} from '@mui/material';
import {
    Search as SearchIcon,
    Clear as ClearIcon,
    CheckCircle as CheckCircleIcon,
    Assignment as AssignmentIcon,
} from '@mui/icons-material';

export default function TemplateSelectModal({
    open = false,
    onClose,
    onSelect,
    templates = [],
    selectedTemplateId = null,
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedGroupId, setSelectedGroupId] = useState('all');
    const [selectedFrequency, setSelectedFrequency] = useState('all');

    useEffect(() => {
        if (open) {
            setSearchQuery('');
            setSelectedGroupId('all');
            setSelectedFrequency('all');
        }
    }, [open]);

    // Unique groups for filter
    const groups = useMemo(() => {
        const map = new Map();
        templates.forEach((t) => {
            if (t.group) {
                map.set(t.group.id, t.group);
            }
        });
        return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
    }, [templates]);

    const filteredTemplates = useMemo(() => {
        return templates.filter((t) => {
            if (!t) return false;

            // Group filter
            if (selectedGroupId !== 'all') {
                if (String(t.group?.id || t.kpi_group_id) !== String(selectedGroupId)) {
                    return false;
                }
            }

            // Frequency filter
            if (selectedFrequency !== 'all') {
                if (t.frequency !== selectedFrequency) {
                    return false;
                }
            }

            // Search query
            if (searchQuery.trim()) {
                const q = searchQuery.toLowerCase().trim();
                const titleMatch = t.title && t.title.toLowerCase().includes(q);
                const descMatch = t.description && t.description.toLowerCase().includes(q);
                const guideMatch = t.guideline && t.guideline.toLowerCase().includes(q);
                const groupMatch = t.group?.name && t.group.name.toLowerCase().includes(q);
                if (!titleMatch && !descMatch && !guideMatch && !groupMatch) {
                    return false;
                }
            }

            return true;
        });
    }, [templates, selectedGroupId, selectedFrequency, searchQuery]);

    const handlePickTemplate = (template) => {
        if (typeof onSelect === 'function') {
            onSelect(template);
        }
        if (typeof onClose === 'function') {
            onClose();
        }
    };

    const getFrequencyColor = (freq) => {
        switch (freq) {
            case 'daily':
                return { bg: '#ecfdf5', text: '#047857', border: '#a7f3d0' };
            case 'weekly':
                return { bg: '#eff6ff', text: '#1d4ed8', border: '#bfdbfe' };
            case 'monthly':
                return { bg: '#faf5ff', text: '#7e22ce', border: '#e9d5ff' };
            default:
                return { bg: '#f1f5f9', text: '#475569', border: '#cbd5e1' };
        }
    };

    return (
        <Dialog
            open={Boolean(open)}
            onClose={onClose}
            maxWidth="md"
            fullWidth
            disableRestoreFocus
            keepMounted={false}
            sx={{
                zIndex: 10000,
            }}
            onKeyDown={(e) => {
                if (e.key === 'Escape') {
                    e.stopPropagation();
                }
            }}
            PaperProps={{
                sx: {
                    borderRadius: '16px',
                    overflow: 'hidden',
                    boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.25)',
                },
            }}
        >
            {/* Header */}
            <DialogTitle
                sx={{
                    backgroundColor: '#1e293b',
                    color: '#fff',
                    py: 2,
                    px: 3,
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                }}
            >
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                    <AssignmentIcon sx={{ fontSize: 28, color: '#38bdf8' }} />
                    <Box>
                        <Typography variant="h6" fontWeight="bold" sx={{ fontSize: '1.1rem', lineHeight: 1.2 }}>
                            Select KPI Task Template
                        </Typography>
                        <Typography variant="caption" sx={{ color: '#94a3b8' }}>
                            Search and select a task template to assign
                        </Typography>
                    </Box>
                </Box>
            </DialogTitle>

            {/* Content */}
            <DialogContent sx={{ p: 3, backgroundColor: '#f8fafc' }}>
                {/* Search & Filters */}
                <Box sx={{ display: 'flex', gap: 2, mb: 2.5, mt: 1, flexWrap: 'wrap', alignItems: 'center' }}>
                    <Box sx={{ flex: 1.5, minWidth: 240 }}>
                        <TextField
                            fullWidth
                            size="small"
                            placeholder="Search template by title, guideline, group..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            InputProps={{
                                startAdornment: (
                                    <InputAdornment position="start">
                                        <SearchIcon color="action" />
                                    </InputAdornment>
                                ),
                                endAdornment: searchQuery && (
                                    <InputAdornment position="end">
                                        <IconButton size="small" type="button" onClick={() => setSearchQuery('')}>
                                            <ClearIcon fontSize="small" />
                                        </IconButton>
                                    </InputAdornment>
                                ),
                                sx: {
                                    backgroundColor: '#fff',
                                    borderRadius: '10px',
                                    fontSize: '0.88rem',
                                },
                            }}
                        />
                    </Box>

                    {/* Group Filter */}
                    <Box sx={{ minWidth: 180 }}>
                        <FormControl fullWidth size="small">
                            <InputLabel id="template-group-filter-label" sx={{ backgroundColor: '#fff', px: 0.5 }}>
                                KPI Group
                            </InputLabel>
                            <Select
                                labelId="template-group-filter-label"
                                value={selectedGroupId}
                                label="KPI Group"
                                onChange={(e) => setSelectedGroupId(e.target.value)}
                                sx={{ backgroundColor: '#fff', borderRadius: '10px', fontSize: '0.88rem' }}
                            >
                                <MenuItem value="all">All Groups</MenuItem>
                                {groups.map((g) => (
                                    <MenuItem key={g.id} value={g.id}>
                                        {g.name}
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </Box>

                    {/* Frequency Filter */}
                    <Box sx={{ minWidth: 150 }}>
                        <FormControl fullWidth size="small">
                            <InputLabel id="template-freq-filter-label" sx={{ backgroundColor: '#fff', px: 0.5 }}>
                                Frequency
                            </InputLabel>
                            <Select
                                labelId="template-freq-filter-label"
                                value={selectedFrequency}
                                label="Frequency"
                                onChange={(e) => setSelectedFrequency(e.target.value)}
                                sx={{ backgroundColor: '#fff', borderRadius: '10px', fontSize: '0.88rem' }}
                            >
                                <MenuItem value="all">All Frequencies</MenuItem>
                                <MenuItem value="daily">Daily</MenuItem>
                                <MenuItem value="weekly">Weekly</MenuItem>
                                <MenuItem value="monthly">Monthly</MenuItem>
                                <MenuItem value="on_demand">On Demand</MenuItem>
                            </Select>
                        </FormControl>
                    </Box>
                </Box>

                {/* Templates Table */}
                <TableContainer
                    component={Paper}
                    elevation={0}
                    sx={{
                        border: '1px solid #e2e8f0',
                        borderRadius: '12px',
                        maxHeight: 380,
                        overflowY: 'auto',
                        backgroundColor: '#fff',
                    }}
                >
                    <Table stickyHeader size="small">
                        <TableHead>
                            <TableRow sx={{ '& th': { backgroundColor: '#f1f5f9', fontWeight: 'bold', color: '#334155' } }}>
                                <TableCell sx={{ minWidth: 220 }}>Template Title</TableCell>
                                <TableCell sx={{ minWidth: 160 }}>KPI Group</TableCell>
                                <TableCell sx={{ minWidth: 100 }} align="center">Frequency</TableCell>
                                <TableCell sx={{ minWidth: 130 }}>Evidence Info</TableCell>
                                <TableCell align="center" sx={{ width: 100 }}>Action</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {filteredTemplates.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} align="center" sx={{ py: 5, color: '#64748b' }}>
                                        <Typography variant="body2" fontWeight="600">
                                            No matching task templates found.
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary">
                                            Try adjusting your search query or filters.
                                        </Typography>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                filteredTemplates.map((tpl) => {
                                    if (!tpl) return null;
                                    const isSelected = selectedTemplateId && String(tpl.id) === String(selectedTemplateId);
                                    const freqColors = getFrequencyColor(tpl.frequency);

                                    return (
                                        <TableRow
                                            key={tpl.id}
                                            hover
                                            onClick={() => handlePickTemplate(tpl)}
                                            sx={{
                                                cursor: 'pointer',
                                                backgroundColor: isSelected ? '#e0f2fe' : 'inherit',
                                                '&:hover': { backgroundColor: isSelected ? '#bae6fd' : '#f8fafc' },
                                                transition: 'background-color 0.15s ease',
                                            }}
                                        >
                                            {/* Title & Guideline */}
                                            <TableCell>
                                                <Typography
                                                    variant="body2"
                                                    fontWeight={isSelected ? '800' : '600'}
                                                    sx={{ color: '#1e293b' }}
                                                >
                                                    {tpl.title}
                                                </Typography>
                                                {tpl.guideline && (
                                                    <Typography
                                                        variant="caption"
                                                        sx={{
                                                            color: '#64748b',
                                                            display: '-webkit-box',
                                                            WebkitLineClamp: 1,
                                                            WebkitBoxOrient: 'vertical',
                                                            overflow: 'hidden',
                                                        }}
                                                    >
                                                        {tpl.guideline}
                                                    </Typography>
                                                )}
                                            </TableCell>

                                            {/* Group & Department */}
                                            <TableCell>
                                                <Typography variant="body2" sx={{ color: '#334155', fontWeight: 500 }}>
                                                    {tpl.group?.name || '—'}
                                                </Typography>
                                                {tpl.group?.department?.name && (
                                                    <Typography variant="caption" sx={{ color: '#64748b' }}>
                                                        Dept: {tpl.group.department.name}
                                                    </Typography>
                                                )}
                                            </TableCell>

                                            {/* Frequency */}
                                            <TableCell align="center">
                                                <Chip
                                                    label={String(tpl.frequency || '-').toUpperCase()}
                                                    size="small"
                                                    sx={{
                                                        backgroundColor: freqColors.bg,
                                                        color: freqColors.text,
                                                        border: `1px solid ${freqColors.border}`,
                                                        fontWeight: 'bold',
                                                        fontSize: '0.68rem',
                                                        height: 22,
                                                    }}
                                                />
                                            </TableCell>

                                            {/* Evidence Info */}
                                            <TableCell>
                                                <Typography variant="caption" sx={{ color: '#475569', display: 'block' }}>
                                                    {tpl.requires_images
                                                        ? `Photos: ${tpl.min_images ?? 0}${tpl.max_images ? ` - ${tpl.max_images}` : '+'}`
                                                        : 'No photo requirement'}
                                                </Typography>
                                                {tpl.cutoff_time && (
                                                    <Typography variant="caption" sx={{ color: '#64748b' }}>
                                                        Cutoff: {tpl.cutoff_time.substring(0, 5)}
                                                    </Typography>
                                                )}
                                            </TableCell>

                                            {/* Action Button */}
                                            <TableCell align="center">
                                                <Button
                                                    type="button"
                                                    size="small"
                                                    variant={isSelected ? 'contained' : 'outlined'}
                                                    color={isSelected ? 'success' : 'primary'}
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handlePickTemplate(tpl);
                                                    }}
                                                    startIcon={isSelected ? <CheckCircleIcon /> : undefined}
                                                    sx={{
                                                        textTransform: 'none',
                                                        fontWeight: 'bold',
                                                        fontSize: '0.75rem',
                                                        px: 1.5,
                                                        py: 0.3,
                                                        borderRadius: '6px',
                                                    }}
                                                >
                                                    {isSelected ? 'Selected' : 'Select'}
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            )}
                        </TableBody>
                    </Table>
                </TableContainer>
            </DialogContent>

            {/* Footer */}
            <DialogActions sx={{ p: 2, backgroundColor: '#f1f5f9', justifyContent: 'space-between' }}>
                <Typography variant="caption" color="text.secondary">
                    Showing {filteredTemplates.length} template{filteredTemplates.length !== 1 ? 's' : ''}
                </Typography>
                <Button type="button" onClick={onClose} color="inherit">
                    Cancel
                </Button>
            </DialogActions>
        </Dialog>
    );
}
