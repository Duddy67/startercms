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
    public function canChangeAccessLevel()
    {
	return ($this->owned_by == auth()->user()->id || auth()->user()->getRoleLevel() > $this->getOwnerRoleLevel()) ? true: false;
    }

    /*
     * Checks whether the current user is allowed to access a given item.
     *
     * @param Object  $item
     * @return boolean
     */
    public function canAccess()
    {
        return ($this->access_level == 'public_ro' || $this->canEdit()) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to edit a given item.
     *
     * @param Object  $item
     * @return boolean
     */
    public function canEdit()
    {
        if ($this->access_level == 'public_rw' || $this->getOwnerRoleLevel() < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) {
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
    public function canDelete()
    {
	// The owner role level is lower than the current user's or the current user owns the item.
	if ($this->getOwnerRoleLevel() < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) {
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
    public function getOwnerRoleLevel()
    {
	$owner = ($this->owned_by == auth()->user()->id) ? auth()->user() : User::findOrFail($this->owned_by);

	return $owner->getRoleLevel();
    }

    /*
     * Checks whether the current user is allowed to change the status level of a given item.
     *
     * @return boolean
     */
    public function canChangeStatus()
    {
        // Use the access level constraints.
	return $this->canChangeAccessLevel();
    }

    /*
     * Checks whether the current user is allowed to change the owner,
     * groups, categories or parent category of a given item.
     *
     * @return boolean
     */
    public function canChangeAttachments()
    {
        // Use the access level constraints.
	return $this->canChangeAccessLevel();
    }
}

