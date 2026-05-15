<x-layouts::app :title="__('Contacts')">
    <div class="max-w-4xl mx-auto py-8 px-4">

        <flux:heading size="lg" class="mb-4">{{ __('Contacts') }}</flux:heading>

        <div class="mb-4 text-right">
            <livewire:add-contact />
        </div>

        @forelse($contacts as $contact)
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4 mb-3">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $contact->name }}</p>
                        <p class="text-xs text-gray-400">{{ $contact->type }} {{ $contact->phone ? '· '.$contact->phone : '' }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ $contact->type }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-400 dark:text-zinc-600 text-sm">
                {{ __('No contacts yet. Add one using the button above.') }}
            </div>
        @endforelse
    </div>
</x-layouts::app>