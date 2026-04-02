<?php
// Abstract class for telephone modules

abstract class TelephoneModule
{
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version ?? "1.0";
    }

    abstract public function call($number, $info);
}

// Class for handling telephone modules

class TelephoneHandler
{
    protected $modules = array();

    public function __construct()
    {
        $addonHandle = opendir(__DIR__ . '/../telephone/');
        while ($f = readdir($addonHandle)) {
            if (is_dir(__DIR__ . '/../telephone/' . $f) && substr($f, 0, 1) != "." && file_exists(__DIR__ . '/../telephone/' . $f . '/' . $f . '.php')) {
                require_once __DIR__ . '/../telephone/' . $f . '/' . $f . '.php';
            }
        }

        closedir($addonHandle);

        $name = array();
        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) == "TelephoneModule") {
                $obj = new $class();
                $this->modules[$obj->getName()] = $obj;
            }
        }

        ksort($this->modules);
    }

    public function get()
    {
        return $this->modules;
    }
}

$telephone = new TelephoneHandler;
