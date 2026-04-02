<?php

// Abstract class for defining necessary menu entry methods
abstract class CMSMenuEntry
{
    protected $menuName = null;
    protected $menuId = null;
    protected $relId = null;
    protected $info = null;
    protected $type = null;

    public function __construct($menuId)
    {
        // Global some variables for security reasons
        global $db, $CFG;

        $menuId = abs(intval($menuId));
        $this->menuId = $menuId;
        $sql = $db->query("SELECT * FROM cms_menu WHERE status IN (1, 2, 3) AND ID = '$menuId'");
        $info = $sql->fetch_object();

        if (unserialize($info->name) !== false) {
            $arr = unserialize($info->name);
            $pos = array_key_exists($CFG['LANG'], $arr) ? $CFG['LANG'] : array_keys($arr)[0];
            $this->menuName = $arr[$pos];
        } else {
            $this->menuName = $info->name;
        }

        $this->relId = $info->relid;

        $this->info = $info;

        $this->type = unserialize($info->type);
    }

    // Method to get menu entry
    public function getMenu($parent = 0, $design)
    {
        global $var;

        if ($parent == 0) {
            $attributes = "";
            if ($this->isActive()) {
                $attributes = " active";
            }

            if ($design == "bs4") {
                return '<li class="nav-item' . $attributes . '"><a href="' . $this->getLink() . '" class="nav-link">' . $this->menuName . '</a></li>';
            } else {
                return '<li class="nav-item' . $attributes . '"><a href="' . $this->getLink() . '">' . $this->menuName . '</a></li>';
            }
        } else {
            if ($design == "bs4") {
                return '<a href="' . $this->getLink() . '" class="dropdown-item' . ($this->isActive() ? ' active' : '') . '">' . $this->menuName . '</a>';
            } else {
                return '<li' . ($this->isActive() ? ' class="active"' : '') . '><a href="' . $this->getLink() . '">' . $this->menuName . '</a></li>';
            }
        }
    }

    abstract public function isActive();

    abstract public function getLink();

    abstract public function isInit();

    abstract public function getName();

    public function getMenuName()
    {
        return $this->menuName;
    }
}

// Class for content management system
class CMS
{
    // Gets entries by parent
    public function getMenu($design = "bs3")
    {
        global $CFG;

        $code = "";
        $entries = $this->getEntries();

        if (is_array($entries)) {
            $i = 0;

            foreach ($entries as $k => $v) {
                $t = unserialize($v['type']);
                $v['name'] = unserialize($v['name'])[$CFG['LANG']];

                if ($t['type'] == "menu") {
                    $active = 0;
                    $tempCode = "";

                    if ($design == "bs4") {
                        $i++;

                        $tempCode .= "<a class=\"nav-link dropdown-toggle\" href=\"#\" id=\"navbarDropdown$i\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">";
                        $tempCode .= $v['name'];
                        $tempCode .= "</a>";
                        $tempCode .= "<div class=\"dropdown-menu\" aria-labelledby=\"navbarDropdown$i\">";
                    } else {
                        $tempCode .= "<a href='#' class='dropdown-toggle' data-toggle='dropdown'>" . $v['name'] . "</a>";
                        $tempCode .= '<ul class="dropdown-menu" role="menu">';
                    }

                    $entries = $this->getEntries($v['id']);

                    if (is_array($entries)) {
                        foreach ($entries as $k => $v) {
                            $tempCode .= $this->getHTML($v, $design);
                            if ($this->isActive($v)) {
                                $active = 1;
                            }

                        }
                    }

                    if ($design == "bs4") {
                        $tempCode .= "</div></li>";
                    } else {
                        $tempCode .= "</ul>";
                        $tempCode .= '</li>';
                    }

                    if ($active == 1) {
                        if ($design == "bs4") {
                            $code .= '<li class="nav-item dropdown active">';
                        } else {
                            $code .= '<li class="nav-item active">';
                        }
                    } else {
                        if ($design == "bs4") {
                            $code .= '<li class="nav-item dropdown">';
                        } else {
                            $code .= '<li class="nav-item">';
                        }
                    }

                    $code .= $tempCode;
                } else {
                    $code .= $this->getHTML($v, $design);
                }
            }
        }

        return $code;
    }

    // Get menu code
    public function getEntries($parent = 0)
    {
        // Global some variables for security reasons
        global $db, $var, $CFG;

        // Escape @var parent
        $parent = abs(intval($parent));

        // Logged in users should see pages for logged in users also
        $logged = "IN (1, 3)";
        if ($var['logged_in']) {
            $logged = "IN (1, 2)";
        }

        // Iterate entries into array
        $arr = array();
        $sql = $db->query("SELECT * FROM cms_menu WHERE parent = '$parent' AND status $logged ORDER BY prio ASC, name ASC");

        while ($menuEntry = $sql->fetch_object()) {
            $arr[] = array("name" => $menuEntry->name, "parent" => $menuEntry->parent, "type" => $menuEntry->type, "id" => $menuEntry->ID, "relid" => $menuEntry->relid);
        }

        if (count($arr) <= 0) {
            return false;
        }

        return $arr;
    }

    // Get HTML code
    public function getHTML($v, $design)
    {
        $obj = $this->getObject($v);
        if (!is_object($obj)) {
            return "";
        }

        return $obj->getMenu($v['parent'], $design);
    }

    // Check if active
    public function getObject($v)
    {
        switch (unserialize($v['type'])['type']) {
            case 'link':
                $obj = new CMSMenuEntryLink($v['id']);
                break;

            case 'page':
                $obj = new CMSMenuEntryPage($v['id']);
                break;

            case 'divider':
                $obj = new CMSMenuDivider($v['parent']);
                break;
        }

        return $obj;
    }

    // Get object based by type

    public function isActive($v)
    {
        $obj = $this->getObject($v);
        if (!is_object($obj)) {
            return "";
        }

        return $obj->isActive();
    }
}

// Menu entry class for pages
class CMSMenuEntryPage extends CMSMenuEntry
{
    // Method to check if all is initialized correct
    public function isInit()
    {
        return isset($this->type['page']);
    }

    // Method to check if page is current page
    public function isActive()
    {
        // Global some variables for security reasons
        global $page, $pars;

        $my = array_merge([$page], $pars ?: []);
        $ex = explode("/", ltrim($this->type['page'], "/"));
        return $ex === $my;
    }

    // Method to get name
    public function getName()
    {
        return $this->name;
    }

    // Method to get slug
    public function getLink()
    {
        global $CFG;
        return rtrim($CFG['PAGEURL'], "/") . "/" . ltrim($this->type['page'], "/");
    }

}

// Menu entry class for links
class CMSMenuEntryLink extends CMSMenuEntry
{
    // Method to check if all is initialized correct
    public function isInit()
    {
        return isset($this->type['link']);
    }

    // Method to check if page is current page
    public function isActive()
    {
        return false;
    }

    // Method to get name
    public function getName()
    {
        return $this->name;
    }

    // Method to get slug
    public function getLink()
    {
        return $this->type['link'] . ($this->type['target'] ? '" target="' . $this->type['target'] : "");
    }

}

// Menu entry class for divider
class CMSMenuDivider extends CMSMenuEntry
{
    protected $parentId = null;

    // Constructor check if it is not a parent
    public function __construct($parentId)
    {
        $this->parentId = $parentId;
    }

    // Gives HTML output
    public function getMenu($parent = 0, $design)
    {
        if ($design == "bs4") {
            return $this->parentId > 0 ? "<div class=\"dropdown-divider\"></div>" : "";
        } else {
            return $this->parentId > 0 ? "<li class=\"divider\"></li>" : "";
        }
    }

    // Abstract functions
    public function isInit()
    {
        return true;
    }

    public function isActive()
    {
        return false;
    }

    public function getName()
    {
        return false;
    }

    public function getLink()
    {
        return false;
    }
}
