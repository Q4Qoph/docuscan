<x-layouts::app :title="__('Cases')">
    <div class="max-w-4xl mx-auto py-8 px-4">

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- New case form --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-8">
            <flux:heading size="lg" class="mb-4">Open a new case</flux:heading>
            <form action="{{ route('cases.store') }}" method="POST">
                @csrf

                {{-- Contact dropdown --}}
                @if($contacts->isNotEmpty())
                    <div class="mb-4">
                        <flux:label for="contact_id">Existing debtor</flux:label>
                        <select id="contact_id" name="contact_id"
                                class="mt-1 block w-full text-sm text-zinc-600 dark:text-zinc-300
                                       border border-zinc-300 dark:border-zinc-600 rounded-lg p-2
                                       bg-white dark:bg-zinc-800">
                            <option value="">{{ __('— Choose existing contact —') }}</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                    {{ $contact->name }}
                                    @if($contact->phone)
                                        ({{ $contact->phone }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-zinc-400 mt-1">
                            {{ __('Or fill in the name manually below.') }}
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <flux:label for="debtor_name">Debtor name</flux:label>
                        <flux:input id="debtor_name" name="debtor_name"
                            placeholder="John Doe / Acme Ltd"
                            value="{{ old('debtor_name') }}" class="mt-1"/>
                        @error('debtor_name')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </div>
                    <div>
                        <flux:label for="debtor_contact">Contact / ID</flux:label>
                        <flux:input id="debtor_contact" name="debtor_contact"
                            placeholder="Phone, email or ID number"
                            value="{{ old('debtor_contact') }}" class="mt-1"/>
                    </div>
                </div>
                <div class="mb-4">
                    <flux:label for="notes">Notes</flux:label>
                    <flux:textarea id="notes" name="notes" rows="2"
                        placeholder="Brief description of the matter"
                        class="mt-1">{{ old('notes') }}</flux:textarea>
                </div>
                <flux:button type="submit" variant="primary">Open case</flux:button>
            </form>
        </div>

        {{-- Cases list --}}
        <flux:heading size="lg" class="mb-4">Active cases</flux:heading>

        @forelse($cases as $case)
            @php
                $completed = $case->completedStages();
                $total     = count(\App\Models\LegalCase::STAGES);
                $pct       = $total > 0 ? round(count($completed) / $total * 100) : 0;
                $next      = $case->nextMissingStage();
            @endphp
            <a href="{{ route('cases.show', $case) }}"
               class="block p-5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700
                      rounded-xl mb-3 hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $case->reference }} — {{ $case->debtor_name }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-zinc-500 mt-0.5">
                            {{ $case->created_at->diffForHumans() }}
                            @if($next)
                                · <span class="text-amber-600 dark:text-amber-400">
                                    Missing: {{ \App\Models\LegalCase::stageLabel($next) }}
                                </span>
                            @else
                                · <span class="text-green-600 dark:text-green-400">All stages complete</span>
                            @endif
                        </p>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-zinc-400 shrink-0">
                        {{ count($completed) }}/{{ $total }} docs
                    </span>
                </div>

                {{-- Progress bar --}}
                <div class="w-full bg-gray-100 dark:bg-zinc-800 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full transition-all"
                         style="width: {{ $pct }}%"></div>
                </div>
            </a>
        @empty
            <div class="text-center py-16 text-gray-400 dark:text-zinc-600 text-sm">
                No cases yet. Open your first case above.
            </div>
        @endforelse
    </div>
</x-layouts::app>