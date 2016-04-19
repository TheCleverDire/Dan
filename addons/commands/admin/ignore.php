<?php

use Dan\Contracts\UserContract;
use Dan\Irc\Connection;

command(['ignore'])
    ->allowPrivate()
    ->allowConsole()
    ->requiresIrcConnection()
    ->rank('AS')
    ->helpText('Ignores a user')
    ->handler(function (Connection $connection, UserContract $user, $message) {

        if (empty($message)) {
            $ignored = $connection->database('ignore')->get();

            if (!$ignored->count()) {
                $user->notice('No users are ignored');

                return;
            }

            $masks = [];

            foreach ($ignored as $mask) {
                $masks[] = $mask['mask'];
            }

            $user->notice(implode(', ', $masks));

            return;
        }

        $table = $connection->database('ignore');
        $query = $connection->database('users')->where('nick', $message);

        if ($query->count() != 0) {
            $message = '*@'.$query->first()->get('host');
        }

        if (strpos($message, '-') === 0) {

            if (!$table->where('mask', $message)->count()) {
                $user->notice('This user is not ignored.');

                return;
            }

            $table->where('mask', substr($message, 1))->delete();
            $user->notice('This user is no longer ignored.');

            return;
        }

        if ($table->where('mask', $message)->count()) {
            $user->notice('This user is already ignored.');

            return;
        }

        $table->insertOrUpdate(['mask', $message], [
            'mask' => $message,
        ]);

        $user->notice('User has been ignored.');
    });
