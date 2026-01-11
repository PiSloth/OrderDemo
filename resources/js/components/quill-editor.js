import Quill from "quill";
import QuillBetterTable from "quill-better-table";

window.Quill = Quill;

// Table support (Quill does not ship tables by default).
// Note: This relies on the quill-better-table module.
try {
    Quill.register(
        {
            "modules/better-table": QuillBetterTable,
        },
        true
    );
} catch (e) {
    // If registration fails, editor still works without tables.
    console.warn("Better table module registration failed", e);
}

// Alpine is not guaranteed to be present in this project.
// Register as a small, global helper that Livewire views can call.
window.__initQuillEditor = function initQuillEditor(element, initialHtml, onChange, options = {}) {
    const uploadUrl = options?.uploadUrl;
    const csrfToken =
        options?.csrfToken ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
        "";

    const toolbarOptions = [
        [{ header: [1, 2, 3, false] }],
        ["bold", "italic", "underline", "strike"],
        [{ color: [] }, { background: [] }],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ align: [] }],
        ["blockquote", "code-block"],
        ["link", "image"],
        ["table"],
        ["clean"],
    ];

    const quill = new Quill(element, {
        theme: "snow",
        modules: {
            toolbar: toolbarOptions,
            "better-table": {
                operationMenu: {
                    items: {
                        unmergeCells: {
                            text: "Unmerge Cells",
                        },
                    },
                },
            },
        },
    });

    // Add table insertion handler if the module is available.
    const toolbar = quill.getModule("toolbar");
    const betterTable = quill.getModule("better-table");
    if (toolbar && betterTable) {
        toolbar.addHandler("table", () => {
            // Inserts a 3x3 table by default.
            betterTable.insertTable(3, 3);
        });
    }

    if (toolbar) {
        toolbar.addHandler("image", () => {
            if (!uploadUrl) {
                alert("Image upload is not configured.");
                return;
            }

            const input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");
            input.click();

            input.onchange = async () => {
                const file = input.files?.[0];
                if (!file) return;

                const isDebug = new URLSearchParams(window.location.search).has('quillDebug');

                if (isDebug) {
                    console.log('[Quill] image upload config', {
                        uploadUrl,
                        hasCsrfToken: Boolean(csrfToken),
                    });
                }

                const overlay = document.createElement('div');
                overlay.setAttribute('data-quill-upload-overlay', '1');
                overlay.style.position = 'fixed';
                overlay.style.inset = '0';
                overlay.style.zIndex = '9999';
                overlay.style.display = 'flex';
                overlay.style.alignItems = 'center';
                overlay.style.justifyContent = 'center';
                overlay.style.background = 'rgba(15, 23, 42, 0.45)';

                const box = document.createElement('div');
                box.style.background = 'rgba(15, 23, 42, 0.85)';
                box.style.color = 'white';
                box.style.padding = '10px 14px';
                box.style.borderRadius = '10px';
                box.style.fontSize = '14px';
                box.style.fontWeight = '600';
                box.style.maxWidth = 'min(520px, calc(100vw - 32px))';
                box.textContent = 'Uploading image…';
                overlay.appendChild(box);

                const setOverlayText = (text) => {
                    box.textContent = text;
                };

                document.body.appendChild(overlay);

                if (isDebug) {
                    console.log('[Quill] image upload selected file', {
                        name: file.name,
                        type: file.type,
                        size: file.size,
                    });
                }

                // Capture a stable insertion point.
                // (If Quill isn't focused, getSelection() can be null.)
                try { quill.focus(); } catch {}
                const range = quill.getSelection(true);
                const previewIndex = typeof range?.index === 'number' ? range.index : quill.getLength();

                // Insert a local preview immediately.
                try {
                    const dataUrl = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = () => reject(new Error('preview failed'));
                        reader.readAsDataURL(file);
                    });

                    if (typeof dataUrl === 'string') {
                        quill.insertEmbed(previewIndex, 'image', dataUrl, 'user');
                        quill.setSelection(previewIndex + 1, 0, 'silent');
                    }
                } catch {
                    // Ignore preview errors.
                }

                // Disable editing while uploading (user input), but still allow programmatic updates.
                quill.enable(false);

                const formData = new FormData();
                formData.append("image", file);

                try {
                    const res = await fetch(uploadUrl, {
                        method: "POST",
                        credentials: 'same-origin',
                        headers: {
                            ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                            ...(isDebug ? { 'X-Quill-Debug': '1' } : {}),
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: formData,
                    });

                    if (!res.ok) {
                        let message = `Upload failed (${res.status})`;
                        try {
                            const data = await res.json();
                            message = data?.message || message;
                        } catch {
                            const text = await res.text();
                            if (text && text.length < 500) message = text;
                        }

                        if (res.status === 401) {
                            message = 'You are not logged in. Please refresh and login again.';
                        }
                        if (res.status === 419) {
                            message = 'Session expired. Please refresh the page and try again.';
                        }

                        throw new Error(message);
                    }

                    const data = await res.json();
                    if (isDebug) {
                        console.log('[Quill] image upload response', data);
                    }
                    if (!data?.url) {
                        throw new Error("Upload response missing url");
                    }

                    const finalUrl = (() => {
                        try {
                            return new URL(data.url, window.location.origin).toString();
                        } catch {
                            return data.url;
                        }
                    })();

                    // Replace the preview (1 embed length) with the final URL if it exists.
                    // Otherwise, just insert the final image at the captured index.
                    try {
                        const delta = quill.getContents(previewIndex, 1);
                        const op = delta?.ops?.[0];
                        const hasImageEmbed = op && typeof op.insert === 'object' && op.insert && op.insert.image;
                        if (hasImageEmbed) {
                            quill.deleteText(previewIndex, 1, 'silent');
                        }
                    } catch {}

                    quill.insertEmbed(previewIndex, "image", finalUrl, "user");
                    quill.setSelection(previewIndex + 1, 0, "silent");

                    if (isDebug) {
                        console.log('[Quill] image upload success', finalUrl);

                        // Verify the URL is actually reachable and the image loads.
                        try {
                            fetch(finalUrl, { method: 'HEAD', credentials: 'same-origin' })
                                .then((headRes) => {
                                    console.log('[Quill] image URL HEAD check', {
                                        url: finalUrl,
                                        status: headRes.status,
                                        ok: headRes.ok,
                                    });
                                })
                                .catch((e) => {
                                    console.warn('[Quill] image URL HEAD check failed', {
                                        url: finalUrl,
                                        error: String(e?.message || e),
                                    });
                                });

                            const img = new Image();
                            img.onload = () => {
                                console.log('[Quill] image load OK', {
                                    url: finalUrl,
                                    width: img.naturalWidth,
                                    height: img.naturalHeight,
                                });
                            };
                            img.onerror = () => {
                                console.warn('[Quill] image load FAILED', { url: finalUrl });
                            };
                            img.src = finalUrl;
                        } catch (e) {
                            console.warn('[Quill] debug load checks failed', e);
                        }
                    }
                } catch (err) {
                    console.error(err);
                    // If preview was inserted, remove it.
                    try {
                        const delta = quill.getContents(previewIndex, 1);
                        const op = delta?.ops?.[0];
                        const hasImageEmbed = op && typeof op.insert === 'object' && op.insert && op.insert.image;
                        if (hasImageEmbed) {
                            quill.deleteText(previewIndex, 1, 'silent');
                        }
                    } catch {}

                    const message = err?.message || 'Image upload failed. Please try again.';
                    setOverlayText(message);
                    // Keep it visible briefly so the user actually sees the reason.
                    setTimeout(() => {
                        overlay?.remove();
                    }, 1800);

                    alert(message);
                } finally {
                    // If it wasn't already removed by error timeout.
                    if (overlay?.isConnected) overlay.remove();
                    quill.enable(true);
                }
            };
        });
    }

    if (typeof initialHtml === "string" && initialHtml.trim() !== "") {
        quill.clipboard.dangerouslyPasteHTML(initialHtml);
    }

    quill.on("text-change", () => {
        const html = element.querySelector(".ql-editor")?.innerHTML ?? "";
        if (typeof onChange === "function") {
            onChange(html);
        }
    });

    return quill;
};

function getClosestLivewireComponentId(element) {
    const componentRoot = element.closest('[wire\\:id]');
    return componentRoot?.getAttribute('wire:id') || null;
}

function findInitialHtml(editorElement) {
    const script = editorElement.parentElement?.querySelector('script[data-quill-initial]');
    if (!script) return "";

    try {
        return JSON.parse(script.textContent || '""') || "";
    } catch {
        return "";
    }
}

function initAllQuillEditors() {
    document.querySelectorAll('[data-quill-editor]').forEach((editorEl) => {
        if (editorEl.__quillInitialized) return;

        const componentId = getClosestLivewireComponentId(editorEl);
        if (!componentId || typeof window.Livewire?.find !== 'function') return;

        const model = editorEl.getAttribute('data-model') || 'body';
        const uploadUrl = editorEl.getAttribute('data-upload-url') || undefined;
        const csrfToken =
            editorEl.getAttribute('data-csrf') ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            "";

        const initial = findInitialHtml(editorEl);

        window.__initQuillEditor(
            editorEl,
            initial,
            (html) => {
                window.Livewire.find(componentId).set(model, html);
            },
            { uploadUrl, csrfToken }
        );

        editorEl.__quillInitialized = true;
    });
}

document.addEventListener('livewire:init', () => {
    initAllQuillEditors();
});

document.addEventListener('livewire:navigated', () => {
    initAllQuillEditors();
});

// Fallback for non-Livewire pages.
document.addEventListener('DOMContentLoaded', () => {
    initAllQuillEditors();
});
