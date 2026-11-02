<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductExporter;
use App\Services\Catalog\ProductImporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                $this->importAction(),
                $this->exportAction(),
                Action::make('template')
                    ->label('Boş şablon indir (Excel)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn () => response()->download(app(ProductExporter::class)->template('xlsx'), 'urun-aktarim-sablonu.xlsx')->deleteFileAfterSend()),
            ])
                ->label('Excel / CSV')
                ->icon('heroicon-o-table-cells')
                ->button()
                ->color('gray'),
        ];
    }

    private function importAction(): Action
    {
        return Action::make('import')
            ->label('İçe aktar')
            ->icon('heroicon-o-arrow-up-tray')
            ->visible(fn () => Gate::allows('create', Product::class))
            ->modalHeading('Ürünleri içe aktar')
            ->modalDescription('Ürünler "kod" sütunuyla eşleştirilir: kod varsa güncellenir, yoksa yeni ürün oluşturulur. Boş hücreler mevcut değerleri değiştirmez. En fazla 5.000 satır.')
            ->modalSubmitActionLabel('Aktarımı başlat')
            ->schema([
                FileUpload::make('file')
                    ->label('Dosya (.xlsx veya .csv)')
                    ->disk('local')
                    ->directory('imports')
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel',
                    ])
                    ->maxSize(10240)
                    ->required(),
                Toggle::make('create_missing')
                    ->label('Eksik marka, kategori ve özellikleri oluştur')
                    ->helperText('Kapalıysa bulunamayan marka/kategori içeren satırlar hata olarak raporlanır.')
                    ->default(true),
            ])
            ->action(function (array $data) {
                $disk = Storage::disk('local');
                $path = $disk->path($data['file']);

                try {
                    $result = app(ProductImporter::class)->import($path, pathinfo($path, PATHINFO_EXTENSION), (bool) $data['create_missing']);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title('Dosya okunamadı')->body($e->getMessage())->persistent()->send();

                    return;
                } finally {
                    $disk->delete($data['file']);
                }

                $errors = collect($result->errors)
                    ->take(10)
                    ->map(fn (string $message, int $row) => "Satır {$row}: {$message}")
                    ->implode("\n");

                Notification::make()
                    ->title($result->failed() ? 'Aktarım tamamlandı, hatalı satırlar var' : 'Aktarım tamamlandı')
                    ->body($result->summary().($errors ? "\n\n".$errors.($result->failed() > 10 ? "\n… ve ".($result->failed() - 10).' satır daha.' : '') : ''))
                    ->status($result->failed() ? 'warning' : 'success')
                    ->persistent()
                    ->send();
            });
    }

    private function exportAction(): Action
    {
        return Action::make('export')
            ->label('Dışa aktar')
            ->icon('heroicon-o-arrow-down-tray')
            ->modalHeading('Ürünleri dışa aktar')
            ->modalDescription('Çöp kutusundakiler hariç tüm ürünler aktarılır. Dosya düzenlenip geri içe aktarılabilir.')
            ->modalSubmitActionLabel('İndir')
            ->schema([
                Select::make('format')
                    ->label('Biçim')
                    ->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (; ayraçlı, Excel uyumlu)'])
                    ->default('xlsx')
                    ->selectablePlaceholder(false),
            ])
            ->action(function (array $data) {
                $format = $data['format'] === 'csv' ? 'csv' : 'xlsx';
                $path = app(ProductExporter::class)->export(Product::query(), $format);

                return response()->download($path, 'urunler-'.now()->format('Y-m-d').'.'.$format)->deleteFileAfterSend();
            });
    }
}
