<?php

namespace App\Filament\Resources;

use App\Enums\LeaveDocumentType;
use App\Enums\LeaveUnit;
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
                    ->native(false)
                    ->options(LeaveUnit::labels())
                    ->required()
                    ->helperText('How leave duration is measured for this type. Day-based types count in full or half days. Hour-based types count exact hours worked.'),
                Toggle::make('is_paid')
                    ->label('Paid Leave')
                    ->columnSpanFull()
                    ->default(true)
                    ->helperText('Turn on if employees continue to receive their normal salary while on this type of leave (e.g. Annual Leave). Turn off for unpaid types (e.g. Unpaid Leave).'),
                Toggle::make('is_system')
                    ->label('System Type')
                    ->columnSpanFull()
                    ->helperText('System types cannot be deleted by users'),
                Select::make('document_type')
                    ->native(false)
                    ->label('Required Document Type')
                    ->options(LeaveDocumentType::labels())
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('If set, employees must upload this document type when submitting a request for this leave. Leave empty if no proof is required (e.g. no document needed for Annual Leave).'),
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
                    ->label('Paid')
                    ->native(false),
                TernaryFilter::make('is_system')
                    ->label('System')
                    ->native(false),
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

    // Global search configuration
    
    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array {
        return ['name'];
    }

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = true;
}
