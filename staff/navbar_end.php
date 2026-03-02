</div><!-- /page-wrap -->

<script>
    function toggleMobile() {
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('ham-icon');
        menu.classList.toggle('open');
        icon.className = menu.classList.contains('open')
            ? 'fa-solid fa-xmark'
            : 'fa-solid fa-bars';
    }
</script>
</body>
</html>