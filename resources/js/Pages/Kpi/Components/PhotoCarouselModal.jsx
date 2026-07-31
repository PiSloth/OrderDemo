import React, { useEffect } from 'react';
import { createPortal } from 'react-dom';

export default function PhotoCarouselModal({ isOpen, images = [], selectedIndex = 0, onClose, onSelectIndex }) {
    if (!isOpen || images.length === 0 || selectedIndex === null || selectedIndex === undefined) {
        return null;
    }

    const currentImage = images[selectedIndex] || images[0];

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                onClose();
            } else if (e.key === 'ArrowLeft') {
                handlePrev();
            } else if (e.key === 'ArrowRight') {
                handleNext();
            }
        };

        if (isOpen) {
            window.addEventListener('keydown', handleKeyDown);
        }

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen, selectedIndex, images.length]);

    const handlePrev = () => {
        const newIndex = selectedIndex === 0 ? images.length - 1 : selectedIndex - 1;
        onSelectIndex(newIndex);
    };

    const handleNext = () => {
        const newIndex = selectedIndex === images.length - 1 ? 0 : selectedIndex + 1;
        onSelectIndex(newIndex);
    };

    if (typeof document === 'undefined') return null;

    return createPortal(
        <div className="fixed inset-0 z-[99999] bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            {/* Backdrop click listener */}
            <div className="fixed inset-0" onClick={onClose}></div>

            {/* Photo Modal Card Container */}
            <div className="relative w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden z-[100000] flex flex-col max-h-[92vh]">
                {/* Header Bar */}
                <div className="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/90">
                    <div className="flex items-center gap-3">
                        <span className="px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold">
                            Photo {selectedIndex + 1} of {images.length}
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

                {/* Main Image Display Stage */}
                <div className="relative flex-1 bg-black/60 flex items-center justify-center p-4 min-h-[350px] sm:min-h-[480px]">
                    {/* Previous Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handlePrev}
                            className="absolute left-4 z-10 p-3 rounded-full bg-slate-900/80 hover:bg-indigo-600 text-white border border-slate-700/80 shadow-lg transition duration-200 focus:outline-none cursor-pointer"
                            title="Previous image (←)"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    )}

                    {/* Main Photo */}
                    <img
                        src={currentImage.file_url || currentImage.url || currentImage}
                        alt={`Evidence ${selectedIndex + 1}`}
                        className="max-w-full max-h-[60vh] sm:max-h-[68vh] object-contain rounded-xl shadow-2xl transition-all duration-300"
                    />

                    {/* Next Button */}
                    {images.length > 1 && (
                        <button
                            type="button"
                            onClick={handleNext}
                            className="absolute right-4 z-10 p-3 rounded-full bg-slate-900/80 hover:bg-indigo-600 text-white border border-slate-700/80 shadow-lg transition duration-200 focus:outline-none cursor-pointer"
                            title="Next image (→)"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    )}
                </div>

                {/* Thumbnail Carousel Footer Strip */}
                {images.length > 1 && (
                    <div className="p-3 bg-slate-950 border-t border-slate-800/80 flex items-center justify-center gap-2 overflow-x-auto no-scrollbar">
                        {images.map((img, idx) => (
                            <button
                                key={img.id || idx}
                                type="button"
                                onClick={() => onSelectIndex(idx)}
                                className={`relative w-14 h-10 rounded-lg overflow-hidden border-2 transition shrink-0 cursor-pointer ${
                                    idx === selectedIndex
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
