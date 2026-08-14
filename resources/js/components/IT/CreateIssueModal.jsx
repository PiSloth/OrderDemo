import React, { useState, useEffect, useMemo } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    Box,
    Button,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    IconButton,
    InputAdornment,
    Switch,
    FormControlLabel,
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
    Tooltip,
} from '@mui/material';
import {
    VpnKey as KeyIcon,
    Inventory as OpenBoxIcon,
    Traffic as TrafficIcon,
    RocketLaunch as RocketIcon,
    Person as PersonIcon,
    Search as SearchIcon,
    Clear as ClearIcon,
    CheckCircle as CheckCircleIcon,
    AccountCircle as AccountCircleIcon,
    Apartment as ApartmentIcon,
    Store as StoreIcon,
} from '@mui/icons-material';

/**
 * CreateIssueModal — Globally reusable "Create New IT Issue" modal.
 *
 * Props:
 *   open             {boolean}   — Whether the modal is visible
 *   onClose          {function}  — Called when the modal should close
 *   onSuccess        {function}  — Optional callback after successful creation
 *   categories       {array}     — [{ id, name }]
 *   priorities       {array}     — [{ id, code, name }]
 *   departments      {array}     — [{ id, name }]
 *   importanceLevels {array}     — [{ id, name }]
 *   users            {array}     — [{ id, name, email, department, branch }]
 *   auth             {object}    — Auth user prop
 */
export default function CreateIssueModal({
    open,
    onClose,
    onSuccess,
    categories = [],
    priorities = [],
    departments = [],
    importanceLevels = [],
    users = [],
    auth,
}) {
    const pageProps = usePage().props;
    const effectiveUsers = users.length > 0 ? users : (pageProps.users || []);
    const currentUserName = auth?.user?.name || pageProps.auth?.user?.name || pageProps.auth_user?.name || '';

    const defaultForm = {
        title: '',
        description: '',
        issue_category_id: categories.length > 0 ? categories[0].id : '',
        issue_priority_id: '',        // EMPTY BY DEFAULT (as per spec)
        issue_importance_id: importanceLevels.length > 0 ? importanceLevels[0].id : '',
        resolution_department_id: departments.length > 0 ? departments[0].id : '',
        assigned_user_id: '',
        issue_by: currentUserName,
        is_third_party_resolver: false,
    };

    const [form, setForm] = useState(defaultForm);
    const [userSelectorOpen, setUserSelectorOpen] = useState(false);
    const [userSearch, setUserSearch] = useState('');

    // Reset form with current user as default when opening modal
    useEffect(() => {
        if (open) {
            setForm((prev) => ({
                ...prev,
                issue_by: prev.issue_by || currentUserName,
                issue_category_id: prev.issue_category_id || (categories.length > 0 ? categories[0].id : ''),
                issue_importance_id: prev.issue_importance_id || (importanceLevels.length > 0 ? importanceLevels[0].id : ''),
                resolution_department_id: prev.resolution_department_id || (departments.length > 0 ? departments[0].id : ''),
            }));
        }
    }, [open, currentUserName, categories, importanceLevels, departments]);

    const handleClose = () => {
        setForm(defaultForm);
        setUserSelectorOpen(false);
        setUserSearch('');
        onClose();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/operations/it/issues', form, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setForm(defaultForm);
                setUserSelectorOpen(false);
                setUserSearch('');
                onClose();
                if (typeof onSuccess === 'function') onSuccess();
            },
        });
    };

    // Filter users list based on real-time search input
    const filteredUsers = useMemo(() => {
        if (!userSearch.trim()) return effectiveUsers;
        const q = userSearch.toLowerCase().trim();
        return effectiveUsers.filter((u) => {
            const nameMatch = u.name && u.name.toLowerCase().includes(q);
            const emailMatch = u.email && u.email.toLowerCase().includes(q);
            const deptMatch = u.department?.name && u.department.name.toLowerCase().includes(q);
            const branchMatch = u.branch?.name && u.branch.name.toLowerCase().includes(q);
            return nameMatch || emailMatch || deptMatch || branchMatch;
        });
    }, [effectiveUsers, userSearch]);

    const handleSelectUser = (user) => {
        setForm((prev) => ({
            ...prev,
            issue_by: user.name,
        }));
        setUserSelectorOpen(false);
        setUserSearch('');
    };

    // ── Shared label floating above border ──────────────────────────────────
    const FloatLabel = ({ text, color }) => (
        <Typography
            variant="caption"
            fontWeight="800"
            sx={{
                position: 'absolute',
                top: -10,
                left: 14,
                backgroundColor: '#ffffff',
                px: 0.8,
                color,
                zIndex: 1,
                textTransform: 'uppercase',
                lineHeight: 1,
            }}
        >
            {text}
        </Typography>
    );

    // ── Shared border-box wrapper ────────────────────────────────────────────
    const BorderBox = ({ borderColor, children, sx = {} }) => (
        <Box
            sx={{
                display: 'flex',
                alignItems: 'center',
                border: `2.5px solid ${borderColor}`,
                borderRadius: '12px',
                px: 1.5,
                py: 1.2,
                backgroundColor: '#fff',
                ...sx,
            }}
        >
            {children}
        </Box>
    );

    // ── Shared bare <input> / <select> / <textarea> style ───────────────────
    const nativeInputStyle = {
        width: '100%',
        border: 'none',
        outline: 'none',
        fontSize: '0.92rem',
        fontWeight: '600',
        color: '#1e293b',
        backgroundColor: 'transparent',
        fontFamily: 'inherit',
        cursor: 'text',
    };

    const nativeSelectStyle = { ...nativeInputStyle, cursor: 'pointer' };

    return (
        <>
            <Dialog
                open={open}
                onClose={handleClose}
                maxWidth="sm"
                fullWidth
                PaperProps={{
                    sx: {
                        borderRadius: '16px',
                        border: '3px solid #3b0764',
                        boxShadow: '0 25px 50px -12px rgba(59, 7, 100, 0.4)',
                        overflow: 'hidden',
                        backgroundColor: '#ffffff',
                    },
                }}
            >
                {/* ── Header ────────────────────────────────────────────────── */}
                <Box
                    sx={{
                        backgroundColor: '#3b0764',
                        color: '#fff',
                        px: 3,
                        py: 2,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                    }}
                >
                    <Typography variant="h6" fontWeight="800" sx={{ fontSize: '1.2rem' }}>
                        Create New IT Issue
                    </Typography>
                    <Button
                        variant="contained"
                        disableElevation
                        sx={{
                            backgroundColor: '#2563eb',
                            borderRadius: '20px',
                            textTransform: 'none',
                            fontWeight: 'bold',
                            fontSize: '0.75rem',
                            px: 2,
                            py: 0.5,
                            '&:hover': { backgroundColor: '#1d4ed8' },
                        }}
                    >
                        SLA Auto-Calculate
                    </Button>
                </Box>

                {/* ── Form Body ─────────────────────────────────────────────── */}
                <form onSubmit={handleSubmit}>
                    <Box sx={{ p: 3, display: 'flex', flexDirection: 'column', gap: 2.8 }}>

                        {/* ROW 1 — ISSUE TITLE * (Full Width, Purple) */}
                        <Box sx={{ position: 'relative' }}>
                            <FloatLabel text="ISSUE TITLE *" color="#3b0764" />
                            <BorderBox borderColor="#3b0764">
                                <KeyIcon sx={{ color: '#3b0764', mr: 1.5, fontSize: 26, flexShrink: 0 }} />
                                <input
                                    type="text"
                                    required
                                    placeholder="Problem with Login"
                                    value={form.title}
                                    onChange={(e) => setForm({ ...form, title: e.target.value })}
                                    style={nativeInputStyle}
                                />
                            </BorderBox>
                        </Box>

                        {/* ROW 2 — CATEGORY * (Teal) + PRIORITY (Purple) half-width each */}
                        <Box sx={{ display: 'flex', gap: 2 }}>
                            {/* Category */}
                            <Box sx={{ flex: 1, position: 'relative' }}>
                                <FloatLabel text="CATEGORY *" color="#0d9488" />
                                <BorderBox borderColor="#0d9488">
                                    <OpenBoxIcon sx={{ color: '#0d9488', mr: 1, fontSize: 24, flexShrink: 0 }} />
                                    <select
                                        required
                                        value={form.issue_category_id}
                                        onChange={(e) => setForm({ ...form, issue_category_id: e.target.value })}
                                        style={nativeSelectStyle}
                                    >
                                        <option value="">Select Category</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>
                                </BorderBox>
                            </Box>

                            {/* Priority — EMPTY by default */}
                            <Box sx={{ flex: 1, position: 'relative' }}>
                                <FloatLabel text="PRIORITY (P-Level SLA)" color="#3b0764" />
                                <BorderBox borderColor="#3b0764">
                                    <TrafficIcon sx={{ color: '#3b0764', mr: 1, fontSize: 24, flexShrink: 0 }} />
                                    <select
                                        value={form.issue_priority_id}
                                        onChange={(e) => setForm({ ...form, issue_priority_id: e.target.value })}
                                        style={nativeSelectStyle}
                                    >
                                        <option value="">- Select Priority -</option>
                                        {priorities.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                - {p.code} - {p.name}
                                            </option>
                                        ))}
                                    </select>
                                </BorderBox>
                            </Box>
                        </Box>

                        {/* ROW 3 — RESOLUTION DEPARTMENT (Full Width, Standard Grey) */}
                        <Box sx={{ position: 'relative' }}>
                            <FloatLabel text="RESOLUTION DEPARTMENT" color="#64748b" />
                            <Box
                                sx={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    border: '1.5px solid #cbd5e1',
                                    borderRadius: '12px',
                                    px: 1.5,
                                    py: 1.2,
                                    backgroundColor: '#fff',
                                }}
                            >
                                <select
                                    value={form.resolution_department_id}
                                    onChange={(e) => setForm({ ...form, resolution_department_id: e.target.value })}
                                    style={nativeSelectStyle}
                                >
                                    <option value="">Select Department</option>
                                    {departments.map((d) => (
                                        <option key={d.id} value={d.id}>{d.name}</option>
                                    ))}
                                </select>
                            </Box>
                        </Box>

                        {/* ROW 4 — REPORTED BY (Searchable User Selection + Third-Party Switch) */}
                        <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
                            <Box sx={{ flex: 1.2, position: 'relative' }}>
                                <FloatLabel text="REPORTED BY *" color="#3b0764" />
                                <BorderBox borderColor="#3b0764" sx={{ pr: 0.8 }}>
                                    <PersonIcon sx={{ color: '#3b0764', mr: 1, fontSize: 24, flexShrink: 0 }} />
                                    <input
                                        type="text"
                                        required
                                        placeholder="Reported by Employee"
                                        value={form.issue_by}
                                        onChange={(e) => setForm({ ...form, issue_by: e.target.value })}
                                        style={{ ...nativeInputStyle, color: '#3b0764', fontWeight: '700' }}
                                    />
                                    <Button
                                        size="small"
                                        variant="contained"
                                        onClick={() => setUserSelectorOpen(true)}
                                        startIcon={<SearchIcon fontSize="small" />}
                                        sx={{
                                            backgroundColor: '#3b0764',
                                            color: '#fff',
                                            fontSize: '0.72rem',
                                            fontWeight: 'bold',
                                            px: 1.4,
                                            py: 0.4,
                                            textTransform: 'none',
                                            borderRadius: '8px',
                                            whiteSpace: 'nowrap',
                                            flexShrink: 0,
                                            boxShadow: 'none',
                                            '&:hover': { backgroundColor: '#581c87' },
                                        }}
                                    >
                                        Select User
                                    </Button>
                                </BorderBox>
                            </Box>

                            <Box sx={{ flex: 0.8, display: 'flex', alignItems: 'center' }}>
                                <FormControlLabel
                                    control={
                                        <Switch
                                            checked={form.is_third_party_resolver}
                                            onChange={(e) => setForm({ ...form, is_third_party_resolver: e.target.checked })}
                                            sx={{
                                                '& .MuiSwitch-switchBase.Mui-checked': { color: '#0d9488' },
                                                '& .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track': {
                                                    backgroundColor: '#0d9488',
                                                },
                                            }}
                                        />
                                    }
                                    label={
                                        <Typography variant="body2" fontWeight="700" sx={{ color: '#1e293b', fontSize: '0.82rem' }}>
                                            Third-Party Developer Fix
                                        </Typography>
                                    }
                                />
                            </Box>
                        </Box>

                        {/* ROW 5 — DescripTION & STEPS TO... (Full Width, Teal) */}
                        <Box sx={{ position: 'relative' }}>
                            <FloatLabel text="DescripTION & STEPS TO ..." color="#0d9488" />
                            <Box
                                sx={{
                                    border: '2.5px solid #0d9488',
                                    borderRadius: '12px',
                                    p: 1.5,
                                    backgroundColor: '#fff',
                                }}
                            >
                                <textarea
                                    required
                                    rows={4}
                                    placeholder="DescripTION & STEPS TO ..."
                                    value={form.description}
                                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                                    style={{
                                        width: '100%',
                                        border: 'none',
                                        outline: 'none',
                                        fontSize: '0.9rem',
                                        fontWeight: '500',
                                        fontFamily: 'inherit',
                                        resize: 'vertical',
                                        color: '#1e293b',
                                    }}
                                />
                            </Box>
                        </Box>
                    </Box>

                    {/* ── Footer Buttons ────────────────────────────────────── */}
                    <Box
                        sx={{
                            p: 2.5,
                            px: 3,
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            backgroundColor: '#ffffff',
                        }}
                    >
                        {/* Cancel — teal rocket circle */}
                        <IconButton
                            onClick={handleClose}
                            sx={{
                                backgroundColor: '#0d9488',
                                color: '#fff',
                                p: 1.5,
                                '&:hover': { backgroundColor: '#0f766e' },
                            }}
                        >
                            <RocketIcon fontSize="medium" />
                        </IconButton>

                        {/* Submit — teal pill button */}
                        <Button
                            type="submit"
                            variant="contained"
                            startIcon={<RocketIcon />}
                            sx={{
                                backgroundColor: '#0d9488',
                                color: '#fff',
                                borderRadius: '50px',
                                px: 4,
                                py: 1.2,
                                fontWeight: '800',
                                fontSize: '0.9rem',
                                letterSpacing: '0.05em',
                                boxShadow: 'none',
                                '&:hover': { backgroundColor: '#0f766e' },
                            }}
                        >
                            CREATE ISSUE
                        </Button>
                    </Box>
                </form>
            </Dialog>

            {/* ── User Selection Table Modal with Search Function ────────── */}
            <Dialog
                open={userSelectorOpen}
                onClose={() => { setUserSelectorOpen(false); setUserSearch(''); }}
                maxWidth="md"
                fullWidth
                PaperProps={{
                    sx: {
                        borderRadius: '16px',
                        overflow: 'hidden',
                        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.2)',
                    },
                }}
            >
                <DialogTitle
                    sx={{
                        backgroundColor: '#3b0764',
                        color: '#fff',
                        py: 2,
                        px: 3,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                    }}
                >
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <PersonIcon sx={{ fontSize: 28 }} />
                        <Box>
                            <Typography variant="h6" fontWeight="bold" sx={{ fontSize: '1.1rem', lineHeight: 1.2 }}>
                                Select Reported By User
                            </Typography>
                            <Typography variant="caption" sx={{ color: '#e9d5ff' }}>
                                Search and select the employee reporting this issue
                            </Typography>
                        </Box>
                    </Box>
                    {currentUserName && (
                        <Button
                            size="small"
                            variant="outlined"
                            onClick={() => handleSelectUser({ name: currentUserName })}
                            sx={{
                                color: '#fff',
                                borderColor: 'rgba(255,255,255,0.6)',
                                textTransform: 'none',
                                fontSize: '0.78rem',
                                '&:hover': { borderColor: '#fff', backgroundColor: 'rgba(255,255,255,0.1)' },
                            }}
                        >
                            Use Current User ({currentUserName})
                        </Button>
                    )}
                </DialogTitle>

                <DialogContent sx={{ p: 3, backgroundColor: '#f8fafc' }}>
                    {/* Search Input Bar */}
                    <Box sx={{ mb: 2.5, mt: 1 }}>
                        <TextField
                            fullWidth
                            size="small"
                            placeholder="Search employee by name, email, department, or branch..."
                            value={userSearch}
                            onChange={(e) => setUserSearch(e.target.value)}
                            autoFocus
                            InputProps={{
                                startAdornment: (
                                    <InputAdornment position="start">
                                        <SearchIcon color="action" />
                                    </InputAdornment>
                                ),
                                endAdornment: userSearch && (
                                    <InputAdornment position="end">
                                        <IconButton size="small" onClick={() => setUserSearch('')}>
                                            <ClearIcon fontSize="small" />
                                        </IconButton>
                                    </InputAdornment>
                                ),
                                sx: {
                                    backgroundColor: '#fff',
                                    borderRadius: '10px',
                                    fontSize: '0.9rem',
                                },
                            }}
                        />
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
                                    <TableCell align="center" sx={{ width: 100 }}>Action</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {filteredUsers.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} align="center" sx={{ py: 5, color: '#64748b' }}>
                                            <Typography variant="body2">No matching employees found.</Typography>
                                            <Typography variant="caption" color="text.secondary">
                                                You can also type a custom name directly in the Reported By field.
                                            </Typography>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    filteredUsers.map((u) => {
                                        const isCurrent = u.name === currentUserName;
                                        const isSelected = u.name === form.issue_by;
                                        return (
                                            <TableRow
                                                key={u.id}
                                                hover
                                                onClick={() => handleSelectUser(u)}
                                                sx={{
                                                    cursor: 'pointer',
                                                    backgroundColor: isSelected ? '#f3e8ff' : 'inherit',
                                                    '&:hover': { backgroundColor: isSelected ? '#ede9fe' : '#f8fafc' },
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
                                                                backgroundColor: isSelected ? '#3b0764' : '#0d9488',
                                                                color: '#fff',
                                                            }}
                                                        >
                                                            {u.name ? u.name.charAt(0).toUpperCase() : '?'}
                                                        </Avatar>
                                                        <Box>
                                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.8 }}>
                                                                <Typography variant="body2" fontWeight={isSelected ? '800' : '600'} sx={{ color: '#1e293b' }}>
                                                                    {u.name}
                                                                </Typography>
                                                                {isCurrent && (
                                                                    <Chip
                                                                        label="You"
                                                                        size="small"
                                                                        color="primary"
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

                                                {/* Action */}
                                                <TableCell align="center">
                                                    <Button
                                                        size="small"
                                                        variant={isSelected ? 'contained' : 'outlined'}
                                                        color={isSelected ? 'success' : 'primary'}
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            handleSelectUser(u);
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

                <DialogActions sx={{ p: 2, backgroundColor: '#f1f5f9', justifyContent: 'space-between' }}>
                    <Typography variant="caption" color="text.secondary">
                        Showing {filteredUsers.length} employee{filteredUsers.length !== 1 ? 's' : ''}
                    </Typography>
                    <Button onClick={() => { setUserSelectorOpen(false); setUserSearch(''); }} color="inherit">
                        Close
                    </Button>
                </DialogActions>
            </Dialog>
        </>
    );
}
