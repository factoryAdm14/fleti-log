<?php

namespace App\Console\Commands;

use App\Lib\FletiLegalPagesContent;
use Illuminate\Console\Command;
use Modules\BusinessManagement\Entities\BusinessSetting;

class SeedFletiLegalPagesCommand extends Command
{
    protected $signature = 'fleti:seed-legal-pages
                            {--force : Sobrescrever conteúdo existente}';

    protected $description = 'Preenche as páginas institucionais e jurídicas da Fleti Log (about, privacy, terms, refund, legal)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $updated = 0;
        $skipped = 0;

        foreach (FletiLegalPagesContent::keys() as $key) {
            $page = BusinessSetting::query()
                ->where('key_name', $key)
                ->where('settings_type', PAGES_SETTINGS)
                ->first();

            $existingHtml = trim((string) ($page?->value['long_description'] ?? ''));
            if ($existingHtml !== '' && ! $force) {
                $this->line("Ignorado (já preenchido): {$key}");
                $skipped++;
                continue;
            }

            $value = [
                'name' => $key,
                'short_description' => FletiLegalPagesContent::renderShort($key),
                'long_description' => FletiLegalPagesContent::render($key),
                'image' => $page?->value['image'] ?? '',
            ];

            if ($page) {
                $page->update(['value' => $value]);
            } else {
                BusinessSetting::query()->create([
                    'key_name' => $key,
                    'settings_type' => PAGES_SETTINGS,
                    'value' => $value,
                ]);
            }

            $this->info("Atualizado: {$key}");
            $updated++;
        }

        $this->newLine();
        $this->info("Concluído: {$updated} página(s) atualizada(s), {$skipped} ignorada(s).");

        return self::SUCCESS;
    }
}
