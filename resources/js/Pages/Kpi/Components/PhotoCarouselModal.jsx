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
            className="bg-slate-950/90 backdrop-blur-xl flex flex-col md:flex-row overflow-hidden"
        >
            {/* Left Sidebar for Details */}
            <div className="w-full md:w-80 lg:w-96 bg-black/40 border-b md:border-b-0 md:border-r border-white/10 p-6 flex flex-col gap-8 shrink-0 z-[1000001] pointer-events-auto overflow-y-auto max-h-[30vh] md:max-h-full">
                <div className="flex items-center justify-between">
                    <span className="px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 text-white text-xs font-bold shrink-0 shadow-lg tracking-wide">
                        Photo {safeIndex + 1} of {images.length}
                    </span>
                    {/* Mobile close button */}
                    <button
                        type="button"
                        onClick={onClose}
                        tabIndex={-1}
                        className="md:hidden p-2 rounded-xl bg-white/10 hover:bg-rose-500/80 border border-white/20 text-white shadow-lg transition focus:outline-none cursor-pointer shrink-0"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="flex flex-col gap-6 mt-2">
                    {photoLabel && (
                        <div className="space-y-2">
                            <h3 className="text-white/50 text-xs font-bold uppercase tracking-widest">Title</h3>
                            <p className="text-white font-semibold text-lg leading-snug">{photoLabel}</p>
                        </div>
                    )}

                    {photoRemark && (
                        <div className="space-y-2">
                            <h3 className="text-white/50 text-xs font-bold uppercase tracking-widest">Remark</h3>
                            <div className="p-4 bg-white/5 rounded-2xl border border-white/10 text-white/90 text-sm italic leading-relaxed shadow-inner">
                                "{photoRemark}"
                            </div>
                        </div>
                    )}

                    {(!photoLabel && !photoRemark) && (
                        <div className="flex flex-col items-center justify-center py-10 opacity-40">
                            <svg className="w-12 h-12 mb-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p className="text-white text-xs font-medium uppercase tracking-widest">No Details Provided</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Main Content Area (Photo Stage) */}
            <div className="flex-1 flex flex-col relative overflow-hidden" onClick={onClose}>
                {/* Top Right Close Button (Desktop) */}
                <div className="hidden md:block absolute top-6 right-6 z-[1000002]">
                    <button
                        type="button"
                        onClick={onClose}
                        tabIndex={-1}
                        className="p-3 rounded-2xl bg-white/10 hover:bg-rose-500/80 border border-white/20 text-white shadow-2xl backdrop-blur-md transition focus:outline-none cursor-pointer hover:scale-105 active:scale-95"
                        title="Close photo preview (Esc)"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Vertical Toolbar (Zoom/Rotate) on the Right Side */}
                <div className="absolute right-4 sm:right-6 top-1/2 -translate-y-1/2 z-[1000002] flex flex-col gap-2 pointer-events-auto bg-white/10 backdrop-blur-xl p-2 rounded-2xl border border-white/20 shadow-2xl">
                    <button onClick={handleZoomIn} className="p-3 rounded-xl text-white/80 hover:text-white hover:bg-white/20 transition cursor-pointer" title="Zoom In (+)">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6M7 10h6" />
                        </svg>
                    </button>
                    <button onClick={handleZoomOut} className="p-3 rounded-xl text-white/80 hover:text-white hover:bg-white/20 transition cursor-pointer" title="Zoom Out (-)">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM7 10h6" />
                        </svg>
                    </button>
                    <div className="w-full h-px bg-white/20 my-1"></div>
                    <button onClick={handleRotateLeft} className="p-3 rounded-xl text-white/80 hover:text-white hover:bg-white/20 transition cursor-pointer" title="Rotate Left">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </button>
                    <button onClick={handleRotateRight} className="p-3 rounded-xl text-white/80 hover:text-white hover:bg-white/20 transition cursor-pointer" title="Rotate Right">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                        </svg>
                    </button>
                </div>

                {/* Main Image Stage */}
                <div className="flex-1 relative flex items-center justify-center p-4">
                    {/* Previous Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handlePrev}
                            tabIndex={-1}
                            className="absolute left-4 sm:left-8 z-[1000001] p-3 sm:p-4 rounded-full bg-white/10 hover:bg-white/30 backdrop-blur text-white border border-white/20 shadow-xl transition focus:outline-none cursor-pointer hover:scale-110 active:scale-95"
                            title="Previous image (←)"
                        >
                            <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    )}

                    {/* Image */}
                    <div className="w-full h-full flex items-center justify-center overflow-hidden">
                        <img
                            src={currentImage?.file_url || currentImage?.url || currentImage}
                            alt={photoLabel || `Evidence ${safeIndex + 1}`}
                            className="max-w-full max-h-full object-contain transition-transform duration-300 ease-out select-none shadow-2xl drop-shadow-2xl"
                            style={{ transform: `scale(${scale}) rotate(${rotation}deg)` }}
                            draggable={false}
                            onClick={(e) => e.stopPropagation()} // Prevent close on image click
                        />
                    </div>

                    {/* Next Button (shifted left slightly to avoid toolbar) */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handleNext}
                            tabIndex={-1}
                            className="absolute right-20 sm:right-28 z-[1000001] p-3 sm:p-4 rounded-full bg-white/10 hover:bg-white/30 backdrop-blur text-white border border-white/20 shadow-xl transition focus:outline-none cursor-pointer hover:scale-110 active:scale-95"
                            title="Next image (→)"
                        >
                            <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    )}
                </div>

                {/* Thumbnail Carousel Footer */}
                {images.length > 1 && (
                    <div className="shrink-0 h-24 bg-black/40 border-t border-white/10 flex items-center justify-center gap-3 px-6 overflow-x-auto no-scrollbar pointer-events-auto">
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
                                        ? 'border-white scale-110 ring-2 ring-white/50 shadow-white/20'
                                        : 'border-white/20 opacity-50 hover:opacity-100 hover:border-white/50'
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
            </div>
        </div>,
        document.body
    );
}
