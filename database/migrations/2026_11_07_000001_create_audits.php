<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->text('url');
            $t->unsignedSmallInteger('maximum');
            $t->string('status')->default('running');
            $t->unsignedSmallInteger('discovered')->default(0);
            $t->unsignedSmallInteger('requests')->default(0);
            $t->text('note')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $t->text('url');
            $t->text('final_url');
            $t->unsignedSmallInteger('status_code')->nullable();
            $t->string('kind');
            $t->text('message')->nullable();
            $t->json('chain');
            $t->unsignedInteger('duration_ms');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_results');
        Schema::dropIfExists('audits');
    }
};
