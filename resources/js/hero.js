// Wait for both DOMContentLoaded and Alpine initialization
let heroComponentsInitialized = false;

function initHeroComponents() {
    // Prevent duplicate initialization
    if (heroComponentsInitialized) {
        console.log('Hero.js: Components already initialized, skipping...');
        return;
    }
    heroComponentsInitialized = true;

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
            if (activeToggle === producerToggle) {
                toggleIndicator.style.transform = 'translateX(0)';
                toggleIndicator.style.width = `${producerToggle.offsetWidth - 8}px`;
            } else if (activeToggle === artistToggle) {
                toggleIndicator.style.transform = `translateX(${producerToggle.offsetWidth}px)`;
                toggleIndicator.style.width = `${artistToggle.offsetWidth - 8}px`;
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

    // High-Performance Audio Visualizer
    const audioVisualizer = document.getElementById('audio-visualizer');

    if (audioVisualizer) {
        console.log('Hero.js: Audio visualizer element found, initializing optimized canvas...');

        // Clear any existing canvases to prevent duplicates
        audioVisualizer.innerHTML = '';

        // Use double requestAnimationFrame to ensure layout is fully settled
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                // Main display canvas
                const canvas = document.createElement('canvas');
                canvas.width = audioVisualizer.offsetWidth;
                canvas.height = audioVisualizer.offsetHeight;
                audioVisualizer.appendChild(canvas);

                // Off-screen buffer canvas for optimized rendering
                const bufferCanvas = document.createElement('canvas');
                bufferCanvas.width = canvas.width;
                bufferCanvas.height = canvas.height;

                const ctx = canvas.getContext('2d');
                const bufferCtx = bufferCanvas.getContext('2d');

                // Enable hardware acceleration
                canvas.style.willChange = 'transform';

                let animationId;
                let lastFrameTime = 0;
                const targetFPS = 30;
                const frameInterval = 1000 / targetFPS;

                // Optimized visualizer configuration
                const config = {
                    barCount: Math.min(60, Math.floor(canvas.width / 12)),
                    colors: [
                        { r: 59, g: 130, b: 246 },   // Blue
                        { r: 147, g: 51, b: 234 },   // Purple
                        { r: 236, g: 72, b: 153 },   // Pink
                    ],
                    waveSpeed: 0.015,
                    amplitude: 0.7
                };

                // Pre-compute expensive values for performance
                const barWidth = Math.max(4, canvas.width / config.barCount);
                const barSpacing = 3;
                const barStep = barWidth + barSpacing;

                // Pre-cached color strings for better performance
                const colorCache = config.colors.map(color => ({
                    normal: `rgba(${color.r}, ${color.g}, ${color.b}, 0.7)`,
                    glow: `rgba(${color.r}, ${color.g}, ${color.b}, 0.3)`,
                    ...color
                }));

                // Sine wave lookup table for ultra-fast calculations
                const LOOKUP_SIZE = 1024;
                const sineTable = new Float32Array(LOOKUP_SIZE);
                for (let i = 0; i < LOOKUP_SIZE; i++) {
                    sineTable[i] = Math.sin((i / LOOKUP_SIZE) * Math.PI * 2);
                }

                // Fast sine lookup function
                function fastSin(angle) {
                    const index = Math.floor(((angle % (Math.PI * 2)) / (Math.PI * 2)) * LOOKUP_SIZE) % LOOKUP_SIZE;
                    return sineTable[index];
                }

                function drawOptimizedVisualizer(currentTime) {
                    // Frame rate limiting
                    if (currentTime - lastFrameTime < frameInterval) {
                        animationId = requestAnimationFrame(drawOptimizedVisualizer);
                        return;
                    }
                    lastFrameTime = currentTime;

                    const time = currentTime / 1000;

                    // Clear buffer canvas completely each frame for smooth animation
                    bufferCtx.clearRect(0, 0, bufferCanvas.width, bufferCanvas.height);

                    // Batch all shadow operations for better performance
                    const shadowBars = [];

                    for (let i = 0; i < config.barCount; i++) {
                        // Ultra-fast wave calculation using lookup table
                        const wave1Index = ((time * 1.8 + i * 0.12) % (Math.PI * 2));
                        const wave2Index = ((time * 1.2 + i * 0.18) % (Math.PI * 2));

                        const wave1 = fastSin(wave1Index) * 0.5 + 0.5;
                        const wave2 = fastSin(wave2Index) * 0.3 + 0.3;

                        const combinedWave = (wave1 + wave2) * 0.5;
                        const barHeight = combinedWave * canvas.height * config.amplitude;

                        // Pre-calculated color index for performance
                        const colorIndex = Math.floor(((i / config.barCount + time * 0.08) % 1) * colorCache.length);
                        const colorData = colorCache[colorIndex];

                        const x = i * barStep;
                        const y = canvas.height - barHeight;

                        // Draw main bar
                        bufferCtx.fillStyle = colorData.normal;
                        bufferCtx.fillRect(x, y, barWidth, barHeight);

                        // Collect shadow bars for batch processing
                        if (i % 3 === 0) {
                            shadowBars.push({ x, y, width: barWidth, height: barHeight, color: colorData.glow });
                        }
                    }

                    // Batch process all shadow effects for optimal performance
                    if (shadowBars.length > 0) {
                        bufferCtx.shadowBlur = 8;
                        shadowBars.forEach(bar => {
                            bufferCtx.shadowColor = bar.color;
                            bufferCtx.fillStyle = bar.color;
                            bufferCtx.fillRect(bar.x, bar.y, bar.width, bar.height);
                        });
                        bufferCtx.shadowBlur = 0;
                    }

                    // Copy buffer to main canvas for display
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(bufferCanvas, 0, 0);

                    animationId = requestAnimationFrame(drawOptimizedVisualizer);
                }

                drawOptimizedVisualizer(performance.now());

                // Optimized resize handler with buffer canvas updates
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        const newWidth = audioVisualizer.offsetWidth;
                        const newHeight = audioVisualizer.offsetHeight;

                        canvas.width = newWidth;
                        canvas.height = newHeight;
                        bufferCanvas.width = newWidth;
                        bufferCanvas.height = newHeight;

                        config.barCount = Math.min(60, Math.floor(newWidth / 12));
                    }, 250);
                });
            });
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
                drawOptimizedVisualizer(performance.now());
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