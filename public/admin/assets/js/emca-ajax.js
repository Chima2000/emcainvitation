/**
 * Submits forms in the background, shows a loading state on the button and a slim progress bar,
 * then swaps the changed parts of the page instead of letting the browser reload it.
 *
 * Markup contract:
 * - body[data-emca-layout]      layouts with the same value can be swapped into each other.
 * - body[data-emca-swap="all"]  replace every [data-emca-region]; otherwise only the region around the form.
 * - [data-emca-region="name"]   parts of the page that are replaced after a submit.
 * - [data-emca-sync="name"]     small pieces outside the regions (wallet chip, badges, menu) refreshed when they change.
 * - form[data-emca-native]      submit normally, with the loading state only.
 * - script[data-emca-rerun]     external page script that must run again after a swap.
 */
(() => {
    if (window.emcaAjax) {
        return;
    }

    const config = document.currentScript?.dataset ?? {};
    const messages = {
        error: config.error || 'Something went wrong. Please try again.',
        offline: config.offline || 'We could not reach the server. Check your connection and try again.',
        expired: config.expired || 'Your session expired. Reloading the page…',
        throttled: config.throttled || 'Too many attempts. Wait a moment and try again.',
        forbidden: config.forbidden || 'You are not allowed to do that.',
        close: config.close || 'Close',
    };

    const stripHash = (url) => url.split('#')[0];
    let renderedUrl = stripHash(window.location.href);
    const loadedScripts = new Set(
        [...document.querySelectorAll('script[src]')].map((script) => new URL(script.getAttribute('src'), window.location.href).href),
    );
    let serverBodyClasses = [...document.body.classList];
    const menuSignature = (menu) => [...menu.querySelectorAll('a, button')]
        .map((link) => `${link.getAttribute('href') ?? ''}|${link.textContent.replace(/\s+/g, ' ').trim()}`)
        .join('\n');

    const progress = (() => {
        let bar = null;
        let timer = null;
        let value = 0;
        let active = 0;

        const element = () => {
            if (!bar || !bar.isConnected) {
                bar = document.createElement('div');
                bar.className = 'emca-progress';
                bar.setAttribute('aria-hidden', 'true');
                bar.innerHTML = '<div class="emca-progress-bar"></div>';
                document.body.appendChild(bar);
            }

            return bar;
        };

        const set = (next) => {
            value = next;
            element().firstElementChild.style.transform = `scaleX(${next})`;
        };

        return {
            start() {
                active++;
                element().classList.remove('is-done');
                element().classList.add('is-active');

                if (active > 1) {
                    return;
                }

                set(0.08);
                window.clearInterval(timer);
                timer = window.setInterval(() => {
                    if (value < 0.92) {
                        set(value + (0.92 - value) * 0.07);
                    }
                }, 180);
            },
            done() {
                active = Math.max(0, active - 1);

                if (active > 0) {
                    return;
                }

                window.clearInterval(timer);
                set(1);
                element().classList.add('is-done');
                window.setTimeout(() => {
                    if (active === 0) {
                        element().classList.remove('is-active', 'is-done');
                        set(0);
                    }
                }, 420);
            },
        };
    })();

    const setLoading = (button, loading) => {
        if (!(button instanceof HTMLElement)) {
            return;
        }

        if (loading) {
            if (button.classList.contains('emca-is-loading')) {
                return;
            }

            button.style.setProperty('--emca-spinner-color', window.getComputedStyle(button).color);
            button.style.setProperty('--emca-loading-width', `${button.offsetWidth}px`);

            if (button instanceof HTMLButtonElement) {
                const spinner = document.createElement('span');
                spinner.className = 'emca-btn-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                button.appendChild(spinner);
            }

            button.classList.add('emca-is-loading');
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('aria-disabled', 'true');

            return;
        }

        button.classList.remove('emca-is-loading');
        button.removeAttribute('aria-busy');
        button.removeAttribute('aria-disabled');
        button.style.removeProperty('--emca-spinner-color');
        button.style.removeProperty('--emca-loading-width');
        button.querySelectorAll(':scope > .emca-btn-spinner').forEach((spinner) => spinner.remove());
    };

    const toast = (message, tone = 'error') => {
        let stack = document.querySelector('.emca-toast-stack');

        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'emca-toast-stack';
            stack.setAttribute('aria-live', 'assertive');
            document.body.appendChild(stack);
        }

        const item = document.createElement('div');
        item.className = `emca-toast emca-toast-${tone}`;
        item.setAttribute('role', 'alert');
        item.innerHTML = '<span class="emca-toast-icon" aria-hidden="true"></span><span class="emca-toast-text"></span><button type="button" class="emca-toast-close"></button>';
        item.querySelector('.emca-toast-text').textContent = message;
        item.querySelector('.emca-toast-close').setAttribute('aria-label', messages.close);
        item.querySelector('.emca-toast-close').textContent = '×';

        const dismiss = () => {
            item.classList.remove('is-visible');
            window.setTimeout(() => item.remove(), 250);
        };

        item.querySelector('.emca-toast-close').addEventListener('click', dismiss);
        stack.appendChild(item);
        window.requestAnimationFrame(() => item.classList.add('is-visible'));
        window.setTimeout(dismiss, 6500);
    };

    const closeOpenModals = () => {
        const openIds = [];

        document.querySelectorAll('.modal.show').forEach((modal) => {
            if (modal.id) {
                openIds.push(modal.id);
            }

            modal.classList.remove('fade');
            window.bootstrap?.Modal?.getInstance(modal)?.hide();
        });

        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        return openIds;
    };

    const runScripts = async (scripts) => {
        if (scripts.length === 0) {
            return;
        }

        const originalDocumentListener = document.addEventListener;
        const originalWindowListener = window.addEventListener;
        const runNow = (original, target) => function (type, listener, options) {
            if (type === 'DOMContentLoaded' || type === 'load') {
                window.queueMicrotask(() => {
                    const event = new Event(type);

                    typeof listener === 'function' ? listener.call(target, event) : listener?.handleEvent?.(event);
                });

                return undefined;
            }

            return original.call(this, type, listener, options);
        };

        document.addEventListener = runNow(originalDocumentListener, document);
        window.addEventListener = runNow(originalWindowListener, window);

        try {
            for (const inert of scripts) {
                if (!inert.isConnected) {
                    continue;
                }

                const type = (inert.getAttribute('type') || '').toLowerCase();

                if (type && !['text/javascript', 'application/javascript', 'module'].includes(type)) {
                    continue;
                }

                const source = inert.getAttribute('src');

                if (source) {
                    const absolute = new URL(source, window.location.href).href;

                    if (loadedScripts.has(absolute) && !inert.hasAttribute('data-emca-rerun')) {
                        continue;
                    }

                    loadedScripts.add(absolute);
                }

                const live = document.createElement('script');
                [...inert.attributes].forEach((attribute) => live.setAttribute(attribute.name, attribute.value));
                live.textContent = inert.textContent;

                const loaded = source
                    ? new Promise((resolve) => {
                        live.addEventListener('load', resolve);
                        live.addEventListener('error', resolve);
                    })
                    : null;

                if (source) {
                    live.async = false;
                }

                inert.replaceWith(live);

                if (loaded) {
                    await loaded;
                }
            }
        } finally {
            await Promise.resolve();
            document.addEventListener = originalDocumentListener;
            window.addEventListener = originalWindowListener;
        }
    };

    const syncHead = (doc) => {
        document.title = doc.title || document.title;

        const present = new Set(
            [...document.head.querySelectorAll('link[rel="stylesheet"]')].map((link) => link.href),
        );

        doc.head.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
            const href = new URL(link.getAttribute('href'), window.location.href).href;

            if (!present.has(href)) {
                document.head.appendChild(document.importNode(link, true));
            }
        });
    };

    const syncPieces = (doc, pathChanged) => {
        document.querySelectorAll('[data-emca-sync]').forEach((current) => {
            const key = current.dataset.emcaSync;
            const next = doc.querySelector(`[data-emca-sync="${key}"]`);

            if (!next) {
                return;
            }

            if (key === 'menu') {
                if (!pathChanged && menuSignature(current) === menuSignature(next)) {
                    return;
                }

                current.replaceWith(document.importNode(next, true));

                if (typeof window.initSidebarMenu === 'function') {
                    window.initSidebarMenu();
                }

                return;
            }

            if (current.outerHTML !== next.outerHTML) {
                current.replaceWith(document.importNode(next, true));
            }
        });
    };

    const scrollToFeedback = (regions, pathChanged) => {
        if (pathChanged) {
            window.scrollTo({ top: 0 });

            return;
        }

        if (document.querySelector('.modal[data-emca-open="1"]')) {
            return;
        }

        const feedback = regions
            .map((region) => region.querySelector('.alert, .is-invalid, .form-error, .invalid-feedback.d-block'))
            .find(Boolean);

        if (!feedback) {
            return;
        }

        const box = feedback.getBoundingClientRect();

        if (box.top < 80 || box.bottom > window.innerHeight) {
            feedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    const swap = async (doc, form, finalUrl) => {
        const swapAll = document.body.dataset.emcaSwap === 'all';
        const names = swapAll
            ? [...document.querySelectorAll('[data-emca-region]')].map((region) => region.dataset.emcaRegion)
            : [form.closest('[data-emca-region]')?.dataset.emcaRegion].filter(Boolean);
        const pairs = names.map((name) => [
            document.querySelector(`[data-emca-region="${name}"]`),
            doc.querySelector(`[data-emca-region="${name}"]`),
        ]);

        if (pairs.length === 0 || pairs.some(([current, next]) => !current || !next)) {
            return false;
        }

        const previousPath = renderedUrl;
        const pathChanged = new URL(finalUrl).pathname !== new URL(previousPath).pathname;
        const reopened = closeOpenModals();

        syncHead(doc);

        if (swapAll) {
            document.body.classList.remove(...serverBodyClasses);
            serverBodyClasses = [...doc.body.classList];
            document.body.classList.add(...serverBodyClasses);
        }

        const regions = pairs.map(([current, next]) => {
            const imported = document.importNode(next, true);
            current.replaceWith(imported);

            return imported;
        });

        syncPieces(doc, pathChanged);

        if (finalUrl !== stripHash(window.location.href)) {
            if (new URL(finalUrl).href === new URL(previousPath).href) {
                window.history.replaceState(window.history.state, '', finalUrl);
            } else {
                window.history.pushState({ emca: true }, '', finalUrl);
            }
        }

        renderedUrl = stripHash(finalUrl);

        reopened.forEach((id) => {
            const modal = document.getElementById(id);

            if (modal?.dataset.emcaOpen === '1' && modal.classList.contains('fade')) {
                modal.classList.remove('fade');
                modal.addEventListener('shown.bs.modal', () => modal.classList.add('fade'), { once: true });
            }
        });

        await runScripts(regions.flatMap((region) => [...region.querySelectorAll('script')]));

        document.dispatchEvent(new CustomEvent('emca:page-load', { detail: { url: finalUrl } }));
        window.ScrollTrigger?.refresh?.();
        scrollToFeedback(regions, pathChanged);

        return true;
    };

    const download = async (response) => {
        const disposition = response.headers.get('content-disposition') || '';
        const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition);
        const link = document.createElement('a');
        const url = URL.createObjectURL(await response.blob());

        link.href = url;
        link.download = match ? decodeURIComponent(match[1]) : '';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    const failureMessage = (status) => {
        if (status === 429) {
            return messages.throttled;
        }

        if (status === 403) {
            return messages.forbidden;
        }

        return messages.error;
    };

    const handleResponse = async (response, form, requestUrl) => {
        const finalUrl = stripHash(response.url || requestUrl);
        const type = response.headers.get('content-type') || '';

        if (response.status === 419) {
            toast(messages.expired, 'info');
            window.setTimeout(() => window.location.reload(), 1400);

            return 'leaving';
        }

        if (/attachment/i.test(response.headers.get('content-disposition') || '')) {
            await download(response);

            return 'done';
        }

        if (!type.includes('text/html')) {
            if (!response.ok) {
                toast(failureMessage(response.status));

                return 'done';
            }

            window.location.assign(finalUrl);

            return 'leaving';
        }

        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const sameLayout = doc.body?.dataset.emcaLayout && doc.body.dataset.emcaLayout === document.body.dataset.emcaLayout;

        if (!sameLayout) {
            if (!response.ok) {
                toast(failureMessage(response.status));

                return 'done';
            }

            window.location.assign(finalUrl);

            return 'leaving';
        }

        if (await swap(doc, form, finalUrl)) {
            return 'done';
        }

        window.location.assign(finalUrl);

        return 'leaving';
    };

    const defaultSubmitter = (form) => form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]')
        ?? (form.id ? document.querySelector(`button[form="${form.id}"][type="submit"], button[form="${form.id}"]:not([type])`) : null);

    const buildFormData = (form, submitter) => {
        try {
            return new FormData(form, submitter ?? undefined);
        } catch {
            const data = new FormData(form);

            if (submitter?.name) {
                data.append(submitter.name, submitter.value);
            }

            return data;
        }
    };

    const submit = async (form, submitter, method) => {
        const action = submitter?.hasAttribute('formaction') ? submitter.formAction : form.action;
        const requestUrl = new URL(action || window.location.href, window.location.href);

        if (requestUrl.origin !== window.location.origin) {
            HTMLFormElement.prototype.submit.call(form);

            return;
        }

        const data = buildFormData(form, submitter);
        const init = {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'text/html, application/xhtml+xml',
                'X-Emca-Layout': document.body.dataset.emcaLayout,
            },
        };

        if (method === 'GET') {
            requestUrl.search = new URLSearchParams(
                [...data].filter(([, value]) => typeof value === 'string'),
            ).toString();
        } else {
            init.body = data;
        }

        form.dataset.emcaBusy = '1';
        form.classList.add('emca-form-busy');
        document.documentElement.classList.add('emca-busy');
        setLoading(submitter, true);
        progress.start();

        let outcome = 'done';

        try {
            const response = await window.fetch(requestUrl.href, init);
            outcome = await handleResponse(response, form, requestUrl.href);
        } catch (error) {
            console.error(error);
            toast(messages.offline);
        } finally {
            if (outcome !== 'leaving') {
                delete form.dataset.emcaBusy;
                form.classList.remove('emca-form-busy');
                document.documentElement.classList.remove('emca-busy');
                setLoading(submitter, false);
                progress.done();
            }
        }
    };

    window.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        if (form.dataset.emcaBusy === '1') {
            event.preventDefault();

            return;
        }

        const submitter = event.submitter ?? defaultSubmitter(form);
        const method = (submitter?.getAttribute('formmethod') || form.getAttribute('method') || 'GET').toUpperCase();
        const target = submitter?.getAttribute('formtarget') || form.getAttribute('target');

        if (method === 'DIALOG' || (target && target !== '_self')) {
            return;
        }

        const canSwap = document.body.dataset.emcaLayout
            && (document.body.dataset.emcaSwap === 'all' || form.closest('[data-emca-region]'))
            && typeof window.fetch === 'function';

        if (form.hasAttribute('data-emca-native') || !canSwap) {
            form.dataset.emcaBusy = '1';
            setLoading(submitter, true);
            progress.start();

            return;
        }

        event.preventDefault();
        submit(form, submitter, method);
    });

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }

        document.querySelectorAll('form[data-emca-busy]').forEach((form) => delete form.dataset.emcaBusy);
        document.querySelectorAll('.emca-is-loading').forEach((button) => setLoading(button, false));
        document.querySelector('.emca-progress')?.classList.remove('is-active', 'is-done');
    });

    window.addEventListener('popstate', () => {
        if (stripHash(window.location.href) !== renderedUrl) {
            window.location.reload();
        }
    });

    window.emcaAjax = { toast, progress, setLoading };
})();
