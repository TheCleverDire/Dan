<?php namespace Dan\Irc\Packets; 


use Dan\Contracts\PacketContract;
use Dan\Core\Dan;

class PacketPrivmsg implements PacketContract {


    public function handle($from, $data)
    {
        event('irc.packets.privmsg', $data);

        $to         = $data[0];
        $message    = $data[1];
        $user       = user($from);

        if(strpos($message, "\001") !== false)
        {
            $ctcp = explode(' ', trim($message, " \t\n\r\0\x0B\001"), 2);

            /*if($ctcp[0] == 'ACTION')
            {
                event('irc.packets.action.public', [
                    'user'      => $channel->getUser($user->nick()),
                    'channel'   => $channel,
                    'message'   => $message
                ]);
            }*/

            $send = event('irc.packets.message.ctcp', [
                'type'  => $ctcp[0],
                'args'  => @$ctcp[1]
            ]);

            if(is_array($send))
            {
                if($ctcp[0] == 'VERSION')
                    $send = "Dan the PHP Bot " . Dan::VERSION . " by UclCommander - http://derpy.me/dan3 - PHP " . phpversion() . " \001";

                if($ctcp[0] == 'TIME')
                    $send =  date('r');

                if($ctcp[0] == 'PING')
                    $send = time();
            }

            send("NOTICE", $user->nick(), "\001{$ctcp[0]} {$send}\001");

            return;
        }

        if($to == config('irc.user.nick'))
        {
            event('irc.packets.message.private', [
                'from'      => $user,
                'message'   => $message
            ]);

            return;
        }

        $channel = connection()->getChannel($to);

        if($channel == null)
            return;

        database()->increment('users', ['nick' => $user->nick()], 'messages');

        console("[{$channel->getLocation()}] {$user->nick()}: {$message}");

        event('irc.packets.message.public', [
            'user'      => $channel->getUser($user->nick()),
            'channel'   => $channel,
            'message'   => $message
        ]);
    }
}