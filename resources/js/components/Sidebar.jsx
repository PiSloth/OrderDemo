import React from 'react';
import {
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText
} from '@mui/material';
import AssessmentIcon from '@mui/icons-material/Assessment';
import DescriptionIcon from '@mui/icons-material/Description';
import BarChartIcon from '@mui/icons-material/BarChart';

export const SIDEBAR_NAV_ITEMS = [
  {
    name: 'Analytic Report',
    path: '/reports/analytic-board',
    icon: AssessmentIcon
  },
  {
    name: 'New Report Studio',
    path: '/reports/create',
    icon: DescriptionIcon
  },
  {
    name: 'Taxonomy Admin',
    path: '/taxonomies',
    icon: BarChartIcon
  }
];

export default function Sidebar({ currentUrl = '' }) {
  const isCurrentUrl = (href) => currentUrl === href || currentUrl.startsWith(href + '/');

  return (
    <List component="nav" size="small" disablePadding>
      {SIDEBAR_NAV_ITEMS.map((item) => {
        const IconComponent = item.icon;
        const selected = isCurrentUrl(item.path);

        return (
          <ListItem key={item.path} disablePadding sx={{ mb: 0.5 }}>
            <ListItemButton
              component="a"
              href={item.path}
              selected={selected}
              sx={{
                borderRadius: 2,
                '&.Mui-selected': {
                  bgcolor: '#EFF6FF',
                  color: '#1E40AF',
                  fontWeight: 'bold'
                }
              }}
            >
              <ListItemIcon sx={{ minWidth: 36, color: 'primary.main' }}>
                <IconComponent fontSize="small" />
              </ListItemIcon>
              <ListItemText
                primary={item.name}
                primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }}
              />
            </ListItemButton>
          </ListItem>
        );
      })}
    </List>
  );
}
