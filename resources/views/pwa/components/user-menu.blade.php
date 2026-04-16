@php
/*
 * Country list for the phone dial-code picker.
 * Filtered/sorted per config('pwa.phone_countries') and phone_default_country.
 */
$allCountries = [
    ['ZA','+27','South Africa'],['US','+1','United States'],['GB','+44','United Kingdom'],
    ['AU','+61','Australia'],['NZ','+64','New Zealand'],['CA','+1','Canada'],
    ['NG','+234','Nigeria'],['KE','+254','Kenya'],['GH','+233','Ghana'],
    ['ZW','+263','Zimbabwe'],['ZM','+260','Zambia'],['BW','+267','Botswana'],
    ['NA','+264','Namibia'],['MZ','+258','Mozambique'],['TZ','+255','Tanzania'],
    ['UG','+256','Uganda'],['RW','+250','Rwanda'],['ET','+251','Ethiopia'],
    ['EG','+20','Egypt'],['MA','+212','Morocco'],['DZ','+213','Algeria'],
    ['TN','+216','Tunisia'],['SN','+221','Senegal'],['CI','+225',"Côte d'Ivoire"],
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
    $countries    = array_values(array_filter($allCountries, fn($c) => in_array($c[0], $allowedCodes)));
} else {
    $countries = $allCountries;
}
usort($countries, fn($a, $b) =>
    ($b[0] === $defaultCountry) - ($a[0] === $defaultCountry) ?: strcmp($a[2], $b[2])
);

$unknownNumberMessage = config('pwa.identity.unknown_message',
    'Your number is not yet linked to an account on this site.');
@endphp

<style>
    .pwa-user-panel {
        background: #f8fafc;
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }
    .pwa-user-panel .card         { border-radius: 14px; }
    .pwa-user-panel .form-control,
    .pwa-user-panel .form-select  {
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
        font-size: .68rem; font-weight: 600;
        padding: 2px 7px; border-radius: 20px; white-space: nowrap;
    }
    .phone-input-group .form-select { border-radius: 10px 0 0 10px; flex: 0 0 140px; }
    .phone-input-group .form-control { border-radius: 0 10px 10px 0; border-left: none; }
    .phone-input-group .form-control:focus { z-index: 3; }
    .pin-input {
        letter-spacing: .3em; font-size: 1.2rem; font-weight: 700;
        text-align: center; width: 110px; flex-shrink: 0;
    }
    .section-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #9ca3af; margin-bottom: .6rem;
    }
    /* Country picker */
    #country-picker-btn:focus { box-shadow: 0 0 0 2px rgba(59,130,246,.15); border-color: #3b82f6; outline: none; }
    #country-picker-btn:hover { background: #f9fafb; }
    #country-list li button {
        width: 100%; text-align: left; background: none; border: none;
        padding: 7px 14px; font-size: .84rem; display: flex;
        align-items: center; gap: 10px; cursor: pointer; color: #111827;
    }
    #country-list li button:hover,
    #country-list li button.active { background: #eff6ff; }
    #country-list li button .dial { color: #6b7280; font-size:.78rem;
                                    font-variant-numeric:tabular-nums; margin-left:auto; }
    /* Identity card */
    .identity-name { font-size: 1rem; font-weight: 600; color: #111827; }
    .identity-phone { font-size: .78rem; color: #6b7280; }
    /* Push card at bottom */
    .push-card {
        border-top: 1px solid #f1f5f9;
        background: #fff;
        border-radius: 0 !important;
        margin: 0 -1rem -1rem;
        padding: 12px 16px;
    }
    .push-card .form-check-input { width: 2.5em; height: 1.25em; }
</style>

<div class="pwa-user-panel p-3">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
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

    {{-- ── Identity card (shown once verified) ────────────────────────── --}}
    <div id="identity-card" class="card shadow-sm border-0 mb-3 d-none">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div id="identity-name" class="identity-name mb-1"></div>
                    <div id="identity-phone" class="identity-phone"></div>
                    {{-- Shown when the number isn't matched to a site user --}}
                    <div id="identity-unknown" class="d-none mt-2">
                        <small class="text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            {{ $unknownNumberMessage }}
                        </small>
                    </div>
                </div>
                <span class="verified-badge bg-success-subtle text-success flex-shrink-0">
                    <i class="bi bi-check-circle-fill me-1"></i>Verified
                </span>
            </div>
            <button id="change-number-btn"
                    class="btn btn-link btn-sm text-muted p-0 mt-2"
                    style="font-size:.75rem">
                <i class="bi bi-pencil me-1"></i>Change number
            </button>
        </div>
    </div>

    {{-- ── Phone verification (shown when NOT verified) ────────────────── --}}
    <div id="verification-card" class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="section-label">Verify your mobile number</div>

            {{-- Country picker + number input --}}
            @php
                $countriesJson = json_encode(
                    array_values(array_map(
                        fn($c) => ['iso' => $c[0], 'dial' => $c[1], 'name' => $c[2]],
                        $countries
                    )),
                    JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            @endphp

            <div id="phone-entry-section">
                <label class="form-label small text-muted">Mobile number</label>
                <div class="d-flex gap-0 mb-2">
                    <button type="button" id="country-picker-btn"
                            class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 flex-shrink-0"
                            style="border-radius:10px 0 0 10px; border-right:none;
                                   min-width:90px; font-size:.85rem; padding:6px 10px;"
                            aria-haspopup="listbox" aria-expanded="false">
                        <img id="country-flag-img" src="" alt="" width="20" height="14"
                             style="border-radius:2px;object-fit:cover;flex-shrink:0"
                             onerror="this.style.display='none';
                                      document.getElementById('country-flag-fb').style.display='inline'"
                             loading="lazy">
                        <span id="country-flag-fb" class="d-none"
                              style="font-size:.7rem;font-weight:700;letter-spacing:.04em"></span>
                        <span id="country-dial-label" style="font-variant-numeric:tabular-nums"></span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size:.65rem;opacity:.5"></i>
                    </button>
                    <input type="tel" id="pref-phone" class="form-control form-control-sm"
                           placeholder="794999139" autocomplete="tel-national"
                           style="border-radius:0 10px 10px 0; font-size:.85rem;">
                    <input type="hidden" id="phone-country" value="{{ $countries[0][1] ?? '+27' }}">
                </div>

                {{-- Country picker dropdown --}}
                <div id="country-picker-dropdown" class="d-none"
                     style="position:absolute; z-index:1200; width:calc(100% - 2rem);
                            max-height:260px; overflow:hidden; display:flex;
                            flex-direction:column; background:#fff;
                            border:1px solid #e5e7eb; border-radius:12px;
                            box-shadow:0 8px 24px rgba(0,0,0,.12);">
                    <div class="p-2 border-bottom">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search" style="font-size:.75rem;color:#9ca3af"></i>
                            </span>
                            <input type="text" id="country-search"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Search country…" autocomplete="off"
                                   style="border-radius:0 8px 8px 0; font-size:.83rem;">
                        </div>
                    </div>
                    <ul id="country-list" role="listbox"
                        style="overflow-y:auto; flex:1; list-style:none; margin:0; padding:4px 0;">
                    </ul>
                </div>

                <script id="pwa-countries-data" type="application/json">{!! $countriesJson !!}</script>

                <button id="send-sms-pin-btn" class="btn btn-primary btn-sm w-100">
                    Send verification code
                </button>
                <div id="phone-error" class="text-danger small mt-1 d-none"></div>
            </div>

            {{-- PIN entry (shown after SMS sent) --}}
            <div id="pin-entry-section" class="d-none">
                <div class="text-muted small mb-2" id="pin-sent-hint"></div>
                <label class="form-label small text-muted" for="pin-input">
                    Enter the 4-digit code
                </label>
                <div class="d-flex gap-2 align-items-center mb-1">
                    <input type="text" id="pin-input"
                           inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                           class="form-control form-control-sm pin-input"
                           placeholder="0000" autocomplete="one-time-code">
                    <button class="btn btn-primary btn-sm" id="verify-pin-btn" type="button">
                        Confirm
                    </button>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-link btn-sm text-muted p-0" id="resend-pin-btn" type="button">
                        Resend code
                    </button>
                    <button class="btn btn-link btn-sm text-muted p-0" id="change-phone-btn" type="button">
                        Change number
                    </button>
                </div>
                <div id="pin-error" class="text-danger small mt-1 d-none"></div>
            </div>
        </div>
    </div>

    {{-- ── Custom fields (gated: phone must be verified) ───────────────── --}}
    @php
        $customFields = config('pwa.user_fields', []);
        $registry     = app(\Lightworx\FilamentPwa\FieldOptions\FieldOptionsRegistry::class);
    @endphp
    @if(!empty($customFields))
    <div id="custom-fields-card" class="card shadow-sm border-0 mb-3 d-none">
        <div class="card-body">
            <div class="section-label">Additional settings</div>
            @foreach($customFields as $field)
                @php
                    $fKey         = $field['key'];
                    $fType        = $field['type'];
                    $fOptions     = $field['options'] ?? [];
                    $isDynamic    = $fOptions === 'dynamic';
                    $isSearchable = $isDynamic && !empty($field['searchable']);
                    $placeholder  = $field['placeholder'] ?? '— select —';
                    $resolvedOptions = ($isDynamic && !$isSearchable && $registry->has($fKey))
                        ? $registry->resolve($fKey)
                        : [];
                @endphp
                <div class="mb-3">
                    <label class="form-label small text-muted" for="pref-custom-{{ $fKey }}">
                        {{ $field['label'] }}
                    </label>

                    @if($fType === 'select' && $isSearchable)
                        <div class="pwa-searchable-select"
                             data-field-key="{{ $fKey }}"
                             data-options-url="{{ url('/app/field-options/' . $fKey) }}"
                             data-placeholder="{{ $placeholder }}">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control pwa-search-input"
                                       placeholder="{{ $placeholder }}" autocomplete="off"
                                       aria-label="{{ $field['label'] }}">
                                <span class="input-group-text text-muted" style="cursor:default">
                                    <i class="bi bi-search" style="font-size:.75rem"></i>
                                </span>
                            </div>
                            <input type="hidden" id="pref-custom-{{ $fKey }}" data-custom-key="{{ $fKey }}">
                            <div class="pwa-search-results list-group mt-1 d-none"
                                 style="max-height:180px;overflow-y:auto;font-size:.85rem;
                                        border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1)">
                            </div>
                            <div class="pwa-search-selected text-muted d-none" style="font-size:.78rem;margin-top:4px"></div>
                        </div>

                    @elseif($fType === 'select')
                        <select id="pref-custom-{{ $fKey }}" class="form-select form-select-sm"
                                data-custom-key="{{ $fKey }}">
                            <option value="">{{ $placeholder }}</option>
                            @if($isDynamic)
                                @foreach($resolvedOptions as $opt)
                                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            @else
                                @foreach($fOptions as $optVal => $optLbl)
                                    <option value="{{ $optVal }}">{{ $optLbl }}</option>
                                @endforeach
                            @endif
                        </select>
                        @if($isDynamic && !$registry->has($fKey))
                            <div class="text-warning small mt-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                No resolver registered for <code>{{ $fKey }}</code>.
                            </div>
                        @endif

                    @elseif($fType === 'toggle')
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="pref-custom-{{ $fKey }}" data-custom-key="{{ $fKey }}">
                        </div>

                    @else
                        <input type="{{ $fType }}" id="pref-custom-{{ $fKey }}"
                               class="form-control form-control-sm"
                               placeholder="{{ $placeholder }}" data-custom-key="{{ $fKey }}">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @stack('pwa-user-fields')
    @stack('pwa-user-settings')

    {{-- ── Preaching reminders (gated: phone must be verified) ─────────── --}}
    <div id="preaching-card" class="card shadow-sm border-0 mb-3 d-none">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="small fw-semibold">
                    <i class="bi bi-book me-1 text-muted"></i>Preaching reminders
                </div>
                <div class="text-muted" style="font-size:.73rem">
                    Notify me if I'm preaching this weekend
                </div>
            </div>
            <div class="form-check form-switch mb-0 ms-3">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="preachingRemindersToggle">
            </div>
        </div>
    </div>

    {{-- ── Inbox link ───────────────────────────────────────────────────── --}}
    <a href="/app/messages" class="card shadow-sm border-0 mb-3 text-decoration-none"
       style="display:block; border-radius:14px;">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
            <div class="position-relative flex-shrink-0">
                <i class="bi bi-inbox fs-5 text-muted"></i>
                <span id="um-unread-badge"
                      class="position-absolute top-0 start-100 translate-middle
                             badge rounded-pill bg-primary d-none"
                      style="font-size:.6rem">0</span>
            </div>
            <div class="flex-grow-1">
                <div class="small fw-semibold text-dark">Messages</div>
                <div class="text-muted" style="font-size:.73rem" id="um-msg-summary">Loading…</div>
            </div>
            <i class="bi bi-chevron-right text-muted" style="font-size:.75rem"></i>
        </div>
    </a>

    {{-- ── Push notifications — pinned to bottom ───────────────────────── --}}
    @if(config('pwa.push.enabled', true))
    <div id="push-card" class="mt-auto push-card d-none">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="small fw-semibold">
                    <i class="bi bi-bell me-1 text-muted"></i>Push notifications
                </div>
                <div class="text-muted" style="font-size:.73rem" id="push-status-label">Checking…</div>
            </div>
            <div class="form-check form-switch mb-0 ms-3">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="pushToggle" disabled>
            </div>
        </div>
        <div id="push-phone-required" class="d-none mt-1">
            <small class="text-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Requires a verified mobile number.
            </small>
        </div>
    </div>
    @endif

</div>

<script>
(function () {
    'use strict';

    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const STORAGE = 'pwa_device_id';

    // ── Cookie helper ──────────────────────────────────────────────────────
    function writeDeviceIdCookie(id) {
        try {
            document.cookie = `pwa_device_id=${encodeURIComponent(id)}; max-age=${60*60*24*365}; path=/; SameSite=Lax`;
        } catch {}
    }

    // ── Device ID ──────────────────────────────────────────────────────────
    let _resolvedDeviceId = null;

    async function resolveDeviceId() {
        if (_resolvedDeviceId) return _resolvedDeviceId;

        const existing = localStorage.getItem(STORAGE);
        if (existing && existing.startsWith('https://')) {
            _resolvedDeviceId = existing;
            return existing;
        }

        if ('serviceWorker' in navigator && 'PushManager' in window) {
            for (let i = 0; i < 20; i++) {
                await new Promise(r => setTimeout(r, 100));
                const settled = localStorage.getItem(STORAGE);
                if (settled && settled.startsWith('https://')) {
                    _resolvedDeviceId = settled;
                    return settled;
                }
            }
        }

        const id = existing ?? (crypto.randomUUID?.() ?? Math.random().toString(36).slice(2));
        if (!existing) {
            localStorage.setItem(STORAGE, id);
            writeDeviceIdCookie(id);
        } else {
            writeDeviceIdCookie(existing);
        }
        _resolvedDeviceId = id;
        return id;
    }

    function deviceId() {
        return _resolvedDeviceId ?? localStorage.getItem(STORAGE) ?? '';
    }

    // ── Fetch helper ───────────────────────────────────────────────────────
    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'HTTP ' + res.status);
        return data;
    }

    // ── DOM helpers ────────────────────────────────────────────────────────
    const $    = id => document.getElementById(id);
    const show = id => $(id)?.classList.remove('d-none');
    const hide = id => $(id)?.classList.add('d-none');
    const val  = id => $(id)?.value?.trim() ?? '';
    const setV = (id, v) => { const el = $(id); if (el) el.value = v ?? ''; };

    // ── State ──────────────────────────────────────────────────────────────
    let state = { phoneVerified: false };

    // ── Apply state to UI ──────────────────────────────────────────────────
    function applyState() {
        if (state.phoneVerified) {
            show('identity-card');
            hide('verification-card');
            // Show gated sections
            @if(!empty($customFields)) show('custom-fields-card'); @endif
            show('preaching-card');
            show('push-card');
        } else {
            hide('identity-card');
            show('verification-card');
            @if(!empty($customFields)) hide('custom-fields-card'); @endif
            hide('preaching-card');
            hide('push-card');
            // Always show phone entry (not PIN) unless mid-flow
            show('phone-entry-section');
            hide('pin-entry-section');
        }
        refreshPushToggle();
    }

    // ── Load preferences ───────────────────────────────────────────────────
    async function loadPreferences() {
        try {
            const id  = await resolveDeviceId();
            const res = await fetch('/app/preferences?device_id=' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            state.phoneVerified = !!data.phone_verified;

            if (state.phoneVerified) {
                // Identity card
                const nameEl  = $('identity-name');
                const phoneEl = $('identity-phone');

                if (nameEl) {
                    nameEl.textContent = data.resolved_name || '';
                }
                if (phoneEl) {
                    phoneEl.textContent = data.phone ?? '';
                }

                // Show unknown-number message if no name resolved
                if (!data.resolved_name) {
                    show('identity-unknown');
                } else {
                    hide('identity-unknown');
                }

                // Restore preaching toggle
                const prToggle = $('preachingRemindersToggle');
                if (prToggle) prToggle.checked = !!data.preaching_reminders;

                // Restore custom fields
                const custom = data.custom_settings ?? {};
                document.querySelectorAll('[data-custom-key]').forEach(el => {
                    const v = custom[el.dataset.customKey];
                    if (el.type === 'hidden' && el.closest('.pwa-searchable-select')) {
                        if (v) {
                            el.value = v;
                            restoreSearchableLabel(el.closest('.pwa-searchable-select'), v);
                        }
                    } else if (el.type === 'checkbox') {
                        el.checked = !!v;
                    } else if (!el.classList.contains('pwa-search-input')) {
                        el.value = v ?? '';
                    }
                });
            }

            applyState();
        } catch (e) {
            console.warn('PWA: could not load preferences', e);
            applyState();
        }
    }

    // ── Auto-save custom settings ──────────────────────────────────────────
    let autoSaveTimer = null;

    async function saveCustomSettings({ silent = false } = {}) {
        if (!state.phoneVerified) return;

        const custom = {};
        document.querySelectorAll('[data-custom-key]').forEach(el => {
            if (el.classList.contains('pwa-search-input')) return;
            custom[el.dataset.customKey] = el.type === 'checkbox' ? el.checked : el.value;
        });

        try {
            await post('/app/preferences', {
                device_id:       deviceId(),
                custom_settings: custom,
            });
            if (!silent) window.showToast?.('Saved');
        } catch {
            window.showToast?.('Could not save — try again', 'error');
        }
    }

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => saveCustomSettings(), 800);
    }

    // ── Country picker ─────────────────────────────────────────────────────
    (function initCountryPicker() {
        const dataEl = document.getElementById('pwa-countries-data');
        if (!dataEl) return;

        const countries   = JSON.parse(dataEl.textContent);
        const btnEl       = $('country-picker-btn');
        const dropdown    = $('country-picker-dropdown');
        const listEl      = $('country-list');
        const searchEl    = $('country-search');
        const flagImg     = $('country-flag-img');
        const flagFb      = $('country-flag-fb');
        const dialLabel   = $('country-dial-label');
        const hiddenInput = $('phone-country');

        if (!btnEl || !dropdown || !listEl) return;

        const FLAGS_BASE = (
            document.querySelector('meta[name="flags-path"]')?.content ?? '/pwa/flags'
        ).replace(/\/$/, '');

        function flagUrl(iso) { return FLAGS_BASE + '/' + iso.toLowerCase() + '.png'; }

        function selectCountry(country) {
            flagImg.src           = flagUrl(country.iso);
            flagImg.alt           = country.name;
            flagImg.style.display = 'inline';
            flagFb.textContent    = country.iso;
            flagFb.style.display  = 'none';
            dialLabel.textContent = country.dial;
            hiddenInput.value     = country.dial;
            btnEl.setAttribute('aria-expanded', 'false');
            closeDropdown();
        }

        function renderList(filter) {
            const q     = (filter || '').toLowerCase();
            const items = q
                ? countries.filter(c =>
                    c.name.toLowerCase().includes(q) ||
                    c.iso.toLowerCase().includes(q)  ||
                    c.dial.includes(q))
                : countries;

            listEl.innerHTML = '';
            if (!items.length) {
                listEl.innerHTML = '<li><div class="text-muted small px-3 py-2">No results</div></li>';
                return;
            }
            items.forEach(c => {
                const li  = document.createElement('li');
                const btn = document.createElement('button');
                btn.type  = 'button';
                btn.setAttribute('role', 'option');

                const img = document.createElement('img');
                img.src    = flagUrl(c.iso);
                img.alt    = c.name;
                img.width  = 20; img.height = 14;
                img.style.cssText = 'border-radius:2px;object-fit:cover;flex-shrink:0';
                img.onerror = function () {
                    this.style.display = 'none';
                    const fb = document.createElement('span');
                    fb.textContent  = c.iso;
                    fb.style.cssText = 'font-size:.7rem;font-weight:700';
                    btn.insertBefore(fb, btn.firstChild);
                };

                const name = document.createElement('span');
                name.textContent = c.name;
                const dial = document.createElement('span');
                dial.className   = 'dial';
                dial.textContent = c.dial;

                btn.appendChild(img);
                btn.appendChild(name);
                btn.appendChild(dial);
                btn.addEventListener('click', () => selectCountry(c));
                if (c.dial === hiddenInput?.value) btn.classList.add('active');

                li.appendChild(btn);
                listEl.appendChild(li);
            });
        }

        function openDropdown() {
            renderList('');
            dropdown.style.display = 'flex';
            dropdown.classList.remove('d-none');
            btnEl.setAttribute('aria-expanded', 'true');
            setTimeout(() => searchEl?.focus(), 40);
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
            dropdown.classList.add('d-none');
            btnEl.setAttribute('aria-expanded', 'false');
            if (searchEl) searchEl.value = '';
        }

        btnEl.addEventListener('click', () => {
            btnEl.getAttribute('aria-expanded') === 'true' ? closeDropdown() : openDropdown();
        });
        document.addEventListener('click', e => {
            if (!btnEl.contains(e.target) && !dropdown.contains(e.target)) closeDropdown();
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDropdown(); });
        searchEl?.addEventListener('input', () => renderList(searchEl.value));

        const defaultIso = '{{ $defaultCountry }}';
        const defaultC   = countries.find(c => c.iso === defaultIso) || countries[0];
        if (defaultC) selectCountry(defaultC);
    })();

    // ── SMS verification flow ──────────────────────────────────────────────
    async function sendSmsPin() {
        const btn     = $('send-sms-pin-btn');
        const errEl   = $('phone-error');
        const local   = val('pref-phone').replace(/\D/g, '').replace(/^0/, '');

        hide('phone-error');

        if (!local) {
            errEl.textContent = 'Please enter your mobile number.';
            show('phone-error');
            return;
        }

        const dialCode = $('phone-country')?.value ?? '+27';
        const e164     = dialCode + local;

        btn.disabled    = true;
        btn.textContent = 'Sending…';

        try {
            const id = await resolveDeviceId();
            await post('/app/verify/send-pin', { device_id: id, phone: e164 });

            // Switch to PIN entry view
            hide('phone-entry-section');
            show('pin-entry-section');
            const hint = $('pin-sent-hint');
            if (hint) hint.textContent = `Code sent to ${e164}. Check your messages.`;
            $('pin-input')?.focus();
        } catch (e) {
            // 403 = number not found in identity model (require_known_number=true)
            // Show the server's message verbatim — it's already user-friendly
            errEl.textContent = e.message || 'Could not send SMS. Please try again.';
            show('phone-error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Send verification code';
        }
    }

    async function verifyPin() {
        const btn   = $('verify-pin-btn');
        const pin   = val('pin-input').replace(/\D/g, '');
        const errEl = $('pin-error');

        if (pin.length !== 4) {
            errEl.textContent = 'Enter the 4-digit code from your SMS.';
            show('pin-error');
            return;
        }

        btn.disabled = true;
        hide('pin-error');

        try {
            const id   = await resolveDeviceId();
            const data = await post('/app/verify/confirm-pin', { device_id: id, pin });

            state.phoneVerified = true;

            // Populate identity card from response
            const nameEl = $('identity-name');
            if (nameEl) nameEl.textContent = data.resolved_name || '';

            const phoneEl = $('identity-phone');
            if (phoneEl) {
                // Reconstruct E.164 from dial code + input
                const dialCode = $('phone-country')?.value ?? '+27';
                const local    = val('pref-phone').replace(/\D/g, '').replace(/^0/, '');
                if (phoneEl) phoneEl.textContent = dialCode + local;
            }

            if (!data.resolved_name) {
                show('identity-unknown');
            } else {
                hide('identity-unknown');
            }

            setV('pin-input', '');
            applyState();
            window.showToast?.('Mobile number verified ✓');
        } catch (e) {
            errEl.textContent = e.message || 'Incorrect code. Please try again.';
            show('pin-error');
            $('pin-input')?.select();
        } finally {
            btn.disabled = false;
        }
    }

    // ── Push toggle ────────────────────────────────────────────────────────
    async function refreshPushToggle() {
        const toggle    = $('pushToggle');
        const statusLbl = $('push-status-label');
        if (!toggle) return;

        if (!state.phoneVerified) {
            toggle.checked  = false;
            toggle.disabled = true;
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

    // ── Searchable select restore ──────────────────────────────────────────
    let searchTimers = {};

    function initSearchableSelects() {
        document.querySelectorAll('.pwa-searchable-select').forEach(widget => {
            const key         = widget.dataset.fieldKey;
            const url         = widget.dataset.optionsUrl;
            const searchInput = widget.querySelector('.pwa-search-input');
            const hiddenInput = widget.querySelector('input[type="hidden"]');
            const resultsList = widget.querySelector('.pwa-search-results');
            const selectedEl  = widget.querySelector('.pwa-search-selected');

            if (!searchInput || !hiddenInput || !resultsList) return;

            async function fetchOptions(search) {
                try {
                    const res = await fetch(url + (search ? '?search=' + encodeURIComponent(search) : ''), {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return [];
                    return (await res.json()).options ?? [];
                } catch { return []; }
            }

            function renderResults(options) {
                resultsList.innerHTML = '';
                if (!options.length) {
                    resultsList.innerHTML = '<div class="list-group-item text-muted small py-2">No results</div>';
                } else {
                    options.forEach(opt => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action py-2';
                        item.textContent = opt.label;
                        item.addEventListener('mousedown', e => {
                            e.preventDefault();
                            selectOption(opt.value, opt.label);
                        });
                        resultsList.appendChild(item);
                    });
                }
                resultsList.classList.remove('d-none');
            }

            function selectOption(value, label) {
                hiddenInput.value       = value;
                searchInput.value       = '';
                searchInput.placeholder = label;
                if (selectedEl) { selectedEl.textContent = label; selectedEl.classList.remove('d-none'); }
                resultsList.classList.add('d-none');
                resultsList.innerHTML = '';
                scheduleAutoSave();
            }

            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimers[key]);
                const q = searchInput.value.trim();
                if (!q) { resultsList.classList.add('d-none'); return; }
                searchTimers[key] = setTimeout(async () => renderResults(await fetchOptions(q)), 280);
            });
            searchInput.addEventListener('blur',  () => setTimeout(() => resultsList.classList.add('d-none'), 150));
            searchInput.addEventListener('focus', () => { if (searchInput.value.trim()) searchInput.dispatchEvent(new Event('input')); });
        });
    }

    async function restoreSearchableLabel(widget, savedValue) {
        const url        = widget.dataset.optionsUrl;
        const searchInput= widget.querySelector('.pwa-search-input');
        const selectedEl = widget.querySelector('.pwa-search-selected');
        if (!url || !searchInput) return;
        try {
            const res     = await fetch(url + '?value=' + encodeURIComponent(savedValue), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const options = (await res.json()).options ?? [];
            const match   = options.find(o => String(o.value) === String(savedValue)) ?? options[0];
            if (match) {
                searchInput.placeholder = match.label;
                if (selectedEl) { selectedEl.textContent = match.label; selectedEl.classList.remove('d-none'); }
            }
        } catch {}
    }

    // ── Inbox badge ────────────────────────────────────────────────────────
    async function loadInboxBadge() {
        const badge   = $('um-unread-badge');
        const summary = $('um-msg-summary');
        if (!badge || !summary) return;
        try {
            const id  = await resolveDeviceId();
            const res = await fetch('/app/messages/unread?device_id=' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data   = await res.json();
            const unread = data.unread ?? 0;
            const total  = data.total  ?? 0;
            if (unread > 0) {
                badge.textContent = unread > 99 ? '99+' : unread;
                badge.classList.remove('d-none');
                summary.textContent = `${unread} unread of ${total}`;
            } else if (total > 0) {
                badge.classList.add('d-none');
                summary.textContent = `${total} message${total !== 1 ? 's' : ''}, all read`;
            } else {
                badge.classList.add('d-none');
                summary.textContent = 'No messages yet';
            }
        } catch {}
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initSearchableSelects();
        loadPreferences();
        loadInboxBadge();

        // SMS verification
        $('send-sms-pin-btn')?.addEventListener('click', sendSmsPin);
        $('resend-pin-btn')  ?.addEventListener('click', () => {
            hide('pin-entry-section');
            show('phone-entry-section');
            setV('pin-input', '');
            hide('pin-error');
        });
        $('change-phone-btn')?.addEventListener('click', () => {
            hide('pin-entry-section');
            show('phone-entry-section');
            setV('pin-input', '');
            hide('pin-error');
        });
        $('change-number-btn')?.addEventListener('click', () => {
            // Allow re-verification — show entry form, keep identity card visible
            // until new number is confirmed
            show('verification-card');
            show('phone-entry-section');
            hide('pin-entry-section');
        });

        // PIN auto-submit on 4th digit
        $('pin-input')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
            if (this.value.length === 4) verifyPin();
        });
        $('verify-pin-btn')?.addEventListener('click', verifyPin);

        // Digits only in phone input
        $('pref-phone')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });

        // Preaching reminders toggle
        $('preachingRemindersToggle')?.addEventListener('change', async function () {
            const checked = this.checked;
            try {
                const id = await resolveDeviceId();
                await post('/app/preferences/preaching-reminders', { device_id: id, enabled: checked });
                window.showToast?.(checked ? 'Preaching reminders enabled' : 'Preaching reminders disabled');
            } catch {
                this.checked = !checked;
                window.showToast?.('Could not update — try again', 'error');
            }
        });

        // Push toggle
        $('pushToggle')?.addEventListener('change', async function () {
            this.disabled = true;
            const statusLbl = $('push-status-label');
            try {
                if (this.checked) {
                    await window.pushNotifications.subscribe();
                    if (statusLbl) statusLbl.textContent = 'Enabled';
                    $('enable-push')?.classList.add('d-none');
                    window.showToast?.('Push notifications enabled');
                } else {
                    await window.pushNotifications.unsubscribe();
                    if (statusLbl) statusLbl.textContent = 'Disabled';
                    window.showToast?.('Push notifications disabled');
                }
            } catch {
                this.checked = !this.checked;
                window.showToast?.('Could not update — try again', 'error');
            } finally {
                this.disabled = false;
            }
        });

        // Auto-save custom fields on change/blur
        document.querySelectorAll('[data-custom-key]').forEach(el => {
            if (el.type === 'hidden') return;
            const evt = (el.type === 'checkbox' || el.tagName === 'SELECT') ? 'change' : 'blur';
            el.addEventListener(evt, scheduleAutoSave);
        });
    });
})();
</script>