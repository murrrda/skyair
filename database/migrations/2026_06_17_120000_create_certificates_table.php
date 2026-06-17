<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('zaposlen_user_id');
            $table->unsignedBigInteger('certificate_type_id');

            $table->foreign('zaposlen_user_id')
                ->references('user_id')->on('zaposleni')
                ->cascadeOnDelete();

            $table->foreign('certificate_type_id')
                ->references('id')->on('certificate_types')
                ->restrictOnDelete();

            $table->date('issued_at');
            $table->date('expires_at');
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
