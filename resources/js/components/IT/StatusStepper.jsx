import React from 'react';
import { Stepper, Step, StepLabel, StepButton, Box, Typography } from '@mui/material';

const DEFAULT_STATUS_ORDER = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'PENDING', 'DONE', 'CLOSED'];

export default function StatusStepper({
    statuses = [],
    currentStatusCode = 'OPEN',
    onSelectStatus,
    orientation = 'horizontal',
    interactive = true
}) {
    // Sort statuses according to defined workflow order or retain original order
    const orderedStatuses = [...statuses].sort((a, b) => {
        const indexA = DEFAULT_STATUS_ORDER.indexOf(a.code);
        const indexB = DEFAULT_STATUS_ORDER.indexOf(b.code);
        if (indexA !== -1 && indexB !== -1) return indexA - indexB;
        return (a.id || 0) - (b.id || 0);
    });

    const activeIndex = orderedStatuses.findIndex((s) => s.code === currentStatusCode);
    const currentStep = activeIndex !== -1 ? activeIndex : 0;

    return (
        <Box sx={{ width: '100%', minWidth: 560, py: 1, px: 0.5, overflow: 'visible' }}>
            <Stepper
                nonLinear
                activeStep={currentStep}
                orientation={orientation}
                alternativeLabel={orientation === 'horizontal'}
                sx={{
                    width: '100%',
                    '& .MuiStep-root': {
                        flex: 1,
                        minWidth: 85,
                        px: 0.5,
                    },
                    '& .MuiStepLabel-label': {
                        mt: 0.8,
                    },
                    '& .MuiStepConnector-line': {
                        borderColor: '#cbd5e1',
                        borderTopWidth: '2px',
                    },
                    '& .MuiStepConnector-root.Mui-active .MuiStepConnector-line': {
                        borderColor: '#3b82f6',
                    },
                    '& .MuiStepConnector-root.Mui-completed .MuiStepConnector-line': {
                        borderColor: '#10b981',
                    },
                }}
            >
                {orderedStatuses.map((st, index) => {
                    const isCompleted = index < currentStep;
                    const isActive = index === currentStep;

                    return (
                        <Step key={st.id || st.code} completed={isCompleted} active={isActive}>
                            {interactive ? (
                                <StepButton
                                    color="inherit"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        if (onSelectStatus) {
                                            onSelectStatus(st);
                                        }
                                    }}
                                    sx={{
                                        py: 0.5,
                                        px: 0.5,
                                        borderRadius: 2,
                                        width: '100%',
                                        transition: 'background-color 0.15s ease',
                                        '&:hover': {
                                            backgroundColor: '#eff6ff',
                                        },
                                    }}
                                >
                                    <StepLabel
                                        StepIconProps={{
                                            sx: {
                                                fontSize: '1.3rem',
                                                color: isActive ? '#2563eb' : isCompleted ? '#10b981' : '#94a3b8',
                                                '&.Mui-active': { color: '#2563eb' },
                                                '&.Mui-completed': { color: '#10b981' },
                                            }
                                        }}
                                    >
                                        <Typography
                                            component="span"
                                            sx={{
                                                fontWeight: isActive ? 700 : 600,
                                                fontSize: '0.75rem',
                                                color: isActive ? '#1d4ed8' : isCompleted ? '#059669' : '#475569',
                                                display: 'block',
                                                textAlign: 'center',
                                                lineHeight: 1.2,
                                            }}
                                        >
                                            {st.name}
                                        </Typography>
                                    </StepLabel>
                                </StepButton>
                            ) : (
                                <StepLabel
                                    StepIconProps={{
                                        sx: {
                                            fontSize: '1.3rem',
                                            color: isActive ? '#2563eb' : isCompleted ? '#10b981' : '#94a3b8',
                                            '&.Mui-active': { color: '#2563eb' },
                                            '&.Mui-completed': { color: '#10b981' },
                                        }
                                    }}
                                >
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontWeight: isActive ? 700 : 600,
                                            fontSize: '0.75rem',
                                            color: isActive ? '#1d4ed8' : isCompleted ? '#059669' : '#475569',
                                            display: 'block',
                                            textAlign: 'center',
                                            lineHeight: 1.2,
                                        }}
                                    >
                                        {st.name}
                                    </Typography>
                                </StepLabel>
                            )}
                        </Step>
                    );
                })}
            </Stepper>
        </Box>
    );
}
