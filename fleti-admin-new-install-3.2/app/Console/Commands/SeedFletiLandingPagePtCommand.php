<?php

namespace App\Console\Commands;

use App\Lib\FletiLandingPagePtContent;
use Illuminate\Console\Command;
use Modules\BusinessManagement\Entities\LandingPageSection;

class SeedFletiLandingPagePtCommand extends Command
{
    protected $signature = 'fleti:seed-landing-pt
                            {--force : Sobrescrever textos existentes}';

    protected $description = 'Traduz e preenche os textos da página inicial (landing) da Fleti Log em português';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $updated = 0;

        $updated += $this->mergeSection(INTRO_SECTION, INTRO_CONTENTS, FletiLandingPagePtContent::intro(), $force);
        $updated += $this->mergeBusinessStatistics($force);
        $updated += $this->mergeSection(OUR_SOLUTIONS_SECTION, INTRO_CONTENTS, FletiLandingPagePtContent::ourSolutionsIntro(), $force);
        $updated += $this->mergeSolutions($force);
        $updated += $this->mergeSection(OUR_SERVICES, INTRO_CONTENTS, FletiLandingPagePtContent::ourServicesIntro(), $force);
        $updated += $this->mergeOurServices($force);
        $updated += $this->mergeGallery($force);
        $updated += $this->mergeSection(EARN_MONEY, INTRO_CONTENTS, FletiLandingPagePtContent::earnMoney(), $force);
        $updated += $this->mergeSection(EARN_MONEY, BUTTON_CONTENTS, FletiLandingPagePtContent::earnMoneyButtons(), $force);
        $updated += $this->mergeSection(CUSTOMER_APP_DOWNLOAD, INTRO_CONTENTS, FletiLandingPagePtContent::customerAppDownload(), $force);
        $updated += $this->mergeSection(CUSTOMER_APP_DOWNLOAD, BUTTON_CONTENTS, FletiLandingPagePtContent::customerAppDownloadButtons(), $force);
        $updated += $this->mergeSection(TESTIMONIAL, INTRO_CONTENTS, FletiLandingPagePtContent::testimonialIntro(), $force);
        $updated += $this->mergeTestimonials($force);
        $updated += $this->mergeNewsletter($force);
        $updated += $this->mergeFooter($force);

        $this->info("Concluído: {$updated} seção(ões) atualizada(s).");

        return self::SUCCESS;
    }

    private function mergeSection(string $settingsType, string $keyName, array $texts, bool $force): int
    {
        $section = LandingPageSection::query()
            ->where('settings_type', $settingsType)
            ->where('key_name', $keyName)
            ->first();

        if (!$section) {
            $this->line("Ignorado (não existe): {$settingsType}/{$keyName}");
            return 0;
        }

        $value = is_array($section->value) ? $section->value : [];
        if (!$force && $this->hasMeaningfulText($value)) {
            $this->line("Ignorado (já preenchido): {$settingsType}/{$keyName}");
            return 0;
        }

        $section->update(['value' => array_merge($value, $texts)]);
        $this->info("Atualizado: {$settingsType}/{$keyName}");
        return 1;
    }

    private function mergeBusinessStatistics(bool $force): int
    {
        $count = 0;
        foreach (FletiLandingPagePtContent::businessStatistics() as $keyName => $texts) {
            $section = LandingPageSection::query()
                ->where('settings_type', BUSINESS_STATISTICS)
                ->where('key_name', $keyName)
                ->first();

            if (!$section) {
                continue;
            }

            $value = is_array($section->value) ? $section->value : [];
            if (!$force && !empty($value['content']) && !$this->looksEnglish($value['content'])) {
                continue;
            }

            $section->update([
                'value' => array_merge($value, [
                    'title' => $texts['title'],
                    'content' => $texts['content'],
                    'status' => $value['status'] ?? 1,
                ]),
            ]);
            $this->info("Atualizado: business_statistics/{$keyName}");
            $count++;
        }

        return $count;
    }

    private function mergeSolutions(bool $force): int
    {
        $items = FletiLandingPagePtContent::ourSolutionsItems();
        $sections = LandingPageSection::query()
            ->where('settings_type', OUR_SOLUTIONS_SECTION)
            ->where('key_name', SOLUTIONS)
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($sections as $index => $section) {
            $texts = $items[$index] ?? null;
            if (!$texts) {
                break;
            }

            $value = is_array($section->value) ? $section->value : [];
            if (!$force && !empty($value['title']) && !$this->looksEnglish($value['title'])) {
                continue;
            }

            $section->update([
                'value' => array_merge($value, [
                    'title' => $texts['title'],
                    'description' => $texts['description'],
                    'status' => $value['status'] ?? 1,
                ]),
            ]);
            $this->info('Atualizado: our_solutions/solutions #' . ($index + 1));
            $count++;
        }

        return $count;
    }

    private function mergeOurServices(bool $force): int
    {
        $items = FletiLandingPagePtContent::ourServicesItems();
        $sections = LandingPageSection::query()
            ->where('settings_type', OUR_SERVICES)
            ->whereIn('key_name', ['service_1', 'service_2'])
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($sections as $index => $section) {
            $texts = $items[$index] ?? null;
            if (!$texts) {
                break;
            }

            $value = is_array($section->value) ? $section->value : [];
            if (!$force && !empty($value['tab_name']) && !$this->looksEnglish($value['tab_name'])) {
                continue;
            }

            $section->update([
                'value' => array_merge($value, [
                    'tab_name' => $texts['tab_name'],
                    'title' => $texts['title'],
                    'description' => $texts['description'],
                    'status' => $value['status'] ?? 1,
                ]),
            ]);
            $this->info('Atualizado: our_services/' . $section->key_name);
            $count++;
        }

        return $count;
    }

    private function mergeTestimonials(bool $force): int
    {
        $items = FletiLandingPagePtContent::testimonials();
        $sections = LandingPageSection::query()
            ->where('settings_type', TESTIMONIAL)
            ->where('key_name', 'reviews')
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($sections as $index => $section) {
            $texts = $items[$index] ?? null;
            if (!$texts) {
                break;
            }

            $value = is_array($section->value) ? $section->value : [];
            if (!$force && !empty($value['review']) && !$this->looksEnglish($value['review'])) {
                continue;
            }

            $section->update([
                'value' => array_merge($value, [
                    'reviewer_name' => $texts['reviewer_name'],
                    'designation' => $texts['designation'],
                    'review' => $texts['review'],
                    'rating' => $texts['rating'],
                    'status' => $value['status'] ?? '1',
                ]),
            ]);
            $this->info('Atualizado: testimonial/reviews #' . ($index + 1));
            $count++;
        }

        return $count;
    }

    private function mergeGallery(bool $force): int
    {
        $count = 0;
        foreach (FletiLandingPagePtContent::gallery() as $keyName => $texts) {
            $section = LandingPageSection::query()
                ->where('settings_type', GALLERY)
                ->where('key_name', $keyName)
                ->first();

            if (!$section) {
                continue;
            }

            $value = is_array($section->value) ? $section->value : [];
            if (!$force && !empty($value['title']) && !$this->looksEnglish($value['title'])) {
                continue;
            }

            $section->update(['value' => array_merge($value, $texts)]);
            $this->info("Atualizado: gallery/{$keyName}");
            $count++;
        }

        return $count;
    }

    private function mergeNewsletter(bool $force): int
    {
        $section = LandingPageSection::query()
            ->where('settings_type', NEWSLETTER)
            ->where('key_name', INTRO_CONTENTS)
            ->first();

        if (!$section) {
            return 0;
        }

        return $this->mergeSection(NEWSLETTER, INTRO_CONTENTS, FletiLandingPagePtContent::newsletter(), $force) > 0 ? 1 : 0;
    }

    private function mergeFooter(bool $force): int
    {
        $section = LandingPageSection::query()
            ->where('settings_type', FOOTER)
            ->where('key_name', FOOTER_CONTENTS)
            ->first();

        if (!$section) {
            return 0;
        }

        $value = is_array($section->value) ? $section->value : [];
        if (!$force && !empty($value['title']) && !$this->looksEnglish($value['title'])) {
            return 0;
        }

        $section->update(['value' => array_merge($value, FletiLandingPagePtContent::footer())]);
        $this->info('Atualizado: footer/footer_contents');
        return 1;
    }

    private function hasMeaningfulText(array $value): bool
    {
        foreach (['title', 'sub_title', 'subtitle'] as $key) {
            if (!empty($value[$key]) && !$this->looksEnglish($value[$key])) {
                return true;
            }
        }

        return false;
    }

    private function looksEnglish(string $text): bool
    {
        $needles = [
            'Download', 'Ride', 'Customer', 'Support', 'Share Your', 'Experience',
            'Explore', 'Earn Money', 'Subscribe', 'Connect with', 'Happy',
            'Complete Ride', 'Parcel Delivery', 'Book a ride', 'Hassle-Free',
            'DriveMond', 'delivery solution', 'newsletters',
        ];

        foreach ($needles as $needle) {
            if (stripos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
