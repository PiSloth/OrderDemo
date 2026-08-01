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

    return createPortal(
        <div
            ref={containerRef}
            tabIndex={0}
            onKeyDown={handleContainerKeyDown}
            style={{ position: 'fixed', inset: 0, zIndex: 999999, outline: 'none' }}
            className="bg-black/75 backdrop-blur-md flex flex-col items-center justify-center overflow-hidden"
        >
            {/* Header / Toolbar overlay */}
            <div className="absolute top-0 inset-x-0 z-[1000001] px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-b from-black/80 to-transparent pointer-events-none">
                <div className="flex flex-col gap-1 pointer-events-auto max-w-xl">
                    <div className="flex items-center gap-3">
                        <span className="px-3 py-1 rounded-full bg-white/10 border border-white/20 text-white text-xs font-bold shrink-0 shadow-lg">
                            {safeIndex + 1} / {images.length}
                        </span>
                        {photoLabel && (
                            <h3 className="text-white font-bold text-base sm:text-lg drop-shadow-md truncate">
                                {photoLabel}
                            </h3>
                        )}
                    </div>
                </div>

                <div className="flex items-center gap-3 pointer-events-auto self-end sm:self-auto">
                    {/* Zoom / Rotate Controls */}
                    <div className="flex items-center bg-white/10 backdrop-blur-md rounded-xl p-1 border border-white/20 shadow-lg">
                        <button onClick={handleZoomOut} className="p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition" title="Zoom Out (-)">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM7 10h6" />
                            </svg>
                        </button>
                        <div className="w-px h-5 bg-white/20 mx-1"></div>
                        <button onClick={handleZoomIn} className="p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition" title="Zoom In (+)">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6M7 10h6" />
                            </svg>
                        </button>
                        <div className="w-px h-5 bg-white/20 mx-1"></div>
                        <button onClick={handleRotateLeft} className="p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition" title="Rotate Left">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                        </button>
                        <button onClick={handleRotateRight} className="p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition" title="Rotate Right">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                            </svg>
                        </button>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        tabIndex={-1}
                        className="p-2.5 rounded-xl bg-white/10 hover:bg-rose-500/80 border border-white/20 text-white shadow-lg transition focus:outline-none cursor-pointer shrink-0"
                        title="Close photo preview (Esc)"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* Main Image Stage (Click to close, unless clicking image/buttons) */}
            <div className="absolute inset-0 flex items-center justify-center" onClick={onClose}>
                {images.length > 1 && (
                    <button
                        type="button"
                        onClick={handlePrev}
                        tabIndex={-1}
                        className="absolute left-4 sm:left-8 z-[1000001] p-3 sm:p-4 rounded-full bg-white/10 hover:bg-white/30 backdrop-blur text-white border border-white/20 shadow-xl transition focus:outline-none cursor-pointer hover:scale-110 active:scale-95"
                        title="Previous image (←)"
                    >
                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                )}

                <div className="w-full h-full flex items-center justify-center overflow-hidden">
                    <img
                        src={currentImage?.file_url || currentImage?.url || currentImage}
                        alt={photoLabel || `Evidence ${safeIndex + 1}`}
                        className="max-w-full max-h-full object-contain transition-transform duration-300 ease-out select-none shadow-2xl"
                        style={{ transform: `scale(${scale}) rotate(${rotation}deg)` }}
                        draggable={false}
                        onClick={(e) => e.stopPropagation()} // Prevent close on image click
                    />
                </div>

                {images.length > 1 && (
                    <button
                        type="button"
                        onClick={handleNext}
                        tabIndex={-1}
                        className="absolute right-4 sm:right-8 z-[1000001] p-3 sm:p-4 rounded-full bg-white/10 hover:bg-white/30 backdrop-blur text-white border border-white/20 shadow-xl transition focus:outline-none cursor-pointer hover:scale-110 active:scale-95"
                        title="Next image (→)"
                    >
                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                )}
            </div>

            {/* Photo Remark (Floating near bottom) */}
            {photoRemark && (
                <div className="absolute bottom-28 sm:bottom-24 left-1/2 -translate-x-1/2 z-[1000001] w-11/12 max-w-2xl bg-black/60 backdrop-blur-md border border-white/20 rounded-2xl px-6 py-4 text-center shadow-2xl pointer-events-none">
                    <p className="leading-relaxed italic text-white text-sm sm:text-base font-medium drop-shadow-md">
                        "{photoRemark}"
                    </p>
                </div>
            )}

            {/* Thumbnail Carousel Footer */}
            {images.length > 1 && (
                <div className="absolute bottom-0 inset-x-0 z-[1000001] p-4 bg-gradient-to-t from-black/80 to-transparent flex items-center justify-center gap-3 overflow-x-auto no-scrollbar pointer-events-auto">
                    {images.map((img, idx) => (
                        <button
                            key={img.id || idx}
                            type="button"
                            tabIndex={-1}
                            onClick={(e) => {
                                e.stopPropagation();
                                onSelectIndex(idx);
                                if (containerRef.current) containerRef.current.focus();
                            }}
                            className={`relative w-16 h-12 rounded-lg overflow-hidden border-2 transition shrink-0 cursor-pointer shadow-lg ${
                                idx === safeIndex
                                    ? 'border-white scale-110 ring-4 ring-white/30'
                                    : 'border-white/30 opacity-50 hover:opacity-100 hover:border-white/70'
                            }`}
                        >
                            <img
                                src={img.file_url || img.url || img}
                                alt={`Thumbnail ${idx + 1}`}
                                className="w-full h-full object-cover"
                            />
                        </button>
                    ))}
                </div>
            )}
        </div>,
        document.body
    );
}
