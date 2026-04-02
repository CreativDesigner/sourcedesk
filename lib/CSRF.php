<?php
// Class for handling CSRF tokens

class CSRF
{
    private static $session_field = "csrf_token";
    private static $form_field = "csrf_token";
    private static $token_length = 96;

    private static function getToken()
    {
        return array_key_exists(self::$session_field, $_SESSION) ? $_SESSION[self::$session_field] : "";
    }

    private static function newToken()
    {
        $_SESSION[self::$session_field] = bin2hex(random_bytes(self::$token_length));
    }

    public static function init()
    {
        if (empty(self::getToken())) {
            self::newToken();
        }
    }

    public static function raw()
    {
        return self::getToken();
    }

    public static function html()
    {
        return '<input type="hidden" name="' . self::$form_field . '" value="' . self::getToken() . '">';
    }

    private static function routeMatch($route)
    {
        global $CFG;

        $routes = $CFG['CSRF_DISABLED'];
        $ex = explode(",", $routes);

        foreach ($ex as $v) {
            $v = trim($v);

            if (empty($v)) {
                continue;
            }

            if ($v == substr($route, 0, strlen($v))) {
                return true;
            }
        }

        return false;
    }

    public static function validate()
    {
        if (!is_array($_POST) || count($_POST) === 0) {
            return;
        }

        if (!empty($_GET['p']) && in_array($_GET['p'], ["dns", "adminer"])) {
            return;
        }

        $route = "/" . $_GET['p'];

        if (self::routeMatch($route)) {
            return;
        }

        if (!array_key_exists(self::$form_field, $_POST) || $_POST[self::$form_field] != self::getToken()) {
            self::stop();
        }
    }

    private static function stop()
    {
        global $smarty, $lang, $var;

        $var['error'] = $lang['GENERAL']['CSRF'];

        if ($smarty instanceof SmartyEngine) {
            $smarty->show("error");
        } else {
            self::stopWithoutSmarty();
        }

        exit;
    }

    public static function auto(&$html)
    {
        $offset = 0;

        while (($pos = stripos($html, "<form ", $offset)) !== false) {
            $offset = $pos + 25;

            $tag = substr($html, $pos);
            $pos2 = strpos($tag, ">") + 1;
            $tag = substr($tag, 0, $pos2);

            if (stripos($tag, 'method="POST"') !== false || stripos($tag, "method='POST'") !== false) {
                $tag .= CSRF::html();
                $html = substr($html, 0, $pos) . $tag . substr($html, $pos + $pos2);
            }
        }
    }

    private static function stopWithoutSmarty()
    {
        global $CFG, $raw_cfg, $lang;

        ?>
        <!DOCTYPE html>
        <html>
            <head>
                <title>Intrusion Detection System :: <?=$CFG['PAGENAME'];?></title>
                <link rel="shortcut icon" href="<?=$raw_cfg['PAGEURL'];?>themes/favicon.ico" type="image/x-icon" />

                <style>
                body {
                    background: #f3f3f3;
                }

                .box {
                    margin: auto;
                    margin-top: 50px;
                    max-width: 500px;
                    width: 100%;
                    -webkit-box-shadow: 3px 3px 8px 4px #ccc;
                    -moz-box-shadow: 3px 3px 8px 4px #ccc;
                    box-shadow: 3px 3px 8px 4px #ccc;
                    border: black 1px solid;
                    border-radius: 10px;
                    line-height: 1.4;
                    text-align: center;
                    font-family: 'Lucida Grande', sans-serif;
                }

                .box h4 {
                    margin: 0;
                    padding: 10px 0;
                    border-bottom: black 1px solid;
                }

                .box p {
                    margin: 10px 0;
                    padding: 5px;
                    padding-bottom: 10px;
                    border-bottom: black 1px solid;;
                }

                .box footer {
                    font-size: 10px;
                    padding-bottom: 10px;
                }
                </style>
            </head>
            <body>
                <div class="box">
                    <h4>Intrusion Detection System</h4>
                    <p><?=$lang['GENERAL']['CSRF'];?></p>
                    <footer>Rule 8184 (XSS)<br />&copy; Copyright sourceIDS <?=date("Y");?></footer>
                </div>
            </body>
        </html>
        <?php
}
}