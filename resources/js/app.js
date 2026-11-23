import Alpine from 'alpinejs';

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------
 | Scroll reveal
 | <section data-reveal>          → fades/slides in when scrolled into view
 | <div data-reveal-stagger>      → its [data-reveal] children get staggered delays
 * ------------------------------------------------------------------ */
function initReveal() {
    document.querySelectorAll('[data-reveal-stagger]').forEach((group) => {
        group.querySelectorAll(':scope > [data-reveal]').forEach((el, i) => {
            el.style.setProperty('--reveal-delay', `${Math.min(i, 8) * 70}ms`);
        });
    });

    const items = document.querySelectorAll('[data-reveal]');
    if (reduceMotion() || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    items.forEach((el) => observer.observe(el));
}

/* ------------------------------------------------------------------
 | Add to basket: fly the product photo into the basket icon, then submit.
 | The form posts normally afterwards, so server-side behaviour is unchanged.
 * ------------------------------------------------------------------ */
window.flyToCart = function (form) {
    const target = document.querySelector('[data-cart-target]');
    const source = form.closest('[data-product]')?.querySelector('[data-product-image]');

    if (reduceMotion() || !target || !source || !source.animate) {
        form.submit();
        return;
    }

    const from = source.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const size = Math.min(from.width, from.height, 140);

    const ghost = document.createElement('img');
    ghost.src = source.currentSrc || source.src;
    ghost.className = 'fly-to-cart';
    Object.assign(ghost.style, {
        width: `${size}px`,
        height: `${size}px`,
        left: `${from.left + from.width / 2 - size / 2}px`,
        top: `${from.top + from.height / 2 - size / 2}px`,
    });
    document.body.appendChild(ghost);

    const dx = to.left + to.width / 2 - (from.left + from.width / 2);
    const dy = to.top + to.height / 2 - (from.top + from.height / 2);

    ghost.animate([
        { transform: 'translate(0, 0) scale(1)', opacity: 1 },
        { transform: `translate(${dx * 0.5}px, ${dy - 80}px) scale(0.6)`, opacity: 0.95, offset: 0.55 },
        { transform: `translate(${dx}px, ${dy}px) scale(0.12)`, opacity: 0.4 },
    ], { duration: 700, easing: 'cubic-bezier(0.55, 0, 0.35, 1)' }).onfinish = () => {
        target.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.2)' }, { transform: 'scale(1)' }],
            { duration: 300, easing: 'ease-out' },
        );
        form.submit();
    };
};

/* ------------------------------------------------------------------
 | Alpine components
 * ------------------------------------------------------------------ */
document.addEventListener('alpine:init', () => {
    // Numbers that count up from 0 when they scroll into view: x-data="countUp(42)"
    Alpine.data('countUp', (target, duration = 1100) => ({
        display: target,
        init() {
            if (reduceMotion() || target === 0) return;
            this.display = 0;
            const start = (t0) => {
                const step = (now) => {
                    const p = Math.min((now - t0) / duration, 1);
                    this.display = Math.round(target * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            };
            const io = new IntersectionObserver(([e]) => {
                if (e.isIntersecting) { start(performance.now()); io.disconnect(); }
            });
            io.observe(this.$el);
        },
    }));

    // Image that shows a shimmer skeleton until it has loaded: x-data="imageLoader"
    Alpine.data('imageLoader', () => ({
        loaded: false,
        init() {
            const img = this.$el.querySelector('img');
            if (!img || (img.complete && img.naturalWidth)) { this.loaded = true; return; }
            img.addEventListener('load', () => (this.loaded = true), { once: true });
            img.addEventListener('error', () => (this.loaded = true), { once: true });
        },
    }));

    // Horizontal snap carousel with prev/next buttons: x-data="carousel"
    Alpine.data('carousel', () => ({
        atStart: true,
        atEnd: false,
        init() { this.update(); },
        update() {
            const t = this.$refs.track;
            this.atStart = t.scrollLeft <= 4;
            this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4;
        },
        go(dir) {
            const t = this.$refs.track;
            t.scrollBy({ left: dir * t.clientWidth * 0.85, behavior: reduceMotion() ? 'auto' : 'smooth' });
        },
    }));

    // Toast notification that slides in and dismisses itself: x-data="toast"
    Alpine.data('toast', (timeout = 5000) => ({
        open: true,
        init() { setTimeout(() => (this.open = false), timeout); },
    }));
});

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', initReveal);
