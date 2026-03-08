import { defineConfig } from 'vite';
import { resolve } from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import autoprefixer from 'autoprefixer';
import { fileURLToPath } from 'url';
import path from 'path';

// Get __dirname in ES module
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
	// Base path - use './' for WordPress theme compatibility
	base: './',

	plugins: [
		// Copy static assets (images and fonts)
		viteStaticCopy({
			targets: [
				{
					src: 'src/images/*.{jpg,jpeg,png,svg,gif,webp,ico}',
					dest: 'images',
				},
				{
					src: 'src/fonts/**/*',
					dest: 'fonts',
				},
			],
		}),
	],

	build: {
		// Output to 'assets' directory
		outDir: 'assets',

		// Clean output before build
		emptyOutDir: true,

		// Generate sourcemaps
		sourcemap: true,

		// Disable manifest file (no hashing needed)
		manifest: false,

		// Minification settings
		minify: 'terser',
		terserOptions: {
			ecma: 2015,
			mangle: {
				toplevel: true,
			},
			compress: {
				drop_console: false,
				drop_debugger: false,
			},
			output: {
				beautify: false,
			},
		},

		rollupOptions: {
			// Define multiple entry points for front-end and admin assets
			input: {
				// Front-end JavaScript
				'js/main': resolve(__dirname, 'src/js/main.js'),

				// Front-end CSS
				'css/main': resolve(__dirname, 'src/scss/main.scss'),

				// Admin JavaScript
				'admin/js/admin': resolve(__dirname, 'src/admin/js/admin.js'),

				// Admin CSS
				'admin/css/admin': resolve(__dirname, 'src/admin/scss/admin.scss'),
			},

			output: {
				// No hashes in JS filenames, add .min suffix
				entryFileNames: '[name].min.js',

				// Chunk files (if code splitting occurs)
				chunkFileNames: 'js/[name].min.js',

				// Asset files (CSS, images, fonts)
				assetFileNames: (assetInfo) => {
					// Handle CSS files - add .min suffix
					if (/\.css$/i.test(assetInfo.name)) {
						return '[name].min[extname]';
					}

					// Fonts imported in CSS/JS
					if (/\.(woff|woff2|eot|ttf|otf)$/i.test(assetInfo.name)) {
						return 'fonts/[name][extname]';
					}

					// Images imported in CSS/JS
					if (/\.(png|jpe?g|gif|svg|webp|ico)$/i.test(assetInfo.name)) {
						return 'images/[name][extname]';
					}

					// Default: no hash
					return '[name][extname]';
				},
			},
		},

		// CSS minification
		cssMinify: 'lightningcss',
		cssTarget: ['chrome61', 'firefox60', 'safari11'],
	},

	css: {
		// PostCSS configuration for autoprefixer
		postcss: {
			plugins: [
				autoprefixer({
					overrideBrowserslist: ['last 2 version', '> 1%'],
				}),
			],
		},

		// SCSS preprocessor options
		preprocessorOptions: {
			scss: {
				// Modern Sass API
				api: 'modern-compiler',

				// Set output style
				outputStyle: 'expanded',
			},
		},
	},

	// Resolve configuration
	resolve: {
		alias: {
			'~': resolve(__dirname, 'src'),
			'@': resolve(__dirname, 'src'),
		},
	},
});
