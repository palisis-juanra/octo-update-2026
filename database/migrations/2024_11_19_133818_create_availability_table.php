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
        Schema::create('availability', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->timestamps();
            $table->integer('departure_id');
            $table->dateTime('local_date_time_start');
            $table->dateTime('local_date_time_end');
            $table->boolean('all_day')->default(false);
            $table->boolean('available');
            $table->string('status');
            $table->integer('vacancies')->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('max_units');
            $table->string('utc_cutoff_at');
            $table->string('opening_hours_from');
            $table->string('opening_hours_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
