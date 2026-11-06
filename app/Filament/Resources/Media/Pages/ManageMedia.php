<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Services\MediaLibrary;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageMedia extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    /** Ürün, marka, slayt, katalog vb. yerlerden yüklenen dosyalar da listede görünsün. */
    public function mount(): void
    {
        parent::mount();
        app(MediaLibrary::class)->sync();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Klasörü yeniden tara')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function () {
                    ['added' => $added, 'removed' => $removed] = app(MediaLibrary::class)->sync();
                    Notification::make()->success()->title("{$added} dosya eklendi, {$removed} kayıp dosya kaldırıldı.")->send();
                }),
            CreateAction::make()->label('Dosya yükle'),
        ];
    }
}
