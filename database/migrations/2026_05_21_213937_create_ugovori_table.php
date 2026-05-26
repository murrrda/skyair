<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ugovori', function (Blueprint $table) {
            $table->unsignedBigInteger('zaposlen_user_id');
            $table->unsignedBigInteger('tip_ugovora_id');

            $table->primary(['zaposlen_user_id', 'tip_ugovora_id']);

            $table->foreign('zaposlen_user_id')
                ->references('user_id')->on('zaposleni')
                ->cascadeOnDelete();

            $table->foreign('tip_ugovora_id')
                ->references('id')->on('tipovi_ugovora')
                ->cascadeOnDelete();

            $table->date('datum_potpisivanja');
            $table->date('datum_isteka');
            $table->text('napomena')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugovori');
    }
};
