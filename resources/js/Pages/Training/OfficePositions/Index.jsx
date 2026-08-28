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
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  IconButton,
  Tooltip,
  Chip,
  Stack,
  Checkbox,
  FormControlLabel,
  Paper,
  Avatar,
  Divider,
  InputAdornment
} from '@mui/material';

import {
  Badge as BadgeIcon,
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Search as SearchIcon,
  Business as BusinessIcon,
  GroupAdd as GroupAddIcon,
  Person as PersonIcon,
  CheckCircle as CheckCircleIcon,
  Clear as ClearIcon
} from '@mui/icons-material';

export default function OfficePositionsIndex({
  positions = {},
  allUsers = [],
  filters = {}
}) {
  const [modalOpen, setModalOpen] = useState(false);
  const [editingPosition, setEditingPosition] = useState(null);
  const [assignModalOpen, setAssignModalOpen] = useState(false);
  const [targetPosition, setTargetPosition] = useState(null);
  const [userSearch, setUserSearch] = useState('');
  const [selectedUserIds, setSelectedUserIds] = useState([]);

  const [search, setSearch] = useState(filters.search || '');

  const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
    name: '',
    description: '',
  });

  const handleOpenCreate = () => {
    setEditingPosition(null);
    reset();
    setModalOpen(true);
  };

  const handleOpenEdit = (pos) => {
    setEditingPosition(pos);
    setData({
      name: pos.name,
      description: pos.description || '',
    });
    setModalOpen(true);
  };

  const handleClose = () => {
    setModalOpen(false);
    setEditingPosition(null);
    reset();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editingPosition) {
      put(`/training/office-positions/${editingPosition.id}`, {
        onSuccess: () => handleClose(),
      });
    } else {
      post('/training/office-positions', {
        onSuccess: () => handleClose(),
      });
    }
  };

  const handleDelete = (pos) => {
    if (confirm(`Are you sure you want to delete office position "${pos.name}"?`)) {
      destroy(`/training/office-positions/${pos.id}`);
    }
  };

  const handleSearch = (e) => {
    if (e) e.preventDefault();
    router.get('/training/office-positions', { search }, { preserveState: true });
  };

  // Manage Users in Position
  const handleOpenAssign = (pos) => {
    setTargetPosition(pos);
    setUserSearch('');
    // Initialize selected users from the position's assigned users or allUsers
    const currentAssigned = allUsers
      .filter((u) => u.office_position_id === pos.id)
      .map((u) => u.id);
    setSelectedUserIds(currentAssigned);
    setAssignModalOpen(true);
  };

  const toggleUserSelection = (userId) => {
    setSelectedUserIds((prev) =>
      prev.includes(userId) ? prev.filter((id) => id !== userId) : [...prev, userId]
    );
  };

  const handleSaveAssignedUsers = (e) => {
    e.preventDefault();
    if (!targetPosition) return;

    router.post(
      `/training/office-positions/${targetPosition.id}/assign-users`,
      {
        user_ids: selectedUserIds,
      },
      {
        onSuccess: () => {
          setAssignModalOpen(false);
          setTargetPosition(null);
        },
      }
    );
  };

  const filteredUsers = allUsers.filter((u) => {
    if (!userSearch) return true;
    const q = userSearch.toLowerCase();
    return u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
  });

  return (
    <AsideLayout title="Office Positions Management">
      <Head title="Office Positions - Training Master" />

      <Box className="max-w-6xl mx-auto space-y-6">
        {/* Header */}
        <Box className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <Typography variant="h5" className="font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <BadgeIcon className="text-sky-600" />
              Office Positions
            </Typography>
            <Typography variant="body2" className="text-slate-500 dark:text-slate-400">
              Manage organizational job titles, assign active workforce employees, and automate scoped training workflows.
            </Typography>
          </div>

          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={handleOpenCreate}
            sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
          >
            New Office Position
          </Button>
        </Box>

        {/* Filters */}
        <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
          <CardContent className="p-4">
            <form onSubmit={handleSearch} className="flex flex-col sm:flex-row items-center gap-3">
              <TextField
                size="small"
                placeholder="Search position title..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full sm:w-80"
              />

              <Button
                type="submit"
                variant="outlined"
                startIcon={<SearchIcon />}
                sx={{ textTransform: 'none' }}
              >
                Search
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Positions Table */}
        <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm bg-white dark:bg-slate-900">
          <TableContainer>
            <Table>
              <TableHead className="bg-slate-100 dark:bg-slate-800">
                <TableRow>
                  <TableCell className="font-bold text-xs">Position Name</TableCell>
                  <TableCell className="font-bold text-xs">Description</TableCell>
                  <TableCell className="font-bold text-xs text-center">Active Employees</TableCell>
                  <TableCell className="font-bold text-xs text-right">Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {(positions.data || []).map((pos) => (
                  <TableRow key={pos.id} hover>
                    <TableCell className="font-bold text-slate-800 dark:text-slate-200">
                      {pos.name}
                    </TableCell>
                    <TableCell className="text-xs text-slate-500 max-w-xs truncate">
                      {pos.description || '—'}
                    </TableCell>

                    {/* Active Employees Column with Quick Assign Action */}
                    <TableCell align="center">
                      <Button
                        size="small"
                        variant={pos.users_count > 0 ? 'outlined' : 'text'}
                        color={pos.users_count > 0 ? 'primary' : 'inherit'}
                        startIcon={<PersonIcon fontSize="small" />}
                        onClick={() => handleOpenAssign(pos)}
                        sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2, fontSize: '0.75rem' }}
                      >
                        {pos.users_count ?? 0} Employee(s)
                      </Button>
                    </TableCell>

                    <TableCell align="right">
                      <Stack direction="row" spacing={1} justifyContent="flex-end">
                        <Button
                          size="small"
                          variant="outlined"
                          color="secondary"
                          startIcon={<GroupAddIcon />}
                          onClick={() => handleOpenAssign(pos)}
                          sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem', fontWeight: 600 }}
                        >
                          Assign Users
                        </Button>

                        <Tooltip title="Edit Position Details">
                          <IconButton size="small" onClick={() => handleOpenEdit(pos)}>
                            <EditIcon fontSize="small" />
                          </IconButton>
                        </Tooltip>

                        <Tooltip title="Delete Position">
                          <IconButton size="small" color="error" onClick={() => handleDelete(pos)}>
                            <DeleteIcon fontSize="small" />
                          </IconButton>
                        </Tooltip>
                      </Stack>
                    </TableCell>
                  </TableRow>
                ))}

                {(positions.data || []).length === 0 && (
                  <TableRow>
                    <TableCell colSpan={5} align="center" className="text-slate-400 py-8">
                      No office positions found. Click "New Office Position" to create one.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </TableContainer>
        </Card>

        {/* Create / Edit Dialog */}
        <Dialog open={modalOpen} onClose={handleClose} maxWidth="sm" fullWidth>
          <DialogTitle className="font-bold">
            {editingPosition ? 'Edit Office Position' : 'Create New Office Position'}
          </DialogTitle>
          <form onSubmit={handleSubmit}>
            <DialogContent className="space-y-4">
              <TextField
                required
                fullWidth
                label="Position Title"
                placeholder="e.g. Sales Staff, Supervisor, Cashier, Branch Manager"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                error={Boolean(errors.name)}
                helperText={errors.name}
              />

              <TextField
                fullWidth
                multiline
                rows={3}
                label="Description & Responsibilities"
                value={data.description}
                onChange={(e) => setData('description', e.target.value)}
                error={Boolean(errors.description)}
                helperText={errors.description}
              />
            </DialogContent>
            <DialogActions className="p-4">
              <Button onClick={handleClose} disabled={processing} sx={{ textTransform: 'none' }}>
                Cancel
              </Button>
              <Button
                type="submit"
                variant="contained"
                disabled={processing}
                sx={{ textTransform: 'none', fontWeight: 700 }}
              >
                {editingPosition ? 'Update Position' : 'Create Position'}
              </Button>
            </DialogActions>
          </form>
        </Dialog>

        {/* Assign Active Users Modal */}
        <Dialog
          open={assignModalOpen}
          onClose={() => setAssignModalOpen(false)}
          maxWidth="md"
          fullWidth
        >
          <DialogTitle className="font-bold flex items-center justify-between">
            <div className="flex items-center gap-2">
              <GroupAddIcon className="text-sky-600" />
              <span>Assign Employees to: <b>{targetPosition?.name}</b></span>
            </div>
            <Chip
              label={`${selectedUserIds.length} Selected`}
              color="primary"
              size="small"
              sx={{ fontWeight: 700 }}
            />
          </DialogTitle>

          <form onSubmit={handleSaveAssignedUsers}>
            <DialogContent className="space-y-4">
              <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
                Select active employees to assign to this office position. Assigned users will automatically be matched with corresponding training requirements.
              </Typography>

              {/* Search Users */}
              <TextField
                fullWidth
                size="small"
                placeholder="Search employee by name or email..."
                value={userSearch}
                onChange={(e) => setUserSearch(e.target.value)}
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchIcon fontSize="small" className="text-slate-400" />
                    </InputAdornment>
                  ),
                  endAdornment: userSearch ? (
                    <InputAdornment position="end">
                      <IconButton size="small" onClick={() => setUserSearch('')}>
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    </InputAdornment>
                  ) : null,
                }}
              />

              {/* Users List with Checkboxes */}
              <TableContainer className="border border-slate-200 dark:border-slate-800 rounded-xl max-h-96 overflow-y-auto">
                <Table size="small" stickyHeader>
                  <TableHead className="bg-slate-100 dark:bg-slate-800">
                    <TableRow>
                      <TableCell padding="checkbox">
                        <Checkbox
                          size="small"
                          checked={
                            filteredUsers.length > 0 &&
                            filteredUsers.every((u) => selectedUserIds.includes(u.id))
                          }
                          indeterminate={
                            filteredUsers.some((u) => selectedUserIds.includes(u.id)) &&
                            !filteredUsers.every((u) => selectedUserIds.includes(u.id))
                          }
                          onChange={(e) => {
                            if (e.target.checked) {
                              const newIds = new Set([...selectedUserIds, ...filteredUsers.map((u) => u.id)]);
                              setSelectedUserIds(Array.from(newIds));
                            } else {
                              const removeIds = new Set(filteredUsers.map((u) => u.id));
                              setSelectedUserIds(selectedUserIds.filter((id) => !removeIds.has(id)));
                            }
                          }}
                        />
                      </TableCell>
                      <TableCell className="font-bold text-xs">Employee</TableCell>
                      <TableCell className="font-bold text-xs">Current Department</TableCell>
                      <TableCell className="font-bold text-xs">Current Office Position</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {filteredUsers.map((user) => {
                      const isSelected = selectedUserIds.includes(user.id);
                      return (
                        <TableRow
                          key={user.id}
                          hover
                          onClick={() => toggleUserSelection(user.id)}
                          className={`cursor-pointer transition-colors ${
                            isSelected ? 'bg-sky-50/50 dark:bg-sky-950/30' : ''
                          }`}
                        >
                          <TableCell padding="checkbox">
                            <Checkbox
                              size="small"
                              checked={isSelected}
                              color="primary"
                              onChange={() => toggleUserSelection(user.id)}
                            />
                          </TableCell>

                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Avatar sx={{ width: 28, height: 28, fontSize: '0.75rem', bgcolor: 'primary.main' }}>
                                {user.name?.charAt(0) || 'U'}
                              </Avatar>
                              <div>
                                <div className="text-xs font-bold text-slate-800 dark:text-slate-100">
                                  {user.name}
                                </div>
                                <div className="text-[11px] text-slate-400">{user.email}</div>
                              </div>
                            </div>
                          </TableCell>

                          <TableCell className="text-xs text-slate-600 dark:text-slate-300">
                            {user.department?.name || <span className="text-slate-400 italic">No Dept</span>}
                          </TableCell>

                          <TableCell className="text-xs">
                            {user.office_position_id === targetPosition?.id ? (
                              <Chip
                                label="Currently In This Position"
                                size="small"
                                color="success"
                                variant="outlined"
                                sx={{ fontSize: '0.7rem', fontWeight: 700 }}
                              />
                            ) : user.office_position ? (
                              <span className="text-slate-500 font-medium">{user.office_position.name}</span>
                            ) : (
                              <span className="text-slate-400 italic">Unassigned</span>
                            )}
                          </TableCell>
                        </TableRow>
                      );
                    })}

                    {filteredUsers.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={4} align="center" className="text-slate-400 py-8">
                          No active employees found matching "{userSearch}".
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </TableContainer>
            </DialogContent>

            <DialogActions className="p-4">
              <Button onClick={() => setAssignModalOpen(false)} sx={{ textTransform: 'none' }}>
                Cancel
              </Button>
              <Button
                type="submit"
                variant="contained"
                color="primary"
                startIcon={<CheckCircleIcon />}
                sx={{ textTransform: 'none', fontWeight: 700 }}
              >
                Save Assigned Employees ({selectedUserIds.length})
              </Button>
            </DialogActions>
          </form>
        </Dialog>
      </Box>
    </AsideLayout>
  );
}
