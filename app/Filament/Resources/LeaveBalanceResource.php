<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceResource\Pages;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\LeaveBalances;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalances::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Scale;

    protected static string|UnitEnum|null $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Leave Balances';

    protected static ?string $modelLabel = 'Leave Balance';

    protected static ?string $pluralModelLabel = 'Leave Balances';

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
                    TextInput::make('balance_minutes')
                        ->numeric()
                        ->default(0)
                        ->suffix('min'),
                    TextInput::make('balance_days')
                        ->numeric()
                        ->default(0)
                        ->step(0.01)
                        ->suffix('days'),
                    DateTimePicker::make('as_of')
                        ->native(false)
                        ->label('As Of')
                        ->required(),
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
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable(),
                TextColumn::make('balance_minutes')
                    ->label('Balance (min)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('balance_days')
                    ->label('Balance (days)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('as_of')
                    ->label('As Of')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name'),
                SelectFilter::make('employer_id')
                    ->label('Employee')
                    ->relationship('employer', 'full_name')
                    ->searchable()
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
            ->defaultSort('as_of', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveBalances::route('/'),
            'create' => Pages\CreateLeaveBalance::route('/create'),
            'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }
}
