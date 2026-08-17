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
        lastSavedHtml: null,
        placeholder: config.placeholder || '',
        fieldKey: config.fieldKey,
        repeaterIndex: config.repeaterIndex,
        subFieldKey: config.subFieldKey,
        isRepeaterField: Boolean(config.isRepeaterField),
        sourceMode: false,
        expanded: false,
        saveTimer: null,
        active: {
            bold: false,
            italic: false,
            underline: false,
            link: false,
            ol: false,
            ul: false,
            block: 'p',
            align: 'left',
        },

        init() {
            this.html = this.normalizeInitialHtml(this.html);
            this.lastSavedHtml = this.html;
            this.$refs.editor.innerHTML = this.html;
            this.refreshState();
        },

        normalizeInitialHtml(value) {
            const trimmed = String(value || '').trim();

            if (trimmed === '') {
                return '';
            }

            if (/<[a-z][\s\S]*>/i.test(trimmed)) {
                return this.sanitizeHtml(trimmed);
            }

            return this.plainTextToHtml(trimmed);
        },

        handleInput() {
            this.html = this.sanitizeHtml(this.activeEditor().innerHTML);
            this.queueSave();
            this.refreshState();
        },

        handlePaste(event) {
            const clipboard = event.clipboardData || window.clipboardData;
            const html = clipboard?.getData('text/html');
            const text = clipboard?.getData('text/plain') || '';
            const content = html ? this.sanitizeHtml(html) : this.plainTextToHtml(text);

            this.insertHtml(content);
            this.handleInput();
        },

        placeCaretFromPointer(event) {
            if (event.button !== 0 || this.sourceMode) {
                return;
            }

            const editor = this.activeEditor();

            requestAnimationFrame(() => {
                if (document.activeElement !== editor) {
                    return;
                }

                let pointRange = document.caretRangeFromPoint?.(event.clientX, event.clientY);

                if (! pointRange && document.caretPositionFromPoint) {
                    const position = document.caretPositionFromPoint(event.clientX, event.clientY);

                    if (position) {
                        pointRange = document.createRange();
                        pointRange.setStart(position.offsetNode, position.offset);
                        pointRange.collapse(true);
                    }
                }

                if (! pointRange || ! editor.contains(pointRange.startContainer)) {
                    return;
                }

                const selection = window.getSelection();

                if (! selection) {
                    return;
                }

                selection.removeAllRanges();
                selection.addRange(pointRange);
                this.refreshState();
            });
        },

        runCommand(command, value = null) {
            this.focusEditor();
            document.execCommand('styleWithCSS', false, true);
            document.execCommand(command, false, value);
            this.handleInput();
        },

        formatBlock(tag) {
            this.runCommand('formatBlock', tag);
        },

        blockLabel() {
            return {
                p: 'Body',
                blockquote: 'Quote',
                h2: 'Heading 2',
                h3: 'Heading 3',
                h4: 'Heading 4',
                h5: 'Heading 5',
                h6: 'Heading 6',
            }[this.active.block] || 'Body';
        },

        createLink() {
            this.focusEditor();

            const existingLink = this.closestTag('a');
            const currentHref = existingLink?.getAttribute('href') || '';
            const href = window.prompt('Paste a URL', currentHref);

            if (href === null) {
                return;
            }

            const cleanHref = href.trim();

            if (cleanHref === '') {
                this.runCommand('unlink');
                return;
            }

            this.runCommand('createLink', cleanHref);
        },

        toggleSource() {
            if (this.sourceMode) {
                this.html = this.sanitizeHtml(this.html);
                this.activeEditor().innerHTML = this.html;
                this.sourceMode = false;
                this.queueSave();
                this.$nextTick(() => this.focusEditor());
                return;
            }

            this.html = this.sanitizeHtml(this.activeEditor().innerHTML);
            this.sourceMode = true;
            this.$nextTick(() => this.activeSource().focus());
        },

        openExpandedEditor() {
            this.expanded = true;
            this.$store.pilotRichTextWorkspace.expanded = true;

            this.$nextTick(() => {
                if (this.sourceMode) {
                    this.$refs.source.focus();
                    return;
                }

                this.$refs.editor.focus();
                this.refreshState();
            });
        },

        closeExpandedEditor() {
            if (! this.expanded) {
                return;
            }

            this.expanded = false;
            this.$store.pilotRichTextWorkspace.expanded = false;
            this.flush();
            this.$nextTick(() => this.$refs.editor.focus());
        },

        destroy() {
            this.$store.pilotRichTextWorkspace.expanded = false;
        },

        queueSave() {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.flush(), 450);
        },

        flush() {
            clearTimeout(this.saveTimer);
            const editor = this.activeEditor();
            this.html = this.sanitizeHtml(this.sourceMode ? this.html : editor.innerHTML);

            // Replacing innerHTML while the contenteditable is focused destroys
            // the browser selection and moves the caret back to the beginning.
            // Normalize the DOM only after focus leaves the editor; autosave can
            // persist the sanitized value without rewriting the active surface.
            if (! this.sourceMode && document.activeElement !== editor && editor.innerHTML !== this.html) {
                editor.innerHTML = this.html;
            }

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

        focusEditor() {
            if (this.sourceMode) {
                this.toggleSource();
            }

            this.activeEditor().focus();
        },

        activeEditor() {
            return this.$refs.editor;
        },

        activeSource() {
            return this.$refs.source;
        },

        insertHtml(html) {
            this.focusEditor();
            document.execCommand('insertHTML', false, html);
        },

        refreshState() {
            this.active.bold = document.queryCommandState('bold');
            this.active.italic = document.queryCommandState('italic');
            this.active.underline = document.queryCommandState('underline');
            this.active.ol = document.queryCommandState('insertOrderedList');
            this.active.ul = document.queryCommandState('insertUnorderedList');
            this.active.link = Boolean(this.closestTag('a'));
            this.active.block = this.currentBlock();
            this.active.align = this.currentAlignment();

        },

        isBlock(tag) {
            return this.active.block === tag;
        },

        currentBlock() {
            const block = this.closestTag('h2,h3,h4,h5,h6,blockquote,li,p,div');
            const tag = block?.tagName?.toLowerCase() || 'p';

            return tag === 'div' || tag === 'li' ? 'p' : tag;
        },

        currentAlignment() {
            if (document.queryCommandState('justifyCenter')) {
                return 'center';
            }

            if (document.queryCommandState('justifyRight')) {
                return 'right';
            }

            return 'left';
        },

        closestTag(selector) {
            const selection = window.getSelection();

            if (! selection || selection.rangeCount === 0) {
                return null;
            }

            const node = selection.anchorNode?.nodeType === Node.TEXT_NODE
                ? selection.anchorNode.parentElement
                : selection.anchorNode;

            if (! node || ! this.activeEditor().contains(node)) {
                return null;
            }

            return node.closest(selector);
        },

        plainTextToHtml(text) {
            return String(text || '')
                .split(/\n{2,}/)
                .map((paragraph) => paragraph.trim())
                .filter(Boolean)
                .map((paragraph) => `<p>${this.escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
                .join('');
        },

        sanitizeHtml(html) {
            const template = document.createElement('template');
            template.innerHTML = String(html || '');
            const allowedTags = new Set(['A', 'B', 'BLOCKQUOTE', 'BR', 'EM', 'H2', 'H3', 'H4', 'H5', 'H6', 'I', 'LI', 'OL', 'P', 'SPAN', 'STRONG', 'U', 'UL']);
            const allowedAttrs = new Set(['href', 'target', 'rel', 'style']);

            template.content.querySelectorAll('*').forEach((node) => {
                if (! allowedTags.has(node.tagName)) {
                    node.replaceWith(...Array.from(node.childNodes));
                    return;
                }

                Array.from(node.attributes).forEach((attribute) => {
                    if (! allowedAttrs.has(attribute.name)) {
                        node.removeAttribute(attribute.name);
                    }
                });

                if (node.hasAttribute('style')) {
                    const safeStyle = this.sanitizeStyle(node.getAttribute('style'));

                    if (safeStyle === '') {
                        node.removeAttribute('style');
                    } else {
                        node.setAttribute('style', safeStyle);
                    }
                }

                if (node.tagName === 'A') {
                    const href = node.getAttribute('href') || '';

                    if (! /^(https?:|mailto:|tel:|\/|#)/i.test(href)) {
                        node.removeAttribute('href');
                    }

                    node.setAttribute('rel', 'noopener noreferrer');
                }
            });

            return template.innerHTML
                .replace(/<p>(\s|&nbsp;|<br>)*<\/p>/gi, '')
                .trim();
        },

        sanitizeStyle(style) {
            return String(style || '')
                .split(';')
                .map((declaration) => declaration.trim())
                .filter(Boolean)
                .map((declaration) => {
                    const [property, ...valueParts] = declaration.split(':');
                    const name = property?.trim().toLowerCase();
                    const value = valueParts.join(':').trim().toLowerCase();

                    if (name === 'color' && (/^#[0-9a-f]{3,8}$/i.test(value) || /^rgb(a)?\([\d\s,.%]+\)$/i.test(value))) {
                        return `color: ${value}`;
                    }

                    if (name === 'text-align' && ['left', 'center', 'right'].includes(value)) {
                        return `text-align: ${value}`;
                    }

                    return null;
                })
                .filter(Boolean)
                .join('; ');
        },

        escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value;

            return div.innerHTML;
        },
    }));
};

if (window.Alpine) {
    registerPilotRichTextEditor();
} else {
    document.addEventListener('alpine:init', registerPilotRichTextEditor);
}
