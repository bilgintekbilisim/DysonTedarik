# Dyson Tedarik — yayın paketi oluşturur (Windows, PowerShell).
#
#   powershell -ExecutionPolicy Bypass -File scripts\paket-olustur.ps1
#
# Paket = son commit'teki dosyalar (git archive) + derlenmiş ön yüz dosyaları (public/build).
# .env, vendor, node_modules, yerel yüklemeler ve yedekler pakete GİRMEZ.
# Çıktı: release\dysontedarik-<tarih>-<commit>.zip  →  sunucuya yükleyip: bash deploy.sh paket.zip

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

foreach ($tool in 'git', 'npm') {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { throw "$tool bulunamadı (Laragon terminalinden çalıştırın)." }
}

if (git status --porcelain) {
    Write-Warning 'Commit edilmemiş değişiklikler var; pakete yalnızca son commit girer.'
}

Write-Host '==> Ön yüz derleniyor (npm run build)' -ForegroundColor Cyan
if (-not (Test-Path node_modules)) { npm ci --no-fund --no-audit }
npm run build
if ($LASTEXITCODE -ne 0) { throw 'npm run build başarısız.' }

$commit = (git rev-parse --short HEAD).Trim()
$name = "dysontedarik-$(Get-Date -Format 'yyyyMMdd-HHmm')-$commit.zip"
New-Item -ItemType Directory -Force release | Out-Null
$zip = Join-Path $root "release\$name"

Write-Host "==> Paket: release\$name" -ForegroundColor Cyan
git archive --format=zip --output="$zip" HEAD
if ($LASTEXITCODE -ne 0) { throw 'git archive başarısız.' }

Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::Open($zip, 'Update')
try {
    Get-ChildItem public\build -Recurse -File | ForEach-Object {
        $entry = $_.FullName.Substring($root.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entry, 'Optimal') | Out-Null
    }
} finally {
    $archive.Dispose()
}

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Hazır: release\$name ($size MB)" -ForegroundColor Green
Write-Host 'Sunucuda: bash deploy.sh ~/releases/' $name
