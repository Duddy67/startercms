<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function(Blueprint $table)
	{
            $table->unsignedBigInteger('checked_out')->nullable()->after('guard_name');
            $table->timestamp('checked_out_time')->nullable()->after('checked_out');
	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function(Blueprint $table)
	{
	    $table->dropColumn('checked_out');
	    $table->dropColumn('checked_out_time');
	});
    }
}
