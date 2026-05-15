<x-layouts::app :title="__($case->reference)">
    <div class="max-w-4xl mx-auto py-8 px-4">

        <a href="{{ route('cases.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400
                  hover:text-zinc-900 dark:hover:text-white mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to cases
        </a>

        {{-- Case header --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-6">
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

        {{-- Livewire Timeline (alert + stepper + uploaded docs) --}}
        <livewire:case-timeline :case="$case" :key="'case-'.$case->id" />

        {{-- Upload document to this case --}}
        <div class="mt-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <flux:heading size="lg" class="mb-4">Upload document</flux:heading>
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="case_id" value="{{ $case->id }}">

                <div class="mb-4">
                    <flux:label for="document">PDF File</flux:label>
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
                    <flux:label for="criteria">Summary criteria
                        <span class="text-zinc-400 font-normal">(optional)</span>
                    </flux:label>
                    <flux:textarea id="criteria" name="criteria" rows="2"
                        placeholder="e.g. Extract key dates and parties only."
                        class="mt-1">{{ old('criteria') }}</flux:textarea>
                </div>

                <flux:button type="submit" variant="primary">Upload &amp; Analyse</flux:button>
            </form>
        </div>


        {{-- Time tracker --}}
        <livewire:time-tracker :caseId="$case->id" :key="'timer-'.$case->id" />

        @php
        $totalHours = $case->timeEntries()->whereNotNull('ended_at')->sum('hours');
        $hourlyRate = 5000; // this can later be pulled from user settings
        $totalTimeValue = $totalHours * $hourlyRate;
        @endphp

        @php $timeEntries = $case->timeEntries()->latest()->take(10)->get(); @endphp
        @if($timeEntries->isNotEmpty())
        <div class="mt-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Time Tracking') }}</flux:heading>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600 dark:text-zinc-400">{{ __('Total hours:') }} {{ number_format($totalHours, 2) }}</span>
                <span class="text-gray-600 dark:text-zinc-400">{{ __('Estimated value:') }} KSh {{ number_format($totalTimeValue, 2) }}</span>
            </div>
            {{-- Existing time entries list --}}
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

        <livewire:add-expense :caseId="$case->id" :key="'expense-'.$case->id" />
        {{-- Recent Expenses --}}
        @php
        $expenses = $case->expenses()->latest()->take(5)->get();
        $totalExpenses = $case->expenses()->sum('amount');
        @endphp
        @if($expenses->isNotEmpty())
        <div class="mt-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Expenses') }}</flux:heading>
            <div class="space-y-2">
                @foreach($expenses as $expense)
                <div class="flex justify-between text-sm">
                    <span>{{ $expense->description }}</span>
                    <span class="text-gray-500">KSh {{ number_format($expense->amount, 2) }}</span>
                </div>
                @endforeach
                
            </div>
            {{-- Total line --}}
            <div class="mt-3 pt-2 border-t border-gray-100 dark:border-zinc-800 text-sm text-right text-gray-700 dark:text-zinc-300">
                {{ __('Total:') }} <strong>KSh {{ number_format($totalExpenses, 2) }}</strong>
            </div>
        </div>
        @endif

    </div>
</x-layouts::app>