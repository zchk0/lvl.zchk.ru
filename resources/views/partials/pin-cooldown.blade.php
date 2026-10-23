<div
    data-pin-cooldown
    data-retry-after="{{ $retryAfter }}"
    role="status"
    class="rounded-2xl bg-amber-500/10 px-3 py-3 text-center text-xs leading-5 text-amber-200">
    <p>Слишком много неверных попыток.</p>
    <p>
        Повторите через
        <span data-pin-cooldown-time role="timer" aria-live="off" class="font-semibold tabular-nums">{{ sprintf('%02d:%02d', intdiv($retryAfter, 60), $retryAfter % 60) }}</span>.
    </p>
</div>
