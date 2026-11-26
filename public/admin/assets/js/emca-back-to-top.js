(() => {
    const wrapper = document.querySelector('.back-to-top-wrapper');
    const button = document.getElementById('back_to_top');

    if (!wrapper || !button || wrapper.dataset.emcaReady === '1') {
        return;
    }

    wrapper.dataset.emcaReady = '1';

    const toggle = () => {
        wrapper.classList.toggle('back-to-top-btn-show', window.scrollY > 300);
    };

    toggle();
    window.addEventListener('scroll', toggle, { passive: true });

    button.addEventListener('click', () => {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const root = document.scrollingElement || document.documentElement;

        root.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
})();
