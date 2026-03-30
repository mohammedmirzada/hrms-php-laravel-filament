<?php

namespace App\Filament\Widgets;

use App\Models\Holiday;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HolidaysWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Holiday::query())
            ->columns([
                TextColumn::make('branch.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_working_day_override')
                    ->boolean()
            ])
            ->paginated(false);
    }

    protected function getHeading(): ?string
    {
        return 'Overview';
    }
}
