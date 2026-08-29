import React, { useState, useEffect } from 'react';
import { Head, useForm, Link, usePage } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  Radio,
  RadioGroup,
  Checkbox,
  FormControlLabel,
  Divider,
  Paper,
  Chip,
  Alert,
  Stack,
  CircularProgress,
  IconButton,
  Tooltip,
} from '@mui/material';

import {
  Quiz as QuizIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  ArrowBack as ArrowBackIcon,
  EmojiEvents as TrophyIcon,
  Replay as ReplayIcon,
  Send as SendIcon,
  Print as PrintIcon,
  Save as SaveIcon,
  Delete as DeleteIcon,
  History as HistoryIcon,
  Check as CheckIcon,
  Close as CloseIcon,
} from '@mui/icons-material';

export default function TakeTest({
  assignment = {},
  test = {},
  previousAttempts = [],
  canViewAttempts = false,
}) {
  const { flash = {}, auth = {} } = usePage().props;
  const training = assignment.training || {};
  const questions = test.questions || [];
  const attemptLimit = Number(test.attempt_limit) || 3;
  const attemptsUsed = previousAttempts.length;
  const isLimitReached = attemptsUsed >= attemptLimit;

  const latestAttempt = previousAttempts[0] || null;
  const [activeTab, setActiveTab] = useState(
    latestAttempt && latestAttempt.result === 'PASSED' ? 'history' : 'test'
  );
  const [selectedAttemptIndex, setSelectedAttemptIndex] = useState(0);
  const [hasLocalDraft, setHasLocalDraft] = useState(false);

  const activeReviewAttempt = previousAttempts[selectedAttemptIndex] || latestAttempt;

  const storageKey = `training_test_answers_u${auth.user?.id || 'me'}_a${assignment.id}_t${test.id}`;

  const { data, setData, post, processing, errors } = useForm({
    answers: {},
    training_session_id: assignment.session_participants?.[0]?.training_session_id || null,
  });

  // 1. Initialize draft answers from localStorage if available
  useEffect(() => {
    try {
      const saved = localStorage.getItem(storageKey);
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed && typeof parsed === 'object' && Object.keys(parsed).length > 0) {
          setData('answers', parsed);
          setHasLocalDraft(true);
        }
      }
    } catch (err) {
      console.warn('Could not load draft answers from localStorage', err);
    }
  }, [storageKey]);

  // Helper to persist answer updates to localStorage
  const updateAnswersAndStorage = (newAnswers) => {
    setData('answers', newAnswers);
    const hasKeys = Object.keys(newAnswers).length > 0;
    setHasLocalDraft(hasKeys);
    try {
      if (hasKeys) {
        localStorage.setItem(storageKey, JSON.stringify(newAnswers));
      } else {
        localStorage.removeItem(storageKey);
      }
    } catch (err) {
      console.warn('Could not save draft answers to localStorage', err);
    }
  };

  const handleSelectOption = (questionId, optionId) => {
    const updated = {
      ...data.answers,
      [questionId]: optionId,
    };
    updateAnswersAndStorage(updated);
  };

  const handleToggleOption = (questionId, optionId) => {
    const current = Array.isArray(data.answers[questionId])
      ? [...data.answers[questionId]]
      : [];
    const index = current.indexOf(optionId);
    if (index > -1) {
      current.splice(index, 1);
    } else {
      current.push(optionId);
    }
    const updated = {
      ...data.answers,
      [questionId]: current,
    };
    updateAnswersAndStorage(updated);
  };

  const handleClearDraft = () => {
    if (confirm('Are you sure you want to clear your saved draft answers for this assessment?')) {
      updateAnswersAndStorage({});
    }
  };

  const answeredCount = questions.filter((q) => {
    const ans = data.answers[q.id];
    if (q.question_type === 'MULTI_SELECT') {
      return Array.isArray(ans) && ans.length > 0;
    }
    return ans !== undefined && ans !== null && ans !== '';
  }).length;

  const handleSubmit = (e) => {
    e.preventDefault();
    if (answeredCount < questions.length) {
      if (!confirm(`You have answered ${answeredCount} of ${questions.length} questions. Unanswered questions will be scored 0. Are you sure you want to submit?`)) {
        return;
      }
    }

    post(`/training/assignments/${assignment.id}/tests/${test.id}/submit`, {
      onSuccess: () => {
        try {
          localStorage.removeItem(storageKey);
        } catch (e) {}
        setHasLocalDraft(false);
        setActiveTab('history');
        setSelectedAttemptIndex(0);
      },
    });
  };

  return (
    <AsideLayout title={`Assessment: ${test.title}`}>
      <Head title={`Take Test: ${test.title}`} />

      <Box className="max-w-4xl mx-auto space-y-6">
        {/* Top Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <QuizIcon className="text-sky-600" />
              {test.title}
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Module: <b>{training.title}</b> ({training.code}) • Passing Score: <b>{test.passing_score}%</b> • Attempt Limit: <b>{attemptLimit}</b>
            </Typography>
          </div>

          <Stack direction="row" spacing={1} alignItems="center">
            <Button
              variant="outlined"
              startIcon={<ArrowBackIcon />}
              component={Link}
              href="/training/my-trainings"
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              My Trainings
            </Button>
          </Stack>
        </Box>

        {flash?.message && (
          <Alert
            severity={latestAttempt?.result === 'PASSED' ? 'success' : 'warning'}
            className="rounded-2xl shadow-sm"
          >
            {flash.message}
          </Alert>
        )}

        {/* Attempt Limit / Status Banner */}
        <div className="flex flex-wrap items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 flex items-center justify-center font-bold">
              #{attemptsUsed}
            </div>
            <div>
              <div className="text-xs text-slate-400 font-bold uppercase tracking-wider">
                Attempt Quota Status
              </div>
              <div className="text-sm font-extrabold text-slate-800 dark:text-slate-200">
                {attemptsUsed} of {attemptLimit} attempts used
                {isLimitReached && (
                  <span className="text-rose-600 ml-2 font-bold">(Limit Reached)</span>
                )}
              </div>
            </div>
          </div>

          {/* Tab Switchers */}
          <div className="flex items-center gap-2">
            <Button
              variant={activeTab === 'test' ? 'contained' : 'outlined'}
              size="small"
              disabled={isLimitReached && latestAttempt?.result !== 'PASSED'}
              onClick={() => setActiveTab('test')}
              sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700 }}
            >
              Take Assessment
            </Button>
            {previousAttempts.length > 0 && (
              <Button
                variant={activeTab === 'history' ? 'contained' : 'outlined'}
                size="small"
                startIcon={<HistoryIcon />}
                onClick={() => setActiveTab('history')}
                sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700 }}
              >
                Review & Correct Answers ({previousAttempts.length})
              </Button>
            )}
          </div>
        </div>

        {/* Tab 1: Take Test Form */}
        {activeTab === 'test' && (
          <>
            {isLimitReached && latestAttempt?.result !== 'PASSED' ? (
              <Card elevation={0} className="border border-rose-300 bg-rose-50/50 dark:bg-rose-950/20 dark:border-rose-800 rounded-2xl p-6 text-center space-y-3">
                <CancelIcon color="error" sx={{ fontSize: 48 }} />
                <Typography variant="h6" className="font-extrabold text-rose-900 dark:text-rose-200">
                  Maximum Attempt Limit Reached
                </Typography>
                <Typography variant="body2" className="text-rose-700 dark:text-rose-300 max-w-lg mx-auto">
                  You have used all <b>{attemptLimit}</b> allowable attempts for this training module. Please contact your trainer or compliance administrator if you require an additional attempt waiver or a retraining session.
                </Typography>
                {previousAttempts.length > 0 && (
                  <Button
                    variant="contained"
                    color="primary"
                    onClick={() => setActiveTab('history')}
                    sx={{ textTransform: 'none', borderRadius: 2, mt: 1 }}
                  >
                    View Answer Breakdown & Correct Answers
                  </Button>
                )}
              </Card>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Local Storage Draft Notice */}
                {hasLocalDraft && (
                  <Alert
                    severity="info"
                    icon={<SaveIcon fontSize="inherit" />}
                    action={
                      <Button
                        color="inherit"
                        size="small"
                        startIcon={<DeleteIcon />}
                        onClick={handleClearDraft}
                        sx={{ textTransform: 'none', fontWeight: 600 }}
                      >
                        Clear Draft
                      </Button>
                    }
                    className="rounded-2xl"
                  >
                    <b>Auto-Saved Draft Active:</b> Your answers are stored locally in this browser. Refreshing or returning later will keep your answers intact until you submit.
                  </Alert>
                )}

                {questions.map((q, qIndex) => (
                  <Card
                    key={q.id}
                    elevation={0}
                    className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900"
                  >
                    <CardContent className="p-6 space-y-4">
                      <div className="flex items-start justify-between gap-4">
                        <div className="flex items-center gap-2">
                          <span className="w-7 h-7 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-bold text-xs flex items-center justify-center">
                            {qIndex + 1}
                          </span>
                          <div>
                            <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100">
                              {q.question}
                            </Typography>
                            {q.question_type === 'MULTI_SELECT' && (
                              <Typography variant="caption" className="text-sky-600 dark:text-sky-400 font-semibold block mt-0.5">
                                (Multiple Choice: Check all correct answers)
                              </Typography>
                            )}
                          </div>
                        </div>

                        <Stack direction="row" spacing={1} alignItems="center">
                          {q.question_type === 'MULTI_SELECT' && (
                            <Chip
                              label="Multi-Select"
                              size="small"
                              color="info"
                              variant="outlined"
                              sx={{ fontWeight: 700, fontSize: '0.7rem' }}
                            />
                          )}
                          <Chip
                            label={`${q.marks} pt${q.marks > 1 ? 's' : ''}`}
                            size="small"
                            variant="outlined"
                            sx={{ fontWeight: 600 }}
                          />
                        </Stack>
                      </div>

                      <Divider />

                      {q.question_type === 'MULTI_SELECT' ? (
                        <div className="space-y-2 pl-2">
                          {(q.options || []).map((opt, oIndex) => {
                            const letter = String.fromCharCode(65 + oIndex);
                            const isSelected = Array.isArray(data.answers[q.id]) && data.answers[q.id].includes(opt.id);
                            return (
                              <Paper
                                key={opt.id}
                                elevation={0}
                                onClick={() => handleToggleOption(q.id, opt.id)}
                                className={`p-3 rounded-xl border cursor-pointer transition-all ${
                                  isSelected
                                    ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 dark:border-sky-700 ring-1 ring-sky-500'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                                }`}
                              >
                                <FormControlLabel
                                  control={
                                    <Checkbox
                                      checked={isSelected}
                                      onChange={() => handleToggleOption(q.id, opt.id)}
                                      size="small"
                                      color="primary"
                                    />
                                  }
                                  label={
                                    <span className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                      <span className="font-bold mr-2 text-slate-400">{letter}.</span>
                                      {opt.answer}
                                    </span>
                                  }
                                  className="w-full m-0"
                                />
                              </Paper>
                            );
                          })}
                        </div>
                      ) : (
                        <RadioGroup
                          value={data.answers[q.id] || ''}
                          onChange={(e) => handleSelectOption(q.id, Number(e.target.value))}
                          className="space-y-2 pl-2"
                        >
                          {(q.options || []).map((opt, oIndex) => {
                            const letter = String.fromCharCode(65 + oIndex);
                            const isSelected = data.answers[q.id] === opt.id;
                            return (
                              <Paper
                                key={opt.id}
                                elevation={0}
                                onClick={() => handleSelectOption(q.id, opt.id)}
                                className={`p-3 rounded-xl border cursor-pointer transition-all ${
                                  isSelected
                                    ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 dark:border-sky-700 ring-1 ring-sky-500'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                                }`}
                              >
                                <FormControlLabel
                                  value={opt.id}
                                  control={<Radio size="small" color="primary" />}
                                  label={
                                    <span className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                      <span className="font-bold mr-2 text-slate-400">{letter}.</span>
                                      {opt.answer}
                                    </span>
                                  }
                                  className="w-full m-0"
                                />
                              </Paper>
                            );
                          })}
                        </RadioGroup>
                      )}
                    </CardContent>
                  </Card>
                ))}

                {questions.length === 0 && (
                  <Alert severity="info" className="rounded-2xl">
                    No questions have been published for this test yet. Please check back later.
                  </Alert>
                )}

                {questions.length > 0 && (
                  <Box className="flex items-center justify-between p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sticky bottom-4 shadow-lg">
                    <div className="space-y-0.5">
                      <Typography variant="body2" className="text-slate-700 dark:text-slate-300 font-bold">
                        Answered: <b className="text-sky-600">{answeredCount}</b> of <b>{questions.length}</b> questions
                      </Typography>
                      {hasLocalDraft && (
                        <Typography variant="caption" className="text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium">
                          <CheckCircleIcon sx={{ fontSize: 13 }} /> Answers auto-saved in browser
                        </Typography>
                      )}
                    </div>

                    <Button
                      type="submit"
                      variant="contained"
                      size="large"
                      disabled={processing}
                      startIcon={processing ? <CircularProgress size={16} color="inherit" /> : <SendIcon />}
                      sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 2, px: 4 }}
                    >
                      {processing ? 'Submitting Answers...' : 'Submit Assessment'}
                    </Button>
                  </Box>
                )}
              </form>
            )}
          </>
        )}

        {/* Tab 2: Result & Correct Answers Breakdown */}
        {activeTab === 'history' && activeReviewAttempt && (
          <div className="space-y-6">
            {/* Attempt Switcher if multiple attempts exist */}
            {previousAttempts.length > 1 && (
              <Paper elevation={0} className="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 flex-wrap">
                <Typography variant="subtitle2" className="font-bold text-slate-700 dark:text-slate-300 pl-2">
                  Select Attempt to Inspect:
                </Typography>
                <Stack direction="row" spacing={1} flexWrap="wrap">
                  {previousAttempts.map((att, idx) => (
                    <Button
                      key={att.id || idx}
                      size="small"
                      variant={selectedAttemptIndex === idx ? 'contained' : 'outlined'}
                      color={att.result === 'PASSED' ? 'success' : 'inherit'}
                      onClick={() => setSelectedAttemptIndex(idx)}
                      sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700 }}
                    >
                      Attempt #{att.attempt_number || previousAttempts.length - idx} ({att.percentage}%)
                    </Button>
                  ))}
                </Stack>
              </Paper>
            )}

            {/* Scorecard Banner */}
            <Card
              elevation={0}
              className={`border rounded-2xl p-6 ${
                activeReviewAttempt.result === 'PASSED'
                  ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-950/30 dark:border-emerald-800'
                  : 'border-rose-300 bg-rose-50 dark:bg-rose-950/30 dark:border-rose-800'
              }`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div
                    className={`w-14 h-14 rounded-2xl flex items-center justify-center ${
                      activeReviewAttempt.result === 'PASSED'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-rose-600 text-white'
                    }`}
                  >
                    {activeReviewAttempt.result === 'PASSED' ? (
                      <TrophyIcon sx={{ fontSize: 32 }} />
                    ) : (
                      <CancelIcon sx={{ fontSize: 32 }} />
                    )}
                  </div>
                  <div>
                    <Typography variant="h5" className="font-extrabold text-slate-900 dark:text-slate-100">
                      Score: {activeReviewAttempt.score} / {activeReviewAttempt.max_score} ({activeReviewAttempt.percentage}%)
                    </Typography>
                    <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                      Result:{' '}
                      <b className={activeReviewAttempt.result === 'PASSED' ? 'text-emerald-700 font-extrabold' : 'text-rose-700 font-extrabold'}>
                        {activeReviewAttempt.result}
                      </b>{' '}
                      • Attempt #{activeReviewAttempt.attempt_number} • Target: <b>{test.passing_score}%</b>
                    </Typography>
                    <Typography variant="caption" className="text-slate-500 dark:text-slate-400 block">
                      Submitted on {activeReviewAttempt.submitted_at ? new Date(activeReviewAttempt.submitted_at).toLocaleString() : 'N/A'}
                    </Typography>
                  </div>
                </div>

                <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap">
                  <Button
                    variant="outlined"
                    color="inherit"
                    component={Link}
                    href={`/training/assignments/${assignment.id}/scorecard`}
                    startIcon={<PrintIcon />}
                    sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
                  >
                    Official Scorecard
                  </Button>

                  {activeReviewAttempt.result === 'FAILED' && !isLimitReached && (
                    <Button
                      variant="contained"
                      color="primary"
                      startIcon={<ReplayIcon />}
                      onClick={() => setActiveTab('test')}
                      sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
                    >
                      Retake Assessment
                    </Button>
                  )}
                </Stack>
              </div>
            </Card>

            {/* Answer Logs / Correct Answer Question Review */}
            <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
              <CardContent className="p-6 space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100">
                      Question Review & Correct Answers
                    </Typography>
                    <Typography variant="caption" className="text-slate-500 dark:text-slate-400 block">
                      Review each question, your submitted response, and the verified correct answer.
                    </Typography>
                  </div>

                  <Stack direction="row" spacing={1}>
                    <Chip
                      icon={<CheckIcon sx={{ fontSize: '14px !important' }} />}
                      label={`${(activeReviewAttempt.answers || []).filter((a) => a.is_correct).length} Passed`}
                      size="small"
                      color="success"
                      variant="outlined"
                      sx={{ fontWeight: 700 }}
                    />
                    <Chip
                      icon={<CloseIcon sx={{ fontSize: '14px !important' }} />}
                      label={`${(activeReviewAttempt.answers || []).filter((a) => !a.is_correct).length} Failed`}
                      size="small"
                      color="error"
                      variant="outlined"
                      sx={{ fontWeight: 700 }}
                    />
                  </Stack>
                </div>

                <div className="space-y-4 pt-2">
                  {(activeReviewAttempt.answers || []).map((ans, idx) => {
                    const q = ans.question || {};
                    const allOptions = q.options || [];
                    const correctOptions = allOptions.filter((o) => o.is_correct);

                    // Candidate chosen answer string
                    const selectedAnswers =
                      ans.selected_options && ans.selected_options.length > 0
                        ? ans.selected_options.map((opt) => opt.answer).join(', ')
                        : ans.selected_option?.answer || (
                            <span className="italic text-slate-400">No answer selected</span>
                          );

                    // Correct answers formatted string
                    const correctAnswersText =
                      correctOptions.length > 0
                        ? correctOptions.map((o) => o.answer).join(', ')
                        : 'No correct answer marked';

                    return (
                      <Paper
                        key={ans.id || idx}
                        elevation={0}
                        className={`p-5 rounded-2xl border transition-all ${
                          ans.is_correct
                            ? 'border-emerald-200 bg-emerald-50/40 dark:bg-emerald-950/20 dark:border-emerald-800'
                            : 'border-rose-200 bg-rose-50/40 dark:bg-rose-950/20 dark:border-rose-800'
                        }`}
                      >
                        {/* Question Title & Points Header */}
                        <div className="flex items-start justify-between gap-3">
                          <div className="flex items-start gap-2">
                            <span
                              className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 mt-0.5 ${
                                ans.is_correct ? 'bg-emerald-600' : 'bg-rose-600'
                              }`}
                            >
                              {idx + 1}
                            </span>
                            <div>
                              <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100 leading-snug">
                                {q.question || `Question #${idx + 1}`}
                              </Typography>
                              <Typography variant="caption" className="text-slate-500 font-medium">
                                Type: {q.question_type === 'MULTI_SELECT' ? 'Multi-Select' : q.question_type === 'TRUE_FALSE' ? 'True/False' : 'Multiple Choice'}
                              </Typography>
                            </div>
                          </div>

                          <Chip
                            icon={ans.is_correct ? <CheckCircleIcon sx={{ fontSize: '14px !important' }} /> : <CancelIcon sx={{ fontSize: '14px !important' }} />}
                            label={ans.is_correct ? `+${ans.marks_obtained || q.marks || 1} pts (Correct)` : `0 / ${q.marks || 1} pts (Failed)`}
                            size="small"
                            color={ans.is_correct ? 'success' : 'error'}
                            sx={{ fontWeight: 800, fontSize: '0.75rem', px: 0.5 }}
                          />
                        </div>

                        <Divider className="my-3 opacity-60" />

                        {/* Answers comparison section */}
                        <div className="space-y-2.5 text-xs">
                          {/* Candidate Answer Box */}
                          <div
                            className={`p-3 rounded-xl border flex items-start gap-2.5 ${
                              ans.is_correct
                                ? 'bg-emerald-100/50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-950 dark:text-emerald-200'
                                : 'bg-rose-100/50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-950 dark:text-rose-200'
                            }`}
                          >
                            <span className="font-bold uppercase tracking-wider text-[11px] shrink-0">
                              Your Answer:
                            </span>
                            <span className="font-semibold">{selectedAnswers}</span>
                          </div>

                          {/* Correct Answer Display Box (Always visible so candidate knows the right answer) */}
                          <div className="p-3 rounded-xl border bg-white dark:bg-slate-800/80 border-emerald-400 dark:border-emerald-700 text-emerald-900 dark:text-emerald-300 flex items-start gap-2.5 shadow-sm">
                            <span className="font-bold uppercase tracking-wider text-[11px] text-emerald-700 dark:text-emerald-400 shrink-0 flex items-center gap-1">
                              <CheckCircleIcon sx={{ fontSize: 15 }} /> Correct Answer:
                            </span>
                            <span className="font-bold">{correctAnswersText}</span>
                          </div>

                          {/* Options Choice Breakdown List */}
                          {allOptions.length > 0 && (
                            <div className="pt-2 pl-2 space-y-1">
                              <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
                                Options Breakdown:
                              </span>
                              <div className="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pt-0.5">
                                {allOptions.map((opt, oIdx) => {
                                  const letter = String.fromCharCode(65 + oIdx);
                                  const isOptionCorrect = !!opt.is_correct;
                                  return (
                                    <div
                                      key={opt.id || oIdx}
                                      className={`p-2 rounded-lg border text-[11px] flex items-center justify-between gap-1.5 ${
                                        isOptionCorrect
                                          ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 font-bold text-emerald-900 dark:text-emerald-200'
                                          : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                                      }`}
                                    >
                                      <span>
                                        <b>{letter}.</b> {opt.answer}
                                      </span>
                                      {isOptionCorrect && (
                                        <Chip
                                          label="Correct"
                                          size="small"
                                          color="success"
                                          sx={{ height: 18, fontSize: '0.65rem', fontWeight: 800 }}
                                        />
                                      )}
                                    </div>
                                  );
                                })}
                              </div>
                            </div>
                          )}
                        </div>
                      </Paper>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          </div>
        )}
      </Box>
    </AsideLayout>
  );
}

