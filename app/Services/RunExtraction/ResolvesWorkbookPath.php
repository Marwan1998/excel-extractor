<?php

namespace App\Services\RunExtraction;

trait ResolvesWorkbookPath
{
    protected function resolveWorkbookPathName(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new \RuntimeException('The workbook path is empty.');
        }

        if (preg_match('~^([a-zA-Z]):[\\\\/](.*)$~', $path, $matches)) {
            $drive = strtolower($matches[1]);
            $remainder = str_replace('\\', '/', $matches[2]);

            return '/mnt/'.$drive.'/'.ltrim($remainder, '/');
        }

        if ($path[0] === '/') {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);

        if (strpos($normalized, 'storage/') === 0) {
            return base_path($normalized);
        }

        return storage_path($normalized);
    }
}
