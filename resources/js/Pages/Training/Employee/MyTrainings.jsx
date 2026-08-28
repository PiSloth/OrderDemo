import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  Chip,
  Stack,
  Divider,
  LinearProgress,
  Avatar,
  Paper,
  Tooltip
} from '@mui/material';

import {
  School as SchoolIcon,
  Schedule as ScheduleIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  Quiz as QuizIcon,
  PlayArrow as PlayIcon,
  EmojiEvents as TrophyIcon,
  HourglassEmpty as PendingIcon,
  Event as EventIcon
} from '@mui/icons-material';

export default function MyTrainings({ assignments = [] }) {
  const getStatusChip = (status) => {
    const map = {
      COMPLETED: { color: 'success', label: 'COMPLETED' },
      IN_PROGRESS: { color: 'primary', label: 'IN PROGRESS' },
      PENDING: { color: 'warning', label: 'PENDING' },
      OVERDUE: { color: 'error', label: 'OVERDUE' },
    };
    const s = map[status] || { color: 'default', label: status };
    return <Chip label={s.label} color={s.color} size="small" sx={{ fontWeight: 700, fontSize: '0.7rem' }} />;
  };

  return (
    <AsideLayout title="My Assigned Trainings">
      <Head title="My Trainings & Tests" />

      <Box className="max-w-6xl mx-auto space-y-6">
        {/* Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <SchoolIcon className="text-sky-600" />
              My Training Requirements
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Track your assigned onboarding, workflow updates, and retraining compliance milestones.
            </Typography>
          </div>
        </Box>

        {/* Assignments List */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {assignments.map((assignment) => {
            const training = assignment.training;
            const test = training?.test;
            const activeSessionParticipant = assignment.session_participants?.[0];
            const session = activeSessionParticipant?.session;
            const latestAttempt = assignment.test_attempts?.[0];

            const isAttended = assignment.session_participants?.some((p) => p.attendance_status === 'ATTENDED');
            const isTestPassed = latestAttempt?.result === 'PASSED';
            const isCompleted = assignment.status === 'COMPLETED';

            return (
              <Card
                key={assignment.id}
                elevation={0}
                className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-shadow bg-white dark:bg-slate-900 flex flex-col justify-between"
              >
                <CardContent className="p-6 space-y-4">
                  {/* Top info */}
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <span className="text-xs font-mono font-bold text-sky-600 bg-sky-50 dark:bg-sky-950 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800">
                        {training.code}
                      </span>
                      <Typography variant="h6" className="font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                        {training.title}
                      </Typography>
                    </div>
                    {getStatusChip(assignment.status)}
                  </div>

                  <Typography variant="body2" className="text-slate-500 dark:text-slate-400 line-clamp-2">
                    {training.description || 'No description provided.'}
                  </Typography>

                  {/* Trigger Reason */}
                  {assignment.trigger && (
                    <div className="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs">
                      <span className="font-bold text-slate-700 dark:text-slate-300">Trigger: </span>
                      <span className="text-slate-600 dark:text-slate-400">{assignment.trigger.reason}</span>
                    </div>
                  )}

                  <Divider />

                  {/* 3-Tier Completion Progress Checklist */}
                  <div className="space-y-2">
                    <Typography variant="caption" className="font-bold text-slate-500 uppercase tracking-wider block">
                      Requirement Completion Criteria
                    </Typography>

                    <div className="grid grid-cols-2 gap-2 text-xs">
                      {/* 1. Attendance */}
                      <Paper
                        elevation={0}
                        className={`p-2.5 rounded-xl border flex items-center gap-2 ${
                          isAttended
                            ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-200'
                            : 'border-slate-200 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                        }`}
                      >
                        {isAttended ? (
                          <CheckCircleIcon color="success" fontSize="small" />
                        ) : (
                          <PendingIcon color="action" fontSize="small" />
                        )}
                        <div>
                          <div className="font-bold">1. Attendance</div>
                          <div className="text-[10px]">
                            {isAttended ? 'Verified Attended' : 'Pending Session'}
                          </div>
                        </div>
                      </Paper>

                      {/* 2. Test Pass */}
                      <Paper
                        elevation={0}
                        className={`p-2.5 rounded-xl border flex items-center gap-2 ${
                          isTestPassed
                            ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-200'
                            : 'border-slate-200 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                        }`}
                      >
                        {isTestPassed ? (
                          <CheckCircleIcon color="success" fontSize="small" />
                        ) : latestAttempt ? (
                          <CancelIcon color="error" fontSize="small" />
                        ) : (
                          <QuizIcon color="action" fontSize="small" />
                        )}
                        <div>
                          <div className="font-bold">2. Test Result</div>
                          <div className="text-[10px]">
                            {latestAttempt
                              ? `${latestAttempt.percentage}% (${latestAttempt.result})`
                              : `Pass req: ${training.passing_score}%`}
                          </div>
                        </div>
                      </Paper>
                    </div>
                  </div>

                  {/* Scheduled Session Info */}
                  {session && (
                    <div className="text-xs text-slate-600 dark:text-slate-300 space-y-1 bg-sky-50/50 dark:bg-sky-950/30 p-3 rounded-xl border border-sky-100 dark:border-sky-900/50">
                      <div className="font-bold text-sky-800 dark:text-sky-200 flex items-center gap-1">
                        <EventIcon fontSize="inherit" />
                        Next Session: {session.title}
                      </div>
                      <div>Status: <b>{session.status}</b></div>
                      {session.scheduled_at && (
                        <div>Time: {new Date(session.scheduled_at).toLocaleString()}</div>
                      )}
                      {session.trainer && <div>Trainer: {session.trainer.name}</div>}
                    </div>
                  )}

                  {/* Due Date & Completion Date */}
                  <div className="flex items-center justify-between text-xs text-slate-500 pt-1">
                    <span>Due: <b>{assignment.due_date || 'N/A'}</b></span>
                    {assignment.completed_at && (
                      <span className="text-emerald-600 font-bold">
                        Completed: {assignment.completed_at.substring(0, 10)}
                      </span>
                    )}
                  </div>
                </CardContent>

                {/* Footer Action */}
                <Box className="p-4 pt-0 border-t border-slate-100 dark:border-slate-800 mt-2 flex items-center justify-between">
                  {isCompleted ? (
                    <div className="flex items-center gap-1.5 text-emerald-600 font-bold text-xs">
                      <TrophyIcon fontSize="small" />
                      Requirement Satisfied & Compliant
                    </div>
                  ) : (
                    <div className="text-xs text-amber-600 font-semibold">
                      Action Required
                    </div>
                  )}

                  {test ? (
                    <Button
                      variant={isCompleted ? 'outlined' : 'contained'}
                      color={isCompleted ? 'secondary' : 'primary'}
                      component={Link}
                      href={`/training/assignments/${assignment.id}/tests/${test.id}/take`}
                      startIcon={<QuizIcon />}
                      sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
                    >
                      {latestAttempt ? 'Retake / Review Test' : 'Take Online Test'}
                    </Button>
                  ) : (
                    <span className="text-xs text-slate-400">No test attached</span>
                  )}
                </Box>
              </Card>
            );
          })}

          {assignments.length === 0 && (
            <div className="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl">
              <SchoolIcon sx={{ fontSize: 48 }} className="text-slate-300 mb-2" />
              <Typography variant="h6">No Assigned Trainings</Typography>
              <Typography variant="body2">
                You are fully compliant! No active training assignments currently pending for your position.
              </Typography>
            </div>
          )}
        </div>
      </Box>
    </AsideLayout>
  );
}
