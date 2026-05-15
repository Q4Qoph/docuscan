<?php

use App\Models\Contact;
use Livewire\Component;

new class extends Component {

    public $showModal = false;
    public $name = '';
    public $type = 'debtor';
    public $phone = '';
    public $email = '';

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:debtor,creditor,advocate,court,judge,other',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        Contact::create([
            'user_id' => auth()->id(),
            'name'    => $this->name,
            'type'    => $this->type,
            'phone'   => $this->phone,
            'email'   => $this->email,
        ]);

        $this->reset(['showModal', 'name', 'phone', 'email']);
        $this->type = 'debtor';
        session()->flash('success', __('Contact added.'));
    }

    public function render()
    {
        return view('components.⚡add-contact');
    }
}; ?>

<div>
    <button wire:click="$set('showModal', true)"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300
                   bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg
                   hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('New Contact') }}
    </button>

    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" wire:click.self="$set('showModal', false)">
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Add New Contact') }}</h3>
                <div class="space-y-3">
                    <div>
                        <label for="ac-name" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Name') }}</label>
                        <input wire:model="name" id="ac-name" class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
                        @error('name') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ac-type" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Type') }}</label>
                        <select wire:model="type" id="ac-type" class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
                            <option value="debtor">{{ __('Debtor') }}</option>
                            <option value="creditor">{{ __('Creditor') }}</option>
                            <option value="advocate">{{ __('Advocate') }}</option>
                            <option value="court">{{ __('Court') }}</option>
                            <option value="judge">{{ __('Judge') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ac-phone" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Phone') }}</label>
                        <input wire:model="phone" id="ac-phone" class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
                    </div>
                    <div>
                        <label for="ac-email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">{{ __('Email') }}</label>
                        <input wire:model="email" id="ac-email" class="w-full text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button wire:click="$set('showModal', false)"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="save"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>