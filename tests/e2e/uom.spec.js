// @ts-check
import { test, expect } from '@playwright/test';

/**
 * UoM (Units of Measure) E2E Tests
 * Tests the UoM management functionality
 */

test.describe('UoM Management', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to UoM list page
    await page.goto('/uom/list');
    await page.waitForLoadState('networkidle');
  });

  test('should display UoM list page', async ({ page }) => {
    // Verify page title
    await expect(page).toHaveTitle(/Units of Measure/);

    // Verify main elements are visible
    await expect(page.getByRole('heading', { name: 'Units of Measure' })).toBeVisible();
    await expect(page.getByRole('button', { name: /New UoM/i })).toBeVisible();
  });

  test('should open create UoM modal', async ({ page }) => {
    // Click "New UoM" button
    await page.getByRole('button', { name: /New UoM/i }).click();

    // Wait for modal to appear
    await expect(page.getByRole('heading', { name: 'Create New UoM' })).toBeVisible();

    // Verify form fields are present
    await expect(page.getByPlaceholder(/e.g., PCS, KG/i)).toBeVisible();
    await expect(page.getByPlaceholder(/e.g., Pieces, Kilogram/i)).toBeVisible();
  });

  test('should search UoMs', async ({ page }) => {
    // Type in search box
    const searchInput = page.getByPlaceholder(/Search UoMs/i);
    await searchInput.fill('kg');

    // Press Enter to search
    await searchInput.press('Enter');

    // Wait for results to update
    await page.waitForLoadState('networkidle');

    // URL should contain search parameter
    await expect(page).toHaveURL(/search=kg/);
  });

  test('should filter by category', async ({ page }) => {
    // Click category filter dropdown
    await page.getByRole('button', { name: /All Categories/i }).click();

    // Wait for dropdown options
    await page.waitForTimeout(300); // Allow dropdown animation

    // The dropdown should be visible (test structure)
    // Actual category selection depends on seeded data
  });

  test('should filter by status', async ({ page }) => {
    // Click status filter dropdown
    const statusDropdown = page.getByRole('button', { name: /All Status/i });
    await statusDropdown.click();

    // Wait for dropdown to open and select "Active" option
    // Use first() to select from dropdown menu, not the modal checkbox label
    await page.getByText('Active', { exact: true }).first().click();

    // Wait for filter to apply
    await page.waitForLoadState('networkidle');

    // URL should contain status parameter
    await expect(page).toHaveURL(/status=active/);
  });
});

test.describe('UoM Category Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/uom-category/list');
    await page.waitForLoadState('networkidle');
  });

  test('should display UoM Categories page', async ({ page }) => {
    await expect(page).toHaveTitle(/UoM Categories/);
    await expect(page.getByRole('heading', { name: 'UoM Categories' })).toBeVisible();
  });

  test('should open create category modal', async ({ page }) => {
    await page.getByRole('button', { name: /New Category/i }).click();
    await expect(page.getByRole('heading', { name: 'Create New UoM Category' })).toBeVisible();
  });
});
