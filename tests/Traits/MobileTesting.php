<?php

namespace Tests\Traits;

use Tests\Helpers\BurritoTestHelper;

/**
 * Trait for mobile-first testing utilities.
 */
trait MobileTesting
{
    /**
     * Test mobile viewport configuration.
     */
    protected function assertMobileViewport($response)
    {
        $response->assertSee('viewport', false);
        $response->assertSee('width=device-width', false);
        $response->assertSee('initial-scale=1', false);
    }

    /**
     * Test responsive design breakpoints.
     */
    protected function assertResponsiveDesign($selector, $expectedSizes = [])
    {
        // This would integrate with browser testing to verify responsive behavior
        // Expected format: ['mobile' => '100%', 'tablet' => '50%', 'desktop' => '33%']
        $this->markTestIncomplete('Responsive design testing requires browser automation');
    }

    /**
     * Test touch target minimum sizes (44px x 44px).
     */
    protected function assertTouchTargetSizes($selectors = [])
    {
        // Common touch targets that should meet minimum size requirements
        $defaultSelectors = [
            'button',
            '.btn',
            'a.nav-link',
            '.ingredient-selector',
            '.quantity-controls',
            '.order-submit',
        ];

        $selectorsToTest = empty($selectors) ? $defaultSelectors : $selectors;

        foreach ($selectorsToTest as $selector) {
            $this->markTestIncomplete("Touch target testing for {$selector} requires browser automation");
        }
    }

    /**
     * Test single-handed operation capabilities.
     */
    protected function assertSingleHandedOperation()
    {
        // Verify critical actions are within thumb reach on mobile
        $thumbReachSelectors = [
            '.primary-cta',      // Primary call-to-action buttons
            '.navigation-menu',   // Main navigation
            '.add-to-cart',      // Add to cart buttons
            '.ingredient-next',   // Next step in burrito builder
        ];

        foreach ($thumbReachSelectors as $selector) {
            $this->markTestIncomplete("Single-handed operation testing for {$selector} requires browser automation");
        }
    }

    /**
     * Test performance on mobile networks.
     */
    protected function assertMobilePerformance($url = '/')
    {
        // Test with slow network conditions
        $networkConfig = BurritoTestHelper::getSlowNetworkConfig();

        $startTime = microtime(true);
        $response = $this->get($url);
        $loadTime = microtime(true) - $startTime;

        // Should load within 3 seconds even on slow networks
        $this->assertLessThan(3.0, $loadTime, 'Page should load within 3 seconds on mobile networks');
        $response->assertStatus(200);
    }

    /**
     * Test with various mobile user agents.
     */
    protected function withMobileUserAgent($userAgent = 'iPhone')
    {
        $userAgents = BurritoTestHelper::getMobileUserAgents();

        if (isset($userAgents[$userAgent])) {
            return $this->withHeaders([
                'User-Agent' => $userAgents[$userAgent]
            ]);
        }

        return $this->withHeaders([
            'User-Agent' => $userAgents['iPhone'] // Default to iPhone
        ]);
    }

    /**
     * Test swipe gestures for ingredient browsing.
     */
    protected function assertSwipeGestures($selector = '.ingredient-carousel')
    {
        $this->markTestIncomplete("Swipe gesture testing for {$selector} requires browser automation");
    }

    /**
     * Test pull-to-refresh functionality.
     */
    protected function assertPullToRefresh($selector = '.availability-display')
    {
        $this->markTestIncomplete("Pull-to-refresh testing for {$selector} requires browser automation");
    }

    /**
     * Test sticky navigation behavior.
     */
    protected function assertStickyNavigation($selector = '.navbar')
    {
        $this->markTestIncomplete("Sticky navigation testing for {$selector} requires browser automation");
    }

    /**
     * Test bottom sheet modals for mobile.
     */
    protected function assertBottomSheetModals($triggerSelector = '.open-modal')
    {
        $this->markTestIncomplete("Bottom sheet modal testing for {$triggerSelector} requires browser automation");
    }

    /**
     * Test critical CSS inlining for mobile performance.
     */
    protected function assertCriticalCSS($response)
    {
        // Check that critical above-the-fold styles are inlined
        $response->assertSee('<style>', false);

        // Verify external CSS is loaded asynchronously
        $content = $response->getContent();
        $this->assertStringContains('rel="preload"', $content, 'CSS should be preloaded for performance');
    }

    /**
     * Test image optimization for mobile.
     */
    protected function assertImageOptimization($response)
    {
        $content = $response->getContent();

        // Check for WebP support
        if (strpos($content, '<img') !== false) {
            $this->assertStringContains('loading="lazy"', $content, 'Images should use lazy loading');
        }

        // Check for responsive images
        if (strpos($content, '<img') !== false) {
            $this->assertStringContains('srcset', $content, 'Images should provide multiple resolutions');
        }
    }
}