<?php

namespace Dan\Setup\Configs;

use Dan\Config\Config;
use Dan\Console\OutputStyle;
use Dan\Contracts\ConfigSetupContract;
use Dan\Services\ShortLinks\Links;

class Dan implements ConfigSetupContract
{
    /**
     * @var \Dan\Console\OutputStyle
     */
    protected $output;

    public function __construct(OutputStyle $output)
    {
        $this->output = $output;
    }

    /**
     * @return Config
     */
    public function setup() : Config
    {
        $config = $this->defaultConfig();

        $host = $this->output->ask(
            "Since you're my owner, what's your hostmask? Please use nick!user@host format. You can use wildcards (*) too!"
        );

        $config->push('dan.owners', $host);

        if ($this->output->confirm('Should I automatically check for updates?')) {
            $config->set('dan.updates.auto_check', true);

            if ($this->output->confirm('Should I automatically install updates?')) {
                $config->set('dan.updates.auto_install', true);
            }
        }

        return $config;
    }

    /**
     * @return Config
     */
    public function defaultConfig() : Config
    {
        return new Config([
            'dan' => [
                'debug'     => false,
                'branch'    => 'master',
                'updates'   => [
                    'auto_check'   => false,
                    'auto_install' => false,
                ],
                'owners'          => [],
                'admins'          => [],
                'providers'       => [],
                'use_short_links' => true,
                'short_link_api'  => Links::class,
                'network_console' => 'network:#channel',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
            ],
        ]);
    }

    /**
     * @return mixed
     */
    public function introText()
    {
        return 'Lets setup my main configuration.';
    }
}
