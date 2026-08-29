import React, { useState, useEffect, useMemo } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Grid,
  Paper,
  TextField,
  MenuItem,
  Button,
  Chip,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer,
  TablePagination,
  LinearProgress,
  Stack,
  Avatar,
  IconButton,
  Tooltip,
  Divider,
  InputAdornment,
  Collapse,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Badge,
  Tabs,
  Tab
} from '@mui/material';

import {
  People as PeopleIcon,
  CheckCircle as CheckCircleIcon,
  Schedule as ScheduleIcon,
  Warning as WarningIcon,
  FilterList as FilterIcon,
  Refresh as RefreshIcon,
  Search as SearchIcon,
  School as SchoolIcon,
  AssignmentTurnedIn as AssignmentTurnedInIcon,
  Quiz as QuizIcon,
  Clear as ClearIcon,
  EmojiEvents as TrophyIcon,
  Print as PrintIcon,
  RestartAlt as ResetIcon,
  Tune as FilterTuneIcon,
  Close as CloseIcon,
  Apartment as DepartmentIcon,
  Work as PositionIcon,
  CalendarMonth as CalendarIcon,
  History as HistoryIcon,
  Check as CheckIcon,
  Cancel as CancelIcon
} from '@mui/icons-material';

export default function Compliance({
  metrics = {},
  departmentStats = [],
  testPerformance = [],
  matrix = {},
  filterOptions = {},
  filters = {},
  permissions = { can_view_attempts: true }
}) {
  const [filterValues, setFilterValues] = useState({
    department_id: filters.department_id || '',
    office_position_id: filters.office_position_id || '',
    training_id: filters.training_id || '',
    status: filters.status || '',
    trigger_type: filters.trigger_type || '',
    due_from: filters.due_from || '',
    due_to: filters.due_to || '',
    search: filters.search || '',
  });

  const [attemptHistoryModalOpen, setAttemptHistoryModalOpen] = useState(false);
  const [selectedAssignmentForAttempts, setSelectedAssignmentForAttempts] = useState(null);
  const [selectedAttemptIdx, setSelectedAttemptIdx] = useState(0);

  const handleOpenAttemptHistory = (row) => {
    setSelectedAssignmentForAttempts(row);
    setSelectedAttemptIdx(0);
    setAttemptHistoryModalOpen(true);
  };

  // Keep state synced with server filters
  useEffect(() => {
    setFilterValues({
      department_id: filters.department_id || '',
      office_position_id: filters.office_position_id || '',
      training_id: filters.training_id || '',
      status: filters.status || '',
      trigger_type: filters.trigger_type || '',
      due_from: filters.due_from || '',
      due_to: filters.due_to || '',
      search: filters.search || '',
    });
  }, [filters]);

  const activeFilterCount = useMemo(() => {
    return Object.entries(filterValues).filter(([key, val]) => val !== undefined && val !== null && String(val).trim() !== '').length;
  }, [filterValues]);

  const handleFilterChange = (field, value) => {
    setFilterValues((prev) => ({ ...prev, [field]: value }));
  };

  const applyFilters = (e) => {
    if (e) e.preventDefault();
    router.get('/training/dashboard', filterValues, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  const removeSpecificFilter = (field) => {
    const updated = { ...filterValues, [field]: '' };
    setFilterValues(updated);
    router.get('/training/dashboard', updated, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  const resetFilters = () => {
    const emptyFilters = {
      department_id: '',
      office_position_id: '',
      training_id: '',
      status: '',
      trigger_type: '',
      due_from: '',
      due_to: '',
      search: '',
    };
    setFilterValues(emptyFilters);
    router.get('/training/dashboard', {}, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  const getStatusChip = (status) => {
    switch (status) {
      case 'COMPLETED':
        return <Chip label="COMPLETED" size="small" color="success" sx={{ fontWeight: 700 }} />;
      case 'IN_PROGRESS':
        return <Chip label="IN PROGRESS" size="small" color="primary" sx={{ fontWeight: 700 }} />;
      case 'PENDING':
        return <Chip label="PENDING" size="small" color="warning" sx={{ fontWeight: 700 }} />;
      case 'OVERDUE':
        return <Chip label="OVERDUE" size="small" color="error" sx={{ fontWeight: 700 }} />;
      default:
        return <Chip label={status} size="small" sx={{ fontWeight: 600 }} />;
    }
  };

  const getTriggerBadge = (trigger) => {
    if (!trigger) return <span className="text-slate-400">—</span>;
    const type = trigger.trigger_type;
    const colors = {
      NEW_USER: 'info',
      WORKFLOW_CHANGE: 'secondary',
      RETRAINING: 'warning',
      MANUAL: 'default',
    };

    return (
      <Tooltip title={trigger.reason || 'No description'}>
        <Chip
          label={type.replace('_', ' ')}
          size="small"
          color={colors[type] || 'default'}
          variant="outlined"
          sx={{ fontSize: '0.7rem', fontWeight: 600 }}
        />
      </Tooltip>
    );
  };

  return (
    <AsideLayout title="Training Compliance Dashboard">
      <Head title="Training Compliance Dashboard" />

      <Box className="max-w-7xl mx-auto space-y-6">
        {/* Top Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <SchoolIcon className="text-sky-600" />
              Training Compliance
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Executive overview of employee training status, assessment performance, and organizational compliance.
            </Typography>
          </div>

          <Stack direction="row" spacing={1.5}>
            <Button
              variant="outlined"
              size="small"
              component={Link}
              href="/training/trainings"
              startIcon={<SchoolIcon />}
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              Training Catalog
            </Button>
            <Button
              variant="contained"
              size="small"
              component={Link}
              href="/training/sessions"
              startIcon={<ScheduleIcon />}
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              Manage Sessions
            </Button>
          </Stack>
        </Box>

        {/* 4 Core Stat Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Active Users */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <Typography variant="caption" className="font-bold text-slate-500 uppercase tracking-wider">
                  Active Users
                </Typography>
                <Typography variant="h4" className="font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                  {metrics.active_users ?? 0}
                </Typography>
                <Typography variant="caption" className="text-slate-400">
                  Total active workforce
                </Typography>
              </div>
              <Avatar sx={{ bgcolor: 'sky.100', color: 'sky.700', width: 48, height: 48 }}>
                <PeopleIcon />
              </Avatar>
            </CardContent>
          </Card>

          {/* Completion Rate */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <Typography variant="caption" className="font-bold text-emerald-600 uppercase tracking-wider">
                  Overall Completion
                </Typography>
                <Typography variant="h4" className="font-extrabold text-emerald-600 mt-1">
                  {metrics.completion_rate ?? 0}%
                </Typography>
                <Typography variant="caption" className="text-slate-400">
                  {metrics.completed ?? 0} of {metrics.total ?? 0} assignments
                </Typography>
              </div>
              <Avatar sx={{ bgcolor: 'emerald.100', color: 'emerald.700', width: 48, height: 48 }}>
                <CheckCircleIcon />
              </Avatar>
            </CardContent>
          </Card>

          {/* Upcoming */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <Typography variant="caption" className="font-bold text-amber-600 uppercase tracking-wider">
                  Upcoming Due
                </Typography>
                <Typography variant="h4" className="font-extrabold text-amber-600 mt-1">
                  {metrics.upcoming ?? 0}
                </Typography>
                <Typography variant="caption" className="text-slate-400">
                  Due within next 14 days
                </Typography>
              </div>
              <Avatar sx={{ bgcolor: 'amber.100', color: 'amber.700', width: 48, height: 48 }}>
                <ScheduleIcon />
              </Avatar>
            </CardContent>
          </Card>

          {/* Overdue */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <Typography variant="caption" className="font-bold text-rose-600 uppercase tracking-wider">
                  Overdue
                </Typography>
                <Typography variant="h4" className="font-extrabold text-rose-600 mt-1">
                  {metrics.overdue ?? 0}
                </Typography>
                <Typography variant="caption" className="text-slate-400">
                  Past requirement deadline
                </Typography>
              </div>
              <Avatar sx={{ bgcolor: 'rose.100', color: 'rose.700', width: 48, height: 48 }}>
                <WarningIcon />
              </Avatar>
            </CardContent>
          </Card>
        </div>

        {/* Section 2: Department Compliance & Test Performance */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Department Breakdown */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-5">
              <Typography variant="subtitle1" className="font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <AssignmentTurnedInIcon color="primary" fontSize="small" />
                Department Compliance Rates
              </Typography>

              <div className="space-y-4">
                {departmentStats.map((dept) => (
                  <div key={dept.id} className="space-y-1.5">
                    <div className="flex justify-between text-xs font-semibold">
                      <span className="text-slate-700 dark:text-slate-300">{dept.name}</span>
                      <span className="text-slate-900 dark:text-slate-100 font-bold">
                        {dept.completion_rate}% ({dept.completed}/{dept.total_assignments})
                      </span>
                    </div>
                    <LinearProgress
                      variant="determinate"
                      value={dept.completion_rate}
                      color={
                        dept.completion_rate >= 90
                          ? 'success'
                          : dept.completion_rate >= 75
                          ? 'primary'
                          : 'warning'
                      }
                      sx={{ height: 8, borderRadius: 4 }}
                    />
                  </div>
                ))}
                {departmentStats.length === 0 && (
                  <Typography variant="body2" className="text-slate-400 py-4 text-center">
                    No department data available.
                  </Typography>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Test Performance */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-5">
              <Typography variant="subtitle1" className="font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <QuizIcon color="secondary" fontSize="small" />
                Test Performance (Pass Rates)
              </Typography>

              <div className="overflow-x-auto">
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell className="font-bold text-xs">Training Module</TableCell>
                      <TableCell className="font-bold text-xs text-center">Attempts</TableCell>
                      <TableCell className="font-bold text-xs text-center">Passed</TableCell>
                      <TableCell className="font-bold text-xs text-right">Pass Rate</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {testPerformance.map((t) => (
                      <TableRow key={t.id} hover>
                        <TableCell className="text-xs font-medium">
                          <div className="font-bold text-slate-800 dark:text-slate-200">{t.title}</div>
                          <span className="text-[11px] text-slate-400">{t.code}</span>
                        </TableCell>
                        <TableCell className="text-xs text-center">{t.total_attempts}</TableCell>
                        <TableCell className="text-xs text-center font-semibold text-emerald-600">
                          {t.passed_attempts}
                        </TableCell>
                        <TableCell className="text-xs text-right">
                          <Chip
                            label={`${t.pass_rate}%`}
                            size="small"
                            color={t.pass_rate >= 80 ? 'success' : t.pass_rate >= 60 ? 'warning' : 'default'}
                            sx={{ fontWeight: 700, fontSize: '0.75rem' }}
                          />
                        </TableCell>
                      </TableRow>
                    ))}
                    {testPerformance.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={4} align="center" className="text-slate-400 py-4">
                          No test performance recorded yet.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Section 3: Training Compliance Matrix with Multi-Filter */}
        <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
          <CardContent className="p-5 sm:p-6 space-y-5">
            {/* Header with Title & Active Filter Summary */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
              <div className="flex items-center gap-2.5">
                <div className="p-2 rounded-xl bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400">
                  <FilterTuneIcon fontSize="small" />
                </div>
                <div>
                  <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100 leading-none">
                    Employee Compliance Matrix
                  </Typography>
                  <Typography variant="caption" className="text-slate-400 mt-0.5 block">
                    Detailed tracking of assigned trainings, tests, and completion statuses
                  </Typography>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <Chip
                  label={`${matrix.total ?? 0} total records`}
                  size="small"
                  variant="outlined"
                  sx={{ fontWeight: 600, fontSize: '0.75rem' }}
                />
                {activeFilterCount > 0 && (
                  <Chip
                    label={`${activeFilterCount} active filter${activeFilterCount > 1 ? 's' : ''}`}
                    size="small"
                    color="primary"
                    sx={{ fontWeight: 700, fontSize: '0.75rem' }}
                  />
                )}
              </div>
            </div>

            {/* Filter Controls Form */}
            <Paper
              component="form"
              onSubmit={applyFilters}
              elevation={0}
              className="p-4 sm:p-5 bg-slate-50/80 dark:bg-slate-800/40 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 space-y-4"
            >
              {/* Row 1: Search Employee & Training */}
              <div>
                <TextField
                  fullWidth
                  size="small"
                  label="Search Employee / Code / Module"
                  value={filterValues.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  placeholder="e.g. John Doe, IT, TR-SOP-01, Compliance..."
                  InputProps={{
                    startAdornment: (
                      <InputAdornment position="start">
                        <SearchIcon fontSize="small" className="text-slate-400" />
                      </InputAdornment>
                    ),
                    endAdornment: filterValues.search ? (
                      <InputAdornment position="end">
                        <IconButton
                          size="small"
                          onClick={() => {
                            handleFilterChange('search', '');
                            if (filters.search) removeSpecificFilter('search');
                          }}
                          edge="end"
                        >
                          <ClearIcon fontSize="small" />
                        </IconButton>
                      </InputAdornment>
                    ) : null,
                  }}
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: 2,
                      bgcolor: 'background.paper',
                    },
                  }}
                />
              </div>

              {/* Row 2: Categorical Select Dropdowns */}
              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                {/* Department */}
                <TextField
                  select
                  fullWidth
                  size="small"
                  label="Department"
                  value={filterValues.department_id}
                  onChange={(e) => handleFilterChange('department_id', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                >
                  <MenuItem value="">
                    <em>All Departments</em>
                  </MenuItem>
                  {(filterOptions.departments || []).map((d) => (
                    <MenuItem key={d.id} value={String(d.id)}>
                      {d.name}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Office Position */}
                <TextField
                  select
                  fullWidth
                  size="small"
                  label="Office Position"
                  value={filterValues.office_position_id}
                  onChange={(e) => handleFilterChange('office_position_id', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                >
                  <MenuItem value="">
                    <em>All Positions</em>
                  </MenuItem>
                  {(filterOptions.officePositions || []).map((p) => (
                    <MenuItem key={p.id} value={String(p.id)}>
                      {p.name}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Training */}
                <TextField
                  select
                  fullWidth
                  size="small"
                  label="Training Module"
                  value={filterValues.training_id}
                  onChange={(e) => handleFilterChange('training_id', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                >
                  <MenuItem value="">
                    <em>All Trainings</em>
                  </MenuItem>
                  {(filterOptions.trainings || []).map((t) => (
                    <MenuItem key={t.id} value={String(t.id)}>
                      {t.code} - {t.title}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Status */}
                <TextField
                  select
                  fullWidth
                  size="small"
                  label="Status"
                  value={filterValues.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                >
                  <MenuItem value="">
                    <em>All Statuses</em>
                  </MenuItem>
                  {(filterOptions.statuses || []).map((s) => (
                    <MenuItem key={s} value={s}>
                      {s}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Trigger Type */}
                <TextField
                  select
                  fullWidth
                  size="small"
                  label="Trigger Type"
                  value={filterValues.trigger_type}
                  onChange={(e) => handleFilterChange('trigger_type', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                >
                  <MenuItem value="">
                    <em>All Trigger Types</em>
                  </MenuItem>
                  {(filterOptions.triggerTypes || []).map((trig) => (
                    <MenuItem key={trig} value={trig}>
                      {trig.replace('_', ' ')}
                    </MenuItem>
                  ))}
                </TextField>
              </div>

              {/* Row 3: Due Dates & Form Actions */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-center pt-1">
                {/* Due From */}
                <TextField
                  type="date"
                  fullWidth
                  size="small"
                  label="Due Date From"
                  InputLabelProps={{ shrink: true }}
                  value={filterValues.due_from}
                  onChange={(e) => handleFilterChange('due_from', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                />

                {/* Due To */}
                <TextField
                  type="date"
                  fullWidth
                  size="small"
                  label="Due Date To"
                  InputLabelProps={{ shrink: true }}
                  value={filterValues.due_to}
                  onChange={(e) => handleFilterChange('due_to', e.target.value)}
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2, bgcolor: 'background.paper' } }}
                />

                {/* Action Buttons */}
                <div className="sm:col-span-2 flex items-center justify-end gap-2.5">
                  <Button
                    type="button"
                    size="small"
                    variant="outlined"
                    color="inherit"
                    onClick={resetFilters}
                    startIcon={<ResetIcon fontSize="small" />}
                    disabled={activeFilterCount === 0}
                    sx={{
                      textTransform: 'none',
                      borderRadius: 2,
                      px: 2,
                      py: 0.8,
                      borderColor: 'slate.300',
                      fontWeight: 600
                    }}
                  >
                    Reset Filters
                  </Button>
                  <Button
                    type="submit"
                    size="small"
                    variant="contained"
                    color="primary"
                    startIcon={<FilterIcon fontSize="small" />}
                    sx={{
                      textTransform: 'none',
                      borderRadius: 2,
                      px: 2.5,
                      py: 0.8,
                      fontWeight: 700,
                      boxShadow: '0 2px 8px rgba(2, 132, 199, 0.25)'
                    }}
                  >
                    Apply Filters
                  </Button>
                </div>
              </div>

              {/* Active Filter Chips Pills */}
              {activeFilterCount > 0 && (
                <div className="pt-2 border-t border-slate-200/60 dark:border-slate-700/50 flex items-center flex-wrap gap-2">
                  <span className="text-xs font-semibold text-slate-500 dark:text-slate-400 mr-1">
                    Active Filters:
                  </span>

                  {filterValues.search && (
                    <Chip
                      size="small"
                      label={`Search: "${filterValues.search}"`}
                      onDelete={() => removeSpecificFilter('search')}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  {filterValues.department_id && (
                    <Chip
                      size="small"
                      label={`Dept: ${filterOptions.departments?.find((d) => String(d.id) === String(filterValues.department_id))?.name || filterValues.department_id}`}
                      onDelete={() => removeSpecificFilter('department_id')}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  {filterValues.office_position_id && (
                    <Chip
                      size="small"
                      label={`Position: ${filterOptions.officePositions?.find((p) => String(p.id) === String(filterValues.office_position_id))?.name || filterValues.office_position_id}`}
                      onDelete={() => removeSpecificFilter('office_position_id')}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  {filterValues.training_id && (
                    <Chip
                      size="small"
                      label={`Training: ${filterOptions.trainings?.find((t) => String(t.id) === String(filterValues.training_id))?.code || filterValues.training_id}`}
                      onDelete={() => removeSpecificFilter('training_id')}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  {filterValues.status && (
                    <Chip
                      size="small"
                      label={`Status: ${filterValues.status}`}
                      onDelete={() => removeSpecificFilter('status')}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  {filterValues.trigger_type && (
                    <Chip
                      size="small"
                      label={`Trigger: ${filterValues.trigger_type.replace('_', ' ')}`}
                      onDelete={() => removeSpecificFilter('trigger_type')}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  {(filterValues.due_from || filterValues.due_to) && (
                    <Chip
                      size="small"
                      label={`Due: ${filterValues.due_from || '...'} → ${filterValues.due_to || '...'}`}
                      onDelete={() => {
                        const updated = { ...filterValues, due_from: '', due_to: '' };
                        setFilterValues(updated);
                        router.get('/training/dashboard', updated, { preserveState: true, preserveScroll: true, replace: true });
                      }}
                      color="primary"
                      variant="outlined"
                      sx={{ borderRadius: 1.5, fontSize: '0.75rem' }}
                    />
                  )}

                  <Button
                    size="small"
                    variant="text"
                    color="error"
                    onClick={resetFilters}
                    sx={{ textTransform: 'none', fontSize: '0.75rem', p: 0.5, minWidth: 'auto' }}
                  >
                    Clear All
                  </Button>
                </div>
              )}
            </Paper>

            {/* Matrix Data Table */}
            <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
              <Table size="small">
                <TableHead className="bg-slate-100 dark:bg-slate-800/80">
                  <TableRow>
                    <TableCell className="font-bold text-xs py-3">Employee</TableCell>
                    <TableCell className="font-bold text-xs py-3">Department & Position</TableCell>
                    <TableCell className="font-bold text-xs py-3">Training Module</TableCell>
                    <TableCell className="font-bold text-xs py-3">Trigger / Source</TableCell>
                    <TableCell className="font-bold text-xs py-3">Due Date</TableCell>
                    <TableCell className="font-bold text-xs py-3">Latest Test</TableCell>
                    <TableCell className="font-bold text-xs py-3 text-center">Status</TableCell>
                    <TableCell className="font-bold text-xs py-3 text-center">Scorecard & Attempts</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {(matrix.data || []).map((row) => {
                    const latestAttempt = row.test_attempts?.[0];
                    return (
                      <TableRow key={row.id} hover className="transition-colors">
                        {/* Employee */}
                        <TableCell className="py-2.5">
                          <div className="flex items-center gap-2.5">
                            <Avatar sx={{ width: 30, height: 30, fontSize: '0.8rem', bgcolor: 'primary.main', fontWeight: 700 }}>
                              {row.user?.name?.charAt(0) || 'U'}
                            </Avatar>
                            <div>
                              <div className="text-xs font-bold text-slate-800 dark:text-slate-100">
                                {row.user?.name}
                              </div>
                              <div className="text-[11px] text-slate-400">{row.user?.email}</div>
                            </div>
                          </div>
                        </TableCell>

                        {/* Department & Position */}
                        <TableCell className="text-xs py-2.5">
                          <div className="font-semibold text-slate-700 dark:text-slate-300">
                            {row.user?.department?.name || 'Unassigned Dept'}
                          </div>
                          <div className="text-[11px] text-slate-400">
                            {row.user?.office_position?.name || 'Unassigned Position'}
                          </div>
                        </TableCell>

                        {/* Training */}
                        <TableCell className="text-xs py-2.5">
                          <div className="font-bold text-slate-900 dark:text-slate-100">
                            {row.training?.title}
                          </div>
                          <div className="text-[11px] font-mono text-slate-400">{row.training?.code}</div>
                        </TableCell>

                        {/* Trigger */}
                        <TableCell className="text-xs py-2.5">
                          {getTriggerBadge(row.trigger)}
                          {row.trigger?.reason && (
                            <div className="text-[11px] text-slate-500 truncate max-w-[180px]">
                              {row.trigger.reason}
                            </div>
                          )}
                        </TableCell>

                        {/* Due Date */}
                        <TableCell className="text-xs py-2.5">
                          <div className="font-medium text-slate-700 dark:text-slate-300">
                            {row.due_date || '—'}
                          </div>
                          {row.completed_at && (
                            <div className="text-[11px] text-emerald-600 font-medium">
                              Done: {row.completed_at.substring(0, 10)}
                            </div>
                          )}
                        </TableCell>

                        {/* Latest Test */}
                        <TableCell className="text-xs py-2.5">
                          {latestAttempt ? (
                            <div className="space-y-0.5">
                              <span
                                className={`font-bold ${
                                  latestAttempt.result === 'PASSED' ? 'text-emerald-600' : 'text-rose-600'
                                }`}
                              >
                                {latestAttempt.percentage}% ({latestAttempt.result})
                              </span>
                              <div className="text-[10px] text-slate-400">
                                Attempt #{latestAttempt.attempt_number}
                              </div>
                            </div>
                          ) : (
                            <span className="text-slate-400">—</span>
                          )}
                        </TableCell>

                        {/* Status */}
                        <TableCell align="center" className="py-2.5">
                          {getStatusChip(row.status)}
                        </TableCell>

                        {/* Scorecard & Attempt History Links */}
                        <TableCell align="center" className="py-2.5">
                          <Stack direction="row" spacing={0.5} justifyContent="center" alignItems="center">
                            {permissions?.can_view_attempts && (
                              <Tooltip title={`View Test Attempts History (${row.test_attempts?.length || 0})`}>
                                <span>
                                  <IconButton
                                    size="small"
                                    color={(row.test_attempts?.length || 0) > 0 ? 'primary' : 'default'}
                                    disabled={!row.test_attempts || row.test_attempts.length === 0}
                                    onClick={() => handleOpenAttemptHistory(row)}
                                    sx={{ '&:hover': { bgcolor: 'sky.50' } }}
                                  >
                                    <Badge
                                      badgeContent={row.test_attempts?.length || 0}
                                      color="primary"
                                      sx={{ '& .MuiBadge-badge': { fontSize: '0.65rem', height: 16, minWidth: 16, px: 0.5 } }}
                                    >
                                      <HistoryIcon fontSize="small" />
                                    </Badge>
                                  </IconButton>
                                </span>
                              </Tooltip>
                            )}

                            <Tooltip title="View International Scorecard & Print Certificate">
                              <IconButton
                                size="small"
                                component={Link}
                                href={`/training/assignments/${row.id}/scorecard`}
                                sx={{ color: 'amber.700', '&:hover': { bgcolor: 'amber.50' } }}
                              >
                                <TrophyIcon fontSize="small" />
                              </IconButton>
                            </Tooltip>
                          </Stack>
                        </TableCell>
                      </TableRow>
                    );
                  })}

                  {(matrix.data || []).length === 0 && (
                    <TableRow>
                      <TableCell colSpan={8} align="center" className="text-slate-400 py-12">
                        <div className="flex flex-col items-center justify-center space-y-2">
                          <FilterIcon className="text-slate-300" sx={{ fontSize: 40 }} />
                          <Typography variant="body2" className="font-medium">
                            No compliance records found matching your filters.
                          </Typography>
                          {activeFilterCount > 0 && (
                            <Button size="small" variant="text" onClick={resetFilters} sx={{ textTransform: 'none' }}>
                              Reset All Filters
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>

            {/* Pagination */}
            {matrix.links && matrix.links.length > 3 && (
              <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                <Typography variant="caption" className="text-slate-500 dark:text-slate-400">
                  Showing records {(matrix.current_page - 1) * matrix.per_page + 1} to{' '}
                  {Math.min(matrix.current_page * matrix.per_page, matrix.total)} of {matrix.total}
                </Typography>
                <Stack direction="row" spacing={0.5} className="flex-wrap">
                  {matrix.links.map((link, idx) => (
                    <Button
                      key={idx}
                      size="small"
                      variant={link.active ? 'contained' : 'outlined'}
                      disabled={!link.url}
                      onClick={() =>
                        link.url &&
                        router.get(link.url, {}, { preserveState: true, preserveScroll: true, replace: true })
                      }
                      sx={{ minWidth: 32, textTransform: 'none', px: 1.2, borderRadius: 1.5 }}
                    >
                      <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </Button>
                  ))}
                </Stack>
              </div>
            )}
          </CardContent>
        </Card>
      </Box>

      {/* Administrator Attempts History Modal */}
      <Dialog
        open={attemptHistoryModalOpen}
        onClose={() => setAttemptHistoryModalOpen(false)}
        maxWidth="md"
        fullWidth
        PaperProps={{
          sx: { borderRadius: 3, p: 1 },
        }}
      >
        {selectedAssignmentForAttempts && (
          <>
            <DialogTitle className="flex items-center justify-between border-b pb-3">
              <div>
                <Typography variant="h6" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                  <HistoryIcon className="text-sky-600" />
                  Test Attempt History: {selectedAssignmentForAttempts.user?.name}
                </Typography>
                <Typography variant="caption" className="text-slate-500 block">
                  Module: <b>{selectedAssignmentForAttempts.training?.title}</b> ({selectedAssignmentForAttempts.training?.code}) • Passing Benchmark: <b>{selectedAssignmentForAttempts.training?.passing_score || 80}%</b>
                </Typography>
              </div>

              <IconButton size="small" onClick={() => setAttemptHistoryModalOpen(false)}>
                <CloseIcon fontSize="small" />
              </IconButton>
            </DialogTitle>

            <DialogContent className="space-y-5 pt-4">
              {/* Attempt Selector Tabs */}
              {selectedAssignmentForAttempts.test_attempts && selectedAssignmentForAttempts.test_attempts.length > 0 ? (
                <>
                  <div className="flex items-center justify-between gap-2 flex-wrap bg-slate-50 dark:bg-slate-800 p-2 rounded-xl">
                    <span className="text-xs font-bold text-slate-500 uppercase tracking-wider pl-2">
                      Total Attempts ({selectedAssignmentForAttempts.test_attempts.length}):
                    </span>
                    <Stack direction="row" spacing={1} flexWrap="wrap">
                      {selectedAssignmentForAttempts.test_attempts.map((att, idx) => (
                        <Button
                          key={att.id || idx}
                          size="small"
                          variant={selectedAttemptIdx === idx ? 'contained' : 'outlined'}
                          color={att.result === 'PASSED' ? 'success' : 'inherit'}
                          onClick={() => setSelectedAttemptIdx(idx)}
                          sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700, fontSize: '0.75rem' }}
                        >
                          Attempt #{att.attempt_number || selectedAssignmentForAttempts.test_attempts.length - idx} ({att.percentage}%)
                        </Button>
                      ))}
                    </Stack>
                  </div>

                  {(() => {
                    const currentAttempt = selectedAssignmentForAttempts.test_attempts[selectedAttemptIdx] || selectedAssignmentForAttempts.test_attempts[0];
                    if (!currentAttempt) return null;

                    return (
                      <div className="space-y-4">
                        {/* Summary Header */}
                        <div
                          className={`p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 ${
                            currentAttempt.result === 'PASSED'
                              ? 'bg-emerald-50/70 border-emerald-300 dark:bg-emerald-950/30 dark:border-emerald-800'
                              : 'bg-rose-50/70 border-rose-300 dark:bg-rose-950/30 dark:border-rose-800'
                          }`}
                        >
                          <div className="flex items-center gap-3">
                            <div
                              className={`w-12 h-12 rounded-xl flex items-center justify-center font-bold text-white ${
                                currentAttempt.result === 'PASSED' ? 'bg-emerald-600' : 'bg-rose-600'
                              }`}
                            >
                              {currentAttempt.result === 'PASSED' ? <TrophyIcon /> : <CancelIcon />}
                            </div>
                            <div>
                              <div className="text-base font-extrabold text-slate-900 dark:text-slate-100">
                                Score: {currentAttempt.score} / {currentAttempt.max_score} ({currentAttempt.percentage}%) —{' '}
                                <span className={currentAttempt.result === 'PASSED' ? 'text-emerald-700 font-black' : 'text-rose-700 font-black'}>
                                  {currentAttempt.result}
                                </span>
                              </div>
                              <div className="text-xs text-slate-500 dark:text-slate-400">
                                Submitted on {currentAttempt.submitted_at ? new Date(currentAttempt.submitted_at).toLocaleString() : 'In Progress'}
                                {currentAttempt.session?.trainer && ` • Trainer: ${currentAttempt.session.trainer.name}`}
                              </div>
                            </div>
                          </div>

                          <Button
                            variant="outlined"
                            size="small"
                            component={Link}
                            href={`/training/assignments/${selectedAssignmentForAttempts.id}/scorecard`}
                            startIcon={<PrintIcon />}
                            sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700 }}
                          >
                            Scorecard
                          </Button>
                        </div>

                        {/* Question Breakdown */}
                        <div className="space-y-3">
                          <Typography variant="subtitle2" className="font-bold text-slate-800 dark:text-slate-200">
                            Evaluated Questions & Verified Correct Answers:
                          </Typography>

                          {(currentAttempt.answers || []).map((ans, qIdx) => {
                            const q = ans.question || {};
                            const allOptions = q.options || [];
                            const correctOptions = allOptions.filter((o) => o.is_correct);

                            const selectedAnswers =
                              ans.selected_options && ans.selected_options.length > 0
                                ? ans.selected_options.map((opt) => opt.answer).join(', ')
                                : ans.selected_option?.answer || (
                                    <span className="italic text-slate-400">No answer selected</span>
                                  );

                            const correctAnswersText =
                              correctOptions.length > 0
                                ? correctOptions.map((o) => o.answer).join(', ')
                                : 'No correct answer specified';

                            return (
                              <Paper
                                key={ans.id || qIdx}
                                elevation={0}
                                className={`p-4 rounded-xl border ${
                                  ans.is_correct
                                    ? 'border-emerald-200 bg-emerald-50/40 dark:bg-emerald-950/20 dark:border-emerald-800'
                                    : 'border-rose-200 bg-rose-50/40 dark:bg-rose-950/20 dark:border-rose-800'
                                }`}
                              >
                                <div className="flex items-start justify-between gap-2">
                                  <div className="flex items-start gap-2">
                                    <span
                                      className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-0.5 ${
                                        ans.is_correct ? 'bg-emerald-600' : 'bg-rose-600'
                                      }`}
                                    >
                                      {qIdx + 1}
                                    </span>
                                    <div>
                                      <Typography variant="body2" className="font-bold text-slate-900 dark:text-slate-100">
                                        {q.question || `Question #${qIdx + 1}`}
                                      </Typography>
                                      <Typography variant="caption" className="text-slate-400">
                                        {q.question_type === 'MULTI_SELECT' ? 'Multi-Select' : q.question_type === 'TRUE_FALSE' ? 'True/False' : 'Multiple Choice'}
                                      </Typography>
                                    </div>
                                  </div>

                                  <Chip
                                    label={ans.is_correct ? `+${ans.marks_obtained || q.marks || 1} pts` : `0 / ${q.marks || 1} pts`}
                                    size="small"
                                    color={ans.is_correct ? 'success' : 'error'}
                                    sx={{ fontWeight: 800, fontSize: '0.7rem' }}
                                  />
                                </div>

                                <div className="mt-3 space-y-1.5 text-xs">
                                  <div
                                    className={`p-2 rounded-lg border flex items-start gap-2 ${
                                      ans.is_correct
                                        ? 'bg-emerald-100/60 border-emerald-300 dark:bg-emerald-950/40 text-emerald-950 dark:text-emerald-200'
                                        : 'bg-rose-100/60 border-rose-300 dark:bg-rose-950/40 text-rose-950 dark:text-rose-200'
                                    }`}
                                  >
                                    <span className="font-bold shrink-0">Employee Choice:</span>
                                    <span>{selectedAnswers}</span>
                                  </div>

                                  <div className="p-2 rounded-lg border bg-white dark:bg-slate-900 border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-300 flex items-start gap-2">
                                    <span className="font-bold shrink-0 flex items-center gap-1 text-emerald-700 dark:text-emerald-400">
                                      <CheckCircleIcon sx={{ fontSize: 14 }} /> Correct Answer:
                                    </span>
                                    <span className="font-bold">{correctAnswersText}</span>
                                  </div>
                                </div>
                              </Paper>
                            );
                          })}

                          {(!currentAttempt.answers || currentAttempt.answers.length === 0) && (
                            <Typography variant="body2" className="text-slate-400 italic py-4 text-center">
                              No evaluated answers recorded for this attempt.
                            </Typography>
                          )}
                        </div>
                      </div>
                    );
                  })()}
                </>
              ) : (
                <div className="py-12 text-center text-slate-400">
                  <QuizIcon sx={{ fontSize: 40 }} className="text-slate-300 mb-2" />
                  <Typography variant="body1" className="font-bold">
                    No Test Attempts Recorded Yet
                  </Typography>
                  <Typography variant="caption">
                    This candidate has not started or submitted any test attempts for this training module.
                  </Typography>
                </div>
              )}
            </DialogContent>

            <DialogActions className="p-4 border-t">
              <Button onClick={() => setAttemptHistoryModalOpen(false)} sx={{ textTransform: 'none', fontWeight: 600 }}>
                Close
              </Button>
            </DialogActions>
          </>
        )}
      </Dialog>
    </AsideLayout>
  );
}
