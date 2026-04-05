<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    private function backupDir(): string
    {
        return storage_path('app/backups');
    }

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    private function findBinary(string $name): string
    {
        // Linux/XAMPP
        if (!$this->isWindows()) {
            $xamppPath = "/opt/lampp/bin/{$name}";
            return file_exists($xamppPath) ? $xamppPath : $name;
        }

        // Windows: search common Laragon MySQL paths
        $laragonBase = 'C:\\laragon\\bin\\mysql';
        if (is_dir($laragonBase)) {
            $dirs = glob($laragonBase . '\\mysql-*', GLOB_ONLYDIR)
                 ?: glob($laragonBase . '\\*', GLOB_ONLYDIR);
            if ($dirs) {
                // Use the latest version folder
                rsort($dirs);
                $bin = $dirs[0] . "\\bin\\{$name}.exe";
                if (file_exists($bin)) {
                    return $bin;
                }
            }
        }

        // Fallback: rely on system PATH (must be added manually by user)
        return $name;
    }

    private function mysqldump(): string
    {
        return $this->findBinary('mysqldump');
    }

    private function mysql(): string
    {
        return $this->findBinary('mysql');
    }

    private function dbArgs(): array
    {
        return [
            'host'     => config('database.connections.mysql.host', '127.0.0.1'),
            'port'     => config('database.connections.mysql.port', '3306'),
            'username' => config('database.connections.mysql.username', 'root'),
            'password' => config('database.connections.mysql.password', ''),
            'database' => config('database.connections.mysql.database'),
        ];
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) return round($bytes, 1) . ' ' . $unit;
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' TB';
    }

    private function databaseSize(): string
    {
        try {
            $db   = config('database.connections.mysql.database');
            $size = \DB::select("SELECT SUM(data_length + index_length) as size
                                 FROM information_schema.tables
                                 WHERE table_schema = ?", [$db]);
            return $this->humanSize((int) ($size[0]->size ?? 0));
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    // ── GET /admin/backup ─────────────────────────────────

    public function index(): Response
    {
        $dir = $this->backupDir();
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $backups = collect(File::files($dir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sql'))
            ->map(fn($f) => [
                'filename'   => $f->getFilename(),
                'size'       => $f->getSize(),
                'size_human' => $this->humanSize($f->getSize()),
                'created_at' => date('Y-m-d H:i:s', $f->getMTime()),
            ])
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('Admin/Backup', [
            'backups'  => $backups,
            'db_size'  => $this->databaseSize(),
            'count'    => $backups->count(),
            'last_backup' => $backups->first() ? $backups->first()['created_at'] : null,
        ]);
    }

    // ── POST /admin/backup ────────────────────────────────

    public function create(): RedirectResponse
    {
        $dir = $this->backupDir();
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filename = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $path     = $dir . '/' . $filename;
        $db       = $this->dbArgs();

        $passArg = $db['password'] !== '' ? '-p' . $db['password'] : '';
        $dump    = $this->mysqldump();
        $outPath = $this->isWindows() ? str_replace('/', '\\', $path) : $path;

        if ($this->isWindows()) {
            $cmd = sprintf(
                'cmd /c ""%s" --host=%s --port=%s -u%s %s %s > "%s" 2>&1"',
                addslashes($dump),
                $db['host'],
                $db['port'],
                $db['username'],
                $passArg !== '' ? '-p' . $db['password'] : '',
                $db['database'],
                addslashes($outPath)
            );
        } else {
            $cmd = sprintf(
                '%s --host=%s --port=%s -u%s %s %s > %s 2>&1',
                escapeshellarg($dump),
                escapeshellarg($db['host']),
                escapeshellarg($db['port']),
                escapeshellarg($db['username']),
                $passArg !== '' ? '-p' . escapeshellarg($db['password']) : '',
                escapeshellarg($db['database']),
                escapeshellarg($outPath)
            );
        }

        exec($cmd, $output, $code);

        if ($code !== 0 || !File::exists($path) || File::size($path) === 0) {
            File::delete($path);
            return back()->with('error', 'Backup failed. ' . implode(' ', $output));
        }

        activity()->log("Database backup created: {$filename}");
        return back()->with('success', "Backup created: {$filename}");
    }

    // ── GET /admin/backup/{filename}/download ─────────────

    public function download(string $filename)
    {
        $path = $this->backupDir() . '/' . basename($filename);

        if (!File::exists($path)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    // ── POST /admin/backup/{filename}/restore ─────────────

    public function restore(string $filename): RedirectResponse
    {
        $path = $this->backupDir() . '/' . basename($filename);

        if (!File::exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        $db      = $this->dbArgs();
        $mysql   = $this->mysql();
        $inPath  = $this->isWindows() ? str_replace('/', '\\', $path) : $path;

        if ($this->isWindows()) {
            $cmd = sprintf(
                'cmd /c ""%s" --host=%s --port=%s -u%s %s %s < "%s" 2>&1"',
                addslashes($mysql),
                $db['host'],
                $db['port'],
                $db['username'],
                $db['password'] !== '' ? '-p' . $db['password'] : '',
                $db['database'],
                addslashes($inPath)
            );
        } else {
            $cmd = sprintf(
                '%s --host=%s --port=%s -u%s %s %s < %s 2>&1',
                escapeshellarg($mysql),
                escapeshellarg($db['host']),
                escapeshellarg($db['port']),
                escapeshellarg($db['username']),
                $db['password'] !== '' ? '-p' . escapeshellarg($db['password']) : '',
                escapeshellarg($db['database']),
                escapeshellarg($inPath)
            );
        }

        exec($cmd, $output, $code);

        if ($code !== 0) {
            return back()->with('error', 'Restore failed. ' . implode(' ', $output));
        }

        activity()->log("Database restored from backup: {$filename}");
        return back()->with('success', "Database successfully restored from: {$filename}");
    }

    // ── DELETE /admin/backup/{filename} ───────────────────

    public function destroy(string $filename): RedirectResponse
    {
        $path = $this->backupDir() . '/' . basename($filename);

        if (File::exists($path)) {
            File::delete($path);
            activity()->log("Backup deleted: {$filename}");
        }

        return back()->with('success', 'Backup file deleted.');
    }
}
