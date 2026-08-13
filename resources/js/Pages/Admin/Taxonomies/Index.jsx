import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import {
  Box,
  Button,
  Paper,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Chip,
  IconButton,
  TextField,
  Snackbar,
  Alert,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import CheckIcon from '@mui/icons-material/Check';
import CloseIcon from '@mui/icons-material/Close';
import FolderPlusIcon from '@mui/icons-material/CreateNewFolder';

export default function Index({ taxonomies = {} }) {
  const [editingRowId, setEditingRowId] = useState(null);
  const [editFormData, setEditFormData] = useState({});
  const [newRowGroup, setNewRowGroup] = useState(null);

  // Group renaming state
  const [editingGroupKey, setEditingGroupKey] = useState(null);
  const [newGroupTitle, setNewGroupTitle] = useState('');

  // Create new group modal state
  const [openNewGroupModal, setOpenNewGroupModal] = useState(false);
  const [customNewGroupKey, setCustomNewGroupKey] = useState('');

  // Snackbar Notification State
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });

  const handleCloseSnackbar = () => {
    setSnackbar({ ...snackbar, open: false });
  };

  // --- Inline Row Edit Handlers ---
  const handleStartEdit = (item) => {
    setNewRowGroup(null);
    setEditingRowId(item.id);
    setEditFormData({ ...item });
  };

  const handleCancelEdit = () => {
    setEditingRowId(null);
    setNewRowGroup(null);
    setEditFormData({});
  };

  const handleSaveInline = (e) => {
    if (e) e.preventDefault();

    if (!editFormData.title || !editFormData.code) {
      setSnackbar({ open: true, message: 'Code and Title are required!', severity: 'error' });
      return;
    }

    if (editingRowId && typeof editingRowId === 'number') {
      router.put(`/taxonomies/${editingRowId}`, editFormData, {
        onSuccess: () => {
          setEditingRowId(null);
          setSnackbar({ open: true, message: `Updated taxonomy '${editFormData.title}' successfully!`, severity: 'success' });
        },
        onError: () => {
          setSnackbar({ open: true, message: 'Failed to update taxonomy entry.', severity: 'error' });
        }
      });
    } else {
      router.post('/taxonomies', editFormData, {
        onSuccess: () => {
          setEditingRowId(null);
          setNewRowGroup(null);
          setSnackbar({ open: true, message: `Created '${editFormData.title}' successfully!`, severity: 'success' });
        },
        onError: () => {
          setSnackbar({ open: true, message: 'Failed to create taxonomy entry.', severity: 'error' });
        }
      });
    }
  };

  const handleStartAddRow = (groupKey) => {
    setEditingRowId('new');
    setNewRowGroup(groupKey);
    setEditFormData({
      group_key: groupKey,
      code: `${groupKey.toUpperCase()}_`,
      title: '',
      color_hex: '#3B82F6',
      sort_order: 1
    });
  };

  const handleDeleteItem = (item) => {
    if (confirm(`Are you sure you want to delete entry '${item.title}'?`)) {
      router.delete(`/taxonomies/${item.id}`, {
        onSuccess: () => {
          setSnackbar({ open: true, message: `Deleted entry '${item.title}' successfully!`, severity: 'success' });
        }
      });
    }
  };

  // --- Group Key Management Handlers ---
  const handleStartRenameGroup = (groupKey) => {
    setEditingGroupKey(groupKey);
    setNewGroupTitle(groupKey);
  };

  const handleSaveRenameGroup = (oldGroupKey) => {
    if (!newGroupTitle.trim()) return;

    router.post('/taxonomies/rename-group', {
      old_group_key: oldGroupKey,
      new_group_key: newGroupTitle.trim()
    }, {
      onSuccess: () => {
        setEditingGroupKey(null);
        setSnackbar({ open: true, message: `Renamed group to '${newGroupTitle.trim()}' successfully!`, severity: 'success' });
      },
      onError: () => {
        setSnackbar({ open: true, message: 'Failed to rename group.', severity: 'error' });
      }
    });
  };

  const handleDeleteGroup = (groupKey) => {
    if (confirm(`CAUTION: Are you sure you want to delete the entire '${groupKey.toUpperCase()}' group and all its entries?`)) {
      router.delete(`/taxonomies/groups/${groupKey}`, {
        onSuccess: () => {
          setSnackbar({ open: true, message: `Deleted group '${groupKey}' cleanly.`, severity: 'success' });
        }
      });
    }
  };

  const handleCreateNewGroup = () => {
    if (!customNewGroupKey.trim()) return;
    const groupKeyClean = customNewGroupKey.trim().toLowerCase().replace(/\s+/g, '_');
    setOpenNewGroupModal(false);
    setCustomNewGroupKey('');
    handleStartAddRow(groupKeyClean);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleSaveInline();
    } else if (e.key === 'Escape') {
      handleCancelEdit();
    }
  };

  const defaultGroups = ['type', 'branch', 'process', 'risk_level'];
  const allGroups = Array.from(new Set([...Object.keys(taxonomies), ...defaultGroups]));

  return (
    <AsideLayout title="Master Taxonomies Document">
      <Head title="Master Taxonomy Document" />

      {/* Main Single-Document Page Canvas */}
      <Box sx={{ maxWidth: 960, mx: 'auto', py: 4, px: { xs: 2, sm: 4 } }}>
        <Paper
          elevation={3}
          sx={{
            p: { xs: 3, sm: 6 },
            bgcolor: '#FFFFFF',
            borderRadius: 3,
            minHeight: '85vh',
            boxShadow: '0 10px 30px rgba(0,0,0,0.05)',
            border: '1px solid #E2E8F0'
          }}
        >
          {/* Document Header */}
          <Box sx={{ borderBottom: '2px solid #0F172A', pb: 2, mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 2 }}>
            <Box>
              <Typography variant="h4" component="h1" sx={{ fontWeight: 800, color: '#0F172A', letterSpacing: '-0.5px' }}>
                Master Taxonomy & Dictionary Specifications
              </Typography>
              <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                Double-click or click icons to rename/delete taxonomy groups. Click table rows to edit entries inline.
              </Typography>
            </Box>
            <Button
              variant="contained"
              startIcon={<FolderPlusIcon />}
              onClick={() => setOpenNewGroupModal(true)}
              sx={{ borderRadius: 2 }}
            >
              + Create New Group
            </Button>
          </Box>

          {/* Group Sections rendered like a Word Document */}
          {allGroups.map((groupKey) => {
            const items = taxonomies[groupKey] || [];
            const isAddingHere = newRowGroup === groupKey;
            const isRenamingGroup = editingGroupKey === groupKey;

            return (
              <Box key={groupKey} sx={{ mb: 5 }}>
                {/* Heading 2 per Group Key with Inline Rename & Delete */}
                <Typography
                  variant="h5"
                  component="h2"
                  sx={{
                    fontWeight: 700,
                    color: '#1E293B',
                    mb: 1.5,
                    borderBottom: '2px solid #E2E8F0',
                    pb: 1,
                    display: 'flex',
                    alignItems: 'center',
                    justify: 'space-between',
                    flexWrap: 'wrap',
                    gap: 1
                  }}
                >
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    {isRenamingGroup ? (
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <TextField
                          size="small"
                          variant="standard"
                          value={newGroupTitle}
                          onChange={(e) => setNewGroupTitle(e.target.value)}
                          onKeyDown={(e) => { if (e.key === 'Enter') handleSaveRenameGroup(groupKey); }}
                          autoFocus
                          sx={{ input: { fontSize: '1.25rem', fontWeight: 700 } }}
                        />
                        <IconButton size="small" color="primary" onClick={() => handleSaveRenameGroup(groupKey)}>
                          <CheckIcon fontSize="small" />
                        </IconButton>
                        <IconButton size="small" onClick={() => setEditingGroupKey(null)}>
                          <CloseIcon fontSize="small" />
                        </IconButton>
                      </Box>
                    ) : (
                      <>
                        <span style={{ textTransform: 'uppercase' }}>{groupKey.replace(/_/g, ' ')}</span>
                        <Tooltip title="Rename Group">
                          <IconButton size="small" onClick={() => handleStartRenameGroup(groupKey)}>
                            <EditIcon fontSize="small" sx={{ color: '#94A3B8' }} />
                          </IconButton>
                        </Tooltip>
                        <Tooltip title="Delete Entire Group">
                          <IconButton size="small" color="error" onClick={() => handleDeleteGroup(groupKey)}>
                            <DeleteIcon fontSize="small" />
                          </IconButton>
                        </Tooltip>
                      </>
                    )}
                  </Box>

                  <Button
                    size="small"
                    startIcon={<AddIcon />}
                    onClick={() => handleStartAddRow(groupKey)}
                    sx={{ textTransform: 'none', fontWeight: 600, fontSize: '0.8rem' }}
                  >
                    Add Entry
                  </Button>
                </Typography>

                {/* Borderless Table under Heading */}
                <Table sx={{ '& .MuiTableCell-root': { borderBottom: 'none', py: 1.2, px: 1.5 } }}>
                  <TableHead>
                    <TableRow sx={{ bgcolor: 'transparent' }}>
                      <TableCell sx={{ fontWeight: 700, color: '#64748B', fontSize: '0.75rem', textTransform: 'uppercase', width: '25%' }}>
                        Taxonomy Code
                      </TableCell>
                      <TableCell sx={{ fontWeight: 700, color: '#64748B', fontSize: '0.75rem', textTransform: 'uppercase', width: '35%' }}>
                        Display Title Badge
                      </TableCell>
                      <TableCell sx={{ fontWeight: 700, color: '#64748B', fontSize: '0.75rem', textTransform: 'uppercase', width: '15%' }}>
                        Badge Color
                      </TableCell>
                      <TableCell sx={{ fontWeight: 700, color: '#64748B', fontSize: '0.75rem', textTransform: 'uppercase', width: '10%' }}>
                        Order
                      </TableCell>
                      <TableCell align="right" sx={{ fontWeight: 700, color: '#64748B', fontSize: '0.75rem', textTransform: 'uppercase', width: '15%' }}>
                        Actions
                      </TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {items.length === 0 && !isAddingHere ? (
                      <TableRow>
                        <TableCell colSpan={5} sx={{ color: '#94A3B8', fontStyle: 'italic', py: 2 }}>
                          No taxonomy entries in this group. Click '+ Add Entry' to insert items.
                        </TableCell>
                      </TableRow>
                    ) : (
                      items.map((item) => {
                        const isEditing = editingRowId === item.id;

                        if (isEditing) {
                          return (
                            <TableRow key={item.id} sx={{ bgcolor: '#FEF9C3' }}>
                              <TableCell>
                                <TextField
                                  size="small"
                                  variant="standard"
                                  fullWidth
                                  value={editFormData.code || ''}
                                  onChange={(e) => setEditFormData({ ...editFormData, code: e.target.value.toUpperCase() })}
                                  onKeyDown={handleKeyDown}
                                  autoFocus
                                />
                              </TableCell>
                              <TableCell>
                                <TextField
                                  size="small"
                                  variant="standard"
                                  fullWidth
                                  value={editFormData.title || ''}
                                  onChange={(e) => setEditFormData({ ...editFormData, title: e.target.value })}
                                  onKeyDown={handleKeyDown}
                                />
                              </TableCell>
                              <TableCell>
                                <input
                                  type="color"
                                  value={editFormData.color_hex || '#3B82F6'}
                                  onChange={(e) => setEditFormData({ ...editFormData, color_hex: e.target.value })}
                                  style={{ width: 36, height: 28, cursor: 'pointer', border: 'none', background: 'none' }}
                                />
                              </TableCell>
                              <TableCell>
                                <TextField
                                  size="small"
                                  variant="standard"
                                  type="number"
                                  value={editFormData.sort_order || 1}
                                  onChange={(e) => setEditFormData({ ...editFormData, sort_order: parseInt(e.target.value, 10) || 0 })}
                                  onKeyDown={handleKeyDown}
                                />
                              </TableCell>
                              <TableCell align="right">
                                <Tooltip title="Save (Enter)">
                                  <IconButton size="small" color="primary" onClick={handleSaveInline}>
                                    <CheckIcon fontSize="small" />
                                  </IconButton>
                                </Tooltip>
                                <Tooltip title="Cancel (Esc)">
                                  <IconButton size="small" color="default" onClick={handleCancelEdit}>
                                    <CloseIcon fontSize="small" />
                                  </IconButton>
                                </Tooltip>
                              </TableCell>
                            </TableRow>
                          );
                        }

                        return (
                          <TableRow
                            key={item.id}
                            onClick={() => handleStartEdit(item)}
                            sx={{
                              cursor: 'pointer',
                              borderRadius: 1,
                              transition: 'all 0.15s ease',
                              '&:hover': { bgcolor: '#F8FAFC' }
                            }}
                          >
                            <TableCell sx={{ fontFamily: 'monospace', fontWeight: 600, color: '#334155' }}>
                              {item.code}
                            </TableCell>
                            <TableCell>
                              <Chip
                                label={item.title}
                                size="small"
                                style={{
                                  backgroundColor: item.color_hex || '#3B82F6',
                                  color: '#FFFFFF',
                                  fontWeight: 700,
                                  fontSize: '0.75rem'
                                }}
                              />
                            </TableCell>
                            <TableCell>
                              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <Box sx={{ width: 14, height: 14, borderRadius: '50%', bgcolor: item.color_hex || '#3B82F6' }} />
                                <Typography variant="caption" sx={{ fontFamily: 'monospace', color: '#64748B' }}>
                                  {item.color_hex}
                                </Typography>
                              </Box>
                            </TableCell>
                            <TableCell sx={{ color: '#64748B', fontWeight: 500 }}>
                              {item.sort_order}
                            </TableCell>
                            <TableCell align="right" onClick={(e) => e.stopPropagation()}>
                              <IconButton size="small" color="error" onClick={() => handleDeleteItem(item)}>
                                <DeleteIcon fontSize="small" />
                              </IconButton>
                            </TableCell>
                          </TableRow>
                        );
                      })
                    )}

                    {/* Inline Add Row Mode */}
                    {isAddingHere && (
                      <TableRow sx={{ bgcolor: '#EFF6FF' }}>
                        <TableCell>
                          <TextField
                            size="small"
                            variant="standard"
                            placeholder="CODE"
                            fullWidth
                            value={editFormData.code || ''}
                            onChange={(e) => setEditFormData({ ...editFormData, code: e.target.value.toUpperCase() })}
                            onKeyDown={handleKeyDown}
                            autoFocus
                          />
                        </TableCell>
                        <TableCell>
                          <TextField
                            size="small"
                            variant="standard"
                            placeholder="Title Name"
                            fullWidth
                            value={editFormData.title || ''}
                            onChange={(e) => setEditFormData({ ...editFormData, title: e.target.value })}
                            onKeyDown={handleKeyDown}
                          />
                        </TableCell>
                        <TableCell>
                          <input
                            type="color"
                            value={editFormData.color_hex || '#3B82F6'}
                            onChange={(e) => setEditFormData({ ...editFormData, color_hex: e.target.value })}
                            style={{ width: 36, height: 28, cursor: 'pointer', border: 'none', background: 'none' }}
                          />
                        </TableCell>
                        <TableCell>
                          <TextField
                            size="small"
                            variant="standard"
                            type="number"
                            value={editFormData.sort_order || 1}
                            onChange={(e) => setEditFormData({ ...editFormData, sort_order: parseInt(e.target.value, 10) || 0 })}
                            onKeyDown={handleKeyDown}
                          />
                        </TableCell>
                        <TableCell align="right">
                          <IconButton size="small" color="primary" onClick={handleSaveInline}>
                            <CheckIcon fontSize="small" />
                          </IconButton>
                          <IconButton size="small" color="default" onClick={handleCancelEdit}>
                            <CloseIcon fontSize="small" />
                          </IconButton>
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </Box>
            );
          })}
        </Paper>
      </Box>

      {/* Create New Group Modal */}
      <Dialog open={openNewGroupModal} onClose={() => setOpenNewGroupModal(false)} fullWidth maxWidth="xs">
        <DialogTitle>Create New Taxonomy Group</DialogTitle>
        <DialogContent dividers>
          <TextField
            fullWidth
            size="small"
            label="Group Key Name"
            placeholder="e.g. department, region, priority"
            value={customNewGroupKey}
            onChange={(e) => setCustomNewGroupKey(e.target.value)}
            autoFocus
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenNewGroupModal(false)}>Cancel</Button>
          <Button variant="contained" onClick={handleCreateNewGroup}>
            Create Group
          </Button>
        </DialogActions>
      </Dialog>

      {/* MUI Notification Snackbar */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={3000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert onClose={handleCloseSnackbar} severity={snackbar.severity} variant="filled" sx={{ width: '100%' }}>
          {snackbar.message}
        </Alert>
      </Snackbar>
    </AsideLayout>
  );
}
