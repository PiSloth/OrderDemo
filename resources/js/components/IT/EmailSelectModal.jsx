import React, { useState, useMemo, useEffect, useRef } from 'react';
import {
    Box,
    Button,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    IconButton,
    InputAdornment,
    Typography,
    TextField,
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
    Pagination,
} from '@mui/material';
import {
    Email as EmailIcon,
    Person as PersonIcon,
    Search as SearchIcon,
    Clear as ClearIcon,
    CheckCircle as CheckCircleIcon,
    Apartment as ApartmentIcon,
    RadioButtonUnchecked as RadioUncheckedIcon,
} from '@mui/icons-material';

/**
 * EmailSelectModal — Reusable dialog for searching and selecting corporate email records.
 *
 * Props:
 *   open             {boolean}
 *   onClose          {function}
 *   onSelect         {function(emailRecord)}
 *   title            {string}
 *   subtitle         {string}
 *   selectedEmailId  {number|string|null}
 *   emails           {array}       — List of EmailList objects with department
 *   departments      {array}       — List of departments for filtering
 */
export default function EmailSelectModal({
    open = false,
    onClose,
    onSelect,
    title = 'Select Master Email Address',
    subtitle = 'Search and select a corporate email from the list below',
    selectedEmailId = null,
    emails = [],
    departments = [],
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [departmentFilter, setDepartmentFilter] = useState('');
    const [selectedId, setSelectedId] = useState(selectedEmailId);
    const [page, setPage] = useState(1);
    const rowsPerPage = 8;

    // Reset state when opening
    const prevOpenRef = useRef(false);
    useEffect(() => {
        if (!prevOpenRef.current && open) {
            setSelectedId(selectedEmailId);
            setSearchQuery('');
            setDepartmentFilter('');
            setPage(1);
        }
        prevOpenRef.current = open;
    }, [open, selectedEmailId]);

    const safeEmails = useMemo(() => {
        if (Array.isArray(emails)) return emails;
        if (emails && Array.isArray(emails.data)) return emails.data;
        return [];
    }, [emails]);

    const safeDepartments = useMemo(() => {
        if (Array.isArray(departments)) return departments;
        if (departments && Array.isArray(departments.data)) return departments.data;
        return [];
    }, [departments]);

    // Filtered emails
    const filteredEmails = useMemo(() => {
        const query = searchQuery.trim().toLowerCase();

        return safeEmails.filter((em) => {
            // Search query matching
            if (query) {
                const emailMatch = (em.email || '').toLowerCase().includes(query);
                const userNameMatch = (em.user_name || '').toLowerCase().includes(query);
                const deptMatch = (em.department?.name || '').toLowerCase().includes(query);
                if (!emailMatch && !userNameMatch && !deptMatch) return false;
            }

            // Department filter
            if (departmentFilter) {
                if (String(em.department_id) !== String(departmentFilter)) {
                    return false;
                }
            }

            return true;
        });
    }, [safeEmails, searchQuery, departmentFilter]);

    // Paginated slice
    const totalPages = Math.ceil(filteredEmails.length / rowsPerPage) || 1;
    const paginatedEmails = useMemo(() => {
        const start = (page - 1) * rowsPerPage;
        return filteredEmails.slice(start, start + rowsPerPage);
    }, [filteredEmails, page]);

    const selectedRecord = useMemo(() => {
        return safeEmails.find(em => String(em.id) === String(selectedId)) || null;
    }, [safeEmails, selectedId]);

    const handleConfirm = () => {
        if (selectedRecord && onSelect) {
            onSelect(selectedRecord);
        }
        if (onClose) {
            onClose();
        }
    };

    const handleRowClick = (em) => {
        setSelectedId(em.id);
    };

    const handleRowDoubleClick = (em) => {
        setSelectedId(em.id);
        if (onSelect) {
            onSelect(em);
        }
        if (onClose) {
            onClose();
        }
    };

    return (
        <Dialog
            open={open}
            onClose={onClose}
            maxWidth="md"
            fullWidth
            PaperProps={{
                sx: {
                    borderRadius: 2.5,
                    maxHeight: '90vh',
                },
            }}
        >
            <DialogTitle sx={{ pb: 1, pt: 2.5, px: 3 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                    <Box
                        sx={{
                            width: 40,
                            height: 40,
                            borderRadius: '10px',
                            bgcolor: 'primary.light',
                            color: 'primary.main',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                        }}
                    >
                        <EmailIcon fontSize="medium" />
                    </Box>
                    <Box sx={{ flex: 1 }}>
                        <Typography variant="h6" sx={{ fontWeight: 700, lineHeight: 1.2 }}>
                            {title}
                        </Typography>
                        {subtitle && (
                            <Typography variant="caption" color="text.secondary">
                                {subtitle}
                            </Typography>
                        )}
                    </Box>
                </Box>
            </DialogTitle>

            <DialogContent dividers sx={{ px: 3, py: 2 }}>
                {/* Search & Filter Bar */}
                <Box sx={{ display: 'flex', gap: 1.5, mb: 2, flexWrap: 'wrap' }}>
                    <TextField
                        size="small"
                        placeholder="Search by email address or custodian name..."
                        value={searchQuery}
                        onChange={(e) => {
                            setSearchQuery(e.target.value);
                            setPage(1);
                        }}
                        sx={{ flex: '2 1 260px' }}
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
                                            setPage(1);
                                        }}
                                    >
                                        <ClearIcon fontSize="small" />
                                    </IconButton>
                                </InputAdornment>
                            ) : null,
                        }}
                    />

                    {safeDepartments.length > 0 && (
                        <FormControl size="small" sx={{ minWidth: 200, flex: '1 1 180px' }}>
                            <InputLabel>Filter by Department</InputLabel>
                            <Select
                                value={departmentFilter}
                                label="Filter by Department"
                                onChange={(e) => {
                                    setDepartmentFilter(e.target.value);
                                    setPage(1);
                                }}
                            >
                                <MenuItem value="">
                                    <em>All Departments</em>
                                </MenuItem>
                                {safeDepartments.map((dept) => (
                                    <MenuItem key={dept.id} value={dept.id}>
                                        {dept.name}
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    )}
                </Box>

                {/* Selected Email Indicator */}
                {selectedRecord && (
                    <Paper
                        variant="outlined"
                        sx={{
                            mb: 2,
                            p: 1.5,
                            bgcolor: 'primary.50',
                            borderColor: 'primary.200',
                            borderRadius: 2,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            flexWrap: 'wrap',
                            gap: 1,
                        }}
                    >
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <CheckCircleIcon color="primary" fontSize="small" />
                            <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                Selected: {selectedRecord.email}
                            </Typography>
                            {selectedRecord.user_name && (
                                <Typography variant="caption" color="text.secondary">
                                    ({selectedRecord.user_name})
                                </Typography>
                            )}
                            {selectedRecord.department?.name && (
                                <Chip
                                    size="small"
                                    icon={<ApartmentIcon fontSize="small" />}
                                    label={selectedRecord.department.name}
                                    variant="outlined"
                                    sx={{ height: 22, fontSize: '0.72rem' }}
                                />
                            )}
                        </Box>
                        <Typography variant="caption" color="primary.main" sx={{ fontWeight: 600 }}>
                            Click Save / Confirm below or double-click row to select
                        </Typography>
                    </Paper>
                )}

                {/* Emails Table */}
                <TableContainer
                    component={Paper}
                    variant="outlined"
                    sx={{ borderRadius: 2, maxHeight: 380, overflowY: 'auto' }}
                >
                    <Table size="small" stickyHeader>
                        <TableHead>
                            <TableRow sx={{ '& th': { bgcolor: 'action.hover', fontWeight: 700 } }}>
                                <TableCell width={50} align="center">Pick</TableCell>
                                <TableCell>Email Address</TableCell>
                                <TableCell>Custodian / Assigned Name</TableCell>
                                <TableCell>Department</TableCell>
                                <TableCell width={100} align="right">Action</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {paginatedEmails.length > 0 ? (
                                paginatedEmails.map((em) => {
                                    const isSelected = String(em.id) === String(selectedId);
                                    return (
                                        <TableRow
                                            key={em.id}
                                            hover
                                            selected={isSelected}
                                            onClick={() => handleRowClick(em)}
                                            onDoubleClick={() => handleRowDoubleClick(em)}
                                            sx={{
                                                cursor: 'pointer',
                                                bgcolor: isSelected ? 'action.selected' : 'inherit',
                                            }}
                                        >
                                            <TableCell align="center">
                                                {isSelected ? (
                                                    <CheckCircleIcon color="primary" fontSize="small" />
                                                ) : (
                                                    <RadioUncheckedIcon color="action" fontSize="small" />
                                                )}
                                            </TableCell>
                                            <TableCell sx={{ fontWeight: isSelected ? 700 : 500, fontFamily: 'monospace' }}>
                                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                                    <EmailIcon fontSize="small" color={isSelected ? 'primary' : 'action'} />
                                                    {em.email}
                                                </Box>
                                            </TableCell>
                                            <TableCell>
                                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.75 }}>
                                                    <PersonIcon fontSize="small" color="action" />
                                                    <Typography variant="body2">{em.user_name || '-'}</Typography>
                                                </Box>
                                            </TableCell>
                                            <TableCell>
                                                {em.department?.name ? (
                                                    <Chip
                                                        size="small"
                                                        variant="outlined"
                                                        label={em.department.name}
                                                        icon={<ApartmentIcon fontSize="small" />}
                                                        sx={{ height: 24, fontSize: '0.72rem' }}
                                                    />
                                                ) : (
                                                    <Typography variant="caption" color="text.secondary">-</Typography>
                                                )}
                                            </TableCell>
                                            <TableCell align="right">
                                                <Button
                                                    size="small"
                                                    variant={isSelected ? 'contained' : 'outlined'}
                                                    color="primary"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleRowDoubleClick(em);
                                                    }}
                                                    sx={{ textTransform: 'none', py: 0.25, px: 1.5 }}
                                                >
                                                    Select
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={5} align="center" sx={{ py: 4 }}>
                                        <Typography color="text.secondary">
                                            No corporate emails found matching criteria.
                                        </Typography>
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </TableContainer>

                {/* Pagination */}
                {totalPages > 1 && (
                    <Box sx={{ mt: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Typography variant="caption" color="text.secondary">
                            Showing {(page - 1) * rowsPerPage + 1} to {Math.min(page * rowsPerPage, filteredEmails.length)} of {filteredEmails.length} emails
                        </Typography>
                        <Pagination
                            count={totalPages}
                            page={page}
                            onChange={(e, val) => setPage(val)}
                            color="primary"
                            size="small"
                            shape="rounded"
                        />
                    </Box>
                )}
            </DialogContent>

            <DialogActions sx={{ p: 2.5, px: 3 }}>
                <Button onClick={onClose} color="inherit">
                    Cancel
                </Button>
                <Button
                    variant="contained"
                    onClick={handleConfirm}
                    disabled={!selectedId}
                    startIcon={<CheckCircleIcon />}
                >
                    Confirm Selection
                </Button>
            </DialogActions>
        </Dialog>
    );
}
