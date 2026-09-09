import '@tailwindplus/elements';
import { createIcons } from 'lucide';
import { icons } from './lucide-icons';

let lucideRescanTimer;
const lucideRoots = new Set();

const renderLucideIcons = (root = document) => {
    if (root === document) {
        lucideRoots.clear();
        lucideRoots.add(document);
    } else if (! lucideRoots.has(document) && root?.querySelectorAll) {
        lucideRoots.add(root);
    }

    clearTimeout(lucideRescanTimer);

    lucideRescanTimer = setTimeout(() => {
        lucideRoots.forEach((pendingRoot) => createIcons({ icons, root: pendingRoot }));
        lucideRoots.clear();
    }, 16);
};

document.addEventListener('DOMContentLoaded', () => renderLucideIcons());
document.addEventListener('livewire:navigated', () => renderLucideIcons());
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.added', ({ el }) => {
        renderLucideIcons(el.matches?.('[data-lucide]') ? el.parentElement : el);
    });
});

const cmsTabKeys = new Set(['ArrowLeft', 'ArrowRight', 'Home', 'End']);

document.addEventListener('keydown', (event) => {
    const currentTab = event.target.closest?.('[data-cms-tabs] [role="tab"]');

    if (! currentTab || ! cmsTabKeys.has(event.key)) {
        return;
    }

    const tablist = currentTab.closest('[data-cms-tabs]');
    const tabs = [...tablist.querySelectorAll('[role="tab"]')].filter((tab) => ! tab.disabled && tab.offsetParent !== null);
    const currentIndex = tabs.indexOf(currentTab);

    if (currentIndex === -1 || tabs.length < 2) {
        return;
    }

    event.preventDefault();

    const nextIndex = event.key === 'Home'
        ? 0
        : event.key === 'End'
            ? tabs.length - 1
            : event.key === 'ArrowRight'
                ? (currentIndex + 1) % tabs.length
                : (currentIndex - 1 + tabs.length) % tabs.length;

    tabs[nextIndex].focus();
    tabs[nextIndex].click();
});

const toastEventMessages = {
    'block-type-deleted': ['Block type deleted', 'success'],
    'cms-settings-reset': ['Settings reset to defaults', 'success'],
    'cms-settings-saved': ['Settings saved', 'success'],
    'content-deleted': ['Content deleted', 'success'],
    'datasource-created': ['Datasource created', 'success'],
    'datasource-deleted': ['Datasource deleted', 'success'],
    'datasource-entry-created': ['Entry created', 'success'],
    'datasource-entry-deleted': ['Entry deleted', 'success'],
    'datasource-entry-updated': ['Entry saved', 'success'],
    'datasource-updated': ['Datasource saved', 'success'],
    'password-updated': ['Password updated', 'success'],
    'profile-updated': ['Profile saved', 'success'],
    'space-deleted': ['Space deleted', 'success'],
    'user-created': ['User created', 'success'],
    'user-deleted': ['User deleted', 'success'],
    'user-updated': ['User saved', 'success'],
};

let toastSequence = 0;
let suppressNextAutosave = false;

const normalizeToast = (detail = {}) => {
    if (typeof detail === 'string') {
        return { message: detail };
    }

    return Array.isArray(detail) ? (detail[0] || {}) : detail;
};

const showToast = (options = {}) => {
    const region = document.getElementById('pilot-toast-region');
    const { message, type = 'success', duration = 3500 } = normalizeToast(options);

    if (! region || ! message) {
        return;
    }

    const toast = document.createElement('div');
    const toastId = `pilot-toast-${++toastSequence}`;
    const icon = type === 'error' ? 'circle-alert' : type === 'warning' ? 'triangle-alert' : 'circle-check';

    toast.id = toastId;
    toast.className = `pilot-toast pilot-toast--${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.innerHTML = `
        <i data-lucide="${icon}" class="pilot-toast__icon" aria-hidden="true"></i>
        <span class="pilot-toast__message"></span>
        <button type="button" class="pilot-toast__close" aria-label="Dismiss notification">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    `;
    toast.querySelector('.pilot-toast__message').textContent = message;

    const dismiss = () => {
        toast.classList.add('pilot-toast--leaving');
        setTimeout(() => toast.remove(), 180);
    };

    toast.querySelector('.pilot-toast__close').addEventListener('click', dismiss);
    region.append(toast);
    renderLucideIcons();
    requestAnimationFrame(() => toast.classList.add('pilot-toast--visible'));

    while (region.children.length > 3) {
        region.firstElementChild?.remove();
    }

    if (duration > 0) {
        setTimeout(dismiss, duration);
    }
};

window.PilotToast = { show: showToast };

document.addEventListener('toast', (event) => {
    const options = normalizeToast(event.detail);
    suppressNextAutosave = Boolean(options.suppressAutosave);
    showToast(options);
});

document.addEventListener('published', () => {
    suppressNextAutosave = true;
    showToast({ message: 'Published successfully' });
});

document.addEventListener('saved', () => {
    if (suppressNextAutosave) {
        suppressNextAutosave = false;
        return;
    }

    showToast({ message: 'Changes autosaved' });
});

document.addEventListener('error', (event) => {
    const detail = normalizeToast(event.detail);
    showToast({ message: detail.message || 'Something went wrong', type: 'error', duration: 5000 });
});

Object.entries(toastEventMessages).forEach(([eventName, [message, type]]) => {
    document.addEventListener(eventName, () => showToast({ message, type }));
});

const showSessionToast = () => {
    const region = document.getElementById('pilot-toast-region');

    if (! region?.dataset.sessionToast) {
        return;
    }

    try {
        const toast = JSON.parse(region.dataset.sessionToast);
        showToast(typeof toast === 'string' ? { message: toast } : toast);
    } catch {
        // A malformed flash message should never interrupt page navigation.
    }

    region.dataset.sessionToast = '';
};

document.addEventListener('DOMContentLoaded', showSessionToast);
document.addEventListener('livewire:navigated', showSessionToast);

let focusNavigationWasTab = false;

const textInputTypes = new Set(['', 'password', 'search', 'tel', 'text', 'url']);

const moveCaretToFieldEnd = (field) => {
    if (field.matches?.('[contenteditable="true"]')) {
        if (! field.textContent) {
            return;
        }

        const range = document.createRange();
        const selection = window.getSelection();

        range.selectNodeContents(field);
        range.collapse(false);
        selection.removeAllRanges();
        selection.addRange(range);

        return;
    }

    if (field instanceof HTMLTextAreaElement) {
        if (field.value === '') {
            return;
        }

        field.setSelectionRange(field.value.length, field.value.length);

        return;
    }

    if (! (field instanceof HTMLInputElement) || ! textInputTypes.has(field.type)) {
        return;
    }

    if (field.value === '') {
        return;
    }

    field.setSelectionRange(field.value.length, field.value.length);
};

document.addEventListener('keydown', (event) => {
    focusNavigationWasTab = event.key === 'Tab';
}, true);

document.addEventListener('pointerdown', () => {
    focusNavigationWasTab = false;
}, true);

document.addEventListener('focusin', (event) => {
    if (! focusNavigationWasTab || ! event.target.closest?.('.cms-shell')) {
        return;
    }

    requestAnimationFrame(() => {
        if (document.activeElement !== event.target) {
            return;
        }

        moveCaretToFieldEnd(event.target);
    });
}, true);

const registerPilotRichTextEditor = () => {
    window.Alpine.store('pilotRichTextWorkspace', {
        expanded: false,
    });

    window.Alpine.data('pilotRichTextEditor', (config) => ({
        html: config.value || '',
        lastSavedHtml: config.value || '',
        fieldKey: config.fieldKey,
        repeaterIndex: config.repeaterIndex,
        subFieldKey: config.subFieldKey,
        isRepeaterField: Boolean(config.isRepeaterField),
        expanded: false,
        editor: null,

        handleReady(event) {
            if (event.target !== this.$refs.editor) {
                return;
            }

            this.editor = event.detail.editor;
            this.html = this.currentHtml();
            this.lastSavedHtml = this.html;
        },

        handleInput() {
            this.html = this.currentHtml();
        },

        currentHtml() {
            if (this.editor) {
                return this.editor.isEmpty ? '' : this.editor.getHTML();
            }

            return this.$refs.editor?.value || '';
        },

        flush() {
            this.html = this.currentHtml();

            if (this.html === this.lastSavedHtml) {
                return;
            }

            this.lastSavedHtml = this.html;

            if (this.isRepeaterField) {
                this.$wire.updateRepeaterField(this.fieldKey, this.repeaterIndex, this.subFieldKey, this.html);
                return;
            }

            this.$wire.updateField(this.fieldKey, this.html);
        },

        openExpandedEditor() {
            this.expanded = true;
            this.$store.pilotRichTextWorkspace.expanded = true;
            this.$nextTick(() => this.$refs.editor?.focus());
        },

        closeExpandedEditor(restoreFocus = true) {
            if (! this.expanded) {
                return;
            }

            this.expanded = false;
            this.$store.pilotRichTextWorkspace.expanded = false;
            this.flush();

            if (restoreFocus) {
                this.$nextTick(() => this.$refs.editor?.focus());
            }
        },

        destroy() {
            this.$store.pilotRichTextWorkspace.expanded = false;
            this.editor = null;
        },
    }));
};

if (window.Alpine) {
    registerPilotRichTextEditor();
} else {
    document.addEventListener('alpine:init', registerPilotRichTextEditor);
}
