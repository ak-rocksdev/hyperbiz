// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Payment E2E Tests
 * Tests the Payment management functionality
 */

test.describe('Payment List', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to payments list page
    await page.goto('/payments');
    await page.waitForLoadState('networkidle');
  });

  test('should display payment list page', async ({ page }) => {
    // Verify page title
    await expect(page).toHaveTitle(/Payments/);

    // Verify main elements are visible
    await expect(page.getByRole('heading', { name: 'Payments' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Payment Records' })).toBeVisible();
  });

  test('should display stats summary cards', async ({ page }) => {
    // Verify stats cards are visible
    await expect(page.getByText('Total Payments')).toBeVisible();
    await expect(page.getByText('Purchase Payments')).toBeVisible();
    await expect(page.getByText('Sales Payments')).toBeVisible();
  });

  test('should search payments', async ({ page }) => {
    // Find and fill search input
    const searchInput = page.getByPlaceholder('Search...');
    await searchInput.fill('PAY');

    // Press Enter to search
    await searchInput.press('Enter');

    // Wait for results to update
    await page.waitForLoadState('networkidle');

    // URL should contain search parameter
    await expect(page).toHaveURL(/search=PAY/);
  });

  test('should filter by date range', async ({ page }) => {
    // Click the date picker
    const datePicker = page.locator('.card-toolbar').getByPlaceholder('Filter by date');
    await datePicker.click();

    // Wait for calendar to appear
    await page.waitForTimeout(300);

    // The calendar should be visible
    await expect(page.locator('.vc-container')).toBeVisible();
  });

  test('should filter by payment type', async ({ page }) => {
    // Find and click type dropdown (SearchableSelect)
    const typeDropdown = page.locator('.card-toolbar').locator('text=All Types').first();
    await typeDropdown.click();

    // Wait for dropdown to open
    await page.waitForTimeout(200);

    // Select "Purchase" option
    await page.getByRole('option', { name: 'Purchase' }).click();

    // Wait for filter to apply
    await page.waitForLoadState('networkidle');

    // URL should contain type parameter
    await expect(page).toHaveURL(/payment_type=purchase/);
  });

  test('should filter by payment method', async ({ page }) => {
    // Find and click method dropdown
    const methodDropdown = page.locator('.card-toolbar').locator('text=All Methods').first();
    await methodDropdown.click();

    // Wait for dropdown to open
    await page.waitForTimeout(200);

    // Select first available payment method (skip "All Methods")
    const options = page.getByRole('option');
    const optionCount = await options.count();

    if (optionCount > 1) {
      await options.nth(1).click();

      // Wait for filter to apply
      await page.waitForLoadState('networkidle');

      // URL should contain method parameter
      await expect(page).toHaveURL(/payment_method=/);
    }
  });

  test('should show reset button when filters are active', async ({ page }) => {
    // Initially reset button should not be visible
    await expect(page.getByRole('button', { name: /Reset/i })).not.toBeVisible();

    // Apply a filter
    const searchInput = page.getByPlaceholder('Search...');
    await searchInput.fill('test');
    await searchInput.press('Enter');
    await page.waitForLoadState('networkidle');

    // Reset button should now be visible
    await expect(page.getByRole('button', { name: /Reset/i })).toBeVisible();
  });

  test('should reset all filters', async ({ page }) => {
    // Apply a search filter first
    const searchInput = page.getByPlaceholder('Search...');
    await searchInput.fill('test');
    await searchInput.press('Enter');
    await page.waitForLoadState('networkidle');

    // Verify filter is applied
    await expect(page).toHaveURL(/search=test/);

    // Click reset button
    await page.getByRole('button', { name: /Reset/i }).click();
    await page.waitForLoadState('networkidle');

    // URL should not contain search parameter anymore
    await expect(page).not.toHaveURL(/search=test/);

    // Search input should be empty
    await expect(searchInput).toHaveValue('');
  });

  test('should display payment table with correct columns', async ({ page }) => {
    // Verify table headers
    await expect(page.getByRole('columnheader', { name: 'Payment #' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Type' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Reference' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Date' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Method' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Amount' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Actions' })).toBeVisible();
  });

  test('should have working pagination', async ({ page }) => {
    // Verify pagination elements exist
    await expect(page.locator('.pagination')).toBeVisible();

    // Verify per page selector exists
    await expect(page.locator('select.select-sm')).toBeVisible();
  });

  test('should change items per page', async ({ page }) => {
    // Find and change per page selector
    const perPageSelect = page.locator('select.select-sm');
    await perPageSelect.selectOption('25');

    // Wait for reload
    await page.waitForLoadState('networkidle');

    // URL should contain per_page parameter
    await expect(page).toHaveURL(/per_page=25/);
  });
});

test.describe('Payment Detail', () => {
  test('should navigate to payment detail from list', async ({ page }) => {
    // Go to payments list
    await page.goto('/payments');
    await page.waitForLoadState('networkidle');

    // Click on first payment link if exists
    const paymentLink = page.locator('table tbody tr').first().locator('a').first();

    if (await paymentLink.isVisible()) {
      const paymentNumber = await paymentLink.textContent();
      await paymentLink.click();
      await page.waitForLoadState('networkidle');

      // Should be on detail page
      await expect(page).toHaveURL(/\/payments\/\d+/);

      // Payment number should be visible on detail page
      if (paymentNumber) {
        await expect(page.getByText(paymentNumber.trim())).toBeVisible();
      }
    }
  });
});
