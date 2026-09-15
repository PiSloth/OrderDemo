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

  const remainingCount = Math.max(0, questions.length - answeredCount);

  const isCurrentQuestionAnswered = () => {
    const q = questions[currentQuestionIndex];
    if (!q) return true;
    const ans = data.answers[q.id];
    if (q.question_type === 'MULTI_SELECT') {
      return Array.isArray(ans) && ans.length > 0;
    }
    return ans !== undefined && ans !== null && ans !== '';
  };

  const goToNextQuestion = () => {
    if (!isCurrentQuestionAnswered()) {
      setSecurityAlert('Please select an answer for this question before proceeding to the next.');
      return;
    }
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
    if (idx > currentQuestionIndex && !isCurrentQuestionAnswered()) {
      setSecurityAlert('Please select an answer for this question before moving forward.');
      return;
    }
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

      <Box className="max-w-4xl mx-auto space-y-2.5 pb-16 sm:pb-0">
        {/* Consolidated Ultra-Compact Top Bar */}
        <Box className="flex items-center justify-between gap-2 p-2 sm:p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
          <div className="flex items-center gap-1.5 min-w-0">
            <Tooltip title="Back to My Trainings">
              <IconButton
                size="small"
                component={Link}
                href="/training/my-trainings"
                sx={{ border: '1px solid', borderColor: 'divider', borderRadius: '10px', p: 0.5 }}
              >
                <ArrowBackIcon sx={{ fontSize: 16 }} />
              </IconButton>
            </Tooltip>

            <div className="truncate">
              <div className="flex items-center gap-1.5 truncate">
                <QuizIcon sx={{ fontSize: 16 }} className="text-sky-600 shrink-0" />
                <span className="font-extrabold text-[13px] sm:text-sm text-slate-900 dark:text-slate-100 truncate">
                  {test.title}
                </span>
                <span className="text-[11px] font-medium text-slate-400 hidden md:inline truncate">
                  ({training.code})
                </span>
              </div>
            </div>
          </div>

          <div className="flex items-center gap-1.5 shrink-0">
            {/* Answered & Remaining Count */}
            {questions.length > 0 && (
              <div className="flex items-center gap-1">
                <span className="text-[11px] font-extrabold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-lg flex items-center gap-1">
                  Answered: <b>{answeredCount}</b>
                </span>
                <span className="text-[11px] font-extrabold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 px-2 py-0.5 rounded-lg flex items-center gap-1">
                  Remain: <b>{remainingCount}</b>
                </span>
              </div>
            )}

            <Chip
              label={`Attempt ${attemptsUsed}/${attemptLimit}`}
              size="small"
              variant="outlined"
              color={isLimitReached ? 'error' : 'default'}
              sx={{ height: 22, fontSize: '10.5px', fontWeight: 700, display: { xs: 'none', sm: 'inline-flex' } }}
            />

            <Chip
              label={`Pass: ${test.passing_score}%`}
              size="small"
              variant="outlined"
              sx={{ height: 22, fontSize: '10.5px', fontWeight: 600, display: { xs: 'none', sm: 'inline-flex' } }}
            />

            {hasLocalDraft && (
              <Tooltip title="Clear saved draft answers">
                <IconButton size="small" onClick={handleClearDraft} sx={{ p: 0.5, color: 'text.secondary' }}>
                  <DeleteIcon sx={{ fontSize: 16 }} />
                </IconButton>
              </Tooltip>
            )}

            <Tooltip title="Copying and screen capture are restricted">
              <span className="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                <LockIcon sx={{ fontSize: 13 }} />
              </span>
            </Tooltip>

            {previousAttempts.length > 0 && (
              <Button
                size="small"
                variant={activeTab === 'history' ? 'contained' : 'outlined'}
                startIcon={<HistoryIcon sx={{ fontSize: 14 }} />}
                onClick={() => setActiveTab(activeTab === 'test' ? 'history' : 'test')}
                sx={{
                  textTransform: 'none',
                  height: 24,
                  fontSize: '11px',
                  fontWeight: 700,
                  borderRadius: '8px',
                  px: 1,
                }}
              >
                {activeTab === 'test' ? `History (${previousAttempts.length})` : 'Back to Test'}
              </Button>
            )}
          </div>
        </Box>

        {flash?.message && (
          <Alert
            severity={latestAttempt?.result === 'PASSED' ? 'success' : 'warning'}
            className="rounded-xl shadow-sm py-0.5 px-3 text-xs"
          >
            {flash.message}
          </Alert>
        )}

        {/* Tab 1: Take Test Form */}
        {activeTab === 'test' && (
          <>
            {isLimitReached && latestAttempt?.result !== 'PASSED' ? (
              <Card elevation={0} className="border border-rose-300 bg-rose-50/50 dark:bg-rose-950/20 dark:border-rose-800 rounded-2xl p-6 text-center space-y-3">
                <CancelIcon color="error" sx={{ fontSize: 44 }} />
                <Typography variant="subtitle1" className="font-extrabold text-rose-900 dark:text-rose-200">
                  Maximum Attempt Limit Reached
                </Typography>
                <Typography variant="body2" className="text-rose-700 dark:text-rose-300 max-w-lg mx-auto text-xs">
                  You have used all <b>{attemptLimit}</b> allowable attempts for this training module. Please contact your trainer if you require an additional waiver.
                </Typography>
                {previousAttempts.length > 0 && (
                  <Button
                    variant="contained"
                    color="primary"
                    size="small"
                    onClick={() => setActiveTab('history')}
                    sx={{ textTransform: 'none', borderRadius: 2, mt: 1 }}
                  >
                    View Answer Breakdown & Correct Answers
                  </Button>
                )}
              </Card>
            ) : (
              <div
                className="space-y-2 secure-assessment select-none"
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

                {/* Security Privacy Overlay */}
                {isScreenProtected && (
                  <div className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-xl p-6 text-center text-white space-y-4 animate-in fade-in duration-200">
                    <div className="w-14 h-14 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center ring-1 ring-amber-500/40">
                      <ShieldIcon sx={{ fontSize: 32 }} />
                    </div>
                    <div className="space-y-1">
                      <Typography variant="subtitle1" className="font-extrabold text-white">
                        Assessment Window Protected
                      </Typography>
                      <Typography variant="body2" className="text-slate-400 max-w-sm mx-auto text-xs">
                        Questions are concealed while window focus is lost or screen capture is active.
                      </Typography>
                    </div>
                    <Button
                      variant="contained"
                      color="primary"
                      size="small"
                      onClick={() => setIsScreenProtected(false)}
                      sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 2, px: 3 }}
                    >
                      Resume Assessment
                    </Button>
                  </div>
                )}

                {/* Form: Slide View with Left/Right Floating Icon Buttons */}
                <form onSubmit={handleSubmit} className="pt-0.5">
                  {questions.length === 0 ? (
                    <Alert severity="info" className="rounded-xl text-xs py-1">
                      No questions have been published for this test yet.
                    </Alert>
                  ) : (
                    (() => {
                      const q = questions[currentQuestionIndex] || questions[0];
                      const qIndex = currentQuestionIndex;
                      const ans = data.answers[q.id];
                      const isAnswered = q.question_type === 'MULTI_SELECT'
                        ? Array.isArray(ans) && ans.length > 0
                        : ans !== undefined && ans !== null && ans !== '';

                      return (
                        <div className="flex items-center gap-1.5 sm:gap-2.5">
                          {/* Floating Left Icon Button (Desktop / Laptop only) */}
                          <div className="hidden sm:inline-flex shrink-0">
                            <Tooltip title={currentQuestionIndex === 0 ? 'First Question' : 'Previous Question'}>
                              <span>
                                <IconButton
                                  size="small"
                                  disabled={currentQuestionIndex === 0}
                                  onClick={goToPrevQuestion}
                                  sx={{
                                    width: { xs: 32, sm: 38 },
                                    height: { xs: 32, sm: 38 },
                                    bgcolor: 'background.paper',
                                    boxShadow: 2,
                                    border: '1px solid',
                                    borderColor: 'divider',
                                    '&:hover': { bgcolor: 'action.hover' },
                                    '&.Mui-disabled': { opacity: 0.25 },
                                  }}
                                >
                                  <NavigateBeforeIcon fontSize="small" />
                                </IconButton>
                              </span>
                            </Tooltip>
                          </div>

                          {/* Slide Question Card with Brain Box & Bulb Watermark */}
                          <Card
                            key={q.id}
                            elevation={0}
                            className="flex-1 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden"
                          >
                            {/* Watermark: Brain Box with Lightbulb */}
                            <div
                              className="absolute right-3 bottom-2 pointer-events-none select-none text-sky-900/10 dark:text-sky-300/10 flex items-center justify-center z-0"
                              aria-hidden="true"
                            >
                              <svg
                                width="140"
                                height="140"
                                viewBox="0 0 100 100"
                                fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg"
                                className="w-24 h-24 sm:w-32 sm:h-32"
                              >
                                {/* Knowledge Box */}
                                <rect x="14" y="22" width="72" height="70" rx="14" fill="none" stroke="currentColor" strokeWidth="4" />
                                <path d="M14 44 H86" stroke="currentColor" strokeWidth="2.5" strokeDasharray="3 3" />
                                
                                {/* Lightbulb glowing atop box */}
                                <path d="M50 4 C43 4 38 9 38 15 C38 19 41 22 43 24 V29 H57 V24 C59 22 62 19 62 15 C62 9 57 4 50 4 Z" fill="currentColor" />
                                <rect x="44" y="30" width="12" height="2.5" rx="1" fill="currentColor" />
                                <rect x="46" y="33.5" width="8" height="2" rx="1" fill="currentColor" />
                                <line x1="50" y1="0" x2="50" y2="2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                                <line x1="33" y1="7" x2="35" y2="9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                                <line x1="67" y1="7" x2="65" y2="9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />

                                {/* Brain structure inside the box */}
                                <path
                                  d="M36 52 C32 52 30 55 30 59 C30 62 32 65 34 66 C32 68 31 71 33 74 C35 77 39 78 42 77 C43 80 47 81 50 80 C53 81 57 80 58 77 C61 78 65 77 67 74 C69 71 68 68 66 66 C68 65 70 62 70 59 C70 55 68 52 64 52 C63 48 59 46 55 47 C53 45 47 45 45 47 C41 46 37 48 36 52 Z"
                                  fill="currentColor"
                                />
                                <path d="M50 50 V78" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
                                <path d="M42 58 Q47 60 45 68" stroke="currentColor" strokeWidth="2" strokeLinecap="round" fill="none" />
                                <path d="M58 58 Q53 60 55 68" stroke="currentColor" strokeWidth="2" strokeLinecap="round" fill="none" />
                              </svg>
                            </div>

                            <CardContent className="p-3 sm:p-4 space-y-2.5 relative z-10">
                              {/* Card Header: badges */}
                              <div className="flex items-center justify-between gap-2 pb-1.5 border-b border-slate-100 dark:border-slate-800">
                                <div className="flex items-center gap-1.5">
                                  <span className="px-2 py-0.5 rounded-md bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-extrabold text-[10.5px]">
                                    Question {qIndex + 1} of {questions.length}
                                  </span>
                                  {isAnswered && (
                                    <span className="text-[10.5px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5">
                                      <CheckCircleIcon sx={{ fontSize: 12 }} /> Answered
                                    </span>
                                  )}
                                </div>

                                <Stack direction="row" spacing={0.75} alignItems="center">
                                  {q.question_type === 'MULTI_SELECT' && (
                                    <Chip
                                      label="Multi-Select"
                                      size="small"
                                      color="info"
                                      variant="outlined"
                                      sx={{ height: 20, fontWeight: 700, fontSize: '0.65rem' }}
                                    />
                                  )}
                                  <Chip
                                    label={`${q.marks} pt`}
                                    size="small"
                                    variant="outlined"
                                    sx={{ height: 20, fontWeight: 700, fontSize: '0.68rem' }}
                                  />
                                </Stack>
                              </div>

                              {/* Question Title (10 pt = ~13.3px on laptop) */}
                              <div className="space-y-0.5">
                                <Typography className="text-[13px] sm:text-[13.3px] font-bold text-slate-900 dark:text-slate-100 leading-snug">
                                  {q.question}
                                </Typography>
                                {q.question_type === 'MULTI_SELECT' && (
                                  <Typography className="text-[10.5px] text-sky-600 dark:text-sky-400 font-semibold block">
                                    (Check all correct answers)
                                  </Typography>
                                )}
                              </div>

                              {/* Options List (10 pt = ~13.3px on laptop) */}
                              {q.question_type === 'MULTI_SELECT' ? (
                                <div className="space-y-1.5">
                                  {(q.options || []).map((opt, oIndex) => {
                                    const letter = String.fromCharCode(65 + oIndex);
                                    const isSelected = Array.isArray(data.answers[q.id]) && data.answers[q.id].includes(opt.id);
                                    return (
                                      <div
                                        key={opt.id}
                                        onClick={() => handleToggleOption(q.id, opt.id)}
                                        className={`min-h-[36px] py-1 px-2.5 rounded-xl border cursor-pointer transition-all flex items-center gap-2 select-none touch-manipulation ${
                                          isSelected
                                            ? 'border-sky-500 bg-sky-50/80 dark:bg-sky-950/40 dark:border-sky-600 ring-1 ring-sky-500/30'
                                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/50'
                                        }`}
                                      >
                                        <Checkbox
                                          checked={isSelected}
                                          onChange={() => handleToggleOption(q.id, opt.id)}
                                          size="small"
                                          color="primary"
                                          sx={{ p: 0.25 }}
                                        />
                                        <span className="w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10.5px] flex items-center justify-center shrink-0">
                                          {letter}
                                        </span>
                                        <span className="text-[13px] font-medium text-slate-800 dark:text-slate-200 flex-1 leading-tight">
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
                                  className="space-y-1.5"
                                >
                                  {(q.options || []).map((opt, oIndex) => {
                                    const letter = String.fromCharCode(65 + oIndex);
                                    const isSelected = data.answers[q.id] === opt.id;
                                    return (
                                      <div
                                        key={opt.id}
                                        onClick={() => handleSelectOption(q.id, opt.id)}
                                        className={`min-h-[36px] py-1 px-2.5 rounded-xl border cursor-pointer transition-all flex items-center gap-2 select-none touch-manipulation ${
                                          isSelected
                                            ? 'border-sky-500 bg-sky-50/80 dark:bg-sky-950/40 dark:border-sky-600 ring-1 ring-sky-500/30'
                                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/50'
                                        }`}
                                      >
                                        <Radio
                                          value={opt.id}
                                          checked={isSelected}
                                          size="small"
                                          color="primary"
                                          sx={{ p: 0.25 }}
                                        />
                                        <span className="w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10.5px] flex items-center justify-center shrink-0">
                                          {letter}
                                        </span>
                                        <span className="text-[13px] font-medium text-slate-800 dark:text-slate-200 flex-1 leading-tight">
                                          {opt.answer}
                                        </span>
                                      </div>
                                    );
                                  })}
                                </RadioGroup>
                              )}

                              {/* Submit Button at end of slide card on the LAST question once answer is selected */}
                              {qIndex === questions.length - 1 && isAnswered && (
                                <div className="pt-2 flex justify-end border-t border-slate-100 dark:border-slate-800">
                                  <Button
                                    type="submit"
                                    variant="contained"
                                    color="success"
                                    size="small"
                                    disabled={processing}
                                    startIcon={processing ? <CircularProgress size={12} color="inherit" /> : <SendIcon sx={{ fontSize: 14 }} />}
                                    sx={{
                                      textTransform: 'none',
                                      fontWeight: 800,
                                      borderRadius: '8px',
                                      px: 2.5,
                                      py: 0.6,
                                      fontSize: '12px',
                                      boxShadow: 2,
                                    }}
                                  >
                                    {processing ? 'Submitting Answers...' : 'Submit Assessment'}
                                  </Button>
                                </div>
                              )}
                            </CardContent>
                          </Card>

                          {/* Floating Right Icon Button (Desktop / Laptop only) */}
                          <div className="hidden sm:inline-flex shrink-0">
                            {currentQuestionIndex < questions.length - 1 ? (
                              <Tooltip title="Next Question">
                                <IconButton
                                  size="small"
                                  color="primary"
                                  onClick={goToNextQuestion}
                                  sx={{
                                    width: { xs: 32, sm: 38 },
                                    height: { xs: 32, sm: 38 },
                                    bgcolor: 'primary.main',
                                    color: 'white',
                                    boxShadow: 2,
                                    '&:hover': { bgcolor: 'primary.dark' },
                                  }}
                                >
                                  <NavigateNextIcon fontSize="small" />
                                </IconButton>
                              </Tooltip>
                            ) : (
                              isAnswered && (
                                <Tooltip title="Submit Assessment">
                                  <IconButton
                                    size="small"
                                    type="submit"
                                    color="success"
                                    disabled={processing}
                                    sx={{
                                      width: { xs: 32, sm: 38 },
                                      height: { xs: 32, sm: 38 },
                                      bgcolor: 'success.main',
                                      color: 'white',
                                      boxShadow: 2,
                                      '&:hover': { bgcolor: 'success.dark' },
                                    }}
                                  >
                                    {processing ? <CircularProgress size={16} color="inherit" /> : <SendIcon sx={{ fontSize: 16 }} />}
                                  </IconButton>
                                </Tooltip>
                              )
                            )}
                          </div>
                        </div>
                      );
                    })()
                  )}

                  {/* Mobile Footer Swipe / Navigation Bar */}
                  {questions.length > 0 && (
                    <Box className="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-2.5 px-4 shadow-xl flex items-center justify-between gap-2">
                      <Button
                        size="small"
                        variant="outlined"
                        disabled={currentQuestionIndex === 0}
                        onClick={goToPrevQuestion}
                        startIcon={<NavigateBeforeIcon sx={{ fontSize: 18 }} />}
                        sx={{
                          textTransform: 'none',
                          fontWeight: 700,
                          borderRadius: '10px',
                          minWidth: 84,
                          fontSize: '11px',
                          py: 0.5,
                        }}
                      >
                        Prev
                      </Button>

                      <div className="text-center px-1">
                        <span className="text-xs font-black text-slate-800 dark:text-slate-200 block">
                          Q {currentQuestionIndex + 1} of {questions.length}
                        </span>
                        <span className="text-[10px] text-slate-500 font-bold">
                          {answeredCount} answered
                        </span>
                      </div>

                      {currentQuestionIndex < questions.length - 1 ? (
                        <Button
                          size="small"
                          variant="contained"
                          onClick={goToNextQuestion}
                          endIcon={<NavigateNextIcon sx={{ fontSize: 18 }} />}
                          sx={{
                            textTransform: 'none',
                            fontWeight: 700,
                            borderRadius: '10px',
                            minWidth: 84,
                            fontSize: '11px',
                            py: 0.5,
                          }}
                        >
                          Next
                        </Button>
                      ) : (
                        <Button
                          type="submit"
                          size="small"
                          variant="contained"
                          color="success"
                          disabled={processing || !isCurrentQuestionAnswered()}
                          startIcon={processing ? <CircularProgress size={12} color="inherit" /> : <SendIcon sx={{ fontSize: 13 }} />}
                          sx={{
                            textTransform: 'none',
                            fontWeight: 800,
                            borderRadius: '10px',
                            minWidth: 84,
                            fontSize: '11px',
                            py: 0.5,
                          }}
                        >
                          {processing ? 'Submitting...' : 'Submit'}
                        </Button>
                      )}
                    </Box>
                  )}
                </form>

                {/* Snackbar Security & Validation Alert */}
                <Snackbar
                  open={Boolean(securityAlert)}
                  autoHideDuration={3000}
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

