<?php

if (!function_exists('getFilamentResourcesAndPages')) {
    /** @return array<string, array{label: string, icon: string, group: string|null, route: string}> */
    function getFilamentResourcesAndPages(): array {
        $panel  = \Filament\Facades\Filament::getDefaultPanel();
        $result = [];

        // Heroicon enum cases need getIconForSize() — non-outlined cases like
        $resolveIcon = function (mixed $icon): string {
            if ($icon instanceof \Filament\Support\Icons\Heroicon) {
                return $icon->getIconForSize(\Filament\Support\Enums\IconSize::Large);
            }

            $value = $icon instanceof \BackedEnum ? $icon->value : (string) $icon;

            if (str_starts_with($value, 'heroicon-')) {
                return $value;
            }

            return 'heroicon-o-' . $value;
        };

        // Resources
        foreach ($panel->getResources() as $resource) {
            $route = $resource::getRouteBaseName() . '.index';
            $result[$route] = [
                'label' => $resource::getNavigationLabel(),
                'icon'  => $resolveIcon($resource::getNavigationIcon()),
                'group' => $resource::getNavigationGroup(),
                'route' => $route,
            ];
        }

        // Pages
        foreach ($panel->getPages() as $page) {
            $route = $page::getRouteName();

            $result[$route] = [
                'label' => $page::getNavigationLabel(),
                'icon'  => $resolveIcon($page::getNavigationIcon()),
                'group' => $page::getNavigationGroup(),
                'route' => $route,
            ];
        }

        return $result;
    }
}