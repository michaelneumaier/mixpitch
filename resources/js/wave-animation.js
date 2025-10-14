/**
 * Wave Animation - Creates flowing wave effect by morphing SVG path
 * Animates wave shapes to create smooth, organic flowing motion
 */

class WaveAnimator {
    constructor(svgElement, options = {}) {
        this.svg = svgElement;
        this.path = svgElement.querySelector('path');

        if (!this.path) {
            console.warn('No path element found in SVG');
            return;
        }

        // Animation parameters
        this.speed = options.speed || 0.02;
        this.amplitude = options.amplitude || 3;
        this.frequency = options.frequency || 0.008;
        this.time = options.initialTime || 0;

        // Store original path data
        this.originalPathData = this.parsePathData(this.path.getAttribute('d'));
        this.animationId = null;

        // Start animation
        this.animate();
    }

    /**
     * Parse SVG path data into array of commands
     */
    parsePathData(pathString) {
        const commands = [];
        const regex = /([MLHVCSQTAZmlhvcsqtaz])([^MLHVCSQTAZmlhvcsqtaz]*)/g;
        let match;

        while ((match = regex.exec(pathString)) !== null) {
            const command = match[1];
            const coords = match[2].trim().split(/[\s,]+/).filter(Boolean).map(Number);
            commands.push({ command, coords });
        }

        return commands;
    }

    /**
     * Apply sine wave transformation to path coordinates
     */
    transformPath(pathData, time) {
        return pathData.map((segment, index) => {
            const { command, coords } = segment;

            // Only transform commands with Y coordinates (not H, V, or Z)
            if (command === 'Z' || command === 'z' || coords.length === 0) {
                return segment;
            }

            // Transform Y coordinates with sine wave
            const transformedCoords = coords.map((value, i) => {
                // Only modify Y coordinates (odd indices in most commands)
                if (i % 2 === 1) {
                    // Get corresponding X coordinate
                    const x = coords[i - 1] || 0;
                    // Apply sine wave offset
                    const offset = Math.sin(time + x * this.frequency) * this.amplitude;
                    return value + offset;
                }
                return value;
            });

            return { command, coords: transformedCoords };
        });
    }

    /**
     * Rebuild path string from command array
     */
    buildPathString(pathData) {
        return pathData.map(({ command, coords }) => {
            return command + coords.join(',');
        }).join('');
    }

    /**
     * Animation loop
     */
    animate() {
        this.time += this.speed;

        // Transform the path
        const transformedPath = this.transformPath(this.originalPathData, this.time);

        // Update SVG path
        const newPathString = this.buildPathString(transformedPath);
        this.path.setAttribute('d', newPathString);

        // Continue animation
        this.animationId = requestAnimationFrame(() => this.animate());
    }

    /**
     * Stop animation
     */
    stop() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
    }

    /**
     * Reset to original path
     */
    reset() {
        this.stop();
        this.path.setAttribute('d', this.buildPathString(this.originalPathData));
    }
}

/**
 * Initialize wave animations
 */
export function initWaveAnimations() {
    // Bottom wave - slower, more subtle
    const bottomWave = document.getElementById('wave-bottom');
    if (bottomWave) {
        new WaveAnimator(bottomWave, {
            speed: 0.015,
            amplitude: 8,
            frequency: 0.006,
            initialTime: 0
        });
    }

    // Top wave - slightly faster, different phase
    const topWave = document.getElementById('wave-top');
    if (topWave) {
        new WaveAnimator(topWave, {
            speed: 0.018,
            amplitude: 7,
            frequency: 0.007,
            initialTime: Math.PI // Start at different phase for variety
        });
    }
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWaveAnimations);
} else {
    initWaveAnimations();
}

export default WaveAnimator;
