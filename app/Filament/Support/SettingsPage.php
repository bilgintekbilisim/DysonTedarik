<?php

namespace App\Filament\Support;

use App\Services\SettingsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

/**
 * settings tablosuna yazan form sayfaları için ortak taban.
 * Alt sınıf yalnızca form alanlarını ve hangi grupları kaydettiğini tanımlar.
 *
 * @property-read Schema $form
 */
abstract class SettingsPage extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Bu sayfanın yazmasına izin verilen ayar grupları (ör. ["contact", "social"]). */
    abstract protected function groups(): array;

    abstract protected function fields(): array;

    public function mount(): void
    {
        $this->form->fill(app(SettingsService::class)->nested());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->fields())
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Kaydet')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ])->key('form-actions'),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();

        // Formda tanımlı olmayan bir grup Livewire üzerinden gönderilse bile yazılmaz.
        $allowed = array_intersect_key($state, array_flip($this->groups()));

        app(SettingsService::class)->setMany($allowed);

        Notification::make()->success()->title('Ayarlar kaydedildi')->send();
    }
}
