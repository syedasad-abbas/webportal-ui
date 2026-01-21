import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite'
import collectModuleAssetsPaths from "./vite-module-loader";

let paths = [
    'resources/css/app.css',
    'resources/js/app.js',
];

const devServerUrl = process.env.VITE_DEV_SERVER_URL || 'http://vm2.technonies.com:5173';
let devServerHost = 'vm2.technonies.com';
let devServerPort = Number(process.env.VITE_DEV_SERVER_PORT) || 5173;
let devServerClientPort = devServerPort;
let devServerProtocol = 'http:';
try {
    const parsed = new URL(devServerUrl);
    devServerHost = parsed.hostname || devServerHost;
    devServerClientPort = parsed.port ? Number(parsed.port) : devServerClientPort;
    devServerProtocol = parsed.protocol || devServerProtocol;
} catch {
    // Keep defaults if URL parsing fails.
}

// Precompute all paths synchronously.
let allPaths = [];
(async () => {
    allPaths = await collectModuleAssetsPaths(paths, 'Modules');
})();

if (allPaths.length === 0) {
    allPaths = paths;
}

export default defineConfig({
    plugins: [
        laravel({
            input: allPaths,
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: devServerPort,
        strictPort: true,
        origin: devServerUrl,
        allowedHosts: 'all',
        cors: true,
        hmr: {
            host: devServerHost,
            port: devServerPort,
            clientPort: devServerClientPort,
            protocol: devServerProtocol.replace(':', '')
        }
    },
    esbuild: {
        jsx: 'automatic',
        // drop: ['console', 'debugger'],
    },
});
