
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('merchant_reference')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->unique();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('passerelle')->default('221pay')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('passerelle')->default('paydunya')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['merchant_reference']);
            $table->dropUnique(['provider_payment_id']);
            $table->dropColumn([
                'merchant_reference',
                'provider_payment_id',
            ]);
        });
    }
};
