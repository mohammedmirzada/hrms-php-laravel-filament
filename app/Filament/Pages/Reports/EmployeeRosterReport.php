<?php

namespace App\Filament\Pages\Reports;

use App\Models\Employer;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeRosterReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::UserGroup;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Employee Roster';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Employee Roster / Directory';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employer::query()
                    ->with(['department', 'position', 'branch', 'employmentStatus', 'manager'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number_1')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->formatStateUsing(fn ($record) => $record->department?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->formatStateUsing(fn ($record) => $record->position?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('hire_date')
                    ->label('Hire Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('employmentStatus.name')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => $record->employmentStatus?->getTranslation('name', 'en'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('manager.full_name')
                    ->label('Manager')
                    ->formatStateUsing(fn ($record) => $record->manager?->getTranslation('full_name', 'en'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('position_id')
                    ->label('Position')
                    ->relationship('position', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('employment_status_id')
                    ->label('Employment Status')
                    ->relationship('employmentStatus', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('id');
    }
}
