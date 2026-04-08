<?php

namespace App\Filament\Pages\Reports;

use App\Enums\DocumentType;
use App\Models\Document;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentExpiryReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ExclamationTriangle;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Document Expiry';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Document Expiry Report';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->whereNotNull('expiry_date')
                    ->with(['employer', 'employer.branch', 'employer.department'])
            )
            ->columns([
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employer.branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->employer?->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('employer.department.name')
                    ->label('Department')
                    ->formatStateUsing(fn ($record) => $record->employer?->department?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('document_type')
                    ->label('Document Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn ($state) => $state ? basename($state) : '—')
                    ->url(fn ($record) => $record->file_path ? asset('storage/' . $record->file_path) : null)
                    ->openUrlInNewTab()
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->color('primary'),
                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('days_remaining')
                    ->label('Days Remaining')
                    ->getStateUsing(function ($record) {
                        if (! $record->expiry_date) return '—';
                        $days = now()->startOfDay()->diffInDays($record->expiry_date, false);
                        return $days;
                    })
                    ->color(fn ($state) => match (true) {
                        $state === '—' => null,
                        $state < 0 => 'danger',
                        $state <= 30 => 'warning',
                        default => 'success',
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('expiry_date', $direction)),
                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => match (true) {
                        $record->isExpired() => 'Expired',
                        $record->isExpiringSoon() => 'Expiring Soon',
                        default => 'Valid',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Expired' => 'danger',
                        'Expiring Soon' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options(DocumentType::labels())
                    ->native(false)
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'expired' => 'Expired',
                        'expiring_soon' => 'Expiring Soon',
                        'valid' => 'Valid',
                    ])
                    ->native(false)
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'expired' => $query->where('expiry_date', '<', now()),
                        'expiring_soon' => $query->where('expiry_date', '>=', now())->where('expiry_date', '<=', now()->addDays(30)),
                        'valid' => $query->where('expiry_date', '>', now()->addDays(30)),
                        default => $query,
                    }),
                SelectFilter::make('branch')
                    ->label('Branch')
                    ->relationship('employer.branch', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('expiry_date', 'asc');
    }
}
