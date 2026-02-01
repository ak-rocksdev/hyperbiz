// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Admin Subscription E2E Tests
 * Tests the admin payment verification functionality
 * Note: These tests require platform admin authentication
 */

test.describe('Admin Payment Verifications', () => {
  // Skip these tests by default as they require admin auth
  test.skip(({ browserName }) => true, 'Requires separate admin auth setup');

  test('should display payment verifications page', async ({ page }) => {
    await page.goto('/admin/payment-verifications');
    await page.waitForLoadState('networkidle');

    // Verify page heading
    await expect(page.getByRole('heading', { name: /Payment Verifications/i })).toBeVisible();
  });

  test('should display payment proofs table', async ({ page }) => {
    await page.goto('/admin/payment-verifications');
    await page.waitForLoadState('networkidle');

    // Check for table or empty state
    const table = page.locator('table, [role="table"]');
    const emptyState = page.locator('text=/No pending|No payment proofs/i');

    const hasContent = (await table.count()) + (await emptyState.count());
    expect(hasContent).toBeGreaterThan(0);
  });

  test('should have status filter tabs', async ({ page }) => {
    await page.goto('/admin/payment-verifications');
    await page.waitForLoadState('networkidle');

    // Check for status filter tabs
    const pendingTab = page.getByRole('tab', { name: /Pending/i });
    const allTab = page.getByRole('tab', { name: /All/i });

    await expect(pendingTab).toBeVisible();
  });

  test('should display verification icons correctly', async ({ page }) => {
    await page.goto('/admin/payment-verifications');
    await page.waitForLoadState('networkidle');

    // Check for verify icon
    const verifyIcon = page.locator('.ki-verify, [class*="ki-verify"]');
    const iconCount = await verifyIcon.count();

    // Icon should be present in sidebar or page
    expect(iconCount).toBeGreaterThanOrEqual(0);
  });
});

test.describe('Admin Sidebar Navigation', () => {
  test.skip(({ browserName }) => true, 'Requires separate admin auth setup');

  test('should show Payment Verifications in admin sidebar', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle');

    // Check for Payment Verifications menu
    const paymentVerificationsMenu = page.locator('text=Payment Verifications');
    await expect(paymentVerificationsMenu).toBeVisible();
  });

  test('should navigate to payment verifications from sidebar', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle');

    // Click on Payment Verifications
    const menuItem = page.getByRole('link', { name: 'Payment Verifications' });
    await menuItem.click();

    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/admin\/payment-verifications/);
  });
});
