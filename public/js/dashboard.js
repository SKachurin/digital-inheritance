
// --- Smooth scroll + Scrollspy for the right nav ---
document.addEventListener('DOMContentLoaded', () => {
    initSideNavScroll();
});

function initSideNavScroll() {
    const links = Array.from(document.querySelectorAll('.aside-link'));
    if (!links.length) return;

    // Smooth scroll
    links.forEach(a => {
        a.addEventListener('click', e => {
            const hash = a.getAttribute('href') || '';
            if (!hash.startsWith('#')) return;
            const target = document.querySelector(hash);
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // Scrollspy (highlights active link)
    const sections = links
        .map(a => document.querySelector(a.getAttribute('href')))
        .filter(Boolean);

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const id = `#${entry.target.id}`;
            const link = document.querySelector(`.aside-link[href="${id}"]`);
            if (!link) return;
            if (entry.isIntersecting) {
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px', threshold: 0.01 });

    sections.forEach(s => obs.observe(s));
}

// Smooth scroll + Scrollspy for LEFT sidebar
document.addEventListener('DOMContentLoaded', () => {
    const links = Array.from(document.querySelectorAll('.aside-link'));
    if (!links.length) return;

    // Smooth scroll to sections
    links.forEach(a => {
        a.addEventListener('click', e => {
            const hash = a.getAttribute('href') || '';
            if (!hash.startsWith('#')) return;
            const target = document.querySelector(hash);
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // Highlight active link while scrolling
    const sections = links
        .map(a => document.querySelector(a.getAttribute('href')))
        .filter(Boolean);

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const id = `#${entry.target.id}`;
            const link = document.querySelector(`.aside-link[href="${id}"]`);
            if (!link) return;
            if (entry.isIntersecting) {
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px', threshold: 0.01 });

    sections.forEach(s => obs.observe(s));
});
