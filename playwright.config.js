import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html'],
    ['json', { outputFile: 'build/playwright-report.json' }],
    ['junit', { outputFile: 'build/playwright-results.xml' }],
  ],
  use: {
    baseURL: 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  projects: [
    // Mobile testing (primary focus)
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 13'] },
    },
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'iPhone SE',
      use: { ...devices['iPhone SE'] },
    },

    // Tablet testing
    {
      name: 'iPad',
      use: { ...devices['iPad Pro'] },
    },

    // Desktop (secondary)
    {
      name: 'Desktop Chrome',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'Desktop Safari',
      use: { ...devices['Desktop Safari'] },
    },

    // Slow network testing
    {
      name: 'Mobile Slow 3G',
      use: {
        ...devices['iPhone 13'],
        launchOptions: {
          slowMo: 100,
        },
      },
    },
  ],

  webServer: {
    command: './vendor/bin/sail up -d && ./vendor/bin/sail artisan serve --host=0.0.0.0 --port=8000',
    port: 8000,
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
  },
});