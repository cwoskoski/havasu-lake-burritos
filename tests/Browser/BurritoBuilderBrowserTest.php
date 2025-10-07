<?php

namespace Tests\Browser;

use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Tests\Helpers\BurritoTestHelper;
use Tests\Traits\MobileTesting;

class BurritoBuilderBrowserTest extends DuskTestCase
{
    use MobileTesting;

    public function test_mobile_burrito_builder_workflow()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone SE size
                ->visit('/burrito-builder')
                ->assertSee('Build Your Burrito')
                ->assertPresent('@ingredient-categories');

            // Test touch targets
            $browser->script('
                const buttons = document.querySelectorAll("button, .btn");
                buttons.forEach(btn => {
                    const rect = btn.getBoundingClientRect();
                    if (rect.width < 44 || rect.height < 44) {
                        throw new Error(`Touch target too small: ${rect.width}x${rect.height}`);
                    }
                });
            ');
        });
    }

    public function test_ingredient_selection_flow()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/burrito-builder')
                // Step 1: Proteins
                ->assertSee('Step 1 of 5')
                ->click('@protein-carnitas')
                ->click('@next-step')
                // Step 2: Rice & Beans
                ->assertSee('Step 2 of 5')
                ->click('@rice-cilantro-lime')
                ->click('@beans-black')
                ->click('@next-step')
                // Step 3: Fresh Toppings
                ->assertSee('Step 3 of 5')
                ->click('@topping-lettuce')
                ->click('@topping-tomatoes')
                ->click('@next-step')
                // Step 4: Salsas
                ->assertSee('Step 4 of 5')
                ->click('@salsa-medium')
                ->click('@next-step')
                // Step 5: Creamy
                ->assertSee('Step 5 of 5')
                ->click('@creamy-cheese')
                ->click('@finish-burrito')
                // Order summary
                ->assertSee('Order Summary')
                ->assertSee('Carnitas')
                ->assertSee('Cilantro Lime Rice')
                ->assertSee('Black Beans');
        });
    }

    public function test_weekend_only_ordering_ui()
    {
        // Test weekday blocking
        $monday = BurritoTestHelper::getWeekdayDates()->first();
        Carbon::setTestNow($monday);

        $this->browse(function (Browser $browser) {
            $browser->visit('/burrito-builder')
                ->assertSee('Ordering is only available on weekends')
                ->assertMissing('@start-building');
        });

        // Test weekend access
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $this->browse(function (Browser $browser) {
            $browser->visit('/burrito-builder')
                ->assertDontSee('Ordering is only available on weekends')
                ->assertPresent('@start-building');
        });
    }

    public function test_real_time_burrito_countdown()
    {
        $saturday = BurritoTestHelper::getWeekendDates()->first();
        Carbon::setTestNow($saturday);

        $this->browse(function (Browser $browser) {
            $browser->visit('/burrito-builder')
                ->assertSeeIn('@burrito-counter', '100 burritos remaining');

            // Simulate order placement in another browser/tab
            $this->simulateOrderPlaced();

            // Refresh and check counter decreased
            $browser->refresh()
                ->assertSeeIn('@burrito-counter', '99 burritos remaining');
        });
    }

    public function test_mobile_swipe_gestures()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/burrito-builder/proteins')
                ->assertPresent('@ingredient-carousel');

            // Test swipe left to see more ingredients
            $browser->script('
                const carousel = document.querySelector("[data-testid=\'ingredient-carousel\']");
                const startX = carousel.offsetWidth / 2;
                const endX = startX - 100;

                // Simulate swipe left
                carousel.dispatchEvent(new TouchEvent("touchstart", {
                    touches: [new Touch({ identifier: 0, target: carousel, clientX: startX, clientY: 100 })]
                }));
                carousel.dispatchEvent(new TouchEvent("touchmove", {
                    touches: [new Touch({ identifier: 0, target: carousel, clientX: endX, clientY: 100 })]
                }));
                carousel.dispatchEvent(new TouchEvent("touchend", { touches: [] }));
            ');

            // Verify carousel moved
            $browser->pause(500)
                ->assertPresent('@ingredient-carousel.moved');
        });
    }

    public function test_single_handed_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->visit('/burrito-builder');

            // Primary actions should be in bottom half of screen (thumb reach)
            $browser->script('
                const screenHeight = window.innerHeight;
                const thumbReachZone = screenHeight * 0.5; // Bottom 50% of screen

                const primaryButtons = document.querySelectorAll("[data-primary-action]");
                primaryButtons.forEach(btn => {
                    const rect = btn.getBoundingClientRect();
                    if (rect.top < thumbReachZone) {
                        throw new Error(`Primary action not in thumb reach zone: ${rect.top}px from top`);
                    }
                });
            ');
        });
    }

    public function test_loading_performance_on_slow_network()
    {
        $this->browse(function (Browser $browser) {
            // Simulate slow 3G network
            $browser->driver->getCommandExecutor()->execute([
                'cmd' => 'Network.enable',
                'params' => [],
            ]);

            $browser->driver->getCommandExecutor()->execute([
                'cmd' => 'Network.emulateNetworkConditions',
                'params' => [
                    'offline' => false,
                    'latency' => 300,
                    'downloadThroughput' => 1.5 * 1024 * 1024 / 8, // 1.5 Mbps
                    'uploadThroughput' => 750 * 1024 / 8, // 750 Kbps
                    'connectionType' => 'cellular3g',
                ],
            ]);

            $startTime = microtime(true);
            $browser->visit('/burrito-builder');
            $loadTime = microtime(true) - $startTime;

            // Should load within 3 seconds on slow network
            $this->assertLessThan(3.0, $loadTime, 'Page should load within 3 seconds on slow network');
            $browser->assertPresent('@burrito-builder');
        });
    }

    public function test_accessibility_compliance()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/burrito-builder')
                // Check for proper heading hierarchy
                ->assertPresent('h1')
                ->assertPresent('[role="button"]')
                // Check for ARIA labels
                ->assertAttributeContains('[data-ingredient]', 'aria-label', 'Select')
                // Check for focus indicators
                ->keys('[data-ingredient]:first-child', '{tab}')
                ->assertHasFocus('[data-ingredient]:first-child');

            // Run axe-core accessibility tests
            $browser->script('
                return new Promise((resolve) => {
                    axe.run(document, (err, results) => {
                        if (results.violations.length > 0) {
                            throw new Error("Accessibility violations found: " + JSON.stringify(results.violations));
                        }
                        resolve(true);
                    });
                });
            ');
        });
    }

    public function test_offline_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/burrito-builder')
                // Build a burrito while online
                ->click('@protein-carnitas')
                ->click('@next-step')
                ->click('@rice-cilantro-lime');

            // Go offline
            $browser->driver->getCommandExecutor()->execute([
                'cmd' => 'Network.enable',
                'params' => [],
            ]);
            $browser->driver->getCommandExecutor()->execute([
                'cmd' => 'Network.emulateNetworkConditions',
                'params' => [
                    'offline' => true,
                    'latency' => 0,
                    'downloadThroughput' => 0,
                    'uploadThroughput' => 0,
                ],
            ]);

            // Should show offline message
            $browser->refresh()
                ->assertSee('You appear to be offline')
                ->assertSee('Your burrito selections are saved locally');
        });
    }

    private function simulateOrderPlaced(): void
    {
        $burritoConfig = BurritoTestHelper::createBurritoConfiguration();
        $this->post('/api/orders', [
            'customer_name' => 'Browser Test Customer',
            'customer_email' => 'browser@test.com',
            'burrito' => $burritoConfig,
        ]);
    }
}
