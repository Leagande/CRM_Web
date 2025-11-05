<?php
// footer.php
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Alle Toasts finden, die beim Laden der Seite vorhanden sind
    const toasts = document.querySelectorAll('.toast-notification');

    toasts.forEach((toast, index) => {
        
        // 2. Nach einer kurzen Verzögerung einblenden (damit die CSS-Animation greift)
        // Wir staffeln sie leicht, falls mehrere Meldungen gleichzeitig kommen
        setTimeout(() => {
            toast.classList.add('show');
        }, 100 * (index + 1));

        // 3. Nach 4 Sekunden den Ausblend-Prozess starten
        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hide');
        }, 4000 + (100 * index));

        // 4. Nachdem die Ausblend-Animation (0.5s) fertig ist, das Element aus dem HTML entfernen
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 4500 + (100 * index));
    });

    // 5. Loading Animation für Submit-Buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('loading')) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Text temporär ändern
                const originalText = submitBtn.textContent;
                submitBtn.setAttribute('data-original-text', originalText);
                submitBtn.textContent = 'Speichert...';
            }
        });
    });

    // 6. Fade-In Animation für Cards
    const cards = document.querySelectorAll('.card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    entry.target.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => {
        observer.observe(card);
    });
});
</script>
</body>
</html>