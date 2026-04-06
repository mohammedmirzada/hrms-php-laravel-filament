<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\Activity;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityLogResource extends Resource {
    
    protected static ?string $model = Activity::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $modelLabel = 'Activity';

    protected static ?string $pluralModelLabel = 'Activity Log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),

                TextColumn::make('log_name')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auth' => 'danger',
                        'employee' => 'info',
                        'organization' => 'warning',
                        'leave' => 'success',
                        'attendance' => 'primary',
                        'payroll' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('description')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                    ->toggleable(),

                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('causer.name')
                    ->label('By')
                    ->default('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Category')
                    ->options([
                        'auth' => 'Auth',
                        'employee' => 'Employee',
                        'organization' => 'Organization',
                        'leave' => 'Leave',
                        'attendance' => 'Attendance',
                        'payroll' => 'Payroll',
                    ]),

                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),

                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(fn () => Activity::query()
                        ->distinct()
                        ->whereNotNull('subject_type')
                        ->pluck('subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->sort()
                        ->toArray()
                    ),

                SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(fn () => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn ($q, $value) => $q->where('causer_type', User::class)->where('causer_id', $value)
                    ))
                    ->searchable(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
