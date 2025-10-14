import { test, expect } from '@playwright/test';

const ADMIN_EMAIL = process.env.ADMIN_EMAIL!;
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD!;

// regex qui tolère /admin  OU /admin/ OU /admin/login?redirect=...
const isLoginUrl = (url: string) => /\/admin(\/login)?\/?(\?.*)?$/.test(url);

test.describe('Auth admin', () => {

  test('zone admin protégée', async ({ page }) => {
    await page.goto('/admin');

    // Certains sites affichent direct la page de login sur /admin (pas de 302).
    // On accepte /admin ou /admin/login tant qu’on VOIT le formulaire de login.
    expect(isLoginUrl(page.url())).toBeTruthy();

    // on vérifie qu’on voit un titre OU au moins le bouton et les inputs:
    await expect(page.getByRole('button', { name: /Se connecter/i })).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('login invalide -> reste sur login + montre une erreur', async ({ page }) => {
    await page.goto('/admin/login');

    await page.fill('input[name="email"]', ADMIN_EMAIL);
    await page.fill('input[name="password"]', 'MauvaisMotDePasse!');
    await page.getByRole('button', { name: /Se connecter/i }).click();

    // on reste sur login (ou revient dessus) :
    await page.waitForLoadState('networkidle');
    expect(isLoginUrl(page.url())).toBeTruthy();

    // Message d’erreur tolérant (la classe .error existe déjà) :
    const err = page.locator('p.error');
    await expect(err).toBeVisible();
    await expect(err).toContainText(/invalide|incorrect|erreur|échoué/i);
  });

  test('login OK -> redirige vers zone admin (plus login)', async ({ page }) => {
    await page.goto('/admin/login');

    await page.fill('input[name="email"]', ADMIN_EMAIL);
    await page.fill('input[name="password"]', ADMIN_PASSWORD);
    await page.getByRole('button', { name: /Se connecter/i }).click();

    // On attend de ne plus être sur la page de login
    await page.waitForURL((u) => !/\/admin\/login(\/)?(\?.*)?$/.test(u.toString()));

    // Et on vérifie un petit indicateur “dashboard”
    await expect(page.locator('body')).toContainText(/Admin|Tableau de bord|Bienvenue/i);
  });

});
// POUR LANCER LES TESTS:
// npx playwright test --ui
// OU
// npx playwright test
// OU avec un navigateur précis:
// npx playwright test --project=chromium
// npx playwright test --project=firefox
// npx playwright test --project=webkit
// npx playwright test --project="Mobile Chrome"
// npx playwright test --project="Mobile Safari"
// npx playwright test --project="Microsoft Edge"
// npx playwright test --project="Google Chrome"
// OU avec un test précis:
// npx playwright test tests/e2e/admin.spec.ts
