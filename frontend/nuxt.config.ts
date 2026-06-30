import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  devtools: { enabled: false },
  devServer: {
    port: 5173,
  },
  css: ['~/assets/css/main.css'],
  vite: {
    plugins: [tailwindcss()],
    server: {
      proxy: {
        '/api/go': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/go/, ''),
          headers: { Host: 'apis.showcase-go.dev' },
        },
        '/api/java': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/java/, ''),
          headers: { Host: 'apis.showcase-java-spring.dev' },
        },
        '/api/php-cake': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/php-cake/, ''),
          headers: { Host: 'apis.showcase-php-cake.dev' },
        },
        '/api/php-codeigniter': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/php-codeigniter/, ''),
          headers: { Host: 'apis.showcase-php-codeigniter.dev' },
        },
        '/api/php-fuel': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/php-fuel/, ''),
          headers: { Host: 'apis.showcase-php-fuel.dev' },
        },
        '/api/python': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/python/, ''),
          headers: { Host: 'apis.showcase-python.dev' },
        },
        '/api/ruby': {
          target: 'https://localhost:8443',
          changeOrigin: true,
          secure: false,
          rewrite: (path: string) => path.replace(/^\/api\/ruby/, ''),
          headers: { Host: 'apis.showcase-ruby.dev' },
        },
      },
    },
  },
  typescript: {
    strict: true,
  },
})
