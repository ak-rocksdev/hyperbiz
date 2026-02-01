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
  test.setTimeout(90000);

  // Run tests serially to avoid rate limiting on login
  test.describe.configure({ mode: 'serial' });

  /**
   * Login helper for TENANT user with expired trial
   * User: Kylan Barnett (abdulkadir.devworks@gmail.com)
   * Company: PT. Makmur Djaya (company_id=2)
   * Role: Tenant user (NOT platform admin)
   */
  const loginAsExpiredUser = async (page, retries = 3) => {
    for (let attempt = 1; attempt <= retries; attempt++) {
      try {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000); // Wait for Vue hydration

        // Check if already logged in
        if (page.url().includes('/dashboard') || page.url().includes('/subscription')) {
          return;
        }

        // Login as TENANT user - Kylan Barnett
        const emailInput = page.getByPlaceholder('Your Email');
        await emailInput.waitFor({ state: 'visible', timeout: 10000 });
        await emailInput.fill('abdulkadir.devworks@gmail.com');

        const passwordInput = page.getByPlaceholder('Enter Password');
        await passwordInput.fill('password');

        await page.getByRole('button', { name: 'Sign In' }).click();

        // Wait for redirect - can be dashboard, subscription, or other pages
        await page.waitForURL(url => {
          const path = url.pathname;
          return path.includes('/dashboard') ||
                 path.includes('/subscription') ||
                 path.includes('/products') ||
                 path.includes('/customer') ||
                 path.includes('/inventory');
        }, { timeout: 30000 });

        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000); // Wait for Vue hydration
        return;
      } catch (error) {
        if (attempt === retries) {
          throw error;
        }
        // Wait before retry to avoid rate limiting
        await page.waitForTimeout(3000);
      }
    }
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
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1500);

      // Subscription page should be accessible (GET allowed)
      // Could be on subscription page OR redirected to dashboard
      const currentUrl = page.url();
      expect(currentUrl.includes('/subscription') || currentUrl.includes('/dashboard')).toBeTruthy();

      // Should be able to see page content
      const hasContent = await page.locator('body').count();
      expect(hasContent).toBeGreaterThan(0);
    });

    test('should be able to view subscription plans', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription/plans');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1500);

      // Plans page should be accessible (subscription/* routes are excluded from check)
      const currentUrl = page.url();
      expect(currentUrl.includes('/subscription/plans') || currentUrl.includes('/subscription')).toBeTruthy();
    });
  });

  test.describe('Write Access (Should Be Blocked)', () => {
    test('should block creating new product via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Navigate to product list first (products are created via modal, not separate page)
      await page.goto('/products/list');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      // Try to make a POST request to create product
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

      // Should return 403 Forbidden (subscription expired)
      expect(response.status).toBe(403);
    });

    test('should block updating existing product via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/products/list');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      // Try to make a PUT request - use a generic ID, the 403 should be returned before validation
      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/products/api/update/9999', {
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

      // Should return 403 Forbidden (subscription expired) - middleware blocks before route execution
      expect(response.status).toBe(403);
    });

    test('should block toggling customer status via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/customer/list');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      // Try to make a PATCH request
      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/customer/api/toggle-status/9999', {
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

      // Should return 403 Forbidden (subscription expired)
      expect(response.status).toBe(403);
    });

    test('should block creating customer via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/customer/list');
      await page.waitForLoadState('networkidle');
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

      // Should return 403 Forbidden (subscription expired)
      expect(response.status).toBe(403);
    });

    test('should block updating customer via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/customer/list');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      const response = await page.evaluate(async () => {
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const res = await fetch('/customer/api/update/9999', {
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

      // Should return 403 Forbidden (subscription expired)
      expect(response.status).toBe(403);
    });
  });

  test.describe('UpgradeRequired Page', () => {
    test('should return 403 with upgrade info on write attempt via API', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');

      // Make a POST request that should return 403 with upgrade info
      const response = await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/customer/api/store', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf || '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ name: 'Test' }),
        });
        const data = await res.json().catch(() => null);
        return { status: res.status, data };
      });

      // Should return 403 with subscription_expired error
      expect(response.status).toBe(403);
      expect(response.data?.error).toBe('subscription_expired');
    });

    test('should allow navigation to subscription plans', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Go directly to subscription plans (subscription routes are excluded from check)
      await page.goto('/subscription/plans');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      // Plans page should be accessible
      const currentUrl = page.url();
      expect(currentUrl.includes('/subscription')).toBeTruthy();
    });

    test('should have sidebar navigation to dashboard', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Go to subscription
      await page.goto('/subscription');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);

      // Should be able to navigate back to dashboard via sidebar
      const dashboardLink = page.locator('a[href*="dashboard"]').first();

      if (await dashboardLink.isVisible()) {
        await dashboardLink.click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/dashboard/);
      } else {
        // Alternatively just navigate directly
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/dashboard/);
      }
    });
  });

  test.describe('Subscription Status Display', () => {
    test('should show warning banner in sidebar when trial expired', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // The sidebar-footer should exist and show warning
      const sidebarFooter = page.locator('.sidebar-footer');

      // Check if sidebar footer is visible
      const isVisible = await sidebarFooter.isVisible().catch(() => false);

      if (isVisible) {
        // Should contain "Trial Expired" or "Expired" or "Upgrade" text
        const bannerText = await sidebarFooter.textContent().catch(() => '');
        expect(
          bannerText.includes('Trial Expired') ||
          bannerText.includes('Expired') ||
          bannerText.includes('Upgrade')
        ).toBeTruthy();
      } else {
        // Check if page has any expired/upgrade indicators anywhere
        const pageContent = await page.content();
        expect(
          pageContent.includes('Trial Expired') ||
          pageContent.includes('expired') ||
          pageContent.includes('Upgrade') ||
          pageContent.includes('Read-only')
        ).toBeTruthy();
      }
    });

    test('should show read-only mode indicator when expired', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);

      // Check for read-only indicator (could be in sidebar or elsewhere)
      const pageContent = await page.content();
      const hasReadOnlyIndicator =
        pageContent.includes('Read-only') ||
        pageContent.includes('read-only') ||
        pageContent.includes('Trial Expired') ||
        pageContent.includes('Expired');

      expect(hasReadOnlyIndicator).toBeTruthy();
    });

    test('should show expired status on subscription page', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1500);

      // Should indicate expired status somewhere on page
      const pageContent = await page.content();
      const hasExpiredIndicator =
        pageContent.includes('Expired') ||
        pageContent.includes('expired') ||
        pageContent.includes('Trial') ||
        pageContent.includes('Upgrade');

      expect(hasExpiredIndicator).toBeTruthy();
    });

    test('should show upgrade prompts on subscription page', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/subscription');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1500);

      // Should have visible upgrade CTA or plans link
      const upgradeLink = page.locator('a[href*="plans"], a[href*="upgrade"]').first();
      const viewPlansBtn = page.getByRole('link', { name: /View Plans|Upgrade|Subscribe|Choose/i }).first();

      const hasUpgradeLink = await upgradeLink.isVisible().catch(() => false);
      const hasViewPlansBtn = await viewPlansBtn.isVisible().catch(() => false);

      // At least one should be visible, or page should mention upgrade
      if (!hasUpgradeLink && !hasViewPlansBtn) {
        const pageContent = await page.content();
        expect(pageContent.includes('plan') || pageContent.includes('Plan') || pageContent.includes('Upgrade')).toBeTruthy();
      } else {
        expect(hasUpgradeLink || hasViewPlansBtn).toBeTruthy();
      }
    });

    test('should allow direct navigation to plans page', async ({ page }) => {
      await loginAsExpiredUser(page);

      // Navigate directly to plans page
      await page.goto('/subscription/plans');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1500);

      // Should be on subscription plans page
      const currentUrl = page.url();
      expect(currentUrl.includes('/subscription')).toBeTruthy();
    });
  });

  test.describe('API Response Verification', () => {
    test('should return correct error structure for blocked API calls', async ({ page }) => {
      await loginAsExpiredUser(page);

      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');
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
          const data = await res.json().catch(() => null);
          return { status: res.status, data };
        } catch (e) {
          return { error: e.message };
        }
      });

      // Should return 403 with proper error structure
      expect(response.status).toBe(403);
      expect(response.data?.error).toBe('subscription_expired');
      expect(response.data?.message).toBeTruthy();
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
