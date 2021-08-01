<?php

namespace App\Traits\Admin;

use App\Models\Settings\General;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Users\User;


trait CheckInCheckOut
{
    /**
     * Checks a given record out for the current user.
     *
     * @param model instance  $record
     * @return void
     */
    public function checkOut($record)
    {
        $record->checked_out = auth()->user()->id;
	$record->checked_out_time = Carbon::now();
	$record->save();
    }

    /**
     * Checks a given record back in for the current user.
     *
     * @param model instance  $record
     * @return void
     */
    public function checkIn($record)
    {
        $record->checked_out = null;
	$record->checked_out_time = null;
	$record->save();
    }

    /**
     * Checks if a given record can be checked back in. 
     *
     * @param model instance  $record
     * @return boolean
     */
    public function canCheckIn($record)
    {
        // Get the user for whom the record is checked out .
	$user = User::findOrFail($record->checked_out);

	// Ensure the current user has a higher role level or that they are the user for whom the record is checked out. 
	if (auth()->user()->getRoleLevel() > $user->getRoleLevel() || $record->checked_out == auth()->user()->id) {
	    return true;
	}

	return false;
    }

    /**
     * Checks multiple records back in for the current user.
     *
     * @param Array  $recordIds
     * @param model instance  $model
     * @return Array
     */
    public function checkInMultiple($recordIds, $model)
    {
        $checkedIn = 0;
	$messages = [];

        // Check in the groups selected from the list.
        foreach ($recordIds as $id) {
	    $record = $model::findOrFail($id);

	    if ($record->checked_out === null) {
	        continue;
	    }

	    if (!$this->canCheckIn($record)) {
	        $messages['error'] = __('messages.generic.check_in_not_auth');
	        continue;
	    }

	    $this->checkIn($record);
	    $checkedIn++;
	}

	if ($checkedIn) {
	    $messages['success'] = __('messages.generic.check_in_success', ['number' => $checkedIn]);
	}

	return $messages;

    }

    public function checkInAll()
    {
    }
}
