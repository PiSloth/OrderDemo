import React, { useState } from 'react';
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
  Divider
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
  Print as PrintIcon
} from '@mui/icons-material';

export default function Compliance({
  metrics = {},
  departmentStats = [],
  testPerformance = [],
  matrix = {},
  filterOptions = {},
  filters = {}
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

  const handleFilterChange = (field, value) => {
    setFilterValues((prev) => ({ ...prev, [field]: value }));
  };

  const applyFilters = (e) => {
    if (e) e.preventDefault();
    router.get('/training/dashboard', filterValues, {
      preserveState: true,
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
    router.get('/training/dashboard', {}, { preserveState: true, replace: true });
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
        <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
          <CardContent className="p-5 space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <FilterIcon fontSize="small" className="text-slate-500" />
                Employee Compliance Matrix
              </Typography>
              <Typography variant="caption" className="text-slate-400">
                Showing {matrix.total ?? 0} total records
              </Typography>
            </div>

            {/* Filter Controls */}
            <form onSubmit={applyFilters} className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3">
              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                {/* Search */}
                <TextField
                  size="small"
                  label="Search Employee / Code"
                  value={filterValues.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  placeholder="e.g. John Doe, ERP"
                />

                {/* Department */}
                <TextField
                  select
                  size="small"
                  label="Department"
                  value={filterValues.department_id}
                  onChange={(e) => handleFilterChange('department_id', e.target.value)}
                >
                  <MenuItem value="">All Departments</MenuItem>
                  {(filterOptions.departments || []).map((d) => (
                    <MenuItem key={d.id} value={String(d.id)}>
                      {d.name}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Office Position */}
                <TextField
                  select
                  size="small"
                  label="Office Position"
                  value={filterValues.office_position_id}
                  onChange={(e) => handleFilterChange('office_position_id', e.target.value)}
                >
                  <MenuItem value="">All Positions</MenuItem>
                  {(filterOptions.officePositions || []).map((p) => (
                    <MenuItem key={p.id} value={String(p.id)}>
                      {p.name}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Training */}
                <TextField
                  select
                  size="small"
                  label="Training"
                  value={filterValues.training_id}
                  onChange={(e) => handleFilterChange('training_id', e.target.value)}
                >
                  <MenuItem value="">All Trainings</MenuItem>
                  {(filterOptions.trainings || []).map((t) => (
                    <MenuItem key={t.id} value={String(t.id)}>
                      {t.code} - {t.title}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Status */}
                <TextField
                  select
                  size="small"
                  label="Status"
                  value={filterValues.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                >
                  <MenuItem value="">All Statuses</MenuItem>
                  {(filterOptions.statuses || []).map((s) => (
                    <MenuItem key={s} value={s}>
                      {s}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Trigger Type */}
                <TextField
                  select
                  size="small"
                  label="Trigger Type"
                  value={filterValues.trigger_type}
                  onChange={(e) => handleFilterChange('trigger_type', e.target.value)}
                >
                  <MenuItem value="">All Trigger Types</MenuItem>
                  {(filterOptions.triggerTypes || []).map((trig) => (
                    <MenuItem key={trig} value={trig}>
                      {trig.replace('_', ' ')}
                    </MenuItem>
                  ))}
                </TextField>

                {/* Due From */}
                <TextField
                  type="date"
                  size="small"
                  label="Due Date From"
                  InputLabelProps={{ shrink: true }}
                  value={filterValues.due_from}
                  onChange={(e) => handleFilterChange('due_from', e.target.value)}
                />

                {/* Due To */}
                <TextField
                  type="date"
                  size="small"
                  label="Due Date To"
                  InputLabelProps={{ shrink: true }}
                  value={filterValues.due_to}
                  onChange={(e) => handleFilterChange('due_to', e.target.value)}
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-2">
                <Button
                  size="small"
                  variant="outlined"
                  onClick={resetFilters}
                  startIcon={<ClearIcon />}
                  sx={{ textTransform: 'none' }}
                >
                  Reset
                </Button>
                <Button
                  type="submit"
                  size="small"
                  variant="contained"
                  startIcon={<FilterIcon />}
                  sx={{ textTransform: 'none', fontWeight: 700 }}
                >
                  Apply Filters
                </Button>
              </div>
            </form>

            {/* Matrix Data Table */}
            <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
              <Table size="small">
                <TableHead className="bg-slate-100 dark:bg-slate-800">
                  <TableRow>
                    <TableCell className="font-bold text-xs">Employee</TableCell>
                    <TableCell className="font-bold text-xs">Department & Position</TableCell>
                    <TableCell className="font-bold text-xs">Training Module</TableCell>
                    <TableCell className="font-bold text-xs">Trigger / Source</TableCell>
                    <TableCell className="font-bold text-xs">Due Date</TableCell>
                    <TableCell className="font-bold text-xs">Latest Test</TableCell>
                    <TableCell className="font-bold text-xs text-center">Status</TableCell>
                    <TableCell className="font-bold text-xs text-center">Scorecard</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {(matrix.data || []).map((row) => {
                    const latestAttempt = row.test_attempts?.[0];
                    return (
                      <TableRow key={row.id} hover>
                        {/* Employee */}
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Avatar sx={{ width: 28, height: 28, fontSize: '0.75rem', bgcolor: 'primary.main' }}>
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
                        <TableCell className="text-xs">
                          <div className="font-semibold text-slate-700 dark:text-slate-300">
                            {row.user?.department?.name || 'Unassigned Dept'}
                          </div>
                          <div className="text-[11px] text-slate-400">
                            {row.user?.office_position?.name || 'Unassigned Position'}
                          </div>
                        </TableCell>

                        {/* Training */}
                        <TableCell className="text-xs">
                          <div className="font-bold text-slate-900 dark:text-slate-100">
                            {row.training?.title}
                          </div>
                          <div className="text-[11px] font-mono text-slate-400">{row.training?.code}</div>
                        </TableCell>

                        {/* Trigger */}
                        <TableCell className="text-xs">
                          {getTriggerBadge(row.trigger)}
                          {row.trigger?.reason && (
                            <div className="text-[11px] text-slate-500 truncate max-w-[180px]">
                              {row.trigger.reason}
                            </div>
                          )}
                        </TableCell>

                        {/* Due Date */}
                        <TableCell className="text-xs">
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
                        <TableCell className="text-xs">
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
                        <TableCell align="center">
                          {getStatusChip(row.status)}
                        </TableCell>

                        {/* Scorecard Link */}
                        <TableCell align="center">
                          <Tooltip title="View International Scorecard & Print Certificate">
                            <IconButton
                              size="small"
                              component={Link}
                              href={`/training/assignments/${row.id}/scorecard`}
                              sx={{ color: 'primary.main' }}
                            >
                              <TrophyIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        </TableCell>
                      </TableRow>
                    );
                  })}

                  {(matrix.data || []).length === 0 && (
                    <TableRow>
                      <TableCell colSpan={8} align="center" className="text-slate-400 py-8">
                        No compliance records found matching your filters.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>

            {/* Pagination */}
            {matrix.links && matrix.links.length > 3 && (
              <div className="flex items-center justify-between pt-2">
                <Typography variant="caption" className="text-slate-400">
                  Page {matrix.current_page} of {matrix.last_page}
                </Typography>
                <Stack direction="row" spacing={0.5}>
                  {matrix.links.map((link, idx) => (
                    <Button
                      key={idx}
                      size="small"
                      variant={link.active ? 'contained' : 'outlined'}
                      disabled={!link.url}
                      onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                      sx={{ minWidth: 32, textTransform: 'none', px: 1 }}
                    />
                  ))}
                </Stack>
              </div>
            )}
          </CardContent>
        </Card>
      </Box>
    </AsideLayout>
  );
}
