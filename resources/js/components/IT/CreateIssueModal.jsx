import React, { useState, useEffect, useMemo } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    Box,
    Button,
    Dialog,
    IconButton,
    Switch,
    FormControlLabel,
    Typography,
} from '@mui/material';
import {
    VpnKey as KeyIcon,
    Inventory as OpenBoxIcon,
    Traffic as TrafficIcon,
    RocketLaunch as RocketIcon,
    Person as PersonIcon,
    Search as SearchIcon,
} from '@mui/icons-material';
import UserSelectModal from './UserSelectModal';

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
    open = false,
    onClose,
    onSuccess,
    categories = [],
    priorities = [],
    departments = [],
    importanceLevels = [],
    users = [],
    auth,
}) {
    const pageProps = usePage()?.props || {};

    const safeUsers = useMemo(() => {
        if (Array.isArray(users) && users.length > 0) return users;
        if (Array.isArray(pageProps.users)) return pageProps.users;
        if (pageProps.users?.data && Array.isArray(pageProps.users.data)) return pageProps.users.data;
        return [];
    }, [users, pageProps.users]);

    const safeDepartments = useMemo(() => {
        if (Array.isArray(departments) && departments.length > 0) return departments;
        if (Array.isArray(pageProps.departments)) return pageProps.departments;
        if (pageProps.departments?.data && Array.isArray(pageProps.departments.data)) return pageProps.departments.data;
        return [];
    }, [departments, pageProps.departments]);

    const safeCategories = useMemo(() => {
        if (Array.isArray(categories) && categories.length > 0) return categories;
        if (Array.isArray(pageProps.categories)) return pageProps.categories;
        return [];
    }, [categories, pageProps.categories]);

    const safePriorities = useMemo(() => {
        if (Array.isArray(priorities) && priorities.length > 0) return priorities;
        if (Array.isArray(pageProps.priorities)) return pageProps.priorities;
        return [];
    }, [priorities, pageProps.priorities]);

    const safeImportanceLevels = useMemo(() => {
        if (Array.isArray(importanceLevels) && importanceLevels.length > 0) return importanceLevels;
        if (Array.isArray(pageProps.importanceLevels)) return pageProps.importanceLevels;
        return [];
    }, [importanceLevels, pageProps.importanceLevels]);

    const currentUserName =
        auth?.user?.name ||
        pageProps.auth?.user?.name ||
        pageProps.auth_user?.name ||
        '';

    const defaultForm = {
        title: '',
        description: '',
        issue_category_id: safeCategories.length > 0 ? safeCategories[0].id : '',
        issue_priority_id: '', // EMPTY BY DEFAULT
        issue_importance_id: safeImportanceLevels.length > 0 ? safeImportanceLevels[0].id : '',
        resolution_department_id: safeDepartments.length > 0 ? safeDepartments[0].id : '',
        assigned_user_id: '',
        issue_by: currentUserName,
        is_third_party_resolver: false,
    };

    const [form, setForm] = useState(defaultForm);
    const [userSelectorOpen, setUserSelectorOpen] = useState(false);

    // Reset form with current user as default when opening modal
    useEffect(() => {
        if (open) {
            setForm((prev) => ({
                ...prev,
                issue_by: prev.issue_by || currentUserName,
                issue_category_id: prev.issue_category_id || (safeCategories.length > 0 ? safeCategories[0].id : ''),
                issue_importance_id: prev.issue_importance_id || (safeImportanceLevels.length > 0 ? safeImportanceLevels[0].id : ''),
                resolution_department_id: prev.resolution_department_id || (safeDepartments.length > 0 ? safeDepartments[0].id : ''),
            }));
        }
    }, [open, currentUserName, safeCategories, safeImportanceLevels, safeDepartments]);

    const handleClose = () => {
        setForm(defaultForm);
        setUserSelectorOpen(false);
        if (typeof onClose === 'function') onClose();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/operations/it/issues', form, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setForm(defaultForm);
                setUserSelectorOpen(false);
                if (typeof onClose === 'function') onClose();
                if (typeof onSuccess === 'function') onSuccess();
            },
        });
    };

    const handleSelectUser = (user) => {
        if (user && user.name) {
            setForm((prev) => ({
                ...prev,
                issue_by: user.name,
            }));
        }
        setUserSelectorOpen(false);
    };

    return (
        <>
            <Dialog
                open={Boolean(open)}
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
                        type="button"
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
                                        {safeCategories.map((c) => (
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
                                        {safePriorities.map((p) => (
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
                                    {safeDepartments.map((d) => (
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
                                        type="button"
                                        size="small"
                                        variant="contained"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            setUserSelectorOpen(true);
                                        }}
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
                            type="button"
                            onClick={(e) => {
                                e.preventDefault();
                                handleClose();
                            }}
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

            {/* ── User Selection Modal with Department Multi-Select & Real-Time Search ── */}
            <UserSelectModal
                open={userSelectorOpen}
                onClose={() => setUserSelectorOpen(false)}
                onSelect={handleSelectUser}
                title="Select Reported By User"
                subtitle="Search and select the employee reporting this issue"
                selectedUserName={form.issue_by}
                users={safeUsers}
                departments={safeDepartments}
                currentUserName={currentUserName}
            />
        </>
    );
}
