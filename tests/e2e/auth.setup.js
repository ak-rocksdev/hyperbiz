// @ts-check
import { test as setup, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

// ES Module compatible __dirname
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const authFile = path.join(__dirname, '.auth/user.json');

/**
 * Authentication Setup
 * Logs in once and saves the session state for reuse in other tests
 */
setup('authenticate', async ({ page }) => {
  // Navigate to login page
  await page.goto('/login');

  // Wait for Vue/Inertia to hydrate
  await page.waitForLoadState('networkidle');

  // Fill in login credentials
  await page.getByPlaceholder('Your Email').fill(process.env.TEST_USER_EMAIL || 'frenchfriespeople@gmail.com');
  await page.getByPlaceholder('Enter Password').fill(process.env.TEST_USER_PASSWORD || 'password');

  // Click sign in button
  await page.getByRole('button', { name: 'Sign In' }).click();

  // Wait for navigation to dashboard (Inertia redirect)
  await page.waitForURL('**/dashboard', { timeout: 30000 });

  // Verify we're logged in
  await expect(page).toHaveURL(/.*dashboard/);

  // Save the authentication state
  await page.context().storageState({ path: authFile });
});
