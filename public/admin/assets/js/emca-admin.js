(() => {
    const keepBrandTheme = () => {
        document.documentElement.setAttribute('data-admin-theme', 'emca');
        document.documentElement.setAttribute('data-color-theme', 'emca');
    };

    keepBrandTheme();

    const moneyFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
    const moneyDigits = (value) => String(value ?? '').replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
    const formatMoney = (value) => {
        const digits = moneyDigits(value);

        return digits === '' ? '' : moneyFormatter.format(Number(digits));
    };

    const formatMoneyInput = (input, keepCaret = false) => {
        const caret = keepCaret ? input.selectionStart ?? input.value.length : null;
        const digitsBeforeCaret = caret === null ? 0 : moneyDigits(input.value.slice(0, caret)).length;
        const formatted = formatMoney(input.value);

        if (input.value === formatted) {
            return;
        }

        input.value = formatted;

        if (caret === null) {
            return;
        }

        let position = 0;
        let seen = 0;

        while (position < formatted.length && seen < digitsBeforeCaret) {
            if (/\d/.test(formatted[position])) {
                seen++;
            }

            position++;
        }

        input.setSelectionRange(position, position);
    };

    window.emcaFormatMoney = formatMoney;

    const initPage = () => {
        keepBrandTheme();

        if (window.bootstrap?.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
                window.bootstrap.Tooltip.getOrCreateInstance(element);
            });
        }

        document.querySelectorAll('input[data-emca-money]').forEach((input) => formatMoneyInput(input));

        if (window.bootstrap?.Modal) {
            document.querySelectorAll('.modal[data-emca-open="1"]').forEach((modal) => {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        }

        highlightEventSection();
        dismissSuccessMessages();
    };

    const dismissSuccessMessages = () => {
        if (!document.body.dataset.emcaLayout?.startsWith('dashboard')) {
            return;
        }

        document.querySelectorAll('.alert-success:not([data-door-result]):not([data-emca-persist])').forEach((message) => {
            if (message.dataset.emcaDismissReady === '1') {
                return;
            }

            message.dataset.emcaDismissReady = '1';
            window.setTimeout(() => {
                message.style.transition = 'opacity 0.2s ease';
                message.style.setProperty('opacity', '0', 'important');
                window.setTimeout(() => message.remove(), 200);
            }, 3000);
        });
    };

    const highlightEventSection = () => {
        document.querySelectorAll('.menubar > li.menu-arrow > a.menu-link').forEach((link) => {
            link.classList.remove('active');
        });

        const links = [...document.querySelectorAll('a[data-emca-section]')];
        const visible = links.filter((link) => document.getElementById(link.dataset.emcaSection));

        if (visible.length === 0) {
            return;
        }

        const line = 96;
        let current = visible[0];

        visible.forEach((link) => {
            const section = document.getElementById(link.dataset.emcaSection);

            if (section.getBoundingClientRect().top <= line) {
                current = link;
            }
        });

        document.querySelectorAll('a[data-emca-event-link]').forEach((link) => {
            link.classList.toggle('active', link === current);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.setTimeout(keepBrandTheme, 0);

        const menubar = document.getElementById('appMenubar');
        const backdrop = document.querySelector('[data-emca-menubar-close]');

        if (menubar && backdrop) {
            const sync = () => backdrop.classList.toggle('is-visible', menubar.classList.contains('open'));

            new MutationObserver(sync).observe(menubar, { attributes: true, attributeFilter: ['class'] });
            backdrop.addEventListener('click', () => menubar.classList.remove('open'));
        }

        initPage();

        if (!window.emcaSectionSpy) {
            window.emcaSectionSpy = true;
            let frame = 0;

            document.addEventListener('scroll', () => {
                if (frame) {
                    return;
                }

                frame = window.requestAnimationFrame(() => {
                    frame = 0;
                    highlightEventSection();
                });
            }, { passive: true, capture: true });

            window.addEventListener('hashchange', highlightEventSection);
        }
    });

    document.addEventListener('emca:page-load', initPage);

    document.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.hasAttribute('data-emca-money')) {
            formatMoneyInput(event.target, true);
        }
    });

    document.addEventListener('formdata', (event) => {
        event.target.querySelectorAll('input[data-emca-money][name]').forEach((input) => {
            if (!input.disabled) {
                event.formData.set(input.name, moneyDigits(input.value));
            }
        });
    }, true);

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-emca-fill]');

        if (!trigger) {
            return;
        }

        const target = document.querySelector(trigger.dataset.bsTarget);
        const values = JSON.parse(trigger.dataset.emcaFill || '{}');

        if (!target) {
            return;
        }

        const form = target.querySelector('form[data-emca-main]') || target.querySelector('form');
        const fieldRoot = form || target;

        if (form && trigger.dataset.emcaAction) {
            form.action = trigger.dataset.emcaAction;
            form.querySelectorAll('[name="_form_action"]').forEach((field) => {
                field.value = trigger.dataset.emcaAction;
            });
        }

        Object.entries(values).forEach(([name, value]) => {
            fieldRoot.querySelectorAll(`[name="${name}"]`).forEach((field) => {
                if (field.type === 'hidden' && typeof value === 'boolean') {
                    return;
                }

                if (field.type === 'checkbox') {
                    field.checked = Boolean(value);
                } else if (field.type === 'radio') {
                    field.checked = String(field.value) === String(value);
                } else if (field.type === 'file') {
                    field.value = '';
                } else if (field.hasAttribute('data-emca-money')) {
                    field.value = formatMoney(value);
                } else {
                    field.value = value ?? '';
                }
            });

            target.querySelectorAll(`[data-emca-text="${name}"]`).forEach((node) => {
                node.textContent = value ?? '';
            });
        });
    });

    const confirmModal = () => document.getElementById('emcaConfirmModal');
    let pendingForm = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.emcaConfirm || form.dataset.emcaConfirmed === '1') {
            return;
        }

        const modal = confirmModal();

        if (!modal || !window.bootstrap?.Modal) {
            if (!window.confirm(form.dataset.emcaConfirm)) {
                event.preventDefault();
            }

            return;
        }

        event.preventDefault();
        pendingForm = form;
        modal.querySelector('[data-emca-confirm-message]').textContent = form.dataset.emcaConfirm;
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-emca-confirm-accept]') || !pendingForm) {
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        window.bootstrap.Modal.getOrCreateInstance(confirmModal()).hide();
        form.dataset.emcaConfirmed = '1';
        form.requestSubmit();
        delete form.dataset.emcaConfirmed;
    });
})();
