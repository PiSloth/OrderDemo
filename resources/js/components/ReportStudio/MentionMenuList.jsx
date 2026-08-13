import React, { forwardRef, useEffect, useImperativeHandle, useState } from 'react';
import {
  Paper,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Typography,
  Chip,
  Box
} from '@mui/material';
import AssignmentTurnedInIcon from '@mui/icons-material/AssignmentTurnedIn';
import CampaignIcon from '@mui/icons-material/Campaign';

const MentionMenuList = forwardRef(({ items, command }, ref) => {
  const [selectedIndex, setSelectedIndex] = useState(0);

  useEffect(() => {
    setSelectedIndex(0);
  }, [items]);

  const selectItem = (index) => {
    const item = items[index];
    console.log('[MentionMenuList] selectItem index:', index, 'item:', item);
    if (item) {
      command(item);
    }
  };

  const upHandler = () => {
    setSelectedIndex((prev) => (prev <= 0 ? items.length - 1 : prev - 1));
  };

  const downHandler = () => {
    setSelectedIndex((prev) => (prev >= items.length - 1 ? 0 : prev + 1));
  };

  const enterHandler = () => {
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
    return (
      <Paper elevation={8} sx={{ p: 1.5, minWidth: 260, bgcolor: '#FFFFFF', borderRadius: 2 }}>
        <Typography variant="caption" color="text.secondary">
          No matching To-Do tasks or Promote Actions found.
        </Typography>
      </Paper>
    );
  }

  return (
    <Paper
      elevation={8}
      sx={{
        bgcolor: '#FFFFFF',
        border: '1px solid #CBD5E1',
        borderRadius: 2.5,
        overflow: 'hidden',
        minWidth: 280,
        maxWidth: 360,
        boxShadow: '0 12px 24px -4px rgba(15, 23, 42, 0.15)',
        p: 0.5
      }}
    >
      <Box sx={{ px: 1.5, py: 0.75, borderBottom: '1px solid #F1F5F9', bgcolor: '#F8FAFC' }}>
        <Typography variant="caption" sx={{ fontWeight: 800, color: '#475569', letterSpacing: '0.05em', fontSize: '0.68rem' }}>
          MENTION TASK / ACTION LIST (@)
        </Typography>
      </Box>
      <List size="small" disablePadding sx={{ py: 0.5, maxHeight: 260, overflowY: 'auto' }}>
        {items.map((item, index) => {
          const isSelected = index === selectedIndex;
          const isTodo = item.type === 'todo_task';

          return (
            <ListItemButton
              key={item.id || index}
              selected={isSelected}
              onMouseDown={(e) => {
                e.preventDefault();
                e.stopPropagation();
                selectItem(index);
              }}
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                selectItem(index);
              }}
              sx={{
                borderRadius: 1.5,
                mx: 0.5,
                my: 0.25,
                py: 0.75,
                px: 1.25,
                bgcolor: isSelected ? (isTodo ? '#ECFDF5' : '#EEF2FF') : 'transparent',
                borderLeft: isSelected ? `3px solid ${isTodo ? '#059669' : '#4F46E5'}` : '3px solid transparent',
                '&:hover': {
                  bgcolor: isTodo ? '#ECFDF5' : '#EEF2FF'
                }
              }}
            >
              <ListItemIcon sx={{ minWidth: 30, color: isTodo ? '#059669' : '#4F46E5' }}>
                {isTodo ? <AssignmentTurnedInIcon fontSize="small" /> : <CampaignIcon fontSize="small" />}
              </ListItemIcon>
              <ListItemText
                primary={
                  <Typography variant="body2" sx={{ fontWeight: 700, color: '#0F172A', fontSize: '0.82rem', lineHeight: 1.2 }}>
                    {item.title}
                  </Typography>
                }
                secondary={
                  <Box component="span" sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.3 }}>
                    <Chip
                      label={item.subtitle || (isTodo ? 'Task' : 'Action')}
                      size="small"
                      sx={{
                        height: 18,
                        fontSize: '0.65rem',
                        fontWeight: 700,
                        bgcolor: isTodo ? '#D1FAE5' : '#E0E7FF',
                        color: isTodo ? '#065F46' : '#3730A3'
                      }}
                    />
                    {item.category && (
                      <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.68rem' }}>
                        {item.category}
                      </Typography>
                    )}
                  </Box>
                }
              />
            </ListItemButton>
          );
        })}
      </List>
    </Paper>
  );
});

export default MentionMenuList;
