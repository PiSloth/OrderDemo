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
  RadioGroup,
  Checkbox,
  FormControlLabel,
  Chip,
  Alert
} from '@mui/material';

import {
  Quiz as QuizIcon,
  Add as AddIcon,
  Delete as DeleteIcon,
  Save as SaveIcon,
  ArrowBack as ArrowBackIcon,
  CheckCircle as CheckCircleIcon
} from '@mui/icons-material';

export default function TestBuilder({
  training = {},
  test = {}
}) {
  const { data, setData, put, processing, errors } = useForm({
    title: test.title || `${training.title} Assessment`,
    description: test.description || `Evaluation test for ${training.title}`,
    passing_score: test.passing_score ?? training.passing_score ?? 80,
    attempt_limit: test.attempt_limit ?? 3,
    status: test.status || 'active',
    questions: (test.questions || []).map((q) => ({
      id: q.id,
      question: q.question,
      question_type: q.question_type,
      marks: q.marks,
      options: (q.options || []).map((opt) => ({
        id: opt.id,
        answer: opt.answer,
        is_correct: Boolean(opt.is_correct),
      })),
    })),
  });

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
        id: null,
        question: '',
        question_type: type,
        marks: 1.0,
        options: defaultOptions,
      },
    ]);
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
              Configure Multiple Choice and True/False questions for <b>{training.title}</b> ({training.code}).
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
                  >
                    <MenuItem value="active">Active</MenuItem>
                    <MenuItem value="draft">Draft</MenuItem>
                    <MenuItem value="inactive">Inactive</MenuItem>
                  </TextField>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Questions Section */}
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100">
                  Questions ({data.questions.length})
                </Typography>
                <Typography variant="caption" className="text-slate-500">
                  Total Marks:{' '}
                  <b>
                    {data.questions.reduce((sum, q) => sum + (Number(q.marks) || 0), 0)} pts
                  </b>
                </Typography>
              </div>

              <Stack direction="row" spacing={1} flexWrap="wrap">
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
                key={qIndex}
                elevation={0}
                className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900"
              >
                <CardContent className="p-5 space-y-4">
                  <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                      <span className="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-bold text-xs flex items-center justify-center">
                        {qIndex + 1}
                      </span>
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
                No questions added yet. Click <b>+ Multiple Choice</b> or <b>+ True / False</b> above to add questions to this test.
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
    </AsideLayout>
  );
}
