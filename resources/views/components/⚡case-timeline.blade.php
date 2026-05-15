<?php

use App\Models\LegalCase;
use Livewire\Component;

new class extends Component {

    public LegalCase $case;
    public array $completed = [];
    public ?string $next = null;
    public array $overdue = [];
    public bool $hasPendingJobs = false;

    public function mount(LegalCase $case): void
    {
        $this->case = $case;
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->case->load('documents.summaries');

        $this->completed = $this->case->completedStages();
        $this->next      = $this->case->nextMissingStage();
        $this->overdue   = $this->case->overdueStages();

        $this->hasPendingJobs = $this->case->documents->contains(function ($doc) {
            return $doc->summaries->contains(fn($s) => in_array($s->status, ['pending', 'processing']));
        });
    }

}; ?>

<div @if($hasPendingJobs) wire:poll.5s="refreshData" @endif>
    {{-- Overdue / next stage alert --}}
    @if(!empty($overdue))
        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-300">
            ⚠ Overdue documents:
            @foreach($overdue as $stage)
                <strong>{{ \App\Models\LegalCase::stageLabel($stage) }}</strong>
                @if(!$loop->last), @endif
            @endforeach
        </div>
    @elseif($next)
        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-800 dark:text-amber-300">
            ⚠ Next required document:
            <strong>{{ \App\Models\LegalCase::stageLabel($next) }}</strong>
        </div>
    @else
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-800 dark:text-green-300">
            ✓ All stages complete
        </div>
    @endif

    {{-- Horizontal Progress Stepper --}}
    <div class="mt-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
        <flux:heading size="lg" class="mb-4">Case timeline</flux:heading>
        <x-case-progress-stepper
            :stages="\App\Models\LegalCase::STAGES"
            :completed="$completed"
            :next="$next"
            :overdue="$overdue"
        />
    </div>

    {{-- Uploaded Documents per Stage --}}
    @php
        $groupedDocs = $case->documents->groupBy('document_type');
    @endphp
    @if($groupedDocs->isNotEmpty())
        <div class="mt-6 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-6">
            <flux:heading size="lg" class="mb-4">Uploaded documents</flux:heading>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($groupedDocs as $type => $docs)
                    @php $stageLabel = \App\Models\LegalCase::stageLabel($type); @endphp
                    <div class="p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                        <p class="text-sm font-medium text-gray-700 dark:text-zinc-300">{{ $stageLabel }}</p>
                        @foreach($docs as $doc)
                            <a href="{{ route('documents.show', $doc) }}"
                               class="text-xs text-blue-600 dark:text-blue-400 hover:underline block mt-1">
                                {{ $doc->original_filename }}
                                <span class="text-gray-400">· {{ $doc->created_at->format('d M Y') }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>