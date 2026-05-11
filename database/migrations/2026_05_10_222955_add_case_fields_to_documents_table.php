<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('case_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('cases')
                  ->nullOnDelete();

            $table->string('document_type')->nullable()->after('mime_type');
            // e.g. letter_of_instruction, demand_letter, plaint, etc.
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropColumn(['case_id', 'document_type']);
        });
    }
};