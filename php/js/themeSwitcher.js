document.addEventListener('DOMContentLoaded', () => {
    const toggleModeBtn = document.getElementById('toggleModeBtn');
    toggleModeBtn.addEventListener('click', function() {
        document.body.classList.toggle('night-mode');
        const isNightMode = document.body.classList.contains('night-mode');
        toggleModeBtn.innerHTML = isNightMode ? '<i class="fas fa-sun"></i> Mode Jour' : '<i class="fas fa-moon"></i> Mode Nuit';
        localStorage.setItem('theme', isNightMode ? 'night' : 'day');
        updateTheme(isNightMode);
    });

    function updateTheme(isNightMode) {
        const themeElements = document.querySelectorAll('.theme-sensitive');
        themeElements.forEach(element => {
            element.classList.toggle('night-mode', isNightMode);
        });
    }

    const isNightMode = localStorage.getItem('theme') === 'night';
    if (isNightMode) {
        document.body.classList.add('night-mode');
        toggleModeBtn.innerHTML = '<i class="fas fa-sun"></i> Mode Jour';
    } else {
        toggleModeBtn.innerHTML = '<i class="fas fa-moon"></i> Mode Nuit';
    }
    updateTheme(isNightMode);
});
