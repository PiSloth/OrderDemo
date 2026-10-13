import React, { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import AsideLayout from '@/Layouts/AsideLayout';
import UserSelectModal from '@/components/IT/UserSelectModal';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Checkbox,
  Chip,
  FormControlLabel,
  Paper,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer
} from '@mui/material';

import {
  Print as PrintIcon,
  ArrowUpward as ArrowUpwardIcon,
  Close as CloseIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  ContentCopy as CopyIcon,
  Share as ShareIcon,
  Quiz as QuizIcon,
  EventAvailable as EventAvailableIcon,
  WorkspacePremium as CertificateIcon,
  MenuBook as MenuBookIcon,
  Description as DescriptionIcon
} from '@mui/icons-material';

export default function Scorecard({
  assignment = {},
  latestAttempt = null,
  previousAttempts = [],
  grading = {},
  trainer = null,
  attendedSession = null,
  scorecardUsers = [],
  scorecardDepartments = [],
  currentUserName = ''
}) {
  const [copied, setCopied] = useState(false);
  const [fabOpen, setFabOpen] = useState(false);
  const [userModalOpen, setUserModalOpen] = useState(false);
  const [showTranscript, setShowTranscript] = useState(false);
  const user = assignment.user || {};
  const training = assignment.training || {};
  const answers = latestAttempt?.answers || [];
  const canSwitchUsers = Array.isArray(scorecardUsers) && scorecardUsers.length > 0;
  const avatarUrl = user.profile_photo_url || null;

  const handleUserSelect = (selected) => {
    setUserModalOpen(false);
    if (selected?.latest_assignment_id && Number(selected.latest_assignment_id) !== Number(assignment.id)) {
      router.get(`/training/assignments/${selected.latest_assignment_id}/scorecard`);
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

      <Box className="max-w-[210mm] mx-auto space-y-6">
        {/* Printable Scorecard Document */}
        <div id="training-scorecard-print" className="space-y-6">
          {/* Premium gold certificate frame */}
          <div className="p-[3px] rounded-[26px] bg-gradient-to-br from-amber-200 via-yellow-600 to-amber-200 shadow-xl print-shadow-none overflow-hidden">
          <Card
            elevation={0}
            className="border border-amber-100 dark:border-slate-700 rounded-[24px] bg-white dark:bg-slate-900 shadow-none overflow-hidden relative"
          >
            {/* Top Official Security & Banner Header — ivory premium */}
            <div
              className="bg-[#FFFEF7] text-slate-900 p-6 sm:p-8 relative overflow-hidden rounded-t-[21px] border-b-4 border-amber-500"
              style={{
                backgroundImage:
                  'radial-gradient(circle at 85% 10%, rgba(251,191,36,0.22) 0, transparent 45%), radial-gradient(circle at 5% 95%, rgba(30,27,75,0.06) 0, transparent 50%)',
              }}
            >
              <div className="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div className="flex items-center gap-4">
                  <div className="w-16 h-16 rounded-2xl border border-amber-300 shadow-sm flex items-center justify-center p-2">
                    <img
                      src="/images/logo.png"
                      alt="Organization Logo"
                      className="w-full h-full object-contain"
                      onError={(e) => {
                        e.target.style.display = 'none';
                      }}
                    />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-[10px] font-mono tracking-widest uppercase px-2 py-0.5 rounded bg-indigo-950 text-amber-300 border border-amber-500/50">
                        Official Academic & Compliance Transcript
                      </span>
                    </div>
                    <Typography variant="h5" className="font-serif font-black tracking-tight text-indigo-950 mt-1">
                      International Training Scorecard
                    </Typography>
                    <div className="flex items-center gap-1 mt-1 text-amber-500 text-[11px] tracking-[0.35em]" aria-hidden="true">
                      ★ ★ ★ ★ ★
                    </div>
                    <Typography variant="caption" className="text-slate-600 font-medium block">
                      {grading.evaluation_framework || 'Kirkpatrick Level 2 Learning Evaluation'} • {grading.standard_code}
                    </Typography>
                  </div>
                </div>

                <div className="text-right sm:border-l sm:border-amber-300 sm:pl-6 space-y-1">
                  <div className="text-[11px] font-mono text-slate-500">
                    Verification ID: <b className="text-indigo-950">{grading.verification_code}</b>
                  </div>
                  <div className="text-[11px] text-slate-500">
                    Issue Date: <b className="text-slate-900">{grading.issue_date}</b>
                  </div>
                  <div className="text-[11px] text-slate-500">
                    Renewal Due: <b className="text-slate-900">{grading.expiry_date}</b>
                  </div>
                </div>
              </div>
            </div>

            <CardContent className="p-6 sm:p-8 space-y-6">
              {/* Section 1: Candidate & Module Metadata */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                {/* Candidate Info */}
                <div className="space-y-1.5 group/profile">
                  <Typography variant="caption" className="font-bold uppercase tracking-wider text-slate-400 block">
                    Candidate Profile
                  </Typography>
                  <div className="flex items-center gap-3.5">
                    {/* Clickable avatar opens employee switch modal (managers) */}
                    <div
                      role={canSwitchUsers ? 'button' : undefined}
                      tabIndex={canSwitchUsers ? 0 : undefined}
                      title={canSwitchUsers ? 'Click to switch employee' : user.name}
                      onClick={canSwitchUsers ? () => setUserModalOpen(true) : undefined}
                      onKeyDown={canSwitchUsers ? (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setUserModalOpen(true); } } : undefined}
                      className={`relative shrink-0 rounded-full ring-2 ring-amber-400 ring-offset-2 ring-offset-white dark:ring-offset-slate-900 outline-none ${
                        canSwitchUsers ? 'cursor-pointer group focus-visible:ring-indigo-500' : 'cursor-default'
                      }`}
                    >
                      <div className="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-indigo-100 via-white to-amber-100 flex items-center justify-center">
                        {avatarUrl ? (
                          <img
                            src={avatarUrl}
                            alt={user.name}
                            className="w-full h-full object-cover transition duration-300 group-hover:scale-105"
                            onError={(e) => { e.target.style.display = 'none'; }}
                          />
                        ) : (
                          <span className="text-2xl font-black text-indigo-800 uppercase select-none">
                            {(user.name || '?').charAt(0)}
                          </span>
                        )}
                      </div>
                      {canSwitchUsers && (
                        <div className="absolute inset-0 rounded-full bg-indigo-950/60 opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 flex items-center justify-center transition-opacity duration-200">
                          <span className="text-[9px] font-black uppercase tracking-wider text-white">Switch</span>
                        </div>
                      )}
                    </div>
                    <div className="min-w-0">
                      <Typography variant="h6" className="font-serif font-black text-2xl tracking-tight text-slate-900 dark:text-slate-100 leading-tight">
                        {user.name}
                      </Typography>
                      {canSwitchUsers && (
                        <div className="text-[10px] font-semibold text-indigo-500 dark:text-indigo-400 mt-0.5 opacity-0 transition-opacity duration-200 group-hover/profile:opacity-100 group-focus-within/profile:opacity-100">
                          Click avatar to switch employee
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                    <div>Department: <b>{user.department?.name || 'General Staff'}</b></div>
                    <div>Office Position: <b>{user.officePosition?.name || user.office_position?.name || 'Staff Member'}</b></div>
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
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                {/* Gauge Radial Chart */}
                <div className="lg:col-span-4 h-full flex flex-col items-center justify-center p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700">
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
                    <Paper elevation={0} className="p-3 h-full rounded-xl border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center justify-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Marks Earned
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-slate-800 dark:text-slate-200">
                        {grading.score} / {grading.max_score}
                      </Typography>
                    </Paper>

                    <Paper elevation={0} className="p-3 h-full rounded-xl border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center justify-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Accuracy Rate
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-slate-800 dark:text-slate-200">
                        {grading.accuracy_rate}%
                      </Typography>
                    </Paper>

                    <Paper elevation={0} className="p-3 h-full rounded-xl border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center justify-center">
                      <Typography variant="caption" className="text-slate-400 font-bold uppercase text-[10px] block">
                        Questions Passed
                      </Typography>
                      <Typography variant="subtitle1" className="font-extrabold text-emerald-600">
                        {grading.correct_questions} / {grading.total_questions}
                      </Typography>
                    </Paper>

                    <Paper elevation={0} className="p-3 h-full rounded-xl border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center justify-center">
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

              {/* Section 3: Company Document Knowledge & Mastery Pass Rate */}
              <div className="print-avoid-break space-y-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                      <MenuBookIcon fontSize="small" className="text-sky-600" />
                      Company Document Mastery & Knowledge Pass Rate
                    </Typography>
                    <Typography variant="caption" className="text-slate-500 dark:text-slate-400">
                      Per-document grade based on scored percentage — see grading scale at the end of this certificate
                    </Typography>
                  </div>
                </div>

                <div className="space-y-2.5 pt-1">
                  {((grading.document_breakdown && grading.document_breakdown.length > 0)
                    ? grading.document_breakdown
                    : (grading.competency_domains || [])
                  ).map((doc, dIdx) => {
                    const percentage = doc.percentage !== undefined ? doc.percentage : (doc.score || 0);
                    const docTitle = doc.document_title || doc.domain || 'Standard Operational Procedure';
                    const g = docGrade(percentage);

                    return (
                      <div
                        key={dIdx}
                        className="flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-900/60"
                      >
                        <span className="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">
                          {docTitle}
                        </span>
                        <span className="flex items-center gap-2.5 shrink-0">
                          <span className="text-base font-extrabold font-mono text-slate-700 dark:text-slate-200">
                            {percentage}%
                          </span>
                          <span className={`w-10 h-10 rounded-xl flex items-center justify-center font-black text-base border ${g.classes}`}>
                            {g.letter}
                          </span>
                        </span>
                      </div>
                    );
                  })}

                  {(!grading.document_breakdown || grading.document_breakdown.length === 0) && (!grading.competency_domains || grading.competency_domains.length === 0) && (
                    <div className="p-4 text-center text-slate-400 text-xs italic bg-white dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800">
                      No document questions recorded for this assessment.
                    </div>
                  )}
                </div>
              </div>

              {/* Section 4: Detailed Questions & Answers Transcript (hidden until checked) */}
              <div className="print-avoid-break space-y-3">
                <div className="flex items-center justify-between gap-2 flex-wrap">
                  <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <QuizIcon fontSize="small" className="text-sky-600" />
                    Itemized Examination Transcript & Evidence Log
                  </Typography>
                  <div className="flex items-center gap-2">
                    <Typography variant="caption" className="text-slate-400 font-medium hidden sm:block">
                      Preserved answers recorded for compliance audit
                    </Typography>
                    <FormControlLabel
                      className="no-print"
                      control={
                        <Checkbox
                          size="small"
                          checked={showTranscript}
                          onChange={(e) => setShowTranscript(e.target.checked)}
                          sx={{ color: '#0284c7', '&.Mui-checked': { color: '#0284c7' }, p: 0.5 }}
                        />
                      }
                      label={
                        <span className="text-xs font-bold text-slate-600 dark:text-slate-300">
                          Show logs{answers.length > 0 ? ` (${answers.length})` : ''}
                        </span>
                      }
                      sx={{ mr: 0 }}
                    />
                  </div>
                </div>

                {showTranscript && (
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
                              <div>{q.question}</div>
                              {q.company_document && (
                                <div className="flex items-center gap-1 mt-1.5 flex-wrap">
                                  <span className="inline-flex items-center gap-1 text-[11px] font-medium text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800">
                                    <DescriptionIcon sx={{ fontSize: 13 }} className="text-sky-600 dark:text-sky-400" />
                                    {q.company_document.title}
                                    {q.document_section_reference && (
                                      <span className="text-slate-500 dark:text-slate-400 font-normal">
                                        • {q.document_section_reference}
                                      </span>
                                    )}
                                  </span>
                                </div>
                              )}
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
                )}
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
              <div className="print-avoid-break pt-6 border-t-2 border-amber-300 dark:border-amber-700">
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
                    <div className="h-14 border-b-2 border-amber-300 dark:border-amber-700 mb-2 flex items-end justify-center pb-1">
                      <div className="inline-flex items-center gap-1.5">
                        <span className="w-8 h-8 rounded-full bg-gradient-to-br from-amber-300 via-yellow-500 to-amber-600 flex items-center justify-center shadow-md ring-2 ring-amber-200 dark:ring-amber-800">
                          <CertificateIcon sx={{ fontSize: 16 }} className="text-white" />
                        </span>
                        <span className="text-amber-700 dark:text-amber-400 text-xs font-black font-mono tracking-widest">CERTIFIED</span>
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

              {/* Grading Scale Reference — end of certificate */}
              <div className="print-avoid-break pt-5 border-t border-dashed border-amber-300 dark:border-amber-800">
                <Typography variant="caption" className="font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400 block text-center">
                  Document Grading Scale
                </Typography>
                <div className="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mt-3">
                  {DOC_GRADE_SCALE.map((s) => {
                    const g = docGrade(s.min);
                    return (
                      <span key={g.letter} className="inline-flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60">
                        <span className={`w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm border ${g.classes}`}>
                          {g.letter}
                        </span>
                        <span className="text-[11px] font-bold text-slate-600 dark:text-slate-300 font-mono">{s.label}</span>
                      </span>
                    );
                  })}
                </div>
              </div>
            </CardContent>
          </Card>
          </div>
        </div>

        {/* ── Floating quick actions: right edge, wave-expand on click ── */}
        <div className="no-print fixed right-2 sm:right-5 top-1/2 -translate-y-1/2 z-50 flex flex-col items-end gap-3">
          {[
            {
              key: 'copy',
              label: copied ? 'Link Copied!' : 'Copy Link',
              icon: copied ? <CheckCircleIcon fontSize="small" /> : <CopyIcon fontSize="small" />,
              onClick: copyShareLink,
              btn: copied
                ? 'bg-emerald-600 border-emerald-500 text-white'
                : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-indigo-400',
              x: '-18px',
            },
            {
              key: 'print',
              label: 'Print / Save PDF',
              icon: <PrintIcon fontSize="small" />,
              onClick: () => window.print(),
              btn: 'bg-indigo-600 border-indigo-500 text-white',
              x: '6px',
            },
            {
              key: 'top',
              label: 'Back to Top',
              icon: <ArrowUpwardIcon fontSize="small" />,
              onClick: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
              btn: 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300',
              x: '-18px',
            },
          ].map((a, i, arr) => (
            <div
              key={a.key}
              className="flex items-center justify-end gap-2 transition-all duration-300 ease-out"
              style={{
                transitionDelay: fabOpen ? `${(arr.length - 1 - i) * 80}ms` : '0ms',
                opacity: fabOpen ? 1 : 0,
                transform: fabOpen
                  ? `translateX(${a.x}) scale(1)`
                  : 'translateX(0) translateY(14px) scale(0.3)',
                pointerEvents: fabOpen ? 'auto' : 'none',
              }}
            >
              <span className="text-[11px] font-bold whitespace-nowrap bg-slate-900/90 dark:bg-white/90 dark:text-slate-900 text-white px-2.5 py-1 rounded-lg shadow-lg">
                {a.label}
              </span>
              <button
                type="button"
                onClick={a.onClick}
                aria-label={a.label}
                className={`w-11 h-11 rounded-full flex items-center justify-center shadow-xl border-2 transition-transform hover:scale-110 active:scale-95 ${a.btn}`}
              >
                {a.icon}
              </button>
            </div>
          ))}

          {/* Main toggle */}
          <button
            type="button"
            onClick={() => setFabOpen((v) => !v)}
            aria-label={fabOpen ? 'Close quick actions' : 'Open quick actions'}
            className={`relative w-14 h-14 rounded-full flex items-center justify-center text-white shadow-2xl border-2 border-white/50 transition-all duration-300 hover:scale-105 active:scale-95 ${
              fabOpen
                ? 'bg-gradient-to-br from-rose-500 to-orange-500 rotate-90'
                : 'bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700'
            }`}
          >
            {!fabOpen && (
              <span className="absolute inline-flex h-full w-full rounded-full bg-violet-500 opacity-30 animate-ping" />
            )}
            <span className="relative">{fabOpen ? <CloseIcon /> : <ShareIcon />}</span>
          </button>
        </div>

        {/* Employee switch modal (opened from avatar) */}
        {canSwitchUsers && (
          <UserSelectModal
            open={userModalOpen}
            onClose={() => setUserModalOpen(false)}
            onSelect={handleUserSelect}
            title="Switch Scorecard Employee"
            subtitle="Search and select an employee to view their training scorecard"
            selectedUserId={user.id}
            selectedUserName={user.name}
            users={scorecardUsers}
            departments={scorecardDepartments}
            currentUserName={currentUserName}
          />
        )}
      </Box>
    </AsideLayout>
  );
}

// Per-document letter grade scale: A+ ≥90, A ≥85, B+ ≥80, B ≥70, C <70
function docGrade(pct) {
  const v = Number(pct) || 0;
  if (v >= 90) return { letter: 'A+', classes: 'bg-amber-100 text-amber-800 border-amber-400 dark:bg-amber-400/20 dark:text-amber-300 dark:border-amber-500/50' };
  if (v >= 85) return { letter: 'A', classes: 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-600/40' };
  if (v >= 80) return { letter: 'B+', classes: 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-500/15 dark:text-sky-300 dark:border-sky-600/40' };
  if (v >= 70) return { letter: 'B', classes: 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-600/40' };
  return { letter: 'C', classes: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-600/40' };
}

const DOC_GRADE_SCALE = [
  { min: 90, label: '≥ 90%' },
  { min: 85, label: '≥ 85%' },
  { min: 80, label: '≥ 80%' },
  { min: 70, label: '≥ 70%' },
  { min: 0, label: '< 70%' },
];

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
