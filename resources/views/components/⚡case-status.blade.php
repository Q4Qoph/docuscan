<?php

use App\Models\LegalCase;
use Livewire\Component;

new class extends Component {

    public LegalCase $case;

    public function mount(LegalCase $case): void
    {
        $this->case = $case;
    }

    public function setStatus($status): void
    {
        abort_if($this->case->user_id !== auth()->id(), 403);
        $this->case->update(['status' => $status]);
        $this->dispatch('caseStatusUpdated');
    }

    public function render()
    {
        return view('components.⚡case-status');
    }
}; ?>

<div>
    <select
        wire:change="setStatus($event.target.value)"
        class="text-xs px-2 py-1 rounded-full border
            @if($case->status === 'active')
                bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800
            @elseif($case->status === 'closed')
                bg-gray-50 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 border-gray-200 dark:border-zinc-700
            @elseif($case->status === 'appealing')
                bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800
            @endif
        ">
        <option value="active" @if($case->status === 'active') selected @endif>{{ __('Active') }}</option>
        <option value="closed" @if($case->status === 'closed') selected @endif>{{ __('Closed') }}</option>
        <option value="appealing" @if($case->status === 'appealing') selected @endif>{{ __('Appealing') }}</option>
    </select>
</div>