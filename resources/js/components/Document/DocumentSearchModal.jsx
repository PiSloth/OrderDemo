import React, { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';
import {
  Dialog,
  DialogContent,
  Box,
  Typography,
  TextField,
  InputAdornment,
  IconButton,
  Chip,
  CircularProgress,
  Stack,
  Divider,
  Paper
} from '@mui/material';

import SearchIcon from '@mui/icons-material/Search';
import ClearIcon from '@mui/icons-material/Clear';
import ArticleIcon from '@mui/icons-material/Article';
import HistoryIcon from '@mui/icons-material/History';
import CampaignIcon from '@mui/icons-material/Campaign';
import KeyboardReturnIcon from '@mui/icons-material/KeyboardReturn';
import ArrowDownwardIcon from '@mui/icons-material/ArrowDownward';
import ArrowUpwardIcon from '@mui/icons-material/ArrowUpward';

export default function DocumentSearchModal({
  open = false,
  onClose,
  onSelectDocument,
  filterOptions = { departments: [], categories: [] },
  initialQuery = '',
}) {
  const [query, setQuery] = useState(initialQuery);
  const [department, setDepartment] = useState('');
  const [category, setCategory] = useState('');
  const [announcementOnly, setAnnouncementOnly] = useState(false);
  
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState([]);
  const [selectedIndex, setSelectedIndex] = useState(0);

  const [recentSearches, setRecentSearches] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('document_recent_searches') || '[]');
    } catch {
      return [];
    }
  });

  const searchInputRef = useRef(null);
  const debounceTimerRef = useRef(null);
  const resultsContainerRef = useRef(null);

  const addRecentSearch = (term) => {
    const trimmed = term.trim();
    if (!trimmed) return;
    const updated = [trimmed, ...recentSearches.filter((item) => item !== trimmed)].slice(0, 8);
    setRecentSearches(updated);
    try {
      localStorage.setItem('document_recent_searches', JSON.stringify(updated));
    } catch (e) {
      console.error(e);
    }
  };

  const clearRecentSearches = () => {
    setRecentSearches([]);
    localStorage.removeItem('document_recent_searches');
  };

  const [meta, setMeta] = useState({ total: 0, from: 0, to: 0 });

  // Perform search API call
  const performSearch = useCallback(async (searchQuery, dept, cat, annOnly) => {
    setLoading(true);
    try {
      const response = await axios.get('/document/library/search-api', {
        params: {
          q: searchQuery,
          department: dept || undefined,
          category: cat || undefined,
          announcementOnly: annOnly ? 1 : undefined,
        },
      });

      const items = response.data?.results || [];
      const searchMeta = response.data?.meta || { total: items.length, from: 1, to: items.length };
      setResults(items);
      setMeta(searchMeta);
      setSelectedIndex(0);
    } catch (err) {
      console.error('Search API error:', err);
      setResults([]);
      setMeta({ total: 0, from: 0, to: 0 });
    } finally {
      setLoading(false);
    }
  }, []);

  // Debounced search trigger
  useEffect(() => {
    if (open) {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
      debounceTimerRef.current = setTimeout(() => {
        performSearch(query, department, category, announcementOnly);
      }, 250);
    }
  }, [query, department, category, announcementOnly, open, performSearch]);

  // Focus input when modal opens
  useEffect(() => {
    if (open) {
      setTimeout(() => {
        searchInputRef.current?.focus();
      }, 100);
    }
  }, [open]);

  // Keyboard navigation within the modal
  const handleKeyDown = (e) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSelectedIndex((prev) => (results.length > 0 ? (prev + 1) % results.length : 0));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSelectedIndex((prev) => (results.length > 0 ? (prev - 1 + results.length) % results.length : 0));
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (results.length > 0 && results[selectedIndex]) {
        handleSelect(results[selectedIndex]);
      }
    }
  };

  // Scroll selected item into view
  useEffect(() => {
    if (resultsContainerRef.current) {
      const selectedEl = resultsContainerRef.current.children[selectedIndex];
      if (selectedEl) {
        selectedEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }
  }, [selectedIndex]);

  const handleSelect = (doc) => {
    if (query.trim()) {
      addRecentSearch(query);
    }
    if (onSelectDocument) {
      onSelectDocument(doc.id);
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
          borderRadius: 4,
          overflow: 'hidden',
          bgcolor: 'background.paper',
          backgroundImage: 'none',
          boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.35)',
        },
      }}
    >
      <DialogContent sx={{ p: 0 }} onKeyDown={handleKeyDown}>
        {/* Search Header Input */}
        <Box className="p-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center gap-2">
          <TextField
            inputRef={searchInputRef}
            fullWidth
            variant="standard"
            placeholder="Search policies, SOPs, workflows, content... (Type / or keywords)"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            InputProps={{
              disableUnderline: true,
              startAdornment: (
                <InputAdornment position="start">
                  <SearchIcon className="text-indigo-600 dark:text-indigo-400 mr-1" />
                </InputAdornment>
              ),
              endAdornment: (
                <InputAdornment position="end">
                  {loading && <CircularProgress size={18} className="mr-2 text-indigo-600" />}
                  {query && (
                    <IconButton size="small" onClick={() => setQuery('')}>
                      <ClearIcon fontSize="small" />
                    </IconButton>
                  )}
                  <Chip
                    label="ESC"
                    size="small"
                    onClick={onClose}
                    sx={{
                      fontSize: '0.7rem',
                      fontWeight: 'bold',
                      height: 22,
                      cursor: 'pointer',
                      borderRadius: 1.5,
                      bgcolor: 'action.hover',
                    }}
                  />
                </InputAdornment>
              ),
              sx: { fontSize: '1.1rem', fontWeight: 500 },
            }}
          />
        </Box>

        {/* Quick Filter Chips Bar */}
        <Box className="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/60 flex flex-wrap items-center gap-1.5 overflow-x-auto">
          <Chip
            label="All Documents"
            size="small"
            clickable
            color={!announcementOnly && !department && !category ? 'primary' : 'default'}
            onClick={() => {
              setAnnouncementOnly(false);
              setDepartment('');
              setCategory('');
            }}
            sx={{ fontWeight: 600, fontSize: '0.75rem' }}
          />

          <Chip
            icon={<CampaignIcon fontSize="small" />}
            label="Announcements"
            size="small"
            clickable
            color={announcementOnly ? 'warning' : 'default'}
            variant={announcementOnly ? 'filled' : 'outlined'}
            onClick={() => setAnnouncementOnly(!announcementOnly)}
            sx={{ fontWeight: 600, fontSize: '0.75rem' }}
          />

          <Divider orientation="vertical" flexItem sx={{ mx: 0.5, my: 0.5 }} />

          {/* Department Quick Filter */}
          {filterOptions.departments?.slice(0, 5).map((dept) => {
            const isSelected = String(department) === String(dept.id);
            return (
              <Chip
                key={dept.id}
                label={dept.name}
                size="small"
                clickable
                color={isSelected ? 'primary' : 'default'}
                variant={isSelected ? 'filled' : 'outlined'}
                onClick={() => setDepartment(isSelected ? '' : String(dept.id))}
                sx={{ fontSize: '0.75rem' }}
              />
            );
          })}

          {/* Category Quick Filter */}
          {filterOptions.categories?.slice(0, 4).map((cat) => {
            const isSelected = String(category) === String(cat.id);
            return (
              <Chip
                key={cat.id}
                label={cat.name}
                size="small"
                clickable
                color={isSelected ? 'secondary' : 'default'}
                variant={isSelected ? 'filled' : 'outlined'}
                onClick={() => setCategory(isSelected ? '' : String(cat.id))}
                sx={{ fontSize: '0.75rem' }}
              />
            );
          })}
        </Box>

        {/* Results Counter Bar */}
        {(query || department || category || announcementOnly) && !loading && (
          <Box className="px-4 py-1.5 bg-slate-100/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-600 dark:text-slate-300 font-medium">
            <span>
              {meta.total > 0
                ? `Showing ${meta.from || 1}-${meta.to || results.length} of ${meta.total} matching documents`
                : 'No matching documents found'}
            </span>
          </Box>
        )}

        {/* Results / Suggestions Container */}
        <Box
          ref={resultsContainerRef}
          className="max-h-[55vh] min-h-[220px] overflow-y-auto p-3 space-y-1.5 bg-slate-50/40 dark:bg-slate-950/40"
        >
          {loading && results.length === 0 ? (
            <Box className="py-16 text-center text-slate-400 flex flex-col items-center justify-center gap-2">
              <CircularProgress size={28} />
              <Typography variant="body2">Searching documents...</Typography>
            </Box>
          ) : results.length > 0 ? (
            results.map((item, idx) => {
              const isSelected = idx === selectedIndex;
              return (
                <Paper
                  key={item.id}
                  elevation={0}
                  onClick={() => handleSelect(item)}
                  onMouseEnter={() => setSelectedIndex(idx)}
                  className={`p-3.5 rounded-xl border transition-all cursor-pointer ${
                    isSelected
                      ? 'border-indigo-500 bg-indigo-50/90 dark:bg-indigo-950/70 shadow-sm'
                      : 'border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-slate-300'
                  }`}
                >
                  <Box className="flex items-start justify-between gap-2">
                    <Typography
                      variant="subtitle1"
                      className={`font-bold text-sm ${
                        isSelected ? 'text-indigo-700 dark:text-indigo-300' : 'text-slate-900 dark:text-slate-100'
                      }`}
                      dangerouslySetInnerHTML={{ __html: item.highlighted_title || item.title }}
                    />

                    <Stack direction="row" spacing={1} alignItems="center">
                      {item.is_announcement && (
                        <Chip
                          icon={<CampaignIcon fontSize="small" />}
                          label="Announcement"
                          size="small"
                          color="warning"
                          sx={{ height: 20, fontSize: '0.65rem', fontWeight: 'bold' }}
                        />
                      )}
                      <Chip
                        label={`v${item.version}`}
                        size="small"
                        sx={{ height: 20, fontSize: '0.65rem', fontFamily: 'monospace' }}
                      />
                    </Stack>
                  </Box>

                  <Box className="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                    <span className="font-semibold text-slate-700 dark:text-slate-300">{item.department || 'General'}</span>
                    <span>•</span>
                    <span>{item.category || 'General'}</span>
                    <span>•</span>
                    <span>By {item.creator || 'Admin'}</span>
                    {item.published_at && (
                      <>
                        <span>•</span>
                        <span>{item.published_at}</span>
                      </>
                    )}
                  </Box>

                  {item.snippet && (
                    <Typography
                      variant="body2"
                      className="mt-1.5 text-xs text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed"
                      dangerouslySetInnerHTML={{ __html: item.snippet }}
                    />
                  )}
                </Paper>
              );
            })
          ) : query ? (
            <Box className="py-14 text-center">
              <ArticleIcon className="text-slate-300 dark:text-slate-600 mb-2" sx={{ fontSize: 44 }} />
              <Typography variant="subtitle1" className="font-bold text-slate-700 dark:text-slate-300">
                No matching documents found
              </Typography>
              <Typography variant="caption" className="text-slate-500 dark:text-slate-400 block mt-1">
                Try searching with different keywords or clearing active filters.
              </Typography>
            </Box>
          ) : (
            // Empty search state: show recent searches & help
            <Box className="p-4 space-y-4">
              {recentSearches.length > 0 && (
                <Box>
                  <Box className="flex items-center justify-between mb-2">
                    <Typography variant="caption" className="uppercase font-bold tracking-wider text-slate-400">
                      Recent Searches
                    </Typography>
                    <button
                      type="button"
                      onClick={clearRecentSearches}
                      className="text-xs text-rose-500 hover:underline"
                    >
                      Clear
                    </button>
                  </Box>
                  <Box className="flex flex-wrap gap-1.5">
                    {recentSearches.map((term, i) => (
                      <Chip
                        key={i}
                        icon={<HistoryIcon fontSize="small" />}
                        label={term}
                        size="small"
                        clickable
                        onClick={() => setQuery(term)}
                        sx={{ borderRadius: 2 }}
                      />
                    ))}
                  </Box>
                </Box>
              )}

              <Box className="pt-2">
                <Typography variant="caption" className="uppercase font-bold tracking-wider text-slate-400 block mb-2">
                  Popular Categories
                </Typography>
                <Box className="flex flex-wrap gap-1.5">
                  {filterOptions.categories?.map((cat) => (
                    <Chip
                      key={cat.id}
                      label={cat.name}
                      size="small"
                      clickable
                      onClick={() => {
                        setCategory(String(cat.id));
                        setQuery('');
                      }}
                      sx={{ borderRadius: 2 }}
                    />
                  ))}
                </Box>
              </Box>
            </Box>
          )}
        </Box>

        {/* Modal Footer with Keyboard Shortcuts */}
        <Box className="px-4 py-2.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <Stack direction="row" spacing={2} alignItems="center">
            <span className="flex items-center gap-1">
              <kbd className="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 font-mono text-[10px] font-bold">↑</kbd>
              <kbd className="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 font-mono text-[10px] font-bold">↓</kbd>
              <span>to navigate</span>
            </span>
            <span className="flex items-center gap-1">
              <kbd className="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 font-mono text-[10px] font-bold">↵</kbd>
              <span>to select</span>
            </span>
            <span className="flex items-center gap-1">
              <kbd className="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 font-mono text-[10px] font-bold">ESC</kbd>
              <span>to close</span>
            </span>
          </Stack>

          <span>
            {results.length > 0 ? `${results.length} results` : 'Type to search'}
          </span>
        </Box>
      </DialogContent>
    </Dialog>
  );
}
