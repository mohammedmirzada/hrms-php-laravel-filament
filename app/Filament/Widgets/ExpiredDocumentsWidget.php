<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiredDocumentsWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Document::query()
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addMonths(6))
                    ->with('employer')
                    ->orderBy('expiry_date', 'asc')
            )
            ->columns([
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->sortable(),
                TextColumn::make('document_type')
                    ->label('Document Type')
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->sortable()
                    ->color(fn (Document $record): string => $record->isExpired() ? 'danger' : 'warning'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Document $record): string => $record->isExpired() ? 'Expired' : 'Expiring Soon')
                    ->color(fn (Document $record): string => $record->isExpired() ? 'danger' : 'warning'),
            ])
            ->paginated(false);
    }

    protected function getHeading(): ?string
    {
        return 'Expired & Expiring Documents (within 6 months)';
    }
}
