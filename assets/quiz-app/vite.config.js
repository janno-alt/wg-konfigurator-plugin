import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        // IIFE + alles in einen Chunk: Das Bundle wird vom PHP-Shortcode als
        // KLASSISCHES Script geladen (wp_enqueue_script, kein type=module).
        // Ohne IIFE-Wrapper landen die Top-Level-Deklarationen im globalen
        // Lexical-Scope und kollidieren mit WordPress-Globals (z. B. `var wp`)
        // → "Identifier 'wp' has already been declared", App lädt nicht.
        format: 'iife',
        inlineDynamicImports: true,
        // index.[hash].js / .css damit der PHP-Shortcode sie finden kann
        entryFileNames: 'assets/index.[hash].js',
        chunkFileNames: 'assets/index.[hash].js',
        assetFileNames: 'assets/index.[hash][extname]',
      },
    },
  },
});
