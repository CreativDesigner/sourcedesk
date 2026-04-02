<?php
// Abstract class for telephone log modules

abstract class TelephoneLogModule
{
    protected $name, $short;
    public $options;

    final public function __construct()
    {
        global $CFG;
        if ($this->isActive()) {
            $this->options = unserialize($CFG['TELEPHONE_LOG'])[$this->short];
        }

    }

    public function getVersion()
    {
        return $this->version ?? "1.0";
    }

    final public function getName()
    {
        return $this->name;
    }

    final public function getShort()
    {
        return $this->short;
    }

    final public function isActive()
    {
        global $CFG;
        $u = unserialize($CFG['TELEPHONE_LOG']);
        return is_array($u) && array_key_exists($this->short, $u);
    }

    public function getSettings()
    {
        return array();
    }

    abstract public function getLogs();
}

// Class for handling telephone modules

class TelephoneLogHandler
{
    protected $modules = array();

    public function __construct()
    {
        $addonHandle = opendir(__DIR__ . '/../telephone_log/');
        while ($f = readdir($addonHandle)) {
            if (is_dir(__DIR__ . '/../telephone_log/' . $f) && substr($f, 0, 1) != "." && file_exists(__DIR__ . '/../telephone_log/' . $f . '/' . $f . '.php')) {
                require_once __DIR__ . '/../telephone_log/' . $f . '/' . $f . '.php';
            }
        }

        closedir($addonHandle);

        $name = array();
        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) == "TelephoneLogModule") {
                $obj = new $class();
                $this->modules[$obj->getShort()] = $obj;
            }
        }

        ksort($this->modules);
    }

    public function get()
    {
        return $this->modules;
    }

    public static function moduleExists($short)
    {
        $obj = new TelephoneLogHandler;
        return array_key_exists($short, $obj->get());
    }
}
