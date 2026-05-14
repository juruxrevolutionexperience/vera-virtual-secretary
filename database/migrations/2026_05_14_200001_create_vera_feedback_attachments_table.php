<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vera_feedback_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vera_feedback_id')->constrained('vera_feedbacks')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedInteger('size');
            $table->string('mime_type', 100);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vera_feedback_attachments');
    }
};
