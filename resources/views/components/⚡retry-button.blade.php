<?php

use App\Jobs\SummarizeDocument;
use App\Models\Summary;
use Livewire\Component;

new class extends Component {

    public Summary $summary;
    public bool $dispatched = false;

    public function retry(): void
    {
        abort_if($this->summary->document->user_id !== auth()->id(), 403);

        if (! $this->summary->isFailed()) {
            return;
        }

        $this->summary->update([
            'status'        => 'pending',
            'error_message' => null,
        ]);

        SummarizeDocument::dispatch($this->summary);

        $this->dispatched = true;
    }

}; ?>

<div>
    @if ($dispatched)
        <span class="text-sm text-blue-600">Queued — refreshing shortly...</span>
    @else
        <button
            wire:click="retry"
            wire:loading.attr="disabled"
            class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors
                   disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span wire:loading.remove wire:target="retry">↺ Retry summarization</span>
            <span wire:loading wire:target="retry">Dispatching...</span>
        </button>
    @endif
</div>