/**
 * PC4S Admin JavaScript
 *
 * Handles:
 *  - Google Analytics dashboard: fetch data via AJAX and render charts / tables.
 *  - GA connection test button on the Settings page.
 */

// ── Utility ───────────────────────────────────────────────────────────────────

/**
 * Format a number with locale-aware thousands separators.
 * @param {number} n
 * @returns {string}
 */
function fmtNum(n) {
	return Number(n).toLocaleString();
}

// ── Settings page: "Test Connection" button ───────────────────────────────────

function initGaTestButton() {
	const btn = document.querySelector('.js-ga-test-btn');
	if (!btn) return;

	const resultEl = document.querySelector('.pc4s-ga-test-result');
	const nonce = btn.dataset.nonce;

	btn.addEventListener('click', async () => {
		btn.disabled = true;
		btn.textContent = 'Testing…';
		if (resultEl) {
			resultEl.textContent = '';
			resultEl.className = 'pc4s-ga-test-result';
		}

		try {
			const res = await fetch(window.pc4sAdmin.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'pc4s_ga_test',
					nonce,
				}),
			});
			const json = await res.json();

			if (json.success) {
				if (resultEl) {
					resultEl.textContent = '✓ ' + (json.data.message || 'Connected');
					resultEl.classList.add('is-success');
				}
				// Refresh the page so the connection status badge updates.
				setTimeout(() => window.location.reload(), 1200);
			} else {
				const msg = json.data?.message || 'Connection failed.';
				if (resultEl) {
					resultEl.textContent = '✗ ' + msg;
					resultEl.classList.add('is-error');
				}
				btn.disabled = false;
				btn.textContent = 'Test Connection';
			}
		} catch (err) {
			if (resultEl) {
				resultEl.textContent = '✗ Network error.';
				resultEl.classList.add('is-error');
			}
			btn.disabled = false;
			btn.textContent = 'Test Connection';
		}
	});
}

// ── Dashboard analytics section ───────────────────────────────────────────────

let trendChart = null;
let sourcesChart = null;

/**
 * Render all charts and tables from the analytics data object.
 * @param {{
 *   summary: object,
 *   pageviews_trend: Array,
 *   traffic_sources: Array,
 *   top_pages: Array,
 *   devices: Array,
 *   cached: boolean,
 *   fetched_at: string,
 * }} data
 */
function renderAnalytics(data) {
	const Chart = window.Chart;
	if (!Chart) {
		console.warn('PC4S: Chart.js not loaded — charts will not render.');
	}

	// ── Metric cards ──────────────────────────────────────────────────────
	const setVal = (sel, val) => {
		const el = document.querySelector(sel);
		if (el) el.textContent = val;
	};

	const s = data.summary || {};
	setVal('.js-metric-pageviews', fmtNum(s.pageviews || 0));
	setVal('.js-metric-users', fmtNum(s.users || 0));
	setVal('.js-metric-sessions', fmtNum(s.sessions || 0));
	setVal('.js-metric-bounce', (s.bounce_rate || 0) + '%');

	// ── Cache label ───────────────────────────────────────────────────────
	const cacheLabel = document.querySelector('.js-analytics-cached-label');
	if (cacheLabel) {
		cacheLabel.textContent = data.cached ? 'Cached ' + (data.cached_at || '') : 'Live data';
	}

	// ── Page views trend chart ────────────────────────────────────────────
	const trendCanvas = document.getElementById('pc4s-chart-trend');
	if (trendCanvas && Chart && (data.pageviews_trend || []).length) {
		if (trendChart) trendChart.destroy();

		const labels = data.pageviews_trend.map((r) => r.date);
		const values = data.pageviews_trend.map((r) => r.value);

		trendChart = new Chart(trendCanvas, {
			type: 'line',
			data: {
				labels,
				datasets: [
					{
						label: 'Page Views',
						data: values,
						borderColor: '#0f5699',
						backgroundColor: 'rgba(15,86,153,0.08)',
						borderWidth: 2,
						fill: true,
						tension: 0.4,
						pointRadius: 3,
						pointHoverRadius: 5,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { intersect: false, mode: 'index' },
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: (ctx) => ' ' + fmtNum(ctx.parsed.y) + ' views',
						},
					},
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: {
							maxTicksLimit: 8,
							color: '#6b7280',
							font: { size: 10 },
						},
						border: { display: false },
					},
					y: {
						beginAtZero: true,
						grid: { color: '#f3f4f6' },
						ticks: {
							color: '#6b7280',
							font: { size: 10 },
							callback: (v) => fmtNum(v),
						},
						border: { display: false },
					},
				},
			},
		});
	}

	// ── Traffic sources chart ─────────────────────────────────────────────
	const sourcesCanvas = document.getElementById('pc4s-chart-sources');
	if (sourcesCanvas && Chart && (data.traffic_sources || []).length) {
		if (sourcesChart) sourcesChart.destroy();

		const COLORS = [
			'#0f5699',
			'#16a34a',
			'#d97706',
			'#dc2626',
			'#7c3aed',
			'#0891b2',
			'#be185d',
			'#92400e',
		];

		const labels = data.traffic_sources.map((r) => r.source);
		const values = data.traffic_sources.map((r) => r.sessions);
		const colors = labels.map((_, i) => COLORS[i % COLORS.length]);

		sourcesChart = new Chart(sourcesCanvas, {
			type: 'doughnut',
			data: {
				labels,
				datasets: [
					{
						data: values,
						backgroundColor: colors,
						borderColor: '#fff',
						borderWidth: 2,
						hoverOffset: 4,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							font: { size: 11 },
							boxWidth: 12,
							boxHeight: 12,
							padding: 12,
						},
					},
					tooltip: {
						callbacks: {
							label: (ctx) => {
								const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
								const pct = total ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
								return ` ${fmtNum(ctx.parsed)} sessions (${pct}%)`;
							},
						},
					},
				},
				cutout: '60%',
			},
		});
	}

	// ── Devices table ─────────────────────────────────────────────────────
	const devicesTbody = document.querySelector('.js-devices-tbody');
	if (devicesTbody && (data.devices || []).length) {
		const total = data.devices.reduce((acc, r) => acc + r.sessions, 0);
		devicesTbody.innerHTML = data.devices
			.map((r) => {
				const pct = total ? ((r.sessions / total) * 100).toFixed(1) : 0;
				const cap = r.device ? r.device.charAt(0).toUpperCase() + r.device.slice(1) : '(other)';
				return `<tr>
				<td>${cap}</td>
				<td class="pc4s-analytics-table__num">${fmtNum(r.sessions)}</td>
				<td class="pc4s-analytics-table__num">${pct}%</td>
			</tr>`;
			})
			.join('');
	}

	// ── Top pages table ───────────────────────────────────────────────────
	const pagesTbody = document.querySelector('.js-pages-tbody');
	if (pagesTbody && (data.top_pages || []).length) {
		const maxViews = Math.max(...data.top_pages.map((r) => r.views), 1);
		pagesTbody.innerHTML = data.top_pages
			.map((r) => {
				const barW = Math.round((r.views / maxViews) * 100);
				return `<tr>
				<td title="${r.path}">${r.path}</td>
				<td class="pc4s-analytics-table__num">${fmtNum(r.views)}</td>
				<td class="pc4s-analytics-table__num">${fmtNum(r.users)}</td>
			</tr>`;
			})
			.join('');
	}
}

/**
 * Fetch analytics data from the server (or clear cache + refetch).
 * @param {string}  nonce    WordPress nonce.
 * @param {boolean} [clear]  When true uses pc4s_ga_clear_cache action.
 */
async function fetchAnalytics(nonce, clear = false) {
	const dashboard = document.querySelector('.js-analytics-dashboard');
	if (!dashboard) return;

	const loadingEl = dashboard.querySelector('.js-analytics-loading');
	const errorEl = dashboard.querySelector('.js-analytics-error');
	const errorMsgEl = dashboard.querySelector('.js-analytics-error-msg');
	const contentEl = dashboard.querySelector('.js-analytics-content');

	// Show loading.
	if (loadingEl) loadingEl.hidden = false;
	if (errorEl) errorEl.hidden = true;
	if (contentEl) contentEl.hidden = true;

	try {
		const res = await fetch(window.pc4sAdmin.ajax_url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({
				action: clear ? 'pc4s_ga_clear_cache' : 'pc4s_ga_fetch',
				nonce,
			}),
		});
		const json = await res.json();

		if (loadingEl) loadingEl.hidden = true;

		if (json.success) {
			if (contentEl) contentEl.hidden = false;
			renderAnalytics(json.data);
		} else {
			const msg = json.data?.message || 'Could not load analytics data.';
			if (errorMsgEl) errorMsgEl.textContent = msg;
			if (errorEl) errorEl.hidden = false;
		}
	} catch (err) {
		if (loadingEl) loadingEl.hidden = true;
		if (errorMsgEl) errorMsgEl.textContent = 'Network error. Please try again.';
		if (errorEl) errorEl.hidden = false;
	}
}

/**
 * Initialise the analytics dashboard on the Overview page.
 */
function initAnalyticsDashboard() {
	const dashboard = document.querySelector('.js-analytics-dashboard');
	if (!dashboard) return;

	const nonce = dashboard.dataset.nonce;

	// Auto-fetch on page load.
	fetchAnalytics(nonce);

	// Refresh buttons (there may be several: toolbar, error state, etc.).
	document.querySelectorAll('.js-ga-refresh-btn').forEach((btn) => {
		btn.addEventListener('click', () => {
			const btnNonce = btn.dataset.nonce || nonce;
			fetchAnalytics(btnNonce, /* clear = */ true);
		});
	});
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
	initGaTestButton();
	initAnalyticsDashboard();
});
