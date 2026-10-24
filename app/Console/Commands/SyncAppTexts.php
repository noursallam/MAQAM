<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Copies the mobile app's built-in wording into the project, so the dashboard can show every
 * text next to the field that replaces it. Run it after adding or changing strings in the app.
 */
class SyncAppTexts extends Command
{
    protected $signature = 'app-texts:sync {path? : folder holding app_ar.arb and app_en.arb}';

    protected $description = 'Export the mobile app\'s translation files to resources/app_texts/defaults.json';

    public function handle(): int
    {
        $dir = rtrim($this->argument('path') ?? base_path('mobile app/lib/l10n'), '/\\');
        $ar = $this->read("{$dir}/app_ar.arb");
        $en = $this->read("{$dir}/app_en.arb");

        if ($ar === null || $en === null) {
            $this->error("app_ar.arb / app_en.arb not found in {$dir}");

            return self::FAILURE;
        }

        $texts = [];
        foreach ($ar as $key => $value) {
            if (str_starts_with($key, '@') || ! is_string($value)) {
                continue;
            }

            preg_match_all('/\{(\w+)\}/', $value.($en[$key] ?? ''), $m);
            $texts[$key] = [
                'ar' => $value,
                'en' => is_string($en[$key] ?? null) ? $en[$key] : '',
                'placeholders' => array_values(array_unique($m[1])),
                // ICU plural/select messages need grammar the dashboard cannot check; they stay in the app
                'plural' => (bool) preg_match('/\{\w+,\s*(plural|select)/', $value),
            ];
        }

        ksort($texts);
        $target = resource_path('app_texts/defaults.json');
        @mkdir(dirname($target), 0755, true);
        file_put_contents($target, json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $this->info(count($texts).' texts written to '.$target);

        return self::SUCCESS;
    }

    private function read(string $path): ?array
    {
        return is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
    }
}
