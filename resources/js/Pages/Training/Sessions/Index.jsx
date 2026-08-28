import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
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
  RadioGroup,
  FormControlLabel,
  Radio
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
  AssignmentTurnedIn as AttendanceIcon
} from '@mui/icons-material';

export default function SessionsIndex({
  sessions = {},
  trainings = [],
  trainers = [],
  statuses = [],
  filters = {}
}) {
  const [modalOpen, setModalOpen] = useState(false);
  const [editingSession, setEditingSession] = useState(null);
  const [attendanceModalOpen, setAttendanceModalOpen] = useState(false);
  const [selectedSessionForAttendance, setSelectedSessionForAttendance] = useState(null);
  const [participantAttendance, setParticipantAttendance] = useState([]);

  const { data, setData, post, put, processing, errors, reset } = useForm({
    training_id: '',
    trainer_id: '',
    title: '',
    session_code: '',
    scheduled_at: '',
    venue: '',
    meeting_link: '',
    status: 'PENDING',
  });

  const handleOpenCreate = () => {
    setEditingSession(null);
    reset();
    setData({
      training_id: trainings[0]?.id ? String(trainings[0].id) : '',
      trainer_id: '',
      title: '',
      session_code: '',
      scheduled_at: '',
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

  const handleOpenAttendance = (session) => {
    setSelectedSessionForAttendance(session);
    setParticipantAttendance(
      (session.participants || []).map((p) => ({
        id: p.id,
        user_name: p.user?.name,
        department: p.user?.department?.name,
        office_position: p.user?.office_position?.name,
        attendance_status: p.attendance_status || 'REGISTERED',
        notes: p.notes || '',
      }))
    );
    setAttendanceModalOpen(true);
  };

  const handleAttendanceChange = (index, status) => {
    const updated = [...participantAttendance];
    updated[index].attendance_status = status;
    setParticipantAttendance(updated);
  };

  const handleSaveAttendance = (e) => {
    e.preventDefault();
    if (!selectedSessionForAttendance) return;
    put(`/training/sessions/${selectedSessionForAttendance.id}/attendance`, {
      participants: participantAttendance.map((p) => ({
        id: p.id,
        attendance_status: p.attendance_status,
        notes: p.notes,
      })),
      onSuccess: () => {
        setAttendanceModalOpen(false);
        setSelectedSessionForAttendance(null);
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

  return (
    <AsideLayout title="Training Sessions & Attendance">
      <Head title="Sessions & Attendance - Training Master" />

      <Box className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <ScheduleIcon className="text-sky-600" />
              Training Sessions & Attendance
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Manage scheduled training sessions, assign trainers, and track attendee attendance records.
            </Typography>
          </div>

          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={handleOpenCreate}
            sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
          >
            Create Session
          </Button>
        </Box>

        {/* Sessions List */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {(sessions.data || []).map((s) => (
            <Card
              key={s.id}
              elevation={0}
              className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-shadow bg-white dark:bg-slate-900 flex flex-col justify-between"
            >
              <CardContent className="p-5 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <span className="text-[11px] font-mono font-bold text-slate-500">
                      {s.session_code}
                    </span>
                    <Typography variant="h6" className="font-extrabold text-slate-900 dark:text-slate-100 leading-snug">
                      {s.title}
                    </Typography>
                  </div>
                  {getStatusBadge(s.status)}
                </div>

                <div className="text-xs text-sky-600 dark:text-sky-400 font-bold">
                  {s.training?.title} ({s.training?.code})
                </div>

                <Divider />

                {/* Session Details */}
                <div className="space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                  <div className="flex items-center gap-1.5">
                    <ScheduleIcon fontSize="inherit" className="text-slate-400" />
                    <span>
                      {s.scheduled_at ? new Date(s.scheduled_at).toLocaleString() : 'Not Scheduled'}
                    </span>
                  </div>

                  {s.venue && (
                    <div className="flex items-center gap-1.5">
                      <LocationIcon fontSize="inherit" className="text-slate-400" />
                      <span>{s.venue}</span>
                    </div>
                  )}

                  {s.meeting_link && (
                    <div className="flex items-center gap-1.5">
                      <LinkIcon fontSize="inherit" className="text-slate-400" />
                      <a href={s.meeting_link} target="_blank" rel="noreferrer" className="text-sky-600 underline truncate max-w-[200px]">
                        {s.meeting_link}
                      </a>
                    </div>
                  )}

                  <div className="flex items-center gap-1.5">
                    <PeopleIcon fontSize="inherit" className="text-slate-400" />
                    <span>
                      Trainer: <b>{s.trainer?.name || 'Unassigned Trainer'}</b>
                    </span>
                  </div>

                  <div className="flex items-center gap-1.5">
                    <AttendanceIcon fontSize="inherit" className="text-emerald-500" />
                    <span>
                      Participants: <b>{s.participants_count ?? 0} employee(s)</b>
                    </span>
                  </div>
                </div>
              </CardContent>

              {/* Status Action Buttons & Attendance */}
              <Box className="p-4 pt-0 space-y-2 border-t border-slate-100 dark:border-slate-800 mt-2">
                <div className="flex items-center justify-between gap-1 pt-2">
                  {/* Status Transitions */}
                  {s.status === 'PENDING' && (
                    <Button
                      size="small"
                      variant="outlined"
                      color="info"
                      startIcon={<OpenIcon />}
                      onClick={() => handleUpdateStatus(s, 'OPEN')}
                      sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                    >
                      Approve & Open
                    </Button>
                  )}

                  {s.status === 'OPEN' && (
                    <Button
                      size="small"
                      variant="contained"
                      color="primary"
                      startIcon={<PlayIcon />}
                      onClick={() => handleUpdateStatus(s, 'IN_PROGRESS')}
                      sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                    >
                      Start Session
                    </Button>
                  )}

                  {s.status === 'IN_PROGRESS' && (
                    <Button
                      size="small"
                      variant="contained"
                      color="success"
                      startIcon={<CheckCircleIcon />}
                      onClick={() => handleUpdateStatus(s, 'COMPLETED')}
                      sx={{ textTransform: 'none', fontSize: '0.75rem' }}
                    >
                      Finish Session
                    </Button>
                  )}

                  <Button
                    size="small"
                    variant="outlined"
                    color="secondary"
                    startIcon={<AttendanceIcon />}
                    onClick={() => handleOpenAttendance(s)}
                    sx={{ textTransform: 'none', fontSize: '0.75rem', fontWeight: 600 }}
                  >
                    Attendance ({s.participants_count ?? 0})
                  </Button>

                  <Tooltip title="Edit Session">
                    <IconButton size="small" onClick={() => handleOpenEdit(s)}>
                      <EditIcon fontSize="small" />
                    </IconButton>
                  </Tooltip>
                </div>
              </Box>
            </Card>
          ))}

          {(sessions.data || []).length === 0 && (
            <div className="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl">
              <ScheduleIcon sx={{ fontSize: 48 }} className="text-slate-300 mb-2" />
              <Typography variant="h6">No Training Sessions</Typography>
              <Typography variant="body2" className="mb-4">
                Sessions are automatically provisioned when training assignments are generated or can be manually scheduled.
              </Typography>
              <Button variant="contained" startIcon={<AddIcon />} onClick={handleOpenCreate}>
                Create Session
              </Button>
            </div>
          )}
        </div>

        {/* Create / Edit Session Dialog */}
        <Dialog open={modalOpen} onClose={handleClose} maxWidth="sm" fullWidth>
          <DialogTitle className="font-bold">
            {editingSession ? 'Edit Training Session' : 'Schedule Training Session'}
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
                onChange={(e) => setData('training_id', e.target.value)}
                error={Boolean(errors.training_id)}
              >
                {trainings.map((t) => (
                  <MenuItem key={t.id} value={String(t.id)}>
                    {t.code} - {t.title}
                  </MenuItem>
                ))}
              </TextField>

              <TextField
                required
                fullWidth
                label="Session Title"
                placeholder="e.g. Odoo Sales Workflow - Q3 Batch"
                value={data.title}
                onChange={(e) => setData('title', e.target.value)}
                error={Boolean(errors.title)}
                helperText={errors.title}
              />

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
                  label="Scheduled Date & Time"
                  InputLabelProps={{ shrink: true }}
                  value={data.scheduled_at}
                  onChange={(e) => setData('scheduled_at', e.target.value)}
                />
              </div>

              <TextField
                fullWidth
                label="Venue / Room Location"
                placeholder="e.g. Training Room B, HQ Level 3"
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

        {/* Record Attendance Modal */}
        <Dialog
          open={attendanceModalOpen}
          onClose={() => setAttendanceModalOpen(false)}
          maxWidth="md"
          fullWidth
        >
          <DialogTitle className="font-bold flex items-center justify-between">
            <div>
              <span>Record Attendance: {selectedSessionForAttendance?.title}</span>
              <Typography variant="caption" className="block text-slate-500">
                Marking ATTENDED will satisfy Tier 1 of the employee requirement.
              </Typography>
            </div>
            {selectedSessionForAttendance && getStatusBadge(selectedSessionForAttendance.status)}
          </DialogTitle>

          <form onSubmit={handleSaveAttendance}>
            <DialogContent className="space-y-4">
              <TableContainer className="border rounded-xl">
                <Table size="small">
                  <TableHead className="bg-slate-100 dark:bg-slate-800">
                    <TableRow>
                      <TableCell className="font-bold text-xs">Employee</TableCell>
                      <TableCell className="font-bold text-xs">Dept & Position</TableCell>
                      <TableCell className="font-bold text-xs">Attendance Status</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {participantAttendance.map((p, idx) => (
                      <TableRow key={p.id} hover>
                        <TableCell className="font-bold text-xs">
                          {p.user_name}
                        </TableCell>
                        <TableCell className="text-xs text-slate-500">
                          {p.department || '—'} / {p.office_position || '—'}
                        </TableCell>
                        <TableCell>
                          <RadioGroup
                            row
                            value={p.attendance_status}
                            onChange={(e) => handleAttendanceChange(idx, e.target.value)}
                          >
                            <FormControlLabel
                              value="ATTENDED"
                              control={<Radio size="small" color="success" />}
                              label={<span className="text-xs font-bold text-emerald-600">Attended</span>}
                            />
                            <FormControlLabel
                              value="REGISTERED"
                              control={<Radio size="small" color="primary" />}
                              label={<span className="text-xs text-slate-600">Registered</span>}
                            />
                            <FormControlLabel
                              value="ABSENT"
                              control={<Radio size="small" color="error" />}
                              label={<span className="text-xs text-rose-600">Absent</span>}
                            />
                            <FormControlLabel
                              value="EXCUSED"
                              control={<Radio size="small" />}
                              label={<span className="text-xs text-slate-400">Excused</span>}
                            />
                          </RadioGroup>
                        </TableCell>
                      </TableRow>
                    ))}

                    {participantAttendance.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={3} align="center" className="text-slate-400 py-6">
                          No registered participants in this session.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </TableContainer>
            </DialogContent>

            <DialogActions className="p-4">
              <Button onClick={() => setAttendanceModalOpen(false)}>Close</Button>
              <Button type="submit" variant="contained" color="primary" sx={{ fontWeight: 700 }}>
                Save Attendance
              </Button>
            </DialogActions>
          </form>
        </Dialog>
      </Box>
    </AsideLayout>
  );
}
