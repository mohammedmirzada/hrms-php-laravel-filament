<?php

namespace App\Filament\Resources;

use App\Enums\LeaveLedgerEntryType;
use App\Filament\Resources\LeaveLedgerEntryResource\Pages;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\LeaveLedgerEntry;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeaveLedgerEntryResource extends Resource
{
    protected static ?string $model = LeaveLedgerEntry::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::InformationCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(3)->schema([
                    Select::make('employer_id')
                        ->native(false)
                        ->label('Employee')
                        ->relationship('employer', 'full_name')
                        ->getOptionLabelFromRecordUsing(fn (Employer $record) => $record->getTranslation('full_name', 'en'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('branch_id')
                        ->native(false)
                        ->label('Branch')
                        ->relationship('branch', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('leave_type_id')
                        ->native(false)
                        ->label('Leave Type')
                        ->relationship('leaveType', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                ]),
                Grid::make(3)->schema([
                    Select::make('leave_request_id')
                        ->native(false)
                        ->label('Leave Request')
                        ->relationship('leaveRequest', 'id')
                        ->searchable()
                        ->preload()
                        ->helperText('Optional. Link this entry to the leave request that caused it. Leave empty for manual adjustments not tied to a specific request.'),
                    Select::make('entry_type')
                        ->native(false)
                        ->options(LeaveLedgerEntryType::labels())
                        ->required()
                        ->helperText('Accrual = leave added automatically. Deduction = leave used by a request. Adjustment = manual correction by HR. Reversal = undoing a previous entry. Expiry = leave removed because it expired.'),
                    TextInput::make('amount_minutes')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->suffix('min')
                        ->helperText('In minutes. 480 = 1 full day (8 hrs). Use positive numbers — the entry type above determines whether this adds or removes leave.'),
                ]),
                Grid::make(2)->schema([
                    DatePicker::make('occurred_on')
                        ->native(false)
                        ->label('Occurred On')
                        ->required()
                        ->helperText('The date this transaction actually happened (not necessarily today). Used for the leave balance timeline.'),
                    Textarea::make('note')
                        ->maxLength(65535)
                        ->helperText('Optional. Briefly explain why this entry exists, especially for manual adjustments (e.g. "Correction for miscalculated accrual in Jan 2026").'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->badge()
                    ->color(fn ($state): string => ($state instanceof LeaveLedgerEntryType ? $state : LeaveLedgerEntryType::tryFrom($state))?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state): string => ($state instanceof LeaveLedgerEntryType ? $state : LeaveLedgerEntryType::tryFrom($state))?->label() ?? $state)
                    ->sortable(),
                TextColumn::make('amount_minutes')
                    ->label('Amount (min)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('occurred_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('leaveRequest.id')
                    ->label('Request #')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('entry_type')
                    ->options(LeaveLedgerEntryType::labels())
                    ->searchable()
                    ->native(false),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload(),
                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload(),
                SelectFilter::make('employer_id')
                    ->label('Employee')
                    ->relationship('employer', 'full_name')
                    ->searchable()
                    ->native(false)
                    ->preload(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('occurred_on', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveLedgerEntries::route('/'),
            'create' => Pages\CreateLeaveLedgerEntry::route('/create'),
            'edit' => Pages\EditLeaveLedgerEntry::route('/{record}/edit'),
        ];
    }
}
