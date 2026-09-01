import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';

export default function ItSatisfactionModal() {
    const { itSatisfactionSurvey = null, auth = {} } = usePage().props;
    const user = auth?.user;

    const [survey, setSurvey] = useState(null);
    const [rating, setRating] = useState(0);
    const [hoverRating, setHoverRating] = useState(0);
    const [feedback, setFeedback] = useState('');
    const [aspects, setAspects] = useState({
        speed: 0,
        helpfulness: 0,
        stability: 0,
    });
    const [submitting, setSubmitting] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const [validationError, setValidationError] = useState('');

    // Check if an active survey is provided via Inertia props or fallback API check
    useEffect(() => {
        if (itSatisfactionSurvey) {
            setSurvey(itSatisfactionSurvey);
        } else if (user) {
            // Optional fallback fetch to ensure immediate reaction if survey was created
            axios.get('/api/it-satisfaction/active-survey')
                .then(res => {
                    if (res.data && res.data.survey) {
                        setSurvey(res.data.survey);
                    }
                })
                .catch(() => { });
        }
    }, [itSatisfactionSurvey, user]);

    // Prevent closing with ESC key - strictly mandatory until submitted
    useEffect(() => {
        if (!survey || submitted) return;

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
            }
        };

        window.addEventListener('keydown', handleKeyDown, { capture: true });
        return () => window.removeEventListener('keydown', handleKeyDown, { capture: true });
    }, [survey, submitted]);

    // Prevent showing to IT department members
    if (user?.is_it_department) {
        return null;
    }

    if (!survey || submitted) {
        return null;
    }

    const ratingOptions = [
        { score: 1, label: 'Very Dissatisfied', emoji: '😠', color: 'from-rose-500 to-red-600', ringColor: 'ring-rose-500', bgColor: 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 border-rose-300 dark:border-rose-800' },
        { score: 2, label: 'Dissatisfied', emoji: '🙁', color: 'from-orange-500 to-amber-600', ringColor: 'ring-orange-500', bgColor: 'bg-orange-50 dark:bg-orange-950/40 text-orange-600 border-orange-300 dark:border-orange-800' },
        { score: 3, label: 'Neutral', emoji: '😐', color: 'from-amber-400 to-yellow-500', ringColor: 'ring-amber-500', bgColor: 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 border-amber-300 dark:border-amber-800' },
        { score: 4, label: 'Satisfied', emoji: '🙂', color: 'from-emerald-400 to-teal-500', ringColor: 'ring-emerald-500', bgColor: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border-emerald-300 dark:border-emerald-800' },
        { score: 5, label: 'Very Satisfied', emoji: '🤩', color: 'from-blue-500 to-indigo-600', ringColor: 'ring-indigo-500', bgColor: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 border-indigo-300 dark:border-indigo-800' },
    ];

    const currentRatingInfo = ratingOptions.find(o => o.score === (hoverRating || rating));
    const isLowScore = rating > 0 && rating < 3;

    const handleRatingSelect = (score) => {
        setRating(score);
        setValidationError('');
    };

    const handleAspectChange = (key, val) => {
        setAspects(prev => ({
            ...prev,
            [key]: val,
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        // Strict validation: Must select a rating score
        if (!rating || rating < 1 || rating > 5) {
            setValidationError('Please select a satisfaction rating from 1 to 5.');
            return;
        }

        // Strict validation: Under 3 (1 or 2) requires feedback
        if (isLowScore && (!feedback || feedback.trim().length < 5)) {
            setValidationError('Because your rating is under 3, feedback is required. Please explain what went wrong (min 5 characters) so IT can resolve it.');
            return;
        }

        setSubmitting(true);
        setValidationError('');

        try {
            const response = await axios.post('/it-satisfaction/rate', {
                survey_id: survey.id,
                rating: rating,
                aspect_ratings: aspects,
                feedback: feedback.trim(),
            });

            if (response.data?.success) {
                toast.success('Thank you! Your IT Satisfaction rating has been submitted.');
                setSubmitted(true);
                setSurvey(null);

                // Redirect user to /performance/sale-dashboard
                setTimeout(() => {
                    window.location.href = '/performance/sale-dashboard';
                }, 400);
            } else {
                toast.error(response.data?.message || 'Failed to submit rating.');
            }
        } catch (error) {
            if (error.response?.data?.errors) {
                const firstErr = Object.values(error.response.data.errors)[0];
                setValidationError(Array.isArray(firstErr) ? firstErr[0] : firstErr);
            } else {
                setValidationError(error.response?.data?.message || 'Error submitting rating. Please try again.');
            }
        } finally {
            setSubmitting(false);
        }
    };

    return createPortal(
        <div
            className="fixed inset-0 z-[99999] overflow-y-auto flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md animate-in fade-in duration-300"
            role="dialog"
            aria-modal="true"
            aria-labelledby="it-satisfaction-title"
        >
            <div
                className="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden flex flex-col my-auto"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Top Header Banner with Gradient */}
                <div className="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 px-6 py-6 sm:px-8 sm:py-7 text-white">
                    <div className="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div className="absolute left-1/2 bottom-0 w-32 h-32 bg-amber-400/20 rounded-full blur-2xl pointer-events-none"></div>

                    <div className="relative z-10">
                        {/* Top Badges: Badge Text & Date Range Window */}
                        <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/20 backdrop-blur-sm text-white border border-white/25">
                                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                {survey.badge_text || 'IT Satisfaction Survey'}
                            </span>

                            {(survey.start_date_formatted || survey.end_date_formatted) && (
                                <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-black/20 backdrop-blur-sm text-amber-200 border border-amber-300/30">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Period: {survey.start_date_formatted} - {survey.end_date_formatted}
                                </span>
                            )}
                        </div>

                        <h2 id="it-satisfaction-title" className="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            {survey.title || 'IT Department Service Satisfaction'}
                        </h2>

                        <p className="mt-2 text-sm sm:text-base text-blue-100/90 leading-relaxed">
                            {survey.description || 'ဝန်ဆောင်မှုကို အကောင်းဆုံးဖြစ်ရန် မြှင့်တင်နေပါသည်။ မိမိကြုံတွေ့ရသော အတွေ့အကြုံအပေါ် အခြေခံ၍ အမှတ် ၁ မှ ၅ အထိ အဆင့်သတ်မှတ်ပေးရန် မေတ္တာရပ်ခံအပ်ပါသည်။'}
                        </p>

                        {/* Notice for required completion */}
                        <div className="mt-3 flex items-center gap-2 text-xs font-medium text-amber-200 bg-amber-500/20 px-3 py-1.5 rounded-xl border border-amber-400/30 w-fit">
                            <svg className="w-4 h-4 shrink-0 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                            </svg>
                            <span>အမှတ်ပေးသူ၏ ကိုယ်ရေးကိုယ်တာ အချက်အလက်များကို သိမ်းဆည်းထားမည်မဟုတ်ပါ။</span>
                        </div>
                    </div>
                </div>

                {/* Form Body */}
                <form onSubmit={handleSubmit} className="p-6 sm:p-8 space-y-6">

                    {/* Primary Satisfaction Rating (1 to 5) */}
                    <div>
                        <div className="flex items-center justify-between mb-3">
                            <label className="block text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                                Overall IT Satisfaction Rating <span className="text-rose-500">*</span>
                            </label>
                            {currentRatingInfo && (
                                <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${currentRatingInfo.bgColor}`}>
                                    {currentRatingInfo.emoji} {currentRatingInfo.score}/5 — {currentRatingInfo.label}
                                </span>
                            )}
                        </div>

                        {/* Interactive 5 Star / Emoji Buttons */}
                        <div className="grid grid-cols-5 gap-2 sm:gap-3">
                            {ratingOptions.map((opt) => {
                                const isSelected = rating === opt.score;
                                const isHovered = hoverRating === opt.score;

                                return (
                                    <button
                                        key={opt.score}
                                        type="button"
                                        onClick={() => handleRatingSelect(opt.score)}
                                        onMouseEnter={() => setHoverRating(opt.score)}
                                        onMouseLeave={() => setHoverRating(0)}
                                        className={`group relative flex flex-col items-center justify-center p-3 sm:p-4 rounded-2xl border-2 transition-all duration-200 ${isSelected
                                            ? `border-indigo-600 dark:border-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/40 shadow-lg scale-105 ring-2 ring-indigo-500/40`
                                            : `border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600 hover:scale-102`
                                            }`}
                                    >
                                        <span className="text-3xl sm:text-4xl transform transition-transform duration-200 group-hover:scale-115 group-active:scale-95">
                                            {opt.emoji}
                                        </span>

                                        <div className="mt-2 flex items-center gap-1 font-bold text-sm text-slate-800 dark:text-slate-200">
                                            <span>{opt.score}</span>
                                            <span className="text-amber-400">★</span>
                                        </div>

                                        <span className="mt-0.5 text-[10px] sm:text-xs text-center font-medium text-slate-500 dark:text-slate-400 line-clamp-1">
                                            {opt.label}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Dynamic Aspect Ratings / Criteria */}
                    {Array.isArray(survey.criteria) && survey.criteria.length > 0 && (
                        <div className="rounded-2xl p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 space-y-3">
                            <p className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                Detailed Evaluation Criteria (Optional)
                            </p>

                            <div className="grid sm:grid-cols-3 gap-3">
                                {survey.criteria.map((item) => (
                                    <div key={item.key || item.label}>
                                        <span className="text-xs font-semibold text-slate-600 dark:text-slate-400 block mb-1 truncate" title={item.label}>
                                            {item.label}
                                        </span>
                                        <div className="flex items-center gap-1">
                                            {[1, 2, 3, 4, 5].map((s) => (
                                                <button
                                                    key={s}
                                                    type="button"
                                                    onClick={() => handleAspectChange(item.key || item.label, s)}
                                                    className={`w-7 h-7 rounded-lg text-xs font-bold transition ${(aspects[item.key || item.label] || 0) >= s
                                                        ? 'bg-amber-400 text-slate-900 shadow-sm'
                                                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                                        }`}
                                                >
                                                    ★
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Feedback / Comments Textarea */}
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <label htmlFor="it-feedback" className="block text-sm font-bold text-slate-900 dark:text-slate-100">
                                Feedback / Comments / Suggestions {isLowScore && <span className="text-rose-500">* (Required for score under 3)</span>}
                            </label>
                            {isLowScore && (
                                <span className="text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 px-2 py-0.5 rounded-full border border-rose-200 dark:border-rose-900 animate-pulse">
                                    Explanation Required
                                </span>
                            )}
                        </div>

                        <textarea
                            id="it-feedback"
                            rows={3}
                            value={feedback}
                            onChange={(e) => {
                                setFeedback(e.target.value);
                                if (validationError) setValidationError('');
                            }}
                            placeholder={
                                isLowScore
                                    ? "Please describe the issues or difficulties you experienced with the IT department so we can take immediate action to fix them..."
                                    : "Share any additional feedback, praises, or ideas for the IT Department (Optional)..."
                            }
                            className={`w-full rounded-2xl px-4 py-3 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 border transition focus:outline-none focus:ring-2 ${isLowScore && (!feedback || feedback.trim().length < 5)
                                ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20'
                                : 'border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/20'
                                }`}
                        />

                        {isLowScore && (
                            <p className="mt-1.5 text-xs text-rose-600 dark:text-rose-400">
                                ⚠️ For rating scores of 1 or 2, providing feedback is mandatory to help IT address the problem.
                            </p>
                        )}
                    </div>

                    {/* Validation Error Message */}
                    {validationError && (
                        <div className="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-start gap-2 animate-shake">
                            <svg className="w-4 h-4 shrink-0 mt-0.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                            <span>{validationError}</span>
                        </div>
                    )}

                    {/* Footer Actions: Strictly Submit Button */}
                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={submitting || rating === 0}
                            className={`w-full py-4 px-6 rounded-2xl text-base font-bold text-white transition-all duration-200 shadow-xl flex items-center justify-center gap-2 ${rating === 0
                                ? 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed opacity-60 shadow-none'
                                : 'bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 hover:from-blue-700 hover:via-indigo-700 hover:to-violet-700 active:scale-98 hover:shadow-indigo-500/25'
                                }`}
                        >
                            {submitting ? (
                                <>
                                    <svg className="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Recording Your Rating...</span>
                                </>
                            ) : (
                                <>
                                    <span>Submit IT Satisfaction Rating</span>
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </>
                            )}
                        </button>

                    </div>
                </form>
            </div>
        </div>,
        document.body
    );
}
