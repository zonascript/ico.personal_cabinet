<?php

namespace Modules\User\Components;

use Mindy\Session\HttpSession;
use Modules\User\Models\Session;

/**
 * Class UserSession
 * @package Modules\User
 */
class UserSession extends HttpSession
{
    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getCustomHandler()
    {
        return new DatabaseSessionHandler();
    }

    /**
     * Updates the current session id with a newly generated one.
     * Please refer to {@link http://php.net/session_regenerate_id} for more details.
     * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
     * @since 1.1.8
     */
    public function regenerateID($deleteOldSession = false)
    {
        $oldID = $this->getId();

        // if no session is started, there is nothing to regenerate
        if (empty($oldID)) {
            return;
        }

        parent::regenerateID(false);
        $newID = $this->getId();

        $session = Session::objects()->filter(['id' => $oldID])->get();
        if ($session !== null) {
            if ($deleteOldSession) {
                $session->objects()->update(['id' => $newID]);
            } else {
                $session->id = $newID;
                $session->save();
            }
        } else {
            $session = new Session([
                'id' => $newID,
                'expire' => time() + $this->getTimeout(),
                'data' => ''
            ]);
            $session->save();
        }
    }
}
