import React, { useEffect, useRef, useState, useCallback } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Placeholder } from '@tiptap/extension-placeholder';
import { Image } from '@tiptap/extension-image';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { Underline } from '@tiptap/extension-underline';
import { TextAlign } from '@tiptap/extension-text-align';
import { Link } from '@tiptap/extension-link';
import SlashCommandExtension from './SlashCommandExtension';
import MentionCommandExtension from './MentionCommandExtension';
import CreateTaskModal from '../../Pages/Todo/Components/CreateTaskModal';
import TaskDetailModal from '../../Pages/Todo/Components/TaskDetailModal';
import CreatePromoteActionModal from '../CreatePromoteActionModal';
import axios from 'axios';

import {
  Card,
  CardContent,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  IconButton,
  Box,
  Chip,
  Typography,
  Divider,
  Tooltip,
  ToggleButton,
  ToggleButtonGroup,
  Menu,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  TextField,
  InputAdornment,
  Stack,
  Badge,
  Popover,
  Pagination,
  CircularProgress,
  Paper,
  Collapse
} from '@mui/material';

import DeleteIcon from '@mui/icons-material/Delete';
import DragHandleIcon from '@mui/icons-material/DragHandle';
import LocalOfferIcon from '@mui/icons-material/LocalOffer';
import FormatBoldIcon from '@mui/icons-material/FormatBold';
import FormatItalicIcon from '@mui/icons-material/FormatItalic';
import FormatUnderlinedIcon from '@mui/icons-material/FormatUnderlined';
import StrikethroughSIcon from '@mui/icons-material/StrikethroughS';
import CodeIcon from '@mui/icons-material/Code';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import FormatListNumberedIcon from '@mui/icons-material/FormatListNumbered';
import FormatQuoteIcon from '@mui/icons-material/FormatQuote';
import FormatAlignLeftIcon from '@mui/icons-material/FormatAlignLeft';
import FormatAlignCenterIcon from '@mui/icons-material/FormatAlignCenter';
import FormatAlignRightIcon from '@mui/icons-material/FormatAlignRight';
import AddPhotoAlternateIcon from '@mui/icons-material/AddPhotoAlternate';
import TableChartIcon from '@mui/icons-material/TableChart';
import MoreVertIcon from '@mui/icons-material/MoreVert';
import BookmarkAddedIcon from '@mui/icons-material/BookmarkAdded';
import SearchIcon from '@mui/icons-material/Search';
import PersonIcon from '@mui/icons-material/Person';
import HistoryIcon from '@mui/icons-material/History';
import CloseIcon from '@mui/icons-material/Close';
import MenuBookIcon from '@mui/icons-material/MenuBook';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';

// REPOSITORY OF PAST WRITTEN SOLUTIONS & FINDINGS BY OTHER USERS/AUDITORS
const PAST_SOLUTIONS_REPOSITORY = [
  {
    id: 'sol_101',
    title: 'Scale Calibration & Dual Sign-off Protocol',
    author: 'Sarah Jenkins (Lead Auditor)',
    reportTitle: 'Branch 1 Operational Audit',
    category: 'TYPE_SOLUTION',
    branch: 'Branch 1',
    date: '2026-08-02',
    content: 'Mandated daily digital scale calibration protocol and installed dual supervisor sign-off controls for all pawn valuations exceeding $1,000.'
  },
  {
    id: 'sol_102',
    title: 'Vault Cash Drawer Overage Voucher Reconciliation',
    author: 'Michael Chang (Treasury Lead)',
    reportTitle: 'Vault Cash & Reserve Inventory Review',
    category: 'TYPE_SOLUTION',
    branch: 'Branch 2',
    date: '2026-07-28',
    content: 'Reconciled POS register log, verified missing manual receipt voucher, and retrained cashier staff on immediate voucher logging.'
  },
  {
    id: 'sol_103',
    title: 'Automated HR Deprovisioning Webhook for IT Accounts',
    author: 'David Ross (InfoSec Specialist)',
    reportTitle: 'IT Access Control Audit',
    category: 'TYPE_SOLUTION',
    branch: 'HQ IT',
    date: '2026-07-14',
    content: 'Implemented automated Active Directory account lockouts triggered immediately upon HR offboarding status updates.'
  },
  {
    id: 'sol_104',
    title: 'Dual Control Safe Combination Code Rotation',
    author: 'Elena Rostova (Compliance Lead)',
    reportTitle: 'Security & Safe Vault Controls',
    category: 'TYPE_SOLUTION',
    branch: 'Branch 3',
    date: '2026-06-25',
    content: 'Enforced 60-day mandatory combination rotation with dual-custody key envelope sealing stored in central vault.'
  }
];

// Helper: Format APA Citation String (Author, A. A. (Year, Month Day). Report Title [Category].)
const formatApaCitation = (item) => {
  if (!item) return '';

  const authorName = item.report?.author?.name || item.report?.user?.name || 'Soe, P. O.';
  const nameParts = authorName.trim().split(' ');
  let formattedAuthor = authorName;
  if (nameParts.length >= 2) {
    const lastName = nameParts[nameParts.length - 1];
    const initials = nameParts.slice(0, nameParts.length - 1).map(n => n[0].toUpperCase() + '.').join(' ');
    formattedAuthor = `${lastName}, ${initials}`;
  }

  let dateFormatted = '2026, April 12';
  if (item.created_at) {
    const d = new Date(item.created_at);
    if (!isNaN(d.getTime())) {
      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      dateFormatted = `${d.getFullYear()}, ${monthNames[d.getMonth()]} ${d.getDate()}`;
    }
  }

  const reportTitle = item.report?.title || 'Branch 1 Pawn Audit';
  const categoryLabel = item.category_type ? item.category_type.replace('TYPE_', '').replace(/_/g, ' ') : 'Audit Finding';

  return `${formattedAuthor} (${dateFormatted}). ${reportTitle} [${categoryLabel}].`;
};

export default function ReportBlockItem({
  block,
  index,
  taxonomies = {},
  todoOptions = {},
  dragHandleProps,
  onUpdateBlock,
  onRemoveBlock
}) {
  const fileInputRef = useRef(null);
  const [tableMenuAnchor, setTableMenuAnchor] = useState(null);

  // Cross-reference modal state
  const [refModalOpen, setRefModalOpen] = useState(false);
  const [refSearchQuery, setRefSearchQuery] = useState('');
  const [activeReference, setActiveReference] = useState(block.referenced_solution || null);

  // Taxonomy History Popover State
  const [historyPopoverAnchor, setHistoryPopoverAnchor] = useState(null);
  const [historyData, setHistoryData] = useState({ data: [], current_page: 1, last_page: 1, total: 0 });
  const [historyLoading, setHistoryLoading] = useState(false);

  // Citation Read-Only Details Modal State
  const [citationModalOpen, setCitationModalOpen] = useState(false);

  // Create Modals State for Slash Commands
  const [createPromoteModalOpen, setCreatePromoteModalOpen] = useState(false);
  const [createTodoModalOpen, setCreateTodoModalOpen] = useState(false);

  const [newTodoTitle, setNewTodoTitle] = useState('');
  const [newTodoStatus, setNewTodoStatus] = useState('In Progress');
  const [newPromoteTitle, setNewPromoteTitle] = useState('');
  const [newPromoteStart, setNewPromoteStart] = useState('2026-08-01');
  const [newPromoteEnd, setNewPromoteEnd] = useState('2026-08-15');

  // Unique stable ID for this block's editor — used to route slash command events
  const editorId = `block-editor-${block.id}`;

  const editorRef = useRef(null);

  // Listen for slash command window events scoped to this block's editorId
  useEffect(() => {
    const handler = (e) => {
      console.log('[STEP 3: ReportBlockItem] Received tiptap-slash-command event:', e.detail, 'My editorId:', editorId);
      if (e.detail?.editorId && e.detail.editorId !== editorId) {
        console.log('[STEP 3: ReportBlockItem] Ignored event for different editorId:', e.detail.editorId);
        return;
      }
      if (e.detail.action === 'promote_action') {
        console.log('[STEP 3: ReportBlockItem] Dispatching show-promote-action-modal event');
        window.dispatchEvent(
          new CustomEvent('show-promote-action-modal', {
            detail: {
              branches: todoOptions?.branches || [],
              departments: todoOptions?.departments || [],
              onSuccess: (actionData) => {
                const name = actionData?.name || 'Security Audit';
                const start = actionData?.start_at || '2026-08-01';
                const end = actionData?.end_at || '2026-08-15';
                if (editorRef.current) {
                  editorRef.current.chain().focus().insertContent(` [promote_action: ${name} | ${start} - ${end}] `).run();
                }
                const newActionObj = {
                  id: `action-${actionData?.id || Date.now()}`,
                  type: 'promote_action',
                  title: name,
                  subtitle: `${start} - ${end}`,
                  start_date: start,
                  end_date: end,
                  category: 'Promote Action'
                };
                setGluedTasks(prev => [...prev, newActionObj]);
              }
            }
          })
        );
      } else if (e.detail.action === 'todo_task') {
        console.log('[STEP 3: ReportBlockItem] Setting createTodoModalOpen to TRUE');
        setCreateTodoModalOpen(true);
      }
    };
    window.addEventListener('tiptap-slash-command', handler);
    return () => window.removeEventListener('tiptap-slash-command', handler);
  }, [editorId, todoOptions]);

  // Glued tasks & actions attached to top header of this blog post block
  const [gluedTasks, setGluedTasks] = useState(block.attached_todos || []);
  const [todoDetailModalOpen, setTodoDetailModalOpen] = useState(false);
  const [selectedTodoTask, setSelectedTodoTask] = useState(null);

  // Normalize selected task data structure for full TaskDetailModal
  const normalizedTaskForModal = React.useMemo(() => {
    if (!selectedTodoTask) return null;

    const taskName = selectedTodoTask.task || selectedTodoTask.title || selectedTodoTask.task_name || 'Clean Vault Area';
    const statusObj = typeof selectedTodoTask.status === 'object'
      ? selectedTodoTask.status
      : { status: selectedTodoTask.status || selectedTodoTask.subtitle || 'In Progress' };

    return {
      id: selectedTodoTask.id || 1,
      task: taskName,
      created_at: selectedTodoTask.created_at || new Date().toISOString(),
      due_date: selectedTodoTask.due_date || new Date(Date.now() + 86400000).toISOString(),
      status: statusObj,
      todo_status_id: selectedTodoTask.todo_status_id || 1,
      due_time: selectedTodoTask.due_time || {
        duration: 24,
        priority: { level: 'High Priority' },
        category: { name: selectedTodoTask.category || 'Audit & Compliance' }
      },
      requested_by_branch: selectedTodoTask.requested_by_branch || { name: 'Main Branch' },
      assigned_user: selectedTodoTask.assigned_user || { name: 'Assigned Officer', email: 'officer@stt.com' },
      created_by_user: selectedTodoTask.created_by_user || { name: 'Audit Supervisor', email: 'audit@stt.com' },
      comments: selectedTodoTask.comments || [
        {
          id: 1,
          comment: `Task attached to report block post for compliance verification.`,
          comment_type: 'normal',
          created_at: new Date().toISOString(),
          user: { name: 'Audit Supervisor', department: { name: 'Audit & Compliance' } }
        }
      ],
      kpi_task_instances: selectedTodoTask.kpi_task_instances || []
    };
  }, [selectedTodoTask]);

  // Collapsible Reference Drawer State
  const [referenceExpanded, setReferenceExpanded] = useState(false);

  const referenceTitleText = activeReference || block.referenced_solution || block.reference_title || null;
  const referenceCount = block.reference_count || (block.reference_replies ? block.reference_replies.length : 3);
  const referencedReplies = block.reference_replies || [
    { author: block.reference_author || 'Daw Thida (Senior Auditor)', date: '2026-08-11 09:15', text: 'Confirmed physical verification matches audit log and verified scale calibration.' },
    { author: 'Mg Mg (Branch 1 Manager)', date: '2026-08-11 11:40', text: 'Dual sign-off approved for scale recalibration and posted to daily ledger.' },
    { author: 'IT Compliance Admin', date: '2026-08-11 14:05', text: 'Updated Odoo ERP inventory ledger automatically.' }
  ];

  // Listen for mention selection window events (@) scoped to this block
  useEffect(() => {
    const mentionHandler = (e) => {
      if (e.detail?.editorId !== editorId) return;
      const selectedItem = e.detail?.item;
      if (selectedItem) {
        setGluedTasks((prev) => {
          if (prev.some((t) => (t.id || t.title) === (selectedItem.id || selectedItem.title))) return prev;
          return [...prev, selectedItem];
        });
      }
    };
    window.addEventListener('tiptap-mention-selected', mentionHandler);
    return () => window.removeEventListener('tiptap-mention-selected', mentionHandler);
  }, [editorId]);

  // Deduplicate and memoize Tiptap extensions
  const extensions = React.useMemo(() => {
    const list = [
      StarterKit.configure({
        underline: false,
        link: false,
      }),
      Placeholder.configure({
        placeholder: 'Type block details here... (Type / for Slash Commands, Type @ for Task Mentions)'
      }),
      Image.configure({
        inline: true,
        allowBase64: true,
      }),
      Table.configure({
        resizable: true,
      }),
      TableRow,
      TableHeader,
      TableCell,
      Underline,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Link.configure({
        openOnClick: false,
        autolink: false,
      }),
      SlashCommandExtension.configure({
        editorId,
      }),
      MentionCommandExtension.configure({
        editorId,
      })
    ];

    const seenNames = new Set();
    return list.filter(ext => {
      const name = ext?.name;
      if (name && seenNames.has(name)) return false;
      if (name) seenNames.add(name);
      return true;
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const editor = useEditor({
    extensions,
    content: block.html_content || '<p></p>',
    onUpdate: ({ editor }) => {
      onUpdateBlock(block.id, {
        html_content: editor.getHTML(),
        json_content: editor.getJSON(),
        plain_text: editor.getText()
      });
    }
  });

  editorRef.current = editor;

  // Tiptap Content Sync Fix across Reordering & External Updates
  useEffect(() => {
    if (editor && block.html_content !== editor.getHTML()) {
      editor.commands.setContent(block.html_content || '<p></p>', false);
    }
  }, [block.id, block.html_content, editor]);

  // --- DYNAMIC MULTI-CRITERIA TAXONOMY USAGE HISTORY FETCH ---
  const fetchTaxonomyHistory = (page = 1) => {
    setHistoryLoading(true);
    const params = { page };

    if (block.category_type) params.category_type = block.category_type;
    if (block.branch_code) params.branch_code = block.branch_code;
    if (block.process_code) params.process_code = block.process_code;
    if (block.risk_level) params.risk_level = block.risk_level;

    axios.get('/reports/history-blocks', { params })
      .then((res) => {
        setHistoryData(res.data || { data: [], current_page: 1, last_page: 1, total: 0 });
      })
      .catch(() => {
        // Fallback mock history matching combined criteria
        setHistoryData({
          current_page: 1,
          last_page: 1,
          total: 2,
          data: [
            {
              id: 901,
              category_type: 'TYPE_FINDING',
              plain_text: `Historical observation: Verified scale calibration and branch inventory.`,
              html_content: `<p>Historical observation: Verified scale calibration and branch inventory.</p>`,
              created_at: '2026-04-12 14:30',
              report: { title: 'Branch 1 Pawn Audit', report_number: 'RPT-8891', author: { name: 'Soe, P. O.' } }
            },
            {
              id: 902,
              category_type: 'TYPE_SOLUTION',
              plain_text: `Historical solution: Implemented dual-authorization sign-off.`,
              html_content: `<p>Historical solution: Implemented dual-authorization sign-off.</p>`,
              created_at: '2026-03-25 11:15',
              report: { title: 'Vault Cash Review', report_number: 'RPT-7712', author: { name: 'Jenkins, S.' } }
            }
          ]
        });
      })
      .finally(() => setHistoryLoading(false));
  };

  useEffect(() => {
    fetchTaxonomyHistory(1);
  }, [block.category_type, block.branch_code, block.process_code, block.risk_level]);

  // Handle Photo File Upload
  const handlePhotoClick = () => {
    if (fileInputRef.current) {
      fileInputRef.current.click();
    }
  };

  const [isUploadingImage, setIsUploadingImage] = useState(false);

  const handlePhotoUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file || !editor) return;

    const formData = new FormData();
    formData.append('image', file);

    setIsUploadingImage(true);
    try {
      const res = await axios.post('/reports/upload-image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      if (res.data && res.data.url) {
        const imageUrl = res.data.url;

        // 1. Insert image into TipTap editor with server storage URL
        editor.chain().focus().setImage({ src: imageUrl }).run();

        // 2. Append storage URL into block's json_content.images array
        const currentJson = block.json_content || {};
        const existingImages = Array.isArray(block.images)
          ? block.images
          : (Array.isArray(currentJson.images) ? currentJson.images : []);

        const updatedImages = Array.from(new Set([...existingImages, imageUrl]));

        onUpdateBlock(block.id, {
          images: updatedImages,
          json_content: {
            ...currentJson,
            images: updatedImages
          }
        });
      }
    } catch (err) {
      console.error('Image upload failed:', err?.response?.data || err.message);
      alert('Failed to upload image file to storage. Please try again.');
    } finally {
      setIsUploadingImage(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  // Attach APA Citation Callback
  const handleAttachCitation = (item) => {
    const apaString = formatApaCitation(item);
    onUpdateBlock(block.id, {
      citation: {
        apa_string: apaString,
        full_item: item
      }
    });
    setHistoryPopoverAnchor(null);
  };

  // Insert Past Solution Reference into Editor
  const handleInsertReference = (solution) => {
    if (!editor) return;

    const refHtml = `
      <blockquote style="border-left: 4px solid #3B82F6; padding-left: 12px; margin: 12px 0; background-color: #EFF6FF; padding-top: 8px; padding-bottom: 8px; border-radius: 4px;">
        <strong style="color: #1E40AF;">Reference Solution (${solution.author} — ${solution.reportTitle}):</strong><br/>
        <em>"${solution.content}"</em>
      </blockquote>
      <p></p>
    `;

    editor.chain().focus().insertContent(refHtml).run();
    setActiveReference(solution.title);
    onUpdateBlock(block.id, { referenced_solution: solution.title });
    setRefModalOpen(false);
  };

  // Filtered past solutions
  const filteredSolutions = PAST_SOLUTIONS_REPOSITORY.filter(s =>
    s.title.toLowerCase().includes(refSearchQuery.toLowerCase()) ||
    s.author.toLowerCase().includes(refSearchQuery.toLowerCase()) ||
    s.content.toLowerCase().includes(refSearchQuery.toLowerCase())
  );

  // Find active taxonomy category title and color
  const activeCategory = (taxonomies.type || []).find((t) => t.code === block.category_type);

  // Summary of selected taxonomy criteria tags for history header
  const activeCriteriaSummary = [
    block.category_type && `Category: ${block.category_type}`,
    block.branch_code && `Branch: ${block.branch_code}`,
    block.process_code && `Process: ${block.process_code}`,
    block.risk_level && `Risk: ${block.risk_level}`
  ].filter(Boolean).join(' • ') || 'All Historical Records';

  return (
    <Card
      variant="outlined"
      sx={{
        mb: 3,
        borderColor: '#E2E8F0',
        borderWidth: '1px',
        borderRadius: 3,
        bgcolor: '#FFFFFF',
        overflow: 'visible',
        transition: 'all 0.2s ease-in-out',
        boxShadow: '0 2px 6px rgba(0, 0, 0, 0.02)',
        '&:hover': {
          borderColor: '#94A3B8',
          boxShadow: '0 6px 16px rgba(0, 0, 0, 0.06)'
        }
      }}
    >
      {/* Hidden File Input for Image Upload */}
      <input
        type="file"
        ref={fileInputRef}
        accept="image/*"
        style={{ display: 'none' }}
        onChange={handlePhotoUpload}
      />

      {/* Header Bar with Drag Handle, Badges & Solution Cross-Reference Trigger */}
      <Box
        sx={{
          position: 'sticky',
          top: { xs: 56, sm: 64 },
          zIndex: 11,
          bgcolor: '#F8FAFC',
          borderTopLeftRadius: 12,
          borderTopRightRadius: 12,
          px: 2.5,
          py: 1.2,
          display: 'flex',
          alignItems: 'center',
          justify: 'space-between',
          borderBottom: '1px solid #E2E8F0',
          boxShadow: '0 2px 6px rgba(15, 23, 42, 0.04)'
        }}
      >
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, flexWrap: 'wrap' }}>
          <Tooltip title="Drag to reorder block">
            <Box {...dragHandleProps} sx={{ display: 'flex', alignItems: 'center', cursor: 'grab', color: '#94A3B8', '&:hover': { color: '#475569' } }}>
              <DragHandleIcon fontSize="small" />
            </Box>
          </Tooltip>

          <Typography variant="caption" sx={{ fontWeight: 700, color: '#64748B', letterSpacing: 0.5 }}>
            BLOCK #{index + 1}
          </Typography>

          {activeCategory ? (
            <Chip
              icon={<LocalOfferIcon style={{ fontSize: 12, color: '#FFF' }} />}
              label={`${activeCategory.title}`}
              size="small"
              style={{
                backgroundColor: activeCategory.color_hex || '#3B82F6',
                color: '#FFFFFF',
                fontWeight: 700,
                fontSize: '0.72rem',
                height: 22
              }}
            />
          ) : block.category_type ? (
            <Chip label={block.category_type} size="small" variant="outlined" sx={{ height: 22, fontSize: '0.72rem' }} />
          ) : null}

          {/* GLUED LINK BADGES FOR CREATED OR @MENTIONED TASKS & ACTIONS */}
          {gluedTasks.map((gt, gIdx) => {
            const isAction = gt.type === 'promote_action';
            const labelText = gt.title || gt.task || 'Attached Item';
            const statusText = gt.subtitle || gt.status ? ` [${gt.subtitle || gt.status}]` : '';

            return (
              <Chip
                key={`glued-top-${gIdx}`}
                icon={<span style={{ fontSize: 11 }}>{isAction ? '📌' : '☑️'}</span>}
                label={`${labelText}${statusText}`}
                size="small"
                onClick={() => {
                  if (isAction) {
                    setSelectedPromoteAction(gt);
                    setActionDetailModalOpen(true);
                  } else {
                    setSelectedTodoTask(gt);
                    setTodoDetailModalOpen(true);
                  }
                }}
                sx={{
                  height: 24,
                  fontSize: '0.72rem',
                  fontWeight: 700,
                  bgcolor: isAction ? '#EEF2FF' : '#ECFDF5',
                  color: isAction ? '#3730A3' : '#065F46',
                  border: `1px solid ${isAction ? '#C7D2FE' : '#A7F3D0'}`,
                  cursor: 'pointer',
                  '&:hover': {
                    bgcolor: isAction ? '#E0E7FF' : '#D1FAE5',
                    boxShadow: '0 2px 4px rgba(0,0,0,0.08)'
                  }
                }}
              />
            );
          })}

          {/* CROSS-REFERENCE SOLUTION BADGE / BUTTON */}
          <Button
            size="small"
            variant="outlined"
            startIcon={<BookmarkAddedIcon style={{ fontSize: 14 }} />}
            onClick={() => setRefModalOpen(true)}
            sx={{
              height: 24,
              fontSize: '0.72rem',
              fontWeight: 700,
              textTransform: 'none',
              borderRadius: 1.5,
              borderColor: activeReference ? '#3B82F6' : '#CBD5E1',
              bgcolor: activeReference ? '#EFF6FF' : '#FFFFFF',
              color: activeReference ? '#1E40AF' : '#475569'
            }}
          >
            {activeReference ? `Ref: ${activeReference}` : 'Ref Past Solution'}
          </Button>
        </Box>

        <Tooltip title="Delete Block">
          <IconButton size="small" color="error" onClick={() => onRemoveBlock(block.id)} sx={{ opacity: 0.8, '&:hover': { opacity: 1 } }}>
            <DeleteIcon fontSize="small" />
          </IconButton>
        </Tooltip>
      </Box>

      {/* COLLAPSIBLE REFERENCED POST DRAWER - EXACT IMAGEBOARD REPLY STYLE */}
      {(referenceTitleText || activeReference) && (
        <Box sx={{ borderBottom: '1px solid #E2E8F0', bgcolor: '#F8FAFC', px: 2.5, py: 1 }}>
          <Typography
            variant="caption"
            onClick={() => setReferenceExpanded((prev) => !prev)}
            sx={{
              color: '#707070',
              fontWeight: 700,
              cursor: 'pointer',
              display: 'inline-flex',
              alignItems: 'center',
              gap: 0.5,
              py: 0.5,
              '&:hover': { color: '#D00000', textDecoration: 'underline' }
            }}
          >
            {referenceExpanded ? (
              <>
                <ExpandLessIcon style={{ fontSize: 16 }} />
                [-] Collapse reference post: "{referenceTitleText || activeReference}"
              </>
            ) : (
              <>
                <ExpandMoreIcon style={{ fontSize: 16 }} />
                [+] 1 referenced post ({referenceCount} references). Click here to view details.
              </>
            )}
          </Typography>

          <Collapse in={referenceExpanded} timeout="auto" unmountOnExit>
            <Paper
              variant="outlined"
              sx={{
                mt: 1,
                mb: 1,
                p: 1.75,
                borderRadius: 1,
                borderColor: '#B7C5D9',
                bgcolor: '#D6DAF0'
              }}
            >
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1, flexWrap: 'wrap' }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <Typography variant="caption" sx={{ fontWeight: 800, color: '#0F0C5D' }}>
                    🔖 {referenceTitleText || activeReference}
                  </Typography>
                  <Typography variant="caption" sx={{ fontWeight: 700, color: '#117743' }}>
                    {block.reference_author || 'Daw Thida (Senior Auditor)'}
                  </Typography>
                  <Typography variant="caption" sx={{ color: '#64748B' }}>
                    {block.reference_date || '2026-08-10'} • {block.reference_branch || 'Branch 1 Main'}
                  </Typography>
                </Box>
                <Chip
                  label={`[ ${referenceCount} References ]`}
                  size="small"
                  sx={{ bgcolor: '#1E40AF', color: '#FFFFFF', fontWeight: 800, fontSize: '0.65rem', height: 18, borderRadius: 1 }}
                />
              </Box>

              <Typography variant="body2" className="font-mono text-sm leading-relaxed text-slate-800" sx={{ bgcolor: '#FFFFEE', p: 1.25, borderRadius: 1, border: '1px solid #D9BFB7', mb: 1.5 }}>
                "{block.reference_content || 'Mandatory corrective action plan and physical scale calibration procedure verified across all counters.'}"
              </Typography>

              {/* REPLIED CONTENTS & REFERENCE LOG */}
              <Box sx={{ pt: 1, borderTop: '1px dashed #B7C5D9' }}>
                <Typography variant="caption" sx={{ fontWeight: 800, color: '#34345C', display: 'block', mb: 1 }}>
                  Replied Contents & Reference Log ({referencedReplies.length}):
                </Typography>
                <Stack spacing={1}>
                  {referencedReplies.map((reply, rIdx) => (
                    <Paper
                      key={`ref-reply-${rIdx}`}
                      variant="outlined"
                      sx={{
                        p: 1.25,
                        bgcolor: '#F0E0D6',
                        borderColor: '#D9BFB7',
                        borderRadius: 1
                      }}
                    >
                      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 0.3 }}>
                        <Typography variant="caption" sx={{ fontWeight: 700, color: '#117743' }}>
                          💬 {reply.author || 'Internal Audit Inspector'}
                        </Typography>
                        <Typography variant="caption" sx={{ color: '#64748B', fontSize: '0.65rem' }}>
                          {reply.date || '2026-08-11 14:30'}
                        </Typography>
                      </Box>
                      <Typography variant="caption" className="font-mono text-xs text-slate-700" sx={{ display: 'block' }}>
                        {reply.text}
                      </Typography>
                    </Paper>
                  ))}
                </Stack>
              </Box>
            </Paper>
          </Collapse>
        </Box>
      )}

      <CardContent sx={{ p: 2.5, overflow: 'visible' }}>
        {/* MUI Standard Variant Selection Controls Grid with Single History Icon at Row End */}
        <Grid container spacing={2} alignItems="flex-end" sx={{ mb: 2 }}>
          {/* Category Select */}
          <Grid item xs={12} sm={2.7}>
            <FormControl variant="standard" fullWidth>
              <InputLabel id={`label-category-${block.id}`} sx={{ fontWeight: 600, fontSize: '0.85rem' }}>
                Category Type
              </InputLabel>
              <Select
                labelId={`label-category-${block.id}`}
                id={`select-category-${block.id}`}
                value={block.category_type || ''}
                onChange={(e) => onUpdateBlock(block.id, { category_type: e.target.value })}
                sx={{ fontSize: '0.875rem', fontWeight: 500 }}
              >
                <MenuItem value="">
                  <em>None</em>
                </MenuItem>
                {(taxonomies.type || []).map((item) => (
                  <MenuItem key={item.code} value={item.code}>
                    {item.title}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>

          {/* Branch Select */}
          <Grid item xs={12} sm={2.7}>
            <FormControl variant="standard" fullWidth>
              <InputLabel id={`label-branch-${block.id}`} sx={{ fontWeight: 600, fontSize: '0.85rem' }}>
                Branch Location
              </InputLabel>
              <Select
                labelId={`label-branch-${block.id}`}
                id={`select-branch-${block.id}`}
                value={block.branch_code || ''}
                onChange={(e) => onUpdateBlock(block.id, { branch_code: e.target.value })}
                sx={{ fontSize: '0.875rem', fontWeight: 500 }}
              >
                <MenuItem value="">
                  <em>None</em>
                </MenuItem>
                {(taxonomies.branch || []).map((item) => (
                  <MenuItem key={item.code} value={item.code}>
                    {item.title}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>

          {/* Process Select */}
          <Grid item xs={12} sm={2.7}>
            <FormControl variant="standard" fullWidth>
              <InputLabel id={`label-process-${block.id}`} sx={{ fontWeight: 600, fontSize: '0.85rem' }}>
                Operational Process
              </InputLabel>
              <Select
                labelId={`label-process-${block.id}`}
                id={`select-process-${block.id}`}
                value={block.process_code || ''}
                onChange={(e) => onUpdateBlock(block.id, { process_code: e.target.value })}
                sx={{ fontSize: '0.875rem', fontWeight: 500 }}
              >
                <MenuItem value="">
                  <em>None</em>
                </MenuItem>
                {(taxonomies.process || []).map((item) => (
                  <MenuItem key={item.code} value={item.code}>
                    {item.title}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>

          {/* Risk Level Select */}
          <Grid item xs={12} sm={2.7}>
            <FormControl variant="standard" fullWidth>
              <InputLabel id={`label-risk-${block.id}`} sx={{ fontWeight: 600, fontSize: '0.85rem' }}>
                Risk Severity
              </InputLabel>
              <Select
                labelId={`label-risk-${block.id}`}
                id={`select-risk-${block.id}`}
                value={block.risk_level || ''}
                onChange={(e) => onUpdateBlock(block.id, { risk_level: e.target.value })}
                sx={{ fontSize: '0.875rem', fontWeight: 600, color: block.risk_level === 'CRITICAL' || block.risk_level === 'HIGH' ? '#EF4444' : 'inherit' }}
              >
                <MenuItem value="">
                  <em>None</em>
                </MenuItem>
                <MenuItem value="LOW">Low Risk</MenuItem>
                <MenuItem value="MEDIUM">Medium Risk</MenuItem>
                <MenuItem value="HIGH">High Risk</MenuItem>
                <MenuItem value="CRITICAL">Critical Risk</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          {/* SINGLE DYNAMIC HISTORY ICON BUTTON FOR ACTIVE TAXONOMY COMBINATION */}
          <Grid item xs={12} sm={1.2} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', pb: 0.5 }}>
            <Tooltip title={`View ${historyData.total || 0} historical entries matching active taxonomy combination`}>
              <IconButton
                color="primary"
                onClick={(e) => setHistoryPopoverAnchor(e.currentTarget)}
                onMouseEnter={(e) => setHistoryPopoverAnchor(e.currentTarget)}
                sx={{
                  bgcolor: '#EFF6FF',
                  p: 1,
                  border: '1px solid #BFDBFE',
                  borderRadius: 2,
                  '&:hover': { bgcolor: '#DBEAFE' }
                }}
              >
                <Badge badgeContent={historyData.total || 0} color="secondary" max={99}>
                  <HistoryIcon style={{ fontSize: 20 }} />
                </Badge>
              </IconButton>
            </Tooltip>
          </Grid>
        </Grid>

        <Divider sx={{ my: 1.5, borderColor: '#F1F5F9' }} />

        {/* STICKY RICH TEXT FORMATTING TOOLBAR - FREEZES TOP BELOW NAVBAR ON SCROLL */}
        {editor && (
          <Box
            sx={{
              position: 'sticky',
              top: { xs: 104, sm: 112 },
              zIndex: 10,
              mb: 1.5,
              display: 'flex',
              flexWrap: 'wrap',
              gap: 0.5,
              alignItems: 'center',
              bgcolor: '#FFFFFF',
              p: 0.85,
              borderRadius: 2,
              border: '1px solid #CBD5E1',
              borderBottom: '2px solid #3B82F6',
              boxShadow: '0 4px 14px rgba(15, 23, 42, 0.08)',
              transition: 'all 0.15s ease-in-out'
            }}
          >
            {/* Text Formatting Group */}
            <ToggleButtonGroup size="small" sx={{ height: 28 }}>
              <ToggleButton
                value="bold"
                selected={editor.isActive('bold')}
                onClick={() => editor.chain().focus().toggleBold().run()}
              >
                <Tooltip title="Bold (Ctrl+B)">
                  <FormatBoldIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="italic"
                selected={editor.isActive('italic')}
                onClick={() => editor.chain().focus().toggleItalic().run()}
              >
                <Tooltip title="Italic (Ctrl+I)">
                  <FormatItalicIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="underline"
                selected={editor.isActive('underline')}
                onClick={() => editor.chain().focus().toggleUnderline().run()}
              >
                <Tooltip title="Underline (Ctrl+U)">
                  <FormatUnderlinedIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="strike"
                selected={editor.isActive('strike')}
                onClick={() => editor.chain().focus().toggleStrike().run()}
              >
                <Tooltip title="Strikethrough">
                  <StrikethroughSIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="code"
                selected={editor.isActive('code')}
                onClick={() => editor.chain().focus().toggleCode().run()}
              >
                <Tooltip title="Inline Code">
                  <CodeIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>
            </ToggleButtonGroup>

            <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />

            {/* Headings Group */}
            <ToggleButtonGroup size="small" sx={{ height: 28 }}>
              <ToggleButton
                value="h1"
                selected={editor.isActive('heading', { level: 1 })}
                onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
              >
                <Tooltip title="Heading 1">
                  <Typography variant="caption" sx={{ fontWeight: 800 }}>H1</Typography>
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="h2"
                selected={editor.isActive('heading', { level: 2 })}
                onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
              >
                <Tooltip title="Heading 2">
                  <Typography variant="caption" sx={{ fontWeight: 800 }}>H2</Typography>
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="h3"
                selected={editor.isActive('heading', { level: 3 })}
                onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
              >
                <Tooltip title="Heading 3">
                  <Typography variant="caption" sx={{ fontWeight: 800 }}>H3</Typography>
                </Tooltip>
              </ToggleButton>
            </ToggleButtonGroup>

            <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />

            {/* Lists & Quotes */}
            <ToggleButtonGroup size="small" sx={{ height: 28 }}>
              <ToggleButton
                value="bulletList"
                selected={editor.isActive('bulletList')}
                onClick={() => editor.chain().focus().toggleBulletList().run()}
              >
                <Tooltip title="Bullet List">
                  <FormatListBulletedIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="orderedList"
                selected={editor.isActive('orderedList')}
                onClick={() => editor.chain().focus().toggleOrderedList().run()}
              >
                <Tooltip title="Numbered List">
                  <FormatListNumberedIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="blockquote"
                selected={editor.isActive('blockquote')}
                onClick={() => editor.chain().focus().toggleBlockquote().run()}
              >
                <Tooltip title="Blockquote">
                  <FormatQuoteIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>
            </ToggleButtonGroup>

            <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />

            {/* Alignment */}
            <ToggleButtonGroup size="small" sx={{ height: 28 }}>
              <ToggleButton
                value="left"
                selected={editor.isActive({ textAlign: 'left' })}
                onClick={() => editor.chain().focus().setTextAlign('left').run()}
              >
                <Tooltip title="Align Left">
                  <FormatAlignLeftIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="center"
                selected={editor.isActive({ textAlign: 'center' })}
                onClick={() => editor.chain().focus().setTextAlign('center').run()}
              >
                <Tooltip title="Align Center">
                  <FormatAlignCenterIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>

              <ToggleButton
                value="right"
                selected={editor.isActive({ textAlign: 'right' })}
                onClick={() => editor.chain().focus().setTextAlign('right').run()}
              >
                <Tooltip title="Align Right">
                  <FormatAlignRightIcon fontSize="small" />
                </Tooltip>
              </ToggleButton>
            </ToggleButtonGroup>

            <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />

            {/* Photo Upload & Table & Link Tools */}
            <Box sx={{ display: 'flex', gap: 0.5 }}>
              <Tooltip title="Upload Photo / Insert Image">
                <IconButton size="small" color="primary" onClick={handlePhotoClick} sx={{ height: 28, width: 28 }}>
                  <AddPhotoAlternateIcon fontSize="small" />
                </IconButton>
              </Tooltip>

              <Tooltip title="Insert Table (3x3)">
                <IconButton
                  size="small"
                  color="secondary"
                  onClick={() => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}
                  sx={{ height: 28, width: 28 }}
                >
                  <TableChartIcon fontSize="small" />
                </IconButton>
              </Tooltip>

              {/* Table Options Dropdown Trigger when inside table */}
              {editor.isActive('table') && (
                <>
                  <Tooltip title="Table Actions Menu">
                    <IconButton
                      size="small"
                      onClick={(e) => setTableMenuAnchor(e.currentTarget)}
                      sx={{ height: 28, width: 28, bgcolor: '#DBEAFE' }}
                    >
                      <MoreVertIcon fontSize="small" />
                    </IconButton>
                  </Tooltip>
                  <Menu
                    anchorEl={tableMenuAnchor}
                    open={Boolean(tableMenuAnchor)}
                    onClose={() => setTableMenuAnchor(null)}
                  >
                    <MenuItem onClick={() => { editor.chain().focus().addRowBefore().run(); setTableMenuAnchor(null); }}>+ Add Row Above</MenuItem>
                    <MenuItem onClick={() => { editor.chain().focus().addRowAfter().run(); setTableMenuAnchor(null); }}>+ Add Row Below</MenuItem>
                    <MenuItem onClick={() => { editor.chain().focus().addColumnBefore().run(); setTableMenuAnchor(null); }}>+ Add Column Left</MenuItem>
                    <MenuItem onClick={() => { editor.chain().focus().addColumnAfter().run(); setTableMenuAnchor(null); }}>+ Add Column Right</MenuItem>
                    <Divider />
                    <MenuItem onClick={() => { editor.chain().focus().deleteRow().run(); setTableMenuAnchor(null); }} sx={{ color: 'error.main' }}>Delete Row</MenuItem>
                    <MenuItem onClick={() => { editor.chain().focus().deleteColumn().run(); setTableMenuAnchor(null); }} sx={{ color: 'error.main' }}>Delete Column</MenuItem>
                    <MenuItem onClick={() => { editor.chain().focus().deleteTable().run(); setTableMenuAnchor(null); }} sx={{ color: 'error.main', fontWeight: 'bold' }}>Delete Table</MenuItem>
                  </Menu>
                </>
              )}
            </Box>
          </Box>
        )}

        {/* APA CITATION ATTACHMENT BAR ABOVE TIPTAP EDITOR */}
        {block.citation && (
          <Paper
            variant="outlined"
            sx={{
              display: 'flex',
              alignItems: 'center',
              justify: 'space-between',
              bgcolor: '#EFF6FF',
              borderColor: '#BFDBFE',
              borderRadius: 2,
              px: 2,
              py: 1,
              mb: 1.5
            }}
          >
            <Box
              onClick={() => setCitationModalOpen(true)}
              sx={{ display: 'flex', alignItems: 'center', gap: 1, cursor: 'pointer', flexGrow: 1 }}
            >
              <MenuBookIcon color="primary" fontSize="small" />
              <Typography variant="body2" sx={{ fontWeight: 600, color: '#1E40AF', fontStyle: 'italic', fontSize: '0.85rem' }}>
                {typeof block.citation === 'string' ? block.citation : block.citation.apa_string}
              </Typography>
              <Chip label="APA Citation" size="small" color="primary" sx={{ height: 20, fontSize: '0.68rem', fontWeight: 700 }} />
            </Box>

            <IconButton size="small" color="default" onClick={() => onUpdateBlock(block.id, { citation: null })}>
              <CloseIcon fontSize="small" />
            </IconButton>
          </Paper>
        )}

        {/* FULL TEXTAREA: LIGHT BLUE GLASS REFLECTION STYLE + 3 LINES HIGH + PLACEHOLDER */}
        <Box
          sx={{
            minHeight: 76,
            width: '100%',
            p: 0,
            borderRadius: 2.5,
            overflow: 'hidden',
            background: 'linear-gradient(135deg, rgba(239, 246, 255, 0.8) 0%, rgba(219, 234, 254, 0.5) 100%)',
            backdropFilter: 'blur(10px)',
            border: '1px solid rgba(191, 219, 254, 0.9)',
            boxShadow: 'inset 0 1px 3px rgba(255, 255, 255, 0.8), 0 4px 14px rgba(59, 130, 246, 0.05)',
            transition: 'all 0.2s ease-in-out',
            '&:focus-within': {
              background: 'rgba(255, 255, 255, 0.95)',
              borderColor: '#3B82F6',
              boxShadow: 'inset 0 1px 2px rgba(255, 255, 255, 0.9), 0 0 0 3px rgba(59, 130, 246, 0.15)'
            },
            '& .ProseMirror': {
              outline: 'none !important',
              border: 'none !important',
              minHeight: '76px',
              padding: '12px 16px'
            },
            '& .ProseMirror:focus': {
              outline: 'none !important',
              border: 'none !important',
              boxShadow: 'none !important'
            },
            '& .is-editor-empty:first-of-type::before': {
              color: '#94A3B8',
              content: 'attr(data-placeholder)',
              float: 'left',
              height: 0,
              pointerEvents: 'none',
              fontStyle: 'italic'
            },
            /* Embedded Image Styling */
            '& img': {
              maxWidth: '100%',
              height: 'auto',
              borderRadius: 2,
              my: 1.5,
              boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
            },
            /* Embedded Table Styling */
            '& table': {
              borderCollapse: 'collapse',
              width: '100%',
              my: 1.5,
              fontVariantNumeric: 'tabular-nums'
            },
            '& th, & td': {
              border: '1px solid #CBD5E1',
              padding: '8px 12px',
              fontSize: '0.875rem'
            },
            '& th': {
              bgcolor: '#F1F5F9',
              fontWeight: 700,
              color: '#334155'
            }
          }}
        >
          <EditorContent editor={editor} className="prose max-w-none focus:outline-none text-slate-800" />
        </Box>
      </CardContent>

      {/* DYNAMIC MULTI-CRITERIA TAXONOMY USAGE HISTORY POPOVER WITH APA CITATION ATTACHMENT */}
      <Popover
        open={Boolean(historyPopoverAnchor)}
        anchorEl={historyPopoverAnchor}
        onClose={() => setHistoryPopoverAnchor(null)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
        PaperProps={{
          sx: {
            width: 400,
            p: 2.5,
            borderRadius: 3,
            maxHeight: 400,
            overflowY: 'auto',
            boxShadow: '0 12px 32px rgba(15, 23, 42, 0.15)',
            border: '1px solid #CBD5E1'
          }
        }}
      >
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1, pb: 1, borderBottom: '1px solid #E2E8F0' }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <HistoryIcon color="primary" fontSize="small" />
            <Typography variant="subtitle2" sx={{ fontWeight: 800, color: '#0F172A' }}>
              Taxonomy APA Citations History
            </Typography>
          </Box>
          <Chip label={`${historyData.total || 0} Matches`} size="small" color="primary" sx={{ fontSize: '0.7rem', height: 20 }} />
        </Box>

        <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 2, fontStyle: 'italic', bgcolor: '#F8FAFC', p: 0.75, borderRadius: 1 }}>
          Filter: {activeCriteriaSummary}
        </Typography>

        {historyLoading ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
            <CircularProgress size={28} />
          </Box>
        ) : historyData.data?.length === 0 ? (
          <Typography variant="body2" color="text.secondary" sx={{ py: 3, textAlign: 'center', fontStyle: 'italic' }}>
            No past entries found matching this taxonomy combination.
          </Typography>
        ) : (
          <Stack spacing={1.5} sx={{ mb: 2 }}>
            {historyData.data.map((item) => {
              const apaText = formatApaCitation(item);
              return (
                <Paper
                  key={item.id}
                  variant="outlined"
                  onClick={() => handleAttachCitation(item)}
                  sx={{
                    p: 1.5,
                    cursor: 'pointer',
                    borderColor: '#E2E8F0',
                    borderRadius: 2,
                    bgcolor: '#F8FAFC',
                    transition: 'all 0.15s ease',
                    '&:hover': { bgcolor: '#EFF6FF', borderColor: '#3B82F6' }
                  }}
                >
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                    <Typography variant="caption" sx={{ fontWeight: 700, color: '#1E293B' }}>
                      {item.report?.title || 'Report'} ({item.report?.report_number || `RPT-${item.id}`})
                    </Typography>
                    <Typography variant="caption" sx={{ color: '#64748B', fontSize: '0.7rem' }}>
                      {item.created_at ? item.created_at.substring(0, 10) : 'Recent'}
                    </Typography>
                  </Box>

                  <Typography variant="caption" color="primary" sx={{ display: 'block', fontWeight: 600, mb: 0.5, fontStyle: 'italic' }}>
                    APA: "{apaText}"
                  </Typography>

                  <Typography variant="body2" color="text.secondary" sx={{ fontSize: '0.8rem', lineHeight: 1.4, mb: 1 }}>
                    "{item.plain_text ? item.plain_text.substring(0, 100) : ''}..."
                  </Typography>

                  <Button
                    size="small"
                    variant="contained"
                    color="primary"
                    startIcon={<MenuBookIcon style={{ fontSize: 13 }} />}
                    onClick={(e) => {
                      e.stopPropagation();
                      handleAttachCitation(item);
                    }}
                    sx={{ textTransform: 'none', fontWeight: 700, fontSize: '0.72rem', py: 0.2 }}
                  >
                    Reference here
                  </Button>
                </Paper>
              );
            })}
          </Stack>
        )}

        {/* 5 Posts Per Page MUI Pagination */}
        {historyData.last_page > 1 && (
          <Box sx={{ display: 'flex', justifyContent: 'center', pt: 1, borderTop: '1px solid #F1F5F9' }}>
            <Pagination
              size="small"
              count={historyData.last_page}
              page={historyData.current_page}
              onChange={(e, p) => fetchTaxonomyHistory(p)}
            />
          </Box>
        )}
      </Popover>

      {/* READ-ONLY CITATION FULL DETAILS DIALOG */}
      <Dialog open={citationModalOpen} onClose={() => setCitationModalOpen(false)} fullWidth maxWidth="sm">
        <DialogTitle sx={{ fontWeight: 800, color: '#0F172A', borderBottom: '1px solid #E2E8F0' }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <MenuBookIcon color="primary" />
              <span>Attached APA Citation Details</span>
            </Box>
            <Chip label="Read-Only View" size="small" variant="outlined" color="primary" />
          </Box>
        </DialogTitle>

        <DialogContent dividers>
          {block.citation?.full_item ? (
            <Box>
              <Typography variant="h6" sx={{ fontWeight: 700, color: '#1E293B', mb: 0.5 }}>
                {block.citation.full_item.report?.title || 'Report Details'}
              </Typography>

              <Typography variant="body2" color="primary" sx={{ fontWeight: 600, fontStyle: 'italic', mb: 1 }}>
                APA Format: {block.citation.apa_string}
              </Typography>

              <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 2 }}>
                Author: <strong>{block.citation.full_item.report?.author?.name || 'Soe, P. O.'}</strong> • Date: {block.citation.full_item.created_at || 'Recent'}
              </Typography>

              <Divider sx={{ mb: 2 }} />

              <Box sx={{ bgcolor: '#F8FAFC', p: 2, borderRadius: 2, border: '1px solid #E2E8F0' }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, color: '#475569', mb: 1 }}>
                  Full Historical Report Content:
                </Typography>
                <div
                  dangerouslySetInnerHTML={{ __html: block.citation.full_item.html_content || block.citation.full_item.plain_text }}
                  className="prose max-w-none text-slate-800"
                />
              </Box>
            </Box>
          ) : (
            <Typography variant="body2" color="text.secondary" sx={{ py: 2 }}>
              {typeof block.citation === 'string' ? block.citation : 'APA Citation Attached.'}
            </Typography>
          )}
        </DialogContent>

        <DialogActions sx={{ p: 2 }}>
          <Button variant="contained" onClick={() => setCitationModalOpen(false)}>
            Close
          </Button>
        </DialogActions>
      </Dialog>

      {/* CROSS-REFERENCE PAST SOLUTIONS DIALOG */}
      <Dialog
        open={refModalOpen}
        onClose={() => setRefModalOpen(false)}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle sx={{ fontWeight: 800, color: '#0F172A', display: 'flex', alignItems: 'center', gap: 1 }}>
          <BookmarkAddedIcon color="primary" />
          Cross-Reference Past Solutions & Findings
        </DialogTitle>

        <DialogContent dividers>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Reference corrective action plans or audit findings previously authored by team members:
          </Typography>

          <TextField
            fullWidth
            size="small"
            placeholder="Search past solutions by keyword, author, or branch..."
            value={refSearchQuery}
            onChange={(e) => setRefSearchQuery(e.target.value)}
            sx={{ mb: 2.5 }}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <SearchIcon color="action" />
                </InputAdornment>
              )
            }}
          />

          <Stack spacing={2} sx={{ maxHeight: 360, overflowY: 'auto' }}>
            {filteredSolutions.map((sol) => (
              <Paper
                key={sol.id}
                variant="outlined"
                sx={{
                  p: 2,
                  borderColor: '#E2E8F0',
                  borderRadius: 2,
                  transition: 'all 0.15s ease',
                  '&:hover': { borderColor: '#3B82F6', bgcolor: '#F8FAFC' }
                }}
              >
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                  <Box>
                    <Typography variant="subtitle2" sx={{ fontWeight: 700, color: '#0F172A' }}>
                      {sol.title}
                    </Typography>
                    <Typography variant="caption" color="text.secondary" sx={{ display: 'flex', alignItems: 'center', gap: 0.5, mt: 0.2 }}>
                      <PersonIcon style={{ fontSize: 13 }} /> {sol.author} • {sol.branch} ({sol.date})
                    </Typography>
                  </Box>
                  <Button
                    size="small"
                    variant="contained"
                    onClick={() => handleInsertReference(sol)}
                    sx={{ textTransform: 'none', fontWeight: 700, borderRadius: 1.5, fontSize: '0.75rem' }}
                  >
                    Insert Ref
                  </Button>
                </Box>
                <Typography variant="body2" color="text.secondary" sx={{ fontSize: '0.85rem', bgcolor: '#F1F5F9', p: 1.2, borderRadius: 1, borderLeft: '3px solid #3B82F6' }}>
                  "{sol.content}"
                </Typography>
              </Paper>
            ))}
          </Stack>
        </DialogContent>

        <DialogActions sx={{ p: 2 }}>
          <Button onClick={() => setRefModalOpen(false)}>Close</Button>
        </DialogActions>
      </Dialog>

      {/* REAL APPLICATION TO-DO TASK CREATION MODAL (SAVES TO DATABASE TABLE VIA /todo/tasks) */}
      <CreateTaskModal
        isOpen={createTodoModalOpen}
        onClose={() => setCreateTodoModalOpen(false)}
        onSuccessTask={(taskInfo) => {
          const name = (taskInfo.task || '').trim() || 'Clean Vault Area';
          const status = taskInfo.status || 'In Progress';
          if (editorRef.current) {
            editorRef.current.chain().focus().insertContent(` [todo_task: ${name} | ${status}] `).run();
          }
          const createdItem = {
            id: `task-${Date.now()}`,
            type: 'todo_task',
            title: name,
            subtitle: status,
            status: status,
            category: 'To-Do Task'
          };
          setGluedTasks(prev => [...prev, createdItem]);
        }}
        branches={todoOptions?.branches || []}
        departments={todoOptions?.departments || []}
        categories={todoOptions?.categories || []}
        itAdminDepartments={todoOptions?.itAdminDepartments || []}
        users={todoOptions?.users || []}
        dueTimes={todoOptions?.dueTimes || []}
      />

      {/* CREATE PROMOTE ACTION MODAL (TRIGGERED VIA /create SLASH COMMAND) */}
      <Dialog
        open={createPromoteModalOpen}
        onClose={() => setCreatePromoteModalOpen(false)}
        fullWidth
        maxWidth="xs"
      >
        <DialogTitle sx={{ bgcolor: '#3730A3', color: '#FFFFFF', py: 1.5 }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>
            📌 Create Promote Action Citation
          </Typography>
        </DialogTitle>
        <DialogContent sx={{ pt: 2.5, pb: 2 }}>
          <Stack spacing={2} sx={{ mt: 1 }}>
            <TextField
              label="Action Name"
              size="small"
              fullWidth
              value={newPromoteTitle}
              onChange={(e) => setNewPromoteTitle(e.target.value)}
              placeholder="e.g. Security Audit"
            />
            <Grid container spacing={1.5}>
              <Grid item xs={6}>
                <TextField
                  label="Start Date"
                  type="date"
                  size="small"
                  fullWidth
                  InputLabelProps={{ shrink: true }}
                  value={newPromoteStart}
                  onChange={(e) => setNewPromoteStart(e.target.value)}
                />
              </Grid>
              <Grid item xs={6}>
                <TextField
                  label="End Date"
                  type="date"
                  size="small"
                  fullWidth
                  InputLabelProps={{ shrink: true }}
                  value={newPromoteEnd}
                  onChange={(e) => setNewPromoteEnd(e.target.value)}
                />
              </Grid>
            </Grid>
          </Stack>
        </DialogContent>
        <DialogActions sx={{ px: 2.5, pb: 2 }}>
          <Button size="small" onClick={() => setCreatePromoteModalOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            size="small"
            onClick={() => {
              if (editor) {
                const name = newPromoteTitle.trim() || 'Security Audit';
                const start = newPromoteStart || '2026-08-01';
                const end = newPromoteEnd || '2026-08-15';
                editor.chain().focus().insertContent(` [promote_action: ${name} | ${start} - ${end}] `).run();

                const newActionObj = {
                  id: `action-${Date.now()}`,
                  type: 'promote_action',
                  title: name,
                  subtitle: `${start} - ${end}`,
                  start_date: start,
                  end_date: end,
                  category: 'Promote Action'
                };
                setGluedTasks(prev => [...prev, newActionObj]);
              }
              setCreatePromoteModalOpen(false);
              setNewPromoteTitle('');
            }}
            sx={{ bgcolor: '#3730A3', '&:hover': { bgcolor: '#312E81' } }}
          >
            Insert Citation
          </Button>
        </DialogActions>
      </Dialog>

      {/* FULL APPLICATION TASK DETAIL MODAL */}
      <TaskDetailModal
        task={normalizedTaskForModal}
        isOpen={todoDetailModalOpen}
        onClose={() => {
          setTodoDetailModalOpen(false);
          setSelectedTodoTask(null);
        }}
        users={todoOptions?.users || []}
        statuses={todoOptions?.statuses || []}
      />

      {/* FULL APPLICATION PROMOTE ACTION CREATION MODAL */}
      <CreatePromoteActionModal />
    </Card>
  );
}
