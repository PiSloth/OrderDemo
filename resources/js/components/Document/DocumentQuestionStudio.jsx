import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Box,
  Typography,
  Button,
  IconButton,
  TextField,
  MenuItem,
  Stack,
  Divider,
  Paper,
  Radio,
  Checkbox,
  Chip,
  Alert,
  Tooltip,
  CircularProgress
} from '@mui/material';

import CloseIcon from '@mui/icons-material/Close';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import SaveIcon from '@mui/icons-material/Save';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import ArrowUpwardIcon from '@mui/icons-material/ArrowUpward';
import ArrowDownwardIcon from '@mui/icons-material/ArrowDownward';
import QuizIcon from '@mui/icons-material/Quiz';
import ArticleIcon from '@mui/icons-material/Article';
import AutoAwesomeIcon from '@mui/icons-material/AutoAwesome';
import SchoolIcon from '@mui/icons-material/School';

export default function DocumentQuestionStudio({
  open,
  onClose,
  document: doc,
  canCreate = true,
  canUpdate = true,
  canDelete = true,
  onQuestionsUpdated
}) {
  const [questions, setQuestions] = useState([]);
  const [saving, setSaving] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    if (open && doc) {
      // Map initial questions from document
      const initial = (doc.questions || []).map((q, idx) => ({
        _uid: q.id ? `q-${q.id}` : `q-init-${idx}-${Date.now()}`,
        id: q.id || null,
        question: q.question || '',
        question_type: q.question_type || 'MULTIPLE_CHOICE',
        marks: q.marks ?? 1.0,
        document_section_reference: q.document_section_reference || '',
        options: (q.options || []).map((opt) => ({
          id: opt.id || null,
          answer: opt.answer || '',
          is_correct: Boolean(opt.is_correct),
        })),
      }));
      setQuestions(initial);
      setErrorMessage('');
      setSuccessMessage('');
    }
  }, [open, doc]);

  const handleAddQuestion = (type = 'MULTIPLE_CHOICE') => {
    const defaultOptions =
      type === 'TRUE_FALSE'
        ? [
            { id: null, answer: 'True', is_correct: true },
            { id: null, answer: 'False', is_correct: false },
          ]
        : type === 'MULTI_SELECT'
        ? [
            { id: null, answer: 'Option A', is_correct: true },
            { id: null, answer: 'Option B', is_correct: true },
            { id: null, answer: 'Option C', is_correct: false },
            { id: null, answer: 'Option D', is_correct: false },
          ]
        : [
            { id: null, answer: 'Option A', is_correct: true },
            { id: null, answer: 'Option B', is_correct: false },
            { id: null, answer: 'Option C', is_correct: false },
            { id: null, answer: 'Option D', is_correct: false },
          ];

    setQuestions((prev) => [
      ...prev,
      {
        _uid: `q-new-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
        id: null,
        question: '',
        question_type: type,
        marks: 1.0,
        document_section_reference: '',
        options: defaultOptions,
      },
    ]);
  };

  const handleMoveQuestion = (qIndex, direction) => {
    const targetIndex = direction === 'up' ? qIndex - 1 : qIndex + 1;
    if (targetIndex < 0 || targetIndex >= questions.length) return;

    const updated = [...questions];
    const item = updated[qIndex];
    updated[qIndex] = updated[targetIndex];
    updated[targetIndex] = item;
    setQuestions(updated);
  };

  const handleRemoveQuestion = (qIndex) => {
    const updated = [...questions];
    updated.splice(qIndex, 1);
    setQuestions(updated);
  };

  const handleQuestionFieldChange = (qIndex, field, value) => {
    const updated = [...questions];
    updated[qIndex][field] = value;
    setQuestions(updated);
  };

  const handleQuestionTypeChange = (qIndex, newType) => {
    const updated = [...questions];
    updated[qIndex].question_type = newType;
    if (newType === 'TRUE_FALSE') {
      updated[qIndex].options = [
        { id: null, answer: 'True', is_correct: true },
        { id: null, answer: 'False', is_correct: false },
      ];
    } else {
      if (updated[qIndex].options.length < 2) {
        updated[qIndex].options = [
          { id: null, answer: 'Option A', is_correct: true },
          { id: null, answer: 'Option B', is_correct: false },
        ];
      }
      if (newType === 'MULTIPLE_CHOICE') {
        let foundFirst = false;
        updated[qIndex].options = updated[qIndex].options.map((opt) => {
          if (opt.is_correct && !foundFirst) {
            foundFirst = true;
            return opt;
          }
          return { ...opt, is_correct: false };
        });
        if (!foundFirst && updated[qIndex].options.length > 0) {
          updated[qIndex].options[0].is_correct = true;
        }
      }
    }
    setQuestions(updated);
  };

  const handleOptionAnswerChange = (qIndex, oIndex, value) => {
    const updated = [...questions];
    updated[qIndex].options[oIndex].answer = value;
    setQuestions(updated);
  };

  const handleToggleCorrectOption = (qIndex, oIndex) => {
    const updated = [...questions];
    const q = updated[qIndex];
    if (q.question_type === 'MULTI_SELECT') {
      q.options[oIndex].is_correct = !q.options[oIndex].is_correct;
    } else {
      q.options = q.options.map((opt, idx) => ({
        ...opt,
        is_correct: idx === oIndex,
      }));
    }
    setQuestions(updated);
  };

  const handleAddOption = (qIndex) => {
    const updated = [...questions];
    const letter = String.fromCharCode(65 + updated[qIndex].options.length);
    updated[qIndex].options.push({
      id: null,
      answer: `Option ${letter}`,
      is_correct: false,
    });
    setQuestions(updated);
  };

  const handleRemoveOption = (qIndex, oIndex) => {
    const updated = [...questions];
    if (updated[qIndex].options.length <= 2) return;
    updated[qIndex].options.splice(oIndex, 1);
    if (!updated[qIndex].options.some((o) => o.is_correct)) {
      updated[qIndex].options[0].is_correct = true;
    }
    setQuestions(updated);
  };

  const handleSaveAll = async () => {
    setErrorMessage('');
    setSuccessMessage('');

    // Validation
    for (let i = 0; i < questions.length; i++) {
      const q = questions[i];
      if (!q.question.trim()) {
        setErrorMessage(`Question #${i + 1} cannot have an empty question prompt.`);
        return;
      }
      const hasCorrect = q.options.some((opt) => opt.is_correct);
      if (!hasCorrect) {
        setErrorMessage(`Question #${i + 1} must have at least one marked correct answer.`);
        return;
      }
      const hasEmptyOption = q.options.some((opt) => !opt.answer.trim());
      if (hasEmptyOption) {
        setErrorMessage(`Question #${i + 1} has an empty option answer.`);
        return;
      }
    }

    setSaving(true);
    try {
      const payload = {
        questions: questions.map((q) => ({
          id: q.id,
          question: q.question,
          question_type: q.question_type,
          marks: q.marks,
          document_section_reference: q.document_section_reference,
          options: q.options.map((opt) => ({
            id: opt.id,
            answer: opt.answer,
            is_correct: opt.is_correct,
          })),
        })),
      };

      const response = await axios.put(`/document/library/${doc.id}/questions/sync`, payload);
      setSuccessMessage('Document assessment questions saved and synced across all referencing catalogs.');

      if (onQuestionsUpdated && response.data?.questions) {
        onQuestionsUpdated(response.data.questions);
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.response?.data?.errors?.questions?.[0] || 'Failed to save questions.';
      setErrorMessage(msg);
    } finally {
      setSaving(false);
    }
  };

  if (!doc) return null;

  const trainingCount = doc.trainings?.length || 0;

  return (
    <Dialog
      open={open}
      onClose={onClose}
      fullScreen
      PaperProps={{
        sx: {
          bgcolor: 'slate.50',
          display: 'flex',
          flexDirection: 'column',
        },
      }}
    >
      {/* Studio Header */}
      <DialogTitle
        sx={{
          py: 2,
          px: 3,
          bgcolor: 'background.paper',
          borderBottom: 1,
          borderColor: 'divider',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
        }}
      >
        <Box className="flex items-center gap-3">
          <Box className="p-2 rounded-xl bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300">
            <QuizIcon />
          </Box>
          <div>
            <Typography variant="h6" className="font-bold flex items-center gap-2">
              Document Assessment Studio: <span className="text-sky-600">{doc.title}</span>
            </Typography>
            <Typography variant="caption" className="text-slate-500 flex items-center gap-2">
              Analyze document text on the left and craft assessment questions on the right.
              {trainingCount > 0 && (
                <Chip
                  size="small"
                  icon={<SchoolIcon sx={{ fontSize: '14px !important' }} />}
                  label={`Auto-propagates to ${trainingCount} Training Catalog(s)`}
                  color="primary"
                  variant="outlined"
                  sx={{ height: 20, fontSize: '0.68rem', fontWeight: 600 }}
                />
              )}
            </Typography>
          </div>
        </Box>

        <Box className="flex items-center gap-2">
          <Button
            variant="contained"
            color="primary"
            startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <SaveIcon />}
            disabled={saving || (!canCreate && !canUpdate)}
            onClick={handleSaveAll}
            sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2, px: 3 }}
          >
            {saving ? 'Syncing...' : 'Save & Sync Questions'}
          </Button>

          <IconButton edge="end" onClick={onClose} aria-label="close">
            <CloseIcon />
          </IconButton>
        </Box>
      </DialogTitle>

      {/* Messages */}
      {errorMessage && (
        <Alert severity="error" onClose={() => setErrorMessage('')} sx={{ mx: 3, mt: 2, borderRadius: 2 }}>
          {errorMessage}
        </Alert>
      )}
      {successMessage && (
        <Alert severity="success" onClose={() => setSuccessMessage('')} sx={{ mx: 3, mt: 2, borderRadius: 2 }}>
          {successMessage}
        </Alert>
      )}

      {/* Studio Body: Split View */}
      <DialogContent sx={{ p: 0, display: 'flex', flex: 1, overflow: 'hidden' }}>
        {/* Left Pane: Document Text Reader (50%) */}
        <Box
          sx={{
            width: '50%',
            height: '100%',
            overflowY: 'auto',
            p: 4,
            borderRight: 1,
            borderColor: 'divider',
            bgcolor: 'background.paper',
          }}
        >
          <Box className="flex items-center justify-between mb-4 pb-2 border-b border-slate-200 dark:border-slate-800">
            <Typography variant="subtitle2" className="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <ArticleIcon fontSize="small" className="text-sky-600" />
              Document Reference Text
            </Typography>
            <Chip
              label={doc.type?.name || 'Document'}
              size="small"
              variant="outlined"
              sx={{ fontWeight: 600, fontSize: '0.7rem' }}
            />
          </Box>

          <Box
            className="prose prose-slate dark:prose-invert max-w-none leading-relaxed text-slate-800 dark:text-slate-200"
            dangerouslySetInnerHTML={{
              __html: doc.body || '<p class="text-slate-400">Empty document text.</p>',
            }}
          />
        </Box>

        {/* Right Pane: Question Studio (50%) */}
        <Box
          sx={{
            width: '50%',
            height: '100%',
            overflowY: 'auto',
            p: 3,
            bgcolor: 'slate.50',
          }}
          className="space-y-4"
        >
          {/* Top Question Actions Bar */}
          <Box className="flex items-center justify-between bg-white dark:bg-slate-900 p-3 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
              <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100">
                Assessment Questions ({questions.length})
              </Typography>
              <Typography variant="caption" className="text-slate-500">
                Total Marks:{' '}
                <b>{questions.reduce((sum, q) => sum + (Number(q.marks) || 0), 0)} pts</b>
              </Typography>
            </div>

            <Stack direction="row" spacing={1}>
              <Button
                size="small"
                variant="outlined"
                startIcon={<AddIcon />}
                disabled={!canCreate}
                onClick={() => handleAddQuestion('MULTIPLE_CHOICE')}
                sx={{ textTransform: 'none', fontWeight: 600 }}
              >
                + Single Choice
              </Button>
              <Button
                size="small"
                variant="outlined"
                color="info"
                startIcon={<AddIcon />}
                disabled={!canCreate}
                onClick={() => handleAddQuestion('MULTI_SELECT')}
                sx={{ textTransform: 'none', fontWeight: 600 }}
              >
                + Multi-Select
              </Button>
              <Button
                size="small"
                variant="outlined"
                color="secondary"
                startIcon={<AddIcon />}
                disabled={!canCreate}
                onClick={() => handleAddQuestion('TRUE_FALSE')}
                sx={{ textTransform: 'none', fontWeight: 600 }}
              >
                + True/False
              </Button>
            </Stack>
          </Box>

          {/* Question List */}
          {questions.length === 0 ? (
            <Paper
              elevation={0}
              className="p-8 text-center rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 space-y-3"
            >
              <AutoAwesomeIcon sx={{ fontSize: 40, color: 'text.secondary' }} />
              <Typography variant="subtitle1" className="font-semibold text-slate-700 dark:text-slate-300">
                No Questions Created for this Document
              </Typography>
              <Typography variant="body2" className="text-slate-500 max-w-md mx-auto">
                Analyze the document text on the left, then click Single Choice, Multi-Select, or True/False above to create questions testing knowledge of this SOP or policy.
              </Typography>
            </Paper>
          ) : (
            questions.map((q, qIndex) => (
              <Paper
                key={q._uid || q.id || qIndex}
                elevation={0}
                className="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-4 shadow-sm"
              >
                {/* Header of Question */}
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <span className="w-6 h-6 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-bold text-xs flex items-center justify-center">
                      {qIndex + 1}
                    </span>
                    <Chip
                      label={
                        q.question_type === 'MULTI_SELECT'
                          ? 'Multi-Select'
                          : q.question_type === 'TRUE_FALSE'
                          ? 'True / False'
                          : 'Single Choice'
                      }
                      size="small"
                      color={
                        q.question_type === 'MULTI_SELECT'
                          ? 'info'
                          : q.question_type === 'TRUE_FALSE'
                          ? 'secondary'
                          : 'primary'
                      }
                      sx={{ fontWeight: 700, fontSize: '0.68rem' }}
                    />

                    {/* Move Up/Down */}
                    <Stack direction="row" spacing={0.5}>
                      <IconButton
                        size="small"
                        disabled={qIndex === 0}
                        onClick={() => handleMoveQuestion(qIndex, 'up')}
                        sx={{ p: 0.5 }}
                      >
                        <ArrowUpwardIcon sx={{ fontSize: 16 }} />
                      </IconButton>
                      <IconButton
                        size="small"
                        disabled={qIndex === questions.length - 1}
                        onClick={() => handleMoveQuestion(qIndex, 'down')}
                        sx={{ p: 0.5 }}
                      >
                        <ArrowDownwardIcon sx={{ fontSize: 16 }} />
                      </IconButton>
                    </Stack>
                  </div>

                  <Stack direction="row" spacing={1} alignItems="center">
                    <TextField
                      size="small"
                      type="number"
                      label="Marks"
                      value={q.marks}
                      onChange={(e) => handleQuestionFieldChange(qIndex, 'marks', Number(e.target.value))}
                      sx={{ width: 80 }}
                    />
                    <TextField
                      select
                      size="small"
                      label="Type"
                      value={q.question_type}
                      onChange={(e) => handleQuestionTypeChange(qIndex, e.target.value)}
                      sx={{ width: 140 }}
                    >
                      <MenuItem value="MULTIPLE_CHOICE">Single Choice</MenuItem>
                      <MenuItem value="MULTI_SELECT">Multi-Select</MenuItem>
                      <MenuItem value="TRUE_FALSE">True / False</MenuItem>
                    </TextField>
                    <IconButton
                      size="small"
                      color="error"
                      disabled={!canDelete}
                      onClick={() => handleRemoveQuestion(qIndex)}
                    >
                      <DeleteIcon fontSize="small" />
                    </IconButton>
                  </Stack>
                </div>

                {/* Section Reference */}
                <TextField
                  fullWidth
                  size="small"
                  label="Document Section Reference (Optional)"
                  placeholder="e.g. Section 3.2 - Handling Hazardous Waste"
                  value={q.document_section_reference || ''}
                  onChange={(e) => handleQuestionFieldChange(qIndex, 'document_section_reference', e.target.value)}
                />

                {/* Question Prompt */}
                <TextField
                  required
                  fullWidth
                  multiline
                  rows={2}
                  label={`Question #${qIndex + 1} Prompt`}
                  placeholder="Enter the assessment question..."
                  value={q.question}
                  onChange={(e) => handleQuestionFieldChange(qIndex, 'question', e.target.value)}
                />

                <Divider />

                {/* Options Section */}
                <div className="space-y-2">
                  <Typography variant="caption" className="font-bold text-slate-500 uppercase tracking-wider block">
                    {q.question_type === 'MULTI_SELECT'
                      ? 'Mark check boxes for all correct answers'
                      : 'Select radio button for the single correct answer'}
                  </Typography>

                  <div className="space-y-2 pl-2">
                    {q.options.map((opt, oIndex) => (
                      <div key={oIndex} className="flex items-center gap-2">
                        {q.question_type === 'MULTI_SELECT' ? (
                          <Checkbox
                            checked={opt.is_correct}
                            onChange={() => handleToggleCorrectOption(qIndex, oIndex)}
                            color="success"
                            size="small"
                          />
                        ) : (
                          <Radio
                            checked={opt.is_correct}
                            onChange={() => handleToggleCorrectOption(qIndex, oIndex)}
                            color="success"
                            size="small"
                          />
                        )}

                        <TextField
                          fullWidth
                          size="small"
                          disabled={q.question_type === 'TRUE_FALSE'}
                          value={opt.answer}
                          onChange={(e) => handleOptionAnswerChange(qIndex, oIndex, e.target.value)}
                          placeholder={`Option ${String.fromCharCode(65 + oIndex)}`}
                          error={opt.is_correct && !opt.answer}
                        />

                        {opt.is_correct && (
                          <Chip
                            label="Correct"
                            size="small"
                            color="success"
                            icon={<CheckCircleIcon />}
                            sx={{ fontWeight: 700, fontSize: '0.68rem' }}
                          />
                        )}

                        {(q.question_type === 'MULTIPLE_CHOICE' || q.question_type === 'MULTI_SELECT') && q.options.length > 2 && (
                          <IconButton
                            size="small"
                            color="error"
                            onClick={() => handleRemoveOption(qIndex, oIndex)}
                          >
                            <DeleteIcon fontSize="small" />
                          </IconButton>
                        )}
                      </div>
                    ))}
                  </div>

                  {(q.question_type === 'MULTIPLE_CHOICE' || q.question_type === 'MULTI_SELECT') && q.options.length < 6 && (
                    <Button
                      size="small"
                      startIcon={<AddIcon />}
                      onClick={() => handleAddOption(qIndex)}
                      sx={{ textTransform: 'none', ml: 4, mt: 0.5, fontSize: '0.75rem' }}
                    >
                      Add Option Choice
                    </Button>
                  )}
                </div>
              </Paper>
            ))
          )}
        </Box>
      </DialogContent>
    </Dialog>
  );
}
