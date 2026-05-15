<?php

use App\Models\TimeEntry;
use Livewire\Component;

new class extends Component {

    public $caseId;
    public $tracking = false;
    public $entryId = null;
    public $description = '';
    public $elapsed = '00:00:00';

    public function mount($caseId): void
    {
        $this->caseId = $caseId;
        $this->checkRunning();
    }

    public function checkRunning(): void
    {
        $running = TimeEntry::where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->first();

        if ($running) {
            $this->tracking = true;
            $this->entryId = $running->id;
            $this->description = $running->description;
        }
    }

    public function start(): void
    {
        $entry = TimeEntry::create([
            'user_id'    => auth()->id(),
            'case_id'    => $this->caseId,
            'description' => $this->description,
            'started_at' => now(),
        ]);

        $this->tracking = true;
        $this->entryId = $entry->id;
    }

    public function stop(): void
    {
        $entry = TimeEntry::find($this->entryId);
        if ($entry && $entry->user_id === auth()->id()) {
            $entry->update(['ended_at' => now()]);
        }

        $this->tracking = false;
        $this->entryId = null;
        $this->description = '';
        $this->dispatch('timeStopped');
    }

    public function render()
    {
        return view('components.⚡time-tracker');
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
    <flux:heading size="lg" class="mb-4">{{ __('Time Tracker') }}</flux:heading>

    @if($tracking)
        <div class="flex items-center gap-4">
            <div class="text-2xl font-mono text-green-600 dark:text-green-400" wire:poll.1s="checkRunning">
                {{ $elapsed }}
            </div>
            <flux:button wire:click="stop" variant="primary">
                {{ __('Stop Timer') }}
            </flux:button>
        </div>
    @else
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <flux:label for="description">{{ __('What are you working on?') }}</flux:label>
                <flux:input wire:model="description" id="description" placeholder="e.g. Drafting defence..." class="mt-1" />
            </div>
            <flux:button wire:click="start" variant="primary">
                {{ __('Start Timer') }}
            </flux:button>
        </div>
    @endif
</div>