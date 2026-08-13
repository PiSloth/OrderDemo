import React from 'react';
import { Box, Tooltip } from '@mui/material';

export default function InlineCitationNode({ citation, onOpenTodoDetail, onOpenActionDetail }) {
  if (!citation) return null;

  const type = citation.type || citation['data-citation-type'] || 'todo_task';
  const data = citation.data || citation;

  if (type === 'todo_task') {
    const taskName = data.task_name || data.task || data.title || 'Clean Vault Area';
    const status = data.status || 'In Progress';

    return (
      <Tooltip title="Click to view To-Do Task details" arrow>
        <Box
          component="span"
          onClick={(e) => {
            e.stopPropagation();
            if (onOpenTodoDetail) onOpenTodoDetail(data);
          }}
          sx={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: 0.5,
            bgcolor: '#ECFDF5',
            color: '#065F46',
            border: '1px solid #A7F3D0',
            borderRadius: '6px',
            px: 1,
            py: 0.2,
            mx: 0.5,
            fontSize: '0.75rem',
            fontWeight: 700,
            cursor: 'pointer',
            transition: 'all 0.15s ease-in-out',
            verticalAlign: 'middle',
            userSelect: 'none',
            '&:hover': {
              bgcolor: '#D1FAE5',
              borderColor: '#059669',
              boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
            }
          }}
        >
          <span>☑️</span>
          <span>{taskName}</span>
          <span style={{ opacity: 0.85, fontWeight: 600 }}>[{status}]</span>
        </Box>
      </Tooltip>
    );
  }

  if (type === 'promote_action') {
    const actionName = data.action_name || data.title || 'Security Audit';
    const startDate = data.start_date || data.start_at || '2026-08-01';
    const endDate = data.end_date || data.end_at || '2026-08-15';

    return (
      <Tooltip title="Click to view Promote Action details" arrow>
        <Box
          component="span"
          onClick={(e) => {
            e.stopPropagation();
            if (onOpenActionDetail) onOpenActionDetail(data);
          }}
          sx={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: 0.5,
            bgcolor: '#EEF2FF',
            color: '#3730A3',
            border: '1px solid #C7D2FE',
            borderRadius: '6px',
            px: 1,
            py: 0.2,
            mx: 0.5,
            fontSize: '0.75rem',
            fontWeight: 700,
            cursor: 'pointer',
            transition: 'all 0.15s ease-in-out',
            verticalAlign: 'middle',
            userSelect: 'none',
            '&:hover': {
              bgcolor: '#E0E7FF',
              borderColor: '#4F46E5',
              boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
            }
          }}
        >
          <span>📌</span>
          <span>{actionName}</span>
          <span style={{ opacity: 0.85, fontWeight: 600 }}>| {startDate} - {endDate}</span>
        </Box>
      </Tooltip>
    );
  }

  return (
    <Box
      component="span"
      sx={{
        display: 'inline-flex',
        alignItems: 'center',
        bgcolor: '#FEF3C7',
        color: '#92400E',
        border: '1px solid #FCD34D',
        borderRadius: '6px',
        px: 1,
        py: 0.2,
        mx: 0.5,
        fontSize: '0.75rem',
        fontWeight: 700
      }}
    >
      🔗 {data.title || data.text || 'Citation'}
    </Box>
  );
}
