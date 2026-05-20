<x-layouts::app :title="__($case->reference)">
    {{-- Full-width Progress Stepper (scrolled independently) --}}
    <div class="w-full max-w-full overflow-x-hidden">
        <livewire:case-timeline :case="$case" :key="'case-'.$case->id" />
    </div>

    {{-- The rest of the page in two columns --}}
    <div class="flex flex-col lg:flex-row gap-6 max-w-7xl mx-auto py-8 px-4">

        {{-- Main content (left) --}}
        <div class="flex-1 min-w-0 space-y-6">
            {{-- Back link --}}
            <a href="{{ route('cases.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400
                      hover:text-zinc-900 dark:hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('Back to cases') }}
            </a>

            {{-- Case header --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $case->reference }} — {{ $case->debtor_name }}
                        </h1>
                        @if($case->debtor_contact)
                            <p class="text-sm text-gray-400 dark:text-zinc-500">{{ $case->debtor_contact }}</p>
                        @endif
                        @if($case->notes)
                            <p class="text-sm text-gray-600 dark:text-zinc-400 mt-2">{{ $case->notes }}</p>
                        @endif
                    </div>
                    <livewire:case-status :case="$case" :key="'status-'.$case->id" />
                </div>
            </div>

            {{-- Upload Document --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
                <flux:heading size="lg" class="mb-4">{{ __('Upload document') }}</flux:heading>
                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="case_id" value="{{ $case->id }}">
                    <div class="mb-4">
                        <flux:label for="document">{{ __('PDF File') }}</flux:label>
                        <input type="file" id="document" name="document" accept=".pdf"
                               class="mt-1 block w-full text-sm text-zinc-600 dark:text-zinc-300
                                      file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-zinc-100 dark:file:bg-zinc-700
                                      file:text-zinc-700 dark:file:text-zinc-200
                                      border border-zinc-300 dark:border-zinc-600 rounded-lg p-2
                                      bg-white dark:bg-zinc-800">
                        @error('document')<flux:error>{{ $message }}</flux:error>@enderror
                    </div>
                    <div class="mb-4">
                        <flux:label for="criteria">{{ __('Summary criteria') }}
                            <span class="text-zinc-400 font-normal">({{ __('optional') }})</span>
                        </flux:label>
                        <flux:textarea id="criteria" name="criteria" rows="2"
                            placeholder="{{ __('e.g. Extract key dates and parties only.') }}"
                            class="mt-1">{{ old('criteria') }}</flux:textarea>
                    </div>
                    <flux:button type="submit" variant="primary">{{ __('Upload & Analyse') }}</flux:button>
                </form>
            </div>

            {{-- Time & Expenses (combined) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <livewire:time-tracker :caseId="$case->id" :key="'timer-'.$case->id" />
                    @php
                        $timeEntries = $case->timeEntries()->latest()->take(5)->get();
                        $totalHours = $case->timeEntries()->whereNotNull('ended_at')->sum('hours');
                        $hourlyRate = 5000;
                        $totalTimeValue = $totalHours * $hourlyRate;
                    @endphp
                    @if($timeEntries->isNotEmpty())
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
                            <flux:heading size="lg" class="mb-4">{{ __('Recent Time') }}</flux:heading>
                            <div class="flex justify-between text-sm mb-2">
                                <span>{{ __('Total:') }} {{ number_format($totalHours, 2) }} hrs</span>
                                <span>KSh {{ number_format($totalTimeValue, 2) }}</span>
                            </div>
                            <div class="space-y-2">
                                @foreach($timeEntries as $entry)
                                    <div class="flex justify-between text-sm">
                                        <div>
                                            <span class="text-gray-700 dark:text-zinc-300">{{ $entry->description ?: __('No description') }}</span>
                                            <span class="text-xs text-gray-400 ml-2">{{ $entry->started_at->format('d M H:i') }} - {{ $entry->ended_at ? $entry->ended_at->format('H:i') : __('ongoing') }}</span>
                                        </div>
                                        <span class="text-gray-500">{{ $entry->hours ? number_format($entry->hours, 2).' hrs' : '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <livewire:add-expense :caseId="$case->id" :key="'expense-'.$case->id" />
                    @php
                        $expenses = $case->expenses()->latest()->take(5)->get();
                        $totalExpenses = $case->expenses()->sum('amount');
                    @endphp
                    @if($expenses->isNotEmpty())
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
                            <flux:heading size="lg" class="mb-4">{{ __('Recent Expenses') }}</flux:heading>
                            <div class="space-y-2">
                                @foreach($expenses as $expense)
                                    <div class="flex justify-between text-sm">
                                        <span>{{ $expense->description }}</span>
                                        <span class="text-gray-500">KSh {{ number_format($expense->amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 pt-2 border-t border-gray-100 dark:border-zinc-800 text-right text-sm font-medium">
                                {{ __('Total:') }} KSh {{ number_format($totalExpenses, 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- AI Sidebar (right) --}}
        <div class="w-full lg:w-80 shrink-0 min-w-0 space-y-6">
            @php
                $latestDocument = $case->documents()->latest()->first();
            @endphp

            @if($latestDocument)
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
                    <livewire:extract-key-facts :document="$latestDocument" :key="'facts-'.$latestDocument->id" />
                </div>
            @endif

            <livewire:ask-ai :case="$case" :key="'ask-'.$case->id" />

            <livewire:case-timeline-ai :case="$case" :key="'ai-timeline-'.$case->id" />
        </div>
    </div>
</x-layouts::app>