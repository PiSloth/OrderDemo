import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  TextField,
  MenuItem,
  Stack,
  Divider,
  Paper,
  IconButton,
  Radio,
  Checkbox,
  Chip,
  Alert,
  Tooltip,
  SpeedDial,
  SpeedDialIcon,
  SpeedDialAction,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Badge
} from '@mui/material';

import {
  Quiz as QuizIcon,
  Add as AddIcon,
  Delete as DeleteIcon,
  Save as SaveIcon,
  ArrowBack as ArrowBackIcon,
  CheckCircle as CheckCircleIcon,
  ArrowUpward as ArrowUpwardIcon,
  ArrowDownward as ArrowDownwardIcon,
  RadioButtonChecked as RadioButtonCheckedIcon,
  CheckBox as CheckBoxIcon,
  ToggleOn as ToggleOnIcon,
  Article as ArticleIcon,
  Public as PublicIcon,
  School as SchoolIcon,
  LibraryBooks as LibraryBooksIcon,
  OpenInNew as OpenInNewIcon,
  ExpandMore as ExpandMoreIcon,
  AutoAwesome as AutoAwesomeIcon
} from '@mui/icons-material';

const dropdownMenuProps = {
  autoFocus: false,
  disableAutoFocusItem: true,
  anchorOrigin: { vertical: 'bottom', horizontal: 'left' },
  transformOrigin: { vertical: 'top', horizontal: 'left' },
  PaperProps: {
    sx: {
      maxHeight: 280,
      mt: 0.5,
      boxShadow: '0 10px 25px -5px rgba(0,0,0,0.2)',
    },
  },
};

export default function TestBuilder({
  training = {},
  test = {},
  globalQuestions = [],
  can = {}
}) {
  const linkedDocs = training.company_documents || training.companyDocuments || [];

  const { data, setData, put, processing, errors } = useForm({
    title: test.title || `${training.title} Assessment`,
    description: test.description || `Evaluation test for ${training.title}`,
    passing_score: test.passing_score ?? training.passing_score ?? 80,
    attempt_limit: test.attempt_limit ?? 3,
    status: test.status || 'active',
    questions: (test.questions || []).map((q, idx) => ({
      _uid: q.id ? `q-${q.id}` : `q-init-${idx}-${Date.now()}`,
      id: q.id || null,
      scope: q.scope || (q.company_document_id ? 'document' : 'catalog'),
      company_document_id: q.company_document_id || null,
      document_section_reference: q.document_section_reference || '',
      document_title: q.company_document?.title || null,
      question: q.question,
      question_type: q.question_type,
      marks: q.marks ?? 1.0,
      options: (q.options || []).map((opt) => ({
        id: opt.id || null,
        answer: opt.answer,
        is_correct: Boolean(opt.is_correct),
      })),
    })),
  });

  const [globalModalOpen, setGlobalModalOpen] = useState(false);
  const [docsExpanded, setDocsExpanded] = useState(true);

  // Check if question is already in test
  const isQuestionInTest = (questionId) => {
    return Boolean(questionId && data.questions.some((q) => q.id === questionId));
  };

  // Attach a question from document or global pool
  const handleAttachExternalQuestion = (extQ, scope = 'document', docTitle = null) => {
    if (isQuestionInTest(extQ.id)) return;

    setData('questions', [
      ...data.questions,
      {
        _uid: `q-ext-${extQ.id}-${Date.now()}`,
        id: extQ.id,
        scope: scope,
        company_document_id: extQ.company_document_id || (scope === 'document' ? extQ.company_document_id : null),
        document_section_reference: extQ.document_section_reference || '',
        document_title: docTitle || extQ.company_document?.title || null,
        question: extQ.question,
        question_type: extQ.question_type,
        marks: Number(extQ.marks) || 1.0,
        options: (extQ.options || []).map((opt) => ({
          id: opt.id || null,
          answer: opt.answer,
          is_correct: Boolean(opt.is_correct),
        })),
      },
    ]);
  };

  // Detach an external question
  const handleDetachQuestion = (questionId) => {
    setData(
      'questions',
      data.questions.filter((q) => q.id !== questionId)
    );
  };

  // Attach all questions from a specific document
  const handleAttachAllFromDoc = (doc) => {
    const docQuestions = doc.questions || [];
    const newToAdd = [];

    docQuestions.forEach((dq) => {
      if (!isQuestionInTest(dq.id)) {
        newToAdd.push({
          _uid: `q-doc-${dq.id}-${Date.now()}`,
          id: dq.id,
          scope: 'document',
          company_document_id: doc.id,
          document_section_reference: dq.document_section_reference || '',
          document_title: doc.title,
          question: dq.question,
          question_type: dq.question_type,
          marks: Number(dq.marks) || 1.0,
          options: (dq.options || []).map((opt) => ({
            id: opt.id || null,
            answer: opt.answer,
            is_correct: Boolean(opt.is_correct),
          })),
        });
      }
    });

    if (newToAdd.length > 0) {
      setData('questions', [...data.questions, ...newToAdd]);
    }
  };

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

    setData('questions', [
      ...data.questions,
      {
        _uid: `q-new-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
        id: null,
        scope: 'catalog',
        company_document_id: null,
        document_section_reference: '',
        document_title: null,
        question: '',
        question_type: type,
        marks: 1.0,
        options: defaultOptions,
      },
    ]);

    setTimeout(() => {
      window.scrollTo({
        top: document.documentElement.scrollHeight,
        behavior: 'smooth',
      });
    }, 120);
  };

  const handleMoveQuestion = (qIndex, direction) => {
    const targetIndex = direction === 'up' ? qIndex - 1 : qIndex + 1;
    if (targetIndex < 0 || targetIndex >= data.questions.length) return;

    const updated = [...data.questions];
    const itemToMove = updated[qIndex];
    updated[qIndex] = updated[targetIndex];
    updated[targetIndex] = itemToMove;
    setData('questions', updated);
  };

  const handleRemoveQuestion = (qIndex) => {
    const updated = [...data.questions];
    updated.splice(qIndex, 1);
    setData('questions', updated);
  };

  const handleQuestionTextChange = (qIndex, value) => {
    const updated = [...data.questions];
    updated[qIndex].question = value;
    setData('questions', updated);
  };

  const handleQuestionMarksChange = (qIndex, value) => {
    const updated = [...data.questions];
    updated[qIndex].marks = Number(value);
    setData('questions', updated);
  };

  const handleQuestionTypeChange = (qIndex, newType) => {
    const updated = [...data.questions];
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
    setData('questions', updated);
  };

  const handleQuestionScopeChange = (qIndex, newScope) => {
    const updated = [...data.questions];
    updated[qIndex].scope = newScope;

    if (newScope === 'document') {
      if (!updated[qIndex].company_document_id && linkedDocs.length > 0) {
        updated[qIndex].company_document_id = linkedDocs[0].id;
        updated[qIndex].document_title = linkedDocs[0].title;
      }
    } else {
      updated[qIndex].company_document_id = null;
      updated[qIndex].document_title = null;
      updated[qIndex].document_section_reference = '';
    }

    setData('questions', updated);
  };

  const handleQuestionDocumentChange = (qIndex, docId) => {
    const updated = [...data.questions];
    const docIdNum = Number(docId);
    updated[qIndex].company_document_id = docIdNum;

    const matchedDoc = linkedDocs.find((d) => d.id === docIdNum);
    updated[qIndex].document_title = matchedDoc ? matchedDoc.title : null;

    setData('questions', updated);
  };

  const handleQuestionSectionRefChange = (qIndex, value) => {
    const updated = [...data.questions];
    updated[qIndex].document_section_reference = value;
    setData('questions', updated);
  };

  const handleOptionAnswerChange = (qIndex, oIndex, value) => {
    const updated = [...data.questions];
    updated[qIndex].options[oIndex].answer = value;
    setData('questions', updated);
  };

  const handleToggleCorrectOption = (qIndex, oIndex) => {
    const updated = [...data.questions];
    const q = updated[qIndex];
    if (q.question_type === 'MULTI_SELECT') {
      q.options[oIndex].is_correct = !q.options[oIndex].is_correct;
    } else {
      q.options = q.options.map((opt, idx) => ({
        ...opt,
        is_correct: idx === oIndex,
      }));
    }
    setData('questions', updated);
  };

  const handleAddOption = (qIndex) => {
    const updated = [...data.questions];
    const letter = String.fromCharCode(65 + updated[qIndex].options.length);
    updated[qIndex].options.push({
      id: null,
      answer: `Option ${letter}`,
      is_correct: false,
    });
    setData('questions', updated);
  };

  const handleRemoveOption = (qIndex, oIndex) => {
    const updated = [...data.questions];
    if (updated[qIndex].options.length <= 2) return;
    updated[qIndex].options.splice(oIndex, 1);
    if (!updated[qIndex].options.some((o) => o.is_correct)) {
      updated[qIndex].options[0].is_correct = true;
    }
    setData('questions', updated);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    put(`/training/tests/${test.id}/save-builder`);
  };

  // Count available document questions
  const totalDocQuestionsAvailable = linkedDocs.reduce(
    (sum, d) => sum + (d.questions?.length || 0),
    0
  );

  return (
    <AsideLayout title={`Test Builder: ${training.title}`}>
      <Head title={`Test Builder - ${training.title}`} />

      <Box className="max-w-5xl mx-auto space-y-6">
        {/* Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <QuizIcon className="text-sky-600" />
              Test & Assessment Builder
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Configure knowledge-check assessments for <b>{training.title}</b> ({training.code}).
            </Typography>
          </div>

          <Button
            variant="outlined"
            startIcon={<ArrowBackIcon />}
            component={Link}
            href="/training/trainings"
            sx={{ textTransform: 'none', borderRadius: 2 }}
          >
            Back to Catalog
          </Button>
        </Box>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Test Metadata Card */}
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
            <CardContent className="p-6 space-y-4">
              <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100">
                Assessment Configuration
              </Typography>

              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div className="sm:col-span-2">
                  <TextField
                    required
                    fullWidth
                    label="Test Title"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    error={Boolean(errors.title)}
                  />
                </div>

                <div>
                  <TextField
                    type="number"
                    required
                    fullWidth
                    label="Passing Score (%)"
                    value={data.passing_score}
                    onChange={(e) => setData('passing_score', Number(e.target.value))}
                  />
                </div>

                <div>
                  <TextField
                    type="number"
                    required
                    fullWidth
                    label="Attempt Limit"
                    value={data.attempt_limit}
                    onChange={(e) => setData('attempt_limit', Number(e.target.value))}
                  />
                </div>

                <div className="sm:col-span-3">
                  <TextField
                    fullWidth
                    multiline
                    rows={2}
                    label="Test Instructions / Description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                  />
                </div>

                <div>
                  <TextField
                    select
                    fullWidth
                    label="Status"
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value)}
                    SelectProps={{ MenuProps: dropdownMenuProps }}
                  >
                    <MenuItem value="active">Active</MenuItem>
                    <MenuItem value="draft">Draft</MenuItem>
                    <MenuItem value="inactive">Inactive</MenuItem>
                  </TextField>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Section 1: Referenced Documents & Shared Question Bank */}
          {linkedDocs.length > 0 && (
            <Card elevation={0} className="border border-sky-200 dark:border-sky-900/60 rounded-2xl shadow-sm bg-sky-50/40 dark:bg-sky-950/20">
              <CardContent className="p-5 space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <ArticleIcon className="text-sky-600" />
                    <div>
                      <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        Referenced Documents Question Bank
                        <Badge
                          badgeContent={totalDocQuestionsAvailable}
                          color="primary"
                          sx={{ '& .MuiBadge-badge': { fontSize: '0.7rem', height: 18, minWidth: 18 } }}
                        />
                      </Typography>
                      <Typography variant="caption" className="text-slate-500">
                        This training catalog references {linkedDocs.length} document(s). Questions created for these documents can be included with auto-updating synchronization.
                      </Typography>
                    </div>
                  </div>

                  <Button
                    size="small"
                    variant="text"
                    onClick={() => setDocsExpanded((prev) => !prev)}
                    endIcon={<ExpandMoreIcon sx={{ transform: docsExpanded ? 'rotate(180deg)' : 'rotate(0deg)', transition: '0.2s' }} />}
                    sx={{ textTransform: 'none', fontWeight: 600 }}
                  >
                    {docsExpanded ? 'Hide Documents' : 'Browse Documents'}
                  </Button>
                </div>

                {docsExpanded && (
                  <div className="space-y-3 pt-2">
                    {linkedDocs.map((doc) => {
                      const docQuestions = doc.questions || [];
                      const includedCount = docQuestions.filter((dq) => isQuestionInTest(dq.id)).length;

                      return (
                        <Paper
                          key={doc.id}
                          elevation={0}
                          className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-3"
                        >
                          <div className="flex items-center justify-between gap-3">
                            <div>
                              <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <ArticleIcon fontSize="small" className="text-sky-600" />
                                {doc.title}
                                <Chip
                                  label={`${docQuestions.length} Questions in Bank`}
                                  size="small"
                                  variant="outlined"
                                  color="info"
                                  sx={{ fontSize: '0.68rem', height: 20 }}
                                />
                              </Typography>
                              <Typography variant="caption" className="text-slate-500">
                                {includedCount} of {docQuestions.length} included in this test assessment.
                              </Typography>
                            </div>

                            <Stack direction="row" spacing={1}>
                              {docQuestions.length > 0 && includedCount < docQuestions.length && (
                                <Button
                                  size="small"
                                  variant="outlined"
                                  color="primary"
                                  startIcon={<AddIcon />}
                                  onClick={() => handleAttachAllFromDoc(doc)}
                                  sx={{ textTransform: 'none', fontSize: '0.75rem', fontWeight: 600 }}
                                >
                                  Include All ({docQuestions.length})
                                </Button>
                              )}

                              <Button
                                size="small"
                                variant="text"
                                color="info"
                                endIcon={<OpenInNewIcon sx={{ fontSize: 14 }} />}
                                component="a"
                                href={`/document/library?doc=${doc.id}`}
                                target="_blank"
                                rel="noreferrer"
                                sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                              >
                                Edit Questions in Studio
                              </Button>
                            </Stack>
                          </div>

                          {/* Preview of Questions in Document */}
                          {docQuestions.length > 0 ? (
                            <div className="space-y-1.5 pl-6 border-l-2 border-slate-100 dark:border-slate-800">
                              {docQuestions.map((dq) => {
                                const inTest = isQuestionInTest(dq.id);
                                return (
                                  <div
                                    key={dq.id}
                                    className="flex items-center justify-between text-xs py-1 px-2 rounded hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                  >
                                    <div className="flex items-center gap-2 flex-1 mr-2">
                                      <Chip
                                        label={dq.question_type === 'MULTI_SELECT' ? 'Multi' : dq.question_type === 'TRUE_FALSE' ? 'T/F' : 'Single'}
                                        size="small"
                                        sx={{ fontSize: '0.62rem', height: 18 }}
                                      />
                                      <span className="font-medium text-slate-800 dark:text-slate-200 line-clamp-1">
                                        {dq.question}
                                      </span>
                                    </div>

                                    {inTest ? (
                                      <Chip
                                        size="small"
                                        color="success"
                                        variant="outlined"
                                        icon={<CheckCircleIcon sx={{ fontSize: '13px !important' }} />}
                                        label="Included"
                                        onDelete={() => handleDetachQuestion(dq.id)}
                                        sx={{ height: 22, fontSize: '0.68rem', fontWeight: 600 }}
                                      />
                                    ) : (
                                      <Button
                                        size="small"
                                        variant="text"
                                        onClick={() => handleAttachExternalQuestion(dq, 'document', doc.title)}
                                        sx={{ textTransform: 'none', fontSize: '0.7rem', py: 0 }}
                                      >
                                        + Add to Test
                                      </Button>
                                    )}
                                  </div>
                                );
                              })}
                            </div>
                          ) : (
                            <Typography variant="caption" className="text-slate-400 italic block pl-6">
                              No questions built for this document yet. Click &quot;Edit Questions in Studio&quot; to build them.
                            </Typography>
                          )}
                        </Paper>
                      );
                    })}
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Section 2: Questions in Test */}
          <div className="space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                  Test Questions ({data.questions.length})
                </Typography>
                <Typography variant="caption" className="text-slate-500">
                  Total Marks:{' '}
                  <b>
                    {data.questions.reduce((sum, q) => sum + (Number(q.marks) || 0), 0)} pts
                  </b>
                  {' • '}
                  {data.questions.filter((q) => q.scope === 'document').length} document-linked,{' '}
                  {data.questions.filter((q) => q.scope === 'catalog').length} catalog-specific,{' '}
                  {data.questions.filter((q) => q.scope === 'global').length} global
                </Typography>
              </div>

              <Stack direction="row" spacing={1} flexWrap="wrap">
                {globalQuestions.length > 0 && (
                  <Button
                    size="small"
                    variant="outlined"
                    color="secondary"
                    startIcon={<PublicIcon />}
                    onClick={() => setGlobalModalOpen(true)}
                    sx={{ textTransform: 'none', fontWeight: 600 }}
                  >
                    Global Bank ({globalQuestions.length})
                  </Button>
                )}

                <Button
                  size="small"
                  variant="outlined"
                  startIcon={<AddIcon />}
                  onClick={() => handleAddQuestion('MULTIPLE_CHOICE')}
                  sx={{ textTransform: 'none', fontWeight: 600 }}
                >
                  + Multiple Choice
                </Button>
                <Button
                  size="small"
                  variant="outlined"
                  color="info"
                  startIcon={<AddIcon />}
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
                  onClick={() => handleAddQuestion('TRUE_FALSE')}
                  sx={{ textTransform: 'none', fontWeight: 600 }}
                >
                  + True / False
                </Button>
              </Stack>
            </div>

            {/* Questions List */}
            {data.questions.map((q, qIndex) => (
              <Card
                key={q._uid || q.id || qIndex}
                elevation={0}
                className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900"
              >
                <CardContent className="p-5 space-y-4">
                  <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-bold text-xs flex items-center justify-center">
                        {qIndex + 1}
                      </span>

                      {/* Question Scope Badge */}
                      {q.scope === 'document' ? (
                        <Chip
                          icon={<ArticleIcon sx={{ fontSize: '14px !important' }} />}
                          label={q.document_title ? `📄 ${q.document_title}` : '📄 Document-Owned'}
                          size="small"
                          color="info"
                          variant="outlined"
                          sx={{ fontWeight: 700, fontSize: '0.7rem' }}
                        />
                      ) : q.scope === 'global' ? (
                        <Chip
                          icon={<PublicIcon sx={{ fontSize: '14px !important' }} />}
                          label="🌐 Global Question"
                          size="small"
                          color="secondary"
                          variant="outlined"
                          sx={{ fontWeight: 700, fontSize: '0.7rem' }}
                        />
                      ) : (
                        <Chip
                          icon={<SchoolIcon sx={{ fontSize: '14px !important' }} />}
                          label="🎓 Catalog Specific"
                          size="small"
                          variant="outlined"
                          sx={{ fontWeight: 600, fontSize: '0.7rem' }}
                        />
                      )}

                      <Chip
                        label={
                          q.question_type === 'MULTI_SELECT'
                            ? 'Multi-Select (Checkboxes)'
                            : q.question_type === 'TRUE_FALSE'
                            ? 'True / False'
                            : 'Multiple Choice'
                        }
                        size="small"
                        color={
                          q.question_type === 'MULTI_SELECT'
                            ? 'info'
                            : q.question_type === 'TRUE_FALSE'
                            ? 'secondary'
                            : 'primary'
                        }
                        sx={{ fontWeight: 700, fontSize: '0.7rem' }}
                      />

                      {/* Question Sequence Up / Down Controls */}
                      <Stack direction="row" spacing={0.5} className="ml-1">
                        <Tooltip title={qIndex === 0 ? "First question (cannot move up)" : "Move question up"}>
                          <span>
                            <IconButton
                              size="small"
                              disabled={qIndex === 0}
                              onClick={() => handleMoveQuestion(qIndex, 'up')}
                              aria-label="Move question up"
                              sx={{
                                p: 0.5,
                                border: '1px solid',
                                borderColor: 'divider',
                                borderRadius: 1.5,
                                color: qIndex === 0 ? 'text.disabled' : 'text.secondary',
                                '&:hover': { bgcolor: 'action.hover', color: 'primary.main' },
                              }}
                            >
                              <ArrowUpwardIcon sx={{ fontSize: 16 }} />
                            </IconButton>
                          </span>
                        </Tooltip>

                        <Tooltip title={qIndex === data.questions.length - 1 ? "Last question (cannot move down)" : "Move question down"}>
                          <span>
                            <IconButton
                              size="small"
                              disabled={qIndex === data.questions.length - 1}
                              onClick={() => handleMoveQuestion(qIndex, 'down')}
                              aria-label="Move question down"
                              sx={{
                                p: 0.5,
                                border: '1px solid',
                                borderColor: 'divider',
                                borderRadius: 1.5,
                                color: qIndex === data.questions.length - 1 ? 'text.disabled' : 'text.secondary',
                                '&:hover': { bgcolor: 'action.hover', color: 'primary.main' },
                              }}
                            >
                              <ArrowDownwardIcon sx={{ fontSize: 16 }} />
                            </IconButton>
                          </span>
                        </Tooltip>
                      </Stack>
                    </div>

                    <Stack direction="row" spacing={1.5} alignItems="center">
                      <TextField
                        size="small"
                        type="number"
                        label="Marks"
                        value={q.marks}
                        onChange={(e) => handleQuestionMarksChange(qIndex, e.target.value)}
                        sx={{ width: 90 }}
                      />

                      <TextField
                        select
                        size="small"
                        label="Type"
                        value={q.question_type}
                        onChange={(e) => handleQuestionTypeChange(qIndex, e.target.value)}
                        SelectProps={{ MenuProps: dropdownMenuProps }}
                        sx={{ width: 170 }}
                      >
                        <MenuItem value="MULTIPLE_CHOICE">Multiple Choice (Single)</MenuItem>
                        <MenuItem value="MULTI_SELECT">Multi-Select (Multiple)</MenuItem>
                        <MenuItem value="TRUE_FALSE">True / False</MenuItem>
                      </TextField>

                      <IconButton
                        size="small"
                        color="error"
                        onClick={() => handleRemoveQuestion(qIndex)}
                      >
                        <DeleteIcon fontSize="small" />
                      </IconButton>
                    </Stack>
                  </div>

                  {/* Question Scope & Document Ownership Configuration */}
                  <Box className="flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800">
                    <Box className="w-full sm:w-56">
                      <TextField
                        select
                        fullWidth
                        size="small"
                        label="Question Scope / Ownership"
                        value={q.scope || 'catalog'}
                        onChange={(e) => handleQuestionScopeChange(qIndex, e.target.value)}
                        SelectProps={{ MenuProps: dropdownMenuProps }}
                        sx={{ bgcolor: 'background.paper', borderRadius: 1 }}
                      >
                        <MenuItem value="catalog">🎓 Catalog Specific</MenuItem>
                        <MenuItem value="document">📄 Belong to Document</MenuItem>
                        <MenuItem value="global">🌐 Global Question</MenuItem>
                      </TextField>
                    </Box>

                    {q.scope === 'document' && (
                      <>
                        <Box className="flex-1 min-w-[240px]">
                          <TextField
                            select
                            fullWidth
                            size="small"
                            label="Target Reference Document"
                            value={q.company_document_id || ''}
                            onChange={(e) => handleQuestionDocumentChange(qIndex, e.target.value)}
                            error={!q.company_document_id}
                            helperText={
                              linkedDocs.length === 0
                                ? 'No documents are linked to this training catalog.'
                                : !q.company_document_id
                                ? 'Select which document this question belongs to'
                                : ''
                            }
                            SelectProps={{ MenuProps: dropdownMenuProps }}
                            sx={{ bgcolor: 'background.paper', borderRadius: 1 }}
                          >
                            <MenuItem value="" disabled>-- Select Referenced Document --</MenuItem>
                            {linkedDocs.length === 0 ? (
                              <MenuItem value="" disabled>No documents linked to this catalog</MenuItem>
                            ) : (
                              linkedDocs.map((doc) => (
                                <MenuItem key={doc.id} value={doc.id}>
                                  {doc.title}
                                </MenuItem>
                              ))
                            )}
                          </TextField>
                        </Box>

                        <Box className="w-full sm:w-60">
                          <TextField
                            fullWidth
                            size="small"
                            label="Section Reference (Optional)"
                            placeholder="e.g. Clause 4.2, Section 1"
                            value={q.document_section_reference || ''}
                            onChange={(e) => handleQuestionSectionRefChange(qIndex, e.target.value)}
                            sx={{ bgcolor: 'background.paper', borderRadius: 1 }}
                          />
                        </Box>
                      </>
                    )}
                  </Box>

                  {q.scope === 'document' && (
                    <Alert severity="info" sx={{ py: 0.5, fontSize: '0.75rem', borderRadius: 2 }}>
                      This question is linked to <b>{q.document_title || 'the selected reference document'}</b>. Any updates made in the Document Studio or here will sync to this document&apos;s question bank and all referencing training courses.
                    </Alert>
                  )}

                  <TextField
                    required
                    fullWidth
                    multiline
                    rows={2}
                    label={`Question #${qIndex + 1}`}
                    placeholder="e.g. What is the first step before creating a sales quotation in Odoo?"
                    value={q.question}
                    onChange={(e) => handleQuestionTextChange(qIndex, e.target.value)}
                  />

                  <Divider />

                  {/* Options List */}
                  <div className="space-y-2">
                    <Typography variant="caption" className="font-bold text-slate-500 uppercase tracking-wider block">
                      {q.question_type === 'MULTI_SELECT'
                        ? 'Options & Correct Answers (Check all boxes that apply as correct answers)'
                        : 'Options & Correct Answer (Select the radio button for the correct answer)'}
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
                              label="Correct Answer"
                              size="small"
                              color="success"
                              icon={<CheckCircleIcon />}
                              sx={{ fontWeight: 700, fontSize: '0.7rem' }}
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
                        sx={{ textTransform: 'none', ml: 4, mt: 1, fontSize: '0.75rem' }}
                      >
                        Add Option Choice
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>
            ))}

            {data.questions.length === 0 && (
              <Alert severity="warning" className="rounded-2xl py-6">
                No questions added to this test yet. Click <b>Multiple Choice</b>, <b>Multi-Select</b>, or <b>True / False</b> above, or include questions from the referenced documents above.
              </Alert>
            )}
          </div>

          {/* Bottom Save Bar */}
          <Box className="flex items-center justify-between p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sticky bottom-4 shadow-lg">
            <Typography variant="body2" className="text-slate-500">
              Make sure each question has at least one marked correct answer.
            </Typography>

            <Button
              type="submit"
              variant="contained"
              size="large"
              disabled={processing || data.questions.length === 0}
              startIcon={<SaveIcon />}
              sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 2, px: 4 }}
            >
              {processing ? 'Saving...' : 'Save Test Questions'}
            </Button>
          </Box>
        </form>
      </Box>

      {/* Global Questions Bank Modal */}
      <Dialog
        open={globalModalOpen}
        onClose={() => setGlobalModalOpen(false)}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle className="flex items-center gap-2">
          <PublicIcon className="text-secondary" />
          Import from Global Questions Pool
        </DialogTitle>
        <DialogContent dividers className="space-y-3">
          <Typography variant="body2" className="text-slate-500">
            Global questions are company-wide questions (general compliance, safety, IT security) available across all training courses.
          </Typography>

          {globalQuestions.length === 0 ? (
            <Typography variant="body2" className="text-slate-400 py-6 text-center italic">
              No global questions available in pool.
            </Typography>
          ) : (
            <div className="space-y-2">
              {globalQuestions.map((gq) => {
                const inTest = isQuestionInTest(gq.id);
                return (
                  <Paper
                    key={gq.id}
                    elevation={0}
                    className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3"
                  >
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <Chip
                          label={gq.question_type}
                          size="small"
                          sx={{ fontSize: '0.65rem', height: 18 }}
                        />
                        <span className="text-xs text-slate-500">{gq.marks} pts</span>
                      </div>
                      <Typography variant="subtitle2" className="font-semibold text-slate-900 dark:text-slate-100">
                        {gq.question}
                      </Typography>
                    </div>

                    {inTest ? (
                      <Chip
                        label="Already Added"
                        size="small"
                        color="success"
                        variant="outlined"
                        sx={{ fontSize: '0.7rem' }}
                      />
                    ) : (
                      <Button
                        size="small"
                        variant="contained"
                        onClick={() => {
                          handleAttachExternalQuestion(gq, 'global');
                        }}
                        sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                      >
                        + Add to Test
                      </Button>
                    )}
                  </Paper>
                );
              })}
            </div>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setGlobalModalOpen(false)}>Done</Button>
        </DialogActions>
      </Dialog>

      {/* Floating Action Button (SpeedDial) to add new questions from anywhere */}
      <SpeedDial
        ariaLabel="Add Question Quick Action"
        sx={{
          position: 'fixed',
          bottom: { xs: 84, sm: 88 },
          right: { xs: 20, sm: 36 },
          zIndex: 1100,
        }}
        icon={<SpeedDialIcon />}
        FabProps={{
          color: 'primary',
          sx: {
            boxShadow: '0 8px 24px -4px rgba(2, 132, 199, 0.5)',
            bgcolor: '#0284c7',
            '&:hover': {
              bgcolor: '#0369a1',
            },
          },
        }}
      >
        <SpeedDialAction
          icon={<RadioButtonCheckedIcon sx={{ color: '#0284c7' }} />}
          tooltipTitle="Multiple Choice"
          tooltipOpen
          onClick={() => handleAddQuestion('MULTIPLE_CHOICE')}
        />
        <SpeedDialAction
          icon={<CheckBoxIcon sx={{ color: '#0284c7' }} />}
          tooltipTitle="Multi-Select"
          tooltipOpen
          onClick={() => handleAddQuestion('MULTI_SELECT')}
        />
        <SpeedDialAction
          icon={<ToggleOnIcon sx={{ color: '#0d9488', fontSize: 26 }} />}
          tooltipTitle="True / False"
          tooltipOpen
          onClick={() => handleAddQuestion('TRUE_FALSE')}
        />
      </SpeedDial>
    </AsideLayout>
  );
}
