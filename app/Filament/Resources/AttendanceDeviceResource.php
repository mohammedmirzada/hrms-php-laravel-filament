<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceDeviceSyncMode;
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
                    ->native(false)
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('The branch where this device is physically installed.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A friendly label for this device (e.g. "Main Entrance", "Warehouse Gate"). Shown when linking attendance events to a device.'),
                TextInput::make('vendor')
                    ->maxLength(255)
                    ->nullable()
                    ->helperText('Optional. The manufacturer name (e.g. ZKTeco, Hikvision). Useful for maintenance records.'),
                TextInput::make('mac_address')
                    ->label('MAC Address')
                    ->maxLength(255)
                    ->nullable()
                    ->helperText('Optional. The device\'s MAC address for network identification. Format: XX:XX:XX:XX:XX:XX'),
                TextInput::make('ip_address')
                    ->label('IP Address')
                    ->maxLength(45)
                    ->nullable()
                    ->helperText('The device\'s local network IP address. Required for Push and Pull sync modes (e.g. 192.168.1.100).'),
                TextInput::make('port')
                    ->numeric()
                    ->nullable()
                    ->helperText('The network port the device listens on. Commonly 4370 for ZKTeco devices. Check your device manual if unsure.')
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
                TextColumn::make('mac_address')
                    ->label('MAC Address')
                    ->searchable()
                    ->placeholder('XX:XX:XX:XX:XX:XX'),
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
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->native(false)
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
