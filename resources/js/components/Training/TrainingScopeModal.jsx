import React from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Box,
  Typography,
  Chip,
  Button,
  IconButton,
  Divider,
  Paper,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
  TableContainer,
  Stack,
  Alert
} from '@mui/material';

import CloseIcon from '@mui/icons-material/Close';
import SchoolIcon from '@mui/icons-material/School';
import ApartmentIcon from '@mui/icons-material/Apartment';
import WorkIcon from '@mui/icons-material/Work';
import LoopIcon from '@mui/icons-material/Loop';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import GroupIcon from '@mui/icons-material/Group';

export default function TrainingScopeModal({
  open = false,
  onClose,
  training = null,
}) {
  if (!training) return null;

  const scopes = training.scopes || [];
  const categoryName = training.category?.name || 'General';

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="md"
      fullWidth
      PaperProps={{
        sx: {
          borderRadius: 3,
          maxHeight: '90vh',
          display: 'flex',
          flexDirection: 'column',
        },
      }}
    >
      {/* Header */}
      <DialogTitle className="p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80">
        <Box className="flex items-start justify-between gap-4">
          <div className="space-y-1 flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <span className="inline-flex items-center gap-1 text-xs font-mono font-bold text-sky-700 dark:text-sky-400 bg-sky-100 dark:bg-sky-950/80 px-2.5 py-0.5 rounded-md">
                <SchoolIcon fontSize="inherit" />
                {training.code || 'TRAINING'}
              </span>
              <Chip
                label={categoryName}
                size="small"
                variant="outlined"
                color="primary"
                sx={{ fontWeight: 600, fontSize: '0.7rem' }}
              />
              <Chip
                label={(training.status || 'active').toUpperCase()}
                size="small"
                color={training.status === 'active' ? 'success' : 'default'}
                sx={{ fontWeight: 700, fontSize: '0.65rem' }}
              />
            </div>

            <Typography variant="h6" className="font-extrabold text-slate-900 dark:text-slate-100 leading-snug pt-1">
              {training.title}
            </Typography>
          </div>

          <IconButton size="small" onClick={onClose} sx={{ mt: -0.5, mr: -0.5 }}>
            <CloseIcon fontSize="small" />
          </IconButton>
        </Box>

        {/* Quick Parameters Bar */}
        <Box className="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs pt-3 mt-2 border-t border-slate-200/70 dark:border-slate-800/70">
          <div className="bg-white dark:bg-slate-800 p-2 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <span className="text-slate-400 text-[11px]">Passing Score</span>
            <span className="font-bold text-emerald-600 dark:text-emerald-400">
              {training.passing_score ?? 80}%
            </span>
          </div>

          <div className="bg-white dark:bg-slate-800 p-2 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <span className="text-slate-400 text-[11px] flex items-center gap-1">
              <LoopIcon fontSize="inherit" /> Retrain
            </span>
            <span className="font-semibold text-slate-800 dark:text-slate-200">
              Every {training.retrain_interval ?? 12} {training.retrain_unit ?? 'month'}(s)
            </span>
          </div>

          <div className="bg-white dark:bg-slate-800 p-2 rounded-xl border border-slate-200 dark:border-slate-700 col-span-2 sm:col-span-1 flex items-center justify-between">
            <span className="text-slate-400 text-[11px] flex items-center gap-1">
              <GroupIcon fontSize="inherit" /> Scopes
            </span>
            <span className="font-bold text-sky-600 dark:text-sky-400">
              {scopes.length} Rule{scopes.length === 1 ? '' : 's'}
            </span>
          </div>
        </Box>
      </DialogTitle>

      {/* Body Content */}
      <DialogContent dividers className="p-6 overflow-y-auto space-y-6">
        {/* Course Description */}
        {training.description && (
          <Box className="space-y-1.5">
            <Typography variant="caption" className="font-bold uppercase tracking-wider text-slate-400">
              Course Description & Objectives
            </Typography>
            <Typography variant="body2" className="text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-900/50 p-3.5 rounded-xl border border-slate-200/60 dark:border-slate-800">
              {training.description}
            </Typography>
          </Box>
        )}

        {/* Target Audience Scopes Section */}
        <Box className="space-y-3">
          <div className="flex items-center justify-between">
            <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
              <GroupIcon className="text-sky-600" fontSize="small" />
              Target Audience Scopes ({scopes.length})
            </Typography>
            <Typography variant="caption" className="text-slate-500 dark:text-slate-400">
              Employees assigned automatically for compliance & onboarding
            </Typography>
          </div>

          {scopes.length > 0 ? (
            <TableContainer component={Paper} elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
              <Table size="small">
                <TableHead className="bg-slate-100/80 dark:bg-slate-800/80">
                  <TableRow>
                    <TableCell className="font-bold text-xs text-slate-700 dark:text-slate-300 py-2.5">
                      #
                    </TableCell>
                    <TableCell className="font-bold text-xs text-slate-700 dark:text-slate-300 py-2.5">
                      Target Department
                    </TableCell>
                    <TableCell className="font-bold text-xs text-slate-700 dark:text-slate-300 py-2.5">
                      Office Position / Role Scope
                    </TableCell>
                  </TableRow>
                </TableHead>
                <TableBody className="divide-y divide-slate-100 dark:divide-slate-800">
                  {scopes.map((scope, index) => {
                    const deptName = scope.department?.name || 'All Departments';
                    const posName = scope.office_position?.name || scope.officePosition?.name;

                    return (
                      <TableRow key={scope.id || index} hover className="transition-colors">
                        <TableCell className="text-xs font-mono text-slate-400 py-3">
                          {index + 1}
                        </TableCell>
                        <TableCell className="py-3">
                          <div className="flex items-center gap-2">
                            <ApartmentIcon fontSize="small" className="text-slate-400 flex-shrink-0" />
                            <span className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                              {deptName}
                            </span>
                          </div>
                        </TableCell>
                        <TableCell className="py-3">
                          {posName ? (
                            <div className="flex items-center gap-1.5">
                              <WorkIcon fontSize="small" className="text-indigo-500 flex-shrink-0" />
                              <Chip
                                label={posName}
                                size="small"
                                color="primary"
                                variant="outlined"
                                sx={{ fontWeight: 600, fontSize: '0.75rem' }}
                              />
                            </div>
                          ) : (
                            <div className="flex items-center gap-1.5 text-sky-700 dark:text-sky-300 font-medium text-xs">
                              <CheckCircleIcon fontSize="inherit" className="text-sky-500" />
                              <span>All Positions in {deptName}</span>
                            </div>
                          )}
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>
          ) : (
            <Alert severity="info" className="rounded-2xl border border-sky-200 dark:border-sky-800 bg-sky-50/50 dark:bg-sky-950/30">
              <Typography variant="subtitle2" className="font-bold text-sky-900 dark:text-sky-200">
                Company-wide Training (No Restricted Scopes)
              </Typography>
              <Typography variant="body2" className="text-xs text-sky-800 dark:text-sky-300 mt-0.5">
                This training module applies to all departments and staff roles across the organization.
              </Typography>
            </Alert>
          )}
        </Box>
      </DialogContent>

      {/* Footer */}
      <DialogActions className="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <Button
          variant="outlined"
          color="inherit"
          onClick={onClose}
          sx={{ textTransform: 'none', borderRadius: 2 }}
        >
          Close
        </Button>

        <Button
          variant="contained"
          color="primary"
          component="a"
          href={`/training/trainings?search=${encodeURIComponent(training.code || '')}`}
          target="_blank"
          rel="noopener noreferrer"
          startIcon={<OpenInNewIcon fontSize="small" />}
          sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
        >
          View in Training Catalog
        </Button>
      </DialogActions>
    </Dialog>
  );
}
