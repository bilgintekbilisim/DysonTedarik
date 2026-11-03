# Dyson Tedarik — yayın paketi oluşturur (Windows, PowerShell, Laragon).
#
#   powershell -ExecutionPolicy Bypass -File scripts\paket-olustur.ps1
#
# Hostingde Composer çalışmadığı (proc_open kapalı) için paket ÇALIŞMAYA HAZIRDIR:
#   - son commit'teki dosyalar (git archive; .env, testler ve betikler hariç),
#   - production bağımlılıkları (vendor/, composer.lock'tan, PHP 8.3.36 platformuyla),
#   - derlenmiş ön yüz dosyaları (public/build).
# Çıktı: release\dysontedarik-<tarih>-<commit>.zip  →  sunucuda: bash deploy.sh ~/releases/<paket>.zip

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $php) { $php = 'C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe' }
$composerPhar = 'C:\laragon\bin\composer\composer.phar'
foreach ($tool in 'git', 'npm') {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { throw "$tool bulunamadı (Laragon terminalinden çalıştırın)." }
}
if (-not (Test-Path $php) -or -not (Test-Path $composerPhar)) { throw "PHP veya Composer bulunamadı: $php / $composerPhar" }

$phpVersion = (& $php -r 'echo PHP_VERSION;')
if (-not $phpVersion.StartsWith('8.3.')) { throw "Paket PHP 8.3 ile hazırlanmalı (sunucu: 8.3.36). Bulunan: $phpVersion" }

if (git status --porcelain) {
    Write-Warning 'Commit edilmemiş değişiklikler var; pakete yalnızca son commit girer.'
}

Write-Host '==> Ön yüz derleniyor (npm run build)' -ForegroundColor Cyan
if (-not (Test-Path node_modules)) { npm ci --no-fund --no-audit }
npm run build
if ($LASTEXITCODE -ne 0) { throw 'npm run build başarısız.' }

$commit = (git rev-parse --short HEAD).Trim()
$name = "dysontedarik-$(Get-Date -Format 'yyyyMMdd-HHmm')-$commit.zip"
$work = Join-Path $env:TEMP "dt-paket-$commit-$(Get-Random)"
$app = Join-Path $work 'app'
New-Item -ItemType Directory -Force $app | Out-Null
# TEMP kısa adla (ör. BILGIN~1) gelebilir; dosya yolları uzun adla döndüğü için gerçek yola çevrilir.
$work = (Get-Item $work).FullName
$app = (Get-Item $app).FullName

try {
    Write-Host '==> Son commit temiz klasöre açılıyor' -ForegroundColor Cyan
    git archive --format=zip --output="$work\kaynak.zip" HEAD
    if ($LASTEXITCODE -ne 0) { throw 'git archive başarısız.' }
    Expand-Archive "$work\kaynak.zip" $app

    Write-Host '==> Production bağımlılıkları kuruluyor (composer install --no-dev)' -ForegroundColor Cyan
    Push-Location $app
    # Betikler sunucuda çalıştırılır (package:discover, filament:assets); burada yalnızca vendor hazırlanır.
    & $php $composerPhar install --no-dev --optimize-autoloader --no-scripts --no-interaction --no-progress --prefer-dist
    $code = $LASTEXITCODE
    & $php $composerPhar check-platform-reqs --no-dev --lock | Out-Null
    Pop-Location
    if ($code -ne 0) { throw 'composer install başarısız.' }

    Copy-Item public\build (Join-Path $app 'public\build') -Recurse

    Write-Host "==> Paket: release\$name" -ForegroundColor Cyan
    New-Item -ItemType Directory -Force release | Out-Null
    $zip = Join-Path $root "release\$name"
    if (Test-Path $zip) { Remove-Item $zip }
    Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
    $archive = [System.IO.Compression.ZipFile]::Open($zip, 'Create')
    try {
        # Giriş adları Linux'ta açılabilmesi için "/" ile yazılır.
        Get-ChildItem $app -Recurse -File -Force | ForEach-Object {
            $entry = $_.FullName.Substring($app.Length + 1).Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entry, 'Optimal') | Out-Null
        }
    } finally {
        $archive.Dispose()
    }
} finally {
    Remove-Item $work -Recurse -Force -ErrorAction SilentlyContinue
}

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Hazır: release\$name ($size MB)" -ForegroundColor Green
Write-Host "Sunucuda: bash deploy.sh ~/releases/$name"
