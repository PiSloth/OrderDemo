import React, { useEffect } from 'react';
import { createPortal } from 'react-dom';

export default function PhotoCarouselModal({ isOpen, images = [], selectedIndex = 0, onClose, onSelectIndex }) {
    if (!isOpen || !images || images.length === 0 || selectedIndex === null || selectedIndex === undefined) {
        return null;
    }

    const safeIndex = Math.max(0, Math.min(selectedIndex, images.length - 1));
    const currentImage = images[safeIndex] || images[0];

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (!isOpen) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                onClose();
            } else if (e.key === 'ArrowLeft' || e.key === 'Left') {
                e.preventDefault();
                e.stopPropagation();
                const prevIndex = safeIndex === 0 ? images.length - 1 : safeIndex - 1;
                onSelectIndex(prevIndex);
            } else if (e.key === 'ArrowRight' || e.key === 'Right') {
                e.preventDefault();
                e.stopPropagation();
                const nextIndex = safeIndex === images.length - 1 ? 0 : safeIndex + 1;
                onSelectIndex(nextIndex);
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen, safeIndex, images.length, onClose, onSelectIndex]);

    const handlePrev = (e) => {
        if (e) e.stopPropagation();
        const prevIndex = safeIndex === 0 ? images.length - 1 : safeIndex - 1;
        onSelectIndex(prevIndex);
    };

    const handleNext = (e) => {
        if (e) e.stopPropagation();
        const nextIndex = safeIndex === images.length - 1 ? 0 : safeIndex + 1;
        onSelectIndex(nextIndex);
    };

    if (typeof document === 'undefined') return null;

    return createPortal(
        <div className="fixed inset-0 z-[999999] bg-slate-950/95 backdrop-blur-lg flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            {/* Backdrop click listener */}
            <div className="fixed inset-0" onClick={onClose}></div>

            {/* Photo Modal Card Container */}
            <div className="relative w-full max-w-5xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden z-[1000000] flex flex-col max-h-[92vh]">
                {/* Header Bar */}
                <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/90 select-none">
                    <div className="flex items-center gap-3">
                        <span className="px-3.5 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold">
                            Photo {safeIndex + 1} of {images.length}
                        </span>
                        <span className="text-xs text-slate-400 font-medium hidden sm:inline">
                            Use ← → arrow keys to navigate carousel
                        </span>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition focus:outline-none cursor-pointer"
                        title="Close photo preview (Esc)"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Main Image Stage */}
                <div className="relative flex-1 bg-black/70 flex items-center justify-center p-4 min-h-[350px] sm:min-h-[480px]">
                    {/* Previous Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handlePrev}
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
                        alt={`Evidence ${safeIndex + 1}`}
                        className="max-w-full max-h-[60vh] sm:max-h-[68vh] object-contain rounded-xl shadow-2xl transition-all duration-300 select-none"
                    />

                    {/* Next Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handleNext}
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
                                onClick={(e) => {
                                    e.stopPropagation();
                                    onSelectIndex(idx);
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
