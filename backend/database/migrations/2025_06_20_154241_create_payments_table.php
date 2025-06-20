<?php

// database/migrations/xxxx_xx_xx_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['Pre-Book', 'Final']);
            $table->integer('amount');
            $table->string('tran_id')->unique();
            $table->string('status')->default('Pending'); // Pending, Success, Failed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

