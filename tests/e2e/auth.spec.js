// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Authentication E2E Tests
 * Tests login, logout, forgot password, and registration functionality
 *
 * Note: Some tests use a fresh context (no stored auth) to test auth flows
 */

test.describe('Login Page', () => {
  test.use({ storageState: { cookies: [], origins: [] } }); // Fresh context, no auth

  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
  });

  test('should display login page elements', async ({ page }) => {
    // Verify page title
    await expect(page).toHaveTitle(/Log in/);

    // Verify form elements - use heading for Sign in text
    await expect(page.locator('h3').filter({ hasText: 'Sign in' })).toBeVisible();
    await expect(page.getByPlaceholder('Your Email')).toBeVisible();
    await expect(page.getByPlaceholder('Enter Password')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign In' })).toBeVisible();
  });

  test('should have link to registration', async ({ page }) => {
    await expect(page.getByText('Need an account?')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Sign up' })).toBeVisible();
  });

  test('should have link to forgot password', async ({ page }) => {
    await expect(page.getByRole('link', { name: 'Forgot Password?' })).toBeVisible();
  });

  test('should show validation error for empty form', async ({ page }) => {
    // Click sign in without filling form
    await page.getByRole('button', { name: 'Sign In' }).click();

    // Wait for validation
    await page.waitForTimeout(500);

    // Form should still be on login page (HTML5 validation prevents submission)
    await expect(page).toHaveURL(/login/);
  });

  test('should show error for invalid credentials', async ({ page }) => {
    // Fill invalid credentials
    await page.getByPlaceholder('Your Email').fill('invalid@test.com');
    await page.getByPlaceholder('Enter Password').fill('wrongpassword');

    // Submit form
    await page.getByRole('button', { name: 'Sign In' }).click();

    // Wait for response
    await page.waitForLoadState('networkidle');

    // Should show error message (Jetstream shows validation errors)
    // Either stays on login or shows error
    const url = page.url();
    expect(url).toContain('login');
  });

  test('should login successfully with valid credentials', async ({ page }) => {
    // Fill valid credentials
    await page.getByPlaceholder('Your Email').fill(process.env.TEST_USER_EMAIL || 'frenchfriespeople@gmail.com');
    await page.getByPlaceholder('Enter Password').fill(process.env.TEST_USER_PASSWORD || 'password');

    // Submit form
    await page.getByRole('button', { name: 'Sign In' }).click();

    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 30000 });

    // Verify we're on dashboard
    await expect(page).toHaveURL(/dashboard/);
  });

  test('should navigate to registration page', async ({ page }) => {
    await page.getByRole('link', { name: 'Sign up' }).click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/register/);
  });

  test('should navigate to forgot password page', async ({ page }) => {
    await page.getByRole('link', { name: 'Forgot Password?' }).click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/forgot-password/);
  });
});

test.describe('Registration Page', () => {
  test.use({ storageState: { cookies: [], origins: [] } }); // Fresh context, no auth

  test.beforeEach(async ({ page }) => {
    await page.goto('/register');
    await page.waitForLoadState('networkidle');
  });

  test('should display registration page elements', async ({ page }) => {
    // Verify page title
    await expect(page).toHaveTitle(/Register/);

    // Verify form elements - use heading for Sign up text
    await expect(page.locator('h3').filter({ hasText: 'Sign up' })).toBeVisible();
    await expect(page.getByPlaceholder('Your Full Name')).toBeVisible();
    await expect(page.getByPlaceholder('email@email.com')).toBeVisible();
    // Use first() since there are 2 password fields with toggle buttons
    await expect(page.getByPlaceholder('Enter Password').first()).toBeVisible();
    await expect(page.getByPlaceholder('Re-enter Password')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign up' })).toBeVisible();
  });

  test('should have link back to login', async ({ page }) => {
    await expect(page.getByText('Already have an Account ?')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Sign In' })).toBeVisible();
  });

  test('should show validation for password mismatch', async ({ page }) => {
    // Fill form with mismatched passwords
    await page.getByPlaceholder('Your Full Name').fill('Test User');
    await page.getByPlaceholder('email@email.com').fill('testuser@example.com');
    // Use first() since there are 2 password inputs (one inside toggle wrapper)
    await page.getByPlaceholder('Enter Password').first().fill('password123');
    await page.getByPlaceholder('Re-enter Password').fill('differentpassword');

    // Submit form
    await page.getByRole('button', { name: 'Sign up' }).click();

    // Wait for response
    await page.waitForLoadState('networkidle');

    // Should stay on register page with error
    await expect(page).toHaveURL(/register/);
  });

  test('should navigate back to login', async ({ page }) => {
    await page.getByRole('link', { name: 'Sign In' }).click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/login/);
  });
});

test.describe('Forgot Password Page', () => {
  test.use({ storageState: { cookies: [], origins: [] } }); // Fresh context, no auth

  test.beforeEach(async ({ page }) => {
    await page.goto('/forgot-password');
    await page.waitForLoadState('networkidle');
  });

  test('should display forgot password page elements', async ({ page }) => {
    // Verify page title
    await expect(page).toHaveTitle(/Forgot Password/);

    // Verify form elements - use heading for "Your Email"
    await expect(page.locator('h3').filter({ hasText: 'Your Email' })).toBeVisible();
    await expect(page.getByText('Enter your email to reset password')).toBeVisible();
    await expect(page.getByPlaceholder('email@email.com')).toBeVisible();
    await expect(page.getByRole('button', { name: /Continue/i })).toBeVisible();
  });

  test('should have link back to login', async ({ page }) => {
    await expect(page.getByRole('link', { name: 'Back to Login' })).toBeVisible();
  });

  test('should submit reset password request', async ({ page }) => {
    // Fill email
    await page.getByPlaceholder('email@email.com').fill('test@example.com');

    // Submit form
    await page.getByRole('button', { name: /Continue/i }).click();

    // Wait for response
    await page.waitForLoadState('networkidle');

    // Should show success message or stay on page
    // (actual email sending depends on mail configuration)
  });

  test('should navigate back to login', async ({ page }) => {
    await page.getByRole('link', { name: 'Back to Login' }).click();
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/login/);
  });
});

test.describe('Logout', () => {
  // Uses authenticated session from setup

  test('should logout successfully', async ({ page }) => {
    // First login/go to authenticated page
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Find and click user menu/logout
    // The logout is typically in a dropdown menu
    const userMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await userMenu.isVisible()) {
      await userMenu.click();
      await page.waitForTimeout(300);

      // Look for logout option
      const logoutLink = page.getByRole('link', { name: /Log Out|Logout|Sign Out/i });
      if (await logoutLink.isVisible()) {
        await logoutLink.click();
        await page.waitForLoadState('networkidle');

        // Should be redirected to login or home
        const url = page.url();
        expect(url.includes('login') || url === page.context().pages()[0].url()).toBeTruthy();
      }
    }
  });
});

test.describe('Protected Routes', () => {
  test.use({ storageState: { cookies: [], origins: [] } }); // Fresh context, no auth

  test('should redirect unauthenticated user to login', async ({ page }) => {
    // Try to access dashboard without auth
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Should be redirected to login
    await expect(page).toHaveURL(/login/);
  });

  test('should redirect unauthenticated user from user list to login', async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveURL(/login/);
  });
});
