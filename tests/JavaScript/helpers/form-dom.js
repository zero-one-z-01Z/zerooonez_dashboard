/** A small DOM/event adapter: registration and later element insertion remain separate operations. */
export function createFormDom() {
    const listeners = [];
    const ready = [];
    const loader = [];

    /** Creates an element whose connection is determined by its actual parent chain. */
    function element(tagName, attributes = {}) {
        return {
            tagName: tagName.toUpperCase(), attributes, children: [], parentElement: null,
            checked: false, resetCalls: 0, validationResets: 0, dataset: {},
            get isConnected() { return this === document || Boolean(this.parentElement?.isConnected); },
            append(child) { child.parentElement = this; this.children.push(child); return child; },
            remove() { if (this.parentElement) this.parentElement.children = this.parentElement.children.filter(child => child !== this); this.parentElement = null; },
            contains(child) { return child === this || this.children.some(node => node.contains(child)); },
            reset() { this.resetCalls++; },
            querySelector(selector) { return descendants(this).find(node => matches(node, selector)) ?? null; },
            querySelectorAll(selector) { return descendants(this).filter(node => matches(node, selector)); }
        };
    }
    const document = element('document');
    const window = {};

    /** Enumerates descendants when a selector is evaluated, including nodes inserted after registration. */
    function descendants(root) {
        return root.children.flatMap(child => [child, ...descendants(child)]);
    }

    /** Supports the ordinary attribute, class and tag selectors used by form events. */
    function matches(node, selector) {
        return selector.split(',').some(part => {
            part = part.trim();
            if (part === ':checked') return node.checked;
            if (part.startsWith('#')) return node.attributes.id === part.slice(1);
            const attribute = /^\[([^=]+)="([^"]*)"\]$/.exec(part);
            if (attribute) return node.attributes[attribute[1]] === attribute[2];
            if (part.startsWith('.')) return part.slice(1).split('.').every(name => (node.attributes.class ?? '').split(' ').includes(name));
            return node.tagName.toLowerCase() === part;
        });
    }

    /** Delivers bubbling events to currently matching descendants, with jQuery namespace filtering. */
    function dispatch(target, name) {
        const [type, ...namespaces] = name.split('.');
        const event = { type, target, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        for (const listener of [...listeners]) {
            if (listener.type !== type || !namespaces.every(namespace => listener.namespaces.includes(namespace))) continue;
            for (let candidate = target; candidate && candidate !== document; candidate = candidate.parentElement) {
                if (candidate.isConnected && matches(candidate, listener.selector)) listener.callback.call(candidate, event);
            }
        }
        return event;
    }

    /** Wraps concrete elements without baking in any module's business decisions. */
    function wrap(nodes) {
        return Object.assign({
            jquery: 'test-adapter', length: nodes.length,
            ready(callback) { ready.push(callback); return this; },
            on(name, selector, callback) {
                const [type, ...namespaces] = name.split('.');
                listeners.push({ type, namespaces, selector, callback }); return this;
            },
            off(name, selector) {
                const [type, ...namespaces] = name.split('.');
                for (let index = listeners.length - 1; index >= 0; index--) {
                    const item = listeners[index];
                    if (item.type === type && namespaces.every(namespace => item.namespaces.includes(namespace)) && item.selector === selector) listeners.splice(index, 1);
                }
                return this;
            },
            find(selector) { return wrap(nodes.flatMap(descendants).filter(node => matches(node, selector))); },
            filter(selector) { return wrap(nodes.filter(node => matches(node, selector))); },
            closest(selector) {
                return wrap(nodes.map(node => {
                    for (let current = node; current; current = current.parentElement) if (matches(current, selector)) return current;
                    return null;
                }).filter(Boolean));
            },
            each(callback) { nodes.forEach((node, index) => callback.call(node, index, node)); return this; },
            attr(name) { return nodes[0]?.attributes[name]; },
            data(name) { return nodes[0]?.attributes['data-' + name]; },
            hasClass(name) { return Boolean(nodes[0] && matches(nodes[0], '.' + name)); },
            is(selector) { return Boolean(nodes[0] && matches(nodes[0], selector)); },
            prop(name, value) {
                if (arguments.length === 1) return nodes[0]?.[name];
                nodes.forEach(node => { node[name] = value; }); return this;
            },
            validate() { return { resetForm: () => { nodes[0].validationResets++; } }; },
            modal(action) { if (action === 'hide') nodes.forEach(node => dispatch(node, 'hide.bs.modal')); return this; },
            css() { return this; },
            fadeIn() { loader.push('on'); return this; },
            fadeOut() { loader.push('off'); return this; },
            focus() { return this; }
        }, nodes);
    }
    const $ = selector => {
        if (selector?.jquery) return selector;
        if (typeof selector === 'string') return wrap(descendants(document).filter(node => matches(node, selector)));
        return wrap(selector ? [selector] : []);
    };
    $.validator = {};
    Object.assign(globalThis, { document, window, $ });
    return { document, window, $, element, dispatch, ready, loader };
}
