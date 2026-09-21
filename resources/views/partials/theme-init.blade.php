<script>
    (() => {
        const savedTheme = localStorage.getItem('resback-theme');
        const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.dataset.theme = savedTheme || preferredTheme;
    })();
</script>
