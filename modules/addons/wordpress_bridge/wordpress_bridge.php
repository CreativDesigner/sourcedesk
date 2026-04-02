<?php
// Addon for providing data for WordPress Bridge

class WordPressBridgeAddon extends Addon
{
    public static $shortName = "wordpress_bridge";

    public function __construct($language)
    {
        $this->language = $language;
        $this->name = self::$shortName;
        parent::__construct();

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;

        $this->info = array(
            'name' => $this->getLang("NAME"),
            'version' => "1.1",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function activate()
    {
        global $db, $CFG;
        parent::activate();

        $key = "";
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $chars .= strtoupper($chars);
        $chars .= "1234567890";

        while (strlen($key) < 16) {
            $key .= substr($chars, rand(0, strlen($chars) - 1), 1);
        }

        $key = encrypt($key);

        $db->query("INSERT INTO `settings` (`key`, `value`) VALUES ('wordpress_bridge_key', '" . $db->real_escape_string($key) . "')");
    }

    public function deactivate()
    {
        global $db, $CFG;
        parent::deactivate();

        $db->query("DELETE FROM `settings` WHERE `key` = 'wordpress_bridge_key'");
    }

    public function getSettings()
    {
        return [];
    }

    public function hooks()
    {
        return [
            ["FrontendFooter", "footer", 0],
        ];
    }

    public function clientPages()
    {
        return [
            "wordpress_bridge" => "client",
        ];
    }

    public function adminPages()
    {
        return [
            "wordpress_bridge" => "admin",
        ];
    }

    public function adminMenu()
    {
        return [
            "WordPress Bridge" => "wordpress_bridge",
        ];
    }

    public function footer()
    {
        global $raw_cfg;
        return '<script type="text/javascript" src="' . $raw_cfg['PAGEURL'] . '/modules/addons/wordpress_bridge/iframeResizer.js"></script>';
    }

    public function admin()
    {
        global $tpl, $var, $CFG;

        $var['url'] = rtrim($CFG['PAGEURL'], "/");
        $var['key'] = decrypt($CFG['WORDPRESS_BRIDGE_KEY']);
        $var['l'] = $this->getLang();
        $tpl = __DIR__ . "/templates/admin.tpl";
    }

    public function client()
    {
        global $pars, $CFG;

        if (empty($pars[0])) {
            die(json_encode(["error" => "No authorization token in request"]));
        }

        if ($pars[0] != decrypt($CFG['WORDPRESS_BRIDGE_KEY'])) {
            die(json_encode(["error" => "Wrong authorization token"]));
        }

        if (empty($pars[1])) {
            die(json_encode(["error" => "No action sent"]));
        }

        $m = "api_" . strtolower($pars[1]);
        if (!method_exists($this, $m)) {
            die(json_encode(["error" => "Action unknown"]));
        }

        $this->$m();
    }

    public function api_hw()
    {
        die(json_encode(array("msg" => "Hello World!")));
    }

    public function api_products()
    {
        global $db, $CFG, $cur, $nfo, $raw_cfg, $dfo;

        $res = [];
        $sql = $db->query("SELECT * FROM products WHERE status = 1");
        while ($row = $sql->fetch_object()) {
            $res[$row->ID] = [
                "price" => $cur->infix($nfo->format($row->price), $cur->getBaseCurrency()),
                "price_raw" => $row->price,
                "buy_url" => $raw_cfg["PAGEURL"] . "cart?add_product=" . $row->ID . "&nm=1",
                "category_id" => $row->category,
            ];

            $name = @unserialize($row->name) ?: $row->name;
            $desc = @unserialize($row->description) ?: $row->description;
            if (!is_array($name)) {
                $res[$row->ID]["name"] = $name;
                $res[$row->ID]["description"] = $description;
            } else {
                foreach ($name as $l => $n) {
                    @include __DIR__ . "/../../../languages/" . basename($l) . ".php";

                    $res[$row->ID]["name_" . $l] = $n;
                    $res[$row->ID]["description_" . $l] = $desc[$l];

                    if (empty($row->billing) || $row->billing == "onetime") {
                        $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $row->price, $cur->getBaseCurrency(), true, true))), $lang['BUY']['LONG']);
                    } else {
                        $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $row->price, $cur->getBaseCurrency(), true, true) * $factor)), $lang['BUY'][strtoupper($p->billing)]);
                    }

                    $res[$row->ID]["buy_string_$l"] = $buystring;

                    if ($l == $CFG['LANG']) {
                        $res[$row->ID]["name"] = $n;
                        $res[$row->ID]["description"] = $desc[$l];
                        $res[$row->ID]["buy_string"] = $buystring;
                    }

                    $csql = $db->query("SELECT currency_code FROM currencies");
                    while ($c = $csql->fetch_object()) {
                        if (empty($row->billing) || $row->billing == "onetime") {
                            $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $row->price, $c->currency_code, true, true)), $cur->getCurrency($c->currency_code)), $lang['BUY']['LONG']);
                        } else {
                            $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $row->price, $c->currency_code, true, true)), $cur->getCurrency($c->currency_code)), $lang['BUY'][strtoupper($p->billing)]);
                        }

                        $cc = strtolower($c->currency_code);
                        $res[$row->ID]["buy_string_{$cc}_{$l}"] = $buystring;
                        $res[$row->ID]["price_{$cc}"] = $cur->infix($nfo->format($cur->convertAmount(null, $row->price, $c->currency_code, true, true)), $cur->getCurrency($c->currency_code));
                        $res[$row->ID]["price_{$cc}_raw"] = $cur->convertAmount(null, $row->price, $c->currency_code, true, true);

                        $res[$row->ID]["setup_{$cc}"] = $cur->infix($nfo->format($cur->convertAmount(null, $row->setup, $cc, true, true)), $cur->getCurrency($cc));
                        $res[$row->ID]["setup_{$cc}_raw"] = $cur->convertAmount(null, $row->setup, $cc, true, true);

                        if ($l == $CFG['LANG']) {
                            $res[$row->ID]["buy_string_{$cc}"] = $res[$row->ID]["buy_string_{$cc}_{$l}"];
                        }

                        $variants = @unserialize($row->variants);
                        if (is_array($variants) && count($variants)) {
                            foreach ($variants as $k => $v) {
                                $res[$row->ID]["variant_{$k}_price"] = $cur->infix($nfo->format($v['price']), $cur->getBaseCurrency());
                                $res[$row->ID]["variant_{$k}_price_raw"] = $v['price'];
                                $res[$row->ID]["variant_{$k}_setup"] = $cur->infix($nfo->format($v['setup']), $cur->getBaseCurrency());
                                $res[$row->ID]["variant_{$k}_setup_raw"] = $v['setup'];
                                $res[$row->ID]["variant_{$k}_billing"] = $v['billing'];

                                if (empty($v['billing']) || $v['billing'] == "onetime") {
                                    $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $v['price'], $c->currency_code, true, true)), $cur->getCurrency($c->currency_code)), $lang['BUY']['LONG']);
                                } else {
                                    $buystring = str_replace("%p", $cur->infix($nfo->format($cur->convertAmount(null, $v['price'], $c->currency_code, true, true)), $cur->getCurrency($c->currency_code)), $lang['BUY'][strtoupper($v['billing'])]);
                                }

                                $cc = strtolower($c->currency_code);
                                $res[$row->ID]["variant_{$k}_buy_string_{$cc}_{$l}"] = $buystring;
                                $res[$row->ID]["variant_{$k}_price_{$cc}"] = $cur->infix($nfo->format($cur->convertAmount(null, $v['price'], $c->currency_code, true, true)), $cur->getCurrency($c->currency_code));
                                $res[$row->ID]["variant_{$k}_price_{$cc}_raw"] = $cur->convertAmount(null, $v['price'], $c->currency_code, true, true);

                                $res[$row->ID]["variant_{$k}_setup_{$cc}"] = $cur->infix($nfo->format($cur->convertAmount(null, $v['setup'], $cc, true, true)), $cur->getCurrency($cc));
                                $res[$row->ID]["variant_{$k}_setup_{$cc}_raw"] = $cur->convertAmount(null, $v['setup'], $cc, true, true);

                                if ($l == $CFG['LANG']) {
                                    $res[$row->ID]["variant_{$k}_buy_string_{$cc}"] = $res[$row->ID]["varint_{$k}_buy_string_{$cc}_{$l}"];
                                }
                            }
                        }
                    }
                }
            }

            $res[$row->ID]["setup"] = $cur->infix($nfo->format($cur->convertAmount(null, $row->setup, null, true, true)), $cur->getBaseCurrency());
            $res[$row->ID]["setup_raw"] = $row->setup;

            $res[$row->ID]["available"] = $row->available < 0 ? -1 : $row->available;
            $res[$row->ID]["billing"] = $row->billing ?: "onetime";
        }

        die(json_encode($res));
    }
}
