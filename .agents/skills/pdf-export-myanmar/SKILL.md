---
name: pdf-export-myanmar
description: >-
  Standard workflow and implementation pattern for generating client-side PDF reports
  with accurate Myanmar Unicode (Pyidaungsu) font rendering and crisp vector tables.
  Use this skill whenever creating, modifying, or fixing PDF export features in the project.
---

# PDF Export with Myanmar Font Support Flow

Use this workflow whenever implementing or updating PDF exports in this codebase (reference implementation: `resources/js/utils/trainingPdfExport.js`).

## Critical Guardrails (What NOT to Do)

1. **NEVER use `html2canvas`**:
   - `resources/css/app.css` sets `html { zoom: 0.75; }` on viewports `>= 768px`.
   - `html2canvas` measures individual words via `Range.getBoundingClientRect()` (scaled by `0.75`) and draws them at `1.0x` font size, causing severe horizontal word collisions and overlapping text.
2. **NEVER render Myanmar Unicode (`\u1000-\u109F`) directly with `jsPDF` `doc.text()`**:
   - `jsPDF` does not have a HarfBuzz / OpenType shaping engine for Myanmar script, so pre-base vowels (`ေ`), medials (`ြ`, `ွ`), and stacked consonants (`္`) render in broken logical order.
3. **Direct Download by Default**:
   - When the user clicks a PDF export button, download the PDF directly via `doc.save(filename)`. Do not show a preview modal unless the user explicitly asks for one.

---

## Standard Implementation Architecture

Combine **vector `jsPDF` + `jspdf-autotable`** (for layout, borders, tables, and ASCII text) with a **4x DPI offscreen HTML5 2D `<canvas>` (`CanvasRenderingContext2D`) shaper** for strings containing Myanmar or non-ASCII Unicode (`/[^\x00-\x7F]/`).

### Step 1: Preload the `Pyidaungsu` Webfont

Ensure the browser's `FontFaceSet` has loaded `Pyidaungsu` (declared in `resources/css/app.css` from `/fonts/Pyidaungsu-Regular.ttf` and `/fonts/Pyidaungsu-Bold.ttf`) before measuring or drawing on a 2D `<canvas>`:

```javascript
const ensurePyidaungsuFontLoaded = async () => {
  try {
    if (typeof document !== 'undefined' && document.fonts && document.fonts.load) {
      await Promise.all([
        document.fonts.load('14px Pyidaungsu'),
        document.fonts.load('bold 14px Pyidaungsu'),
      ]);
      await document.fonts.ready;
    }
  } catch (e) {
    console.warn('Pyidaungsu font load check warning:', e);
  }
};
```

### Step 2: Detect & Shape Myanmar / Complex Unicode via Offscreen 2D `<canvas>`

Use `hasComplexUnicode` to detect any string with Myanmar or non-ASCII characters, tokenize long Myanmar phrases at word/syllable boundaries (`Intl.Segmenter('my', { granularity: 'word' })` with regex fallback), wrap lines using `ctx.measureText(candidate).width`, and draw each wrapped line in a single `ctx.fillText(line, 0, baselineY)` call at `SCALE = 4` (`384 DPI`):

```javascript
const hasComplexUnicode = (text) => /[^\x00-\x7F]/.test(String(text || ''));

const tokenizeForMyanmarWrap = (paragraph) => {
  if (!paragraph) return [''];
  if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
    try {
      const segmenter = new Intl.Segmenter('my', { granularity: 'word' });
      const tokens = [];
      for (const seg of segmenter.segment(paragraph)) {
        tokens.push(seg.segment);
      }
      if (tokens.length > 0) return tokens;
    } catch {
      // Fallback below
    }
  }
  return paragraph
    .split(/( +|(?=[\u1000-\u1021\u1023-\u102A\u103F\u1040-\u104F])(?<![\u1031\u1039\u103A]))/g)
    .filter(Boolean);
};

const renderUnicodeTextBlockToPng = (
  text,
  {
    fontSizePt = 9,
    maxWidthMm = 170,
    color = '#0f172a',
    fontWeight = 'normal',
    lineHeightMultiplier = 1.65,
  } = {}
) => {
  const PX_PER_MM = 96 / 25.4;
  const MM_PER_PX = 25.4 / 96;
  const SCALE = 4; // 384 DPI print quality

  const fontSizePx = fontSizePt * (96 / 72);
  const maxWidthPx = Math.max(24, maxWidthMm * PX_PER_MM);
  const fontSpec = `${fontWeight} ${fontSizePx}px 'Pyidaungsu', 'Padauk', 'Myanmar Text', 'Segoe UI', Arial, sans-serif`;

  const measureCanvas = document.createElement('canvas');
  const measureCtx = measureCanvas.getContext('2d');
  measureCtx.font = fontSpec;

  const paragraphs = String(text || '').split('\n');
  const wrappedLines = [];

  for (const para of paragraphs) {
    const trimmedPara = para.trim();
    if (!trimmedPara) {
      wrappedLines.push('');
      continue;
    }

    const spaceTokens = trimmedPara.split(/( +)/).filter(Boolean);
    const fineTokens = [];
    for (const st of spaceTokens) {
      if (measureCtx.measureText(st).width > maxWidthPx * 0.65 && !/^ +$/.test(st)) {
        fineTokens.push(...tokenizeForMyanmarWrap(st));
      } else {
        fineTokens.push(st);
      }
    }

    let currentLine = '';
    for (const token of fineTokens) {
      const candidate = currentLine ? currentLine + token : token.trimStart();
      if (!candidate) continue;

      if (measureCtx.measureText(candidate).width <= maxWidthPx || !currentLine) {
        currentLine = candidate;
      } else {
        wrappedLines.push(currentLine.trimEnd());
        currentLine = token.trimStart();
      }
    }
    if (currentLine) {
      wrappedLines.push(currentLine.trimEnd());
    }
  }

  if (wrappedLines.length === 0) wrappedLines.push('');

  const lineHeightPx = Math.ceil(fontSizePx * lineHeightMultiplier);
  const topPadPx = Math.ceil(fontSizePx * 0.35);
  const bottomPadPx = Math.ceil(fontSizePx * 0.45);
  const totalHeightPx = wrappedLines.length * lineHeightPx + topPadPx + bottomPadPx;

  const canvas = document.createElement('canvas');
  canvas.width = Math.ceil(maxWidthPx * SCALE);
  canvas.height = Math.ceil(totalHeightPx * SCALE);

  const ctx = canvas.getContext('2d');
  ctx.scale(SCALE, SCALE);
  ctx.font = fontSpec;
  ctx.fillStyle = color;
  ctx.textBaseline = 'alphabetic';

  wrappedLines.forEach((line, idx) => {
    if (!line) return;
    const baselineY = topPadPx + idx * lineHeightPx + fontSizePx * 1.05;
    ctx.fillText(line, 0, baselineY);
  });

  return {
    dataUrl: canvas.toDataURL('image/png'),
    widthMm: maxWidthMm,
    heightMm: totalHeightPx * MM_PER_PX,
    lineCount: wrappedLines.length,
  };
};
```

### Step 3: Render Standalone Blocks & `autoTable` Cells

- **Standalone text blocks (titles, descriptions, scopes)**:
  - If `hasComplexUnicode(text)` is `true`, pre-measure with `renderUnicodeTextBlockToPng(...)` so the surrounding card/box height matches `img.heightMm`, and embed via `doc.addImage(img.dataUrl, 'PNG', x, y, img.widthMm, img.heightMm)`.
  - If `false`, use `doc.setFont('helvetica', style)` and `doc.splitTextToSize(text, maxWidthMm)` + `doc.text(...)`.
- **`jspdf-autotable` cells**:
  - Set `styles: { font: 'helvetica', ... }` as the base table font so ASCII text, numbers, and bold/italic styles render as crisp vector text.
  - Intercept cells containing Myanmar Unicode in `didParseCell` and draw the shaped PNG in `didDrawCell`:

```javascript
didParseCell: (data) => {
  if (data.section !== 'body') return;
  const rawVal = data.cell.raw;
  if (!rawVal || typeof rawVal === 'object') return;
  const strVal = String(rawVal);
  if (hasComplexUnicode(strVal)) {
    const colW = colWidths[data.column.index] || 50;
    const innerW = Math.max(15, colW - 5);
    const isBoldCol = data.column.index === 1;
    const unicodeImg = renderUnicodeTextBlockToPng(strVal, {
      fontSizePt: 8.2,
      maxWidthMm: innerW,
      color: isBoldCol ? '#0f172a' : '#334155',
      fontWeight: isBoldCol ? 'bold' : 'normal',
    });
    data.cell._unicodeImg = unicodeImg;
    data.cell.text = new Array(Math.max(1, unicodeImg.lineCount)).fill(' ');
    data.cell.styles.minCellHeight = Math.max(6.8, unicodeImg.heightMm + 2.4);
  }
},
didDrawCell: (data) => {
  if (data.section === 'body' && data.cell._unicodeImg) {
    const img = data.cell._unicodeImg;
    const drawX = data.cell.x + 2.2;
    const drawY = data.cell.y + Math.max(1, (data.cell.height - img.heightMm) / 2);
    doc.addImage(img.dataUrl, 'PNG', drawX, drawY, img.widthMm, img.heightMm);
  }
},
```

### Step 4: Build Frontend Assets

Always run `npm run build` after creating or modifying any frontend PDF export utility so `public/build/` assets are updated.
