const M=(()=>{let c;function b(){const o={isEnabled:!1,isDragging:!1,dragOverlay:null,activeDropZones:new Map,currentTarget:null,defaultMeta:null,dragEnterCounter:0,listenersSetup:!1};function k(){if(o.dragOverlay&&document.body.contains(o.dragOverlay))return o.dragOverlay;const e=document.createElement("div");e.id="global-drag-overlay",e.className="global-drag-overlay",e.innerHTML=`
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
            `;const a=`
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
            `;if(!document.getElementById("global-drag-drop-styles")){const r=document.createElement("style");r.id="global-drag-drop-styles",r.textContent=a,document.head.appendChild(r)}return document.body.appendChild(e),o.dragOverlay=e,e}function g(e=null){const a=document.getElementById("global-drag-overlay-context");if(!a)return;const r=e||o.currentTarget||o.defaultMeta;if(!r){a.textContent="Drop files to upload";return}let t="";if(r.expandable&&r.section){t=`Hover to expand ${r.modelLabel}`,a.textContent=t;return}r.modelLabel&&r.modelId?r.modelLabel==="Pitch"&&r.isClientManagement?(t="Upload deliverables",r.pitchTitle&&(t+=` for: ${r.pitchTitle}`),r.clientName&&(t+=` (Client: ${r.clientName})`)):r.workflowType==="contest"?(r.modelLabel==="Project"?t="Upload contest files":t="Upload contest entry",(r.projectTitle||r.pitchTitle)&&(t+=` for: ${r.projectTitle||r.pitchTitle}`)):r.modelLabel==="Pitch"?r.pitchStatus&&["completed","denied"].includes(r.pitchStatus)?t=`Cannot upload - pitch is ${r.pitchStatus}`:(t="Upload pitch files",r.pitchTitle&&(t+=` for: ${r.pitchTitle}`)):r.modelLabel==="Project"?(t="Upload project files",r.projectTitle&&(t+=` for: ${r.projectTitle}`),r.workflowType&&r.workflowType!=="standard"&&(t+=` (${r.workflowType.replace("_"," ")})`)):r.modelLabel==="Order"?(t="Upload order files",r.itemName&&(t+=` for: ${r.itemName}`)):r.modelLabel==="Service"?(t="Upload service files",r.itemName&&(t+=` for: ${r.itemName}`)):(t=`Upload to ${r.modelLabel}`,r.projectTitle?t+=`: ${r.projectTitle}`:r.pitchTitle?t+=`: ${r.pitchTitle}`:r.itemName&&(t+=`: ${r.itemName}`)):t=`Upload to ${r.context||"project"}`,a.textContent=t}function D(){const e=document.querySelector("[data-flux-sidebar]");if(!e)return 0;const a=e.getBoundingClientRect(),r=window.getComputedStyle(e),t=Math.max(0,a.left),l=Math.min(window.innerWidth,a.right),p=Math.max(0,l-t);return p>0&&a.width>0&&r.display!=="none"&&r.visibility!=="hidden"&&r.opacity!=="0"?Math.round(p):0}function v(){if(!o.isDragging||!o.dragOverlay)return;const e=D();o.dragOverlay.style.left=`${e}px`}function E(){if(o.listenersSetup)return;o.listenersSetup=!0,document.addEventListener("dragover",r=>{r.preventDefault(),r.stopPropagation()}),document.addEventListener("dragenter",r=>{r.preventDefault(),r.stopPropagation(),o.dragEnterCounter++;const t=document.body.hasAttribute("data-has-dashboard-drops");o.dragEnterCounter===1&&o.isEnabled&&!t&&(o.isDragging=!0,z())}),document.addEventListener("dragleave",r=>{r.preventDefault(),r.stopPropagation(),o.dragEnterCounter--,o.dragEnterCounter===0&&(o.isDragging=!1,h())}),document.addEventListener("drop",r=>{if(r.preventDefault(),r.stopPropagation(),o.dragEnterCounter=0,o.isDragging=!1,h(),!o.isEnabled)return;const t=Array.from(r.dataTransfer.files||[]);if(t.length>0){const l=o.currentTarget||o.defaultMeta;if(l&&window.GlobalUploader){const p=l.modelType&&l.modelType!==l.modelLabel?L(l):l;window.GlobalUploader.addValidatedFiles(t,p)}}}),o.dragOverlay&&(o.dragOverlay.addEventListener("dragover",r=>{r.preventDefault(),o.dragOverlay.classList.add("drag-over")}),o.dragOverlay.addEventListener("dragleave",r=>{o.dragOverlay.contains(r.relatedTarget)||o.dragOverlay.classList.remove("drag-over")}),o.dragOverlay.addEventListener("drop",r=>{o.dragOverlay.classList.remove("drag-over")})),window.addEventListener("resize",()=>{v()});const e=document.querySelector("[data-flux-sidebar]");e&&(new MutationObserver(()=>v()).observe(e,{attributes:!0,attributeFilter:["class","style","data-stashed"]}),e.addEventListener("transitionend",t=>{t.propertyName==="transform"&&v()})),new MutationObserver(r=>{for(const t of r)t.type==="attributes"&&t.attributeName==="data-show-stashed-sidebar"&&v()}).observe(document.body,{attributes:!0,attributeFilter:["data-show-stashed-sidebar"]})}function z(){o.dragOverlay&&(g(),o.dragOverlay.classList.add("show"),v(),document.body.classList.add("global-drag-active"),window.dispatchEvent(new CustomEvent("global-drag-drop:show")))}function h(){o.dragOverlay&&(o.dragOverlay.classList.remove("show","drag-over"),o.dragOverlay.style.left="",document.body.classList.remove("global-drag-active"),window.dispatchEvent(new CustomEvent("global-drag-drop:hide")))}function x(e){return e.expandable&&e.section||e.modelLabel==="Pitch"&&e.pitchStatus&&["completed","denied","cancelled"].includes(e.pitchStatus)?!1:(e.workflowType==="contest"&&e.modelLabel==="Pitch",!0)}function S(e){if(e.modelLabel==="Pitch"&&e.pitchStatus){if(e.pitchStatus==="completed")return"Cannot upload files to a completed pitch";if(e.pitchStatus==="denied")return"Cannot upload files to a denied pitch";if(e.pitchStatus==="cancelled")return"Cannot upload files to a cancelled pitch"}return"Upload not authorized for this item"}function L(e){const a={modelId:e.modelId,modelLabel:e.modelLabel,context:e.context};switch(e.modelType){case"project":case"App\\Models\\Project":a.modelType="App\\Models\\Project",a.projectTitle=e.projectTitle,a.workflowType=e.workflowType,a.projectStatus=e.projectStatus;break;case"pitch":case"App\\Models\\Pitch":a.modelType="App\\Models\\Pitch",a.pitchTitle=e.pitchTitle,a.pitchStatus=e.pitchStatus,a.workflowType=e.workflowType,a.isClientManagement=e.isClientManagement,e.clientName&&(a.clientName=e.clientName);break;case"order":case"App\\Models\\Order":a.modelType="App\\Models\\Order";break;case"service":case"App\\Models\\ServicePackage":a.modelType="App\\Models\\ServicePackage";break;default:a.modelType=e.modelType}return a}let y=new Map,m=new Set;function O(e,a){if(m.has(e))return;m.add(e),a.classList.add("drag-expand-pending"),a._originalBackground=a.style.background,a.style.background="#3b82f6";const r=setTimeout(()=>{try{const t=new CustomEvent("drag-expand-section",{detail:{section:e},bubbles:!0});a.dispatchEvent(t)}catch{}m.delete(e),a.classList.remove("drag-expand-pending"),a._originalBackground!==void 0&&(a.style.background=a._originalBackground,delete a._originalBackground)},500);y.set(e,r)}function T(e,a){y.has(e)&&(clearTimeout(y.get(e)),y.delete(e),m.delete(e),a.classList.remove("drag-expand-pending"),a._originalBackground!==void 0&&(a.style.background=a._originalBackground,delete a._originalBackground))}function C(e,a){if(!e||o.activeDropZones.has(e))return;const r=d=>{d.preventDefault(),d.stopPropagation(),e.classList.contains("dashboard-drop-zone")&&document.querySelectorAll(".dashboard-drop-zone.drag-drop-active, .dashboard-drop-zone.drag-drop-disabled").forEach(i=>{i!==e&&i.classList.remove("drag-drop-active","drag-drop-disabled")}),o.currentTarget=a,g(a),a.expandable&&a.section&&O(a.section,e),x(a)?(e.classList.add("drag-drop-active"),e.classList.remove("drag-drop-disabled")):(e.classList.add("drag-drop-disabled"),e.classList.remove("drag-drop-active"))},t=d=>{d.preventDefault(),d.stopPropagation(),!e.classList.contains("drag-drop-active")&&!e.classList.contains("drag-drop-disabled")&&(o.currentTarget=a,g(a),x(a)?(e.classList.add("drag-drop-active"),e.classList.remove("drag-drop-disabled")):(e.classList.add("drag-drop-disabled"),e.classList.remove("drag-drop-active")))},l=d=>{if(d.preventDefault(),d.stopPropagation(),a.expandable&&a.section){const i=e.getBoundingClientRect(),n=d.clientX,s=d.clientY;!(n>=i.left&&n<=i.right&&s>=i.top&&s<=i.bottom)&&!e.contains(d.relatedTarget)?T(a.section,e):setTimeout(()=>{const u=e.getBoundingClientRect();!(d.clientX>=u.left&&d.clientX<=u.right&&d.clientY>=u.top&&d.clientY<=u.bottom)&&!e.contains(d.relatedTarget)&&T(a.section,e)},50)}if(e.classList.contains("dashboard-drop-zone")){const i=typeof d.clientX=="number"?d.clientX:null,n=typeof d.clientY=="number"?d.clientY:null;let s=null;if(i!==null&&n!==null){const f=document.elementFromPoint(i,n);f&&(s=f.closest(".dashboard-drop-zone"))}(!s||s&&s!==e)&&(e.classList.remove("drag-drop-active","drag-drop-disabled"),s&&document.querySelectorAll(".dashboard-drop-zone.drag-drop-active, .dashboard-drop-zone.drag-drop-disabled").forEach(f=>{f!==s&&f.classList.remove("drag-drop-active","drag-drop-disabled")}),o.currentTarget===a&&(o.currentTarget=null,g()))}else setTimeout(()=>{const i=e.getBoundingClientRect(),n=d.clientX,s=d.clientY,w=n>=i.left&&n<=i.right&&s>=i.top&&s<=i.bottom,u=e.contains(d.relatedTarget);!w&&!u&&(e.classList.remove("drag-drop-active","drag-drop-disabled"),o.currentTarget===a&&(o.currentTarget=o.defaultMeta,g()))},50)},p=d=>{d.preventDefault(),d.stopPropagation(),e.classList.remove("drag-drop-active","drag-drop-disabled");const i=Array.from(d.dataTransfer.files||[]);if(i.length>0&&window.GlobalUploader)if(x(a)){const n=L(a);window.GlobalUploader.addValidatedFiles(i,n)}else try{window.dispatchEvent(new CustomEvent("toaster:error",{detail:{message:S(a)}}))}catch(n){console.warn("Could not show upload authorization error:",n)}};e.addEventListener("dragenter",r),e.addEventListener("dragover",t),e.addEventListener("dragleave",l),e.addEventListener("drop",p),o.activeDropZones.set(e,{meta:a,handlers:{enterHandler:r,overHandler:t,leaveHandler:l,dropHandler:p}}),e.classList.add("drag-drop-zone")}return{enablePageDragDrop(e){if(o.isEnabled=!0,o.defaultMeta=e,k(),E(),!document.getElementById("drag-drop-zone-styles")){const a=document.createElement("style");a.id="drag-drop-zone-styles",a.textContent=`
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
                    `,document.head.appendChild(a)}},disablePageDragDrop(){o.isEnabled=!1,h()},registerDropZone(e,a){typeof e=="string"&&(e=document.querySelector(e)),e&&C(e,a)},unregisterDropZone(e){typeof e=="string"&&(e=document.querySelector(e));const a=o.activeDropZones.get(e);if(a){const{handlers:r}=a;e.removeEventListener("dragenter",r.enterHandler),e.removeEventListener("dragover",r.overHandler),e.removeEventListener("dragleave",r.leaveHandler),e.removeEventListener("drop",r.dropHandler),e.classList.remove("drag-drop-zone","drag-drop-active","drag-drop-disabled"),o.activeDropZones.delete(e)}},setCurrentTarget(e){o.currentTarget=e,g(e)},clearCurrentTarget(){o.currentTarget=null,g()},getState(){return{isEnabled:o.isEnabled,isDragging:o.isDragging,activeZones:o.activeDropZones.size,currentTarget:o.currentTarget,defaultMeta:o.defaultMeta}},destroy(){o.isEnabled=!1,h();for(const e of o.activeDropZones.keys())this.unregisterDropZone(e);o.dragOverlay&&(o.dragOverlay.remove(),o.dragOverlay=null),o.dragEnterCounter=0,o.isDragging=!1,o.currentTarget=null,o.defaultMeta=null}}}return{getInstance(){return c||(c=b()),c}}})();window.GlobalDragDrop=M.getInstance();document.addEventListener("alpine:init",()=>{Alpine.store("dragDrop",{isActive:!1,isDragging:!1,currentTarget:null,init(){window.addEventListener("global-drag-drop:show",()=>{this.isDragging=!0}),window.addEventListener("global-drag-drop:hide",()=>{this.isDragging=!1})},enable(c){var b;this.isActive=!0,(b=window.GlobalDragDrop)==null||b.enablePageDragDrop(c)},disable(){var c;this.isActive=!1,(c=window.GlobalDragDrop)==null||c.disablePageDragDrop()},registerZone(c,b){var o;(o=window.GlobalDragDrop)==null||o.registerDropZone(c,b)}})});document.addEventListener("DOMContentLoaded",()=>{});
