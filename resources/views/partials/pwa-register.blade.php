<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (error) {
                console.warn('No se pudo registrar el service worker de SuWork.', error);
            });
        });
    }
</script>
