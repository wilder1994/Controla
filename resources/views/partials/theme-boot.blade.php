<script>
(function () {
    try {
        var t = localStorage.getItem('controla-theme');
        document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();
</script>
<meta name="theme-color" content="#020617">
