<?php namespace Dan\Core;

use Dan\Commands\CommandManager;
use Dan\Contracts\ServiceContract;
use Dan\Irc\Connection;
use Dan\Irc\Location\User;
use Dan\Plugins\PluginManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Dan\Console\Console;
use League\Flysystem\Exception;

class Dan {

    const VERSION = '3.3.0';

    /** @var object[] */
    protected $services = [];

    /** @var static */
    protected static $dan;

    /** @var Blacklist */
    protected static $blacklist;
    protected $filesystem;

    /**
     * Load 'er up.
     */
    public function __construct()
    {
        static::$dan    = $this;
        $this->services = new Collection();

        $this->filesystem = new Filesystem();

        if(!$this->filesystem->exists(STORAGE_DIR))
            $this->filesystem->makeDirectory(STORAGE_DIR);

        if(!$this->filesystem->exists(STORAGE_DIR . '/database/'))
            $this->filesystem->makeDirectory(STORAGE_DIR . '/database/');

        if(!$this->filesystem->exists(STORAGE_DIR . '/plugins/'))
            $this->filesystem->makeDirectory(STORAGE_DIR . '/plugins/');

        if(!$this->filesystem->exists(ROOT_DIR . '/logs/'))
            $this->filesystem->makeDirectory(ROOT_DIR . '/logs/');

        Config::load();

        static::$blacklist = new Blacklist();
    }

    /**
     * Boots Dan.
     *
     * @param $args
     */
    public function boot($args)
    {
        Console::info('Loading bot..');

        if(Config::get('dan.debug'))
        {
            error_reporting(E_ALL);
            ini_set("display_errors", true);
            Console::alert("{brown}!!!!Debug mode is active!!!!");
        }

        CommandManager::init();
        $this->services->put('pluginManager', new PluginManager());

        if(!in_array('--safemode', $args))
           $this->loadPlugins();

        Console::success("Bot loaded. Connecting to IRC.");

        if(in_array('--dry', $args))
            die;

        $this->services->put('irc', new Connection());
        $this->services->get('irc')->run();
    }

    /**
     * Loads the plugins
     */
    public function loadPlugins()
    {
        $plugins = Config::get('dan.plugins');

        if(count($plugins) == 0)
            return;

        foreach ($plugins as $plugin)
        {
            try
            {
                $this->services->get('pluginManager')->loadPlugin($plugin);
            }
            catch (\Exception $e)
            {
                Console::exception($e);
            }
        }
    }

    public static function isSudoUser(User $user)
    {
        foreach(Config::get('dan.sudo_users') as $usr)
            if (fnmatch($usr, "{$user->getNick()}!{$user->getUser()}@{$user->getHost()}"))
                return true;

        return false;
    }

    /**
     * Gets a service.
     *
     * @param string $key
     * @return ServiceContract|CommandManager|PluginManager|Connection
     */
    public static function service($key)
    {
        return static::$dan->services->get($key, null);
    }

    /**
     * Registers a service.
     *
     * @param string $key
     * @param \Dan\Contracts\ServiceContract $service
     * @return \Illuminate\Support\Collection|object
     */
    public static function registerService($key, ServiceContract $service)
    {
        static::$dan->services->put($key, $service);
    }

    /**
     * Unregisters a service.
     *
     * @param $key
     */
    public static function unregisterService($key)
    {
        static::$dan->services->forget($key);
    }

    /**
     * @return Blacklist
     */
    public static function blacklist()
    {
        return static::$blacklist;
    }
}
 