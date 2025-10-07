<?php

declare(strict_types=1);

namespace Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

/**
 * Base class for browser tests using Laravel Dusk.
 * Tests mobile-first UI interactions and touch behaviors.
 */
abstract class BrowserTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=375,667', // iPhone SE size for mobile-first
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security',
            '--user-agent=Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', // Mobile user agent
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->reject(function ($item) {
                return $item === '--headless';
            });
        })->values()->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     */
    protected function shouldStartMaximized(): bool
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
               isset($_ENV['DUSK_START_MAXIMIZED']);
    }

    /**
     * Test mobile viewport and touch interactions.
     */
    protected function assertMobileViewport($browser): void
    {
        $browser->assertPresent('meta[name="viewport"]')
            ->assertAttribute('meta[name="viewport"]', 'content', 'width=device-width, initial-scale=1');
    }

    /**
     * Test touch target sizes (minimum 44px).
     */
    protected function assertTouchTargetSize($browser, $selector): void
    {
        $script = "
            const element = document.querySelector('$selector');
            const rect = element.getBoundingClientRect();
            return {width: rect.width, height: rect.height};
        ";

        $dimensions = $browser->driver->executeScript($script);

        $this->assertGreaterThanOrEqual(44, $dimensions['width'], 'Touch target width must be at least 44px');
        $this->assertGreaterThanOrEqual(44, $dimensions['height'], 'Touch target height must be at least 44px');
    }

    /**
     * Test weekend-only ordering availability.
     */
    protected function assertWeekendOrderingOnly($browser): void
    {
        $dayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)

        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Monday to Friday
            $browser->assertSee('Orders are only available on weekends');
        } else { // Saturday or Sunday
            $browser->assertDontSee('Orders are only available on weekends');
            $browser->assertPresent('.burrito-builder');
        }
    }

    /**
     * Test performance metrics for mobile.
     */
    protected function assertMobilePerformance($browser): void
    {
        // Test page load times
        $loadTime = $browser->driver->executeScript('return window.performance.timing.loadEventEnd - window.performance.timing.navigationStart');
        $this->assertLessThan(3000, $loadTime, 'Page should load in under 3 seconds on mobile');

        // Test first contentful paint
        $fcp = $browser->driver->executeScript('return window.performance.getEntriesByType("paint").find(entry => entry.name === "first-contentful-paint")?.startTime');
        if ($fcp) {
            $this->assertLessThan(2000, $fcp, 'First contentful paint should be under 2 seconds');
        }
    }

    /**
     * Simulate mobile touch gestures.
     */
    protected function swipeLeft($browser, $selector): void
    {
        $browser->driver->executeScript("
            const element = document.querySelector('$selector');
            const touchStart = new TouchEvent('touchstart', {
                touches: [{clientX: 200, clientY: 100}]
            });
            const touchEnd = new TouchEvent('touchend', {
                touches: [{clientX: 50, clientY: 100}]
            });
            element.dispatchEvent(touchStart);
            setTimeout(() => element.dispatchEvent(touchEnd), 100);
        ");
    }

    /**
     * Test burrito builder track navigation.
     */
    protected function navigateBurritoTrack($browser): void
    {
        $browser->visit('/build-burrito')
            ->assertSee('Build Your Burrito')
            ->click('@protein-selection')
            ->waitFor('@rice-beans-selection')
            ->click('@rice-beans-selection')
            ->waitFor('@fresh-toppings-selection')
            ->click('@fresh-toppings-selection')
            ->waitFor('@salsas-selection')
            ->click('@salsas-selection')
            ->waitFor('@creamy-selection')
            ->click('@creamy-selection')
            ->waitFor('@order-summary');
    }
}
