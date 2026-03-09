<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceBranchSettingResource\Pages;
use App\Models\AttendanceBranchSetting;
use App\Models\Branch;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceBranchSettingResource extends Resource
{
    protected static ?string $model = AttendanceBranchSetting::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Cog6Tooth;

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
                KeyValue::make('settings')
                    ->label('Settings')
                    ->helperText('Working hours, grace period, OT rules, shift templates, mobile rules, geofence')
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
            'index' => Pages\ListAttendanceBranchSettings::route('/'),
            'create' => Pages\CreateAttendanceBranchSetting::route('/create'),
            'edit' => Pages\EditAttendanceBranchSetting::route('/{record}/edit'),
        ];
    }
}
