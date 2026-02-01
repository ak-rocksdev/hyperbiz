@extends('errors::minimal')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('icon', 'ki-time')
@section('color', 'warning')
@section('message', 'You have sent too many requests in a short period. Our servers need a moment to catch up.')

@section('additional_content')
<div class="bg-warning-light dark:bg-warning/10 rounded-lg p-4 mb-8">
    <div class="flex items-start gap-3">
        <div class="shrink-0">
            <i class="ki-filled ki-information-2 text-warning text-lg"></i>
        </div>
        <div class="text-sm text-gray-700 dark:text-gray-300">
            <p class="font-medium text-warning-active dark:text-warning mb-1">What can you do?</p>
            <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                <li>Wait a few moments before trying again</li>
                <li>Refresh the page after 30-60 seconds</li>
                <li>Avoid rapid repeated actions</li>
            </ul>
        </div>
    </div>
</div>

<!-- Countdown timer -->
<div class="text-center mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Auto-refresh in:</p>
    <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-coal-400 rounded-full px-4 py-2">
        <i class="ki-filled ki-timer text-warning"></i>
        <span id="countdown" class="font-mono font-semibold text-gray-700 dark:text-gray-200">60</span>
        <span class="text-gray-500 dark:text-gray-400 text-sm">seconds</span>
    </div>
</div>

<script>
    (function() {
        let seconds = 60;
        const countdownEl = document.getElementById('countdown');

        const timer = setInterval(function() {
            seconds--;
            if (countdownEl) {
                countdownEl.textContent = seconds;
            }

            if (seconds <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);
    })();
</script>
@endsection
