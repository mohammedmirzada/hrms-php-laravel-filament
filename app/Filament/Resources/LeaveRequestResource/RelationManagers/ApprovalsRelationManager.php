<?php

namespace App\Filament\Resources\LeaveRequestResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('step')
                    ->numeric()
                    ->required(),
                Select::make('role')
                    ->native(false)
                    ->options([
                        'MANAGER' => 'Manager',
                        'HR' => 'HR',
                        'FINAL' => 'Final Approver',
                    ])
                    ->required(),
                Select::make('assigned_to_user_id')
                    ->native(false)
                    ->label('Assigned To')
                    ->relationship('assignedToUser', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->native(false)
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                        'SKIPPED' => 'Skipped',
                    ])
                    ->default('PENDING')
                    ->required(),
                Textarea::make('comment')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('step')
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('assignedToUser.name')
                    ->label('Assigned To'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'SKIPPED' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('actionByUser.name')
                    ->label('Actioned By'),
                TextColumn::make('action_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('comment')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
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
}
