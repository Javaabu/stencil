import { defineConfig } from "vite"

/**
 * Builds the prebuilt assets in dist/ for apps that don't build javaabu/stencil with
 * their own Vite. javaabu/manifest finds them through dist/.vite/manifest.json, keyed
 * by these entry paths. dist/ is committed; CI fails when it's out of date.
 *
 * The React renderer isn't prebuilt: apps build it from resources/js with their Vite.
 */
export const entries = ["resources/js/blade/stencil.ts", "resources/css/stencil.css"]

export default defineConfig({
  // Relative URLs, as javaabu/manifest serves dist/ from a sub-path.
  base: "./",
  publicDir: false,
  build: {
    outDir: "dist",
    emptyOutDir: true,
    manifest: true,
    target: "es2020",
    sourcemap: false,
    reportCompressedSize: false,
    rollupOptions: { input: entries },
  },
})
