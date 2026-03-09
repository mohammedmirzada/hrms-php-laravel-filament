<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceDayResource\Pages;
use App\Models\AttendanceDay;
use App\Models\Branch;
use App\Models\Employer;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class AttendanceDayResource extends Resource
{
    protected static ?string $model = AttendanceDay::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::TableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Daily Attendance';

    protected static ?string $modelLabel = 'Attendance Day';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Employee & Branch')
                    ->icon(Heroicon::UserGroup)
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
                            Select::make('status')
                                ->native(false)
                                ->options([
                                    'PRESENT' => 'Present',
                                    'ABSENT' => 'Absent',
                                    'LATE' => 'Late',
                                    'HOLIDAY' => 'Holiday',
                                    'WEEKEND' => 'Weekend',
                                    'ON_LEAVE' => 'On Leave',
                                    'INCOMPLETE' => 'Incomplete',
                                ])
                                ->required(),
                        ]),
                    ]),

                Section::make('Schedule')
                    ->icon(Heroicon::Clock)
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('date')
                                ->native(false)
                                ->required(),
                            TextInput::make('shift_code')
                                ->label('Shift Code')
                                ->placeholder('e.g. MORNING, NIGHT'),
                        ]),
                        Grid::make(2)->schema([
                            DateTimePicker::make('scheduled_start_at')
                                ->native(false)
                                ->label('Scheduled Start'),
                            DateTimePicker::make('scheduled_end_at')
                                ->native(false)
                                ->label('Scheduled End'),
                        ]),
                    ]),

                Section::make('Actual Attendance')
                    ->icon(Heroicon::ClipboardDocumentCheck)
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('first_in_at')
                                ->native(false)
                                ->label('First In'),
                            DateTimePicker::make('last_out_at')
                                ->native(false)
                                ->label('Last Out'),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('worked_minutes')
                                ->numeric()
                                ->default(0)
                                ->suffix('min'),
                            TextInput::make('late_minutes')
                                ->numeric()
                                ->default(0)
                                ->suffix('min'),
                            TextInput::make('overtime_minutes')
                                ->numeric()
                                ->default(0)
                                ->suffix('min'),
                        ]),
                    ]),

                Section::make('Override')
                    ->icon(Heroicon::PencilSquare)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('is_overridden')
                            ->label('Is Overridden')
                            ->reactive(),
                        Grid::make(2)->schema([
                            Select::make('override_by_user_id')
                                ->native(false)
                                ->label('Override By')
                                ->relationship('overrideByUser', 'name')
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('is_overridden')),
                            DateTimePicker::make('override_at')
                                ->native(false)
                                ->label('Override At')
                                ->visible(fn ($get) => $get('is_overridden')),
                        ]),
                        Textarea::make('override_reason')
                            ->label('Override Reason')
                            ->visible(fn ($get) => $get('is_overridden'))
                            ->columnSpanFull(),
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
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PRESENT' => 'success',
                        'ABSENT' => 'danger',
                        'LATE' => 'warning',
                        'HOLIDAY', 'WEEKEND' => 'info',
                        'ON_LEAVE' => 'primary',
                        'INCOMPLETE' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('shift_code')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('first_in_at')
                    ->label('In')
                    ->dateTime('H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('last_out_at')
                    ->label('Out')
                    ->dateTime('H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('worked_minutes')
                    ->label('Worked')
                    ->suffix(' min')
                    ->sortable(),
                TextColumn::make('late_minutes')
                    ->label('Late')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('overtime_minutes')
                    ->label('OT')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_overridden')
                    ->label('Override')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'PRESENT' => 'Present',
                        'ABSENT' => 'Absent',
                        'LATE' => 'Late',
                        'HOLIDAY' => 'Holiday',
                        'WEEKEND' => 'Weekend',
                        'ON_LEAVE' => 'On Leave',
                        'INCOMPLETE' => 'Incomplete',
                    ]),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
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
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceDays::route('/'),
            'create' => Pages\CreateAttendanceDay::route('/create'),
            'edit' => Pages\EditAttendanceDay::route('/{record}/edit'),
        ];
    }
}
