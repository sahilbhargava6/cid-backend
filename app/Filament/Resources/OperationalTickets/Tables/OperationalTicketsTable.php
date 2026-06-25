<?php

namespace App\Filament\Resources\OperationalTickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OperationalTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_type')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tax_prep' => 'Tax Prep',
                        'bookkeeping' => 'Bookkeeping',
                        'solar' => 'Solar',
                        'small_business' => 'Logistics/Accts',
                        'procurement' => 'Procurement',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'tax_prep' => 'info',
                        'bookkeeping' => 'gray',
                        'solar' => 'warning',
                        'small_business' => 'primary',
                        'procurement' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Scheduled Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->options([
                        'tax_prep' => 'Tax Preparation',
                        'bookkeeping' => 'Virtual Bookkeeping',
                        'solar' => 'Home Solar Systems',
                        'small_business' => 'Business Accounts & Logistics',
                        'procurement' => 'Procurement Sourcing',
                    ])
                    ->label('Service Type'),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partially Paid',
                        'paid' => 'Fully Paid',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
