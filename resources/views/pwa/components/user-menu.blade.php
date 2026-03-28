@php
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
    .pin-input {
        letter-spacing: .3em; font-size: 1.2rem; font-weight: 700;
        text-align: center; width: 110px; flex-shrink: 0;
    }
    .section-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #9ca3af; margin-bottom: .6rem;
    }
    #pwa-prefs-status { min-height: 1rem; font-size: .8rem; }

    /* Push card at bottom */
    .push-card {
        border-top: 1px solid #f1f5f9;
        background: #fff;
        border-radius: 0 !important;
        margin: 0 -1rem -1rem;   /* bleed to panel edges */
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

    {{-- ── Basic info ───────────────────────────────────────────────────── --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="section-label">Basic info</div>

            {{-- Name (required — gates the Verify button) --}}
            <div class="mb-3">
                <label class="form-label small text-muted" for="pref-name">
                    Name <span class="text-danger">*</span>
                </label>
                <input type="text" id="pref-name"
                       class="form-control form-control-sm"
                       autocomplete="name"
                       placeholder="Your name"
                       required>
                <div id="name-error" class="text-danger small mt-1 d-none">
                    Please enter your name before verifying your email.
                </div>
            </div>

            {{-- Email + verify --}}
            <div class="mb-2">
                <label class="form-label small text-muted d-flex justify-content-between align-items-center"
                       for="pref-email">
                    <span>Email</span>
                    <span id="email-verified-badge"
                          class="d-none verified-badge bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill me-1"></i>Verified
                    </span>
                    <span id="email-unverified-badge"
                          class="d-none verified-badge bg-warning-subtle text-warning">
                        Unverified
                    </span>
                </label>
                <div class="input-group input-group-sm">
                    <input type="email" id="pref-email"
                           class="form-control" autocomplete="email"
                           placeholder="you@example.com">
                    <button class="btn btn-outline-secondary btn-sm"
                            id="send-pin-btn" type="button" disabled>
                        Verify
                    </button>
                </div>
                <div class="form-text d-none" id="pin-sent-hint">
                    A 4-digit code has been sent — check your inbox.
                </div>
            </div>

            {{-- PIN entry (hidden until code sent) --}}
            <div id="pin-entry-row" class="d-none mb-3">
                <label class="form-label small text-muted" for="pin-input">
                    Enter the 4-digit code
                </label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="pin-input"
                           inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                           class="form-control form-control-sm pin-input"
                           placeholder="0000" autocomplete="one-time-code">
                    <button class="btn btn-primary btn-sm" id="verify-pin-btn" type="button">
                        Confirm
                    </button>
                    <button class="btn btn-link btn-sm text-muted p-0"
                            id="resend-pin-btn" type="button">
                        Resend
                    </button>
                </div>
                <div id="pin-error" class="text-danger small mt-1 d-none"></div>
            </div>

            {{-- Auto-saved — no button needed. Status shown as a toast. --}}
            <div id="pwa-prefs-status" class="text-success small mt-1 d-none"></div>
        </div>
    </div>

    {{-- ── Mobile number (gated: email must be verified) ───────────────── --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="section-label d-flex justify-content-between align-items-center">
                <span>Mobile number</span>
                <span id="phone-verified-badge"
                      class="d-none verified-badge bg-success-subtle text-success">
                    <i class="bi bi-check-circle-fill me-1"></i>Verified
                </span>
            </div>

            <div id="phone-locked" class="text-muted small py-1">
                <i class="bi bi-lock me-1"></i>
                Verify your email address first.
            </div>

            <div id="phone-unlocked" class="d-none">
                {{-- Encode country list as JSON for the JS custom dropdown --}}
                @php
                    $countriesJson = json_encode(
                        array_values(array_map(
                            fn($c) => ['iso' => $c[0], 'dial' => $c[1], 'name' => $c[2]],
                            $countries
                        )),
                        JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                @endphp

                <label class="form-label small text-muted">Number</label>

                {{-- Custom country-code picker + number input --}}
                <div class="d-flex gap-0 mb-2" id="phone-input-row">

                    {{-- Trigger button (shows selected flag + dial code) --}}
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

                    {{-- Number input --}}
                    <input type="tel" id="pref-phone" class="form-control form-control-sm"
                           placeholder="794999139" autocomplete="tel-national"
                           style="border-radius:0 10px 10px 0; font-size:.85rem;">

                    {{-- Hidden input carries the resolved dial code for savePhone() --}}
                    <input type="hidden" id="phone-country" value="{{ $countries[0][1] ?? '+27' }}">
                </div>

                {{-- Dropdown panel --}}
                <div id="country-picker-dropdown"
                     class="d-none"
                     style="position:absolute; z-index:1200; width:calc(100% - 2rem);
                            max-height:260px; overflow:hidden; display:flex;
                            flex-direction:column; background:#fff;
                            border:1px solid #e5e7eb; border-radius:12px;
                            box-shadow:0 8px 24px rgba(0,0,0,.12);">

                    {{-- Search within dropdown --}}
                    <div class="p-2 border-bottom">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search" style="font-size:.75rem;color:#9ca3af"></i>
                            </span>
                            <input type="text" id="country-search"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Search country…"
                                   autocomplete="off"
                                   style="border-radius:0 8px 8px 0; font-size:.83rem;">
                        </div>
                    </div>

                    {{-- Scrollable list --}}
                    <ul id="country-list" role="listbox"
                        style="overflow-y:auto; flex:1; list-style:none;
                               margin:0; padding:4px 0;">
                    </ul>
                </div>

                {{-- Pass country data to JS --}}
                <script id="pwa-countries-data" type="application/json">{!! $countriesJson !!}</script>

                <button id="save-phone-btn"
                        class="btn btn-outline-primary btn-sm w-100">
                    Save number
                </button>
                <div id="phone-error" class="text-danger small mt-1 d-none"></div>
            </div>
        </div>
    </div>

    {{-- Config-driven custom fields --}}
    @php
        $customFields = config('pwa.user_fields', []);
        $registry     = app(\Lightworx\FilamentPwa\FieldOptions\FieldOptionsRegistry::class);
    @endphp
    @if(!empty($customFields))
    <div class="card shadow-sm border-0 mb-3">
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

                    // For non-searchable dynamic fields, resolve now so options
                    // are embedded in the HTML — no AJAX request needed.
                    $resolvedOptions = ($isDynamic && !$isSearchable && $registry->has($fKey))
                        ? $registry->resolve($fKey)
                        : [];
                @endphp
                <div class="mb-3">
                    <label class="form-label small text-muted"
                           for="pref-custom-{{ $fKey }}">
                        {{ $field['label'] }}
                    </label>

                    @if($fType === 'select' && $isSearchable)
                        {{--
                            AJAX / searchable select.
                            Rendered as a text input + hidden value + results list.
                            JS fetches /app/field-options/{key}?search=… on input.
                        --}}
                        <div class="pwa-searchable-select"
                             data-field-key="{{ $fKey }}"
                             data-options-url="{{ url('/app/field-options/' . $fKey) }}"
                             data-placeholder="{{ $placeholder }}">
                            <div class="input-group input-group-sm">
                                <input type="text"
                                       class="form-control pwa-search-input"
                                       placeholder="{{ $placeholder }}"
                                       autocomplete="off"
                                       aria-label="{{ $field['label'] }}">
                                <span class="input-group-text text-muted" style="cursor:default">
                                    <i class="bi bi-search" style="font-size:.75rem"></i>
                                </span>
                            </div>
                            {{-- Hidden input carries the actual saved value --}}
                            <input type="hidden"
                                   id="pref-custom-{{ $fKey }}"
                                   data-custom-key="{{ $fKey }}">
                            <div class="pwa-search-results list-group mt-1 d-none"
                                 style="max-height:180px;overflow-y:auto;font-size:.85rem;
                                        border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1)">
                            </div>
                            <div class="pwa-search-selected text-muted d-none"
                                 style="font-size:.78rem;margin-top:4px">
                                {{-- Shows the label of the currently selected value --}}
                            </div>
                        </div>

                    @elseif($fType === 'select')
                        {{--
                            Standard select — options are either a static array
                            from config or a dynamic resolver called at render time.
                        --}}
                        <select id="pref-custom-{{ $fKey }}"
                                class="form-select form-select-sm"
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
                                Call <code>PwaFieldOptions::register('{{ $fKey }}', ...)</code>
                                in your AppServiceProvider.
                            </div>
                        @endif

                    @elseif($fType === 'toggle')
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="pref-custom-{{ $fKey }}"
                                   data-custom-key="{{ $fKey }}">
                        </div>

                    @else
                        <input type="{{ $fType }}"
                               id="pref-custom-{{ $fKey }}"
                               class="form-control form-control-sm"
                               placeholder="{{ $placeholder }}"
                               data-custom-key="{{ $fKey }}">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @stack('pwa-user-fields')
    @stack('pwa-user-settings')

    {{-- ── Preaching reminders opt-in ────────────────────────────────────── --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="small fw-semibold">
                    <i class="bi bi-book me-1 text-muted"></i>Preaching reminders
                </div>
                <div class="text-muted" style="font-size:.73rem">
                    Notify me if I'm preaching this week
                </div>
            </div>
            <div class="form-check form-switch mb-0 ms-3">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="preachingRemindersToggle">
            </div>
        </div>
    </div>

    {{-- ── Inbox link ──────────────────────────────────────────────────── --}}
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
                <div class="text-muted" style="font-size:.73rem" id="um-msg-summary">
                    Loading…
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted" style="font-size:.75rem"></i>
        </div>
    </a>

    {{-- ── Push notifications — pinned to bottom ───────────────────────── --}}
    @if(config('pwa.push.enabled', true))
    <div class="mt-auto push-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="small fw-semibold">
                    <i class="bi bi-bell me-1 text-muted"></i>Push notifications
                </div>
                <div class="text-muted" style="font-size:.73rem" id="push-status-label">
                    Checking…
                </div>
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

    // Mirror device_id into a cookie so PHP middleware can read it server-side
    function writeDeviceIdCookie(id) {
        try {
            document.cookie = `pwa_device_id=${encodeURIComponent(id)}; max-age=${60*60*24*365}; path=/; SameSite=Lax`;
        } catch {}
    }

    // ── Device ID ──────────────────────────────────────────────────────────
    // Returns a stable device identifier.
    // Priority: push subscription endpoint (written by push-notifications.js)
    //           → existing localStorage value
    //           → new random UUID (non-push devices)
    //
    // When a push subscription exists, push-notifications.js writes the endpoint
    // to localStorage during checkStatus(). Because that call is async and happens
    // after service worker registration, we poll briefly on first load to let it
    // settle before we send the preferences request with the wrong id.
    async function resolveDeviceId() {
        const existing = localStorage.getItem(STORAGE);

        // If we already have a value that looks like a push endpoint, use it.
        if (existing && existing.startsWith('https://')) return existing;

        // If push is supported, wait up to 2 s for push-notifications.js to
        // write the endpoint. Poll every 100 ms.
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            for (let i = 0; i < 20; i++) {
                await new Promise(r => setTimeout(r, 100));
                const settled = localStorage.getItem(STORAGE);
                if (settled && settled.startsWith('https://')) return settled;
            }
        }

        // No push subscription — fall back to existing UUID or create one.
        if (existing) {
            writeDeviceIdCookie(existing);
            return existing;
        }
        const id = crypto.randomUUID?.() ?? Math.random().toString(36).slice(2);
        localStorage.setItem(STORAGE, id);
        writeDeviceIdCookie(id);
        return id;
    }

    // Synchronous version for use after resolveDeviceId() has already run.
    function deviceId() {
        return localStorage.getItem(STORAGE) ?? '';
    }

    // ── Fetch helper ───────────────────────────────────────────────────────
    async function post(url, body) {
        const res = await fetch(url, {
            method:  'POST',
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

    // ── Local state ────────────────────────────────────────────────────────
    let state = {
        emailVerified: false,
        phoneVerified: false,
        verifiedEmail: '',   // the email address that was verified
    };

    // ── DOM helpers ────────────────────────────────────────────────────────
    const $  = id => document.getElementById(id);
    const show = id => $(id)?.classList.remove('d-none');
    const hide = id => $(id)?.classList.add('d-none');
    const val  = id => $(id)?.value?.trim() ?? '';
    const setV = (id, v) => { const el = $(id); if (el) el.value = v ?? ''; };

    // ── Sync UI to state ───────────────────────────────────────────────────
    function applyState() {
        const emailVal = val('pref-email');

        // Email badges
        if (state.emailVerified && emailVal === state.verifiedEmail) {
            show('email-verified-badge');
            hide('email-unverified-badge');
        } else if (emailVal) {
            hide('email-verified-badge');
            show('email-unverified-badge');
            // If email has changed since verification, clear local verified state
            if (state.emailVerified && emailVal !== state.verifiedEmail) {
                state.emailVerified = false;
                state.phoneVerified = false;
            }
        } else {
            hide('email-verified-badge');
            hide('email-unverified-badge');
        }

        // Phone section gate
        if (state.emailVerified) {
            hide('phone-locked');
            show('phone-unlocked');
        } else {
            show('phone-locked');
            hide('phone-unlocked');
        }

        // Phone verified badge
        state.phoneVerified ? show('phone-verified-badge') : hide('phone-verified-badge');

        // Verify button — requires name to be filled
        updateVerifyButton();

        // Push toggle
        refreshPushToggle();
    }

    // ── Verify button gating (requires name) ──────────────────────────────
    function updateVerifyButton() {
        const btn          = $('send-pin-btn');
        const hasName      = val('pref-name').length > 0;
        const hasEmail     = val('pref-email').length > 0;
        const alreadyVerif = state.emailVerified && val('pref-email') === state.verifiedEmail;

        if (btn) {
            btn.disabled = !hasName || !hasEmail || alreadyVerif;
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
            if (statusLbl) statusLbl.textContent = 'Requires verified mobile number';
            show('push-phone-required');
            return;
        }

        hide('push-phone-required');

        if (!window.pushNotifications) {
            if (statusLbl) statusLbl.textContent = 'Not available';
            return;
        }

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
            // resolveDeviceId() waits for push-notifications.js to write the
            // push endpoint into localStorage before we fire the request.
            const id  = await resolveDeviceId();
            const res = await fetch(
                '/app/preferences?device_id=' + encodeURIComponent(id),
                { headers: { 'Accept': 'application/json' } }
            );
            if (!res.ok) return;
            const data = await res.json();

            setV('pref-name',  data.name);
            setV('pref-email', data.email);

            // Restore phone — strip the dial code prefix so only the local
            // number shows in the input. The custom picker already holds the dial code.
            if (data.phone) {
                const dialCode = $('phone-country')?.value ?? '';
                const local    = dialCode && data.phone.startsWith(dialCode)
                    ? data.phone.slice(dialCode.length)
                    : data.phone.replace(/^\+[0-9]{1,3}/, '');
                setV('pref-phone', local);
            }

            // Custom fields — standard inputs and plain selects
            const custom = data.custom_settings ?? {};
            document.querySelectorAll('[data-custom-key]').forEach(el => {
                const v = custom[el.dataset.customKey];
                if (el.type === 'hidden' && el.closest('.pwa-searchable-select')) {
                    // Searchable select — restore saved value + fetch its label
                    if (v) {
                        el.value = v;
                        restoreSearchableLabel(el.closest('.pwa-searchable-select'), v);
                    }
                } else if (el.type === 'checkbox') {
                    el.checked = !!v;
                } else {
                    el.value = v ?? '';
                }
            });

            state.emailVerified = !!data.email_verified;
            state.phoneVerified = !!data.phone_verified;
            state.verifiedEmail = data.email_verified ? (data.email ?? '') : '';

            // Restore preaching reminders toggle
            const prToggle = $('preachingRemindersToggle');
            if (prToggle) prToggle.checked = !!data.preaching_reminders;

            applyState();
        } catch (e) {
            console.warn('PWA: could not load preferences', e);
        }
    }

    // ── Auto-save preferences ──────────────────────────────────────────────
    // Triggered on blur / change with a short debounce. No save button needed.
    let autoSaveTimer = null;

    async function savePreferences({ silent = false } = {}) {
        // Name is required before anything can be saved
        if (!val('pref-name')) {
            show('name-error');
            return;
        }
        hide('name-error');

        const custom = {};
        document.querySelectorAll('[data-custom-key]').forEach(el => {
            // Skip the search text input — only the hidden value input matters
            if (el.classList.contains('pwa-search-input')) return;
            custom[el.dataset.customKey] = el.type === 'checkbox' ? el.checked : el.value;
        });

        try {
            const data = await post('/app/preferences', {
                device_id:       deviceId(),
                name:            val('pref-name'),
                email:           val('pref-email'),
                custom_settings: custom,
            });
            state.emailVerified = !!data.email_verified;
            state.phoneVerified = !!data.phone_verified;
            applyState();
            if (!silent) window.showToast?.('Saved');
        } catch (e) {
            window.showToast?.('Could not save — try again', 'error');
        }
    }

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => savePreferences(), 800);
    }

    // ── Send PIN ───────────────────────────────────────────────────────────
    async function sendPin() {
        // Guard: name required
        if (!val('pref-name')) {
            show('name-error');
            $('pref-name')?.focus();
            return;
        }
        hide('name-error');

        const btn   = $('send-pin-btn');
        const email = val('pref-email');
        if (!email) return;

        btn.disabled    = true;
        btn.textContent = '…';

        try {
            await post('/app/verify/send-pin', { device_id: deviceId(), email });
            show('pin-entry-row');
            show('pin-sent-hint');
            hide('pin-error');
            $('pin-input')?.focus();
            window.showToast?.('Code sent — check your inbox');
        } catch (e) {
            window.showToast?.(e.message || 'Could not send code', 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Verify';
        }
    }

    // ── Verify PIN ─────────────────────────────────────────────────────────
    async function verifyPin() {
        const btn   = $('verify-pin-btn');
        const pin   = val('pin-input');
        const errEl = $('pin-error');

        if (pin.replace(/\D/g,'').length !== 4) {
            errEl.textContent = 'Enter the 4-digit code.';
            show('pin-error');
            return;
        }

        btn.disabled = true;
        hide('pin-error');

        try {
            await post('/app/verify/confirm-pin', { device_id: deviceId(), pin });

            state.emailVerified = true;
            state.verifiedEmail = val('pref-email');

            hide('pin-entry-row');
            hide('pin-sent-hint');
            setV('pin-input', '');
            applyState();
            window.showToast?.('Email verified ✓');
        } catch (e) {
            errEl.textContent = e.message || 'Incorrect code.';
            show('pin-error');
            $('pin-input')?.select();
        } finally {
            btn.disabled = false;
        }
    }

    // ── Country picker ─────────────────────────────────────────────────────
    (function initCountryPicker() {
        const dataEl = document.getElementById('pwa-countries-data');
        if (!dataEl) return;

        const countries   = JSON.parse(dataEl.textContent);
        const btnEl       = document.getElementById('country-picker-btn');
        const dropdown    = document.getElementById('country-picker-dropdown');
        const listEl      = document.getElementById('country-list');
        const searchEl    = document.getElementById('country-search');
        const flagImg     = document.getElementById('country-flag-img');
        const flagFb      = document.getElementById('country-flag-fb');  // fallback text
        const dialLabel   = document.getElementById('country-dial-label');
        const hiddenInput = document.getElementById('phone-country');

        if (!btnEl || !dropdown || !listEl) return;

        // ── Flag helpers ──────────────────────────────────────────────────
        // Base path comes from <meta name="flags-path"> set in app.blade.php.
        // Falls back to /pwa/flags if the meta tag is absent.
        const FLAGS_BASE = (
            document.querySelector('meta[name="flags-path"]')?.content
            ?? '/pwa/flags'
        ).replace(/\/$/, '');  // strip trailing slash

        function flagUrl(iso) {
            return FLAGS_BASE + '/' + iso.toLowerCase() + '.png';
        }

        // ── Select a country ──────────────────────────────────────────────
        function selectCountry(country) {
            // Update trigger button
            flagImg.src          = flagUrl(country.iso);
            flagImg.alt          = country.name;
            flagImg.style.display = 'inline';
            flagFb.textContent   = country.iso;   // shown if image fails
            flagFb.style.display = 'none';
            dialLabel.textContent = country.dial;

            // Update hidden input (read by savePhone)
            hiddenInput.value = country.dial;

            // Aria
            btnEl.setAttribute('aria-expanded', 'false');
            closeDropdown();
        }

        // ── Render list ───────────────────────────────────────────────────
        function renderList(filter) {
            const q    = (filter || '').toLowerCase();
            const items = q
                ? countries.filter(c =>
                    c.name.toLowerCase().includes(q) ||
                    c.iso.toLowerCase().includes(q)  ||
                    c.dial.includes(q)
                  )
                : countries;

            listEl.innerHTML = '';

            if (!items.length) {
                listEl.innerHTML =
                    '<li><div class="text-muted small px-3 py-2">No results</div></li>';
                return;
            }

            items.forEach(c => {
                const li  = document.createElement('li');
                const btn = document.createElement('button');
                btn.type  = 'button';
                btn.setAttribute('role', 'option');
                btn.setAttribute('data-iso', c.iso);

                // Flag image
                const img = document.createElement('img');
                img.src    = flagUrl(c.iso);
                img.alt    = c.name;
                img.width  = 20;
                img.height = 14;
                img.style.cssText = 'border-radius:2px;object-fit:cover;flex-shrink:0';
                img.onerror = function() {
                    this.style.display = 'none';
                    const fb = document.createElement('span');
                    fb.textContent  = c.iso;
                    fb.style.cssText = 'font-size:.7rem;font-weight:700';
                    btn.insertBefore(fb, btn.firstChild);
                };

                // Name
                const name = document.createElement('span');
                name.textContent = c.name;

                // Dial code (right-aligned)
                const dial = document.createElement('span');
                dial.className   = 'dial';
                dial.textContent = c.dial;

                btn.appendChild(img);
                btn.appendChild(name);
                btn.appendChild(dial);
                btn.addEventListener('click', () => selectCountry(c));

                // Highlight currently selected
                if (c.dial === hiddenInput.value) btn.classList.add('active');

                li.appendChild(btn);
                listEl.appendChild(li);
            });
        }

        // ── Open / close ──────────────────────────────────────────────────
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
            const isOpen = btnEl.getAttribute('aria-expanded') === 'true';
            isOpen ? closeDropdown() : openDropdown();
        });

        // Close on outside click
        document.addEventListener('click', e => {
            if (!btnEl.contains(e.target) && !dropdown.contains(e.target)) {
                closeDropdown();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeDropdown();
        });

        // Filter on search input
        searchEl?.addEventListener('input', () => renderList(searchEl.value));

        // ── Initialise with default country ───────────────────────────────
        const defaultIso  = '{{ $defaultCountry }}';
        const defaultC    = countries.find(c => c.iso === defaultIso) || countries[0];
        if (defaultC) selectCountry(defaultC);
    })();

    // ── Save phone ─────────────────────────────────────────────────────────
    async function savePhone() {
        const btn    = $('save-phone-btn');
        const errEl  = $('phone-error');
        const local  = val('pref-phone').replace(/\D/g, '');

        hide('phone-error');

        if (!local) {
            errEl.textContent = 'Enter a phone number.';
            show('phone-error');
            return;
        }

        const dialCode = $('phone-country')?.value ?? '+27';
        const e164     = dialCode + local;

        btn.disabled = true;

        try {
            await post('/app/verify/phone', { device_id: deviceId(), phone: e164 });
            state.phoneVerified = true;
            applyState();
            window.showToast?.('Mobile number saved');
        } catch (e) {
            errEl.textContent = e.message || 'Could not save number.';
            show('phone-error');
        } finally {
            btn.disabled = false;
        }
    }

    // ── Push toggle change ─────────────────────────────────────────────────
    function bindPushToggle() {
        const toggle    = $('pushToggle');
        const statusLbl = $('push-status-label');
        if (!toggle) return;

        toggle.addEventListener('change', async () => {
            toggle.disabled = true;
            try {
                if (toggle.checked) {
                    await window.pushNotifications.subscribe();
                    if (statusLbl) statusLbl.textContent = 'Enabled';
                    $('enable-push')?.classList.add('d-none');
                    window.showToast?.('Push notifications enabled');
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

    // ── Searchable select ──────────────────────────────────────────────────
    //
    // Each .pwa-searchable-select widget works as follows:
    //   - Text input drives a debounced AJAX search to /app/field-options/{key}
    //   - Results appear in a floating .pwa-search-results list
    //   - Selecting a result writes the value to the hidden input and hides the list
    //   - On load, if a value is saved, we fetch its label from the server to display

    let searchTimers = {};

    function initSearchableSelects() {
        document.querySelectorAll('.pwa-searchable-select').forEach(widget => {
            const key        = widget.dataset.fieldKey;
            const url        = widget.dataset.optionsUrl;
            const searchInput= widget.querySelector('.pwa-search-input');
            const hiddenInput= widget.querySelector('input[type="hidden"]');
            const resultsList= widget.querySelector('.pwa-search-results');
            const selectedEl = widget.querySelector('.pwa-search-selected');

            if (!searchInput || !hiddenInput || !resultsList) return;

            // Fetch results from server
            async function fetchOptions(search) {
                try {
                    const res = await fetch(url + (search ? '?search=' + encodeURIComponent(search) : ''), {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return [];
                    const data = await res.json();
                    return data.options ?? [];
                } catch { return []; }
            }

            // Render results into the dropdown list
            function renderResults(options) {
                resultsList.innerHTML = '';
                if (!options.length) {
                    resultsList.innerHTML =
                        '<div class="list-group-item text-muted small py-2">No results</div>';
                } else {
                    options.forEach(opt => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action py-2';
                        item.textContent = opt.label;
                        item.addEventListener('mousedown', e => {
                            e.preventDefault(); // prevent blur before click registers
                            selectOption(opt.value, opt.label);
                        });
                        resultsList.appendChild(item);
                    });
                }
                resultsList.classList.remove('d-none');
            }

            // Commit a selection — then auto-save so the value is persisted
            function selectOption(value, label) {
                hiddenInput.value       = value;
                searchInput.value       = '';
                searchInput.placeholder = label;
                if (selectedEl) {
                    selectedEl.textContent = label;
                    selectedEl.classList.remove('d-none');
                }
                resultsList.classList.add('d-none');
                resultsList.innerHTML = '';
                // Save immediately — don't wait for blur
                scheduleAutoSave();
            }

            // Debounced input handler
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimers[key]);
                const q = searchInput.value.trim();
                if (!q) {
                    resultsList.classList.add('d-none');
                    return;
                }
                searchTimers[key] = setTimeout(async () => {
                    const options = await fetchOptions(q);
                    renderResults(options);
                }, 280);
            });

            // Hide results on blur (slight delay so mousedown fires first)
            searchInput.addEventListener('blur', () => {
                setTimeout(() => resultsList.classList.add('d-none'), 150);
            });

            // Show results again on focus if there's a query
            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim()) searchInput.dispatchEvent(new Event('input'));
            });
        });
    }

    // Restore the displayed label for a saved searchable-select value
    async function restoreSearchableLabel(widget, savedValue) {
        const url        = widget.dataset.optionsUrl;
        const searchInput= widget.querySelector('.pwa-search-input');
        const selectedEl = widget.querySelector('.pwa-search-selected');
        if (!url || !searchInput) return;

        try {
            // Pass ?value= so the endpoint can do a targeted lookup instead of
            // returning an unpredictable subset of the full list.
            const res = await fetch(
                url + '?value=' + encodeURIComponent(savedValue),
                { headers: { 'Accept': 'application/json' } }
            );
            if (!res.ok) return;
            const data    = await res.json();
            const options = data.options ?? [];
            // Endpoint returns just the matching item when ?value= is supplied,
            // but fall back to a find() in case the resolver ignores the param.
            const match = options.find(o => String(o.value) === String(savedValue))
                       ?? options[0];
            if (match) {
                searchInput.placeholder = match.label;
                if (selectedEl) {
                    selectedEl.textContent = match.label;
                    selectedEl.classList.remove('d-none');
                }
            }
        } catch { /* non-fatal */ }
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initSearchableSelects();
        loadPreferences();
        bindPushToggle();

        // Preaching reminders toggle
        $('preachingRemindersToggle')?.addEventListener('change', async function () {
            try {
                await post('/app/preferences/preaching-reminders', {
                    device_id: deviceId(),
                    enabled:   this.checked,
                });
                window.showToast?.(this.checked ? 'Preaching reminders enabled' : 'Preaching reminders disabled');
            } catch (e) {
                this.checked = !this.checked;   // revert on failure
                window.showToast?.('Could not update — try again', 'error');
            }
        });

        $('send-pin-btn')    ?.addEventListener('click',  sendPin);
        $('resend-pin-btn')  ?.addEventListener('click',  sendPin);
        $('verify-pin-btn')  ?.addEventListener('click',  verifyPin);
        $('save-phone-btn')  ?.addEventListener('click',  savePhone);

        // ── Auto-save on blur for text/email fields ───────────────────────
        ['pref-name', 'pref-email'].forEach(id => {
            $(id)?.addEventListener('blur',  scheduleAutoSave);
            $(id)?.addEventListener('input', updateVerifyButton);
        });
        $('pref-name')?.addEventListener('input', () => hide('name-error'));

        // Auto-save on change for toggle/select custom fields
        document.querySelectorAll('[data-custom-key]').forEach(el => {
            if (el.type === 'hidden') return;  // searchable selects save via selectOption
            const evt = (el.type === 'checkbox' || el.tagName === 'SELECT') ? 'change' : 'blur';
            el.addEventListener(evt, scheduleAutoSave);
        });

        // Auto-submit PIN on 4th digit
        $('pin-input')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
            if (this.value.length === 4) verifyPin();
        });

        // Digits only in phone local field
        $('pref-phone')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });

        // ── Inbox badge ────────────────────────────────────────────────────
        loadInboxBadge();
    });

    async function loadInboxBadge() {
        const badge   = document.getElementById('um-unread-badge');
        const summary = document.getElementById('um-msg-summary');
        if (!badge || !summary) return;

        try {
            const id  = await resolveDeviceId();
            const res = await fetch('/app/messages/unread?device_id=' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

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
        } catch { /* non-fatal */ }
    }
})();
</script>