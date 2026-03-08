/**
 * PC4S Website - Main JavaScript
 */

(function () {
	'use strict';

	// DOM Elements
	const siteHeader = document.getElementById('site-header');
	const menuToggle = document.getElementById('menu-toggle');
	const mainNavigation = document.getElementById('main-navigation');

	// Get all dropdown items
	const dropdownItems = document.querySelectorAll('.nav-item.has-dropdown');

	// ------------------------------------------
	// 1. Header Scroll Effect
	// ------------------------------------------
	let ticking = false;

	function handleScroll() {
		const scrollY = window.scrollY;
		if (scrollY > 50) {
			siteHeader.classList.add('scrolled');
		} else {
			siteHeader.classList.remove('scrolled');
		}
		ticking = false;
	}

	function onScroll() {
		if (!ticking) {
			window.requestAnimationFrame(handleScroll);
			ticking = true;
		}
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	handleScroll();

	// ------------------------------------------
	// 2. Mobile Menu Toggle
	// ------------------------------------------
	function toggleMenu() {
		const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';

		menuToggle.setAttribute('aria-expanded', !isOpen);
		mainNavigation.classList.toggle('open');
		document.body.style.overflow = isOpen ? '' : 'hidden';

		if (!isOpen) {
			const firstFocusable = mainNavigation.querySelector('a, button');
			if (firstFocusable) firstFocusable.focus();
		}
	}

	function closeMenu() {
		menuToggle.setAttribute('aria-expanded', 'false');
		mainNavigation.classList.remove('open');
		document.body.style.overflow = '';
		menuToggle.focus();
	}

	menuToggle.addEventListener('click', toggleMenu);

	// ------------------------------------------
	// 3. Dropdown Functions
	// ------------------------------------------
	function toggleDropdown(dropdownItem) {
		const toggleBtn = dropdownItem.querySelector('.nav-link');
		const isOpen = dropdownItem.classList.contains('is-open');

		// Close all other dropdowns first
		closeAllDropdowns();

		// Toggle current dropdown
		if (!isOpen) {
			dropdownItem.classList.add('is-open');
			toggleBtn.setAttribute('aria-expanded', 'true');
		} else {
			dropdownItem.classList.remove('is-open');
			toggleBtn.setAttribute('aria-expanded', 'false');
		}
	}

	function closeDropdown(dropdownItem) {
		const toggleBtn = dropdownItem.querySelector('.nav-link');
		dropdownItem.classList.remove('is-open');
		if (toggleBtn) {
			toggleBtn.setAttribute('aria-expanded', 'false');
		}
	}

	function closeAllDropdowns() {
		dropdownItems.forEach((item) => closeDropdown(item));
	}

	// ------------------------------------------
	// 4. Attach Dropdown Event Handlers
	// ------------------------------------------
	dropdownItems.forEach((item) => {
		const toggleBtn = item.querySelector('.nav-link');
		if (!toggleBtn) return;

		// Click handler
		toggleBtn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			toggleDropdown(item);
		});

		// Keyboard handler
		toggleBtn.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				toggleDropdown(item);
			}
		});
	});

	// ------------------------------------------
	// 5. Desktop Hover Support
	// ------------------------------------------
	function initDesktopDropdowns() {
		if (window.matchMedia('(min-width: 64rem)').matches) {
			dropdownItems.forEach((item) => {
				// Show on mouseenter
				item.addEventListener('mouseenter', function () {
					const toggleBtn = this.querySelector('.nav-link');
					this.classList.add('is-open');
					if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
				});

				// Hide on mouseleave
				item.addEventListener('mouseleave', function () {
					closeDropdown(this);
				});

				// Show on focus
				item.addEventListener('focusin', function () {
					const toggleBtn = this.querySelector('.nav-link');
					this.classList.add('is-open');
					if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
				});

				// Hide on focus out
				item.addEventListener('focusout', function (e) {
					if (!this.contains(e.relatedTarget)) {
						closeDropdown(this);
					}
				});
			});
		}
	}

	initDesktopDropdowns();

	// ------------------------------------------
	// 6. Close on Outside Click
	// ------------------------------------------
	document.addEventListener('click', function (e) {
		dropdownItems.forEach((item) => {
			if (!item.contains(e.target)) {
				closeDropdown(item);
			}
		});

		if (
			window.innerWidth < 1024 &&
			mainNavigation.classList.contains('open') &&
			!mainNavigation.contains(e.target) &&
			!menuToggle.contains(e.target)
		) {
			closeMenu();
		}
	});

	// ------------------------------------------
	// 7. Escape Key Handler
	// ------------------------------------------
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			if (mainNavigation.classList.contains('open')) {
				closeMenu();
			}
			closeAllDropdowns();
		}
	});

	// ------------------------------------------
	// 8. Resize Handler
	// ------------------------------------------
	let resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function () {
			if (window.innerWidth >= 1024 && mainNavigation.classList.contains('open')) {
				closeMenu();
			}
			closeAllDropdowns();
			initDesktopDropdowns(); // Re-init desktop dropdowns
		}, 100);
	});

	// ------------------------------------------
	// 9. Active Navigation State
	// ------------------------------------------
	function initActiveNav() {
		/**
		 * Apply active state to a nav link element.
		 * isParent = true for dropdown toggle buttons (ancestor of current page).
		 */
		function markActive(el, isParent) {
			el.classList.add('nav-link--active');
			if (!isParent) {
				el.setAttribute('aria-current', 'page');
			}
		}

		// WordPress stamps current-menu-item / current-menu-ancestor / current-menu-parent
		// on the <li> elements server-side — use that as the single source of truth.

		// --- 1. Top-level non-dropdown links ---
		document
			.querySelectorAll('.nav-list > .nav-item.current-menu-item:not(.has-dropdown) > a.nav-link')
			.forEach(function (link) {
				markActive(link, false);
			});

		// --- 2. Dropdown parents + their submenu children ---
		document.querySelectorAll('.nav-item.has-dropdown').forEach(function (dropdownItem) {
			const toggleBtn = dropdownItem.querySelector(':scope > button.nav-link');
			let hasActiveChild = false;

			dropdownItem
				.querySelectorAll('.submenu .nav-item.current-menu-item > a.nav-link')
				.forEach(function (link) {
					markActive(link, false);
					hasActiveChild = true;
				});

			// Also activate parent toggle when it is itself the current page's ancestor
			if (
				!hasActiveChild &&
				(dropdownItem.classList.contains('current-menu-ancestor') ||
					dropdownItem.classList.contains('current-menu-parent'))
			) {
				hasActiveChild = true;
			}

			if (hasActiveChild && toggleBtn) {
				markActive(toggleBtn, true);
			}
		});
	}

	initActiveNav();

	// ------------------------------------------
	// 10. Initialize
	// ------------------------------------------
	document.addEventListener('DOMContentLoaded', function () {
		console.log('PC4S Website - JavaScript initialized');
	});

	// ------------------------------------------
	// 10. Events Slider
	// ------------------------------------------
	const eventsTrack = document.getElementById('events-track');
	const eventsSlider = document.querySelector('.events-slider');
	const prevBtn = document.querySelector('.events-nav--prev');
	const nextBtn = document.querySelector('.events-nav--next');

	if (eventsTrack && prevBtn && nextBtn && eventsSlider) {
		const slides = Array.from(eventsTrack.querySelectorAll('.event-slide'));
		const AUTO_INTERVAL = 5000; // ms between auto-advances
		let autoTimer = null;
		let currentIndex = 0;

		function getSlideScrollWidth() {
			const slide = slides[0];
			if (!slide) return 0;
			const gap = parseFloat(getComputedStyle(eventsTrack).gap) || 0;
			return slide.offsetWidth + gap;
		}

		function goToIndex(index) {
			// Clamp and wrap
			if (index >= slides.length) index = 0;
			if (index < 0) index = slides.length - 1;
			currentIndex = index;
			eventsTrack.scrollTo({ left: getSlideScrollWidth() * currentIndex, behavior: 'smooth' });
			updateNavState();
		}

		function updateNavState() {
			const scrollLeft = eventsTrack.scrollLeft;
			const maxScroll = eventsTrack.scrollWidth - eventsTrack.clientWidth;
			prevBtn.disabled = slides.length <= 1;
			nextBtn.disabled = slides.length <= 1;
		}

		function startAuto() {
			if (autoTimer) return;
			autoTimer = setInterval(function () {
				goToIndex(currentIndex + 1);
			}, AUTO_INTERVAL);
		}

		function stopAuto() {
			clearInterval(autoTimer);
			autoTimer = null;
		}

		prevBtn.addEventListener('click', function () {
			stopAuto();
			goToIndex(currentIndex - 1);
			startAuto();
		});

		nextBtn.addEventListener('click', function () {
			stopAuto();
			goToIndex(currentIndex + 1);
			startAuto();
		});

		// Pause on hover or keyboard focus inside the slider
		eventsSlider.addEventListener('mouseenter', stopAuto);
		eventsSlider.addEventListener('mouseleave', startAuto);
		eventsSlider.addEventListener('focusin', stopAuto);
		eventsSlider.addEventListener('focusout', function (e) {
			if (!eventsSlider.contains(e.relatedTarget)) startAuto();
		});

		// Sync currentIndex when user drags/scrolls manually
		eventsTrack.addEventListener(
			'scroll',
			function () {
				const slideWidth = getSlideScrollWidth();
				if (slideWidth > 0) {
					currentIndex = Math.round(eventsTrack.scrollLeft / slideWidth);
				}
				updateNavState();
			},
			{ passive: true }
		);

		// Respect reduced-motion preference — skip animation, still advance
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			eventsTrack.style.scrollBehavior = 'auto';
		}

		updateNavState();
		startAuto();
	}

	// -------------------------------------------------------------------------
	// 11. Month Tabs (WAI-ARIA Tabs Pattern — automatic activation)
	// -------------------------------------------------------------------------
	function initMonthTabs() {
		var containers = document.querySelectorAll('[data-month-tabs]');
		if (!containers.length) return;

		containers.forEach(function (container) {
			var tabList = container.querySelector('[role="tablist"]');
			var tabs = Array.from(container.querySelectorAll('[role="tab"]'));
			var panels = Array.from(container.querySelectorAll('[role="tabpanel"]'));

			if (!tabList || !tabs.length || !panels.length) return;

			// Deactivate all tabs, hide all panels, then show the chosen one.
			function activateTab(tab) {
				tabs.forEach(function (t) {
					t.setAttribute('aria-selected', 'false');
					t.setAttribute('tabindex', '-1');
				});

				panels.forEach(function (p) {
					p.hidden = true;
				});

				tab.setAttribute('aria-selected', 'true');
				tab.setAttribute('tabindex', '0');

				var panelId = tab.getAttribute('aria-controls');
				var panel = document.getElementById(panelId);
				if (panel) panel.hidden = false;
			}

			// Click — activate and move focus to the tab.
			tabs.forEach(function (tab) {
				tab.addEventListener('click', function () {
					activateTab(tab);
					tab.focus();
				});
			});

			// Keyboard — roving tabindex with wrap-around.
			// ArrowRight / ArrowDown → next  |  ArrowLeft / ArrowUp → prev
			// Home → first  |  End → last
			tabList.addEventListener('keydown', function (e) {
				var idx = tabs.indexOf(document.activeElement);
				if (idx === -1) return;

				var target = -1;

				switch (e.key) {
					case 'ArrowRight':
					case 'ArrowDown':
						e.preventDefault();
						target = (idx + 1) % tabs.length;
						break;
					case 'ArrowLeft':
					case 'ArrowUp':
						e.preventDefault();
						target = (idx - 1 + tabs.length) % tabs.length;
						break;
					case 'Home':
						e.preventDefault();
						target = 0;
						break;
					case 'End':
						e.preventDefault();
						target = tabs.length - 1;
						break;
					default:
						return;
				}

				activateTab(tabs[target]);
				tabs[target].focus();
			});
		});
	}
	initMonthTabs();

	// ------------------------------------------
	// X. Form Success Auto-Reset
	// After a successful form submission the URL contains ?pc4s_form=success,
	// which causes PHP to hide the form and show the thank-you message.
	// Silently redirect to the clean URL after a short delay so the form
	// becomes visible again without the user having to navigate away.
	// ------------------------------------------
	(function formSuccessReset() {
		var url = new URL(window.location.href);
		if (url.searchParams.get('pc4s_form') !== 'success') return;

		var cleanUrl = new URL(window.location.href);
		cleanUrl.searchParams.delete('pc4s_form');
		cleanUrl.searchParams.delete('form_id');

		setTimeout(function () {
			window.location.replace(cleanUrl.toString());
		}, 5000);
	})();
})();
