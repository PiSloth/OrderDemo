import React, { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableCell } from '@tiptap/extension-table-cell';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Placeholder from '@tiptap/extension-placeholder';
import axios from 'axios';

import {
  Box,
  Button,
  IconButton,
  Tooltip,
  Divider,
  Menu,
  MenuItem,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  CircularProgress,
  Typography,
  Popover,
  Stack,
  Tabs,
  Tab,
  Alert,
  InputAdornment,
  List,
  ListItem,
  ListItemButton,
  ListItemText,
  Chip
} from '@mui/material';

import FormatBoldIcon from '@mui/icons-material/FormatBold';
import FormatItalicIcon from '@mui/icons-material/FormatItalic';
import FormatUnderlinedIcon from '@mui/icons-material/FormatUnderlined';
import StrikethroughSIcon from '@mui/icons-material/StrikethroughS';
import CodeIcon from '@mui/icons-material/Code';
import FormatQuoteIcon from '@mui/icons-material/FormatQuote';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import FormatListNumberedIcon from '@mui/icons-material/FormatListNumbered';
import HorizontalRuleIcon from '@mui/icons-material/HorizontalRule';
import FormatAlignLeftIcon from '@mui/icons-material/FormatAlignLeft';
import FormatAlignCenterIcon from '@mui/icons-material/FormatAlignCenter';
import FormatAlignRightIcon from '@mui/icons-material/FormatAlignRight';
import FormatAlignJustifyIcon from '@mui/icons-material/FormatAlignJustify';
import ImageIcon from '@mui/icons-material/Image';
import CloudUploadIcon from '@mui/icons-material/CloudUpload';
import InsertLinkIcon from '@mui/icons-material/InsertLink';
import LinkOffIcon from '@mui/icons-material/LinkOff';
import TableChartIcon from '@mui/icons-material/TableChart';
import UndoIcon from '@mui/icons-material/Undo';
import RedoIcon from '@mui/icons-material/Redo';
import FormatColorTextIcon from '@mui/icons-material/FormatColorText';
import ViewHeadlineIcon from '@mui/icons-material/ViewHeadline';
import ArticleIcon from '@mui/icons-material/Article';
import SearchIcon from '@mui/icons-material/Search';
import ClearIcon from '@mui/icons-material/Clear';

const COLOR_PALETTE = [
  '#000000', '#475569', '#64748b', '#dc2626', '#ea580c', '#d97706',
  '#16a34a', '#0d9488', '#0284c7', '#2563eb', '#7c3aed', '#c026d3'
];

export default function ReactRichTextEditor({
  value = '',
  onChange,
  placeholder = 'Write document content here... (Paste or drag images directly into the editor)',
  editable = true,
  minHeight = '360px',
  uploadUrl = '/document/library/upload-image',
}) {
  const [imageDialogOpen, setImageDialogOpen] = useState(false);
  const [imageTab, setImageTab] = useState(0);
  const [imageUrl, setImageUrl] = useState('');
  const [selectedFile, setSelectedFile] = useState(null);
  const [filePreview, setFilePreview] = useState(null);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadError, setUploadError] = useState('');

  const [linkDialogOpen, setLinkDialogOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState('');

  const [docLinkDialogOpen, setDocLinkDialogOpen] = useState(false);
  const [docSearchQuery, setDocSearchQuery] = useState('');
  const [docSearchResults, setDocSearchResults] = useState([]);
  const [isSearchingDocs, setIsSearchingDocs] = useState(false);
  
  // Menus
  const [tableAnchorEl, setTableAnchorEl] = useState(null);
  const [headingAnchorEl, setHeadingAnchorEl] = useState(null);
  const [colorAnchorEl, setColorAnchorEl] = useState(null);

  const fileInputRef = useRef(null);
  const editorRef = useRef(null);

  // Upload file helper: uploads to storage and inserts the public storage URL
  const uploadFileAndInsert = useCallback(async (file, targetEditor) => {
    const currentEditor = targetEditor || editorRef.current;
    if (!file || !currentEditor) return;

    if (!file.type.startsWith('image/')) {
      alert('Selected file is not a supported image.');
      return;
    }

    const formData = new FormData();
    formData.append('image', file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    setIsUploading(true);
    setUploadError('');

    try {
      const response = await axios.post(uploadUrl, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'X-CSRF-TOKEN': csrfToken || '',
        },
      });

      const publicStorageUrl = response.data?.url;
      if (publicStorageUrl) {
        currentEditor.chain().focus().setImage({ src: publicStorageUrl }).run();
        setImageDialogOpen(false);
        setSelectedFile(null);
        setFilePreview(null);
      } else {
        throw new Error('Image URL was not returned by server.');
      }
    } catch (err) {
      console.error('Image upload failed:', err);
      const errMsg = err.response?.data?.message || err.message || 'Failed to upload image. Please ensure image is under 10MB.';
      setUploadError(errMsg);
      alert(errMsg);
    } finally {
      setIsUploading(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  }, [uploadUrl]);

  const extensions = useMemo(() => [
    StarterKit.configure({
      heading: { levels: [1, 2, 3, 4] },
    }),
    Underline,
    TextStyle,
    Color,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-indigo-600 dark:text-indigo-400 underline hover:text-indigo-800 cursor-pointer',
      },
    }),
    Image.configure({
      inline: true,
      allowBase64: true,
      HTMLAttributes: {
        class: 'max-w-full h-auto rounded-xl shadow-md my-4 border border-slate-200 dark:border-slate-700 block',
      },
    }),
    Table.configure({
      resizable: true,
      HTMLAttributes: {
        class: 'border-collapse table-auto w-full my-4 border border-slate-300 dark:border-slate-600 rounded-lg overflow-hidden',
      },
    }),
    TableRow,
    TableHeader.configure({
      HTMLAttributes: {
        class: 'border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 p-2.5 text-left font-semibold text-sm',
      },
    }),
    TableCell.configure({
      HTMLAttributes: {
        class: 'border border-slate-300 dark:border-slate-600 p-2 text-sm',
      },
    }),
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
    Placeholder.configure({
      placeholder,
    }),
  ], [placeholder]);

  const editor = useEditor({
    extensions,
    content: value || '<p></p>',
    editable,
    editorProps: {
      handlePaste: (view, event) => {
        const items = Array.from(event.clipboardData?.items || []);
        const imageItems = items.filter(item => item.type.indexOf('image') !== -1);
        if (imageItems.length > 0) {
          event.preventDefault();
          imageItems.forEach(item => {
            const file = item.getAsFile();
            if (file) {
              uploadFileAndInsert(file, editorRef.current);
            }
          });
          return true;
        }
        return false;
      },
      handleDrop: (view, event, slice, moved) => {
        if (!moved && event.dataTransfer?.files?.length) {
          const files = Array.from(event.dataTransfer.files);
          const imageFiles = files.filter(file => file.type.startsWith('image/'));
          if (imageFiles.length > 0) {
            event.preventDefault();
            imageFiles.forEach(file => {
              uploadFileAndInsert(file, editorRef.current);
            });
            return true;
          }
        }
        return false;
      }
    },
    onUpdate: ({ editor }) => {
      if (onChange) {
        onChange(editor.getHTML());
      }
    },
  });

  editorRef.current = editor;

  // Sync external value changes if needed
  useEffect(() => {
    if (editor && value !== undefined && value !== editor.getHTML()) {
      if (editor.getText() === '' && (value === '' || value === '<p></p>')) {
        return;
      }
      editor.commands.setContent(value || '<p></p>', false);
    }
  }, [value, editor]);

  if (!editor) {
    return null;
  }

  // Handle direct file input selection
  const handleFileInputChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      uploadFileAndInsert(file, editor);
    }
  };

  const handleModalFileSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      setSelectedFile(file);
      setFilePreview(URL.createObjectURL(file));
      setUploadError('');
    }
  };

  const handleInsertImageUrl = () => {
    if (imageUrl.trim()) {
      editor.chain().focus().setImage({ src: imageUrl.trim() }).run();
      setImageUrl('');
      setImageDialogOpen(false);
    }
  };

  const handleSetLink = () => {
    if (!linkUrl.trim()) {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
      let formattedUrl = linkUrl.trim();
      if (!/^https?:\/\//i.test(formattedUrl)) {
        formattedUrl = 'https://' + formattedUrl;
      }
      editor.chain().focus().extendMarkRange('link').setLink({ href: formattedUrl }).run();
    }
    setLinkUrl('');
    setLinkDialogOpen(false);
  };

  const openLinkDialog = () => {
    const previousUrl = editor.getAttributes('link').href || '';
    setLinkUrl(previousUrl);
    setLinkDialogOpen(true);
  };

  const searchLibraryDocs = useCallback(async (query) => {
    setIsSearchingDocs(true);
    try {
      const res = await axios.get('/document/library/search-api', {
        params: { q: query || '' },
      });
      setDocSearchResults(res.data?.results || []);
    } catch (err) {
      console.error('Failed to search library documents:', err);
    } finally {
      setIsSearchingDocs(false);
    }
  }, []);

  useEffect(() => {
    if (docLinkDialogOpen) {
      searchLibraryDocs(docSearchQuery);
    }
  }, [docLinkDialogOpen, docSearchQuery, searchLibraryDocs]);

  const handleInsertDocumentLink = (docItem) => {
    if (!docItem || !editor) return;
    const docUrl = `/document/library?doc=${docItem.id}`;
    const { from, to } = editor.state.selection;
    const hasSelection = from !== to;

    if (hasSelection) {
      editor.chain().focus().extendMarkRange('link').setLink({ href: docUrl }).run();
    } else {
      editor.chain().focus().insertContent(` <a href="${docUrl}" data-document-id="${docItem.id}" class="text-indigo-600 dark:text-indigo-400 underline font-medium">📄 ${docItem.title}</a> `).run();
    }
    setDocLinkDialogOpen(false);
    setDocSearchQuery('');
  };

  const getCurrentHeadingLabel = () => {
    if (editor.isActive('heading', { level: 1 })) return 'Heading 1';
    if (editor.isActive('heading', { level: 2 })) return 'Heading 2';
    if (editor.isActive('heading', { level: 3 })) return 'Heading 3';
    if (editor.isActive('heading', { level: 4 })) return 'Heading 4';
    return 'Normal Text';
  };

  return (
    <Box className="w-full border rounded-2xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm flex flex-col relative">
      {/* Hidden Global File Input */}
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleFileInputChange}
        accept="image/*"
        style={{ display: 'none' }}
      />

      {/* Toolbar - Sticky on scroll below AppBar */}
      {editable && (
        <Box
          sx={{
            position: 'sticky',
            top: { xs: 56, sm: 64 },
            zIndex: 20,
            bgcolor: 'rgba(248, 250, 252, 0.97)',
            backdropFilter: 'blur(12px)',
            borderTopLeftRadius: 16,
            borderTopRightRadius: 16,
            boxShadow: '0 4px 12px -2px rgba(0,0,0,0.06)',
            '.dark &': {
              bgcolor: 'rgba(30, 41, 59, 0.97)',
              borderColor: 'rgba(255,255,255,0.08)',
            },
          }}
          className="flex flex-wrap items-center gap-1 p-2 border-b border-slate-200 dark:border-slate-800 transition-all"
        >
          {/* History */}
          <Tooltip title="Undo (Ctrl+Z)">
            <span>
              <IconButton
                size="small"
                onClick={() => editor.chain().focus().undo().run()}
                disabled={!editor.can().undo()}
                sx={{ p: 0.7 }}
              >
                <UndoIcon fontSize="small" />
              </IconButton>
            </span>
          </Tooltip>
          <Tooltip title="Redo (Ctrl+Y)">
            <span>
              <IconButton
                size="small"
                onClick={() => editor.chain().focus().redo().run()}
                disabled={!editor.can().redo()}
                sx={{ p: 0.7 }}
              >
                <RedoIcon fontSize="small" />
              </IconButton>
            </span>
          </Tooltip>

          <Divider orientation="vertical" flexItem sx={{ mx: 0.5, my: 0.5 }} />

          {/* Heading Dropdown */}
          <Button
            size="small"
            variant="outlined"
            onClick={(e) => setHeadingAnchorEl(e.currentTarget)}
            startIcon={<ViewHeadlineIcon fontSize="small" />}
            sx={{
              textTransform: 'none',
              fontSize: '0.8rem',
              py: 0.3,
              px: 1,
              borderColor: 'divider',
              color: 'text.primary',
            }}
          >
            {getCurrentHeadingLabel()}
          </Button>
          <Menu
            anchorEl={headingAnchorEl}
            open={Boolean(headingAnchorEl)}
            onClose={() => setHeadingAnchorEl(null)}
          >
            <MenuItem
              onClick={() => {
                editor.chain().focus().setParagraph().run();
                setHeadingAnchorEl(null);
              }}
              selected={!editor.isActive('heading')}
            >
              Normal Text
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 1 }).run();
                setHeadingAnchorEl(null);
              }}
              selected={editor.isActive('heading', { level: 1 })}
              sx={{ fontWeight: 'bold', fontSize: '1.2rem' }}
            >
              Heading 1
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 2 }).run();
                setHeadingAnchorEl(null);
              }}
              selected={editor.isActive('heading', { level: 2 })}
              sx={{ fontWeight: 'bold', fontSize: '1.05rem' }}
            >
              Heading 2
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 3 }).run();
                setHeadingAnchorEl(null);
              }}
              selected={editor.isActive('heading', { level: 3 })}
              sx={{ fontWeight: 'bold', fontSize: '0.95rem' }}
            >
              Heading 3
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 4 }).run();
                setHeadingAnchorEl(null);
              }}
              selected={editor.isActive('heading', { level: 4 })}
              sx={{ fontWeight: 'bold', fontSize: '0.85rem' }}
            >
              Heading 4
            </MenuItem>
          </Menu>

          <Divider orientation="vertical" flexItem sx={{ mx: 0.5, my: 0.5 }} />

          {/* Formatting */}
          <Tooltip title="Bold (Ctrl+B)">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleBold().run()}
              color={editor.isActive('bold') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('bold') ? 'action.selected' : 'transparent' }}
            >
              <FormatBoldIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          <Tooltip title="Italic (Ctrl+I)">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleItalic().run()}
              color={editor.isActive('italic') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('italic') ? 'action.selected' : 'transparent' }}
            >
              <FormatItalicIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          <Tooltip title="Underline (Ctrl+U)">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleUnderline().run()}
              color={editor.isActive('underline') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('underline') ? 'action.selected' : 'transparent' }}
            >
              <FormatUnderlinedIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          <Tooltip title="Strikethrough">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleStrike().run()}
              color={editor.isActive('strike') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('strike') ? 'action.selected' : 'transparent' }}
            >
              <StrikethroughSIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          <Tooltip title="Inline Code">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleCode().run()}
              color={editor.isActive('code') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('code') ? 'action.selected' : 'transparent' }}
            >
              <CodeIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          {/* Color Picker */}
          <Tooltip title="Text Color">
            <IconButton
              size="small"
              onClick={(e) => setColorAnchorEl(e.currentTarget)}
              sx={{ p: 0.7 }}
            >
              <FormatColorTextIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Popover
            open={Boolean(colorAnchorEl)}
            anchorEl={colorAnchorEl}
            onClose={() => setColorAnchorEl(null)}
            anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
          >
            <Box sx={{ p: 1.5, display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 1, width: 140 }}>
              {COLOR_PALETTE.map((color) => (
                <Box
                  key={color}
                  onClick={() => {
                    editor.chain().focus().setColor(color).run();
                    setColorAnchorEl(null);
                  }}
                  sx={{
                    width: 24,
                    height: 24,
                    bgcolor: color,
                    borderRadius: 1,
                    cursor: 'pointer',
                    border: '1px solid rgba(0,0,0,0.1)',
                    '&:hover': { transform: 'scale(1.15)' },
                    transition: 'transform 0.1s',
                  }}
                />
              ))}
            </Box>
          </Popover>

          <Divider orientation="vertical" flexItem sx={{ mx: 0.5, my: 0.5 }} />

          {/* Alignment */}
          <Tooltip title="Align Left">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().setTextAlign('left').run()}
              color={editor.isActive({ textAlign: 'left' }) ? 'primary' : 'default'}
              sx={{ p: 0.7 }}
            >
              <FormatAlignLeftIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Align Center">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().setTextAlign('center').run()}
              color={editor.isActive({ textAlign: 'center' }) ? 'primary' : 'default'}
              sx={{ p: 0.7 }}
            >
              <FormatAlignCenterIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Align Right">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().setTextAlign('right').run()}
              color={editor.isActive({ textAlign: 'right' }) ? 'primary' : 'default'}
              sx={{ p: 0.7 }}
            >
              <FormatAlignRightIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Justify">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().setTextAlign('justify').run()}
              color={editor.isActive({ textAlign: 'justify' }) ? 'primary' : 'default'}
              sx={{ p: 0.7 }}
            >
              <FormatAlignJustifyIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          <Divider orientation="vertical" flexItem sx={{ mx: 0.5, my: 0.5 }} />

          {/* Lists & Quotes */}
          <Tooltip title="Bullet List">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleBulletList().run()}
              color={editor.isActive('bulletList') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('bulletList') ? 'action.selected' : 'transparent' }}
            >
              <FormatListBulletedIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Numbered List">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleOrderedList().run()}
              color={editor.isActive('orderedList') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('orderedList') ? 'action.selected' : 'transparent' }}
            >
              <FormatListNumberedIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Blockquote">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().toggleBlockquote().run()}
              color={editor.isActive('blockquote') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('blockquote') ? 'action.selected' : 'transparent' }}
            >
              <FormatQuoteIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Horizontal Divider">
            <IconButton
              size="small"
              onClick={() => editor.chain().focus().setHorizontalRule().run()}
              sx={{ p: 0.7 }}
            >
              <HorizontalRuleIcon fontSize="small" />
            </IconButton>
          </Tooltip>

          <Divider orientation="vertical" flexItem sx={{ mx: 0.5, my: 0.5 }} />

          {/* Links */}
          <Tooltip title={editor.isActive('link') ? 'Edit Link' : 'Add Link'}>
            <IconButton
              size="small"
              onClick={openLinkDialog}
              color={editor.isActive('link') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('link') ? 'action.selected' : 'transparent' }}
            >
              <InsertLinkIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          {editor.isActive('link') && (
            <Tooltip title="Remove Link">
              <IconButton
                size="small"
                onClick={() => editor.chain().focus().unsetLink().run()}
                sx={{ p: 0.7 }}
              >
                <LinkOffIcon fontSize="small" />
              </IconButton>
            </Tooltip>
          )}

          {/* Document Link (Internal Document Library Picker) */}
          <Tooltip title="Link to Library Document">
            <IconButton
              size="small"
              onClick={() => {
                setDocSearchQuery('');
                setDocLinkDialogOpen(true);
              }}
              color="default"
              sx={{ p: 0.7 }}
            >
              <ArticleIcon fontSize="small" className="text-indigo-600 dark:text-indigo-400" />
            </IconButton>
          </Tooltip>

          {/* Image Upload / Insert Button */}
          <Tooltip title="Insert Image (Upload / Storage / URL)">
            <span>
              <IconButton
                size="small"
                onClick={() => {
                  setImageDialogOpen(true);
                  setSelectedFile(null);
                  setFilePreview(null);
                  setUploadError('');
                }}
                disabled={isUploading}
                color={isUploading ? 'primary' : 'default'}
                sx={{ p: 0.7 }}
              >
                {isUploading ? <CircularProgress size={16} /> : <ImageIcon fontSize="small" />}
              </IconButton>
            </span>
          </Tooltip>

          {/* Table Menu */}
          <Tooltip title="Table Tools">
            <IconButton
              size="small"
              onClick={(e) => setTableAnchorEl(e.currentTarget)}
              color={editor.isActive('table') ? 'primary' : 'default'}
              sx={{ p: 0.7, bgcolor: editor.isActive('table') ? 'action.selected' : 'transparent' }}
            >
              <TableChartIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Menu
            anchorEl={tableAnchorEl}
            open={Boolean(tableAnchorEl)}
            onClose={() => setTableAnchorEl(null)}
          >
            <MenuItem
              onClick={() => {
                editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
                setTableAnchorEl(null);
              }}
            >
              Insert 3x3 Table
            </MenuItem>
            <Divider />
            <MenuItem
              onClick={() => {
                editor.chain().focus().addColumnAfter().run();
                setTableAnchorEl(null);
              }}
              disabled={!editor.can().addColumnAfter()}
            >
              Add Column After
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().deleteColumn().run();
                setTableAnchorEl(null);
              }}
              disabled={!editor.can().deleteColumn()}
            >
              Delete Column
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().addRowAfter().run();
                setTableAnchorEl(null);
              }}
              disabled={!editor.can().addRowAfter()}
            >
              Add Row After
            </MenuItem>
            <MenuItem
              onClick={() => {
                editor.chain().focus().deleteRow().run();
                setTableAnchorEl(null);
              }}
              disabled={!editor.can().deleteRow()}
            >
              Delete Row
            </MenuItem>
            <Divider />
            <MenuItem
              onClick={() => {
                editor.chain().focus().deleteTable().run();
                setTableAnchorEl(null);
              }}
              disabled={!editor.can().deleteTable()}
              sx={{ color: 'error.main' }}
            >
              Delete Table
            </MenuItem>
          </Menu>
        </Box>
      )}

      {/* Uploading Progress Overlay Banner */}
      {isUploading && (
        <Box className="bg-indigo-50 dark:bg-indigo-950/70 border-b border-indigo-200 dark:border-indigo-800 px-4 py-2 flex items-center gap-3 text-indigo-700 dark:text-indigo-300 text-xs font-semibold">
          <CircularProgress size={14} color="inherit" />
          <span>Uploading image to server storage and generating link...</span>
        </Box>
      )}

      {/* Editor Content Area */}
      <Box
        sx={{
          minHeight,
          p: 3,
          cursor: editable ? 'text' : 'default',
          '& .tiptap': {
            outline: 'none',
            minHeight: '280px',
            '& p.is-editor-empty:first-child::before': {
              color: '#94a3b8',
              content: 'attr(data-placeholder)',
              float: 'left',
              height: 0,
              pointerEvents: 'none',
            },
            '& h1': { fontSize: '1.875rem', fontWeight: 800, marginTop: '1rem', marginBottom: '0.5rem', lineHeight: 1.2 },
            '& h2': { fontSize: '1.5rem', fontWeight: 700, marginTop: '0.875rem', marginBottom: '0.375rem', lineHeight: 1.25 },
            '& h3': { fontSize: '1.25rem', fontWeight: 600, marginTop: '0.75rem', marginBottom: '0.25rem', lineHeight: 1.3 },
            '& h4': { fontSize: '1.1rem', fontWeight: 600, marginTop: '0.625rem', marginBottom: '0.25rem' },
            '& p': { marginY: '0.5rem', lineHeight: 1.65 },
            '& ul': { listStyleType: 'disc', paddingLeft: '1.5rem', marginY: '0.5rem' },
            '& ol': { listStyleType: 'decimal', paddingLeft: '1.5rem', marginY: '0.5rem' },
            '& li': { marginY: '0.25rem' },
            '& blockquote': {
              borderLeft: '4px solid #6366f1',
              paddingLeft: '1rem',
              marginY: '1rem',
              fontStyle: 'italic',
              color: '#475569',
            },
            '& pre': {
              backgroundColor: '#1e293b',
              color: '#f8fafc',
              padding: '0.75rem 1rem',
              borderRadius: '0.5rem',
              fontFamily: 'monospace',
              overflowX: 'auto',
              marginY: '0.75rem',
            },
            '& table': {
              borderCollapse: 'collapse',
              width: '100%',
              margin: '1rem 0',
              overflow: 'hidden',
              tableLayout: 'fixed',
            },
            '& th, & td': {
              border: '1px solid #cbd5e1',
              padding: '8px 12px',
              verticalAlign: 'top',
              boxSizing: 'border-box',
              position: 'relative',
            },
            '& th': {
              backgroundColor: '#f1f5f9',
              fontWeight: 'bold',
              textAlign: 'left',
            },
            '& img': {
              maxWidth: '100%',
              height: 'auto',
              borderRadius: '12px',
              margin: '14px 0',
              boxShadow: '0 4px 12px rgba(0,0,0,0.08)',
              border: '1px solid rgba(0,0,0,0.06)',
            },
            '& hr': {
              border: 'none',
              borderTop: '2px solid #e2e8f0',
              margin: '1.5rem 0',
            },
          },
        }}
        onClick={() => {
          if (editable && !editor.isFocused) {
            editor.chain().focus().run();
          }
        }}
      >
        <EditorContent editor={editor} />
      </Box>

      {/* Image Upload Dialog */}
      <Dialog
        open={imageDialogOpen}
        onClose={() => !isUploading && setImageDialogOpen(false)}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle className="font-bold flex items-center gap-2">
          <ImageIcon className="text-indigo-600" />
          Insert Image
        </DialogTitle>
        <DialogContent dividers>
          <Tabs
            value={imageTab}
            onChange={(e, val) => setImageTab(val)}
            sx={{ mb: 3 }}
          >
            <Tab label="Upload to Storage" icon={<CloudUploadIcon fontSize="small" />} iconPosition="start" sx={{ textTransform: 'none', fontWeight: 600 }} />
            <Tab label="Image URL" icon={<InsertLinkIcon fontSize="small" />} iconPosition="start" sx={{ textTransform: 'none', fontWeight: 600 }} />
          </Tabs>

          {uploadError && (
            <Alert severity="error" className="mb-3">
              {uploadError}
            </Alert>
          )}

          {/* Tab 0: Upload from Computer */}
          {imageTab === 0 && (
            <Box className="space-y-4">
              <Box
                onClick={() => !isUploading && fileInputRef.current?.click()}
                className="border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-indigo-500 dark:hover:border-indigo-400 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-slate-50/50 dark:bg-slate-800/40"
              >
                <CloudUploadIcon sx={{ fontSize: 44 }} className="text-indigo-500 mb-2" />
                <Typography variant="subtitle2" className="font-bold text-slate-800 dark:text-slate-200">
                  Click to select an image from your computer
                </Typography>
                <Typography variant="caption" className="text-slate-500 dark:text-slate-400 block mt-1">
                  Supports JPG, PNG, GIF, WEBP, SVG (Max: 10MB). Image will be stored on server and linked into the document.
                </Typography>
                <Typography variant="caption" className="text-indigo-600 dark:text-indigo-400 block mt-2 font-semibold">
                  Tip: You can also paste (Ctrl+V) or drag & drop images directly onto the editor.
                </Typography>
              </Box>

              {filePreview && (
                <Box className="p-3 border rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                  <Typography variant="caption" className="font-bold text-slate-500 mb-2 block">
                    Preview ({selectedFile?.name})
                  </Typography>
                  <img
                    src={filePreview}
                    alt="Preview"
                    className="max-h-48 rounded-lg mx-auto object-contain"
                  />
                </Box>
              )}
            </Box>
          )}

          {/* Tab 1: Image Link URL */}
          {imageTab === 1 && (
            <Box className="space-y-3 pt-2">
              <TextField
                autoFocus
                label="Direct Image URL"
                type="url"
                fullWidth
                value={imageUrl}
                onChange={(e) => setImageUrl(e.target.value)}
                placeholder="https://example.com/images/sample.png"
                helperText="Paste the direct URL to an online image"
              />
              {imageUrl.trim() && (
                <Box className="mt-2 p-2 border rounded-lg">
                  <Typography variant="caption" className="text-slate-500 block mb-1">Preview:</Typography>
                  <img
                    src={imageUrl}
                    alt="URL Preview"
                    className="max-h-36 rounded mx-auto object-contain"
                    onError={(e) => { e.target.style.display = 'none'; }}
                  />
                </Box>
              )}
            </Box>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setImageDialogOpen(false)} disabled={isUploading}>
            Cancel
          </Button>
          {imageTab === 0 ? (
            selectedFile ? (
              <Button
                variant="contained"
                onClick={() => uploadFileAndInsert(selectedFile, editor)}
                disabled={isUploading}
                startIcon={isUploading ? <CircularProgress size={16} color="inherit" /> : <CloudUploadIcon />}
              >
                {isUploading ? 'Uploading...' : 'Upload & Insert'}
              </Button>
            ) : (
              <Button
                variant="contained"
                onClick={() => fileInputRef.current?.click()}
                disabled={isUploading}
                startIcon={<CloudUploadIcon />}
              >
                Choose Image
              </Button>
            )
          ) : (
            <Button
              variant="contained"
              onClick={handleInsertImageUrl}
              disabled={!imageUrl.trim()}
            >
              Insert Image
            </Button>
          )}
        </DialogActions>
      </Dialog>

      {/* Link Dialog */}
      <Dialog open={linkDialogOpen} onClose={() => setLinkDialogOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Insert / Edit Link</DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            margin="dense"
            label="URL (e.g. https://example.com)"
            type="url"
            fullWidth
            variant="outlined"
            value={linkUrl}
            onChange={(e) => setLinkUrl(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                handleSetLink();
              }
            }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setLinkDialogOpen(false)}>Cancel</Button>
          <Button variant="contained" onClick={handleSetLink}>Save</Button>
        </DialogActions>
      </Dialog>

      {/* Document Link Picker Dialog */}
      <Dialog
        open={docLinkDialogOpen}
        onClose={() => setDocLinkDialogOpen(false)}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle className="font-bold flex items-center justify-between">
          <div className="flex items-center gap-2">
            <ArticleIcon className="text-indigo-600 dark:text-indigo-400" />
            <span>Insert Link to Company Document</span>
          </div>
          <IconButton size="small" onClick={() => setDocLinkDialogOpen(false)}>
            <ClearIcon fontSize="small" />
          </IconButton>
        </DialogTitle>
        <DialogContent dividers className="space-y-3">
          <Typography variant="caption" className="text-slate-500 block">
            Search documents by title or keywords. Clicking a document will insert an interactive reference link.
          </Typography>

          <TextField
            autoFocus
            fullWidth
            size="small"
            placeholder="Search documents, policies, SOPs..."
            value={docSearchQuery}
            onChange={(e) => setDocSearchQuery(e.target.value)}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <SearchIcon fontSize="small" className="text-slate-400" />
                </InputAdornment>
              ),
              endAdornment: docSearchQuery ? (
                <InputAdornment position="end">
                  <IconButton size="small" onClick={() => setDocSearchQuery('')}>
                    <ClearIcon fontSize="small" />
                  </IconButton>
                </InputAdornment>
              ) : null,
            }}
          />

          <Box className="max-h-64 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-xl">
            {isSearchingDocs ? (
              <Box className="py-8 flex flex-col items-center justify-center gap-2 text-slate-500">
                <CircularProgress size={24} />
                <Typography variant="caption">Searching library...</Typography>
              </Box>
            ) : docSearchResults.length === 0 ? (
              <Box className="py-8 text-center text-slate-400 text-xs">
                No matching documents found.
              </Box>
            ) : (
              <List disablePadding>
                {docSearchResults.map((item) => (
                  <ListItem key={item.id} disablePadding divider>
                    <ListItemButton
                      onClick={() => handleInsertDocumentLink(item)}
                      sx={{ py: 1.5, px: 2 }}
                    >
                      <ListItemText
                        primary={
                          <div className="flex items-center gap-2">
                            <ArticleIcon fontSize="small" className="text-indigo-600 dark:text-indigo-400" />
                            <span className="font-semibold text-sm text-slate-800 dark:text-slate-100">
                              {item.title}
                            </span>
                          </div>
                        }
                        secondary={
                          <div className="flex items-center gap-2 mt-1">
                            {item.department && (
                              <Chip label={item.department.name || item.department} size="small" variant="outlined" sx={{ fontSize: '0.65rem', height: 20 }} />
                            )}
                            {item.type && (
                              <Chip label={item.type.name || item.type} size="small" color="primary" variant="outlined" sx={{ fontSize: '0.65rem', height: 20 }} />
                            )}
                            {item.author && (
                              <span className="text-[11px] text-slate-400">By {item.author.name || item.author}</span>
                            )}
                          </div>
                        }
                      />
                    </ListItemButton>
                  </ListItem>
                ))}
              </List>
            )}
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDocLinkDialogOpen(false)}>Cancel</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
