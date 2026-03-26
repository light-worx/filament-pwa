@php
/*
 * Build the country list for the phone dial-code selector.
 * ALL_COUNTRIES is the master list of [iso, dialCode, name].
 * If config('pwa.phone_countries') is non-empty, we filter to those codes.
 * The default country is config('pwa.phone_default_country', 'ZA').
 */
$allCountries = [
    ['ZA','+27','South Africa'],['US','+1','United States'],['GB','+44','United Kingdom'],
    ['AU','+61','Australia'],['NZ','+64','New Zealand'],['CA','+1','Canada'],
    ['NG','+234','Nigeria'],['KE','+254','Kenya'],['GH','+233','Ghana'],
    ['ZW','+263','Zimbabwe'],['ZM','+260','Zambia'],['BW','+267','Botswana'],
    ['NA','+264','Namibia'],['MZ','+258','Mozambique'],['TZ','+255','Tanzania'],
    ['UG','+256','Uganda'],['RW','+250','Rwanda'],['ET','+251','Ethiopia'],
    ['EG','+20','Egypt'],['MA','+212','Morocco'],['DZ','+213','Algeria'],
    ['TN','+216','Tunisia'],['SN','+221','Senegal'],['CI','+225','Côte d\'Ivoire'],
    ['CM','+237','Cameroon'],['AO','+244','Angola'],['IN','+91','India'],
    ['PK','+92','Pakistan'],['BD','+880','Bangladesh'],['LK','+94','Sri Lanka'],
    ['NP','+977','Nepal'],['PH','+63','Philippines'],['ID','+62','Indonesia'],
    ['MY','+60','Malaysia'],['SG','+65','Singapore'],['TH','+66','Thailand'],
    ['VN','+84','Vietnam'],['CN','+86','China'],['JP','+81','Japan'],
    ['KR','+82','South Korea'],['DE','+49','Germany'],['FR','+33','France'],
    ['IT','+39','Italy'],['ES','+34','Spain'],['PT','+351','Portugal'],
    ['NL','+31','Netherlands'],['BE','+32','Belgium'],['CH','+41','Switzerland'],
    ['AT','+43','Austria'],['SE','+46','Sweden'],['NO','+47','Norway'],
    ['DK','+45','Denmark'],['FI','+358','Finland'],['PL','+48','Poland'],
    ['CZ','+420','Czech Republic'],['HU','+36','Hungary'],['RO','+40','Romania'],
    ['GR','+30','Greece'],['TR','+90','Turkey'],['RU','+7','Russia'],
    ['UA','+380','Ukraine'],['IL','+972','Israel'],['AE','+971','UAE'],
    ['SA','+966','Saudi Arabia'],['QA','+974','Qatar'],['KW','+965','Kuwait'],
    ['BH','+973','Bahrain'],['OM','+968','Oman'],['JO','+962','Jordan'],
    ['LB','+961','Lebanon'],['IQ','+964','Iraq'],['IR','+98','Iran'],
    ['BR','+55','Brazil'],['AR','+54','Argentina'],['MX','+52','Mexico'],
    ['CO','+57','Colombia'],['CL','+56','Chile'],['PE','+51','Peru'],
    ['VE','+58','Venezuela'],['EC','+593','Ecuador'],['BO','+591','Bolivia'],
    ['UY','+598','Uruguay'],['PY','+595','Paraguay'],
];

$allowedCodes   = config('pwa.phone_countries', []);
$defaultCountry = strtoupper(config('pwa.phone_default_country', 'ZA'));

if (!empty($allowedCodes)) {
    $allowedCodes = array_map('strtoupper', $allowedCodes);
    $countries    = array_filter($allCountries, fn($c) => in_array($c[0], $allowedCodes));
    // Ensure default is first
    usort($countries, fn($a, $b) =>
        ($b[0] === $defaultCountry) - ($a[0] === $defaultCountry)
        ?: strcmp($a[2], $b[2])
    );
} else {
    // All countries — default first, rest alphabetical
    usort($allCountries, fn($a, $b) =>
        ($b[0] === $defaultCountry) - ($a[0] === $defaultCountry)
        ?: strcmp($a[2], $b[2])
    );
    $countries = $allCountries;
}
@endphp

<style>
    .pwa-user-panel { background: #f8fafc; height: 100%; }
    .pwa-user-panel .card { border-radius: 14px; }
    .pwa-user-panel .form-control,
    .pwa-user-panel .form-select {
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        font-size: .875rem;
    }
    .pwa-user-panel .form-control:focus,
    .pwa-user-panel .form-select:focus {
        box-shadow: 0 0 0 2px rgba(59,130,246,.15);
        border-color: #3b82f6;
    }
    .pwa-user-panel .form-check-input:checked {
        background-color: var(--pwa-accent, #3b82f6);
        border-color: var(--pwa-accent, #3b82f6);
    }
    .verified-badge {
        font-size: .7rem; font-weight: 600;
        padding: 2px 8px; border-radius: 20px;
    }
    .phone-input-group .form-select { border-radius: 10px 0 0 10px; max-width: 130px; }
    .phone-input-group .form-control { border-radius: 0 10px 10px 0; border-left: none; }
    .phone-input-group .form-control:focus { z-index: 3; }
    .pin-input { letter-spacing: .3em; font-size: 1.2rem; font-weight: 700;
                 text-align: center; max-width: 120px; }
    .section-label { font-size: .7rem; font-weight: 700; text-transform: uppercase;
                     letter-spacing: .06em; color: #9ca3af; margin-bottom: .5rem; }
    #pwa-prefs-status { min-height: 1rem; font-size: .8rem; }
</style>

<div class="pwa-user-panel p-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h6 class="fw-semibold mb-0">Your Settings</h6>
            <small class="text-muted">Saved to this device</small>
        </div>
        <button class="btn btn-sm text-muted"
                onclick="document.getElementById('menuOverlay').click()"
                aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    {{-- ── Basic info ───────────────────────────────────────────────────── --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="section-label">Basic info</div>

            <div class="mb-3">
                <label class="form-label small text-muted" for="pref-name">Name</label>
                <input type="text" id="pref-name" class="form-control form-control-sm" autocomplete="name">
            </div>

            {{-- Email with verification flow --}}
            <div class="mb-2">
                <label class="form-label small text-muted d-flex justify-content-between" for="pref-email">
                    <span>Email</span>
                    <span id="email-verified-badge" class="d-none verified-badge bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill me-1"></i>Verified
                    </span>
                    <span id="email-unverified-badge" class="d-none verified-badge bg-warning-subtle text-warning">
                        Unverified
                    </span>
                </label>
                <div class="input-group input-group-sm">
                    <input type="email" id="pref-email" class="form-control" autocomplete="email">
                    <button class="btn btn-outline-secondary btn-sm" id="send-pin-btn" type="button">
                        Verify
                    </button>
                </div>
                <div class="form-text d-none" id="pin-sent-hint">
                    Check your inbox — a 4-digit code has been sent.
                </div>
            </div>

            {{-- PIN entry (hidden until code is sent) --}}
            <div id="pin-entry-row" class="d-none mb-3">
                <label class="form-label small text-muted" for="pin-input">Enter the 4-digit code</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="pin-input" inputmode="numeric" pattern="[0-9]{4}"
                           maxlength="4" class="form-control form-control-sm pin-input"
                           placeholder="0000" autocomplete="one-time-code">
                    <button class="btn btn-primary btn-sm" id="verify-pin-btn" type="button">
                        Confirm
                    </button>
                    <button class="btn btn-link btn-sm text-muted p-0" id="resend-pin-btn" type="button">
                        Resend
                    </button>
                </div>
                <div id="pin-error" class="text-danger small mt-1 d-none"></div>
            </div>

            <div id="pwa-prefs-status" class="text-muted mb-2"></div>
            <button id="pwa-save-prefs" class="btn btn-primary w-100 btn-sm py-2">
                Save
            </button>
        </div>
    </div>

    {{-- ── Phone number (gated: email must be verified) ─────────────────── --}}
    <div class="card shadow-sm border-0 mb-3" id="phone-card">
        <div class="card-body">
            <div class="section-label d-flex justify-content-between align-items-center">
                <span>Mobile number</span>
                <span id="phone-verified-badge" class="d-none verified-badge bg-success-subtle text-success">
                    <i class="bi bi-check-circle-fill me-1"></i>Verified
                </span>
            </div>

            {{-- Locked state --}}
            <div id="phone-locked" class="text-muted small py-2">
                <i class="bi bi-lock me-1"></i>
                Verify your email address first to unlock this field.
            </div>

            {{-- Unlocked state --}}
            <div id="phone-unlocked" class="d-none">
                <label class="form-label small text-muted" for="pref-phone">Number</label>
                <div class="input-group input-group-sm phone-input-group mb-2">
                    <select id="phone-country" class="form-select form-select-sm">
                        @foreach($countries as [$iso, $dial, $name])
                            <option value="{{ $dial }}" data-iso="{{ $iso }}"
                                {{ $iso === $defaultCountry ? 'selected' : '' }}>
                                {{ $iso }} {{ $dial }} — {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="tel" id="pref-phone" class="form-control"
                           placeholder="820000000" autocomplete="tel-national">
                </div>
                <button id="save-phone-btn" class="btn btn-outline-primary btn-sm w-100">
                    Save number
                </button>
                <div id="phone-error" class="text-danger small mt-1 d-none"></div>
            </div>
        </div>
    </div>

    {{-- Config-driven custom fields --}}
    @php $customFields = config('pwa.user_fields', []); @endphp
    @if(!empty($customFields))
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="section-label">Additional settings</div>
            @foreach($customFields as $field)
                <div class="mb-3">
                    <label class="form-label small text-muted" for="pref-custom-{{ $field['key'] }}">
                        {{ $field['label'] }}
                    </label>
                    @if($field['type'] === 'select')
                        <select id="pref-custom-{{ $field['key'] }}"
                                class="form-select form-select-sm"
                                data-custom-key="{{ $field['key'] }}">
                            <option value="">— select —</option>
                            @foreach($field['options'] ?? [] as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    @elseif($field['type'] === 'toggle')
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="pref-custom-{{ $field['key'] }}"
                                   data-custom-key="{{ $field['key'] }}">
                        </div>
                    @else
                        <input type="{{ $field['type'] }}"
                               id="pref-custom-{{ $field['key'] }}"
                               class="form-control form-control-sm"
                               placeholder="{{ $field['placeholder'] ?? '' }}"
                               data-custom-key="{{ $field['key'] }}">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Push notifications card ──────────────────────────────────────── --}}
    @if(config('pwa.push.enabled', true))
    <div class="card shadow-sm border-0 mb-3" id="push-card">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="small fw-medium">Push notifications</div>
                <div class="text-muted" style="font-size:.75rem" id="push-status-label">Checking…</div>
            </div>
            <div class="form-check form-switch mb-0 ms-2">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="pushToggle" disabled>
            </div>
        </div>
        {{-- Shown only when push is locked because phone isn't verified --}}
        <div id="push-phone-required" class="d-none px-3 pb-2">
            <small class="text-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                A verified phone number is required to enable push notifications.
            </small>
        </div>
    </div>
    @endif

    @stack('pwa-user-fields')
    @stack('pwa-user-settings')

</div>

<script>
(function () {
    'use strict';

    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const STORAGE = 'pwa_device_id';

    // ── Device ID ──────────────────────────────────────────────────────────
    function deviceId() {
        let id = localStorage.getItem(STORAGE);
        if (!id) {
            id = (crypto.randomUUID?.() ?? Math.random().toString(36).slice(2));
            localStorage.setItem(STORAGE, id);
        }
        return id;
    }

    // ── Simple fetch wrapper ───────────────────────────────────────────────
    async function post(url, body) {
        const res = await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);
        return data;
    }

    // ── State ──────────────────────────────────────────────────────────────
    // Loaded from server on open; drives badge + lock visibility
    let state = {
        emailVerified: false,
        phoneVerified: false,
        phone:         '',
    };

    // ── UI helpers ─────────────────────────────────────────────────────────
    function show(id)  { document.getElementById(id)?.classList.remove('d-none'); }
    function hide(id)  { document.getElementById(id)?.classList.add('d-none'); }
    function val(id)   { return document.getElementById(id)?.value?.trim() ?? ''; }
    function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v ?? ''; }

    function applyState() {
        // Email badges
        if (state.emailVerified) {
            show('email-verified-badge');
            hide('email-unverified-badge');
        } else if (val('pref-email')) {
            hide('email-verified-badge');
            show('email-unverified-badge');
        } else {
            hide('email-verified-badge');
            hide('email-unverified-badge');
        }

        // Phone section
        if (state.emailVerified) {
            hide('phone-locked');
            show('phone-unlocked');
        } else {
            show('phone-locked');
            hide('phone-unlocked');
        }

        // Phone verified badge
        if (state.phoneVerified) {
            show('phone-verified-badge');
        } else {
            hide('phone-verified-badge');
        }

        // Push toggle gating
        refreshPushToggle();
    }

    // ── Push toggle ────────────────────────────────────────────────────────
    async function refreshPushToggle() {
        const toggle    = document.getElementById('pushToggle');
        const statusLbl = document.getElementById('push-status-label');
        const lockedMsg = document.getElementById('push-phone-required');
        if (!toggle) return;

        if (!state.phoneVerified) {
            toggle.checked  = false;
            toggle.disabled = true;
            if (statusLbl) statusLbl.textContent = 'Requires verified phone number';
            show('push-phone-required');
            return;
        }

        hide('push-phone-required');

        if (!window.pushNotifications) return;

        const status = await window.pushNotifications.checkStatus();

        if (status.permission === 'denied') {
            if (statusLbl) statusLbl.textContent = 'Blocked — reset in browser settings';
            toggle.disabled = true;
            return;
        }

        toggle.disabled = false;
        toggle.checked  = status.subscribed;
        if (statusLbl) statusLbl.textContent = status.subscribed ? 'Enabled' : 'Disabled';
    }

    // ── Load preferences ───────────────────────────────────────────────────
    async function loadPreferences() {
        try {
            const res = await fetch('/app/preferences?device_id=' + encodeURIComponent(deviceId()), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            setVal('pref-name',  data.name);
            setVal('pref-email', data.email);

            // Phone — populate the number part only (strip dial code if present)
            if (data.phone) {
                const countrySelect = document.getElementById('phone-country');
                const selectedDial  = countrySelect?.value ?? '';
                if (selectedDial && data.phone.startsWith(selectedDial)) {
                    setVal('pref-phone', data.phone.slice(selectedDial.length));
                } else {
                    setVal('pref-phone', data.phone);
                }
            }

            const custom = data.custom_settings ?? {};
            document.querySelectorAll('[data-custom-key]').forEach(el => {
                const v = custom[el.dataset.customKey];
                if (el.type === 'checkbox') el.checked = !!v;
                else el.value = v ?? '';
            });

            state.emailVerified = !!data.email_verified;
            state.phoneVerified = !!data.phone_verified;
            state.phone         = data.phone ?? '';

            applyState();
        } catch (e) {
            console.warn('PWA: could not load preferences', e);
        }
    }

    // ── Save basic preferences (name + email + custom) ─────────────────────
    async function savePreferences() {
        const btn      = document.getElementById('pwa-save-prefs');
        const statusEl = document.getElementById('pwa-prefs-status');
        btn.disabled   = true;
        if (statusEl) statusEl.textContent = '';

        const custom = {};
        document.querySelectorAll('[data-custom-key]').forEach(el => {
            custom[el.dataset.customKey] = el.type === 'checkbox' ? el.checked : el.value;
        });

        const emailNow = val('pref-email');

        // If email changed, clear verified state locally
        if (state.emailVerified && emailNow !== document.getElementById('pref-email')._verifiedEmail) {
            state.emailVerified = false;
            state.phoneVerified = false;
            applyState();
        }

        try {
            const data = await post('/app/preferences', {
                device_id:       deviceId(),
                name:            val('pref-name'),
                email:           emailNow,
                custom_settings: custom,
            });
            state.emailVerified = !!data.email_verified;
            state.phoneVerified = !!data.phone_verified;
            applyState();
            window.showToast?.('Saved');
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Could not save — try again.';
        } finally {
            btn.disabled = false;
        }
    }

    // ── Email verification flow ────────────────────────────────────────────
    async function sendPin() {
        const btn   = document.getElementById('send-pin-btn');
        const email = val('pref-email');
        if (!email) { window.showToast?.('Enter an email address first', 'error'); return; }

        btn.disabled  = true;
        btn.textContent = '…';

        try {
            await post('/app/verify/send-pin', { device_id: deviceId(), email });
            show('pin-entry-row');
            show('pin-sent-hint');
            hide('pin-error');
            document.getElementById('pin-input')?.focus();
            window.showToast?.('Code sent — check your inbox');
        } catch (e) {
            window.showToast?.(e.message || 'Could not send code', 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Verify';
        }
    }

    async function verifyPin() {
        const btn = document.getElementById('verify-pin-btn');
        const pin = val('pin-input');
        const errEl = document.getElementById('pin-error');

        if (pin.length !== 4) {
            errEl.textContent = 'Enter the 4-digit code.';
            show('pin-error');
            return;
        }

        btn.disabled = true;
        hide('pin-error');

        try {
            await post('/app/verify/confirm-pin', { device_id: deviceId(), pin });

            // Mark verified locally
            state.emailVerified = true;
            document.getElementById('pref-email')._verifiedEmail = val('pref-email');

            hide('pin-entry-row');
            hide('pin-sent-hint');
            setVal('pin-input', '');
            applyState();
            window.showToast?.('Email verified ✓');
        } catch (e) {
            errEl.textContent = e.message || 'Incorrect code.';
            show('pin-error');
            document.getElementById('pin-input')?.select();
        } finally {
            btn.disabled = false;
        }
    }

    // ── Phone save ─────────────────────────────────────────────────────────
    async function savePhone() {
        const btn     = document.getElementById('save-phone-btn');
        const errEl   = document.getElementById('phone-error');
        const dialSel = document.getElementById('phone-country');
        const local   = val('pref-phone').replace(/\D/g, '');   // digits only

        hide('phone-error');

        if (!local) {
            errEl.textContent = 'Enter a phone number.';
            show('phone-error');
            return;
        }

        const dialCode = dialSel?.value ?? '+27';
        const e164     = dialCode + local;  // e.g. +27794999139

        btn.disabled = true;

        try {
            await post('/app/verify/phone', { device_id: deviceId(), phone: e164 });
            state.phoneVerified = true;
            state.phone         = e164;
            applyState();
            window.showToast?.('Phone number saved');
        } catch (e) {
            errEl.textContent = e.message || 'Could not save number.';
            show('phone-error');
        } finally {
            btn.disabled = false;
        }
    }

    // ── Push toggle change handler ─────────────────────────────────────────
    function bindPushToggle() {
        const toggle    = document.getElementById('pushToggle');
        const statusLbl = document.getElementById('push-status-label');
        if (!toggle) return;

        toggle.addEventListener('change', async () => {
            toggle.disabled = true;
            try {
                if (toggle.checked) {
                    await window.pushNotifications.subscribe();
                    if (statusLbl) statusLbl.textContent = 'Enabled';
                    window.showToast?.('Push notifications enabled');
                    // Remove top-bar bell once subscribed from here too
                    document.getElementById('enable-push')?.classList.add('d-none');
                } else {
                    await window.pushNotifications.unsubscribe();
                    if (statusLbl) statusLbl.textContent = 'Disabled';
                    window.showToast?.('Push notifications disabled');
                }
            } catch (e) {
                toggle.checked = !toggle.checked;
                window.showToast?.('Could not update — try again', 'error');
            } finally {
                toggle.disabled = false;
            }
        });
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        loadPreferences();
        bindPushToggle();

        document.getElementById('pwa-save-prefs')?.addEventListener('click', savePreferences);
        document.getElementById('send-pin-btn')?.addEventListener('click', sendPin);
        document.getElementById('resend-pin-btn')?.addEventListener('click', sendPin);
        document.getElementById('verify-pin-btn')?.addEventListener('click', verifyPin);
        document.getElementById('save-phone-btn')?.addEventListener('click', savePhone);

        // Auto-submit PIN when 4 digits are typed
        document.getElementById('pin-input')?.addEventListener('input', function () {
            if (this.value.replace(/\D/g,'').length === 4) verifyPin();
        });

        // Enforce digits-only in phone field
        document.getElementById('pref-phone')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
    });
})();
</script>