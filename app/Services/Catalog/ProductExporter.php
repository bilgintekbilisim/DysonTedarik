<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Ürünleri ProductSheet sözleşmesine uygun Excel/CSV dosyasına yazar.
 * Dosya geçici dizine yazılır; çağıran indirme sonrası siler.
 */
class ProductExporter
{
    /** @var array<int, string> kategori id => "Ana > Alt" yolu */
    private array $paths = [];

    public function export(Builder $query, string $format): string
    {
        $this->paths = $this->categoryPaths();

        $path = $this->tempPath($format);
        $writer = $this->writer($format);
        $writer->openToFile($path);

        $this->writeHeader($writer, $format);

        $query->with(['brand', 'categories', 'attributeValues.attribute'])
            ->orderBy('id')
            ->chunk(500, function ($products) use ($writer) {
                foreach ($products as $product) {
                    $writer->addRow(Row::fromValues($this->row($product)));
                }
            });

        $writer->close();

        return $path;
    }

    /** Yalnızca başlık satırı ve (Excel'de) sütun açıklamaları içeren boş şablon. */
    public function template(string $format): string
    {
        $path = $this->tempPath($format);
        $writer = $this->writer($format);
        $writer->openToFile($path);
        $this->writeHeader($writer, $format);

        if ($writer instanceof XlsxWriter) {
            $writer->addNewSheetAndMakeItCurrent()->setName('Açıklamalar');
            $writer->addRow(Row::fromValues(['Sütun', 'Açıklama'], (new Style)->setFontBold()));

            foreach (ProductSheet::COLUMNS as $column => $description) {
                $writer->addRow(Row::fromValues([$column, $description]));
            }

            $writer->addRow(Row::fromValues(['', 'Boş bırakılan hücreler mevcut ürünlerde değiştirilmez. Görseller ve PDF\'ler panelden yüklenir.']));
        }

        $writer->close();

        return $path;
    }

    private function writeHeader(WriterInterface $writer, string $format): void
    {
        if ($writer instanceof XlsxWriter) {
            $writer->getCurrentSheet()->setName('Ürünler');
        }

        $writer->addRow(Row::fromValues(array_keys(ProductSheet::COLUMNS), (new Style)->setFontBold()));
    }

    private function row(Product $product): array
    {
        return [
            $product->code,
            $product->name,
            $product->brand?->name ?? '',
            $this->paths[$product->category_id] ?? '',
            $product->categories->map(fn (Category $c) => $this->paths[$c->id] ?? $c->name)->implode(' '.ProductSheet::LIST_SEPARATOR.' '),
            (string) $product->short_description,
            (string) $product->description,
            (string) $product->usage_areas,
            ProductSheet::formatSpecs($product->technical_specs),
            (string) $product->color,
            (string) $product->volume,
            (string) $product->packaging,
            $product->attributeValues
                ->map(fn ($value) => $value->attribute->name.'='.$value->value)
                ->implode(' '.ProductSheet::LIST_SEPARATOR.' '),
            ProductSheet::formatBool($product->is_active),
            ProductSheet::formatBool($product->is_featured),
            (string) $product->sort_order,
            (string) $product->meta_title,
            (string) $product->meta_description,
        ];
    }

    private function categoryPaths(): array
    {
        $categories = Category::withTrashed()->get(['id', 'parent_id', 'name'])->keyBy('id');

        return $categories->map(function (Category $category) use ($categories) {
            $names = [$category->name];
            $current = $category;

            while ($current->parent_id && $categories->has($current->parent_id) && count($names) < 10) {
                $current = $categories[$current->parent_id];
                array_unshift($names, $current->name);
            }

            return implode(' '.ProductSheet::PATH_SEPARATOR.' ', $names);
        })->all();
    }

    private function writer(string $format): WriterInterface
    {
        return match ($format) {
            'xlsx' => new XlsxWriter,
            'csv' => new CsvWriter(tap(new CsvOptions, fn (CsvOptions $o) => $o->FIELD_DELIMITER = ';')),
            default => throw new InvalidArgumentException('Desteklenmeyen biçim.'),
        };
    }

    private function tempPath(string $format): string
    {
        $dir = storage_path('app/private/exports');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'/urunler-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.'.$format;
    }
}
