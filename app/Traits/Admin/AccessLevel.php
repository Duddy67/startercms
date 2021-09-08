<?php

namespace App\Traits\Admin;

use App\Models\Users\User;


trait AccessLevel
{
    /*
     * Checks whether the current user is allowed to to change the access level of a given item.
     *
     * @param Object  $item
     * @return boolean
     */
    public function canChangeAccessLevel($item)
    {
	return ($item->owned_by == auth()->user()->id || auth()->user()->getRoleLevel() > $this->getOwnerRoleLevel($item)) ? true: false;
    }

    /*
     * Checks whether the current user is allowed to access a given item.
     *
     * @param Object  $item
     * @return boolean
     */
    public function canAccess($item)
    {
        return ($item->access_level == 'public_ro' || $this->canEdit($item)) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to edit a given item.
     *
     * @param Object  $item
     * @return boolean
     */
    public function canEdit($item)
    {
        if ($item->access_level == 'public_rw' || $this->getOwnerRoleLevel($item) < auth()->user()->getRoleLevel() || $item->owned_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }

    /*
     * Checks whether the current user is allowed to delete a given item according to their role level.
     *
     * @param Object  $item
     * @return boolean
     */
    public function canDelete($item)
    {
	// The owner role level is lower than the current user's or the current user owns the item.
	if ($this->getOwnerRoleLevel($item) < auth()->user()->getRoleLevel() || $item->owned_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }

    /*
     * Returns the role level of the item's owner.
     *
     * @param Object  $item
     * @return integer
     */
    public function getOwnerRoleLevel($item)
    {
	$owner = ($item->owned_by == auth()->user()->id) ? auth()->user() : User::findOrFail($item->owned_by);

	return $owner->getRoleLevel();
    }

    /*
     * Checks whether the current user is allowed to change the status level of a given item.
     *
     * @return boolean
     */
    public function canChangeStatus($item)
    {
        // Use the access level constraints.
	return $this->canChangeAccessLevel($item);
    }
}

