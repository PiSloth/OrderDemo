import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import CreatePromoteActionModal from '../components/CreatePromoteActionModal';

// Material UI Imports
import {
    AppBar,
    Toolbar,
    IconButton,
    Drawer,
    Typography,
    Box,
    Button,
    List,
    ListItem,
    ListItemButton,
    ListItemIcon,
    ListItemText,
    Collapse,
    Avatar,
    Chip
} from '@mui/material';

import {
    Menu as MenuIcon,
    ChevronLeft as ChevronLeftIcon,
    TrendingUp as TrendingUpIcon,
    BarChart as BarChartIcon,
    Assignment as AssignmentIcon,
    FormatListNumbered as ListIcon,
    DesktopWindows as MonitorIcon,
    CalendarMonth as CalendarIcon,
    ExpandLess,
    ExpandMore,
    Add as AddIcon,
    Person as PersonIcon,
    Description as DescriptionIcon,
    Assessment as AssessmentIcon,
    BugReport as BugReportIcon,
    LocalLibrary as LibraryIcon
} from '@mui/icons-material';

const drawerWidth = 280;

export default function AsideLayout({ children, title, headerActions }) {
    const { auth = {}, url = '' } = usePage().props;
    const currentUrl = usePage().url || window.location.pathname;
    const user = auth?.user;

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState({
        performance: false,
        kpi: false,
        todo: false,
        reports: false,
        itIssues: false,
        documents: false,
    });

    // Auto-open group based on current URL path
    useEffect(() => {
        const path = currentUrl;
        const newGroups = { ...openGroups };
        
        if (path.startsWith('/performance')) newGroups.performance = true;
        else if (path.startsWith('/kpi') && !path.startsWith('/kpi/sale-kpi')) newGroups.kpi = true;
        else if (path.startsWith('/todo')) newGroups.todo = true;
        else if (path.startsWith('/reports') || path.startsWith('/taxonomies')) newGroups.reports = true;
        else if (path.startsWith('/operations/it/issues')) newGroups.itIssues = true;
        else if (path.startsWith('/document')) newGroups.documents = true;

        setOpenGroups(newGroups);
    }, [currentUrl]);

    const isCurrentUrl = (href) => {
        return currentUrl === href || currentUrl.startsWith(href + '/');
    };

    const toggleGroup = (groupKey) => {
        setOpenGroups(prev => ({
            ...prev,
            [groupKey]: !prev[groupKey]
        }));
    };

    const handleDrawerToggle = () => {
        setSidebarOpen(!sidebarOpen);
    };

    return (
        <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }} className="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100">
            {/* Top MUI Navigation Bar */}
            <AppBar 
                position="sticky" 
                elevation={0}
                sx={{
                    bgcolor: 'rgba(255, 255, 255, 0.95)',
                    color: 'text.primary',
                    borderBottom: '1px solid',
                    borderColor: 'divider',
                    backdropFilter: 'blur(8px)',
                    zIndex: (theme) => theme.zIndex.drawer + 1,
                    '.dark &': {
                        bgcolor: 'rgba(15, 23, 42, 0.95)',
                        borderColor: 'rgba(255, 255, 255, 0.12)',
                        color: '#fff',
                    }
                }}
            >
                <Toolbar sx={{ justifyContent: 'space-between', gap: 2, flexWrap: { xs: 'wrap', md: 'nowrap' }, py: 0.5 }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                        <IconButton
                            color="inherit"
                            aria-label="open drawer"
                            edge="start"
                            onClick={handleDrawerToggle}
                            sx={{ 
                                border: '1px solid', 
                                borderColor: 'divider', 
                                borderRadius: 2,
                                '.dark &': { borderColor: 'rgba(255,255,255,0.2)' } 
                            }}
                        >
                            <MenuIcon />
                        </IconButton>

                        <Box component="a" href="/order" sx={{ display: 'flex', alignItems: 'center', gap: 1.5, textDecoration: 'none', color: 'inherit' }}>
                            <Box 
                                component="img" 
                                src="/images/logo.png" 
                                alt="STT Logo" 
                                sx={{ width: 32, height: 26, bgcolor: '#fff', borderRadius: 1, p: 0.5, boxShadow: 1 }} 
                            />
                            <Box>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.2 }}>
                                    ShweTatar
                                </Typography>
                                <Typography variant="caption" sx={{ fontSize: '0.65rem', color: 'text.secondary', display: 'block', lineHeight: 1 }}>
                                    Gold & Jewellery
                                </Typography>
                            </Box>
                        </Box>
                    </Box>

                    {/* Header Actions or Title & User Profile */}
                    {headerActions ? (
                        <Box sx={{ width: { xs: '100%', md: 'auto' }, display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: { xs: 'flex-start', md: 'flex-end' }, gap: 1 }}>
                            {headerActions}
                        </Box>
                    ) : (
                        <Box sx={{ display: { xs: 'none', md: 'flex' }, alignItems: 'center', gap: 2 }}>
                            {title && (
                                <Typography variant="caption" sx={{ fontWeight: 700, letterSpacing: 1, textTransform: 'uppercase', color: 'text.secondary' }}>
                                    {title}
                                </Typography>
                            )}
                            {user && (
                                <Chip 
                                    avatar={<Avatar sx={{ width: 24, height: 24 }}><PersonIcon fontSize="small" /></Avatar>} 
                                    label={user.name} 
                                    variant="outlined" 
                                    size="small" 
                                />
                            )}
                        </Box>
                    )}
                </Toolbar>
            </AppBar>

            {/* MUI AppDrawer Navigation */}
            <Drawer
                variant="temporary"
                open={sidebarOpen}
                onClose={handleDrawerToggle}
                ModalProps={{ keepMounted: true }}
                sx={{
                    '& .MuiDrawer-paper': { 
                        width: drawerWidth, 
                        boxSizing: 'border-box',
                        bgcolor: 'background.paper',
                        boxShadow: 24,
                        '.dark &': {
                            bgcolor: '#0f172a',
                            color: '#fff',
                            borderColor: 'rgba(255,255,255,0.1)'
                        }
                    },
                }}
            >
                <Box sx={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
                    {/* Drawer Header */}
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
                        <Box component="a" href="/order" sx={{ display: 'flex', alignItems: 'center', gap: 1.5, textDecoration: 'none', color: 'inherit' }}>
                            <Box 
                                component="img" 
                                src="/images/logo.png" 
                                alt="STT Logo" 
                                sx={{ width: 36, height: 28, bgcolor: '#fff', borderRadius: 1, p: 0.5, boxShadow: 1 }} 
                            />
                            <Box>
                                <Typography variant="subtitle1" sx={{ fontWeight: 800, lineHeight: 1.2 }}>
                                    ShweTatar
                                </Typography>
                                <Typography variant="caption" sx={{ fontSize: '0.7rem', color: 'text.secondary' }}>
                                    Gold & Jewellery
                                </Typography>
                            </Box>
                        </Box>
                        <IconButton onClick={handleDrawerToggle} size="small">
                            <ChevronLeftIcon />
                        </IconButton>
                    </Box>

                    {/* Quick Action Button */}
                    <Box sx={{ p: 2, pb: 1 }}>
                        <Button
                            fullWidth
                            variant="contained"
                            startIcon={<AddIcon />}
                            onClick={() => { window.location.href = '/todo/list?createTask=1'; }}
                            sx={{
                                bgcolor: '#FEF08A',
                                color: '#1e293b',
                                fontWeight: 700,
                                borderRadius: 3,
                                textTransform: 'none',
                                boxShadow: 2,
                                '&:hover': { bgcolor: '#FDE047' }
                            }}
                        >
                            Create Todo Task
                        </Button>
                    </Box>

                    {/* Navigation Items List */}
                    <Box sx={{ flexGrow: 1, overflowY: 'auto', px: 1, py: 1 }}>
                        <List component="nav" size="small" disablePadding>
                            {/* Performance Group */}
                            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
                                <ListItemButton onClick={() => toggleGroup('performance')} sx={{ borderRadius: 2 }}>
                                    <ListItemIcon sx={{ minWidth: 36, color: 'primary.main' }}>
                                        <TrendingUpIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Performance" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                    {openGroups.performance ? <ExpandLess fontSize="small" /> : <ExpandMore fontSize="small" />}
                                </ListItemButton>
                                <Collapse in={openGroups.performance} timeout="auto" unmountOnExit>
                                    <List component="div" disablePadding sx={{ pl: 3 }}>
                                        <ListItemButton 
                                            component="a" 
                                            href="/performance/branch-score" 
                                            selected={isCurrentUrl('/performance/branch-score')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Daily Scores" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/performance/sale-dashboard" 
                                            selected={isCurrentUrl('/performance/sale-dashboard')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Sale" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                    </List>
                                </Collapse>
                            </ListItem>

                            {/* Sale KPI Item */}
                            <ListItem disablePadding sx={{ mb: 0.5 }}>
                                <ListItemButton 
                                    component="a" 
                                    href="/sale-kpi" 
                                    selected={isCurrentUrl('/sale-kpi')}
                                    sx={{ 
                                        borderRadius: 2,
                                        '&.Mui-selected': { bgcolor: '#FEF08A', color: '#0f172a', fontWeight: 'bold', '&:hover': { bgcolor: '#FDE047' } }
                                    }}
                                >
                                    <ListItemIcon sx={{ minWidth: 36, color: 'warning.main' }}>
                                        <BarChartIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Sale KPI" primaryTypographyProps={{ fontWeight: 700, fontSize: '0.875rem' }} />
                                </ListItemButton>
                            </ListItem>

                            {/* KPI Tasks Group */}
                            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
                                <ListItemButton onClick={() => toggleGroup('kpi')} sx={{ borderRadius: 2 }}>
                                    <ListItemIcon sx={{ minWidth: 36, color: 'info.main' }}>
                                        <AssignmentIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="KPI Tasks" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                    {openGroups.kpi ? <ExpandLess fontSize="small" /> : <ExpandMore fontSize="small" />}
                                </ListItemButton>
                                <Collapse in={openGroups.kpi} timeout="auto" unmountOnExit>
                                    <List component="div" disablePadding sx={{ pl: 3 }}>
                                        <ListItemButton 
                                            component="a" 
                                            href="/kpi/dashboard" 
                                            selected={isCurrentUrl('/kpi/dashboard')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Dashboard" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/kpi/tasks" 
                                            selected={isCurrentUrl('/kpi/tasks')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="My Tasks" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/kpi/approvals" 
                                            selected={isCurrentUrl('/kpi/approvals')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Approvals" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                    </List>
                                </Collapse>
                            </ListItem>

                            {/* Todo Lists Group */}
                            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
                                <ListItemButton onClick={() => toggleGroup('todo')} sx={{ borderRadius: 2 }}>
                                    <ListItemIcon sx={{ minWidth: 36, color: 'success.main' }}>
                                        <ListIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Todo Lists" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                    {openGroups.todo ? <ExpandLess fontSize="small" /> : <ExpandMore fontSize="small" />}
                                </ListItemButton>
                                <Collapse in={openGroups.todo} timeout="auto" unmountOnExit>
                                    <List component="div" disablePadding sx={{ pl: 3 }}>
                                        <ListItemButton 
                                            component="a" 
                                            href="/todo/dashboard" 
                                            selected={isCurrentUrl('/todo/dashboard')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Dashboard" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/todo/list" 
                                            selected={isCurrentUrl('/todo/list')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Task List" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                    </List>
                                </Collapse>
                            </ListItem>

                            {/* IT Issue Operations Group */}
                            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
                                <ListItemButton onClick={() => toggleGroup('itIssues')} sx={{ borderRadius: 2 }}>
                                    <ListItemIcon sx={{ minWidth: 36, color: '#3b0764' }}>
                                        <BugReportIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="IT Issue Operations" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                    {openGroups.itIssues ? <ExpandLess fontSize="small" /> : <ExpandMore fontSize="small" />}
                                </ListItemButton>
                                <Collapse in={openGroups.itIssues} timeout="auto" unmountOnExit>
                                    <List component="div" disablePadding sx={{ pl: 3 }}>
                                        <ListItemButton 
                                            component="a" 
                                            href="/operations/it/issues" 
                                            selected={isCurrentUrl('/operations/it/issues') && currentUrl === '/operations/it/issues'}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Issue Tracking List" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 600 }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/operations/it/issues/dashboard" 
                                            selected={isCurrentUrl('/operations/it/issues/dashboard')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="SLA Analytics Board" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                         <ListItemButton 
                                            component="a" 
                                            href="/operations/it/issues/configure" 
                                            selected={isCurrentUrl('/operations/it/issues/configure')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Issue Configuration" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/operations/it/issues/reports" 
                                            selected={isCurrentUrl('/operations/it/issues/reports')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="SLA & Credit Reports" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                    </List>
                                </Collapse>
                            </ListItem>

                            {/* Rich Text Reports Group */}
                            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
                                <ListItemButton onClick={() => toggleGroup('reports')} sx={{ borderRadius: 2 }}>
                                    <ListItemIcon sx={{ minWidth: 36, color: 'primary.main' }}>
                                        <DescriptionIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Rich Text Reports" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                    {openGroups.reports ? <ExpandLess fontSize="small" /> : <ExpandMore fontSize="small" />}
                                </ListItemButton>
                                <Collapse in={openGroups.reports} timeout="auto" unmountOnExit>
                                    <List component="div" disablePadding sx={{ pl: 3 }}>
                                        <ListItemButton 
                                            component="a" 
                                            href="/reports/analytic-board" 
                                            selected={isCurrentUrl('/reports/analytic-board')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemIcon sx={{ minWidth: 28, color: 'primary.main' }}>
                                                <AssessmentIcon fontSize="small" />
                                            </ListItemIcon>
                                            <ListItemText primary="Analytic Report" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 600 }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/reports/create" 
                                            selected={isCurrentUrl('/reports/create')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="New Report Studio" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/taxonomies" 
                                            selected={isCurrentUrl('/taxonomies')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Taxonomy Admin" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                    </List>
                                </Collapse>
                            </ListItem>

                            {/* Document Library Group */}
                            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
                                <ListItemButton onClick={() => toggleGroup('documents')} sx={{ borderRadius: 2 }}>
                                    <ListItemIcon sx={{ minWidth: 36, color: 'info.main' }}>
                                        <LibraryIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Document Library" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                    {openGroups.documents ? <ExpandLess fontSize="small" /> : <ExpandMore fontSize="small" />}
                                </ListItemButton>
                                <Collapse in={openGroups.documents} timeout="auto" unmountOnExit>
                                    <List component="div" disablePadding sx={{ pl: 3 }}>
                                        <ListItemButton 
                                            component="a" 
                                            href="/document/library" 
                                            selected={isCurrentUrl('/document/library')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Browse Documents" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 600 }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/document/library/create" 
                                            selected={isCurrentUrl('/document/library/create')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="New Document" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                        <ListItemButton 
                                            component="a" 
                                            href="/document/email-list" 
                                            selected={isCurrentUrl('/document/email-list')}
                                            sx={{ borderRadius: 2, my: 0.2 }}
                                        >
                                            <ListItemText primary="Email List" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                        </ListItemButton>
                                    </List>
                                </Collapse>
                            </ListItem>

                            {/* Whiteboard Config */}
                            <ListItem disablePadding sx={{ mb: 0.5 }}>
                                <ListItemButton 
                                    component="a" 
                                    href="/whiteboard/config" 
                                    selected={isCurrentUrl('/whiteboard/config')}
                                    sx={{ borderRadius: 2 }}
                                >
                                    <ListItemIcon sx={{ minWidth: 36, color: 'secondary.main' }}>
                                        <MonitorIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Whiteboard Config" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                </ListItemButton>
                            </ListItem>

                            {/* Calendar */}
                            <ListItem disablePadding sx={{ mb: 0.5 }}>
                                <ListItemButton 
                                    component="a" 
                                    href="/calendar/index" 
                                    selected={isCurrentUrl('/calendar/index')}
                                    sx={{ borderRadius: 2 }}
                                >
                                    <ListItemIcon sx={{ minWidth: 36, color: 'error.main' }}>
                                        <CalendarIcon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText primary="Calendar" primaryTypographyProps={{ fontWeight: 600, fontSize: '0.875rem' }} />
                                </ListItemButton>
                            </ListItem>
                        </List>
                    </Box>

                    {/* User Profile Footer */}
                    {user && (
                        <Box sx={{ p: 2, borderTop: '1px solid', borderColor: 'divider', display: 'flex', alignItems: 'center', gap: 1.5 }}>
                            <Avatar sx={{ width: 36, height: 36, bgcolor: 'primary.main', fontWeight: 'bold' }}>
                                {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                            </Avatar>
                            <Box sx={{ minWidth: 0 }}>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {user.name}
                                </Typography>
                                <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {user.email}
                                </Typography>
                            </Box>
                        </Box>
                    )}
                </Box>
            </Drawer>

            {/* Main Content Body */}
            <Box component="main" sx={{ flexGrow: 1, p: { xs: 2, sm: 3 } }}>
                {children}
            </Box>

            <CreatePromoteActionModal />
        </Box>
    );
}
