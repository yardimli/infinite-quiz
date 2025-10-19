import forms from '@tailwindcss/forms';
import daisyui from 'daisyui'; // New: Import daisyui

/** @type {import('tailwindcss').Config} */
export default {
	content: [
		"./resources/**/*.blade.php",
		"./resources/**/*.js",
		'./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
	],
	
	plugins: [
		forms,
		daisyui, // New: Add daisyui to plugins
	],
	
	// New section: Add daisyui config to specify themes. [1, 5]
	daisyui: {
		themes: ["light", "dark", "cupcake"],
	},
};
