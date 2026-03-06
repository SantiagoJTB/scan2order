<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

if (! function_exists('saveFallbackData')) {
    /**
     * Save data to JSON fallback file when database write fails.
     *
     * @param array $data
     * @return void
     */
    function saveFallbackData(array $data)
    {
        $path = 'db_fallback.json';

        try {
            $existing = [];
            if (Storage::exists($path)) {
                $contents = Storage::get($path);
                $existing = json_decode($contents, true) ?: [];
            }

            $existing[] = $data;
            Storage::put($path, json_encode($existing, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::channel('db_errors')->error('Failed to save fallback data', ['exception' => $e, 'data' => $data]);
        }
    }
}
