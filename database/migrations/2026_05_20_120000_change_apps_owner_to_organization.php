<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable()->after('id')->index();
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('organization_id');
            $table->unsignedInteger('user_id')->after('id');
        });
    }
};
