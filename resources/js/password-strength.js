import Alpine from 'alpinejs';

const sequences = ['abcdefghijklmnopqrstuvwxyz', '0123456789', 'qwertyuiopasdfghjklzxcvbnm'];
const patterns = sequences.flatMap(sequence => [sequence.repeat(2), [...sequence.repeat(2)].reverse().join('')]);

export function passwordFeedback(password, confirmation, policy) {
    const length = [...password].length;
    const normalized = password.trim().toLowerCase();
    const longEnough = length >= policy.minimum;
    const withinLimit = length <= policy.maximum && !password.includes('\0')
        && (policy.maximumBytes === null || new TextEncoder().encode(password).length <= policy.maximumBytes);
    const predictable = !normalized || policy.common.includes(normalized)
        || /^(.{1,4})\1+$/su.test(normalized)
        || patterns.some(pattern => pattern.includes(normalized));

    let score = 0;
    if (length) {
        score = 1;
        if (longEnough && withinLimit && !predictable) {
            score = length >= 22 ? 4 : length >= 16 ? 3 : 2;
            const unique = new Set(normalized).size;
            const hasSequence = [...normalized].some((_, index) => {
                const part = normalized.slice(index, index + 5);
                return part.length === 5 && patterns.some(pattern => pattern.includes(part));
            });
            if (unique < Math.min(8, normalized.length / 2) || hasSequence
                || /^(?:password|qwerty|welcome|letmein|changeme)/u.test(normalized)
                || /(.{1,5})\1{2,}/su.test(normalized)) {
                score = Math.max(1, score - 1);
            }
        }
    }

    return {
        score,
        level: ['', 'Weak', 'Fair', 'Good', 'Strong'][score],
        longEnough,
        withinLimit,
        uncommon: !predictable,
        match: confirmation === '' ? 'Enter the password again to confirm.' : password === confirmation
            ? 'Passwords match.' : 'Passwords do not match.',
    };
}

Alpine.data('uiPasswordStrength', (passwordId, confirmationId, policy) => ({
    ...passwordFeedback('', '', policy),
    listeners: null,

    init() {
        const password = document.getElementById(passwordId);
        const confirmation = document.getElementById(confirmationId);
        if (!(password instanceof HTMLInputElement) || !(confirmation instanceof HTMLInputElement)) return;

        this.listeners = new AbortController();
        const update = () => Object.assign(this, passwordFeedback(password.value, confirmation.value, policy));
        for (const field of [password, confirmation]) {
            field.addEventListener('input', update, {signal: this.listeners.signal});
            field.addEventListener('change', update, {signal: this.listeners.signal});
        }
        window.addEventListener('pageshow', update, {signal: this.listeners.signal});
        update();
    },

    destroy() {
        this.listeners?.abort();
    },
}));
