import React, { useState, useRef, useEffect } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Placeholder } from '@tiptap/extension-placeholder';
import { Image } from '@tiptap/extension-image';
import { Underline } from '@tiptap/extension-underline';
import axios from 'axios';
import dayjs from 'dayjs';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import { DatePicker } from '@mui/x-date-pickers/DatePicker';
import InlineCitationNode from './InlineCitationNode';
import SlashCommandExtension from './SlashCommandExtension.jsx';
import MentionCommandExtension from './MentionCommandExtension.jsx';
import CreateTaskModal from '../../Pages/Todo/Components/CreateTaskModal';
import TaskDetailModal from '../../Pages/Todo/Components/TaskDetailModal';
import CreatePromoteActionModal from '../CreatePromoteActionModal';

import {
  Box,
  Paper,
  Typography,
  Chip,
  Stack,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  IconButton,
  Tooltip,
  Divider,
  ToggleButton,
  ToggleButtonGroup,
  CircularProgress,
  Badge,
  Alert,
  TextField,
  Collapse,
  Pagination
} from '@mui/material';

import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';
import FormatBoldIcon from '@mui/icons-material/FormatBold';
import FormatItalicIcon from '@mui/icons-material/FormatItalic';
import FormatUnderlinedIcon from '@mui/icons-material/FormatUnderlined';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import FormatListNumberedIcon from '@mui/icons-material/FormatListNumbered';
import FormatQuoteIcon from '@mui/icons-material/FormatQuote';
import AddPhotoAlternateIcon from '@mui/icons-material/AddPhotoAlternate';
import SendIcon from '@mui/icons-material/Send';
import CloseIcon from '@mui/icons-material/Close';
import MenuBookIcon from '@mui/icons-material/MenuBook';
import BookmarkAddedIcon from '@mui/icons-material/BookmarkAdded';
import PersonIcon from '@mui/icons-material/Person';
import FavoriteIcon from '@mui/icons-material/Favorite';
import FavoriteBorderIcon from '@mui/icons-material/FavoriteBorder';
import BookmarkIcon from '@mui/icons-material/Bookmark';
import BookmarkBorderIcon from '@mui/icons-material/BookmarkBorder';
import FilterListIcon from '@mui/icons-material/FilterList';
import TaskAltIcon from '@mui/icons-material/TaskAlt';
import CampaignIcon from '@mui/icons-material/Campaign';

// DEMO 4CHAN / IMAGEBOARD THREADS DATASET
const INITIAL_IMAGEBOARD_THREADS = [
  {
    id: 100293,
    title: 'Branch 1 Pawn Weight & Scale Audit Discrepancy',
    author: 'Auditor_Anon',
    timestamp: '08/12/26(Wed)02:15:09',
    category: 'Audit Finding',
    branch: 'Branch 1 (Downtown)',
    process: 'Pawn Valuation',
    risk: 'High Risk',
    status: 'Master Template: Active',
    images: ['https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=400&q=80'],
    content: `> Be me, Lead Auditor checking Branch 1 counters.
> Notice digital gold scale showing +2.5% variance on 24k pawn valuations.
> Cross-referenced daily calibration logs and found no entries since last week.
Recommend immediate recalibration and dual-supervisor authorization protocol.
[todo_task: Clean Vault Area | In Progress]
[promote_action: Security Audit | 2026-08-01 - 2026-08-15]
Detailed inspection log:
- Counter 1: Calibration sticker expired 3 days ago.
- Counter 2: Minor zero-point drift observed (+0.2g).
- Counter 3: Passed physical check.
All supervisors notified for daily authorization logging routine.`,
    replies: [
      {
        id: 290067179,
        author: 'Treasury_Lead',
        timestamp: '08/12/26(Wed)02:18:44',
        content: `Verified cash register logs for the same shift. [todo_task: Scale Recalibration | Completed] Overage voucher #902 matches scale variance exactly.`,
        images: []
      },
      {
        id: 290067204,
        author: 'Compliance_Anon',
        timestamp: '08/12/26(Wed)02:22:10',
        content: `Updated SOP document #SOP-882 in vault repository. [promote_action: SOP Compliance Review | 2026-08-10 - 2026-08-25] Both supervisors must sign off before register opening.`,
        images: []
      },
      {
        id: 290067331,
        author: 'Branch Manager',
        timestamp: '08/12/26(Wed)02:30:15',
        content: `All counter staff have completed the mandatory re-training module today. Dual sign-off form is active.`,
        images: []
      }
    ]
  },
  {
    id: 100412,
    title: 'Vault Reserve Inventory & Cash Drawer Overages',
    author: 'Treasury_Anon',
    timestamp: '08/11/26(Tue)16:40:22',
    category: 'Finance & Treasury',
    branch: 'Branch 2 (Westside)',
    process: 'Cash Handling',
    risk: 'Low Risk',
    status: 'Status: Finalized',
    images: [],
    content: `Reconciled vault cash drawer #3 after evening shift change.
Found $45.00 cash overage due to unlogged receipt voucher from customer return.
[todo_task: Voucher Reconciliation | Completed]
Retrained cashier staff on immediate voucher logging procedures.
Summary of daily balance sheet adjustments completed.`,
    replies: [
      {
        id: 290088190,
        author: 'Internal Auditor',
        timestamp: '08/11/26(Tue)17:05:11',
        content: `Verified receipt voucher #V-402 against POS register journal. Reconciliation complete and signed off.`,
        images: []
      }
    ]
  }
];

// Helper: 4chan Greentext & Citation Parser
const render4chanFormattedText = (text, onOpenTodoDetail, onOpenActionDetail) => {
  if (!text) return null;
  const citationRegex = /\[(todo_task|promote_action):\s*([^|\]]+)(?:\|\s*([^\]]+))?\]/g;
  const lines = String(text).split('\n');

  return lines.map((line, idx) => {
    const isGreenText = line.trim().startsWith('>');
    const elements = [];
    let lastIndex = 0;
    let match;

    while ((match = citationRegex.exec(line)) !== null) {
      if (match.index > lastIndex) {
        elements.push(line.substring(lastIndex, match.index));
      }

      const cType = match[1];
      const p1 = match[2]?.trim();
      const p2 = match[3]?.trim();

      if (cType === 'todo_task') {
        const cData = { task_name: p1, status: p2 || 'In Progress' };
        elements.push(
          <InlineCitationNode
            key={`cit-${idx}-${match.index}`}
            citation={{ type: 'todo_task', data: cData }}
            onOpenTodoDetail={onOpenTodoDetail}
            onOpenActionDetail={onOpenActionDetail}
          />
        );
      } else if (cType === 'promote_action') {
        const dates = (p2 || '').split('-').map(s => s.trim());
        const cData = {
          action_name: p1,
          start_date: dates[0] || '2026-08-01',
          end_date: dates[1] || '2026-08-15'
        };
        elements.push(
          <InlineCitationNode
            key={`cit-${idx}-${match.index}`}
            citation={{ type: 'promote_action', data: cData }}
            onOpenTodoDetail={onOpenTodoDetail}
            onOpenActionDetail={onOpenActionDetail}
          />
        );
      }

      lastIndex = citationRegex.lastIndex;
    }

    if (lastIndex < line.length) {
      elements.push(line.substring(lastIndex));
    }

    return (
      <span key={idx} className={isGreenText ? 'text-[#789922] font-semibold block' : 'text-slate-800 block'}>
        {elements.length > 0
          ? elements.map((el, i) => (typeof el === 'string' ? <span key={i}>{el}</span> : el))
          : line}
      </span>
    );
  });
};

// Helper: Check if thread has an attached To-Do Task
const hasRelatedTodoTask = (thread) => {
  if (!thread) return false;
  if (thread.todo_task || thread.todo_id || (thread.attached_todos && thread.attached_todos.length > 0)) return true;
  const allText = String(thread.content || '') + ' ' + (thread.replies || []).map(r => String(r.content || r.html_content || '')).join(' ');
  return /todo_task|attached_todos|@todo|@task|#todo|#task/i.test(allText);
};

// Helper: Check if thread has an attached Promote Action
const hasRelatedPromoteAction = (thread) => {
  if (!thread) return false;
  if (thread.promote_action || thread.action_id || (thread.attached_promote_actions && thread.attached_promote_actions.length > 0)) return true;
  const allText = String(thread.content || '') + ' ' + (thread.replies || []).map(r => String(r.content || r.html_content || '')).join(' ');
  return /promote_action|attached_promote|@pa|@promote|@action|#pa|#promote/i.test(allText);
};

// Helper: Format APA Citation for Target Post
const formatTargetApaCitation = (thread, targetPost) => {
  const author = targetPost?.author || thread?.author || 'Soe, P. O.';
  const nameParts = author.trim().split(' ');
  let formattedAuthor = author;
  if (nameParts.length >= 2) {
    const lastName = nameParts[nameParts.length - 1];
    const initials = nameParts.slice(0, nameParts.length - 1).map(n => n[0].toUpperCase() + '.').join(' ');
    formattedAuthor = `${lastName}, ${initials}`;
  }

  const dateFormatted = '2026, August 12';
  const title = thread?.title || 'Audit Report';
  const category = targetPost?.category || thread?.category || 'Audit Finding';

  return `${formattedAuthor} (${dateFormatted}). ${title} [${category}].`;
};

export default function ReportImageboardView({ taxonomies = {}, todoOptions = {} }) {
  const [threads, setThreads] = useState([]); // Default empty array to reflect real database state
  const [selectedCategories, setSelectedCategories] = useState([]); // Array state for multi-selection
  const [selectedBranch, setSelectedBranch] = useState('All');
  const [startDate, setStartDate] = useState(null);
  const [endDate, setEndDate] = useState(null);
  const [expandedThreads, setExpandedThreads] = useState({});
  const [isFilterOpen, setIsFilterOpen] = useState(false); // Default CLOSED

  // Close filter grid when ESC key is pressed
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isFilterOpen) {
        setIsFilterOpen(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isFilterOpen]);

  const handleToggleCategory = (categoryCode) => {
    if (categoryCode === 'All') {
      setSelectedCategories([]);
      return;
    }
    setSelectedCategories((prev) => {
      if (prev.includes(categoryCode)) {
        return prev.filter((c) => c !== categoryCode);
      }
      return [...prev, categoryCode];
    });
  };

  const saveMetadataToLocalAndServer = (threadId, updatedFields) => {
    // 1. Save to Local Storage for offline/instant persistence
    const localStore = JSON.parse(localStorage.getItem('stt_imageboard_metadata_store') || '{}');
    const existing = localStore[threadId] || {};
    localStore[threadId] = { ...existing, ...updatedFields };
    localStorage.setItem('stt_imageboard_metadata_store', JSON.stringify(localStore));

    // 2. Persist in database via POST request
    axios.post(`/reports/${threadId}/metadata`, updatedFields)
      .then(res => {
        console.log('Metadata saved to database for thread', threadId, res.data);
      })
      .catch(err => {
        console.warn('Skipping server update for local demo thread or offline mode:', err?.response?.data || err.message);
      });
  };

  const handleToggleLike = (threadId) => {
    setThreads((prev) =>
      prev.map((t) => {
        if (t.id === threadId) {
          const currentlyLiked = !!t.is_liked;
          const newLikesCount = currentlyLiked ? Math.max(0, (t.likes_count || 1) - 1) : (t.likes_count || 0) + 1;
          const likedUsers = currentlyLiked
            ? (t.liked_by_users || []).filter((u) => u !== 'CurrentUser_Anon')
            : [...(t.liked_by_users || []), 'CurrentUser_Anon'];

          const updatedFields = {
            is_liked: !currentlyLiked,
            likes_count: newLikesCount,
            liked_by_users: likedUsers
          };

          saveMetadataToLocalAndServer(threadId, updatedFields);

          return { ...t, ...updatedFields };
        }
        return t;
      })
    );
  };

  const handleToggleBookmark = (threadId) => {
    setThreads((prev) =>
      prev.map((t) => {
        if (t.id === threadId) {
          const currentlySaved = !!t.is_saved;
          const savedUsers = currentlySaved
            ? (t.saved_by_users || []).filter((u) => u !== 'CurrentUser_Anon')
            : [...(t.saved_by_users || []), 'CurrentUser_Anon'];

          const updatedFields = {
            is_saved: !currentlySaved,
            saved_by_users: savedUsers
          };

          saveMetadataToLocalAndServer(threadId, updatedFields);

          return { ...t, ...updatedFields };
        }
        return t;
      })
    );
  };

  const handleToggleRequireTodo = (threadId) => {
    setThreads((prev) =>
      prev.map((t) => {
        if (t.id === threadId) {
          const updatedFields = {
            require_todo_task: !t.require_todo_task
          };

          saveMetadataToLocalAndServer(threadId, updatedFields);

          return { ...t, ...updatedFields };
        }
        return t;
      })
    );
  };

  const handleToggleRequirePromote = (threadId) => {
    setThreads((prev) =>
      prev.map((t) => {
        if (t.id === threadId) {
          const updatedFields = {
            require_promote_action: !t.require_promote_action
          };

          saveMetadataToLocalAndServer(threadId, updatedFields);

          return { ...t, ...updatedFields };
        }
        return t;
      })
    );
  };

  // Reply Modal State
  const [replyModalOpen, setReplyModalOpen] = useState(false);
  const [targetThread, setTargetThread] = useState(null);
  const [targetPost, setTargetPost] = useState(null);
  const [targetCitationApa, setTargetCitationApa] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState(null);

  // Citation Detail Modals State
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
  const [actionDetailModalOpen, setActionDetailModalOpen] = useState(false);
  const [selectedPromoteAction, setSelectedPromoteAction] = useState(null);

  const handleOpenTodoDetail = (taskData) => {
    setSelectedTodoTask(taskData);
    setTodoDetailModalOpen(true);
  };

  const handleOpenActionDetail = (actionData) => {
    setSelectedPromoteAction(actionData);
    setActionDetailModalOpen(true);
  };

  // Modal Taxonomy Form Fields
  const [replyCategory, setReplyCategory] = useState('');
  const [replyBranch, setReplyBranch] = useState('');
  const [replyProcess, setReplyProcess] = useState('');
  const [replyRisk, setReplyRisk] = useState('MEDIUM');

  const modalFileInputRef = useRef(null);

  // FETCH REAL DATABASE THREADS & PERSIST REPLIES & METADATA ACROSS PAGE REFRESHES
  useEffect(() => {
    const savedLocalReplies = JSON.parse(localStorage.getItem('stt_imageboard_replies_store') || '{}');
    const savedLocalMetadata = JSON.parse(localStorage.getItem('stt_imageboard_metadata_store') || '{}');

    axios.get('/reports/imageboard-threads')
      .then((res) => {
        const dbThreads = res.data || [];

        const formattedDbThreads = dbThreads.map(dbThread => {
          const extraLocal = savedLocalReplies[dbThread.id] || [];
          const metaLocal = savedLocalMetadata[dbThread.id] || {};
          const seenIds = new Set();
          const allReplies = [...(dbThread.replies || []), ...extraLocal].filter(r => {
            if (r.id && seenIds.has(r.id)) return false;
            if (r.id) seenIds.add(r.id);
            return true;
          });
          return { ...dbThread, ...metaLocal, replies: allReplies };
        });

        setThreads(formattedDbThreads);
      })
      .catch((err) => {
        console.warn('Failed to load database threads:', err);
        setThreads([]);
      });
  }, []);

  // Collapsible Reference State for Thread Cards
  const [expandedReferences, setExpandedReferences] = useState({});
  const toggleReferenceExpansion = (threadId) => {
    setExpandedReferences(prev => ({ ...prev, [threadId]: !prev[threadId] }));
  };

  // Create Modals State for Slash Commands
  const [createPromoteModalOpen, setCreatePromoteModalOpen] = useState(false);
  const [createTodoModalOpen, setCreateTodoModalOpen] = useState(false);

  const [newTodoTitle, setNewTodoTitle] = useState('');
  const [newTodoStatus, setNewTodoStatus] = useState('In Progress');
  const [newPromoteTitle, setNewPromoteTitle] = useState('');
  const [newPromoteStart, setNewPromoteStart] = useState('2026-08-01');
  const [newPromoteEnd, setNewPromoteEnd] = useState('2026-08-15');

  const replyEditorRef = useRef(null);

  const handleOpenPromoteModal = React.useCallback(() => {
    window.dispatchEvent(
      new CustomEvent('show-promote-action-modal', {
        detail: {
          branches: todoOptions?.branches || [],
          departments: todoOptions?.departments || [],
          onSuccess: (actionData) => {
            const name = actionData?.name || 'Security Audit';
            const start = actionData?.start_at || '2026-08-01';
            const end = actionData?.end_at || '2026-08-15';
            if (replyEditorRef.current) {
              replyEditorRef.current.chain().focus().insertContent(` [promote_action: ${name} | ${start} - ${end}] `).run();
            }
          }
        }
      })
    );
  }, [todoOptions]);

  const handleOpenTodoModal = React.useCallback(() => {
    setCreateTodoModalOpen(true);
  }, []);

  // Deduplicate and memoize Tiptap extensions for Reply Editor
  const replyExtensions = React.useMemo(() => {
    const list = [
      StarterKit.configure({
        underline: false,
        link: false,
      }),
      Placeholder.configure({
        placeholder: 'Type your reply here... (Type / or /create to open Slash Commands)'
      }),
      Image.configure({
        inline: true,
        allowBase64: true,
      }),
      Underline,
      SlashCommandExtension.configure({
        onOpenPromoteModal: handleOpenPromoteModal,
        onOpenTodoModal: handleOpenTodoModal
      }),
      MentionCommandExtension.configure({
        editorId: 'imageboard-reply-editor',
      })
    ];

    const seenNames = new Set();
    return list.filter(ext => {
      const name = ext?.name;
      if (name && seenNames.has(name)) return false;
      if (name) seenNames.add(name);
      return true;
    });
  }, [handleOpenPromoteModal, handleOpenTodoModal]);

  const replyEditor = useEditor({
    extensions: replyExtensions,
    content: '<p></p>'
  });

  replyEditorRef.current = replyEditor;

  const BOARD_TAGS = ['All', 'Audit Finding', 'Finance & Treasury', 'Branch 1', 'Pawn Valuation', 'Low Risk', 'High Risk'];
  const COMPANY_LOGO = '/images/logo.png';

  const GROUP_PALETTE = [
    { bg: '#2563eb', softBg: '#eff6ff' }, // Blue
    { bg: '#d97706', softBg: '#fef3c7' }, // Amber
    { bg: '#059669', softBg: '#ecfdf5' }, // Emerald
    { bg: '#9333ea', softBg: '#faf5ff' }, // Purple
    { bg: '#e11d48', softBg: '#fff1f2' }, // Rose
    { bg: '#4f46e5', softBg: '#eef2ff' }, // Indigo
    { bg: '#0891b2', softBg: '#ecfeff' }, // Cyan
    { bg: '#c026d3', softBg: '#fdf4ff' }  // Fuchsia
  ];

  const DEFAULT_GROUPS = [
    {
      group_code: 'TYPE',
      group_name: 'Category Type',
      color: '#2563eb',
      items: [
        { code: 'TYPE_MAJOR_WIN', title: 'Major Win / Achievement', color: '#2563eb' },
        { code: 'TYPE_MAJOR_PROBLEM', title: 'Major Problem / Issue', color: '#dc2626' },
        { code: 'TYPE_ACTION_PLAN', title: 'Action Plan & Strategy', color: '#7c3aed' },
        { code: 'TYPE_SERVICE_QUALITY', title: 'Service Quality & Complaints', color: '#d97706' },
        { code: 'TYPE_STAFF_PRODUCTIVITY', title: 'Staff Productivity & Training', color: '#059669' },
        { code: 'TYPE_PROCESS_ERP', title: 'Process & ERP Efficiency', color: '#0891b2' }
      ]
    },
    {
      group_code: 'BRANCH',
      group_name: 'Branch',
      color: '#d97706',
      items: [
        { code: 'BR_B1', title: 'Branch 1', color: '#d97706' },
        { code: 'BR_B2', title: 'Branch 2', color: '#d97706' },
        { code: 'BR_B3', title: 'Branch 3', color: '#d97706' },
        { code: 'BR_B4', title: 'Branch 4', color: '#d97706' },
        { code: 'BR_B5', title: 'Branch 5', color: '#d97706' },
        { code: 'BR_B6', title: 'Branch 6', color: '#d97706' },
        { code: 'BR_B7', title: 'Branch 7', color: '#d97706' },
        { code: 'BR_PAWN_DEPT', title: 'Pawn Department', color: '#d97706' },
        { code: 'BR_ONLINE_SALE', title: 'Online Sale', color: '#d97706' }
      ]
    },
    {
      group_code: 'PROCESS',
      group_name: 'Process',
      color: '#059669',
      items: [
        { code: 'PROC_SALES_TRACKING', title: 'Sales & Conversion Tracking', color: '#059669' },
        { code: 'PROC_PAWN', title: 'Pawn Operations', color: '#059669' },
        { code: 'PROC_LOCAL_MARKETING', title: 'Door-to-Door & Local Marketing', color: '#059669' },
        { code: 'PROC_HR_LEADERSHIP', title: 'HR, Training & Leadership', color: '#059669' },
        { code: 'PROC_ERP_STOCK', title: 'ERP & Ground Stock Reconciliation', color: '#059669' },
        { code: 'PROC_REPURCHASE_PORTAL', title: 'Repurchase Website Portal', color: '#059669' },
        { code: 'PROC_CRM', title: 'Customer Relationship & CRM', color: '#059669' },
        { code: 'PROC_RENOVATION', title: 'Branch Renovation & Infrastructure', color: '#059669' }
      ]
    },
    {
      group_code: 'PERFORMANCE_LEVEL',
      group_name: 'Performance',
      color: '#16a34a',
      items: [
        { code: 'PERF_TOP', title: 'Top Performer', color: '#16a34a' },
        { code: 'PERF_MODERATE', title: 'Moderate Performer', color: '#ca8a04' },
        { code: 'PERF_LOW', title: 'Low Performer', color: '#e11d48' }
      ]
    },
    {
      group_code: 'RISK_SEVERITY',
      group_name: 'Risk Severity',
      color: '#dc2626',
      items: [
        { code: 'LOW', title: 'Low Risk', color: '#16a34a' },
        { code: 'MEDIUM', title: 'Medium Risk', color: '#d97706' },
        { code: 'HIGH', title: 'High Risk', color: '#dc2626' }
      ]
    }
  ];

  const GROUP_TITLES = {
    TYPE: 'Category Type',
    BRANCH: 'Branch',
    PROCESS: 'Process',
    PERFORMANCE_LEVEL: 'Performance',
    RISK_SEVERITY: 'Risk Severity'
  };

  const taxonomyGroups = React.useMemo(() => {
    const groups = [];
    let colorIdx = 0;

    if (taxonomies && typeof taxonomies === 'object') {
      Object.keys(taxonomies).forEach(key => {
        const value = taxonomies[key];
        if (Array.isArray(value) && value.length > 0) {
          const palette = GROUP_PALETTE[colorIdx % GROUP_PALETTE.length];
          colorIdx++;

          const groupUpper = key.toUpperCase();
          const formattedTitle = GROUP_TITLES[groupUpper] || key
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());

          groups.push({
            group_code: groupUpper,
            group_name: formattedTitle,
            color: palette.bg,
            softBg: palette.softBg,
            items: value.map(item => {
              const itemColor = typeof item === 'object' ? (item.color_hex || item.color || palette.bg) : palette.bg;
              return {
                code: typeof item === 'object' ? (item.code || item.title || item.name) : String(item),
                title: typeof item === 'object' ? (item.title || item.name || item.code) : String(item),
                color: itemColor
              };
            })
          });
        }
      });
    }

    if (groups.length === 0) {
      return DEFAULT_GROUPS;
    }

    return groups;
  }, [taxonomies]);

  const getThreadDayjs = (thread) => {
    if (thread.created_at) {
      const d = dayjs(thread.created_at);
      if (d.isValid()) return d;
    }
    if (thread.timestamp) {
      const match = String(thread.timestamp).match(/(\d{2})\/(\d{2})\/(\d{2})\(.*\)(\d{2}):(\d{2}):(\d{2})/);
      if (match) {
        const month = parseInt(match[1], 10) - 1;
        const day = parseInt(match[2], 10);
        const year = 2000 + parseInt(match[3], 10);
        const hours = parseInt(match[4], 10);
        const mins = parseInt(match[5], 10);
        const secs = parseInt(match[6], 10);
        const d = dayjs(new Date(year, month, day, hours, mins, secs));
        if (d.isValid()) return d;
      }
      const d = dayjs(thread.timestamp);
      if (d.isValid()) return d;
    }
    return dayjs(0);
  };

  const isThreadMatchingFilters = (thread, categoriesToMatch, branchToMatch, startToMatch, endToMatch) => {
    // 1. Branch Filter
    if (branchToMatch && branchToMatch !== 'All') {
      const bLower = branchToMatch.toLowerCase();
      const directBranch = String(thread.branch || thread.branch_code || '').toLowerCase();
      const blocksBranch = (thread.textBlocks || []).some(b => String(b.branch_code || '').toLowerCase().includes(bLower));
      if (!directBranch.includes(bLower) && !blocksBranch) return false;
    }

    // 2. Date Range Filter using Dayjs
    if (startToMatch || endToMatch) {
      const threadDayjs = getThreadDayjs(thread);
      if (startToMatch && dayjs.isDayjs(startToMatch) && startToMatch.isValid()) {
        if (threadDayjs.isBefore(startToMatch.startOf('day'))) return false;
      }
      if (endToMatch && dayjs.isDayjs(endToMatch) && endToMatch.isValid()) {
        if (threadDayjs.isAfter(endToMatch.endOf('day'))) return false;
      }
    }

    // 3. Multi-Selection Category / Taxonomy Filter (Progressive Intersection AND logic)
    if (categoriesToMatch && categoriesToMatch.length > 0) {
      const matchesAllCategories = categoriesToMatch.every(cat => {
        const catLower = String(cat).toLowerCase().replace(/_/g, ' ');
        const rawCatLower = String(cat).toLowerCase();

        // Extract title mapping from taxonomyGroups if available
        let titleLower = '';
        if (taxonomyGroups && taxonomyGroups.length > 0) {
          taxonomyGroups.forEach(g => {
            g.items?.forEach(it => {
              if (it.code === cat || it.title === cat) {
                titleLower = String(it.title || '').toLowerCase();
              }
            });
          });
        }

        const categoryStr = String(thread.category || thread.category_type || '').toLowerCase();
        const branchStr = String(thread.branch || thread.branch_code || '').toLowerCase();
        const processStr = String(thread.process || thread.process_code || '').toLowerCase();
        const riskStr = String(thread.risk || thread.risk_level || '').toLowerCase();
        const titleStr = String(thread.title || '').toLowerCase();

        const textBlocks = thread.textBlocks || [];
        const subBlockMatch = textBlocks.some(b => {
          const bCat = String(b.category_type || '').toLowerCase();
          const bBranch = String(b.branch_code || '').toLowerCase();
          const bProc = String(b.process_code || '').toLowerCase();
          const bRisk = String(b.risk_level || '').toLowerCase();
          const bText = String(b.plain_text || '').toLowerCase();

          return bCat.includes(rawCatLower) || bCat.includes(catLower) || (titleLower && bCat.includes(titleLower)) ||
                 bBranch.includes(rawCatLower) || bBranch.includes(catLower) || (titleLower && bBranch.includes(titleLower)) ||
                 bProc.includes(rawCatLower) || bProc.includes(catLower) || (titleLower && bProc.includes(titleLower)) ||
                 bRisk.includes(rawCatLower) || bRisk.includes(catLower) || (titleLower && bRisk.includes(titleLower)) ||
                 bText.includes(rawCatLower) || bText.includes(catLower);
        });

        if (cat === 'FILTER_REQUIRE_TODO' || cat === '☑ Require Task') {
          return !!thread.require_todo_task && !hasRelatedTodoTask(thread);
        }
        if (cat === 'FILTER_REQUIRE_PROMOTE' || cat === '📌 Require Promote') {
          return !!thread.require_promote_action && !hasRelatedPromoteAction(thread);
        }
        if (cat === 'FILTER_MY_LIKES' || cat === '❤️ My Liked Posts') {
          return !!thread.is_liked;
        }
        if (cat === 'FILTER_MY_SAVED' || cat === '🔖 My Saved Posts') {
          return !!thread.is_saved;
        }

        if (cat === 'Branch 1') {
          return branchStr.includes('branch 1') || branchStr.includes('br_b1') || subBlockMatch;
        }
        if (cat === 'Pawn Valuation') {
          return categoryStr.includes('pawn') || processStr.includes('pawn') || titleStr.includes('pawn') || subBlockMatch;
        }
        if (cat === 'Low Risk') {
          return riskStr.includes('low') || subBlockMatch;
        }
        if (cat === 'High Risk') {
          return riskStr.includes('high') || subBlockMatch;
        }

        return categoryStr.includes(rawCatLower) || categoryStr.includes(catLower) || (titleLower && categoryStr.includes(titleLower)) ||
               branchStr.includes(rawCatLower) || branchStr.includes(catLower) || (titleLower && branchStr.includes(titleLower)) ||
               processStr.includes(rawCatLower) || processStr.includes(catLower) || (titleLower && processStr.includes(titleLower)) ||
               riskStr.includes(rawCatLower) || riskStr.includes(catLower) || (titleLower && riskStr.includes(titleLower)) ||
               titleStr.includes(rawCatLower) || titleStr.includes(catLower) || (titleLower && titleStr.includes(titleLower)) ||
               subBlockMatch;
      });

      if (!matchesAllCategories) return false;
    }

    return true;
  };

  const SPECIAL_FILTERS = [
    { code: 'FILTER_REQUIRE_TODO', title: '☑ Require Task', color: '#059669' },
    { code: 'FILTER_REQUIRE_PROMOTE', title: '📌 Require Promote', color: '#4F46E5' },
    { code: 'FILTER_MY_LIKES', title: '❤️ My Liked Posts', color: '#E11D48' },
    { code: 'FILTER_MY_SAVED', title: '🔖 My Saved Posts', color: '#2563EB' }
  ];

  const tagCounts = React.useMemo(() => {
    const counts = {};
    counts['All'] = threads.filter(t => isThreadMatchingFilters(t, [], selectedBranch, startDate, endDate)).length;

    const computeTagCount = (tagCode) => {
      // If already selected, count current active selection
      if (selectedCategories.includes(tagCode)) {
        return threads.filter(t => isThreadMatchingFilters(t, selectedCategories, selectedBranch, startDate, endDate)).length;
      }
      // If not selected, count cumulative intersection preview (...selectedCategories + tagCode)
      return threads.filter(t => isThreadMatchingFilters(t, [...selectedCategories, tagCode], selectedBranch, startDate, endDate)).length;
    };

    taxonomyGroups.forEach(group => {
      group.items.forEach(item => {
        counts[item.code] = computeTagCount(item.code);
      });
    });

    SPECIAL_FILTERS.forEach(f => {
      counts[f.code] = computeTagCount(f.code);
    });

    return counts;
  }, [threads, selectedCategories, selectedBranch, startDate, endDate, taxonomyGroups]);

  const filteredThreads = React.useMemo(() => {
    const list = threads.filter(t => isThreadMatchingFilters(t, selectedCategories, selectedBranch, startDate, endDate));

    return [...list].sort((a, b) => {
      const idA = Number(a.id) || 0;
      const idB = Number(b.id) || 0;
      if (idA !== idB) return idB - idA;
      const timeA = getThreadDayjs(a).valueOf();
      const timeB = getThreadDayjs(b).valueOf();
      return timeB - timeA;
    });
  }, [threads, selectedCategories, selectedBranch, startDate, endDate]);

  // MUI THREAD PAGINATION
  const THREADS_PER_PAGE = 4;
  const [threadPage, setThreadPage] = useState(1);

  useEffect(() => {
    setThreadPage(1);
  }, [selectedCategories, selectedBranch, startDate, endDate]);

  const totalThreadPages = Math.ceil(filteredThreads.length / THREADS_PER_PAGE) || 1;
  const paginatedThreads = React.useMemo(() => {
    const start = (threadPage - 1) * THREADS_PER_PAGE;
    return filteredThreads.slice(start, start + THREADS_PER_PAGE);
  }, [filteredThreads, threadPage]);

  const toggleThreadExpansion = (threadId) => {
    setExpandedThreads((prev) => ({
      ...prev,
      [threadId]: !prev[threadId]
    }));
  };

  const resolveCategoryCode = (post, threadObj) => {
    const rawCategory = post?.category_type || post?.category || threadObj?.category_type || threadObj?.category || '';
    if (!rawCategory) return '';

    const typeItems = taxonomies?.type || [];
    const directMatch = typeItems.find(t => t.code === rawCategory || t.title === rawCategory);
    if (directMatch) return directMatch.code;

    const lower = String(rawCategory).toLowerCase();
    const fuzzyMatch = typeItems.find(t =>
      t.code.toLowerCase().includes(lower) ||
      t.title.toLowerCase().includes(lower) ||
      lower.includes(t.title.toLowerCase())
    );

    if (fuzzyMatch) return fuzzyMatch.code;

    if (lower.includes('finding')) return 'TYPE_FINDING';
    if (lower.includes('solution') || lower.includes('corrective')) return 'TYPE_SOLUTION';
    if (lower.includes('observation')) return 'TYPE_OBSERVATION';
    if (lower.includes('recom')) return 'TYPE_RECOM';

    return typeItems[0]?.code || '';
  };

  const resolveBranchCode = (post, threadObj) => {
    const raw = post?.branch_code || post?.branch || threadObj?.branch_code || threadObj?.branch || '';
    if (!raw) return '';
    const branchItems = taxonomies?.branch || [];
    const direct = branchItems.find(b => b.code === raw || b.title === raw);
    if (direct) return direct.code;
    const lower = String(raw).toLowerCase();
    const fuzzy = branchItems.find(b => b.code.toLowerCase().includes(lower) || b.title.toLowerCase().includes(lower));
    if (fuzzy) return fuzzy.code;
    if (lower.includes('branch 1') || lower.includes('dt')) return 'BR_B1';
    if (lower.includes('branch 2') || lower.includes('ws')) return 'BR_B2';
    return branchItems[0]?.code || '';
  };

  const resolveProcessCode = (post, threadObj) => {
    const raw = post?.process_code || post?.process || threadObj?.process_code || threadObj?.process || '';
    if (!raw) return '';
    const procItems = taxonomies?.process || [];
    const direct = procItems.find(p => p.code === raw || p.title === raw);
    if (direct) return direct.code;
    const lower = String(raw).toLowerCase();
    const fuzzy = procItems.find(p => p.code.toLowerCase().includes(lower) || p.title.toLowerCase().includes(lower));
    if (fuzzy) return fuzzy.code;
    return '';
  };

  const resolveRiskLevel = (post, threadObj) => {
    const raw = post?.risk_level || post?.risk || threadObj?.risk_level || threadObj?.risk || '';
    if (!raw) return 'MEDIUM';
    const lower = String(raw).toLowerCase();
    if (lower.includes('low')) return 'LOW';
    if (lower.includes('high')) return 'HIGH';
    if (lower.includes('critical')) return 'CRITICAL';
    return 'MEDIUM';
  };

  const handleOpenReplyModal = (thread, specificPost = null) => {
    const postToQuote = specificPost || thread;
    setTargetThread(thread);
    setTargetPost(postToQuote);
    setSubmitError(null);

    const apaString = formatTargetApaCitation(thread, postToQuote);
    setTargetCitationApa(apaString);

    // Auto-complete category, branch, process, and risk level from parent post block
    setReplyCategory(resolveCategoryCode(postToQuote, thread));
    setReplyBranch(resolveBranchCode(postToQuote, thread));
    setReplyProcess(resolveProcessCode(postToQuote, thread));
    setReplyRisk(resolveRiskLevel(postToQuote, thread));

    if (replyEditor) {
      replyEditor.commands.setContent('<p></p>');
    }
    setReplyModalOpen(true);
  };

  const handleModalPhotoUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file || !replyEditor) return;

    const formData = new FormData();
    formData.append('image', file);

    try {
      const res = await axios.post('/reports/upload-image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      if (res.data && res.data.url) {
        replyEditor.chain().focus().setImage({ src: res.data.url }).run();
      }
    } catch (err) {
      console.error('Modal photo upload failed:', err?.response?.data || err.message);
      alert('Failed to upload image. Please try again.');
    }
  };

  const handleSubmitReply = () => {
    if (!replyEditor || !targetThread) return;

    const htmlContent = replyEditor.getHTML();
    const plainText = replyEditor.getText();

    if (!plainText.trim() && !htmlContent.includes('<img')) {
      setSubmitError('Please type reply content or upload an image before submitting.');
      return;
    }

    setIsSubmitting(true);
    setSubmitError(null);

    const payload = {
      category_type: replyCategory,
      branch_code: replyBranch,
      process_code: replyProcess,
      risk_level: replyRisk,
      plain_text: plainText,
      html_content: htmlContent,
      target_citation: targetCitationApa
    };

    axios.post(`/reports/${targetThread.id}/replies`, payload)
      .then((res) => {
        const newReply = res.data.reply || {
          id: Date.now(),
          author: 'CurrentUser_Anon',
          timestamp: new Date().toLocaleTimeString(),
          content: plainText,
          html_content: htmlContent,
          images: []
        };

        appendReplyToThread(targetThread.id, newReply);

        setReplyModalOpen(false);
        setSubmitError(null);
      })
      .catch((err) => {
        const errMsg = err.response?.data?.message || 'Database error: Unable to save reply to DB. Please check required fields and retry.';
        setSubmitError(errMsg);
      })
      .finally(() => {
        setIsSubmitting(false);
      });
  };

  const appendReplyToThread = (threadId, newReply) => {
    // Save to localStorage for instant local persistence across page refresh
    const savedLocalReplies = JSON.parse(localStorage.getItem('stt_imageboard_replies_store') || '{}');
    const updatedForThread = [...(savedLocalReplies[threadId] || []), newReply];
    savedLocalReplies[threadId] = updatedForThread;
    localStorage.setItem('stt_imageboard_replies_store', JSON.stringify(savedLocalReplies));

    setThreads(prevThreads =>
      prevThreads.map(t => {
        if (t.id === threadId) {
          return {
            ...t,
            replies: [...t.replies, newReply]
          };
        }
        return t;
      })
    );

    setExpandedThreads(prev => ({
      ...prev,
      [threadId]: true
    }));
  };

  return (
    <Box sx={{ bgcolor: '#FFFFEE', minHeight: '100vh', p: { xs: 2, sm: 4 }, fontFamily: 'Courier New, monospace' }}>
      {/* STICKY CONTROL & FILTER CONTAINER */}
      <Paper
        elevation={0}
        className="sticky top-0 z-20 bg-[#FFFFEE]/95 backdrop-blur border-b border-amber-900/20 shadow-xs"
        sx={{
          position: 'sticky',
          top: 0,
          zIndex: 20,
          bgcolor: 'rgba(255, 255, 238, 0.95)',
          backdropFilter: 'blur(8px)',
          borderBottom: '1px solid rgba(120, 53, 15, 0.2)',
          boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)',
          borderRadius: 1.5,
          p: 2,
          mb: 2.5
        }}
      >
        {/* TOP CONTROLS BAR: TITLE + BRANCH SELECTOR + DATE RANGE */}
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 2, mb: 1.5 }}>
          <Typography variant="h6" sx={{ fontWeight: 800, color: '#800000', fontFamily: 'serif' }}>
            Analytic Report
          </Typography>

          <Box sx={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: 1.5 }}>
            {/* Branch Selection Dropdown */}
            <FormControl size="small" sx={{ minWidth: 160 }}>
              <InputLabel sx={{ fontSize: '0.75rem', fontWeight: 700, color: '#800000' }}>Branch</InputLabel>
              <Select
                value={selectedBranch}
                label="Branch"
                onChange={(e) => setSelectedBranch(e.target.value)}
                sx={{
                  fontSize: '0.8125rem',
                  fontWeight: 700,
                  color: '#800000',
                  bgcolor: '#F0E0D6',
                  borderRadius: 1,
                  '& .MuiOutlinedInput-notchedOutline': { borderColor: '#D9BFB7' }
                }}
              >
                <MenuItem value="All">All Branches</MenuItem>
                {(taxonomies.branch || []).map((b) => (
                  <MenuItem key={b.code} value={b.code}>{b.title}</MenuItem>
                ))}
                {(!taxonomies.branch || taxonomies.branch.length === 0) && [
                  <MenuItem key="BR_1" value="Branch 1">Branch 1 (Downtown)</MenuItem>,
                  <MenuItem key="BR_2" value="Branch 2">Branch 2 (Westside)</MenuItem>
                ]}
              </Select>
            </FormControl>

            {/* MUI Uncontrolled DatePickers wrapped in LocalizationProvider */}
            <LocalizationProvider dateAdapter={AdapterDayjs}>
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
                <DatePicker
                  label="From"
                  value={startDate}
                  onChange={(newValue) => setStartDate(newValue)}
                  slotProps={{
                    textField: {
                      size: 'small',
                      className: 'bg-white rounded',
                      sx: {
                        width: 145,
                        bgcolor: '#FFFFFF',
                        borderRadius: 1,
                        '& .MuiInputBase-root': { fontSize: '0.75rem', fontWeight: 600 },
                        '& .MuiInputLabel-root': { fontSize: '0.75rem', fontWeight: 700 }
                      }
                    }
                  }}
                />
                <DatePicker
                  label="To"
                  value={endDate}
                  onChange={(newValue) => setEndDate(newValue)}
                  slotProps={{
                    textField: {
                      size: 'small',
                      className: 'bg-white rounded',
                      sx: {
                        width: 145,
                        bgcolor: '#FFFFFF',
                        borderRadius: 1,
                        '& .MuiInputBase-root': { fontSize: '0.75rem', fontWeight: 600 },
                        '& .MuiInputLabel-root': { fontSize: '0.75rem', fontWeight: 700 }
                      }
                    }
                  }}
                />
              </Box>
            </LocalizationProvider>

            {/* Reset Filters */}
            {(selectedBranch !== 'All' || startDate || endDate || selectedCategories.length > 0) && (
              <Button
                size="small"
                onClick={() => {
                  setSelectedBranch('All');
                  setStartDate(null);
                  setEndDate(null);
                  setSelectedCategories([]);
                }}
                sx={{
                  fontSize: '0.75rem',
                  fontWeight: 700,
                  color: '#D00000',
                  textTransform: 'none',
                  px: 1,
                  py: 0.5
                }}
              >
                Reset Filters
              </Button>
            )}
          </Box>
        </Box>

        {/* GROUP-BASED TAXONOMY BADGES - HOVER/CLICK PINK FILTER ICON BUTTON TO SHOW */}
        <Box
          onMouseLeave={() => setIsFilterOpen(false)}
          sx={{ pt: 1, borderTop: '1px dashed #D9BFB7', position: 'relative' }}
        >
          {/* FILTER HEADER & PINK MUI FILTER ICON BUTTON */}
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 1, mb: 1 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <Typography variant="caption" sx={{ fontWeight: 800, color: '#800000', fontSize: '0.825rem' }}>
                [ Analytic Report Categories ]
              </Typography>

              {/* PINK MUI FILTER ICON BUTTON */}
              <Tooltip title="Hover or click filter icon to view categories (Press ESC to close)">
                <IconButton
                  onClick={() => setIsFilterOpen((prev) => !prev)}
                  onMouseEnter={() => setIsFilterOpen(true)}
                  sx={{
                    bgcolor: '#ec4899', // Pink
                    color: '#ffffff',
                    p: 0.8,
                    borderRadius: '10px',
                    boxShadow: '0 2px 8px rgba(236,72,153,0.35)',
                    transition: 'all 0.15s ease-in-out',
                    '&:hover': {
                      bgcolor: '#db2777', // Darker pink
                      transform: 'scale(1.05)'
                    }
                  }}
                >
                  <Badge badgeContent={selectedCategories.length} color="error" overlap="rectangular">
                    <FilterListIcon sx={{ fontSize: 20, color: '#ffffff' }} />
                  </Badge>
                </IconButton>
              </Tooltip>

              <Typography variant="caption" sx={{ color: '#64748b', fontSize: '0.725rem', fontWeight: 600 }}>
                {isFilterOpen ? '(Hover out or press ESC to close)' : '(Hover icon to expand filters)'}
              </Typography>
            </Box>

            {selectedCategories.length > 0 && (
              <button
                type="button"
                onClick={() => setSelectedCategories([])}
                className="text-xs font-bold text-red-700 hover:underline cursor-pointer"
              >
                Clear All ({selectedCategories.length})
              </button>
            )}
          </Box>

          {/* ACTIVE SELECTED FILTERS BAR WITH REMOVE (X) ICONS - ULTRA COMPACT 7PX STYLE */}
          {selectedCategories.length > 0 && (
            <div className="flex flex-wrap items-center gap-1 mb-1.5 p-1 bg-amber-50/90 border border-amber-900/20 rounded-md">
              <span className="text-[7px] font-extrabold text-amber-900 mr-1 flex items-center gap-1 uppercase tracking-wider">
                Active:
              </span>
              {selectedCategories.map((code) => {
                let title = code;
                let color = '#3b82f6';

                const special = SPECIAL_FILTERS.find((f) => f.code === code);
                if (special) {
                  title = special.title;
                  color = special.color;
                } else {
                  taxonomyGroups.forEach((g) => {
                    const item = g.items.find((i) => i.code === code || i.title === code);
                    if (item) {
                      title = item.title;
                      color = item.color || g.color;
                    }
                  });
                }

                return (
                  <span
                    key={code}
                    style={{ backgroundColor: color }}
                    className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[7px] font-bold text-white shadow-2xs transition-all hover:opacity-90"
                  >
                    <span className="whitespace-nowrap text-[7px]">{title}</span>
                    <button
                      type="button"
                      onClick={() => handleToggleCategory(code)}
                      className="hover:bg-black/25 rounded-full p-0.5 inline-flex items-center justify-center transition-colors cursor-pointer"
                      title="Remove filter"
                    >
                      <CloseIcon style={{ fontSize: 9, color: '#FFFFFF' }} />
                    </button>
                  </span>
                );
              })}
              <button
                type="button"
                onClick={() => setSelectedCategories([])}
                className="ml-auto text-[7px] font-bold text-red-700 hover:underline cursor-pointer px-1"
              >
                Reset All
              </button>
            </div>
          )}

          {/* HOVER-EXPANDABLE 6-COLUMN GRID CONTAINER - ULTRA COMPACT 7PX STYLE */}
          <Collapse in={isFilterOpen}>
            <div className="w-full overflow-x-auto border border-amber-900/20 rounded-md bg-[#FFFFEE] shadow-xs">
              <div className="flex gap-2.5 p-2 min-w-max">
                {/* Column 1: Quick / Other Filters (General) */}
                <div className="flex flex-col gap-1 min-w-[150px] flex-1">
                  <div className="text-[7px] font-extrabold uppercase tracking-wider text-slate-500 border-b border-slate-300/60 pb-0.5 mb-0.5 whitespace-nowrap">
                    General
                  </div>
                  <div className="flex flex-col gap-1">
                    {/* 'All' Badge */}
                    <button
                      type="button"
                      onClick={() => handleToggleCategory('All')}
                      style={{
                        borderColor: selectedCategories.length === 0 ? '#334155' : '#CBD5E1',
                        backgroundColor: selectedCategories.length === 0 ? '#334155' : '#F1F5F9',
                        color: selectedCategories.length === 0 ? '#FFFFFF' : '#475569'
                      }}
                      className={`w-full inline-flex items-center justify-between gap-1.5 px-1.5 py-0.5 rounded text-[7px] font-bold transition-all duration-150 cursor-pointer border whitespace-nowrap hover:-translate-y-0.5 ${
                        selectedCategories.length === 0 ? 'shadow-xs' : ''
                      }`}
                    >
                      <span className="whitespace-nowrap text-[7px]">All</span>
                      <span
                        style={{
                          backgroundColor: selectedCategories.length === 0 ? 'rgba(255,255,255,0.25)' : '#94A3B8',
                          color: '#FFFFFF'
                        }}
                        className="px-1 py-0.1 rounded text-[7px] font-extrabold shrink-0"
                      >
                        [{tagCounts['All'] || 0}]
                      </span>
                    </button>

                    {/* Special System Status Filters */}
                    {SPECIAL_FILTERS.map((sFilter) => {
                      const isSelected = selectedCategories.includes(sFilter.code);
                      const count = tagCounts[sFilter.code] || 0;
                      const fColor = sFilter.color;

                      return (
                        <button
                          key={sFilter.code}
                          type="button"
                          onClick={() => handleToggleCategory(sFilter.code)}
                          style={{
                            borderColor: isSelected ? fColor : `${fColor}50`,
                            backgroundColor: isSelected ? fColor : `${fColor}15`,
                            color: isSelected ? '#FFFFFF' : fColor
                          }}
                          className={`w-full inline-flex items-center justify-between gap-1.5 px-1.5 py-0.5 rounded text-[7px] font-bold transition-all duration-150 cursor-pointer border whitespace-nowrap hover:-translate-y-0.5 ${
                            isSelected ? 'shadow-xs' : ''
                          }`}
                        >
                          <span className="whitespace-nowrap text-left text-[7px]">{sFilter.title}</span>
                          <span
                            style={{
                              backgroundColor: isSelected ? 'rgba(255,255,255,0.25)' : fColor,
                              color: '#FFFFFF'
                            }}
                            className="px-1 py-0.1 rounded text-[7px] font-extrabold shrink-0"
                          >
                            [{count}]
                          </span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Columns 2-6: Dynamic Taxonomy Groups */}
                {taxonomyGroups.map((group) => (
                  <div key={group.group_code} className="flex flex-col gap-1 min-w-[150px] flex-1">
                    <div className="text-[7px] font-extrabold uppercase tracking-wider text-slate-500 border-b border-slate-300/60 pb-0.5 mb-0.5 whitespace-nowrap">
                      {group.group_name}
                    </div>
                    <div className="flex flex-col gap-1">
                      {group.items.filter(item => item.code !== 'All').map((item) => {
                        const isSelected = selectedCategories.includes(item.code) || selectedCategories.includes(item.title);
                        const count = tagCounts[item.code] || tagCounts[item.title] || 0;
                        const itemColor = item.color || group.color || '#2563eb';

                        return (
                          <button
                            key={item.code}
                            type="button"
                            onClick={() => handleToggleCategory(item.code)}
                            style={{
                              borderColor: isSelected ? itemColor : `${itemColor}50`,
                              backgroundColor: isSelected ? itemColor : `${itemColor}15`,
                              color: isSelected ? '#FFFFFF' : itemColor
                            }}
                            className={`w-full inline-flex items-center justify-between gap-1.5 px-1.5 py-0.5 rounded text-[7px] font-bold transition-all duration-150 cursor-pointer border whitespace-nowrap hover:-translate-y-0.5 ${
                              isSelected ? 'shadow-xs' : ''
                            }`}
                          >
                            <span className="whitespace-nowrap text-left text-[7px]">{item.title}</span>
                            <span
                              style={{
                                backgroundColor: isSelected ? 'rgba(255,255,255,0.25)' : itemColor,
                                color: '#FFFFFF'
                              }}
                              className="px-1 py-0.1 rounded text-[7px] font-extrabold shrink-0"
                            >
                              [{count}]
                            </span>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </Collapse>
        </Box>
      </Paper>

      {/* TOP HEADER GROUP TAGLINES BAR */}
      <Box
        sx={{
          textAlign: 'center',
          py: 0.75,
          px: 2,
          mb: 3,
          bgcolor: '#F0E0D6',
          border: '1px solid #D9BFB7',
          borderRadius: 1,
          color: '#800000',
          fontWeight: 700,
          fontSize: '0.875rem',
          fontStyle: 'italic',
          letterSpacing: 0.5
        }}
      >
        Intimate / Intelligence / Innovate / Inclusive / Insurance
      </Box>

      {/* THREAD LIST CARDS */}
      <Stack spacing={4}>
        {paginatedThreads.length === 0 ? (
          <Paper
            variant="outlined"
            sx={{
              bgcolor: '#FFFFEE',
              borderColor: '#D9BFB7',
              borderRadius: 2,
              p: 6,
              textAlign: 'center'
            }}
          >
            <Typography variant="h6" sx={{ fontWeight: 800, color: '#800000', mb: 1 }}>
              No Reports Found
            </Typography>
            <Typography variant="body2" sx={{ color: '#64748B' }}>
              There are currently no reports in the database matching your search or filter criteria. Create a new report to view it here.
            </Typography>
          </Paper>
        ) : (
          paginatedThreads.map((thread) => {
          const isExpanded = expandedThreads[thread.id] ?? false;
          const visibleReplies = isExpanded ? thread.replies : thread.replies.slice(0, 1);
          const hiddenCount = thread.replies.length - 1;

          const displayImage = (thread.images && thread.images.length > 0)
            ? thread.images[0]
            : COMPANY_LOGO;

          const taxonomyTagline = [thread.category, thread.branch, thread.process, thread.risk]
            .filter(Boolean)
            .join(' / ');

          const threadRefTitle = thread.referenced_solution || thread.reference_title || (thread.content && String(thread.content).toLowerCase().includes('reference') ? 'Branch 1 Pawn Weight & Scale Audit Discrepancy' : null);
          const threadRefAuthor = thread.reference_author || 'Daw Thida (Senior Manager)';
          const threadRefBranch = thread.reference_branch || thread.branch || 'Branch 1 Main';
          const threadRefDate = thread.reference_date || '2026-08-10';
          const threadRefContent = thread.reference_content || 'Mandatory corrective action plan and physical scale calibration procedure verified across all counters.';
          const threadRefCount = thread.reference_count || (thread.replies ? thread.replies.length : 3);
          const threadRefReplies = thread.reference_replies || [
            { author: 'Mg Mg (Branch 1 Manager)', date: '2026-08-11 09:15', text: 'Confirmed physical verification matches audit log and verified scale calibration.' },
            { author: 'Daw Thida (Senior Manager)', date: '2026-08-11 11:40', text: 'Dual sign-off approved for scale recalibration and posted to daily ledger.' },
            { author: 'IT Compliance Admin', date: '2026-08-11 14:05', text: 'Updated Odoo ERP inventory ledger automatically.' }
          ];

          return (
            <Paper
              key={thread.id}
              variant="outlined"
              sx={{
                bgcolor: '#FFFFEE',
                borderColor: '#D9BFB7',
                borderRadius: 1,
                p: 2.5,
                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.03)'
              }}
            >
              {/* OP HEADER BAR */}
              <Box sx={{ mb: 1 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, flexWrap: 'wrap' }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 800, color: '#0F0C5D', fontSize: '1.05rem' }}>
                    {thread.title}
                  </Typography>

                  <Typography variant="body2" sx={{ fontWeight: 700, color: '#117743' }}>
                    {thread.author}
                  </Typography>

                  <Typography variant="caption" sx={{ color: '#64748B' }}>
                    {thread.timestamp}
                  </Typography>

                  {/* [Reply] OP TRIGGER */}
                  <Box
                    component="span"
                    onClick={() => handleOpenReplyModal(thread, thread)}
                    sx={{
                      color: '#34345C',
                      cursor: 'pointer',
                      fontWeight: 700,
                      fontSize: '0.75rem',
                      bgcolor: '#F0E0D6',
                      px: 1,
                      py: 0.2,
                      borderRadius: 0.5,
                      border: '1px solid #D9BFB7',
                      '&:hover': { color: '#D00000', borderColor: '#D00000' }
                    }}
                  >
                    [Reply]
                  </Box>

                  {/* REQUIRE TODO TASK TOGGLE */}
                  {(() => {
                    const hasTask = hasRelatedTodoTask(thread);
                    const isReq = !!thread.require_todo_task;
                    let label = 'Require Task';
                    let bg = '#F1F5F9';
                    let color = '#475569';
                    let border = '#CBD5E1';

                    if (isReq && !hasTask) {
                      label = 'Task Required (Pending)';
                      bg = '#FEF2F2';
                      color = '#991B1B';
                      border = '#FCA5A5';
                    } else if (isReq && hasTask) {
                      label = 'Task Resolved ✔';
                      bg = '#D1FAE5';
                      color = '#065F46';
                      border = '#6EE7B7';
                    }

                    return (
                      <Chip
                        icon={<TaskAltIcon style={{ fontSize: 13, color: color }} />}
                        label={label}
                        size="small"
                        onClick={() => handleToggleRequireTodo(thread.id)}
                        sx={{
                          height: 22,
                          fontSize: '0.68rem',
                          fontWeight: 700,
                          bgcolor: bg,
                          color: color,
                          borderColor: border,
                          border: '1px solid',
                          cursor: 'pointer',
                          '&:hover': { opacity: 0.85 }
                        }}
                      />
                    );
                  })()}

                  {/* REQUIRE PROMOTE ACTION TOGGLE */}
                  {(() => {
                    const hasAction = hasRelatedPromoteAction(thread);
                    const isReq = !!thread.require_promote_action;
                    let label = 'Require Promote';
                    let bg = '#F1F5F9';
                    let color = '#475569';
                    let border = '#CBD5E1';

                    if (isReq && !hasAction) {
                      label = 'Promote Required (Pending)';
                      bg = '#FFFBEB';
                      color = '#92400E';
                      border = '#FCD34D';
                    } else if (isReq && hasAction) {
                      label = 'Action Resolved ✔';
                      bg = '#EEF2FF';
                      color = '#3730A3';
                      border = '#C7D2FE';
                    }

                    return (
                      <Chip
                        icon={<CampaignIcon style={{ fontSize: 13, color: color }} />}
                        label={label}
                        size="small"
                        onClick={() => handleToggleRequirePromote(thread.id)}
                        sx={{
                          height: 22,
                          fontSize: '0.68rem',
                          fontWeight: 700,
                          bgcolor: bg,
                          color: color,
                          borderColor: border,
                          border: '1px solid',
                          cursor: 'pointer',
                          '&:hover': { opacity: 0.85 }
                        }}
                      />
                    );
                  })()}
                </Box>

                {/* SUB-TITLE METADATA ROW & STATUS BADGES */}
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, flexWrap: 'wrap', mt: 0.75 }}>
                  <Chip
                    label={`[ ${thread.status || 'Master Template: Active'} ]`}
                    size="small"
                    style={{
                      backgroundColor: '#1E40AF',
                      color: '#FFFFFF',
                      fontWeight: 700,
                      fontSize: '0.7rem',
                      height: 20,
                      borderRadius: 4
                    }}
                  />

                  {thread.is_liked && (
                    <Chip
                      icon={<FavoriteIcon style={{ fontSize: 12, color: '#FFFFFF' }} />}
                      label={`Liked (${thread.likes_count || 1})`}
                      size="small"
                      sx={{ bgcolor: '#E11D48', color: '#FFFFFF', fontWeight: 700, fontSize: '0.68rem', height: 20 }}
                    />
                  )}

                  {thread.is_saved && (
                    <Chip
                      icon={<BookmarkIcon style={{ fontSize: 12, color: '#FFFFFF' }} />}
                      label="Saved"
                      size="small"
                      sx={{ bgcolor: '#2563EB', color: '#FFFFFF', fontWeight: 700, fontSize: '0.68rem', height: 20 }}
                    />
                  )}

                  <Typography variant="caption" sx={{ color: '#34345C', fontWeight: 700, fontStyle: 'italic', fontSize: '0.8rem' }}>
                    {taxonomyTagline}
                  </Typography>
                </Box>
              </Box>

              {/* OP CONTENT AREA */}
              <Box sx={{ display: 'flex', flexDirection: { xs: 'column', sm: 'row' }, gap: 2, mb: 2, mt: 1.5 }}>
                <Box sx={{ width: 140, flexShrink: 0 }}>
                  <Box
                    component="img"
                    src={displayImage}
                    alt="Post thumbnail"
                    onError={(e) => {
                      e.target.src = COMPANY_LOGO;
                    }}
                    sx={{
                      width: 140,
                      height: 105,
                      objectFit: 'cover',
                      borderRadius: 1,
                      border: '1px solid #D9BFB7',
                      bgcolor: '#F8FAFC'
                    }}
                  />

                  {/* HEART (LIKE) AND BOOKMARK (SAVE) BUTTONS DIRECTLY UNDER PHOTO DIV */}
                  <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mt: 0.75, p: 0.25, bgcolor: '#FAF5FF', borderRadius: 1, border: '1px solid #E9D5FF' }}>
                    <Tooltip title={thread.is_liked ? "Unlike post" : "Like post (Heart)"}>
                      <Button
                        size="small"
                        onClick={() => handleToggleLike(thread.id)}
                        startIcon={thread.is_liked ? <FavoriteIcon style={{ fontSize: 14, color: '#E11D48' }} /> : <FavoriteBorderIcon style={{ fontSize: 14, color: '#64748B' }} />}
                        sx={{
                          minWidth: 0,
                          px: 0.5,
                          py: 0.1,
                          fontSize: '0.68rem',
                          fontWeight: 800,
                          color: thread.is_liked ? '#E11D48' : '#475569',
                          textTransform: 'none'
                        }}
                      >
                        {thread.likes_count || (thread.is_liked ? 1 : 0)}
                      </Button>
                    </Tooltip>

                    <Divider orientation="vertical" flexItem sx={{ my: 0.5 }} />

                    <Tooltip title={thread.is_saved ? "Remove bookmark" : "Save post (Bookmark)"}>
                      <Button
                        size="small"
                        onClick={() => handleToggleBookmark(thread.id)}
                        startIcon={thread.is_saved ? <BookmarkIcon style={{ fontSize: 14, color: '#2563EB' }} /> : <BookmarkBorderIcon style={{ fontSize: 14, color: '#64748B' }} />}
                        sx={{
                          minWidth: 0,
                          px: 0.5,
                          py: 0.1,
                          fontSize: '0.68rem',
                          fontWeight: 800,
                          color: thread.is_saved ? '#2563EB' : '#475569',
                          textTransform: 'none'
                        }}
                      >
                        {thread.is_saved ? 'Saved' : 'Save'}
                      </Button>
                    </Tooltip>
                  </Box>
                </Box>

                <Box sx={{ flexGrow: 1, pt: 0.5 }}>
                  <div className="line-clamp-5 overflow-hidden text-ellipsis font-mono text-sm leading-relaxed text-slate-800">
                    {render4chanFormattedText(thread.content, handleOpenTodoDetail, handleOpenActionDetail)}
                  </div>
                </Box>
              </Box>

              {/* COLLAPSIBLE REFERENCED POST - EXACT IMAGEBOARD REPLY STYLE */}
              {threadRefTitle && (
                <Box sx={{ mb: 1.5, pl: { xs: 1, sm: 4 } }}>
                  <Typography
                    variant="caption"
                    onClick={() => toggleReferenceExpansion(thread.id)}
                    sx={{
                      color: '#707070',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: 0.5,
                      mb: 0.75,
                      '&:hover': { color: '#D00000', textDecoration: 'underline' }
                    }}
                  >
                    {expandedReferences[thread.id] ? (
                      <>
                        <ExpandLessIcon style={{ fontSize: 16 }} />
                        [-] Collapse reference post: "{threadRefTitle}"
                      </>
                    ) : (
                      <>
                        <ExpandMoreIcon style={{ fontSize: 16 }} />
                        [+] 1 referenced post ({threadRefCount} references). Click here to view details.
                      </>
                    )}
                  </Typography>

                  <Collapse in={expandedReferences[thread.id]} timeout="auto" unmountOnExit>
                    <Paper
                      variant="outlined"
                      sx={{
                        bgcolor: '#D6DAF0',
                        borderColor: '#B7C5D9',
                        borderRadius: 1,
                        p: 1.75,
                        maxWidth: 720
                      }}
                    >
                      {/* HEADER */}
                      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1, flexWrap: 'wrap' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                          <Typography variant="caption" sx={{ fontWeight: 800, color: '#0F0C5D' }}>
                            🔖 {threadRefTitle}
                          </Typography>
                          <Typography variant="caption" sx={{ fontWeight: 700, color: '#117743' }}>
                            {threadRefAuthor}
                          </Typography>
                          <Typography variant="caption" sx={{ color: '#64748B' }}>
                            {threadRefDate} • {threadRefBranch}
                          </Typography>
                        </Box>
                        <Chip
                          label={`[ ${threadRefCount} References ]`}
                          size="small"
                          sx={{ bgcolor: '#1E40AF', color: '#FFFFFF', fontWeight: 800, fontSize: '0.65rem', height: 18, borderRadius: 1 }}
                        />
                      </Box>

                      {/* CONTENT BODY */}
                      <Typography variant="body2" className="font-mono text-sm leading-relaxed text-slate-800" sx={{ bgcolor: '#FFFFEE', p: 1.25, borderRadius: 1, border: '1px solid #D9BFB7', mb: 1.5 }}>
                        "{threadRefContent}"
                      </Typography>

                      {/* REPLIED CONTENTS LOG */}
                      <Box sx={{ pt: 1, borderTop: '1px dashed #B7C5D9' }}>
                        <Typography variant="caption" sx={{ fontWeight: 800, color: '#34345C', display: 'block', mb: 1 }}>
                          Replied Contents & Reference Log ({threadRefReplies.length}):
                        </Typography>
                        <Stack spacing={1}>
                          {threadRefReplies.map((reply, rIdx) => (
                            <Paper
                              key={`thread-ref-reply-${rIdx}`}
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
                                  💬 {reply.author}
                                </Typography>
                                <Typography variant="caption" sx={{ color: '#64748B', fontSize: '0.65rem' }}>
                                  {reply.date}
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

              {/* HIDDEN REPLIES COLLAPSIBLE BUTTON */}
              {thread.replies.length > 1 && (
                <Box sx={{ mb: 1.5, pl: 2 }}>
                  <Typography
                    variant="caption"
                    onClick={() => toggleThreadExpansion(thread.id)}
                    sx={{
                      color: '#707070',
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: 0.5,
                      '&:hover': { color: '#D00000', textDecoration: 'underline' }
                    }}
                  >
                    {isExpanded ? (
                      <>
                        <ExpandLessIcon style={{ fontSize: 16 }} />
                        [-] Collapse replies.
                      </>
                    ) : (
                      <>
                        <ExpandMoreIcon style={{ fontSize: 16 }} />
                        [+] {hiddenCount} replies omitted. Click here to view.
                      </>
                    )}
                  </Typography>
                </Box>
              )}

              {/* INDENTED SUB-REPLIES CONTAINER */}
              <Stack spacing={1.5} sx={{ pl: { xs: 1, sm: 4 } }}>
                {visibleReplies.map((reply) => {
                  const replyImage = (reply.images && reply.images.length > 0)
                    ? reply.images[0]
                    : COMPANY_LOGO;

                  return (
                    <Paper
                      key={reply.id}
                      variant="outlined"
                      sx={{
                        bgcolor: '#D6DAF0',
                        borderColor: '#B7C5D9',
                        borderRadius: 1,
                        p: 1.5,
                        maxWidth: 680
                      }}
                    >
                      <Box sx={{ display: 'flex', alignItems: 'center', justify: 'space-between', mb: 1, flexWrap: 'wrap' }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                          <Typography variant="caption" sx={{ fontWeight: 700, color: '#117743' }}>
                            {reply.author}
                          </Typography>
                          <Typography variant="caption" sx={{ color: '#64748B' }}>
                            {reply.timestamp}
                          </Typography>
                        </Box>

                        {/* [Reply] SUB-REPLY TRIGGER */}
                        <Box
                          component="span"
                          onClick={() => handleOpenReplyModal(thread, reply)}
                          sx={{
                            color: '#34345C',
                            cursor: 'pointer',
                            fontWeight: 700,
                            fontSize: '0.7rem',
                            bgcolor: '#EFF6FF',
                            px: 0.75,
                            py: 0.1,
                            borderRadius: 0.5,
                            border: '1px solid #BFDBFE',
                            '&:hover': { color: '#D00000', borderColor: '#D00000' }
                          }}
                        >
                          [Reply]
                        </Box>
                      </Box>

                      <Box sx={{ display: 'flex', gap: 1.5 }}>
                        <Box
                          component="img"
                          src={replyImage}
                          alt="Reply thumbnail"
                          onError={(e) => {
                            e.target.src = COMPANY_LOGO;
                          }}
                          sx={{ width: 64, height: 48, objectFit: 'cover', borderRadius: 1, border: '1px solid #B7C5D9', flexShrink: 0 }}
                        />
                        <Box sx={{ flexGrow: 1 }}>
                          <div className="line-clamp-5 overflow-hidden text-ellipsis font-mono text-sm leading-relaxed text-slate-800">
                            {render4chanFormattedText(reply.content || reply.html_content, handleOpenTodoDetail, handleOpenActionDetail)}
                          </div>
                        </Box>
                      </Box>
                    </Paper>
                  );
                })}
              </Stack>
            </Paper>
          );
        }))}
      </Stack>

      {/* MUI THREAD PAGINATION */}
      {totalThreadPages > 1 && (
        <Box sx={{ display: 'flex', justifyContent: 'center', my: 4 }}>
          <Pagination
            count={totalThreadPages}
            page={threadPage}
            onChange={(e, v) => setThreadPage(v)}
            color="primary"
            size="medium"
            showFirstButton
            showLastButton
            sx={{
              bgcolor: '#FFFFFF',
              px: 2.5,
              py: 1,
              borderRadius: 2,
              border: '1px solid #D9BFB7',
              boxShadow: '0 2px 8px rgba(0,0,0,0.05)',
              '& .MuiPaginationItem-root': { fontWeight: 700 }
            }}
          />
        </Box>
      )}

      {/* RICH TEXT REPLY MODAL (<Dialog>) WITH ASYNC SUCCESS/ERROR HANDLING */}
      <Dialog
        open={replyModalOpen}
        onClose={() => !isSubmitting && setReplyModalOpen(false)}
        fullWidth
        maxWidth="md"
        PaperProps={{
          sx: { borderRadius: 3, p: 1 }
        }}
      >
        <DialogTitle sx={{ fontWeight: 800, color: '#0F172A', display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Typography variant="h6" sx={{ fontWeight: 800 }}>
              Post Reply to: "{targetThread?.title}"
            </Typography>
          </Box>
          <IconButton size="small" onClick={() => !isSubmitting && setReplyModalOpen(false)}>
            <CloseIcon fontSize="small" />
          </IconButton>
        </DialogTitle>

        <DialogContent dividers sx={{ py: 2 }}>
          {/* RED ERROR ALERT BANNER ON POST FAILURE */}
          {submitError && (
            <Alert severity="error" onClose={() => setSubmitError(null)} sx={{ mb: 2, borderRadius: 2 }}>
              {submitError}
            </Alert>
          )}

          <input
            type="file"
            ref={modalFileInputRef}
            accept="image/*"
            style={{ display: 'none' }}
            onChange={handleModalPhotoUpload}
          />

          {/* READ-ONLY TARGET CITATION BANNER (LOCKED) */}
          <Paper
            variant="outlined"
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 1.5,
              bgcolor: '#EFF6FF',
              borderColor: '#BFDBFE',
              borderRadius: 2,
              p: 1.5,
              mb: 2.5
            }}
          >
            <MenuBookIcon color="primary" fontSize="small" />
            <Box sx={{ flexGrow: 1 }}>
              <Typography variant="caption" sx={{ fontWeight: 800, color: '#1E40AF', display: 'block' }}>
                REPLYING TO TARGET CITATION (LOCKED)
              </Typography>
              <Typography variant="body2" sx={{ fontWeight: 600, color: '#1E3A8A', fontStyle: 'italic', fontSize: '0.85rem' }}>
                "{targetCitationApa}"
              </Typography>
            </Box>
            <Chip label="Locked APA Citation" size="small" color="primary" sx={{ height: 22, fontSize: '0.68rem', fontWeight: 700 }} />
          </Paper>

          {/* TAXONOMY DROPDOWN SELECTORS */}
          <Grid container spacing={2} sx={{ mb: 2.5 }}>
            <Grid item xs={12} sm={3}>
              <FormControl variant="standard" fullWidth>
                <InputLabel sx={{ fontWeight: 600, fontSize: '0.85rem' }}>Category Type</InputLabel>
                <Select
                  value={replyCategory}
                  onChange={(e) => setReplyCategory(e.target.value)}
                  sx={{ fontSize: '0.875rem' }}
                >
                  <MenuItem value=""><em>None</em></MenuItem>
                  {(taxonomies.type || []).map((item) => (
                    <MenuItem key={item.code} value={item.code}>{item.title}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} sm={3}>
              <FormControl variant="standard" fullWidth>
                <InputLabel sx={{ fontWeight: 600, fontSize: '0.85rem' }}>Branch Location</InputLabel>
                <Select
                  value={replyBranch}
                  onChange={(e) => setReplyBranch(e.target.value)}
                  sx={{ fontSize: '0.875rem' }}
                >
                  <MenuItem value=""><em>None</em></MenuItem>
                  {(taxonomies.branch || []).map((item) => (
                    <MenuItem key={item.code} value={item.code}>{item.title}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} sm={3}>
              <FormControl variant="standard" fullWidth>
                <InputLabel sx={{ fontWeight: 600, fontSize: '0.85rem' }}>Operational Process</InputLabel>
                <Select
                  value={replyProcess}
                  onChange={(e) => setReplyProcess(e.target.value)}
                  sx={{ fontSize: '0.875rem' }}
                >
                  <MenuItem value=""><em>None</em></MenuItem>
                  {(taxonomies.process || []).map((item) => (
                    <MenuItem key={item.code} value={item.code}>{item.title}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            </Grid>

            <Grid item xs={12} sm={3}>
              <FormControl variant="standard" fullWidth>
                <InputLabel sx={{ fontWeight: 600, fontSize: '0.85rem' }}>Risk Severity</InputLabel>
                <Select
                  value={replyRisk}
                  onChange={(e) => setReplyRisk(e.target.value)}
                  sx={{ fontSize: '0.875rem', fontWeight: 600 }}
                >
                  <MenuItem value="LOW">Low Risk</MenuItem>
                  <MenuItem value="MEDIUM">Medium Risk</MenuItem>
                  <MenuItem value="HIGH">High Risk</MenuItem>
                  <MenuItem value="CRITICAL">Critical Risk</MenuItem>
                </Select>
              </FormControl>
            </Grid>
          </Grid>

          {/* STICKY TIPTAP TOOLBAR */}
          {replyEditor && (
            <Box
              sx={{
                position: 'sticky',
                top: 0,
                zIndex: 10,
                mb: 1.5,
                display: 'flex',
                flexWrap: 'wrap',
                gap: 0.5,
                alignItems: 'center',
                bgcolor: '#F8FAFC',
                p: 0.85,
                borderRadius: 2,
                border: '1px solid #CBD5E1',
                borderBottom: '2px solid #3B82F6',
                boxShadow: '0 2px 6px rgba(15, 23, 42, 0.04)'
              }}
            >
              <ToggleButtonGroup size="small" sx={{ height: 28 }}>
                <ToggleButton
                  value="bold"
                  selected={replyEditor.isActive('bold')}
                  onClick={() => replyEditor.chain().focus().toggleBold().run()}
                >
                  <Tooltip title="Bold"><FormatBoldIcon fontSize="small" /></Tooltip>
                </ToggleButton>

                <ToggleButton
                  value="italic"
                  selected={replyEditor.isActive('italic')}
                  onClick={() => replyEditor.chain().focus().toggleItalic().run()}
                >
                  <Tooltip title="Italic"><FormatItalicIcon fontSize="small" /></Tooltip>
                </ToggleButton>

                <ToggleButton
                  value="underline"
                  selected={replyEditor.isActive('underline')}
                  onClick={() => replyEditor.chain().focus().toggleUnderline().run()}
                >
                  <Tooltip title="Underline"><FormatUnderlinedIcon fontSize="small" /></Tooltip>
                </ToggleButton>
              </ToggleButtonGroup>

              <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />

              <ToggleButtonGroup size="small" sx={{ height: 28 }}>
                <ToggleButton
                  value="bulletList"
                  selected={replyEditor.isActive('bulletList')}
                  onClick={() => replyEditor.chain().focus().toggleBulletList().run()}
                >
                  <Tooltip title="Bullet List"><FormatListBulletedIcon fontSize="small" /></Tooltip>
                </ToggleButton>

                <ToggleButton
                  value="orderedList"
                  selected={replyEditor.isActive('orderedList')}
                  onClick={() => replyEditor.chain().focus().toggleOrderedList().run()}
                >
                  <Tooltip title="Numbered List"><FormatListNumberedIcon fontSize="small" /></Tooltip>
                </ToggleButton>

                <ToggleButton
                  value="blockquote"
                  selected={replyEditor.isActive('blockquote')}
                  onClick={() => replyEditor.chain().focus().toggleBlockquote().run()}
                >
                  <Tooltip title="Blockquote"><FormatQuoteIcon fontSize="small" /></Tooltip>
                </ToggleButton>
              </ToggleButtonGroup>

              <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />

              <Tooltip title="Upload Photo">
                <IconButton
                  size="small"
                  color="primary"
                  onClick={() => modalFileInputRef.current?.click()}
                  sx={{ height: 28, width: 28 }}
                >
                  <AddPhotoAlternateIcon fontSize="small" />
                </IconButton>
              </Tooltip>
            </Box>
          )}

          {/* TIPTAP RICH TEXT EDITOR CONTAINER */}
          <Box
            sx={{
              minHeight: 140,
              borderRadius: 2,
              bgcolor: '#FFFFFF',
              border: '1px solid #CBD5E1',
              p: 1.5,
              '& .ProseMirror': {
                outline: 'none !important',
                minHeight: '120px'
              },
              '& .is-editor-empty:first-of-type::before': {
                color: '#94A3B8',
                content: 'attr(data-placeholder)',
                float: 'left',
                height: 0,
                pointerEvents: 'none',
                fontStyle: 'italic'
              }
            }}
          >
            <EditorContent editor={replyEditor} className="prose max-w-none text-slate-800" />
          </Box>
        </DialogContent>

        <DialogActions sx={{ p: 2, justifyContent: 'space-between' }}>
          <Button onClick={() => !isSubmitting && setReplyModalOpen(false)} disabled={isSubmitting} sx={{ fontWeight: 600, color: '#64748B' }}>
            Cancel
          </Button>
          <Button
            variant="contained"
            color="primary"
            disabled={isSubmitting}
            startIcon={isSubmitting ? <CircularProgress size={16} color="inherit" /> : <SendIcon />}
            onClick={handleSubmitReply}
            sx={{ borderRadius: 2, px: 3, fontWeight: 700, textTransform: 'none' }}
          >
            {isSubmitting ? 'Saving to Database...' : 'Submit Reply'}
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

      {/* PROMOTE ACTION DETAIL MODAL */}
      <Dialog
        open={actionDetailModalOpen}
        onClose={() => setActionDetailModalOpen(false)}
        fullWidth
        maxWidth="xs"
      >
        <DialogTitle sx={{ bgcolor: '#3730A3', color: '#FFFFFF', py: 1.5, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>
            📌 Promote Action Details
          </Typography>
          <IconButton size="small" onClick={() => setActionDetailModalOpen(false)} sx={{ color: '#FFFFFF' }}>
            <CloseIcon fontSize="small" />
          </IconButton>
        </DialogTitle>
        <DialogContent sx={{ pt: 2.5, pb: 2 }}>
          {selectedPromoteAction && (
            <Stack spacing={2}>
              <Box>
                <Typography variant="caption" sx={{ color: '#64748B', fontWeight: 700, display: 'block', mb: 0.5 }}>
                  ACTION NAME
                </Typography>
                <Typography variant="body1" sx={{ fontWeight: 800, color: '#0F172A' }}>
                  {selectedPromoteAction.action_name || selectedPromoteAction.title || 'Security Audit'}
                </Typography>
              </Box>

              <Grid container spacing={2}>
                <Grid item xs={6}>
                  <Typography variant="caption" sx={{ color: '#64748B', fontWeight: 700, display: 'block', mb: 0.5 }}>
                    START DATE
                  </Typography>
                  <Typography variant="body2" sx={{ fontWeight: 700, color: '#334155' }}>
                    {selectedPromoteAction.start_date || selectedPromoteAction.start_at || '2026-08-01'}
                  </Typography>
                </Grid>
                <Grid item xs={6}>
                  <Typography variant="caption" sx={{ color: '#64748B', fontWeight: 700, display: 'block', mb: 0.5 }}>
                    END DATE
                  </Typography>
                  <Typography variant="body2" sx={{ fontWeight: 700, color: '#334155' }}>
                    {selectedPromoteAction.end_date || selectedPromoteAction.end_at || '2026-08-15'}
                  </Typography>
                </Grid>
              </Grid>

              <Box>
                <Typography variant="caption" sx={{ color: '#64748B', fontWeight: 700, display: 'block', mb: 0.5 }}>
                  BRANCH / DEPARTMENT
                </Typography>
                <Typography variant="body2" sx={{ fontWeight: 700, color: '#334155' }}>
                  {selectedPromoteAction.branch || 'Branch 1 (Downtown)'}
                </Typography>
              </Box>

              <Box>
                <Typography variant="caption" sx={{ color: '#64748B', fontWeight: 700, display: 'block', mb: 0.5 }}>
                  PROMOTION DETAILS
                </Typography>
                <Paper variant="outlined" sx={{ p: 1.5, bgcolor: '#F8FAFC', borderRadius: 1 }}>
                  <Typography variant="body2" sx={{ color: '#475569', fontSize: '0.8125rem' }}>
                    {selectedPromoteAction.description || 'High-priority action item promoted directly to management review board.'}
                  </Typography>
                </Paper>
              </Box>
            </Stack>
          )}
        </DialogContent>
        <DialogActions sx={{ px: 2.5, pb: 2 }}>
          <Button variant="contained" size="small" onClick={() => setActionDetailModalOpen(false)} sx={{ bgcolor: '#3730A3', '&:hover': { bgcolor: '#312E81' } }}>
            Close
          </Button>
        </DialogActions>
      </Dialog>

      {/* REAL APPLICATION TO-DO TASK CREATION MODAL (SAVES TO DATABASE TABLE VIA /todo/tasks) */}
      <CreateTaskModal
        isOpen={createTodoModalOpen}
        onClose={() => setCreateTodoModalOpen(false)}
        onSuccessTask={(taskInfo) => {
          if (replyEditor) {
            const name = (taskInfo.task || '').trim() || 'Clean Vault Area';
            const status = taskInfo.status || 'In Progress';
            replyEditor.chain().focus().insertContent(` [todo_task: ${name} | ${status}] `).run();
          }
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
              if (replyEditor) {
                const name = newPromoteTitle.trim() || 'Security Audit';
                const start = newPromoteStart || '2026-08-01';
                const end = newPromoteEnd || '2026-08-15';
                replyEditor.chain().focus().insertContent(` [promote_action: ${name} | ${start} - ${end}] `).run();
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

      {/* FULL APPLICATION PROMOTE ACTION CREATION MODAL */}
      <CreatePromoteActionModal />
    </Box>
  );
}
