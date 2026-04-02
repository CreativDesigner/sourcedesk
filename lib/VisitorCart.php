<?php

// Class for managing carts for visitors without any login session

class VisitorCart
{
    protected $elements = null;
    protected $count = null;
    protected $voucher = null;
    protected $voucher_tmp = null;

    // Constructor gets cart elements from session or cookie
    public function __construct()
    {
        // Global some variables for security reasons
        global $db, $CFG;

        // Apply voucher
        if (isset($_SESSION['voucher']) && ($sql = $db->query("SELECT ID FROM vouchers WHERE code = '" . $db->real_escape_string($_SESSION['voucher']) . "' LIMIT 1")) && $sql->num_rows == 1) {
            $this->addVoucher($sql->fetch_object()->ID);
        }

        $this->buildPArray();
    }

    // Method to push to piwik
    public function piwik()
    {
        global $CFG, $db, $var, $lang;

        $cart = $this->get();

        $sum = 0;
        foreach ($cart as $v) {
            if ($v['type'] == "product") {
                $id = "PRODUCT-" . $v['relid'];
            } else if ($v['type'] == "bundle") {
                $id = "BUNDLE-" . $v['relid'];
            } else if ($v['type'] == "domain_reg") {
                $id = "REGISTER-";
            } else if ($v['type'] == "domain_in") {
                $id = "TRANSFER-";
            }

            if ($v['type'] == "domain_reg" || $v['type'] == "domain_in") {
                $ex = explode(".", unserialize($v['license'])['domain']);
                $sld = array_shift($ex);
                $tld = implode(".", $ex);
                $id .= str_replace(".", "-", $tld);
            }

            $name = unserialize($v['name'])[$CFG['LANG']];
            if ($v['type'] == "product" && $v['license'] == "r") {
                $name .= " ({$lang['CART']['ISRESELLER']})";
            }

            if ($v['type'] == "domain_reg") {
                $name = "{$lang['CART']['ISREG']}: $sld.$tld";
            }

            if ($v['type'] == "domain_in") {
                $name = "{$lang['CART']['ISTRANS']}: $sld.$tld";
            }

            $cat = "";
            if ($v['type'] == "product") {
                $sql = $db->query("SELECT category FROM products WHERE ID = " . intval($v['relid']));
                if ($sql->num_rows == 1) {
                    $catid = $sql->fetch_object()->category;
                    $sql = $db->query("SELECT name FROM product_categories WHERE ID = " . intval($catid));
                    if ($sql->num_rows == 1) {
                        $cat = unserialize($sql->fetch_object()->name)[$CFG['LANG']];
                    }

                }
            }

            $price = $v['amount'];
            if ($v['type'] == "PRODUCT") {
                $price += $v['setup'];
            }

            $qty = $v['qty'];
            $sum += $price * $qty;

            $var['pcomm'] .= "_paq.push(['addEcommerceItem','$id','$name','$cat',$price,$qty]);";
        }

        $var['pcomm'] .= "_paq.push(['trackEcommerceCartUpdate',$sum]);";
    }

    // Method to rebuild elements

    public function addVoucher($relid)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT code FROM vouchers WHERE ID = " . intval($relid) . " LIMIT 1");
        if (!$sql->num_rows) {
            return false;
        }

        $this->voucher = $relid;
        $_SESSION['voucher'] = $sql->fetch_object()->code;
        $this->buildPArray();
    }

    // Method to build product array

    private function buildPArray($rebuild = false)
    {
        // Global some variables for security reasons
        global $db, $CFG, $currencies, $cur;

        if ($rebuild) {
            $elements = $this->elements;
        } else {
            $elements = $this->localElements();
        }

        // Iterate the cart entries and fill @var elements and count @var count
        $this->count = 0;
        $this->elements = array();
        foreach ($elements as $id => $item) {
            $qty = $item['qty'];
            $item = (object) $item;
            $item->ID = $id;

            if ($item->type == "product") {
                // In case item is a product, we select all necessary information from product table
                $sql = $db->query("SELECT * FROM products WHERE status = 1 AND ID = " . $item->relid);
                if ($sql->num_rows != 1) {
                    continue;
                }

                $info = $sql->fetch_object();

                $price = $info->price;

                // Variants
                $setup = 0;
                $a = unserialize($item->additional);
                $variant = "";
                if (is_array($a) && array_key_exists("variant", $a) && substr($a["variant"], 0, 1) == "v") {
                    $variants = @unserialize($info->variants);
                    if (is_array($variants) && array_key_exists(substr($a["variant"], 1), $variants)) {
                        $variant = substr($a["variant"], 1);
                        $d = $variants[$variant];

                        $price = $d['price'];
                        $isBase = false;
                        $code = "";

                        foreach ($currencies as $k => $v) {
                            if ($v["ID"] == $d["currency"]) {
                                $code = $k;
                                $isBase = boolval($d["base"]);
                                break;
                            }
                        }

                        if (!$isBase && $code) {
                            $price = $cur->convertAmount($code, $price, $cur->getBaseCurrency());
                        }

                        $setup += $d['setup'];

                        $info->billing = $d['billing'];
                    }
                }

                if ($variant === "") {
                    $setup += $info->setup;
                }

                $item->license = "h";

                $price = Product::getClientPrice($price, $info->tax);
                $setup = Product::getClientPrice($setup, $info->tax);

                // Check if a voucher applies to the product
                $voucherInfo = $this->getVoucher($item->license == "r" ? "reseller" : "product", $item->relid);
                $oldprice = 0;
                if ($voucherInfo !== false) {
                    $oldprice = $price;
                    if ($voucherInfo->type == "percentage") {
                        $price = floor($price * (1 - ($voucherInfo->value / 100)) * 100) / 100;
                    } else {
                        $price -= $voucherInfo->value;
                    }

                    if ($price < 0) {
                        $price = 0;
                    }

                }

                // Get extra pricing because of configoptions
                if (is_array($a) && array_key_exists("customfields", $a) && count($a['customfields']) > 0) {
                    $cf = $a['customfields'];
                    foreach ($cf as $id => $v) {
                        $cfSql = $db->query("SELECT * FROM products_cf WHERE ID = " . intval($id));
                        if ($cfSql->num_rows != 1) {
                            continue;
                        }

                        $i = $cfSql->fetch_object();
                        $o = (object) unserialize($i->options);

                        if ($o->amount != 0 && $i->type == "number") {
                            if ($o->onetime) {
                                $setup += $o->amount * $v;
                            } else {
                                $price += $o->amount * $v;
                                if ($oldprice > 0) {
                                    $oldprice += $o->amount * $v;
                                }
                            }
                        } else if ($i->type == "select") {
                            $values = explode("|", $o->values);
                            $key = array_search($v, $values) ?: 0;
                            $more = explode("|", $o->costs)[$key] ?: 0;

                            if ($o->onetime) {
                                $setup += $more;
                            } else {
                                $price += $more;
                                if ($oldprice > 0) {
                                    $oldprice += $more;
                                }
                            }
                        } else if ($i->type == "radio") {
                            $values = explode("|", $o->values);
                            $key = array_search($v, $values) ?: 0;
                            $more = explode("|", $o->costs)[$key] ?: 0;

                            if ($o->onetime) {
                                $setup += $more;
                            } else {
                                $price += $more;
                                if ($oldprice > 0) {
                                    $oldprice += $more;
                                }
                            }
                        } else if ($i->type == "check" && $v) {
                            if ($o->onetime) {
                                $setup += $o->costs;
                            } else {
                                $price += $o->costs;
                                if ($oldprice > 0) {
                                    $oldprice += $o->costs;
                                }
                            }
                        }
                    }
                }

                $sum = $price * $qty;

                $billing = $info->billing;

                $this->elements[$item->ID] = array("qty" => $qty, "ID" => $item->ID, "type" => $item->type, "added" => $item->added, "user" => 0, "relid" => $item->relid, "name" => $info->name, "amount" => $price, "sum" => $price * $qty, "license" => $item->license, "oldprice" => $oldprice, "billing" => $billing, "prepaid" => $info->prepaid, "setup" => $setup, "ptype" => $info->type, "additional" => $item->additional, "variant" => $variant, "prorata" => $info->prorata);
            } else if ($item->type == "domain_reg" || $item->type == "domain_in") {
                if ($db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string(unserialize($item->license)['domain']) . "' AND status IN ('REG_WAITING', 'KK_WAITING')")->num_rows > 0) {
                    unset($this->elements[$id]);
                    continue;
                }

                $c = !empty($_SESSION['country']) ? $_SESSION['country'] : 0;
                if (!empty($_POST['country']) && is_numeric($_POST['country'])) {
                    $c = $_POST['country'];
                }

                $sql = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . intval($c));
                if ($sql->num_rows == 1) {
                    $couInfo = $sql->fetch_object();
                    if ($tempVat = TempVat::rate($couInfo->alpha2, $couInfo->percent)) {
                        $couInfo->percent = $tempVat;
                    }

                    $tax = $couInfo->percent;
                } else {
                    $couInfo = $db->query("SELECT percent, alpha2 FROM client_countries WHERE ID = " . intval($CFG['DEFAULT_COUNTRY']))->fetch_object();
                    if ($tempVat = TempVat::rate($couInfo->alpha2, $couInfo->percent)) {
                        $couInfo->percent = $tempVat;
                    }

                    $tax = $couInfo->percent;
                }

                $ex = explode(".", unserialize($item->license)['domain']);
                array_shift($ex);
                $tld = implode(".", $ex);

                $sql = $db->query("SELECT register, transfer, privacy FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
                $price = $sql->fetch_object();
                $privacy = $price->privacy;

                $aSql = $db->query("SELECT `type`, `price` FROM domain_actions WHERE `start` <= '" . date("Y-m-d H:i:s") . "' AND `end` >= '" . date("Y-m-d H:i:s") . "' AND tld = '" . $db->real_escape_string(ltrim($tld, ".")) . "'");
                while ($aRow = $aSql->fetch_object()) {
                    $price2 = [
                        "REG" => "register",
                        "RENEW" => "renew",
                        "KK" => "transfer",
                    ][$aRow->type];

                    if ($price->$price2 > $aRow->price) {
                        $price->$price2 = $aRow->price;
                    }
                }

                $price = $item->type == "domain_reg" ? $price->register : $price->transfer;

                $reg_info = unserialize($item->license);
                if (isset($reg_info['privacy']) && $reg_info['privacy'] == "1" && $privacy >= 0) {
                    $price += $privacy;
                }

                $price *= 1 + $tax / 100;

                $oldprice = $price;

                // Domain voucher
                $relidSql = $db->query("SELECT ID FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
                if ($relidSql->num_rows) {
                    $voucherInfo = $this->getVoucher($item->type == "domain_reg" ? "reg" : "kk", $relidSql->fetch_object()->ID);
                    if ($voucherInfo !== false) {
                        if ($voucherInfo->type == "percentage") {
                            $price = floor($price * (1 - ($voucherInfo->value / 100)) * 100) / 100;
                        } else {
                            $price -= $voucherInfo->value;
                        }

                        if ($price < 0) {
                            $price = 0;
                        }
                    }
                }

                $this->elements[$item->ID] = array("added" => $item->added, "user" => 0, "qty" => 1, "ID" => $item->ID, "type" => $item->type, "relid" => $item->relid, "name" => $info->name, "amount" => $price, "sum" => $price, "oldprice" => $oldprice, "license" => $item->license);
            } else if ($item->type == "bundle") {
                // In case item is a product, we select all necessary information from product table
                $sql = $db->query("SELECT * FROM product_bundles WHERE ID = " . $item->relid);
                if ($sql->num_rows != 1) {
                    continue;
                }

                $info = $sql->fetch_object();

                // We get the price
                $price = $info->price;

                $qty = $item->qty;
                $sum = $price * $qty;

                // Build description
                $desc = "";
                $org = unserialize($info->products);
                $products = array();
                foreach ($org as $pid) {
                    $sql = $db->query("SELECT name FROM products WHERE ID = " . intval($pid));
                    if ($sql->num_rows != 1) {
                        continue;
                    }

                    $pinfo = $sql->fetch_object();
                    $names = unserialize($pinfo->name);
                    $products[$pid] = $names[$CFG['LANG']];
                }
                asort($products);

                foreach ($products as $p) {
                    $desc .= "+ $p<br />";
                }

                $desc = substr($desc, 0, -6);

                $this->elements[$item->ID] = array("qty" => $qty, "ID" => $item->ID, "type" => $item->type, "added" => $item->added, "user" => 0, "relid" => $item->relid, "name" => $info->name, "amount" => $price, "sum" => $sum, "license" => $item->license, "oldprice" => $price, "desc" => $desc);
            }

            $this->count += $qty;
        }
    }

    // Method to check if a voucher was already used by this user more often than allowed

    public static function localElements()
    {
        // Global local streams
        global $session, $_SESSION, $db, $CFG;

        $items = array();

        if (is_array(unserialize($session->get('cart')))) {
            foreach (unserialize($session->get('cart')) as $id => $item) {
                $items[$id] = $item;
            }
        }

        if (isset($_SESSION['cart'])) {
            if (is_array(unserialize($_SESSION['cart']))) {
                foreach (unserialize($_SESSION['cart']) as $id => $item) {
                    $items[$id] = $item;
                }
            }
        }

        if (is_string($session->get('voucher'))) {
            $voucherSql = $db->query("SELECT ID FROM vouchers WHERE code = '" . $db->real_escape_string($session->get('voucher')) . "' LIMIT 1");
            if ($voucherSql->num_rows == 1) {
                $items[self::genId()] = array("relid" => $voucherSql->fetch_object()->ID, "type" => "voucher");
            }

        }

        if (is_string($_COOKIE['voucher'])) {
            $voucherSql = $db->query("SELECT ID FROM vouchers WHERE code = '" . $db->real_escape_string($_COOKIE['voucher']) . "' LIMIT 1");
            if ($voucherSql->num_rows == 1) {
                $items[self::genId()] = array("relid" => $voucherSql->fetch_object()->ID, "type" => "voucher");
            }

        }

        return $items;
    }

    // Destructor saves elements

    private static function genId()
    {
        return rand(100000000, 999999999);
    }

    // Method to change the quantity of a product

    public function getVoucher($type = "product", $relid = 0)
    {
        global $db, $CFG;

        if (!$this->voucher) {
            return false;
        }

        $sql = $db->query("SELECT * FROM vouchers WHERE ID = '" . intval($this->voucher) . "'");
        if ($sql->num_rows != 1) {
            $this->removeVoucher();
            return false;
        }

        $info = $sql->fetch_object();
        if (($info->valid_to < time() && $info->valid_to > 0) || ($info->valid_from > time() && $info->valid_from > 0) || $info->active != 1 || ($info->max_uses <= $info->uses && $info->max_uses >= 0) || $info->user != 0) {
            $this->removeVoucher();
            return false;
        }

        // Modify $info->value to 0 if voucher is not valid for supplied product
        try {
            if ($info->valid_for == "all") {
                throw new Exception("ok");
            }

            if ($relid <= 0) {
                throw new Exception();
            }

            if ($type != "product" && $type != "reseller" && $type != "reg" && $type != "kk") {
                throw new Exception();
            }

            $valid_for = unserialize($info->valid_for);
            if (!$valid_for || !is_array($valid_for)) {
                throw new Exception();
            }

            if (!in_array($type . $relid, $valid_for)) {
                throw new Exception();
            }

        } catch (Exception $ex) {
            if ($ex->getMessage() != "ok") {
                $info->value = 0;
            }

        }

        return (object) $info;
    }

    // Method to get the active voucher

    public function removeVoucher()
    {
        $this->voucher = null;
        $_SESSION['voucher'] = null;
        $this->buildPArray();
        return true;
    }

    // Method to add voucher

    public function rebuild()
    {
        $this->buildPArray(true);
    }

    // Method to remove the active voucher

    public function checkVoucherUsage($code = false, $relid = false)
    {
        return true;
    }

    // Delete all items from the cart

    public function __destruct()
    {
        $_SESSION['cart'] = serialize($this->elements);
    }

    // Get all elements in cart

    public function changeQty($id, $qty)
    {
        global $db, $CFG;
        if (!isset($this->elements[$id])) {
            return false;
        }

        $element = &$this->elements[$id];
        $oldQty = $element['qty'];
        if (!empty($element['additional']) && !in_array($qty, [0, 1])) {
            return false;
        }

        if (($element['license'] == "e" || $element['license'] == "h") && is_numeric($qty)) {
            if ($qty <= 0) {
                unset($this->elements[$id]);
                $this->count -= $oldQty;
            } else {
                if ($element['type'] == "product") {
                    $maxpc = intval($db->query("SELECT maxpc FROM products WHERE ID = " . $element['relid'])->fetch_object()->maxpc);
                    $available = intval($db->query("SELECT available FROM products WHERE ID = " . $element['relid'])->fetch_object()->available);

                    if ($maxpc >= 0) {
                        $qty = min($maxpc, $qty);
                    }

                    if ($available >= 0) {
                        $qty = min($available, $qty);
                    }

                }
                $this->count -= $oldQty;
                $this->count += $qty;
                $element['qty'] = $qty;
                $element['sum'] = $element['amount'] * $qty;
            }
        }
    }

    // Count the elements in cart

    public function null()
    {
        $this->elements = array();
        $this->count = 0;
    }

    // Method to get the elements from session or from cookie

    public function get()
    {
        return $this->elements;
    }

    // Method to generate an ID for a new element

    public function count()
    {
        return $this->count;
    }

    // Method to remove an element

    public function removeElement($id)
    {
        if (isset($this->elements[$id])) {
            $this->count -= $this->elements[$id]['qty'];
            unset($this->elements[$id]);
        }
    }

    // Method to add a new cart entry
    // You can specify the related ID, the type and the license type (last for products)
    public function add($relid, $type = "product", $license = "e", $additional = "")
    {
        // Global some variables for security reasons
        global $db, $CFG;

        $relid = intval($relid);

        // We will select the product from the table
        // Other types will be catched and produce a return of false
        switch ($type) {
            case 'product':
                if ($db->query("SELECT ID FROM products WHERE status = 1 AND ID = $relid LIMIT 1")->num_rows != 1) {
                    return false;
                }

                break;
        }

        if (isset($found)) {
            unset($found);
        }

        if (empty($additional)) {
            foreach ($this->elements as $id => &$info) {
                if ($info['type'] == $type && $info['relid'] == $relid && ($info['license'] == $license) && in_array($type, array("product", "bundle"))) {
                    if (!isset($info['license']) || $info['license'] != "r") {
                        $sql = $db->query("SELECT maxpc FROM products WHERE ID = " . $relid);
                        if ($sql->num_rows == 1) {
                            $max = $sql->fetch_object()->maxpc;
                            if ($info['qty'] + 1 >= $max) {
                                $found = 1;
                                break;
                            }
                        }

                        $info['qty']++;
                        $this->elements[$id] = $info;
                        $this->count++;
                    }
                    $found = 1;
                    break;
                } else if (($type == "domain_reg" || $type == "domain_in") && ($info['type'] == "domain_reg" || $info['type'] == "domain_in") && is_array(unserialize($info['license'])) && unserialize($info['license'])['domain'] == unserialize($license)['domain']) {
                    $info['license'] = $license;
                    $info['type'] = $type;
                    $found = 1;
                }
            }
        }

        if (!isset($found)) {
            $this->elements[$this->genId()] = array("user" => 0, "type" => $type, "relid" => $relid, "added" => time(), "license" => $license, "qty" => 1, "additional" => $additional);
            $this->count++;
        }

        return true;
    }

}
