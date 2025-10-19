import './bootstrap';

// Modified section: Replaced the old theme controller with a more robust theme manager for multiple themes.

/**
 * Manages theme selection, application, and persistence in localStorage.
 */
const themeManager = {
	/**
	 * The key used to store the selected theme in localStorage.
	 * @type {string}
	 */
	storageKey: 'theme',
	
	/**
	 * Applies the given theme to the document root and saves it to localStorage.
	 * @param {string} theme The name of the theme to apply (e.g., 'light', 'dark', 'cupcake').
	 */
	applyTheme: function (theme) {
		if (theme) {
			document.documentElement.setAttribute('data-theme', theme);
			localStorage.setItem(this.storageKey, theme);
		}
	},
	
	/**
	 * Initializes the theme manager.
	 * Sets up an event listener on the theme selection menu to handle theme changes.
	 */
	init: function () {
		const themeMenu = document.getElementById('theme-menu');
		
		if (!themeMenu) {
			return; // Exit if the theme menu isn't on the page.
		}
		
		// Use event delegation to handle clicks on theme buttons.
		themeMenu.addEventListener('click', (e) => {
			const themeButton = e.target.closest('[data-set-theme]');
			if (themeButton) {
				const theme = themeButton.dataset.setTheme;
				this.applyTheme(theme);
			}
		});
	}
};

// Initialize the theme manager once the DOM is fully loaded.
document.addEventListener('DOMContentLoaded', () => {
	themeManager.init();
});
