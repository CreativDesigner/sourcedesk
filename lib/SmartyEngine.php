<?php

// Class to display page with Smarty

class SmartyEngine
{

    // @property tpl for instance of Smarty
    public $template = null;
    protected $tpl = null;

    // Constructor of SmartyEngine

    public function __construct()
    {
        global $CFG;

        // Require Smarty class and create an private instance of
        require_once __DIR__ . '/smarty/Smarty.class.php';
        $this->tpl = new Smarty;
        $this->tpl->setCompileDir('./templates/compiled');
        if ($CFG['TRIM_WHITESPACE']) {
            $this->tpl->loadFilter('output', 'trimwhitespace');
        }

    }

    // Method to display a page
    // Requires the title and template (without extension)

    public function show($pTpl)
    {
        global $lang, $addons, $title, $tpl, $var;

        // Check if controller exists
        if (file_exists(__DIR__ . "/../controller/$pTpl.php")) {
            // Require the specified controller and display the page with title and template name from controller
            require_once __DIR__ . "/../controller/$pTpl.php";
            $this->displayPage($title, $tpl);
        } else if ($addons->routePage($pTpl, "client", false)) {
            $this->displayPage($title, $tpl);
        } else {
            // If specified controller does not exist, display the error page
            $this->displayPage($lang['ERROR']['TITLE'], "error");
        }
    }

    // Method to show a page within a controller

    protected function displayPage($pTitle, $pTpl)
    {
        // Global template variables for security reasons
        global $var, $cms, $lang;

        $this->template = $pTpl;

        // Assign title to template engine (for HTML title tag in layout template)
        $this->tpl->assign('title', $pTitle);

        // If template does not exists, prevent Smarty error message (because of include) and display the index page
        if (file_exists(__DIR__ . "/../themes/" . $var['layout'] . "/templates/$pTpl.tpl")) {
            $this->tpl->assign('tpl', realpath(__DIR__ . "/../themes/" . $var['layout'] . "/templates/$pTpl.tpl"));
        } else if (file_exists(__DIR__ . "/../templates/$pTpl.tpl")) {
            $this->tpl->assign('tpl', realpath(__DIR__ . "/../templates/$pTpl.tpl"));
        } else if (file_exists($pTpl) && is_readable($pTpl)) {
            $this->tpl->assign('tpl', realpath($pTpl));
        } else {
            return $this->show("index");
        }

        // Parse menu into template variable
        $var['menu_code'] = $cms->getMenu();
        $var['menu_code_bs4'] = $cms->getMenu("bs4");

        // Assign all default variables to template engine
        if (isset($var) && is_array($var)) {
            foreach ($var as $k => $v) {
                $this->tpl->assign($k, $v);
            }
        }

        // Case analysis for themes
        try {
            if (file_exists(__DIR__ . "/../themes/" . $var['layout'] . "/templates/layout.tpl")) {
                $html = $this->tpl->fetch(__DIR__ . "/../themes/" . $var['layout'] . "/templates/layout.tpl");
            } else {
                $html = $this->tpl->fetch('layout.tpl');
            }
        } catch (Exception $ex) {
            $file = substr($ex->getMessage(), 26, strpos($ex->getMessage(), '"', 26) - 26);
            if (strpos($file, "layout") !== false) {
                die($lang['GENERAL']['SYNTAX_ERROR'] . "<br /><br /><i>" . htmlentities($ex->getMessage()) . "</i>");
            }

            $this->tpl->assign('tpl', 'error.tpl');
            $this->tpl->assign('error', $lang['GENERAL']['SYNTAX_ERROR']);
            $this->tpl->assign('debug', $ex->getMessage());

            try {
                if (file_exists(__DIR__ . "/../themes/" . $var['layout'] . "/templates/layout.tpl")) {
                    $html = $this->tpl->fetch("../themes/" . $var['layout'] . "/templates/layout.tpl");
                } else {
                    $html = $this->tpl->fetch('layout.tpl');
                }

            } catch (Exception $ex) {
                die($lang['GENERAL']['SYNTAX_ERROR']);
            }
        }

        CSRF::auto($html);
        echo $html;
    }

    // Method to register functions

    public function register($a, $b)
    {
        return $this->tpl->registerPlugin("function", $a, $b);
    }

    // Smarty function to display a product page
    public function product_view($params)
    {
        global $pars, $noHeader, $noCurrency;
        $productId = $params['p'];
        if (isset($params['h']) && $params['h'] == 0) {
            $noHeader = true;
        }

        if (isset($params['c']) && $params['c'] == 0) {
            $noCurrency = true;
        }

        $pars = array($productId);
        require __DIR__ . "/../controller/product.php";
        foreach ($var as $k => $v) {
            $this->tpl->assign($k, $v);
        }

        return $this->tpl->fetch(__DIR__ . "/../templates/" . $tpl . ".tpl");
    }

    // Smarty function to display a group page
    public function group_view($params)
    {
        global $pars, $noHeader, $noCurrency;
        $groupId = $params['g'];
        if (isset($params['h']) && $params['h'] == 0) {
            $noHeader = true;
        }

        if (isset($params['c']) && $params['c'] == 0) {
            $noCurrency = true;
        }

        $pars = array($groupId);
        require __DIR__ . "/../controller/cat.php";
        foreach ($var as $k => $v) {
            $this->tpl->assign($k, $v);
        }

        return $this->tpl->fetch(__DIR__ . "/../templates/" . $tpl . ".tpl");
    }

    public function fetch($a, $b)
    {
        return $this->tpl->fetch($a, $b);
    }

    public function setCompileDir($d)
    {
        $this->tpl->setCompileDir($d);
    }
}
