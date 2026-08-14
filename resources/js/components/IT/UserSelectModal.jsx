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
    Avatar,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    Checkbox,
    ListItemText,
    OutlinedInput,
} from '@mui/material';
import {
    Person as PersonIcon,
    Search as SearchIcon,
    Clear as ClearIcon,
    CheckCircle as CheckCircleIcon,
    Apartment as ApartmentIcon,
    Store as StoreIcon,
} from '@mui/icons-material';

const ITEM_HEIGHT = 48;
const ITEM_PADDING_TOP = 8;
const MenuProps = {
    PaperProps: {
        style: {
            maxHeight: ITEM_HEIGHT * 4.5 + ITEM_PADDING_TOP,
            width: 250,
        },
    },
};

/**
 * UserSelectModal — Reusable dialog for searching and selecting users across Create, Edit, and Status Transition flows.
 *
 * Props:
 *   open                 {boolean}
 *   onClose              {function}
 *   onSelect             {function(user)}
 *   title                {string}
 *   subtitle             {string}
 *   selectedUserId       {number|string|null}
 *   selectedUserName     {string|null}
 *   users                {array}       — List of user objects with department and branch
 *   departments          {array}       — List of all departments for multi-select filter
 *   currentUserName      {string}      — Logged-in user's name
 *   initialDepartmentIds {array}       — Default department IDs to filter by
 */
export default function UserSelectModal({
    open = false,
    onClose,
    onSelect,
    title = 'Select Assigned User',
    subtitle = 'Search and select an employee from the table below',
    selectedUserId = null,
    selectedUserName = null,
    users = [],
    departments = [],
    currentUserName = '',
    initialDepartmentIds = [],
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedDeptIds, setSelectedDeptIds] = useState([]);

    // Safe normalization of users and departments arrays
    const safeUsers = useMemo(() => {
        if (Array.isArray(users)) return users;
        if (users && Array.isArray(users.data)) return users.data;
        return [];
    }, [users]);

    const safeDepartments = useMemo(() => {
        if (Array.isArray(departments)) return departments;
        if (departments && Array.isArray(departments.data)) return departments.data;
        return [];
    }, [departments]);

    // Reset search & department filter only when the modal transitions from closed → open.
    // We intentionally do NOT include `initialDepartmentIds` in deps because the parent
    // passes a new array literal every render, which would cause an infinite setState loop
    // (React error #185 – Maximum update depth exceeded).
    const prevOpenRef = useRef(false);
    useEffect(() => {
        if (open && !prevOpenRef.current) {
            // Modal just opened — snapshot current initialDepartmentIds
            const initial = Array.isArray(initialDepartmentIds)
                ? initialDepartmentIds.map(Number).filter(Boolean)
                : [];
            setSelectedDeptIds(initial);
            setSearchQuery('');
        }
        prevOpenRef.current = open;
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]); // ← only `open` in deps; initialDepartmentIds read by ref at open time

    const handleDeptChange = (event) => {
        const {
            target: { value },
        } = event;
        const valArray = typeof value === 'string' ? value.split(',').map(Number) : value;
        setSelectedDeptIds(valArray || []);
    };

    const handleClearDeptFilter = () => {
        setSelectedDeptIds([]);
    };

    // Filter users list based on real-time search query and department multi-select
    const filteredUsers = useMemo(() => {
        return safeUsers.filter((u) => {
            if (!u) return false;

            // 1. Department Multi-Select Filter
            if (selectedDeptIds && selectedDeptIds.length > 0) {
                const userDeptId = u.department_id || u.department?.id;
                if (!userDeptId || !selectedDeptIds.includes(Number(userDeptId))) {
                    return false;
                }
            }

            // 2. Text Search Query Filter
            if (searchQuery && searchQuery.trim()) {
                const q = searchQuery.toLowerCase().trim();
                const nameMatch = u.name && String(u.name).toLowerCase().includes(q);
                const emailMatch = u.email && String(u.email).toLowerCase().includes(q);
                const deptMatch = u.department?.name && String(u.department.name).toLowerCase().includes(q);
                const branchMatch = u.branch?.name && String(u.branch.name).toLowerCase().includes(q);
                if (!nameMatch && !emailMatch && !deptMatch && !branchMatch) {
                    return false;
                }
            }

            return true;
        });
    }, [safeUsers, searchQuery, selectedDeptIds]);

    const handlePickUser = (user) => {
        if (typeof onSelect === 'function') {
            onSelect(user);
        }
        if (typeof onClose === 'function') {
            onClose();
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
            onKeyDown={(e) => {
                // Prevent Escape key from bubbling to any parent Dialog
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
            {/* ── Dialog Header ── */}
            <DialogTitle
                sx={{
                    backgroundColor: '#1e293b',
                    color: '#fff',
                    py: 2,
                    px: 3,
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    flexWrap: 'wrap',
                    gap: 1,
                }}
            >
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                    <PersonIcon sx={{ fontSize: 28, color: '#38bdf8' }} />
                    <Box>
                        <Typography variant="h6" fontWeight="bold" sx={{ fontSize: '1.1rem', lineHeight: 1.2 }}>
                            {title}
                        </Typography>
                        <Typography variant="caption" sx={{ color: '#94a3b8' }}>
                            {subtitle}
                        </Typography>
                    </Box>
                </Box>

                {currentUserName && (
                    <Button
                        type="button"
                        size="small"
                        variant="outlined"
                        onClick={(e) => {
                            e.preventDefault();
                            const me = safeUsers.find((u) => u && u.name === currentUserName) || { name: currentUserName };
                            handlePickUser(me);
                        }}
                        sx={{
                            color: '#38bdf8',
                            borderColor: '#38bdf8',
                            textTransform: 'none',
                            fontSize: '0.78rem',
                            fontWeight: 'bold',
                            '&:hover': { borderColor: '#7dd3fc', backgroundColor: 'rgba(56, 189, 248, 0.1)' },
                        }}
                    >
                        Assign to Myself ({currentUserName})
                    </Button>
                )}
            </DialogTitle>

            {/* ── Dialog Content ── */}
            <DialogContent sx={{ p: 3, backgroundColor: '#f8fafc' }}>
                {/* Search & Multi-Select Filters Row */}
                <Box sx={{ display: 'flex', gap: 2, mb: 2.5, mt: 1, flexWrap: 'wrap', alignItems: 'center' }}>
                    {/* Text Search Input */}
                    <Box sx={{ flex: 1.5, minWidth: 240 }}>
                        <TextField
                            fullWidth
                            size="small"
                            placeholder="Search employee by name, email, or branch..."
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

                    {/* Department Multi-Select Dropdown */}
                    <Box sx={{ flex: 1, minWidth: 220 }}>
                        <FormControl fullWidth size="small">
                            <InputLabel id="dept-filter-label" sx={{ backgroundColor: '#fff', px: 0.5 }}>
                                Filter by Department
                            </InputLabel>
                            <Select
                                labelId="dept-filter-label"
                                multiple
                                value={selectedDeptIds}
                                onChange={handleDeptChange}
                                input={<OutlinedInput label="Filter by Department" />}
                                renderValue={(selected) => {
                                    if (!selected || selected.length === 0) return <em>All Departments</em>;
                                    return (
                                        <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                            {selected.map((id) => {
                                                const d = safeDepartments.find((dept) => dept && dept.id === id);
                                                return (
                                                    <Chip
                                                        key={id}
                                                        label={d ? d.name : id}
                                                        size="small"
                                                        sx={{ height: 20, fontSize: '0.7rem' }}
                                                    />
                                                );
                                            })}
                                        </Box>
                                    );
                                }}
                                MenuProps={MenuProps}
                                sx={{ backgroundColor: '#fff', borderRadius: '10px', fontSize: '0.88rem' }}
                            >
                                {safeDepartments.map((dept) => (
                                    <MenuItem key={dept.id} value={dept.id}>
                                        <Checkbox checked={selectedDeptIds.indexOf(dept.id) > -1} size="small" />
                                        <ListItemText primary={dept.name} />
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </Box>

                    {/* Clear Dept Filter button */}
                    {selectedDeptIds.length > 0 && (
                        <Button
                            type="button"
                            size="small"
                            color="inherit"
                            onClick={handleClearDeptFilter}
                            sx={{ textTransform: 'none', fontSize: '0.75rem', color: '#64748b' }}
                        >
                            Clear Dept Filter
                        </Button>
                    )}
                </Box>

                {/* Users Table */}
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
                                <TableCell sx={{ minWidth: 200 }}>Employee Name</TableCell>
                                <TableCell sx={{ minWidth: 150 }}>Department</TableCell>
                                <TableCell sx={{ minWidth: 140 }}>Branch</TableCell>
                                <TableCell align="center" sx={{ width: 110 }}>Action</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {filteredUsers.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} align="center" sx={{ py: 5, color: '#64748b' }}>
                                        <Typography variant="body2" fontWeight="600">
                                            No matching employees found.
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary">
                                            Try adjusting your search terms or department filter.
                                        </Typography>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                filteredUsers.map((u) => {
                                    if (!u) return null;
                                    const isCurrent = u.name === currentUserName;
                                    const isSelected =
                                        (selectedUserId && String(u.id) === String(selectedUserId)) ||
                                        (selectedUserName && u.name === selectedUserName);

                                    return (
                                        <TableRow
                                            key={u.id}
                                            hover
                                            onClick={() => handlePickUser(u)}
                                            sx={{
                                                cursor: 'pointer',
                                                backgroundColor: isSelected ? '#e0f2fe' : 'inherit',
                                                '&:hover': { backgroundColor: isSelected ? '#bae6fd' : '#f8fafc' },
                                                transition: 'background-color 0.15s ease',
                                            }}
                                        >
                                            {/* Name & Email */}
                                            <TableCell>
                                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                                                    <Avatar
                                                        sx={{
                                                            width: 32,
                                                            height: 32,
                                                            fontSize: '0.8rem',
                                                            fontWeight: 'bold',
                                                            backgroundColor: isSelected ? '#0284c7' : '#0d9488',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        {u.name ? String(u.name).charAt(0).toUpperCase() : '?'}
                                                    </Avatar>
                                                    <Box>
                                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.8 }}>
                                                            <Typography
                                                                variant="body2"
                                                                fontWeight={isSelected ? '800' : '600'}
                                                                sx={{ color: '#1e293b' }}
                                                            >
                                                                {u.name}
                                                            </Typography>
                                                            {isCurrent && (
                                                                <Chip
                                                                    label="You"
                                                                    size="small"
                                                                    color="info"
                                                                    sx={{ height: 18, fontSize: '0.65rem', fontWeight: 'bold' }}
                                                                />
                                                            )}
                                                        </Box>
                                                        {u.email && (
                                                            <Typography variant="caption" sx={{ color: '#64748b', display: 'block' }}>
                                                                {u.email}
                                                            </Typography>
                                                        )}
                                                    </Box>
                                                </Box>
                                            </TableCell>

                                            {/* Department */}
                                            <TableCell>
                                                {u.department?.name ? (
                                                    <Chip
                                                        label={u.department.name}
                                                        size="small"
                                                        variant="outlined"
                                                        icon={<ApartmentIcon sx={{ fontSize: '14px !important' }} />}
                                                        sx={{ fontSize: '0.75rem', height: 22, borderColor: '#cbd5e1' }}
                                                    />
                                                ) : (
                                                    <Typography variant="caption" color="text.secondary">—</Typography>
                                                )}
                                            </TableCell>

                                            {/* Branch */}
                                            <TableCell>
                                                {u.branch?.name ? (
                                                    <Chip
                                                        label={u.branch.name}
                                                        size="small"
                                                        variant="outlined"
                                                        icon={<StoreIcon sx={{ fontSize: '14px !important' }} />}
                                                        sx={{ fontSize: '0.75rem', height: 22, borderColor: '#cbd5e1' }}
                                                    />
                                                ) : (
                                                    <Typography variant="caption" color="text.secondary">—</Typography>
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
                                                        handlePickUser(u);
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

            {/* ── Dialog Footer ── */}
            <DialogActions sx={{ p: 2, backgroundColor: '#f1f5f9', justifyContent: 'space-between' }}>
                <Typography variant="caption" color="text.secondary">
                    Showing {filteredUsers.length} employee{filteredUsers.length !== 1 ? 's' : ''}
                    {selectedDeptIds.length > 0 ? ` (filtered by ${selectedDeptIds.length} department${selectedDeptIds.length > 1 ? 's' : ''})` : ''}
                </Typography>
                <Button type="button" onClick={onClose} color="inherit">
                    Cancel
                </Button>
            </DialogActions>
        </Dialog>
    );
}
