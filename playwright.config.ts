import {defineConfig} from "@playwright/test";
import * as dotenv from "dotenv";

dotenv.config({path: ".env.local"});

export default defineConfig({
    testDir: "./tests",
    use: {
        baseURL: "http://localhost:8000",
        headless: true,
    },
    webServer: {
        command: "php -S localhost:8000 -t public public/router.php",
        port: 8000,
        reuseExistingServer: true,
        timeout: 120_000,
    },
});
