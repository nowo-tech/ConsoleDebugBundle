import { test, expect, type Page } from '@playwright/test';

/**
 * REQ-DEMO-013 — happy path of the Console Debug demo: the pages render inside the demo platform shell, the login form
 * rejects bad credentials, the debugger account gets the console script and the viewer account does not.
 */
async function signIn(page: Page, user: string, pass: string): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="_username"]', user);
  await page.fill('input[name="_password"]', pass);
  await page.click('button[type="submit"]');
}

test.describe('Console Debug demo', () => {
  test('home renders inside the demo platform shell with the use-case table', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    await expect(page.getByTestId('demo-platform')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Console Debug Bundle', level: 1 })).toBeVisible();
    await expect(page.getByTestId('demo-use-cases')).toContainText('Full Twig context');
  });

  test('the Examples page asks for a login', async ({ page }) => {
    await page.goto('/debug');
    await expect(page).toHaveURL(/\/login$/);
  });

  test('bad credentials show an error flash', async ({ page }) => {
    await signIn(page, 'debugger', 'wrong');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('.nowo-ui-flashes')).toContainText(/invalid credentials/i);
  });

  test('the debugger account gets the console script', async ({ page }) => {
    await signIn(page, 'debugger', 'debug');
    await expect(page).toHaveURL(/\/debug$/);
    await expect(page.getByTestId('demo-session')).toContainText('Expect 11 console groups');
    await expect(page.locator('script[data-nowo-console-debug]')).toHaveCount(1);
  });

  test('the viewer account has the gate closed', async ({ page }) => {
    await signIn(page, 'viewer', 'viewer');
    await expect(page).toHaveURL(/\/debug$/);
    await expect(page.getByTestId('demo-session')).toContainText('Gate closed');
    await expect(page.locator('script[data-nowo-console-debug]')).toHaveCount(0);
  });
});
