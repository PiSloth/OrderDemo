/**
 * Utility to load and register the Pyidaungsu Unicode font in jsPDF instances.
 * Supports Myanmar Unicode script characters (e.g. မင်္ဂလာပါ။) and English characters.
 */

let regularFontBase64 = null;
let boldFontBase64 = null;

const arrayBufferToBase64 = (buffer) => {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const chunkSize = 8192;
    for (let i = 0; i < bytes.length; i += chunkSize) {
        const chunk = bytes.subarray(i, i + chunkSize);
        binary += String.fromCharCode.apply(null, chunk);
    }
    return window.btoa(binary);
};

export const loadPyidaungsuFonts = async () => {
    if (!regularFontBase64) {
        try {
            const regRes = await fetch('/fonts/Pyidaungsu-Regular.ttf');
            if (regRes.ok) {
                const regBuffer = await regRes.arrayBuffer();
                regularFontBase64 = arrayBufferToBase64(regBuffer);
            }
        } catch (e) {
            console.error('Failed to fetch Pyidaungsu-Regular.ttf:', e);
        }
    }

    if (!boldFontBase64) {
        try {
            const boldRes = await fetch('/fonts/Pyidaungsu-Bold.ttf');
            if (boldRes.ok) {
                const boldBuffer = await boldRes.arrayBuffer();
                boldFontBase64 = arrayBufferToBase64(boldBuffer);
            }
        } catch (e) {
            console.error('Failed to fetch Pyidaungsu-Bold.ttf:', e);
        }
    }

    return {
        regular: regularFontBase64,
        bold: boldFontBase64,
    };
};

/**
 * Adds Pyidaungsu regular and bold fonts to a jsPDF instance and sets the default font.
 * @param {import('jspdf').jsPDF} doc
 * @returns {Promise<string>} The font name ('Pyidaungsu' or fallback 'helvetica')
 */
export const applyPyidaungsuFont = async (doc) => {
    try {
        const fonts = await loadPyidaungsuFonts();

        if (fonts.regular) {
            doc.addFileToVFS('Pyidaungsu-Regular.ttf', fonts.regular);
            doc.addFont('Pyidaungsu-Regular.ttf', 'Pyidaungsu', 'normal');
            doc.addFont('Pyidaungsu-Regular.ttf', 'Pyidaungsu', 'italic');
        }
        if (fonts.bold) {
            doc.addFileToVFS('Pyidaungsu-Bold.ttf', fonts.bold);
            doc.addFont('Pyidaungsu-Bold.ttf', 'Pyidaungsu', 'bold');
        }

        if (fonts.regular || fonts.bold) {
            doc.setFont('Pyidaungsu', 'normal');
            return 'Pyidaungsu';
        }
    } catch (err) {
        console.error('Error applying Pyidaungsu font to jsPDF:', err);
    }
    return 'helvetica';
};
