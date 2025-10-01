import { test, expect } from '@playwright/test';

test('server responds at /', async ({ page }) => {
  const res = await page.goto('/');
  expect(res, 'no response from / (server not running?)').not.toBeNull();
  const status = res!.status();
  expect([200, 301, 302]).toContain(status);
  // Optionnel: vérifie un bout de ton HTML racine (logo, titre, etc.)
  await expect(page.locator('body')).toContainText(/Prestations|Accueil|Bienvenue/i);
});

test('router serves /admin/login (not 404)', async ({ page }) => {
  const res = await page.goto('/admin/login');
  expect(res, 'no response from /admin/login').not.toBeNull();
  const status = res!.status();
  // Accepte 200 (page servie) ou 302/301 (redir vers login canonique)
  expect([200, 301, 302]).toContain(status);

  // Si 200, on doit voir ton formulaire:
  if (status === 200) {
    await expect(page.getByRole('button', { name: /Se connecter/i })).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
  }
});
