<?php

namespace App\Filament\Resources\EmployerResource\RelationManagers;

use App\Models\Activity;
use App\Models\Document;
use App\Models\Employer;
use App\Models\Leave;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ClipboardDocumentList;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $employer = $this->getOwnerRecord();

                // Include activities on the employer AND on related models
                $query->orWhere(function (Builder $q) use ($employer) {
                    $q->where('subject_type', Document::class)
                        ->whereIn('subject_id', $employer->documents()->pluck('id'));
                })->orWhere(function (Builder $q) use ($employer) {
                    $q->where('subject_type', Leave::class)
                        ->whereIn('subject_id', $employer->leaves()->pluck('id'));
                });
            })
            ->columns([
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
                    }),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('description')
                    ->limit(60),

                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),

                TextColumn::make('causer.name')
                    ->label('By')
                    ->default('-'),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Category')
                    ->options([
                        'employee' => 'Employee',
                        'leave' => 'Leave',
                    ]),
            ]);
    }
}
