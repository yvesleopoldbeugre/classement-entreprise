<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('expediteur'); // App\Enums\Expediteur : visiteur | bot | admin
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('corps');
            $table->timestamp('lu_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
