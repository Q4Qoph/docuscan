<?php

use App\Models\LegalCase;
use App\Services\AiService;
use Livewire\Component;

new class extends Component {

    public LegalCase $case;
    public array $events = [];
    public bool $loading = false;

    public function mount(LegalCase $case): void
    {
        $this->case = $case;
        if (isset($this->case->metadata['ai_timeline'])) {
            $this->events = $this->case->metadata['ai_timeline'];
        }
    }

    public function generate(): void
    {
        abort_if($this->case->user_id !== auth()->id(), 403);

        $allTexts = $this->case->getAllDocumentTexts();
        if (empty($allTexts)) {
            return;
        }

        $this->loading = true;
        try {
            $aiService = app(AiService::class);
            $this->events = $aiService->generateTimeline($allTexts);

            // Cache in case metadata (add a 'metadata' JSON column to cases table)
            $this->case->update([
                'metadata' => array_merge($this->case->metadata ?? [], ['ai_timeline' => $this->events]),
            ]);
        } catch (\Exception $e) {
            // ignore
        }
        $this->loading = false;
    }

    public function render()
    {
        return view('components.⚡case-timeline-ai');
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">{{ __('AI Timeline') }}</h3>

    @if(empty($events))
        <button wire:click="generate" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded-lg
                       hover:bg-blue-600 transition-colors disabled:opacity-50">
            <span wire:loading.remove wire:target="generate">{{ __('Generate Timeline') }}</span>
            <span wire:loading wire:target="generate">{{ __('Generating...') }}</span>
        </button>
    @else
        <div class="space-y-3">
            @foreach($events as $event)
                <div class="flex gap-3 text-sm">
                    <span class="text-xs font-medium text-blue-600 w-20 shrink-0">{{ $event['date'] }}</span>
                    <div>
                        <p class="font-medium">{{ $event['title'] }}</p>
                        <p class="text-xs text-gray-500">{{ $event['description'] }}</p>
                        @if(!empty($event['source_document_id']))
                            <a href="{{ route('documents.show', $event['source_document_id']) }}"
                               class="text-xs text-blue-500 hover:underline">View document</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>