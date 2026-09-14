
        (function () {
            if (window.STAI_WIDGET_LOADED) {
                return;
            }
            window.STAI_WIDGET_LOADED = true;

            const config = window.STAI_WIDGET_CONFIG || {};

const endpoint = config.endpoint || '';
const trackEndpoint = config.trackEndpoint || '';
const leadEndpoint = config.leadEndpoint || '';
const whatsappUrl = config.whatsappUrl || '';
const recaptchaSiteKey = config.recaptchaSiteKey || '';
let staiIsSending = false;

async function getCaptchaToken() {
    if (!recaptchaSiteKey || typeof grecaptcha === 'undefined') {
        return '';
    }

    return new Promise(function (resolve) {
        grecaptcha.ready(function () {
            grecaptcha.execute(recaptchaSiteKey, { action: 'stai_chat' })
                .then(function (token) {
                    resolve(token || '');
                })
                .catch(function () {
                    resolve('');
                });
        });
    });
}

function trackEvent(eventType, payload = {}) {
    if (!trackEndpoint) {
        return;
    }

    const body = Object.assign({
        event_type: eventType,
        page_url: window.location.href
    }, payload);

    fetch(trackEndpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
    }).catch(function () {});
}

            const launcher = document.getElementById('stai-launcher');
const panel = document.getElementById('stai-panel');
const closeBtn = document.getElementById('stai-close');
const minimizeBtn = document.getElementById('stai-minimize');
const clearBtn = document.getElementById('stai-clear');
const messages = document.getElementById('stai-messages');
const input = document.getElementById('stai-input');
const send = document.getElementById('stai-send');

const staiHistoryKey = 'stai_chat_history_v1';
const staiPanelStateKey = 'stai_panel_open_v1';
const staiMaxStoredMessages = 20;
const staiInitialMessagesHtml = messages ? messages.innerHTML : '';

            if (!launcher || !panel || !messages || !input || !send) {
                return;
            }

            let staiOpenTracked = false;

function openPanel() {
    panel.classList.add('stai-open');
    panel.setAttribute('aria-hidden', 'false');

    try {
        localStorage.setItem(staiPanelStateKey, '1');
    } catch (e) {}

    if (!staiOpenTracked) {
        staiOpenTracked = true;
        trackEvent('widget_open');
    }

    setTimeout(() => input.focus(), 240);
}

            function closePanel() {
    panel.classList.remove('stai-open');
    panel.setAttribute('aria-hidden', 'true');

    try {
        localStorage.setItem(staiPanelStateKey, '0');
    } catch (e) {}
}

            launcher.addEventListener('click', openPanel);
            closeBtn.addEventListener('click', closePanel);
            minimizeBtn.addEventListener('click', closePanel);
if (clearBtn) {
    clearBtn.addEventListener('click', function () {
        clearChatHistory();
        messages.innerHTML = staiInitialMessagesHtml;
        bindQuickActions();
        scrollToBottom();
        input.focus();
    });
}
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePanel();
                }
            });

            function scrollToBottom() {
                messages.scrollTop = messages.scrollHeight;
            }
            
            function getChatHistory() {
    try {
        const raw = localStorage.getItem(staiHistoryKey);
        const parsed = raw ? JSON.parse(raw) : [];

        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}

function saveChatHistory(history) {
    try {
        const limited = history.slice(-staiMaxStoredMessages);
        localStorage.setItem(staiHistoryKey, JSON.stringify(limited));
    } catch (e) {}
}

function pushChatHistory(item) {
    const history = getChatHistory();
    history.push(Object.assign({
        time: Date.now()
    }, item));

    saveChatHistory(history);
}

function clearChatHistory() {
    try {
        localStorage.removeItem(staiHistoryKey);
    } catch (e) {}
}

function renderBotMessageInstant(text, userText, cards) {
    const whatsappFromAnswer = extractWhatsappUrl(text);
    const cleaned = cleanBotText(text) || text;

    const wrap = document.createElement('div');
    wrap.className = 'stai-msg stai-bot';

    const bubble = document.createElement('div');
    bubble.className = 'stai-msg-text';
    bubble.textContent = cleaned;

    wrap.appendChild(bubble);

    const actions = document.createElement('div');
    actions.className = 'stai-actions';

    if (whatsappFromAnswer) {
        createWhatsappButton(whatsappFromAnswer, actions, 'WhatsApp ile Bilgi Al');
    }

    if (actions.children.length > 0) {
        wrap.appendChild(actions);
    }

    const followups = document.createElement('div');
    followups.className = 'stai-followups';

    buildFollowups(userText || '', text || '').slice(0, 4).forEach(function(item) {
        createButton(item.label, item.message, followups);
    });

    if (whatsappUrl) {
        createWhatsappButton(whatsappUrl, followups, 'WhatsApp');
    }

    if (followups.children.length > 0) {
        wrap.appendChild(followups);
    }

    messages.appendChild(wrap);

    if (Array.isArray(cards) && cards.length > 0) {
        renderProgramCards(cards);
    }

    scrollToBottom();
}

function restoreChatHistory() {
    const history = getChatHistory();

    if (!history.length) {
        return;
    }

    messages.innerHTML = '';

    history.forEach(function(item) {
        if (item.role === 'user') {
            createUserMessage(item.text || '');
        }

        if (item.role === 'bot') {
            renderBotMessageInstant(item.text || '', item.userText || '', item.cards || []);
        }
    });

    bindQuickActions();
    scrollToBottom();
}

            function extractWhatsappUrl(text) {
                const match = text.match(/https:\/\/wa\.me\/\S+/i);
                return match ? match[0].trim() : '';
            }

            function cleanBotText(text) {
                let cleaned = text || '';
                cleaned = cleaned.replace(/WhatsApp:\s*/gi, '');
                cleaned = cleaned.replace(/واتساپ:\s*/g, '');
                cleaned = cleaned.replace(/https:\/\/wa\.me\/\S+/ig, '');
                cleaned = cleaned.replace(/\n{3,}/g, '\n\n');
                return cleaned.trim();
            }

            function extractProgramNo(text) {
                if (!text) return '';
                const m1 = text.match(/program\s+(\d+)/i);
                const m2 = text.match(/برنامه\s+(\d+)/i);
                return m1 ? m1[1] : (m2 ? m2[1] : '');
            }

            function createUserMessage(text) {
                const wrap = document.createElement('div');
                wrap.className = 'stai-msg stai-user';

                const bubble = document.createElement('div');
                bubble.className = 'stai-msg-text';
                bubble.textContent = text;

                wrap.appendChild(bubble);
                messages.appendChild(wrap);
                scrollToBottom();
            }

            function createTypingMessage() {
                const wrap = document.createElement('div');
                wrap.className = 'stai-msg stai-bot stai-typing-wrap';

                const bubble = document.createElement('div');
                bubble.className = 'stai-msg-text';

                const typing = document.createElement('div');
                typing.className = 'stai-typing';
                typing.innerHTML = '<span></span><span></span><span></span>';

                bubble.appendChild(typing);
                wrap.appendChild(bubble);
                messages.appendChild(wrap);
                scrollToBottom();

                return {
                    wrap,
                    bubble
                };
            }

            function createButton(label, message, container) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'stai-chip';
                btn.textContent = label;
                btn.addEventListener('click', function () {
                    input.value = message;
                    sendMessage();
                });
                container.appendChild(btn);
            }

            function createWhatsappButton(url, container, label = 'WhatsApp') {
                if (!url) return;

                const a = document.createElement('a');
                a.className = 'stai-chip stai-chip-wa';
                a.href = url;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                a.textContent = label;
                container.appendChild(a);
            }

            function buildFollowups(userText, botText) {
                const lower = ((userText || '') + ' ' + (botText || '')).toLowerCase();
                const programNo = extractProgramNo(userText) || extractProgramNo(botText);

                if (programNo) {
                    return [
                        { label: 'Çocuk Ücreti', message: 'Program ' + programNo + ' çocuk fiyatı nedir?' },
                        { label: '2 Kişilik Fiyat', message: 'Program ' + programNo + ' 2 kişilik fiyatı ne kadar?' },
                        { label: '3 Kişilik Fiyat', message: 'Program ' + programNo + ' 3 kişilik fiyatı ne kadar?' },
                        { label: 'Benzer Programlar', message: 'Bu programa benzer programları göster' }
                    ];
                }

                if (lower.includes('lüks') || lower.includes('luks')) {
                    return [
                        { label: 'Ekonomik Programlar', message: 'Ekonomik programları göster' },
                        { label: 'En Uygun Program', message: 'En uygun program hangisi?' },
                        { label: 'Haziran Programları', message: 'Haziran programları var mı?' }
                    ];
                }

                if (lower.includes('eko') || lower.includes('ekonomik')) {
                    return [
                        { label: 'Lüks Programlar', message: 'Lüks programları listele' },
                        { label: 'En Uygun Program', message: 'En uygun program hangisi?' },
                        { label: 'Mevcut Programlar', message: 'Mevcut programları göster' }
                    ];
                }

                if (lower.includes('haziran') || lower.includes('june') || lower.includes('ژوئن')) {
                    return [
                        { label: 'Lüks Programlar', message: 'Lüks programları listele' },
                        { label: 'Ekonomik Programlar', message: 'Ekonomik programları göster' },
                        { label: 'Mevcut Programlar', message: 'Mevcut programları göster' }
                    ];
                }

                if (lower.includes('en uygun') || lower.includes('en ucuz') || lower.includes('ucuz')) {
                    return [
                        { label: 'Lüks Programlar', message: 'Lüks programları listele' },
                        { label: 'Ekonomik Programlar', message: 'Ekonomik programları göster' },
                        { label: 'Mevcut Programlar', message: 'Mevcut programları göster' }
                    ];
                }

                return [
                    { label: 'Mevcut Programlar', message: 'Mevcut programları göster' },
                    { label: 'Lüks Programlar', message: 'Lüks programları listele' },
                    { label: 'Ekonomik Programlar', message: 'Ekonomik programları göster' },
                    { label: 'En Uygun Program', message: 'En uygun program hangisi?' }
                ];
            }

            async function typeText(element, text, speed = 12) {
                element.textContent = '';
                for (let i = 0; i < text.length; i++) {
                    element.textContent += text.charAt(i);
                    if (i % 3 === 0) {
                        scrollToBottom();
                    }
                    await new Promise(resolve => setTimeout(resolve, speed));
                }
                scrollToBottom();
            }

            async function renderBotMessage(fullText, userText) {
                const whatsappFromAnswer = extractWhatsappUrl(fullText);
                const cleaned = cleanBotText(fullText) || fullText;

                const typingObj = createTypingMessage();

                typingObj.bubble.innerHTML = '';
                const textBox = document.createElement('div');
                textBox.className = 'stai-msg-text';
                typingObj.wrap.innerHTML = '';
                typingObj.wrap.appendChild(textBox);

                await typeText(textBox, cleaned, 10);

                const actions = document.createElement('div');
                actions.className = 'stai-actions';

                if (whatsappFromAnswer) {
                    createWhatsappButton(whatsappFromAnswer, actions, 'WhatsApp ile Bilgi Al');
                }

                const followups = document.createElement('div');
                followups.className = 'stai-followups';

                buildFollowups(userText, fullText).slice(0, 4).forEach(function(item) {
                    createButton(item.label, item.message, followups);
                });

                if (whatsappUrl) {
                    createWhatsappButton(whatsappUrl, followups, 'WhatsApp');
                }
                createLeadButton(followups, extractProgramNo(userText) || extractProgramNo(fullText));

                if (actions.children.length > 0) {
                    typingObj.wrap.appendChild(actions);
                }

                if (followups.children.length > 0) {
                    typingObj.wrap.appendChild(followups);
                }

                scrollToBottom();
            }


function renderProgramCards(cards) {
    if (!Array.isArray(cards) || cards.length === 0) {
        return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'stai-msg stai-bot';

    const cardsBox = document.createElement('div');
    cardsBox.className = 'stai-program-cards';

    cards.forEach(function (card) {
        const item = document.createElement('div');
        item.className = 'stai-program-card';

        item.innerHTML = `
            <div class="stai-card-top">
                <div class="stai-card-no">Program ${escapeHtml(card.program_no || '')}</div>
                <div class="stai-card-type">${escapeHtml(card.type || '')}</div>
            </div>

            <div class="stai-card-title">${escapeHtml(card.title || '')}</div>

            <div class="stai-card-meta">
                <div>📅 ${escapeHtml(card.departure || '-')} - ${escapeHtml(card.return_date || '-')}</div>
                <div>🕌 Medine: ${escapeHtml(card.medinah_hotel || '-')}</div>
                <div>🕋 Mekke: ${escapeHtml(card.makkah_hotel || '-')}</div>
            </div>

            <div class="stai-card-prices">
                <div class="stai-price-pill">2 Kişilik<strong>${escapeHtml(card.price_double || '-')}</strong></div>
                <div class="stai-price-pill">3 Kişilik<strong>${escapeHtml(card.price_triple || '-')}</strong></div>
                <div class="stai-price-pill">4 Kişilik<strong>${escapeHtml(card.price_quad || '-')}</strong></div>
            </div>
        `;

        const actions = document.createElement('div');
        actions.className = 'stai-card-actions';

        const detailUrl = card.detail_url || (
    'https://www.serverturizm.com.tr/umre-1/?program=' + encodeURIComponent(card.program_no || '')
);

const detailLink = document.createElement('a');
detailLink.className = 'stai-chip stai-chip-detail';
detailLink.textContent = 'Detay Gör';
detailLink.href = detailUrl;
detailLink.target = '_blank';
detailLink.rel = 'noopener noreferrer';
detailLink.dataset.programNo = card.program_no || '';

detailLink.addEventListener('click', function () {
    trackEvent('program_detail', {
        program_no: card.program_no || '',
        href: detailUrl
    });
});

actions.appendChild(detailLink);
        createLeadButton(actions, card.program_no || '');

        if (card.whatsapp_url) {
            const wa = document.createElement('a');
           wa.className = 'stai-chip stai-chip-wa';
wa.href = card.whatsapp_url;
wa.target = '_blank';
wa.rel = 'noopener noreferrer';
wa.textContent = 'WhatsApp';
wa.dataset.programNo = card.program_no || '';
actions.appendChild(wa);
        }

        item.appendChild(actions);
        cardsBox.appendChild(item);
    });

    wrap.appendChild(cardsBox);
    messages.appendChild(wrap);
    scrollToBottom();
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function createLeadButton(container, programNo = '') {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'stai-chip stai-chip-lead';
    btn.textContent = 'Beni Arayın';
    btn.dataset.leadOpen = '1';
    btn.dataset.programNo = programNo || '';
    container.appendChild(btn);
}

function renderLeadForm(programNo = '', note = '') {
    const wrap = document.createElement('div');
    wrap.className = 'stai-msg stai-bot';

    const card = document.createElement('div');
    card.className = 'stai-lead-card';

    const header = document.createElement('div');
    header.className = 'stai-lead-header';
    header.innerHTML = `
        <div class="stai-lead-icon">☎</div>
        <div>
            <strong>Size yardımcı olalım</strong>
            <span>Satış ekibimiz kısa süre içinde sizinle iletişime geçsin.</span>
        </div>
    `;

    const form = document.createElement('form');
    form.className = 'stai-lead-form';

    form.innerHTML = `
        <div class="stai-form-row">
            <label class="stai-field">
                <span>Ad Soyad</span>
                <input type="text" name="name" placeholder="Örn: Ahmet Yılmaz" required>
            </label>

            <label class="stai-field">
                <span>Telefon</span>
                <input type="tel" name="phone" placeholder="Örn: 05XX XXX XX XX" required>
            </label>
        </div>

        <label class="stai-field">
            <span>İlgilendiğiniz Program</span>
            <input type="text" name="program_no" placeholder="Program No" value="${escapeHtml(programNo || '')}">
        </label>

        <label class="stai-field">
            <span>Notunuz</span>
            <textarea name="note" placeholder="Hangi tarih veya programla ilgileniyorsunuz?">${escapeHtml(note || '')}</textarea>
        </label>

        <button type="submit" class="stai-lead-submit">
            <span>Bilgilerimi Gönder</span>
        </button>

        <div class="stai-lead-status"></div>
    `;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = form.querySelector('.stai-lead-submit');
        const status = form.querySelector('.stai-lead-status');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Gönderiliyor...</span>';
        status.className = 'stai-lead-status';
        status.textContent = '';

        const formData = new FormData(form);

        try {
            const response = await fetch(leadEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: formData.get('name') || '',
                    phone: formData.get('phone') || '',
                    program_no: formData.get('program_no') || '',
                    note: formData.get('note') || '',
                    page_url: window.location.href,
                    captcha_token: await getCaptchaToken()
                })
            });

            const data = await response.json();

            if (data.success) {
                card.classList.add('stai-lead-success-card');

                card.innerHTML = `
                    <div class="stai-success-box">
                        <div class="stai-success-icon">✓</div>
                        <strong>Bilgileriniz alındı</strong>
                        <p>${escapeHtml(data.message || 'Satış ekibimiz en kısa sürede sizinle iletişime geçecektir.')}</p>
                    </div>
                `;

                trackEvent('lead_submit', {
                    program_no: formData.get('program_no') || ''
                });
            } else {
                status.className = 'stai-lead-status stai-lead-error';
                status.textContent = data.message || 'Form gönderilemedi. Lütfen tekrar deneyin.';

                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Bilgilerimi Gönder</span>';
            }
        } catch (error) {
            status.className = 'stai-lead-status stai-lead-error';
            status.textContent = 'Bağlantı hatası oluştu. Lütfen tekrar deneyin.';

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Bilgilerimi Gönder</span>';
        }

        scrollToBottom();
    });

    card.appendChild(header);
    card.appendChild(form);

    wrap.appendChild(card);
    messages.appendChild(wrap);
    scrollToBottom();
}

            async function sendMessage() {
                if (staiIsSending) {
    return;
}

const text = input.value.trim();

if (!text) return;

staiIsSending = true;

                openPanel();
                createUserMessage(text);
pushChatHistory({
    role: 'user',
    text: text
});

input.value = '';
send.disabled = true;

                const tempTyping = createTypingMessage();

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
    message: text,
    captcha_token: await getCaptchaToken(),
    page_url: window.location.href
})
                    });

                    const data = await response.json();

                    tempTyping.wrap.remove();

                    if (data.success && data.answer) {
    await renderBotMessage(data.answer, text);

    if (Array.isArray(data.cards) && data.cards.length > 0) {
        renderProgramCards(data.cards);
    }

    pushChatHistory({
        role: 'bot',
        text: data.answer,
        userText: text,
        cards: Array.isArray(data.cards) ? data.cards : []
    });
} else {
    const fallbackText = data.message || 'Şu anda cevap alınamadı.';

    await renderBotMessage(fallbackText, text);

    pushChatHistory({
        role: 'bot',
        text: fallbackText,
        userText: text,
        cards: []
    });
}
                } catch (error) {
                    tempTyping.wrap.remove();
                    await renderBotMessage('Bağlantı hatası oluştu. Lütfen tekrar deneyin.', text);
                }

                send.disabled = false;
                
staiIsSending = false;
input.focus();
            }

            send.addEventListener('click', sendMessage);

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    sendMessage();
                }
            });


document.addEventListener('click', function (event) {
    const whatsappLink = event.target.closest('#stai-floating-root a.stai-chip-wa');

    if (whatsappLink) {
        trackEvent('whatsapp_click', {
            program_no: whatsappLink.dataset.programNo || '',
            href: whatsappLink.href
        });
    }
});
            function bindQuickActions() {
    document.querySelectorAll('#stai-floating-root .stai-chip[data-message]').forEach(function (btn) {
        if (btn.dataset.staiBound === '1') {
            return;
        }

        btn.dataset.staiBound = '1';

        btn.addEventListener('click', function () {
            input.value = btn.getAttribute('data-message');
            sendMessage();
        });
    });
}
document.addEventListener('click', function (event) {
    const leadBtn = event.target.closest('#stai-floating-root [data-lead-open]');

    if (!leadBtn) {
        return;
    }

    const programNo = leadBtn.dataset.programNo || '';
    const note = programNo
        ? 'Program ' + programNo + ' hakkında bilgi almak istiyorum.'
        : 'Umre programları hakkında bilgi almak istiyorum.';

    renderLeadForm(programNo, note);
});
bindQuickActions();
restoreChatHistory();

try {
    if (localStorage.getItem(staiPanelStateKey) === '1') {
        panel.classList.add('stai-open');
        panel.setAttribute('aria-hidden', 'false');
        setTimeout(() => input.focus(), 240);
    }
} catch (e) {}
        })();
