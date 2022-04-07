<?php

namespace App\Traits\Admin;

use App\Traits\Admin\AccessLevel;


trait TreeAccessLevel
{
    use AccessLevel;


    public function anyDescendantCheckedOut()
    {
        foreach ($this->descendants as $descendant) {
            if ($descendant->checked_out !== null) {
                return true;
            }
        }

        return false;
    }

    public function canDeleteDescendants()
    {
        foreach ($this->descendants as $descendant) {
            if (!$descendant->canDelete()) {
                return false;
            }
        }

        return true;
    }

    public function canDescendantsBePrivate()
    {
        // All the descendants must be owned by the parent owner.
        foreach ($this->descendants as $descendant) {
            if ($descendant->owned_by != $this->owned_by) {
                return false;
            }
        }

        return true;
    }

    public function setDescendantAccessToPrivate()
    {
        foreach ($this->descendants as $descendant) {
            $descendant->access_level = 'private';
            $descendant->save();
        }
    }

    public function isParentPrivate()
    {
        return ($this->getParentId() && get_class($this)::find($this->getParentId())->access_level == 'private') ? true : false;
    }
}

