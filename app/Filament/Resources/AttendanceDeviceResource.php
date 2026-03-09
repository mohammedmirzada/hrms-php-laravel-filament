<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceDeviceResource\Pages;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceDeviceResource extends Resource
{
    protected static ?string $model = AttendanceDevice::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::FingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('vendor')
                    ->maxLength(255)
                    ->nullable(),
                TextInput::make('ip_address')
                    ->label('IP Address')
                    ->maxLength(45)
                    ->nullable(),
                TextInput::make('port')
                    ->numeric()
                    ->nullable(),
                Select::make('sync_mode')
                    ->options([
                        'push' => 'Push',
                        'pull' => 'Pull',
                        'manual' => 'Manual',
                    ])
                    ->nullable(),
                DateTimePicker::make('last_sync_at')
                    ->label('Last Sync')
                    ->disabled()
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('vendor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->sortable(),
                TextColumn::make('port')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('sync_mode')
                    ->badge()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('last_sync_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
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
            'index' => Pages\ListAttendanceDevices::route('/'),
            'create' => Pages\CreateAttendanceDevice::route('/create'),
            'edit' => Pages\EditAttendanceDevice::route('/{record}/edit'),
        ];
    }
}
