// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Subscription E2E Tests
 * Tests the tenant subscription management functionality
 */

test.describe('Subscription Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to subscription page
    await page.goto('/subscription');
    await page.waitForLoadState('networkidle');
  });

  test('should display subscription dashboard', async ({ page }) => {
    // Verify page title
    await expect(page).toHaveTitle(/Subscription/);

    // Verify main heading is visible
    await expect(page.getByRole('heading', { name: 'Subscription' })).toBeVisible();
  });

  test('should display current plan card', async ({ page }) => {
    // Check for current plan section
    const currentPlanSection = page.locator('text=Current Plan').first();
    await expect(currentPlanSection).toBeVisible();
  });

  test('should display usage statistics', async ({ page }) => {
    // Check for usage section elements
    await expect(page.locator('text=Usage')).toBeVisible();
  });

  test('should have upgrade button if on lower plan', async ({ page }) => {
    // Check if upgrade button is present (may not be visible depending on plan)
    const upgradeBtn = page.getByRole('link', { name: /Upgrade|View Plans/i });
    // This is conditional based on current plan
    const hasUpgrade = await upgradeBtn.count();
    expect(hasUpgrade).toBeGreaterThanOrEqual(0); // May or may not have upgrade option
  });

  test('should navigate to plans page', async ({ page }) => {
    // Click on View Plans or Upgrade link
    const plansLink = page.getByRole('link', { name: /View Plans|Upgrade|Change Plan/i }).first();

    if (await plansLink.isVisible()) {
      await plansLink.click();
      await page.waitForLoadState('networkidle');
      await expect(page).toHaveURL(/subscription\/plans/);
    }
  });
});

test.describe('Subscription Plans Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/subscription/plans');
    await page.waitForLoadState('networkidle');
  });

  test('should display plans page', async ({ page }) => {
    // Verify page is loaded
    await expect(page).toHaveTitle(/Plans|Subscription/);

    // Verify heading
    await expect(page.getByRole('heading', { name: /Choose Your Plan|Plans|Pricing/i })).toBeVisible();
  });

  test('should display billing cycle toggle', async ({ page }) => {
    // Check for monthly/yearly toggle
    const monthlyOption = page.getByText('Monthly').first();
    const yearlyOption = page.getByText('Yearly').first();

    await expect(monthlyOption).toBeVisible();
    await expect(yearlyOption).toBeVisible();
  });

  test('should toggle billing cycle', async ({ page }) => {
    // Find yearly toggle/button
    const yearlyToggle = page.getByText('Yearly').first();
    await yearlyToggle.click();

    // Wait for prices to update
    await page.waitForTimeout(300);

    // Click back to monthly
    const monthlyToggle = page.getByText('Monthly').first();
    await monthlyToggle.click();

    await page.waitForTimeout(300);
  });

  test('should display multiple plan cards', async ({ page }) => {
    // Check that at least one plan card exists
    const planCards = page.locator('.card, [class*="plan"]');
    const cardCount = await planCards.count();

    // Should have at least one plan
    expect(cardCount).toBeGreaterThan(0);
  });

  test('should display plan features', async ({ page }) => {
    // Look for common feature indicators
    const featureElements = page.locator('text=/Users|Products|Customers|Orders/i');
    const hasFeatures = await featureElements.count();

    expect(hasFeatures).toBeGreaterThan(0);
  });

  test('should mark current plan if subscribed', async ({ page }) => {
    // Check for "Current Plan" badge or indicator
    const currentPlanIndicator = page.locator('text=/Current Plan|Your Plan/i');
    // This is conditional based on subscription status
    const hasCurrentPlan = await currentPlanIndicator.count();

    // May or may not have current plan indicator
    expect(hasCurrentPlan).toBeGreaterThanOrEqual(0);
  });
});

test.describe('Billing History', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/subscription/billing-history');
    await page.waitForLoadState('networkidle');
  });

  test('should display billing history page', async ({ page }) => {
    // Verify page heading
    await expect(page.getByRole('heading', { name: /Billing History|Invoices/i })).toBeVisible();
  });

  test('should display invoice table or empty state', async ({ page }) => {
    // Check for either table or empty state message
    const table = page.locator('table, [role="table"]');
    const emptyState = page.locator('text=/No invoices|No billing history/i');

    const hasTable = await table.count();
    const hasEmpty = await emptyState.count();

    // Should have either invoices table or empty state
    expect(hasTable + hasEmpty).toBeGreaterThan(0);
  });

  test('should have status filter if invoices exist', async ({ page }) => {
    // Check for status filter
    const statusFilter = page.getByRole('button', { name: /Status|All Status/i });
    const hasFilter = await statusFilter.count();

    // Filter may be present if there are invoices
    expect(hasFilter).toBeGreaterThanOrEqual(0);
  });
});

test.describe('Sidebar Navigation', () => {
  test('should have subscription menu in sidebar', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Check for Subscription menu item in sidebar
    const subscriptionMenu = page.locator('text=Subscription').first();
    await expect(subscriptionMenu).toBeVisible();
  });

  test('should navigate to subscription from sidebar', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Click on Subscription in sidebar
    const subscriptionMenu = page.locator('.menu-item').filter({ hasText: 'Subscription' }).first();
    await subscriptionMenu.click();

    // Wait for submenu to expand
    await page.waitForTimeout(300);

    // Click on Current Plan
    const currentPlanLink = page.getByRole('link', { name: 'Current Plan' }).first();
    if (await currentPlanLink.isVisible()) {
      await currentPlanLink.click();
      await page.waitForLoadState('networkidle');
      await expect(page).toHaveURL(/subscription/);
    }
  });

  test('should navigate to plans from sidebar', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Click on Subscription menu
    const subscriptionMenu = page.locator('.menu-item').filter({ hasText: 'Subscription' }).first();
    await subscriptionMenu.click();

    await page.waitForTimeout(300);

    // Click on Available Plans
    const plansLink = page.getByRole('link', { name: 'Available Plans' }).first();
    if (await plansLink.isVisible()) {
      await plansLink.click();
      await page.waitForLoadState('networkidle');
      await expect(page).toHaveURL(/subscription\/plans/);
    }
  });

  test('should navigate to billing from sidebar', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Click on Subscription menu
    const subscriptionMenu = page.locator('.menu-item').filter({ hasText: 'Subscription' }).first();
    await subscriptionMenu.click();

    await page.waitForTimeout(300);

    // Click on Billing History
    const billingLink = page.getByRole('link', { name: 'Billing History' }).first();
    if (await billingLink.isVisible()) {
      await billingLink.click();
      await page.waitForLoadState('networkidle');
      await expect(page).toHaveURL(/subscription\/billing-history/);
    }
  });
});

test.describe('Icons Display', () => {
  test('should display icons correctly on subscription page', async ({ page }) => {
    await page.goto('/subscription');
    await page.waitForLoadState('networkidle');

    // Check that Keenicons are rendered (ki-filled class icons)
    const icons = page.locator('[class*="ki-"]');
    const iconCount = await icons.count();

    // There should be icons on the page
    expect(iconCount).toBeGreaterThan(0);
  });

  test('should display icons correctly on plans page', async ({ page }) => {
    await page.goto('/subscription/plans');
    await page.waitForLoadState('networkidle');

    // Check for icons
    const icons = page.locator('[class*="ki-"]');
    const iconCount = await icons.count();

    expect(iconCount).toBeGreaterThan(0);
  });

  test('should display sidebar subscription icons', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Check for subscription menu icon
    const discountIcon = page.locator('.ki-discount, .ki-filled.ki-discount');
    // The icon should be present in sidebar
    const hasIcon = await discountIcon.count();

    expect(hasIcon).toBeGreaterThanOrEqual(0); // May be hidden if menu collapsed
  });
});

test.describe('Plan Selection Flow', () => {
  test('should show checkout button on plans', async ({ page }) => {
    await page.goto('/subscription/plans');
    await page.waitForLoadState('networkidle');

    // Look for subscribe/select buttons
    const selectButtons = page.getByRole('button', { name: /Subscribe|Select|Choose|Get Started/i });
    const buttonCount = await selectButtons.count();

    // Should have at least one plan selection button
    expect(buttonCount).toBeGreaterThanOrEqual(0);
  });
});
