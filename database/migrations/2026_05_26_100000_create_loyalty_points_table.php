<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['status', 'reward']);
            $table->enum('action', ['earned', 'spent', 'expired']);
            $table->integer('amount');
            $table->string('description');
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::table('putnici', function (Blueprint $table) {
            $table->integer('status_points')->default(0);
            $table->integer('reward_points')->default(0);
            $table->enum('tier', ['silver', 'gold', 'platinum'])->default('silver');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');

        Schema::table('putnici', function (Blueprint $table) {
            $table->dropColumn(['status_points', 'reward_points', 'tier']);
        });
    }
};
