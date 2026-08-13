import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import axios from 'axios';
import { useBlockDraftStorage } from '../../Hooks/useBlockDraftStorage';
import ReportBlockItem from './ReportBlockItem';
import ReportImageboardView from './ReportImageboardView';
import {
  Box,
  Button,
  Paper,
  Typography,
  TextField,
  Chip,
  Snackbar,
  Alert,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Stack,
  ToggleButton,
  ToggleButtonGroup,
  Pagination
} from '@mui/material';

import SaveIcon from '@mui/icons-material/Save';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import ArticleIcon from '@mui/icons-material/Article';
import ForumIcon from '@mui/icons-material/Forum';
import { router } from '@inertiajs/react';

export default function ReportEditorContainer({ report, taxonomies = {}, todoOptions = {} }) {
  const storageKey = `report_draft_blocks_${report?.id || 'new'}`;
  const [viewMode, setViewMode] = useState('editor'); // 'editor' | 'imageboard'
  const [title, setTitle] = useState(report?.title || 'Operational Audit & Compliance Report');
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });

  // Delete Confirmation Dialog State (Step 1)
  const [deleteDialog, setDeleteDialog] = useState({ open: false, blockId: null, index: null });

  // 5-Second Undo Deletion Snackbar State (Step 2)
  const [undoSnackbar, setUndoSnackbar] = useState({ open: false, backup: null });

  const defaultBlocks = report?.text_blocks?.length > 0 
    ? report.text_blocks.map(b => {
        const meta = typeof b.json_content === 'object' && b.json_content ? b.json_content : {};
        return {
          id: b.id,
          category_type: b.category_type || '',
          branch_code: b.branch_code || '',
          process_code: b.process_code || '',
          risk_level: b.risk_level || 'MEDIUM',
          html_content: b.html_content || '',
          json_content: meta,
          plain_text: b.plain_text || '',
          referenced_solution: meta.referenced_solution || meta.reference_title || null,
          reference_title: meta.reference_title || meta.referenced_solution || null,
          reference_author: meta.reference_author || null,
          reference_branch: meta.reference_branch || null,
          reference_date: meta.reference_date || null,
          reference_content: meta.reference_content || null,
          reference_count: meta.reference_count || 1,
          attached_todos: meta.attached_todos || []
        };
      })
    : [
        {
          id: 'tmp_' + Date.now() + '_1',
          category_type: 'TYPE_FINDING',
          branch_code: 'BR_B1',
          process_code: 'PROC_PAWN',
          risk_level: 'HIGH',
          html_content: '<h3>Audit Observation #1</h3><p>Observed discrepancy during pawn valuation checks at Branch 1 counter.</p>',
          json_content: {},
          plain_text: 'Audit Observation #1 Observed discrepancy during pawn valuation checks at Branch 1 counter.'
        },
        {
          id: 'tmp_' + Date.now() + '_2',
          category_type: 'TYPE_SOLUTION',
          branch_code: 'BR_B1',
          process_code: 'PROC_PAWN',
          risk_level: 'LOW',
          html_content: '<p><b>Corrective Action:</b> Recalibrated digital scales and enforced dual supervisor authorization.</p>',
          json_content: {},
          plain_text: 'Corrective Action: Recalibrated digital scales and enforced dual supervisor authorization.'
        }
      ];

  const { blocks, setBlocks, lastSaved, clearDraft } = useBlockDraftStorage(storageKey, defaultBlocks);

  // Pagination for Report Blocks (5 blocks per page)
  const [blockPage, setBlockPage] = useState(1);
  const BLOCKS_PER_PAGE = 5;
  const totalBlockPages = Math.ceil(blocks.length / BLOCKS_PER_PAGE) || 1;

  useEffect(() => {
    if (blockPage > totalBlockPages) {
      setBlockPage(totalBlockPages);
    }
  }, [blocks.length, totalBlockPages, blockPage]);

  const displayedBlocks = totalBlockPages > 1
    ? blocks.slice((blockPage - 1) * BLOCKS_PER_PAGE, blockPage * BLOCKS_PER_PAGE)
    : blocks;

  const handleDragEnd = (result) => {
    if (!result.destination) return;
    const offset = totalBlockPages > 1 ? (blockPage - 1) * BLOCKS_PER_PAGE : 0;
    const sourceIdx = offset + result.source.index;
    const destIdx = offset + result.destination.index;
    const reordered = Array.from(blocks);
    const [removed] = reordered.splice(sourceIdx, 1);
    reordered.splice(destIdx, 0, removed);
    setBlocks(reordered);
  };

  const handleAddBlock = (categoryPreset = '') => {
    const newBlock = {
      id: 'tmp_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
      category_type: categoryPreset,
      branch_code: '',
      process_code: '',
      risk_level: 'MEDIUM',
      html_content: '<p></p>',
      json_content: {},
      plain_text: ''
    };
    const updated = [...blocks, newBlock];
    setBlocks(updated);
    if (totalBlockPages > 1) {
      setBlockPage(Math.ceil(updated.length / BLOCKS_PER_PAGE));
    }
  };

  const handleUpdateBlock = (id, fields) => {
    setBlocks(blocks.map(b => (b.id === id ? { ...b, ...fields } : b)));
  };

  // --- TWO-STEP DELETE WORKFLOW HANDLERS ---
  const handleOpenDeleteModal = (blockId, index) => {
    setDeleteDialog({ open: true, blockId, index });
  };

  const handleCloseDeleteModal = () => {
    setDeleteDialog({ open: false, blockId: null, index: null });
  };

  const handleConfirmDeleteBlock = () => {
    const { blockId, index } = deleteDialog;
    if (blockId === null || index === null) return;

    const targetBlock = blocks[index] || blocks.find(b => b.id === blockId);
    if (!targetBlock) return;

    // Auto-archive any linked To-Do tasks in the database via /todo/tasks/{id} DELETE API
    if (targetBlock.attached_todo_ids && Array.isArray(targetBlock.attached_todo_ids)) {
      targetBlock.attached_todo_ids.forEach(taskId => {
        axios.delete(`/todo/tasks/${taskId}`).catch(() => {});
      });
    }

    const updatedBlocks = blocks.filter(b => b.id !== blockId);
    setBlocks(updatedBlocks);
    setDeleteDialog({ open: false, blockId: null, index: null });

    setUndoSnackbar({
      open: true,
      backup: { block: targetBlock, index: index }
    });
  };

  const handleUndoDelete = () => {
    if (undoSnackbar.backup) {
      const { block, index } = undoSnackbar.backup;
      const restoredBlocks = [...blocks];
      restoredBlocks.splice(index, 0, block);
      setBlocks(restoredBlocks);
    }
    setUndoSnackbar({ open: false, backup: null });
  };

  const handleUndoSnackbarClose = (event, reason) => {
    if (reason === 'clickaway') return;
    setUndoSnackbar({ open: false, backup: null });
  };

  const handleSaveReport = () => {
    const payload = {
      title,
      blocks: blocks.map((b, idx) => {
        const jsonMeta = {
          ...(typeof b.json_content === 'object' && b.json_content ? b.json_content : {}),
          referenced_solution: b.referenced_solution || b.reference_title || null,
          reference_title: b.reference_title || b.referenced_solution || null,
          reference_author: b.reference_author || null,
          reference_branch: b.reference_branch || null,
          reference_date: b.reference_date || null,
          reference_content: b.reference_content || null,
          reference_count: b.reference_count || 1,
          attached_todos: b.attached_todos || b.gluedTasks || []
        };

        return {
          id: typeof b.id === 'number' ? b.id : null,
          sequence_order: idx + 1,
          category_type: b.category_type,
          branch_code: b.branch_code,
          process_code: b.process_code,
          risk_level: b.risk_level,
          plain_text: b.plain_text,
          html_content: b.html_content,
          json_content: jsonMeta
        };
      })
    };

    const url = report?.id ? `/reports/${report.id}` : '/reports/save';
    const method = report?.id ? 'put' : 'post';

    router[method](url, payload, {
      onSuccess: () => {
        clearDraft();

        // Reset form content after successful report creation
        if (!report?.id) {
          setTitle('');
          setBlocks([
            {
              id: 'tmp_' + Date.now() + '_1',
              category_type: '',
              branch_code: '',
              process_code: '',
              risk_level: 'MEDIUM',
              html_content: '<p></p>',
              json_content: {},
              plain_text: ''
            }
          ]);
        }

        setSnackbar({ open: true, message: 'Report created successfully and form content reset!', severity: 'success' });
      },
      onError: () => {
        setSnackbar({ open: true, message: 'Failed to save report. Please check required fields.', severity: 'error' });
      }
    });
  };

  return (
    <Box sx={{ maxWidth: 980, mx: 'auto', py: 3, px: { xs: 1.5, sm: 3 } }}>
      {/* View Mode Switcher: Document Studio vs 4chan Imageboard */}
      <Box sx={{ display: 'flex', justifyContent: 'flex-end', mb: 2 }}>
        <ToggleButtonGroup
          size="small"
          value={viewMode}
          exclusive
          onChange={(e, newMode) => newMode && setViewMode(newMode)}
          sx={{ bgcolor: '#FFFFFF', borderRadius: 2, boxShadow: '0 2px 6px rgba(0,0,0,0.05)' }}
        >
          <ToggleButton value="editor" sx={{ textTransform: 'none', fontWeight: 700, px: 2 }}>
            <ArticleIcon fontSize="small" sx={{ mr: 1 }} /> Document Studio
          </ToggleButton>
          <ToggleButton value="imageboard" sx={{ textTransform: 'none', fontWeight: 700, px: 2 }}>
            <ForumIcon fontSize="small" sx={{ mr: 1 }} /> Analytic Report
          </ToggleButton>
        </ToggleButtonGroup>
      </Box>

      {viewMode === 'imageboard' ? (
        <ReportImageboardView taxonomies={taxonomies} todoOptions={todoOptions} />
      ) : (
        /* WORD FILE + NOTION STYLE PAPER CANVAS WITH SOLID BORDER */
        <Paper
          elevation={4}
          sx={{
            bgcolor: '#FFFFFF',
            borderRadius: 4,
            border: '2px solid #CBD5E1',
            p: { xs: 2.5, sm: 5 },
            minHeight: '88vh',
            overflow: 'visible',
            boxShadow: '0 12px 36px rgba(15, 23, 42, 0.06)'
          }}
        >
        {/* Header Toolbar */}
        <Box
          sx={{
            display: 'flex',
            alignItems: 'center',
            justify: 'space-between',
            flexWrap: 'wrap',
            gap: 2,
            pb: 2,
            mb: 3,
            borderBottom: '2px solid #F1F5F9'
          }}
        >
          <Box sx={{ flexGrow: 1, minWidth: 280 }}>
            <TextField
              fullWidth
              variant="standard"
              placeholder="Type report title (e.g. Operational Audit, Cash Inventory)..."
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              InputProps={{
                disableUnderline: true,
                style: { fontSize: '1.75rem', fontWeight: 800, color: '#0F172A' }
              }}
            />

            {lastSaved && (
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mt: 1 }}>
                <Chip
                  icon={<CheckCircleIcon style={{ fontSize: 14, color: '#10B981' }} />}
                  label={`Autosaved ${lastSaved.toLocaleTimeString()}`}
                  size="small"
                  sx={{ bgcolor: '#ECFDF5', color: '#065F46', fontWeight: 600, fontSize: '0.72rem' }}
                />
              </Box>
            )}
          </Box>

          <Stack direction="row" spacing={1.5} alignItems="center">
            <Button
              variant="contained"
              color="primary"
              size="medium"
              startIcon={<SaveIcon />}
              onClick={handleSaveReport}
              sx={{
                borderRadius: 2.5,
                px: 3,
                py: 1,
                fontWeight: 700,
                textTransform: 'none',
                boxShadow: '0 4px 12px rgba(59, 130, 246, 0.3)'
              }}
            >
              Save Report
            </Button>
          </Stack>
        </Box>

        {/* MUI Pagination for Report Blocks */}
        {totalBlockPages > 1 && (
          <Box sx={{ display: 'flex', justifyContent: 'center', mb: 3 }}>
            <Pagination
              count={totalBlockPages}
              page={blockPage}
              onChange={(e, v) => setBlockPage(v)}
              color="primary"
              shape="rounded"
              showFirstButton
              showLastButton
              sx={{
                bgcolor: '#F8FAFC',
                px: 2,
                py: 0.75,
                borderRadius: 2,
                border: '1px solid #E2E8F0',
                boxShadow: '0 1px 4px rgba(0,0,0,0.04)',
                '& .MuiPaginationItem-root': { fontWeight: 700 }
              }}
            />
          </Box>
        )}

        {/* Drag & Drop Block Containers Canvas */}
        <DragDropContext onDragEnd={handleDragEnd}>
          <Droppable droppableId="report-blocks-droppable">
            {(provided) => (
              <Box ref={provided.innerRef} {...provided.droppableProps} sx={{ mb: 4 }}>
                {displayedBlocks.map((block, relativeIndex) => {
                  const absoluteIndex = totalBlockPages > 1 ? (blockPage - 1) * BLOCKS_PER_PAGE + relativeIndex : relativeIndex;
                  return (
                    <Draggable key={String(block.id)} draggableId={String(block.id)} index={relativeIndex}>
                      {(dragProvided) => (
                        <Box
                          ref={dragProvided.innerRef}
                          {...dragProvided.draggableProps}
                        >
                          <ReportBlockItem
                            key={block.id}
                            block={block}
                            index={absoluteIndex}
                            taxonomies={taxonomies}
                            todoOptions={todoOptions}
                            dragHandleProps={dragProvided.dragHandleProps}
                            onUpdateBlock={handleUpdateBlock}
                            onRemoveBlock={() => handleOpenDeleteModal(block.id, absoluteIndex)}
                          />
                        </Box>
                      )}
                    </Draggable>
                  );
                })}
                {provided.placeholder}
              </Box>
            )}
          </Droppable>
        </DragDropContext>

        {/* Floating / Bottom Action Bar: Add Block Presets */}
        <Paper
          variant="outlined"
          sx={{
            p: 2,
            borderRadius: 3,
            bgcolor: '#F8FAFC',
            borderColor: '#E2E8F0',
            display: 'flex',
            alignItems: 'center',
            justify: 'space-between',
            flexWrap: 'wrap',
            gap: 1.5
          }}
        >
          <Typography variant="body2" sx={{ fontWeight: 700, color: '#475569' }}>
            + Add New Block Container:
          </Typography>

          <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
            <Button
              size="small"
              variant="outlined"
              onClick={() => handleAddBlock('TYPE_FINDING')}
              sx={{ textTransform: 'none', fontWeight: 600, borderRadius: 2 }}
            >
              + Audit Finding
            </Button>
            <Button
              size="small"
              variant="outlined"
              onClick={() => handleAddBlock('TYPE_SOLUTION')}
              sx={{ textTransform: 'none', fontWeight: 600, borderRadius: 2 }}
            >
              + Solution / Plan
            </Button>
            <Button
              size="small"
              variant="outlined"
              onClick={() => handleAddBlock('TYPE_RISK')}
              sx={{ textTransform: 'none', fontWeight: 600, borderRadius: 2 }}
            >
              + Risk Factor
            </Button>
            <Button
              size="small"
              variant="contained"
              color="secondary"
              disableElevation
              onClick={() => handleAddBlock('')}
              sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 2 }}
            >
              + Empty Block
            </Button>
          </Stack>
        </Paper>
      </Paper>
      )}

      {/* STEP 1: MUI DELETE CONFIRMATION DIALOG */}
      <Dialog
        open={deleteDialog.open}
        onClose={handleCloseDeleteModal}
        fullWidth
        maxWidth="xs"
      >
        <DialogTitle sx={{ fontWeight: 700, color: '#0F172A' }}>
          Delete Block?
        </DialogTitle>
        <DialogContent dividers>
          <Typography variant="body1" color="text.secondary">
            Are you sure you want to delete this block? It will be removed from your draft.
          </Typography>
        </DialogContent>
        <DialogActions sx={{ p: 2 }}>
          <Button onClick={handleCloseDeleteModal} sx={{ fontWeight: 600, color: '#64748B' }}>
            Cancel
          </Button>
          <Button
            color="error"
            variant="contained"
            onClick={handleConfirmDeleteBlock}
            sx={{ fontWeight: 700, borderRadius: 2 }}
          >
            Delete
          </Button>
        </DialogActions>
      </Dialog>

      {/* STEP 2 & 3: MUI 5-SECOND UNDO SNACKBAR */}
      <Snackbar
        open={undoSnackbar.open}
        autoHideDuration={5000}
        onClose={handleUndoSnackbarClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
        message="Block deleted."
        action={
          <Button color="secondary" size="small" onClick={handleUndoDelete} sx={{ fontWeight: 700 }}>
            UNDO
          </Button>
        }
      />

      {/* General Notification Snackbar */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={4000}
        onClose={() => setSnackbar({ ...snackbar, open: false })}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert onClose={() => setSnackbar({ ...snackbar, open: false })} severity={snackbar.severity} variant="filled" sx={{ width: '100%' }}>
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Box>
  );
}
