import { test, expect, type Page } from "@playwright/test";
import { readFile } from "node:fs/promises";
async function register(page: Page, email: string) {
    await page.goto("/masuk?mode=daftar");
    await page.getByLabel("Nama lengkap").fill("Pengguna Pengujian");
    await page.getByLabel("Email", { exact: true }).fill(email);
    await page
        .getByLabel("Kata sandi", { exact: true })
        .fill(process.env.E2E_PASSWORD!);
    await page.getByLabel("Ulangi kata sandi").fill(process.env.E2E_PASSWORD!);
    await page
        .getByRole("button", { name: "Daftar ruang kerja", exact: true })
        .click();
    await expect(page).toHaveURL(/audits$/);
}
test("Audit HTTP publik nyata, penolakan privat, filter, CSV dan privasi", async ({
    page,
    browser,
}) => {
    const errors: string[] = [];
    page.on("pageerror", (e) => errors.push(e.message));
    await register(page, "pemilik@example.test");
    await page.goto("/");
    await page.getByLabel("URL website").fill("http://127.0.0.1");
    await page.getByLabel("Batas URL yang diperiksa").selectOption("5");
    await page.getByRole("button", { name: "Jalankan audit ↗" }).click();
    await expect(page.getByRole("alert")).toContainText("privat");
    await page
        .getByLabel("URL website")
        .fill("https://httpbingo.org/links/3/0");
    await page.getByRole("button", { name: "Jalankan audit ↗" }).click();
    await expect(page).toHaveURL(/audits\/\d+$/, { timeout: 40000 });
    const report = new URL(page.url()).pathname;
    await expect(page.locator(".result-row")).toHaveCount(3);
    await expect(page.locator(".result-row").first()).toContainText("200");
    await expect(page.locator(".result-row").first()).toContainText(
        "https://httpbingo.org/links/3/0",
    );
    await page.locator("details summary").first().click();
    await expect(page.locator("details[open] li").first()).toContainText("200");
    await page.getByLabel("Kategori hasil").selectOption("ok");
    await page.getByRole("button", { name: "Terapkan filter" }).click();
    await expect(page.locator(".result-row")).not.toHaveCount(0);
    const downloadPromise = page.waitForEvent("download");
    await page.getByRole("link", { name: "Unduh CSV ↓" }).click();
    const download = await downloadPromise;
    await download.saveAs("test-results/audit.csv");
    const csv = await readFile("test-results/audit.csv", "utf8");
    expect(csv).toContain("URL akhir");
    expect(csv).toContain("https://httpbingo.org/links/3/0");
    expect(csv).toContain("200");
    for (const width of [1440, 768, 390]) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto(report);
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth + 1,
            ),
        ).toBe(true);
        await page.screenshot({
            path: "test-results/report-" + width + ".png",
            fullPage: true,
        });
        await page.goto("/");
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth + 1,
            ),
        ).toBe(true);
        await page.screenshot({
            path: "test-results/home-" + width + ".png",
            fullPage: true,
        });
    }
    await page.goto("/");
    await page
        .getByLabel("URL website")
        .fill("https://httpbingo.org/status/404");
    await page.getByRole("button", { name: "Jalankan audit ↗" }).click();
    await expect(
        page.locator(".result-row").first().locator("strong"),
    ).toHaveText("404");
    await expect(
        page.locator(".result-row").first().locator(".badge"),
    ).toHaveText("HTTP 4xx");
    const secondContext = await browser.newContext();
    const second = await secondContext.newPage();
    await register(second, "kedua@example.test");
    expect((await second.goto(report))?.status()).toBe(403);
    expect((await second.request.get(report + "/export")).status()).toBe(403);
    await page.goto("/masuk");
    await expect(page).toHaveURL(/audits$/);
    await expect(page.locator("tbody")).toContainText(
        "https://httpbingo.org/links/3/0",
    );
    expect(errors).toEqual([]);
    await secondContext.close();
});
