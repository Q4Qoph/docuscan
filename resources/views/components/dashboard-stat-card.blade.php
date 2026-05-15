@props(['label', 'value', 'icon', 'color' => 'blue'])

@php
    $colorClasses = match ($color) {
        'green' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'amber' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
        'red'   => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
    };
@endphp

<div class="rounded-xl border p-4 {{ $colorClasses }}">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">{{ $label }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $value }}</p>
        </div>
        <div class="shrink-0 w-10 h-10 rounded-full bg-white dark:bg-zinc-800 flex items-center justify-center">
            <flux:icon :name="$icon" class="w-5 h-5 text-gray-500 dark:text-zinc-400" />
        </div>
    </div>
</div>