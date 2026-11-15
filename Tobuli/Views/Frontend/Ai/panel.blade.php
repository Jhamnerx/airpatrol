{{-- AirBot: asistente IA. Solo se incluye con el permiso ai_assistant (view). --}}
<link rel="stylesheet" href="{{ asset_resource('assets/airbot/airbot.css') }}">

<button type="button" id="airbot-launcher" class="airbot-launcher" title="{{ trans('front.ai_assistant_title') }} (Ctrl+B)"
    aria-controls="airbot" aria-expanded="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="4" y="7" width="16" height="12" rx="4"/><path d="M12 7V4"/><circle cx="12" cy="3" r="1"/>
        <circle cx="9" cy="13" r="1.2" fill="currentColor"/><circle cx="15" cy="13" r="1.2" fill="currentColor"/><path d="M2 12v2M22 12v2"/>
    </svg>
    <span>{{ trans('front.ai_assistant_title') }}</span>
</button>

<section id="airbot" class="airbot" role="dialog" aria-label="{{ trans('front.ai_assistant_title') }}" hidden
    data-url-chat="{{ route('ai.chat') }}"
    data-url-suggestions="{{ route('ai.suggestions') }}"
    data-url-conversations="{{ route('ai.conversations') }}"
    data-url-confirm="{{ route('ai.actions.confirm', '__TOKEN__') }}"
    data-url-cancel="{{ route('ai.actions.cancel', '__TOKEN__') }}"
    data-csrf="{{ csrf_token() }}"
    data-i18n="{{ json_encode([
        'thinking' => trans('front.ai_thinking'),
        'error' => trans('front.ai_error'),
        'quota_left' => trans('front.ai_quota_left'),
        'no_history' => trans('front.ai_no_history'),
        'consulted' => trans('front.ai_consulted'),
        'periods' => [
            trans('front.ai_period_today'),
            trans('front.ai_period_yesterday'),
            trans('front.ai_period_this_week'),
            trans('front.ai_period_last_week'),
            trans('front.ai_period_this_month'),
            trans('front.ai_period_last_month'),
        ],
    ]) }}">

    <header class="airbot-head">
        <div class="airbot-title">
            <span class="airbot-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="7" width="16" height="12" rx="4"/><path d="M12 7V4"/><circle cx="9" cy="13" r="1.2" fill="currentColor"/><circle cx="15" cy="13" r="1.2" fill="currentColor"/></svg>
            </span>
            <strong>{{ trans('front.ai_assistant_title') }}</strong>
        </div>
        <div class="airbot-head-actions">
            <button type="button" class="airbot-icon-btn" data-airbot="history" title="{{ trans('front.ai_history') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
            </button>
            <button type="button" class="airbot-icon-btn" data-airbot="new" title="{{ trans('front.ai_new_chat') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </button>
            <button type="button" class="airbot-icon-btn" data-airbot="close" title="{{ trans('global.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </header>

    <div class="airbot-body" id="airbot-body">
        <div class="airbot-welcome" id="airbot-welcome">
            <div class="airbot-welcome-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="7" width="16" height="12" rx="4"/><path d="M12 7V4"/><circle cx="12" cy="3" r="1"/><circle cx="9" cy="13" r="1.2" fill="currentColor"/><circle cx="15" cy="13" r="1.2" fill="currentColor"/><path d="M2 12v2M22 12v2"/></svg>
            </div>
            <h3>{{ trans('front.ai_welcome') }}</h3>
            <p>{{ trans('front.ai_welcome_sub') }}</p>
            <div class="airbot-suggestions" id="airbot-suggestions"></div>
        </div>
        <div class="airbot-messages" id="airbot-messages" aria-live="polite"></div>
        <div class="airbot-history" id="airbot-history" hidden></div>
    </div>

    <footer class="airbot-foot">
        <form id="airbot-form" class="airbot-form" autocomplete="off">
            <textarea id="airbot-input" rows="1" maxlength="2000" placeholder="{{ trans('front.ai_placeholder') }}"
                aria-label="{{ trans('front.ai_placeholder') }}"></textarea>
            <button type="submit" class="airbot-send" title="{{ trans('front.ai_send') }}" aria-label="{{ trans('front.ai_send') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            </button>
        </form>
        <div class="airbot-quota" id="airbot-quota"></div>
    </footer>
</section>

<script src="{{ asset_resource('assets/airbot/airbot.js') }}"></script>
