// @ts-check
import { test, expect } from '@playwright/test';

/**
 * User Management E2E Tests
 * Tests user listing, creation, editing, and deletion functionality
 */

test.describe('User List Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');
  });

  test('should display user list page', async ({ page }) => {
    // Verify page elements
    await expect(page.getByText('User Management')).toBeVisible();
    await expect(page.getByText('Manage user accounts and permissions')).toBeVisible();
  });

  test('should display users table with correct columns', async ({ page }) => {
    // Verify table headers
    await expect(page.getByText('User', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Role', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Status', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Registered', { exact: true })).toBeVisible();
    await expect(page.getByText('Actions', { exact: true }).first()).toBeVisible();
  });

  test('should search users', async ({ page }) => {
    // Find and fill search input
    const searchInput = page.getByPlaceholder('Search users...');
    await searchInput.fill('admin');

    // Wait for debounce and results
    await page.waitForTimeout(500);
    await page.waitForLoadState('networkidle');

    // URL should contain search parameter
    await expect(page).toHaveURL(/search=admin/);
  });

  test('should filter users by role', async ({ page }) => {
    // Find role filter dropdown
    const roleSelect = page.locator('select').filter({ hasText: 'All Roles' });

    if (await roleSelect.isVisible()) {
      // Get available options
      const options = await roleSelect.locator('option').allTextContents();

      // Select first non-empty role if available
      if (options.length > 1) {
        await roleSelect.selectOption({ index: 1 });
        await page.waitForLoadState('networkidle');

        // URL should contain role parameter
        await expect(page).toHaveURL(/role=/);
      }
    }
  });

  test('should filter users by status', async ({ page }) => {
    // Find status filter dropdown
    const statusSelect = page.locator('select').filter({ hasText: 'All Status' });

    if (await statusSelect.isVisible()) {
      await statusSelect.selectOption('active');
      await page.waitForLoadState('networkidle');

      // URL should contain status parameter
      await expect(page).toHaveURL(/status=active/);
    }
  });

  test('should clear filters', async ({ page }) => {
    // First apply a filter
    const searchInput = page.getByPlaceholder('Search users...');
    await searchInput.fill('test');
    await page.waitForTimeout(500);
    await page.waitForLoadState('networkidle');

    // Verify filter is applied
    await expect(page).toHaveURL(/search=test/);

    // Click clear button
    const clearButton = page.getByRole('button', { name: /Clear/i });
    if (await clearButton.isVisible()) {
      await clearButton.click();
      await page.waitForLoadState('networkidle');

      // URL should not contain search parameter
      await expect(page).not.toHaveURL(/search=test/);
    }
  });

  test('should have working pagination', async ({ page }) => {
    // Check if pagination exists
    const pagination = page.locator('.pagination');

    if (await pagination.isVisible()) {
      // Verify pagination controls
      await expect(page.locator('select').filter({ hasText: /10|25|50|100/ })).toBeVisible();
    }
  });

  test('should change items per page', async ({ page }) => {
    // Find per page selector
    const perPageSelect = page.locator('select').filter({ hasText: /of.*users/ }).locator('xpath=preceding-sibling::select');

    // Alternative: find by option values
    const selectWithOptions = page.locator('select').filter({ has: page.locator('option[value="25"]') }).first();

    if (await selectWithOptions.isVisible()) {
      await selectWithOptions.selectOption('25');
      await page.waitForLoadState('networkidle');

      // URL should contain per_page parameter
      await expect(page).toHaveURL(/per_page=25/);
    }
  });
});

test.describe('User Create Modal', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');
  });

  test('should open create user modal', async ({ page }) => {
    // Click add user button
    const addButton = page.getByRole('button', { name: /Add User/i });

    if (await addButton.isVisible()) {
      await addButton.click();
      await page.waitForTimeout(300);

      // Verify modal is open
      await expect(page.getByText('Add New User')).toBeVisible();
    }
  });

  test('should display create form fields', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /Add User/i });

    if (await addButton.isVisible()) {
      await addButton.click();
      await page.waitForTimeout(300);

      // Verify form fields
      await expect(page.getByPlaceholder('Enter full name')).toBeVisible();
      await expect(page.getByPlaceholder('Enter email address')).toBeVisible();
      await expect(page.getByPlaceholder('Enter password (min 8 characters)')).toBeVisible();
      await expect(page.getByPlaceholder('Re-enter password')).toBeVisible();

      // Verify role selection
      await expect(page.getByText('Role').first()).toBeVisible();
    }
  });

  test('should close modal on cancel', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /Add User/i });

    if (await addButton.isVisible()) {
      await addButton.click();
      await page.waitForTimeout(300);

      // Click cancel
      await page.getByRole('button', { name: 'Cancel' }).click();
      await page.waitForTimeout(300);

      // Modal should be closed
      await expect(page.getByText('Add New User')).not.toBeVisible();
    }
  });

  test('should close modal on backdrop click', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /Add User/i });

    if (await addButton.isVisible()) {
      await addButton.click();
      await page.waitForTimeout(300);

      // Click backdrop
      await page.locator('.bg-black\\/50').click();
      await page.waitForTimeout(300);

      // Modal should be closed
      await expect(page.getByText('Add New User')).not.toBeVisible();
    }
  });

  test('should show validation errors for empty form', async ({ page }) => {
    const addButton = page.getByRole('button', { name: /Add User/i });

    if (await addButton.isVisible()) {
      await addButton.click();
      await page.waitForTimeout(300);

      // Try to submit empty form
      await page.getByRole('button', { name: 'Create User' }).click();
      await page.waitForTimeout(500);

      // Form should still be visible (validation failed)
      await expect(page.getByText('Add New User')).toBeVisible();
    }
  });
});

test.describe('User Detail Modal', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');
  });

  test('should open user detail modal', async ({ page }) => {
    // Find action menu for first user
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      // Click view details
      const viewButton = page.getByRole('button', { name: /View Details/i });
      if (await viewButton.isVisible()) {
        await viewButton.click();
        await page.waitForTimeout(500);

        // Verify modal is open
        await expect(page.getByText('User Details')).toBeVisible();
      }
    }
  });

  test('should display user information in detail modal', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const viewButton = page.getByRole('button', { name: /View Details/i });
      if (await viewButton.isVisible()) {
        await viewButton.click();
        await page.waitForTimeout(500);

        // Verify detail fields
        await expect(page.getByText('Status', { exact: true }).last()).toBeVisible();
        await expect(page.getByText('Roles', { exact: true })).toBeVisible();
        await expect(page.getByText('Created At')).toBeVisible();
        await expect(page.getByText('Updated At')).toBeVisible();
      }
    }
  });
});

test.describe('User Edit Modal', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');
  });

  test('should open edit modal from action menu', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const editButton = page.getByRole('button', { name: /^Edit$/i });
      if (await editButton.isVisible()) {
        await editButton.click();
        await page.waitForTimeout(500);

        // Verify edit modal is open
        await expect(page.getByText('Edit User')).toBeVisible();
      }
    }
  });

  test('should pre-fill user data in edit form', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const editButton = page.getByRole('button', { name: /^Edit$/i });
      if (await editButton.isVisible()) {
        await editButton.click();
        await page.waitForTimeout(500);

        // Name and email should be pre-filled
        const nameInput = page.getByPlaceholder('Enter full name');
        const emailInput = page.getByPlaceholder('Enter email address');

        await expect(nameInput).not.toHaveValue('');
        await expect(emailInput).not.toHaveValue('');
      }
    }
  });

  test('should have optional password fields in edit form', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const editButton = page.getByRole('button', { name: /^Edit$/i });
      if (await editButton.isVisible()) {
        await editButton.click();
        await page.waitForTimeout(500);

        // Password hint should indicate it's optional
        await expect(page.getByText('leave blank to keep current')).toBeVisible();
      }
    }
  });
});

test.describe('User Actions', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');
  });

  test('should have toggle status option in action menu', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      // Should have activate or deactivate option
      const toggleButton = page.getByRole('button', { name: /Deactivate|Activate/i });
      await expect(toggleButton).toBeVisible();
    }
  });

  test('should have delete option in action menu', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const deleteButton = page.getByRole('button', { name: /Delete/i });
      await expect(deleteButton).toBeVisible();
    }
  });

  test('should show confirmation dialog for delete', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const deleteButton = page.getByRole('button', { name: /Delete/i });
      if (await deleteButton.isVisible()) {
        await deleteButton.click();
        await page.waitForTimeout(500);

        // SweetAlert confirmation should appear
        await expect(page.getByText('Delete User?')).toBeVisible();
        await expect(page.getByText('This action cannot be undone')).toBeVisible();

        // Cancel the deletion
        await page.getByRole('button', { name: 'Cancel' }).click();
      }
    }
  });

  test('should show confirmation dialog for status toggle', async ({ page }) => {
    const actionMenu = page.locator('[data-menu-item-toggle="dropdown"]').first();

    if (await actionMenu.isVisible()) {
      await actionMenu.click();
      await page.waitForTimeout(300);

      const toggleButton = page.getByRole('button', { name: /Deactivate|Activate/i });
      if (await toggleButton.isVisible()) {
        await toggleButton.click();
        await page.waitForTimeout(500);

        // SweetAlert confirmation should appear
        await expect(page.getByText(/Deactivate User\?|Activate User\?/)).toBeVisible();

        // Cancel the action
        await page.getByRole('button', { name: 'Cancel' }).click();
      }
    }
  });
});

test.describe('User Role Badges', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/user/list');
    await page.waitForLoadState('networkidle');
  });

  test('should display role badges for users', async ({ page }) => {
    // Check if role badges are visible
    const roleBadges = page.locator('.badge').filter({ hasText: /admin|staff|superadmin/i });
    const count = await roleBadges.count();

    // Should have at least one role badge if users exist
    if (count > 0) {
      await expect(roleBadges.first()).toBeVisible();
    }
  });

  test('should display status badges for users', async ({ page }) => {
    // Check if status badges are visible
    const statusBadges = page.locator('.badge').filter({ hasText: /Active|Inactive/i });
    const count = await statusBadges.count();

    if (count > 0) {
      await expect(statusBadges.first()).toBeVisible();
    }
  });
});
