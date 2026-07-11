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

// Base URL for all API calls — read from meta tag set in app.blade.php layout
// Falls back to computing it directly so the component works even if included standalone
$pwaPrefix = config('pwa.route_prefix', 'app');
$pwaDomain = config('pwa.route_domain');
if ($pwaDomain) {
    $pwaBase = rtrim(
        parse_url(config('app.url'), PHP_URL_SCHEME) . '://'
        . $pwaDomain . '.' . parse_url(config('app.url'), PHP_URL_HOST),
        '/'
    );
} else {
    $pwaBase = $pwaPrefix !== '' ? rtrim(url($pwaPrefix), '/') : rtrim(url('/'), '/');
}
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

    /* Profile picture */
    .profile-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        object-fit: cover; flex-shrink: 0;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
    }
    .avatar-placeholder {
        width: 64px; height: 64px; border-radius: 50%;
        flex-shrink: 0; background: var(--pwa-accent, #3b82f6);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; font-weight: 700; color: #fff;
        border: 2px solid #e5e7eb; cursor: pointer;
    }
    .avatar-wrap { position: relative; cursor: pointer; }
    .avatar-wrap:hover .avatar-edit-hint { opacity: 1; }
    .avatar-edit-hint {
        position: absolute; inset: 0; border-radius: 50%;
        background: rgba(0,0,0,.45); display: flex;
        align-items: center; justify-content: center;
        opacity: 0; transition: opacity .2s;
    }
    .avatar-edit-hint i { color: #fff; font-size: 1.1rem; }
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
            <div class="d-flex align-items-center gap-3">

                {{-- Profile picture / initials placeholder --}}
                <div class="avatar-wrap flex-shrink-0"
                     id="avatar-wrap"
                     title="Change profile picture"
                     role="button" tabindex="0"
                     aria-label="Change profile picture">
                    {{-- Populated by JS --}}
                    <div class="avatar-placeholder" id="avatar-placeholder">?</div>
                    <img id="avatar-img" class="profile-avatar d-none" src="" alt="Profile picture">
                    <div class="avatar-edit-hint">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                </div>

                {{-- Name, phone, verified badge --}}
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div id="identity-name" class="identity-name"></div>
                        <span class="verified-badge bg-success-subtle text-success">
                            <i class="bi bi-check-circle-fill me-1"></i>Verified
                        </span>
                    </div>
                    <div id="identity-phone" class="identity-phone"></div>
                    <div id="identity-unknown" class="d-none mt-1">
                        <small class="text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            {{ $unknownNumberMessage }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Hidden file input (triggered by avatar tap) --}}
            <input type="file" id="avatar-file-input"
                   accept="image/*"
                   class="d-none">

            {{-- Picture action bar (shown after avatar tap) --}}
            <div id="avatar-action-bar" class="d-none mt-3 pt-3 border-top">
                <div class="d-flex gap-2 flex-wrap">
                    <button id="avatar-upload-btn"
                            class="btn btn-outline-primary btn-sm flex-fill">
                        <i class="bi bi-upload me-1"></i>Upload photo
                    </button>
                    <button id="avatar-camera-btn"
                            class="btn btn-outline-secondary btn-sm flex-fill">
                        <i class="bi bi-camera me-1"></i>Take photo
                    </button>
                    <button id="avatar-remove-btn"
                            class="btn btn-outline-danger btn-sm d-none">
                        <i class="bi bi-trash me-1"></i>Remove
                    </button>
                </div>
                <button id="avatar-cancel-btn"
                        class="btn btn-link btn-sm text-muted p-0 mt-1"
                        style="font-size:.75rem">
                    Cancel
                </button>
            </div>

            {{-- Upload progress --}}
            <div id="avatar-uploading" class="d-none mt-2 text-muted small">
                <div class="spinner-border spinner-border-sm me-2"></div>Uploading…
            </div>
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
                           placeholder="820000000" autocomplete="tel-national"
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
                             data-options-url="{{ $pwaBase }}/field-options/{{ $fKey }}"
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

    {{-- ── Inbox link (hidden until phone verified + identity resolved) ─── --}}
    @if(config('pwa.messages.enabled', true))
    <a id="inbox-link" href="{{ $pwaBase }}/messages"
       class="card shadow-sm border-0 mb-3 text-decoration-none d-none"
       style="display:none; border-radius:14px;">
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
    @endif

    {{-- ── Push notifications + sign out — pinned to bottom ────────────── --}}
    <div id="push-card" class="mt-auto push-card d-none">
        @if(config('pwa.push.enabled', true))
        {{-- Not yet subscribed --}}
        <div id="push-enable-row" class="d-none">
            <button id="push-enable-btn"
                    class="btn btn-primary btn-sm w-100 py-2">
                <i class="bi bi-bell me-2"></i>Enable push notifications
            </button>
            <div id="push-enable-error" class="text-danger small mt-1 d-none"></div>
        </div>

        {{-- Already subscribed --}}
        <div id="push-enabled-row" class="d-none">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-bell-fill text-success" style="font-size:1rem"></i>
                <div class="flex-grow-1">
                    <div class="small fw-semibold">Push notifications enabled</div>
                    <div id="push-disable-wrap" class="d-none">
                        <a href="#" id="push-disable-link"
                           class="text-muted" style="font-size:.7rem">
                            Turn off
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Permission denied --}}
        <div id="push-blocked-row" class="d-none">
            <div class="d-flex align-items-center gap-2 text-muted">
                <i class="bi bi-bell-slash" style="font-size:1rem"></i>
                <div class="small">
                    Notifications blocked — reset in your browser settings to enable.
                </div>
            </div>
        </div>
        @endif

        {{-- Sign out — always visible once verified ─────────────────────── --}}
        <div class="mt-2 pt-2 border-top text-center">
            <a href="#" id="sign-out-link"
               class="text-muted"
               style="font-size:.72rem">
                <i class="bi bi-box-arrow-right me-1"></i>Sign out / change number
            </a>
        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const PWA_BASE = (document.querySelector('meta[name="pwa-base"]')?.content ?? '/app')
                     .replace(/\/$/, '');
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

        // If we already have a push endpoint stored, use it immediately.
        if (existing && existing.startsWith('https://')) {
            _resolvedDeviceId = existing;
            writeDeviceIdCookie(existing);
            return existing;
        }

        // Only poll for a push endpoint if push is already subscribed in the
        // browser — avoids a 2-second wait on first-time (unsubscribed) installs.
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    // Push is active — wait briefly for push-notifications.js to
                    // write the endpoint to localStorage
                    for (let i = 0; i < 20; i++) {
                        await new Promise(r => setTimeout(r, 100));
                        const settled = localStorage.getItem(STORAGE);
                        if (settled && settled.startsWith('https://')) {
                            _resolvedDeviceId = settled;
                            writeDeviceIdCookie(settled);
                            return settled;
                        }
                    }
                    // Fell through — use the endpoint directly
                    localStorage.setItem(STORAGE, sub.endpoint);
                    writeDeviceIdCookie(sub.endpoint);
                    _resolvedDeviceId = sub.endpoint;
                    return sub.endpoint;
                }
            } catch { /* SW not ready — fall through to UUID */ }
        }

        // No push subscription — use existing UUID or create a new one.
        // This will be replaced by the push endpoint after store() merges
        // the row once push is enabled.
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
    let state = { phoneVerified: false, identityResolved: false, pictureUrl: null };

    // ── Apply state to UI ──────────────────────────────────────────────────
    function applyState() {
        if (state.phoneVerified) {
            show('identity-card');
            hide('verification-card');
            // Show gated sections
            @if(!empty($customFields)) show('custom-fields-card'); @endif
            show('push-card');
            loadInboxBadge();
            // Inbox only shown when identity is also resolved (name found)
            if (state.identityResolved) {
                const inboxEl = document.getElementById('inbox-link');
                if (inboxEl) inboxEl.style.display = 'block';
            }
        } else {
            hide('identity-card');
            show('verification-card');
            @if(!empty($customFields)) hide('custom-fields-card'); @endif
            hide('push-card');
            const inboxEl = document.getElementById('inbox-link');
            if (inboxEl) inboxEl.style.display = 'none';
            // Always show phone entry (not PIN) unless mid-flow
            show('phone-entry-section');
            hide('pin-entry-section');
        }
        refreshPushUI();
    }

    // ── Load preferences ───────────────────────────────────────────────────
    async function loadPreferences() {
        try {
            const id  = await resolveDeviceId();
            const res = await fetch(PWA_BASE + '/preferences?device_id=' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            console.debug('[PWA] preferences response', {
                device_id:        id,
                phone_verified:   data.phone_verified,
                resolved_name:    data.resolved_name,
                resolved_picture: data.resolved_picture,
            });

            state.phoneVerified    = !!data.phone_verified;
            state.identityResolved = !!data.phone_verified && !!data.resolved_name;

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

                // Profile picture
                state.pictureUrl = data.resolved_picture || null;
                renderAvatar(state.pictureUrl, data.resolved_name || '');

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
            await post(PWA_BASE + '/preferences', {
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
            await post(PWA_BASE + '/verify/send-pin', { device_id: id, phone: e164 });

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
            const data = await post(PWA_BASE + '/verify/confirm-pin', { device_id: id, pin });

            state.phoneVerified    = true;
            state.identityResolved = !!data.resolved_name;

            // Populate identity card from response
            const nameEl = $('identity-name');
            if (nameEl) nameEl.textContent = data.resolved_name || '';

            const phoneEl = $('identity-phone');
            if (phoneEl) {
                const dialCode = $('phone-country')?.value ?? '+27';
                const local    = val('pref-phone').replace(/\D/g, '').replace(/^0/, '');
                phoneEl.textContent = dialCode + local;
            }

            if (!data.resolved_name) {
                show('identity-unknown');
            } else {
                hide('identity-unknown');
            }

            // Profile picture (may not be set yet at verify time)
            state.pictureUrl = data.resolved_picture || null;
            renderAvatar(state.pictureUrl, data.resolved_name || '');

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

    // ── Push notifications (one-way enable, hard to turn off) ────────────
    async function refreshPushUI() {
        // Push card only visible when phone is verified
        if (!state.phoneVerified) return;

        if (!window.pushNotifications) return;

        const status = await window.pushNotifications.checkStatus();

        // Show the correct row
        hide('push-enable-row');
        hide('push-enabled-row');
        hide('push-blocked-row');

        if (status.permission === 'denied') {
            show('push-blocked-row');
        } else if (status.subscribed) {
            show('push-enabled-row');
            // "Turn off" link hidden by default — reveal only after a deliberate
            // hover/click sequence to make accidental disabling very unlikely
        } else {
            show('push-enable-row');
        }
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

    // ── Avatar rendering ───────────────────────────────────────────────────
    function renderAvatar(url, name) {
        const img  = $('avatar-img');
        const ph   = $('avatar-placeholder');
        const rmBtn = $('avatar-remove-btn');

        if (url) {
            if (img) {
                img.src = url;
                img.classList.remove('d-none');
            }
            if (ph) ph.classList.add('d-none');
            if (rmBtn) rmBtn.classList.remove('d-none');  // can remove upload
        } else {
            if (img) img.classList.add('d-none');
            if (ph) {
                // Show initials
                const initials = (name || '?')
                    .split(' ')
                    .map(w => w[0] ?? '')
                    .slice(0, 2)
                    .join('')
                    .toUpperCase();
                ph.textContent = initials || '?';
                ph.classList.remove('d-none');
            }
            if (rmBtn) rmBtn.classList.add('d-none');  // nothing to remove
        }
    }

    // ── Profile picture upload ──────────────────────────────────────────────
    function initAvatarUpload() {
        const wrap       = $('avatar-wrap');
        const actionBar  = $('avatar-action-bar');
        const uploadBtn  = $('avatar-upload-btn');
        const cameraBtn  = $('avatar-camera-btn');
        const removeBtn  = $('avatar-remove-btn');
        const cancelBtn  = $('avatar-cancel-btn');
        const fileInput  = $('avatar-file-input');
        const uploading  = $('avatar-uploading');

        if (!wrap) return;

        function showActionBar()  { show('avatar-action-bar'); }
        function hideActionBar()  { hide('avatar-action-bar'); }

        // Tap avatar to reveal action bar
        wrap.addEventListener('click', showActionBar);
        wrap.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') showActionBar(); });
        cancelBtn?.addEventListener('click', hideActionBar);

        // ── Upload from file ────────────────────────────────────────────
        uploadBtn?.addEventListener('click', () => {
            fileInput.removeAttribute('capture');
            fileInput.click();
        });

        // ── Take photo with camera ──────────────────────────────────────
        cameraBtn?.addEventListener('click', () => {
            fileInput.setAttribute('capture', 'user');   // prefer front camera
            fileInput.click();
        });

        // ── Compress image using Canvas before upload ──────────────────
        // Resizes to a max dimension of 1200px and encodes as JPEG at
        // decreasing quality until the file fits within the server limit.
        async function compressImage(file) {
            const MAX_DIMENSION = 1200;
            const MAX_BYTES     = {{ (int) config('pwa.picture_upload.max_kb', 2048) * 1024 }};

            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);

                img.onload = () => {
                    URL.revokeObjectURL(url);

                    const srcW = img.naturalWidth;
                    const srcH = img.naturalHeight;

                    // ── Crop to centre square in source coordinates ───────
                    const srcSize = Math.min(srcW, srcH);
                    const srcX    = Math.floor((srcW - srcSize) / 2);
                    const srcY    = Math.floor((srcH - srcSize) / 2);

                    // ── Scale the square down if it exceeds MAX_DIMENSION ─
                    const destSize = Math.min(srcSize, MAX_DIMENSION);

                    const canvas  = document.createElement('canvas');
                    canvas.width  = destSize;
                    canvas.height = destSize;

                    // drawImage(src, sx, sy, sWidth, sHeight, dx, dy, dWidth, dHeight)
                    // Crops the centre square from the source and draws it into
                    // the full (square) canvas in one step.
                    canvas.getContext('2d').drawImage(
                        img,
                        srcX, srcY, srcSize, srcSize,  // source crop
                        0,    0,    destSize, destSize  // destination
                    );

                    // Try JPEG at decreasing quality until it fits
                    const qualities = [0.85, 0.75, 0.65, 0.55, 0.45];
                    for (const q of qualities) {
                        const dataUrl  = canvas.toDataURL('image/jpeg', q);
                        const bytes    = Math.round((dataUrl.length - 'data:image/jpeg;base64,'.length) * 0.75);
                        if (bytes <= MAX_BYTES) {
                            resolve(dataUrl);
                            return;
                        }
                    }
                    // Last resort — lowest quality
                    resolve(canvas.toDataURL('image/jpeg', 0.35));
                };

                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Could not read image')); };
                img.src = url;
            });
        }

        // ── Handle selected file ────────────────────────────────────────
        fileInput?.addEventListener('change', async function () {
            const file = this.files?.[0];
            if (!file) return;

            hideActionBar();
            show('avatar-uploading');

            try {
                const id      = await resolveDeviceId();
                const dataUrl = await compressImage(file);

                const res = await fetch(PWA_BASE + '/profile/picture', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({ device_id: id, picture_data: dataUrl }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Upload failed');

                state.pictureUrl = data.picture_url;
                renderAvatar(state.pictureUrl, $('identity-name')?.textContent ?? '');
                window.showToast?.('Profile picture updated');
            } catch (e) {
                window.showToast?.(e.message || 'Upload failed', 'error');
            } finally {
                hide('avatar-uploading');
                this.value = '';   // allow re-selection of same file
            }
        });

        // ── Remove uploaded picture ─────────────────────────────────────
        removeBtn?.addEventListener('click', async () => {
            if (!confirm('Remove your uploaded picture?')) return;
            hideActionBar();
            show('avatar-uploading');

            try {
                const id  = await resolveDeviceId();
                const res = await fetch(PWA_BASE + '/profile/picture', {
                    method:  'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ device_id: id }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Remove failed');

                state.pictureUrl = data.picture_url || null;
                renderAvatar(state.pictureUrl, $('identity-name')?.textContent ?? '');
                window.showToast?.('Picture removed');
            } catch (e) {
                window.showToast?.(e.message || 'Could not remove picture', 'error');
            } finally {
                hide('avatar-uploading');
            }
        });
    }

    // ── Inbox badge ────────────────────────────────────────────────────────
    async function loadInboxBadge() {
        // Only fetch when the phone is verified — avoids pointless requests
        // (and 500s if the push_messages table doesn't exist yet) for
        // unverified devices.
        if (!state.phoneVerified) return;

        const badge   = $('um-unread-badge');
        const summary = $('um-msg-summary');
        if (!badge || !summary) return;
        try {
            const id  = await resolveDeviceId();
            if (!id) return;
            const res = await fetch(PWA_BASE + '/messages/unread?device_id=' + encodeURIComponent(id), {
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
        } catch { /* non-fatal — badge stays hidden */ }
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initSearchableSelects();
        initAvatarUpload();
        loadPreferences();
        // loadInboxBadge is called from applyState() once phone_verified is known

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

        // ── Push enable button ─────────────────────────────────────────────
        $('push-enable-btn')?.addEventListener('click', async function () {
            const btn    = this;
            const errEl  = $('push-enable-error');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Enabling…';
            hide('push-enable-error');
            try {
                await window.pushNotifications.subscribe();
                hide('push-enable-row');
                show('push-enabled-row');
                window.showToast?.('Push notifications enabled');
            } catch (e) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-bell me-2"></i>Enable push notifications';
                if (errEl) {
                    errEl.textContent = Notification.permission === 'denied'
                        ? 'Notifications are blocked. Reset them in your browser settings.'
                        : (e.message || 'Could not enable — please try again.');
                    show('push-enable-error');
                }
                if (Notification.permission === 'denied') {
                    hide('push-enable-row');
                    show('push-blocked-row');
                }
            }
        });

        // ── "Turn off" — revealed only after deliberate interaction ────────
        // The link is hidden; the user must hold the enabled row for 2 seconds
        // (or long-press on mobile) to reveal it, preventing accidental taps.
        $('push-enabled-row')?.addEventListener('pointerdown', function () {
            const wrap = $('push-disable-wrap');
            if (!wrap) return;
            this._holdTimer = setTimeout(() => {
                wrap.classList.remove('d-none');
            }, 1500);
        });
        $('push-enabled-row')?.addEventListener('pointerup',    () => clearTimeout($('push-enabled-row')?._holdTimer));
        $('push-enabled-row')?.addEventListener('pointerleave', () => clearTimeout($('push-enabled-row')?._holdTimer));

        $('push-disable-link')?.addEventListener('click', async function (e) {
            e.preventDefault();
            if (!confirm('Turn off push notifications? You can re-enable them here at any time.')) return;
            try {
                await window.pushNotifications.unsubscribe();
                $('push-disable-wrap')?.classList.add('d-none');
                hide('push-enabled-row');
                show('push-enable-row');
                window.showToast?.('Push notifications turned off');
            } catch {
                window.showToast?.('Could not turn off — try again', 'error');
            }
        });

        // ── Sign out ───────────────────────────────────────────────────────
        $('sign-out-link')?.addEventListener('click', function (e) {
            e.preventDefault();
            if (!confirm('Sign out? You will need to re-verify your number to use personalised features.')) return;

            // Clear device identity from localStorage and cookie
            try {
                localStorage.removeItem('pwa_device_id');
                document.cookie = 'pwa_device_id=; max-age=0; path=/; SameSite=Lax';
            } catch {}

            // Reset in-page state
            state.phoneVerified    = false;
            state.identityResolved = false;
            _resolvedDeviceId      = null;

            // Clear displayed values
            ['identity-name','identity-phone'].forEach(id => { const el = $(id); if (el) el.textContent = ''; });
            hide('push-enable-error');
            hide('push-disable-wrap');

            // Reset verification form
            const phoneEl = $('pref-phone');
            if (phoneEl) phoneEl.value = '';
            hide('pin-entry-section');
            show('phone-entry-section');

            applyState();
            document.getElementById('menuOverlay')?.click();  // close panel
            window.showToast?.('Signed out');
        });

        // Remove the old change-number-btn handler if it still exists
        // (now handled by sign-out)
        $('change-number-btn')?.addEventListener('click', () => {
            $('sign-out-link')?.click();
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