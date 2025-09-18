/**
 * Global Drag & Drop Manager for MixPitch
 * Extends the existing GlobalUploadManager to provide drag & drop functionality across the entire application
 */

const GlobalDragDropManager = (() => {
    let instance;

    function create() {
        const state = {
            isEnabled: false,
            isDragging: false,
            dragOverlay: null,
            activeDropZones: new Map(),
            currentTarget: null,
            defaultMeta: null,
            dragEnterCounter: 0, // Track nested drag events
        };

        function createDragOverlay() {
            if (state.dragOverlay) return state.dragOverlay;

            const overlay = document.createElement('div');
            overlay.id = 'global-drag-overlay';
            overlay.className = 'global-drag-overlay';
            overlay.innerHTML = `
                <div class="global-drag-overlay-content">
                    <div class="global-drag-overlay-icon">
                        <svg class="w-16 h-16 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <div class="global-drag-overlay-text">
                        <h3 class="text-xl font-semibold text-white mb-2">Drop files to upload</h3>
                        <p class="text-gray-300" id="global-drag-overlay-context"></p>
                    </div>
                </div>
            `;

            // Add CSS styles
            const styles = `
                .global-drag-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.8);
                    backdrop-filter: blur(8px);
                    z-index: 9999;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s ease-out;
                }
                
                .global-drag-overlay.show {
                    display: flex;
                }
                
                .global-drag-overlay-content {
                    text-align: center;
                    padding: 2rem;
                    border: 2px dashed #60a5fa;
                    border-radius: 1rem;
                    background: rgba(59, 130, 246, 0.1);
                    min-width: 300px;
                    transition: all 0.2s ease-out;
                }
                
                .global-drag-overlay.drag-over .global-drag-overlay-content {
                    border-color: #34d399;
                    background: rgba(52, 211, 153, 0.2);
                    transform: scale(1.05);
                }
                
                .global-drag-overlay-icon {
                    margin-bottom: 1rem;
                }
                
                .global-drag-overlay.drag-over .global-drag-overlay-icon svg {
                    color: #34d399;
                }
            `;

            // Inject styles if not already present
            if (!document.getElementById('global-drag-drop-styles')) {
                const styleSheet = document.createElement('style');
                styleSheet.id = 'global-drag-drop-styles';
                styleSheet.textContent = styles;
                document.head.appendChild(styleSheet);
            }

            document.body.appendChild(overlay);
            state.dragOverlay = overlay;

            return overlay;
        }

        function updateOverlayContext(meta = null) {
            const contextEl = document.getElementById('global-drag-overlay-context');
            if (!contextEl) return;

            const targetMeta = meta || state.currentTarget || state.defaultMeta;
            if (!targetMeta) {
                contextEl.textContent = 'Drop files to upload';
                return;
            }

            let contextText = '';

            // Handle section headers (expandable sections)
            if (targetMeta.expandable && targetMeta.section) {
                contextText = `Hover to expand ${targetMeta.modelLabel}`;
                contextEl.textContent = contextText;
                return;
            }

            if (targetMeta.modelLabel && targetMeta.modelId) {
                // Handle different upload contexts
                if (targetMeta.modelLabel === 'Pitch' && targetMeta.isClientManagement) {
                    contextText = 'Upload deliverables';
                    if (targetMeta.pitchTitle) {
                        contextText += ` for: ${targetMeta.pitchTitle}`;
                    }
                    if (targetMeta.clientName) {
                        contextText += ` (Client: ${targetMeta.clientName})`;
                    }
                } else if (targetMeta.workflowType === 'contest') {
                    if (targetMeta.modelLabel === 'Project') {
                        contextText = 'Upload contest files';
                    } else {
                        contextText = 'Upload contest entry';
                    }
                    if (targetMeta.projectTitle || targetMeta.pitchTitle) {
                        contextText += ` for: ${targetMeta.projectTitle || targetMeta.pitchTitle}`;
                    }
                } else if (targetMeta.modelLabel === 'Pitch') {
                    // Check pitch status for authorization hints
                    if (targetMeta.pitchStatus && ['completed', 'denied'].includes(targetMeta.pitchStatus)) {
                        contextText = `Cannot upload - pitch is ${targetMeta.pitchStatus}`;
                    } else {
                        contextText = 'Upload pitch files';
                        if (targetMeta.pitchTitle) {
                            contextText += ` for: ${targetMeta.pitchTitle}`;
                        }
                    }
                } else if (targetMeta.modelLabel === 'Project') {
                    contextText = 'Upload project files';
                    if (targetMeta.projectTitle) {
                        contextText += ` for: ${targetMeta.projectTitle}`;
                    }
                    if (targetMeta.workflowType && targetMeta.workflowType !== 'standard') {
                        contextText += ` (${targetMeta.workflowType.replace('_', ' ')})`;
                    }
                } else if (targetMeta.modelLabel === 'Order') {
                    contextText = 'Upload order files';
                    if (targetMeta.itemName) {
                        contextText += ` for: ${targetMeta.itemName}`;
                    }
                } else if (targetMeta.modelLabel === 'Service') {
                    contextText = 'Upload service files';
                    if (targetMeta.itemName) {
                        contextText += ` for: ${targetMeta.itemName}`;
                    }
                } else {
                    contextText = `Upload to ${targetMeta.modelLabel}`;

                    // Add additional context if available
                    if (targetMeta.projectTitle) {
                        contextText += `: ${targetMeta.projectTitle}`;
                    } else if (targetMeta.pitchTitle) {
                        contextText += `: ${targetMeta.pitchTitle}`;
                    } else if (targetMeta.itemName) {
                        contextText += `: ${targetMeta.itemName}`;
                    }
                }
            } else {
                contextText = `Upload to ${targetMeta.context || 'project'}`;
            }

            contextEl.textContent = contextText;
        }

        function setupGlobalListeners() {
            // Prevent default drag behaviors on the entire document
            document.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            document.addEventListener('dragenter', (e) => {
                e.preventDefault();
                e.stopPropagation();

                state.dragEnterCounter++;

                // Don't show overlay if dashboard drop zones are present
                const hasDashboardDrops = document.body.hasAttribute('data-has-dashboard-drops');

                if (state.dragEnterCounter === 1 && state.isEnabled && !hasDashboardDrops) {
                    state.isDragging = true;
                    showOverlay();
                }
            });

            document.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();

                state.dragEnterCounter--;

                if (state.dragEnterCounter === 0) {
                    state.isDragging = false;
                    hideOverlay();
                }
            });

            document.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();

                state.dragEnterCounter = 0;
                state.isDragging = false;
                hideOverlay();

                if (!state.isEnabled) return;

                const files = Array.from(e.dataTransfer.files || []);
                if (files.length > 0) {
                    const targetMeta = state.currentTarget || state.defaultMeta;
                    if (targetMeta && window.GlobalUploader) {
                        // Convert dashboard metadata if needed
                        const uploadMeta = targetMeta.modelType && targetMeta.modelType !== targetMeta.modelLabel
                            ? convertToUploadMeta(targetMeta)
                            : targetMeta;
                        // Use the enhanced addValidatedFiles method which includes validation and feedback
                        window.GlobalUploader.addValidatedFiles(files, uploadMeta);
                    }
                }
            });

            // Handle overlay drag events
            if (state.dragOverlay) {
                state.dragOverlay.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    state.dragOverlay.classList.add('drag-over');
                });

                state.dragOverlay.addEventListener('dragleave', (e) => {
                    if (!state.dragOverlay.contains(e.relatedTarget)) {
                        state.dragOverlay.classList.remove('drag-over');
                    }
                });

                state.dragOverlay.addEventListener('drop', (e) => {
                    state.dragOverlay.classList.remove('drag-over');
                });
            }
        }

        function showOverlay() {
            if (!state.dragOverlay) return;

            updateOverlayContext();
            state.dragOverlay.classList.add('show');

            // Add global drag state class to body for CSS targeting
            document.body.classList.add('global-drag-active');

            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('global-drag-drop:show'));
        }

        function hideOverlay() {
            if (!state.dragOverlay) return;

            state.dragOverlay.classList.remove('show', 'drag-over');

            // Remove global drag state class from body
            document.body.classList.remove('global-drag-active');

            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('global-drag-drop:hide'));
        }

        function isUploadAuthorized(meta) {
            // Section headers are not valid upload targets
            if (meta.expandable && meta.section) {
                return false;
            }

            // Check for terminal pitch states that shouldn't accept uploads
            if (meta.modelLabel === 'Pitch' && meta.pitchStatus) {
                const terminalStates = ['completed', 'denied', 'cancelled'];
                if (terminalStates.includes(meta.pitchStatus)) {
                    return false;
                }
            }

            // Contest entries might have time restrictions (would need server validation)
            if (meta.workflowType === 'contest' && meta.modelLabel === 'Pitch') {
                // For now, allow contest entries - server will validate deadline
                return true;
            }

            // All other cases are authorized (server will do final authorization)
            return true;
        }

        function getAuthorizationErrorMessage(meta) {
            if (meta.modelLabel === 'Pitch' && meta.pitchStatus) {
                if (meta.pitchStatus === 'completed') {
                    return 'Cannot upload files to a completed pitch';
                } else if (meta.pitchStatus === 'denied') {
                    return 'Cannot upload files to a denied pitch';
                } else if (meta.pitchStatus === 'cancelled') {
                    return 'Cannot upload files to a cancelled pitch';
                }
            }

            return 'Upload not authorized for this item';
        }

        function convertToUploadMeta(meta) {
            // Convert dashboard drop zone metadata to GlobalUploader format
            const converted = {
                modelId: meta.modelId,
                modelLabel: meta.modelLabel,
                context: meta.context
            };

            // Map dashboard model types to GlobalUploader expected types
            // Handle both simple names ('project') and full class names ('App\\Models\\Project')
            const modelType = meta.modelType;
            switch (modelType) {
                case 'project':
                case 'App\\Models\\Project':
                    converted.modelType = 'App\\Models\\Project';
                    converted.projectTitle = meta.projectTitle;
                    converted.workflowType = meta.workflowType;
                    converted.projectStatus = meta.projectStatus;
                    break;

                case 'pitch':
                case 'App\\Models\\Pitch':
                    converted.modelType = 'App\\Models\\Pitch';
                    converted.pitchTitle = meta.pitchTitle;
                    converted.pitchStatus = meta.pitchStatus;
                    converted.workflowType = meta.workflowType;
                    converted.isClientManagement = meta.isClientManagement;
                    if (meta.clientName) {
                        converted.clientName = meta.clientName;
                    }
                    break;

                case 'order':
                case 'App\\Models\\Order':
                    converted.modelType = 'App\\Models\\Order';
                    break;

                case 'service':
                case 'App\\Models\\ServicePackage':
                    converted.modelType = 'App\\Models\\ServicePackage';
                    break;

                default:
                    converted.modelType = meta.modelType;
            }

            return converted;
        }

        // Auto-expand functionality for section headers
        let expandTimeouts = new Map();
        let expandingSections = new Set();

        function handleSectionAutoExpand(sectionName, element) {

            // Don't start a new timeout if already expanding this section
            if (expandingSections.has(sectionName)) {
                return;
            }

            expandingSections.add(sectionName);
            element.classList.add('drag-expand-pending');

            // Store original background for cleanup
            element._originalBackground = element.style.background;

            // Apply simple background change
            element.style.background = '#3b82f6';


            const timeout = setTimeout(() => {
                try {
                    // Trigger Alpine.js section expansion
                    const event = new CustomEvent('drag-expand-section', {
                        detail: { section: sectionName },
                        bubbles: true
                    });
                    element.dispatchEvent(event);
                } catch (e) {
                    // Failed to expand section
                }

                // Clean up after successful expansion
                expandingSections.delete(sectionName);
                element.classList.remove('drag-expand-pending');

                // Restore original background after expansion
                if (element._originalBackground !== undefined) {
                    element.style.background = element._originalBackground;
                    delete element._originalBackground;
                }

            }, 500); // Reduced to 500ms for faster testing

            expandTimeouts.set(sectionName, timeout);
        }

        function handleSectionAutoExpandLeave(sectionName, element) {
            if (expandTimeouts.has(sectionName)) {
                clearTimeout(expandTimeouts.get(sectionName));
                expandTimeouts.delete(sectionName);
                expandingSections.delete(sectionName);
                element.classList.remove('drag-expand-pending');

                // Restore original background when leaving (not when expanding)
                if (element._originalBackground !== undefined) {
                    element.style.background = element._originalBackground;
                    delete element._originalBackground;
                }

            }
        }

        function setupZoneListeners(element, meta) {
            if (!element || state.activeDropZones.has(element)) return;

            const enterHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();

                // For dashboard drop zones, clear all other dashboard active states first
                if (element.classList.contains('dashboard-drop-zone')) {
                    // Clear other dashboard drop zones to prevent multiple active states
                    document.querySelectorAll('.dashboard-drop-zone.drag-drop-active, .dashboard-drop-zone.drag-drop-disabled').forEach(el => {
                        if (el !== element) {
                            el.classList.remove('drag-drop-active', 'drag-drop-disabled');
                        }
                    });
                }


                state.currentTarget = meta;
                updateOverlayContext(meta);

                // Handle auto-expand for section headers
                if (meta.expandable && meta.section) {
                    handleSectionAutoExpand(meta.section, element);
                }

                // Check authorization and add appropriate visual feedback
                if (isUploadAuthorized(meta)) {
                    element.classList.add('drag-drop-active');
                    element.classList.remove('drag-drop-disabled');
                } else {
                    element.classList.add('drag-drop-disabled');
                    element.classList.remove('drag-drop-active');
                }
            };

            const overHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                // Ensure consistent state during dragover
                if (!element.classList.contains('drag-drop-active') && !element.classList.contains('drag-drop-disabled')) {
                    state.currentTarget = meta;
                    updateOverlayContext(meta);

                    if (isUploadAuthorized(meta)) {
                        element.classList.add('drag-drop-active');
                        element.classList.remove('drag-drop-disabled');
                    } else {
                        element.classList.add('drag-drop-disabled');
                        element.classList.remove('drag-drop-active');
                    }
                }
            };

            const leaveHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();

                // Handle section auto-expand leave with immediate cleanup
                if (meta.expandable && meta.section) {
                    // Check if we're truly leaving the section
                    const rect = element.getBoundingClientRect();
                    const mouseX = e.clientX;
                    const mouseY = e.clientY;

                    const isInside = mouseX >= rect.left && mouseX <= rect.right &&
                        mouseY >= rect.top && mouseY <= rect.bottom;

                    // If clearly outside bounds, clear immediately
                    if (!isInside && !element.contains(e.relatedTarget)) {
                        handleSectionAutoExpandLeave(meta.section, element);
                    } else {
                        // Use a shorter timeout for edge cases
                        setTimeout(() => {
                            const rect2 = element.getBoundingClientRect();
                            const isStillInside = e.clientX >= rect2.left && e.clientX <= rect2.right &&
                                e.clientY >= rect2.top && e.clientY <= rect2.bottom;

                            if (!isStillInside && !element.contains(e.relatedTarget)) {
                                handleSectionAutoExpandLeave(meta.section, element);
                            }
                        }, 50); // Shorter delay
                    }
                }

                // For dashboard drop zones, use pointer-position based cleanup to prevent sticky states
                if (element.classList.contains('dashboard-drop-zone')) {
                    // Determine the element currently under the pointer
                    const pointerX = typeof e.clientX === 'number' ? e.clientX : null;
                    const pointerY = typeof e.clientY === 'number' ? e.clientY : null;

                    let nextZone = null;
                    if (pointerX !== null && pointerY !== null) {
                        const node = document.elementFromPoint(pointerX, pointerY);
                        if (node) {
                            nextZone = node.closest('.dashboard-drop-zone');
                        }
                    }

                    const movingToAnotherDropZone = nextZone && nextZone !== element;
                    const trulyLeavingThisZone = !nextZone || movingToAnotherDropZone;

                    if (trulyLeavingThisZone) {
                        // Clean up current element's visual state immediately
                        element.classList.remove('drag-drop-active', 'drag-drop-disabled');

                        // If moving to another drop zone, ensure only that one remains active
                        if (nextZone) {
                            document.querySelectorAll('.dashboard-drop-zone.drag-drop-active, .dashboard-drop-zone.drag-drop-disabled').forEach(el => {
                                if (el !== nextZone) {
                                    el.classList.remove('drag-drop-active', 'drag-drop-disabled');
                                }
                            });
                        }

                        // Reset current target if this was it
                        if (state.currentTarget === meta) {
                            state.currentTarget = null;
                            updateOverlayContext();
                        }
                    }
                } else {
                    // Use timeout for non-dashboard elements to ensure reliable detection
                    setTimeout(() => {
                        // Check if mouse is still within element bounds
                        const rect = element.getBoundingClientRect();
                        const mouseX = e.clientX;
                        const mouseY = e.clientY;

                        const isInside = mouseX >= rect.left && mouseX <= rect.right &&
                            mouseY >= rect.top && mouseY <= rect.bottom;

                        // Also check if the related target is a child element
                        const isChildTarget = element.contains(e.relatedTarget);

                        // Only remove active state if we're truly leaving
                        if (!isInside && !isChildTarget) {
                            element.classList.remove('drag-drop-active', 'drag-drop-disabled');

                            // Only reset target if this was the current target
                            if (state.currentTarget === meta) {
                                state.currentTarget = state.defaultMeta;
                                updateOverlayContext();
                            }
                        }
                    }, 50); // Slightly longer timeout to be more reliable
                }
            };

            const dropHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                element.classList.remove('drag-drop-active', 'drag-drop-disabled');

                const files = Array.from(e.dataTransfer.files || []);
                if (files.length > 0 && window.GlobalUploader) {
                    // Check authorization before allowing upload
                    if (isUploadAuthorized(meta)) {
                        // Convert dashboard metadata to proper upload format
                        const uploadMeta = convertToUploadMeta(meta);
                        window.GlobalUploader.addValidatedFiles(files, uploadMeta);
                    } else {
                        // Show authorization error
                        try {
                            window.dispatchEvent(new CustomEvent('toaster:error', {
                                detail: {
                                    message: getAuthorizationErrorMessage(meta)
                                }
                            }));
                        } catch (e) {
                            console.warn('Could not show upload authorization error:', e);
                        }
                    }
                }
            };

            element.addEventListener('dragenter', enterHandler);
            element.addEventListener('dragover', overHandler);
            element.addEventListener('dragleave', leaveHandler);
            element.addEventListener('drop', dropHandler);

            // Store handlers for cleanup
            state.activeDropZones.set(element, {
                meta,
                handlers: { enterHandler, overHandler, leaveHandler, dropHandler }
            });

            // Add visual styling
            element.classList.add('drag-drop-zone');
        }

        return {
            enablePageDragDrop(defaultMeta) {
                state.isEnabled = true;
                state.defaultMeta = defaultMeta;
                createDragOverlay();
                setupGlobalListeners();

                // Add CSS for drop zones with enhanced sidebar feedback
                if (!document.getElementById('drag-drop-zone-styles')) {
                    const styleSheet = document.createElement('style');
                    styleSheet.id = 'drag-drop-zone-styles';
                    styleSheet.textContent = `
                        .drag-drop-zone {
                            transition: all 0.2s ease-out;
                            position: relative;
                        }
                        
                        .drag-drop-zone.drag-drop-active {
                            background-color: rgba(59, 130, 246, 0.1);
                            border-color: #60a5fa;
                            transform: scale(1.02);
                            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
                        }
                        
                        .drag-drop-zone:hover {
                            background-color: rgba(59, 130, 246, 0.05);
                        }
                        
                        /* Enhanced sidebar drop zone styling */
                        .sidebar-drop-zone {
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            border-left: 3px solid transparent;
                        }
                        
                        .sidebar-drop-zone.drag-drop-active {
                            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
                            border-left: 3px solid #60a5fa;
                            border-radius: 0.5rem;
                            transform: translateX(4px);
                            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
                        }
                        
                        .sidebar-drop-zone.drag-drop-active::before {
                            content: '';
                            position: absolute;
                            left: -3px;
                            top: 0;
                            bottom: 0;
                            width: 3px;
                            background: linear-gradient(to bottom, #60a5fa, #3b82f6);
                            border-radius: 0 2px 2px 0;
                        }
                        
                        /* Dark mode adjustments */
                        .dark .sidebar-drop-zone.drag-drop-active {
                            background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.08) 100%);
                            border-left-color: #3b82f6;
                            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
                        }
                        
                        /* Pulse animation for active drop zones during drag */
                        .global-drag-active .sidebar-drop-zone {
                            animation: subtle-pulse 2s infinite ease-in-out;
                        }
                        
                        @keyframes subtle-pulse {
                            0%, 100% { opacity: 1; }
                            50% { opacity: 0.8; }
                        }
                        
                        /* Enhanced feedback for invalid drop targets */
                        .sidebar-drop-zone.drag-drop-disabled {
                            opacity: 0.5;
                            cursor: not-allowed;
                            background: rgba(239, 68, 68, 0.1);
                            border-left-color: #ef4444;
                        }
                        
                        .dark .sidebar-drop-zone.drag-drop-disabled {
                            background: rgba(239, 68, 68, 0.15);
                        }
                        
                        /* Section header drag styling with high specificity */
                        .section-header-drop-zone {
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            position: relative;
                        }
                        
                        .section-header-drop-zone.drag-drop-disabled {
                            opacity: 1 !important;
                            cursor: default !important;
                        }
                        
                        /* Override all existing hover and background styles during drag */
                        .global-drag-active .section-header-drop-zone.drag-expand-pending,
                        .global-drag-active .section-header-drop-zone.drag-expand-pending:hover,
                        .section-header-drop-zone.drag-expand-pending,
                        .section-header-drop-zone.drag-expand-pending:hover {
                            background-color: #3b82f6 !important;
                            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
                            color: white !important;
                            border: 2px solid #1d4ed8 !important;
                            border-radius: 0.75rem !important;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4), 0 4px 12px rgba(59, 130, 246, 0.3) !important;
                            transform: scale(1.02) !important;
                        }
                        
                        /* Dark mode overrides */
                        .dark .global-drag-active .section-header-drop-zone.drag-expand-pending,
                        .dark .global-drag-active .section-header-drop-zone.drag-expand-pending:hover,
                        .dark .section-header-drop-zone.drag-expand-pending,
                        .dark .section-header-drop-zone.drag-expand-pending:hover {
                            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
                            border-color: #1e40af !important;
                            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.5), 0 4px 12px rgba(37, 99, 235, 0.4) !important;
                        }
                        
                        /* Badge and icon color overrides during drag */
                        .section-header-drop-zone.drag-expand-pending * {
                            color: white !important;
                        }
                        
                        /* Loading indicator for expanding sections */
                        .section-header-drop-zone.drag-expand-pending::after {
                            content: '';
                            position: absolute;
                            right: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            width: 18px;
                            height: 18px;
                            border: 3px solid rgba(255, 255, 255, 0.3);
                            border-top: 3px solid white;
                            border-radius: 50%;
                            animation: spin 0.8s linear infinite;
                            z-index: 10;
                        }
                        
                        @keyframes spin {
                            0% { transform: translateY(-50%) rotate(0deg); }
                            100% { transform: translateY(-50%) rotate(360deg); }
                        }
                        
                        /* Dashboard table row drop zone styling */
                        .dashboard-drop-zone {
                            transition: all 0.2s ease-out;
                            position: relative;
                        }
                        
                        .dashboard-drop-zone.drag-drop-active {
                            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%) !important;
                            border-left: 4px solid #60a5fa;
                            transform: scale(1.01);
                            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
                            border-radius: 0.5rem;
                        }
                        
                        .dashboard-drop-zone.drag-drop-active:hover {
                            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.08) 100%) !important;
                        }
                        
                        /* Dark mode adjustments */
                        .dark .dashboard-drop-zone.drag-drop-active {
                            background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(99, 102, 241, 0.1) 100%) !important;
                            border-left-color: #3b82f6;
                            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
                        }
                        
                        .dark .dashboard-drop-zone.drag-drop-active:hover {
                            background: linear-gradient(90deg, rgba(59, 130, 246, 0.25) 0%, rgba(99, 102, 241, 0.15) 100%) !important;
                        }
                        
                        /* Remove ::before on dashboard rows to avoid full-table hover capture */
                        .dashboard-drop-zone::before { content: none !important; }

                        /* Enhanced feedback for unauthorized dashboard drops */
                        .dashboard-drop-zone.drag-drop-disabled {
                            opacity: 0.6;
                            cursor: not-allowed;
                            background: rgba(239, 68, 68, 0.08) !important;
                            border-left: 4px solid #ef4444;
                            border-radius: 0.5rem;
                        }
                        
                        .dark .dashboard-drop-zone.drag-drop-disabled {
                            background: rgba(239, 68, 68, 0.12) !important;
                        }
                        
                        /* Subtle animation for dashboard items during global drag */
                        .global-drag-active .dashboard-drop-zone {
                            animation: dashboard-breathe 3s infinite ease-in-out;
                        }
                        
                        @keyframes dashboard-breathe {
                            0%, 100% { transform: scale(1); }
                            50% { transform: scale(1.005); }
                        }
                    `;
                    document.head.appendChild(styleSheet);
                }
            },

            disablePageDragDrop() {
                state.isEnabled = false;
                hideOverlay();
            },

            registerDropZone(element, meta) {
                if (typeof element === 'string') {
                    element = document.querySelector(element);
                }
                if (element) {
                    setupZoneListeners(element, meta);
                }
            },

            unregisterDropZone(element) {
                if (typeof element === 'string') {
                    element = document.querySelector(element);
                }

                const zoneData = state.activeDropZones.get(element);
                if (zoneData) {
                    const { handlers } = zoneData;
                    element.removeEventListener('dragenter', handlers.enterHandler);
                    element.removeEventListener('dragover', handlers.overHandler);
                    element.removeEventListener('dragleave', handlers.leaveHandler);
                    element.removeEventListener('drop', handlers.dropHandler);
                    element.classList.remove('drag-drop-zone', 'drag-drop-active', 'drag-drop-disabled');
                    state.activeDropZones.delete(element);
                }
            },

            setCurrentTarget(meta) {
                state.currentTarget = meta;
                updateOverlayContext(meta);
            },

            clearCurrentTarget() {
                state.currentTarget = null;
                updateOverlayContext();
            },

            getState() {
                return {
                    isEnabled: state.isEnabled,
                    isDragging: state.isDragging,
                    activeZones: state.activeDropZones.size,
                    currentTarget: state.currentTarget,
                    defaultMeta: state.defaultMeta,
                };
            },

            destroy() {
                state.isEnabled = false;
                hideOverlay();

                // Clean up all drop zones
                for (const element of state.activeDropZones.keys()) {
                    this.unregisterDropZone(element);
                }

                // Remove overlay
                if (state.dragOverlay) {
                    state.dragOverlay.remove();
                    state.dragOverlay = null;
                }

                // Reset state
                state.dragEnterCounter = 0;
                state.isDragging = false;
                state.currentTarget = null;
                state.defaultMeta = null;
            }
        };
    }

    return {
        getInstance() {
            if (!instance) instance = create();
            return instance;
        }
    };
})();

// Make available globally first
window.GlobalDragDrop = GlobalDragDropManager.getInstance();

// Create Alpine.js store for reactive drag drop state
document.addEventListener('alpine:init', () => {
    Alpine.store('dragDrop', {
        isActive: false,
        isDragging: false,
        currentTarget: null,

        init() {
            // Listen for global drag drop events
            window.addEventListener('global-drag-drop:show', () => {
                this.isDragging = true;
            });

            window.addEventListener('global-drag-drop:hide', () => {
                this.isDragging = false;
            });
        },

        enable(defaultMeta) {
            this.isActive = true;
            window.GlobalDragDrop?.enablePageDragDrop(defaultMeta);
        },

        disable() {
            this.isActive = false;
            window.GlobalDragDrop?.disablePageDragDrop();
        },

        registerZone(element, meta) {
            window.GlobalDragDrop?.registerDropZone(element, meta);
        }
    });
});

// Ensure initialization occurs after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize the global drag drop system
});