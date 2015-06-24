<?php

use Dan\Irc\Location\Channel;
use Dan\Irc\Location\User;

/** @var User $user */
/** @var Channel $channel */
/** @var string $message */
/** @var string $entry */

if($entry == 'use')
{
    \Dan\Helpers\Hooks::registerHooks();

    message($channel, "Reloaded");
}

if($entry == 'help')
{
    return ["Reloads hooks"];
}