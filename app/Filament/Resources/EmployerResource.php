<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslatableFields;
use App\Filament\Resources\EmployerResource\Pages;
use App\Filament\Resources\EmployerResource\RelationManagers;
use App\Models\Employer;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EmployerResource extends Resource
{
    use HasTranslatableFields;

    protected static ?string $model = Employer::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $modelLabel = 'Employee';

    protected static ?string $pluralModelLabel = 'Employees';

    protected static string|UnitEnum|null $navigationGroup = 'Employees';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Employee')
                    ->schema([
                        Tab::make('Personal Information')
                            ->icon(Heroicon::User)
                            ->schema([
                                Section::make('Full Name')
                                    ->icon(Heroicon::Identification)
                                    ->schema([
                                        static::translatableTabs('full_name', 'Full Name', required: true),
                                    ]),

                                Section::make('Personal Details')
                                    ->icon(Heroicon::UserCircle)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('genre')
                                                    ->label('Gender')
                                                    ->options([
                                                        'male' => 'Male',
                                                        'female' => 'Female',
                                                    ])
                                                    ->required(),
                                                DatePicker::make('date_of_birth')
                                                    ->label('Date of Birth')
                                                    ->required()
                                                    ->maxDate(now()->subYears(16)),
                                                Select::make('marital_status')
                                                    ->label('Marital Status')
                                                    ->options([
                                                        'single' => 'Single',
                                                        'married' => 'Married',
                                                        'divorced' => 'Divorced',
                                                        'widowed' => 'Widowed',
                                                    ])
                                                    ->required(),
                                            ]),
                                    ]),

                                Section::make('Contact Information')
                                    ->icon(Heroicon::Phone)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255),
                                                TextInput::make('phone_number_1')
                                                    ->label('Primary Phone')
                                                    ->tel()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(20),
                                                TextInput::make('phone_number_2')
                                                    ->label('Secondary Phone')
                                                    ->tel()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(20),
                                            ]),
                                    ]),

                                Section::make('Emergency Contact')
                                    ->icon(Heroicon::ExclamationTriangle)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('emergency_contact.name')
                                                    ->label('Contact Name')
                                                    ->maxLength(255),
                                                TextInput::make('emergency_contact.phone')
                                                    ->label('Contact Phone')
                                                    ->tel()
                                                    ->maxLength(20),
                                                TextInput::make('emergency_contact.relation')
                                                    ->label('Relationship')
                                                    ->maxLength(100),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Employment Information')
                            ->icon(Heroicon::Briefcase)
                            ->schema([
                                Section::make('Assignment')
                                    ->icon(Heroicon::BuildingOffice2)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('department_id')
                                                    ->relationship('department', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                                                    ->searchable()
                                                    ->preload(),
                                                Select::make('position_id')
                                                    ->relationship('position', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                                                    ->searchable()
                                                    ->preload(),
                                                Select::make('manager_id')
                                                    ->label('Manager')
                                                    ->relationship('manager', 'full_name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                                                    ->searchable()
                                                    ->preload(),
                                            ]),
                                    ]),

                                Section::make('Dates & Contract')
                                    ->icon(Heroicon::CalendarDays)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                DatePicker::make('hire_date')
                                                    ->label('Hiring Date')
                                                    ->required(),
                                                DatePicker::make('probation_period_start_date')
                                                    ->label('Probation Start'),
                                                DatePicker::make('probation_period_end_date')
                                                    ->label('Probation End'),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                DatePicker::make('contract_expiry_date')
                                                    ->label('Contract Expiry'),
                                                Select::make('employment_status_id')
                                                    ->label('Employment Status')
                                                    ->relationship('employmentStatus', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en') . ' (' . $record->code . ')')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('salary_structure_id')
                                                    ->label('Salary Structure')
                                                    ->relationship('salaryStructure', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone_number_1')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->formatStateUsing(fn ($record) => $record->department?->getTranslation('name', 'en'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->formatStateUsing(fn ($record) => $record->position?->getTranslation('name', 'en'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('employmentStatus.code')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'resigned', 'terminated' => 'danger',
                        'future_hired' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('hire_date')
                    ->label('Hire Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employment_status_id')
                    ->label('Status')
                    ->relationship('employmentStatus', 'code')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('position_id')
                    ->label('Position')
                    ->relationship('position', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('marital_status')
                    ->options([
                        'single' => 'Single',
                        'married' => 'Married',
                        'divorced' => 'Divorced',
                        'widowed' => 'Widowed',
                    ]),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\CompensationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployers::route('/'),
            'create' => Pages\CreateEmployer::route('/create'),
            'view' => Pages\ViewEmployer::route('/{record}'),
            'edit' => Pages\EditEmployer::route('/{record}/edit'),
        ];
    }
}
