import React, { useState, useEffect, forwardRef, useImperativeHandle } from 'react';
import { Paper, List, ListItemButton, ListItemIcon, ListItemText, Typography } from '@mui/material';
import CampaignIcon from '@mui/icons-material/Campaign';
import AssignmentTurnedInIcon from '@mui/icons-material/AssignmentTurnedIn';

const SlashMenuList = forwardRef(({ items, command }, ref) => {
  const [selectedIndex, setSelectedIndex] = useState(0);

  useEffect(() => {
    setSelectedIndex(0);
  }, [items]);

  const selectItem = (index) => {
    const item = items[index];
    console.log('[STEP 1: SlashMenuList] selectItem index:', index, 'item:', item);
    if (item) {
      command(item);
    } else {
      console.warn('[STEP 1: SlashMenuList] No item found at index:', index);
    }
  };

  const upHandler = () => {
    setSelectedIndex((prev) => (prev <= 0 ? items.length - 1 : prev - 1));
  };

  const downHandler = () => {
    setSelectedIndex((prev) => (prev >= items.length - 1 ? 0 : prev + 1));
  };

  const enterHandler = () => {
    console.log('[STEP 1: SlashMenuList] enterHandler selectedIndex:', selectedIndex);
    selectItem(selectedIndex);
  };

  useImperativeHandle(ref, () => ({
    onKeyDown: ({ event }) => {
      if (event.key === 'ArrowUp') {
        upHandler();
        return true;
      }
      if (event.key === 'ArrowDown') {
        downHandler();
        return true;
      }
      if (event.key === 'Enter') {
        enterHandler();
        return true;
      }
      return false;
    }
  }));

  if (!items || items.length === 0) {
    return null;
  }

  return (
    <Paper
      elevation={8}
      sx={{
        bgcolor: '#FFFFEE',
        border: '1px solid #D9BFB7',
        borderRadius: 2,
        overflow: 'hidden',
        minWidth: 220,
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        p: 0.5
      }}
    >
      <Typography variant="caption" sx={{ px: 1.5, py: 0.5, display: 'block', fontWeight: 800, color: '#800000', fontSize: '0.68rem', letterSpacing: '0.05em' }}>
        SLASH COMMANDS
      </Typography>
      <List size="small" disablePadding sx={{ py: 0.5 }}>
        {items.map((item, index) => {
          const isSelected = index === selectedIndex;
          const isPromote = item.id === 'promote_action';

          return (
            <ListItemButton
              key={item.id}
              selected={isSelected}
              onMouseDown={(e) => {
                console.log('[STEP 0: SlashMenuList] onMouseDown triggered for item:', item.id);
                e.preventDefault();
                e.stopPropagation();
                selectItem(index);
              }}
              onClick={(e) => {
                console.log('[STEP 0: SlashMenuList] onClick triggered for item:', item.id);
                e.preventDefault();
                e.stopPropagation();
                selectItem(index);
              }}
              sx={{
                borderRadius: 1,
                mx: 0.5,
                py: 0.75,
                bgcolor: isSelected ? (isPromote ? '#EEF2FF' : '#ECFDF5') : 'transparent',
                borderLeft: isSelected ? `3px solid ${isPromote ? '#4F46E5' : '#059669'}` : '3px solid transparent',
                '&:hover': {
                  bgcolor: isPromote ? '#EEF2FF' : '#ECFDF5'
                }
              }}
            >
              <ListItemIcon sx={{ minWidth: 32, color: isPromote ? '#4F46E5' : '#059669' }}>
                {isPromote ? <CampaignIcon fontSize="small" /> : <AssignmentTurnedInIcon fontSize="small" />}
              </ListItemIcon>
              <ListItemText
                primary={item.title}
                secondary={item.subtitle}
                primaryTypographyProps={{
                  fontSize: '0.8125rem',
                  fontWeight: 700,
                  color: isSelected ? (isPromote ? '#3730A3' : '#065F46') : '#1E293B'
                }}
                secondaryTypographyProps={{
                  fontSize: '0.7rem',
                  color: '#64748B'
                }}
              />
            </ListItemButton>
          );
        })}
      </List>
    </Paper>
  );
});

export default SlashMenuList;
