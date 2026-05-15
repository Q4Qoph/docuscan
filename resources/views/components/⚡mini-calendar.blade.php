<?php

use App\Models\Event;
use Livewire\Component;

new class extends Component {

    public $year;
    public $month;
    public $events = []; // plain array, not Eloquent collection

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        $startOfMonth = now()->setYear($this->year)->setMonth($this->month)->startOfMonth();
        $endOfMonth = now()->setYear($this->year)->setMonth($this->month)->endOfMonth();

        $eventModels = Event::where('user_id', auth()->id())
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // Convert to plain array keyed by date string
        $this->events = [];
        foreach ($eventModels as $event) {
            $dateKey = $event->date->format('Y-m-d');
            if (!isset($this->events[$dateKey])) {
                $this->events[$dateKey] = [];
            }
            $this->events[$dateKey][] = [
                'title' => $event->title,
                'case_ref' => $event->legalCase?->reference,
            ];
        }
    }

    public function prevMonth(): void
    {
        $this->month--;
        if ($this->month < 1) {
            $this->month = 12;
            $this->year--;
        }
        $this->loadEvents();
    }

    public function nextMonth(): void
    {
        $this->month++;
        if ($this->month > 12) {
            $this->month = 1;
            $this->year++;
        }
        $this->loadEvents();
    }

    public function render()
    {
        $startOfMonth = now()->setYear($this->year)->setMonth($this->month)->startOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;
        $startDayOfWeek = $startOfMonth->dayOfWeek; // Sunday=0, Monday=1...

        return view('components.⚡mini-calendar', [
            'daysInMonth'     => $daysInMonth,
            'startDayOfWeek'  => $startDayOfWeek,
            'monthName'       => $startOfMonth->format('F Y'),
        ]);
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
    <div class="flex items-center justify-between mb-2">
        <button wire:click="prevMonth" class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-white">&larr;</button>
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $monthName }}</h3>
        <button wire:click="nextMonth" class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-white">&rarr;</button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
    </div>
    <div class="grid grid-cols-7 gap-1 mt-1">
        @for($i = 0; $i < $startDayOfWeek; $i++)
            <div></div>
        @endfor
        @for($day = 1; $day <= $daysInMonth; $day++)
            @php $dateKey = now()->setYear($this->year)->setMonth($this->month)->setDay($day)->format('Y-m-d'); @endphp
            <div class="text-xs py-1 rounded-full relative
                @if(isset($events[$dateKey]))
                    bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-medium
                @else
                    text-gray-700 dark:text-zinc-300
                @endif
            ">
                {{ $day }}
                @if(isset($events[$dateKey]))
                    <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-500 rounded-full"></span>
                @endif
            </div>
        @endfor
    </div>
    {{-- Event list --}}
    <div class="mt-3 text-xs space-y-1 max-h-24 overflow-y-auto">
        @foreach($events as $date => $dayEvents)
            @foreach($dayEvents as $event)
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-zinc-400">{{ \Carbon\Carbon::parse($date)->format('M d') }}</span>
                    <span class="text-gray-800 dark:text-zinc-200">{{ $event['title'] }}</span>
                </div>
            @endforeach
        @endforeach
        @if(empty($events))
            <p class="text-gray-400 dark:text-zinc-600 text-center py-2">{{ __('No events this month') }}</p>
        @endif
    </div>
</div>