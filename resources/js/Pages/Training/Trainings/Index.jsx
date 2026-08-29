import React, { useState, useMemo } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  TextField,
  MenuItem,
  Chip,
  IconButton,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Stack,
  Divider,
  Paper,
  Alert,
  InputAdornment,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer,
  Checkbox,
  Tabs,
  Tab,
  Avatar,
  Badge
} from '@mui/material';

import {
  School as SchoolIcon,
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Quiz as QuizIcon,
  Send as SendIcon,
  Loop as LoopIcon,
  CheckCircle as CheckCircleIcon,
  GroupAdd as GroupAddIcon,
  RemoveCircle as RemoveIcon,
  Description as DocumentIcon,
  Search as SearchIcon,
  Clear as ClearIcon,
  MenuBook as MenuBookIcon,
  PictureAsPdf as PdfIcon,
  Apartment as DepartmentIcon,
  Work as PositionIcon,
  Person as PersonIcon,
  Tune as CustomIcon,
  CalendarMonth as CalendarIcon,
  SelectAll as SelectAllIcon,
  Deselect as DeselectIcon,
  AssignmentInd as AssignIcon,
  FilterAlt as FilterIcon
} from '@mui/icons-material';

import { exportTrainingMatrixPdf } from '../../../utils/trainingPdfExport';

export default function TrainingsIndex({
  trainings = {},
  allActiveTrainings = [],
  activeUsers = [],
  categories = [],
  departments = [],
  officePositions = [],
  allDocuments = [],
  filters = {},
  permissions = {
    can_view: true,
    can_create: true,
    can_update: true,
    can_delete: true,
  }
}) {
  const [modalOpen, setModalOpen] = useState(false);
  const [editingTraining, setEditingTraining] = useState(null);
  const [manualAssignOpen, setManualAssignOpen] = useState(false);
  const [targetTraining, setTargetTraining] = useState(null);
  const [docSearchModalOpen, setDocSearchModalOpen] = useState(false);
  const [docSearchQuery, setDocSearchQuery] = useState('');
  const [exportingPdf, setExportingPdf] = useState(false);

  // Custom Assignment modal helper filters
  const [empSearch, setEmpSearch] = useState('');
  const [empDeptFilter, setEmpDeptFilter] = useState('');
  const [empPosFilter, setEmpPosFilter] = useState('');

  const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
    code: '',
    title: '',
    description: '',
    training_category_id: '',
    new_category_name: '',
    retrain_interval: 12,
    retrain_unit: 'month',
    passing_score: 80,
    status: 'active',
    scopes: [],
    document_ids: [],
  });

  const { data: assignData, setData: setAssignData, post: postAssign, processing: assignProcessing, reset: resetAssign } = useForm({
    target_type: 'scopes', // 'scopes', 'departments', 'positions', 'employees'
    assignment_type: 'FULL_TRAINING', // 'FULL_TRAINING', 'TEST_ONLY'
    department_ids: [],
    office_position_ids: [],
    user_ids: [],
    due_date: '',
    reason: '',
  });

  const handleOpenCreate = () => {
    setEditingTraining(null);
    reset();
    setData({
      code: '',
      title: '',
      description: '',
      training_category_id: '',
      new_category_name: '',
      retrain_interval: 12,
      retrain_unit: 'month',
      passing_score: 80,
      status: 'active',
      scopes: [],
      document_ids: [],
    });
    setModalOpen(true);
  };

  const handleOpenEdit = (t) => {
    setEditingTraining(t);
    const docs = t.company_documents || t.companyDocuments || [];
    setData({
      code: t.code,
      title: t.title,
      description: t.description || '',
      training_category_id: t.training_category_id ? String(t.training_category_id) : '',
      new_category_name: '',
      retrain_interval: t.retrain_interval,
      retrain_unit: t.retrain_unit,
      passing_score: t.passing_score,
      status: t.status,
      scopes: (t.scopes || []).map((s) => ({
        department_id: s.department_id,
        office_position_id: s.office_position_id ? s.office_position_id : '',
      })),
      document_ids: docs.map((d) => d.id),
    });
    setModalOpen(true);
  };

  const handleClose = () => {
    setModalOpen(false);
    setEditingTraining(null);
    reset();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editingTraining) {
      put(`/training/trainings/${editingTraining.id}`, {
        onSuccess: () => handleClose(),
      });
    } else {
      post('/training/trainings', {
        onSuccess: () => handleClose(),
      });
    }
  };

  const handleDelete = (t) => {
    if (confirm(`Are you sure you want to delete training "${t.title}" (${t.code})?`)) {
      destroy(`/training/trainings/${t.id}`);
    }
  };

  const handleOpenManualAssign = (t) => {
    setTargetTraining(t);
    const d = new Date();
    d.setDate(d.getDate() + 30);
    const defaultDueDate = d.toISOString().split('T')[0];

    setAssignData({
      target_type: 'scopes',
      assignment_type: 'FULL_TRAINING',
      department_ids: [],
      office_position_ids: [],
      user_ids: [],
      due_date: defaultDueDate,
      reason: '',
    });
    setEmpSearch('');
    setEmpDeptFilter('');
    setEmpPosFilter('');
    setManualAssignOpen(true);
  };

  const handleManualAssignSubmit = (e) => {
    e.preventDefault();
    if (!targetTraining) return;
    postAssign(`/training/trainings/${targetTraining.id}/assign`, {
      onSuccess: () => {
        setManualAssignOpen(false);
        setTargetTraining(null);
        resetAssign();
      },
    });
  };

  // Live calculation of matching users for assignment
  const currentMatchingUsers = useMemo(() => {
    if (!targetTraining) return [];

    if (assignData.target_type === 'scopes') {
      const scopes = targetTraining.scopes || [];
      if (scopes.length === 0) return [];
      return activeUsers.filter((u) => {
        return scopes.some((s) => {
          if (s.department_id !== u.department_id) return false;
          if (s.office_position_id && s.office_position_id !== u.office_position_id) return false;
          return true;
        });
      });
    }

    if (assignData.target_type === 'departments') {
      const dIds = assignData.department_ids || [];
      if (dIds.length === 0) return [];
      return activeUsers.filter((u) => dIds.includes(u.department_id));
    }

    if (assignData.target_type === 'positions') {
      const pIds = assignData.office_position_ids || [];
      if (pIds.length === 0) return [];
      return activeUsers.filter((u) => pIds.includes(u.office_position_id));
    }

    if (assignData.target_type === 'employees') {
      const uIds = assignData.user_ids || [];
      if (uIds.length === 0) return [];
      return activeUsers.filter((u) => uIds.includes(u.id));
    }

    return [];
  }, [targetTraining, assignData.target_type, assignData.department_ids, assignData.office_position_ids, assignData.user_ids, activeUsers]);

  // Filtered employees list for the Specific Employees selection mode
  const filteredEmployees = useMemo(() => {
    return activeUsers.filter((u) => {
      if (empDeptFilter && u.department_id !== Number(empDeptFilter)) return false;
      if (empPosFilter && u.office_position_id !== Number(empPosFilter)) return false;
      if (empSearch) {
        const q = empSearch.toLowerCase();
        const nameMatch = u.name && u.name.toLowerCase().includes(q);
        const emailMatch = u.email && u.email.toLowerCase().includes(q);
        const deptMatch = u.department?.name && u.department.name.toLowerCase().includes(q);
        const posMatch = u.officePosition?.name && u.officePosition.name.toLowerCase().includes(q);
        if (!nameMatch && !emailMatch && !deptMatch && !posMatch) return false;
      }
      return true;
    });
  }, [activeUsers, empSearch, empDeptFilter, empPosFilter]);

  // Target toggle helpers
  const toggleAssignDepartment = (deptId) => {
    const current = [...(assignData.department_ids || [])];
    const exists = current.includes(deptId);
    setAssignData('department_ids', exists ? current.filter((id) => id !== deptId) : [...current, deptId]);
  };

  const toggleAssignPosition = (posId) => {
    const current = [...(assignData.office_position_ids || [])];
    const exists = current.includes(posId);
    setAssignData('office_position_ids', exists ? current.filter((id) => id !== posId) : [...current, posId]);
  };

  const toggleAssignEmployee = (userId) => {
    const current = [...(assignData.user_ids || [])];
    const exists = current.includes(userId);
    setAssignData('user_ids', exists ? current.filter((id) => id !== userId) : [...current, userId]);
  };

  const selectAllFilteredEmployees = () => {
    const visibleIds = filteredEmployees.map((u) => u.id);
    const combined = Array.from(new Set([...(assignData.user_ids || []), ...visibleIds]));
    setAssignData('user_ids', combined);
  };

  const deselectAllFilteredEmployees = () => {
    const visibleIds = new Set(filteredEmployees.map((u) => u.id));
    const filtered = (assignData.user_ids || []).filter((id) => !visibleIds.has(id));
    setAssignData('user_ids', filtered);
  };

  // Scope rules helper
  const handleAddScope = () => {
    setData('scopes', [
      ...data.scopes,
      {
        department_id: departments[0]?.id || '',
        office_position_id: '',
      },
    ]);
  };

  const handleRemoveScope = (index) => {
    const updated = data.scopes.filter((_, i) => i !== index);
    setData('scopes', updated);
  };

  const handleScopeChange = (index, field, value) => {
    const updated = [...data.scopes];
    updated[index][field] = value;
    setData('scopes', updated);
  };

  // Document link helper
  const toggleDocSelection = (docId) => {
    const current = [...(data.document_ids || [])];
    const exists = current.includes(docId);
    let updated;
    if (exists) {
      updated = current.filter((id) => id !== docId);
    } else {
      updated = [...current, docId];
    }
    setData('document_ids', updated);
  };

  const removeDocument = (docId) => {
    const updated = (data.document_ids || []).filter((id) => id !== docId);
    setData('document_ids', updated);
  };

  const filteredDocs = allDocuments.filter((d) => {
    if (!docSearchQuery) return true;
    const q = docSearchQuery.toLowerCase();
    return d.title && d.title.toLowerCase().includes(q);
  });

  const handleExportPdf = async () => {
    setExportingPdf(true);
    try {
      await exportTrainingMatrixPdf({
        activeUsers,
        allTrainings: allActiveTrainings,
        departments,
        officePositions,
      });
    } catch (e) {
      console.error('PDF export failed:', e);
      alert('PDF generation error: ' + (e?.message || 'Please check browser console'));
    } finally {
      setExportingPdf(false);
    }
  };

  return (
    <AsideLayout title="Training Master Catalog">
      <Head title="Training Catalog - Training Master" />

      <Box className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <SchoolIcon className="text-sky-600" />
              Training Master Catalog
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Manage training courses, connect company SOPs/documents, define target audience scopes, and export compliance matrix reports.
            </Typography>
          </div>

          <Stack direction="row" spacing={2} alignItems="center">
            <Button
              variant="outlined"
              color="primary"
              startIcon={<PdfIcon className="text-red-500" />}
              onClick={handleExportPdf}
              disabled={exportingPdf}
              sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
            >
              {exportingPdf ? 'Generating PDF...' : 'Export PDF Report'}
            </Button>

            {permissions?.can_create && (
              <Button
                variant="contained"
                startIcon={<AddIcon />}
                onClick={handleOpenCreate}
                sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
              >
                New Training Catalog
              </Button>
            )}
          </Stack>
        </Box>

        {/* Training Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {(trainings.data || []).map((t) => {
            const docs = t.company_documents || t.companyDocuments || [];
            return (
              <Card
                key={t.id}
                elevation={0}
                className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900 flex flex-col justify-between hover:border-sky-300 transition-all"
              >
                <CardContent className="space-y-4 p-5">
                  <div className="flex items-start justify-between">
                    <div>
                      <span className="text-xs font-mono font-bold text-sky-600 bg-sky-50 dark:bg-sky-950 px-2 py-0.5 rounded-md">
                        {t.code}
                      </span>
                      <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100 mt-1 leading-snug">
                        {t.title}
                      </Typography>
                    </div>

                    <Chip
                      label={t.status.toUpperCase()}
                      size="small"
                      color={t.status === 'active' ? 'success' : 'default'}
                      sx={{ fontWeight: 700, fontSize: '0.65rem' }}
                    />
                  </div>

                  <Typography variant="body2" className="text-slate-500 dark:text-slate-400 line-clamp-2 text-xs">
                    {t.description || 'No description provided.'}
                  </Typography>

                  {/* Metadata Chips */}
                  <div className="grid grid-cols-2 gap-2 text-xs pt-1">
                    <div className="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl border border-slate-100 dark:border-slate-800">
                      <span className="text-slate-400 block text-[11px]">Category</span>
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        {t.category?.name || 'General'}
                      </span>
                    </div>

                    <div className="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl border border-slate-100 dark:border-slate-800">
                      <span className="text-slate-400 block text-[11px]">Passing Score</span>
                      <span className="font-semibold text-emerald-600">
                        {t.passing_score}%
                      </span>
                    </div>

                    <div className="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-xl border border-slate-100 dark:border-slate-800 col-span-2 flex items-center justify-between">
                      <span className="text-slate-400 text-[11px] flex items-center gap-1">
                        <LoopIcon fontSize="inherit" /> Retrain Interval
                      </span>
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        Every {t.retrain_interval} {t.retrain_unit}(s)
                      </span>
                    </div>
                  </div>

                  {/* Connected Company Documents */}
                  {docs.length > 0 && (
                    <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
                      <div className="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center gap-1">
                        <DocumentIcon fontSize="inherit" className="text-sky-500" />
                        Connected Documents ({docs.length})
                      </div>
                      <div className="flex flex-wrap gap-1">
                        {docs.map((d) => (
                          <Chip
                            key={d.id}
                            label={d.title}
                            size="small"
                            component="a"
                            href={`/document/library?doc=${d.id}`}
                            target="_blank"
                            clickable
                            variant="outlined"
                            color="info"
                            sx={{ fontSize: '0.7rem' }}
                          />
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Scopes Preview */}
                  <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div className="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center justify-between">
                      <span>Target Scopes ({t.scopes?.length || 0})</span>
                    </div>

                    <div className="space-y-1">
                      {(t.scopes || []).slice(0, 3).map((scope) => (
                        <div key={scope.id} className="text-xs text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                          <span className="w-1.5 h-1.5 rounded-full bg-sky-500 inline-block" />
                          <span className="font-medium">{scope.department?.name || 'Any Dept'}</span>
                          <span className="text-slate-400">→</span>
                          <span className={`font-semibold ${scope.office_position || scope.officePosition ? 'text-slate-900 dark:text-slate-100' : 'text-sky-700 dark:text-sky-400'}`}>
                            {scope.office_position?.name || scope.officePosition?.name || 'All Department People'}
                          </span>
                        </div>
                      ))}
                      {(t.scopes?.length || 0) > 3 && (
                        <span className="text-[11px] text-slate-400 font-medium">
                          +{t.scopes.length - 3} more scope combinations
                        </span>
                      )}
                      {(t.scopes || []).length === 0 && (
                        <span className="text-xs text-slate-400 italic">No scope defined yet.</span>
                      )}
                    </div>
                  </div>
                </CardContent>

                {/* Card Footer Actions */}
                <Box className="p-4 pt-0 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 mt-2">
                  <Stack direction="row" spacing={1}>
                    <Button
                      size="small"
                      variant="outlined"
                      color="secondary"
                      component={Link}
                      href={`/training/trainings/${t.id}/test-builder`}
                      startIcon={<QuizIcon />}
                      sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem' }}
                    >
                      Test Builder
                    </Button>
                    {permissions?.can_update && (
                      <Button
                        size="small"
                        variant="outlined"
                        color="primary"
                        onClick={() => handleOpenManualAssign(t)}
                        startIcon={<AssignIcon />}
                        sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem', fontWeight: 600 }}
                      >
                        Assign
                      </Button>
                    )}
                  </Stack>

                  <Stack direction="row" spacing={0.5}>
                    {permissions?.can_update && (
                      <Tooltip title="Edit Training & Scopes">
                        <IconButton size="small" onClick={() => handleOpenEdit(t)}>
                          <EditIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    )}
                    {permissions?.can_delete && (
                      <Tooltip title="Delete">
                        <IconButton size="small" color="error" onClick={() => handleDelete(t)}>
                          <DeleteIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    )}
                  </Stack>
                </Box>
              </Card>
            );
          })}

          {(trainings.data || []).length === 0 && (
            <div className="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl">
              <SchoolIcon sx={{ fontSize: 48 }} className="text-slate-300 mb-2" />
              <Typography variant="h6">No Trainings Found</Typography>
              <Typography variant="body2" className="mb-4">
                Get started by creating your first training module with associated target scopes.
              </Typography>
              {permissions?.can_create && (
                <Button variant="contained" startIcon={<AddIcon />} onClick={handleOpenCreate}>
                  Create Training
                </Button>
              )}
            </div>
          )}
        </div>

        {/* Create / Edit Training Modal */}
        <Dialog open={modalOpen} onClose={handleClose} maxWidth="md" fullWidth>
          <DialogTitle className="font-bold flex items-center justify-between">
            <div className="flex items-center gap-2">
              <SchoolIcon className="text-sky-600" />
              <span>{editingTraining ? 'Edit Training Catalog' : 'New Training Catalog'}</span>
            </div>
            {editingTraining && (
              <Chip label={editingTraining.code} size="small" color="primary" sx={{ fontWeight: 700 }} />
            )}
          </DialogTitle>

          <form onSubmit={handleSubmit}>
            <DialogContent className="space-y-6">
              {/* Basic Fields */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <TextField
                  fullWidth
                  label="Training Code"
                  placeholder="e.g. SEC-101"
                  value={data.code}
                  onChange={(e) => setData('code', e.target.value)}
                  error={!!errors.code}
                  helperText={errors.code}
                  required
                />

                <TextField
                  fullWidth
                  label="Training Title"
                  placeholder="e.g. Information Security Awareness"
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                  error={!!errors.title}
                  helperText={errors.title}
                  required
                />

                <div className="sm:col-span-2">
                  <TextField
                    fullWidth
                    multiline
                    rows={2}
                    label="Description"
                    placeholder="Provide a summary of the training course objectives..."
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    error={!!errors.description}
                    helperText={errors.description}
                  />
                </div>

                <TextField
                  select
                  fullWidth
                  label="Category"
                  value={data.training_category_id}
                  onChange={(e) => setData('training_category_id', e.target.value)}
                  error={!!errors.training_category_id}
                  helperText={errors.training_category_id}
                >
                  <MenuItem value="">
                    <em>None / General</em>
                  </MenuItem>
                  {categories.map((c) => (
                    <MenuItem key={c.id} value={String(c.id)}>
                      {c.name}
                    </MenuItem>
                  ))}
                </TextField>

                <TextField
                  fullWidth
                  label="Or Create New Category"
                  placeholder="e.g. Health & Safety"
                  value={data.new_category_name}
                  onChange={(e) => setData('new_category_name', e.target.value)}
                />

                <div className="grid grid-cols-2 gap-2">
                  <TextField
                    fullWidth
                    type="number"
                    label="Retrain Interval"
                    value={data.retrain_interval}
                    onChange={(e) => setData('retrain_interval', e.target.value)}
                    error={!!errors.retrain_interval}
                    helperText={errors.retrain_interval}
                    required
                  />

                  <TextField
                    select
                    fullWidth
                    label="Unit"
                    value={data.retrain_unit}
                    onChange={(e) => setData('retrain_unit', e.target.value)}
                    required
                  >
                    <MenuItem value="day">Day(s)</MenuItem>
                    <MenuItem value="month">Month(s)</MenuItem>
                    <MenuItem value="year">Year(s)</MenuItem>
                  </TextField>
                </div>

                <TextField
                  fullWidth
                  type="number"
                  label="Passing Score (%)"
                  value={data.passing_score}
                  onChange={(e) => setData('passing_score', e.target.value)}
                  error={!!errors.passing_score}
                  helperText={errors.passing_score}
                  required
                />

                <TextField
                  select
                  fullWidth
                  label="Status"
                  value={data.status}
                  onChange={(e) => setData('status', e.target.value)}
                  required
                >
                  <MenuItem value="active">Active</MenuItem>
                  <MenuItem value="draft">Draft</MenuItem>
                  <MenuItem value="archived">Archived</MenuItem>
                </TextField>
              </div>

              {/* Connected Company Documents Section */}
              <div className="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div className="flex items-center justify-between">
                  <div>
                    <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                      <DocumentIcon className="text-sky-600" fontSize="small" />
                      Connected Company SOPs & Documents
                    </Typography>
                    <Typography variant="caption" className="text-slate-500">
                      Link formal SOPs or documents from Document Library that employees must review for this training.
                    </Typography>
                  </div>

                  <Button
                    size="small"
                    variant="outlined"
                    startIcon={<SearchIcon />}
                    onClick={() => setDocSearchModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                  >
                    Browse & Link Documents ({data.document_ids?.length || 0})
                  </Button>
                </div>

                {data.document_ids && data.document_ids.length > 0 ? (
                  <Paper variant="outlined" className="p-3 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 space-y-2">
                    <div className="flex flex-wrap gap-2">
                      {data.document_ids.map((id) => {
                        const doc = allDocuments.find((d) => d.id === id);
                        if (!doc) return null;
                        return (
                          <Chip
                            key={id}
                            label={doc.title}
                            onDelete={() => removeDocument(id)}
                            color="info"
                            variant="outlined"
                            size="small"
                            sx={{ fontWeight: 600, borderRadius: 2 }}
                          />
                        );
                      })}
                    </div>
                  </Paper>
                ) : (
                  <Alert severity="info" className="rounded-xl">
                    No SOP documents linked yet. Click "Browse & Link Documents" to connect company manuals.
                  </Alert>
                )}
              </div>

              {/* Scopes Definition */}
              <div className="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div className="flex items-center justify-between">
                  <div>
                    <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100">
                      Target Audience Scopes
                    </Typography>
                    <Typography variant="caption" className="text-slate-500">
                      Define which departments and positions this training is intended for.
                    </Typography>
                  </div>

                  <Button
                    size="small"
                    variant="outlined"
                    startIcon={<AddIcon />}
                    onClick={handleAddScope}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                  >
                    Add Scope Rule
                  </Button>
                </div>

                <div className="space-y-3">
                  {data.scopes.map((scope, idx) => (
                    <Paper
                      key={idx}
                      variant="outlined"
                      className="p-3 rounded-xl flex items-center gap-3 bg-slate-50/50 dark:bg-slate-800/50"
                    >
                      <TextField
                        select
                        size="small"
                        label="Department"
                        value={scope.department_id}
                        onChange={(e) => handleScopeChange(idx, 'department_id', e.target.value)}
                        className="w-1/2"
                        required
                      >
                        {departments.map((d) => (
                          <MenuItem key={d.id} value={d.id}>
                            {d.name}
                          </MenuItem>
                        ))}
                      </TextField>

                      <TextField
                        select
                        size="small"
                        label="Office Position"
                        value={scope.office_position_id || ''}
                        onChange={(e) => handleScopeChange(idx, 'office_position_id', e.target.value)}
                        className="w-1/2"
                      >
                        <MenuItem value="">
                          <em className="text-sky-600 font-medium">All Positions (Entire Department)</em>
                        </MenuItem>
                        {officePositions.map((p) => (
                          <MenuItem key={p.id} value={p.id}>
                            {p.name}
                          </MenuItem>
                        ))}
                      </TextField>
                    </Paper>
                  ))}
                </div>
              </div>
            </DialogContent>

            <DialogActions className="p-4">
              <Button onClick={handleClose} disabled={processing} sx={{ textTransform: 'none' }}>
                Cancel
              </Button>
              <Button
                type="submit"
                variant="contained"
                disabled={processing}
                sx={{ textTransform: 'none', fontWeight: 700 }}
              >
                {editingTraining ? 'Update Training' : 'Create Training'}
              </Button>
            </DialogActions>
          </form>
        </Dialog>

        {/* Custom Assignment Modal */}
        <Dialog open={manualAssignOpen} onClose={() => setManualAssignOpen(false)} maxWidth="md" fullWidth>
          <DialogTitle className="font-bold flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div className="flex items-center gap-2">
              <AssignIcon className="text-sky-600" />
              <span>Assign Training: {targetTraining?.title}</span>
            </div>
            {targetTraining && (
              <Chip label={targetTraining.code} size="small" color="primary" sx={{ fontWeight: 700 }} />
            )}
          </DialogTitle>

          <form onSubmit={handleManualAssignSubmit}>
            <DialogContent className="space-y-5 pt-4">
              {/* Assignment Mode: Full Training vs Question Test Only */}
              <div className="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                <Typography variant="caption" className="font-bold text-slate-500 uppercase tracking-wider block mb-2">
                  Assignment Mode & Requirements
                </Typography>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <Paper
                    variant="outlined"
                    onClick={() => setAssignData('assignment_type', 'FULL_TRAINING')}
                    className={`p-3 rounded-xl cursor-pointer transition-all ${
                      assignData.assignment_type === 'FULL_TRAINING'
                        ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/40 text-sky-950 dark:text-sky-100 ring-2 ring-sky-400/30'
                        : 'hover:border-slate-300 bg-white dark:bg-slate-900'
                    }`}
                  >
                    <div className="flex items-start gap-2">
                      <SchoolIcon className={assignData.assignment_type === 'FULL_TRAINING' ? 'text-sky-600' : 'text-slate-400'} fontSize="small" />
                      <div>
                        <div className="font-bold text-xs">Full Training (Session + Test)</div>
                        <div className="text-[11px] text-slate-500">
                          Provisions training session & requires both session attendance and passing the test.
                        </div>
                      </div>
                    </div>
                  </Paper>

                  <Paper
                    variant="outlined"
                    onClick={() => setAssignData('assignment_type', 'TEST_ONLY')}
                    className={`p-3 rounded-xl cursor-pointer transition-all ${
                      assignData.assignment_type === 'TEST_ONLY'
                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-950 dark:text-indigo-100 ring-2 ring-indigo-400/30'
                        : 'hover:border-slate-300 bg-white dark:bg-slate-900'
                    }`}
                  >
                    <div className="flex items-start gap-2">
                      <QuizIcon className={assignData.assignment_type === 'TEST_ONLY' ? 'text-indigo-600' : 'text-slate-400'} fontSize="small" />
                      <div>
                        <div className="font-bold text-xs text-indigo-950 dark:text-indigo-200">
                          Question Test Only (No Session)
                        </div>
                        <div className="text-[11px] text-slate-500">
                          Triggers only question evaluation / re-test. Completes directly upon passing test without training sessions.
                        </div>
                      </div>
                    </div>
                  </Paper>
                </div>
              </div>

              {/* Mode Tabs */}
              <div>
                <Typography variant="caption" className="font-bold text-slate-500 uppercase tracking-wider block mb-2">
                  Target Audience
                </Typography>
                <Tabs
                  value={assignData.target_type}
                  onChange={(_, val) => setAssignData('target_type', val)}
                  variant="scrollable"
                  scrollButtons="auto"
                  sx={{
                    borderBottom: 1,
                    borderColor: 'divider',
                    '& .MuiTab-root': { textTransform: 'none', fontWeight: 600, minHeight: 44 }
                  }}
                >
                  <Tab
                    value="scopes"
                    icon={<SchoolIcon fontSize="small" />}
                    iconPosition="start"
                    label="Catalog Scopes (Bulk)"
                  />
                  <Tab
                    value="departments"
                    icon={<DepartmentIcon fontSize="small" />}
                    iconPosition="start"
                    label={`By Department (${assignData.department_ids?.length || 0})`}
                  />
                  <Tab
                    value="positions"
                    icon={<PositionIcon fontSize="small" />}
                    iconPosition="start"
                    label={`By Position (${assignData.office_position_ids?.length || 0})`}
                  />
                  <Tab
                    value="employees"
                    icon={<GroupAddIcon fontSize="small" />}
                    iconPosition="start"
                    label={`Specific Employees (${assignData.user_ids?.length || 0})`}
                  />
                </Tabs>
              </div>

              {/* Mode 1: Catalog Scopes */}
              {assignData.target_type === 'scopes' && (
                <div className="space-y-3">
                  <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                    Assign this training to all active employees who match the pre-configured scopes of this training catalog:
                  </Typography>

                  <div className="bg-slate-50 dark:bg-slate-800/70 p-4 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2">
                    {(targetTraining?.scopes || []).map((s) => (
                      <div key={s.id} className="flex items-center gap-2 text-sm">
                        <span className="w-2 h-2 rounded-full bg-sky-500 inline-block" />
                        <span className="font-semibold text-slate-800 dark:text-slate-200">
                          {s.department?.name || 'Any Department'}
                        </span>
                        <span className="text-slate-400">→</span>
                        <span className={`font-semibold ${s.office_position || s.officePosition ? 'text-slate-900 dark:text-slate-100' : 'text-sky-600 dark:text-sky-400'}`}>
                          {s.office_position?.name || s.officePosition?.name || 'All Positions (Entire Department)'}
                        </span>
                      </div>
                    ))}
                    {(targetTraining?.scopes || []).length === 0 && (
                      <div className="text-amber-600 text-sm font-medium">
                        No target scopes are defined for this training. Switch to Department, Position, or Specific Employee mode to assign.
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Mode 2: By Department */}
              {assignData.target_type === 'departments' && (
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                      Select one or more departments to assign all active staff within them:
                    </Typography>
                    <Stack direction="row" spacing={1}>
                      <Button
                        size="small"
                        variant="text"
                        startIcon={<SelectAllIcon />}
                        onClick={() => setAssignData('department_ids', departments.map((d) => d.id))}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        Select All
                      </Button>
                      <Button
                        size="small"
                        variant="text"
                        color="inherit"
                        startIcon={<DeselectIcon />}
                        onClick={() => setAssignData('department_ids', [])}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        Clear
                      </Button>
                    </Stack>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-64 overflow-y-auto p-1">
                    {departments.map((dept) => {
                      const isSelected = (assignData.department_ids || []).includes(dept.id);
                      const deptUserCount = activeUsers.filter((u) => u.department_id === dept.id).length;
                      return (
                        <Paper
                          key={dept.id}
                          variant="outlined"
                          onClick={() => toggleAssignDepartment(dept.id)}
                          className={`p-3 rounded-xl cursor-pointer transition-all flex items-center justify-between ${
                            isSelected
                              ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 text-sky-950 dark:text-sky-100 shadow-sm'
                              : 'hover:border-slate-300 bg-white dark:bg-slate-800'
                          }`}
                        >
                          <div className="flex items-center gap-2">
                            <Checkbox
                              size="small"
                              checked={isSelected}
                              color="primary"
                              sx={{ p: 0.5 }}
                              onChange={() => toggleAssignDepartment(dept.id)}
                            />
                            <div className="font-semibold text-xs leading-tight">{dept.name}</div>
                          </div>
                          <Chip
                            label={`${deptUserCount} staff`}
                            size="small"
                            color={isSelected ? 'primary' : 'default'}
                            variant={isSelected ? 'filled' : 'outlined'}
                            sx={{ fontSize: '0.65rem', height: 20, fontWeight: 700 }}
                          />
                        </Paper>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Mode 3: By Position */}
              {assignData.target_type === 'positions' && (
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                      Select one or more office positions across all departments:
                    </Typography>
                    <Stack direction="row" spacing={1}>
                      <Button
                        size="small"
                        variant="text"
                        startIcon={<SelectAllIcon />}
                        onClick={() => setAssignData('office_position_ids', officePositions.map((p) => p.id))}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        Select All
                      </Button>
                      <Button
                        size="small"
                        variant="text"
                        color="inherit"
                        startIcon={<DeselectIcon />}
                        onClick={() => setAssignData('office_position_ids', [])}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        Clear
                      </Button>
                    </Stack>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-64 overflow-y-auto p-1">
                    {officePositions.map((pos) => {
                      const isSelected = (assignData.office_position_ids || []).includes(pos.id);
                      const posUserCount = activeUsers.filter((u) => u.office_position_id === pos.id).length;
                      return (
                        <Paper
                          key={pos.id}
                          variant="outlined"
                          onClick={() => toggleAssignPosition(pos.id)}
                          className={`p-3 rounded-xl cursor-pointer transition-all flex items-center justify-between ${
                            isSelected
                              ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 text-sky-950 dark:text-sky-100 shadow-sm'
                              : 'hover:border-slate-300 bg-white dark:bg-slate-800'
                          }`}
                        >
                          <div className="flex items-center gap-2">
                            <Checkbox
                              size="small"
                              checked={isSelected}
                              color="primary"
                              sx={{ p: 0.5 }}
                              onChange={() => toggleAssignPosition(pos.id)}
                            />
                            <div className="font-semibold text-xs leading-tight">{pos.name}</div>
                          </div>
                          <Chip
                            label={`${posUserCount} staff`}
                            size="small"
                            color={isSelected ? 'primary' : 'default'}
                            variant={isSelected ? 'filled' : 'outlined'}
                            sx={{ fontSize: '0.65rem', height: 20, fontWeight: 700 }}
                          />
                        </Paper>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Mode 4: Specific Employees (Multi-Select) */}
              {assignData.target_type === 'employees' && (
                <div className="space-y-3">
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <TextField
                      fullWidth
                      size="small"
                      placeholder="Search employee name or email..."
                      value={empSearch}
                      onChange={(e) => setEmpSearch(e.target.value)}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <SearchIcon fontSize="small" className="text-slate-400" />
                          </InputAdornment>
                        ),
                        endAdornment: empSearch ? (
                          <InputAdornment position="end">
                            <IconButton size="small" onClick={() => setEmpSearch('')}>
                              <ClearIcon fontSize="small" />
                            </IconButton>
                          </InputAdornment>
                        ) : null,
                      }}
                    />

                    <TextField
                      select
                      fullWidth
                      size="small"
                      label="Filter Department"
                      value={empDeptFilter}
                      onChange={(e) => setEmpDeptFilter(e.target.value)}
                    >
                      <MenuItem value="">All Departments</MenuItem>
                      {departments.map((d) => (
                        <MenuItem key={d.id} value={String(d.id)}>
                          {d.name}
                        </MenuItem>
                      ))}
                    </TextField>

                    <TextField
                      select
                      fullWidth
                      size="small"
                      label="Filter Position"
                      value={empPosFilter}
                      onChange={(e) => setEmpPosFilter(e.target.value)}
                    >
                      <MenuItem value="">All Positions</MenuItem>
                      {officePositions.map((p) => (
                        <MenuItem key={p.id} value={String(p.id)}>
                          {p.name}
                        </MenuItem>
                      ))}
                    </TextField>
                  </div>

                  <div className="flex items-center justify-between px-1">
                    <Typography variant="caption" className="font-semibold text-slate-500">
                      Showing {filteredEmployees.length} matching staff • {(assignData.user_ids || []).length} selected
                    </Typography>

                    <Stack direction="row" spacing={1}>
                      <Button
                        size="small"
                        variant="text"
                        startIcon={<SelectAllIcon />}
                        onClick={selectAllFilteredEmployees}
                        disabled={filteredEmployees.length === 0}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        Select Filtered ({filteredEmployees.length})
                      </Button>
                      <Button
                        size="small"
                        variant="text"
                        color="inherit"
                        startIcon={<DeselectIcon />}
                        onClick={deselectAllFilteredEmployees}
                        disabled={(assignData.user_ids || []).length === 0}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        Clear Filtered
                      </Button>
                    </Stack>
                  </div>

                  <TableContainer className="border border-slate-200 dark:border-slate-700 rounded-xl max-h-64 overflow-y-auto">
                    <Table size="small" stickyHeader>
                      <TableHead className="bg-slate-100 dark:bg-slate-800">
                        <TableRow>
                          <TableCell padding="checkbox">
                            <Checkbox
                              size="small"
                              checked={
                                filteredEmployees.length > 0 &&
                                filteredEmployees.every((u) => (assignData.user_ids || []).includes(u.id))
                              }
                              indeterminate={
                                filteredEmployees.some((u) => (assignData.user_ids || []).includes(u.id)) &&
                                !filteredEmployees.every((u) => (assignData.user_ids || []).includes(u.id))
                              }
                              onChange={(e) => {
                                if (e.target.checked) {
                                  selectAllFilteredEmployees();
                                } else {
                                  deselectAllFilteredEmployees();
                                }
                              }}
                            />
                          </TableCell>
                          <TableCell className="font-bold text-xs">Employee</TableCell>
                          <TableCell className="font-bold text-xs">Department</TableCell>
                          <TableCell className="font-bold text-xs">Position</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {filteredEmployees.map((emp) => {
                          const isSelected = (assignData.user_ids || []).includes(emp.id);
                          return (
                            <TableRow
                              key={emp.id}
                              hover
                              onClick={() => toggleAssignEmployee(emp.id)}
                              className={`cursor-pointer transition-colors ${
                                isSelected ? 'bg-sky-50/70 dark:bg-sky-950/40' : ''
                              }`}
                            >
                              <TableCell padding="checkbox">
                                <Checkbox
                                  size="small"
                                  checked={isSelected}
                                  color="primary"
                                  onChange={() => toggleAssignEmployee(emp.id)}
                                />
                              </TableCell>
                              <TableCell>
                                <div className="flex items-center gap-2">
                                  <Avatar
                                    sx={{
                                      width: 26,
                                      height: 26,
                                      fontSize: '0.75rem',
                                      bgcolor: isSelected ? '#0284c7' : '#94a3b8'
                                    }}
                                  >
                                    {emp.name ? emp.name.charAt(0).toUpperCase() : 'U'}
                                  </Avatar>
                                  <div>
                                    <div className="font-semibold text-xs text-slate-800 dark:text-slate-100">
                                      {emp.name}
                                    </div>
                                    <div className="text-[11px] text-slate-400">{emp.email}</div>
                                  </div>
                                </div>
                              </TableCell>
                              <TableCell className="text-xs text-slate-700 dark:text-slate-300">
                                {emp.department?.name || '—'}
                              </TableCell>
                              <TableCell className="text-xs text-slate-700 dark:text-slate-300">
                                {emp.officePosition?.name || '—'}
                              </TableCell>
                            </TableRow>
                          );
                        })}

                        {filteredEmployees.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={4} align="center" className="text-slate-400 py-6 text-xs">
                              No employees found matching current filter criteria.
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>
                </div>
              )}

              {/* Assignment Options: Due Date & Reason */}
              <div className="pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <TextField
                  fullWidth
                  type="date"
                  label="Due Date"
                  value={assignData.due_date}
                  onChange={(e) => setAssignData('due_date', e.target.value)}
                  InputLabelProps={{ shrink: true }}
                  helperText="Default: 30 days from now"
                />

                <TextField
                  fullWidth
                  label="Assignment Reason (Optional)"
                  placeholder="e.g. Annual refresh, Manager request, Onboarding"
                  value={assignData.reason}
                  onChange={(e) => setAssignData('reason', e.target.value)}
                />
              </div>

              {/* Live Impact Counter Banner */}
              <Alert
                severity={currentMatchingUsers.length > 0 ? 'info' : 'warning'}
                className="rounded-xl"
                icon={assignData.assignment_type === 'TEST_ONLY' ? <QuizIcon /> : <AssignIcon />}
              >
                {currentMatchingUsers.length > 0 ? (
                  <span>
                    Ready to {assignData.assignment_type === 'TEST_ONLY' ? 'trigger question test for' : 'assign training to'}{' '}
                    <strong>{currentMatchingUsers.length} employee(s)</strong>.
                    {assignData.assignment_type === 'TEST_ONLY' && (
                      <span className="block text-[11px] text-slate-500 mt-0.5">
                        • Staff can directly answer questions in My Trainings. No session attendance required.
                      </span>
                    )}
                  </span>
                ) : (
                  <span>
                    No employees currently targeted. Please select at least one department, position, or employee.
                  </span>
                )}
              </Alert>
            </DialogContent>

            <DialogActions className="p-4 border-t border-slate-100 dark:border-slate-800">
              <Button onClick={() => setManualAssignOpen(false)} disabled={assignProcessing} sx={{ textTransform: 'none' }}>
                Cancel
              </Button>
              <Button
                type="submit"
                variant="contained"
                color={assignData.assignment_type === 'TEST_ONLY' ? 'secondary' : 'primary'}
                disabled={assignProcessing || currentMatchingUsers.length === 0}
                startIcon={assignData.assignment_type === 'TEST_ONLY' ? <QuizIcon /> : <SendIcon />}
                sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
              >
                {assignData.assignment_type === 'TEST_ONLY' ? 'Trigger Question Test' : 'Assign Training'} ({currentMatchingUsers.length} Employees)
              </Button>
            </DialogActions>
          </form>
        </Dialog>

        {/* Document Search Modal */}
        <Dialog
          open={docSearchModalOpen}
          onClose={() => setDocSearchModalOpen(false)}
          maxWidth="md"
          fullWidth
        >
          <DialogTitle className="font-bold flex items-center justify-between">
            <div className="flex items-center gap-2">
              <DocumentIcon className="text-sky-600" />
              <span>Select Company SOPs & Documents</span>
            </div>
            <Chip
              label={`${data.document_ids?.length || 0} Selected`}
              color="primary"
              size="small"
              sx={{ fontWeight: 700 }}
            />
          </DialogTitle>

          <DialogContent className="space-y-4">
            <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
              Search and connect company documents / SOPs to this training catalog.
            </Typography>

            <TextField
              fullWidth
              size="small"
              placeholder="Search document title..."
              value={docSearchQuery}
              onChange={(e) => setDocSearchQuery(e.target.value)}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchIcon fontSize="small" className="text-slate-400" />
                  </InputAdornment>
                ),
                endAdornment: docSearchQuery ? (
                  <InputAdornment position="end">
                    <IconButton size="small" onClick={() => setDocSearchQuery('')}>
                      <ClearIcon fontSize="small" />
                    </IconButton>
                  </InputAdornment>
                ) : null,
              }}
            />

            <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl max-h-96 overflow-y-auto">
              <Table size="small" stickyHeader>
                <TableHead className="bg-slate-100 dark:bg-slate-800">
                  <TableRow>
                    <TableCell padding="checkbox">
                      <Checkbox
                        size="small"
                        checked={
                          filteredDocs.length > 0 &&
                          filteredDocs.every((d) => (data.document_ids || []).includes(d.id))
                        }
                        indeterminate={
                          filteredDocs.some((d) => (data.document_ids || []).includes(d.id)) &&
                          !filteredDocs.every((d) => (data.document_ids || []).includes(d.id))
                        }
                        onChange={(e) => {
                          if (e.target.checked) {
                            const newIds = new Set([...(data.document_ids || []), ...filteredDocs.map((d) => d.id)]);
                            setData('document_ids', Array.from(newIds));
                          } else {
                            const removeIds = new Set(filteredDocs.map((d) => d.id));
                            setData(
                              'document_ids',
                              (data.document_ids || []).filter((id) => !removeIds.has(id))
                            );
                          }
                        }}
                      />
                    </TableCell>
                    <TableCell className="font-bold text-xs">Document Title</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {filteredDocs.map((doc) => {
                    const isSelected = (data.document_ids || []).includes(doc.id);
                    return (
                      <TableRow
                        key={doc.id}
                        hover
                        onClick={() => toggleDocSelection(doc.id)}
                        className={`cursor-pointer transition-colors ${
                          isSelected ? 'bg-sky-50/60 dark:bg-sky-950/40' : ''
                        }`}
                      >
                        <TableCell padding="checkbox">
                          <Checkbox
                            size="small"
                            checked={isSelected}
                            color="primary"
                            onChange={() => toggleDocSelection(doc.id)}
                          />
                        </TableCell>

                        <TableCell className="font-bold text-xs text-slate-800 dark:text-slate-100">
                          {doc.title}
                        </TableCell>
                      </TableRow>
                    );
                  })}

                  {filteredDocs.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={2} align="center" className="text-slate-400 py-8">
                        No company documents found matching "{docSearchQuery}".
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </DialogContent>

          <DialogActions className="p-4">
            <Button
              onClick={() => setDocSearchModalOpen(false)}
              variant="contained"
              color="primary"
              startIcon={<CheckCircleIcon />}
              sx={{ textTransform: 'none', fontWeight: 700 }}
            >
              Done ({data.document_ids?.length || 0} Selected)
            </Button>
          </DialogActions>
        </Dialog>
      </Box>
    </AsideLayout>
  );
}
