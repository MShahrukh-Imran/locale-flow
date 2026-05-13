<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('translations', function (Blueprint $table) {
            $table->fullText('content', 'translations_content_fulltext');
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('translations', function (Blueprint $table) {
            $table->dropFullText('translations_content_fulltext');
        });
    }

    private function supportsFullText(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true);
    }
};
