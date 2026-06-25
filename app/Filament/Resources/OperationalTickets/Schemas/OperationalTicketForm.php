<?php

namespace App\Filament\Resources\OperationalTickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;

class OperationalTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Booking Details')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->label('Client Name'),
                        Select::make('organization_id')
                            ->relationship('organization', 'name')
                            ->label('Organization (optional)'),
                        Select::make('assigned_to_id')
                            ->relationship('assignee', 'name')
                            ->label('Assignee / Staff'),
                        Select::make('service_type')
                            ->options([
                                'tax_prep' => 'Tax Preparation',
                                'bookkeeping' => 'Virtual Bookkeeping',
                                'solar' => 'Home Solar Systems',
                                'small_business' => 'Business Accounts & Logistics',
                                'procurement' => 'Procurement Sourcing',
                            ])
                            ->required()
                            ->live() // makes form update dynamically when value changes
                            ->label('Service Type'),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending Review',
                                'in_progress' => 'In Progress / Processing',
                                'completed' => 'Fulfillment Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        DateTimePicker::make('scheduled_at')
                            ->label('Appointment Date/Time'),
                        Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Unpaid / Pending Deposit',
                                'partial' => 'Partially Paid / Milestones',
                                'paid' => 'Fully Paid',
                            ])
                            ->required()
                            ->default('unpaid'),
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->label('Quoted Price / Invoice Amount'),
                    ]),

                // Dynamic parameters based on the Service Type selected
                Section::make('Tax Preparation Intake Parameters')
                    ->visible(fn ($get) => $get('service_type') === 'tax_prep')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('input_parameters.tax_year')
                                ->label('Tax Year')
                                ->default(date('Y') - 1),
                            Select::make('input_parameters.filing_status')
                                ->options([
                                    'single' => 'Single',
                                    'married_jointly' => 'Married Filing Jointly',
                                    'married_separately' => 'Married Filing Separately',
                                    'head_household' => 'Head of Household',
                                ])
                                ->label('Filing Status'),
                        ]),
                    ]),

                Section::make('Home Solar System Parameters')
                    ->visible(fn ($get) => $get('service_type') === 'solar')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('input_parameters.roof_type')
                                ->label('Roof Material'),
                            TextInput::make('input_parameters.average_monthly_bill')
                                ->numeric()
                                ->prefix('$')
                                ->label('Avg Monthly Electric Bill'),
                            TextInput::make('input_parameters.system_size_kw')
                                ->numeric()
                                ->label('Target System Size (kW)'),
                            TextInput::make('input_parameters.inverter_preference')
                                ->label('Inverter Model Preference')
                                ->columnSpan(3),
                        ]),
                    ]),

                Section::make('Procurement Sourcing Parameters')
                    ->visible(fn ($get) => $get('service_type') === 'procurement')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('input_parameters.item_category')
                                ->options([
                                    'automobile' => 'Automobiles & Vehicles',
                                    'homes' => 'Real Estate & Homes',
                                    'electronics' => 'Electronics & Hardware',
                                    'other' => 'Other Sourcing Assets',
                                ])
                                ->label('Sourcing Category'),
                            TextInput::make('input_parameters.target_item')
                                ->label('Item Model / Description'),
                            TextInput::make('input_parameters.max_budget')
                                ->numeric()
                                ->prefix('$')
                                ->label('Max Client Budget'),
                        ]),
                        KeyValue::make('input_parameters.specifications')
                            ->label('Detailed Specifications & Criteria'),
                    ]),
            ]);
    }
}
