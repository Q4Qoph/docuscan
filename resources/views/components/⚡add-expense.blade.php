<?php

use App\Models\Expense;
use Livewire\Component;

new class extends Component {

    public $caseId;
    public $description = '';
    public $amount = '';
    public $date = '';
    public $showForm = false;

    public function mount($caseId): void
    {
        $this->caseId = $caseId;
        $this->date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
            'date'        => 'required|date',
        ]);

        Expense::create([
            'user_id'     => auth()->id(),
            'case_id'     => $this->caseId,
            'description' => $this->description,
            'amount'      => $this->amount,
            'date'        => $this->date,
        ]);

        $this->reset(['description', 'amount', 'showForm']);
        $this->date = now()->format('Y-m-d');
        $this->dispatch('expenseAdded');
    }

    public function render()
    {
        return view('components.⚡add-expense');
    }
}; ?>

<div>
    @if(!$showForm)
        <button wire:click="$set('showForm', true)"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300
                       bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg
                       hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Log Expense') }}
        </button>
    @else
        <div class="mt-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">{{ __('New Expense') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="ae-description" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Description') }}</label>
                    <input wire:model="description" id="ae-description" placeholder="Filing fee, transport..."
                           class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
                    @error('description') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ae-amount" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Amount (KSh)') }}</label>
                    <input wire:model="amount" type="number" step="0.01" id="ae-amount"
                           class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
                    @error('amount') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ae-date" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Date') }}</label>
                    <input wire:model="date" type="date" id="ae-date"
                           class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                <button wire:click="save"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors">
                    {{ __('Save') }}
                </button>
                <button wire:click="$set('showForm', false)"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    @endif
</div>