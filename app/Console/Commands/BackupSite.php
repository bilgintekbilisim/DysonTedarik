<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Veritabanı dökümü (SQL) ve yüklenen dosyaları (storage/app/public) tek ZIP'e yedekler.
 * mysqldump gerektirmez (paylaşımlı hostinglerde çoğu zaman kapalıdır); PDO ile döküm alır.
 * .env bilerek yedeğe eklenmez (gizli anahtarlar içerir); ayrıca güvenli bir yerde saklanmalıdır.
 */
#[Signature('site:backup {--no-files : Yalnızca veritabanını yedekle} {--keep=14 : Saklanacak yedek sayısı}')]
#[Description('Veritabanı ve yüklenen dosyaların yedeğini storage/app/private/backups altına alır')]
class BackupSite extends Command
{
    public function handle(): int
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir);

        $name = 'yedek-'.now()->format('Ymd-His');
        $sqlFile = "{$dir}/{$name}.sql";
        $zipFile = "{$dir}/{$name}.zip";

        @set_time_limit(0);

        $this->dumpDatabase($sqlFile);

        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Yedek dosyası oluşturulamadı: {$zipFile}");
        }

        $zip->addFile($sqlFile, 'veritabani.sql');
        $files = 0;

        if (! $this->option('no-files')) {
            $base = storage_path('app/public');
            foreach (File::allFiles($base, true) as $file) {
                $zip->addFile($file->getPathname(), 'dosyalar/'.str_replace('\\', '/', $file->getRelativePathname()));
                $files++;
            }
        }

        $zip->setArchiveComment('Dyson Tedarik yedeği '.now()->toDateTimeString());
        $zip->close();
        File::delete($sqlFile);

        $this->prune($dir, max(1, (int) $this->option('keep')));

        $this->info(sprintf('Yedek alındı: %s (%s dosya, %.1f MB)', basename($zipFile), $files, filesize($zipFile) / 1048576));

        return self::SUCCESS;
    }

    private function dumpDatabase(string $target): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            File::copy($connection->getDatabaseName(), $target);

            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Desteklenmeyen veritabanı sürücüsü: {$driver}");
        }

        $pdo = $connection->getPdo();
        $out = fopen($target, 'w');
        fwrite($out, "-- Dyson Tedarik veritabanı yedeği\n-- ".now()->toDateTimeString()."\n\n");
        fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = array_map(fn ($row) => array_values((array) $row)[0], $connection->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'));

        foreach ($tables as $table) {
            $create = (array) $connection->selectOne("SHOW CREATE TABLE `{$table}`");
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n".array_values($create)[1].";\n\n");

            $stmt = $pdo->query("SELECT * FROM `{$table}`", PDO::FETCH_ASSOC);
            $batch = [];

            while ($row = $stmt->fetch()) {
                $batch[] = '('.implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)).')';

                if (count($batch) === 200) {
                    $this->writeInsert($out, $table, $batch);
                    $batch = [];
                }
            }

            $batch && $this->writeInsert($out, $table, $batch);
            fwrite($out, "\n");
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);
    }

    private function writeInsert($out, string $table, array $rows): void
    {
        fwrite($out, "INSERT INTO `{$table}` VALUES\n".implode(",\n", $rows).";\n");
    }

    private function prune(string $dir, int $keep): void
    {
        $backups = collect(File::glob("{$dir}/yedek-*.zip"))->sort()->values();

        $backups->slice(0, max(0, $backups->count() - $keep))->each(fn ($file) => File::delete($file));
    }
}
