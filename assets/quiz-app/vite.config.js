import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        // index.[hash].js / .css damit der PHP-Shortcode sie finden kann
        entryFileNames: 'assets/index.[hash].js',
        chunkFileNames: 'assets/index.[hash].js',
        assetFileNames: 'assets/index.[hash][extname]',
      },
    },
  },
});
