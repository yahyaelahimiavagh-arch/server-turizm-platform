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

    const leaveForm = document.querySelector('[data-leave-form]');
    const preview = document.querySelector('[data-leave-preview]');

    if (!leaveForm || !preview) {
        return;
    }

    const previewUrl = leaveForm.dataset.previewUrl;
    const watchedNames = ['leave_type_id', 'start_date', 'end_date', 'duration_type', 'half_day_period'];
    let timer = null;
    let requestSequence = 0;
    let activeController = null;

    const formatDays = (value) => {
        const number = Number(value || 0);
        return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    };

    const resetPreview = (message) => {
        preview.replaceChildren();
        const empty = document.createElement('div');
        empty.className = 'leave-preview-empty';
        empty.textContent = message;
        preview.appendChild(empty);
    };

    const renderCalculation = (calculation) => {
        preview.replaceChildren();

        const heading = document.createElement('div');
        heading.className = 'leave-preview-title';
        heading.textContent = 'İzin Hesaplama Özeti';
        preview.appendChild(heading);

        const breakdown = calculation.breakdown || {};
        const rows = [
            ['Takvim aralığı', formatDays(breakdown.calendar_days) + ' gün'],
            ['Çalışma günü olmayan günler', formatDays(breakdown.weekly_rest_days) + ' gün'],
            ['Tam gün resmî tatil', formatDays(breakdown.full_holiday_days) + ' gün'],
            ['Yarım gün resmî tatil', formatDays(breakdown.half_holiday_days) + ' gün'],
            ['Hesaplanan izin süresi', formatDays(calculation.total) + ' gün'],
        ];

        const list = document.createElement('div');
        list.className = 'leave-preview-grid';

        rows.forEach(([labelText, valueText]) => {
            const row = document.createElement('div');
            row.className = 'leave-preview-row';

            const label = document.createElement('span');
            label.textContent = labelText;
            const value = document.createElement('strong');
            value.textContent = valueText;

            row.append(label, value);
            list.appendChild(row);
        });

        preview.appendChild(list);

        const result = document.createElement('div');
        result.className = calculation.deducts_annual_allowance
            ? 'leave-preview-result'
            : 'leave-preview-result leave-preview-result-neutral';

        if (calculation.deducts_annual_allowance) {
            result.textContent = 'Yıllık izin bakiyesinden düşecek: ' + formatDays(calculation.total) + ' gün';
        } else {
            result.textContent = 'Bu izin türü yıllık izin bakiyesinden düşmez.';
        }

        preview.appendChild(result);

        const policy = document.createElement('div');
        policy.className = 'leave-preview-policy';
        policy.textContent = 'Çalışma günleri: ' + (calculation.working_weekdays_text || 'şirket ayarlarına göre');
        preview.appendChild(policy);
    };

    const hasRequiredPreviewValues = () => {
        const type = leaveForm.elements.leave_type_id;
        const start = leaveForm.elements.start_date;
        const end = leaveForm.elements.end_date;

        return type && start && end && type.value && start.value && end.value;
    };

    const requestPreview = async () => {
        if (!previewUrl || !hasRequiredPreviewValues()) {
            resetPreview('İzin türünü ve tarihleri seçtiğinizde hesaplama burada gösterilir.');
            return;
        }

        requestSequence += 1;
        const sequence = requestSequence;

        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        preview.classList.add('is-loading');

        try {
            const data = new FormData(leaveForm);
            const response = await fetch(previewUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeController.signal,
            });

            const payload = await response.json();

            if (sequence !== requestSequence) {
                return;
            }

            if (!response.ok || !payload.ok) {
                resetPreview(payload.message || 'Hesaplama yapılamadı.');
                return;
            }

            renderCalculation(payload.calculation || {});
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            resetPreview('Hesaplama önizlemesi alınamadı. Talep gönderilirken sunucu tekrar hesaplayacaktır.');
        } finally {
            if (sequence === requestSequence) {
                preview.classList.remove('is-loading');
            }
        }
    };

    const schedulePreview = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(requestPreview, 220);
    };

    watchedNames.forEach((name) => {
        const field = leaveForm.elements[name];
        if (field) {
            field.addEventListener('change', schedulePreview);
            field.addEventListener('input', schedulePreview);
        }
    });

    if (hasRequiredPreviewValues()) {
        requestPreview();
    }
})();
