<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedTranslations extends Command
{
    protected $signature = 'translations:seed {--count=100000 : Total translation rows to create} {--chunk=2000 : Insert batch size}';

    protected $description = 'Populate the translations table with a large dataset for performance testing.';

    private array $locales = ['en', 'fr', 'es', 'de', 'it'];

    private array $groups = ['auth', 'dashboard', 'profile', 'billing', 'errors', 'common', 'emails', 'menu', 'forms', 'validation'];

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $chunkSize = (int) $this->option('chunk');

        $this->info("Seeding {$count} translations...");

        $tagIds = $this->ensureTags();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = now();
        $produced = 0;
        $batch = [];

        while ($produced < $count) {
            $remaining = $count - $produced;
            $size = min($chunkSize, $remaining);

            for ($i = 0; $i < $size; $i++) {
                $batch[] = [
                    'locale' => $this->locales[array_rand($this->locales)],
                    'key' => $this->groups[array_rand($this->groups)].'.'.Str::random(10).'.'.($produced + $i),
                    'content' => Str::random(40),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $startId = DB::table('translations')->max('id') ?? 0;
            DB::table('translations')->insert($batch);

            $insertedIds = range($startId + 1, $startId + count($batch));
            $this->attachRandomTags($insertedIds, $tagIds);

            $produced += $size;
            $bar->advance($size);
            $batch = [];
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Total translations: {$produced}.");

        return self::SUCCESS;
    }

    private function ensureTags(): array
    {
        $names = ['mobile', 'desktop', 'web', 'admin', 'public', 'transactional', 'marketing', 'ios', 'android'];

        foreach ($names as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }

        return Tag::pluck('id')->all();
    }

    private function attachRandomTags(array $translationIds, array $tagIds): void
    {
        $pivot = [];

        foreach ($translationIds as $tid) {
            $picked = collect($tagIds)->random(random_int(1, min(3, count($tagIds))));

            foreach ($picked as $tagId) {
                $pivot[] = ['translation_id' => $tid, 'tag_id' => $tagId];
            }
        }

        if (! empty($pivot)) {
            DB::table('translation_tag')->insert($pivot);
        }
    }
}
