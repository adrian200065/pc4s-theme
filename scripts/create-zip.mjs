#!/usr/bin/env node

/**
 * WordPress Theme ZIP Creator
 *
 * Creates a production-ready ZIP archive of the theme,
 * excluding development files and source directories.
 *
 * This integrates with the Vite workflow and replaces the Gulp 'zip' task.
 *
 * Usage: npm run zip
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import archiver from 'archiver';
import { minimatch } from 'minimatch';

// Get __dirname in ES module
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

// ZIP Configuration
const config = {
	zipName: 'pc4s',
	zipDest: './',

	// Files/folders to exclude from ZIP
	zipIgnoreGlob: [
		'node_modules/**',
		'src/**',
		'.babelrc',
		'.eslintrc',
		'.gitignore',
		'gulpfile.mjs',
		'wpgulp.config.mjs',
		'.eslintignore',
		'.editorconfig',
		'.stylelintrc.json',
		'phpcs.xml.dist',
		'package.json',
		'package-lock.json',
		'pnpm-lock.yaml',
		'README.md',
		'readme.txt',
		'LICENSE',
		'WORDPRESS_INTEGRATION_PLAN.md',
		'THEME_ARCHITECTURE_PLAN.md',
		'IMPLEMENTATION_CHECKLIST.md',
		'QA_REPORT.md',
		'ACF_SCHEMA.md',
		'style-rtl.css',
		'assets/css/*.map',
		'assets/js/*.map',
		'assets/admin/css/*.map',
		'assets/admin/js/*.map',
		'assets/video/**',
		'theme-dev.md',
		'html/**',
		'docs/**',
		'static-site/**',
		'memory-bank/**',
		'scripts/**',

		// Vite-specific development files to exclude
		'vite.config.js',
		'vite.config.ts',
		'VITE_SETUP_GUIDE.md',
		'VITE_WORKFLOW_GUIDE.md',
		'MIGRATION_GUIDE.md',
		'GULP_TO_VITE_COMPARISON.md',
		'QUICK_START.md',
		'ZIP_GUIDE.md',
		'ZIP_INTEGRATION_SUMMARY.md',
		'vite-wordpress-helper.php',

		// Exclude the ZIP file itself
		'*.zip',

		// Hidden files and folders
		'.git/**',
		'.github/**',
		'.gitattributes',
		'.DS_Store',
		'Thumbs.db',
		'.vscode/**',
		'.claude/**',

		// Installation script
		'wpinstall.sh',
	],
};

/**
 * Check if a file should be excluded
 */
function shouldExclude(filePath) {
	const relativePath = path.relative(rootDir, filePath);

	return config.zipIgnoreGlob.some((pattern) => {
		return minimatch(relativePath, pattern, { dot: true });
	});
}

/**
 * Get all files recursively
 */
function getAllFiles(dirPath, arrayOfFiles = []) {
	const files = fs.readdirSync(dirPath);

	files.forEach((file) => {
		const filePath = path.join(dirPath, file);

		if (fs.statSync(filePath).isDirectory()) {
			arrayOfFiles = getAllFiles(filePath, arrayOfFiles);
		} else {
			arrayOfFiles.push(filePath);
		}
	});

	return arrayOfFiles;
}

/**
 * Create the ZIP archive
 */
async function createZip() {
	const zipFileName = `${config.zipName}.zip`;
	const zipFilePath = path.resolve(rootDir, config.zipDest, zipFileName);

	// Remove existing ZIP if it exists
	if (fs.existsSync(zipFilePath)) {
		console.log(`🗑️  Removing existing ${zipFileName}...`);
		fs.unlinkSync(zipFilePath);
	}

	console.log(`📦 Creating production ZIP: ${zipFileName}`);
	console.log(`📁 Scanning files in: ${rootDir}`);

	return new Promise((resolve, reject) => {
		// Create write stream
		const output = fs.createWriteStream(zipFilePath);
		const archive = archiver('zip', {
			zlib: { level: 9 }, // Maximum compression
		});

		// Handle events
		output.on('close', () => {
			const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
			console.log(`\n✅ ZIP created successfully!`);
			console.log(`📊 Total size: ${sizeInMB} MB`);
			console.log(`📍 Location: ${zipFilePath}`);
			resolve();
		});

		archive.on('error', (err) => {
			console.error('❌ Error creating ZIP:', err);
			reject(err);
		});

		archive.on('warning', (err) => {
			if (err.code === 'ENOENT') {
				console.warn('⚠️  Warning:', err);
			} else {
				reject(err);
			}
		});

		// Pipe archive to output file
		archive.pipe(output);

		// Get all files
		const allFiles = getAllFiles(rootDir);
		let includedCount = 0;
		let excludedCount = 0;

		console.log(`\n📋 Processing files...`);

		// Add files to archive
		allFiles.forEach((filePath) => {
			const relativePath = path.relative(rootDir, filePath);

			if (shouldExclude(filePath)) {
				excludedCount++;
				// Uncomment to see excluded files:
				// console.log(`   ⊗ Excluded: ${relativePath}`);
			} else {
				includedCount++;
				archive.file(filePath, { name: relativePath });
				console.log(`   ✓ Added: ${relativePath}`);
			}
		});

		console.log(`\n📊 Summary:`);
		console.log(`   ✅ Included: ${includedCount} files`);
		console.log(`   ⊗ Excluded: ${excludedCount} files`);

		// Finalize the archive
		archive.finalize();
	});
}

/**
 * Main execution
 */
async function main() {
	try {
		console.log('\n===========================================');
		console.log('  WordPress Theme ZIP Creator');
		console.log('===========================================\n');

		// Check if assets directory exists
		const assetsDir = path.resolve(rootDir, 'assets');
		if (!fs.existsSync(assetsDir)) {
			console.error('❌ Error: assets/ directory not found!');
			console.error('   Run "npm run build" first to generate assets.');
			process.exit(1);
		}

		// Create the ZIP
		await createZip();

		console.log('\n===========================================');
		console.log('  ✅ Done!');
		console.log('===========================================\n');
	} catch (error) {
		console.error('\n❌ Error:', error.message);
		process.exit(1);
	}
}

// Run the script
main();
