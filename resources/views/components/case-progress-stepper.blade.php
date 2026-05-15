@props(['stages', 'completed', 'next', 'overdue'])

<div class="overflow-x-auto pb-2">
    <nav aria-label="Case document progress" class="flex items-center min-w-max">
        @foreach($stages as $key => $label)
            @php
                $isCompleted = in_array($key, $completed);
                $isNext      = ($key === $next);
                $isOverdue   = in_array($key, $overdue);
                $isLast      = $loop->last;
            @endphp

            <div class="flex items-center {{ !$isLast ? 'flex-1' : '' }}">
                {{-- Step indicator --}}
                <div class="relative flex flex-col items-center group">
                    <button
                        @if($isCompleted)
                            class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500 text-white shadow transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-green-300"
                        @elseif($isOverdue)
                            class="w-10 h-10 rounded-full flex items-center justify-center bg-red-500 text-white shadow transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-red-300"
                        @elseif($isNext)
                            class="w-10 h-10 rounded-full flex items-center justify-center bg-amber-500 text-white shadow transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-300 animate-pulse"
                        @else
                            class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-200 dark:bg-zinc-700 text-gray-500 dark:text-zinc-400"
                        @endif
                        aria-label="{{ $label }} - {{ $isCompleted ? 'Completed' : ($isOverdue ? 'Overdue' : ($isNext ? 'Next required' : 'Pending')) }}"
                    >
                        @if($isCompleted)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @elseif($isOverdue)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/>
                            </svg>
                        @else
                            <span class="text-sm font-medium">{{ $loop->index + 1 }}</span>
                        @endif
                    </button>

                    {{-- Stage label --}}
                    <span class="mt-2 text-xs font-medium text-center w-20 leading-tight
                        {{ $isCompleted ? 'text-green-700 dark:text-green-400' : '' }}
                        {{ $isOverdue ? 'text-red-700 dark:text-red-400' : '' }}
                        {{ $isNext ? 'text-amber-700 dark:text-amber-400' : '' }}
                        {{ !$isCompleted && !$isNext && !$isOverdue ? 'text-gray-500 dark:text-zinc-400' : '' }}">
                        {{ $label }}
                    </span>

                    {{-- Tooltip on hover --}}
                    <div class="absolute -bottom-12 left-1/2 transform -translate-x-1/2 z-10 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 dark:bg-gray-700 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                        @if($isOverdue)
                            Overdue — upload required
                        @elseif($isNext)
                            Next required document
                        @elseif($isCompleted)
                            Uploaded
                        @endif
                    </div>
                </div>

                {{-- Connector line --}}
                @unless($isLast)
                    <div class="flex-1 h-0.5 mx-2 {{ $isCompleted ? 'bg-green-500' : 'bg-gray-200 dark:bg-zinc-700' }}"></div>
                @endunless
            </div>
        @endforeach
    </nav>
</div>