// Wait for both DOMContentLoaded and Alpine initialization
function initHeroComponents() {
    console.log('Hero.js: Initializing hero components...');
    
    try {
    // Enhanced Role Toggle Functionality
    const artistToggle = document.getElementById('artist-toggle');
    const producerToggle = document.getElementById('producer-toggle');
    const toggleIndicator = document.querySelector('.toggle-indicator');
    const artistContent = document.getElementById('artist-content');
    const producerContent = document.getElementById('producer-content');

    if (artistToggle && producerToggle && toggleIndicator) {
        console.log('Hero.js: Role toggle elements found, setting up toggle functionality...');
        // Set initial toggle state with improved sizing
        const updateToggleIndicator = () => {
            const activeToggle = document.querySelector('.role-toggle-btn.active');
            if (activeToggle === artistToggle) {
                toggleIndicator.style.transform = 'translateX(0)';
                toggleIndicator.style.width = `${artistToggle.offsetWidth - 8}px`;
            } else if (activeToggle === producerToggle) {
                toggleIndicator.style.transform = `translateX(${artistToggle.offsetWidth}px)`;
                toggleIndicator.style.width = `${producerToggle.offsetWidth - 8}px`;
            }
        };

        // Initial setup
        updateToggleIndicator();

        // Handle window resize
        window.addEventListener('resize', updateToggleIndicator);

        // Enhanced toggle animations
        const switchToRole = (activeBtn, inactiveBtn, activeContent, inactiveContent) => {
            // Update button states
            activeBtn.classList.add('active', 'text-white');
            inactiveBtn.classList.remove('active', 'text-white');
            inactiveBtn.classList.add('text-white/70');

            // Update toggle indicator
            updateToggleIndicator();

            // Animate content transition
            inactiveContent.style.opacity = '0';
            inactiveContent.style.transform = 'translateY(20px)';

            setTimeout(() => {
                inactiveContent.classList.add('hidden');
                inactiveContent.classList.remove('active');
                activeContent.classList.remove('hidden');
                activeContent.classList.add('active');

                // Animate in new content
                activeContent.style.opacity = '0';
                activeContent.style.transform = 'translateY(20px)';

                requestAnimationFrame(() => {
                    activeContent.style.transition = 'all 0.5s ease-out';
                    activeContent.style.opacity = '1';
                    activeContent.style.transform = 'translateY(0)';
                });
            }, 200);
        };

        artistToggle.addEventListener('click', () => {
            if (!artistToggle.classList.contains('active')) {
                switchToRole(artistToggle, producerToggle, artistContent, producerContent);
            }
        });

        producerToggle.addEventListener('click', () => {
            if (!producerToggle.classList.contains('active')) {
                switchToRole(producerToggle, artistToggle, producerContent, artistContent);
            }
        });
    } else {
        console.warn('Hero.js: Role toggle elements not found:', {
            artistToggle: !!artistToggle,
            producerToggle: !!producerToggle,
            toggleIndicator: !!toggleIndicator
        });
    }

    // Enhanced Audio Visualizer with WebGL-like effects
    const audioVisualizer = document.getElementById('audio-visualizer');

    if (audioVisualizer) {
        console.log('Hero.js: Audio visualizer element found, initializing canvas...');
        const canvas = document.createElement('canvas');
        canvas.width = audioVisualizer.offsetWidth;
        canvas.height = audioVisualizer.offsetHeight;
        audioVisualizer.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        let animationId;
        let lastFrameTime = 0;
        const targetFPS = 30; // Reduced from 60 for better performance
        const frameInterval = 1000 / targetFPS;

        // Optimized visualizer configuration
        const config = {
            barCount: Math.min(60, Math.floor(canvas.width / 12)), // Reduced bar count
            colors: [
                { r: 59, g: 130, b: 246 },   // Blue
                { r: 147, g: 51, b: 234 },   // Purple
                { r: 236, g: 72, b: 153 },   // Pink
            ],
            waveSpeed: 0.015, // Slightly slower for smoother animation
            amplitude: 0.7
        };

        function drawEnhancedVisualizer(currentTime) {
            // Frame rate limiting
            if (currentTime - lastFrameTime < frameInterval) {
                animationId = requestAnimationFrame(drawEnhancedVisualizer);
                return;
            }
            lastFrameTime = currentTime;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const time = currentTime / 1000;
            const barWidth = Math.max(4, canvas.width / config.barCount);
            const barSpacing = 3;

            for (let i = 0; i < config.barCount; i++) {
                // Simplified wave calculation for better performance
                const wave1 = Math.sin(time * 1.8 + i * 0.12) * 0.5 + 0.5;
                const wave2 = Math.sin(time * 1.2 + i * 0.18) * 0.3 + 0.3;

                const combinedWave = (wave1 + wave2) / 2; // Reduced from 3 waves to 2
                const barHeight = combinedWave * canvas.height * config.amplitude;

                // Simplified color calculation
                const colorIndex = (i / config.barCount + time * 0.08) % 1;
                const colorArrayIndex = Math.floor(colorIndex * config.colors.length);
                const color = config.colors[colorArrayIndex];

                // Use solid colors instead of gradients for better performance
                ctx.fillStyle = `rgba(${color.r}, ${color.g}, ${color.b}, 0.7)`;
                ctx.fillRect(
                    i * (barWidth + barSpacing),
                    canvas.height - barHeight,
                    barWidth,
                    barHeight
                );

                // Simplified glow effect (only for every 3rd bar)
                if (i % 3 === 0) {
                    ctx.shadowColor = `rgba(${color.r}, ${color.g}, ${color.b}, 0.3)`;
                    ctx.shadowBlur = 8;
                    ctx.fillRect(
                        i * (barWidth + barSpacing),
                        canvas.height - barHeight,
                        barWidth,
                        barHeight
                    );
                    ctx.shadowBlur = 0;
                }
            }

            animationId = requestAnimationFrame(drawEnhancedVisualizer);
        }

        drawEnhancedVisualizer(performance.now());

        // Optimized resize handler with debouncing
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                canvas.width = audioVisualizer.offsetWidth;
                canvas.height = audioVisualizer.offsetHeight;
                config.barCount = Math.min(60, Math.floor(canvas.width / 12));
            }, 250);
        });
    }

    // Optimized Floating Particles System
    const particlesContainer = document.getElementById('particles-container');

    if (particlesContainer) {
        const particleCanvas = document.createElement('canvas');
        particleCanvas.width = particlesContainer.offsetWidth;
        particleCanvas.height = particlesContainer.offsetHeight;
        particlesContainer.appendChild(particleCanvas);

        const pCtx = particleCanvas.getContext('2d');
        const particles = [];
        const particleCount = 25; // Reduced from 50
        let lastParticleFrame = 0;
        const particleFrameInterval = 1000 / 20; // 20 FPS for particles

        // Optimized Particle class
        class Particle {
            constructor() {
                this.reset();
                this.y = Math.random() * particleCanvas.height;
            }

            reset() {
                this.x = Math.random() * particleCanvas.width;
                this.y = -10;
                this.size = Math.random() * 2 + 1; // Smaller particles
                this.speedY = Math.random() * 1.5 + 0.3; // Slower movement
                this.speedX = (Math.random() - 0.5) * 0.3;
                this.opacity = Math.random() * 0.4 + 0.1; // Lower opacity
                this.color = `rgba(255, 255, 255, ${this.opacity})`;
            }

            update() {
                this.y += this.speedY;
                this.x += this.speedX;

                if (this.y > particleCanvas.height + 10) {
                    this.reset();
                }

                if (this.x < 0 || this.x > particleCanvas.width) {
                    this.speedX *= -1;
                }
            }

            draw() {
                pCtx.beginPath();
                pCtx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                pCtx.fillStyle = this.color;
                pCtx.fill();
            }
        }

        // Initialize particles
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        function animateParticles(currentTime) {
            // Frame rate limiting for particles
            if (currentTime - lastParticleFrame < particleFrameInterval) {
                requestAnimationFrame(animateParticles);
                return;
            }
            lastParticleFrame = currentTime;

            pCtx.clearRect(0, 0, particleCanvas.width, particleCanvas.height);

            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });

            requestAnimationFrame(animateParticles);
        }

        animateParticles(performance.now());

        // Handle resize for particles with debouncing
        let particleResizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(particleResizeTimeout);
            particleResizeTimeout = setTimeout(() => {
                particleCanvas.width = particlesContainer.offsetWidth;
                particleCanvas.height = particlesContainer.offsetHeight;
            }, 250);
        });
    }

    // Scroll-triggered animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
            }
        });
    }, observerOptions);

    // Observe elements for scroll animations
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Enhanced button hover effects
    document.querySelectorAll('.group').forEach(button => {
        button.addEventListener('mouseenter', function () {
            this.style.transform = 'scale(1.05) translateY(-2px)';
        });

        button.addEventListener('mouseleave', function () {
            this.style.transform = 'scale(1) translateY(0)';
        });
    });

    // Performance optimization: Pause animations when not visible
    let isVisible = true;

    document.addEventListener('visibilitychange', () => {
        isVisible = !document.hidden;

        if (!isVisible) {
            // Pause heavy animations when tab is not visible
            if (animationId) {
                cancelAnimationFrame(animationId);
            }
        } else {
            // Resume animations when tab becomes visible
            if (audioVisualizer) {
                drawEnhancedVisualizer(performance.now());
            }
        }
    });

    // Add dynamic gradient animation
    const gradientOverlay = document.querySelector('.animate-gradient-x');
    if (gradientOverlay) {
        let gradientPosition = 0;

        function animateGradient() {
            gradientPosition += 0.5;
            gradientOverlay.style.backgroundPosition = `${gradientPosition}% 50%`;

            if (isVisible) {
                requestAnimationFrame(animateGradient);
            }
        }

        animateGradient();
    }
    
    console.log('Hero.js: Hero components initialization completed successfully');
    
    } catch (error) {
        console.error('Hero.js: Error during initialization:', error);
        console.error('Hero.js: Stack trace:', error.stack);
    }
}

// Initialize when DOM is ready and after a slight delay for Flux components
console.log('Hero.js: Script loaded, document.readyState:', document.readyState);

if (document.readyState === 'loading') {
    console.log('Hero.js: Waiting for DOMContentLoaded...');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Hero.js: DOMContentLoaded event fired');
        initHeroComponents();
    });
} else {
    console.log('Hero.js: DOM already loaded, initializing immediately');
    // DOM is already loaded
    initHeroComponents();
}

// Also initialize after Alpine and Flux components are ready
document.addEventListener('alpine:init', function() {
    console.log('Hero.js: Alpine initialized, initializing hero components with delay...');
    setTimeout(initHeroComponents, 100);
}); 