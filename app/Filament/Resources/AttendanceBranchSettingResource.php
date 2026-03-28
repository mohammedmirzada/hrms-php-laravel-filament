<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceBranchSettingResource\Pages;
use App\Models\AttendanceBranchSetting;
use App\Models\Branch;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceBranchSettingResource extends Resource
{
    protected static ?string $model = AttendanceBranchSetting::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::CubeTransparent;

    protected static string|UnitEnum|null $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Branch Settings';

    protected static ?string $modelLabel = 'Attendance Branch Setting';

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
                    ->unique(ignoreRecord: true),
                ComponentsSection::make('Settings')
                    ->schema([
                        TextInput::make('settings.late_grace_minutes')
                            ->label('Late Grace (min)')
                            ->helperText('e.g. 10 → If you arrive within 10 minutes of shift start, you\'re still on time. After that, you\'re marked late.')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('settings.overtime_threshold_minutes')
                            ->label('Overtime Threshold (min)')
                            ->helperText('e.g. 30 → You only get overtime credit if you stay 30+ minutes past shift end.')
                            ->numeric()
                            ->minValue(0),
                        TimePicker::make('settings.work_start_time')
                            ->label('Work Start Time')
                            ->helperText('e.g. 09:00 → The official start of the workday for this branch.')
                            ->seconds(false),
                        TimePicker::make('settings.work_end_time')
                            ->label('Work End Time')
                            ->helperText('e.g. 17:00 → The official end of the workday for this branch.')
                            ->seconds(false),
                        Toggle::make('settings.require_selfie')
                            ->label('Require Selfie')
                            ->helperText('If enabled, employees must take a photo when clocking in from their phone.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->preload()
                    ->native(false)
                    ->searchable()
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
            'index' => Pages\ListAttendanceBranchSettings::route('/'),
            'create' => Pages\CreateAttendanceBranchSetting::route('/create'),
            'edit' => Pages\EditAttendanceBranchSetting::route('/{record}/edit'),
        ];
    }
}
