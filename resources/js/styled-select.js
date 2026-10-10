// Progressive enhancement: the native select still submits the chosen value
// and remains usable when JavaScript is unavailable.
export function enhanceSelects() {
    document.querySelectorAll('select[data-styled-select]').forEach((select) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'styled-select';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = `${select.id}-trigger`;
        trigger.className = 'form-control styled-select-trigger';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', `${select.id}-options`);
        const label = document.querySelector(`label[for="${select.id}"]`);
        if (label) {
            label.id ||= `${select.id}-label`;
            trigger.setAttribute('aria-labelledby', label.id);
        }
        else if (select.hasAttribute('aria-label')) trigger.setAttribute('aria-label', select.getAttribute('aria-label'));
        const value = document.createElement('span');
        trigger.append(value);
        const list = document.createElement('div');
        list.id = `${select.id}-options`;
        list.className = 'styled-select-options';
        list.setAttribute('role', 'listbox');
        if (label) list.setAttribute('aria-labelledby', label.id);
        else if (select.hasAttribute('aria-label')) list.setAttribute('aria-label', select.getAttribute('aria-label'));
        list.hidden = true;
        list.addEventListener('pointerdown', event => event.preventDefault());
        const options = Array.from(select.options);
        let active = select.selectedIndex;
        let search = '';
        let searchTimer;
        const rows = options.map((option, index) => {
            const row = document.createElement('div');
            row.id = `${select.id}-option-${index}`;
            row.className = 'styled-select-option';
            row.setAttribute('role', 'option');
            row.textContent = option.textContent;
            row.addEventListener('click', () => choose(index));
            list.append(row);
            return row;
        });
        const sync = () => {
            value.textContent = options[select.selectedIndex].textContent;
            rows.forEach((row, index) => row.setAttribute('aria-selected', String(index === select.selectedIndex)));
        };
        const highlight = (index) => {
            active = Math.max(0, Math.min(options.length - 1, index));
            rows.forEach((row, i) => row.classList.toggle('is-active', i === active));
            trigger.setAttribute('aria-activedescendant', rows[active].id);
            rows[active].scrollIntoView({ block: 'nearest' });
        };
        const close = () => {
            list.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            trigger.removeAttribute('aria-activedescendant');
            search = '';
            clearTimeout(searchTimer);
        };
        const open = () => {
            list.hidden = false;
            if (select.hasAttribute('data-select-floating')) {
                const rect = trigger.getBoundingClientRect();
                const height = Math.min(300, list.scrollHeight);
                const below = window.innerHeight - rect.bottom - 16;
                const above = rect.top - 16;
                const useAbove = below < height && above > below;
                list.style.position = 'fixed';
                list.style.width = `${rect.width}px`;
                list.style.left = `${rect.left}px`;
                list.style.right = 'auto';
                list.style.maxHeight = `${Math.max(40, Math.min(300, useAbove ? above : below))}px`;
                list.style.top = useAbove ? 'auto' : `${rect.bottom + 8}px`;
                list.style.bottom = useAbove ? `${window.innerHeight - rect.top + 8}px` : 'auto';
            }
            trigger.setAttribute('aria-expanded', 'true');
            highlight(select.selectedIndex);
        };
        function choose(index) {
            select.selectedIndex = index;
            sync();
            close();
            trigger.focus();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        trigger.addEventListener('click', () => list.hidden ? open() : close());
        trigger.addEventListener('keydown', (event) => {
            const key = event.key;
            if (key === 'Tab') { close(); return; }
            if (key === 'Escape') { event.preventDefault(); close(); return; }
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(key)) {
                event.preventDefault();
                const wasClosed = list.hidden;
                if (wasClosed) open();
                if (key === 'Home') highlight(0);
                else if (key === 'End') highlight(options.length - 1);
                else if (!wasClosed) highlight(active + (key === 'ArrowDown' ? 1 : -1));
                return;
            }
            if (key === 'Enter' || key === ' ') {
                event.preventDefault();
                if (list.hidden) open(); else choose(active);
                return;
            }
            if (key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                if (list.hidden) open();
                search += key.toLowerCase();
                const index = options.findIndex(option => option.textContent.toLowerCase().startsWith(search));
                if (index !== -1) highlight(index);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => { search = ''; }, 600);
            }
        });
        document.addEventListener('pointerdown', event => { if (!wrapper.contains(event.target) && !list.contains(event.target)) close(); });
        if (select.hasAttribute('data-select-floating')) {
            window.addEventListener('resize', close);
            document.addEventListener('scroll', event => { if (!list.contains(event.target)) close(); }, true);
        }
        trigger.addEventListener('blur', close);
        select.addEventListener('change', sync);
        window.addEventListener('pageshow', sync);
        select.before(wrapper);
        wrapper.append(trigger, list, select);
        if (select.hasAttribute('data-select-floating')) document.body.append(list);
        sync();
        select.hidden = true;
        if (label) label.htmlFor = trigger.id;
    });
}
