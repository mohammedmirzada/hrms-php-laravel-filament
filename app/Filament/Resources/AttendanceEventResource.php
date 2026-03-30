<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceEventResource\Pages;
use App\Models\AttendanceEvent;
use App\Models\Branch;
use App\Models\Employer;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceEventResource extends Resource
{
    protected static ?string $model = AttendanceEvent::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::QueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Attendance Events';

    protected static ?string $modelLabel = 'Attendance Event';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Event Details')
                    ->icon(Heroicon::InformationCircle)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('branch_id')
                                ->native(false)
                                ->label('Branch')
                                ->relationship('branch', 'name')
                                ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                                ->required()
                                ->searchable()
                                ->preload(),
                            Select::make('employer_id')
                                ->native(false)
                                ->label('Employee')
                                ->relationship('employer', 'full_name')
                                ->getOptionLabelFromRecordUsing(fn (Employer $record) => $record->getTranslation('full_name', 'en'))
                                ->searchable()
                                ->preload(),
                            Select::make('device_id')
                                ->native(false)
                                ->label('Device')
                                ->relationship('device', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('device_user_code')
                                ->label('Device User Code')
                                ->maxLength(255),
                            Select::make('source')
                                ->native(false)
                                ->options([
                                    'BIOMETRIC' => 'Biometric',
                                    'MOBILE' => 'Mobile'
                                ])
                                ->required(),
                            Select::make('event_type')
                                ->native(false)
                                ->options([
                                    'IN' => 'In',
                                    'OUT' => 'Out',
                                ]),
                        ]),
                        DateTimePicker::make('event_at')
                            ->native(false)
                            ->label('Event Time')
                            ->required(),
                    ]),

                Section::make('Validation')
                    ->icon(Heroicon::ShieldCheck)
                    ->collapsible()
                    ->schema([
                        FileUpload::make('selfie_path')
                            ->label('Selfie')
                            ->directory('attendance-selfies')
                            ->disk('public')
                            ->image()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        Toggle::make('is_valid')
                            ->label('Valid')
                            ->default(true)
                            ->reactive(),
                        TextInput::make('invalid_reason')
                            ->label('Invalid Reason')
                            ->maxLength(255)
                            ->visible(fn ($get) => !$get('is_valid')),
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
                TextColumn::make('source')
                    ->badge()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'IN' => 'success',
                        'OUT' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('event_at')
                    ->label('Event Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('device.name')
                    ->label('Device')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                IconColumn::make('is_valid')
                    ->label('Valid')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options([
                        'BIOMETRIC' => 'Biometric',
                        'MOBILE' => 'Mobile'
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('event_type')
                    ->options([
                        'IN' => 'In',
                        'OUT' => 'Out',
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('employer_id')
                    ->label('Employee')
                    ->relationship('employer', 'full_name')
                    ->searchable()
                    ->preload()
                    ->native(false)
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
            ->defaultSort('event_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceEvents::route('/'),
            'create' => Pages\CreateAttendanceEvent::route('/create'),
            'edit' => Pages\EditAttendanceEvent::route('/{record}/edit'),
        ];
    }
}
