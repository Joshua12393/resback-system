import Chart from 'chart.js/auto';
import { enhanceSelects } from './styled-select';
import { enhanceDatePickers } from './date-picker';

window.Chart = Chart;

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const chartTheme = () => {
    const styles = getComputedStyle(document.documentElement);
    return {
        text: styles.getPropertyValue('--gray-600').trim(),
        grid: styles.getPropertyValue('--gray-200').trim(),
        surface: styles.getPropertyValue('--surface').trim(),
    };
};
const applyChartTheme = (chart) => {
    const colors = chartTheme();
    chart.options.animation = motionPreference.matches ? false : { duration: 400 };
    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = colors.text;
    if (chart.options.plugins?.tooltip) {
        chart.options.plugins.tooltip.backgroundColor = colors.surface;
        chart.options.plugins.tooltip.titleColor = colors.text;
        chart.options.plugins.tooltip.bodyColor = colors.text;
        chart.options.plugins.tooltip.borderColor = colors.grid;
        chart.options.plugins.tooltip.borderWidth = 1;
    }
    Object.values(chart.options.scales || {}).forEach((scale) => {
        if (scale.ticks) scale.ticks.color = colors.text;
        if (scale.grid) scale.grid.color = colors.grid;
    });
};
Chart.register({ id: 'resbackTheme', beforeInit: applyChartTheme });
const refreshCharts = () => Object.values(Chart.instances).forEach((chart) => {
    applyChartTheme(chart);
    chart.update('none');
});
motionPreference.addEventListener('change', refreshCharts);

document.addEventListener('DOMContentLoaded', () => {
    enhanceSelects();
    enhanceDatePickers();
    document.querySelectorAll('[data-success-dialog]').forEach(dialog => {
        if (dialog.hasAttribute('data-auto-open')) {
            dialog.showModal();
            dialog.focus({ preventScroll: true });
        }
        dialog.addEventListener('click', event => {
            const rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
        });
    });
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
            try { localStorage.setItem('resback-theme', nextTheme); } catch { /* Theme still works without storage. */ }
            applyThemeButtonState();
            refreshCharts();
        });
    });

    applyThemeButtonState();

    const feedbackReader = document.getElementById('feedbackReadDialog');
    if (feedbackReader) {
        document.addEventListener('click', event => {
            const summary = event.target.closest('[data-feedback-reader] > summary');
            if (!summary) return;
            event.preventDefault();
            const details = summary.parentElement;
            feedbackReader.querySelector('.feedback-reading-content').textContent = details.querySelector('.feedback-full-text').textContent;
            feedbackReader.querySelector('[data-feedback-reading-meta]').textContent = details.dataset.feedbackMeta;
            feedbackReader.showModal();
        });
        feedbackReader.querySelector('[data-close-feedback-reader]').addEventListener('click', () => feedbackReader.close());
    }

    // Standard POST forms keep native submission and server validation.
    ['feedback', 'account'].forEach(kind => {
        const deleteDialog = document.getElementById(`${kind}DeleteConfirmation`);
        if (!deleteDialog) return;
        let deleteForm = null;
        let deleteSubmitter = null;
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute(`data-${kind}-delete`)) return;
            if (form.dataset.deleteConfirmed === 'true') return;
            event.preventDefault();
            deleteForm = form;
            deleteSubmitter = event.submitter;
            const accountName = deleteDialog.querySelector('[data-delete-account-name]');
            if (accountName) accountName.textContent = form.dataset.accountNickname;
            if (!deleteDialog.open) deleteDialog.showModal();
        });
        deleteDialog.querySelector(`[data-cancel-${kind}-delete]`).addEventListener('click', () => deleteDialog.close());
        deleteDialog.querySelector(`[data-confirm-${kind}-delete]`).addEventListener('click', () => {
            const form = deleteForm;
            const submitter = deleteSubmitter;
            deleteDialog.close();
            if (!form?.isConnected) return;
            form.dataset.deleteConfirmed = 'true';
            form.requestSubmit(submitter || undefined);
        });
        deleteDialog.addEventListener('close', () => { deleteForm = null; deleteSubmitter = null; });
    });
    const pendingButtons = new Map();
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (event.defaultPrevented || !(form instanceof HTMLFormElement) || form.method !== 'post' || form.id === 'feedbackForm') return;
        if (form.dataset.pending === 'true') { event.preventDefault(); return; }
        const button = event.submitter || form.querySelector('button[type="submit"]');
        if (!button || button.name) return;
        form.dataset.pending = 'true';
        pendingButtons.set(button, button.innerHTML);
        button.disabled = true;
        button.classList.add('is-submitting');
        button.setAttribute('aria-busy', 'true');
        button.textContent = form.dataset.pendingLabel || 'Please wait…';
    });
    window.addEventListener('pageshow', () => {
        pendingButtons.forEach((label, button) => {
            button.innerHTML = label;
            button.disabled = false;
            button.classList.remove('is-submitting');
            button.removeAttribute('aria-busy');
            delete button.form.dataset.pending;
        });
        pendingButtons.clear();
    });

    let feedbackRequestController = null;

    const loadFeedbackPage = async (url, updateHistory = true) => {
        const currentPanel = document.getElementById('ccisFeedbackPanel');
        if (!currentPanel) return;

        feedbackRequestController?.abort();
        feedbackRequestController = new AbortController();
        currentPanel.classList.add('is-loading');
        currentPanel.setAttribute('aria-busy', 'true');
        const loadingStatus = document.getElementById('feedbackLoadStatus');
        if (loadingStatus) loadingStatus.textContent = 'Loading feedback…';

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
            nextPanel.classList.add('animate-fade-up');
            nextPanel.scrollIntoView({ behavior: motionPreference.matches ? 'auto' : 'smooth', block: 'start' });
            const status = document.getElementById('feedbackLoadStatus');
            if (status) status.textContent = 'Feedback page loaded.';
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
