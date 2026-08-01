import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';

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
    { href: '#', icon: '📋', iconBg: 'linear-gradient(135deg,#e0e7ff,#c7d2fe)', title: 'View Order Histories', desc: 'Browse all past and recent orders.' },
    { href: '#', icon: '✏️', iconBg: 'linear-gradient(135deg,#d1fae5,#a7f3d0)', title: 'Create New Order',     desc: 'Place a new order in the system.' },
    { href: '#', icon: '🔍', iconBg: 'linear-gradient(135deg,#fef3c7,#fde68a)', title: 'Order Status',         desc: 'Track pending and arrived orders.' },
    { href: '#', icon: '📊', iconBg: 'linear-gradient(135deg,#fce7f3,#fbcfe8)', title: 'Branch Performance',   desc: 'Analyze how each branch performs.' },
    { href: '#', icon: '📈', iconBg: 'linear-gradient(135deg,#e0f2fe,#bae6fd)', title: 'Sales Rate',           desc: 'Visualize sales trends over time.' },
    { href: '#', icon: '💎', iconBg: 'linear-gradient(135deg,#f3e8ff,#e9d5ff)', title: 'Top Products',         desc: 'View most demanded jewelry pieces.' },
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
   MAIN PAGE
───────────────────────────────────────────────────────────── */
export default function Welcome() {
    const reduceMotion =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const [ringsVisible,  setRingsVisible]  = useState(reduceMotion);
    const [decorVisible,  setDecorVisible]  = useState(reduceMotion);
    const [loginHovered,  setLoginHovered]  = useState(false);

    useEffect(() => {
        if (reduceMotion) return;
        const t1 = setTimeout(() => setRingsVisible(true),  100);   // mirrors original
        const t2 = setTimeout(() => setDecorVisible(true), 1500);   // mirrors original
        return () => { clearTimeout(t1); clearTimeout(t2); };
    }, []);

    return (
        <>
            <Head title="Welcome" />
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
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 28, flexWrap: 'wrap', marginBottom: 12, animation: 'fadeInDown 0.55s ease both' }}>
                        <div style={{ width: 100, height: 100, borderRadius: 20, background: 'linear-gradient(135deg,#fefce8,#fef9c3)', border: '3px solid #fbbf24', boxShadow: '0 8px 24px rgba(251,191,36,0.25), 0 2px 8px rgba(0,0,0,0.08)', display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden', flexShrink: 0 }}>
                            <img src="/images/logo.png" alt="JewelTrack Logo" style={{ width: '100%', height: '100%', objectFit: 'contain', padding: 8 }} />
                        </div>
                        <div>
                            <p style={{ fontSize: 'clamp(0.85rem,2vw,0.95rem)', fontWeight: 600, letterSpacing: '0.12em', textTransform: 'uppercase', color: '#6366f1', marginBottom: 4, margin: '0 0 4px 0' }}>
                                Welcome to
                            </p>
                            <h1 style={{ fontSize: 'clamp(2rem,5vw,3rem)', fontWeight: 900, lineHeight: 1, letterSpacing: '-0.02em', background: 'linear-gradient(135deg,#1e293b 0%,#334155 60%,#6366f1 100%)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent', backgroundClip: 'text', margin: 0 }}>
                                JewelTrack
                            </h1>
                            <p style={{ fontSize: '0.97rem', color: '#64748b', marginTop: 6, fontWeight: 400, margin: '6px 0 0 0' }}>
                                Manage luxurious jewelry orders with elegance and efficiency.
                            </p>
                        </div>
                    </div>

                    {/* Divider */}
                    <div aria-hidden="true" style={{ width: 60, height: 3, borderRadius: 99, background: 'linear-gradient(90deg,#6366f1,#3b82f6)', margin: '28px auto', animation: 'fadeIn 0.5s 0.15s ease both' }} />

                    {/* Nav cards */}
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(260px,1fr))', gap: 16, width: '100%', animation: 'fadeInUp 0.55s 0.2s ease both' }}>
                        {NAV_CARDS.map((card) => <NavCard key={card.title} card={card} />)}
                    </div>

                    {/* Login */}
                    <div style={{ marginTop: 36, animation: 'fadeInUp 0.55s 0.35s ease both' }}>
                        <a
                            href="/login"
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

                    {/* Footer */}
                    <footer style={{ marginTop: 40, fontSize: '0.82rem', color: '#94a3b8', textAlign: 'center', animation: 'fadeIn 0.5s 0.4s ease both' }}>
                        Invented by <span style={{ color: '#6366f1', fontWeight: 600 }}>IT Department</span> • Shwe Tatar
                    </footer>
                </div>
            </div>
        </>
    );
}
