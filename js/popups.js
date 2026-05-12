'use strict';

const Popup = (() => {
    let el       = null;
    let titleEl  = null;
    let bodyEl   = null;
    let visible  = false;

    function init() {
        el      = document.getElementById('metric-popup');
        titleEl = el.querySelector('.metric-popup__title');
        bodyEl  = el.querySelector('.metric-popup__body');

        // Delegated click: any element with data-popup triggers the popup
        document.addEventListener('click', delegatedClick);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    }

    function delegatedClick(e) {
        // If click is inside the popup itself (e.g. close button), ignore
        if (el && el.contains(e.target)) return;

        const trigger = e.target.closest('[data-popup]');

        if (!trigger) {
            // Click outside everything — close
            if (visible) close();
            return;
        }

        // Prevent table row's drill-down handler from also firing
        // Only stop propagation when the trigger is NOT itself a table row
        if (!trigger.matches('tr')) {
            e.stopPropagation();
        }

        const key   = trigger.dataset.popup;
        const title = resolveTriggerTitle(trigger);
        show(trigger, title, I18n.t(key));
    }

    function resolveTriggerTitle(trigger) {
        // Try explicit override first
        if (trigger.dataset.popupTitle) return trigger.dataset.popupTitle;
        // KPI card label
        const label = trigger.querySelector('.kpi-card__label');
        if (label) return label.textContent.trim();
        // Table header
        if (trigger.tagName === 'TH') return trigger.textContent.trim();
        // Greek badge
        const span = trigger.querySelector('span');
        if (span) return span.textContent.trim();
        // P&L attribution label
        const attrLabel = trigger.querySelector('.pnl-attr__label');
        if (attrLabel) return attrLabel.textContent.trim();
        return '';
    }

    function show(anchor, title, body) {
        titleEl.textContent = title;
        // Replace \n with line breaks for readability
        bodyEl.innerHTML = escHtml(body).replace(/\n/g, '<br>');

        // Position popup near the anchor
        position(anchor);

        el.classList.add('popup-visible');
        visible = true;
    }

    function close() {
        el.classList.remove('popup-visible');
        visible = false;
    }

    function position(anchor) {
        const rect    = anchor.getBoundingClientRect();
        const popW    = 330;
        const popH    = el.offsetHeight || 160;
        const margin  = 8;
        const vw      = window.innerWidth;
        const vh      = window.innerHeight;

        let top  = rect.bottom + margin;
        let left = rect.left;

        // Flip above if not enough space below
        if (top + popH > vh - margin) {
            top = rect.top - popH - margin;
        }
        // Clamp left so popup doesn't go off screen
        if (left + popW > vw - margin) {
            left = vw - popW - margin;
        }
        if (left < margin) left = margin;

        el.style.top  = Math.max(0, top)  + 'px';
        el.style.left = Math.max(0, left) + 'px';
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return { init, show, close };
})();
