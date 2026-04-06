<x-filament-panels::page>
    {{-- Today's Status --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="text-center space-y-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ now()->format('l, M j, Y') }}
            </p>
            <p class="text-4xl font-bold text-gray-950 dark:text-white">
                {{ now()->format('h:i A') }}
            </p>

            @if($this->isClockedIn())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-sm font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">
                    <span class="h-2 w-2 rounded-full bg-success-500 animate-pulse"></span>
                    Clocked In
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                    Not Clocked In
                </span>
            @endif
        </div>
    </div>

    {{-- Selfie form (if required) --}}
    <form wire:submit="clock" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-center">
            @if($this->isClockedIn())
                <x-filament::button type="submit" size="xl" color="danger" icon="heroicon-o-arrow-right-start-on-rectangle">
                    Clock Out
                </x-filament::button>
            @else
                <x-filament::button type="submit" size="xl" color="success" icon="heroicon-o-arrow-left-end-on-rectangle">
                    Clock In
                </x-filament::button>
            @endif
        </div>
    </form>

    {{-- Today's Timeline --}}
    @php $events = $this->getTodayEvents(); @endphp
    @if($events->isNotEmpty())
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Today's Activity</h3>
            <div class="space-y-3">
                @foreach($events as $event)
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $event->event_type === 'IN' ? 'bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400' : 'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400' }}">
                            @if($event->event_type === 'IN')
                                <x-heroicon-s-arrow-left-end-on-rectangle class="h-4 w-4" />
                            @else
                                <x-heroicon-s-arrow-right-start-on-rectangle class="h-4 w-4" />
                            @endif
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $event->event_type === 'IN' ? 'Clock In' : 'Clock Out' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $event->event_at->format('h:i A') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
