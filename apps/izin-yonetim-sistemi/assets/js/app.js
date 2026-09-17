(() => {
    'use strict';

    const duration = document.querySelector('[data-duration-type]');
    const halfDayWrap = document.querySelector('[data-half-day-wrap]');

    if (duration && halfDayWrap) {
        const syncHalfDay = () => {
            const isHalf = duration.value === 'half_day';
            halfDayWrap.hidden = !isHalf;
            halfDayWrap.querySelectorAll('input, select').forEach((el) => {
                el.disabled = !isHalf;
            });
        };

        duration.addEventListener('change', syncHalfDay);
        syncHalfDay();
    }
})();
