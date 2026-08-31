import React, { useState, useEffect } from 'react';
import axios from 'axios';
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
  CircularProgress,
  Divider,
  Tabs,
  Tab,
  Paper,
  Alert,
  Tooltip
} from '@mui/material';

import CloseIcon from '@mui/icons-material/Close';
import ArticleIcon from '@mui/icons-material/Article';
import HistoryIcon from '@mui/icons-material/History';
import CampaignIcon from '@mui/icons-material/Campaign';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import VisibilityIcon from '@mui/icons-material/Visibility';
import SchoolIcon from '@mui/icons-material/School';
import TrainingScopeModal from '../Training/TrainingScopeModal';

export default function DocumentPreviewModal({
  open = false,
  onClose,
  documentId = null,
  documentData = null,
  onOpenInViewer = null,
}) {
  const [doc, setDoc] = useState(documentData);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState(0);
  const [selectedRevision, setSelectedRevision] = useState(null);
  const [copied, setCopied] = useState(false);
  const [scopeModalOpen, setScopeModalOpen] = useState(false);
  const [selectedTrainingForScope, setSelectedTrainingForScope] = useState(null);

  useEffect(() => {
    if (!open) {
      setDoc(null);
      setSelectedRevision(null);
      setError('');
      setActiveTab(0);
      setScopeModalOpen(false);
      setSelectedTrainingForScope(null);
      return;
    }

    if (documentData && (!documentId || String(documentData.id) === String(documentId))) {
      setDoc(documentData);
      setLoading(false);
      setError('');
      return;
    }

    if (documentId) {
      fetchDocument(documentId);
    }
  }, [open, documentId, documentData]);

  const fetchDocument = async (id) => {
    setLoading(true);
    setError('');
    try {
      const response = await axios.get(`/document/library/api/${id}`);
      if (response.data?.document) {
        setDoc(response.data.document);
      } else {
        setError('Document not found or inaccessible.');
      }
    } catch (err) {
      console.error('Failed to fetch document:', err);
      setError(err.response?.data?.message || 'Could not load document preview.');
    } finally {
      setLoading(false);
    }
  };

  const handleCopyLink = () => {
    if (!doc) return;
    const url = `${window.location.origin}/document/library?doc=${doc.id}`;
    navigator.clipboard.writeText(url);
    setCopied(true);
    setTimeout(() => setCopied(false), 2500);
  };

  const handleOpenMain = () => {
    if (onOpenInViewer && doc) {
      onOpenInViewer(doc);
    }
    if (onClose) {
      onClose();
    }
  };

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
      {/* Dialog Header */}
      <DialogTitle className="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80">
        <Box className="flex items-start justify-between gap-4">
          <div className="space-y-1 flex-1 min-w-0">
            <div className="flex items-center gap-2">
              <ArticleIcon className="text-indigo-600 dark:text-indigo-400" fontSize="small" />
              <Typography variant="caption" className="font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                Document Quick Preview
              </Typography>
            </div>
            <Typography variant="h6" className="font-extrabold text-slate-900 dark:text-slate-100 truncate">
              {loading ? 'Loading document...' : doc ? doc.title : 'Document Preview'}
            </Typography>
          </div>

          <div className="flex items-center gap-1.5 flex-shrink-0">
            {doc && (
              <>
                <Button
                  size="small"
                  variant="outlined"
                  startIcon={<ContentCopyIcon fontSize="small" />}
                  onClick={handleCopyLink}
                  sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem' }}
                >
                  {copied ? 'Copied!' : 'Copy Link'}
                </Button>
                {onOpenInViewer && (
                  <Button
                    size="small"
                    variant="contained"
                    color="primary"
                    startIcon={<OpenInNewIcon fontSize="small" />}
                    onClick={handleOpenMain}
                    sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700, fontSize: '0.75rem' }}
                  >
                    Open in Viewer
                  </Button>
                )}
              </>
            )}

            <IconButton size="small" onClick={onClose} sx={{ ml: 1 }}>
              <CloseIcon fontSize="small" />
            </IconButton>
          </div>
        </Box>

        {doc && !loading && (
          <Box className="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400 pt-2">
            <Chip
              label={doc.department?.name || 'No Dept'}
              size="small"
              variant="outlined"
              sx={{ fontWeight: 600, fontSize: '0.7rem' }}
            />
            <Chip
              label={doc.type?.name || 'General'}
              size="small"
              color="primary"
              variant="outlined"
              sx={{ fontWeight: 600, fontSize: '0.7rem' }}
            />
            {doc.announced_at && (
              <Chip
                icon={<CampaignIcon fontSize="small" />}
                label={`Announced: ${doc.announced_at}`}
                size="small"
                color="warning"
                sx={{ fontWeight: 600, fontSize: '0.7rem' }}
              />
            )}
            <span>Author: <b>{doc.author?.name || 'Unknown'}</b></span>
            {doc.revisions && doc.revisions.length > 0 && (
              <span>• <b>v{doc.revisions[0]?.version || 1}</b></span>
            )}
          </Box>
        )}

        {/* Related Training Catalogs Section */}
        {doc && !loading && doc.trainings && doc.trainings.length > 0 && (
          <Box className="mt-3 p-2.5 rounded-xl border border-sky-200 dark:border-sky-800/80 bg-gradient-to-r from-sky-50/90 via-sky-50/50 to-indigo-50/40 dark:from-sky-950/40 dark:via-slate-900/60 dark:to-indigo-950/30">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1.5">
              <div className="flex items-center gap-1.5">
                <SchoolIcon fontSize="small" className="text-sky-600 dark:text-sky-400" />
                <Typography variant="caption" className="font-bold text-sky-900 dark:text-sky-200 uppercase tracking-wider text-[10px]">
                  Related Training Catalog ({doc.trainings.length})
                </Typography>
              </div>
              <Typography variant="caption" className="text-slate-500 dark:text-slate-400 text-[10px]">
                Click catalog to view target scopes
              </Typography>
            </div>

            <div className="flex flex-wrap items-center gap-1.5">
              {doc.trainings.map((t) => (
                <Tooltip key={t.id} title={`Click to view target audience scopes for ${t.title}`}>
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedTrainingForScope(t);
                      setScopeModalOpen(true);
                    }}
                    className="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-sky-300 dark:border-sky-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 hover:border-sky-500 dark:hover:border-sky-400 hover:bg-sky-50 dark:hover:bg-slate-700/80 shadow-xs hover:shadow transition-all text-xs font-semibold cursor-pointer"
                  >
                    <span className="font-mono text-[10px] font-bold text-sky-600 dark:text-sky-400 bg-sky-100/80 dark:bg-sky-950 px-1.5 py-0.5 rounded">
                      {t.code}
                    </span>
                    <span className="font-medium truncate max-w-xs">{t.title}</span>
                    <span className="inline-flex items-center text-[10px] text-sky-600 dark:text-sky-400 font-bold group-hover:underline pl-0.5">
                      Scopes ({t.scopes?.length || 0}) →
                    </span>
                  </button>
                </Tooltip>
              ))}
            </div>
          </Box>
        )}
      </DialogTitle>

      {/* Dialog Tabs if revisions exist */}
      {doc && !loading && doc.revisions && doc.revisions.length > 0 && (
        <Box className="border-b border-slate-200 dark:border-slate-800 px-4 bg-slate-50/50 dark:bg-slate-900/40">
          <Tabs value={activeTab} onChange={(e, val) => setActiveTab(val)}>
            <Tab label="Document Content" icon={<ArticleIcon fontSize="small" />} iconPosition="start" sx={{ textTransform: 'none', fontWeight: 600 }} />
            <Tab
              label={`Revisions (${doc.revisions.length})`}
              icon={<HistoryIcon fontSize="small" />}
              iconPosition="start"
              sx={{ textTransform: 'none', fontWeight: 600 }}
            />
          </Tabs>
        </Box>
      )}

      {/* Content Area */}
      <DialogContent dividers className="p-6 overflow-y-auto flex-1">
        {loading && (
          <Box className="py-16 flex flex-col items-center justify-center gap-3 text-slate-500">
            <CircularProgress size={32} />
            <Typography variant="body2">Loading document details...</Typography>
          </Box>
        )}

        {error && (
          <Alert severity="error" className="my-4 rounded-xl">
            {error}
          </Alert>
        )}

        {!loading && !error && doc && (
          <>
            {activeTab === 0 && (
              <Box
                className="prose prose-slate dark:prose-invert max-w-none leading-relaxed text-slate-800 dark:text-slate-200"
                dangerouslySetInnerHTML={{ __html: doc.body || '<p class="text-slate-400">Empty document content.</p>' }}
              />
            )}

            {activeTab === 1 && (
              <Box className="space-y-3">
                {doc.revisions?.map((rev) => (
                  <Paper
                    key={rev.id}
                    elevation={0}
                    className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4"
                  >
                    <Box className="space-y-0.5">
                      <Typography variant="subtitle2" className="font-bold flex items-center gap-2">
                        <span className="px-2 py-0.5 rounded-md bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-mono text-xs">
                          v{rev.version}
                        </span>
                        <span>{rev.title}</span>
                      </Typography>
                      <Typography variant="caption" className="text-slate-500 block">
                        Edited by <b>{rev.editor?.name || 'Unknown'}</b> on {rev.created_at ? new Date(rev.created_at).toLocaleString() : '-'}
                      </Typography>
                    </Box>

                    <Button
                      size="small"
                      variant="outlined"
                      startIcon={<VisibilityIcon />}
                      onClick={() => setSelectedRevision(rev)}
                      sx={{ textTransform: 'none', borderRadius: 2, fontSize: '0.75rem' }}
                    >
                      View Snapshot
                    </Button>
                  </Paper>
                ))}
              </Box>
            )}
          </>
        )}
      </DialogContent>

      <DialogActions className="p-3 border-t border-slate-200 dark:border-slate-800">
        <Button onClick={onClose} sx={{ textTransform: 'none' }}>
          Close
        </Button>
        {doc && onOpenInViewer && (
          <Button
            variant="contained"
            color="primary"
            onClick={handleOpenMain}
            startIcon={<OpenInNewIcon />}
            sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
          >
            Open in Document Library Viewer
          </Button>
        )}
      </DialogActions>

      {/* Snapshot Submodal */}
      <Dialog
        open={Boolean(selectedRevision)}
        onClose={() => setSelectedRevision(null)}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle className="flex items-center justify-between">
          <span>Revision v{selectedRevision?.version}: {selectedRevision?.title}</span>
          <Chip label={`By ${selectedRevision?.editor?.name || 'Unknown'}`} size="small" />
        </DialogTitle>
        <DialogContent dividers>
          <Box
            className="prose prose-slate dark:prose-invert max-w-none p-4"
            dangerouslySetInnerHTML={{ __html: selectedRevision?.body || '<p>No content in this revision.</p>' }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setSelectedRevision(null)}>Close Snapshot</Button>
        </DialogActions>
      </Dialog>

      {/* Related Training Scope Details Modal */}
      <TrainingScopeModal
        open={scopeModalOpen}
        onClose={() => {
          setScopeModalOpen(false);
          setSelectedTrainingForScope(null);
        }}
        training={selectedTrainingForScope}
      />
    </Dialog>
  );
}
