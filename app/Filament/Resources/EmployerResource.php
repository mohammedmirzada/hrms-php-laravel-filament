<?php

namespace App\Filament\Resources;

use App\Enums\DocumentType;
use App\Enums\EmergencyContactRelation;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Filament\Concerns\HasTranslatableFields;
use App\Filament\Resources\EmployerResource\Pages;
use App\Filament\Resources\EmployerResource\RelationManagers;
use App\Models\Employer;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                                Section::make('Profile & Name')
                                    ->icon(Heroicon::Identification)
                                    ->schema([
                                        FileUpload::make('profile_picture')
                                            ->label('Profile Picture')
                                            ->directory('employer-photos')
                                            ->disk('public')
                                            ->image()
                                            ->maxSize(5120)
                                            ->circleCropper(true)
                                            ->imageAspectRatio('1:1')
                                            ->imageEditor()
                                            ->alignCenter(true)
                                            ->circleCropper()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->avatar()
                                            ->imageAspectRatio('1:1')
                                            ->alignCenter(true)
                                            ->columnSpanFull()
                                            ->helperText('Optional. Max 5MB. The photo will be shown on the employee card and list view.'),
                                        static::translatableTabs('full_name', 'Full Name', required: true),
                                    ]),

                                Section::make('Personal Details')
                                    ->icon(Heroicon::UserCircle)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('genre')
                                                    ->native(false)
                                                    ->label('Gender')
                                                    ->options(Gender::labels())
                                                    ->required()
                                                    ->helperText('Used for HR reporting and compliance records.'),
                                                DatePicker::make('date_of_birth')
                                                    ->native(false)
                                                    ->label('Date of Birth')
                                                    ->required()
                                                    ->maxDate(now()->subYears(16))
                                                    ->helperText('Must be at least 16 years ago. Used for age calculations and legal compliance.'),
                                                Select::make('marital_status')
                                                    ->native(false)
                                                    ->label('Marital Status')
                                                    ->options(MaritalStatus::labels())
                                                    ->required()
                                                    ->helperText('Used for tax and benefits calculations in some countries.'),
                                            ]),
                                    ]),

                                Section::make('Contact Information')
                                    ->icon(Heroicon::Phone)
                                    ->schema([
                                        TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                        Grid::make(2)
                                            ->schema([
                                                Grid::make(5)
                                                    ->schema([
                                                        Select::make('phone_code_1')
                                                            ->label('Code')
                                                            ->options(static::getPhoneCodes())
                                                            ->native(false)
                                                            ->searchable()
                                                            ->default('+964')
                                                            ->dehydrated(false)
                                                            ->columnSpan(2),
                                                        TextInput::make('phone_number_1')
                                                            ->label('Primary Phone')
                                                            ->tel()
                                                            ->required()
                                                            ->unique(ignoreRecord: true)
                                                            ->maxLength(20)
                                                            ->helperText('Select the country code first, then type the number without the country prefix.')
                                                            ->afterStateHydrated(function ($state, $set) {
                                                                if ($state && str_starts_with($state, '+')) {
                                                                    $parts = explode(' ', $state, 2);
                                                                    if (count($parts) === 2) {
                                                                        $set('phone_code_1', $parts[0]);
                                                                        $set('phone_number_1', $parts[1]);
                                                                    }
                                                                }
                                                            })
                                                            ->dehydrateStateUsing(fn ($state, $get) => ($get('phone_code_1') ?? '+670') . ' ' . $state)
                                                            ->columnSpan(3),
                                                    ]),
                                                Grid::make(5)
                                                    ->schema([
                                                        Select::make('phone_code_2')
                                                            ->label('Code')
                                                            ->options(static::getPhoneCodes())
                                                            ->native(false)
                                                            ->searchable()
                                                            ->default('+964')
                                                            ->dehydrated(false)
                                                            ->columnSpan(2),
                                                        TextInput::make('phone_number_2')
                                                            ->label('Secondary Phone')
                                                            ->tel()
                                                            ->unique(ignoreRecord: true)
                                                            ->maxLength(20)
                                                            ->helperText('Optional. A second number (personal, home, or work alternate).')
                                                            ->afterStateHydrated(function ($state, $set) {
                                                                if ($state && str_starts_with($state, '+')) {
                                                                    $parts = explode(' ', $state, 2);
                                                                    if (count($parts) === 2) {
                                                                        $set('phone_code_2', $parts[0]);
                                                                        $set('phone_number_2', $parts[1]);
                                                                    }
                                                                }
                                                            })
                                                            ->dehydrateStateUsing(fn ($state, $get) => $state ? (($get('phone_code_2') ?? '+670') . ' ' . $state) : null)
                                                            ->columnSpan(3),
                                                    ]),
                                            ]),
                                    ]),

                                Section::make('Emergency Contacts')
                                    ->icon(Heroicon::ExclamationTriangle)
                                    ->schema([
                                        Repeater::make('emergency_contact')
                                            ->label('')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Contact Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->helperText('Full name of the person to contact in an emergency.'),
                                                Grid::make(5)
                                                    ->schema([
                                                        Select::make('phone_code')
                                                            ->label('Code')
                                                            ->options(static::getPhoneCodes())
                                                            ->native(false)
                                                            ->searchable()
                                                            ->default('+964')
                                                            ->columnSpan(2),
                                                        TextInput::make('phone')
                                                            ->label('Contact Phone')
                                                            ->tel()
                                                            ->required()
                                                            ->maxLength(20)
                                                            ->helperText('Include country code (e.g. +964 770...).')
                                                            ->columnSpan(3),
                                                    ]),
                                                Select::make('relation')
                                                    ->native(false)
                                                    ->label('Relationship')
                                                    ->options(EmergencyContactRelation::labels())
                                                    ->required()
                                                    ->helperText('How this person is related to the employee.'),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull()
                                            ->defaultItems(1)
                                            ->addActionLabel('Add Emergency Contact')
                                            ->maxItems(3),
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
                                                    ->native(false)
                                                    ->relationship('department', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm(DepartmentResource::form(Schema::make())->getComponents()),
                                                Select::make('position_id')
                                                    ->native(false)
                                                    ->relationship('position', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm(PositionResource::form(Schema::make())->getComponents()),
                                                Select::make('manager_id')
                                                    ->native(false)
                                                    ->label('Manager')
                                                    ->relationship(
                                                        'manager',
                                                        'full_name',
                                                        fn ($query, $record) => $query->when(
                                                            $record?->id,
                                                            fn ($q) => $q->where('id', '!=', $record->id)
                                                        )
                                                    )
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                                                    ->searchable()
                                                    ->preload(),
                                                Select::make('branch_id')
                                                    ->native(false)
                                                    ->label('Branch')
                                                    ->relationship('branch', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm(BranchResource::form(Schema::make())->getComponents()),
                                            ]),
                                    ]),

                                Section::make('Dates & Contract')
                                    ->icon(Heroicon::CalendarDays)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                DatePicker::make('hire_date')
                                                    ->native(false)
                                                    ->label('Hiring Date')
                                                    ->required()
                                                    ->helperText('The first official working day. Used to calculate tenure, leave accrual start, and payroll eligibility.'),
                                                DatePicker::make('probation_period_start_date')
                                                    ->native(false)
                                                    ->label('Probation Start')
                                                    ->helperText('The first day of the trial period. Leave empty if this employee has no probation.'),
                                                DatePicker::make('probation_period_end_date')
                                                    ->native(false)
                                                    ->label('Probation End')
                                                    ->after('probation_period_start_date')
                                                    ->helperText('The last day of the trial period. Must be after Probation Start. Leave accrual policies set to "After Probation" will start from this date.'),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('contract_expiry_date')
                                                    ->native(false)
                                                    ->label('Contract Expiry')
                                                    ->helperText('Leave empty for permanent employees. Set for fixed-term contracts so HR can track renewals before they expire.'),
                                                Select::make('employment_status_id')
                                                    ->native(false)
                                                    ->label('Employment Status')
                                                    ->relationship('employmentStatus', 'name')
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en') . ' (' . $record->code . ')')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->createOptionForm(EmploymentStatusResource::form(Schema::make())->getComponents()),
                                            ]),
                                    ]),

                            ]),

                        Tab::make('Authentication')
                            ->icon(Heroicon::LockClosed)
                            ->schema([
                                Section::make('Employee Portal Access')
                                    ->icon(Heroicon::ComputerDesktop)
                                    ->description('Set a password to allow this employee to log in to the employee portal.')
                                    ->schema([
                                        Text::make(new \Illuminate\Support\HtmlString(
                                            'Employee portal login page: <a href="' . url('/employee/login') . '" target="_blank" class="fi-link text-primary-600 underline">' . url('/employee/login') . '</a><br><span class="text-sm text-gray-500">Share this link with the employee along with their email and password.</span>'
                                        )),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('password')
                                                    ->label('Password')
                                                    ->password()
                                                    ->revealable()
                                                    ->minLength(8)
                                                    ->maxLength(255)
                                                    ->dehydrated(fn ($state) => filled($state))
                                                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                                                    ->helperText('Leave blank to keep the current password. Min 8 characters.'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Documents & Media')
                            ->icon(Heroicon::DocumentText)
                            ->schema([
                                Repeater::make('documents')
                                            ->relationship()
                                            ->schema([
                                                Select::make('document_type')
                                                    ->native(false)
                                                    ->options(DocumentType::labels())
                                                    ->required()
                                                    ->helperText('Choose the category that best describes this file.'),
                                                FileUpload::make('file_path')
                                                    ->label('File')
                                                    ->directory('documents')
                                                    ->disk('public')
                                                    ->maxSize(5120)
                                                    ->openable()
                                                    ->previewable()
                                                    ->required()
                                                    ->helperText('Max 5MB. Accepted formats: PDF, images, Word documents.'),
                                                DatePicker::make('expiry_date')
                                                    ->native(false)
                                                    ->label('Expiry Date')
                                                    ->helperText('Optional. Set for documents that expire (visas, work permits, passports) so renewals can be tracked.'),
                                            ])
                                            ->columnSpanFull()
                                            ->defaultItems(0)
                                            ->addActionLabel('Add Document'),
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
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('profile_picture')
                    ->label('Photo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'data:image/svg+xml,' . rawurlencode(
                        '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
                        . '<rect width="128" height="128" fill="#EBF4FF"/>'
                        . '<text x="64" y="64" font-size="48" fill="#7F9CF5" font-family="sans-serif" text-anchor="middle" dominant-baseline="central">'
                        . mb_strtoupper(mb_substr($record->getTranslation('full_name', 'en') ?? '?', 0, 1))
                        . '</text></svg>'
                    )),
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
                    ->searchable()
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
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->formatStateUsing(fn ($record) => $record->createdBy?->name)
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
                    ->options(MaritalStatus::labels())
                    ->searchable()
                    ->native(false),
                Filter::make('hire_date')
                    ->schema([
                        DatePicker::make('hire_date_from')
                            ->native(false)
                            ->label('Hire Date From'),
                        DatePicker::make('hire_date_to')
                            ->native(false)
                            ->label('Hire Date To'),
                    ])
                    ->query(function ($query, $data) {
                        if ($data['hire_date_from']) {
                            $query->whereDate('hire_date', '>=', $data['hire_date_from']);
                        }
                        if ($data['hire_date_to']) {
                            $query->whereDate('hire_date', '<=', $data['hire_date_to']);
                        }
                    }),
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
            RelationManagers\ShiftsRelationManager::class,
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

    private static function getPhoneCodes(): array
    {
        return [
            '+93' => 'Afghanistan (+93)',
            '+355' => 'Albania (+355)',
            '+213' => 'Algeria (+213)',
            '+376' => 'Andorra (+376)',
            '+244' => 'Angola (+244)',
            '+1-268' => 'Antigua & Barbuda (+1-268)',
            '+54' => 'Argentina (+54)',
            '+374' => 'Armenia (+374)',
            '+61' => 'Australia (+61)',
            '+43' => 'Austria (+43)',
            '+994' => 'Azerbaijan (+994)',
            '+1-242' => 'Bahamas (+1-242)',
            '+973' => 'Bahrain (+973)',
            '+880' => 'Bangladesh (+880)',
            '+1-246' => 'Barbados (+1-246)',
            '+375' => 'Belarus (+375)',
            '+32' => 'Belgium (+32)',
            '+501' => 'Belize (+501)',
            '+229' => 'Benin (+229)',
            '+975' => 'Bhutan (+975)',
            '+591' => 'Bolivia (+591)',
            '+387' => 'Bosnia & Herzegovina (+387)',
            '+267' => 'Botswana (+267)',
            '+55' => 'Brazil (+55)',
            '+673' => 'Brunei (+673)',
            '+359' => 'Bulgaria (+359)',
            '+226' => 'Burkina Faso (+226)',
            '+257' => 'Burundi (+257)',
            '+855' => 'Cambodia (+855)',
            '+237' => 'Cameroon (+237)',
            '+1' => 'Canada / USA (+1)',
            '+238' => 'Cape Verde (+238)',
            '+236' => 'Central African Republic (+236)',
            '+235' => 'Chad (+235)',
            '+56' => 'Chile (+56)',
            '+86' => 'China (+86)',
            '+57' => 'Colombia (+57)',
            '+269' => 'Comoros (+269)',
            '+242' => 'Congo (+242)',
            '+243' => 'Congo DR (+243)',
            '+506' => 'Costa Rica (+506)',
            '+225' => 'Ivory Coast (+225)',
            '+385' => 'Croatia (+385)',
            '+53' => 'Cuba (+53)',
            '+357' => 'Cyprus (+357)',
            '+420' => 'Czech Republic (+420)',
            '+45' => 'Denmark (+45)',
            '+253' => 'Djibouti (+253)',
            '+1-767' => 'Dominica (+1-767)',
            '+1-809' => 'Dominican Republic (+1-809)',
            '+670' => 'East Timor (+670)',
            '+593' => 'Ecuador (+593)',
            '+20' => 'Egypt (+20)',
            '+503' => 'El Salvador (+503)',
            '+240' => 'Equatorial Guinea (+240)',
            '+291' => 'Eritrea (+291)',
            '+372' => 'Estonia (+372)',
            '+268' => 'Eswatini (+268)',
            '+251' => 'Ethiopia (+251)',
            '+679' => 'Fiji (+679)',
            '+358' => 'Finland (+358)',
            '+33' => 'France (+33)',
            '+241' => 'Gabon (+241)',
            '+220' => 'Gambia (+220)',
            '+995' => 'Georgia (+995)',
            '+49' => 'Germany (+49)',
            '+233' => 'Ghana (+233)',
            '+30' => 'Greece (+30)',
            '+1-473' => 'Grenada (+1-473)',
            '+502' => 'Guatemala (+502)',
            '+224' => 'Guinea (+224)',
            '+245' => 'Guinea-Bissau (+245)',
            '+592' => 'Guyana (+592)',
            '+509' => 'Haiti (+509)',
            '+504' => 'Honduras (+504)',
            '+852' => 'Hong Kong (+852)',
            '+36' => 'Hungary (+36)',
            '+354' => 'Iceland (+354)',
            '+91' => 'India (+91)',
            '+62' => 'Indonesia (+62)',
            '+98' => 'Iran (+98)',
            '+964' => 'Iraq (+964)',
            '+353' => 'Ireland (+353)',
            '+972' => 'Israel (+972)',
            '+39' => 'Italy (+39)',
            '+1-876' => 'Jamaica (+1-876)',
            '+81' => 'Japan (+81)',
            '+962' => 'Jordan (+962)',
            '+7' => 'Kazakhstan / Russia (+7)',
            '+254' => 'Kenya (+254)',
            '+686' => 'Kiribati (+686)',
            '+850' => 'North Korea (+850)',
            '+82' => 'South Korea (+82)',
            '+383' => 'Kosovo (+383)',
            '+965' => 'Kuwait (+965)',
            '+996' => 'Kyrgyzstan (+996)',
            '+856' => 'Laos (+856)',
            '+371' => 'Latvia (+371)',
            '+961' => 'Lebanon (+961)',
            '+266' => 'Lesotho (+266)',
            '+231' => 'Liberia (+231)',
            '+218' => 'Libya (+218)',
            '+423' => 'Liechtenstein (+423)',
            '+370' => 'Lithuania (+370)',
            '+352' => 'Luxembourg (+352)',
            '+853' => 'Macau (+853)',
            '+261' => 'Madagascar (+261)',
            '+265' => 'Malawi (+265)',
            '+60' => 'Malaysia (+60)',
            '+960' => 'Maldives (+960)',
            '+223' => 'Mali (+223)',
            '+356' => 'Malta (+356)',
            '+692' => 'Marshall Islands (+692)',
            '+222' => 'Mauritania (+222)',
            '+230' => 'Mauritius (+230)',
            '+52' => 'Mexico (+52)',
            '+691' => 'Micronesia (+691)',
            '+373' => 'Moldova (+373)',
            '+377' => 'Monaco (+377)',
            '+976' => 'Mongolia (+976)',
            '+382' => 'Montenegro (+382)',
            '+212' => 'Morocco (+212)',
            '+258' => 'Mozambique (+258)',
            '+95' => 'Myanmar (+95)',
            '+264' => 'Namibia (+264)',
            '+674' => 'Nauru (+674)',
            '+977' => 'Nepal (+977)',
            '+31' => 'Netherlands (+31)',
            '+64' => 'New Zealand (+64)',
            '+505' => 'Nicaragua (+505)',
            '+227' => 'Niger (+227)',
            '+234' => 'Nigeria (+234)',
            '+389' => 'North Macedonia (+389)',
            '+47' => 'Norway (+47)',
            '+968' => 'Oman (+968)',
            '+92' => 'Pakistan (+92)',
            '+680' => 'Palau (+680)',
            '+970' => 'Palestine (+970)',
            '+507' => 'Panama (+507)',
            '+675' => 'Papua New Guinea (+675)',
            '+595' => 'Paraguay (+595)',
            '+51' => 'Peru (+51)',
            '+63' => 'Philippines (+63)',
            '+48' => 'Poland (+48)',
            '+351' => 'Portugal (+351)',
            '+1-787' => 'Puerto Rico (+1-787)',
            '+974' => 'Qatar (+974)',
            '+40' => 'Romania (+40)',
            '+250' => 'Rwanda (+250)',
            '+685' => 'Samoa (+685)',
            '+378' => 'San Marino (+378)',
            '+239' => 'Sao Tome & Principe (+239)',
            '+966' => 'Saudi Arabia (+966)',
            '+221' => 'Senegal (+221)',
            '+381' => 'Serbia (+381)',
            '+248' => 'Seychelles (+248)',
            '+232' => 'Sierra Leone (+232)',
            '+65' => 'Singapore (+65)',
            '+421' => 'Slovakia (+421)',
            '+386' => 'Slovenia (+386)',
            '+677' => 'Solomon Islands (+677)',
            '+252' => 'Somalia (+252)',
            '+27' => 'South Africa (+27)',
            '+211' => 'South Sudan (+211)',
            '+34' => 'Spain (+34)',
            '+94' => 'Sri Lanka (+94)',
            '+249' => 'Sudan (+249)',
            '+597' => 'Suriname (+597)',
            '+46' => 'Sweden (+46)',
            '+41' => 'Switzerland (+41)',
            '+963' => 'Syria (+963)',
            '+886' => 'Taiwan (+886)',
            '+992' => 'Tajikistan (+992)',
            '+255' => 'Tanzania (+255)',
            '+66' => 'Thailand (+66)',
            '+228' => 'Togo (+228)',
            '+676' => 'Tonga (+676)',
            '+1-868' => 'Trinidad & Tobago (+1-868)',
            '+216' => 'Tunisia (+216)',
            '+90' => 'Turkey (+90)',
            '+993' => 'Turkmenistan (+993)',
            '+688' => 'Tuvalu (+688)',
            '+256' => 'Uganda (+256)',
            '+380' => 'Ukraine (+380)',
            '+971' => 'UAE (+971)',
            '+44' => 'United Kingdom (+44)',
            '+598' => 'Uruguay (+598)',
            '+998' => 'Uzbekistan (+998)',
            '+678' => 'Vanuatu (+678)',
            '+379' => 'Vatican City (+379)',
            '+58' => 'Venezuela (+58)',
            '+84' => 'Vietnam (+84)',
            '+967' => 'Yemen (+967)',
            '+260' => 'Zambia (+260)',
            '+263' => 'Zimbabwe (+263)',
        ];
    }

    // Global search configuration
    
    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array {
        return ['full_name', 'email', 'phone_number_1', 'phone_number_2'];
    }

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = true;

}
