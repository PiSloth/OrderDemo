import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import UserSelectModal from '@/Components/IT/UserSelectModal';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  TextField,
  MenuItem,
  Chip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  IconButton,
  Tooltip,
  Stack,
  Divider,
  Paper,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer,
  Avatar,
  Tabs,
  Tab,
  FormControl,
  InputLabel,
  Select,
  InputAdornment,
  Menu,
  ListItemIcon,
  ListItemText
} from '@mui/material';

import {
  Schedule as ScheduleIcon,
  Add as AddIcon,
  Edit as EditIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  PlayArrow as PlayIcon,
  LockOpen as OpenIcon,
  People as PeopleIcon,
  LocationOn as LocationIcon,
  Link as LinkIcon,
  AssignmentTurnedIn as AttendanceIcon,
  PictureAsPdf as PdfIcon,
  Verified as VerifiedIcon,
  PersonAdd as PersonAddIcon,
  Delete as DeleteIcon,
  History as HistoryIcon,
  Search as SearchIcon,
  Warning as WarningIcon,
  Quiz as QuizIcon,
  MoreVert as MoreVertIcon,
  EventNote as EventNoteIcon,
  HourglassEmpty as HourglassEmptyIcon
} from '@mui/icons-material';

import { exportSessionAnnouncementPdf } from '../../../utils/trainingPdfExport';

export default function SessionsIndex({
  sessions = {},
  trainings = [],
  trainers = [],
  allActiveUsers = [],
  departments = [],
  auth_user = {},
  statuses = [],
  permissions = {},
  filters = {}
}) {
  const [modalOpen, setModalOpen] = useState(false);
  const [editingSession, setEditingSession] = useState(null);

  // Card More Actions Menu State
  const [menuAnchorEl, setMenuAnchorEl] = useState(null);
  const [activeMenuSession, setActiveMenuSession] = useState(null);

  const handleOpenMenu = (event, session) => {
    event.stopPropagation();
    setMenuAnchorEl(event.currentTarget);
    setActiveMenuSession(session);
  };

  const handleCloseMenu = () => {
    setMenuAnchorEl(null);
    setActiveMenuSession(null);
  };

  const handleDeleteSession = (session) => {
    if (session.attempt_results_count > 0) {
      alert(`Cannot delete session "${session.title}" (${session.session_code}) because employees have already submitted assessment results for this session.`);
      return;
    }

    if (confirm(`Are you sure you want to delete session "${session.title}" (${session.session_code})? Any unattempted test templates and attendance records generated for this session will also be deleted.`)) {
      router.delete(`/training/sessions/${session.id}`);
    }
  };

  // Attendance modal state
  const [attendanceModalOpen, setAttendanceModalOpen] = useState(false);
  const [selectedSessionForAttendance, setSelectedSessionForAttendance] = useState(null);
  const [participantAttendance, setParticipantAttendance] = useState([]);

  // Absent confirmation state
  const [absentConfirmOpen, setAbsentConfirmOpen] = useState(false);
  const [pendingAbsentChange, setPendingAbsentChange] = useState(null);
  const [absentReason, setAbsentReason] = useState('');

  // Add participant modal state
  const [addParticipantOpen, setAddParticipantOpen] = useState(false);
  const [selectedUserIdToAdd, setSelectedUserIdToAdd] = useState('');

  // Approve session modal state
  const [approveModalOpen, setApproveModalOpen] = useState(false);
  const [sessionToApprove, setSessionToApprove] = useState(null);
  const [approvalNotes, setApprovalNotes] = useState('');

  // Test template / answering status modal state
  const [testStatusModalOpen, setTestStatusModalOpen] = useState(false);
  const [selectedSessionForTestStatus, setSelectedSessionForTestStatus] = useState(null);

  // Filter states
  const [timeFilter, setTimeFilter] = useState(filters.time_filter || 'all');
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || '');
  const [trainingFilter, setTrainingFilter] = useState(filters.training_id || '');
  const [trainerFilter, setTrainerFilter] = useState(filters.trainer_id || '');

  const { data, setData, post, put, processing, errors, reset } = useForm({
    training_id: '',
    trainer_id: '',
    title: '',
    session_code: '',
    scheduled_at: '',
    start_date: '',
    end_date: '',
    duration_days: 1,
    venue: '',
    meeting_link: '',
    status: 'PENDING',
  });

  const handleFilterChange = (key, val) => {
    const query = {
      time_filter: key === 'time_filter' ? val : timeFilter,
      status: key === 'status' ? val : statusFilter,
      training_id: key === 'training_id' ? val : trainingFilter,
      trainer_id: key === 'trainer_id' ? val : trainerFilter,
      search: key === 'search' ? val : searchQuery,
    };
    router.get('/training/sessions', query, { preserveState: true, replace: true });
  };

  const handleOpenCreate = () => {
    setEditingSession(null);
    reset();
    const defaultTraining = trainings[0];
    const dur = defaultTraining?.duration_days || 1;
    const defaultStart = new Date(Date.now() + 7 * 86400000).toISOString().substring(0, 10);
    const defaultEnd = new Date(Date.now() + (7 + dur - 1) * 86400000).toISOString().substring(0, 10);

    setData({
      training_id: defaultTraining?.id ? String(defaultTraining.id) : '',
      trainer_id: '',
      title: defaultTraining ? `${defaultTraining.title} - Shared Session` : '',
      session_code: '',
      scheduled_at: `${defaultStart}T09:00`,
      start_date: defaultStart,
      end_date: defaultEnd,
      duration_days: dur,
      venue: '',
      meeting_link: '',
      status: 'OPEN',
    });
    setModalOpen(true);
  };

  const handleOpenEdit = (session) => {
    setEditingSession(session);
    setData({
      training_id: String(session.training_id),
      trainer_id: session.trainer_id ? String(session.trainer_id) : '',
      title: session.title || '',
      session_code: session.session_code || '',
      scheduled_at: session.scheduled_at ? session.scheduled_at.substring(0, 16) : '',
      start_date: session.start_date ? session.start_date.substring(0, 10) : '',
      end_date: session.end_date ? session.end_date.substring(0, 10) : '',
      duration_days: session.duration_days || 1,
      venue: session.venue || '',
      meeting_link: session.meeting_link || '',
      status: session.status,
    });
    setModalOpen(true);
  };

  const handleClose = () => {
    setModalOpen(false);
    setEditingSession(null);
    reset();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editingSession) {
      put(`/training/sessions/${editingSession.id}`, {
        onSuccess: () => handleClose(),
      });
    } else {
      post('/training/sessions', {
        onSuccess: () => handleClose(),
      });
    }
  };

  const handleUpdateStatus = (session, newStatus) => {
    put(`/training/sessions/${session.id}/status`, { status: newStatus });
  };

  // Open Attendance Modal
  const handleOpenAttendance = (session) => {
    setSelectedSessionForAttendance(session);
    setParticipantAttendance(
      (session.participants || []).map((p) => ({
        id: p.id,
        user_id: p.user_id,
        user_name: p.user?.name,
        department: p.user?.department?.name,
        office_position: p.user?.office_position?.name,
        attendance_status: p.attendance_status || 'REGISTERED',
        daily_attendance: p.daily_attendance || {},
        notes: p.notes || '',
      }))
    );
    setAttendanceModalOpen(true);
  };

  // Change status of a specific date for a participant
  const handleDailyStatusChange = (pIndex, dateKey, newStatus) => {
    const participant = participantAttendance[pIndex];
    if (newStatus === 'ABSENT') {
      // Trigger Absent confirmation modal
      setPendingAbsentChange({ pIndex, dateKey, newStatus, participantName: participant.user_name });
      setAbsentReason('');
      setAbsentConfirmOpen(true);
      return;
    }

    applyDailyStatus(pIndex, dateKey, newStatus, '');
  };

  const applyDailyStatus = (pIndex, dateKey, newStatus, reason = '') => {
    const updated = [...participantAttendance];
    const p = updated[pIndex];
    const currentDaily = { ...(p.daily_attendance || {}) };
    currentDaily[dateKey] = {
      status: newStatus,
      recorded_at: new Date().toISOString(),
      notes: reason,
    };
    p.daily_attendance = currentDaily;

    // Recalculate overall status:
    const sessionDates = selectedSessionForAttendance?.session_dates || [dateKey];
    const totalDays = sessionDates.length;
    let attendedDays = 0;
    let absentDays = 0;

    sessionDates.forEach((d) => {
      const st = currentDaily[d]?.status;
      if (st === 'ATTENDED') attendedDays++;
      if (st === 'ABSENT') absentDays++;
    });

    if (attendedDays === totalDays) {
      p.attendance_status = 'ATTENDED';
    } else if (absentDays === totalDays) {
      p.attendance_status = 'ABSENT';
    } else if (attendedDays > 0) {
      p.attendance_status = 'ATTENDED'; // Partial/attended minimum
    }

    setParticipantAttendance(updated);
  };

  const confirmAbsentChange = () => {
    if (!pendingAbsentChange) return;
    const { pIndex, dateKey, newStatus } = pendingAbsentChange;
    applyDailyStatus(pIndex, dateKey, newStatus, absentReason);
    setAbsentConfirmOpen(false);
    setPendingAbsentChange(null);
  };

  // Mark all participants attended for a specific day
  const handleMarkAllAttendedForDay = (dateKey) => {
    const updated = participantAttendance.map((p) => {
      const currentDaily = { ...(p.daily_attendance || {}) };
      currentDaily[dateKey] = {
        status: 'ATTENDED',
        recorded_at: new Date().toISOString(),
        notes: '',
      };
      return {
        ...p,
        daily_attendance: currentDaily,
        attendance_status: 'ATTENDED',
      };
    });
    setParticipantAttendance(updated);
  };

  const handleSaveAttendance = (e) => {
    e.preventDefault();
    if (!selectedSessionForAttendance) return;
    put(`/training/sessions/${selectedSessionForAttendance.id}/attendance`, {
      participants: participantAttendance.map((p) => ({
        id: p.id,
        attendance_status: p.attendance_status,
        daily_attendance: p.daily_attendance,
        notes: p.notes,
      })),
      onSuccess: () => {
        setAttendanceModalOpen(false);
        setSelectedSessionForAttendance(null);
      },
    });
  };

  // Add Participant via UserSelectModal
  const handleSelectUserToAdd = (selectedUser) => {
    if (!selectedSessionForAttendance || !selectedUser || !selectedUser.id) return;
    post(`/training/sessions/${selectedSessionForAttendance.id}/participants`, {
      user_id: selectedUser.id,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setAddParticipantOpen(false);
      },
    });
  };

  // Remove Participant
  const handleRemoveParticipant = (participantId, participantName) => {
    if (!selectedSessionForAttendance) return;
    if (confirm(`Are you sure you want to remove "${participantName}" from this session?`)) {
      router.delete(`/training/sessions/${selectedSessionForAttendance.id}/participants/${participantId}`, {
        onSuccess: () => {
          setAttendanceModalOpen(false);
        },
      });
    }
  };

  // Open Approve Modal
  const handleOpenApprove = (session) => {
    setSessionToApprove(session);
    setApprovalNotes('');
    setApproveModalOpen(true);
  };

  const handleConfirmApprove = () => {
    if (!sessionToApprove) return;
    post(`/training/sessions/${sessionToApprove.id}/approve`, {
      notes: approvalNotes,
    }, {
      onSuccess: () => {
        setApproveModalOpen(false);
        setSessionToApprove(null);
      },
    });
  };

  const getStatusBadge = (status) => {
    const map = {
      PENDING: { color: 'warning', label: 'PENDING APPROVAL' },
      OPEN: { color: 'info', label: 'OPEN / SCHEDULED' },
      IN_PROGRESS: { color: 'primary', label: 'IN PROGRESS' },
      COMPLETED: { color: 'success', label: 'COMPLETED' },
      CANCELLED: { color: 'error', label: 'CANCELLED' },
    };
    const s = map[status] || { color: 'default', label: status };
    return <Chip label={s.label} color={s.color} size="small" sx={{ fontWeight: 700, fontSize: '0.7rem' }} />;
  };

  const getScheduleStateChip = (state) => {
    if (state === 'ongoing') {
      return (
        <Chip
          label="⚡ Ongoing Today"
          size="small"
          className="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-extrabold text-[11px]"
        />
      );
    }
    if (state === 'upcoming') {
      return (
        <Chip
          label="📅 Upcoming"
          size="small"
          className="bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 font-extrabold text-[11px]"
        />
      );
    }
    if (state === 'expired') {
      return (
        <Chip
          label="⏳ Expired / Past"
          size="small"
          className="bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 font-extrabold text-[11px]"
        />
      );
    }
    return null;
  };

  return (
    <AsideLayout title="Training Sessions & Attendance">
      <Head title="Sessions & Attendance - Training Master" />

      <Box className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <ScheduleIcon className="text-sky-600" />
              Training Sessions & Multi-Day Attendance
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Manage shared group training sessions, record daily attendance, approve sessions to unlock tests, and export department announcements.
            </Typography>
          </div>

          {permissions.can_create !== false && (
            <Button
              variant="contained"
              startIcon={<AddIcon />}
              onClick={handleOpenCreate}
              sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
            >
              Create Shared Session
            </Button>
          )}
        </Box>

        {/* Schedule Filter Tabs & Filter Controls */}
        <Paper elevation={0} className="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 bg-white dark:bg-slate-900">
          <Box className="border-b border-slate-200 dark:border-slate-800 pb-2 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <Tabs
              value={timeFilter}
              onChange={(_, val) => {
                setTimeFilter(val);
                handleFilterChange('time_filter', val);
              }}
              variant="scrollable"
              scrollButtons="auto"
              sx={{ '& .MuiTab-root': { textTransform: 'none', fontWeight: 700, minHeight: 42 } }}
            >
              <Tab value="all" label="All Sessions" />
              <Tab value="ongoing" label="⚡ Ongoing Sessions" />
              <Tab value="upcoming" label="📅 Upcoming Sessions" />
              <Tab value="expired" label="⏳ Expired / Past" />
            </Tabs>

            <TextField
              size="small"
              placeholder="Search session code, title..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') handleFilterChange('search', searchQuery);
              }}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchIcon fontSize="small" className="text-slate-400" />
                  </InputAdornment>
                ),
              }}
              sx={{ minWidth: 240 }}
            />
          </Box>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <FormControl size="small" fullWidth>
              <InputLabel>Filter by Training Catalog</InputLabel>
              <Select
                value={trainingFilter}
                label="Filter by Training Catalog"
                onChange={(e) => {
                  setTrainingFilter(e.target.value);
                  handleFilterChange('training_id', e.target.value);
                }}
              >
                <MenuItem value="">All Training Catalogs</MenuItem>
                {trainings.map((t) => (
                  <MenuItem key={t.id} value={String(t.id)}>
                    {t.title} ({t.code})
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl size="small" fullWidth>
              <InputLabel>Filter by Trainer</InputLabel>
              <Select
                value={trainerFilter}
                label="Filter by Trainer"
                onChange={(e) => {
                  setTrainerFilter(e.target.value);
                  handleFilterChange('trainer_id', e.target.value);
                }}
              >
                <MenuItem value="">All Trainers</MenuItem>
                {trainers.map((tr) => (
                  <MenuItem key={tr.id} value={String(tr.id)}>
                    {tr.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl size="small" fullWidth>
              <InputLabel>Session Status</InputLabel>
              <Select
                value={statusFilter}
                label="Session Status"
                onChange={(e) => {
                  setStatusFilter(e.target.value);
                  handleFilterChange('status', e.target.value);
                }}
              >
                <MenuItem value="">All Statuses</MenuItem>
                {statuses.map((st) => (
                  <MenuItem key={st} value={st}>
                    {st}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </div>
        </Paper>

        {/* Sessions Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {(sessions.data || []).map((s) => (
            <Card
              key={s.id}
              elevation={0}
              className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 bg-white dark:bg-slate-900 flex flex-col justify-between overflow-hidden group"
            >
              {/* Card Header & Main Info */}
              <CardContent className="p-5 space-y-3.5">
                {/* Top Row: Code, Badge & More Actions Menu Button */}
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="text-[11px] font-mono font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                      {s.session_code}
                    </span>
                    {getScheduleStateChip(s.schedule_state)}
                    {s.parent_session && (
                      <Chip
                        icon={<HistoryIcon fontSize="small" />}
                        label={`Ref: ${s.parent_session.session_code}`}
                        size="small"
                        color="secondary"
                        sx={{ fontSize: '0.65rem', height: 20, fontWeight: 700 }}
                      />
                    )}
                  </div>

                  <div className="flex items-center gap-1">
                    {getStatusBadge(s.status)}
                    <IconButton
                      size="small"
                      onClick={(e) => handleOpenMenu(e, s)}
                      aria-label="session actions"
                      className="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
                    >
                      <MoreVertIcon fontSize="small" />
                    </IconButton>
                  </div>
                </div>

                {/* Session Title & Linked Training Catalog */}
                <div>
                  <Typography
                    variant="h6"
                    className="font-extrabold text-slate-900 dark:text-slate-100 leading-snug cursor-pointer hover:text-sky-600 dark:hover:text-sky-400 transition-colors"
                    onClick={() => handleOpenAttendance(s)}
                  >
                    {s.title}
                  </Typography>
                  <div className="text-xs text-sky-600 dark:text-sky-400 font-semibold mt-0.5">
                    {s.training?.title} ({s.training?.code})
                  </div>
                </div>

                {/* Key Metrics Strip (Schedule, Trainer, Trainees Count) */}
                <div className="grid grid-cols-2 gap-2 bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80 text-xs">
                  <div>
                    <span className="text-[10px] text-slate-400 uppercase tracking-wider font-bold block">
                      Schedule & Duration
                    </span>
                    <span className="font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1 mt-0.5">
                      <ScheduleIcon sx={{ fontSize: 13 }} className="text-slate-400" />
                      {s.duration_days || 1} Day(s) {s.start_date ? `• ${s.start_date}` : ''}
                    </span>
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 uppercase tracking-wider font-bold block">
                      Trainees
                    </span>
                    <span className="font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1 mt-0.5">
                      <PeopleIcon sx={{ fontSize: 13 }} className="text-emerald-500" />
                      {s.participants_count ?? 0} Trainee(s)
                    </span>
                  </div>
                </div>

                {/* Secondary Meta: Trainer & Venue */}
                <div className="space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                  <div className="flex items-center gap-1.5 truncate">
                    <Avatar sx={{ width: 18, height: 18, fontSize: '0.65rem', bgcolor: '#38bdf8' }}>
                      {s.trainer?.name ? s.trainer.name.charAt(0).toUpperCase() : 'T'}
                    </Avatar>
                    <span className="truncate">
                      Trainer: <b className="text-slate-800 dark:text-slate-100">{s.trainer?.name || 'Unassigned Trainer'}</b>
                    </span>
                  </div>

                  {s.venue && (
                    <div className="flex items-center gap-1.5 truncate">
                      <LocationIcon fontSize="inherit" className="text-slate-400" />
                      <span className="truncate">{s.venue}</span>
                    </div>
                  )}

                  {s.meeting_link && (
                    <div className="flex items-center gap-1.5 truncate">
                      <LinkIcon fontSize="inherit" className="text-slate-400" />
                      <a href={s.meeting_link} target="_blank" rel="noreferrer" className="text-sky-600 hover:underline truncate">
                        {s.meeting_link}
                      </a>
                    </div>
                  )}
                </div>

                {/* Approval & Test Release Status Banner */}
                {s.approved_at ? (
                  <div className="bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1.5 rounded-xl border border-emerald-200/80 dark:border-emerald-900/40 text-[11px] text-emerald-800 dark:text-emerald-300 flex items-center justify-between">
                    <span className="flex items-center gap-1.5 font-medium">
                      <VerifiedIcon sx={{ fontSize: 15 }} color="success" />
                      Approved (Tests Unlocked)
                    </span>
                    <span className="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono">
                      {s.approved_at.substring(0, 10)}
                    </span>
                  </div>
                ) : (
                  <div className="bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1.5 rounded-xl border border-amber-200/80 dark:border-amber-900/40 text-[11px] text-amber-800 dark:text-amber-300 flex items-center justify-between">
                    <span className="flex items-center gap-1.5 font-medium">
                      <HourglassEmptyIcon sx={{ fontSize: 14 }} color="warning" />
                      Pending Approval (Tests Locked)
                    </span>
                    {!s.approved_at && permissions.can_update !== false && (
                      <Button
                        size="small"
                        variant="text"
                        color="success"
                        onClick={() => handleOpenApprove(s)}
                        sx={{ fontSize: '10px', p: 0, minHeight: 18, textTransform: 'none', fontWeight: 800 }}
                      >
                        Approve Now
                      </Button>
                    )}
                  </div>
                )}
              </CardContent>

              {/* Minimal Card Footer: Primary Attendance Action + Quick Test Status Badge */}
              <Box className="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between gap-2">
                <Button
                  size="small"
                  variant="outlined"
                  color="primary"
                  startIcon={<AttendanceIcon sx={{ fontSize: 16 }} />}
                  onClick={() => handleOpenAttendance(s)}
                  sx={{
                    textTransform: 'none',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    borderRadius: '8px',
                    borderColor: '#93c5fd',
                    color: '#0284c7',
                    '&:hover': { borderColor: '#0284c7', backgroundColor: '#f0f9ff' }
                  }}
                >
                  Manage Attendance ({s.participants_count ?? 0})
                </Button>

                <Button
                  size="small"
                  variant="text"
                  color="info"
                  startIcon={<QuizIcon sx={{ fontSize: 16 }} />}
                  onClick={() => {
                    setSelectedSessionForTestStatus(s);
                    setTestStatusModalOpen(true);
                  }}
                  sx={{ textTransform: 'none', fontSize: '0.75rem', fontWeight: 700 }}
                >
                  Test Status
                </Button>
              </Box>
            </Card>
          ))}

          {(sessions.data || []).length === 0 && (
            <div className="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl">
              <ScheduleIcon sx={{ fontSize: 48 }} className="text-slate-300 mb-2" />
              <Typography variant="h6">No Training Sessions Found</Typography>
              <Typography variant="body2" className="mb-4">
                Sessions are automatically created when training is assigned or can be manually scheduled.
              </Typography>
              <Button variant="contained" startIcon={<AddIcon />} onClick={handleOpenCreate}>
                Create Shared Session
              </Button>
            </div>
          )}
        </div>

        {/* Create / Edit Session Dialog */}
        <Dialog open={modalOpen} onClose={handleClose} maxWidth="sm" fullWidth>
          <DialogTitle className="font-bold">
            {editingSession ? 'Edit Training Session' : 'Schedule Shared Training Session'}
          </DialogTitle>
          <form onSubmit={handleSubmit}>
            <DialogContent className="space-y-4">
              <TextField
                select
                required
                fullWidth
                disabled={Boolean(editingSession)}
                label="Training Module"
                value={data.training_id}
                onChange={(e) => {
                  const tId = e.target.value;
                  setData('training_id', tId);
                  const sel = trainings.find((t) => String(t.id) === String(tId));
                  if (sel) {
                    setData('title', `${sel.title} - Shared Session`);
                    setData('duration_days', sel.duration_days || 1);
                  }
                }}
              >
                {trainings.map((t) => (
                  <MenuItem key={t.id} value={String(t.id)}>
                    {t.title} ({t.code})
                  </MenuItem>
                ))}
              </TextField>

              <TextField
                fullWidth
                required
                label="Session Title"
                value={data.title}
                onChange={(e) => setData('title', e.target.value)}
                error={Boolean(errors.title)}
                helperText={errors.title}
              />

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <TextField
                  type="date"
                  required
                  fullWidth
                  label="Start Date"
                  InputLabelProps={{ shrink: true, sx: { bgcolor: 'background.paper', px: 0.5, borderRadius: 0.5 } }}
                  value={data.start_date}
                  onChange={(e) => {
                    const st = e.target.value;
                    setData('start_date', st);
                    if (st && data.duration_days) {
                      const d = new Date(st);
                      d.setDate(d.getDate() + parseInt(data.duration_days || 1, 10) - 1);
                      setData('end_date', d.toISOString().substring(0, 10));
                    }
                  }}
                />

                <TextField
                  type="number"
                  required
                  fullWidth
                  label="Duration (Days)"
                  inputProps={{ min: 1, max: 30 }}
                  value={data.duration_days}
                  onChange={(e) => {
                    const dur = parseInt(e.target.value || 1, 10);
                    setData('duration_days', dur);
                    if (data.start_date) {
                      const d = new Date(data.start_date);
                      d.setDate(d.getDate() + dur - 1);
                      setData('end_date', d.toISOString().substring(0, 10));
                    }
                  }}
                />

                <TextField
                  type="date"
                  fullWidth
                  label="End Date"
                  InputLabelProps={{ shrink: true, sx: { bgcolor: 'background.paper', px: 0.5, borderRadius: 0.5 } }}
                  value={data.end_date}
                  onChange={(e) => setData('end_date', e.target.value)}
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <TextField
                  select
                  fullWidth
                  label="Trainer / Instructor"
                  value={data.trainer_id}
                  onChange={(e) => setData('trainer_id', e.target.value)}
                >
                  <MenuItem value="">-- Unassigned --</MenuItem>
                  {trainers.map((tr) => (
                    <MenuItem key={tr.id} value={String(tr.id)}>
                      {tr.name}
                    </MenuItem>
                  ))}
                </TextField>

                <TextField
                  type="datetime-local"
                  fullWidth
                  label="Time & Schedule"
                  InputLabelProps={{ shrink: true, sx: { bgcolor: 'background.paper', px: 0.5, borderRadius: 0.5 } }}
                  value={data.scheduled_at}
                  onChange={(e) => setData('scheduled_at', e.target.value)}
                />
              </div>

              <TextField
                fullWidth
                label="Venue / Room Location"
                placeholder="e.g. Conference Room B, HQ Level 3"
                value={data.venue}
                onChange={(e) => setData('venue', e.target.value)}
              />

              <TextField
                fullWidth
                label="Online Meeting Link (Optional)"
                placeholder="https://meet.google.com/..."
                value={data.meeting_link}
                onChange={(e) => setData('meeting_link', e.target.value)}
              />

              <TextField
                select
                required
                fullWidth
                label="Initial Status"
                value={data.status}
                onChange={(e) => setData('status', e.target.value)}
              >
                <MenuItem value="PENDING">PENDING (Waiting Approval)</MenuItem>
                <MenuItem value="OPEN">OPEN (Approved & Open for attendance)</MenuItem>
                <MenuItem value="IN_PROGRESS">IN_PROGRESS (Currently Ongoing)</MenuItem>
                <MenuItem value="COMPLETED">COMPLETED (Finished)</MenuItem>
                <MenuItem value="CANCELLED">CANCELLED</MenuItem>
              </TextField>
            </DialogContent>

            <DialogActions className="p-4">
              <Button onClick={handleClose} disabled={processing}>
                Cancel
              </Button>
              <Button type="submit" variant="contained" disabled={processing} sx={{ fontWeight: 700 }}>
                {editingSession ? 'Update Session' : 'Save & Schedule'}
              </Button>
            </DialogActions>
          </form>
        </Dialog>

        {/* Multi-Day Attendance & Participant Control Modal */}
        <Dialog
          open={attendanceModalOpen}
          onClose={() => setAttendanceModalOpen(false)}
          maxWidth="lg"
          fullWidth
        >
          <DialogTitle className="font-bold flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
              <div className="flex items-center gap-2">
                <span className="text-base">Record Attendance: {selectedSessionForAttendance?.title}</span>
                {selectedSessionForAttendance && getStatusBadge(selectedSessionForAttendance.status)}
              </div>
              <Typography variant="caption" className="block text-slate-500">
                Session Code: <b>{selectedSessionForAttendance?.session_code}</b> • Duration: <b>{selectedSessionForAttendance?.duration_days || 1} Day(s)</b>
              </Typography>
            </div>

            <Stack direction="row" spacing={1}>
              {/* Add participant control before approved */}
              {!selectedSessionForAttendance?.approved_at && (
                <Button
                  size="small"
                  variant="outlined"
                  color="primary"
                  startIcon={<PersonAddIcon />}
                  onClick={() => setAddParticipantOpen(true)}
                  sx={{ textTransform: 'none', fontWeight: 700, fontSize: '0.75rem' }}
                >
                  + Add Participant
                </Button>
              )}

              <Button
                size="small"
                variant="outlined"
                color="secondary"
                startIcon={<PdfIcon />}
                onClick={() => exportSessionAnnouncementPdf(selectedSessionForAttendance)}
                sx={{ textTransform: 'none', fontWeight: 700, fontSize: '0.75rem' }}
              >
                PDF Announcement
              </Button>
            </Stack>
          </DialogTitle>

          <form onSubmit={handleSaveAttendance}>
            <DialogContent className="space-y-4 pt-4">
              <TableContainer className="border rounded-2xl overflow-x-auto">
                <Table size="small">
                  <TableHead className="bg-slate-100 dark:bg-slate-800">
                    <TableRow>
                      <TableCell className="font-bold text-xs" sx={{ width: 40 }}>#</TableCell>
                      <TableCell className="font-bold text-xs">Employee & Position</TableCell>

                      {/* Dynamic Date Columns */}
                      {(selectedSessionForAttendance?.session_dates || []).map((dateStr, dIdx) => (
                        <TableCell key={dateStr} align="center" className="font-bold text-xs min-w-[130px]">
                          <div className="font-bold text-sky-700 dark:text-sky-300">
                            Day {dIdx + 1}
                          </div>
                          <div className="text-[10px] text-slate-500 font-mono">
                            {dateStr}
                          </div>
                          <Button
                            size="small"
                            variant="text"
                            onClick={() => handleMarkAllAttendedForDay(dateStr)}
                            sx={{ fontSize: '9px', p: 0, minHeight: 18, textTransform: 'none' }}
                          >
                            Mark All Present
                          </Button>
                        </TableCell>
                      ))}

                      <TableCell className="font-bold text-xs" align="center">Overall Status</TableCell>
                      {!selectedSessionForAttendance?.approved_at && (
                        <TableCell className="font-bold text-xs" align="center" sx={{ width: 50 }}>Action</TableCell>
                      )}
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {participantAttendance.map((p, pIdx) => (
                      <TableRow key={p.id} hover>
                        <TableCell className="text-xs font-mono text-slate-400">
                          {pIdx + 1}
                        </TableCell>
                        <TableCell className="text-xs">
                          <div className="font-bold text-slate-900 dark:text-slate-100">
                            {p.user_name}
                          </div>
                          <div className="text-[11px] text-slate-500">
                            {p.department || '—'} • {p.office_position || 'Staff'}
                          </div>
                        </TableCell>

                        {/* Daily Status Cells */}
                        {(selectedSessionForAttendance?.session_dates || []).map((dateStr) => {
                          const dailyStatus = p.daily_attendance?.[dateStr]?.status || 'REGISTERED';
                          return (
                            <TableCell key={dateStr} align="center">
                              <Select
                                size="small"
                                value={dailyStatus}
                                onChange={(e) => handleDailyStatusChange(pIdx, dateStr, e.target.value)}
                                sx={{
                                  fontSize: '0.72rem',
                                  fontWeight: 700,
                                  height: 28,
                                  color: dailyStatus === 'ATTENDED' ? '#059669' : (dailyStatus === 'ABSENT' ? '#e11d48' : '#64748b'),
                                  backgroundColor: dailyStatus === 'ATTENDED' ? '#ecfdf5' : (dailyStatus === 'ABSENT' ? '#fff1f2' : '#f8fafc'),
                                }}
                              >
                                <MenuItem value="REGISTERED">Pending</MenuItem>
                                <MenuItem value="ATTENDED" sx={{ color: '#059669', fontWeight: 700 }}>🟢 Attended</MenuItem>
                                <MenuItem value="ABSENT" sx={{ color: '#e11d48', fontWeight: 700 }}>🔴 Absent</MenuItem>
                                <MenuItem value="EXCUSED" sx={{ color: '#d97706' }}>🟡 Excused</MenuItem>
                              </Select>
                              {p.daily_attendance?.[dateStr]?.notes && (
                                <div className="text-[10px] text-rose-600 truncate max-w-[120px] mt-0.5">
                                  {p.daily_attendance[dateStr].notes}
                                </div>
                              )}
                            </TableCell>
                          );
                        })}

                        {/* Overall Status Badge */}
                        <TableCell align="center">
                          <Chip
                            label={p.attendance_status}
                            size="small"
                            color={p.attendance_status === 'ATTENDED' ? 'success' : (p.attendance_status === 'ABSENT' ? 'error' : 'default')}
                            sx={{ fontWeight: 700, fontSize: '0.65rem' }}
                          />
                        </TableCell>

                        {/* Remove participant control before approved */}
                        {!selectedSessionForAttendance?.approved_at && (
                          <TableCell align="center">
                            <Tooltip title="Remove participant from session">
                              <IconButton
                                size="small"
                                color="error"
                                onClick={() => handleRemoveParticipant(p.id, p.user_name)}
                              >
                                <DeleteIcon fontSize="small" />
                              </IconButton>
                            </Tooltip>
                          </TableCell>
                        )}
                      </TableRow>
                    ))}

                    {participantAttendance.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={6} align="center" className="text-slate-400 py-6">
                          No registered participants in this session. Click <b>+ Add Participant</b> to register employees.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </TableContainer>
            </DialogContent>

            <DialogActions className="p-4 border-t border-slate-200 dark:border-slate-800">
              <Button onClick={() => setAttendanceModalOpen(false)}>Close</Button>
              <Button type="submit" variant="contained" color="primary" sx={{ fontWeight: 700 }}>
                Save Multi-Day Attendance
              </Button>
            </DialogActions>
          </form>
        </Dialog>

        {/* Confirm Absent Dialog */}
        <Dialog open={absentConfirmOpen} onClose={() => setAbsentConfirmOpen(false)} maxWidth="xs" fullWidth>
          <DialogTitle className="font-bold flex items-center gap-2 text-rose-600">
            <WarningIcon color="error" />
            Confirm Marking Absent
          </DialogTitle>
          <DialogContent className="space-y-3 pt-2">
            <Typography variant="body2">
              Are you sure you want to mark <b>{pendingAbsentChange?.participantName}</b> as <b>ABSENT</b> on <b>{pendingAbsentChange?.dateKey}</b>?
            </Typography>
            <TextField
              fullWidth
              multiline
              rows={2}
              label="Absence Reason / Notice (Optional)"
              placeholder="e.g. Medical leave, emergency absent without notice..."
              value={absentReason}
              onChange={(e) => setAbsentReason(e.target.value)}
            />
          </DialogContent>
          <DialogActions className="p-3">
            <Button onClick={() => setAbsentConfirmOpen(false)}>Cancel</Button>
            <Button variant="contained" color="error" onClick={confirmAbsentChange} sx={{ fontWeight: 700 }}>
              Confirm Absent
            </Button>
          </DialogActions>
        </Dialog>

        {/* Employee Select Modal for Adding Participant to Session (Reused from IT Issues) */}
        <UserSelectModal
          open={addParticipantOpen}
          onClose={() => setAddParticipantOpen(false)}
          onSelect={handleSelectUserToAdd}
          title="Add Participant to Session"
          subtitle={`Search and select an employee to register for session: ${selectedSessionForAttendance?.session_code || ''}`}
          users={allActiveUsers}
          departments={departments}
          currentUserName={auth_user?.name || ''}
        />

        {/* Card Actions Overflow Menu */}
        <Menu
          anchorEl={menuAnchorEl}
          open={Boolean(menuAnchorEl)}
          onClose={handleCloseMenu}
          PaperProps={{
            elevation: 3,
            sx: {
              borderRadius: '12px',
              minWidth: 190,
              boxShadow: '0 10px 25px -5px rgba(0,0,0,0.15)',
              border: '1px solid #e2e8f0',
              mt: 0.5,
            },
          }}
          transformOrigin={{ horizontal: 'right', vertical: 'top' }}
          anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
        >
          {/* Approve Session (if pending approval) */}
          {activeMenuSession && !activeMenuSession.approved_at && (
            <MenuItem
              onClick={() => {
                const s = activeMenuSession;
                handleCloseMenu();
                handleOpenApprove(s);
              }}
              sx={{ py: 1, fontSize: '0.82rem', fontWeight: 600, color: '#15803d' }}
            >
              <ListItemIcon sx={{ minWidth: 30, color: '#16a34a' }}>
                <VerifiedIcon fontSize="small" />
              </ListItemIcon>
              <ListItemText primary="Approve Session" primaryTypographyProps={{ fontSize: '0.82rem', fontWeight: 600 }} />
            </MenuItem>
          )}

          {/* Announcement PDF */}
          <MenuItem
            onClick={() => {
              const s = activeMenuSession;
              handleCloseMenu();
              exportSessionAnnouncementPdf(s);
            }}
            sx={{ py: 1, fontSize: '0.82rem', fontWeight: 500 }}
          >
            <ListItemIcon sx={{ minWidth: 30, color: '#9333ea' }}>
              <PdfIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primary="Announcement PDF" primaryTypographyProps={{ fontSize: '0.82rem' }} />
          </MenuItem>

          {/* Edit Session */}
          {permissions.can_update !== false && (
            <MenuItem
              onClick={() => {
                const s = activeMenuSession;
                handleCloseMenu();
                handleOpenEdit(s);
              }}
              sx={{ py: 1, fontSize: '0.82rem', fontWeight: 500 }}
            >
              <ListItemIcon sx={{ minWidth: 30, color: '#0284c7' }}>
                <EditIcon fontSize="small" />
              </ListItemIcon>
              <ListItemText primary="Edit Details" primaryTypographyProps={{ fontSize: '0.82rem' }} />
            </MenuItem>
          )}

          <Divider sx={{ my: 0.5 }} />

          {/* Delete Session */}
          {permissions.can_delete !== false && (
            <MenuItem
              disabled={activeMenuSession?.attempt_results_count > 0}
              onClick={() => {
                const s = activeMenuSession;
                handleCloseMenu();
                handleDeleteSession(s);
              }}
              sx={{
                py: 1,
                fontSize: '0.82rem',
                fontWeight: 600,
                color: activeMenuSession?.attempt_results_count > 0 ? '#94a3b8' : '#e11d48',
              }}
            >
              <ListItemIcon sx={{ minWidth: 30, color: activeMenuSession?.attempt_results_count > 0 ? '#cbd5e1' : '#e11d48' }}>
                <DeleteIcon fontSize="small" />
              </ListItemIcon>
              <ListItemText
                primary={activeMenuSession?.attempt_results_count > 0 ? "Cannot Delete (Has Results)" : "Delete Session"}
                primaryTypographyProps={{ fontSize: '0.82rem', fontWeight: 600 }}
              />
            </MenuItem>
          )}
        </Menu>

        {/* Approve Session Dialog */}
        <Dialog open={approveModalOpen} onClose={() => setApproveModalOpen(false)} maxWidth="sm" fullWidth>
          <DialogTitle className="font-bold flex items-center gap-2 text-emerald-700">
            <VerifiedIcon color="success" />
            Approve Training Session
          </DialogTitle>
          <DialogContent className="space-y-3 pt-2">
            <Typography variant="body2">
              You are approving session: <b>{sessionToApprove?.title}</b> ({sessionToApprove?.session_code}).
            </Typography>

            <Paper variant="outlined" className="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-xs text-emerald-900 dark:text-emerald-200">
              <b>Important Notice:</b> Approving this session will officially verify training execution and <b>unlock the test template</b> for all participating employees who were not marked absent.
            </Paper>

            <TextField
              fullWidth
              multiline
              rows={3}
              label="Trainer / Approver Remarks (Optional)"
              placeholder="e.g. Session successfully delivered. Trainees completed practical exercises..."
              value={approvalNotes}
              onChange={(e) => setApprovalNotes(e.target.value)}
            />
          </DialogContent>
          <DialogActions className="p-3">
            <Button onClick={() => setApproveModalOpen(false)}>Cancel</Button>
            <Button variant="contained" color="success" onClick={handleConfirmApprove} sx={{ fontWeight: 700 }}>
              Approve & Unlock Tests
            </Button>
          </DialogActions>
        </Dialog>

        {/* Test Template & Answering Status Modal */}
        <Dialog
          open={testStatusModalOpen}
          onClose={() => setTestStatusModalOpen(false)}
          maxWidth="md"
          fullWidth
        >
          <DialogTitle className="font-bold flex items-center justify-between border-b border-slate-100 dark:border-slate-800">
            <div className="flex items-center gap-2 text-sky-700 dark:text-sky-400">
              <QuizIcon />
              <span>Assessment Test Status: {selectedSessionForTestStatus?.title}</span>
            </div>
            <Chip
              label={selectedSessionForTestStatus?.session_code}
              size="small"
              variant="outlined"
              sx={{ fontWeight: 700 }}
            />
          </DialogTitle>
          <DialogContent className="space-y-4 pt-4">
            {/* Header info banner */}
            <div className="flex flex-wrap items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60">
              <div>
                <div className="text-xs text-slate-500">Training Module</div>
                <div className="text-sm font-bold text-slate-900 dark:text-slate-100">
                  {selectedSessionForTestStatus?.training?.title} ({selectedSessionForTestStatus?.training?.code})
                </div>
              </div>

              <div>
                <div className="text-xs text-slate-500">Test Template Access</div>
                {selectedSessionForTestStatus?.approved_at ? (
                  <Chip
                    size="small"
                    color="success"
                    label="Unlocked (Session Approved)"
                    sx={{ fontWeight: 700, fontSize: '0.72rem' }}
                  />
                ) : (
                  <Chip
                    size="small"
                    color="warning"
                    label="Locked (Awaiting Trainer Approval)"
                    sx={{ fontWeight: 700, fontSize: '0.72rem' }}
                  />
                )}
              </div>

              <div>
                <div className="text-xs text-slate-500">Passing Threshold</div>
                <div className="text-sm font-bold text-slate-800 dark:text-slate-200">
                  {selectedSessionForTestStatus?.training?.passing_score ?? 80}%
                </div>
              </div>
            </div>

            {/* Participants Test Status Table */}
            <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
              <Table size="small">
                <TableHead className="bg-slate-100 dark:bg-slate-800/80">
                  <TableRow>
                    <TableCell className="font-bold text-xs py-2.5">Participant</TableCell>
                    <TableCell className="font-bold text-xs py-2.5">Department</TableCell>
                    <TableCell className="font-bold text-xs py-2.5 text-center">Attendance</TableCell>
                    <TableCell className="font-bold text-xs py-2.5 text-center">Test Status</TableCell>
                    <TableCell className="font-bold text-xs py-2.5 text-center">Score / Result</TableCell>
                    <TableCell className="font-bold text-xs py-2.5 text-center">Attempts</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {(selectedSessionForTestStatus?.participants || []).map((p) => {
                    const attempts = p.assignment?.testAttempts || [];
                    const latestAttempt = p.assignment?.latestAttempt || attempts[0];
                    const hasSubmitted = latestAttempt && latestAttempt.submitted_at;
                    const isPending = !hasSubmitted;

                    return (
                      <TableRow key={p.id} hover>
                        <TableCell className="py-2.5">
                          <div className="flex items-center gap-2">
                            <Avatar sx={{ width: 28, height: 28, fontSize: '0.75rem', bgcolor: 'primary.main', fontWeight: 700 }}>
                              {p.user?.name?.charAt(0) || 'U'}
                            </Avatar>
                            <div>
                              <div className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                {p.user?.name}
                              </div>
                              <div className="text-[11px] text-slate-400">{p.user?.email}</div>
                            </div>
                          </div>
                        </TableCell>

                        <TableCell className="text-xs py-2.5">
                          <div className="text-slate-700 dark:text-slate-300 font-medium">
                            {p.user?.department?.name || 'Unassigned'}
                          </div>
                          <div className="text-[11px] text-slate-400">
                            {p.user?.office_position?.name || 'Unassigned'}
                          </div>
                        </TableCell>

                        <TableCell align="center" className="py-2.5">
                          <Chip
                            size="small"
                            label={p.attendance_status}
                            color={
                              p.attendance_status === 'ATTENDED'
                                ? 'success'
                                : p.attendance_status === 'ABSENT'
                                ? 'error'
                                : 'default'
                            }
                            sx={{ fontSize: '0.7rem', fontWeight: 700 }}
                          />
                        </TableCell>

                        <TableCell align="center" className="py-2.5">
                          {isPending ? (
                            <Chip
                              size="small"
                              label="PENDING TEST"
                              color="warning"
                              variant="outlined"
                              sx={{ fontSize: '0.7rem', fontWeight: 700 }}
                            />
                          ) : latestAttempt?.result === 'PASSED' ? (
                            <Chip
                              size="small"
                              label="PASSED"
                              color="success"
                              sx={{ fontSize: '0.7rem', fontWeight: 700 }}
                            />
                          ) : (
                            <Chip
                              size="small"
                              label="FAILED"
                              color="error"
                              sx={{ fontSize: '0.7rem', fontWeight: 700 }}
                            />
                          )}
                        </TableCell>

                        <TableCell align="center" className="py-2.5">
                          {hasSubmitted ? (
                            <div>
                              <span
                                className={`text-xs font-bold ${
                                  latestAttempt.result === 'PASSED' ? 'text-emerald-600' : 'text-rose-600'
                                }`}
                              >
                                {latestAttempt.percentage}%
                              </span>
                              <div className="text-[10px] text-slate-400">
                                {latestAttempt.score} / {latestAttempt.max_score} pts
                              </div>
                            </div>
                          ) : (
                            <span className="text-xs text-slate-400 italic">Not taken yet</span>
                          )}
                        </TableCell>

                        <TableCell align="center" className="py-2.5">
                          <span className="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">
                            {attempts.length}
                          </span>
                        </TableCell>
                      </TableRow>
                    );
                  })}

                  {(selectedSessionForTestStatus?.participants || []).length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} align="center" className="py-8 text-slate-400 text-xs">
                        No participants registered in this session.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </DialogContent>
          <DialogActions className="p-3 border-t border-slate-100 dark:border-slate-800">
            <Button onClick={() => setTestStatusModalOpen(false)}>Close</Button>
          </DialogActions>
        </Dialog>
      </Box>
    </AsideLayout>
  );
}
