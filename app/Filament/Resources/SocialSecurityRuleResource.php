<?php

namespace App\Filament\Resources;

use App\Enums\EmploymentType;
use App\Enums\SocialSecurityBaseRule;
use App\Filament\Resources\SocialSecurityRuleResource\Pages;
use App\Models\Branch;
use App\Models\SocialSecurityRule;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SocialSecurityRuleResource extends Resource
{
    protected static ?string $model = SocialSecurityRule::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll & Compensation';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Rule Setup')
                    ->schema([
                        Select::make('branch_id')
                            ->native(false)
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The branch this rule applies to. Different branches or countries may have different social security laws.'),
                        Select::make('employment_type')
                            ->native(false)
                            ->options(EmploymentType::labels())
                            ->required()
                            ->helperText('Social security rates often differ by employment type. This rule will only apply to employees of the selected type.'),
                        Select::make('base_rule')
                            ->native(false)
                            ->options(SocialSecurityBaseRule::labels())
                            ->required()
                            ->helperText('Which part of the salary to calculate contributions on. "Basic Only" uses the fixed base salary. "Basic + Marked Items" includes specific allowances. "Gross" uses the full total before deductions.'),
                        Select::make('currency_code')
                            ->native(false)
                            ->label('Currency')
                            ->options(config('currency'))
                            ->default('USD')
                            ->required()
                            ->searchable()
                            ->helperText('The currency used for the contribution cap amount below.'),
                    ])
                    ->columns(2),

                Section::make('Contribution Rates')
                    ->schema([
                        TextInput::make('employer_percent')
                            ->label('Employer %')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->helperText('The percentage the company pays to the government on top of the employee\'s salary (e.g. 5 means the company pays an extra 5% of the base).'),
                        TextInput::make('employee_percent')
                            ->label('Employee %')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->helperText('The percentage deducted from the employee\'s own salary and sent to the government (e.g. 3 means $30 is deducted from a $1,000 salary).'),
                    ])
                    ->columns(2),

                Section::make('Cap Settings')
                    ->schema([
                        Toggle::make('cap_enabled')
                            ->label('Enable Cap')
                            ->live()
                            ->helperText('Turn on if the law sets a maximum monthly contribution amount, regardless of how high the salary is.'),
                        TextInput::make('cap_amount')
                            ->numeric()
                            ->nullable()
                            ->hidden(fn ($get) => !$get('cap_enabled'))
                            ->helperText('The maximum social security contribution per month in the selected currency. Contributions will never exceed this amount.'),
                    ])
                    ->columns(2),

                Section::make('Effective Period')
                    ->schema([
                        DatePicker::make('effective_from')
                            ->native(false)
                            ->required()
                            ->default(now())
                            ->helperText('The date this rule starts being applied to payroll calculations.'),
                        DatePicker::make('effective_to')
                            ->native(false)
                            ->nullable()
                            ->after('effective_from')
                            ->helperText('The last date this rule is active. Leave empty if this rule is still in effect today. When the law changes, set this date and create a new rule.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('employment_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('employer_percent')
                    ->label('Employer %')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('employee_percent')
                    ->label('Employee %')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('base_rule')
                    ->badge()
                    ->sortable(),
                IconColumn::make('cap_enabled')
                    ->label('Cap')
                    ->boolean(),
                TextColumn::make('cap_amount')
                    ->money(fn ($record) => $record->currency_code)
                    ->placeholder('—'),
                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->badge(),
                TextColumn::make('effective_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->date()
                    ->sortable()
                    ->placeholder('Ongoing'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload(),
                SelectFilter::make('employment_type')
                    ->options(EmploymentType::labels())
                    ->searchable()
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialSecurityRules::route('/'),
            'create' => Pages\CreateSocialSecurityRule::route('/create'),
            'edit' => Pages\EditSocialSecurityRule::route('/{record}/edit'),
        ];
    }
}
