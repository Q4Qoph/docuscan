<?php

use App\Models\LegalCase;
use App\Services\AiService;
use Livewire\Component;

new class extends Component {

    public LegalCase $case;
    public string $question = '';
    public string $answer = '';
    public bool $loading = false;
    public array $history = [];

    public function mount(LegalCase $case): void
    {
        $this->case = $case;
    }

    public function ask(): void
    {
        $this->validate(['question' => 'required|string|min:3']);
        abort_if($this->case->user_id !== auth()->id(), 403);

        $question = $this->question;
        $this->question = '';
        $this->answer = '';
        $this->loading = true;

        // Get all document texts for the case
        $allTexts = $this->case->getAllDocumentTexts();
        $context = implode("\n\n", $allTexts);

        if (empty($context)) {
            $this->answer = 'No document text available for this case.';
            $this->loading = false;
            return;
        }

        try {
            $aiService = app(AiService::class);
            $fullAnswer = $aiService->askAboutText($question, $context, function ($chunk) {
                $this->answer .= $chunk;
                $this->stream(to: 'answer', content: $chunk, replace: false);
            });

            $this->history[] = ['question' => $question, 'answer' => $this->answer];
        } catch (\Exception $e) {
            $this->answer = 'Error: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('components.⚡ask-ai');
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">{{ __('Ask AI') }}</h3>

    {{-- Chat history --}}
    @foreach($history as $item)
        <div class="mb-2">
            <p class="text-xs font-medium text-gray-500">{{ __('You:') }} {{ $item['question'] }}</p>
            <p class="text-xs text-gray-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $item['answer'] }}</p>
        </div>
    @endforeach

    {{-- Current answer (streaming) --}}
    @if($loading)
        <div wire:stream="answer" class="text-xs text-gray-700 dark:text-zinc-300 whitespace-pre-wrap mb-2"></div>
    @elseif($answer && !$history)
        <p class="text-xs text-gray-700 dark:text-zinc-300 whitespace-pre-wrap mb-2">{{ $answer }}</p>
    @endif

    {{-- Input --}}
    <div class="flex gap-2 mt-2">
        <input wire:model="question" type="text" placeholder="{{ __('Ask anything about these documents...') }}"
               wire:keydown.enter="ask"
               class="flex-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
        <button wire:click="ask" wire:loading.attr="disabled"
                class="px-3 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50">
            {{ __('Ask') }}
        </button>
    </div>
</div>