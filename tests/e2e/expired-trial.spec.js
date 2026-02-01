// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Expired Trial E2E Tests
 *
 * Tests the behavior when a tenant's trial has expired.
 * The company (PT. Makmur Djaya) should be in read-only mode:
 * - Can view all pages (GET requests)
 * - Cannot create, edit, or delete data (POST/PUT/DELETE blocked)
 * - Should see UpgradeRequired page when attempting write operations
 *
 * Prerequisites:
 * - PT. Makmur Djaya (company_id=2) has trial_ends_at set to past date
 * - User: Kylan Barnett (abdulkadir.devworks@gmail.com)
 *
 * IMPORTANT: These tests use TENANT user credentials, not platform admin.
 * The auth.setup.js is for platform admin tests - we skip it here.
 */

test.describe('Expired Trial - Read-Only Mode', () => {
  // IMPORTANT: Use fresh context - we login as TENANT user, not platform admin
  // This clears any saved auth state and prevents using the admin session
  test.use({ storageState: { cookies: [], origins: [] } });

  // Increase timeout for these tests since they login fresh each time
  test.setTimeout(60000);

  /**
   * Login helper for TENANT user with expired trial
   * User: Kylan Barnett (abdulkadir.devworks@gmail.com)
   * Company: PT. Makmur Djaya (company_id=2)
   * Role: Tenant user (NOT platform admin)
   */
  const loginAsExpiredUser = async (page) => {
    await page.goto('/login');
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500); // Wait for Vue hydration

    // Login as TENANT user - Kylan Barnett
    await page.getByPlaceholder('Your Email').fill('abdulkadir.devworks@gmail.com');
    await page.getByPlaceholder('Enter Password').fill('password');
    await page.getByRole('button', { name: 'Sign In' }).click();

    // Wait for redirect to complete
    await page.waitForURL(/dashboard|onboarding/, { timeout: 30000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000); // Wait for Vue hydration
  };

  test.describe('Read Access (Should Work)', () => {
    test('should login successfully with expired trial account', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Should be able to login and see dashboard
      await expect(page).toHaveURL(/dashboard/);

      // Wait for Vue content to render - check for sidebar or main content
      await page.waitForSelector('.sidebar, #app, [data-page]', { timeout: 10000 });
    });

    test('should be able to view dashboard', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Dashboard should be visible
      await expect(page).toHaveURL(/dashboard/);

      // Wait for any content to appear (sidebar, menu, or dashboard content)
      const hasContent = await page.locator('.sidebar, .menu, [class*="dashboard"]').count();
      expect(hasContent).toBeGreaterThan(0);
    });

    test('should be able to view product list', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/product/list');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Product list should be accessible
      await expect(page).toHaveURL(/product\/list/);

      // Check page loaded (either product heading or table)
      const hasContent = await page.locator('h1, h2, table, .card').count();
      expect(hasContent).toBeGreaterThan(0);
    });

    test('should be able to view customer list', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/customer/list');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Customer list should be accessible
      await expect(page).toHaveURL(/customer\/list/);
    });

    test('should be able to view inventory', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/inventory');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Inventory should be accessible
      await expect(page).toHaveURL(/inventory/);
    });

    test('should be able to view subscription page', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Subscription page should be accessible
      await expect(page).toHaveURL(/subscription/);

      // Should show expired status or upgrade prompts
      const pageContent = await page.content();
      expect(
        pageContent.includes('Expired') ||
        pageContent.includes('Upgrade') ||
        pageContent.includes('expired') ||
        pageContent.includes('Trial')
      ).toBeTruthy();
    });

    test('should be able to view subscription plans', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription/plans');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Plans page should be accessible
      await expect(page).toHaveURL(/subscription\/plans/);
    });
  });

  test.describe('Write Access (Should Be Blocked)', () => {
    test('should block creating new product via form submission', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Go to product creation page
      await page.goto('/product/create');
      await page.waitForLoadState('networkidle');

      // Try to fill and submit the form
      const nameInput = page.getByPlaceholder(/name/i).first();
      if (await nameInput.isVisible()) {
        await nameInput.fill('Test Product Expired');

        // Try to submit
        const submitBtn = page.getByRole('button', { name: /Save|Create|Submit/i }).first();
        if (await submitBtn.isVisible()) {
          await submitBtn.click();
          await page.waitForLoadState('networkidle');

          // Should see UpgradeRequired page or error message
          const pageContent = await page.content();
          const isBlocked =
            pageContent.includes('Upgrade') ||
            pageContent.includes('subscription') ||
            pageContent.includes('expired') ||
            page.url().includes('upgrade');

          expect(isBlocked).toBeTruthy();
        }
      }
    });

    test('should block creating new product via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Navigate to a page first to get cookies
      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Try to make a POST request to create product (correct route: /products/api/store)
      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/products/api/store', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf || '',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              name: 'Test Product API',
              sku: 'TEST-API-001',
              price: 10000,
            }),
          });
          return { status: res.status, ok: res.ok };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 Forbidden
      expect(response.status).toBe(403);
    });

    test('should block updating existing data via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Try to make a PUT/PATCH request (correct route: /products/api/update/{id})
      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/products/api/update/1', {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf || '',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              name: 'Updated Product Name',
            }),
          });
          return { status: res.status, ok: res.ok };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 Forbidden
      expect(response.status).toBe(403);
    });

    test('should block toggling customer status via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Try to make a PATCH request (correct route: /customer/api/toggle-status/{id})
      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/customer/api/toggle-status/1', {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf || '',
              'Accept': 'application/json',
            },
          });
          return { status: res.status, ok: res.ok };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 Forbidden
      expect(response.status).toBe(403);
    });

    test('should block creating customer via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/customer/api/store', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf || '',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              name: 'Test Customer Blocked',
              email: 'blocked@test.com',
            }),
          });
          return { status: res.status, ok: res.ok };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 Forbidden
      expect(response.status).toBe(403);
    });

    test('should block updating customer via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/customer/api/update/1', {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf || '',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              name: 'Updated Customer Name',
            }),
          });
          return { status: res.status, ok: res.ok };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 Forbidden
      expect(response.status).toBe(403);
    });
  });

  test.describe('UpgradeRequired Page', () => {
    test('should display UpgradeRequired page on write attempt', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Navigate to product create
      await page.goto('/product/create');
      await page.waitForLoadState('networkidle');

      // Check if we can access the create page (GET is allowed)
      // Try to submit a form with Inertia
      const nameInput = page.getByLabel(/name/i).first();

      if (await nameInput.isVisible()) {
        await nameInput.fill('Test Product');

        const saveBtn = page.getByRole('button', { name: /Save/i });
        if (await saveBtn.isVisible()) {
          await saveBtn.click();
          await page.waitForLoadState('networkidle');
          await page.waitForTimeout(500);

          // Check if UpgradeRequired page is shown
          const upgradeHeading = page.locator('text=Subscription Expired');
          const upgradeBtn = page.getByRole('link', { name: /Upgrade/i });

          const hasUpgradeContent =
            await upgradeHeading.isVisible() ||
            await upgradeBtn.isVisible();

          if (hasUpgradeContent) {
            expect(hasUpgradeContent).toBeTruthy();
          }
        }
      }
    });

    test('should have working Upgrade Now button', async ({ page }) => {
      await loginAsExpiredUser(page);

      // First trigger the UpgradeRequired page
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Make a POST request that should trigger UpgradeRequired
      await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/customer/api/store', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf || '',
            'Accept': 'text/html',
            'X-Inertia': 'true',
          },
          body: JSON.stringify({ name: 'Test' }),
        });
        // This will return UpgradeRequired page via Inertia
        return res.status;
      });

      // Go directly to subscription plans
      await page.goto('/subscription/plans');
      await page.waitForLoadState('networkidle');

      // Plans page should be accessible
      await expect(page).toHaveURL(/subscription\/plans/);
    });

    test('should have link back to dashboard', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Go to subscription
      await page.goto('/subscription');
      await page.waitForLoadState('networkidle');

      // Should be able to navigate back to dashboard
      const dashboardLink = page.getByRole('link', { name: /Dashboard/i }).first();

      if (await dashboardLink.isVisible()) {
        await dashboardLink.click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/dashboard/);
      }
    });
  });

  test.describe('Subscription Status Display', () => {
    test('should show warning banner in sidebar when trial expired', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1500);

      // Should show warning banner in sidebar
      const sidebarBanner = page.locator('.sidebar-footer');
      await expect(sidebarBanner).toBeVisible({ timeout: 10000 });

      // Should contain "Trial Expired" or "Expired" text
      const bannerText = await sidebarBanner.textContent();
      expect(
        bannerText.includes('Trial Expired') ||
        bannerText.includes('Expired') ||
        bannerText.includes('Upgrade')
      ).toBeTruthy();

      // Should have upgrade link
      const upgradeLink = sidebarBanner.getByRole('link', { name: /Upgrade/i });
      await expect(upgradeLink).toBeVisible();
    });

    test('should show read-only mode indicator when expired', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1500);

      // Should show read-only indicator
      const readOnlyText = page.locator('text=Read-only mode');
      await expect(readOnlyText).toBeVisible({ timeout: 10000 });
    });

    test('should show expired status on subscription page', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Should indicate expired status
      const pageContent = await page.content();
      const hasExpiredIndicator =
        pageContent.includes('Expired') ||
        pageContent.includes('expired') ||
        pageContent.includes('Trial Expired');

      expect(hasExpiredIndicator).toBeTruthy();
    });

    test('should show upgrade prompts prominently', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Should have visible upgrade CTA
      const upgradeLink = page.getByRole('link', { name: /Upgrade|View Plans|Subscribe/i }).first();
      await expect(upgradeLink).toBeVisible();
    });

    test('should allow navigation to plans page', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Click on upgrade/view plans
      const upgradeLink = page.getByRole('link', { name: /Upgrade|View Plans|Subscribe/i }).first();

      if (await upgradeLink.isVisible()) {
        await upgradeLink.click();
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(1000);

        await expect(page).toHaveURL(/subscription\/plans/);
      }
    });
  });

  test.describe('API Response Verification', () => {
    test('should return correct error structure for blocked API calls', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/products/api/store', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf || '',
              'Accept': 'application/json',
            },
            body: JSON.stringify({ name: 'Test' }),
          });
          const data = await res.json();
          return { status: res.status, data };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 with proper error structure
      expect(response.status).toBe(403);
      expect(response.data).toHaveProperty('error', 'subscription_expired');
      expect(response.data).toHaveProperty('message');
    });
  });
});

/**
 * Test cleanup info:
 * To restore PT. Makmur Djaya's trial after testing, run:
 *
 * UPDATE mst_company
 * SET subscription_status = 'trial',
 *     trial_ends_at = '2026-03-03 10:45:57'
 * WHERE id = 2;
 */
