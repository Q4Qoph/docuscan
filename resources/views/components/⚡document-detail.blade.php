<?php

use Livewire\Component;
use App\Models\Document;

new class extends Component {

    public Document $document;
    public $hasPendingJobs = false;
    public $showRawText = false;

    public function mount(Document $document): void
    {
        $this->document = $document;
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->document->load('summaries');
        $this->hasPendingJobs = $this->document->summaries
            ->contains(fn($s) => in_array($s->status, ['pending', 'processing']));
    }

    public function toggleRawText(): void
    {
        $this->showRawText = !$this->showRawText;
    }

    public function render()
    {
        return view('components.⚡document-detail');
    }
}; ?>

<div>
    <a href="{{ url()->previous() }}"
        class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400
              hover:text-zinc-900 dark:hover:text-white mb-6 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('Back') }}
    </a>

    {{-- Document header --}}
    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ $document->original_filename }}
                </h1>
                <p class="text-sm text-gray-400 dark:text-zinc-500 mt-0.5">
                    {{ number_format($document->file_size_bytes / 1024, 0) }} KB
                    · {{ __('Uploaded') }} {{ $document->created_at->diffForHumans() }}
                </p>
            </div>
            <form action="{{ route('documents.destroy', $document) }}" method="POST"
                onsubmit="return confirm('{{ __('Delete this document and its summaries?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="text-sm text-red-500 hover:text-red-700 transition-colors">
                    {{ __('Delete') }}
                </button>
            </form>
        </div>
    </div>
    {{-- Key Facts --}}
    <livewire:extract-key-facts :document="$document" :key="'facts-'.$document->id" />

    {{-- Extracted text toggle (if available) --}}
    @if($document->extracted_text)
    <div class="mb-4">
        <button wire:click="toggleRawText"
            class="text-sm text-blue-600 hover:underline">
            {{ $showRawText ? __('Hide raw text') : __('Show raw text') }}
        </button>
    </div>
    @if($showRawText)
    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4 mb-6 max-h-64 overflow-y-auto">
        <pre class="text-xs whitespace-pre-wrap">{{ $document->extracted_text }}</pre>
    </div>
    @endif
    @endif

    {{-- Summaries with polling --}}
    <div @if($hasPendingJobs) wire:poll.5s="refreshData" @endif>
        @foreach ($document->summaries()->latest()->get() as $summary)
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-4">
            @if ($summary->criteria)
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3">
                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ __('Criteria:') }}</span>
                {{ $summary->criteria }}
            </p>
            @endif

            @if ($summary->isPending())
            <div class="flex items-center gap-2 text-sm text-yellow-700 dark:text-yellow-400">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ __('Waiting in queue...') }}
            </div>

            @elseif ($summary->isProcessing())
            <div class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-400">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ __('AI is summarizing your document...') }}
            </div>

            @elseif ($summary->isCompleted())
            <div class="prose prose-sm dark:prose-invert max-w-none">
                {!! $summary->summaryAsHtml() !!}
            </div>
            <p class="mt-4 text-xs text-zinc-400 dark:text-zinc-600">
                {{ $summary->ai_model_used }}
                · {{ number_format($summary->tokens_used) }} {{ __('tokens') }}
                · {{ $summary->updated_at->diffForHumans() }}
            </p>

            @elseif ($summary->isFailed())
            <div class="text-sm text-red-500 dark:text-red-400">
                <p class="font-medium mb-1">{{ __('Summarization failed.') }}</p>
                @if ($summary->error_message)
                <p class="text-red-400 dark:text-red-500 mb-3">{{ $summary->error_message }}</p>
                @endif
                <livewire:retry-button :summary="$summary" :key="$summary->id" />
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>