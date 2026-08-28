import React, { useState } from 'react';
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
  FormControlLabel,
  Divider,
  Paper,
  Chip,
  Alert,
  Stack,
  CircularProgress
} from '@mui/material';

import {
  Quiz as QuizIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  ArrowBack as ArrowBackIcon,
  EmojiEvents as TrophyIcon,
  Replay as ReplayIcon,
  Send as SendIcon
} from '@mui/icons-material';

export default function TakeTest({
  assignment = {},
  test = {},
  previousAttempts = []
}) {
  const { flash = {} } = usePage().props;
  const training = assignment.training || {};
  const questions = test.questions || [];

  const latestAttempt = previousAttempts[0] || null;
  const [activeTab, setActiveTab] = useState(
    latestAttempt && latestAttempt.result === 'PASSED' ? 'history' : 'test'
  );

  const { data, setData, post, processing, errors } = useForm({
    answers: {},
    training_session_id: assignment.session_participants?.[0]?.training_session_id || null,
  });

  const handleSelectOption = (questionId, optionId) => {
    setData('answers', {
      ...data.answers,
      [questionId]: optionId,
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (Object.keys(data.answers).length < questions.length) {
      if (!confirm('You have unanswered questions. Are you sure you want to submit?')) {
        return;
      }
    }

    post(`/training/assignments/${assignment.id}/tests/${test.id}/submit`, {
      onSuccess: () => {
        setActiveTab('history');
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
              Module: <b>{training.title}</b> ({training.code}) • Passing Score: <b>{test.passing_score}%</b>
            </Typography>
          </div>

          <Button
            variant="outlined"
            startIcon={<ArrowBackIcon />}
            component={Link}
            href="/training/my-trainings"
            sx={{ textTransform: 'none', borderRadius: 2 }}
          >
            Back to My Trainings
          </Button>
        </Box>

        {flash?.message && (
          <Alert
            severity={latestAttempt?.result === 'PASSED' ? 'success' : 'warning'}
            className="rounded-2xl shadow-sm"
          >
            {flash.message}
          </Alert>
        )}

        {/* Attempt Tabs */}
        {previousAttempts.length > 0 && (
          <div className="flex items-center gap-2">
            <Button
              variant={activeTab === 'test' ? 'contained' : 'outlined'}
              size="small"
              onClick={() => setActiveTab('test')}
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              Take Assessment
            </Button>
            <Button
              variant={activeTab === 'history' ? 'contained' : 'outlined'}
              size="small"
              onClick={() => setActiveTab('history')}
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              Latest Result & Answers Breakdown
            </Button>
          </div>
        )}

        {/* Tab 1: Take Test Form */}
        {activeTab === 'test' && (
          <form onSubmit={handleSubmit} className="space-y-6">
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
                      <Typography variant="subtitle1" className="font-bold text-slate-900 dark:text-slate-100">
                        {q.question}
                      </Typography>
                    </div>

                    <Chip
                      label={`${q.marks} pt${q.marks > 1 ? 's' : ''}`}
                      size="small"
                      variant="outlined"
                      sx={{ fontWeight: 600 }}
                    />
                  </div>

                  <Divider />

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
                              ? 'border-sky-500 bg-sky-50/70 dark:bg-sky-950/40 dark:border-sky-700'
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
                <Typography variant="body2" className="text-slate-500">
                  Answered: <b>{Object.keys(data.answers).length}</b> of <b>{questions.length}</b> questions
                </Typography>

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

        {/* Tab 2: Latest Result & Preserved Answers Breakdown */}
        {activeTab === 'history' && latestAttempt && (
          <div className="space-y-6">
            {/* Scorecard Banner */}
            <Card
              elevation={0}
              className={`border rounded-2xl p-6 ${
                latestAttempt.result === 'PASSED'
                  ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-950/30 dark:border-emerald-800'
                  : 'border-rose-300 bg-rose-50 dark:bg-rose-950/30 dark:border-rose-800'
              }`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div
                    className={`w-14 h-14 rounded-2xl flex items-center justify-center ${
                      latestAttempt.result === 'PASSED'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-rose-600 text-white'
                    }`}
                  >
                    {latestAttempt.result === 'PASSED' ? (
                      <TrophyIcon sx={{ fontSize: 32 }} />
                    ) : (
                      <CancelIcon sx={{ fontSize: 32 }} />
                    )}
                  </div>
                  <div>
                    <Typography variant="h5" className="font-extrabold text-slate-900 dark:text-slate-100">
                      Score: {latestAttempt.score} / {latestAttempt.max_score} ({latestAttempt.percentage}%)
                    </Typography>
                    <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                      Result:{' '}
                      <b className={latestAttempt.result === 'PASSED' ? 'text-emerald-700' : 'text-rose-700'}>
                        {latestAttempt.result}
                      </b>{' '}
                      • Attempt #{latestAttempt.attempt_number} on{' '}
                      {latestAttempt.submitted_at
                        ? new Date(latestAttempt.submitted_at).toLocaleString()
                        : 'N/A'}
                    </Typography>
                  </div>
                </div>

                {latestAttempt.result === 'FAILED' && (
                  <Button
                    variant="contained"
                    color="primary"
                    startIcon={<ReplayIcon />}
                    onClick={() => setActiveTab('test')}
                    sx={{ textTransform: 'none', fontWeight: 700 }}
                  >
                    Retake Assessment
                  </Button>
                )}
              </div>
            </Card>

            {/* Answer Logs / Historical Evidence */}
            <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
              <CardContent className="p-6 space-y-4">
                <Typography variant="h6" className="font-bold text-slate-900 dark:text-slate-100">
                  Question Review & Preserved Answers
                </Typography>
                <Typography variant="caption" className="text-slate-400 block -mt-2">
                  Historical answers saved for compliance auditing.
                </Typography>

                <div className="space-y-4 pt-2">
                  {(latestAttempt.answers || []).map((ans, idx) => (
                    <Paper
                      key={ans.id}
                      elevation={0}
                      className={`p-4 rounded-xl border ${
                        ans.is_correct
                          ? 'border-emerald-200 bg-emerald-50/40 dark:bg-emerald-950/20'
                          : 'border-rose-200 bg-rose-50/40 dark:bg-rose-950/20'
                      }`}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-xs text-slate-400">Q{idx + 1}.</span>
                          <Typography variant="subtitle2" className="font-bold text-slate-800 dark:text-slate-200">
                            {ans.question?.question}
                          </Typography>
                        </div>
                        <Chip
                          label={ans.is_correct ? `+${ans.marks_obtained} pts` : '0 pts (Wrong)'}
                          size="small"
                          color={ans.is_correct ? 'success' : 'error'}
                          sx={{ fontWeight: 700, fontSize: '0.7rem' }}
                        />
                      </div>

                      <div className="mt-2 text-xs space-y-1">
                        <div className="text-slate-700 dark:text-slate-300">
                          Selected Answer: <b>{ans.selected_option?.answer || 'No answer selected'}</b>
                        </div>
                      </div>
                    </Paper>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        )}
      </Box>
    </AsideLayout>
  );
}
