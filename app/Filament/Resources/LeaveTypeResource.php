<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslatableFields;
use App\Filament\Resources\LeaveTypeResource\Pages;
use App\Models\LeaveType;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeaveTypeResource extends Resource
{
    use HasTranslatableFields;

    protected static ?string $model = LeaveType::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                static::translatableTabs('name', 'Leave Type Name', required: true),
                static::translatableTabs('description', 'Description'),
                Select::make('default_unit')
                    ->options([
                        'HOUR' => 'Hour',
                        'DAY' => 'Day',
                    ])
                    ->required(),
                Toggle::make('is_paid')
                    ->label('Paid Leave')
                    ->default(true),
                Toggle::make('is_system')
                    ->label('System Type')
                    ->helperText('System types cannot be deleted by users'),
                Select::make('document_id')
                    ->label('Required Document')
                    ->relationship('document', 'document_type')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('default_unit')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_paid')
                    ->label('Paid'),
                TernaryFilter::make('is_system')
                    ->label('System'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}
