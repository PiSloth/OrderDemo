import React, { useState, useEffect } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Box,
  Typography,
  IconButton,
  Tooltip,
  Button,
  Chip
} from '@mui/material';

import CloseIcon from '@mui/icons-material/Close';
import ZoomInIcon from '@mui/icons-material/ZoomIn';
import ZoomOutIcon from '@mui/icons-material/ZoomOut';
import RestartAltIcon from '@mui/icons-material/RestartAlt';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import DownloadIcon from '@mui/icons-material/Download';
import ImageIcon from '@mui/icons-material/Image';

export default function DocumentImageModal({
  open = false,
  onClose,
  src = '',
  alt = '',
  title = '',
}) {
  const [zoom, setZoom] = useState(1);

  // Reset zoom whenever modal opens or image changes
  useEffect(() => {
    if (open) {
      setZoom(1);
    }
  }, [open, src]);

  if (!src) return null;

  const handleZoomIn = () => {
    setZoom((prev) => Math.min(prev + 0.25, 3));
  };

  const handleZoomOut = () => {
    setZoom((prev) => Math.max(prev - 0.25, 0.5));
  };

  const handleResetZoom = () => {
    setZoom(1);
  };

  const handleDownload = () => {
    if (!src) return;
    const a = document.createElement('a');
    a.href = src;
    a.download = alt || title || 'document-image';
    a.target = '_blank';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  };

  const displayCaption = title || alt || '';

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="lg"
      fullWidth
      PaperProps={{
        sx: {
          borderRadius: 3,
          bgcolor: 'background.paper',
          overflow: 'hidden',
          maxHeight: '92vh',
          display: 'flex',
          flexDirection: 'column',
        },
      }}
    >
      {/* Floating / Elegant Header */}
      <DialogTitle className="p-3.5 px-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80">
        <Box className="flex items-center justify-between gap-3">
          <div className="flex items-center gap-2 truncate min-w-0">
            <ImageIcon className="text-indigo-600 dark:text-indigo-400 flex-shrink-0" fontSize="small" />
            <Typography variant="subtitle2" className="font-bold text-slate-900 dark:text-slate-100 truncate text-sm">
              {displayCaption || 'Image Preview'}
            </Typography>
            {zoom !== 1 && (
              <Chip
                label={`${Math.round(zoom * 100)}%`}
                size="small"
                variant="outlined"
                sx={{ fontSize: '0.65rem', height: 20, fontWeight: 700 }}
              />
            )}
          </div>

          {/* Action Toolbar */}
          <div className="flex items-center gap-1 flex-shrink-0">
            <Tooltip title="Zoom Out">
              <span>
                <IconButton size="small" onClick={handleZoomOut} disabled={zoom <= 0.5}>
                  <ZoomOutIcon fontSize="small" />
                </IconButton>
              </span>
            </Tooltip>

            <Tooltip title="Reset Zoom">
              <span>
                <IconButton size="small" onClick={handleResetZoom} disabled={zoom === 1}>
                  <RestartAltIcon fontSize="small" />
                </IconButton>
              </span>
            </Tooltip>

            <Tooltip title="Zoom In">
              <span>
                <IconButton size="small" onClick={handleZoomIn} disabled={zoom >= 3}>
                  <ZoomInIcon fontSize="small" />
                </IconButton>
              </span>
            </Tooltip>

            <Tooltip title="Download Image">
              <IconButton size="small" onClick={handleDownload}>
                <DownloadIcon fontSize="small" />
              </IconButton>
            </Tooltip>

            <Tooltip title="Open Original in New Tab">
              <IconButton size="small" component="a" href={src} target="_blank" rel="noopener noreferrer">
                <OpenInNewIcon fontSize="small" />
              </IconButton>
            </Tooltip>

            <IconButton size="small" onClick={onClose} sx={{ ml: 0.5 }}>
              <CloseIcon fontSize="small" />
            </IconButton>
          </div>
        </Box>
      </DialogTitle>

      {/* Image Content Body */}
      <DialogContent
        dividers
        className="p-0 flex-1 flex items-center justify-center bg-slate-950/90 dark:bg-slate-950 overflow-auto min-h-[380px] max-h-[78vh]"
      >
        <Box
          className="p-4 flex items-center justify-center w-full h-full"
          sx={{
            cursor: zoom > 1 ? 'grab' : 'zoom-in',
          }}
          onClick={() => {
            if (zoom === 1) handleZoomIn();
            else if (zoom > 1.5) handleResetZoom();
            else handleZoomIn();
          }}
        >
          <img
            src={src}
            alt={alt || 'Document Image Preview'}
            title={title || alt || ''}
            style={{
              transform: `scale(${zoom})`,
              transformOrigin: 'center center',
              transition: 'transform 0.2s ease-out',
            }}
            className="max-h-[72vh] max-w-full object-contain rounded-lg shadow-2xl select-none"
            draggable={false}
          />
        </Box>
      </DialogContent>

      {/* Footer / Caption */}
      {displayCaption && (
        <DialogActions className="p-2.5 px-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 flex items-center justify-between">
          <Typography variant="caption" className="text-slate-500 dark:text-slate-400 italic text-xs truncate">
            {displayCaption}
          </Typography>
          <Button size="small" onClick={onClose} sx={{ textTransform: 'none', fontSize: '0.75rem' }}>
            Close Preview
          </Button>
        </DialogActions>
      )}
    </Dialog>
  );
}
