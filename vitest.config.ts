import { defineConfig } from "vitest/config"

export default defineConfig({
  resolve: {
    alias: { "@javaabu/stencil": new URL("./resources/js", import.meta.url).pathname },
  },
  test: {
    environment: "jsdom",
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      reporter: ["text", "clover", "lcov"],
      reportsDirectory: "coverage",
    },
  },
})
