// @ts-check
import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// ES Module compatible __dirname
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load environment variables from .env
dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * Playwright Configuration for HyperBiz (Laravel + Vue.js + Inertia)
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  /* Test directory */
  testDir: './tests/e2e',

  /* Test file patterns */
  testMatch: '**/*.spec.js',

  /* Output directory for test artifacts */
  outputDir: './tests/e2e/test-results',

  /* Global timeout for each test (Vue/Inertia apps may need longer for hydration) */
  timeout: 60 * 1000, // 60 seconds per test

  /* Timeout for expect() assertions */
  expect: {
    timeout: 10 * 1000, // 10 seconds for assertions
  },

  /* Run tests in files in parallel */
  fullyParallel: true,

  /* Fail the build on CI if you accidentally left test.only in the source code */
  forbidOnly: !!process.env.CI,

  /* Retry failed tests */
  retries: process.env.CI ? 2 : 1,

  /* Limit parallel workers */
  workers: process.env.CI ? 1 : 4,

  /* Reporter configuration */
  reporter: process.env.CI
    ? [
        ['list'],
        ['html', { outputFolder: './tests/e2e/playwright-report', open: 'never' }],
        ['json', { outputFile: './tests/e2e/test-results.json' }],
      ]
    : [
        ['list'],
        ['html', { outputFolder: './tests/e2e/playwright-report', open: 'never' }],
      ],

  /* Shared settings for all projects */
  use: {
    /* Base URL - matches your Laravel app */
    baseURL: process.env.APP_URL || 'http://hyperbiz.local',

    /* Viewport size */
    viewport: { width: 1280, height: 720 },

    /* Timeouts for actions and navigation */
    actionTimeout: 15 * 1000, // 15 seconds for clicks, fills, etc.
    navigationTimeout: 30 * 1000, // 30 seconds for page navigation (Inertia transitions)

    /* Collect trace for debugging */
    trace: 'on-first-retry',

    /* Screenshot on failure */
    screenshot: 'only-on-failure',

    /* Video recording (useful for debugging flaky tests) */
    video: 'on-first-retry',

    /* Ignore HTTPS errors (for local development with self-signed certs) */
    ignoreHTTPSErrors: true,

    /* Browser context options */
    contextOptions: {
      /* Reduce motion for faster animations */
      reducedMotion: 'reduce',
    },

    /* Locale and timezone for consistent testing */
    locale: 'en-US',
    timezoneId: 'Asia/Jakarta',

    /* Extra HTTP headers */
    extraHTTPHeaders: {
      'Accept-Language': 'en-US,en;q=0.9',
    },
  },

  /* Configure projects for different browsers/scenarios */
  projects: [
    /* Authentication setup - runs before other tests */
    {
      name: 'setup',
      testMatch: /.*\.setup\.js/,
      teardown: 'cleanup',
    },

    /* Cleanup after tests */
    {
      name: 'cleanup',
      testMatch: /.*\.teardown\.js/,
    },

    /* Primary browser for development */
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        /* Use storage state from auth setup */
        storageState: './tests/e2e/.auth/user.json',
      },
      dependencies: ['setup'],
    },

    /* Cross-browser testing (enable for CI or full test runs) */
    {
      name: 'firefox',
      use: {
        ...devices['Desktop Firefox'],
        storageState: './tests/e2e/.auth/user.json',
      },
      dependencies: ['setup'],
    },

    {
      name: 'webkit',
      use: {
        ...devices['Desktop Safari'],
        storageState: './tests/e2e/.auth/user.json',
      },
      dependencies: ['setup'],
    },

    /* Mobile viewport testing */
    {
      name: 'mobile-chrome',
      use: {
        ...devices['Pixel 5'],
        storageState: './tests/e2e/.auth/user.json',
      },
      dependencies: ['setup'],
    },

    {
      name: 'mobile-safari',
      use: {
        ...devices['iPhone 13'],
        storageState: './tests/e2e/.auth/user.json',
      },
      dependencies: ['setup'],
    },
  ],

  /* Web server configuration - auto-start Laravel dev server */
  webServer: [
    {
      command: 'php artisan serve --port=8000',
      url: 'http://localhost:8000',
      reuseExistingServer: !process.env.CI,
      timeout: 120 * 1000, // 2 minutes to start
      stdout: 'pipe',
      stderr: 'pipe',
    },
    /* Uncomment if you need Vite dev server for HMR during tests */
    // {
    //   command: 'npm run dev',
    //   url: 'http://localhost:5173',
    //   reuseExistingServer: !process.env.CI,
    //   timeout: 120 * 1000,
    // },
  ],

  /* Global setup/teardown scripts */
  // globalSetup: './tests/e2e/global-setup.js',
  // globalTeardown: './tests/e2e/global-teardown.js',
});

