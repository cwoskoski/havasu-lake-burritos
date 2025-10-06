import { test, expect } from '@playwright/test';

test.describe('Mobile Burrito Builder', () => {
  test.beforeEach(async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/burrito-builder');
  });

  test('displays mobile-optimized burrito builder interface', async ({ page }) => {
    // Check for mobile viewport meta tag
    const viewport = await page.$('meta[name="viewport"]');
    expect(viewport).toBeTruthy();

    // Check main heading
    await expect(page.locator('h1')).toContainText('Build Your Burrito');

    // Verify ingredient categories are visible
    await expect(page.locator('[data-testid="ingredient-categories"]')).toBeVisible();
  });

  test('validates touch target sizes meet 44px minimum', async ({ page }) => {
    const buttons = await page.locator('button, .btn, [role="button"]').all();

    for (const button of buttons) {
      const box = await button.boundingBox();
      if (box) {
        expect(box.width).toBeGreaterThanOrEqual(44);
        expect(box.height).toBeGreaterThanOrEqual(44);
      }
    }
  });

  test('completes full burrito building workflow on mobile', async ({ page }) => {
    // Step 1: Select protein
    await expect(page.locator('[data-testid="step-indicator"]')).toContainText('Step 1 of 5');
    await page.locator('[data-testid="protein-carnitas"]').tap();
    await page.locator('[data-testid="next-step"]').tap();

    // Step 2: Select rice and beans
    await expect(page.locator('[data-testid="step-indicator"]')).toContainText('Step 2 of 5');
    await page.locator('[data-testid="rice-cilantro-lime"]').tap();
    await page.locator('[data-testid="beans-black"]').tap();
    await page.locator('[data-testid="next-step"]').tap();

    // Step 3: Select fresh toppings
    await expect(page.locator('[data-testid="step-indicator"]')).toContainText('Step 3 of 5');
    await page.locator('[data-testid="topping-lettuce"]').tap();
    await page.locator('[data-testid="topping-tomatoes"]').tap();
    await page.locator('[data-testid="next-step"]').tap();

    // Step 4: Select salsas
    await expect(page.locator('[data-testid="step-indicator"]')).toContainText('Step 4 of 5');
    await page.locator('[data-testid="salsa-medium"]').tap();
    await page.locator('[data-testid="next-step"]').tap();

    // Step 5: Select creamy options
    await expect(page.locator('[data-testid="step-indicator"]')).toContainText('Step 5 of 5');
    await page.locator('[data-testid="creamy-cheese"]').tap();
    await page.locator('[data-testid="finish-burrito"]').tap();

    // Verify order summary
    await expect(page.locator('[data-testid="order-summary"]')).toBeVisible();
    await expect(page.locator('[data-testid="order-summary"]')).toContainText('Carnitas');
    await expect(page.locator('[data-testid="order-summary"]')).toContainText('Cilantro Lime Rice');
    await expect(page.locator('[data-testid="order-summary"]')).toContainText('Black Beans');
  });

  test('displays weekend-only ordering message on weekdays', async ({ page }) => {
    // Mock a Monday
    await page.addInitScript(() => {
      const mockDate = new Date('2024-01-15T10:00:00Z'); // Monday
      Date.now = () => mockDate.getTime();
    });

    await page.reload();
    await expect(page.locator('[data-testid="weekend-only-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="weekend-only-message"]')).toContainText('Ordering is only available on weekends');
  });

  test('shows real-time burrito countdown', async ({ page }) => {
    // Mock a Saturday
    await page.addInitScript(() => {
      const mockDate = new Date('2024-01-13T10:00:00Z'); // Saturday
      Date.now = () => mockDate.getTime();
    });

    await page.reload();
    await expect(page.locator('[data-testid="burrito-counter"]')).toBeVisible();
    await expect(page.locator('[data-testid="burrito-counter"]')).toContainText('burritos remaining');
  });

  test('supports swipe gestures for ingredient selection', async ({ page }) => {
    await page.goto('/burrito-builder/proteins');

    const carousel = page.locator('[data-testid="ingredient-carousel"]');
    await expect(carousel).toBeVisible();

    // Get initial position
    const initialPosition = await carousel.evaluate(el => el.scrollLeft);

    // Simulate swipe left
    await carousel.hover();
    await page.mouse.down();
    await page.mouse.move(200, 0);
    await page.mouse.up();

    // Verify carousel moved
    const newPosition = await carousel.evaluate(el => el.scrollLeft);
    expect(newPosition).not.toBe(initialPosition);
  });

  test('performs well on slow mobile networks', async ({ page }) => {
    // Simulate slow 3G network
    await page.route('**/*', async route => {
      await new Promise(resolve => setTimeout(resolve, 100)); // Add latency
      await route.continue();
    });

    const startTime = Date.now();
    await page.goto('/burrito-builder');
    const loadTime = Date.now() - startTime;

    // Should load within 3 seconds even on slow network
    expect(loadTime).toBeLessThan(3000);
    await expect(page.locator('[data-testid="burrito-builder"]')).toBeVisible();
  });

  test('maintains single-handed operation capability', async ({ page }) => {
    const screenHeight = 667; // iPhone SE height
    const thumbReachZone = screenHeight * 0.75; // Bottom 75% of screen

    // Check primary action buttons are in thumb reach
    const primaryButtons = await page.locator('[data-primary-action="true"]').all();

    for (const button of primaryButtons) {
      const box = await button.boundingBox();
      if (box) {
        expect(box.y).toBeGreaterThan(screenHeight - thumbReachZone);
      }
    }
  });

  test('displays loading states appropriately', async ({ page }) => {
    // Intercept API calls to simulate loading
    await page.route('/api/**', async route => {
      await new Promise(resolve => setTimeout(resolve, 1000));
      await route.continue();
    });

    await page.locator('[data-testid="protein-carnitas"]').tap();

    // Should show loading state
    await expect(page.locator('[data-testid="loading-indicator"]')).toBeVisible();

    // Loading should disappear when complete
    await expect(page.locator('[data-testid="loading-indicator"]')).toBeHidden({ timeout: 2000 });
  });

  test('handles offline scenarios gracefully', async ({ page }) => {
    // Go offline
    await page.context().setOffline(true);

    await page.reload();

    // Should show offline message
    await expect(page.locator('[data-testid="offline-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="offline-message"]')).toContainText('You appear to be offline');
  });

  test('supports accessibility features', async ({ page }) => {
    // Check for proper ARIA labels
    const ingredientButtons = await page.locator('[data-testid^="protein-"]').all();

    for (const button of ingredientButtons) {
      const ariaLabel = await button.getAttribute('aria-label');
      expect(ariaLabel).toBeTruthy();
      expect(ariaLabel).toContain('Select');
    }

    // Check keyboard navigation
    await page.keyboard.press('Tab');
    const focusedElement = await page.locator(':focus');
    expect(focusedElement).toBeTruthy();

    // Check for focus indicators
    const focusedBox = await focusedElement.boundingBox();
    expect(focusedBox).toBeTruthy();
  });

  test('validates form submission prevents invalid orders', async ({ page }) => {
    // Try to submit without selecting required ingredients
    await page.locator('[data-testid="submit-order"]').tap();

    // Should show validation errors
    await expect(page.locator('[data-testid="validation-error"]')).toBeVisible();
    await expect(page.locator('[data-testid="validation-error"]')).toContainText('Please select');
  });
});