<?php

namespace Modules\User\Commands;

use Mindy\Console\ConsoleCommand;
use Mindy\Helper\Console;
use Mindy\Validation\EmailValidator;
use Modules\User\Models\User;

/**
 * Class UserCommand
 * @package Modules\User
 */
class UserCommand extends ConsoleCommand
{
    protected function getStdinLine()
    {
        $handle = fopen("php://stdin", "r");
        return str_replace("\n", "", fgets($handle));
    }

    protected function getPasswordPrompt()
    {
        return [Console::prompt("Password:"), Console::prompt("Confirm password:")];
    }

    protected function getPassword()
    {
        list($password, $confirmPassword) = $this->getPasswordPrompt();

        while ($password != $confirmPassword) {
            echo "Incorrect data, please try again:\n";
            list($password, $confirmPassword) = $this->getPasswordPrompt();
        }

        return $password;
    }

    public function actionChangepassword($username, $hashType = null)
    {
        $user = User::objects()->get(['username' => $username]);
        if ($user === null) {
            echo "User does not exists\n";
            exit(1);
        }
        $password = $this->getPassword();

        if (empty($hashType)) {
            $hashType = $user->hash_type;
        } else if (!empty($hashType) && $user->hash_type != $hashType) {
            $user->hash_type = $hashType;
            $user->save(['hash_type']);
        }
        $updated = $user->objects()->setPassword($password, $hashType);
        echo $updated ? "Password updated\n" : "Failed update password\n";
        exit(0);
    }

    /**
     * @param $username string
     * @param $email string
     * @param $hashType null|string
     * @param $superuser bool
     */
    protected function createUser($username, $email, $hashType = null, $superuser)
    {
        if ($username === null) {
            $username = Console::prompt("Username:");
        }

        if ($email === null) {
            $email = Console::prompt("Email:");
        }

        $emailValidator = new EmailValidator(true);
        if (!$emailValidator->validate($email)) {
            echo "Incorrect email address\n";
            exit(1);
        }

        $has = User::objects()->filter(['username' => $username])->orFilter(['email' => $email])->get();

        if ($has === null) {
            $password = $this->getPassword();

            if ($superuser) {
                $model = User::objects()->createSuperUser($username, $password, $email, [
                    'hash_type' => $hashType
                ]);
            } else {
                $model = User::objects()->createUser($username, $password, $email, [
                    'hash_type' => $hashType
                ]);
            }

            if (is_array($model)) {
                echo implode("\n", $model);
                exit(1);
            } else {
                echo "Created\n";
            }
            exit(0);
        } else {
            echo "User already exists\n";
            exit(0);
        }
    }

    public function actionCreatesuperuser($username = null, $email = null, $hashType = null)
    {
        $this->createUser($username, $email, $hashType, true);
    }

    public function actionCreateUser($username = null, $email = null, $hashType = null)
    {
        $this->createUser($username, $email, $hashType, false);
    }
}
