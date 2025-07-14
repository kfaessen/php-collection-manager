/**
 * Collection Manager - Enhanced Application JavaScript
 * Responsive design and advanced interaction features
 */

// Global variables
let html5QrCode = null;
let isScanning = false;
let touchStartX = 0;
let touchStartY = 0;
let currentTheme = 'light';

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApplication();
});

/**
 * Initialize all application features
 */
function initializeApplication() {
    initializeTheme();
    initializeNavigation();
    initializeModals();
    initializeCards();
    initializeTooltips();
    initializeAccessibility();
    initializePerformanceOptimizations();
    initializeTouchGestures();
    initializeSearch();
    initializeBarcode();
    initializeToasts();
    initializeScrollEffects();
    initializePushNotifications();
    
    // Initialize responsive behaviors
    handleViewportChanges();
    window.addEventListener('resize', debounce(handleViewportChanges, 250));
    window.addEventListener('orientationchange', function() {
        setTimeout(handleViewportChanges, 100);
    });
}

/**
 * Theme Management (Dark/Light Mode)
 */
function initializeTheme() {
    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('theme') || 'light';
    currentTheme = savedTheme;
    
    // Apply initial theme
    applyTheme(currentTheme);
    
    // Create dark mode toggle button
    createDarkModeToggle();
    
    // Listen for system theme changes
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
}

function createDarkModeToggle() {
    const toggle = document.createElement('button');
    toggle.className = 'dark-mode-toggle';
    toggle.setAttribute('aria-label', 'Toggle dark mode');
    toggle.innerHTML = currentTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
    
    toggle.addEventListener('click', toggleTheme);
    document.body.appendChild(toggle);
}

function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    applyTheme(currentTheme);
    localStorage.setItem('theme', currentTheme);
    
    // Update toggle button icon
    const toggle = document.querySelector('.dark-mode-toggle');
    if (toggle) {
        toggle.innerHTML = currentTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
    }
    
    // Announce theme change to screen readers
    announceToScreenReader(`Switched to ${currentTheme} mode`);
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.body.className = document.body.className.replace(/\b(light-mode|dark-mode)\b/g, '');
    document.body.classList.add(theme + '-mode');
}

/**
 * Enhanced Navigation
 */
function initializeNavigation() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        // Enhanced mobile menu behavior
        navbarToggler.addEventListener('click', function() {
            const isExpanded = navbarToggler.getAttribute('aria-expanded') === 'true';
            setTimeout(() => {
                if (isExpanded) {
                    document.addEventListener('click', closeNavOnOutsideClick);
                } else {
                    document.removeEventListener('click', closeNavOnOutsideClick);
                }
            }, 100);
        });
        
        // Close nav on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navbarCollapse.classList.contains('show')) {
                navbarToggler.click();
            }
        });
    }
    
    // Add active states to navigation links
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

function closeNavOnOutsideClick(e) {
    const navbar = document.querySelector('.navbar');
    if (navbar && !navbar.contains(e.target)) {
        const navbarToggler = document.querySelector('.navbar-toggler');
        if (navbarToggler && navbarToggler.getAttribute('aria-expanded') === 'true') {
            navbarToggler.click();
        }
    }
}

/**
 * Enhanced Modal Functionality
 */
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        // Auto-focus first form element when modal opens
        modal.addEventListener('shown.bs.modal', function() {
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
        
        // Reset forms when modal closes
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => form.reset());
            
            // Reset any custom states
            const metadataPreview = modal.querySelector('#metadata-preview');
            if (metadataPreview) {
                metadataPreview.style.display = 'none';
            }
            
            // Stop any ongoing camera scanning
            if (isScanning) {
                stopScanning();
            }
        });
        
        // Trap focus within modal
        modal.addEventListener('keydown', trapFocus);
    });
}

function trapFocus(e) {
    if (e.key !== 'Tab') return;
    
    const modal = e.currentTarget;
    const focusableElements = modal.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    if (e.shiftKey && e.target === firstElement) {
        e.preventDefault();
        lastElement.focus();
    } else if (!e.shiftKey && e.target === lastElement) {
        e.preventDefault();
        firstElement.focus();
    }
}

/**
 * Enhanced Card Interactions
 */
function initializeCards() {
    const cards = document.querySelectorAll('.item-card');
    
    cards.forEach(card => {
        // Add keyboard navigation
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const viewButton = card.querySelector('[onclick*="viewItem"]');
                if (viewButton) {
                    const itemId = viewButton.getAttribute('onclick').match(/\d+/)[0];
                    viewItem(itemId);
                }
            }
        });
        
        // Add loading states
        const img = card.querySelector('.item-cover');
        if (img) {
            img.addEventListener('load', function() {
                this.classList.add('fade-in');
            });
            
            img.addEventListener('error', function() {
                this.style.display = 'none';
                const placeholder = card.querySelector('.placeholder-cover');
                if (placeholder) {
                    placeholder.style.display = 'flex';
                }
            });
        }
        
        // Add swipe gestures for mobile
        addSwipeGestures(card);
    });
}

/**
 * Touch Gestures and Mobile Interactions
 */
function initializeTouchGestures() {
    // Add pull-to-refresh functionality
    let startY = 0;
    let currentY = 0;
    let pulling = false;
    
    document.addEventListener('touchstart', function(e) {
        startY = e.touches[0].clientY;
        if (window.scrollY === 0) {
            pulling = true;
        }
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        if (!pulling) return;
        
        currentY = e.touches[0].clientY;
        const pullDistance = currentY - startY;
        
        if (pullDistance > 100 && window.scrollY === 0) {
            // Show pull to refresh indicator
            showPullToRefreshIndicator();
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function() {
        if (pulling && currentY - startY > 100) {
            // Trigger refresh
            location.reload();
        }
        pulling = false;
        hidePullToRefreshIndicator();
    });
}

function addSwipeGestures(element) {
    let startX = 0;
    let startY = 0;
    
    element.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, { passive: true });
    
    element.addEventListener('touchend', function(e) {
        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        // Check if it's a horizontal swipe
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0) {
                // Swipe left - show delete option
                showQuickActions(element, 'delete');
            } else {
                // Swipe right - show view option
                showQuickActions(element, 'view');
            }
        }
    });
}

function showQuickActions(card, action) {
    // Remove any existing quick actions
    document.querySelectorAll('.quick-action').forEach(el => el.remove());
    
    const quickAction = document.createElement('div');
    quickAction.className = 'quick-action';
    quickAction.style.cssText = `
        position: absolute;
        top: 10px;
        ${action === 'delete' ? 'right: 10px;' : 'left: 10px;'}
        background: ${action === 'delete' ? '#dc3545' : '#0d6efd'};
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        z-index: 10;
        animation: quickActionSlideIn 0.3s ease-out;
    `;
    quickAction.textContent = action === 'delete' ? 'Delete' : 'View';
    
    card.style.position = 'relative';
    card.appendChild(quickAction);
    
    // Auto-hide after 2 seconds
    setTimeout(() => {
        quickAction.remove();
    }, 2000);
    
    // Add click handler
    quickAction.addEventListener('click', function(e) {
        e.stopPropagation();
        const itemId = card.querySelector('[onclick*="Item"]').getAttribute('onclick').match(/\d+/)[0];
        if (action === 'delete') {
            deleteItem(itemId);
        } else {
            viewItem(itemId);
        }
    });
}

/**
 * Enhanced Search Functionality
 */
function initializeSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.querySelector('.search-form, form');
    
    if (searchInput) {
        // Add search suggestions
        createSearchSuggestions(searchInput);
        
        // Add clear button
        addClearButton(searchInput);
        
        // Add voice search if available
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            addVoiceSearch(searchInput);
        }
        
        // Debounced search
        searchInput.addEventListener('input', debounce(function() {
            if (this.value.length > 2) {
                performLiveSearch(this.value);
            }
        }, 300));
    }
    
    // Make search form responsive
    if (searchForm) {
        searchForm.classList.add('search-form');
        makeSearchResponsive();
    }
}

function createSearchSuggestions(input) {
    const suggestions = document.createElement('div');
    suggestions.className = 'search-suggestions';
    suggestions.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    `;
    
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(suggestions);
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.parentElement.contains(e.target)) {
            suggestions.style.display = 'none';
        }
    });
}

function addClearButton(input) {
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'btn btn-link position-absolute';
    clearButton.style.cssText = `
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        padding: 4px;
        z-index: 10;
        display: none;
    `;
    clearButton.innerHTML = '<i class="bi bi-x-circle"></i>';
    
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(clearButton);
    
    clearButton.addEventListener('click', function() {
        input.value = '';
        input.focus();
        this.style.display = 'none';
    });
    
    input.addEventListener('input', function() {
        clearButton.style.display = this.value ? 'block' : 'none';
    });
}

function addVoiceSearch(input) {
    const voiceButton = document.createElement('button');
    voiceButton.type = 'button';
    voiceButton.className = 'btn btn-outline-secondary';
    voiceButton.innerHTML = '<i class="bi bi-mic"></i>';
    voiceButton.setAttribute('aria-label', 'Voice search');
    
    // Insert after the input
    input.parentElement.insertBefore(voiceButton, input.nextSibling);
    
    voiceButton.addEventListener('click', function() {
        startVoiceRecognition(input);
    });
}

function startVoiceRecognition(input) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    
    recognition.lang = document.documentElement.lang || 'nl-NL';
    recognition.continuous = false;
    recognition.interimResults = false;
    
    recognition.onstart = function() {
        input.placeholder = 'Luisteren...';
        input.classList.add('listening');
    };
    
    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        input.value = transcript;
        input.dispatchEvent(new Event('input'));
    };
    
    recognition.onerror = function() {
        showToast('Spraakherkenning mislukt', 'error');
    };
    
    recognition.onend = function() {
        input.placeholder = 'Zoeken in collectie...';
        input.classList.remove('listening');
    };
    
    recognition.start();
}

function makeSearchResponsive() {
    const searchForm = document.querySelector('.search-form');
    if (!searchForm) return;
    
    function updateSearchLayout() {
        const isMobile = window.innerWidth < 768;
        const formElements = searchForm.querySelectorAll('.form-control, .form-select, .btn');
        
        formElements.forEach(element => {
            if (isMobile) {
                element.classList.add('mb-2');
                element.style.width = '100%';
            } else {
                element.classList.remove('mb-2');
                element.style.width = '';
            }
        });
    }
    
    updateSearchLayout();
    window.addEventListener('resize', debounce(updateSearchLayout, 250));
}

/**
 * Enhanced Barcode Scanning
 */
function initializeBarcode() {
    const startScanButton = document.getElementById('start-scan');
    const stopScanButton = document.getElementById('stop-scan');
    const manualBarcodeInput = document.getElementById('manual-barcode');
    
    if (startScanButton) {
        startScanButton.addEventListener('click', startScanning);
    }
    
    if (stopScanButton) {
        stopScanButton.addEventListener('click', stopScanning);
    }
    
    if (manualBarcodeInput) {
        manualBarcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupBarcode();
            }
        });
    }
}

async function startScanning() {
    const qrReaderElement = document.getElementById('qr-reader');
    if (!qrReaderElement) return;
    
    try {
        // Check for camera permissions
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        stream.getTracks().forEach(track => track.stop());
        
        html5QrCode = new Html5Qrcode("qr-reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            }
        };
        
        await html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        );
        
        isScanning = true;
        document.getElementById('start-scan').style.display = 'none';
        document.getElementById('stop-scan').style.display = 'inline-block';
        
        showToast('Camera gestart', 'success');
        
    } catch (err) {
        console.error('Camera access denied:', err);
        showToast('Camera toegang geweigerd. Gebruik handmatige barcode invoer.', 'error');
        
        // Focus on manual input as fallback
        const manualInput = document.getElementById('manual-barcode');
        if (manualInput) manualInput.focus();
    }
}

async function stopScanning() {
    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.stop();
            html5QrCode = null;
            isScanning = false;
            
            document.getElementById('start-scan').style.display = 'inline-block';
            document.getElementById('stop-scan').style.display = 'none';
            
            showToast('Camera gestopt', 'info');
        } catch (err) {
            console.error('Error stopping camera:', err);
        }
    }
}

function onScanSuccess(decodedText) {
    console.log('Barcode gescand:', decodedText);
    
    // Stop scanning immediately after successful scan
    stopScanning();
    
    // Fill manual input with scanned code
    const manualInput = document.getElementById('manual-barcode');
    if (manualInput) {
        manualInput.value = decodedText;
    }
    
    // Automatically lookup the barcode
    lookupBarcode(decodedText);
    
    // Provide haptic feedback if available
    if ('vibrate' in navigator) {
        navigator.vibrate(200);
    }
    
    showToast(`Barcode gescand: ${decodedText}`, 'success');
}

function onScanError(errorMessage) {
    // Handle scan errors silently - don't spam console
    if (!errorMessage.includes('NotFoundException')) {
        console.log('Scan error:', errorMessage);
    }
}

/**
 * Enhanced Toast Notifications
 */
function initializeToasts() {
    createToastContainer();
}

function createToastContainer() {
    if (document.getElementById('toast-container')) return;
    
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
}

function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    const iconMap = {
        success: 'bi-check-circle',
        error: 'bi-exclamation-circle',
        warning: 'bi-exclamation-triangle',
        info: 'bi-info-circle'
    };
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ${iconMap[type] || 'bi-info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration
    });
    
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
    
    // Auto-hide for screen readers
    setTimeout(() => {
        announceToScreenReader(message);
    }, 100);
}

/**
 * Accessibility Enhancements
 */
function initializeAccessibility() {
    // Add skip link
    addSkipLink();
    
    // Enhance focus management
    enhanceFocusManagement();
    
    // Add ARIA labels where missing
    addMissingAriaLabels();
    
    // Initialize screen reader announcements
    createScreenReaderAnnouncer();
    
    // Add keyboard shortcuts
    addKeyboardShortcuts();
}

function addSkipLink() {
    if (document.querySelector('.skip-link')) return;
    
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Skip to main content';
    
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Add main content ID if it doesn't exist
    const mainContent = document.querySelector('main, .container').closest('.container');
    if (mainContent && !mainContent.id) {
        mainContent.id = 'main-content';
    }
}

function enhanceFocusManagement() {
    // Make cards focusable
    document.querySelectorAll('.item-card').forEach(card => {
        if (!card.hasAttribute('tabindex')) {
            card.setAttribute('tabindex', '0');
        }
    });
    
    // Enhance focus visibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
}

function addMissingAriaLabels() {
    // Add labels to buttons without text
    document.querySelectorAll('button:not([aria-label])').forEach(button => {
        const icon = button.querySelector('i[class*="bi-"]');
        if (icon && !button.textContent.trim()) {
            const iconClass = icon.className.match(/bi-([^\\s]+)/);
            if (iconClass) {
                const label = getAriaLabelForIcon(iconClass[1]);
                if (label) {
                    button.setAttribute('aria-label', label);
                }
            }
        }
    });
    
    // Add labels to form controls without labels
    document.querySelectorAll('input:not([aria-label]):not([aria-labelledby])').forEach(input => {
        const placeholder = input.getAttribute('placeholder');
        if (placeholder && !input.previousElementSibling?.tagName === 'LABEL') {
            input.setAttribute('aria-label', placeholder);
        }
    });
}

function getAriaLabelForIcon(iconName) {
    const iconLabels = {
        'plus-lg': 'Add item',
        'eye': 'View item',
        'trash': 'Delete item',
        'search': 'Search',
        'camera': 'Start camera',
        'stop': 'Stop camera',
        'moon': 'Enable dark mode',
        'sun': 'Enable light mode',
        'x-circle': 'Clear input'
    };
    return iconLabels[iconName] || null;
}

function createScreenReaderAnnouncer() {
    if (document.getElementById('screen-reader-announcer')) return;
    
    const announcer = document.createElement('div');
    announcer.id = 'screen-reader-announcer';
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    announcer.className = 'sr-only';
    document.body.appendChild(announcer);
}

function announceToScreenReader(message) {
    const announcer = document.getElementById('screen-reader-announcer');
    if (announcer) {
        announcer.textContent = message;
        setTimeout(() => {
            announcer.textContent = '';
        }, 1000);
    }
}

function addKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Only handle shortcuts when not in form inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        // Alt + shortcuts
        if (e.altKey) {
            switch (e.key) {
                case 'a':
                    e.preventDefault();
                    const addButton = document.querySelector('[data-bs-target="#addItemModal"]');
                    if (addButton) addButton.click();
                    break;
                case 's':
                    e.preventDefault();
                    const searchInput = document.querySelector('input[name="search"]');
                    if (searchInput) searchInput.focus();
                    break;
                case 'd':
                    e.preventDefault();
                    toggleTheme();
                    break;
            }
        }
    });
}

/**
 * Performance Optimizations
 */
function initializePerformanceOptimizations() {
    // Lazy load images
    lazyLoadImages();
    
    // Preload critical resources
    preloadCriticalResources();
    
    // Optimize scroll performance
    optimizeScrollPerformance();
    
    // Add performance monitoring
    monitorPerformance();
}

function lazyLoadImages() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('fade-in');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

function preloadCriticalResources() {
    // Preload commonly used icons
    const criticalIcons = ['bi-plus-lg', 'bi-search', 'bi-camera', 'bi-eye', 'bi-trash'];
    criticalIcons.forEach(icon => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'font';
        link.crossOrigin = 'anonymous';
        link.href = `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/fonts/bootstrap-icons.woff2`;
        document.head.appendChild(link);
    });
}

function optimizeScrollPerformance() {
    let ticking = false;
    
    function updateScrollState() {
        const scrollTop = window.pageYOffset;
        
        // Add/remove scroll class
        if (scrollTop > 100) {
            document.body.classList.add('scrolled');
        } else {
            document.body.classList.remove('scrolled');
        }
        
        ticking = false;
    }
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateScrollState);
            ticking = true;
        }
    }, { passive: true });
}

function monitorPerformance() {
    // Monitor paint metrics
    if ('PerformanceObserver' in window) {
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.name === 'first-contentful-paint') {
                    console.log('FCP:', entry.startTime);
                }
                if (entry.name === 'largest-contentful-paint') {
                    console.log('LCP:', entry.startTime);
                }
            }
        });
        
        observer.observe({ entryTypes: ['paint', 'largest-contentful-paint'] });
    }
}

/**
 * Scroll Effects and Animations
 */
function initializeScrollEffects() {
    if ('IntersectionObserver' in window) {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        // Observe cards for animation
        document.querySelectorAll('.item-card').forEach(card => {
            animationObserver.observe(card);
        });
    }
}

/**
 * Viewport and Orientation Handling
 */
function handleViewportChanges() {
    updateViewportHeight();
    updateCardLayout();
    updateNavigationLayout();
    handleOrientationChange();
}

function updateViewportHeight() {
    // Fix for mobile viewport height issues
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

function updateCardLayout() {
    const containers = document.querySelectorAll('.row');
    const isSmallScreen = window.innerWidth < 576;
    const isMediumScreen = window.innerWidth < 992;
    
    containers.forEach(container => {
        const cards = container.querySelectorAll('.col-lg-3, .col-md-4, .col-sm-6');
        cards.forEach(card => {
            if (isSmallScreen) {
                card.className = 'col-12 mb-3';
            } else if (isMediumScreen) {
                card.className = 'col-sm-6 col-md-4 mb-4';
            } else {
                card.className = 'col-lg-3 col-md-4 col-sm-6 mb-4';
            }
        });
    });
}

function updateNavigationLayout() {
    const navbar = document.querySelector('.navbar');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbar && navbarCollapse) {
        const isMobile = window.innerWidth < 992;
        
        if (isMobile) {
            navbar.classList.add('mobile-nav');
        } else {
            navbar.classList.remove('mobile-nav');
            // Close mobile menu if open
            if (navbarCollapse.classList.contains('show')) {
                const toggler = navbar.querySelector('.navbar-toggler');
                if (toggler) toggler.click();
            }
        }
    }
}

function handleOrientationChange() {
    // Handle specific orientation changes
    if (screen.orientation) {
        const orientation = screen.orientation.angle;
        document.body.setAttribute('data-orientation', orientation);
        
        // Adjust scanning area for landscape
        if (html5QrCode && isScanning) {
            setTimeout(() => {
                stopScanning();
                setTimeout(startScanning, 100);
            }, 500);
        }
    }
}

/**
 * Pull to Refresh Functionality
 */
function showPullToRefreshIndicator() {
    let indicator = document.getElementById('pull-refresh-indicator');
    
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'pull-refresh-indicator';
        indicator.className = 'text-center py-3';
        indicator.innerHTML = `
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <div class="mt-2 small text-muted">Release to refresh</div>
        `;
        indicator.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        `;
        document.body.appendChild(indicator);
    }
    
    indicator.style.transform = 'translateY(0)';
}

function hidePullToRefreshIndicator() {
    const indicator = document.getElementById('pull-refresh-indicator');
    if (indicator) {
        indicator.style.transform = 'translateY(-100%)';
        setTimeout(() => {
            indicator.remove();
        }, 300);
    }
}

/**
 * Existing Functions (Enhanced)
 */

// Enhanced debounce function
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(this, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(this, args);
    };
}

// Enhanced barcode lookup with loading states
async function lookupBarcode(barcode = null) {
    const barcodeValue = barcode || document.getElementById('manual-barcode')?.value;
    if (!barcodeValue) {
        showToast('Voer een barcode in', 'warning');
        return;
    }
    
    showLoadingSpinner('Barcode opzoeken...');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=lookup_barcode&barcode=${encodeURIComponent(barcodeValue)}`
        });
        
        const data = await response.json();
        
        if (data.success && data.data) {
            showMetadataPreview(data.data);
            showToast('Metadata gevonden!', 'success');
        } else {
            showToast(data.message || 'Geen metadata gevonden', 'warning');
        }
    } catch (error) {
        console.error('Error looking up barcode:', error);
        showToast('Fout bij opzoeken barcode', 'error');
    } finally {
        hideLoadingSpinner();
    }
}

// Enhanced metadata preview
function showMetadataPreview(metadata) {
    const preview = document.getElementById('metadata-preview');
    if (!preview) return;
    
    const coverHtml = metadata.cover_url 
        ? `<img src="${metadata.cover_url}" alt="Cover" class="img-fluid rounded" style="max-height: 200px;" loading="lazy">`
        : '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;"><i class="bi bi-image fs-1 text-muted"></i></div>';
    
    preview.innerHTML = `
        <div class="card fade-in">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        ${coverHtml}
                    </div>
                    <div class="col-md-8">
                        <h5 class="card-title">${metadata.title || 'Onbekende titel'}</h5>
                        <p class="card-text"><small class="text-muted">Type: ${metadata.type || 'Onbekend'}</small></p>
                        ${metadata.platform ? `<p class="card-text"><small class="text-muted">Platform: ${metadata.platform}</small></p>` : ''}
                        ${metadata.description ? `<p class="card-text">${metadata.description.substring(0, 200)}${metadata.description.length > 200 ? '...' : ''}</p>` : ''}
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" onclick="saveItem(true)">
                                <i class="bi bi-plus-lg"></i> Item opslaan
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="clearPreview()">
                                <i class="bi bi-x"></i> Annuleren
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    preview.style.display = 'block';
    
    // Store metadata for saving
    window.currentMetadata = metadata;
    
    // Switch to manual tab to show the preview
    const manualTab = document.getElementById('manual-tab');
    if (manualTab) {
        manualTab.click();
    }
}

function clearPreview() {
    const preview = document.getElementById('metadata-preview');
    if (preview) {
        preview.style.display = 'none';
        preview.innerHTML = '';
    }
    window.currentMetadata = null;
}

// Enhanced loading spinner
function showLoadingSpinner(message = 'Laden...') {
    hideLoadingSpinner(); // Remove any existing spinner
    
    const spinner = document.createElement('div');
    spinner.id = 'loading-spinner';
    spinner.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
    spinner.style.cssText = `
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        backdrop-filter: blur(5px);
    `;
    
    spinner.innerHTML = `
        <div class="text-center text-white">
            <div class="spinner-border mb-3" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <div>${message}</div>
        </div>
    `;
    
    document.body.appendChild(spinner);
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Enhanced save item function
async function saveItem(useMetadata = false) {
    const formData = new FormData();
    formData.append('action', 'add_item');
    
    if (useMetadata && window.currentMetadata) {
        // Use scanned/looked up metadata
        Object.keys(window.currentMetadata).forEach(key => {
            if (window.currentMetadata[key]) {
                formData.append(key, window.currentMetadata[key]);
            }
        });
    } else {
        // Use manual form data
        const form = document.getElementById('manual-form');
        if (form) {
            const formDataEntries = new FormData(form);
            for (let [key, value] of formDataEntries.entries()) {
                formData.append(key, value);
            }
        }
    }
    
    showLoadingSpinner('Item opslaan...');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Item succesvol toegevoegd!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addItemModal'));
            if (modal) modal.hide();
            
            // Refresh page after short delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Fout bij opslaan item', 'error');
        }
    } catch (error) {
        console.error('Error saving item:', error);
        showToast('Fout bij opslaan item', 'error');
    } finally {
        hideLoadingSpinner();
    }
}

// Enhanced delete item function
async function deleteItem(itemId) {
    if (!confirm('Weet je zeker dat je dit item wilt verwijderen?')) {
        return;
    }
    
    showLoadingSpinner('Item verwijderen...');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_item&id=${itemId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Item verwijderd', 'success');
            
            // Remove item card with animation
            const itemCard = document.querySelector(`[onclick*="${itemId}"]`)?.closest('.col-lg-3, .col-md-4, .col-sm-6, .col-12');
            if (itemCard) {
                itemCard.style.transition = 'all 0.3s ease';
                itemCard.style.transform = 'scale(0)';
                itemCard.style.opacity = '0';
                setTimeout(() => {
                    itemCard.remove();
                }, 300);
            }
        } else {
            showToast(data.message || 'Fout bij verwijderen item', 'error');
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        showToast('Fout bij verwijderen item', 'error');
    } finally {
        hideLoadingSpinner();
    }
}

// Enhanced view item function
function viewItem(itemId) {
    // This would typically open a modal or navigate to a detail page
    showToast(`Item ${itemId} bekijken (functionaliteit nog toe te voegen)`, 'info');
}

// Enhanced tooltip initialization
function initializeTooltips() {
    // Initialize Bootstrap tooltips for elements with title attributes
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover focus'
        });
    });
}

// Live search functionality
function performLiveSearch(query) {
    // This would typically make an AJAX request for live search results
    console.log('Live search for:', query);
}

/**
 * Push Notifications Management
 */

// Global variables for push notifications
let pushSubscription = null;
let vapidPublicKey = null;
let notificationPermission = 'default';

/**
 * Initialize push notifications
 */
function initializePushNotifications() {
    // Check for service worker and push manager support
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Push notifications not supported');
        return;
    }
    
    // Get initial permission state
    notificationPermission = Notification.permission;
    
    // Initialize push notification UI
    createNotificationButton();
    
    // Check for existing subscription
    checkPushSubscription();
    
    // Get VAPID public key from server
    fetchVapidPublicKey();
}

/**
 * Create notification permission button
 */
function createNotificationButton() {
    // Don't create if already exists
    if (document.getElementById('notification-toggle')) return;
    
    const button = document.createElement('button');
    button.id = 'notification-toggle';
    button.className = 'btn btn-outline-primary btn-sm me-2';
    button.innerHTML = '<i class="bi bi-bell"></i>';
    button.setAttribute('aria-label', 'Toggle notifications');
    button.style.display = 'none'; // Hidden until we check support
    
    // Add to navigation
    const navbar = document.querySelector('.navbar .d-flex');
    if (navbar) {
        navbar.insertBefore(button, navbar.firstChild);
    }
    
    button.addEventListener('click', togglePushNotifications);
    
    // Update button state
    updateNotificationButton();
}

/**
 * Update notification button appearance
 */
function updateNotificationButton() {
    const button = document.getElementById('notification-toggle');
    if (!button) return;
    
    const icon = button.querySelector('i');
    
    switch (notificationPermission) {
        case 'granted':
            button.className = 'btn btn-success btn-sm me-2';
            icon.className = 'bi bi-bell-fill';
            button.setAttribute('aria-label', 'Notifications enabled');
            button.title = 'Notifications enabled';
            break;
        case 'denied':
            button.className = 'btn btn-danger btn-sm me-2';
            icon.className = 'bi bi-bell-slash';
            button.setAttribute('aria-label', 'Notifications disabled');
            button.title = 'Notifications disabled';
            break;
        default:
            button.className = 'btn btn-outline-primary btn-sm me-2';
            icon.className = 'bi bi-bell';
            button.setAttribute('aria-label', 'Enable notifications');
            button.title = 'Enable notifications';
    }
    
    button.style.display = 'inline-block';
}

/**
 * Toggle push notifications
 */
async function togglePushNotifications() {
    try {
        if (notificationPermission === 'granted' && pushSubscription) {
            // Unsubscribe
            await unsubscribeFromPush();
        } else {
            // Subscribe
            await subscribeToush();
        }
    } catch (error) {
        console.error('Error toggling push notifications:', error);
        showToast('Fout bij wijzigen meldingen', 'error');
    }
}

/**
 * Subscribe to push notifications
 */
async function subscribeToush() {
    try {
        // Request permission
        const permission = await Notification.requestPermission();
        notificationPermission = permission;
        
        if (permission !== 'granted') {
            showToast('Meldingen zijn geweigerd', 'warning');
            updateNotificationButton();
            return false;
        }
        
        // Get service worker registration
        const registration = await navigator.serviceWorker.ready;
        
        // Check if we already have a subscription
        let subscription = await registration.pushManager.getSubscription();
        
        if (!subscription) {
            // Create new subscription
            if (!vapidPublicKey) {
                throw new Error('VAPID public key not available');
            }
            
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });
        }
        
        // Send subscription to server
        const success = await sendSubscriptionToServer(subscription);
        
        if (success) {
            pushSubscription = subscription;
            showToast('Meldingen zijn ingeschakeld', 'success');
            updateNotificationButton();
            return true;
        } else {
            throw new Error('Failed to register subscription with server');
        }
        
    } catch (error) {
        console.error('Error subscribing to push notifications:', error);
        showToast('Fout bij inschakelen meldingen', 'error');
        return false;
    }
}

/**
 * Unsubscribe from push notifications
 */
async function unsubscribeFromPush() {
    try {
        if (pushSubscription) {
            // Unsubscribe from browser
            await pushSubscription.unsubscribe();
            
            // Remove from server
            await removeSubscriptionFromServer(pushSubscription);
            
            pushSubscription = null;
            showToast('Meldingen zijn uitgeschakeld', 'info');
        }
        
        updateNotificationButton();
        return true;
        
    } catch (error) {
        console.error('Error unsubscribing from push notifications:', error);
        showToast('Fout bij uitschakelen meldingen', 'error');
        return false;
    }
}

/**
 * Check existing push subscription
 */
async function checkPushSubscription() {
    try {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (subscription) {
                pushSubscription = subscription;
                notificationPermission = Notification.permission;
            }
        }
        
        updateNotificationButton();
        
    } catch (error) {
        console.error('Error checking push subscription:', error);
    }
}

/**
 * Fetch VAPID public key from server
 */
async function fetchVapidPublicKey() {
    try {
        const response = await fetch('./notifications.php?action=get_vapid_key');
        const data = await response.json();
        
        if (data.success && data.publicKey) {
            vapidPublicKey = data.publicKey;
        }
        
    } catch (error) {
        console.error('Error fetching VAPID public key:', error);
    }
}

/**
 * Send subscription to server
 */
async function sendSubscriptionToServer(subscription) {
    try {
        const response = await fetch('./notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'subscribe',
                subscription: subscription.toJSON(),
                userAgent: navigator.userAgent
            })
        });
        
        const data = await response.json();
        return data.success;
        
    } catch (error) {
        console.error('Error sending subscription to server:', error);
        return false;
    }
}

/**
 * Remove subscription from server
 */
async function removeSubscriptionFromServer(subscription) {
    try {
        const response = await fetch('./notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unsubscribe',
                endpoint: subscription.endpoint
            })
        });
        
        const data = await response.json();
        return data.success;
        
    } catch (error) {
        console.error('Error removing subscription from server:', error);
        return false;
    }
}

/**
 * Send test notification
 */
async function sendTestNotification() {
    try {
        const response = await fetch('./notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'test_notification'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Test melding verzonden', 'success');
        } else {
            showToast('Fout bij verzenden test melding', 'error');
        }
        
    } catch (error) {
        console.error('Error sending test notification:', error);
        showToast('Fout bij verzenden test melding', 'error');
    }
}

/**
 * Utility function to convert VAPID key
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
}

/**
 * Handle notification click (from service worker)
 */
function handleNotificationClick(event) {
    console.log('Notification clicked:', event);
    
    // Close notification
    event.notification.close();
    
    // Handle different notification types
    const data = event.notification.data || {};
    
    if (data.url) {
        // Open specific URL
        event.waitUntil(
            clients.openWindow(data.url)
        );
    } else {
        // Open app
        event.waitUntil(
            clients.openWindow('./index.php')
        );
    }
}

/**
 * Request notification permission with custom UI
 */
async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        showToast('Dit apparaat ondersteunt geen meldingen', 'warning');
        return false;
    }
    
    if (Notification.permission === 'granted') {
        return true;
    }
    
    if (Notification.permission === 'denied') {
        showToast('Meldingen zijn geblokkeerd. Schakel ze in via browserinstellingen.', 'warning');
        return false;
    }
    
    // Show custom permission request
    const result = await showNotificationPermissionModal();
    
    if (result) {
        const permission = await Notification.requestPermission();
        notificationPermission = permission;
        updateNotificationButton();
        
        if (permission === 'granted') {
            showToast('Meldingen zijn ingeschakeld', 'success');
            return true;
        } else {
            showToast('Meldingen zijn geweigerd', 'warning');
            return false;
        }
    }
    
    return false;
}

/**
 * Show custom notification permission modal
 */
function showNotificationPermissionModal() {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-bell"></i> Meldingen inschakelen
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Wil je meldingen ontvangen voor:</p>
                        <ul>
                            <li>Nieuwe items in je collectie</li>
                            <li>Updates van bestaande items</li>
                            <li>Gedeelde collecties</li>
                            <li>Belangrijke app updates</li>
                        </ul>
                        <p class="text-muted small">
                            Je kunt meldingen altijd later uitschakelen in de instellingen.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Later
                        </button>
                        <button type="button" class="btn btn-primary" id="allow-notifications">
                            <i class="bi bi-bell"></i> Inschakelen
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        modal.querySelector('#allow-notifications').addEventListener('click', () => {
            bsModal.hide();
            resolve(true);
        });
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
            resolve(false);
        });
    });
}

// Add CSS animations dynamically
function addAnimationStyles() {
    if (document.getElementById('dynamic-animations')) return;
    
    const style = document.createElement('style');
    style.id = 'dynamic-animations';
    style.textContent = `
        @keyframes quickActionSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .animate-in {
            animation: slideUp 0.6s ease-out forwards;
        }
        
        .keyboard-navigation *:focus {
            outline: 2px solid var(--primary-color) !important;
            outline-offset: 2px !important;
        }
        
        .listening {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        
        .mobile-nav .navbar-nav {
            background: var(--navbar-bg);
            border-radius: 8px;
            margin-top: 0.5rem;
            padding: 0.5rem;
        }
        
        .scrolled .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 575.98px) {
            .quick-action {
                font-size: 0.7rem !important;
                padding: 6px 12px !important;
            }
        }
        
        /* Notification button animations */
        #notification-toggle {
            transition: all 0.3s ease;
        }
        
        #notification-toggle:hover {
            transform: scale(1.05);
        }
        
        #notification-toggle.btn-success {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
            }
        }
    `;
    
    document.head.appendChild(style);
}

// Initialize animations
addAnimationStyles(); 