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
  LinearProgress,
  Snackbar,
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
  NavigateNext as NavigateNextIcon,
  NavigateBefore as NavigateBeforeIcon,
  ViewCarousel as SlideViewIcon,
  ViewList as ListViewIcon,
  Security as SecurityIcon,
  Shield as ShieldIcon,
  Lock as LockIcon,
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

  // Slide Card & Security States
  const [viewMode, setViewMode] = useState('slide'); // 'slide' | 'list'
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [isScreenProtected, setIsScreenProtected] = useState(false);
  const [securityAlert, setSecurityAlert] = useState(null);

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

  // 2. Anti-Screenshot & Screen Capture Protection
  useEffect(() => {
    if (activeTab !== 'test') return;

    const handleWindowBlur = () => {
      setIsScreenProtected(true);
    };

    const handleVisibilityChange = () => {
      if (document.hidden) {
        setIsScreenProtected(true);
      }
    };

    const handleKeyDown = (e) => {
      // PrintScreen detection: clear clipboard immediately
      if (e.key === 'PrintScreen' || e.keyCode === 44) {
        try {
          if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText('');
          }
        } catch (err) {}
        setIsScreenProtected(true);
        setSecurityAlert('Screenshots and screen capture are prohibited during this assessment.');
      }

      // Block Ctrl+P / Cmd+P (Print)
      if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
        e.preventDefault();
        setSecurityAlert('Printing the assessment is prohibited.');
      }

      // Block Ctrl+S / Cmd+S (Save)
      if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
      }

      // Block Ctrl+U (View Source)
      if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U')) {
        e.preventDefault();
      }
    };

    const handleKeyUp = (e) => {
      if (e.key === 'PrintScreen' || e.keyCode === 44) {
        try {
          if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText('');
          }
        } catch (err) {}
        setIsScreenProtected(true);
      }
    };

    window.addEventListener('blur', handleWindowBlur);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);

    return () => {
      window.removeEventListener('blur', handleWindowBlur);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('keydown', handleKeyDown);
      window.removeEventListener('keyup', handleKeyUp);
    };
  }, [activeTab]);

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

  const goToNextQuestion = () => {
    if (currentQuestionIndex < questions.length - 1) {
      setCurrentQuestionIndex((prev) => prev + 1);
    }
  };

  const goToPrevQuestion = () => {
    if (currentQuestionIndex > 0) {
      setCurrentQuestionIndex((prev) => prev - 1);
    }
  };

  const jumpToQuestion = (idx) => {
    if (idx >= 0 && idx < questions.length) {
      setCurrentQuestionIndex(idx);
    }
  };

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
              <div className="space-y-5 secure-assessment select-none"
                onContextMenu={(e) => {
                  e.preventDefault();
                  setSecurityAlert('Right-click context menu is restricted during the assessment.');
                }}
                onCopy={(e) => {
                  e.preventDefault();
                  setSecurityAlert('Copying test questions or answers is restricted.');
                }}
                onCut={(e) => e.preventDefault()}
                onDragStart={(e) => e.preventDefault()}
              >
                <style>{`
                  .secure-assessment {
                    -webkit-user-select: none !important;
                    -moz-user-select: none !important;
                    -ms-user-select: none !important;
                    user-select: none !important;
                    -webkit-touch-callout: none !important;
                  }
                  @media print {
                    body {
                      display: none !important;
                    }
                  }
                  .no-scrollbar::-webkit-scrollbar {
                    display: none;
                  }
                  .no-scrollbar {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                  }
                `}</style>

                {/* Security Overlay for Screen Capture / Blur Protection */}
                {isScreenProtected && (
                  <div className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-xl p-6 text-center text-white space-y-4 animate-in fade-in duration-200">
                    <div className="w-16 h-16 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center ring-1 ring-amber-500/40">
                      <ShieldIcon sx={{ fontSize: 38 }} />
                    </div>
                    <div className="space-y-1">
                      <Typography variant="h6" className="font-extrabold text-white">
                        Assessment Privacy Screen Active
                      </Typography>
                      <Typography variant="body2" className="text-slate-400 max-w-sm mx-auto text-xs sm:text-sm">
                        Test questions are concealed while the window is out of focus or screen capture tools are active to protect exam integrity.
                      </Typography>
                    </div>
                    <Button
                      variant="contained"
                      color="primary"
                      size="large"
                      onClick={() => setIsScreenProtected(false)}
                      sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 3, px: 4, py: 1 }}
                    >
                      Resume Assessment
                    </Button>
                  </div>
                )}

                {/* Control Bar: Mode switch, Security badge & Draft status */}
                <div className="flex flex-wrap items-center justify-between gap-3 p-3 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                  <div className="flex items-center gap-2">
                    <div className="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                      <Button
                        size="small"
                        variant={viewMode === 'slide' ? 'contained' : 'text'}
                        color={viewMode === 'slide' ? 'primary' : 'inherit'}
                        onClick={() => setViewMode('slide')}
                        startIcon={<SlideViewIcon sx={{ fontSize: 16 }} />}
                        sx={{
                          textTransform: 'none',
                          fontWeight: 700,
                          fontSize: '0.78rem',
                          borderRadius: '10px',
                          px: 1.5,
                          py: 0.5,
                          boxShadow: viewMode === 'slide' ? undefined : 'none',
                        }}
                      >
                        Slide View
                      </Button>
                      <Button
                        size="small"
                        variant={viewMode === 'list' ? 'contained' : 'text'}
                        color={viewMode === 'list' ? 'primary' : 'inherit'}
                        onClick={() => setViewMode('list')}
                        startIcon={<ListViewIcon sx={{ fontSize: 16 }} />}
                        sx={{
                          textTransform: 'none',
                          fontWeight: 700,
                          fontSize: '0.78rem',
                          borderRadius: '10px',
                          px: 1.5,
                          py: 0.5,
                          boxShadow: viewMode === 'list' ? undefined : 'none',
                        }}
                      >
                        List View
                      </Button>
                    </div>

                    <Tooltip title="Screenshots and text copy are restricted for test integrity">
                      <Chip
                        icon={<LockIcon sx={{ fontSize: 14 }} />}
                        label="Secure"
                        size="small"
                        variant="outlined"
                        color="default"
                        sx={{ fontWeight: 700, fontSize: '0.7rem', display: { xs: 'none', sm: 'inline-flex' } }}
                      />
                    </Tooltip>
                  </div>

                  <div className="flex items-center gap-2">
                    {hasLocalDraft && (
                      <Button
                        color="inherit"
                        size="small"
                        startIcon={<DeleteIcon sx={{ fontSize: 16 }} />}
                        onClick={handleClearDraft}
                        sx={{ textTransform: 'none', fontSize: '0.75rem', fontWeight: 600, color: 'text.secondary' }}
                      >
                        Clear Draft
                      </Button>
                    )}

                    <span className="text-xs font-bold text-slate-600 dark:text-slate-300">
                      Answered: <b className="text-sky-600 dark:text-sky-400">{answeredCount}</b> / {questions.length}
                    </span>
                  </div>
                </div>

                {/* Progress Bar */}
                {questions.length > 0 && (
                  <div className="space-y-1.5 px-1">
                    <div className="flex items-center justify-between text-xs text-slate-500 font-semibold">
                      <span>Overall Progress</span>
                      <span>{Math.round((answeredCount / (questions.length || 1)) * 100)}%</span>
                    </div>
                    <LinearProgress
                      variant="determinate"
                      value={questions.length ? (answeredCount / questions.length) * 100 : 0}
                      sx={{
                        height: 7,
                        borderRadius: 4,
                        bgcolor: 'slate.200',
                        '& .MuiLinearProgress-bar': {
                          borderRadius: 4,
                          background: 'linear-gradient(90deg, #0284c7 0%, #06b6d4 100%)',
                        },
                      }}
                    />
                  </div>
                )}

                {/* Question Jump Strip (Horizontal Scroll for Mobile & Desktop) */}
                {questions.length > 1 && (
                  <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-2 sm:p-2.5 shadow-sm">
                    <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar scroll-smooth py-1 px-0.5">
                      {questions.map((q, idx) => {
                        const ans = data.answers[q.id];
                        const isAnswered = q.question_type === 'MULTI_SELECT'
                          ? Array.isArray(ans) && ans.length > 0
                          : ans !== undefined && ans !== null && ans !== '';
                        const isCurrent = viewMode === 'slide' && idx === currentQuestionIndex;

                        return (
                          <button
                            key={q.id}
                            type="button"
                            onClick={() => {
                              jumpToQuestion(idx);
                              if (viewMode === 'list') {
                                const el = document.getElementById(`q-card-${q.id}`);
                                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                              }
                            }}
                            className={`shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center relative ${
                              isCurrent
                                ? 'bg-sky-600 text-white shadow-md ring-2 ring-sky-400 ring-offset-2 dark:ring-offset-slate-900'
                                : isAnswered
                                ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-500/25'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'
                            }`}
                          >
                            {idx + 1}
                            {isAnswered && !isCurrent && (
                              <span className="absolute -top-1 -right-1 w-3 h-3 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900 flex items-center justify-center">
                                <span className="text-[7px] text-white">✓</span>
                              </span>
                            )}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                )}

                {/* Form Body: Slide View or List View */}
                <form onSubmit={handleSubmit} className="space-y-6">
                  {questions.length === 0 ? (
                    <Alert severity="info" className="rounded-2xl">
                      No questions have been published for this test yet. Please check back later.
                    </Alert>
                  ) : viewMode === 'slide' ? (
                    /* SLIDE VIEW: One Question Card at a time */
                    (() => {
                      const q = questions[currentQuestionIndex] || questions[0];
                      const qIndex = currentQuestionIndex;
                      const ans = data.answers[q.id];
                      const isAnswered = q.question_type === 'MULTI_SELECT'
                        ? Array.isArray(ans) && ans.length > 0
                        : ans !== undefined && ans !== null && ans !== '';

                      return (
                        <Card
                          key={q.id}
                          elevation={0}
                          className="border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm bg-white dark:bg-slate-900 transition-all"
                        >
                          <CardContent className="p-5 sm:p-8 space-y-6">
                            {/* Card Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                              <div className="flex items-center gap-2">
                                <span className="px-3 py-1 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-extrabold text-xs tracking-wide">
                                  Question {qIndex + 1} of {questions.length}
                                </span>
                                {isAnswered && (
                                  <span className="text-xs text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                    <CheckCircleIcon sx={{ fontSize: 14 }} /> Answered
                                  </span>
                                )}
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
                                  sx={{ fontWeight: 700 }}
                                />
                              </Stack>
                            </div>

                            {/* Question Title */}
                            <div className="space-y-1">
                              <Typography variant="h6" className="font-extrabold text-slate-900 dark:text-slate-100 text-base sm:text-lg leading-relaxed">
                                {q.question}
                              </Typography>
                              {q.question_type === 'MULTI_SELECT' && (
                                <Typography variant="caption" className="text-sky-600 dark:text-sky-400 font-semibold block">
                                  Select all options that apply.
                                </Typography>
                              )}
                            </div>

                            {/* Options List */}
                            {q.question_type === 'MULTI_SELECT' ? (
                              <div className="space-y-3">
                                {(q.options || []).map((opt, oIndex) => {
                                  const letter = String.fromCharCode(65 + oIndex);
                                  const isSelected = Array.isArray(data.answers[q.id]) && data.answers[q.id].includes(opt.id);
                                  return (
                                    <div
                                      key={opt.id}
                                      onClick={() => handleToggleOption(q.id, opt.id)}
                                      className={`min-h-[52px] p-3.5 sm:p-4 rounded-2xl border-2 cursor-pointer transition-all flex items-center gap-3 select-none touch-manipulation ${
                                        isSelected
                                          ? 'border-sky-500 bg-sky-50/80 dark:bg-sky-950/40 dark:border-sky-600 ring-2 ring-sky-500/20 shadow-sm'
                                          : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/50'
                                      }`}
                                    >
                                      <Checkbox
                                        checked={isSelected}
                                        onChange={() => handleToggleOption(q.id, opt.id)}
                                        size="medium"
                                        color="primary"
                                        sx={{ p: 0.5 }}
                                      />
                                      <span className="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs flex items-center justify-center shrink-0">
                                        {letter}
                                      </span>
                                      <span className="text-sm sm:text-base font-semibold text-slate-800 dark:text-slate-200 flex-1">
                                        {opt.answer}
                                      </span>
                                    </div>
                                  );
                                })}
                              </div>
                            ) : (
                              <RadioGroup
                                value={data.answers[q.id] || ''}
                                onChange={(e) => handleSelectOption(q.id, Number(e.target.value))}
                                className="space-y-3"
                              >
                                {(q.options || []).map((opt, oIndex) => {
                                  const letter = String.fromCharCode(65 + oIndex);
                                  const isSelected = data.answers[q.id] === opt.id;
                                  return (
                                    <div
                                      key={opt.id}
                                      onClick={() => handleSelectOption(q.id, opt.id)}
                                      className={`min-h-[52px] p-3.5 sm:p-4 rounded-2xl border-2 cursor-pointer transition-all flex items-center gap-3 select-none touch-manipulation ${
                                        isSelected
                                          ? 'border-sky-500 bg-sky-50/80 dark:bg-sky-950/40 dark:border-sky-600 ring-2 ring-sky-500/20 shadow-sm'
                                          : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/50'
                                      }`}
                                    >
                                      <Radio
                                        value={opt.id}
                                        checked={isSelected}
                                        size="medium"
                                        color="primary"
                                        sx={{ p: 0.5 }}
                                      />
                                      <span className="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs flex items-center justify-center shrink-0">
                                        {letter}
                                      </span>
                                      <span className="text-sm sm:text-base font-semibold text-slate-800 dark:text-slate-200 flex-1">
                                        {opt.answer}
                                      </span>
                                    </div>
                                  );
                                })}
                              </RadioGroup>
                            )}

                            {/* Desktop Prev / Next Card Footer */}
                            <div className="hidden sm:flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                              <Button
                                variant="outlined"
                                disabled={currentQuestionIndex === 0}
                                onClick={goToPrevQuestion}
                                startIcon={<NavigateBeforeIcon />}
                                sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
                              >
                                Previous Question
                              </Button>

                              {currentQuestionIndex < questions.length - 1 ? (
                                <Button
                                  variant="contained"
                                  onClick={goToNextQuestion}
                                  endIcon={<NavigateNextIcon />}
                                  sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
                                >
                                  Next Question
                                </Button>
                              ) : (
                                <Button
                                  type="submit"
                                  variant="contained"
                                  color="success"
                                  disabled={processing}
                                  startIcon={processing ? <CircularProgress size={16} color="inherit" /> : <SendIcon />}
                                  sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 2, px: 3 }}
                                >
                                  {processing ? 'Submitting...' : 'Finish & Submit'}
                                </Button>
                              )}
                            </div>
                          </CardContent>
                        </Card>
                      );
                    })()
                  ) : (
                    /* LIST VIEW: All Questions Sequentially */
                    <div className="space-y-6">
                      {questions.map((q, qIndex) => (
                        <Card
                          id={`q-card-${q.id}`}
                          key={q.id}
                          elevation={0}
                          className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900"
                        >
                          <CardContent className="p-5 sm:p-6 space-y-4">
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
                              <div className="space-y-2.5">
                                {(q.options || []).map((opt, oIndex) => {
                                  const letter = String.fromCharCode(65 + oIndex);
                                  const isSelected = Array.isArray(data.answers[q.id]) && data.answers[q.id].includes(opt.id);
                                  return (
                                    <div
                                      key={opt.id}
                                      onClick={() => handleToggleOption(q.id, opt.id)}
                                      className={`min-h-[48px] p-3 rounded-xl border cursor-pointer transition-all flex items-center gap-2.5 ${
                                        isSelected
                                          ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 dark:border-sky-700 ring-1 ring-sky-500'
                                          : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                                      }`}
                                    >
                                      <Checkbox
                                        checked={isSelected}
                                        onChange={() => handleToggleOption(q.id, opt.id)}
                                        size="small"
                                        color="primary"
                                        sx={{ p: 0.5 }}
                                      />
                                      <span className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        <span className="font-bold mr-2 text-slate-400">{letter}.</span>
                                        {opt.answer}
                                      </span>
                                    </div>
                                  );
                                })}
                              </div>
                            ) : (
                              <RadioGroup
                                value={data.answers[q.id] || ''}
                                onChange={(e) => handleSelectOption(q.id, Number(e.target.value))}
                                className="space-y-2.5"
                              >
                                {(q.options || []).map((opt, oIndex) => {
                                  const letter = String.fromCharCode(65 + oIndex);
                                  const isSelected = data.answers[q.id] === opt.id;
                                  return (
                                    <div
                                      key={opt.id}
                                      onClick={() => handleSelectOption(q.id, opt.id)}
                                      className={`min-h-[48px] p-3 rounded-xl border cursor-pointer transition-all flex items-center gap-2.5 ${
                                        isSelected
                                          ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 dark:border-sky-700 ring-1 ring-sky-500'
                                          : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                                      }`}
                                    >
                                      <Radio
                                        value={opt.id}
                                        checked={isSelected}
                                        size="small"
                                        color="primary"
                                        sx={{ p: 0.5 }}
                                      />
                                      <span className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        <span className="font-bold mr-2 text-slate-400">{letter}.</span>
                                        {opt.answer}
                                      </span>
                                    </div>
                                  );
                                })}
                              </RadioGroup>
                            )}
                          </CardContent>
                        </Card>
                      ))}
                    </div>
                  )}

                  {/* Responsive Sticky Action Bar (Mobile thumb-friendly & Desktop) */}
                  {questions.length > 0 && (
                    <Box className="sticky bottom-3 sm:bottom-4 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-xl">
                      <div className="flex items-center justify-between gap-2">
                        {viewMode === 'slide' ? (
                          <>
                            <Button
                              size="medium"
                              variant="outlined"
                              disabled={currentQuestionIndex === 0}
                              onClick={goToPrevQuestion}
                              startIcon={<NavigateBeforeIcon />}
                              sx={{
                                textTransform: 'none',
                                fontWeight: 700,
                                borderRadius: 2,
                                minWidth: { xs: 44, sm: 100 },
                                px: { xs: 1.5, sm: 2.5 },
                              }}
                            >
                              <span className="hidden sm:inline">Previous</span>
                            </Button>

                            <div className="text-center px-1">
                              <div className="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200">
                                Q {currentQuestionIndex + 1} of {questions.length}
                              </div>
                              <div className="text-[11px] text-slate-500 font-bold">
                                {answeredCount} answered
                              </div>
                            </div>

                            {currentQuestionIndex < questions.length - 1 ? (
                              <Button
                                size="medium"
                                variant="contained"
                                onClick={goToNextQuestion}
                                endIcon={<NavigateNextIcon />}
                                sx={{
                                  textTransform: 'none',
                                  fontWeight: 700,
                                  borderRadius: 2,
                                  minWidth: { xs: 44, sm: 100 },
                                  px: { xs: 1.5, sm: 2.5 },
                                }}
                              >
                                <span className="hidden sm:inline">Next</span>
                              </Button>
                            ) : (
                              <Button
                                type="submit"
                                size="medium"
                                variant="contained"
                                color="success"
                                disabled={processing}
                                startIcon={processing ? <CircularProgress size={14} color="inherit" /> : <SendIcon sx={{ fontSize: 16 }} />}
                                sx={{
                                  textTransform: 'none',
                                  fontWeight: 800,
                                  borderRadius: 2,
                                  minWidth: { xs: 44, sm: 100 },
                                  px: { xs: 2, sm: 3 },
                                }}
                              >
                                {processing ? 'Submitting...' : 'Submit'}
                              </Button>
                            )}
                          </>
                        ) : (
                          <>
                            <div className="space-y-0.5">
                              <Typography variant="body2" className="text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm">
                                Answered: <b className="text-sky-600">{answeredCount}</b> of <b>{questions.length}</b>
                              </Typography>
                              {hasLocalDraft && (
                                <Typography variant="caption" className="text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium text-[11px]">
                                  <CheckCircleIcon sx={{ fontSize: 13 }} /> Auto-saved
                                </Typography>
                              )}
                            </div>

                            <Button
                              type="submit"
                              variant="contained"
                              size="large"
                              disabled={processing}
                              startIcon={processing ? <CircularProgress size={16} color="inherit" /> : <SendIcon />}
                              sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 2, px: { xs: 2.5, sm: 4 } }}
                            >
                              {processing ? 'Submitting...' : 'Submit Assessment'}
                            </Button>
                          </>
                        )}
                      </div>
                    </Box>
                  )}
                </form>

                {/* Snackbar Security Alert */}
                <Snackbar
                  open={Boolean(securityAlert)}
                  autoHideDuration={3500}
                  onClose={() => setSecurityAlert(null)}
                  message={securityAlert}
                  anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
                />
              </div>
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

