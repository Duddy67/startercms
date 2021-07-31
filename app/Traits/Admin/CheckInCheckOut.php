<?php

namespace App\Traits\Admin;

use App\Models\Settings\General;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Users\User;


trait CheckInCheckOut
{
    /**
     * Checks out a given record for the current user.
     *
     * @param string  $tableName
     * @param User    $user
     * @param integer $recordId
     *
     * @return void
     */
    public function checkOut($record)
    {
        $record->checked_out = auth()->user()->id;
	$record->checked_out_time = Carbon::now();
	$record->save();
    }

    public function checkIn($record)
    {
        $record->checked_out = null;
	$record->checked_out_time = null;
	$record->save();
    }

    public function canCheckIn($record)
    {
        // Get the checked out user.
	$user = User::findOrFail($record->checked_out);

	// Ensure the current user has a higher role level or that they are the checked out user themselves. 
	if (auth()->user()->getRoleLevel() > $user->getRoleLevel() || $record->checked_out == auth()->user()->id) {
	    return true;
	}

	return false;
    }

    public function checkInAll()
    {
    }
}
