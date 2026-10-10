// Keep the native date value and GET form contract, with a themed calendar on top.
export function enhanceDatePickers() {
    const parse = value => value ? new Date(`${value}T12:00:00`) : null;
    const iso = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const display = new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short', year: 'numeric' });
    const monthDisplay = new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' });
    document.querySelectorAll('input[data-styled-date]').forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.className = 'date-picker';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = `${input.id}-trigger`;
        trigger.className = 'form-control date-picker-trigger';
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        const label = document.querySelector(`label[for="${input.id}"]`);
        const labelText = label?.textContent.trim() || 'Date';
        const popup = document.createElement('div');
        popup.id = `${input.id}-calendar`;
        popup.className = 'date-picker-popup';
        popup.setAttribute('role', 'dialog');
        popup.setAttribute('aria-label', `Choose ${labelText.toLowerCase()}`);
        popup.hidden = true;
        trigger.setAttribute('aria-controls', popup.id);
        const header = document.createElement('div');
        header.className = 'date-picker-header';
        const heading = document.createElement('strong');
        heading.setAttribute('aria-live', 'polite');
        const makeButton = (text, name, action) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = text;
            button.setAttribute('aria-label', name);
            button.addEventListener('click', action);
            return button;
        };
        let cursor = parse(input.value) || parse(input.max) || new Date();
        let month = new Date(cursor.getFullYear(), cursor.getMonth(), 1, 12);
        let rendering = false;
        const allowed = date => (!input.min || iso(date) >= input.min) && (!input.max || iso(date) <= input.max);
        const close = (focus = false) => {
            popup.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            if (focus) trigger.focus();
        };
        const sync = () => {
            trigger.textContent = input.value ? display.format(parse(input.value)) : 'Choose date';
            trigger.setAttribute('aria-label', `${labelText}: ${trigger.textContent}`);
        };
        const choose = date => {
            input.value = date ? iso(date) : '';
            sync();
            close(true);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const changeMonth = delta => {
            month = new Date(month.getFullYear(), month.getMonth() + delta, 1, 12);
            cursor = new Date(month);
            render();
        };
        const prev = makeButton('‹', 'Previous month', () => changeMonth(-1));
        const next = makeButton('›', 'Next month', () => changeMonth(1));
        header.append(prev, heading, next);
        const weekdays = document.createElement('div');
        weekdays.className = 'date-picker-weekdays';
        ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'].forEach(day => {
            const span = document.createElement('span');
            span.textContent = day;
            weekdays.append(span);
        });
        const days = document.createElement('div');
        days.className = 'date-picker-days';
        const footer = document.createElement('div');
        footer.className = 'date-picker-footer';
        const today = new Date();
        const todayButton = makeButton('Today', 'Choose today', () => choose(today));
        todayButton.disabled = !allowed(today);
        footer.append(makeButton('Clear', `Clear ${labelText.toLowerCase()}`, () => choose(null)), todayButton);
        popup.append(header, weekdays, days, footer);
        function render(focus = false) {
            rendering = true;
            heading.textContent = monthDisplay.format(month);
            prev.disabled = !!input.min && iso(new Date(month.getFullYear(), month.getMonth(), 0, 12)) < input.min;
            next.disabled = !!input.max && iso(new Date(month.getFullYear(), month.getMonth() + 1, 1, 12)) > input.max;
            days.replaceChildren();
            const offset = (month.getDay() + 6) % 7;
            const count = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
            for (let i = 0; i < offset; i++) days.append(document.createElement('span'));
            for (let day = 1; day <= count; day++) {
                const date = new Date(month.getFullYear(), month.getMonth(), day, 12);
                const button = makeButton(String(day), display.format(date), () => choose(date));
                button.disabled = !allowed(date);
                button.tabIndex = iso(date) === iso(cursor) ? 0 : -1;
                button.classList.toggle('is-selected', input.value === iso(date));
                if (input.value === iso(date)) button.setAttribute('aria-pressed', 'true');
                if (iso(today) === iso(date)) button.setAttribute('aria-current', 'date');
                button.addEventListener('keydown', event => {
                    const shifts = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
                    let target = new Date(date);
                    if (event.key in shifts) target.setDate(target.getDate() + shifts[event.key]);
                    else if (event.key === 'Home') target.setDate(day - (date.getDay() + 6) % 7);
                    else if (event.key === 'End') target.setDate(day + 6 - (date.getDay() + 6) % 7);
                    else if (event.key === 'PageUp' || event.key === 'PageDown') {
                        const delta = event.key === 'PageUp' ? -1 : 1;
                        target = new Date(date.getFullYear(), date.getMonth() + delta, 1, 12);
                        target.setDate(Math.min(day, new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate()));
                    } else return;
                    event.preventDefault();
                    if (!allowed(target)) return;
                    cursor = target;
                    month = new Date(target.getFullYear(), target.getMonth(), 1, 12);
                    render(true);
                });
                days.append(button);
            }
            if (focus) days.querySelector('button[tabindex="0"]:not(:disabled)')?.focus();
            rendering = false;
        }
        trigger.addEventListener('click', () => {
            if (!popup.hidden) { close(); return; }
            cursor = parse(input.value) || parse(input.max) || new Date();
            month = new Date(cursor.getFullYear(), cursor.getMonth(), 1, 12);
            popup.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            render(true);
        });
        wrapper.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !popup.hidden) { event.preventDefault(); close(true); }
        });
        wrapper.addEventListener('focusout', event => { if (!rendering && !wrapper.contains(event.relatedTarget)) close(); });
        document.addEventListener('pointerdown', event => { if (!wrapper.contains(event.target)) close(); });
        input.addEventListener('change', sync);
        window.addEventListener('pageshow', sync);
        input.before(wrapper);
        wrapper.append(trigger, popup, input);
        input.hidden = true;
        if (label) label.htmlFor = trigger.id;
        sync();
    });
}
