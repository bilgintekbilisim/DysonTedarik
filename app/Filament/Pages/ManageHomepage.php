<?php

namespace App\Filament\Pages;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Filament\Support\Fields;
use App\Filament\Support\PageBlocks;
use App\Models\Page as PageModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use UnitEnum;

/**
 * Ana sayfa blokları: düzenlemeler önce taslağa (draft_blocks) kaydedilir,
 * "Yayınla" ile ziyaretçilere açılır (blocks).
 *
 * @property-read Schema $form
 */
class ManageHomepage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Ana Sayfa Yönetimi';

    protected static ?string $title = 'Ana Sayfa Yönetimi';

    protected static ?string $slug = 'ana-sayfa';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public PageModel $record;

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', PageModel::class);
    }

    public function mount(): void
    {
        $this->record = PageModel::query()->firstOrCreate(
            ['type' => PageType::Home],
            ['title' => 'Ana Sayfa', 'slug' => 'ana-sayfa', 'status' => ContentStatus::Published, 'blocks' => [], 'draft_blocks' => []],
        );

        $this->form->fill([
            'draft_blocks' => $this->record->draft_blocks ?? $this->record->blocks ?? [],
            ...$this->record->only(['meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex']),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->record)
            ->statePath('data')
            ->components([
                Callout::make('Yayınlanmamış değişiklikler var')
                    ->description('Taslaktaki bloklar henüz ziyaretçilere gösterilmiyor. Kontrol edip "Yayınla" butonunu kullanın.')
                    ->warning()
                    ->visible(fn () => $this->record->hasUnpublishedChanges()),
                Section::make('Sayfa blokları')
                    ->description('Blokları ekleyin, sürükleyerek sıralayın; her bloğun "Görünüm ayarları" ile aktiflik, cihaz ve tasarım varyantını seçin.')
                    ->schema([PageBlocks::builder('draft_blocks', home: true)]),
                Fields::seoSection('pages/seo'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('saveDraft')
            ->footer([
                Actions::make([
                    Action::make('saveDraft')->label('Taslağı kaydet')->submit('saveDraft')->keyBindings(['mod+s']),
                    Action::make('publish')
                        ->label('Kaydet ve yayınla')
                        ->color('success')
                        ->icon('heroicon-o-rocket-launch')
                        ->requiresConfirmation()
                        ->modalDescription('Taslaktaki bloklar ana sayfada hemen yayınlanacak.')
                        ->action(fn () => $this->publish()),
                ])->key('form-actions'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Taslağı önizle')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => Route::has('home') ? route('home', ['onizleme' => 1]) : null)
                ->openUrlInNewTab()
                ->visible(fn () => Route::has('home')),
            Action::make('discardDraft')
                ->label('Taslağı geri al')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Kaydedilmiş taslak silinir ve yayındaki bloklara dönülür.')
                ->visible(fn () => $this->record->hasUnpublishedChanges())
                ->action(function () {
                    $this->record->update(['draft_blocks' => $this->record->blocks]);
                    $this->mount();
                    Notification::make()->success()->title('Taslak yayındaki sürüme döndürüldü')->send();
                }),
        ];
    }

    public function saveDraft(): void
    {
        $this->persist(publish: false);

        Notification::make()->success()->title('Taslak kaydedildi')->body('Değişiklikler henüz yayında değil.')->send();
    }

    public function publish(): void
    {
        $this->persist(publish: true);

        Notification::make()->success()->title('Ana sayfa yayınlandı')->send();
    }

    private function persist(bool $publish): void
    {
        abort_unless(Gate::allows('update', $this->record), 403);

        $state = $this->form->getState();

        $this->record->fill([
            'draft_blocks' => array_values($state['draft_blocks'] ?? []),
            ...collect($state)->only(['meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex'])->all(),
        ])->save();

        if ($publish) {
            $this->record->publishDraft();
        }

        $this->record->refresh();
    }
}
