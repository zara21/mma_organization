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
