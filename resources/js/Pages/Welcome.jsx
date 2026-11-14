import React, { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';

/* ─────────────────────────────────────────────────────────────
   KEYFRAMES
───────────────────────────────────────────────────────────── */
const KEYFRAMES = `
  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-22px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
  }
  /* shape spin-in — mirrors original shape-spin-in exactly */
  @keyframes shapeSpinIn {
    from { transform: rotate(-360deg); }
    to   { transform: var(--final-transform, rotate(0deg)); }
  }
  /* Decor containers slide in from bottom corners */
  @keyframes decorSlideLeft {
    from { opacity: 0; transform: translate(-400px, 400px); }
    to   { opacity: 1; transform: translate(0, 0); }
  }
  @keyframes decorSlideRight {
    from { opacity: 0; transform: translate(400px, 400px); }
    to   { opacity: 1; transform: translate(0, 0); }
  }
  /* Throwing login card in from below with a crisp springy arc */
  @keyframes throwCardIn {
    0% {
      opacity: 0;
      transform: translate3d(0, 120px, 0) scale(0.84) rotate(-3.5deg);
      filter: blur(4px);
    }
    65% {
      opacity: 1;
      transform: translate3d(0, -10px, 0) scale(1.02) rotate(0.6deg);
      filter: blur(0);
    }
    100% {
      opacity: 1;
      transform: translate3d(0, 0, 0) scale(1) rotate(0deg);
      filter: blur(0);
    }
  }
  /* Throwing login card back out when returning to welcome */
  @keyframes throwCardOut {
    0% {
      opacity: 1;
      transform: translate3d(0, 0, 0) scale(1) rotate(0deg);
    }
    100% {
      opacity: 0;
      transform: translate3d(0, 110px, 0) scale(0.86) rotate(3deg);
    }
  }
  /* Staggered form field reveal inside thrown card */
  @keyframes fieldSlideUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  /* Hide Edge/IE native password reveal button so only one eye icon shows */
  #login-password::-ms-reveal,
  #login-password::-ms-clear {
    display: none;
  }
`;

/* ─────────────────────────────────────────────────────────────
   Ring — circle outline that slides in from left or right.
   Matches original: transition 1s ease, triggered at 100ms.
───────────────────────────────────────────────────────────── */
function Ring({ direction, size, top, left, right, blur, visible }) {
    const style = {
        position: 'fixed',
        width: size,
        height: size,
        borderRadius: '50%',
        pointerEvents: 'none',
        zIndex: 1,
        top,
        background: 'linear-gradient(140deg, #c7d2fe 0%, #818cf8 18%, #e0e7ff 36%, #a5b4fc 50%, #6366f1 68%, #c7d2fe 100%)',
        WebkitMask: 'radial-gradient(farthest-side, transparent calc(100% - 29px), #000 calc(100% - 28px))',
        mask:        'radial-gradient(farthest-side, transparent calc(100% - 29px), #000 calc(100% - 28px))',
        boxShadow: '0 0 30px rgba(99,102,241,0.10), 0 18px 28px rgba(0,0,0,0.04)',
        filter: blur ? `saturate(1.08) blur(${blur})` : 'saturate(1.08)',
        transition: 'transform 1s ease, opacity 1s ease, filter 1s ease',
        opacity:   visible ? 1 : 0,
        transform: visible
            ? 'translateX(0)'
            : direction === 'left' ? 'translateX(-140vw)' : 'translateX(140vw)',
    };
    if (left  !== undefined) style.left  = left;
    if (right !== undefined) style.right = right;
    return <div style={style} aria-hidden="true" />;
}

/* ─────────────────────────────────────────────────────────────
   Shape — square or circle, spins in when active.
───────────────────────────────────────────────────────────── */
function Shape({ type, variant, style: extra, finalTransform, active }) {
    const isCircle = type === 'circle';
    const base = {
        position: 'absolute',
        display: 'block',
        '--final-transform': finalTransform || 'rotate(0deg)',
        borderRadius: isCircle ? '50%' : '0',
        animation: active ? 'shapeSpinIn 1s ease forwards' : 'none',
        transform: active ? undefined : 'rotate(-360deg)',
        ...extra,
    };

    if (type === 'square') {
        if (variant === 'solid') {
            Object.assign(base, {
                background: 'linear-gradient(135deg, rgba(99,102,241,0.18), rgba(129,140,248,0.06) 36%, rgba(165,180,252,0.14) 100%)',
                border: '1px solid rgba(99,102,241,0.30)',
                boxShadow: 'inset 1px 1px 0 rgba(255,255,255,0.8), inset -6px -6px 12px rgba(99,102,241,0.08), 0 10px 20px rgba(99,102,241,0.10)',
            });
        } else {
            Object.assign(base, {
                backgroundColor: 'rgba(99,102,241,0.04)',
                background: [
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) top    left  / 20px 2px  no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) top    left  / 2px  20px no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) top    right / 20px 2px  no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) top    right / 2px  20px no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) bottom left  / 20px 2px  no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) bottom left  / 2px  20px no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) bottom right / 20px 2px  no-repeat',
                    'linear-gradient(rgba(99,102,241,0.28),rgba(99,102,241,0.28)) bottom right / 2px  20px no-repeat',
                ].join(', '),
                border: '1px solid rgba(99,102,241,0.10)',
                boxShadow: 'inset 1px 1px 0 rgba(255,255,255,0.6), 0 10px 20px rgba(99,102,241,0.07)',
            });
        }
    }

    if (type === 'circle') {
        if (variant === 'solid') {
            Object.assign(base, {
                background: 'linear-gradient(135deg, rgba(99,102,241,0.24), rgba(165,180,252,0.10) 36%, rgba(129,140,248,0.20) 100%)',
                border: '1px solid rgba(99,102,241,0.30)',
                boxShadow: 'inset 1px 1px 0 rgba(255,255,255,0.7), 0 8px 18px rgba(99,102,241,0.12)',
            });
        } else {
            Object.assign(base, {
                background: 'rgba(99,102,241,0.07)',
                border: '2px solid rgba(99,102,241,0.24)',
                boxShadow: 'inset 1px 1px 0 rgba(255,255,255,0.6), 0 8px 18px rgba(99,102,241,0.08)',
            });
        }
    }

    return <span style={base} />;
}

/* ─────────────────────────────────────────────────────────────
   Scene Decor container — slides in from bottom corner.
───────────────────────────────────────────────────────────── */
function SceneDecor({ side, decorVisible, children }) {
    const style = {
        position: 'fixed',
        bottom: '-20px',
        width: '320px',
        height: '320px',
        pointerEvents: 'none',
        zIndex: 2,
        filter: 'drop-shadow(0 14px 24px rgba(99,102,241,0.08))',
        ...(side === 'left' ? { left: '-24px' } : { right: '-24px' }),
        animation: decorVisible
            ? (side === 'left' ? 'decorSlideLeft 1s ease forwards' : 'decorSlideRight 1s ease forwards')
            : 'none',
        /* start off-screen when not yet visible */
        opacity:   decorVisible ? undefined : 0,
        transform: decorVisible ? undefined : (side === 'left' ? 'translate(-400px, 400px)' : 'translate(400px, 400px)'),
    };
    return <div style={style} aria-hidden="true">{children}</div>;
}

/* ─────────────────────────────────────────────────────────────
   Nav cards data
───────────────────────────────────────────────────────────── */
const NAV_CARDS = [
    { href: '#', icon: '✅', iconBg: 'linear-gradient(135deg,#e0e7ff,#c7d2fe)', title: 'Tasks & To-Do',         desc: 'Organize daily assignments, checklists, and team task progress.' },
    { href: '#', icon: '📊', iconBg: 'linear-gradient(135deg,#d1fae5,#a7f3d0)', title: 'KPIs & Performance',    desc: 'Track targets, branch scorecards, and employee performance metrics.' },
    { href: '#', icon: '🖥️', iconBg: 'linear-gradient(135deg,#e0f2fe,#bae6fd)', title: 'IT Operations',         desc: 'Manage IT support issues, digital accounts, and hardware assets.' },
    { href: '#', icon: '🎓', iconBg: 'linear-gradient(135deg,#fef3c7,#fde68a)', title: 'Training & Skills',     desc: 'Coordinate staff trainings, assessments, and skill matrices.' },
    { href: '#', icon: '📄', iconBg: 'linear-gradient(135deg,#fce7f3,#fbcfe8)', title: 'Documents & Reports',   desc: 'Access company document libraries, analytic boards, and reports.' },
    { href: '#', icon: '🔄', iconBg: 'linear-gradient(135deg,#f3e8ff,#e9d5ff)', title: 'Daily Team Workflows',  desc: 'Streamline orders, calendars, approvals, and branch operations.' },
];

function NavCard({ card }) {
    const [hovered, setHovered] = useState(false);
    return (
        <a
            href={card.href}
            style={{
                display: 'block',
                background: 'rgba(255,255,255,0.85)',
                backdropFilter: 'blur(8px)',
                WebkitBackdropFilter: 'blur(8px)',
                border: '1px solid ' + (hovered ? '#a5b4fc' : 'rgba(226,232,240,0.8)'),
                borderRadius: '16px',
                padding: '22px 24px',
                textDecoration: 'none',
                color: 'inherit',
                boxShadow: hovered
                    ? '0 12px 32px rgba(99,102,241,0.14), 0 2px 8px rgba(0,0,0,0.06)'
                    : '0 2px 8px rgba(0,0,0,0.05)',
                transform: hovered ? 'translateY(-3px)' : 'none',
                transition: 'transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease',
                cursor: 'pointer',
            }}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
        >
            <div style={{ width: 40, height: 40, borderRadius: 10, background: card.iconBg, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, marginBottom: 12 }}>
                {card.icon}
            </div>
            <h2 style={{ fontSize: '0.97rem', fontWeight: 700, color: '#1e293b', margin: '0 0 6px 0' }}>{card.title}</h2>
            <p  style={{ fontSize: '0.82rem', color: '#64748b', lineHeight: 1.55, margin: 0 }}>{card.desc}</p>
        </a>
    );
}

/* ─────────────────────────────────────────────────────────────
   Login Card — thrown into view from `/`
───────────────────────────────────────────────────────────── */
function LoginCard({ canResetPassword, status, onBack, isClosing, reduceMotion }) {
    const emailInputRef = useRef(null);
    const [showPassword, setShowPassword] = useState(false);
    const [focusedField, setFocusedField] = useState(null);
    const [submitHovered, setSubmitHovered] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        if (isClosing) return;
        const timer = setTimeout(() => {
            emailInputRef.current?.focus();
        }, reduceMotion ? 20 : 280);
        return () => clearTimeout(timer);
    }, [isClosing, reduceMotion]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    const cardAnimation = reduceMotion
        ? 'none'
        : isClosing
            ? 'throwCardOut 0.28s cubic-bezier(0.4, 0, 1, 1) forwards'
            : 'throwCardIn 0.56s cubic-bezier(0.22, 1.22, 0.36, 1) forwards';

    const inputStyle = (field, hasError) => ({
        width: '100%',
        boxSizing: 'border-box',
        padding: field === 'password' ? '12px 44px 12px 14px' : '12px 14px',
        borderRadius: 12,
        border: `1.5px solid ${
            hasError
                ? '#ef4444'
                : focusedField === field
                    ? '#6366f1'
                    : 'rgba(148, 163, 184, 0.45)'
        }`,
        background: 'rgba(255, 255, 255, 0.92)',
        color: '#0f172a',
        fontSize: '0.95rem',
        outline: 'none',
        boxShadow:
            focusedField === field
                ? '0 0 0 4px rgba(99, 102, 241, 0.14)'
                : '0 1px 2px rgba(15, 23, 42, 0.04)',
        transition: 'border-color 0.18s ease, box-shadow 0.18s ease',
    });

    const stagger = (delayMs) =>
        reduceMotion
            ? {}
            : { animation: `fieldSlideUp 0.4s ${delayMs}ms ease both` };

    return (
        <div
            style={{
                width: '100%',
                maxWidth: 440,
                margin: '0 auto',
                background: 'rgba(255, 255, 255, 0.88)',
                backdropFilter: 'blur(18px)',
                WebkitBackdropFilter: 'blur(18px)',
                borderRadius: 24,
                border: '1px solid rgba(165, 180, 252, 0.55)',
                boxShadow:
                    '0 24px 60px rgba(79, 70, 229, 0.16), 0 8px 24px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9)',
                padding: '28px 30px 30px',
                boxSizing: 'border-box',
                transformOrigin: 'center bottom',
                animation: cardAnimation,
            }}
        >
            {/* Top bar */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 18 }}>
                <button
                    type="button"
                    onClick={onBack}
                    style={{
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: 6,
                        padding: '6px 12px',
                        borderRadius: 9999,
                        border: '1px solid rgba(203, 213, 225, 0.85)',
                        background: 'rgba(248, 250, 252, 0.9)',
                        color: '#475569',
                        fontSize: '0.82rem',
                        fontWeight: 600,
                        cursor: 'pointer',
                        transition: 'background 0.15s ease, color 0.15s ease',
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.background = '#eef2ff';
                        e.currentTarget.style.color = '#4f46e5';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.background = 'rgba(248, 250, 252, 0.9)';
                        e.currentTarget.style.color = '#475569';
                    }}
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.3" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                    Back
                </button>

                <span
                    style={{
                        fontSize: '0.75rem',
                        fontWeight: 700,
                        letterSpacing: '0.08em',
                        textTransform: 'uppercase',
                        color: '#6366f1',
                        background: 'rgba(99, 102, 241, 0.1)',
                        padding: '4px 10px',
                        borderRadius: 9999,
                    }}
                >
                    Account Sign In
                </span>
            </div>

            {status && (
                <div
                    role="status"
                    style={{
                        marginBottom: 16,
                        padding: '10px 14px',
                        borderRadius: 12,
                        background: 'rgba(16, 185, 129, 0.12)',
                        border: '1px solid rgba(16, 185, 129, 0.3)',
                        color: '#065f46',
                        fontSize: '0.85rem',
                        fontWeight: 500,
                    }}
                >
                    {status}
                </div>
            )}

            <form onSubmit={handleSubmit} noValidate>
                {/* Email */}
                <div style={{ marginBottom: 16, ...stagger(110) }}>
                    <label
                        htmlFor="login-email"
                        style={{ display: 'block', fontSize: '0.84rem', fontWeight: 600, color: '#334155', marginBottom: 6 }}
                    >
                        Email
                    </label>
                    <input
                        ref={emailInputRef}
                        id="login-email"
                        type="email"
                        name="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        onFocus={() => setFocusedField('email')}
                        onBlur={() => setFocusedField(null)}
                        autoComplete="username"
                        required
                        placeholder="you@shwetatar.org"
                        style={inputStyle('email', Boolean(errors.email))}
                    />
                    {errors.email && (
                        <p style={{ margin: '6px 0 0 0', fontSize: '0.8rem', color: '#dc2626', fontWeight: 500 }}>
                            {errors.email}
                        </p>
                    )}
                </div>

                {/* Password */}
                <div style={{ marginBottom: 16, ...stagger(160) }}>
                    <label
                        htmlFor="login-password"
                        style={{ display: 'block', fontSize: '0.84rem', fontWeight: 600, color: '#334155', marginBottom: 6 }}
                    >
                        Password
                    </label>
                    <div style={{ position: 'relative' }}>
                        <input
                            id="login-password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            onFocus={() => setFocusedField('password')}
                            onBlur={() => setFocusedField(null)}
                            autoComplete="current-password"
                            required
                            placeholder="••••••••"
                            style={inputStyle('password', Boolean(errors.password))}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((prev) => !prev)}
                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                            style={{
                                position: 'absolute',
                                right: 10,
                                top: '50%',
                                transform: 'translateY(-50%)',
                                background: 'transparent',
                                border: 'none',
                                color: '#64748b',
                                cursor: 'pointer',
                                padding: 4,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                            }}
                        >
                            {showPassword ? (
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            ) : (
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            )}
                        </button>
                    </div>
                    {errors.password && (
                        <p style={{ margin: '6px 0 0 0', fontSize: '0.8rem', color: '#dc2626', fontWeight: 500 }}>
                            {errors.password}
                        </p>
                    )}
                </div>

                {/* Remember & Forgot Password */}
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 12,
                        marginBottom: 22,
                        flexWrap: 'wrap',
                        ...stagger(200),
                    }}
                >
                    <label style={{ display: 'inline-flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontSize: '0.85rem', color: '#475569' }}>
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            style={{ width: 16, height: 16, accentColor: '#4f46e5', cursor: 'pointer' }}
                        />
                        Remember me
                    </label>

                    {canResetPassword && (
                        <a
                            href="/forgot-password"
                            style={{ fontSize: '0.84rem', fontWeight: 600, color: '#4f46e5', textDecoration: 'none' }}
                        >
                            Forgot your password?
                        </a>
                    )}
                </div>

                {/* Submit */}
                <div style={stagger(240)}>
                    <button
                        type="submit"
                        disabled={processing}
                        onMouseEnter={() => setSubmitHovered(true)}
                        onMouseLeave={() => setSubmitHovered(false)}
                        style={{
                            width: '100%',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 8,
                            background: processing
                                ? 'linear-gradient(135deg, #818cf8, #6366f1)'
                                : 'linear-gradient(135deg, #6366f1, #4f46e5)',
                            color: '#fff',
                            fontWeight: 700,
                            fontSize: '0.98rem',
                            padding: '13px 24px',
                            border: 'none',
                            borderRadius: 12,
                            cursor: processing ? 'not-allowed' : 'pointer',
                            opacity: processing ? 0.8 : 1,
                            boxShadow:
                                submitHovered && !processing
                                    ? '0 10px 26px rgba(99, 102, 241, 0.42)'
                                    : '0 4px 16px rgba(99, 102, 241, 0.3)',
                            transform: submitHovered && !processing ? 'translateY(-1px)' : 'none',
                            transition: 'transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease',
                        }}
                    >
                        {processing ? 'Signing in...' : 'Log in'}
                    </button>
                </div>
            </form>
        </div>
    );
}

/* ─────────────────────────────────────────────────────────────
   MAIN PAGE
───────────────────────────────────────────────────────────── */
export default function Welcome({ initialShowLogin = false, canResetPassword = true, status = null }) {
    const reduceMotion =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const [ringsVisible,  setRingsVisible]  = useState(reduceMotion);
    const [decorVisible,  setDecorVisible]  = useState(reduceMotion);
    const [loginHovered,  setLoginHovered]  = useState(false);
    const [showLogin,     setShowLogin]     = useState(initialShowLogin);
    const [isClosing,     setIsClosing]     = useState(false);

    useEffect(() => {
        if (reduceMotion) return;
        const t1 = setTimeout(() => setRingsVisible(true),  100);   // mirrors original
        const t2 = setTimeout(() => setDecorVisible(true), 1500);   // mirrors original
        return () => { clearTimeout(t1); clearTimeout(t2); };
    }, []);

    useEffect(() => {
        setShowLogin(initialShowLogin);
        setIsClosing(false);
    }, [initialShowLogin]);

    useEffect(() => {
        const handlePopState = () => {
            const onLoginPath = window.location.pathname === '/login';
            setIsClosing(false);
            setShowLogin(onLoginPath);
        };
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && showLogin && !isClosing) {
                closeLogin();
            }
        };
        window.addEventListener('popstate', handlePopState);
        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('popstate', handlePopState);
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [showLogin, isClosing]);

    const openLogin = (e) => {
        e.preventDefault();
        if (showLogin) return;
        setIsClosing(false);
        setShowLogin(true);
        if (window.location.pathname !== '/login') {
            window.history.pushState({ showLogin: true }, '', '/login');
        }
    };

    const closeLogin = () => {
        if (!showLogin || isClosing) return;
        if (reduceMotion) {
            setShowLogin(false);
            if (window.location.pathname !== '/') {
                window.history.pushState({ showLogin: false }, '', '/');
            }
            return;
        }
        setIsClosing(true);
        setTimeout(() => {
            setShowLogin(false);
            setIsClosing(false);
            if (window.location.pathname !== '/') {
                window.history.pushState({ showLogin: false }, '', '/');
            }
        }, 260);
    };

    return (
        <>
            <Head title={showLogin ? 'Login' : 'Welcome'} />
            <style>{KEYFRAMES}</style>

            {/* ── Layer 0: full-page background (behind everything) ── */}
            <div
                aria-hidden="true"
                style={{
                    position: 'fixed',
                    inset: 0,
                    zIndex: 0,
                    background: 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%)',
                    pointerEvents: 'none',
                }}
            />

            {/* ── Layer 1: Rings (circles) — enter from left / right ── */}

            {/* top-left large */}
            <Ring direction="left"  size="400px" top="-132px" left="-140px" blur="3px" visible={ringsVisible} />
            {/* top-left small */}
            <Ring direction="left"  size="240px" top="74px"   left="70px"   blur="2px" visible={ringsVisible} />
            {/* right */}
            <Ring direction="right" size="220px" top="100px"  right="-110px"           visible={ringsVisible} />
            {/* mid-left */}
            <Ring direction="left"  size="220px" top="300px"  left="-66px"             visible={ringsVisible} />

            {/* ── Layer 2: Scene decor — squares + circles from bottom corners ── */}
            <SceneDecor side="left" decorVisible={decorVisible}>
                {/* squares */}
                <Shape type="square" variant="solid"   active={decorVisible} finalTransform="rotate(-12deg)" style={{ width: 98, height: 98, left: 10,  bottom: 12  }} />
                <Shape type="square" variant="outline" active={decorVisible} finalTransform="rotate(11deg)"  style={{ width: 76, height: 76, left: 92,  bottom: 56  }} />
                <Shape type="square" variant="solid"   active={decorVisible} finalTransform="rotate(23deg)"  style={{ width: 58, height: 58, left: 58,  bottom: 138 }} />
                <Shape type="square" variant="outline" active={decorVisible} finalTransform="rotate(-8deg)"  style={{ width: 88, height: 88, left: 160, bottom: 22  }} />
                <Shape type="square" variant="solid"   active={decorVisible} finalTransform="rotate(17deg)"  style={{ width: 52, height: 52, left: 212, bottom: 118 }} />
                {/* circles */}
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(18deg) scale(1.05)"  style={{ width: 32, height: 32, left: 26,  bottom: 124 }} />
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(-24deg) scale(0.72)" style={{ width: 28, height: 28, left: 138, bottom: 126 }} />
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(32deg) scale(0.85)"  style={{ width: 24, height: 24, left: 108, bottom: 192 }} />
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(12deg) scale(1.1)"   style={{ width: 30, height: 30, left: 186, bottom: 82  }} />
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(-14deg) scale(0.68)" style={{ width: 20, height: 20, left: 222, bottom: 166 }} />
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(42deg) scale(0.9)"   style={{ width: 26, height: 26, left: 78,  bottom: 24  }} />
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(8deg) scale(0.78)"   style={{ width: 22, height: 22, left: 250, bottom: 60  }} />
            </SceneDecor>

            <SceneDecor side="right" decorVisible={decorVisible}>
                {/* squares */}
                <Shape type="square" variant="outline" active={decorVisible} finalTransform="rotate(9deg)"   style={{ width: 94, height: 94, right: 12,  bottom: 18  }} />
                <Shape type="square" variant="solid"   active={decorVisible} finalTransform="rotate(-14deg)" style={{ width: 74, height: 74, right: 112, bottom: 72  }} />
                <Shape type="square" variant="outline" active={decorVisible} finalTransform="rotate(22deg)"  style={{ width: 56, height: 56, right: 78,  bottom: 162 }} />
                <Shape type="square" variant="solid"   active={decorVisible} finalTransform="rotate(13deg)"  style={{ width: 84, height: 84, right: 180, bottom: 24  }} />
                <Shape type="square" variant="outline" active={decorVisible} finalTransform="rotate(-18deg)" style={{ width: 64, height: 64, right: 222, bottom: 112 }} />
                {/* circles */}
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(-16deg) scale(0.92)" style={{ width: 30, height: 30, right: 32,  bottom: 126 }} />
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(28deg) scale(0.76)"  style={{ width: 24, height: 24, right: 126, bottom: 140 }} />
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(-34deg) scale(0.7)"  style={{ width: 22, height: 22, right: 98,  bottom: 204 }} />
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(14deg) scale(1.08)"  style={{ width: 32, height: 32, right: 196, bottom: 92  }} />
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(38deg) scale(0.74)"  style={{ width: 26, height: 26, right: 230, bottom: 154 }} />
                <Shape type="circle" variant="solid"   active={decorVisible} finalTransform="rotate(-10deg) scale(0.88)" style={{ width: 20, height: 20, right: 70,  bottom: 34  }} />
                <Shape type="circle" variant="outline" active={decorVisible} finalTransform="rotate(21deg) scale(0.84)"  style={{ width: 28, height: 28, right: 176, bottom: 14  }} />
            </SceneDecor>

            {/* ── Layer 3: Page content (transparent background) ── */}
            <div
                style={{
                    position: 'relative',
                    zIndex: 3,
                    minHeight: '100vh',
                    fontFamily: '"Figtree", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
                    color: '#0f172a',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'flex-start',
                    padding: '40px 24px 48px',
                    boxSizing: 'border-box',
                }}
            >
                <div style={{ width: '100%', maxWidth: '960px', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>

                    {/* Header */}
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            textAlign: 'center',
                            gap: 14,
                            marginBottom: 4,
                            maxWidth: 660,
                            animation: reduceMotion ? 'none' : 'fadeInDown 0.55s ease both',
                        }}
                    >
                        <div
                            style={{
                                width: 84,
                                height: 84,
                                borderRadius: 22,
                                background: 'linear-gradient(135deg,#fefce8,#fef9c3)',
                                border: '2.5px solid #fbbf24',
                                boxShadow: '0 10px 28px rgba(251,191,36,0.22), 0 4px 12px rgba(99,102,241,0.10)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                overflow: 'hidden',
                            }}
                        >
                            <img src="/images/logo.png" alt="ShweTatar Logo" style={{ width: '100%', height: '100%', objectFit: 'contain', padding: 8 }} />
                        </div>

                        <div>
                            <p
                                style={{
                                    display: 'inline-block',
                                    fontSize: '0.78rem',
                                    fontWeight: 700,
                                    letterSpacing: '0.14em',
                                    textTransform: 'uppercase',
                                    color: '#4f46e5',
                                    background: 'rgba(99,102,241,0.10)',
                                    padding: '5px 14px',
                                    borderRadius: 9999,
                                    margin: '0 0 10px 0',
                                }}
                            >
                                Shwe Tatar • Office Management Software
                            </p>
                            <h1
                                style={{
                                    fontSize: 'clamp(2rem,4.8vw,2.85rem)',
                                    fontWeight: 900,
                                    lineHeight: 1.08,
                                    letterSpacing: '-0.025em',
                                    background: 'linear-gradient(135deg,#0f172a 0%,#334155 55%,#4f46e5 100%)',
                                    WebkitBackgroundClip: 'text',
                                    WebkitTextFillColor: 'transparent',
                                    backgroundClip: 'text',
                                    margin: 0,
                                }}
                            >
                                ShweTatar Workspace
                            </h1>
                        </div>
                    </div>

                    {/* Divider */}
                    <div aria-hidden="true" style={{ width: 60, height: 3, borderRadius: 99, background: 'linear-gradient(90deg,#6366f1,#3b82f6)', margin: '28px auto', animation: 'fadeIn 0.5s 0.15s ease both' }} />

                    {/* Interactive stage: Welcome cards <-> Thrown Login Card */}
                    {!showLogin ? (
                        <div style={{ width: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                            {/* Nav cards */}
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(260px,1fr))', gap: 16, width: '100%', animation: reduceMotion ? 'none' : 'fadeInUp 0.55s 0.2s ease both' }}>
                                {NAV_CARDS.map((card) => <NavCard key={card.title} card={card} />)}
                            </div>

                            {/* Login */}
                            <div style={{ marginTop: 36, animation: reduceMotion ? 'none' : 'fadeInUp 0.55s 0.35s ease both' }}>
                                <a
                                    href="/login"
                                    onClick={openLogin}
                                    style={{
                                        display: 'inline-flex', alignItems: 'center', gap: 8,
                                        background: 'linear-gradient(135deg,#6366f1,#4f46e5)',
                                        color: '#fff', fontWeight: 700, fontSize: '1rem',
                                        padding: '13px 36px', borderRadius: 9999, textDecoration: 'none',
                                        boxShadow: loginHovered ? '0 8px 24px rgba(99,102,241,0.45)' : '0 4px 16px rgba(99,102,241,0.35)',
                                        transform: loginHovered ? 'translateY(-2px) scale(1.03)' : 'none',
                                        transition: 'transform 0.18s ease, box-shadow 0.18s ease',
                                        letterSpacing: '0.01em',
                                    }}
                                    onMouseEnter={() => setLoginHovered(true)}
                                    onMouseLeave={() => setLoginHovered(false)}
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                        <polyline points="10 17 15 12 10 7" />
                                        <line x1="15" y1="12" x2="3" y2="12" />
                                    </svg>
                                    Login
                                </a>
                            </div>
                        </div>
                    ) : (
                        <div style={{ width: '100%', paddingTop: 4 }}>
                            <LoginCard
                                canResetPassword={canResetPassword}
                                status={status}
                                onBack={closeLogin}
                                isClosing={isClosing}
                                reduceMotion={reduceMotion}
                            />
                        </div>
                    )}

                    {/* Footer */}
                    <footer style={{ marginTop: 40, fontSize: '0.82rem', color: '#94a3b8', textAlign: 'center', animation: 'fadeIn 0.5s 0.4s ease both' }}>
                        Invented by <span style={{ color: '#6366f1', fontWeight: 600 }}>IT Department</span> • Shwe Tatar
                    </footer>
                </div>
            </div>
        </>
    );
}
