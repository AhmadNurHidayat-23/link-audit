import { spawnSync } from "node:child_process";
import { randomBytes } from "node:crypto";
import { existsSync, writeFileSync, unlinkSync, mkdirSync } from "node:fs";
import { resolve } from "node:path";
const db = resolve("database/.browser-test.sqlite");
// All browser writes go to this project-owned disposable database.
if (existsSync(db)) unlinkSync(db);
writeFileSync(db, "");
mkdirSync("storage/framework/sessions-browser", { recursive: true });
const env = {
    ...process.env,
    APP_ENV: "e2e",
    APP_DEBUG: "false",
    APP_URL: "http://127.0.0.1:8112",
    APP_KEY: "base64:" + randomBytes(32).toString("base64"),
    DB_CONNECTION: "sqlite",
    DB_DATABASE: db,
    SESSION_DRIVER: "file",
    SESSION_COOKIE: "link-audit-e2e",
    SESSION_FILES: resolve("storage/framework/sessions-browser"),
    CACHE_STORE: "array",
    MAIL_MAILER: "array",
    E2E_PASSWORD: randomBytes(24).toString("base64url"),
};
function run(cmd, args) {
    const r = spawnSync(cmd, args, { stdio: "inherit", env, shell: false });
    if (r.status !== 0) process.exit(r.status || 1);
}
run("php", ["artisan", "migrate", "--seed", "--force", "--no-ansi"]);
run(process.execPath, ["node_modules/@playwright/test/cli.js", "test"]);
