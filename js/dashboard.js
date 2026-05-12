'use strict';

const POLL_MS = 7000;

let pollTimer    = null;
let latestMarket = null;
let latestCredit = null;
let activeTab    = 'market';
let drillOpen    = false;

// ── Boot ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    Popup.init();
    setupTabs();
    setupLangSwitcher();
    applyLanguage(I18n.getLang()); // restore persisted language
    startPolling();
});

// ── Language ─────────────────────────────────────────────
function setupLangSwitcher() {
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const l = btn.dataset.lang;
            I18n.setLang(l);
            applyLanguage(l);
        });
    });
}

function applyLanguage(lang) {
    // Update active button
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });

    // Update all static data-i18n elements
    document.querySelectorAll('[data-i18n]').forEach(el => {
        el.textContent = I18n.t(el.dataset.i18n);
    });

    // Re-render dynamic panels if data is available
    if (latestMarket) renderMarketPanel(latestMarket);
    if (latestCredit) renderCreditPanel(latestCredit);

    // If drill-down is open, update its labels
    if (drillOpen) DrillDown.applyDrillI18n();
}

// ── Tabs ─────────────────────────────────────────────────
function setupTabs() {
    document.querySelectorAll('.risk-tab').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });
}

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.risk-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('panel-hidden', p.dataset.panel !== tab));
}

// ── Polling ──────────────────────────────────────────────
function startPolling() {
    pollAll();
    pollTimer = setInterval(pollAll, POLL_MS);
}

function stopPolling() {
    if (pollTimer) clearInterval(pollTimer);
}

async function pollAll() {
    const seed = Math.floor(Date.now() / 7000);
    const base = getBasePath();

    pulseStart();

    try {
        const [mRes, cRes] = await Promise.all([
            fetch(`${base}api/market_data.php?seed=${seed}`),
            fetch(`${base}api/credit_data.php?seed=${seed}`),
        ]);

        if (!mRes.ok || !cRes.ok) throw new Error('API error');

        const [mData, cData] = await Promise.all([mRes.json(), cRes.json()]);

        latestMarket = mData;
        latestCredit = cData;

        renderMarketPanel(mData);
        renderCreditPanel(cData);
        updateTimestamp();
    } catch (err) {
        console.error('Poll error:', err);
        showError(I18n.t('err_refresh'));
    } finally {
        pulseEnd();
    }
}

// ── Timestamp ────────────────────────────────────────────
function updateTimestamp() {
    const el = document.getElementById('last-refresh');
    if (!el) return;
    el.textContent = new Date().toLocaleTimeString('en-GB', { hour12: false });
}

function pulseStart() {
    document.getElementById('live-dot')?.classList.add('pulse');
}

function pulseEnd() {
    setTimeout(() => document.getElementById('live-dot')?.classList.remove('pulse'), 800);
}

// ── Helpers ──────────────────────────────────────────────
function getBasePath() {
    const path = window.location.pathname;
    return path.substring(0, path.lastIndexOf('/') + 1);
}

function fmtUSD(val, decimals = 0) {
    if (val === undefined || val === null) return '—';
    const abs = Math.abs(val);
    if (abs >= 1e9) return (val / 1e9).toFixed(2) + 'B';
    if (abs >= 1e6) return (val / 1e6).toFixed(2) + 'M';
    if (abs >= 1e3) return (val / 1e3).toFixed(1) + 'K';
    return val.toFixed(decimals);
}

function fmtPct(val, decimals = 2) {
    return (val * 100).toFixed(decimals) + '%';
}

function showError(msg) {
    const el = document.getElementById('error-banner');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 5000);
}

window.fmtUSD = fmtUSD;
window.fmtPct = fmtPct;
