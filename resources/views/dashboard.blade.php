<x-layouts::app :title="__('Dashboard')">
    <div class="max-w-7xl mx-auto py-8 px-4">

        {{-- Welcome / date --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ __('Good ') . (now()->hour < 12 ? __('morning') : (now()->hour < 18 ? __('afternoon') : __('evening'))) }}, {{ auth()->user()->name }} 👋
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ now()->isoFormat('dddd, Do MMMM YYYY') }}
            </p>
        </div>

        {{-- Financial summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Overdue Cases Card --}}
            <div class="rounded-xl border p-4 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Overdue Documents') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $overdueCasesCount }}</p>
                    </div>
                    <div class="shrink-0 w-10 h-10 rounded-full bg-white dark:bg-zinc-800 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Cases Missing Stages Card --}}
            <div class="rounded-xl border p-4 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Incomplete Cases') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $missingCasesCount }}</p>
                    </div>
                    <div class="shrink-0 w-10 h-10 rounded-full bg-white dark:bg-zinc-800 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border p-4 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Outstanding') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">KSh 0.00</p>
                    </div>
                    <div class="shrink-0 w-10 h-10 rounded-full bg-white dark:bg-zinc-800 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick-create buttons --}}
        <div class="mb-6">
            <flux:heading size="lg" class="mb-3">{{ __('Quick Create') }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                {{-- New Case --}}
                <a href="{{ route('cases.index') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300
                          bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg
                          hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('New Case') }}
                </a>

                {{-- New Contact --}}
                <livewire:add-contact />

                {{-- Upload Document --}}
                <a href="{{ route('documents.index') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300
                          bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg
                          hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ __('Upload Document') }}
                </a>

                {{-- Log Time --}}
                <a href="{{ route('cases.index') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300
          bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg
          hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Log Time') }}
                </a>

                {{-- Add Expense (disabled) --}}
                <a href="{{ route('cases.index') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300
          bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg
          hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Add Expense') }}
                </a>
            </div>
        </div>

        {{-- Active cases & upcoming deadlines --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cases list --}}
            <div class="lg:col-span-2">
                <flux:heading size="lg" class="mb-3">{{ __('Active Cases') }}</flux:heading>
                @forelse($activeCases ?? [] as $case)
                @php
                $completed = $case->completedStages();
                $total = count(\App\Models\LegalCase::STAGES);
                $pct = $total > 0 ? round(count($completed) / $total * 100) : 0;
                $next = $case->nextMissingStage();
                $overdue = $case->overdueStages();
                @endphp
                <a href="{{ route('cases.show', $case) }}"
                    class="block p-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700
                              rounded-xl mb-3 hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $case->reference }} — {{ $case->debtor_name }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-zinc-500 mt-0.5">
                                {{ $case->created_at->diffForHumans() }}
                                @if(!empty($overdue))
                                · <span class="text-red-600 dark:text-red-400 font-medium">
                                    Overdue: {{ \App\Models\LegalCase::stageLabel($overdue[0]) }}
                                </span>
                                @elseif($next)
                                · <span class="text-amber-600 dark:text-amber-400">
                                    Next: {{ \App\Models\LegalCase::stageLabel($next) }}
                                </span>
                                @else
                                · <span class="text-green-600 dark:text-green-400">{{ __('All complete') }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="text-xs font-medium text-gray-500 dark:text-zinc-400 shrink-0">
                            {{ count($completed) }}/{{ $total }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-zinc-800 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full transition-all"
                            style="width: {{ $pct }}%"></div>
                    </div>
                </a>
                @empty
                <div class="text-center py-12 text-gray-400 dark:text-zinc-600 text-sm">
                    {{ __('No active cases yet. Create your first case to see it here.') }}
                </div>
                @endforelse
            </div>

            {{-- Upcoming deadlines sidebar --}}
            <div>
                <flux:heading size="lg" class="mb-3">{{ __('Upcoming Deadlines') }}</flux:heading>
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
                    @forelse($upcomingDeadlines ?? [] as $deadline)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-zinc-800 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $deadline['title'] }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-zinc-500">
                                {{ $deadline['case_ref'] ?? '' }}
                            </p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $deadline['is_overdue'] ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                            {{ $deadline['date']->isoFormat('D MMM') }}
                        </span>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 dark:text-zinc-600 py-4 text-center">
                        {{ __('No upcoming deadlines.') }}
                    </p>
                    @endforelse
                </div>

                <livewire:mini-calendar :key="'calendar-'.now()->format('Ym')" />
            </div>
        </div>

    </div>
</x-layouts::app>