const handoffKey = 'tempmail.navigation';
const handoffLifetime = 15000;
const navigationTimeout = 30000;

export function mountPageProgress(element) {
    if (!element || element.dataset.mounted) return;
    element.dataset.mounted = 'true';

    const fill = element.firstElementChild;
    const motion = window.matchMedia?.('(prefers-reduced-motion: reduce)');
    let frame;
    let fade;
    let hide;
    let navigation;
    let requests = 0;
    let requestFailed = false;

    function update(value) {
        fill.style.transform = 'scaleX(' + value + ')';
    }

    function reset() {
        clearTimeout(fade);
        clearTimeout(hide);
        cancelAnimationFrame(frame);
        element.hidden = true;
        delete element.dataset.state;
    }

    function begin() {
        if (element.dataset.state === 'active') return;
        reset();
        element.hidden = false;
        element.dataset.state = 'active';
        update(motion?.matches ? 0.3 : 0.09);

        if (motion?.matches) return;
        const started = performance.now();
        const advance = time => {
            update(motion?.matches ? 0.3 : 0.88 - 0.79 * Math.exp(-(time - started) / 2600));
            if (time - started < 15000 && !motion?.matches) frame = requestAnimationFrame(advance);
        };
        frame = requestAnimationFrame(advance);
    }

    function settle(success) {
        if (navigation || requests || element.hidden) return;
        cancelAnimationFrame(frame);
        clearTimeout(fade);
        clearTimeout(hide);

        if (motion?.matches) {
            reset();
            return;
        }

        element.dataset.state = success ? 'complete' : 'fading';
        if (success) {
            update(1);
            fade = setTimeout(() => { element.dataset.state = 'fading'; }, 140);
        }
        hide = setTimeout(reset, success ? 320 : 180);
    }

    function armNavigation(signal) {
        if (signal?.aborted) return;
        clearTimeout(navigation?.timeout);
        const token = {};
        const cancel = () => {
            if (navigation !== token) return;
            clearTimeout(token.timeout);
            navigation = undefined;
            settle(false);
        };
        token.timeout = setTimeout(cancel, navigationTimeout);
        token.cancel = cancel;
        navigation = token;
        signal?.addEventListener('abort', cancel, {once: true});
        begin();
    }

    function eligible(destination) {
        return destination.origin === location.origin && ['https:', 'http:'].includes(destination.protocol);
    }

    function nativeTarget(target) {
        return !target || target.toLowerCase() === '_self';
    }

    if (window.navigation?.addEventListener) {
        window.navigation.addEventListener('navigate', event => {
            const source = event.sourceElement;
            if (event.defaultPrevented || event.destination.sameDocument || event.downloadRequest != null
                || source?.closest('[data-page-progress="off"], form[enctype="multipart/form-data"]')
                || [...(event.formData?.values() ?? [])].some(value => value instanceof File)
                || !eligible(new URL(event.destination.url))) return;

            armNavigation(event.signal);
        });
        window.navigation.addEventListener('navigateerror', () => navigation?.cancel());
    } else {
        window.addEventListener('click', event => {
            const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (event.defaultPrevented || !link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
                || link.hasAttribute('download') || !nativeTarget(link.target) || link.closest('[data-page-progress="off"]')) return;

            const destination = new URL(link.href, location.href);
            if (!eligible(destination) || (destination.pathname === location.pathname && destination.search === location.search
                && (destination.hash || link.getAttribute('href').startsWith('#')))) return;
            setTimeout(() => { if (!event.defaultPrevented) armNavigation(); }, 0);
        });

        window.addEventListener('submit', event => {
            const form = event.target;
            if (event.defaultPrevented || !(form instanceof HTMLFormElement)
                || form.closest('[data-page-progress="off"]') || event.submitter?.closest('[data-page-progress="off"]')
                || !nativeTarget(event.submitter?.getAttribute('formtarget') ?? form.target)
                || (event.submitter?.getAttribute('formmethod') ?? form.method) === 'dialog'
                || (event.submitter?.getAttribute('formenctype') ?? form.enctype) === 'multipart/form-data'
                || !eligible(new URL(event.submitter?.getAttribute('formaction') ?? form.action, location.href))) return;
            setTimeout(() => { if (!event.defaultPrevented) armNavigation(); }, 0);
        });
    }

    window.addEventListener('pagehide', () => {
        if (navigation) {
            try { sessionStorage.setItem(handoffKey, String(Date.now())); } catch {}
        }
        clearTimeout(navigation?.timeout);
        navigation = undefined;
        requests = 0;
        reset();
    });

    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            try { sessionStorage.removeItem(handoffKey); } catch {}
            reset();
        }
    });

    try {
        const timestamp = Number(sessionStorage.getItem(handoffKey));
        sessionStorage.removeItem(handoffKey);
        const elapsed = Date.now() - timestamp;
        if (elapsed >= 0 && elapsed < handoffLifetime && !motion?.matches) {
            begin();
            cancelAnimationFrame(frame);
            update(0.88);
            frame = requestAnimationFrame(() => {
                frame = requestAnimationFrame(() => settle(true));
            });
        }
    } catch {}

    return {
        async track(operation) {
            if (requests === 0) requestFailed = false;
            requests++;
            begin();
            let success = false;
            try {
                const result = await operation;
                success = !(result instanceof Response) || result.ok;
                return result;
            } finally {
                requestFailed ||= !success;
                requests = Math.max(0, requests - 1);
                settle(!requestFailed);
            }
        },
    };
}
