import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AsideLayout from '@/Layouts/AsideLayout';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  Chip,
  Divider,
  Paper,
  Stack,
  MenuItem,
  TextField,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer,
  LinearProgress
} from '@mui/material';

import {
  Print as PrintIcon,
  ArrowBack as ArrowBackIcon,
  EmojiEvents as TrophyIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  School as SchoolIcon,
  VerifiedUser as VerifiedIcon,
  ContentCopy as CopyIcon,
  Share as ShareIcon,
  Quiz as QuizIcon,
  AssignmentTurnedIn as AssignmentTurnedInIcon,
  EventAvailable as EventAvailableIcon,
  WorkspacePremium as CertificateIcon
} from '@mui/icons-material';

export default function Scorecard({
  assignment = {},
  latestAttempt = null,
  previousAttempts = [],
  grading = {},
  trainer = null,
  attendedSession = null,
  canManage = false,
  availableAssignments = []
}) {
  const [copied, setCopied] = useState(false);
  const user = assignment.user || {};
  const training = assignment.training || {};
  const answers = latestAttempt?.answers || [];

  const handleAssignmentChange = (e) => {
    const nextId = e.target.value;
    if (nextId) {
      router.get(`/training/assignments/${nextId}/scorecard`, {}, { preserveScroll: true });
    }
  };

  const copyShareLink = () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  };

  return (
    <AsideLayout title={`Training Scorecard: ${user.name || 'Employee'}`}>
      <Head title={`Training Scorecard - ${training.title || 'Report'}`} />

      {/* Print Styles */}
      <style>{`
        @media print {
          body * {
            visibility: hidden;
          }
          #training-scorecard-print,
          #training-scorecard-print * {
            visibility: visible;
          }
          #training-scorecard-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            background: #ffffff !important;
            color: #0f172a !important;
          }
          @page {
            size: A4 portrait;
            margin: 8mm 10mm;
          }
          .no-print {
            display: none !important;
          }
          .print-avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
          }
          .print-shadow-none {
            box-shadow: none !important;
          }
          * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
          }
        }
      `}</style>

      <Box className="max-w-5xl mx-auto space-y-6">
        {/* Controls Toolbar (Hidden in Print) */}
        <Box className="no-print flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
          <div className="flex items-center gap-3 flex-wrap">
            <Button
              variant="outlined"
              size="small"
              startIcon={<ArrowBackIcon />}
              component={Link}
              href="/training/my-trainings"
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              My Trainings
            </Button>

            {canManage && (
              <Button
                variant="outlined"
                size="small"
                component={Link}
                href="/training/dashboard"
                sx={{ textTransform: 'none', borderRadius: 2 }}
              >
                Compliance Matrix
              </Button>
            )}

            {canManage && availableAssignments.length > 0 && (
              <TextField
                select
                size="small"
                label="Select Record"
                value={assignment.id || ''}
                onChange={handleAssignmentChange}
                sx={{ minWidth: 260 }}
              >
                {availableAssignments.map((a) => (
                  <MenuItem key={a.id} value={a.id}>
                    {a.label}
                  </MenuItem>
                ))}
              </TextField>
            )}
          </div>

          <div className="flex items-center gap-2">
            <Button
              variant="outlined"
              size="small"
              startIcon={<CopyIcon />}
              onClick={copyShareLink}
              sx={{ textTransform: 'none', borderRadius: 2 }}
            >
              {copied ? 'Link Copied!' : 'Copy Link'}
            </Button>

            <Button
              variant="contained"
              size="small"
              color="primary"
              startIcon={<PrintIcon />}
              onClick={() => window.print()}
              sx={{ textTransform: 'none', fontWeight: 800, borderRadius: 2, px: 3 }}
            >
              Print / Save PDF
            </Button>
          </div>
        </Box>

        {/* Printable Scorecard Document */}
        <div id="training-scorecard-print" className="space-y-6">
          <Card
            elevation={0}
            className="border-2 border-slate-200 dark:border-slate-700 rounded-3xl bg-white dark:bg-slate-900 shadow-sm overflow-hidden"
          >
            {/* Top Official Security & Banner Header */}
            <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 relative overflow-hidden">
              <div className="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div className="flex items-center gap-4">
                  <div className="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center p-2">
                    <img
                      src="/images/logo.png"
                      alt="Organization Logo"
                      className="w-full h-full object-contain filter brightness-110"
                      onError={(e) => {
                        e.target.style.display = 'none';
                      }}
                    />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-[10px] font-mono tracking-widest uppercase px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 border border-sky-400/30">
                        Official Academic & Compliance Transcript
                      </span>
                    </div>
                    <Typography variant="h5" className="font-black tracking-tight text-white mt-1">
                      International Training Scorecard
                    </Typography>
                    <Typography variant="caption" className="text-slate-300 block">
                      {grading.evaluation_framework || 'Kirkpatrick Level 2 Learning Evaluation'} • {grading.standard_code}
                    </Typography>
                  </div>
                </div>

                <div className="text-right sm:border-l sm:border-white/15 sm:pl-6 space-y-1">
                  <div className="text-[11px] font-mono text-slate-300">
                    Verification ID: <b className="text-white">{grading.verification_code}</b>
                  </div>
                  <div className="text-[11px] text-slate-300">
                    Issue Date: <b>{grading.issue_date}</b>
                  </div>
                  <div className="text-[11px] text-slate-300">
                    Renewal Due: <b>{grading.expiry_date}</b>
                  </div>
                </div>
              </div>
            </div>

            <CardContent className="p-6 sm:p-8 space-y-6">
              {/* Section 1: Candidate & Module Metadata */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                {/* Candidate Info */}
                <div className="space-y-1.5">
                  <Typography variant="caption" className="font-bold uppercase tracking-wider text-slate-400 block">
                    Candidate Profile
                  </Typography>
                  <Typography variant="h6" className="font-extrabold text-slate-900 dark:text-slate-100">
                    {user.name}
                  </Typography>
                  <div className="text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                    <div>Department: <b>{user.department?.name || 'General Staff'}</b></div>
                    <div>Office Position: <b>{user.office_position?.name || 'Staff Member'}</b></div>
                    <div>Employee Email: <b>{user.email}</b></div>
                  </div>
                </div>

                {/* Course Module Info */}
                <div className="space-y-1.5 md:border-l md:border-slate-200 dark:md:border-slate-700 md:pl-5">
                  <Typography variant="caption" className="font-bold uppercase tracking-wider text-slate-400 block">
                    Assessed Training Module
                  </Typography>
                  <Typography variant="h6" className="font-extrabold text-indigo-700 dark:text-indigo-400">
                    {training.title}
                  </Typography>
                  <div className="text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                    <div>Module Code: <span className="font-mono font-bold text-sky-600">{training.code}</span></div>
                    <div>Category: <b>{training.category?.name || 'Standard SOP'}</b></div>
                    <div>
                      Passing Standard: <b>{grading.passing_score}% Score</b>
                    </div>
                  </div>
                </div>
              </div>

              {/* Section 2: International Scoring & Performance Badges */}
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                {/* Gauge Radial Chart */}
                <div className="lg:col-span-4 flex flex-col items-center justify-center p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700">
                  <Typography variant="caption" className="font-bold uppercase tracking-wider text-slate-400 mb-2 text-center">
                    Assessment Score
                  </Typography>
                  <div className="w-full max-w-[210px]">
                    <ApexScoreGauge
                      value={grading.percentage}
                      color={grading.passed ? '#059669' : '#e11d48'}
                    />
                  </div>
                  <div className="text-center mt-2">
                    <span className="text-xs font-semibold text-slate-500">
                      Passing Benchmark: <b>{grading.passing_score}%</b>
                    </span>
                  </div>
                </div>

                {/* International Honors & Grade Card */}
                <div className="lg:col-span-8 space-y-4">
                  <div
                    className={`p-5 rounded-2xl border ${
                      grading.passed
                        ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800'
                        : 'bg-rose-50/60 dark:bg-rose-950/20 border-rose-300 dark:border-rose-800'
                    }`}
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                      <div className="flex items-center gap-3">
                        <div
                          className={`w-12 h-12 rounded-xl flex items-center justify-center font-black text-xl text-white ${
                            grading.passed ? 'bg-emerald-600' : 'bg-rose-600'
                          }`}
                        >
                          {grading.grade}
                        </div>
                        <div>
                          <Typography variant="h6" className="font-black text-slate-900 dark:text-slate-100">
                            {grading.grade_title}
                          </Typography>
                          <Typography variant="caption" className="text-slate-500 font-semibold block">
                            {grading.competency_level}
                          </Typography>
                        </div>
                      </div>

                      <Chip
                        label={grading.passed ? '✓ REQUIREMENT MET' : '✗ RETAKE REQUIRED'}
                        color={grading.passed ? 'success' : 'error'}
                        sx={{ fontWeight: 900, letterSpacing: '0.05em' }}
                      />
                    </div>

                    <Typography variant="body2" className="text-slate-600 dark:text-slate-300 text-xs mt-3 leading-relaxed">
                      {grading.performance_descriptor}
                    </Typography>
                  </div>

                  {/* 4 Summary Metrics */}
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Paper elevation={0} className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Marks Earned
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-slate-800 dark:text-slate-200">
                        {grading.score} / {grading.max_score}
                      </Typography>
                    </Paper>

                    <Paper elevation={0} className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Accuracy Rate
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-slate-800 dark:text-slate-200">
                        {grading.accuracy_rate}%
                      </Typography>
                    </Paper>

                    <Paper elevation={0} className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Questions Passed
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-emerald-600">
                        {grading.correct_questions} / {grading.total_questions}
                      </Typography>
                    </Paper>

                    <Paper elevation={0} className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Attempt Count
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-slate-800 dark:text-slate-200">
                        #{latestAttempt?.attempt_number || 1}
                      </Typography>
                    </Paper>
                  </div>
                </div>
              </div>

              {/* Section 3: Competency Domains Breakdown */}
              <div className="print-avoid-break space-y-3 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700">
                <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                  <AssignmentTurnedInIcon fontSize="small" className="text-sky-600" />
                  International Competency Domain Index
                </Typography>

                <div className="space-y-3 pt-1">
                  {(grading.competency_domains || []).map((dom, dIdx) => (
                    <div key={dIdx} className="space-y-1">
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-bold text-slate-800 dark:text-slate-200">
                          {dom.domain}
                        </span>
                        <span className="font-mono font-bold text-slate-700 dark:text-slate-300">
                          {dom.score}% (Benchmark: {dom.benchmark}%)
                        </span>
                      </div>
                      <LinearProgress
                        variant="determinate"
                        value={Math.min(100, dom.score)}
                        color={dom.score >= dom.benchmark ? 'success' : 'warning'}
                        sx={{ height: 6, borderRadius: 3 }}
                      />
                      <Typography variant="caption" className="text-[11px] text-slate-400 block">
                        {dom.description}
                      </Typography>
                    </div>
                  ))}
                </div>
              </div>

              {/* Section 4: Detailed Questions & Answers Transcript */}
              <div className="print-avoid-break space-y-3">
                <div className="flex items-center justify-between">
                  <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <QuizIcon fontSize="small" className="text-sky-600" />
                    Itemized Examination Transcript & Evidence Log
                  </Typography>
                  <Typography variant="caption" className="text-slate-400 font-medium">
                    Preserved answers recorded for compliance audit
                  </Typography>
                </div>

                <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                  <Table size="small">
                    <TableHead className="bg-slate-100 dark:bg-slate-800">
                      <TableRow>
                        <TableCell className="font-bold text-xs w-12 text-center">No</TableCell>
                        <TableCell className="font-bold text-xs">Question & Concept</TableCell>
                        <TableCell className="font-bold text-xs w-28">Type</TableCell>
                        <TableCell className="font-bold text-xs">Selected Answer(s)</TableCell>
                        <TableCell className="font-bold text-xs w-20 text-center">Marks</TableCell>
                        <TableCell className="font-bold text-xs w-20 text-center">Status</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {answers.map((ans, aIdx) => {
                        const q = ans.question || {};
                        const selectedAnswers =
                          ans.selected_options && ans.selected_options.length > 0
                            ? ans.selected_options.map((o) => o.answer).join(', ')
                            : ans.selected_option?.answer || 'No answer selected';

                        return (
                          <TableRow key={ans.id || aIdx} hover>
                            <TableCell className="text-xs text-center font-bold text-slate-400">
                              {aIdx + 1}
                            </TableCell>

                            <TableCell className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                              {q.question}
                            </TableCell>

                            <TableCell className="text-xs">
                              <Chip
                                label={
                                  q.question_type === 'MULTI_SELECT'
                                    ? 'Multi-Select'
                                    : q.question_type === 'TRUE_FALSE'
                                    ? 'True/False'
                                    : 'Multiple Choice'
                                }
                                size="small"
                                variant="outlined"
                                color={q.question_type === 'MULTI_SELECT' ? 'info' : 'default'}
                                sx={{ fontSize: '0.65rem', height: 20 }}
                              />
                            </TableCell>

                            <TableCell className="text-xs text-slate-700 dark:text-slate-300">
                              <span className="font-medium">{selectedAnswers}</span>
                            </TableCell>

                            <TableCell className="text-xs text-center font-mono font-bold">
                              {ans.marks_obtained} / {q.marks}
                            </TableCell>

                            <TableCell className="text-xs text-center">
                              {ans.is_correct ? (
                                <span className="text-emerald-600 font-bold text-xs flex items-center justify-center gap-0.5">
                                  <CheckCircleIcon sx={{ fontSize: 14 }} /> Correct
                                </span>
                              ) : (
                                <span className="text-rose-600 font-bold text-xs flex items-center justify-center gap-0.5">
                                  <CancelIcon sx={{ fontSize: 14 }} /> Incorrect
                                </span>
                              )}
                            </TableCell>
                          </TableRow>
                        );
                      })}

                      {answers.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={6} align="center" className="text-slate-400 py-6">
                            No evaluated question answers available for this assessment yet.
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </div>

              {/* Section 5: Session Attendance Record (if applicable) */}
              {attendedSession && (
                <div className="print-avoid-break p-4 rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50/50 dark:bg-sky-950/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div className="flex items-center gap-2.5">
                    <EventAvailableIcon color="primary" fontSize="small" />
                    <div>
                      <span className="font-bold text-sky-900 dark:text-sky-200">
                        Session Attendance: {attendedSession.title} ({attendedSession.session_code})
                      </span>
                      <div className="text-slate-500 dark:text-slate-400 text-[11px]">
                        Conducted on {attendedSession.scheduled_at ? new Date(attendedSession.scheduled_at).toLocaleString() : 'N/A'} • Trainer: <b>{trainer?.name || 'Designated Lead Trainer'}</b>
                      </div>
                    </div>
                  </div>
                  <Chip label="ATTENDANCE VERIFIED" size="small" color="primary" sx={{ fontWeight: 700, fontSize: '0.65rem' }} />
                </div>
              )}

              {/* Section 6: Official Verification & Sign-off Block */}
              <div className="print-avoid-break pt-6 border-t-2 border-slate-200 dark:border-slate-700">
                <div className="grid grid-cols-3 gap-6 text-center">
                  <div className="space-y-1">
                    <div className="h-14 border-b-2 border-slate-300 dark:border-slate-600 mb-2 flex items-end justify-center pb-1">
                      <span className="text-[11px] font-serif italic text-slate-400">
                        {trainer?.name || 'Official Proctor / Assessor'}
                      </span>
                    </div>
                    <Typography variant="caption" className="font-bold text-slate-700 dark:text-slate-300 block">
                      Lead Trainer / Assessor
                    </Typography>
                    <Typography variant="caption" className="text-[10px] text-slate-400 block">
                      Evaluation & Knowledge Sign-off
                    </Typography>
                  </div>

                  <div className="space-y-1">
                    <div className="h-14 border-b-2 border-slate-300 dark:border-slate-600 mb-2 flex items-end justify-center pb-1">
                      <span className="text-[11px] font-serif italic text-slate-400">
                        {user.name}
                      </span>
                    </div>
                    <Typography variant="caption" className="font-bold text-slate-700 dark:text-slate-300 block">
                      Candidate Signature
                    </Typography>
                    <Typography variant="caption" className="text-[10px] text-slate-400 block">
                      Acknowledgment of Results
                    </Typography>
                  </div>

                  <div className="space-y-1">
                    <div className="h-14 border-b-2 border-slate-300 dark:border-slate-600 mb-2 flex items-end justify-center pb-1">
                      <div className="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-400 text-xs font-bold font-mono">
                        <VerifiedIcon sx={{ fontSize: 16 }} /> CERTIFIED
                      </div>
                    </div>
                    <Typography variant="caption" className="font-bold text-slate-700 dark:text-slate-300 block">
                      Compliance & QA Director
                    </Typography>
                    <Typography variant="caption" className="text-[10px] text-slate-400 block">
                      Audit & Accreditation Seal
                    </Typography>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </Box>
    </AsideLayout>
  );
}

// ApexCharts Half-Donut Score Gauge
function ApexScoreGauge({ value = 0, color = '#059669' }) {
  const containerRef = useRef(null);
  const chartRef = useRef(null);

  useEffect(() => {
    if (!containerRef.current) return;

    const buildOptions = (val, clr) => ({
      chart: {
        type: 'radialBar',
        height: 190,
        offsetY: -10,
        sparkline: { enabled: true },
      },
      plotOptions: {
        radialBar: {
          startAngle: -90,
          endAngle: 90,
          track: {
            background: '#e2e8f0',
            strokeWidth: '96%',
            margin: 4,
          },
          dataLabels: {
            name: { show: false },
            value: {
              offsetY: -4,
              fontSize: '24px',
              fontWeight: 800,
              color: clr,
              formatter: (v) => `${parseFloat(v).toFixed(1)}%`,
            },
          },
        },
      },
      colors: [clr],
      series: [value],
      grid: { padding: { top: -10 } },
      fill: { type: 'solid' },
    });

    const ApexCharts = window.ApexCharts;
    if (!ApexCharts) {
      import('apexcharts').then(({ default: AC }) => {
        if (chartRef.current) chartRef.current.destroy();
        const chart = new AC(containerRef.current, buildOptions(value, color));
        chart.render();
        chartRef.current = chart;
      });
      return;
    }

    if (chartRef.current) {
      chartRef.current.updateOptions(buildOptions(value, color), true);
      chartRef.current.updateSeries([value]);
    } else {
      const chart = new ApexCharts(containerRef.current, buildOptions(value, color));
      chart.render();
      chartRef.current = chart;
    }

    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
  }, [value, color]);

  return <div ref={containerRef} />;
}
