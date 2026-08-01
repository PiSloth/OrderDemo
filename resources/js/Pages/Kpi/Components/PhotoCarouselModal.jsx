import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

export default function PhotoCarouselModal({ isOpen, images = [], selectedIndex = 0, onClose, onSelectIndex }) {
    const containerRef = useRef(null);
    const [scale, setScale] = useState(1);
    const [rotation, setRotation] = useState(0);

    const safeIndex = (isOpen && images && images.length > 0 && selectedIndex !== null && selectedIndex !== undefined)
        ? Math.max(0, Math.min(selectedIndex, images.length - 1))
        : 0;

    const currentImage = (isOpen && images && images.length > 0) ? (images[safeIndex] || images[0]) : null;
    const photoLabel = (typeof currentImage === 'object' && currentImage !== null)
        ? (currentImage.title || currentImage.label || null)
        : null;
    const photoRemark = (typeof currentImage === 'object' && currentImage !== null)
        ? (currentImage.remark || currentImage.remarks || currentImage.description || null)
        : null;

    // Reset zoom and rotation when image changes
    useEffect(() => {
        setScale(1);
        setRotation(0);
    }, [safeIndex]);

    const handleZoomIn = (e) => { e.stopPropagation(); setScale(s => Math.min(s + 0.25, 4)); };
    const handleZoomOut = (e) => { e.stopPropagation(); setScale(s => Math.max(s - 0.25, 0.25)); };
    const handleRotateLeft = (e) => { e.stopPropagation(); setRotation(r => r - 90); };
    const handleRotateRight = (e) => { e.stopPropagation(); setRotation(r => r + 90); };

    // Auto-focus the container div on open so keyboard events are captured reliably
    useEffect(() => {
        if (isOpen && containerRef.current) {
            containerRef.current.focus();
        }
    }, [isOpen]);

    // Handle keydown directly on the focused container
    const handleContainerKeyDown = (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            onClose();
        } else if (e.key === 'ArrowLeft' || e.key === 'Left') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const prevIndex = safeIndex === 0 ? images.length - 1 : safeIndex - 1;
            onSelectIndex(prevIndex);
        } else if (e.key === 'ArrowRight' || e.key === 'Right') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const nextIndex = safeIndex === images.length - 1 ? 0 : safeIndex + 1;
            onSelectIndex(nextIndex);
        } else if (e.key === '+' || e.key === '=') {
            handleZoomIn(e);
        } else if (e.key === '-') {
            handleZoomOut(e);
        }
    };

    // Also keep a window-level listener as fallback when focus escapes
    useEffect(() => {
        if (!isOpen) return;

        const handleWindowKeyDown = (e) => {
            if (e.key === 'ArrowLeft' || e.key === 'Left') {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                onSelectIndex(safeIndex === 0 ? images.length - 1 : safeIndex - 1);
            } else if (e.key === 'ArrowRight' || e.key === 'Right') {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                onSelectIndex(safeIndex === images.length - 1 ? 0 : safeIndex + 1);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                onClose();
            }
        };

        window.addEventListener('keydown', handleWindowKeyDown, true); // capture phase
        return () => {
            window.removeEventListener('keydown', handleWindowKeyDown, true);
        };
    }, [isOpen, safeIndex, images.length, onClose, onSelectIndex]);

    const handlePrev = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const newIndex = safeIndex === 0 ? images.length - 1 : safeIndex - 1;
        onSelectIndex(newIndex);
        // Re-focus container after button click
        if (containerRef.current) containerRef.current.focus();
    };

    const handleNext = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const newIndex = safeIndex === images.length - 1 ? 0 : safeIndex + 1;
        onSelectIndex(newIndex);
        // Re-focus container after button click
        if (containerRef.current) containerRef.current.focus();
    };

    if (!isOpen || !images || images.length === 0 || selectedIndex === null || selectedIndex === undefined) {
        return null;
    }

    if (typeof document === 'undefined') return null;

    const imageSrc = typeof currentImage === 'string'
        ? currentImage
        : (currentImage?.file_url || currentImage?.url || currentImage?.path || currentImage?.src || '');

    return createPortal(
        <div
            ref={containerRef}
            tabIndex={0}
            onKeyDown={handleContainerKeyDown}
            style={{
                position: 'fixed',
                inset: 0,
                zIndex: 999999,
                outline: 'none',
                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                backdropFilter: 'blur(16px)',
                WebkitBackdropFilter: 'blur(16px)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                overflow: 'hidden'
            }}
        >
            {/* Backdrop click listener */}
            <div style={{ position: 'absolute', inset: 0, zIndex: 1000000 }} onClick={onClose}></div>

            {/* Left Floating Info Panel (Title & Remark) */}
            <div
                style={{
                    position: 'absolute',
                    top: '1.5rem',
                    left: '1.5rem',
                    zIndex: 1000002,
                    width: '300px',
                    maxWidth: 'calc(100vw - 3rem)',
                    maxHeight: 'calc(100vh - 12rem)',
                    backgroundColor: 'rgba(0, 0, 0, 0.75)',
                    backdropFilter: 'blur(16px)',
                    WebkitBackdropFilter: 'blur(16px)',
                    border: '1px solid rgba(255, 255, 255, 0.15)',
                    borderRadius: '1.25rem',
                    padding: '1.25rem',
                    color: '#ffffff',
                    boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.5)',
                    overflowY: 'auto',
                    pointerEvents: 'auto'
                }}
            >
                <div style={{ display: 'flex', itemsCenter: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
                    <span style={{
                        padding: '0.35rem 0.85rem',
                        borderRadius: '9999px',
                        backgroundColor: 'rgba(255, 255, 255, 0.15)',
                        border: '1px solid rgba(255, 255, 255, 0.25)',
                        color: '#ffffff',
                        fontSize: '0.75rem',
                        fontWeight: '700',
                        letterSpacing: '0.05em'
                    }}>
                        Photo {safeIndex + 1} of {images.length}
                    </span>

                    {/* Mobile close button */}
                    <button
                        type="button"
                        onClick={onClose}
                        tabIndex={-1}
                        style={{
                            display: 'none',
                            padding: '0.5rem',
                            borderRadius: '0.75rem',
                            backgroundColor: 'rgba(255, 255, 255, 0.1)',
                            border: '1px solid rgba(255, 255, 255, 0.2)',
                            color: '#ffffff',
                            cursor: 'pointer'
                        }}
                        className="sm:hidden"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    {photoLabel && (
                        <div>
                            <div style={{ fontSize: '0.7rem', fontWeight: '700', textTransform: 'uppercase', letterSpacing: '0.1em', color: 'rgba(255, 255, 255, 0.5)', marginBottom: '0.25rem' }}>
                                Title
                            </div>
                            <div style={{ fontSize: '1.1rem', fontWeight: '600', color: '#ffffff', wordBreak: 'break-word' }}>
                                {photoLabel}
                            </div>
                        </div>
                    )}

                    {photoRemark && (
                        <div>
                            <div style={{ fontSize: '0.7rem', fontWeight: '700', textTransform: 'uppercase', letterSpacing: '0.1em', color: 'rgba(255, 255, 255, 0.5)', marginBottom: '0.25rem' }}>
                                Remark
                            </div>
                            <div style={{
                                padding: '0.85rem',
                                borderRadius: '0.85rem',
                                backgroundColor: 'rgba(255, 255, 255, 0.08)',
                                border: '1px solid rgba(255, 255, 255, 0.12)',
                                color: '#f1f5f9',
                                fontSize: '0.875rem',
                                fontStyle: 'italic',
                                lineHeight: '1.5',
                                wordBreak: 'break-word'
                            }}>
                                "{photoRemark}"
                            </div>
                        </div>
                    )}

                    {(!photoLabel && !photoRemark) && (
                        <div style={{ padding: '1.5rem 0', textAlign: 'center', color: 'rgba(255, 255, 255, 0.4)', fontSize: '0.75rem', textTransform: 'uppercase', letterSpacing: '0.1em' }}>
                            No Details Provided
                        </div>
                    )}
                </div>
            </div>

            {/* Top Right Close Button */}
            <div style={{ position: 'absolute', top: '1.5rem', right: '1.5rem', zIndex: 1000003, pointerEvents: 'auto' }}>
                <button
                    type="button"
                    onClick={onClose}
                    tabIndex={-1}
                    style={{
                        padding: '0.75rem',
                        borderRadius: '1rem',
                        backgroundColor: 'rgba(0, 0, 0, 0.65)',
                        backdropFilter: 'blur(12px)',
                        border: '1px solid rgba(255, 255, 255, 0.2)',
                        color: '#ffffff',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.5)'
                    }}
                    title="Close photo preview (Esc)"
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {/* Right Vertical Zoom / Rotate Toolbar */}
            <div
                style={{
                    position: 'absolute',
                    right: '1.5rem',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    zIndex: 1000003,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '0.5rem',
                    backgroundColor: 'rgba(0, 0, 0, 0.65)',
                    backdropFilter: 'blur(12px)',
                    border: '1px solid rgba(255, 255, 255, 0.2)',
                    borderRadius: '1rem',
                    padding: '0.5rem',
                    pointerEvents: 'auto',
                    boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.5)'
                }}
            >
                <button
                    onClick={handleZoomIn}
                    style={{ padding: '0.75rem', borderRadius: '0.75rem', color: '#ffffff', backgroundColor: 'transparent', border: 'none', cursor: 'pointer' }}
                    title="Zoom In (+)"
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6M7 10h6" />
                    </svg>
                </button>
                <button
                    onClick={handleZoomOut}
                    style={{ padding: '0.75rem', borderRadius: '0.75rem', color: '#ffffff', backgroundColor: 'transparent', border: 'none', cursor: 'pointer' }}
                    title="Zoom Out (-)"
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM7 10h6" />
                    </svg>
                </button>
                <div style={{ width: '100%', height: '1px', backgroundColor: 'rgba(255, 255, 255, 0.2)', margin: '0.25rem 0' }}></div>
                <button
                    onClick={handleRotateLeft}
                    style={{ padding: '0.75rem', borderRadius: '0.75rem', color: '#ffffff', backgroundColor: 'transparent', border: 'none', cursor: 'pointer' }}
                    title="Rotate Left"
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </button>
                <button
                    onClick={handleRotateRight}
                    style={{ padding: '0.75rem', borderRadius: '0.75rem', color: '#ffffff', backgroundColor: 'transparent', border: 'none', cursor: 'pointer' }}
                    title="Rotate Right"
                >
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                    </svg>
                </button>
            </div>

            {/* Main Stage (Image Display) */}
            <div
                style={{
                    position: 'relative',
                    width: '100vw',
                    height: '100vh',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '2rem',
                    zIndex: 1000001
                }}
            >
                {/* Previous Button */}
                {images.length > 1 && (
                    <button
                        type="button"
                        onClick={handlePrev}
                        tabIndex={-1}
                        style={{
                            position: 'absolute',
                            left: '21rem',
                            zIndex: 1000003,
                            padding: '1rem',
                            borderRadius: '9999px',
                            backgroundColor: 'rgba(0, 0, 0, 0.65)',
                            backdropFilter: 'blur(12px)',
                            border: '1px solid rgba(255, 255, 255, 0.2)',
                            color: '#ffffff',
                            cursor: 'pointer',
                            pointerEvents: 'auto',
                            boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.5)'
                        }}
                        title="Previous image (←)"
                    >
                        <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                )}

                {/* The Image */}
                {imageSrc ? (
                    <img
                        src={imageSrc}
                        alt={photoLabel || `Evidence ${safeIndex + 1}`}
                        style={{
                            maxWidth: 'calc(100vw - 26rem)',
                            maxHeight: 'calc(100vh - 10rem)',
                            width: 'auto',
                            height: 'auto',
                            objectFit: 'contain',
                            borderRadius: '0.75rem',
                            boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.7)',
                            transform: `scale(${scale}) rotate(${rotation}deg)`,
                            transition: 'transform 0.2s ease-out',
                            pointerEvents: 'auto'
                        }}
                        draggable={false}
                        onClick={(e) => e.stopPropagation()}
                    />
                ) : (
                    <div style={{ color: '#ffffff', fontSize: '1rem', opacity: 0.6 }}>
                        No Image Available
                    </div>
                )}

                {/* Next Button */}
                {images.length > 1 && (
                    <button
                        type="button"
                        onClick={handleNext}
                        tabIndex={-1}
                        style={{
                            position: 'absolute',
                            right: '6rem',
                            zIndex: 1000003,
                            padding: '1rem',
                            borderRadius: '9999px',
                            backgroundColor: 'rgba(0, 0, 0, 0.65)',
                            backdropFilter: 'blur(12px)',
                            border: '1px solid rgba(255, 255, 255, 0.2)',
                            color: '#ffffff',
                            cursor: 'pointer',
                            pointerEvents: 'auto',
                            boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.5)'
                        }}
                        title="Next image (→)"
                    >
                        <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                )}
            </div>

            {/* Bottom Thumbnail Carousel Footer */}
            {images.length > 1 && (
                <div
                    style={{
                        position: 'absolute',
                        bottom: '1.25rem',
                        left: '50%',
                        transform: 'translateX(-50%)',
                        zIndex: 1000003,
                        display: 'flex',
                        alignItems: 'center',
                        gap: '0.75rem',
                        padding: '0.5rem 1rem',
                        backgroundColor: 'rgba(0, 0, 0, 0.75)',
                        backdropFilter: 'blur(16px)',
                        border: '1px solid rgba(255, 255, 255, 0.15)',
                        borderRadius: '1.25rem',
                        maxWidth: '90vw',
                        overflowX: 'auto',
                        pointerEvents: 'auto',
                        boxShadow: '0 15px 25px -5px rgba(0, 0, 0, 0.6)'
                    }}
                >
                    {images.map((img, idx) => {
                        const thumbSrc = typeof img === 'string' ? img : (img?.file_url || img?.url || img?.path || img?.src || '');
                        const isSelected = idx === safeIndex;
                        return (
                            <button
                                key={img.id || idx}
                                type="button"
                                tabIndex={-1}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    onSelectIndex(idx);
                                    if (containerRef.current) containerRef.current.focus();
                                }}
                                style={{
                                    position: 'relative',
                                    width: '4rem',
                                    height: '3rem',
                                    borderRadius: '0.6rem',
                                    overflow: 'hidden',
                                    border: isSelected ? '2px solid #ffffff' : '1px solid rgba(255, 255, 255, 0.2)',
                                    opacity: isSelected ? 1 : 0.5,
                                    transform: isSelected ? 'scale(1.1)' : 'scale(1)',
                                    transition: 'all 0.2s ease-in-out',
                                    cursor: 'pointer',
                                    flexShrink: 0
                                }}
                            >
                                <img
                                    src={thumbSrc}
                                    alt={`Thumbnail ${idx + 1}`}
                                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                />
                            </button>
                        );
                    })}
                </div>
            )}
        </div>,
        document.body
    );
}
