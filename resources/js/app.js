import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    const applyThemeButtonState = () => {
        const isDark = document.documentElement.dataset.theme === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} mode`);
            button.setAttribute('title', `Switch to ${isDark ? 'light' : 'dark'} mode`);
        });
    };

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem('resback-theme', nextTheme);
            applyThemeButtonState();
        });
    });

    applyThemeButtonState();

    let feedbackRequestController = null;

    const loadFeedbackPage = async (url, updateHistory = true) => {
        const currentPanel = document.getElementById('ccisFeedbackPanel');
        if (!currentPanel) return;

        feedbackRequestController?.abort();
        feedbackRequestController = new AbortController();
        currentPanel.classList.add('is-loading');
        currentPanel.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Feedback-Partial': '1',
                },
                signal: feedbackRequestController.signal,
            });

            if (!response.ok) throw new Error(`Feedback page request failed with ${response.status}`);

            const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextPanel = documentFragment.getElementById('ccisFeedbackPanel');
            if (!nextPanel) throw new Error('Feedback panel was missing from the response.');

            currentPanel.replaceWith(nextPanel);
            if (updateHistory) window.history.pushState({ feedbackPage: true }, '', url);
            nextPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            if (error.name === 'AbortError') return;

            window.location.assign(url);
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('#ccisFeedbackPanel .pagination-link[href]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        loadFeedbackPage(link.href);
    });

    window.addEventListener('popstate', () => {
        if (document.getElementById('ccisFeedbackPanel')) {
            loadFeedbackPage(window.location.href, false);
        }
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const isVisible = input.type === 'text';
            const label = button.dataset.passwordLabel || 'password';

            input.type = isVisible ? 'password' : 'text';
            button.classList.toggle('is-visible', !isVisible);
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} ${label}`);
        });
    });
});
