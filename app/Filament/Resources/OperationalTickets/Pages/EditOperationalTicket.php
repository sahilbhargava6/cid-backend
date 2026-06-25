<?php

namespace App\Filament\Resources\OperationalTickets\Pages;

use App\Filament\Resources\OperationalTickets\OperationalTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOperationalTicket extends EditRecord
{
    protected static string $resource = OperationalTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
