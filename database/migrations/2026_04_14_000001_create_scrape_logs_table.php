<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scrape_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('url_id')->nullable();
            $table->string('property_name', 255)->nullable();
            $table->enum('level', ['info', 'warn', 'error'])->default('info');
            $table->string('event', 64);                // e.g. "navigate", "captcha_solved", "extract_fail"
            $table->text('message');
            $table->json('context')->nullable();         // optional structured data
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['url_id', 'created_at']);
            $table->index(['level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_logs');
    }
};
