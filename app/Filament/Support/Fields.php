<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Panel genelinde tekrar eden form alanları. Güvenlik kuralları (MIME, boyut, bağlantı şeması)
 * tek yerde tanımlanır; böylece her modül aynı sınırlara uyar.
 */
class Fields
{
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const IMAGE_MAX_KB = 5120;

    public const PDF_MAX_KB = 20480;

    /** Yalnızca site içi yol (/...), http(s), tel:, mailto: veya #çapa kabul eder; javascript: vb. reddedilir. */
    public const LINK_REGEX = '/^(\/(?!\/)|https?:\/\/|tel:|mailto:|#)/i';

    public static function image(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(self::IMAGE_TYPES)
            ->maxSize(self::IMAGE_MAX_KB)
            ->imageEditor()
            ->helperText('JPG, PNG veya WEBP — en fazla 5 MB.');
    }

    public static function pdf(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize(self::PDF_MAX_KB)
            ->helperText('Yalnızca PDF — en fazla 20 MB.');
    }

    public static function richText(string $name, string $directory = 'editor'): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                ['h2', 'h3'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['table', 'attachFiles'],
                ['undo', 'redo'],
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory($directory)
            ->fileAttachmentsVisibility('public')
            ->linkProtocols(['http', 'https', 'mailto', 'tel']);
    }

    public static function link(string $name, string $label = 'Bağlantı'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(500)
            ->regex(self::LINK_REGEX)
            ->validationMessages(['regex' => 'Bağlantı "/" ile başlayan site içi bir yol veya https:// adresi olmalıdır.'])
            ->placeholder('/urunler veya https://...');
    }

    /** Başlık alanı yazılırken boş slug alanını otomatik doldurur. */
    public static function titleWithSlug(string $title = 'title', string $label = 'Başlık'): TextInput
    {
        return TextInput::make($title)
            ->label($label)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                if ($operation === 'create' || blank($get('slug'))) {
                    $set('slug', Str::slug((string) $state, '-', 'tr'));
                }
            });
    }

    public static function slug(): TextInput
    {
        return TextInput::make('slug')
            ->label('URL (slug)')
            ->maxLength(255)
            ->alphaDash()
            ->unique(ignoreRecord: true)
            ->helperText('Boş bırakılırsa başlıktan üretilir. Yayındaki bir sayfanın URL\'si değişirse eski adres için yönlendirme ekleyin.');
    }

    public static function seoSection(string $directory = 'seo'): Section
    {
        return Section::make('SEO')
            ->description('Boş bırakılan alanlar içerikten otomatik üretilir.')
            ->icon('heroicon-o-magnifying-glass')
            ->collapsible()
            ->collapsed()
            ->schema([
                TextInput::make('meta_title')
                    ->label('Meta başlık')
                    ->maxLength(70)
                    ->helperText('Önerilen: 50–60 karakter.'),
                Textarea::make('meta_description')
                    ->label('Meta açıklama')
                    ->rows(3)
                    ->maxLength(320)
                    ->helperText('Önerilen: 120–160 karakter.'),
                self::image('og_image', $directory)
                    ->label('Open Graph görseli')
                    ->helperText('Sosyal medyada paylaşım görseli. Önerilen 1200×630 px.'),
                TextInput::make('canonical_url')
                    ->label('Canonical URL')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Yalnızca içerik başka bir adreste de yayınlanıyorsa doldurun.'),
                Toggle::make('noindex')
                    ->label('Arama motorlarında gösterme (noindex)'),
            ]);
    }
}
