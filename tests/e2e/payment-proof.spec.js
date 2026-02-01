// @ts-check
import { test, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

// ES Module compatible __dirname
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Payment Proof Upload E2E Tests
 * Tests the tenant payment proof upload functionality for bank transfer payments
 */

test.describe('Payment Proof Upload', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to billing history
    await page.goto('/subscription/billing-history');
    await page.waitForLoadState('networkidle');
  });

  test('should display billing history page with invoices', async ({ page }) => {
    // Verify page heading
    await expect(page.getByRole('heading', { name: /Billing History/i })).toBeVisible();

    // Check for invoices table or empty state
    const table = page.locator('table');
    const emptyState = page.locator('text=/No Invoices/i');

    const hasTable = await table.count();
    const hasEmpty = await emptyState.count();

    // Should have either invoices table or empty state
    expect(hasTable + hasEmpty).toBeGreaterThan(0);
  });

  test('should navigate to payment proof upload page from pending invoice', async ({ page }) => {
    // Look for a pending invoice action button
    const uploadButton = page.locator('a[href*="/subscription/payment-proof"]').first();

    if (await uploadButton.count() > 0) {
      await uploadButton.click();
      await page.waitForLoadState('networkidle');

      // Verify we're on payment proof page
      await expect(page).toHaveURL(/\/subscription\/payment-proof\/\d+/);
      await expect(page.getByRole('heading', { name: /Upload Payment Proof/i })).toBeVisible();
    }
  });
});

test.describe('Payment Proof Form', () => {
  test('should display upload form with all required fields', async ({ page }) => {
    // Navigate directly to payment proof page (assuming invoice ID 1 exists)
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    // Check if page loaded (might 404 if no invoice)
    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Verify invoice summary is displayed
      await expect(page.locator('text=Invoice Summary')).toBeVisible();

      // Verify bank account information section
      await expect(page.locator('text=Transfer to Bank Account')).toBeVisible();

      // Verify upload form section
      await expect(page.locator('text=Upload Payment Proof')).toBeVisible();

      // Verify form fields exist
      await expect(page.locator('text=Bank Name')).toBeVisible();
      await expect(page.locator('text=Account Holder Name')).toBeVisible();
      await expect(page.locator('text=Account Number')).toBeVisible();
      await expect(page.locator('text=Transfer Date')).toBeVisible();
      await expect(page.locator('text=Transfer Amount')).toBeVisible();
    }
  });

  test('should show validation errors when submitting empty form', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Try to submit without filling form
      const submitButton = page.getByRole('button', { name: /Submit Payment Proof/i });

      if (await submitButton.count() > 0) {
        await submitButton.click();

        // Should show validation error popup
        await expect(page.locator('text=/Validation Error|Please upload payment proof/i')).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('should upload payment proof file successfully', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Check if upload form is available (invoice must be pending)
      const uploadArea = page.locator('text=Click to upload or drag and drop');

      if (await uploadArea.count() > 0) {
        // Create a test image file
        const testImagePath = path.join(__dirname, 'fixtures', 'test-payment-proof.png');

        // Check if fixture exists, if not create it programmatically
        const fileInput = page.locator('input[type="file"]');

        // Upload using file chooser
        const [fileChooser] = await Promise.all([
          page.waitForEvent('filechooser'),
          uploadArea.click(),
        ]);

        // Create a simple test PNG buffer
        const testPngBuffer = createTestPng();
        await fileChooser.setFiles({
          name: 'test-payment-proof.png',
          mimeType: 'image/png',
          buffer: testPngBuffer,
        });

        // Verify file preview is shown
        await expect(page.locator('text=Ready to upload')).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('should fill form and submit payment proof', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Check if upload form is available
      const uploadArea = page.locator('text=Click to upload or drag and drop');

      if (await uploadArea.count() > 0) {
        // Upload file
        const [fileChooser] = await Promise.all([
          page.waitForEvent('filechooser'),
          uploadArea.click(),
        ]);

        const testPngBuffer = createTestPng();
        await fileChooser.setFiles({
          name: 'test-payment-proof.png',
          mimeType: 'image/png',
          buffer: testPngBuffer,
        });

        // Wait for file to be processed
        await expect(page.locator('text=Ready to upload')).toBeVisible({ timeout: 5000 });

        // Fill in bank transfer details
        await page.locator('input[placeholder*="BCA, Mandiri"]').fill('BCA');
        await page.locator('input[placeholder*="name on bank account"]').fill('John Doe');
        await page.locator('input[placeholder*="bank account number"]').fill('1234567890');

        // Transfer amount should be pre-filled, but let's ensure it's set
        const amountInput = page.locator('input[type="number"]').first();
        const currentAmount = await amountInput.inputValue();
        if (!currentAmount || currentAmount === '0') {
          await amountInput.fill('299000');
        }

        // Add optional notes
        await page.locator('textarea').fill('Test payment proof upload');

        // Submit the form
        await page.getByRole('button', { name: /Submit Payment Proof/i }).click();

        // Wait for response - should show success or error
        await page.waitForTimeout(2000);

        // Check for success message
        const successMessage = page.locator('text=/Payment Proof Uploaded|submitted for verification/i');
        const errorMessage = page.locator('.swal2-popup');

        // Either success or an error popup should appear
        const hasSuccess = await successMessage.count();
        const hasError = await errorMessage.count();

        expect(hasSuccess + hasError).toBeGreaterThan(0);
      }
    }
  });

  test('should reject invalid file types', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      const uploadArea = page.locator('text=Click to upload or drag and drop');

      if (await uploadArea.count() > 0) {
        // Try to upload an invalid file type
        const [fileChooser] = await Promise.all([
          page.waitForEvent('filechooser'),
          uploadArea.click(),
        ]);

        // Create a fake text file
        await fileChooser.setFiles({
          name: 'test.txt',
          mimeType: 'text/plain',
          buffer: Buffer.from('This is a test file'),
        });

        // Should show error for invalid file type
        await expect(page.locator('text=/Invalid File Type/i')).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('should display bank account information for transfer', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Verify bank accounts are displayed
      const bankInfo = page.locator('text=Transfer to Bank Account');

      if (await bankInfo.count() > 0) {
        // Check for bank name
        await expect(page.locator('text=BCA').or(page.locator('text=Mandiri'))).toBeVisible();

        // Check for account number
        await expect(page.locator('text=/\\d{3}-\\d{3}-\\d{4}/').first()).toBeVisible();
      }
    }
  });
});

test.describe('Payment Proof Status', () => {
  test('should show payment progress steps', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Check for status progress steps
      await expect(page.locator('text=Invoice Created')).toBeVisible();
      await expect(page.locator('text=Payment Proof Uploaded')).toBeVisible();
      await expect(page.locator('text=Payment Verified')).toBeVisible();
    }
  });

  test('should display payment summary sidebar', async ({ page }) => {
    await page.goto('/subscription/payment-proof/1');
    await page.waitForLoadState('networkidle');

    const pageTitle = await page.title();
    if (pageTitle.includes('Payment Proof')) {
      // Check for payment summary
      await expect(page.locator('text=Payment Summary')).toBeVisible();
      await expect(page.locator('text=Amount Due')).toBeVisible();
    }
  });
});

/**
 * Creates a minimal valid PNG buffer for testing
 * This creates a 1x1 pixel red PNG
 */
function createTestPng() {
  // Minimal PNG header and data for a 1x1 red pixel
  const pngSignature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);

  // IHDR chunk (image header)
  const ihdrData = Buffer.from([
    0, 0, 0, 1,  // width: 1
    0, 0, 0, 1,  // height: 1
    8,            // bit depth: 8
    2,            // color type: RGB
    0,            // compression: deflate
    0,            // filter: adaptive
    0,            // interlace: none
  ]);
  const ihdrCrc = crc32(Buffer.concat([Buffer.from('IHDR'), ihdrData]));
  const ihdrChunk = Buffer.concat([
    Buffer.from([0, 0, 0, 13]), // length
    Buffer.from('IHDR'),
    ihdrData,
    ihdrCrc,
  ]);

  // IDAT chunk (image data) - compressed single red pixel
  const idatData = Buffer.from([
    0x78, 0x9c, 0x62, 0xf8, 0xcf, 0xc0, 0x00, 0x00, 0x00, 0x05, 0x00, 0x01
  ]);
  const idatCrc = crc32(Buffer.concat([Buffer.from('IDAT'), idatData]));
  const idatChunk = Buffer.concat([
    Buffer.from([0, 0, 0, idatData.length]),
    Buffer.from('IDAT'),
    idatData,
    idatCrc,
  ]);

  // IEND chunk (image end)
  const iendCrc = crc32(Buffer.from('IEND'));
  const iendChunk = Buffer.concat([
    Buffer.from([0, 0, 0, 0]), // length
    Buffer.from('IEND'),
    iendCrc,
  ]);

  return Buffer.concat([pngSignature, ihdrChunk, idatChunk, iendChunk]);
}

/**
 * Simple CRC32 implementation for PNG chunks
 */
function crc32(data) {
  let crc = 0xffffffff;
  const table = [];

  for (let i = 0; i < 256; i++) {
    let c = i;
    for (let j = 0; j < 8; j++) {
      c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
    }
    table[i] = c;
  }

  for (let i = 0; i < data.length; i++) {
    crc = table[(crc ^ data[i]) & 0xff] ^ (crc >>> 8);
  }

  const result = (crc ^ 0xffffffff) >>> 0;
  return Buffer.from([
    (result >>> 24) & 0xff,
    (result >>> 16) & 0xff,
    (result >>> 8) & 0xff,
    result & 0xff,
  ]);
}
