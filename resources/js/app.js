import Alpine from 'alpinejs';
import { animate, inView } from 'motion';

window.Alpine = Alpine;

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const money = (cents) =>
    cents === 0 ? 'Free' : new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);

/* ---------------- Alpine components ---------------- */

// Live countdown to an ISO timestamp (used for checkout stock holds).
Alpine.data('countdown', (expiresAt, onExpire = null) => ({
    remaining: 0,
    timer: null,
    init() {
        const target = new Date(expiresAt).getTime();
        const tick = () => {
            this.remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
            if (this.remaining === 0) {
                clearInterval(this.timer);
                if (onExpire) window.location.href = onExpire;
            }
        };
        tick();
        this.timer = setInterval(tick, 1000);
    },
    get minutes() {
        return String(Math.floor(this.remaining / 60)).padStart(2, '0');
    },
    get seconds() {
        return String(this.remaining % 60).padStart(2, '0');
    },
    get urgent() {
        return this.remaining > 0 && this.remaining <= 120;
    },
    get progress() {
        return Math.min(100, (this.remaining / 600) * 100);
    },
}));

// Ticket quantity stepper with a live order summary.
Alpine.data('ticketPicker', (types, maxTotal = 10) => ({
    types,
    qty: Object.fromEntries(types.map((t) => [t.id, 0])),
    money,
    get count() {
        return Object.values(this.qty).reduce((a, b) => a + b, 0);
    },
    get total() {
        return this.types.reduce((sum, t) => sum + t.price_cents * this.qty[t.id], 0);
    },
    canInc(t) {
        return this.qty[t.id] < Math.min(t.max, t.available) && this.count < maxTotal;
    },
    inc(t) {
        if (this.canInc(t)) this.qty[t.id]++;
    },
    dec(t) {
        if (this.qty[t.id] > 0) this.qty[t.id]--;
    },
}));

// Animated credit-card preview for the (test-mode) checkout.
Alpine.data('cardForm', () => ({
    name: '',
    number: '',
    expiry: '',
    cvc: '',
    flipped: false,
    busy: false,
    get display() {
        const digits = this.number.replace(/\D/g, '').padEnd(16, '•').slice(0, 16);
        return digits.replace(/(.{4})/g, '$1 ').trim();
    },
    formatNumber() {
        this.number = this.number.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
    },
    formatExpiry() {
        const d = this.expiry.replace(/\D/g, '').slice(0, 4);
        this.expiry = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
    },
}));

// Staff QR scanner. The camera library is loaded on demand so other pages stay light.
Alpine.data('scanner', (verifyUrl, csrf) => ({
    status: 'idle', // idle | scanning | ok | warn | bad
    message: 'Point the camera at a ticket QR code',
    detail: '',
    manual: '',
    busy: false,
    counts: { checked_in: 0, total: 0 },
    history: [],
    reader: null,
    init() {
        const el = this.$el.dataset;
        this.counts = { checked_in: Number(el.checkedIn || 0), total: Number(el.total || 0) };
    },
    async start() {
        const { Html5Qrcode } = await import('html5-qrcode');
        this.reader = new Html5Qrcode('qr-reader');
        this.status = 'scanning';
        try {
            await this.reader.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (text) => this.submit(text),
                () => {},
            );
        } catch (e) {
            this.status = 'bad';
            this.message = 'Camera unavailable';
            this.detail = 'Allow camera access, or enter the code manually below.';
        }
    },
    async stop() {
        if (this.reader?.isScanning) await this.reader.stop();
        this.status = 'idle';
        this.message = 'Scanner stopped';
    },
    async submit(raw) {
        const code = String(raw).trim().split('/').pop();
        if (!code || this.busy || code === this.last) return;
        this.busy = true;
        this.last = code;
        try {
            const res = await fetch(verifyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ code }),
            });
            const data = await res.json();
            this.status = data.result === 'ok' ? 'ok' : data.result === 'used' ? 'warn' : 'bad';
            this.message = data.message;
            this.detail = data.detail || '';
            if (data.counts) this.counts = data.counts;
            this.history.unshift({ ...data, at: new Date().toLocaleTimeString() });
            this.history = this.history.slice(0, 8);
            navigator.vibrate?.(data.result === 'ok' ? 60 : [80, 60, 80]);
        } catch (e) {
            this.status = 'bad';
            this.message = 'Network error';
            this.detail = 'Check your connection and try again.';
        } finally {
            setTimeout(() => {
                this.busy = false;
                this.last = null;
            }, 1800);
            this.manual = '';
        }
    },
}));

Alpine.start();

/* ---------------- Motion: reveal, counters, tilt, confetti ---------------- */

// Rise-and-fade using the individual `translate`/`scale` CSS properties. Unlike animating
// `transform`, these compose with the 3D tilt transform instead of overwriting it.
const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
const rise = (el, { delay = 0, from = '0 26px', duration = 800 } = {}) =>
    el.animate(
        [
            { opacity: 0, translate: from, scale: 0.985 },
            { opacity: 1, translate: '0 0', scale: 1 },
        ],
        { duration, delay, easing: EASE, fill: 'both' },
    );

document.addEventListener('DOMContentLoaded', () => {
    const shown = new WeakSet();
    const show = (el, opts) => {
        shown.add(el);
        rise(el, opts);
    };

    // Reveal on scroll (with a safety net so content can never stay hidden).
    const revealables = [...document.querySelectorAll('[data-reveal]')];
    if (reduceMotion) {
        revealables.forEach((el) => (el.style.opacity = 1));
    } else {
        inView(
            '[data-reveal]',
            (el) => {
                const delay = Number(el.dataset.delay || 0) * 1000;
                const from = el.dataset.reveal === 'left' ? '-28px 0' : el.dataset.reveal === 'right' ? '28px 0' : '0 26px';
                show(el, { delay, from });
            },
            { margin: '0px 0px -8% 0px' },
        );
        setTimeout(() => revealables.filter((el) => !shown.has(el)).forEach((el) => (el.style.opacity = 1)), 2500);
    }

    // Staggered children, e.g. card grids: <div data-stagger>…</div>
    document.querySelectorAll('[data-stagger]').forEach((group) => {
        const kids = [...group.children];
        if (reduceMotion) return;
        kids.forEach((k) => (k.style.opacity = 0));
        inView(group, () => kids.forEach((k, i) => show(k, { delay: i * 70, from: '0 24px', duration: 650 })));
        setTimeout(() => kids.filter((k) => !shown.has(k)).forEach((k) => (k.style.opacity = 1)), 2500);
    });

    // Animated number counters: <span data-count-to="1200" data-prefix="$">
    document.querySelectorAll('[data-count-to]').forEach((el) => {
        const to = Number(el.dataset.countTo);
        const fmt = (v) => `${el.dataset.prefix || ''}${Math.round(v).toLocaleString('en-US')}${el.dataset.suffix || ''}`;
        if (reduceMotion) return void (el.textContent = fmt(to));
        el.textContent = fmt(0);
        inView(el, () => {
            animate(0, to, { duration: 1.6, ease: [0.22, 1, 0.36, 1], onUpdate: (v) => (el.textContent = fmt(v)) });
        });
    });

    // Pointer-driven 3D tilt with a moving glare.
    if (!reduceMotion && window.matchMedia('(hover: hover)').matches) {
        document.querySelectorAll('[data-tilt]').forEach((el) => {
            const max = Number(el.dataset.tilt) || 7;
            el.addEventListener('pointermove', (e) => {
                const r = el.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width;
                const py = (e.clientY - r.top) / r.height;
                el.style.setProperty('--ry', `${(px - 0.5) * max * 2}deg`);
                el.style.setProperty('--rx', `${(0.5 - py) * max * 2}deg`);
                el.style.setProperty('--mx', `${px * 100}%`);
                el.style.setProperty('--my', `${py * 100}%`);
            });
            el.addEventListener('pointerleave', () => {
                el.style.setProperty('--rx', '0deg');
                el.style.setProperty('--ry', '0deg');
            });
        });
    }

    // Celebration on pages that opt in: <div data-confetti>
    if (!reduceMotion && document.querySelector('[data-confetti]')) {
        import('canvas-confetti').then(({ default: confetti }) => {
            const colors = ['#7c5cff', '#ff4d8d', '#ffb84d', '#34d399', '#22d3ee'];
            confetti({ particleCount: 90, spread: 80, origin: { y: 0.62 }, colors });
            setTimeout(() => confetti({ particleCount: 60, angle: 60, spread: 70, origin: { x: 0, y: 0.7 }, colors }), 250);
            setTimeout(() => confetti({ particleCount: 60, angle: 120, spread: 70, origin: { x: 1, y: 0.7 }, colors }), 400);
        });
    }
});
