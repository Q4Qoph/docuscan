<?php

use App\Models\Document;
use App\Services\AiService;
use Livewire\Component;

new class extends Component {

    public Document $document;
    public ?array $facts = null;
    public bool $loading = false;
    public string $error = '';

    public function mount(Document $document): void
    {
        $this->document = $document;
        // Check if facts already cached in metadata
        if (isset($this->document->metadata['key_facts'])) {
            $this->facts = $this->document->metadata['key_facts'];
        }
    }

    public function extract(): void
    {
        abort_if($this->document->user_id !== auth()->id(), 403);

        if (!$this->document->extracted_text) {
            $this->error = 'No extracted text available.';
            return;
        }

        $this->loading = true;
        $this->error = '';

        try {
            $aiService = app(AiService::class);
            $this->facts = $aiService->extractKeyFacts($this->document->extracted_text);

            // Cache in document metadata
            $this->document->update([
                'metadata' => array_merge($this->document->metadata ?? [], ['key_facts' => $this->facts]),
            ]);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('components.⚡extract-key-facts');
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">{{ __('Key Facts') }}</h3>

    @if($facts)
        <div class="space-y-2 text-sm">
            @if(!empty($facts['parties']['plaintiff']))
                <div><span class="text-gray-500">{{ __('Plaintiff:') }}</span> {{ $facts['parties']['plaintiff'] }}</div>
            @endif
            @if(!empty($facts['parties']['defendant']))
                <div><span class="text-gray-500">{{ __('Defendant:') }}</span> {{ $facts['parties']['defendant'] }}</div>
            @endif
            @if(!empty($facts['dates']['document_date']))
                <div><span class="text-gray-500">{{ __('Document Date:') }}</span> {{ $facts['dates']['document_date'] }}</div>
            @endif
            @if(!empty($facts['claim_amount']))
                <div><span class="text-gray-500">{{ __('Claim Amount:') }}</span> KSh {{ number_format($facts['claim_amount'], 2) }}</div>
            @endif
            @if(!empty($facts['court']))
                <div><span class="text-gray-500">{{ __('Court:') }}</span> {{ $facts['court'] }}</div>
            @endif
            @if(!empty($facts['case_number']))
                <div><span class="text-gray-500">{{ __('Case Number:') }}</span> {{ $facts['case_number'] }}</div>
            @endif
            @if(!empty($facts['relief_sought']))
                <div><span class="text-gray-500">{{ __('Relief Sought:') }}</span></div>
                <ul class="list-disc list-inside text-xs ml-2">
                    @foreach($facts['relief_sought'] as $relief)
                        <li>{{ $relief }}</li>
                    @endforeach
                </ul>
            @endif
            @if(!empty($facts['key_events']))
                <div class="mt-2"><span class="text-gray-500">{{ __('Key Events:') }}</span></div>
                <ul class="list-disc list-inside text-xs ml-2">
                    @foreach($facts['key_events'] as $event)
                        <li>{{ $event }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @else
        <button wire:click="extract"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded-lg
                       hover:bg-blue-600 transition-colors disabled:opacity-50">
            <span wire:loading.remove wire:target="extract">{{ __('Extract Key Facts') }}</span>
            <span wire:loading wire:target="extract">{{ __('Extracting...') }}</span>
        </button>

        @if($error)
            <p class="text-sm text-red-500 mt-2">{{ $error }}</p>
        @endif
    @endif
</div>