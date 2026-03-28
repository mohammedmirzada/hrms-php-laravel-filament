<x-filament-panels::page>

    @php $shortcuts = $this->getShortcuts(); @endphp

    @if(!empty($shortcuts))
        <div
            x-data="{ open: true }"
            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800"
        >
            {{-- Header --}}
            <button
                type="button"
                @click="open = !open"
                class="flex w-full items-center justify-between px-5 py-3 text-left"
            >
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-s-squares-2x2"
                        class="h-4 w-4 text-primary-500"
                    />
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-300">
                        Quick Access
                    </span>
                </div>
                <x-filament::icon
                    icon="heroicon-s-chevron-up"
                    x-bind:class="open ? 'rotate-0' : 'rotate-180'"
                    class="h-4 w-4 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                />
            </button>

            {{-- Shortcuts grid --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="grid grid-cols-3 divide-x divide-y divide-gray-100 border-t border-gray-100 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 dark:divide-white/10 dark:border-white/10"
            >
                @foreach($shortcuts as $shortcut)
                    <a
                        href="{{ route($shortcut['route']) }}"
                        wire:navigate
                        class="group flex flex-col items-center justify-center gap-2 px-3 py-4 transition-colors hover:bg-primary-50 dark:hover:bg-primary-950/30"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 transition-colors group-hover:bg-primary-100 dark:bg-white/10 dark:group-hover:bg-primary-900/50">
                            <x-filament::icon
                                :icon="$shortcut['icon']"
                                class="h-5 w-5 text-gray-500 transition-colors group-hover:text-primary-600 dark:text-gray-300 dark:group-hover:text-primary-400"
                            />
                        </div>
                        <span class="text-center text-xs font-medium leading-tight text-gray-500 group-hover:text-primary-600 dark:text-gray-300 dark:group-hover:text-primary-400">
                            {{ $shortcut['label'] }}
                        </span>
                    </a>
                @endforeach
            </div>

        </div>
    @endif

</x-filament-panels::page>
