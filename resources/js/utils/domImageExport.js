import { toCanvas, toPng, toBlob } from 'html-to-image';

/**
 * Ensures Pyidaungsu fonts are ready in document.fonts before rendering.
 */
export const ensurePyidaungsuFontLoaded = async () => {
    try {
        if (typeof document !== 'undefined' && document.fonts && document.fonts.load) {
            await Promise.all([
                document.fonts.load('11px Pyidaungsu'),
                document.fonts.load('bold 11px Pyidaungsu'),
                document.fonts.load('12px Pyidaungsu'),
                document.fonts.load('bold 12px Pyidaungsu'),
                document.fonts.load('13.5px Pyidaungsu'),
                document.fonts.load('14px Pyidaungsu'),
                document.fonts.load('bold 14px Pyidaungsu'),
            ]);
            await document.fonts.ready;
        }
    } catch (e) {
        console.warn('Pyidaungsu font load check warning:', e);
    }
};

/**
 * Module-level memory cache for inlined Pyidaungsu @font-face CSS.
 * Supplying `fontEmbedCSS` directly to `html-to-image` completely bypasses its
 * slow document.styleSheets crawl and network fetches of application CSS and fonts,
 * reducing render time down to milliseconds and ensuring Myanmar Unicode renders accurately.
 */
let cachedPyidaungsuFontEmbedCSS = null;
let fontEmbedLoadingPromise = null;

export const getPyidaungsuFontEmbedCSS = async () => {
    if (cachedPyidaungsuFontEmbedCSS) return cachedPyidaungsuFontEmbedCSS;
    if (fontEmbedLoadingPromise) return fontEmbedLoadingPromise;

    fontEmbedLoadingPromise = (async () => {
        try {
            const fetchAsBase64 = async (url) => {
                const res = await fetch(url);
                if (!res.ok) throw new Error(`HTTP ${res.status} for ${url}`);
                const blob = await res.blob();
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            };

            const [regularBase64, boldBase64] = await Promise.all([
                fetchAsBase64('/fonts/Pyidaungsu-Regular.ttf'),
                fetchAsBase64('/fonts/Pyidaungsu-Bold.ttf'),
            ]);

            cachedPyidaungsuFontEmbedCSS = `
@font-face {
    font-family: 'Pyidaungsu';
    src: url('${regularBase64}') format('truetype');
    font-weight: normal;
    font-style: normal;
}
@font-face {
    font-family: 'Pyidaungsu';
    src: url('${boldBase64}') format('truetype');
    font-weight: bold;
    font-style: normal;
}
            `.trim();
            return cachedPyidaungsuFontEmbedCSS;
        } catch (err) {
            console.warn('Failed to pre-cache Pyidaungsu font embed CSS:', err);
            return '';
        }
    })();

    return fontEmbedLoadingPromise;
};

// Pre-warm font cache in browser environment so fonts are ready in memory
if (typeof window !== 'undefined' && typeof fetch !== 'undefined') {
    getPyidaungsuFontEmbedCSS();
}

/**
 * Capture an element to HTML5 Canvas safely using html-to-image.
 * Immune to html { zoom: 0.75 } letter-spacing overlap bugs that break html2canvas.
 */
export const captureElementToCanvas = async (element, options = {}) => {
    if (!element) return null;

    await ensurePyidaungsuFontLoaded();
    const fontCss = await getPyidaungsuFontEmbedCSS();

    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    const defaultBg = isDark ? '#0f172a' : '#ffffff';

    return await toCanvas(element, {
        pixelRatio: options.pixelRatio || 2,
        backgroundColor: options.backgroundColor || defaultBg,
        cacheBust: false,
        fontEmbedCSS: fontCss || undefined,
        filter: (node) => {
            if (node.nodeType === 1) { // ELEMENT_NODE
                if (
                    node.classList?.contains('no-export') ||
                    node.classList?.contains('no-print') ||
                    node.getAttribute?.('data-html2canvas-ignore') === 'true' ||
                    node.getAttribute?.('data-export-ignore') === 'true'
                ) {
                    return false;
                }
            }
            if (typeof options.filter === 'function') {
                return options.filter(node);
            }
            return true;
        },
        ...options,
    });
};
