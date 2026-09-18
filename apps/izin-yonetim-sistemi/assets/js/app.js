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

    const leaveTypeSelect = document.querySelector('#leave_type_id');
    const attachmentInput = document.querySelector('#attachment');
    const attachmentRequiredLabel = document.querySelector('[data-attachment-required-label]');

    const syncAttachmentRequirement = () => {
        if (!leaveTypeSelect || !attachmentInput) {
            return;
        }

        const selected = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
        const required = Boolean(selected && selected.dataset.requiresAttachment === '1');

        attachmentInput.required = required;
        if (attachmentRequiredLabel) {
            attachmentRequiredLabel.hidden = !required;
        }
    };

    if (leaveTypeSelect && attachmentInput) {
        leaveTypeSelect.addEventListener('change', syncAttachmentRequirement);
        syncAttachmentRequirement();
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

    const renderCalculation = (calculation, teamContext = {}) => {
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

        if (calculation.requires_attachment) {
            const attachmentNotice = document.createElement('div');
            attachmentNotice.className = 'leave-preview-attachment';
            attachmentNotice.textContent = 'Bu izin türü için belge yüklemek zorunludur.';
            preview.appendChild(attachmentNotice);
        }

        const policy = document.createElement('div');
        policy.className = 'leave-preview-policy';
        policy.textContent = 'Çalışma günleri: ' + (calculation.working_weekdays_text || 'şirket ayarlarına göre');
        preview.appendChild(policy);

        const team = document.createElement('section');
        team.className = teamContext.warning
            ? 'leave-team-context is-warning'
            : 'leave-team-context';

        const teamTitle = document.createElement('div');
        teamTitle.className = 'leave-team-title';
        teamTitle.textContent = 'Ekip Uygunluğu';
        team.appendChild(teamTitle);

        const activeEmployees = Number(teamContext.active_employees || 0);
        const maxPotentialLeave = Number(teamContext.max_potential_leave || 0);
        const minOnDuty = Number(teamContext.min_potential_on_duty || 0);
        const approvedOther = Number(teamContext.max_approved_other || 0);
        const pendingOther = Number(teamContext.max_pending_other || 0);
        const threshold = Number(teamContext.max_concurrent_leave_employees || 0);

        const teamText = document.createElement('p');
        if (activeEmployees > 0) {
            teamText.textContent =
                'Aktif çalışan: ' + activeEmployees +
                ' · Talebiniz dahil aynı anda izinli olabilecek en yüksek kişi sayısı: ' + maxPotentialLeave +
                ' · En düşük işte kalacak kişi sayısı: ' + minOnDuty + '.';
        } else {
            teamText.textContent = 'Ekip kapasitesi hesaplanamadı.';
        }
        team.appendChild(teamText);

        const detail = document.createElement('p');
        detail.className = 'leave-team-detail';
        detail.textContent =
            'Aynı tarihlerde diğer çalışanlar: ' +
            approvedOther + ' onaylı izin · ' +
            pendingOther + ' bekleyen talep.';
        team.appendChild(detail);

        if (threshold > 0) {
            const thresholdNote = document.createElement('p');
            thresholdNote.className = 'leave-team-threshold';
            thresholdNote.textContent =
                'Şirket eşzamanlı izin uyarı eşiği: ' + threshold + ' kişi.';
            team.appendChild(thresholdNote);
        }

        if (teamContext.warning) {
            const warning = document.createElement('div');
            warning.className = 'leave-team-warning';
            warning.textContent =
                'Bu tarih aralığında ekip kapasitesi uyarı eşiğini aşıyor. Talep gönderilebilir; yönetici ekip uygunluğunu ayrıca değerlendirecektir.';
            team.appendChild(warning);
        }

        preview.appendChild(team);
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

            renderCalculation(payload.calculation || {}, payload.team_context || {});
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
