<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ff_view_schema');
    }

    public function down(): void
    {
        Schema::create('ff_view_schema', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamp('added_at')->useCurrent();
        });
    }
};
