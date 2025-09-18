const S=(()=>{let l;function p(){const o={isEnabled:!1,isDragging:!1,dragOverlay:null,activeDropZones:new Map,currentTarget:null,defaultMeta:null,dragEnterCounter:0};function L(){if(o.dragOverlay)return o.dragOverlay;const e=document.createElement("div");e.id="global-drag-overlay",e.className="global-drag-overlay",e.innerHTML=`
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
            `;const r=`
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
            `;if(!document.getElementById("global-drag-drop-styles")){const a=document.createElement("style");a.id="global-drag-drop-styles",a.textContent=r,document.head.appendChild(a)}return document.body.appendChild(e),o.dragOverlay=e,e}function c(e=null){const r=document.getElementById("global-drag-overlay-context");if(!r)return;const a=e||o.currentTarget||o.defaultMeta;if(!a){r.textContent="Drop files to upload";return}let t="";if(a.expandable&&a.section){t=`Hover to expand ${a.modelLabel}`,r.textContent=t;return}a.modelLabel&&a.modelId?a.modelLabel==="Pitch"&&a.isClientManagement?(t="Upload deliverables",a.pitchTitle&&(t+=` for: ${a.pitchTitle}`),a.clientName&&(t+=` (Client: ${a.clientName})`)):a.workflowType==="contest"?(a.modelLabel==="Project"?t="Upload contest files":t="Upload contest entry",(a.projectTitle||a.pitchTitle)&&(t+=` for: ${a.projectTitle||a.pitchTitle}`)):a.modelLabel==="Pitch"?a.pitchStatus&&["completed","denied"].includes(a.pitchStatus)?t=`Cannot upload - pitch is ${a.pitchStatus}`:(t="Upload pitch files",a.pitchTitle&&(t+=` for: ${a.pitchTitle}`)):a.modelLabel==="Project"?(t="Upload project files",a.projectTitle&&(t+=` for: ${a.projectTitle}`),a.workflowType&&a.workflowType!=="standard"&&(t+=` (${a.workflowType.replace("_"," ")})`)):a.modelLabel==="Order"?(t="Upload order files",a.itemName&&(t+=` for: ${a.itemName}`)):a.modelLabel==="Service"?(t="Upload service files",a.itemName&&(t+=` for: ${a.itemName}`)):(t=`Upload to ${a.modelLabel}`,a.projectTitle?t+=`: ${a.projectTitle}`:a.pitchTitle?t+=`: ${a.pitchTitle}`:a.itemName&&(t+=`: ${a.itemName}`)):t=`Upload to ${a.context||"project"}`,r.textContent=t}function k(){document.addEventListener("dragover",e=>{e.preventDefault(),e.stopPropagation()}),document.addEventListener("dragenter",e=>{e.preventDefault(),e.stopPropagation(),o.dragEnterCounter++;const r=document.body.hasAttribute("data-has-dashboard-drops");o.dragEnterCounter===1&&o.isEnabled&&!r&&(o.isDragging=!0,D())}),document.addEventListener("dragleave",e=>{e.preventDefault(),e.stopPropagation(),o.dragEnterCounter--,o.dragEnterCounter===0&&(o.isDragging=!1,b())}),document.addEventListener("drop",e=>{if(e.preventDefault(),e.stopPropagation(),o.dragEnterCounter=0,o.isDragging=!1,b(),!o.isEnabled)return;const r=Array.from(e.dataTransfer.files||[]);if(r.length>0){const a=o.currentTarget||o.defaultMeta;if(a&&window.GlobalUploader){const t=a.modelType&&a.modelType!==a.modelLabel?x(a):a;window.GlobalUploader.addValidatedFiles(r,t)}}}),o.dragOverlay&&(o.dragOverlay.addEventListener("dragover",e=>{e.preventDefault(),o.dragOverlay.classList.add("drag-over")}),o.dragOverlay.addEventListener("dragleave",e=>{o.dragOverlay.contains(e.relatedTarget)||o.dragOverlay.classList.remove("drag-over")}),o.dragOverlay.addEventListener("drop",e=>{o.dragOverlay.classList.remove("drag-over")}))}function D(){o.dragOverlay&&(c(),o.dragOverlay.classList.add("show"),document.body.classList.add("global-drag-active"),window.dispatchEvent(new CustomEvent("global-drag-drop:show")))}function b(){o.dragOverlay&&(o.dragOverlay.classList.remove("show","drag-over"),document.body.classList.remove("global-drag-active"),window.dispatchEvent(new CustomEvent("global-drag-drop:hide")))}function h(e){return e.expandable&&e.section||e.modelLabel==="Pitch"&&e.pitchStatus&&["completed","denied","cancelled"].includes(e.pitchStatus)?!1:(e.workflowType==="contest"&&e.modelLabel==="Pitch",!0)}function E(e){if(e.modelLabel==="Pitch"&&e.pitchStatus){if(e.pitchStatus==="completed")return"Cannot upload files to a completed pitch";if(e.pitchStatus==="denied")return"Cannot upload files to a denied pitch";if(e.pitchStatus==="cancelled")return"Cannot upload files to a cancelled pitch"}return"Upload not authorized for this item"}function x(e){const r={modelId:e.modelId,modelLabel:e.modelLabel,context:e.context};switch(e.modelType){case"project":case"App\\Models\\Project":r.modelType="App\\Models\\Project",r.projectTitle=e.projectTitle,r.workflowType=e.workflowType,r.projectStatus=e.projectStatus;break;case"pitch":case"App\\Models\\Pitch":r.modelType="App\\Models\\Pitch",r.pitchTitle=e.pitchTitle,r.pitchStatus=e.pitchStatus,r.workflowType=e.workflowType,r.isClientManagement=e.isClientManagement,e.clientName&&(r.clientName=e.clientName);break;case"order":case"App\\Models\\Order":r.modelType="App\\Models\\Order";break;case"service":case"App\\Models\\ServicePackage":r.modelType="App\\Models\\ServicePackage";break;default:r.modelType=e.modelType}return r}let f=new Map,v=new Set;function z(e,r){if(v.has(e))return;v.add(e),r.classList.add("drag-expand-pending"),r._originalBackground=r.style.background,r.style.background="#3b82f6";const a=setTimeout(()=>{try{const t=new CustomEvent("drag-expand-section",{detail:{section:e},bubbles:!0});r.dispatchEvent(t)}catch{}v.delete(e),r.classList.remove("drag-expand-pending"),r._originalBackground!==void 0&&(r.style.background=r._originalBackground,delete r._originalBackground)},500);f.set(e,a)}function m(e,r){f.has(e)&&(clearTimeout(f.get(e)),f.delete(e),v.delete(e),r.classList.remove("drag-expand-pending"),r._originalBackground!==void 0&&(r.style.background=r._originalBackground,delete r._originalBackground))}function C(e,r){if(!e||o.activeDropZones.has(e))return;const a=d=>{d.preventDefault(),d.stopPropagation(),e.classList.contains("dashboard-drop-zone")&&document.querySelectorAll(".dashboard-drop-zone.drag-drop-active, .dashboard-drop-zone.drag-drop-disabled").forEach(n=>{n!==e&&n.classList.remove("drag-drop-active","drag-drop-disabled")}),o.currentTarget=r,c(r),r.expandable&&r.section&&z(r.section,e),h(r)?(e.classList.add("drag-drop-active"),e.classList.remove("drag-drop-disabled")):(e.classList.add("drag-drop-disabled"),e.classList.remove("drag-drop-active"))},t=d=>{d.preventDefault(),d.stopPropagation(),!e.classList.contains("drag-drop-active")&&!e.classList.contains("drag-drop-disabled")&&(o.currentTarget=r,c(r),h(r)?(e.classList.add("drag-drop-active"),e.classList.remove("drag-drop-disabled")):(e.classList.add("drag-drop-disabled"),e.classList.remove("drag-drop-active")))},w=d=>{if(d.preventDefault(),d.stopPropagation(),r.expandable&&r.section){const n=e.getBoundingClientRect(),i=d.clientX,s=d.clientY;!(i>=n.left&&i<=n.right&&s>=n.top&&s<=n.bottom)&&!e.contains(d.relatedTarget)?m(r.section,e):setTimeout(()=>{const g=e.getBoundingClientRect();!(d.clientX>=g.left&&d.clientX<=g.right&&d.clientY>=g.top&&d.clientY<=g.bottom)&&!e.contains(d.relatedTarget)&&m(r.section,e)},50)}if(e.classList.contains("dashboard-drop-zone")){const n=typeof d.clientX=="number"?d.clientX:null,i=typeof d.clientY=="number"?d.clientY:null;let s=null;if(n!==null&&i!==null){const u=document.elementFromPoint(n,i);u&&(s=u.closest(".dashboard-drop-zone"))}(!s||s&&s!==e)&&(e.classList.remove("drag-drop-active","drag-drop-disabled"),s&&document.querySelectorAll(".dashboard-drop-zone.drag-drop-active, .dashboard-drop-zone.drag-drop-disabled").forEach(u=>{u!==s&&u.classList.remove("drag-drop-active","drag-drop-disabled")}),o.currentTarget===r&&(o.currentTarget=null,c()))}else setTimeout(()=>{const n=e.getBoundingClientRect(),i=d.clientX,s=d.clientY,y=i>=n.left&&i<=n.right&&s>=n.top&&s<=n.bottom,g=e.contains(d.relatedTarget);!y&&!g&&(e.classList.remove("drag-drop-active","drag-drop-disabled"),o.currentTarget===r&&(o.currentTarget=o.defaultMeta,c()))},50)},T=d=>{d.preventDefault(),d.stopPropagation(),e.classList.remove("drag-drop-active","drag-drop-disabled");const n=Array.from(d.dataTransfer.files||[]);if(n.length>0&&window.GlobalUploader)if(h(r)){const i=x(r);window.GlobalUploader.addValidatedFiles(n,i)}else try{window.dispatchEvent(new CustomEvent("toaster:error",{detail:{message:E(r)}}))}catch(i){console.warn("Could not show upload authorization error:",i)}};e.addEventListener("dragenter",a),e.addEventListener("dragover",t),e.addEventListener("dragleave",w),e.addEventListener("drop",T),o.activeDropZones.set(e,{meta:r,handlers:{enterHandler:a,overHandler:t,leaveHandler:w,dropHandler:T}}),e.classList.add("drag-drop-zone")}return{enablePageDragDrop(e){if(o.isEnabled=!0,o.defaultMeta=e,L(),k(),!document.getElementById("drag-drop-zone-styles")){const r=document.createElement("style");r.id="drag-drop-zone-styles",r.textContent=`
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
                    `,document.head.appendChild(r)}},disablePageDragDrop(){o.isEnabled=!1,b()},registerDropZone(e,r){typeof e=="string"&&(e=document.querySelector(e)),e&&C(e,r)},unregisterDropZone(e){typeof e=="string"&&(e=document.querySelector(e));const r=o.activeDropZones.get(e);if(r){const{handlers:a}=r;e.removeEventListener("dragenter",a.enterHandler),e.removeEventListener("dragover",a.overHandler),e.removeEventListener("dragleave",a.leaveHandler),e.removeEventListener("drop",a.dropHandler),e.classList.remove("drag-drop-zone","drag-drop-active","drag-drop-disabled"),o.activeDropZones.delete(e)}},setCurrentTarget(e){o.currentTarget=e,c(e)},clearCurrentTarget(){o.currentTarget=null,c()},getState(){return{isEnabled:o.isEnabled,isDragging:o.isDragging,activeZones:o.activeDropZones.size,currentTarget:o.currentTarget,defaultMeta:o.defaultMeta}},destroy(){o.isEnabled=!1,b();for(const e of o.activeDropZones.keys())this.unregisterDropZone(e);o.dragOverlay&&(o.dragOverlay.remove(),o.dragOverlay=null),o.dragEnterCounter=0,o.isDragging=!1,o.currentTarget=null,o.defaultMeta=null}}}return{getInstance(){return l||(l=p()),l}}})();window.GlobalDragDrop=S.getInstance();document.addEventListener("alpine:init",()=>{Alpine.store("dragDrop",{isActive:!1,isDragging:!1,currentTarget:null,init(){window.addEventListener("global-drag-drop:show",()=>{this.isDragging=!0}),window.addEventListener("global-drag-drop:hide",()=>{this.isDragging=!1})},enable(l){var p;this.isActive=!0,(p=window.GlobalDragDrop)==null||p.enablePageDragDrop(l)},disable(){var l;this.isActive=!1,(l=window.GlobalDragDrop)==null||l.disablePageDragDrop()},registerZone(l,p){var o;(o=window.GlobalDragDrop)==null||o.registerDropZone(l,p)}})});document.addEventListener("DOMContentLoaded",()=>{});
