<?php

namespace Dan\Database;

use Carbon\Carbon;

class DatabaseManager
{
    /** @var Database[] */
    protected static $databases = [];

    protected $lastBackup;

    /**
     * Loads a database.
     *
     * @param $name
     *
     * @throws \Exception
     */
    public function loadDatabase($name)
    {
        if (!$this->exists($name)) {
            throw new \Exception("Database {$name} doesn't exist.");
        }

        if ($this->loaded($name)) {
            throw new \Exception("Database {$name} is already loaded.");
        }

        $load = new Database($name);

        static::$databases[$name] = $load;
    }

    /**
     * Gets a loaded database.
     *
     * @param $name
     *
     * @throws \Exception
     *
     * @return \Dan\Database\Database
     */
    public function get($name) : Database
    {
        if (!$this->loaded($name)) {
            $this->loadDatabase($name);
        }

        return static::$databases[$name];
    }

    /**
     * Checks to see if a database exists.
     *
     * @param $name
     *
     * @return bool
     */
    public function exists($name) : bool
    {
        return filesystem()->exists(databasePath("{$name}.json"));
    }

    /**
     * Creates a database.
     *
     * @param $name
     *
     * @throws \Exception
     *
     * @return Database
     */
    public function create($name) : Database
    {
        if ($this->exists($name)) {
            throw new \Exception("Database {$name} already exist.");
        }

        $new = new Database($name);
        $new->save();

        static::$databases[$name] = $new;

        return static::$databases[$name];
    }

    /**
     * Checks to see if a database is loaded.
     *
     * @param $database
     *
     * @return bool
     */
    public function loaded($database) : bool
    {
        return array_key_exists($database, static::$databases);
    }

    public function backupAll()
    {
        if ($this->lastBackup instanceof Carbon) {
            if ($this->lastBackup->diffInMinutes(new Carbon(), true) < config('dan.database_backup_interval')) {
                return;
            }
        }

        foreach (static::$databases as $name => $database) {
            $database->save(true);
        }

        $this->lastBackup = new Carbon();
    }
}
