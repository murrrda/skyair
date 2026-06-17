<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_trainings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('zaposlen_user_id');
            $table->unsignedBigInteger('training_type_id');

            $table->foreign('zaposlen_user_id')
                ->references('user_id')->on('zaposleni')
                ->cascadeOnDelete();

            $table->foreign('training_type_id')
                ->references('id')->on('training_types')
                ->restrictOnDelete();

            $table->date('started_at');
            $table->date('finished_at');
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_trainings');
    }
};
