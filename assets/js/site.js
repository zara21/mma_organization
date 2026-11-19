const accountMenu = document.querySelector('.account-disclosure');
if (accountMenu) {
    document.addEventListener('click', event => {
        if (accountMenu.open && !accountMenu.contains(event.target)) accountMenu.open = false;
    });
    accountMenu.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !accountMenu.open) return;
        event.preventDefault();
        accountMenu.open = false;
        accountMenu.querySelector('summary').focus();
    });
}

const countdown = document.querySelector('[data-countdown]');
if (countdown) {
    const start = Number(countdown.dataset.countdown) * 1000;
    const update = () => {
        const seconds = Math.max(0, Math.floor((start - Date.now()) / 1000));
        const times = [Math.floor(seconds / 86400), Math.floor(seconds / 3600) % 24, Math.floor(seconds / 60) % 60, seconds % 60];
        countdown.querySelectorAll('strong').forEach((element, index) => {
            element.textContent = String(times[index]).padStart(2, '0');
        });
        return seconds;
    };
    if (update()) {
        const timer = setInterval(() => {
            if (!update()) clearInterval(timer);
        }, 1000);
    }
}

document.querySelectorAll('[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

const rankingEditor = document.querySelector('[data-ranking-editor]');
const swapGuide = rankingEditor?.querySelector('[data-rank-swap-guide]');
if (swapGuide) {
    const body = rankingEditor.querySelector('tbody');
    const status = rankingEditor.querySelector('[data-rank-swap-status]');
    const rowSelector = '[data-ranking-row]';
    const handleSelector = '[data-rank-swap]';
    const position = row => row.querySelector('input[type="number"]');
    const name = row => row.querySelector('[data-ranked-name]').textContent;
    const sortableRank = row => position(row).value === '' ? Infinity : Number(position(row).value);
    let selected = null;
    let drag = null;
    let dropTarget = null;
    let skipClick = false;

    swapGuide.hidden = false;
    body.querySelectorAll(handleSelector).forEach(handle => { handle.hidden = false; });

    function select(handle) {
        selected?.setAttribute('aria-pressed', 'false');
        selected = handle;
        selected?.setAttribute('aria-pressed', 'true');
    }

    function swap(first, second) {
        if (sortableRank(first) === sortableRank(second)) {
            status.textContent = 'These fighters already share the same position.';
            return;
        }
        [position(first).value, position(second).value] = [position(second).value, position(first).value];
        const active = document.activeElement;
        const sorted = Array.from(body.rows).sort((a, b) => (sortableRank(a) - sortableRank(b)) || name(a).localeCompare(name(b)));
        body.append(...sorted);
        if (body.contains(active)) active.focus({ preventScroll: true });
        status.textContent = 'Positions swapped. Save rankings to apply the changes.';
    }

    body.addEventListener('click', event => {
        const handle = event.target.closest(handleSelector);
        if (!handle) return;
        if (skipClick && event.detail !== 0) {
            skipClick = false;
            return;
        }
        const previous = selected;
        select(previous ? null : handle);
        if (!previous) {
            status.textContent = 'Select another fighter to swap positions.';
        } else if (previous === handle) {
            status.textContent = '';
        } else {
            swap(previous.closest(rowSelector), handle.closest(rowSelector));
        }
    });

    body.addEventListener('pointerdown', event => {
        const handle = event.target.closest(handleSelector);
        if (!handle || !event.isPrimary || event.button !== 0) return;
        skipClick = false;
        handle.setPointerCapture(event.pointerId);
        drag = { id: event.pointerId, row: handle.closest(rowSelector), x: event.clientX, y: event.clientY, started: false };
    });

    function rowAt(event) {
        const target = document.elementFromPoint(event.clientX, event.clientY)?.closest(rowSelector);
        return target && body.contains(target) && target !== drag.row ? target : null;
    }

    body.addEventListener('pointermove', event => {
        if (drag?.id !== event.pointerId) return;
        if (!drag.started && Math.hypot(event.clientX - drag.x, event.clientY - drag.y) < 6) return;
        if (!drag.started) {
            drag.started = true;
            select(null);
            drag.row.classList.add('rank-grabbed');
        }
        const target = rowAt(event);
        if (target !== dropTarget) {
            dropTarget?.classList.remove('rank-drop-target');
            dropTarget = target;
            dropTarget?.classList.add('rank-drop-target');
        }
    });

    function finishDrag(event) {
        if (drag?.id !== event.pointerId) return;
        if (drag.started) {
            skipClick = true;
            const target = event.type === 'pointerup' ? rowAt(event) : null;
            drag.row.classList.remove('rank-grabbed');
            dropTarget?.classList.remove('rank-drop-target');
            if (target) swap(drag.row, target);
        }
        drag = dropTarget = null;
    }

    body.addEventListener('pointerup', finishDrag);
    body.addEventListener('pointercancel', finishDrag);
    body.addEventListener('lostpointercapture', finishDrag);
    body.addEventListener('keydown', event => {
        if (event.key === 'Escape' && selected) {
            select(null);
            status.textContent = '';
        }
    });
}
