<?php

namespace App\Filament\Resources\EmployerShifts;

use App\Filament\Resources\EmployerShifts\Pages\CreateEmployerShift;
use App\Filament\Resources\EmployerShifts\Pages\EditEmployerShift;
use App\Filament\Resources\EmployerShifts\Pages\ListEmployerShifts;
use App\Filament\Resources\EmployerShifts\Schemas\EmployerShiftForm;
use App\Filament\Resources\EmployerShifts\Tables\EmployerShiftsTable;
use App\Models\EmployerShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmployerShiftResource extends Resource
{
    protected static ?string $model = EmployerShift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Employee Shifts';

    protected static ?string $modelLabel = 'Employee Shift';

    public static function form(Schema $schema): Schema
    {
        return EmployerShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployerShiftsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployerShifts::route('/'),
            'create' => CreateEmployerShift::route('/create'),
            'edit' => EditEmployerShift::route('/{record}/edit'),
        ];
    }
}
