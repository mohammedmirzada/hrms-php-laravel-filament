<?php

namespace App\Filament\Resources\SocialSecurityRuleResource\Pages;

use App\Filament\Resources\SocialSecurityRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialSecurityRules extends ListRecords
{
    protected static string $resource = SocialSecurityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
