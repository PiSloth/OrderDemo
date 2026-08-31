import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AsideLayout from '../../../Layouts/AsideLayout';
import DocumentSearchModal from '../../../components/Document/DocumentSearchModal';

import {
  Box,
  Button,
  Card,
  CardContent,
  Typography,
  Chip,
  IconButton,
  TextField,
  MenuItem,
  FormControlLabel,
  Checkbox,
  Stack,
  Divider,
  Paper,
  Tabs,
  Tab,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Pagination,
  Tooltip,
  Collapse,
  Drawer
} from '@mui/material';

import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
import SearchIcon from '@mui/icons-material/Search';
import ClearIcon from '@mui/icons-material/Clear';
import FolderIcon from '@mui/icons-material/Folder';
import FolderOpenIcon from '@mui/icons-material/FolderOpen';
import ArticleIcon from '@mui/icons-material/Article';
import HistoryIcon from '@mui/icons-material/History';
import CampaignIcon from '@mui/icons-material/Campaign';
import FilterListIcon from '@mui/icons-material/FilterList';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import VisibilityIcon from '@mui/icons-material/Visibility';
import TuneIcon from '@mui/icons-material/Tune';
import MenuIcon from '@mui/icons-material/Menu';
import CloseIcon from '@mui/icons-material/Close';

export default function Index({
  treeByDepartment = {},
  treeByType = {},
  selectedDocument = null,
  searchResults = [],
  searchPaginator = {},
  searchMeta = {},
  filterOptions = { departments: [], categories: [], creators: [] },
  suggestions = [],
  filters = {},
  can = {},
  permissions = {},
}) {
  const canCreate = Boolean(can?.create ?? permissions?.can_create);
  const canUpdate = Boolean(can?.update ?? permissions?.can_update);
  const canDelete = Boolean(can?.delete ?? permissions?.can_delete);

  const [mode, setMode] = useState(filters.mode || 'department');
  const [search, setSearch] = useState(filters.q || '');
  const [department, setDepartment] = useState(filters.department || '');
  const [category, setCategory] = useState(filters.category || '');
  const [creator, setCreator] = useState(filters.creator || '');
  const [announcementOnly, setAnnouncementOnly] = useState(Boolean(filters.announcementOnly));
  const [version, setVersion] = useState(filters.version || '');
  const [publishedFrom, setPublishedFrom] = useState(filters.publishedFrom || '');
  const [publishedTo, setPublishedTo] = useState(filters.publishedTo || '');
  const [sort, setSort] = useState(filters.sort || 'relevance');

  const [activeTab, setActiveTab] = useState(0);
  const [showFilters, setShowFilters] = useState(false);
  const [searchModalOpen, setSearchModalOpen] = useState(false);
  const [mobileExplorerOpen, setMobileExplorerOpen] = useState(false);
  const [treeSearch, setTreeSearch] = useState('');
  const [expandedFolders, setExpandedFolders] = useState({});
  const [selectedRevision, setSelectedRevision] = useState(null);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);

  // Global keyboard shortcut: Ctrl+K / Cmd+K to open search modal
  useEffect(() => {
    const handleKeyDown = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        setSearchModalOpen((prev) => !prev);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

  const executeSearch = (overrideParams = {}) => {
    const params = {
      mode,
      doc: filters.doc || (selectedDocument ? selectedDocument.id : ''),
      q: search,
      department,
      category,
      creator,
      announcementOnly: announcementOnly ? 1 : undefined,
      version: version || undefined,
      publishedFrom: publishedFrom || undefined,
      publishedTo: publishedTo || undefined,
      sort,
      ...overrideParams,
    };

    // Remove empty parameters
    Object.keys(params).forEach((key) => {
      if (params[key] === '' || params[key] === undefined || params[key] === null) {
        delete params[key];
      }
    });

    router.get('/document/library', params, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  const handleClearFilters = () => {
    setSearch('');
    setDepartment('');
    setCategory('');
    setCreator('');
    setAnnouncementOnly(false);
    setVersion('');
    setPublishedFrom('');
    setPublishedTo('');
    setSort('relevance');

    router.get('/document/library', { mode, doc: selectedDocument?.id }, {
      preserveState: true,
      replace: true,
    });
  };

  const handleOpenDoc = (id) => {
    executeSearch({ doc: id });
  };

  const handlePageChange = (event, page) => {
    executeSearch({ page });
  };

  const handleDelete = () => {
    if (!selectedDocument) return;
    router.delete(`/document/library/${selectedDocument.id}`, {
      onSuccess: () => {
        setDeleteConfirmOpen(false);
      },
    });
  };

  const toggleFolder = (folderKey, isCurrentlyOpen) => {
    setExpandedFolders((prev) => ({
      ...prev,
      [folderKey]: !isCurrentlyOpen,
    }));
  };

  const isFolderOpen = (folderKey, defaultOpen = false) => {
    if (expandedFolders[folderKey] !== undefined) {
      return Boolean(expandedFolders[folderKey]);
    }
    return defaultOpen;
  };

  const activeTree = mode === 'type' ? treeByType : treeByDepartment;

  // Filter tree items based on treeSearch
  const filteredTree = React.useMemo(() => {
    if (!treeSearch.trim()) return activeTree;
    const lower = treeSearch.toLowerCase();
    const result = {};

    Object.entries(activeTree).forEach(([groupName, subGroups]) => {
      const filteredSubs = {};
      let hasMatchesInGroup = groupName.toLowerCase().includes(lower);

      Object.entries(subGroups || {}).forEach(([subName, docs]) => {
        const filteredDocs = (docs || []).filter(
          (d) => d.title.toLowerCase().includes(lower) || subName.toLowerCase().includes(lower) || groupName.toLowerCase().includes(lower)
        );
        if (filteredDocs.length > 0 || subName.toLowerCase().includes(lower) || hasMatchesInGroup) {
          filteredSubs[subName] = filteredDocs.length > 0 ? filteredDocs : docs;
        }
      });

      if (Object.keys(filteredSubs).length > 0 || hasMatchesInGroup) {
        result[groupName] = Object.keys(filteredSubs).length > 0 ? filteredSubs : subGroups;
      }
    });

    return result;
  }, [activeTree, treeSearch]);

  const hasActiveFilters = search || department || category || creator || announcementOnly || version || publishedFrom || publishedTo || sort !== 'relevance';

  const renderExplorerTreeContent = (isMobile = false) => (
    <Box className="flex flex-col h-full min-h-0">
      {/* Header & Mode Switcher */}
      <Box className="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 flex-shrink-0">
        <Box className="flex items-center justify-between gap-2 mb-3">
          <Typography variant="subtitle1" className="font-bold flex items-center gap-1.5 text-slate-800 dark:text-slate-100">
            <FolderIcon fontSize="small" className="text-indigo-600 dark:text-indigo-400" />
            Document Explorer
          </Typography>

          <div className="flex items-center gap-2">
            <Box className="inline-flex rounded-lg border border-slate-300 dark:border-slate-700 p-0.5 bg-white dark:bg-slate-800">
              <button
                type="button"
                onClick={() => {
                  setMode('department');
                  executeSearch({ mode: 'department' });
                }}
                className={`px-2.5 py-1 text-xs font-semibold rounded-md transition-all ${mode === 'department'
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                  }`}
              >
                Dept
              </button>
              <button
                type="button"
                onClick={() => {
                  setMode('type');
                  executeSearch({ mode: 'type' });
                }}
                className={`px-2.5 py-1 text-xs font-semibold rounded-md transition-all ${mode === 'type'
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                  }`}
              >
                Type
              </button>
            </Box>

            {isMobile && (
              <IconButton size="small" onClick={() => setMobileExplorerOpen(false)}>
                <CloseIcon fontSize="small" />
              </IconButton>
            )}
          </div>
        </Box>

        {/* Instant Tree Search Input */}
        <TextField
          size="small"
          fullWidth
          placeholder="Filter explorer tree..."
          value={treeSearch}
          onChange={(e) => setTreeSearch(e.target.value)}
          InputProps={{
            startAdornment: <SearchIcon fontSize="small" className="text-slate-400 mr-1.5" />,
            endAdornment: treeSearch ? (
              <IconButton size="small" onClick={() => setTreeSearch('')}>
                <ClearIcon fontSize="small" />
              </IconButton>
            ) : null,
            sx: { borderRadius: 2, bgcolor: 'background.paper', fontSize: '0.875rem' },
          }}
        />
      </Box>

      {/* Tree Hierarchical List */}
      <Box className="document-tree-scroll p-3 overflow-y-auto flex-1 min-h-0 space-y-1">
        {Object.keys(filteredTree).length === 0 ? (
          <Box className="py-8 text-center text-sm text-slate-400">
            No documents found in explorer.
          </Box>
        ) : (
          Object.entries(filteredTree).map(([groupName, subGroups]) => {
            const groupKey = `g_${groupName}`;
            const isGroupOpen = isFolderOpen(groupKey, true);
            const totalInGroup = Object.values(subGroups || {}).reduce((acc, docs) => acc + (docs?.length || 0), 0);

            return (
              <Box key={groupKey} className="rounded-xl overflow-hidden mb-1">
                {/* Top Group Header */}
                <button
                  type="button"
                  onClick={() => toggleFolder(groupKey, isGroupOpen)}
                  className="w-full flex items-center justify-between px-3 py-2 text-left text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                >
                  <span className="flex items-center gap-2 truncate">
                    {isGroupOpen ? (
                      <FolderOpenIcon fontSize="small" className="text-amber-500" />
                    ) : (
                      <FolderIcon fontSize="small" className="text-amber-500" />
                    )}
                    <span className="truncate">{groupName}</span>
                  </span>
                  <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                    {totalInGroup}
                  </span>
                </button>

                {/* Sub Groups Collapse */}
                <Collapse in={isGroupOpen}>
                  <Box className="pl-3 py-1 space-y-1">
                    {Object.entries(subGroups || {}).map(([subName, docs]) => {
                      const subKey = `s_${groupName}_${subName}`;
                      const hasSearchMatch = Boolean(treeSearch.trim());
                      const isSubOpen = isFolderOpen(subKey, hasSearchMatch);

                      return (
                        <Box key={subKey} className="rounded-lg">
                          <button
                            type="button"
                            onClick={() => toggleFolder(subKey, isSubOpen)}
                            className="w-full flex items-center justify-between px-2.5 py-1.5 text-left text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 rounded-md transition-colors"
                          >
                            <span className="flex items-center gap-1.5 truncate">
                              <ChevronRightIcon
                                fontSize="inherit"
                                className={`text-slate-400 transition-transform ${isSubOpen ? 'rotate-90' : ''}`}
                              />
                              <span className="truncate">{subName}</span>
                            </span>
                            <span className="text-[10px] text-slate-400">
                              {(docs || []).length}
                            </span>
                          </button>

                          {/* Document Leaf Nodes */}
                          <Collapse in={isSubOpen}>
                            <Box className="pl-4 py-0.5 space-y-0.5">
                              {(docs || []).map((docItem) => {
                                const isSelected = String(selectedDocument?.id) === String(docItem.id);
                                return (
                                  <button
                                    key={docItem.id}
                                    type="button"
                                    onClick={() => {
                                      handleOpenDoc(docItem.id);
                                      if (isMobile) setMobileExplorerOpen(false);
                                    }}
                                    className={`w-full flex items-center gap-2 px-2.5 py-1.5 text-left text-xs rounded-lg transition-all ${isSelected
                                        ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-semibold border-l-4 border-indigo-600'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                      }`}
                                  >
                                    <ArticleIcon
                                      fontSize="inherit"
                                      className={isSelected ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'}
                                    />
                                    <span className="truncate flex-1">{docItem.title}</span>
                                    {docItem.announced_at && (
                                      <CampaignIcon fontSize="inherit" className="text-amber-500" titleAccess="Announcement" />
                                    )}
                                  </button>
                                );
                              })}
                            </Box>
                          </Collapse>
                        </Box>
                      );
                    })}
                  </Box>
                </Collapse>
              </Box>
            );
          })
        )}
      </Box>
    </Box>
  );

  return (
    <AsideLayout
      title="Document Library"
      headerActions={
        <Stack direction="row" spacing={1.5} alignItems="center">
          {/* Mobile Explorer Hamburger Button */}
          <Button
            variant="outlined"
            onClick={() => setMobileExplorerOpen(true)}
            startIcon={<MenuIcon />}
            className="lg:!hidden"
            sx={{
              textTransform: 'none',
              borderRadius: 2,
              color: 'text.secondary',
              borderColor: 'divider',
              px: 1.5,
            }}
          >
            Explorer
          </Button>

          <Button
            variant="outlined"
            onClick={() => setSearchModalOpen(true)}
            startIcon={<SearchIcon />}
            sx={{
              textTransform: 'none',
              borderRadius: 2,
              color: 'text.secondary',
              borderColor: 'divider',
              px: 2,
            }}
          >
            Search (Ctrl+K)
          </Button>

          {canCreate && (
            <Button
              variant="contained"
              color="primary"
              startIcon={<AddIcon />}
              component={Link}
              href="/document/library/create"
              sx={{ textTransform: 'none', fontWeight: 600, borderRadius: 2 }}
            >
              New Document
            </Button>
          )}
        </Stack>
      }
    >
      <Head title="Document Library" />

      {/* Mobile Explorer Drawer */}
      <Drawer
        anchor="left"
        open={mobileExplorerOpen}
        onClose={() => setMobileExplorerOpen(false)}
        PaperProps={{
          sx: {
            width: '85vw',
            maxWidth: 360,
            bgcolor: 'background.paper',
            p: 0,
          },
        }}
      >
        {renderExplorerTreeContent(true)}
      </Drawer>

      <Box className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {/* Left Side: Tree Navigation Panel (Sticky on Desktop) */}
        <Box className="hidden lg:flex lg:col-span-4 flex-col gap-4 lg:sticky lg:top-20 self-start max-h-[calc(100vh-6rem)] min-h-0">
          <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col max-h-[calc(100vh-6rem)] h-[calc(100vh-6rem)] min-h-0">
            {renderExplorerTreeContent(false)}
          </Card>
        </Box>

        {/* Right Side: Search, Filters & Document Viewer */}
        <Box className="col-span-12 lg:col-span-8 flex flex-col gap-6">
          {/* Mobile Quick Bar */}
          <Box className="flex lg:hidden items-center justify-between p-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
            <div className="flex items-center gap-2 truncate">
              <FolderIcon fontSize="small" className="text-indigo-600 dark:text-indigo-400" />
              <span className="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                {selectedDocument ? selectedDocument.title : 'Document Explorer'}
              </span>
            </div>
            <Button
              size="small"
              variant="contained"
              color="primary"
              startIcon={<MenuIcon fontSize="small" />}
              onClick={() => setMobileExplorerOpen(true)}
              sx={{ textTransform: 'none', borderRadius: 2, fontWeight: 700, px: 2, whiteSpace: 'nowrap' }}
            >
              Browse Tree
            </Button>
          </Box>

          {/* Command Palette / Quick Search Trigger Bar */}
          <Box className="space-y-3">
            <Box
              onClick={() => setSearchModalOpen(true)}
              className="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs cursor-pointer hover:border-indigo-500 dark:hover:border-indigo-500 hover:shadow-md transition-all group"
            >
              <div className="flex items-center gap-3 text-slate-500 dark:text-slate-400">
                <SearchIcon className="text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" />
                <span className="text-sm font-medium">
                  {search ? `Filtering by: "${search}"` : 'Search documents, policies, SOPs, workflows...'}
                </span>
              </div>

              <div className="flex items-center gap-2">
                <IconButton
                  size="small"
                  onClick={(e) => {
                    e.stopPropagation();
                    setShowFilters(!showFilters);
                  }}
                  color={showFilters || hasActiveFilters ? 'primary' : 'default'}
                  title="Filter Options"
                >
                  <TuneIcon fontSize="small" />
                </IconButton>

                <div className="hidden sm:flex items-center gap-1">
                  <kbd className="px-2 py-0.5 text-[11px] font-bold font-mono rounded-md border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    Ctrl
                  </kbd>
                  <kbd className="px-2 py-0.5 text-[11px] font-bold font-mono rounded-md border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    K
                  </kbd>
                </div>
              </div>
            </Box>

            {/* Collapsible Advanced Filters */}
            <Collapse in={showFilters}>
              <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl p-4 bg-white dark:bg-slate-900 shadow-sm">
                <Box className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                  <TextField
                    select
                    size="small"
                    label="Department"
                    value={department}
                    onChange={(e) => {
                      setDepartment(e.target.value);
                      executeSearch({ department: e.target.value, page: 1 });
                    }}
                  >
                    <MenuItem value="">All Departments</MenuItem>
                    {filterOptions.departments?.map((dept) => (
                      <MenuItem key={dept.id} value={dept.id}>{dept.name}</MenuItem>
                    ))}
                  </TextField>

                  <TextField
                    select
                    size="small"
                    label="Category / Type"
                    value={category}
                    onChange={(e) => {
                      setCategory(e.target.value);
                      executeSearch({ category: e.target.value, page: 1 });
                    }}
                  >
                    <MenuItem value="">All Categories</MenuItem>
                    {filterOptions.categories?.map((type) => (
                      <MenuItem key={type.id} value={type.id}>{type.name}</MenuItem>
                    ))}
                  </TextField>

                  <TextField
                    select
                    size="small"
                    label="Creator"
                    value={creator}
                    onChange={(e) => {
                      setCreator(e.target.value);
                      executeSearch({ creator: e.target.value, page: 1 });
                    }}
                  >
                    <MenuItem value="">All Creators</MenuItem>
                    {filterOptions.creators?.map((c) => (
                      <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>
                    ))}
                  </TextField>

                  <TextField
                    type="number"
                    size="small"
                    label="Min Version"
                    value={version}
                    onChange={(e) => {
                      setVersion(e.target.value);
                      executeSearch({ version: e.target.value, page: 1 });
                    }}
                  />

                  <TextField
                    type="date"
                    size="small"
                    label="Published From"
                    InputLabelProps={{ shrink: true }}
                    value={publishedFrom}
                    onChange={(e) => {
                      setPublishedFrom(e.target.value);
                      executeSearch({ publishedFrom: e.target.value, page: 1 });
                    }}
                  />

                  <TextField
                    type="date"
                    size="small"
                    label="Published To"
                    InputLabelProps={{ shrink: true }}
                    value={publishedTo}
                    onChange={(e) => {
                      setPublishedTo(e.target.value);
                      executeSearch({ publishedTo: e.target.value, page: 1 });
                    }}
                  />
                </Box>

                <Box className="mt-3 flex flex-wrap items-center justify-between gap-2">
                  <FormControlLabel
                    control={
                      <Checkbox
                        size="small"
                        checked={announcementOnly}
                        onChange={(e) => {
                          setAnnouncementOnly(e.target.checked);
                          executeSearch({ announcementOnly: e.target.checked ? 1 : undefined, page: 1 });
                        }}
                      />
                    }
                    label={<Typography variant="body2" className="text-slate-700 dark:text-slate-300">Announcements Only</Typography>}
                  />

                  <Stack direction="row" spacing={1.5} alignItems="center">
                    <TextField
                      select
                      size="small"
                      label="Sort By"
                      value={sort}
                      onChange={(e) => {
                        setSort(e.target.value);
                        executeSearch({ sort: e.target.value, page: 1 });
                      }}
                      sx={{ minWidth: 140 }}
                    >
                      <MenuItem value="relevance">Relevance</MenuItem>
                      <MenuItem value="newest">Newest</MenuItem>
                      <MenuItem value="oldest">Oldest</MenuItem>
                      <MenuItem value="title_asc">Title A-Z</MenuItem>
                      <MenuItem value="title_desc">Title Z-A</MenuItem>
                    </TextField>

                    {hasActiveFilters && (
                      <Button
                        size="small"
                        variant="outlined"
                        color="secondary"
                        onClick={handleClearFilters}
                        sx={{ textTransform: 'none' }}
                      >
                        Reset Filters
                      </Button>
                    )}
                  </Stack>
                </Box>
              </Card>
            </Collapse>
          </Box>

          {/* Selected Document Viewer */}
          {selectedDocument ? (
            <Card elevation={0} className="border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden bg-white dark:bg-slate-900">
              {/* Document Header */}
              <Box className="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
                <Box className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                  <Box className="space-y-1">
                    <Typography variant="h5" className="font-extrabold text-slate-900 dark:text-slate-50">
                      {selectedDocument.title}
                    </Typography>
                    <Box className="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400 pt-1">
                      <Chip
                        label={selectedDocument.department?.name || 'No Dept'}
                        size="small"
                        variant="outlined"
                        className="font-medium"
                      />
                      <Chip
                        label={selectedDocument.type?.name || 'General'}
                        size="small"
                        color="primary"
                        variant="outlined"
                        className="font-medium"
                      />
                      {selectedDocument.announced_at && (
                        <Chip
                          icon={<CampaignIcon fontSize="small" />}
                          label={`Announced: ${selectedDocument.announced_at}`}
                          size="small"
                          color="warning"
                        />
                      )}
                      <span>By <b>{selectedDocument.author?.name || 'Unknown'}</b></span>
                      {selectedDocument.last_editor && (
                        <span>(Last edited by <b>{selectedDocument.last_editor?.name}</b>)</span>
                      )}
                    </Box>
                  </Box>

                  {/* Actions */}
                  {(canUpdate || canDelete) && (
                    <Stack direction="row" spacing={1.5} alignItems="center">
                      {canUpdate && (
                        <Button
                          variant="contained"
                          color="primary"
                          startIcon={<EditIcon />}
                          component={Link}
                          href={`/document/library/${selectedDocument.id}/edit`}
                          sx={{ textTransform: 'none', fontWeight: 600, borderRadius: 2 }}
                        >
                          Edit
                        </Button>
                      )}
                      {canDelete && (
                        <Button
                          variant="outlined"
                          color="error"
                          startIcon={<DeleteIcon />}
                          onClick={() => setDeleteConfirmOpen(true)}
                          sx={{ textTransform: 'none', fontWeight: 600, borderRadius: 2 }}
                        >
                          Delete
                        </Button>
                      )}
                    </Stack>
                  )}
                </Box>

                {/* Tabs: Content vs Revision History */}
                <Box className="mt-4 border-t border-slate-200 dark:border-slate-800 pt-2">
                  <Tabs value={activeTab} onChange={(e, val) => setActiveTab(val)}>
                    <Tab label="Document Content" icon={<ArticleIcon fontSize="small" />} iconPosition="start" sx={{ textTransform: 'none', fontWeight: 600 }} />
                    <Tab
                      label={`Revision History (${selectedDocument.revisions?.length || 0})`}
                      icon={<HistoryIcon fontSize="small" />}
                      iconPosition="start"
                      sx={{ textTransform: 'none', fontWeight: 600 }}
                    />
                  </Tabs>
                </Box>
              </Box>

              {/* Tab 0: Document Content Body */}
              {activeTab === 0 && (
                <CardContent className="p-8">
                  <Box
                    className="prose prose-slate dark:prose-invert max-w-none leading-relaxed text-slate-800 dark:text-slate-200"
                    dangerouslySetInnerHTML={{ __html: selectedDocument.body || '<p class="text-slate-400">Empty document content.</p>' }}
                  />
                </CardContent>
              )}

              {/* Tab 1: Revision History */}
              {activeTab === 1 && (
                <CardContent className="p-6 space-y-3">
                  {selectedDocument.revisions?.length === 0 ? (
                    <Typography variant="body2" className="text-slate-400 py-4 text-center">
                      No revisions recorded yet.
                    </Typography>
                  ) : (
                    selectedDocument.revisions?.map((rev) => (
                      <Paper
                        key={rev.id}
                        elevation={0}
                        className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4"
                      >
                        <Box className="space-y-1">
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
                          sx={{ textTransform: 'none', borderRadius: 2 }}
                        >
                          View Snapshot
                        </Button>
                      </Paper>
                    ))
                  )}
                </CardContent>
              )}
            </Card>
          ) : (
            !search && (
              <Card elevation={0} className="border border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-12 text-center bg-white dark:bg-slate-900">
                <ArticleIcon className="text-slate-300 dark:text-slate-600 mb-3" sx={{ fontSize: 48 }} />
                <Typography variant="h6" className="font-bold text-slate-700 dark:text-slate-300">
                  Select a document to read
                </Typography>
                <Typography variant="body2" className="text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                  Pick any document from the explorer on the left, or press <b>Ctrl+K</b> to quickly search policies, SOPs, and announcements.
                </Typography>
                <Box className="mt-4">
                  <Button
                    variant="outlined"
                    startIcon={<SearchIcon />}
                    onClick={() => setSearchModalOpen(true)}
                    sx={{ textTransform: 'none', borderRadius: 2 }}
                  >
                    Open Quick Search (Ctrl+K)
                  </Button>
                </Box>
              </Card>
            )
          )}
        </Box>
      </Box>

      {/* Global Document Spotlight Search Modal (Ctrl+K) */}
      <DocumentSearchModal
        open={searchModalOpen}
        onClose={() => setSearchModalOpen(false)}
        onSelectDocument={handleOpenDoc}
        filterOptions={filterOptions}
        initialQuery={search}
      />

      {/* Revision Snapshot Modal */}
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
          <Button onClick={() => setSelectedRevision(null)}>Close</Button>
        </DialogActions>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog
        open={deleteConfirmOpen}
        onClose={() => setDeleteConfirmOpen(false)}
        maxWidth="xs"
        fullWidth
      >
        <DialogTitle>Delete Document?</DialogTitle>
        <DialogContent>
          <Typography variant="body2" className="text-slate-600 dark:text-slate-300">
            Are you sure you want to delete <b>{selectedDocument?.title}</b>? This action will remove the document from active listings.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteConfirmOpen(false)}>Cancel</Button>
          <Button variant="contained" color="error" onClick={handleDelete}>
            Confirm Delete
          </Button>
        </DialogActions>
      </Dialog>
    </AsideLayout>
  );
}
