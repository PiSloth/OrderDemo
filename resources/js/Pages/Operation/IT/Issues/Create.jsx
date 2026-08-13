import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AsideLayout from '@/Layouts/AsideLayout';
import {
    Box,
    Button,
    Card,
    CardContent,
    Grid,
    MenuItem,
    Paper,
    Select,
    TextField,
    Typography,
    InputLabel,
    FormControl,
} from '@mui/material';
import { ArrowBack as ArrowBackIcon, Save as SaveIcon } from '@mui/icons-material';

export default function Create({ categories, priorities, importanceLevels, departments, users }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        issue_category_id: categories[0]?.id || '',
        issue_priority_id: '',
        issue_importance_id: '',
        resolution_department_id: departments[0]?.id || '',
        assigned_user_id: '',
        proposed_solution: '',
        issue_by: '',
        is_third_party_resolver: false,
        due_date: '',
    });

    const selectedPriority = priorities.find((p) => p.id === data.issue_priority_id);
    const isManualDueDate = selectedPriority?.settings?.clock_type === 'manual_schedule' || selectedPriority?.level === 4;

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/operations/it/issues');
    };

    return (
        <AsideLayout title="Create IT Issue">
            <Head title="Create IT Issue" />

            <Box sx={{ p: 3, maxWidth: 900, mx: 'auto' }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Button component={Link} href="/operations/it/issues" startIcon={<ArrowBackIcon />}>
                        Back to Issues List
                    </Button>
                </Box>

                <Paper sx={{ p: 4, borderRadius: 3, boxShadow: 3 }}>
                    <Typography variant="h5" fontWeight="bold" sx={{ mb: 3, color: 'primary.main' }}>
                        Log New IT Issue
                    </Typography>

                    <form onSubmit={handleSubmit}>
                        <Grid container spacing={3}>
                            <Grid item xs={12}>
                                <TextField
                                    label="Issue Title"
                                    required
                                    fullWidth
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    error={Boolean(errors.title)}
                                    helperText={errors.title}
                                />
                            </Grid>

                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth required>
                                    <InputLabel>Category</InputLabel>
                                    <Select
                                        value={data.issue_category_id}
                                        label="Category"
                                        onChange={(e) => setData('issue_category_id', e.target.value)}
                                    >
                                        {categories.map((c) => (
                                            <MenuItem key={c.id} value={c.id}>
                                                {c.name}
                                            </MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </Grid>

                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth required>
                                    <InputLabel>Resolver Type (Who Fixes Issue)</InputLabel>
                                    <Select
                                        value={data.is_third_party_resolver ? 'true' : 'false'}
                                        label="Resolver Type (Who Fixes Issue)"
                                        onChange={(e) => setData('is_third_party_resolver', e.target.value === 'true')}
                                    >
                                        <MenuItem value="false">Internal User / IT Department Fix</MenuItem>
                                        <MenuItem value="true">Third-Party Developer Fix</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>

                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth>
                                    <InputLabel>Priority (P-Level - Can Assign Later)</InputLabel>
                                    <Select
                                        value={data.issue_priority_id}
                                        label="Priority (P-Level - Can Assign Later)"
                                        onChange={(e) => setData('issue_priority_id', e.target.value)}
                                    >
                                        <MenuItem value="">
                                            <em>Unassigned (Assign Later)</em>
                                        </MenuItem>
                                        {priorities.map((p) => (
                                            <MenuItem key={p.id} value={p.id}>
                                                {p.name} (Level {p.level})
                                            </MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </Grid>

                            {isManualDueDate && (
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Manual Target Due Date (Schedule)"
                                        type="datetime-local"
                                        fullWidth
                                        required
                                        InputLabelProps={{ shrink: true }}
                                        value={data.due_date}
                                        onChange={(e) => setData('due_date', e.target.value)}
                                        helperText="Manual schedule priority requires specifying a due date."
                                    />
                                </Grid>
                            )}

                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Reported By (Person / User Name)"
                                    fullWidth
                                    value={data.issue_by}
                                    onChange={(e) => setData('issue_by', e.target.value)}
                                />
                            </Grid>

                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth>
                                    <InputLabel>Assign Staff (Optional)</InputLabel>
                                    <Select
                                        value={data.assigned_user_id}
                                        label="Assign Staff (Optional)"
                                        onChange={(e) => setData('assigned_user_id', e.target.value)}
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
                            </Grid>

                            <Grid item xs={12}>
                                <TextField
                                    label="Issue Description & Steps to Reproduce"
                                    required
                                    multiline
                                    rows={4}
                                    fullWidth
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    error={Boolean(errors.description)}
                                    helperText={errors.description}
                                />
                            </Grid>

                            <Grid item xs={12}>
                                <TextField
                                    label="Proposed Solution / Initial Findings"
                                    multiline
                                    rows={2}
                                    fullWidth
                                    value={data.proposed_solution}
                                    onChange={(e) => setData('proposed_solution', e.target.value)}
                                />
                            </Grid>

                            <Grid item xs={12} sx={{ display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
                                <Button component={Link} href="/operations/it/issues" variant="outlined">
                                    Cancel
                                </Button>
                                <Button type="submit" variant="contained" color="primary" startIcon={<SaveIcon />} disabled={processing}>
                                    Submit Issue
                                </Button>
                            </Grid>
                        </Grid>
                    </form>
                </Paper>
            </Box>
        </AsideLayout>
    );
}
