import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
	content: [
		"./resources/**/*.blade.php",
		"./resources/**/*.js",
		'./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
	],
	
	plugins: [
		forms,
		daisyui,
	],
	
	daisyui: {
		themes: ["light", "dark", "cupcake"],
	},
};
