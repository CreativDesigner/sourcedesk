<?php
// Abstract class for addons

abstract class Addon
{
    protected $lang;
    protected $info;
    protected $active = false;
    protected $name;
    protected $language;
    protected $options = array();

    public function __construct()
    {
        global $addons;

        $this->options = $addons->getSettings($this->name);
        if (!isset($this->options["active"])) {
            $this->setOption("active", "false");
            $this->active = false;
        } else {
            $this->active = $this->options["active"] == "true";
        }
    }

    public function haveAccess($id = null)
    {
        global $adminInfo;
        if ($id === null) {
            $id = $adminInfo->ID;
        }

        $arr = unserialize($this->options["access"]);
        return is_array($arr) && in_array($id, $arr);
    }

    public function activate()
    {
        global $db, $CFG;
        $db->query("UPDATE `addons` SET `value` = '" . encrypt("true") . "' WHERE `addon` = '" . $db->real_escape_string($this->name) . "' AND `setting` = 'active'");
        $aff = $db->affected_rows;
        if ($aff > 0) {
            $db->query("INSERT INTO `addons` (`addon`, `setting`, `value`) VALUES ('" . $db->real_escape_string($this->name) . "', 'access', '')");
        }

        foreach ($this->getSettings() as $k => $v) {
            $db->query("INSERT INTO `addons` (`addon`, `setting`, `value`) VALUES ('" . $db->real_escape_string($this->name) . "', '" . $db->real_escape_string($k) . "', '" . (!empty($v['default']) ? $db->real_escape_string(encrypt($v['default'])) : "") . "')");
        }

        $this->__construct($this->language);
        return (bool) $aff;
    }

    public function deactivate()
    {
        global $db, $CFG;
        $db->query("UPDATE `addons` SET `value` = '" . encrypt("false") . "' WHERE `addon` = '" . $db->real_escape_string($this->name) . "' AND `setting` = 'active'");
        $db->query("DELETE FROM `addons` WHERE `addon` = '" . $db->real_escape_string($this->name) . "'");
        $aff = $db->affected_rows;
        $this->__construct($this->language);
        return (bool) $aff;
    }

    public function setOption($k, $v)
    {
        global $db, $CFG;

        $k = $db->real_escape_string($k);
        $v = $db->real_escape_string(encrypt($v));

        if (isset($this->options[$k])) {
            $db->query("UPDATE addons SET `value` = '$v' WHERE `addon` = '" . $db->real_escape_string($this->name) . "' AND `setting` = '$k'");
        } else {
            $db->query("INSERT INTO addons (`value`, `addon`, `setting`) VALUES ('$v', '" . $db->real_escape_string($this->name) . "', '$k')");
        }

        $res = $db->affected_rows;

        $this->options[$k] = $v;
        if ($k == "active") {
            $this->active = $this->options["active"] == "true";
        }

        return $res;
    }

    final public function isActive()
    {return (bool) $this->active;}
    final public function getInfo($str = "")
    {
        if (isset($this->info[$str])) {
            return $this->info[$str];
        }

        if (!filter_var($this->info['url'], FILTER_VALIDATE_URL)) {
            unset($this->info['url']);
        }

        $this->info['company'] = htmlentities($this->info['company']);
        return $this->info;
    }
    public function adminPages()
    {return array();}
    public function clientPages()
    {return array();}
    public function hooks()
    {return array();}
    public function getSettings()
    {return array();}
    public function getWidgets()
    {return array();}
    public function adminMenu()
    {return false;}

    public function getLang($str = "")
    {
        if ($this->language != currentLang()) {
            $this->__construct(currentLang());
        }

        return !empty(trim($str)) && isset($this->lang[$str]) ? $this->lang[$str] : $this->lang;
    }

    public function getOption($n)
    {
        return isset($this->options[$n]) ? $this->options[$n] : false;
    }

    protected function deleteDir($dirname)
    {
        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file)) {
                    @unlink($dirname . "/" . $file);
                } else {
                    $this->deleteDir($dirname . '/' . $file);
                }

            }
        }

        closedir($dir_handle);
        return rmdir($dirname);
    }
}

// Class for handling addons

class AddonHandler
{
    protected $addons = array();
    protected $adminPages = array();
    protected $clientPages = array();
    protected $adminMenu = array();
    protected $hooks = array();
    protected $settings = array();
    protected $dryAdminPages = array();
    protected $dryAdminMenu = array();

    public function construct()
    {
        global $var, $CFG, $db, $adminInfo;
        $language = isset($var['language']) ? $var['language'] : $CFG['LANG'];

        $this->addons = $this->adminPages = $this->dryAdminPages = $this->dryAdminMenu = $this->clientPages = $this->adminMenu = $this->hooks = $this->settings = array();

        $this->loadAllSettings();

        $addonHandle = opendir(__DIR__ . '/../addons/');
        while ($f = readdir($addonHandle)) {
            if (is_dir(__DIR__ . '/../addons/' . $f) && substr($f, 0, 1) != "." && file_exists(__DIR__ . '/../addons/' . $f . '/' . $f . '.php')) {
                require_once __DIR__ . '/../addons/' . $f . '/' . $f . '.php';
            }
        }

        closedir($addonHandle);

        $name = array();
        foreach (get_declared_classes() as $class) {
            if (get_parent_class($class) == "Addon") {
                $this->addons[$class::$shortName] = new $class($language);
                $name[$class::$shortName] = $this->addons[$class::$shortName]->getInfo()['name'];

                if (is_array($this->addons[$class::$shortName]->clientPages()) && $this->addons[$class::$shortName]->isActive()) {
                    foreach ($this->addons[$class::$shortName]->clientPages() as $clientPage => $function) {
                        $this->clientPages[$clientPage] = array($class::$shortName, $function);
                    }
                }

                if (isset($adminInfo)) {
                    if (is_array($this->settings) && array_key_exists($class::$shortName, $this->settings) && array_key_exists("access", $this->settings[$class::$shortName])) {
                        $arr = unserialize($this->settings[$class::$shortName]["access"]);
                        if (is_array($arr) && in_array($adminInfo->ID, $arr) && $this->addons[$class::$shortName]->isActive()) {
                            if (is_array($this->addons[$class::$shortName]->adminMenu())) {
                                foreach ($this->addons[$class::$shortName]->adminMenu() as $k => $v) {
                                    $this->adminMenu[$k] = $v;
                                }
                            }

                            if (is_array($this->addons[$class::$shortName]->adminPages())) {
                                foreach ($this->addons[$class::$shortName]->adminPages() as $adminPage => $function) {
                                    $this->adminPages[$adminPage] = array($class::$shortName, $function);
                                }
                            }
                        }
                    }

                    if ($this->addons[$class::$shortName]->isActive()) {
                        if (is_array($this->addons[$class::$shortName]->adminMenu())) {
                            foreach ($this->addons[$class::$shortName]->adminMenu() as $k => $v) {
                                $this->dryAdminMenu[$k] = [$class::$shortName, $v];
                            }
                        }

                        if (is_array($this->addons[$class::$shortName]->adminPages())) {
                            foreach ($this->addons[$class::$shortName]->adminPages() as $adminPage => $function) {
                                $this->dryAdminPages[$adminPage] = array($class::$shortName, $function);
                            }
                        }
                    }
                }

                $hooks = $this->addons[$class::$shortName]->hooks();
                if ($this->addons[$class::$shortName]->isActive() && is_array($hooks)) {
                    foreach ($hooks as $hook) {
                        if (!is_array($hook) || count($hook) != 3) {
                            continue;
                        }

                        $hook[0] = strtolower($hook[0]);

                        if (!array_key_exists($hook[0], $this->hooks)) {
                            $this->hooks[$hook[0]] = array($hook[2] => array($class::$shortName, $hook[1]));
                        } else {
                            while (array_key_exists($hook[2], $this->hooks[$hook[0]])) {
                                $hook[2]++;
                            }

                            $this->hooks[$hook[0]][$hook[2]] = array($class::$shortName, $hook[1]);
                        }
                    }
                }
            }
        }

        ksort($this->adminMenu);
        array_multisort($name, SORT_ASC, $this->addons);
    }

    public function loadAllSettings()
    {
        global $db, $CFG;

        $sql = $db->query("SELECT `addon`, `setting`, `value` FROM `addons`");
        while ($row = $sql->fetch_object()) {
            if (!array_key_exists($row->addon, $this->settings) || !is_array($this->settings[$row->addon])) {
                $this->settings[$row->addon] = [];
            }

            $this->settings[$row->addon][$row->setting] = decrypt($row->value);
        }
    }

    public function get($name = null)
    {
        return $name && array_key_exists($name, $this->addons) ? $this->addons[$name] : $this->addons;
    }

    public function getWidgets()
    {
        global $adminInfo, $db, $CFG;

        $w = array();
        foreach ($this->addons as $a) {
            if (!$a->isActive() || !$a->haveAccess()) {
                continue;
            }

            $w2 = $a->getWidgets();
            foreach ($w2 as $k => $i) {
                $w[$k] = array($i[0], $i[1], $a->getInfo()['name']);
            }

        }

        return $w;
    }

    public function getAdminMenu()
    {
        return $this->adminMenu;
    }

    public function routePage($page, $type = "admin", $dry = true)
    {
        if ($type == "admin") {
            if (isset($this->adminPages[$page])) {
                $method = $this->adminPages[$page][1];
                if (!$dry) {
                    $this->addons[$this->adminPages[$page][0]]->$method();
                    title($this->addons[$this->adminPages[$page][0]]->getInfo("name"));
                    menu("addons");
                }

                return true;
            }
            return false;
        } else if ($type == "client") {
            if (isset($this->clientPages[$page])) {
                $method = $this->clientPages[$page][1];
                if (!$dry) {
                    $this->addons[$this->clientPages[$page][0]]->$method();
                }

                return true;
            }
            return false;
        }
    }

    public function getHooks()
    {
        return $this->hooks;
    }

    public function getAdminPages()
    {
        return $this->adminPages;
    }

    public function getClientPages()
    {
        return $this->clientPages;
    }

    public function getDryAdminPages()
    {
        return $this->dryAdminPages;
    }

    public function getDryAdminMenu()
    {
        return $this->dryAdminMenu;
    }

    public function runHook($hook, $params = array())
    {
        $hook = strtolower($hook);
        if (!array_key_exists($hook, $this->hooks) || !is_array($this->hooks[$hook])) {
            return array();
        }

        $res = array();
        ksort($this->hooks[$hook]);
        foreach ($this->hooks[$hook] as $path) {
            $func = $path[1];
            array_push($res, $this->addons[$path[0]]->$func($params));
        }
        return $res;
    }

    public function getSettings($addon)
    {
        return array_key_exists($addon, $this->settings) ? $this->settings[$addon] : [];
    }
}

$addons = new AddonHandler;
$addons->construct();
