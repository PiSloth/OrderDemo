import dayjs from 'dayjs';

export const GLOBAL_DATE_FORMAT = 'DD-MMM-YY';
export const GLOBAL_TIME_FORMAT = 'hh:mm A';
export const GLOBAL_DATETIME_FORMAT = `${GLOBAL_DATE_FORMAT} ${GLOBAL_TIME_FORMAT}`;

/**
 * Format a date/datetime value into the global date format (e.g. 10-Nov-26).
 */
export function formatDate(value, fallback = '-') {
    if (!value) return fallback;
    const parsed = dayjs(value);
    if (!parsed.isValid()) return String(value);
    return parsed.format(GLOBAL_DATE_FORMAT);
}

/**
 * Format a date/datetime value into time with AM/PM (e.g. 05:34 PM).
 */
export function formatTime(value, fallback = '-') {
    if (!value) return fallback;
    const parsed = dayjs(value);
    if (!parsed.isValid()) return String(value);
    return parsed.format(GLOBAL_TIME_FORMAT);
}

/**
 * Format a date/datetime value into global date + AM/PM time (e.g. 10-Nov-26 05:34 PM).
 */
export function formatDateTime(value, fallback = '-') {
    if (!value) return fallback;
    const parsed = dayjs(value);
    if (!parsed.isValid()) return String(value);
    return parsed.format(GLOBAL_DATETIME_FORMAT);
}
