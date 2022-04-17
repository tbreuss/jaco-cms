import { resolve } from "path";
import { defineConfig } from "vite";

export default defineConfig({
  //root: './test',
  //base: '/test/',
  server: {
    //open: '/test/index.html'
  },
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'test/index.html'),
        //nested: resolve(__dirname, 'nested/index.html')
      }
    }
  }  
  // Uncomment to use JSX:
  // esbuild: {
  //   jsx: "transform",
  //   jsxFactory: "m",
  //   jsxFragment: "'['",
  // },
});
