<x-layouts::app :title="__($case->reference)">
    <div class="max-w-4xl mx-auto py-8 px-4">

        <a href="{{ route('cases.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400
                  hover:text-zinc-900 dark:hover:text-white mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
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
                <span class="text-xs px-2.5 py-1 rounded-full border
                    {{ $case->status === 'active'
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                        : 'bg-gray-50 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 border-gray-200 dark:border-zinc-700' }}">
                    {{ ucfirst($case->status) }}
                </span>
            </div>

            @if($next)
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-800 dark:text-amber-300">
                    ⚠ Next required document:
                    <strong>{{ \App\Models\LegalCase::stageLabel($next) }}</strong>
                </div>
            @else
                <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-800 dark:text-green-300">
                    ✓ All stages complete
                </div>
            @endif
        </div>

        {{-- Upload document to this case --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-6">
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

        {{-- Stage timeline --}}
        <flux:heading size="lg" class="mb-4">Case timeline</flux:heading>

        @foreach(\App\Models\LegalCase::STAGES as $key => $label)
            @php
                $doc = $case->documents->firstWhere('document_type', $key);
                $isDone = in_array($key, $completed);
                $isNext = $key === $next;
            @endphp
            <div class="flex items-start gap-4 mb-3">

                {{-- Stage indicator --}}
                <div class="shrink-0 flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium
                        {{ $isDone
                            ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400'
                            : ($isNext
                                ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400'
                                : 'bg-gray-100 dark:bg-zinc-800 text-gray-400 dark:text-zinc-600') }}">
                        @if($isDone) ✓ @elseif($isNext) ! @else ○ @endif
                    </div>
                </div>

                {{-- Stage content --}}
                <div class="flex-1 pb-3 border-b border-gray-100 dark:border-zinc-800">
                    <p class="text-sm font-medium
                        {{ $isDone
                            ? 'text-gray-900 dark:text-white'
                            : ($isNext ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 dark:text-zinc-600') }}">
                        {{ $label }}
                    </p>

                    @if($doc)
                        <a href="{{ route('documents.show', $doc) }}"
                           class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                            {{ $doc->original_filename }}
                            · {{ $doc->created_at->format('d M Y') }}
                        </a>
                    @elseif($isNext)
                        <p class="text-xs text-amber-600 dark:text-amber-500">Upload required</p>
                    @endif
                </div>
            </div>
        @endforeach

    </div>
</x-layouts::app>