import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Mobile-first Alpine.js configuration
Alpine.data('mobileHelpers', () => ({
    // Touch and gesture helpers
    isTouchDevice: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
    isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent),
    isAndroid: /Android/.test(navigator.userAgent),
    isPWA: window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches,

    // Viewport helpers
    get isPortrait() {
        return window.innerHeight > window.innerWidth;
    },

    get isMobile() {
        return window.innerWidth < 768;
    },

    get isTablet() {
        return window.innerWidth >= 768 && window.innerWidth < 1024;
    },

    // Safe area helpers for modern mobile devices
    get safeAreaInsets() {
        const style = getComputedStyle(document.documentElement);
        return {
            top: style.getPropertyValue('env(safe-area-inset-top)') || '0px',
            bottom: style.getPropertyValue('env(safe-area-inset-bottom)') || '0px',
            left: style.getPropertyValue('env(safe-area-inset-left)') || '0px',
            right: style.getPropertyValue('env(safe-area-inset-right)') || '0px',
        };
    },

    // Network status
    isOnline: navigator.onLine,
    connectionSpeed: null,

    init() {
        // Monitor network status
        window.addEventListener('online', () => {
            this.isOnline = true;
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
        });

        // Detect connection speed if available
        if ('connection' in navigator) {
            this.connectionSpeed = navigator.connection.effectiveType;
            navigator.connection.addEventListener('change', () => {
                this.connectionSpeed = navigator.connection.effectiveType;
            });
        }

        // iOS viewport height fix
        if (this.isIOS) {
            this.fixIOSViewportHeight();
        }

        // Prevent zoom on double-tap for better UX
        this.preventDoubleTapZoom();
    },

    // Fix iOS Safari viewport height issues
    fixIOSViewportHeight() {
        const setVH = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', () => {
            setTimeout(setVH, 100);
        });
    },

    // Prevent accidental zooming on double-tap
    preventDoubleTapZoom() {
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (event) => {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
    },

    // Haptic feedback (if supported)
    vibrate(pattern = [10]) {
        if ('vibrate' in navigator && this.isTouchDevice) {
            navigator.vibrate(pattern);
        }
    },

    // Show/hide loading states
    showLoading(message = 'Loading...') {
        // Could integrate with a loading component
        console.log('Loading:', message);
    },

    hideLoading() {
        console.log('Loading complete');
    },

    // Share API helper
    async share(data) {
        if (navigator.share) {
            try {
                await navigator.share(data);
                return true;
            } catch (error) {
                console.log('Error sharing:', error);
                return false;
            }
        }
        return false;
    },

    // Clipboard helper
    async copyToClipboard(text) {
        if (navigator.clipboard) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (error) {
                console.log('Failed to copy:', error);
                return false;
            }
        }
        return false;
    }
}));

// Global touch event optimization
document.addEventListener('DOMContentLoaded', () => {
    // Add touch classes to body for CSS targeting
    if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
        document.body.classList.add('touch-device');
    } else {
        document.body.classList.add('no-touch');
    }

    // Add device classes
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        document.body.classList.add('ios-device');
    } else if (/Android/.test(navigator.userAgent)) {
        document.body.classList.add('android-device');
    }

    // Add PWA class if running as installed app
    if (window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches) {
        document.body.classList.add('pwa-mode');
    }
});

// Performance optimization: Use passive listeners where possible
const addPassiveListener = (element, event, handler) => {
    element.addEventListener(event, handler, { passive: true });
};

// Export for global use
window.addPassiveListener = addPassiveListener;

Alpine.start();
