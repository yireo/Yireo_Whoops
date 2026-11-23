import {test, expect, APIRequestContext, Page} from '@playwright/test';

const exceptionPath = '/whoops_test/index/exception';
const errorPath = '/whoops_test/index/error';
const jsonHeaders = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'};

async function getMode(request: APIRequestContext): Promise<string> {
    const response = await request.get('/whoops_test/index/mode');
    expect(response.ok(), 'Fixture module Yireo_WhoopsTestFixture is installed').toBeTruthy();
    return (await response.json()).mode;
}

async function expectNoWhoopsPage(page: Page, message: string) {
    await expect(page.locator('.Whoops')).toHaveCount(0);
    expect(await page.content()).not.toContain(message);
}

test.describe('Whoops', function () {
    test('shows the Whoops page for an exception in developer mode only', async function ({page}) {
        const mode = await getMode(page.request);
        const response = await page.goto(exceptionPath);
        expect(response?.status()).toBeGreaterThanOrEqual(500);

        if (mode !== 'developer') {
            await expectNoWhoopsPage(page, 'Whoops fixture exception');
            return;
        }

        await expect(page.locator('.Whoops .exception')).toBeVisible();
        await expect(page.locator('.exc-title')).toContainText('RuntimeException');
        await expect(page.locator('.exc-message')).toContainText('Whoops fixture exception');
    });

    test('shows the original PHP error in developer mode only', async function ({page}) {
        const mode = await getMode(page.request);
        const response = await page.goto(errorPath);
        expect(response?.status()).toBeGreaterThanOrEqual(500);

        if (mode !== 'developer') {
            await expectNoWhoopsPage(page, 'Whoops fixture error');
            return;
        }

        await expect(page.locator('.Whoops .exception')).toBeVisible();
        await expect(page.locator('.exc-title-primary')).toHaveText('Error');
        await expect(page.locator('.exc-message')).toContainText('Whoops fixture error');
    });

    test('returns JSON for AJAX requests in developer mode only', async function ({page}) {
        const mode = await getMode(page.request);
        const response = await page.request.get(exceptionPath, {headers: jsonHeaders});
        expect(response.status()).toBeGreaterThanOrEqual(500);
        const body = await response.text();

        if (mode !== 'developer') {
            expect(body).not.toContain('Whoops fixture exception');
            return;
        }

        expect(response.headers()['content-type']).toContain('application/json');
        const data = JSON.parse(body);
        expect(data.error.type).toBe('RuntimeException');
        expect(data.error.message).toBe('Whoops fixture exception');
        expect(Array.isArray(data.error.trace)).toBeTruthy();
    });

    test('hides cookies and credentials on the Whoops page in developer mode', async function ({page, context}) {
        const mode = await getMode(page.request);
        test.skip(mode !== 'developer', 'Whoops is only used in developer mode');

        const baseUrl = new URL(test.info().project.use.baseURL as string);
        await context.addCookies([{name: 'PHPSESSID', value: 'whoopssecretsession', url: baseUrl.origin}]);
        await page.setExtraHTTPHeaders({'Authorization': 'Bearer whoopssecrettoken'});
        await page.goto(exceptionPath);

        await expect(page.locator('.Whoops .exception')).toBeVisible();
        const content = await page.content();
        expect(content).not.toContain('whoopssecretsession');
        expect(content).not.toContain('whoopssecrettoken');
    });
});
