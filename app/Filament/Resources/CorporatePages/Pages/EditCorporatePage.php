<?php

namespace App\Filament\Resources\CorporatePages\Pages;

use App\Filament\Resources\CorporatePages\CorporatePageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCorporatePage extends EditRecord
{
    protected static string $resource = CorporatePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
