import React, { useState, useEffect, useRef, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import AsideLayout from '../../Layouts/AsideLayout';
import axios from 'axios';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import ApexCharts from 'apexcharts';
import toast from 'react-hot-toast';

import ReportEditorContainer from '../../Components/ReportStudio/ReportEditorContainer';

// MUI Imports
import {
    Button,
    ButtonGroup,
    Menu,
    MenuItem,
    Box,
    Dialog,
    DialogTitle,
    DialogContent,
    IconButton,
    Typography
} from '@mui/material';

import {
    ContentCopy as ContentCopyIcon,
    FileDownload as FileDownloadIcon,
    KeyboardArrowDown as KeyboardArrowDownIcon,
    Campaign as CampaignIcon,
    EmojiEvents as EmojiEventsIcon,
    MilitaryTech as MilitaryTechIcon,
    WorkspacePremium as WorkspacePremiumIcon,
    Refresh as RefreshIcon,
    ArrowDropDown as ArrowDropDownIcon,
    Add as AddIcon,
    Close as CloseIcon,
    Link as LinkIcon,
    Check as CheckIcon,
    Description as DescriptionIcon
} from '@mui/icons-material';

export default function SaleKpi({ branches = [], departments = [], defaultFrom, defaultTo, taxonomies = {} }) {
    const capitalize = (str) => (!str ? '' : str.replace(/\b\w/g, (l) => l.toUpperCase()));

    const [showRichTextReportModal, setShowRichTextReportModal] = useState(false);

    // Format date string (e.g. 2026-08-09 -> Sun-9-Aug)
    const formatTrendDate = (dateStr) => {
        if (!dateStr || typeof dateStr !== 'string') return dateStr;
        const cleanStr = dateStr.trim().split(' ')[0];
        const parts = cleanStr.split('-');
        if (parts.length === 3) {
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1; // 0-indexed
            const day = parseInt(parts[2], 10);
            
            if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
                const d = new Date(year, month, day);
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const dayName = days[d.getDay()];
                const monthName = months[d.getMonth()];
                return `${dayName}-${day}-${monthName}`;
            }
        }
        return dateStr;
    };

    const [startDate, setStartDate] = useState(defaultFrom);
    const [endDate, setEndDate] = useState(defaultTo);
    const [selectedBranches, setSelectedBranches] = useState([]);
    const [viewType, setViewType] = useState('daily'); // 'daily' or 'monthly'
    const [activeMetric, setActiveMetric] = useState('weight'); // 'weight', 'quantity', 'customer'
    
    // API Data
    const [dashboardData, setDashboardData] = useState(null);
    const [loading, setLoading] = useState(true);

    // Branch Dropdown states
    const [branchDropdownOpen, setBranchDropdownOpen] = useState(false);
    const branchDropdownRef = useRef(null);

    // Click outside handler for branch dropdown
    useEffect(() => {
        const handleOutsideClick = (e) => {
            if (branchDropdownRef.current && !branchDropdownRef.current.contains(e.target)) {
                setBranchDropdownOpen(false);
            }
        };
        window.addEventListener('click', handleOutsideClick);
        return () => window.removeEventListener('click', handleOutsideClick);
    }, []);

    // Rewards Table sorting
    const [selectedRewardsColumn, setSelectedRewardsColumn] = useState('pcs_ratio');

    // Line Chart detail modal
    const [showLineDetailModal, setShowLineDetailModal] = useState(false);
    const [lineDetailLabel, setLineDetailLabel] = useState('');
    const [activePromoteActions, setActivePromoteActions] = useState([]);

    // Promote Actions List Modal
    const [showPromoteActionsListModal, setShowPromoteActionsListModal] = useState(false);

    // Chart refs
    const gramChartRef = useRef(null);
    const pcsChartRef = useRef(null);
    const lineChartRef = useRef(null);

    // Chart Instance refs
    const gramChartObjRef = useRef(null);
    const pcsChartObjRef = useRef(null);
    const lineChartObjRef = useRef(null);

    // Helper to convert base64 dataURI to Blob cleanly without fetch issues
    const dataURItoBlob = (dataURI) => {
        try {
            const byteString = atob(dataURI.split(',')[1]);
            const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
            const ab = new ArrayBuffer(byteString.length);
            const ia = new Uint8Array(ab);
            for (let i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return new Blob([ab], { type: mimeString });
        } catch (e) {
            console.error('Error converting dataURI to Blob:', e);
            return null;
        }
    };

    // Helper to copy chart image to clipboard
    const copyChartToClipboard = (chartObj, title = 'Chart') => {
        if (!chartObj || typeof chartObj.dataURI !== 'function') {
            toast.error('Chart instance is not ready');
            return;
        }
        chartObj.dataURI().then((result) => {
            const uri = result?.imgURI || result;
            if (!uri || typeof uri !== 'string') {
                toast.error('Could not generate chart image');
                return;
            }
            const blob = dataURItoBlob(uri);
            if (!blob) {
                toast.error('Failed to create image blob');
                return;
            }
            if (navigator.clipboard && window.ClipboardItem) {
                const item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(() => {
                    toast.success(`${title} image copied to clipboard!`);
                }).catch(err => {
                    console.error('Clipboard write error:', err);
                    toast.error('Failed to copy image to clipboard');
                });
            } else {
                toast.error('Clipboard API not supported in this browser');
            }
        }).catch(err => {
            console.error('Copy chart image error:', err);
            toast.error('Failed to copy chart image');
        });
    };

    // Helper to download chart image
    const downloadChartImage = (chartObj, filename = 'chart.png') => {
        if (!chartObj || typeof chartObj.dataURI !== 'function') {
            toast.error('Chart instance is not ready');
            return;
        }
        chartObj.dataURI().then((result) => {
            const uri = result?.imgURI || result;
            if (!uri || typeof uri !== 'string') {
                toast.error('Could not generate chart image');
                return;
            }
            const blob = dataURItoBlob(uri);
            if (!blob) {
                toast.error('Failed to create image blob');
                return;
            }
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(url), 2000);
            toast.success(`Downloaded ${filename}`);
        }).catch(err => {
            console.error('Download chart image error:', err);
            toast.error('Failed to download chart image');
        });
    };

    // MUI ButtonGroup Dropdown Export Menu Component for Charts with Stateful Feedback
    const ChartExportMenu = ({ chartRef, filename, title }) => {
        const [anchorEl, setAnchorEl] = useState(null);
        const open = Boolean(anchorEl);
        const [copied, setCopied] = useState(false);
        const [downloaded, setDownloaded] = useState(false);

        const handleClick = (event) => setAnchorEl(event.currentTarget);
        const handleClose = () => setAnchorEl(null);

        const handleCopy = () => {
            handleClose();
            const chartObj = chartRef?.current;
            if (!chartObj) {
                toast.error('Chart is not ready yet');
                return;
            }
            setCopied(true);
            copyChartToClipboard(chartObj, title);
            setTimeout(() => setCopied(false), 2000);
        };

        const handleDownload = () => {
            handleClose();
            const chartObj = chartRef?.current;
            if (!chartObj) {
                toast.error('Chart is not ready yet');
                return;
            }
            setDownloaded(true);
            downloadChartImage(chartObj, filename);
            setTimeout(() => setDownloaded(false), 2000);
        };

        // Determine active icon and label
        let mainIcon = <ContentCopyIcon fontSize="small" />;
        let mainLabel = 'Copy';

        if (copied) {
            mainIcon = <CheckIcon fontSize="small" sx={{ color: 'success.main' }} />;
            mainLabel = 'Copied!';
        } else if (downloaded) {
            mainIcon = <CheckIcon fontSize="small" sx={{ color: 'success.main' }} />;
            mainLabel = 'Downloaded!';
        }

        return (
            <Box>
                <ButtonGroup variant="outlined" size="small" sx={{ borderRadius: 2 }}>
                    <Button
                        onClick={handleCopy}
                        startIcon={mainIcon}
                        sx={{
                            textTransform: 'none',
                            fontWeight: 600,
                            fontSize: '0.75rem',
                            borderColor: (copied || downloaded) ? 'success.main' : 'rgba(226, 232, 240, 0.8)',
                            color: (copied || downloaded) ? 'success.main' : 'text.primary',
                            bgcolor: 'background.paper',
                            '&:hover': { bgcolor: 'action.hover' },
                            '.dark &': { borderColor: (copied || downloaded) ? '#4ade80' : 'rgba(255,255,255,0.15)', color: (copied || downloaded) ? '#4ade80' : '#fff' }
                        }}
                    >
                        {mainLabel}
                    </Button>
                    <Button
                        size="small"
                        onClick={handleClick}
                        sx={{
                            px: 0.75,
                            borderColor: (copied || downloaded) ? 'success.main' : 'rgba(226, 232, 240, 0.8)',
                            color: (copied || downloaded) ? 'success.main' : 'text.primary',
                            bgcolor: 'background.paper',
                            '&:hover': { bgcolor: 'action.hover' },
                            '.dark &': { borderColor: (copied || downloaded) ? '#4ade80' : 'rgba(255,255,255,0.15)', color: (copied || downloaded) ? '#4ade80' : '#fff' }
                        }}
                    >
                        <KeyboardArrowDownIcon fontSize="small" />
                    </Button>
                </ButtonGroup>

                <Menu
                    anchorEl={anchorEl}
                    open={open}
                    onClose={handleClose}
                    anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
                    transformOrigin={{ vertical: 'top', horizontal: 'right' }}
                    slotProps={{
                        paper: {
                            elevation: 3,
                            sx: {
                                borderRadius: 2,
                                minWidth: 160,
                                mt: 0.5,
                                '.dark &': { bgcolor: '#1e293b', color: '#fff' }
                            }
                        }
                    }}
                >
                    <MenuItem onClick={handleCopy} sx={{ fontSize: '0.8125rem', gap: 1.5, py: 1 }}>
                        {copied ? <CheckIcon fontSize="small" sx={{ color: 'success.main' }} /> : <ContentCopyIcon fontSize="small" sx={{ color: 'primary.main' }} />}
                        {copied ? 'Copied to Clipboard' : 'Copy as Image'}
                    </MenuItem>
                    <MenuItem onClick={handleDownload} sx={{ fontSize: '0.8125rem', gap: 1.5, py: 1 }}>
                        {downloaded ? <CheckIcon fontSize="small" sx={{ color: 'success.main' }} /> : <FileDownloadIcon fontSize="small" sx={{ color: 'success.main' }} />}
                        {downloaded ? 'Downloaded!' : 'Download PNG'}
                    </MenuItem>
                </Menu>
            </Box>
        );
    };

    // Flatpickr ref
    const dateRangeInputRef = useRef(null);

    // Fetch dashboard data
    const fetchDashboardData = (isInitial = false) => {
        if (isInitial) setLoading(true);
        axios.get('/sale-kpi/data', {
            params: {
                start_date: startDate,
                end_date: endDate,
                branch_ids: selectedBranches.join(','),
                view_type: viewType
            }
        })
        .then((res) => {
            setDashboardData(res.data);
        })
        .catch((err) => {
            console.error('Error fetching dashboard data:', err);
        })
        .finally(() => {
            setLoading(false);
        });
    };

    // Refetch when filters change
    useEffect(() => {
        fetchDashboardData(dashboardData === null);
    }, [startDate, endDate, selectedBranches, viewType]);

    // Initialize Flatpickr
    useEffect(() => {
        if (!dateRangeInputRef.current) return;
        const fp = flatpickr(dateRangeInputRef.current, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [startDate, endDate],
            onChange: (selectedDates) => {
                if (selectedDates.length === 2) {
                    const [start, end] = selectedDates;
                    const startStr = start.getFullYear() + '-' + String(start.getMonth() + 1).padStart(2, '0') + '-' + String(start.getDate()).padStart(2, '0');
                    const endStr = end.getFullYear() + '-' + String(end.getMonth() + 1).padStart(2, '0') + '-' + String(end.getDate()).padStart(2, '0');
                    setStartDate(startStr);
                    setEndDate(endStr);
                }
            }
        });
        return () => fp.destroy();
    }, []);

    // Render Column Charts
    useEffect(() => {
        if (!dashboardData || loading) return;

        // 1. Gram Column Chart
        const gramData = dashboardData.gram_chart || [];
        const gramOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: true,
                    tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false }
                },
                background: 'transparent'
            },
            colors: ['#FACC15', '#94A3B8'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 0
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.2,
                    opacityFrom: 0.95,
                    opacityTo: 0.8,
                    stops: [0, 100]
                }
            },
            dataLabels: { enabled: false },
            series: [
                { name: 'Actual Sale (g)', data: gramData.map(d => d.actual) },
                { name: 'Target (g)', data: gramData.map(d => d.target) }
            ],
            xaxis: {
                categories: gramData.map(d => capitalize(d.branch_name)),
                labels: { style: { colors: '#64748B' } }
            },
            yaxis: {
                title: { text: 'Grams (g)' }
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} g`
                }
            },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };

        if (gramChartRef.current) {
            gramChartRef.current.innerHTML = '';
            const chart = new ApexCharts(gramChartRef.current, gramOptions);
            gramChartObjRef.current = chart;
            chart.render();
        }

        // 2. Pcs Column Chart
        const pcsData = dashboardData.pcs_chart || [];
        const pcsOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: true,
                    tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false }
                },
                background: 'transparent'
            },
            colors: ['#2DD4BF', '#94A3B8'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 0
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.2,
                    opacityFrom: 0.95,
                    opacityTo: 0.8,
                    stops: [0, 100]
                }
            },
            dataLabels: { enabled: false },
            series: [
                { name: 'Actual Sale (pcs)', data: pcsData.map(d => d.actual) },
                { name: 'Target (pcs)', data: pcsData.map(d => d.target) }
            ],
            xaxis: {
                categories: pcsData.map(d => capitalize(d.branch_name)),
                labels: { style: { colors: '#64748B' } }
            },
            yaxis: {
                title: { text: 'Pieces' }
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} pcs`
                }
            },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };

        if (pcsChartRef.current) {
            pcsChartRef.current.innerHTML = '';
            const chart = new ApexCharts(pcsChartRef.current, pcsOptions);
            pcsChartObjRef.current = chart;
            chart.render();
        }

    }, [dashboardData, loading]);

    // Render Line Chart
    useEffect(() => {
        if (!dashboardData || loading) return;

        const lc = dashboardData.line_chart || {};
        const labels = lc.labels || [];
        const overlaps = lc.overlap_counts || [];
        const customerValues = lc.customer || [];
        
        // Select active primary metric values
        let rawValues = [];
        if (activeMetric === 'weight') rawValues = lc.weight || [];
        else rawValues = lc.quantity || [];

        // Build segment series for primary metric with PA campaign highlights
        const s0 = Array(rawValues.length).fill(null); // No PA (gray, dotted)
        const s1 = Array(rawValues.length).fill(null); // 1 PA (yellow)
        const s2 = Array(rawValues.length).fill(null); // 2 PA (teal)
        const s3 = Array(rawValues.length).fill(null); // 3 PA (dark yellow)
        const s4 = Array(rawValues.length).fill(null); // 3+ PA (dark teal)

        for (let i = 0; i < rawValues.length; i++) {
            const val = rawValues[i];
            const count = overlaps[i];

            if (count === 0) s0[i] = val;
            else if (count === 1) s1[i] = val;
            else if (count === 2) s2[i] = val;
            else if (count === 3) s3[i] = val;
            else s4[i] = val;

            // Connect boundary segments
            if (i > 0) {
                const prevCount = overlaps[i - 1];
                if (prevCount !== count) {
                    if (prevCount === 0) s0[i] = val;
                    else if (prevCount === 1) s1[i] = val;
                    else if (prevCount === 2) s2[i] = val;
                    else if (prevCount === 3) s3[i] = val;
                    else s4[i] = val;
                }
            }
        }

        const primaryValidNums = rawValues.filter(v => v !== null && v !== undefined && !isNaN(v));
        const custValidNums = customerValues.filter(v => v !== null && v !== undefined && !isNaN(v));
        
        const rawMaxPrimary = primaryValidNums.length > 0 ? Math.max(...primaryValidNums) : 100;
        const rawMaxCust = custValidNums.length > 0 ? Math.max(...custValidNums) : 100;

        const primaryYMax = Math.ceil(rawMaxPrimary * 1.1);
        const custYMax = Math.ceil(rawMaxCust * 1.1);

        const lineOptions = {
            chart: {
                type: 'line',
                height: 400,
                toolbar: {
                    show: true,
                    tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false }
                },
                events: {
                    markerClick: (event, chartContext, { dataPointIndex }) => {
                        const rawLabel = labels[dataPointIndex];
                        const formattedLabel = formatTrendDate(rawLabel);
                        const details = lc.overlap_details?.[rawLabel] || lc.overlap_details?.[formattedLabel] || [];
                        setLineDetailLabel(formattedLabel);
                        setActivePromoteActions(details);
                        setShowLineDetailModal(true);
                    }
                }
            },
            series: [
                { name: 'Customer Count (Gray Line)', data: customerValues },
                { name: 'No Campaign (Gray Dotted)', data: s0 },
                { name: '1 Promote Action (Yellow)', data: s1 },
                { name: '2 Promote Actions (Teal)', data: s2 },
                { name: '3 Promote Actions (Dark Yellow)', data: s3 },
                { name: '3+ Promote Actions (Dark Teal)', data: s4 }
            ],
            stroke: {
                width: [2, 3, 3, 3, 3, 3],
                curve: 'smooth',
                dashArray: [0, 5, 0, 0, 0, 0] // 0 for Customer, 5 for s0 (dotted), others solid
            },
            colors: ['#CBD5E1', '#94A3B8', '#FEF08A', '#99F6E4', '#EAB308', '#0D9488'],
            markers: {
                size: [3, 5, 5, 5, 5, 5],
                hover: { size: 7 }
            },
            xaxis: {
                categories: labels.map(formatTrendDate),
                labels: { style: { colors: '#64748B' } }
            },
            yaxis: [
                {
                    seriesName: 'Customer Count (Gray Line)',
                    opposite: true,
                    title: { text: 'CUSTOMER COUNT' },
                    labels: { style: { colors: '#94A3B8' } },
                    min: 0,
                    max: custYMax
                },
                {
                    seriesName: 'No Campaign (Gray Dotted)',
                    title: { text: activeMetric === 'weight' ? 'WEIGHT (G)' : 'QUANTITY (PCS)' },
                    labels: { style: { colors: '#64748B' } },
                    min: 0,
                    max: primaryYMax
                },
                {
                    seriesName: '1 Promote Action (Yellow)',
                    show: false,
                    min: 0,
                    max: primaryYMax
                },
                {
                    seriesName: '2 Promote Actions (Teal)',
                    show: false,
                    min: 0,
                    max: primaryYMax
                },
                {
                    seriesName: '3 Promote Actions (Dark Yellow)',
                    show: false,
                    min: 0,
                    max: primaryYMax
                },
                {
                    seriesName: '3+ Promote Actions (Dark Teal)',
                    show: false,
                    min: 0,
                    max: primaryYMax
                }
            ],
            tooltip: {
                custom: function({ dataPointIndex }) {
                    const rawLabel = labels[dataPointIndex];
                    const formattedLabel = formatTrendDate(rawLabel);
                    const val = rawValues[dataPointIndex];
                    const cust = customerValues[dataPointIndex];
                    const details = lc.overlap_details?.[rawLabel] || lc.overlap_details?.[formattedLabel] || [];
                    const metricUnit = activeMetric === 'weight' ? 'g' : 'pcs';

                    let detailsHtml = '';
                    if (details.length === 0) {
                        detailsHtml = `<div style="font-size: 11px; color: #94A3B8; font-style: italic; margin-top: 4px;">No active promote actions</div>`;
                    } else {
                        detailsHtml = details.map(d => `
                            <div style="display: flex; align-items: center; gap: 4px; margin-top: 4px; font-size: 11px; font-weight: 700; color: #0F172A;">
                                <span style="font-size: 12px;">📢</span>
                                <span>${d.name}</span>
                                <span style="font-size: 10px; font-weight: 500; color: #64748B;">(${d.department || 'N/A'})</span>
                            </div>
                        `).join('');
                    }

                    return `
                        <div style="padding: 10px 12px; background: #ffffff; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); font-family: inherit;">
                            <div style="font-weight: 700; font-size: 12px; color: #0F172A; margin-bottom: 4px;">
                                📅 ${formattedLabel}
                            </div>
                            <div style="font-size: 12px; font-weight: 600; color: #475569; padding-bottom: 4px;">
                                ${activeMetric.toUpperCase()}: <span style="font-weight: 800; color: #0F172A;">${val !== null && val !== undefined ? val : 0} ${metricUnit}</span>
                            </div>
                            <div style="font-size: 12px; font-weight: 600; color: #64748B; padding-bottom: 6px; border-bottom: 1px solid #F1F5F9;">
                                CUSTOMER: <span style="font-weight: 800; color: #475569;">${cust !== null && cust !== undefined ? cust : 0} cust</span>
                            </div>
                            <div style="margin-top: 6px;">
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B;">
                                    Promote Action Items (${details.length})
                                </div>
                                ${detailsHtml}
                            </div>
                        </div>
                    `;
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            }
        };

        if (lineChartRef.current) {
            lineChartRef.current.innerHTML = '';
            const chart = new ApexCharts(lineChartRef.current, lineOptions);
            lineChartObjRef.current = chart;
            chart.render();
        }

    }, [dashboardData, loading, activeMetric]);

    // Handle open global modal
    const openCreatePromoteModal = () => {
        const event = new CustomEvent('show-promote-action-modal', {
            detail: {
                branches,
                departments,
                onSuccess: (newPA) => {
                    fetchDashboardData();
                }
            }
        });
        window.dispatchEvent(event);
    };

    // Rewards Table column mapping and positioning
    const dynamicCols = useMemo(() => {
        const defaultRatioCols = [
            { key: 'pcs_ratio', label: 'Pcs Ratio' },
            { key: 'gram_ratio', label: 'Gram Ratio' },
            { key: 'pcs_per_staff', label: 'Pcs Per Staff' },
            { key: 'customer_per_staff', label: 'Customer Per Staff' }
        ];
        return [
            { key: 'branch_name', label: 'Branch Name' },
            ...defaultRatioCols.filter(c => c.key === selectedRewardsColumn),
            ...defaultRatioCols.filter(c => c.key !== selectedRewardsColumn)
        ];
    }, [selectedRewardsColumn]);

    const sortedRewardsRows = useMemo(() => {
        const rewardsRows = dashboardData?.rewards_table || [];
        return [...rewardsRows].sort((a, b) => b[selectedRewardsColumn] - a[selectedRewardsColumn]);
    }, [dashboardData, selectedRewardsColumn]);

    const headerFilterActions = (
        <div className="flex items-center gap-2 flex-wrap justify-end">
            {/* Branch Filters - Custom Dropdown Checkbox */}
            <div className="relative" ref={branchDropdownRef}>
                <button
                    type="button"
                    onClick={() => setBranchDropdownOpen(!branchDropdownOpen)}
                    className="h-8 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-slate-500 transition flex items-center justify-between gap-1.5 min-w-36 shadow-sm"
                >
                    <span className="truncate">
                        {selectedBranches.length === 0 
                            ? 'All Branches' 
                            : branches
                                .filter(b => selectedBranches.includes(b.id))
                                .map(b => capitalize(b.name))
                                .join(', ')
                        }
                    </span>
                    <ArrowDropDownIcon fontSize="small" className="text-slate-400" />
                </button>

                {branchDropdownOpen && (
                    <div className="absolute right-0 mt-1 w-56 max-h-64 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-50 p-2 space-y-0.5">
                        {/* Combined Reset Action inside Selection Dropdown */}
                        <button
                            type="button"
                            onClick={() => {
                                setStartDate(defaultFrom);
                                setEndDate(defaultTo);
                                setSelectedBranches([]);
                                if (dateRangeInputRef.current && dateRangeInputRef.current._flatpickr) {
                                    dateRangeInputRef.current._flatpickr.setDate([defaultFrom, defaultTo]);
                                }
                                setBranchDropdownOpen(false);
                            }}
                            className="w-full flex items-center gap-2 px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-slate-600 dark:text-slate-300 text-xs font-bold transition border-b border-slate-100 dark:border-slate-700 pb-2 mb-1"
                        >
                            <RefreshIcon fontSize="small" className="text-slate-500" />
                            <span>Reset All Filters</span>
                        </button>

                        {branches.map((b) => {
                            const isChecked = selectedBranches.includes(b.id);
                            return (
                                <label
                                    key={b.id}
                                    className="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg cursor-pointer transition select-none"
                                >
                                    <input
                                        type="checkbox"
                                        checked={isChecked}
                                        onChange={() => {
                                            if (isChecked) {
                                                setSelectedBranches(selectedBranches.filter(id => id !== b.id));
                                            } else {
                                                setSelectedBranches([...selectedBranches, b.id]);
                                            }
                                        }}
                                        className="w-4 h-4 rounded border-slate-350 dark:border-slate-650 text-slate-900 focus:ring-slate-500 cursor-pointer"
                                    />
                                    <span className="text-xs text-slate-700 dark:text-slate-350 font-medium">
                                        {capitalize(b.name)}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Date Range Picker */}
            <input
                ref={dateRangeInputRef}
                type="text"
                placeholder="Select Date Range"
                className="h-8 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-slate-500 shadow-sm w-44"
            />

            {/* View Promote Actions Trigger Button displaying Detail Item Names */}
            {(() => {
                const paList = dashboardData?.promote_actions || [];
                let buttonText = 'PA (0)';
                if (paList.length === 1) {
                    buttonText = paList[0].name;
                } else if (paList.length === 2) {
                    buttonText = `${paList[0].name}, ${paList[1].name}`;
                } else if (paList.length > 2) {
                    buttonText = `${paList[0].name}, ${paList[1].name} (+${paList.length - 2})`;
                }

                return (
                    <button
                        onClick={() => setShowPromoteActionsListModal(true)}
                        className="h-8 px-3 rounded-xl bg-[#FEF08A] hover:bg-[#FDE047] text-slate-800 text-xs font-bold transition flex items-center gap-1.5 shadow-sm max-w-xs md:max-w-md"
                        title={paList.length > 0 ? paList.map(pa => pa.name).join(', ') : 'No active promote actions'}
                    >
                        <CampaignIcon fontSize="small" />
                        <span className="truncate">
                            {paList.length === 0 ? 'PA (0)' : `PA: ${buttonText}`}
                        </span>
                    </button>
                );
            })()}

            {/* Rich Text Report Studio Modal Trigger */}
            <button
                type="button"
                onClick={() => setShowRichTextReportModal(true)}
                className="h-8 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
            >
                <DescriptionIcon fontSize="small" />
                <span>Rich Text Report</span>
            </button>
        </div>
    );

    return (
        <AsideLayout title="Sale KPI Report Dashboard" headerActions={headerFilterActions}>
            <Head title="Sale KPI Report Dashboard" />

            <div className="space-y-6 text-slate-800 dark:text-slate-100">

                {loading && !dashboardData && (
                    <div className="flex justify-center items-center py-12">
                        <div className="w-8 h-8 border-4 border-slate-900 dark:border-slate-100 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                )}

                {/* Section 1: Column Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-3">
                        <div className="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Actual vs Target (Grams)</h3>
                                <p className="text-xs text-slate-500">Branch weight performance overview</p>
                            </div>
                            <ChartExportMenu 
                                chartRef={gramChartObjRef} 
                                filename="gram-kpi-chart.png" 
                                title="Grams Chart" 
                            />
                        </div>
                        <div ref={gramChartRef} />
                    </div>

                    <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-3">
                        <div className="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Actual vs Target (Pieces)</h3>
                                <p className="text-xs text-slate-500">Branch quantity performance overview</p>
                            </div>
                            <ChartExportMenu 
                                chartRef={pcsChartObjRef} 
                                filename="pcs-kpi-chart.png" 
                                title="Pieces Chart" 
                            />
                        </div>
                        <div ref={pcsChartRef} />
                    </div>
                </div>

                {/* Section 3: Line Chart */}
                <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-4">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Trend & Campaign Overlap Chart</h3>
                            <p className="text-xs text-slate-500">Tracks performance overlap during active campaign periods (Dotted: No PA, Yellow: 1 PA, Teal: 2 PA, Dark Yellow: 3 PA, Dark Teal: 3+ PA)</p>
                        </div>

                        {/* Controls */}
                        <div className="flex items-center gap-3 flex-wrap self-end sm:self-center">
                            {/* MUI Dropdown Export Menu */}
                            <ChartExportMenu 
                                chartRef={lineChartObjRef} 
                                filename="trend-kpi-chart.png" 
                                title="Trend Chart" 
                            />
                            {/* Metric Toggle */}
                            <div className="flex bg-slate-100 dark:bg-slate-850 p-1 rounded-xl">
                                {['weight', 'quantity'].map((m) => (
                                    <button
                                        key={m}
                                        onClick={() => setActiveMetric(m)}
                                        className={`px-3 py-1 text-xs font-semibold rounded-lg capitalize transition ${
                                            activeMetric === m 
                                                ? 'bg-[#FEF08A] shadow text-slate-900 font-bold' 
                                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-350'
                                        }`}
                                    >
                                        {m}
                                    </button>
                                ))}
                            </div>

                            {/* Date View Mode (Daily vs Monthly) */}
                            <div className="flex bg-slate-100 dark:bg-slate-850 p-1 rounded-xl">
                                {['daily', 'monthly'].map((v) => (
                                    <button
                                        key={v}
                                        onClick={() => setViewType(v)}
                                        className={`px-3 py-1 text-xs font-semibold rounded-lg capitalize transition ${
                                            viewType === v 
                                                ? 'bg-[#FEF08A] shadow text-slate-900 font-bold' 
                                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-350'
                                        }`}
                                    >
                                        {v}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div ref={lineChartRef} />
                </div>

                {/* Section 4: Top Performer Table */}
                <div className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-sm space-y-4">
                    <div>
                        <h3 className="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <EmojiEventsIcon sx={{ color: '#F59E0B' }} />
                            <span>Top Performer</span>
                        </h3>
                        <p className="text-xs text-slate-500">Branch ranking based on selected KPI performance ratio</p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                            <thead className="bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-350 text-xs uppercase font-semibold">
                                <tr>
                                    {dynamicCols.map((col) => {
                                        const isSortable = col.key !== 'branch_name';
                                        return (
                                            <th key={col.key} className="px-6 py-3">
                                                <div className="flex items-center gap-2">
                                                    {isSortable && (
                                                        <input
                                                            type="radio"
                                                            name="rewards_sort"
                                                            checked={selectedRewardsColumn === col.key}
                                                            onChange={() => setSelectedRewardsColumn(col.key)}
                                                            className="w-4 h-4 text-slate-900 border-slate-300 dark:border-slate-650 focus:ring-slate-500 cursor-pointer"
                                                        />
                                                    )}
                                                    <span className={isSortable ? 'cursor-pointer' : ''} onClick={() => isSortable && setSelectedRewardsColumn(col.key)}>
                                                        {col.label}
                                                    </span>
                                                </div>
                                            </th>
                                        );
                                    })}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {sortedRewardsRows.length === 0 ? (
                                    <tr>
                                        <td colSpan={dynamicCols.length} className="px-6 py-8 text-center text-slate-400">
                                            No rewards data available.
                                        </td>
                                    </tr>
                                ) : (
                                    sortedRewardsRows.map((row, index) => {
                                        // Rank-based permanent row styling
                                        let rowBgClass = "hover:bg-slate-50/50 dark:hover:bg-slate-800/40";
                                        if (index === 0) {
                                            rowBgClass = "bg-amber-100 dark:bg-amber-900/60 text-amber-950 dark:text-amber-100 font-bold";
                                        } else if (index === 1) {
                                            rowBgClass = "bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold";
                                        } else if (index === 2) {
                                            rowBgClass = "bg-orange-100/80 dark:bg-amber-950/40 text-amber-950 dark:text-amber-200 font-semibold";
                                        }

                                        return (
                                            <tr key={row.branch_id} className={`transition-colors ${rowBgClass}`}>
                                                {dynamicCols.map((col) => {
                                                    let val = row[col.key];
                                                    const isHighlighted = col.key === selectedRewardsColumn;
                                                    
                                                    const cellHighlight = isHighlighted 
                                                        ? 'font-black underline decoration-slate-400/60 decoration-2' 
                                                        : '';

                                                    return (
                                                        <td 
                                                            key={col.key} 
                                                            className={`px-6 py-4 ${
                                                                col.key === 'branch_name' 
                                                                    ? 'font-bold text-slate-900 dark:text-slate-100' 
                                                                    : 'font-semibold'
                                                            } ${cellHighlight}`}
                                                        >
                                                            {col.key === 'branch_name' ? (
                                                                <div className="flex items-center gap-2.5">
                                                                    {index === 0 && <WorkspacePremiumIcon sx={{ color: '#F59E0B', fontSize: 20 }} titleAccess="1st Place Gold" />}
                                                                    {index === 1 && <MilitaryTechIcon sx={{ color: '#94A3B8', fontSize: 20 }} titleAccess="2nd Place Silver" />}
                                                                    {index === 2 && <MilitaryTechIcon sx={{ color: '#B45309', fontSize: 20 }} titleAccess="3rd Place Bronze" />}
                                                                    {index > 2 && <span className="text-xs font-bold text-slate-400 w-5 text-center">#{index + 1}</span>}
                                                                    <span>{capitalize(val)}</span>
                                                                </div>
                                                            ) : (
                                                                val
                                                            )}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Line Chart marker details Modal */}
            {showLineDetailModal && (
                <div 
                    style={{ position: 'fixed', inset: 0, zIndex: 9500, backgroundColor: 'rgba(15,23,42,0.6)' }}
                    className="flex items-center justify-center p-4 backdrop-blur-sm"
                    onClick={() => setShowLineDetailModal(false)}
                >
                    <div 
                        className="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-xl p-6 border border-slate-200 dark:border-slate-800"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex justify-between items-center mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">
                                Promote Actions on {lineDetailLabel}
                            </h3>
                            <button 
                                onClick={() => setShowLineDetailModal(false)} 
                                className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                            >
                                <CloseIcon fontSize="small" />
                            </button>
                        </div>
                        
                        <div className="max-h-60 overflow-y-auto space-y-3 pr-1">
                            {activePromoteActions.length === 0 ? (
                                <p className="text-xs text-slate-500 text-center py-4">
                                    No active promote actions on this date.
                                </p>
                            ) : (
                                activePromoteActions.map((pa) => (
                                    <div 
                                        key={pa.id} 
                                        className="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-100 dark:border-slate-800"
                                    >
                                        <h4 className="font-semibold text-xs text-slate-800 dark:text-slate-250 flex items-center gap-1.5">
                                            <CampaignIcon fontSize="small" className="text-amber-500" />
                                            <span>{pa.name}</span>
                                        </h4>
                                        <div className="text-[10px] text-slate-500 mt-2 space-y-1">
                                            <div>Duration: {pa.start_at} to {pa.end_at}</div>
                                            <div>Department: {pa.department}</div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        <div className="flex justify-end pt-4 mt-2 border-t border-slate-100 dark:border-slate-800">
                            <button
                                onClick={() => setShowLineDetailModal(false)}
                                className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-850 text-slate-700 dark:text-slate-350 text-xs font-semibold hover:opacity-90"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Promote Actions Table Modal */}
            {showPromoteActionsListModal && (
                <div 
                    style={{ position: 'fixed', inset: 0, zIndex: 8500, backgroundColor: 'rgba(15,23,42,0.6)' }}
                    className="flex items-center justify-center p-4 backdrop-blur-sm"
                    onClick={() => setShowPromoteActionsListModal(false)}
                >
                    <div 
                        className="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-3xl shadow-xl p-6 border border-slate-200 dark:border-slate-800"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex justify-between items-center mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-slate-100">
                                    Promote Actions List
                                </h3>
                                <p className="text-xs text-slate-500">Ongoing active campaigns in date scope</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <button
                                    onClick={() => {
                                        setShowPromoteActionsListModal(false);
                                        openCreatePromoteModal();
                                    }}
                                    className="h-9 px-4 bg-[#FEF08A] hover:bg-[#FDE047] text-slate-800 rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1"
                                >
                                    <AddIcon fontSize="small" />
                                    <span>Create Promotion</span>
                                </button>
                                <button 
                                    onClick={() => setShowPromoteActionsListModal(false)} 
                                    className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    <CloseIcon fontSize="small" />
                                </button>
                            </div>
                        </div>
                        
                        <div className="overflow-x-auto max-h-[55vh] overflow-y-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="bg-slate-55 dark:bg-slate-800 text-slate-700 dark:text-slate-350 text-xs uppercase font-semibold">
                                    <tr>
                                        <th className="px-6 py-3">Promotion Name</th>
                                        <th className="px-6 py-3">Target Branch</th>
                                        <th className="px-6 py-3">Action By</th>
                                        <th className="px-6 py-3">Start Date</th>
                                        <th className="px-6 py-3">End Date</th>
                                        <th className="px-6 py-3">References</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {(dashboardData?.promote_actions || []).length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-8 text-center text-slate-400">
                                                No promote actions registered in this period.
                                            </td>
                                        </tr>
                                    ) : (
                                        (dashboardData.promote_actions).map((pa) => (
                                            <tr key={pa.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                                <td className="px-6 py-4 font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                                    <CampaignIcon fontSize="small" className="text-amber-500" />
                                                    <span>{pa.name}</span>
                                                </td>
                                                <td className="px-6 py-4 font-medium text-slate-800 dark:text-slate-200">{pa.target_branch}</td>
                                                <td className="px-6 py-4 font-medium text-slate-800 dark:text-slate-200">{pa.action_by_dept}</td>
                                                <td className="px-6 py-4 text-xs font-semibold text-slate-600 dark:text-slate-400">{pa.start_at}</td>
                                                <td className="px-6 py-4 text-xs font-semibold text-slate-600 dark:text-slate-400">{pa.end_at}</td>
                                                <td className="px-6 py-4 text-xs">
                                                    {pa.reference ? (
                                                        typeof pa.reference === 'object' && pa.reference.title ? (
                                                            <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 font-semibold border border-amber-200 dark:border-amber-900/40">
                                                                <LinkIcon fontSize="small" />
                                                                <span>{pa.reference.title}</span>
                                                            </span>
                                                        ) : (
                                                            <span className="font-mono text-[11px] text-slate-500">{JSON.stringify(pa.reference)}</span>
                                                        )
                                                    ) : (
                                                        <span className="text-slate-400">None</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="flex justify-end pt-4 mt-4 border-t border-slate-100 dark:border-slate-800">
                            <button
                                onClick={() => setShowPromoteActionsListModal(false)}
                                className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-850 text-slate-700 dark:text-slate-350 text-xs font-semibold hover:opacity-90"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Rich Text Reporting Studio Full Modal */}
            <Dialog
                open={showRichTextReportModal}
                onClose={() => setShowRichTextReportModal(false)}
                fullWidth
                maxWidth="xl"
                scroll="paper"
                PaperProps={{
                    sx: { borderRadius: 3, minHeight: '85vh', bgcolor: '#F8FAFC' }
                }}
            >
                <DialogTitle sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1, borderBottom: '1px solid #E2E8F0' }}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <DescriptionIcon color="primary" />
                        <Typography variant="h6" fontWeight="bold">
                            Context-Aware Rich Text Reporting Studio
                        </Typography>
                    </Box>
                    <IconButton onClick={() => setShowRichTextReportModal(false)}>
                        <CloseIcon />
                    </IconButton>
                </DialogTitle>
                <DialogContent dividers sx={{ p: 2 }}>
                    <ReportEditorContainer taxonomies={taxonomies} />
                </DialogContent>
            </Dialog>
        </AsideLayout>
    );
}
