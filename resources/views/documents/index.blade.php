<x-layouts::app :title="__('My Documents')">
    <div class="max-w-3xl mx-auto py-8 px-4">

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Upload form --}}
        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6 mb-8">
            <flux:heading size="lg" class="mb-1">Upload a document</flux:heading>
            <flux:text class="mb-5">PDF files only · Max 20MB</flux:text>

            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- File input --}}
                <div class="mb-4">
                    <flux:label for="document">PDF File</flux:label>
                    <input
                        type="file"
                        id="document"
                        name="document"
                        accept=".pdf"
                        class="mt-1 block w-full text-sm text-zinc-600 dark:text-zinc-300
                               file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                               file:text-sm file:font-medium
                               file:bg-zinc-100 dark:file:bg-zinc-700
                               file:text-zinc-700 dark:file:text-zinc-200
                               hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                               border border-zinc-300 dark:border-zinc-600 rounded-lg p-2
                               bg-white dark:bg-zinc-800
                               @error('document') border-red-400 @enderror"
                    >
                    @error('document')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>

                {{-- Criteria input --}}
                <div class="mb-5">
                    <flux:label for="criteria">
                        Summary criteria
                        <span class="text-zinc-400 font-normal">(optional)</span>
                    </flux:label>
                    <flux:textarea
                        id="criteria"
                        name="criteria"
                        rows="3"
                        placeholder="e.g. Focus on financial risks and key dates only. Bullet points. Max 5 bullets."
                        class="mt-1 @error('criteria') border-red-400 @enderror"
                    >{{ old('criteria') }}</flux:textarea>
                    @error('criteria')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <flux:text size="sm" class="mt-1">
                        Tell the AI what to focus on. Leave blank for a general summary.
                    </flux:text>
                </div>

                <flux:button type="submit" variant="primary">
                    Upload &amp; Summarize
                </flux:button>
            </form>
        </div>

        {{-- Documents list --}}
        <flux:heading size="lg" class="mb-4">Your documents</flux:heading>

        @forelse ($documents as $document)
            @php $summary = $document->summaries->first(); @endphp
            <a href="{{ route('documents.show', $document) }}"
               class="flex items-center justify-between p-4
                      bg-white dark:bg-zinc-900
                      border border-gray-200 dark:border-zinc-700
                      rounded-xl mb-3 hover:border-blue-300 dark:hover:border-blue-600
                      transition-colors group">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="shrink-0 w-9 h-9 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $document->original_filename }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-zinc-500">
                            {{ number_format($document->file_size_bytes / 1024, 0) }} KB
                            · {{ $document->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                @if ($summary)
                    @php
                        $badges = [
                            'pending'    => 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800',
                            'processing' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                            'completed'  => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
                            'failed'     => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
                        ];
                        $badgeClass = $badges[$summary->status] ?? $badges['pending'];
                    @endphp
                    <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full border {{ $badgeClass }}">
                        {{ ucfirst($summary->status) }}
                    </span>
                @endif
            </a>
        @empty
            <div class="text-center py-16 text-gray-400 dark:text-zinc-600 text-sm">
                No documents yet. Upload your first PDF above.
            </div>
        @endforelse

    </div>
</x-layouts::app>