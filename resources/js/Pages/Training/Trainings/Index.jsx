import React, { useState } from 'react';
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
  Checkbox
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
  PictureAsPdf as PdfIcon
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
  filters = {}
}) {
  const [modalOpen, setModalOpen] = useState(false);
  const [editingTraining, setEditingTraining] = useState(null);
  const [manualAssignOpen, setManualAssignOpen] = useState(false);
  const [targetTraining, setTargetTraining] = useState(null);
  const [docSearchModalOpen, setDocSearchModalOpen] = useState(false);
  const [docSearchQuery, setDocSearchQuery] = useState('');
  const [exportingPdf, setExportingPdf] = useState(false);

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
    setAssignData({ reason: '' });
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

            <Button
              variant="contained"
              startIcon={<AddIcon />}
              onClick={handleOpenCreate}
              sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
            >
              New Training Catalog
            </Button>
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

                    <Button
                      size="small"
                      variant="outlined"
                      color="primary"
                      onClick={() => handleOpenManualAssign(t)}
                      startIcon={<SendIcon />}
                      sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem' }}
                    >
                      Bulk Assign
                    </Button>
                  </Stack>

                  <Stack direction="row" spacing={0.5}>
                    <Tooltip title="Edit Training & Scopes">
                      <IconButton size="small" onClick={() => handleOpenEdit(t)}>
                        <EditIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                    <Tooltip title="Delete">
                      <IconButton size="small" color="error" onClick={() => handleDelete(t)}>
                        <DeleteIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
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
              <Button variant="contained" startIcon={<AddIcon />} onClick={handleOpenCreate}>
                Create Training
              </Button>
            </div>
          )}
        </div>

        {/* Create / Edit Training Modal */}
        <Dialog open={modalOpen} onClose={handleClose} maxWidth="md" fullWidth>
          <DialogTitle className="font-bold flex items-center justify-between">
            <span>{editingTraining ? `Edit: ${editingTraining.title}` : 'Create Training Master'}</span>
            <Chip
              label={data.status.toUpperCase()}
              size="small"
              color={data.status === 'active' ? 'success' : 'default'}
            />
          </DialogTitle>

          <form onSubmit={handleSubmit}>
            <DialogContent className="space-y-6">
              {/* General Metadata */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <TextField
                    required
                    fullWidth
                    label="Training Code"
                    placeholder="e.g. ERP-SALE-001"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    error={Boolean(errors.code)}
                    helperText={errors.code}
                  />
                </div>

                <div className="md:col-span-2">
                  <TextField
                    required
                    fullWidth
                    label="Training Title"
                    placeholder="e.g. Odoo Sales Workflow"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    error={Boolean(errors.title)}
                    helperText={errors.title}
                  />
                </div>

                <div>
                  <TextField
                    select
                    fullWidth
                    label="Category"
                    value={data.training_category_id}
                    onChange={(e) => setData('training_category_id', e.target.value)}
                  >
                    <MenuItem value="">-- Select Category --</MenuItem>
                    {categories.map((c) => (
                      <MenuItem key={c.id} value={String(c.id)}>
                        {c.name}
                      </MenuItem>
                    ))}
                  </TextField>
                </div>

                <div>
                  <TextField
                    fullWidth
                    label="Or New Category"
                    placeholder="e.g. ERP, Safety, Finance"
                    value={data.new_category_name}
                    onChange={(e) => setData('new_category_name', e.target.value)}
                  />
                </div>

                <div>
                  <TextField
                    select
                    fullWidth
                    label="Status"
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value)}
                  >
                    <MenuItem value="active">Active</MenuItem>
                    <MenuItem value="draft">Draft</MenuItem>
                    <MenuItem value="archived">Archived</MenuItem>
                  </TextField>
                </div>

                <div>
                  <TextField
                    required
                    type="number"
                    fullWidth
                    label="Retrain Interval"
                    value={data.retrain_interval}
                    onChange={(e) => setData('retrain_interval', e.target.value)}
                  />
                </div>

                <div>
                  <TextField
                    select
                    fullWidth
                    label="Retrain Unit"
                    value={data.retrain_unit}
                    onChange={(e) => setData('retrain_unit', e.target.value)}
                  >
                    <MenuItem value="day">Day(s)</MenuItem>
                    <MenuItem value="month">Month(s)</MenuItem>
                    <MenuItem value="year">Year(s)</MenuItem>
                  </TextField>
                </div>

                <div>
                  <TextField
                    required
                    type="number"
                    fullWidth
                    label="Passing Score (%)"
                    value={data.passing_score}
                    onChange={(e) => setData('passing_score', e.target.value)}
                    InputProps={{ endAdornment: <span className="text-slate-400 font-bold">%</span> }}
                  />
                </div>

                <div className="md:col-span-3">
                  <TextField
                    fullWidth
                    multiline
                    rows={2}
                    label="Course Description & Objectives"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                  />
                </div>
              </div>

              <Divider />

              {/* Connected Company SOPs & Documents Section */}
              <Paper elevation={0} className="p-4 rounded-xl border border-sky-200 bg-sky-50/40 dark:bg-sky-950/20 space-y-3">
                <div className="flex items-center justify-between">
                  <div>
                    <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                      <DocumentIcon fontSize="small" className="text-sky-600" />
                      Connected Company SOPs & Documents ({data.document_ids?.length || 0})
                    </Typography>
                    <Typography variant="caption" className="text-slate-500">
                      When linked company documents are revised or updated, this training module will automatically announce retraining for in-scope staff.
                    </Typography>
                  </div>

                  <Button
                    size="small"
                    variant="outlined"
                    startIcon={<SearchIcon />}
                    onClick={() => setDocSearchModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700 }}
                  >
                    Select / Search Documents
                  </Button>
                </div>

                {/* Selected Document Chips */}
                <div className="flex flex-wrap gap-2 pt-1">
                  {(data.document_ids || []).map((docId) => {
                    const doc = allDocuments.find((d) => d.id === docId);
                    if (!doc) return null;
                    return (
                      <Chip
                        key={doc.id}
                        icon={<DocumentIcon fontSize="small" />}
                        label={doc.title}
                        color="primary"
                        variant="outlined"
                        onDelete={() => removeDocument(doc.id)}
                        sx={{ fontWeight: 600, fontSize: '0.75rem' }}
                      />
                    );
                  })}

                  {(data.document_ids || []).length === 0 && (
                    <Typography variant="caption" className="text-slate-400 italic">
                      No company documents connected yet. Click "Select / Search Documents" to connect.
                    </Typography>
                  )}
                </div>
              </Paper>

              <Divider />

              {/* Target Audience Scopes Matrix */}
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <div>
                    <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                      <GroupAddIcon className="text-sky-600" />
                      Target Audience Scopes
                    </Typography>
                    <Typography variant="caption" className="text-slate-500">
                      Define Department rules (target all employees in the department or restrict to a specific office position).
                    </Typography>
                  </div>

                  <Button
                    variant="outlined"
                    size="small"
                    startIcon={<AddIcon />}
                    onClick={handleAddScope}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                  >
                    Add Scope Rule
                  </Button>
                </div>

                <div className="space-y-2">
                  {data.scopes.map((scope, idx) => (
                    <Paper
                      key={idx}
                      elevation={0}
                      className="p-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl flex items-center gap-3"
                    >
                      {/* Department Select */}
                      <TextField
                        select
                        size="small"
                        label="Department"
                        value={scope.department_id}
                        onChange={(e) => handleScopeChange(idx, 'department_id', e.target.value)}
                        className="flex-1"
                      >
                        {departments.map((d) => (
                          <MenuItem key={d.id} value={d.id}>
                            {d.name}
                          </MenuItem>
                        ))}
                      </TextField>

                      <span className="font-bold text-slate-400">→</span>

                      {/* Office Position Select */}
                      <TextField
                        select
                        size="small"
                        label="Office Position"
                        value={scope.office_position_id || ''}
                        onChange={(e) => handleScopeChange(idx, 'office_position_id', e.target.value)}
                        className="flex-1"
                        helperText="Leave empty to target all positions in department"
                      >
                        <MenuItem value="">
                          <em>All Positions (Entire Department)</em>
                        </MenuItem>
                        {officePositions.map((p) => (
                          <MenuItem key={p.id} value={p.id}>
                            {p.name}
                          </MenuItem>
                        ))}
                      </TextField>

                      <IconButton size="small" color="error" onClick={() => handleRemoveScope(idx)}>
                        <RemoveIcon fontSize="small" />
                      </IconButton>
                    </Paper>
                  ))}

                  {data.scopes.length === 0 && (
                    <Alert severity="info" className="rounded-xl">
                      No scope rules defined yet. Click "Add Scope Rule" to target specific departments and positions.
                    </Alert>
                  )}
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

        {/* Manual Bulk Assign Dialog */}
        <Dialog open={manualAssignOpen} onClose={() => setManualAssignOpen(false)} maxWidth="sm" fullWidth>
          <DialogTitle className="font-bold">
            Assign "{targetTraining?.title}" to Scope
          </DialogTitle>
          <form onSubmit={handleManualAssignSubmit}>
            <DialogContent className="space-y-4">
              <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                This will trigger a bulk assignment across all active employees matching this training's defined scopes:
              </Typography>

              <div className="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border text-xs space-y-1">
                {(targetTraining?.scopes || []).map((s) => (
                  <div key={s.id} className="flex items-center gap-1.5 font-medium">
                    <span>• {s.department?.name}</span>
                    <span className="text-slate-400">→</span>
                    <span className={s.office_position || s.officePosition ? 'font-semibold' : 'text-sky-700 dark:text-sky-400 font-semibold'}>
                      {s.office_position?.name || s.officePosition?.name || 'All Positions (Entire Department)'}
                    </span>
                  </div>
                ))}
              </div>

              <TextField
                fullWidth
                multiline
                rows={2}
                label="Assignment Reason (Optional)"
                placeholder="e.g. Annual company compliance refresh"
                value={assignData.reason}
                onChange={(e) => setAssignData('reason', e.target.value)}
              />
            </DialogContent>
            <DialogActions className="p-4">
              <Button onClick={() => setManualAssignOpen(false)} disabled={assignProcessing}>
                Cancel
              </Button>
              <Button
                type="submit"
                variant="contained"
                color="primary"
                disabled={assignProcessing}
                startIcon={<SendIcon />}
                sx={{ textTransform: 'none', fontWeight: 700 }}
              >
                Confirm & Trigger Assignments
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
