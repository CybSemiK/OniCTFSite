<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.timer-info').forEach(function(element) {
        const startTime = element.getAttribute('data-start-time');
        const timerElement = element.querySelector('.timer');
        if (startTime && timerElement) {
            const start = new Date(startTime).getTime();

            setInterval(function() {
                const now = new Date().getTime();
                const diff = now - start;

                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                const everything = "`${hours}h ${minutes}m ${seconds}s`"
                timerElement.innerHTML = everything;
            }, 1000);
        }
    });
});
</script>
