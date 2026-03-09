<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Filament\Resources\LeaveRequestResource\RelationManagers;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Request Details')
                    ->schema([
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
                        Select::make('leave_type_id')
                            ->native(false)
                            ->label('Leave Type')
                            ->relationship('leaveType', 'name')
                            ->getOptionLabelFromRecordUsing(fn (LeaveType $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('policy_id')
                            ->native(false)
                            ->label('Leave Policy')
                            ->relationship('policy', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Policy #{$record->id} — " . ($record->branch?->getTranslation('name', 'en') ?? ''))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Duration')
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->native(false)
                            ->required(),
                        DateTimePicker::make('end_at')
                            ->native(false)
                            ->required()
                            ->after('start_at'),
                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->nullable(),
                        TextInput::make('duration_days')
                            ->label('Duration (days)')
                            ->numeric()
                            ->nullable(),
                        Select::make('day_part')
                            ->native(false)
                            ->options([
                                'full' => 'Full Day',
                                'first_half' => 'First Half',
                                'second_half' => 'Second Half',
                            ])
                            ->nullable(),
                    ])
                    ->columns(3),

                Section::make('Details')
                    ->schema([
                        Textarea::make('reason')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label('Attachment')
                            ->directory('leave-attachments')
                            ->nullable(),
                        Select::make('status')
                            ->native(false)
                            ->options([
                                'DRAFT' => 'Draft',
                                'SUBMITTED' => 'Submitted',
                                'MANAGER_APPROVED' => 'Manager Approved',
                                'HR_APPROVED' => 'HR Approved',
                                'FINAL_APPROVED' => 'Final Approved',
                                'REJECTED' => 'Rejected',
                                'CANCELLED' => 'Cancelled',
                            ])
                            ->default('DRAFT')
                            ->required(),
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
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) => $record->leaveType?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('start_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Days')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'SUBMITTED' => 'info',
                        'MANAGER_APPROVED' => 'warning',
                        'HR_APPROVED' => 'warning',
                        'FINAL_APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'CANCELLED' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'SUBMITTED' => 'Submitted',
                        'MANAGER_APPROVED' => 'Manager Approved',
                        'HR_APPROVED' => 'HR Approved',
                        'FINAL_APPROVED' => 'Final Approved',
                        'REJECTED' => 'Rejected',
                        'CANCELLED' => 'Cancelled',
                    ]),
                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name'),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
