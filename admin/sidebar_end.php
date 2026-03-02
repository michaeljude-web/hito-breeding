</div><!-- /content -->
</div><!-- /main -->

<script>
    const d = new Date();
    document.getElementById('topbar-date').textContent =
        d.toLocaleDateString('en-PH', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('overlay').classList.toggle('show');
    }
</script>
</body>
</html>