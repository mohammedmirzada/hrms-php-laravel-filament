<?php

namespace App\Filament\Resources;

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
                            ->preload(),
                        Select::make('employment_type')
                            ->native(false)
                            ->options([
                                'full_time' => 'Full Time',
                                'part_time' => 'Part Time',
                                'contract' => 'Contract',
                            ])
                            ->required(),
                        Select::make('base_rule')
                            ->native(false)
                            ->options([
                                'basic_only' => 'Basic Only',
                                'basic_plus_marked' => 'Basic + Marked Items',
                                'gross' => 'Gross Salary',
                            ])
                            ->required(),
                        Select::make('currency_code')
                            ->native(false)
                            ->label('Currency')
                            ->options(config('currency'))
                            ->default('USD')
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Contribution Rates')
                    ->schema([
                        TextInput::make('employer_percent')
                            ->label('Employer %')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('employee_percent')
                            ->label('Employee %')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Cap Settings')
                    ->schema([
                        Toggle::make('cap_enabled')
                            ->label('Enable Cap')
                            ->reactive(),
                        TextInput::make('cap_amount')
                            ->numeric()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Effective Period')
                    ->schema([
                        DatePicker::make('effective_from')
                            ->native(false)
                            ->required(),
                        DatePicker::make('effective_to')
                            ->native(false)
                            ->nullable()
                            ->after('effective_from'),
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
                    ->numeric()
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
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract' => 'Contract',
                    ])
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
