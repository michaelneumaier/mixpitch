document.addEventListener('DOMContentLoaded', function () {
    // Role Toggle Functionality
    const artistToggle = document.getElementById('artist-toggle');
    const producerToggle = document.getElementById('producer-toggle');
    const toggleIndicator = document.querySelector('.toggle-indicator');
    const artistContent = document.getElementById('artist-content');
    const producerContent = document.getElementById('producer-content');

    if (artistToggle && producerToggle && toggleIndicator) {
        // Set initial toggle state
        toggleIndicator.style.width = `${artistToggle.offsetWidth}px`;

        // Function to update toggle indicator based on active toggle
        const updateToggleIndicator = () => {
            const activeToggle = document.querySelector('.role-toggle-btn.active');
            if (activeToggle === artistToggle) {
                toggleIndicator.style.transform = 'translateX(0)';
                toggleIndicator.style.width = `${artistToggle.offsetWidth}px`;
            } else if (activeToggle === producerToggle) {
                toggleIndicator.style.transform = `translateX(${artistToggle.offsetWidth}px)`;
                toggleIndicator.style.width = `${producerToggle.offsetWidth}px`;
            }
        };

        // Handle window resize to adjust toggle indicator
        window.addEventListener('resize', updateToggleIndicator);

        // Handle initial animation for artist content (which is visible by default)
        const artistParagraph = artistContent.querySelector('p');
        if (artistParagraph) {
            // Make sure it's visible initially
            artistParagraph.style.opacity = '1';
        }

        // Toggle between artist and producer roles
        artistToggle.addEventListener('click', function () {
            // Update active states
            artistToggle.classList.add('active');
            artistToggle.classList.add('text-white');
            producerToggle.classList.remove('active');
            producerToggle.classList.remove('text-white');

            // Update toggle indicator
            updateToggleIndicator();

            // Show/hide content
            artistContent.classList.remove('hidden');
            artistContent.classList.add('active');
            producerContent.classList.add('hidden');
            producerContent.classList.remove('active');

            // Animate the content in
            const content = artistContent.querySelector('p');
            if (content) {
                // Reset animation by removing and re-adding the class
                content.classList.remove('animate-fade-in');
                // Force a reflow to restart the animation
                void content.offsetWidth;
                content.classList.add('animate-fade-in');
            }
        });

        producerToggle.addEventListener('click', function () {
            // Update active states
            producerToggle.classList.add('active');
            producerToggle.classList.add('text-white');
            artistToggle.classList.remove('active');
            artistToggle.classList.remove('text-white');

            // Update toggle indicator
            updateToggleIndicator();

            // Show/hide content
            producerContent.classList.remove('hidden');
            producerContent.classList.add('active');
            artistContent.classList.add('hidden');
            artistContent.classList.remove('active');

            // Animate the content in
            const content = producerContent.querySelector('p');
            if (content) {
                // Reset animation by removing and re-adding the class
                content.classList.remove('animate-fade-in');
                // Force a reflow to restart the animation
                void content.offsetWidth;
                content.classList.add('animate-fade-in');
            }
        });
    }

    // Audio Visualization
    const generateWaveformBars = () => {
        const beforeWaveform = document.querySelector('.waveform-before');
        const afterWaveform = document.querySelector('.waveform-after');

        if (beforeWaveform && afterWaveform) {
            // Clear existing bars
            beforeWaveform.innerHTML = '';
            afterWaveform.innerHTML = '';

            // Generate 20 bars for each waveform
            for (let i = 0; i < 20; i++) {
                // Before waveform (smaller amplitude)
                const beforeBar = document.createElement('div');
                const randomHeight = 10 + Math.random() * 25; // Random height between 5-35px
                beforeBar.className = 'inline-block bg-primary/60 mx-0.5 rounded-sm';
                beforeBar.style.height = `${randomHeight}px`;
                beforeBar.style.width = '3px';
                beforeWaveform.appendChild(beforeBar);

                // After waveform (larger amplitude, more defined)
                const afterBar = document.createElement('div');
                const enhancedHeight = randomHeight * (1.5 + Math.random() * 0.5); // 1.5-2x taller
                afterBar.className = 'inline-block bg-primary mx-0.5 rounded-sm';
                afterBar.style.height = `${enhancedHeight}px`;
                afterBar.style.width = '3px';
                afterWaveform.appendChild(afterBar);
            }
        }
    };

    // Generate waveform visualization
    generateWaveformBars();

    // Audio Visualizer Background
    const audioVisualizer = document.getElementById('audio-visualizer');

    if (audioVisualizer) {
        // Create canvas for audio visualization
        const canvas = document.createElement('canvas');
        canvas.width = audioVisualizer.offsetWidth;
        canvas.height = audioVisualizer.offsetHeight;
        audioVisualizer.appendChild(canvas);

        const ctx = canvas.getContext('2d');

        // Animation function for visualizer
        function drawVisualizer() {
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw visualization (simple frequency bars)
            const barWidth = Math.max(2, canvas.width / 100);
            const barSpacing = 1;
            const barCount = Math.floor(canvas.width / (barWidth + barSpacing));

            for (let i = 0; i < barCount; i++) {
                // Random height based on position (creating a wave-like pattern)
                const heightFactor = 0.2 + 0.5 * Math.sin((Date.now() / 1000 + i / 20) * Math.PI);
                const barHeight = heightFactor * canvas.height / 2;

                // Gradient color
                const gradient = ctx.createLinearGradient(0, canvas.height - barHeight, 0, canvas.height);
                gradient.addColorStop(0, 'rgba(124, 58, 237, 0.8)');  // Primary color (purple)
                gradient.addColorStop(1, 'rgba(124, 58, 237, 0.2)');

                ctx.fillStyle = gradient;
                ctx.fillRect(
                    i * (barWidth + barSpacing),
                    canvas.height - barHeight,
                    barWidth,
                    barHeight
                );
            }

            requestAnimationFrame(drawVisualizer);
        }

        // Start visualization
        drawVisualizer();

        // Handle resize
        window.addEventListener('resize', () => {
            canvas.width = audioVisualizer.offsetWidth;
            canvas.height = audioVisualizer.offsetHeight;
        });
    }

    // Animated Counter
    const animateCounters = () => {
        const counters = document.querySelectorAll('.count-up');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const startTime = performance.now();

            const updateCounter = (timestamp) => {
                const elapsed = timestamp - startTime;
                const progress = Math.min(elapsed / duration, 1);
                counter.textContent = Math.floor(progress * target).toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            };

            requestAnimationFrame(updateCounter);
        });
    };

    // Initialize counter animation when in viewport
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.disconnect(); // Only run once
            }
        });
    });

    const statsCounter = document.querySelector('.stats-counter');
    if (statsCounter) {
        observer.observe(statsCounter);
    }
}); 