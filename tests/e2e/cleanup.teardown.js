// @ts-check
import { test as teardown } from '@playwright/test';

/**
 * Cleanup Teardown
 * Runs after all tests to clean up any test data
 */
teardown('cleanup test data', async ({ }) => {
  // Add any cleanup logic here
  // For example: delete test-created records via API
  console.log('Cleanup completed');
});
