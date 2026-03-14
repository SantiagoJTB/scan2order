<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOrphanProductImages extends Command
{
    protected $signature = 'products:prune-orphan-images';

    protected $description = 'Delete product images from storage that are no longer referenced by any product';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $storedFiles = $disk->allFiles('products');

        $referencedFiles = Product::query()
            ->whereNotNull('image')
            ->pluck('image')
            ->filter(fn ($path) => is_string($path) && str_starts_with($path, 'products/'))
            ->unique()
            ->values()
            ->all();

        $orphanFiles = array_values(array_diff($storedFiles, $referencedFiles));

        if (empty($orphanFiles)) {
            $this->info('No orphan product images found.');
            return self::SUCCESS;
        }

        $deletedCount = 0;

        foreach ($orphanFiles as $file) {
            if ($disk->delete($file)) {
                $deletedCount++;
                $this->line("Deleted: {$file}");
            }
        }

        $this->info("Orphan product images deleted: {$deletedCount}");

        return self::SUCCESS;
    }
}
