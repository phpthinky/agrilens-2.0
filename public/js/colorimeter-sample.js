/**
 * Colorimeter color pick — shared by the pH and N/P/K test pages.
 *
 * The colorimeter box holds the test tube upright at the centre of the box,
 * close to the fixed USB camera. At that distance the tube fills roughly a
 * quarter of the frame width and the box LEDs reflect off the glass as
 * bright spots near the tube's left and right edges. A fixed square in the
 * middle of the frame would catch those reflections, so the pick instead:
 *
 *   1. Finds the tube in each horizontal band of the frame by its color
 *      saturation (reagent liquid vs. the white paper behind it).
 *   2. Averages only the middle 40 % of the tube's width — clear of the
 *      edge reflections and the curved glass.
 *   3. Drops the brightest 5 % (specular glints) and darkest 3 % (specks,
 *      bubbles) of those pixels.
 *
 * A colorless liquid has no saturation to find the tube by; then a narrow
 * strip in the middle of the frame (where the box holds the tube) is used.
 *
 * app/Services/SnapshotColorService.php is the server-side twin of pick()
 * for photos uploaded by the field app. Keep the two in step: same
 * constants, same integer maths, same result for the same pixels.
 *
 * Usage:
 *   const zone = ColorimeterSample.pick(ctx.getImageData(0, 0, w, h));
 *   // zone = { r, g, b, hex, method: 'tube' | 'center', pixels, bands, rect }
 *   ColorimeterSample.watchAll();   // live guide on every .js-zone-overlay
 */
(function () {
    'use strict';

    const C = {
        MARGIN: 0.10,          // ignore top/bottom 10 % (cap, tube base)
        BAND: 4,               // rows per band
        SMOOTH: 2,             // ±2 px column smoothing
        SAT_MIN: 40,           // saturation (0–255) a tube column must exceed …
        SAT_ABOVE_MEDIAN: 30,  // … and by this much over the band's median
        MIN_TUBE: 0.05,        // tube run at least 5 % of frame width
        MAX_OFFSET: 0.25,      // tube centre within 25 % of frame centre
        MIN_BANDS: 0.25,       // tube found in ≥ 25 % of bands
        CORE: 0.20,            // sample ±20 % of tube width around its centre
        STRIP: 30 / 320,       // colorless fallback strip width
        DROP_DARK: 0.03,
        DROP_BRIGHT: 0.95,
        GLARE: 250,            // V ≥ this is always glare
        DARK: 40,              // V below this: saturation is noise (black cap, shadows)
        BAND_DIM: 0.6,         // drop tube bands darker than 60 % of the median band …
        BAND_PALE: 0.6,        // … or less saturated than 60 % of it (glass rim, holder)
        MIN_PIXELS: 50,
        GUIDE: 0.27,           // tube width at box centre (guide band)
    };

    function satVal(r, g, b) {
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        return [max < C.DARK ? 0 : Math.floor((max - min) * 255 / max), max];
    }

    /** [mean V, saturation of the mean color] of a box [x1, x2) × [y1, y2). */
    function boxStats(data, w, [x1, x2, y1, y2]) {
        let v = 0, r = 0, g = 0, b = 0, n = 0;
        for (let y = y1; y < y2; y++) {
            for (let x = x1; x < x2; x++) {
                const i = (y * w + x) * 4;
                v += Math.max(data[i], data[i + 1], data[i + 2]);
                r += data[i]; g += data[i + 1]; b += data[i + 2]; n++;
            }
        }
        const max = Math.max(r, g, b);
        return [v / n, max === 0 ? 0 : (max - Math.min(r, g, b)) / max];
    }

    function median(list) {
        return list.slice().sort((a, b) => a - b)[Math.floor(list.length / 2)];
    }

    /** Tube run [x1, x2] in one band, or null. */
    function bandRun(data, w, y0, y1) {
        const col = new Array(w).fill(0);
        for (let y = y0; y < y1; y++) {
            for (let x = 0; x < w; x++) {
                const i = (y * w + x) * 4;
                col[x] += satVal(data[i], data[i + 1], data[i + 2])[0];
            }
        }
        const rows = y1 - y0;
        const s = new Array(w);
        for (let x = 0; x < w; x++) {
            let sum = 0, n = 0;
            for (let k = Math.max(0, x - C.SMOOTH); k <= Math.min(w - 1, x + C.SMOOTH); k++) {
                sum += col[k] / rows; n++;
            }
            s[x] = sum / n;
        }
        const sorted = s.slice().sort((a, b) => a - b);
        const thr = Math.max(C.SAT_MIN, sorted[Math.floor(w / 2)] + C.SAT_ABOVE_MEDIAN);

        const mid = Math.floor(w / 2);
        let best = null, bestDist = Infinity;
        for (let x = 0; x < w; x++) {
            if (s[x] <= thr) continue;
            let x2 = x;
            while (x2 + 1 < w && s[x2 + 1] > thr) x2++;
            const dist = mid < x ? x - mid : (mid > x2 ? mid - x2 : 0);
            if (x2 - x + 1 >= Math.round(w * C.MIN_TUBE) && dist < bestDist) {
                best = [x, x2]; bestDist = dist;
            }
            x = x2;
        }
        if (!best || bestDist > Math.round(w * C.MAX_OFFSET)) return null;
        return best;
    }

    function pick(imageData) {
        const { data, width: w, height: h } = imageData;
        const top = Math.round(h * C.MARGIN);
        const bottom = h - top;

        const bands = [];
        let total = 0;
        for (let y = top; y + C.BAND <= bottom; y += C.BAND) {
            total++;
            const run = bandRun(data, w, y, y + C.BAND);
            if (run) bands.push([y, run[0], run[1]]);
        }

        let boxes = bands.map(([y, x1, x2]) => {
            const c = Math.floor((x1 + x2) / 2);
            const half = Math.max(2, Math.floor((x2 - x1 + 1) * C.CORE));
            return [c - half, c + half, y, y + C.BAND];
        });
        // Drop bands that don't look like the rest of the liquid: far darker
        // (cap or label edge, shadow at the surface) or far paler (glass rim,
        // the holder below the liquid, air above it).
        if (boxes.length) {
            const stats = boxes.map(b => boxStats(data, w, b));
            const mv = median(stats.map(s => s[0]));
            const ms = median(stats.map(s => s[1]));
            boxes = boxes.filter((b, i) => stats[i][0] >= mv * C.BAND_DIM && stats[i][1] >= ms * C.BAND_PALE);
        }

        const tube = boxes.length >= Math.ceil(total * C.MIN_BANDS);
        if (!tube) {
            boxes = [];
            const sw = Math.max(4, Math.round(w * C.STRIP));
            const x1 = Math.floor((w - sw) / 2);
            boxes.push([x1, x1 + sw, top, bottom]);
        }

        const px = [];
        for (const [x1, x2, y1, y2] of boxes) {
            for (let y = y1; y < y2; y++) {
                for (let x = x1; x < x2; x++) {
                    const i = (y * w + x) * 4;
                    px.push([data[i], data[i + 1], data[i + 2], satVal(data[i], data[i + 1], data[i + 2])[1]]);
                }
            }
        }

        const vs = px.map(p => p[3]).sort((a, b) => a - b);
        const lo = vs[Math.floor(vs.length * C.DROP_DARK)];
        const hi = vs[Math.floor(vs.length * C.DROP_BRIGHT)];
        let keep = px.filter(p => p[3] >= lo && p[3] <= hi && p[3] < C.GLARE);
        if (keep.length < C.MIN_PIXELS) keep = px.filter(p => p[3] < C.GLARE);
        if (keep.length < C.MIN_PIXELS) keep = px;

        let r = 0, g = 0, b = 0;
        for (const p of keep) { r += p[0]; g += p[1]; b += p[2]; }
        r = Math.round(r / keep.length); g = Math.round(g / keep.length); b = Math.round(b / keep.length);

        return {
            r, g, b,
            hex: '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase(),
            method: tube ? 'tube' : 'center',
            pixels: keep.length,
            bands: tube ? boxes.length : 0,
            boxes,
        };
    }

    /**
     * Live alignment guide over the video: a dashed band where the tube
     * should stand, and the strip that will actually be measured.
     */
    function watch(video, overlay, label) {
        const work = document.createElement('canvas');
        const wctx = work.getContext('2d', { willReadFrequently: true });
        const octx = overlay.getContext('2d');

        function draw() {
            const W = overlay.width, H = overlay.height;
            octx.clearRect(0, 0, W, H);

            const gw = Math.round(W * C.GUIDE);
            octx.setLineDash([5, 4]);
            octx.strokeStyle = 'rgba(255,255,255,.85)';
            octx.lineWidth = 1.5;
            octx.strokeRect((W - gw) / 2, H * C.MARGIN, gw, H * (1 - 2 * C.MARGIN));
            octx.setLineDash([]);

            // Skip hidden steps (pH page has one video per step) and idle cameras.
            if (!video.videoWidth || video.offsetParent === null) return;
            work.width = W; work.height = H;
            wctx.drawImage(video, 0, 0, W, H);
            const zone = pick(wctx.getImageData(0, 0, W, H));

            octx.fillStyle = zone.method === 'tube' ? 'rgba(25,135,84,.45)' : 'rgba(255,193,7,.45)';
            for (const [x1, x2, y1, y2] of zone.boxes) octx.fillRect(x1, y1, x2 - x1, y2 - y1);

            if (label) {
                label.textContent = zone.method === 'tube'
                    ? 'Tube detected — measuring the green strip'
                    : 'No colored liquid found — measuring the centre strip';
            }
        }

        draw();
        return setInterval(draw, 500);
    }

    /** Start the guide on every `.js-zone-overlay` (once each), next to its video. */
    function watchAll() {
        document.querySelectorAll('.js-zone-overlay').forEach(overlay => {
            if (overlay.dataset.watching) return;
            const video = overlay.parentElement.querySelector('video');
            if (!video) return;
            overlay.dataset.watching = '1';
            watch(video, overlay, overlay.parentElement.querySelector('.js-zone-label'));
        });
    }

    window.ColorimeterSample = { pick, watch, watchAll, constants: C };
})();
