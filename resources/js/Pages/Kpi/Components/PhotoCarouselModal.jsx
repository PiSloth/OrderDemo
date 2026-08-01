import React, { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

export default function PhotoCarouselModal({ isOpen, images = [], selectedIndex = 0, onClose, onSelectIndex }) {
    const containerRef = useRef(null);

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
            style={{ position: 'fixed', inset: 0, zIndex: 999999, backgroundColor: 'rgba(2,6,23,0.95)', outline: 'none' }}
            className="backdrop-blur-lg flex items-center justify-center p-4 sm:p-6"
        >
            {/* Backdrop click listener */}
            <div style={{ position: 'fixed', inset: 0 }} onClick={onClose}></div>

            {/* Photo Modal Card Container */}
            <div style={{ position: 'relative', zIndex: 1000000 }} className="w-full max-w-5xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
                {/* Header Bar */}
                <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/90 select-none">
                    <div className="flex items-center gap-3 min-w-0">
                        <span className="px-3.5 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold shrink-0">
                            Photo {safeIndex + 1} of {images.length}
                        </span>
                        {photoLabel && (
                            <span className="text-sm font-semibold text-white truncate max-w-sm sm:max-w-md">
                                {photoLabel}
                            </span>
                        )}
                        <span className="text-xs text-slate-400 font-medium hidden md:inline shrink-0">
                            Use ← → arrow keys to navigate
                        </span>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        tabIndex={-1}
                        className="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition focus:outline-none cursor-pointer shrink-0"
                        title="Close photo preview (Esc)"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Main Image Stage */}
                <div className="relative flex-1 bg-black/70 flex flex-col items-center justify-center p-4 min-h-[350px] sm:min-h-[480px]">
                    {/* Previous Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handlePrev}
                            tabIndex={-1}
                            className="absolute left-4 z-20 p-3.5 rounded-full bg-slate-900/90 hover:bg-indigo-600 text-white border border-slate-700 shadow-xl transition duration-200 focus:outline-none cursor-pointer hover:scale-110 active:scale-95"
                            title="Previous image (←)"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    )}

                    {/* Main Image */}
                    <img
                        src={currentImage?.file_url || currentImage?.url || currentImage}
                        alt={photoLabel || `Evidence ${safeIndex + 1}`}
                        className="max-w-full max-h-[55vh] sm:max-h-[62vh] object-contain rounded-xl shadow-2xl transition-all duration-300 select-none"
                        draggable={false}
                    />

                    {/* Photo Label & Remark Caption Bar */}
                    {(photoLabel || photoRemark) && (
                        <div className="mt-3 w-full max-w-2xl bg-slate-900/90 backdrop-blur-md border border-slate-800/90 rounded-2xl px-4 py-3 text-center shadow-2xl space-y-1 z-20">
                            {photoLabel && (
                                <div className="flex items-center justify-center gap-2">
                                    <span className="px-2.5 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold tracking-wide">
                                        {photoLabel}
                                    </span>
                                </div>
                            )}
                            {photoRemark && (
                                <div className="flex items-start justify-center gap-2 text-xs text-slate-200">
                                    <svg className="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    <p className="leading-relaxed italic max-w-xl text-center">
                                        "{photoRemark}"
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Next Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handleNext}
                            tabIndex={-1}
                            className="absolute right-4 z-20 p-3.5 rounded-full bg-slate-900/90 hover:bg-indigo-600 text-white border border-slate-700 shadow-xl transition duration-200 focus:outline-none cursor-pointer hover:scale-110 active:scale-95"
                            title="Next image (→)"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    )}
                </div>

                {/* Thumbnail Carousel Footer Bar */}
                {images.length > 1 && (
                    <div className="p-3 bg-slate-950 border-t border-slate-800/80 flex items-center justify-center gap-2 overflow-x-auto no-scrollbar">
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
                                className={`relative w-14 h-10 rounded-lg overflow-hidden border-2 transition shrink-0 cursor-pointer ${
                                    idx === safeIndex
                                        ? 'border-indigo-500 scale-105 shadow-md ring-2 ring-indigo-500/40'
                                        : 'border-slate-800 opacity-50 hover:opacity-100 hover:border-slate-600'
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
