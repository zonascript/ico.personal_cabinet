<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 26/08/14.08.2014 16:54
 */

namespace Modules\User\Components;

use Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\Traits\Configurator;
use Modules\User\Models\Session;
use SessionHandler;

/**
 * Class DatabaseSessionHandler
 * @package Modules\User
 */
class DatabaseSessionHandler extends SessionHandler
{
    use Configurator;

    public $key;

    public $encrypt = false;

    public function init()
    {
        $this->key = Mindy::getVersion();
    }

    /**
     * @return bool|void
     */
    public function close()
    {
        return true;
    }

    /**
     * Session open handler.
     * Do not call this method directly.
     * @param string $save_path session save path
     * @param string $session_id session name
     * @return boolean whether session is opened successfully
     */
    public function open($save_path, $session_id)
    {
        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $session_id session ID
     * @return string the session data
     */
    public function read($session_id)
    {
        $session = Session::objects()->filter([
            'expire__gte' => time(),
            'id' => $session_id
        ])->get();

        $data = $session === null ? '' : $session->data;
        if ($this->encrypt) {
            $data = mcrypt_decrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB);
        }
        return $data;
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $session_id session ID
     * @param string $session_data session data
     * @throws Exception
     * @return boolean whether session write is successful
     */
    public function write($session_id, $session_data)
    {
        // exception must be caught in session write handler
        // http://us.php.net/manual/en/function.session-set-save-handler.php
        $expire = time() + (int)ini_get('session.gc_maxlifetime');

        if ($this->encrypt) {
            $session_data = mcrypt_encrypt(MCRYPT_3DES, $this->key, $session_data, MCRYPT_MODE_ECB);
        }

        $session = Session::objects()->get(['id' => $session_id]);
        if ($session === null) {
            $session = new Session([
                'id' => $session_id,
                'data' => $session_data,
                'expire' => $expire,
            ]);
            if ($session->save() === false) {
                throw new Exception("Can't create session");
            }
        } else {
            Session::objects()->filter(['id' => $session_id])->update([
                'data' => $session_data,
                'expire' => $expire
            ]);
        }
        return true;
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $session_id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroy($session_id)
    {
        Session::objects()->filter(['id' => $session_id])->delete();
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxlifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gc($maxlifetime)
    {
        Session::objects()->filter(['expire__lt' => time()])->delete();
        return true;
    }
}
