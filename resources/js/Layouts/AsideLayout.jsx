import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import CreatePromoteActionModal from '../components/CreatePromoteActionModal';
import ItSatisfactionModal from '../components/IT/ItSatisfactionModal';

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

// Untitled UI / Lucide Outline Icons (Exact matching shapes with Blade icons)
import {
    Menu,
    X,
    PanelLeftClose,
    PanelLeftOpen,
    Search,
    TrendingUp,
    ClipboardList,
    LayoutGrid,
    BarChart3,
    CheckSquare,
    ListTodo,
    Database,
    Bug,
    PieChart,
    Folder,
    Monitor,
    GraduationCap,
    ShieldCheck,
    Laptop,
    Sparkles,
    Calendar,
    BookOpen,
    HelpCircle,
    Settings,
    ChevronDown,
    Plus,
    User as UserIcon
} from 'lucide-react';

const drawerWidth = 260;

export default function AsideLayout({ 
    children, 
    title, 
    headerActions, 
    hideTopNav = false,
    noPadding = false,
    sidebarOpen: externalSidebarOpen,
    onToggleSidebar: externalOnToggleSidebar 
}) {
    const { auth = {} } = usePage().props;
    const currentUrl = usePage().url || window.location.pathname;
    const user = auth?.user;
    const can = auth?.can || {};

    const [internalSidebarOpen, setInternalSidebarOpen] = useState(false);
    const isControlled = externalSidebarOpen !== undefined;
    const sidebarOpen = isControlled ? externalSidebarOpen : internalSidebarOpen;

    // Listen to global open / toggle events so any module can trigger main aside
    useEffect(() => {
        const handleOpenAside = () => {
            if (isControlled && externalOnToggleSidebar) {
                externalOnToggleSidebar(true);
            } else {
                setInternalSidebarOpen(true);
            }
        };
        const handleToggleAside = () => {
            if (isControlled && externalOnToggleSidebar) {
                externalOnToggleSidebar(!sidebarOpen);
            } else {
                setInternalSidebarOpen(prev => !prev);
            }
        };

        window.addEventListener('open-main-aside', handleOpenAside);
        window.addEventListener('toggle-main-aside', handleToggleAside);
        return () => {
            window.removeEventListener('open-main-aside', handleOpenAside);
            window.removeEventListener('toggle-main-aside', handleToggleAside);
        };
    }, [isControlled, externalOnToggleSidebar, sidebarOpen]);
    const [searchQuery, setSearchQuery] = useState('');
    const [openGroups, setOpenGroups] = useState({
        performance: false,
        order: false,
        psi: false,
        kpi: false,
        todo: false,
        operation: false,
        reports: false,
        documents: false,
        whiteboard: false,
        training: false,
        digitalAssets: false,
        officeAsset: false,
        jewelry: false,
        calendar: false,
    });

    // Auto-open group based on current URL path
    useEffect(() => {
        const path = currentUrl;
        const newGroups = { ...openGroups };
        
        if (path.startsWith('/performance')) newGroups.performance = true;
        else if (path.startsWith('/order')) newGroups.order = true;
        else if (path.startsWith('/psi')) newGroups.psi = true;
        else if (path.startsWith('/kpi') && !path.startsWith('/kpi/sale-kpi') && !path.startsWith('/sale-kpi')) newGroups.kpi = true;
        else if (path.startsWith('/todo') || path.startsWith('/operations/daily-notes')) newGroups.todo = true;
        else if ((path.startsWith('/operations') || path.startsWith('/operation')) && !path.startsWith('/operations/daily-notes')) newGroups.operation = true;
        else if (path.startsWith('/reports') || path.startsWith('/taxonomies')) newGroups.reports = true;
        else if (path.startsWith('/document')) newGroups.documents = true;
        else if (path.startsWith('/whiteboard')) newGroups.whiteboard = true;
        else if (path.startsWith('/training')) newGroups.training = true;
        else if (path.startsWith('/digital-assets')) newGroups.digitalAssets = true;
        else if (path.startsWith('/office-asset')) newGroups.officeAsset = true;
        else if (path.startsWith('/jewelry')) newGroups.jewelry = true;
        else if (path.startsWith('/calendar')) newGroups.calendar = true;

        setOpenGroups(newGroups);
    }, [currentUrl]);

    const isCurrentUrl = (href) => {
        if (!href) return false;
        return currentUrl === href || currentUrl.startsWith(href + '/') || currentUrl.startsWith(href + '?');
    };

    const toggleGroup = (groupKey) => {
        setOpenGroups(prev => ({
            ...prev,
            [groupKey]: !prev[groupKey]
        }));
    };

    const handleDrawerToggle = () => {
        if (isControlled && externalOnToggleSidebar) {
            externalOnToggleSidebar(!sidebarOpen);
        } else {
            setInternalSidebarOpen(!sidebarOpen);
        }
    };

    const handleOpenSidebar = () => {
        if (!sidebarOpen) {
            if (isControlled && externalOnToggleSidebar) {
                externalOnToggleSidebar(true);
            } else {
                setInternalSidebarOpen(true);
            }
        }
    };

    // Filter helper for search bar
    const matchesSearch = (text) => {
        if (!searchQuery.trim()) return true;
        return text.toLowerCase().includes(searchQuery.toLowerCase());
    };

    // Shared item styles matching Untitled UI
    const navItemButtonSx = (active = false) => ({
        borderRadius: 2,
        px: 1.5,
        py: 0.9,
        my: 0.25,
        color: active ? '#0f172a' : '#475569',
        bgcolor: active ? '#f1f5f9' : 'transparent',
        fontWeight: active ? 600 : 500,
        transition: 'background-color 0.15s ease-in-out, color 0.15s ease-in-out',
        '&:hover': {
            bgcolor: active ? '#f1f5f9' : '#f8fafc',
            color: '#0f172a',
            '& .nav-icon': {
                color: '#0f172a'
            }
        },
        '.dark &': {
            color: active ? '#f8fafc' : '#94a3b8',
            bgcolor: active ? 'rgba(255, 255, 255, 0.08)' : 'transparent',
            '&:hover': {
                bgcolor: active ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.04)',
                color: '#f8fafc',
                '& .nav-icon': {
                    color: '#f8fafc'
                }
            }
        }
    });

    const subItemButtonSx = (active = false) => ({
        borderRadius: 1.5,
        py: 0.65,
        px: 1.5,
        my: 0.2,
        color: active ? '#0f172a' : '#64748b',
        bgcolor: active ? '#f1f5f9' : 'transparent',
        fontWeight: active ? 600 : 400,
        fontSize: '0.825rem',
        transition: 'background-color 0.15s ease-in-out, color 0.15s ease-in-out',
        '&:hover': {
            bgcolor: active ? '#f1f5f9' : '#f8fafc',
            color: '#0f172a',
        },
        '.dark &': {
            color: active ? '#f8fafc' : '#94a3b8',
            bgcolor: active ? 'rgba(255, 255, 255, 0.08)' : 'transparent',
            '&:hover': {
                bgcolor: active ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.04)',
                color: '#f8fafc',
            }
        }
    });

    return (
        <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }} className="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans">
            {/* Top Navigation Bar */}
            {!hideTopNav && (
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
                            borderColor: 'rgba(255, 255, 255, 0.08)',
                            color: '#fff',
                        }
                    }}
                >
                    <Toolbar sx={{ justifyContent: 'space-between', gap: 2, flexWrap: { xs: 'wrap', md: 'nowrap' }, py: 0.5 }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                            <IconButton
                                color="inherit"
                                aria-label={sidebarOpen ? 'collapse sidebar' : 'expand sidebar'}
                                edge="start"
                                onClick={handleDrawerToggle}
                                sx={{ 
                                    border: '1px solid', 
                                    borderColor: sidebarOpen ? 'primary.main' : 'divider', 
                                    bgcolor: sidebarOpen ? 'action.selected' : 'transparent',
                                    borderRadius: 2,
                                    p: 1,
                                    transition: 'all 0.15s ease-in-out',
                                    '.dark &': { 
                                        borderColor: sidebarOpen ? 'primary.light' : 'rgba(255,255,255,0.15)',
                                        bgcolor: sidebarOpen ? 'rgba(255,255,255,0.08)' : 'transparent',
                                    } 
                                }}
                                title={sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
                            >
                                {sidebarOpen ? (
                                    <PanelLeftClose size={18} className="text-indigo-600 dark:text-indigo-400" />
                                ) : (
                                    <Menu size={18} />
                                )}
                            </IconButton>

                            <Box component="a" href="/order/dashboard" sx={{ display: 'flex', alignItems: 'center', gap: 1.5, textDecoration: 'none', color: 'inherit' }}>
                                <Box 
                                    component="img" 
                                    src="/images/logo.png" 
                                    alt="STT Logo" 
                                    sx={{ width: 32, height: 26, bgcolor: '#fff', borderRadius: 1.5, p: 0.5, border: '1px solid', borderColor: 'divider' }} 
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
                                    <Typography variant="caption" sx={{ fontWeight: 700, letterSpacing: 0.5, textTransform: 'uppercase', color: 'text.secondary' }}>
                                        {title}
                                    </Typography>
                                )}
                                {user && (
                                    <Chip 
                                        avatar={<Avatar sx={{ width: 24, height: 24 }}><UserIcon size={14} /></Avatar>} 
                                        label={user.name} 
                                        variant="outlined" 
                                        size="small" 
                                    />
                                )}
                            </Box>
                        )}
                    </Toolbar>
                </AppBar>
            )}

            {/* Left Margin Sticky Floating Sidebar Trigger (Obsidian & Gold Luxury Tab) */}
            {hideTopNav && (
                <button
                    type="button"
                    onClick={handleDrawerToggle}
                    onMouseEnter={handleOpenSidebar}
                    className={`fixed left-0 top-3 sm:top-3.5 z-40 group flex items-center gap-2 bg-slate-900/95 dark:bg-slate-950/95 backdrop-blur-md border border-l-0 border-amber-500/40 dark:border-amber-400/50 shadow-xl shadow-slate-900/30 rounded-r-2xl py-2 px-3 text-white transition-all duration-300 ease-in-out hover:bg-slate-800 hover:translate-x-1 hover:shadow-2xl hover:border-amber-400 cursor-pointer ${
                        sidebarOpen ? 'opacity-0 pointer-events-none -translate-x-4' : 'opacity-100 pointer-events-auto translate-x-0'
                    }`}
                    title="Open Navigation Sidebar"
                    aria-label="Open Navigation Sidebar"
                >
                    {/* Glowing Gold Accent Line */}
                    <span className="w-1 h-4 rounded-full bg-gradient-to-b from-amber-300 via-amber-400 to-yellow-500 shadow-[0_0_8px_rgba(251,191,36,0.7)]" />

                    {/* Gold Panel Icon */}
                    <PanelLeftOpen size={18} className="text-amber-400 group-hover:scale-110 group-hover:text-amber-300 transition-all duration-200" />

                    {/* Distinct Crisp Typography */}
                    <span className="text-xs font-bold tracking-wider uppercase text-amber-100/90 group-hover:text-white transition-colors">
                        Menu
                    </span>

                    {/* Subtle micro jewel dot */}
                    <span className="w-1.5 h-1.5 rounded-full bg-amber-400/80 group-hover:bg-amber-300 shadow-[0_0_6px_rgba(251,191,36,0.9)] animate-pulse hidden sm:inline-block ml-0.5" />
                </button>
            )}

            {/* Untitled UI-Styled AppDrawer Navigation */}
            <Drawer
                variant="temporary"
                open={sidebarOpen}
                onClose={handleDrawerToggle}
                ModalProps={{ keepMounted: true }}
                sx={{
                    zIndex: (theme) => theme.zIndex.drawer + 5,
                    '& .MuiBackdrop-root': {
                        top: 0,
                    },
                    '& .MuiDrawer-paper': { 
                        top: 0,
                        height: '100%',
                        width: drawerWidth, 
                        boxSizing: 'border-box',
                        bgcolor: '#ffffff',
                        borderRight: '1px solid',
                        borderColor: 'divider',
                        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.08)',
                        overflow: 'hidden',
                        contain: 'paint layout',
                        isolation: 'isolate',
                        backfaceVisibility: 'hidden',
                        WebkitBackfaceVisibility: 'hidden',
                        willChange: 'transform',
                        transformStyle: 'flat',
                        '.dark &': {
                            bgcolor: '#0f172a',
                            color: '#fff',
                            borderColor: 'rgba(255,255,255,0.08)'
                        }
                    },
                }}
            >
                <Box 
                    sx={{ 
                        display: 'flex', 
                        flexDirection: 'column', 
                        height: '100%', 
                        overflow: 'hidden', 
                        contain: 'paint',
                        opacity: sidebarOpen ? 1 : 0,
                        transition: sidebarOpen 
                            ? 'opacity 180ms ease-in' 
                            : 'opacity 80ms cubic-bezier(0.4, 0, 1, 1)',
                        willChange: 'opacity',
                    }}
                >
                    {/* Top Header with Brand & Close Button - Adding Generous Top Space for Aside Bar */}
                    <Box sx={{ 
                        pt: 3.5, 
                        pb: 2, 
                        px: 2, 
                        display: 'flex', 
                        alignItems: 'center', 
                        justifyContent: 'space-between',
                        borderBottom: '1px solid',
                        borderColor: 'divider',
                        bgcolor: '#ffffff',
                        '.dark &': { bgcolor: '#0f172a' }
                    }}>
                        <Box component="a" href="/order/dashboard" sx={{ display: 'flex', alignItems: 'center', gap: 1.5, textDecoration: 'none', color: 'inherit' }}>
                            <Box 
                                component="img" 
                                src="/images/logo.png" 
                                alt="STT Logo" 
                                sx={{ width: 32, height: 26, bgcolor: '#fff', borderRadius: 1.5, p: 0.5, border: '1px solid', borderColor: 'divider' }} 
                            />
                            <Box>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.2, color: 'text.primary' }}>
                                    ShweTatar
                                </Typography>
                                <Typography variant="caption" sx={{ fontSize: '0.65rem', color: 'text.secondary', display: 'block', lineHeight: 1 }}>
                                    Gold & Jewellery
                                </Typography>
                            </Box>
                        </Box>

                        <IconButton
                            onClick={handleDrawerToggle}
                            disableRipple
                            size="small"
                            sx={{ 
                                border: '1px solid', 
                                borderColor: 'divider', 
                                borderRadius: 2, 
                                p: 0.8,
                                color: 'text.secondary',
                                transition: 'background-color 0.15s ease-in-out, border-color 0.15s ease-in-out',
                                '&:hover': { 
                                    bgcolor: 'action.hover',
                                    color: 'primary.main',
                                    borderColor: 'primary.main'
                                },
                                '&:focus': { outline: 'none' }
                            }}
                            title="Collapse sidebar"
                        >
                            <PanelLeftClose size={18} className="text-indigo-600 dark:text-indigo-400" />
                        </IconButton>
                    </Box>

                    {/* Search Bar matching Untitled UI (Pic 2) */}
                    <Box sx={{ px: 2, pt: 2, pb: 1 }}>
                        <Box 
                            sx={{
                                display: 'flex',
                                alignItems: 'center',
                                height: 38,
                                px: 1.25,
                                borderRadius: '8px',
                                border: '1px solid',
                                borderColor: '#D0D5DD',
                                bgcolor: '#ffffff',
                                gap: 1,
                                boxShadow: '0px 1px 2px rgba(16, 24, 40, 0.05)',
                                transition: 'all 0.15s ease-in-out',
                                '&:focus-within': {
                                    borderColor: '#7F56D9',
                                    boxShadow: '0 0 0 3px rgba(127, 86, 217, 0.2)'
                                },
                                '.dark &': {
                                    bgcolor: 'rgba(255, 255, 255, 0.04)',
                                    borderColor: 'rgba(255, 255, 255, 0.12)',
                                    '&:focus-within': {
                                        borderColor: '#9E77ED',
                                        boxShadow: '0 0 0 3px rgba(158, 119, 237, 0.25)'
                                    }
                                }
                            }}
                        >
                            <Search size={16} strokeWidth={2} style={{ color: '#667085', flexShrink: 0 }} />
                            <input 
                                type="text"
                                placeholder="Search"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full bg-transparent border-0 p-0 text-sm focus:ring-0 focus:outline-none focus:border-0 shadow-none text-slate-800 dark:text-slate-100 placeholder:text-slate-400"
                                style={{
                                    border: 'none',
                                    outline: 'none',
                                    boxShadow: 'none',
                                    padding: 0,
                                    width: '100%',
                                    fontSize: '0.875rem',
                                    lineHeight: '20px',
                                    background: 'transparent'
                                }}
                            />
                            <Box 
                                component="kbd" 
                                sx={{
                                    fontSize: '0.7rem',
                                    fontWeight: 500,
                                    fontFamily: 'system-ui, -apple-system, sans-serif',
                                    px: 0.75,
                                    py: 0.2,
                                    borderRadius: '4px',
                                    border: '1px solid',
                                    borderColor: '#D0D5DD',
                                    color: '#667085',
                                    bgcolor: '#F9FAFB',
                                    lineHeight: 1.2,
                                    flexShrink: 0,
                                    boxShadow: '0 1px 1px rgba(0,0,0,0.05)',
                                    '.dark &': {
                                        bgcolor: 'rgba(255,255,255,0.08)',
                                        borderColor: 'rgba(255,255,255,0.15)',
                                        color: '#94a3b8'
                                    }
                                }}
                            >
                                ⌘K
                            </Box>
                        </Box>
                    </Box>

                    {/* Quick Action Button - Sleek Untitled UI Design */}
                    <Box sx={{ px: 2, py: 1 }}>
                        <Button
                            fullWidth
                            variant="contained"
                            startIcon={<Plus size={16} />}
                            onClick={() => { window.location.href = '/todo/list?createTask=1'; }}
                            sx={{
                                bgcolor: '#0f172a',
                                color: '#ffffff',
                                fontWeight: 600,
                                fontSize: '0.85rem',
                                borderRadius: 2,
                                textTransform: 'none',
                                py: 0.9,
                                boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                                '&:hover': { bgcolor: '#1e293b' },
                                '.dark &': {
                                    bgcolor: '#f8fafc',
                                    color: '#0f172a',
                                    '&:hover': { bgcolor: '#e2e8f0' }
                                }
                            }}
                        >
                            Create Todo Task
                        </Button>
                    </Box>

                    {/* Synchronized Navigation Items List */}
                    <Box 
                        className="aside-slim-scroll"
                        sx={{ 
                            flexGrow: 1, 
                            overflowY: 'auto', 
                            overflowX: 'hidden',
                            contain: 'paint',
                            isolation: 'isolate',
                            px: 1.5, 
                            py: 1,
                            '&::-webkit-scrollbar': {
                                width: '2.5px',
                            },
                            '&::-webkit-scrollbar-track': {
                                background: 'transparent',
                            },
                            '&::-webkit-scrollbar-thumb': {
                                backgroundColor: '#334155',
                                borderRadius: 0,
                            },
                            '&::-webkit-scrollbar-thumb:hover': {
                                backgroundColor: '#0f172a',
                            },
                            scrollbarWidth: 'thin',
                            scrollbarColor: '#475569 transparent',
                        }}
                    >
                        <List component="nav" size="small" disablePadding>

                            {/* 1. Performance Group */}
                            {matchesSearch('performance daily scores sale') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('performance')} 
                                        sx={navItemButtonSx(isCurrentUrl('/performance'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <TrendingUp size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Performance" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.performance ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.performance} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/performance/branch-score" 
                                                selected={isCurrentUrl('/performance/branch-score')}
                                                sx={subItemButtonSx(isCurrentUrl('/performance/branch-score'))}
                                            >
                                                <ListItemText primary="Daily Scores" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/performance/sale-dashboard" 
                                                selected={isCurrentUrl('/performance/sale-dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/performance/sale-dashboard'))}
                                            >
                                                <ListItemText primary="Sale" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 2. Order Group */}
                            {matchesSearch('order add summary dashboard export meeting comments supplier') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('order')} 
                                        sx={navItemButtonSx(isCurrentUrl('/order'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <ClipboardList size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Order" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.order ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.order} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/add-order" 
                                                selected={isCurrentUrl('/order/add-order')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/add-order'))}
                                            >
                                                <ListItemText primary="Add Order" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/dashboard" 
                                                selected={isCurrentUrl('/order/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/dashboard'))}
                                            >
                                                <ListItemText primary="Summary" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/order/dashboard" 
                                                selected={isCurrentUrl('/order/order/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/order/dashboard'))}
                                            >
                                                <ListItemText primary="Dashboard" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/export" 
                                                selected={isCurrentUrl('/order/export')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/export'))}
                                            >
                                                <ListItemText primary="Export" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/chats" 
                                                selected={isCurrentUrl('/order/chats')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/chats'))}
                                            >
                                                <ListItemText primary="i-Meeting" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/comment/history" 
                                                selected={isCurrentUrl('/order/comment/history')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/comment/history'))}
                                            >
                                                <ListItemText primary="Comments" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/order/addsupplier" 
                                                selected={isCurrentUrl('/order/addsupplier')}
                                                sx={subItemButtonSx(isCurrentUrl('/order/addsupplier'))}
                                            >
                                                <ListItemText primary="Supplier Config" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 3. PSI Group */}
                            {matchesSearch('psi mainboard oos daily sale report') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('psi')} 
                                        sx={navItemButtonSx(isCurrentUrl('/psi'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <LayoutGrid size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="PSI" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.psi ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.psi} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/psi/mainboard" 
                                                selected={isCurrentUrl('/psi/mainboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/psi/mainboard'))}
                                            >
                                                <ListItemText primary="Mainboard" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/psi/oos" 
                                                selected={isCurrentUrl('/psi/oos')}
                                                sx={subItemButtonSx(isCurrentUrl('/psi/oos'))}
                                            >
                                                <ListItemText primary="OoS" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/psi/product/daily-sale" 
                                                selected={isCurrentUrl('/psi/product/daily-sale')}
                                                sx={subItemButtonSx(isCurrentUrl('/psi/product/daily-sale'))}
                                            >
                                                <ListItemText primary="Daily Sale" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/psi/report" 
                                                selected={isCurrentUrl('/psi/report')}
                                                sx={subItemButtonSx(isCurrentUrl('/psi/report'))}
                                            >
                                                <ListItemText primary="PSI Report" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 4. Sale KPI Item (Standalone Link) */}
                            {matchesSearch('sale kpi') && (
                                <ListItem disablePadding>
                                    <ListItemButton 
                                        component="a" 
                                        href="/sale-kpi" 
                                        selected={isCurrentUrl('/sale-kpi')}
                                        sx={navItemButtonSx(isCurrentUrl('/sale-kpi'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <BarChart3 size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Sale KPI" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                    </ListItemButton>
                                </ListItem>
                            )}

                            {/* 5. KPI Tasks Group */}
                            {matchesSearch('kpi tasks dashboard approvals') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('kpi')} 
                                        sx={navItemButtonSx(isCurrentUrl('/kpi') && !isCurrentUrl('/sale-kpi'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <CheckSquare size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="KPI Tasks" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <Box 
                                            component="span" 
                                            sx={{
                                                fontSize: '0.7rem',
                                                fontWeight: 600,
                                                px: 1,
                                                py: 0.2,
                                                mr: 1,
                                                borderRadius: 5,
                                                bgcolor: 'action.hover',
                                                border: '1px solid',
                                                borderColor: 'divider',
                                                color: 'text.secondary'
                                            }}
                                        >
                                            10
                                        </Box>
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.kpi ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.kpi} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/kpi/dashboard" 
                                                selected={isCurrentUrl('/kpi/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/kpi/dashboard'))}
                                            >
                                                <ListItemText primary="Dashboard" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/kpi/tasks" 
                                                selected={isCurrentUrl('/kpi/tasks')}
                                                sx={subItemButtonSx(isCurrentUrl('/kpi/tasks'))}
                                            >
                                                <ListItemText primary="My Tasks" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/kpi/approvals" 
                                                selected={isCurrentUrl('/kpi/approvals')}
                                                sx={subItemButtonSx(isCurrentUrl('/kpi/approvals'))}
                                            >
                                                <ListItemText primary="Approvals" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 6. Todo Group */}
                            {matchesSearch('todo task list dashboard daily notes') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('todo')} 
                                        sx={navItemButtonSx(isCurrentUrl('/todo') || isCurrentUrl('/operations/daily-notes'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <ListTodo size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Todo" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.todo ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.todo} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                             <ListItemButton 
                                                component="a" 
                                                href="/todo/dashboard" 
                                                selected={isCurrentUrl('/todo/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/todo/dashboard'))}
                                            >
                                                <ListItemText primary="Dashboard" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/todo/list" 
                                                selected={isCurrentUrl('/todo/list')}
                                                sx={subItemButtonSx(isCurrentUrl('/todo/list'))}
                                            >
                                                <ListItemText primary="Task List" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/operations/daily-notes" 
                                                selected={isCurrentUrl('/operations/daily-notes')}
                                                sx={subItemButtonSx(isCurrentUrl('/operations/daily-notes'))}
                                            >
                                                <ListItemText primary="Daily Notes" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 7. ERP Issue Group */}
                            {matchesSearch('erp issue sla analytics tickets configuration satisfaction') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('operation')} 
                                        sx={navItemButtonSx((isCurrentUrl('/operations') || isCurrentUrl('/operation')) && !isCurrentUrl('/operations/daily-notes'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <Database size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="ERP Issue" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.operation ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.operation} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/operations/it/issues" 
                                                selected={isCurrentUrl('/operations/it/issues') && currentUrl === '/operations/it/issues'}
                                                sx={subItemButtonSx(isCurrentUrl('/operations/it/issues') && currentUrl === '/operations/it/issues')}
                                            >
                                                <ListItemText primary="Issue Tracking List" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/operations/it/issues/dashboard" 
                                                selected={isCurrentUrl('/operations/it/issues/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/operations/it/issues/dashboard'))}
                                            >
                                                <ListItemText primary="SLA Analytics Board" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/operations/it/issues/create" 
                                                selected={isCurrentUrl('/operations/it/issues/create')}
                                                sx={subItemButtonSx(isCurrentUrl('/operations/it/issues/create'))}
                                            >
                                                <ListItemText primary="New Ticket Studio" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/operations/it/issues/configure" 
                                                selected={isCurrentUrl('/operations/it/issues/configure')}
                                                sx={subItemButtonSx(isCurrentUrl('/operations/it/issues/configure'))}
                                            >
                                                <ListItemText primary="Issue Configuration" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/operations/it/issues/reports" 
                                                selected={isCurrentUrl('/operations/it/issues/reports')}
                                                sx={subItemButtonSx(isCurrentUrl('/operations/it/issues/reports'))}
                                            >
                                                <ListItemText primary="SLA & Credit Reports" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            {can.itSatisfactionView && (
                                                <ListItemButton 
                                                    component="a" 
                                                    href="/operations/it/satisfaction" 
                                                    selected={isCurrentUrl('/operations/it/satisfaction')}
                                                    sx={subItemButtonSx(isCurrentUrl('/operations/it/satisfaction'))}
                                                >
                                                    <ListItemText primary="IT Satisfaction Surveys" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 600 }} />
                                                </ListItemButton>
                                            )}
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 8. Rich Text Reports Group */}
                            {matchesSearch('rich text reports analytic report studio taxonomy') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('reports')} 
                                        sx={navItemButtonSx(isCurrentUrl('/reports') || isCurrentUrl('/taxonomies'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <PieChart size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Rich Text Reports" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.reports ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.reports} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/reports/analytic-board" 
                                                selected={isCurrentUrl('/reports/analytic-board')}
                                                sx={subItemButtonSx(isCurrentUrl('/reports/analytic-board'))}
                                            >
                                                <ListItemText primary="Analytic Report" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/reports/create" 
                                                selected={isCurrentUrl('/reports/create')}
                                                sx={subItemButtonSx(isCurrentUrl('/reports/create'))}
                                            >
                                                <ListItemText primary="New Report Studio" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/taxonomies" 
                                                selected={isCurrentUrl('/taxonomies')}
                                                sx={subItemButtonSx(isCurrentUrl('/taxonomies'))}
                                            >
                                                <ListItemText primary="Taxonomy Admin" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 9. Document Library Group */}
                            {matchesSearch('document library browse documents new email list') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('documents')} 
                                        sx={navItemButtonSx(isCurrentUrl('/document'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <Folder size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Document Library" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.documents ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.documents} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/document/library" 
                                                selected={isCurrentUrl('/document/library')}
                                                sx={subItemButtonSx(isCurrentUrl('/document/library'))}
                                            >
                                                <ListItemText primary="Browse Documents" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/document/library/create" 
                                                selected={isCurrentUrl('/document/library/create')}
                                                sx={subItemButtonSx(isCurrentUrl('/document/library/create'))}
                                            >
                                                <ListItemText primary="New Document" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/document/email-list" 
                                                selected={isCurrentUrl('/document/email-list')}
                                                sx={subItemButtonSx(isCurrentUrl('/document/email-list'))}
                                            >
                                                <ListItemText primary="Email List" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 10. Whiteboard Group */}
                            {matchesSearch('whiteboard board configuration') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('whiteboard')} 
                                        sx={navItemButtonSx(isCurrentUrl('/whiteboard'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <Monitor size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Whiteboard" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.whiteboard ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.whiteboard} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/whiteboard/board" 
                                                selected={isCurrentUrl('/whiteboard/board')}
                                                sx={subItemButtonSx(isCurrentUrl('/whiteboard/board'))}
                                            >
                                                <ListItemText primary="Board" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/whiteboard/config" 
                                                selected={isCurrentUrl('/whiteboard/config')}
                                                sx={subItemButtonSx(isCurrentUrl('/whiteboard/config'))}
                                            >
                                                <ListItemText primary="Configuration" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 11. Training & Compliance Group */}
                            {matchesSearch('training compliance dashboard catalog office positions sessions attendance tests') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('training')} 
                                        sx={navItemButtonSx(isCurrentUrl('/training'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <GraduationCap size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Training & Compliance" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.training ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.training} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/training/dashboard" 
                                                selected={isCurrentUrl('/training/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/training/dashboard'))}
                                            >
                                                <ListItemText primary="Compliance Dashboard" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/training/trainings" 
                                                selected={isCurrentUrl('/training/trainings')}
                                                sx={subItemButtonSx(isCurrentUrl('/training/trainings'))}
                                            >
                                                <ListItemText primary="Training Catalog" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/training/office-positions" 
                                                selected={isCurrentUrl('/training/office-positions')}
                                                sx={subItemButtonSx(isCurrentUrl('/training/office-positions'))}
                                            >
                                                <ListItemText primary="Office Positions" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/training/sessions" 
                                                selected={isCurrentUrl('/training/sessions')}
                                                sx={subItemButtonSx(isCurrentUrl('/training/sessions'))}
                                            >
                                                <ListItemText primary="Sessions & Attendance" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/training/my-trainings" 
                                                selected={isCurrentUrl('/training/my-trainings')}
                                                sx={subItemButtonSx(isCurrentUrl('/training/my-trainings'))}
                                            >
                                                <ListItemText primary="My Trainings & Tests" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 12. Digital Assets Group */}
                            {matchesSearch('digital assets software accounts platforms security properties compliance reviews devices audit logs') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('digitalAssets')} 
                                        sx={navItemButtonSx(isCurrentUrl('/digital-assets'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <ShieldCheck size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Digital Assets" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.digitalAssets ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.digitalAssets} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/digital-assets/accounts" 
                                                selected={isCurrentUrl('/digital-assets/accounts')}
                                                sx={subItemButtonSx(isCurrentUrl('/digital-assets/accounts'))}
                                            >
                                                <ListItemText primary="Software Accounts" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/digital-assets/platforms" 
                                                selected={isCurrentUrl('/digital-assets/platforms')}
                                                sx={subItemButtonSx(isCurrentUrl('/digital-assets/platforms'))}
                                            >
                                                <ListItemText primary="Platforms Catalog" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/digital-assets/property-definitions" 
                                                selected={isCurrentUrl('/digital-assets/property-definitions')}
                                                sx={subItemButtonSx(isCurrentUrl('/digital-assets/property-definitions'))}
                                            >
                                                <ListItemText primary="Security Properties" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/digital-assets/reviews" 
                                                selected={isCurrentUrl('/digital-assets/reviews')}
                                                sx={subItemButtonSx(isCurrentUrl('/digital-assets/reviews'))}
                                            >
                                                <ListItemText primary="Compliance Reviews" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/digital-assets/devices" 
                                                selected={isCurrentUrl('/digital-assets/devices')}
                                                sx={subItemButtonSx(isCurrentUrl('/digital-assets/devices'))}
                                            >
                                                <ListItemText primary="Device Inventory" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/digital-assets/audit-logs" 
                                                selected={isCurrentUrl('/digital-assets/audit-logs')}
                                                sx={subItemButtonSx(isCurrentUrl('/digital-assets/audit-logs'))}
                                            >
                                                <ListItemText primary="Security Audit Logs" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 13. Office Asset Group */}
                            {matchesSearch('office asset category list') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('officeAsset')} 
                                        sx={navItemButtonSx(isCurrentUrl('/office-asset'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <Laptop size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Office Asset" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.officeAsset ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.officeAsset} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/office-asset" 
                                                selected={isCurrentUrl('/office-asset') && !currentUrl.includes('groupBy=category')}
                                                sx={subItemButtonSx(isCurrentUrl('/office-asset') && !currentUrl.includes('groupBy=category'))}
                                            >
                                                <ListItemText primary="Asset List" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/office-asset?groupBy=category" 
                                                selected={currentUrl.includes('groupBy=category')}
                                                sx={subItemButtonSx(currentUrl.includes('groupBy=category'))}
                                            >
                                                <ListItemText primary="Category Summary" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 14. Jewelry Group */}
                            {matchesSearch('jewelry dashboard groups') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('jewelry')} 
                                        sx={navItemButtonSx(isCurrentUrl('/jewelry'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <Sparkles size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Jewelry" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.jewelry ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.jewelry} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/jewelry/dashboard" 
                                                selected={isCurrentUrl('/jewelry/dashboard')}
                                                sx={subItemButtonSx(isCurrentUrl('/jewelry/dashboard'))}
                                            >
                                                <ListItemText primary="Dashboard" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/jewelry/groups" 
                                                selected={isCurrentUrl('/jewelry/groups')}
                                                sx={subItemButtonSx(isCurrentUrl('/jewelry/groups'))}
                                            >
                                                <ListItemText primary="Groups" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                            {/* 15. Calendar Group */}
                            {matchesSearch('calendar auto sync') && (
                                <ListItem disablePadding sx={{ display: 'block' }}>
                                    <ListItemButton 
                                        onClick={() => toggleGroup('calendar')} 
                                        sx={navItemButtonSx(isCurrentUrl('/calendar'))}
                                    >
                                        <ListItemIcon sx={{ minWidth: 32, color: 'inherit' }}>
                                            <Calendar size={18} className="nav-icon" />
                                        </ListItemIcon>
                                        <ListItemText 
                                            primary="Calendar" 
                                            primaryTypographyProps={{ fontSize: '0.875rem', fontWeight: 'inherit' }} 
                                        />
                                        <ChevronDown 
                                            size={15} 
                                            style={{ 
                                                transition: 'transform 0.2s', 
                                                transform: openGroups.calendar ? 'rotate(180deg)' : 'rotate(0deg)',
                                                color: '#94a3b8' 
                                            }} 
                                        />
                                    </ListItemButton>
                                    <Collapse in={openGroups.calendar} timeout="auto" unmountOnExit>
                                        <List component="div" disablePadding sx={{ pl: 3.5, ml: 1.5, borderLeft: '1px solid', borderColor: 'divider' }}>
                                            <ListItemButton 
                                                component="a" 
                                                href="/calendar/index" 
                                                selected={isCurrentUrl('/calendar/index')}
                                                sx={subItemButtonSx(isCurrentUrl('/calendar/index'))}
                                            >
                                                <ListItemText primary="Calendar" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                            <ListItemButton 
                                                component="a" 
                                                href="/calendar/auto-sync" 
                                                selected={isCurrentUrl('/calendar/auto-sync')}
                                                sx={subItemButtonSx(isCurrentUrl('/calendar/auto-sync'))}
                                            >
                                                <ListItemText primary="Calendar Auto Sync" primaryTypographyProps={{ fontSize: '0.825rem', fontWeight: 'inherit' }} />
                                            </ListItemButton>
                                        </List>
                                    </Collapse>
                                </ListItem>
                            )}

                        </List>
                    </Box>

                    {/* Bottom Utilities (Matching Blade bottom links) */}
                    <Box sx={{ pt: 1, pb: 1, px: 1.5, borderTop: '1px solid', borderColor: 'divider' }}>
                        <List component="div" size="small" disablePadding>
                            <ListItem disablePadding>
                                <ListItemButton component="a" href="#" sx={subItemButtonSx(false)}>
                                    <ListItemIcon sx={{ minWidth: 28, color: 'text.secondary' }}>
                                        <BookOpen size={16} />
                                    </ListItemIcon>
                                    <ListItemText primary="Docs" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                </ListItemButton>
                            </ListItem>
                            <ListItem disablePadding>
                                <ListItemButton component="a" href="/help" sx={subItemButtonSx(isCurrentUrl('/help'))}>
                                    <ListItemIcon sx={{ minWidth: 28, color: 'text.secondary' }}>
                                        <HelpCircle size={16} />
                                    </ListItemIcon>
                                    <ListItemText primary="Help" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                </ListItemButton>
                            </ListItem>
                            {can.isSuperAdmin && (
                                <ListItem disablePadding>
                                    <ListItemButton component="a" href="/config" sx={subItemButtonSx(isCurrentUrl('/config'))}>
                                        <ListItemIcon sx={{ minWidth: 28, color: 'text.secondary' }}>
                                            <Settings size={16} />
                                        </ListItemIcon>
                                        <ListItemText primary="Super Config" primaryTypographyProps={{ fontSize: '0.825rem' }} />
                                    </ListItemButton>
                                </ListItem>
                            )}
                        </List>
                    </Box>

                    {/* User Profile Footer (Untitled UI Style) */}
                    {user && (
                        <Box sx={{ p: 2, borderTop: '1px solid', borderColor: 'divider', display: 'flex', alignItems: 'center', gap: 1.5, bgcolor: '#ffffff', '.dark &': { bgcolor: 'rgba(255,255,255,0.02)' } }}>
                            <Avatar sx={{ width: 36, height: 36, bgcolor: '#0f172a', color: '#ffffff', fontWeight: 600, fontSize: '0.875rem' }}>
                                {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                            </Avatar>
                            <Box sx={{ minWidth: 0, flexGrow: 1 }}>
                                <Typography variant="subtitle2" sx={{ fontWeight: 600, fontSize: '0.85rem', color: 'text.primary', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {user.name}
                                </Typography>
                                <Typography variant="caption" sx={{ color: 'text.secondary', fontSize: '0.75rem', display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                    {user.email}
                                </Typography>
                            </Box>
                        </Box>
                    )}
                </Box>
            </Drawer>

            {/* Main Content Body */}
            <Box component="main" sx={{ 
                flexGrow: 1, 
                p: noPadding ? 0 : { xs: 2, sm: 3 }, 
                pl: noPadding ? 0 : (hideTopNav ? { xs: 7, sm: 8 } : { xs: 2, sm: 3 }), 
                pt: noPadding ? 0 : (hideTopNav ? { xs: 2, sm: 2.5 } : { xs: 2, sm: 3 }) 
            }}>
                {children}
            </Box>

            <CreatePromoteActionModal />
            <ItSatisfactionModal />
        </Box>
    );
}
