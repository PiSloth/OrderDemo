import React, { useState, useMemo } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import ReactRichTextEditor from '../../../components/Document/ReactRichTextEditor';
import { useDocumentDraft } from '../../../Hooks/useDocumentDraft';

import {
  Box,
  Button,
  Card,
  CardContent,
  Typography,
  TextField,
  MenuItem,
  Stack,
  Alert,
  CircularProgress,
  Divider,
  Paper,
  Chip,
  FormControlLabel,
  Checkbox,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  InputAdornment,
  IconButton,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer
} from '@mui/material';

import SchoolIcon from '@mui/icons-material/School';
import SaveIcon from '@mui/icons-material/Save';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import HistoryIcon from '@mui/icons-material/History';
import SearchIcon from '@mui/icons-material/Search';
import ClearIcon from '@mui/icons-material/Clear';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import AddIcon from '@mui/icons-material/Add';
import MenuBookIcon from '@mui/icons-material/MenuBook';
import CloudDoneIcon from '@mui/icons-material/CloudDone';
import RestoreIcon from '@mui/icons-material/Restore';
import DeleteIcon from '@mui/icons-material/Delete';

export default function Edit({
  document = {},
  departments = [],
  documentTypes = [],
  trainings = []
}) {
  const [createNewType, setCreateNewType] = useState(false);
  const [trainingSearchModalOpen, setTrainingSearchModalOpen] = useState(false);
  const [trainingSearchQuery, setTrainingSearchQuery] = useState('');
  const [trainingCategoryFilter, setTrainingCategoryFilter] = useState('');

  // Extract currently linked training IDs
  const initialLinkedTrainingIds = useMemo(
    () => (document.trainings || []).map((t) => t.id),
    [document.trainings]
  );

  const initialFormData = useMemo(() => ({
    title: document.title || '',
    company_document_type_id: document.company_document_type_id ? String(document.company_document_type_id) : '',
    new_document_type: '',
    department_id: document.department_id ? String(document.department_id) : '',
    announced_at: document.announced_at
      ? (document.announced_at.includes('T')
          ? document.announced_at.split('T')[0]
          : document.announced_at.split(' ')[0])
      : '',
    body: document.body || '',
    training_required: false,
    training_ids: initialLinkedTrainingIds,
    training_id: initialLinkedTrainingIds[0] ? String(initialLinkedTrainingIds[0]) : '',
    training_reason: '',
    change_summary: '',
  }), [document, initialLinkedTrainingIds]);

  const { data, setData, put, processing, errors } = useForm(initialFormData);

  // Auto-save & draft recovery via localStorage
  const {
    draftDetected,
    draftSavedAt,
    lastSavedTime,
    restoreDraft,
    discardDraft,
    clearDraft,
  } = useDocumentDraft({
    storageKey: `stt_doc_draft_edit_${document.id}`,
    data,
    setData,
    initialData: initialFormData,
    isEdit: true,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    put(`/document/library/${document.id}`, {
      onSuccess: () => {
        clearDraft();
      },
    });
  };

  const toggleTrainingSelection = (trainingId) => {
    const current = [...(data.training_ids || [])];
    const exists = current.includes(trainingId);
    let updated;
    if (exists) {
      updated = current.filter((id) => id !== trainingId);
    } else {
      updated = [...current, trainingId];
    }
    setData('training_ids', updated);
    if (updated.length > 0 && !data.training_id) {
      setData('training_id', String(updated[0]));
    }
  };

  const removeTraining = (trainingId) => {
    const updated = (data.training_ids || []).filter((id) => id !== trainingId);
    setData('training_ids', updated);
    if (String(data.training_id) === String(trainingId)) {
      setData('training_id', updated[0] ? String(updated[0]) : '');
    }
  };

  // Filter trainings in the search modal
  const filteredTrainings = trainings.filter((t) => {
    const matchesCategory = !trainingCategoryFilter || String(t.training_category_id) === String(trainingCategoryFilter);
    if (!matchesCategory) return false;
    if (!trainingSearchQuery) return true;
    const q = trainingSearchQuery.toLowerCase();
    return (
      (t.title && t.title.toLowerCase().includes(q)) ||
      (t.code && t.code.toLowerCase().includes(q)) ||
      (t.category?.name && t.category.name.toLowerCase().includes(q))
    );
  });

  // Unique categories for filter dropdown
  const trainingCategories = Array.from(
    new Set(trainings.map((t) => t.category).filter(Boolean).map((c) => JSON.stringify(c)))
  ).map((c) => JSON.parse(c));

  return (
    <AsideLayout title={`Edit Document - ${document.title || ''}`}>
      <Head title={`Edit: ${document.title} - Document Library`} />

      <Box className="max-w-5xl mx-auto space-y-6">
        {/* Top Header */}
        <Box className="flex items-center justify-between">
          <Box>
            <Typography variant="h5" className="font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-2">
              Edit Document
              <Chip label={`v${document.revisions?.length ? document.revisions[0].version + 1 : 1} (Next Rev)`} size="small" color="primary" />
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Update document content and manage linked Training Catalog modules for automatic compliance triggers.
            </Typography>
          </Box>

          <Button
            variant="outlined"
            startIcon={<ArrowBackIcon />}
            component={Link}
            href={`/document/library?doc=${document.id}`}
            sx={{ textTransform: 'none', borderRadius: 2 }}
          >
            Back to Document
          </Button>
        </Box>

        {Object.keys(errors).length > 0 && (
          <Alert severity="error" className="rounded-xl">
            Please correct the validation errors below before submitting.
          </Alert>
        )}

        {/* Unsaved Draft Recovery Alert */}
        {draftDetected && (
          <Alert
            severity="info"
            icon={<RestoreIcon />}
            className="rounded-2xl border border-sky-300 dark:border-sky-800 bg-sky-50 dark:bg-sky-950/40"
            action={
              <Stack direction="row" spacing={1} alignItems="center">
                <Button
                  size="small"
                  variant="contained"
                  color="primary"
                  onClick={restoreDraft}
                  startIcon={<RestoreIcon fontSize="small" />}
                  sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
                >
                  Restore Draft
                </Button>
                <Button
                  size="small"
                  variant="outlined"
                  color="inherit"
                  onClick={discardDraft}
                  startIcon={<DeleteIcon fontSize="small" />}
                  sx={{ textTransform: 'none', borderRadius: 2 }}
                >
                  Discard
                </Button>
              </Stack>
            }
          >
            <Typography variant="subtitle2" className="font-bold text-sky-900 dark:text-sky-200">
              Unsaved draft detected for this document
            </Typography>
            <Typography variant="body2" className="text-xs text-sky-800 dark:text-sky-300 mt-0.5">
              We recovered unsaved changes from {draftSavedAt ? new Date(draftSavedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'a previous session'}. Click "Restore Draft" to recover your edits.
            </Typography>
          </Alert>
        )}

        {/* Main Form */}
        <Card elevation={0} sx={{ overflow: 'visible' }} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
          <CardContent sx={{ overflow: 'visible' }} className="p-6">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Title */}
                <div className="md:col-span-2">
                  <TextField
                    label="Document Title"
                    required
                    fullWidth
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    error={Boolean(errors.title)}
                    helperText={errors.title}
                  />
                </div>

                {/* Document Type */}
                <div>
                  {!createNewType ? (
                    <TextField
                      select
                      fullWidth
                      label="Document Type / Category"
                      value={data.company_document_type_id}
                      onChange={(e) => setData('company_document_type_id', e.target.value)}
                      error={Boolean(errors.company_document_type_id)}
                      helperText={errors.company_document_type_id}
                    >
                      <MenuItem value="">-- Select Type --</MenuItem>
                      {documentTypes.map((type) => (
                        <MenuItem key={type.id} value={String(type.id)}>
                          {type.name}
                        </MenuItem>
                      ))}
                    </TextField>
                  ) : (
                    <TextField
                      fullWidth
                      label="New Document Type Name"
                      value={data.new_document_type}
                      onChange={(e) => setData('new_document_type', e.target.value)}
                      error={Boolean(errors.new_document_type)}
                      helperText={errors.new_document_type}
                      placeholder="e.g. Policy, Guideline"
                    />
                  )}

                  <Box className="mt-1">
                    <Button
                      size="small"
                      variant="text"
                      onClick={() => {
                        setCreateNewType(!createNewType);
                        if (!createNewType) {
                          setData('company_document_type_id', '');
                        } else {
                          setData('new_document_type', '');
                        }
                      }}
                      sx={{ textTransform: 'none', fontSize: '0.75rem', p: 0 }}
                    >
                      {createNewType ? '← Select existing type' : '+ Create new document type'}
                    </Button>
                  </Box>
                </div>

                {/* Department */}
                <div>
                  <TextField
                    select
                    fullWidth
                    required
                    label="Department"
                    value={data.department_id}
                    onChange={(e) => setData('department_id', e.target.value)}
                    error={Boolean(errors.department_id)}
                    helperText={errors.department_id}
                  >
                    <MenuItem value="">-- Select Department --</MenuItem>
                    {departments.map((dept) => (
                      <MenuItem key={dept.id} value={String(dept.id)}>
                        {dept.name}
                      </MenuItem>
                    ))}
                  </TextField>
                </div>

                {/* Announced Date */}
                <div>
                  <TextField
                    type="date"
                    fullWidth
                    label="Announced / Effective Date"
                    InputLabelProps={{ shrink: true }}
                    value={data.announced_at}
                    onChange={(e) => setData('announced_at', e.target.value)}
                    error={Boolean(errors.announced_at)}
                    helperText={errors.announced_at || 'Optional effective date'}
                  />
                </div>

                {/* Change Summary */}
                <div>
                  <TextField
                    fullWidth
                    label="Revision Summary"
                    placeholder="Brief description of what changed in this version"
                    value={data.change_summary}
                    onChange={(e) => setData('change_summary', e.target.value)}
                    error={Boolean(errors.change_summary)}
                    helperText={errors.change_summary}
                  />
                </div>
              </div>

              <Divider />

              {/* Connected Training Catalogs Section with Search Modal */}
              <Paper
                elevation={0}
                className="p-4 rounded-xl border border-sky-200 bg-sky-50/40 dark:bg-sky-950/20 dark:border-sky-800 space-y-3"
              >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <Typography variant="subtitle2" className="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                      <SchoolIcon className="text-sky-600" fontSize="small" />
                      Connected Training Catalogs ({data.training_ids?.length || 0})
                    </Typography>
                    <Typography variant="caption" className="text-slate-500 dark:text-slate-400">
                      Link this company document to relevant training modules. Updating this document can automatically announce retraining to employees in scope.
                    </Typography>
                  </div>

                  <Button
                    variant="outlined"
                    size="small"
                    startIcon={<SearchIcon />}
                    onClick={() => setTrainingSearchModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700 }}
                  >
                    Select / Search Training Catalogs
                  </Button>
                </div>

                {/* Selected Training Chips */}
                <div className="flex flex-wrap gap-2 pt-2">
                  {(data.training_ids || []).map((tId) => {
                    const t = trainings.find((item) => item.id === tId);
                    if (!t) return null;
                    return (
                      <Chip
                        key={t.id}
                        icon={<MenuBookIcon fontSize="small" />}
                        label={`${t.code} - ${t.title}`}
                        color="primary"
                        variant="outlined"
                        onDelete={() => removeTraining(t.id)}
                        sx={{ fontWeight: 600, fontSize: '0.75rem' }}
                      />
                    );
                  })}

                  {(data.training_ids || []).length === 0 && (
                    <Typography variant="caption" className="text-slate-400 italic">
                      No training catalogs linked yet. Click "Select / Search Training Catalogs" to connect.
                    </Typography>
                  )}
                </div>

                {/* Checkbox: Announce / Trigger Retraining */}
                <div className="pt-3 border-t border-sky-100 dark:border-sky-900/50">
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={data.training_required}
                        onChange={(e) => setData('training_required', e.target.checked)}
                        color="primary"
                      />
                    }
                    label={
                      <div>
                        <span className="text-xs font-bold text-slate-800 dark:text-slate-200">
                          ☑ Announce and Require Retraining for In-Scope Employees
                        </span>
                        <span className="block text-[11px] text-slate-500">
                          When checked, saving this version will immediately create a WORKFLOW_CHANGE trigger and schedule sessions for all employees with matching Department & Office Position scopes.
                        </span>
                      </div>
                    }
                  />

                  {data.training_required && (
                    <div className="mt-3 ml-7">
                      <TextField
                        fullWidth
                        size="small"
                        label="Workflow Change Reason (Announced to Employees)"
                        placeholder="e.g. Payment verification step updated; Cashier staff must complete session"
                        value={data.training_reason}
                        onChange={(e) => setData('training_reason', e.target.value)}
                        error={Boolean(errors.training_reason)}
                        helperText={errors.training_reason || 'This reason is recorded in the compliance trigger audit logs.'}
                      />
                    </div>
                  )}
                </div>
              </Paper>

              <Divider />

              {/* Rich Text Editor */}
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Typography variant="subtitle2" className="font-bold text-slate-800 dark:text-slate-200">
                    Document Content <span className="text-red-500">*</span>
                  </Typography>

                  {lastSavedTime && (
                    <div className="flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800">
                      <CloudDoneIcon sx={{ fontSize: 13 }} />
                      <span>Draft saved locally at {lastSavedTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                  )}
                </div>
                <ReactRichTextEditor
                  value={data.body}
                  onChange={(html) => setData('body', html)}
                  placeholder="Edit document content, update tables, and adjust formatting..."
                  minHeight="420px"
                />
                {errors.body && (
                  <Typography variant="caption" className="text-red-600 block mt-1">
                    {errors.body}
                  </Typography>
                )}
              </div>

              {/* Actions */}
              <Box className="flex items-center justify-between pt-4">
                <Typography variant="caption" className="text-slate-400">
                  Current Version: <b>v{document.revisions?.length || 1}</b> • Created by <b>{document.author?.name || 'Unknown'}</b>
                </Typography>

                <Box className="flex items-center gap-3">
                  <Button
                    variant="outlined"
                    component={Link}
                    href={`/document/library?doc=${document.id}`}
                    disabled={processing}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                  >
                    Cancel
                  </Button>
                  <Button
                    type="submit"
                    variant="contained"
                    color="primary"
                    disabled={processing}
                    startIcon={processing ? <CircularProgress size={16} color="inherit" /> : <SaveIcon />}
                    sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2, px: 3 }}
                  >
                    {processing ? 'Saving...' : 'Save & Publish Changes'}
                  </Button>
                </Box>
              </Box>
            </form>
          </CardContent>
        </Card>

        {/* Training Search Modal */}
        <Dialog
          open={trainingSearchModalOpen}
          onClose={() => setTrainingSearchModalOpen(false)}
          maxWidth="md"
          fullWidth
        >
          <DialogTitle className="font-bold flex items-center justify-between">
            <div className="flex items-center gap-2">
              <SchoolIcon className="text-sky-600" />
              <span>Select Training Catalogs</span>
            </div>
            <Chip
              label={`${data.training_ids?.length || 0} Selected`}
              color="primary"
              size="small"
              sx={{ fontWeight: 700 }}
            />
          </DialogTitle>

          <DialogContent className="space-y-4">
            <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
              Search and connect training catalog courses to this company document.
            </Typography>

            {/* Search Filters */}
            <div className="flex flex-col sm:flex-row items-center gap-3">
              <TextField
                fullWidth
                size="small"
                placeholder="Search training title or code (e.g. ERP-SALE-001)..."
                value={trainingSearchQuery}
                onChange={(e) => setTrainingSearchQuery(e.target.value)}
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchIcon fontSize="small" className="text-slate-400" />
                    </InputAdornment>
                  ),
                  endAdornment: trainingSearchQuery ? (
                    <InputAdornment position="end">
                      <IconButton size="small" onClick={() => setTrainingSearchQuery('')}>
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    </InputAdornment>
                  ) : null,
                }}
              />

              <TextField
                select
                size="small"
                label="Category"
                value={trainingCategoryFilter}
                onChange={(e) => setTrainingCategoryFilter(e.target.value)}
                className="w-full sm:w-56"
              >
                <MenuItem value="">All Categories</MenuItem>
                {trainingCategories.map((c) => (
                  <MenuItem key={c.id} value={String(c.id)}>
                    {c.name}
                  </MenuItem>
                ))}
              </TextField>
            </div>

            {/* Trainings Table */}
            <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl max-h-96 overflow-y-auto">
              <Table size="small" stickyHeader>
                <TableHead className="bg-slate-100 dark:bg-slate-800">
                  <TableRow>
                    <TableCell padding="checkbox">
                      <Checkbox
                        size="small"
                        checked={
                          filteredTrainings.length > 0 &&
                          filteredTrainings.every((t) => (data.training_ids || []).includes(t.id))
                        }
                        indeterminate={
                          filteredTrainings.some((t) => (data.training_ids || []).includes(t.id)) &&
                          !filteredTrainings.every((t) => (data.training_ids || []).includes(t.id))
                        }
                        onChange={(e) => {
                          if (e.target.checked) {
                            const newIds = new Set([...(data.training_ids || []), ...filteredTrainings.map((t) => t.id)]);
                            setData('training_ids', Array.from(newIds));
                          } else {
                            const removeIds = new Set(filteredTrainings.map((t) => t.id));
                            setData(
                              'training_ids',
                              (data.training_ids || []).filter((id) => !removeIds.has(id))
                            );
                          }
                        }}
                      />
                    </TableCell>
                    <TableCell className="font-bold text-xs">Course Code & Title</TableCell>
                    <TableCell className="font-bold text-xs">Category</TableCell>
                    <TableCell className="font-bold text-xs">Retraining & Passing</TableCell>
                    <TableCell className="font-bold text-xs">Target Audience Scopes</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {filteredTrainings.map((training) => {
                    const isSelected = (data.training_ids || []).includes(training.id);
                    return (
                      <TableRow
                        key={training.id}
                        hover
                        onClick={() => toggleTrainingSelection(training.id)}
                        className={`cursor-pointer transition-colors ${
                          isSelected ? 'bg-sky-50/60 dark:bg-sky-950/40' : ''
                        }`}
                      >
                        <TableCell padding="checkbox">
                          <Checkbox
                            size="small"
                            checked={isSelected}
                            color="primary"
                            onChange={() => toggleTrainingSelection(training.id)}
                          />
                        </TableCell>

                        <TableCell>
                          <div className="font-bold text-xs text-slate-800 dark:text-slate-100">
                            {training.title}
                          </div>
                          <div className="text-[11px] text-sky-600 font-mono font-bold">
                            {training.code}
                          </div>
                        </TableCell>

                        <TableCell className="text-xs text-slate-600 dark:text-slate-300">
                          {training.category?.name || '—'}
                        </TableCell>

                        <TableCell className="text-xs">
                          <div className="text-slate-700 dark:text-slate-200">
                            {training.retrain_interval} {training.retrain_unit}(s)
                          </div>
                          <div className="text-[11px] text-emerald-600 font-semibold">
                            Pass: {training.passing_score}%
                          </div>
                        </TableCell>

                        <TableCell className="text-xs">
                          <div className="flex flex-wrap gap-1 max-w-xs">
                            {(training.scopes || []).map((sc, idx) => (
                              <Chip
                                key={idx}
                                label={`${sc.department?.name || 'Dept'} + ${sc.office_position?.name || 'Position'}`}
                                size="small"
                                variant="outlined"
                                sx={{ fontSize: '0.65rem' }}
                              />
                            ))}
                            {(training.scopes || []).length === 0 && (
                              <span className="text-slate-400 italic text-[11px]">No specific scope</span>
                            )}
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  })}

                  {filteredTrainings.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={5} align="center" className="text-slate-400 py-8">
                        No training catalogs found matching "{trainingSearchQuery}".
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </DialogContent>

          <DialogActions className="p-4">
            <Button
              onClick={() => setTrainingSearchModalOpen(false)}
              variant="contained"
              color="primary"
              startIcon={<CheckCircleIcon />}
              sx={{ textTransform: 'none', fontWeight: 700 }}
            >
              Done ({data.training_ids?.length || 0} Selected)
            </Button>
          </DialogActions>
        </Dialog>
      </Box>
    </AsideLayout>
  );
}
