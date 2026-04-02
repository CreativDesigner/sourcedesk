<?php

// Class for managing cart

class Cart
{

    protected $elements = null;
    protected $userId = null;
    protected $count = null;

    public function __destruct()
    {
    }

    // Constructor gets cart elements from database (you need to specify a user ID or you would get all cart entries)

    public function importSession()
    {
        global $user, $db, $CFG, $session;

        $items = VisitorCart::localElements();
        if (count($items) <= 0) {
            return;
        }

        foreach ($items as $item) {
            $item = (object) $item;
            switch ($item->type) {
                case 'product':
                    if ($item->license != "r" || ($db->query("SELECT ID FROM client_cart WHERE user = '" . $this->userId . "' AND type = 'product' AND license = 'r' AND relid = '" . intval($item->relid) . "'")->num_rows == 0 && $db->query("SELECT ID FROM client_products WHERE user = " . $this->userId . " AND product = " . intval($item->relid) . " AND type = 'r' LIMIT 1")->num_rows == 0)) {
                        if (is_object($sql = $db->query("SELECT ID FROM client_cart WHERE user = '" . $this->userId . "' AND type = 'product' AND license = '" . $db->real_escape_string($item->license) . "' AND relid = '" . intval($item->relid) . "'")) && $sql->num_rows > 0 && empty($item->additional)) {
                            $db->query("UPDATE client_cart SET qty = qty + " . intval($item->qty) . " WHERE ID = " . $sql->fetch_object()->ID . " LIMIT 1");
                        } else {
                            $sql = $db->prepare("INSERT INTO client_cart (`user`, `type`, `relid`, `added`, `license`, `qty`, `additional`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            if ($sql) {
                                $sql->bind_param("isiisis", $this->userId, $item->type, $a = $item->relid, $item->added, $item->license, $item->qty, $item->additional);
                                $sql->execute();
                            }
                        }
                    }
                    break;

                case 'voucher':
                    if ($db->query("SELECT ID FROM client_cart WHERE user = " . $this->userId . " AND type = 'voucher'")->num_rows == 0 && $this->checkVoucherUsage(false, $item->relid)) {
                        $this->addVoucher($item->relid);
                    }

                    break;

                case 'domain_reg':
                case 'domain_in':
                    $f = 0;
                    $sql = $db->query("SELECT license FROM client_cart WHERE user = {$this->userId} AND (type = 'domain_in' OR type = 'domain_reg')");
                    while ($row = $sql->fetch_object()) {
                        if (unserialize($row->license)['domain'] == unserialize($item->license)['domain']) {
                            $f = 1;
                        }
                    }

                    $sql = $db->prepare("INSERT INTO client_cart (`user`, `type`, `relid`, `added`, `license`, `qty`) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($sql && !$f) {
                        $sql->bind_param("isiisi", $this->userId, $item->type, $a = $item->relid, $item->added, $item->license, $item->qty);
                        $sql->execute();
                    }
                    break;

                case 'bundle':
                    if (is_object($sql = $db->query("SELECT ID FROM client_cart WHERE user = '" . $this->userId . "' AND type = 'bundle' AND license = '" . $db->real_escape_string($item->license) . "' AND relid = '" . intval($item->relid) . "'")) && $sql->num_rows > 0) {
                        $db->query("UPDATE client_cart SET qty = qty + " . intval($item->qty) . " WHERE ID = " . $sql->fetch_object()->ID . " LIMIT 1");
                    } else {
                        $sql = $db->prepare("INSERT INTO client_cart (`user`, `type`, `relid`, `added`, `license`, `qty`) VALUES (?, ?, ?, ?, ?, ?)");
                        if ($sql) {
                            $sql->bind_param("isiisi", $this->userId, $item->type, $a = $item->relid, $item->added, $item->license, $item->qty);
                            $sql->execute();
                        }
                    }
                    break;
            }
        }

        $this->__construct();
    }

    // Method to push to piwik
    public function piwik($cartitems = null, $orderId = null)
    {
        global $CFG, $db, $var, $lang;

        if ($cartitems === null) {
            $cart = new Cart($this->userId);
            $cart = $cart->get();
        } else {
            $cart = $cartitems;
        }

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
            } else if ($v['type'] == "update") {
                $id = "UPDATE-";
            }

            if ($v['type'] == "domain_reg" || $v['type'] == "domain_in") {
                $ex = explode(".", unserialize($v['license'])['domain']);
                $sld = array_shift($ex);
                $tld = implode(".", $ex);
                $id .= str_replace(".", "-", $tld);
            } else if ($v['type'] == "update") {
                $sql = $db->query("SELECT product FROM client_products WHERE ID = " . intval($v['relid']));
                if ($sql->num_rows == 1) {
                    $pid = $sql->fetch_object()->product;
                    $id .= $pid;
                    $sql = $db->query("SELECT category FROM products WHERE ID = " . $pid);
                    if ($sql->num_rows == 1) {
                        $catid = $sql->fetch_object()->category;
                        $sql = $db->query("SELECT name FROM product_categories WHERE ID = " . intval($catid));
                        if ($sql->num_rows == 1) {
                            $cat = unserialize($sql->fetch_object()->name)[$CFG['LANG']];
                        }

                    }
                }
            }

            $name = unserialize($v['name'])[$CFG['LANG']];

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

        if ($orderId !== null) {
            $var['pcomm'] .= "_paq.push(['trackEcommerceOrder','$orderId',$sum]);";
        } else {
            $var['pcomm'] .= "_paq.push(['trackEcommerceCartUpdate',$sum]);";
        }

    }

    // Method to check if a voucher was already used by this user more often than allowed

    public function checkVoucherUsage($code = false, $relid = false)
    {
        global $user, $db, $CFG;

        if ($code === false && $relid === false) {
            return false;
        }

        $condition = !$code ? '`relid` = ' . intval($relid) : "`code` = '" . $db->real_escape_string($code) . "'";

        $sql = $db->query("SELECT * FROM vouchers WHERE $condition");
        if ($sql->num_rows != 1) {
            return false;
        }

        $voucherInfo = $sql->fetch_object();

        if ($voucherInfo->max_per_user <= 0) {
            return true;
        }

        $sql = $db->query("SELECT ID FROM invoices WHERE client = $this->userId AND voucher = " . $voucherInfo->ID);

        if ($sql->num_rows > $voucherInfo->max_per_user) {
            return false;
        }

        return true;
    }

    // Method to import information from Session (maybe after login or registration/confirmation)

    public function addVoucher($relid)
    {
        global $db, $CFG;
        $db->query("INSERT INTO client_cart (`user`, `type`, `relid`, `added`) VALUES (" . $this->userId . ", 'voucher', $relid, " . time() . ")");
    }

    // Method to change the quantity of a product

    public function __construct($userId = -1)
    {
        // Global some variables for security reasons
        global $db, $CFG, $cur, $currencies;

        // Set the user ID as property
        $this->userId = $userId;

        // Get the user factor for prices
        if ($this->userId != -1) {
            $userObj = new User($this->userId, "ID");
            $factor = $userObj->getRaw()['pricelevel'] / 100;
        }

        // Build the select query and do it
        $q = "SELECT * FROM client_cart";
        if ($userId != -1) {
            $q .= " WHERE user = $userId";
        }

        $q .= " ORDER BY ID ASC";
        $itemSQL = $db->query($q);

        // Iterate the entries and fill @var elements and count @var count
        $this->count = 0;
        $this->elements = array();
        while ($item = $itemSQL->fetch_object()) {
            $qty = $item->qty;

            if ($this->userId == -1) {
                $userObj = new User($item->user, "ID");
                $factor = $userObj->getRaw()['pricelevel'] / 100;
            }

            if ($item->type == "voucher") {
                continue;
            }

            if ($item->type == "product") {
                // In case item is a product, we select all necessary information from product table
                $sql = $db->query("SELECT * FROM products WHERE status = 1 AND ID = " . $item->relid);
                if ($sql->num_rows != 1) {
                    continue;
                }

                $info = $sql->fetch_object();

                // We get the price (either for single place or reseller)
                $price = $info->price;
                $pcg = unserialize($info->price_cgroups);
                if (!is_array($pcg)) {
                    $pcg = [];
                }
                $cg = $userObj->get()['cgroup'];
                if (array_key_exists($cg, $pcg)) {
                    $price = $pcg[$cg][0];
                }

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

                $price = Product::getClientPrice($price, $info->tax);
                $setup = Product::getClientPrice($setup, $info->tax);

                $price *= $factor;

                // Check if a voucher applies to the product
                $voucherInfo = $this->getVoucher($item->user, $item->license == "r" ? "reseller" : "product", $item->relid);
                $oldprice = $price;
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
                                $oldprice += $o->amount * $v;
                            }
                        } else if ($i->type == "select") {
                            $values = explode("|", $o->values);
                            $key = array_search($v, $values) ?: 0;
                            $more = explode("|", $o->costs)[$key] ?: 0;

                            if ($o->onetime) {
                                $setup += $more;
                            } else {
                                $price += $more;
                                $oldprice += $more;
                            }
                        } else if ($i->type == "radio") {
                            $values = explode("|", $o->values);
                            $key = array_search($v, $values) ?: 0;
                            $more = explode("|", $o->costs)[$key] ?: 0;

                            if ($o->onetime) {
                                $setup += $more;
                            } else {
                                $price += $more;
                                $oldprice += $more;
                            }
                        } else if ($i->type == "check" && $v) {
                            if ($o->onetime) {
                                $setup += $o->costs;
                            } else {
                                $price += $o->costs;
                                $oldprice += $o->costs;
                            }
                        }
                    }
                }

                if ($info->available != 0) {
                    if ($info->available > 0 && $qty > $info->available) {
                        $qty = $info->available;
                    }

                    $billing = "";
                    $setupFee = Product::getClientPrice($info->setup, $info->tax);

                    $pcg = unserialize($info->price_cgroups);
                    if (!is_array($pcg)) {
                        $pcg = [];
                    }
                    $cg = $userObj->get()['cgroup'];
                    if (array_key_exists($cg, $pcg)) {
                        $setupFee = $pcg[$cg][1];
                    }

                    $item->license = "h";
                    if ($variant === "") {
                        $setup += $setupFee * $factor;
                    }

                    $billing = $info->billing;

                    $this->elements[$item->ID] = array("qty" => $qty, "ID" => $item->ID, "type" => $item->type, "added" => $item->added, "user" => $item->user, "relid" => $item->relid, "name" => $info->name, "amount" => $price, "sum" => $price * $qty, "license" => $item->license, "oldprice" => $oldprice, "billing" => $billing, "setup" => $setup, "ptype" => $info->type, "additional" => $item->additional, "variant" => $variant, "prepaid" => $info->prepaid, "prorata" => $info->prorata);
                }
            } else if ($item->type == "domain_reg" || $item->type == "domain_in") {
                if ($db->query("SELECT 1 FROM domains WHERE domain = '" . $db->real_escape_string(unserialize($item->license)['domain']) . "' AND status IN ('REG_WAITING', 'KK_WAITING')")->num_rows > 0) {
                    $db->query("DELETE FROM client_cart WHERE ID = " . $item->ID);
                    continue;
                }

                $ex = explode(".", unserialize($item->license)['domain']);
                array_shift($ex);
                $tld = implode(".", $ex);

                $u = new User($item->user, "ID");
                $price = $u->getDomainPrice($tld, $item->type == "domain_reg" ? "register" : "transfer");
                $reg_info = unserialize($item->license);
                if (isset($reg_info['privacy']) && $reg_info['privacy'] == "1" && $u->getDomainPrice($tld, 'privacy') > 0) {
                    $price += $u->getDomainPrice($tld, 'privacy');
                }

                $price = $u->addTax($price);

                $oldprice = $price;

                // Domain voucher
                $relidSql = $db->query("SELECT ID FROM domain_pricing WHERE tld = '" . $db->real_escape_string($tld) . "'");
                if ($relidSql->num_rows) {
                    $voucherInfo = $this->getVoucher($item->user, $item->type == "domain_reg" ? "reg" : "kk", $relidSql->fetch_object()->ID);
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

                $this->elements[$item->ID] = array("added" => $item->added, "user" => $item->user, "qty" => 1, "ID" => $item->ID, "type" => $item->type, "relid" => $item->relid, "name" => $info->name, "amount" => $price, "sum" => $price, "oldprice" => $oldprice, "license" => $item->license);
            } else if ($item->type == "bundle") {
                // In case item is a product, we select all necessary information from product table
                $sql = $db->query("SELECT * FROM product_bundles WHERE ID = " . $item->relid);
                if ($sql->num_rows != 1) {
                    continue;
                }

                $info = $sql->fetch_object();

                // We get the price
                $price = $info->price;
                $price *= $factor;

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

                $qty = $item->qty;
                $this->elements[$item->ID] = array("qty" => $qty, "ID" => $item->ID, "type" => $item->type, "added" => $item->added, "user" => $item->user, "relid" => $item->relid, "name" => $info->name, "amount" => $price, "sum" => $price * $qty, "license" => $item->license, "oldprice" => $price, "desc" => $desc);
            }

            $this->count += $qty;
        }
    }

    // Method to get the active voucher

    public function getVoucher($userId = 0, $type = "product", $relid = 0)
    {
        // Global some variables for security reasons
        global $db, $CFG;

        if ($userId == 0) {
            $userId = $this->userId;
        }

        $sql = $db->query("SELECT * FROM client_cart WHERE type = 'voucher' AND user = $userId LIMIT 1");
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();
        $sql = $db->query("SELECT * FROM vouchers WHERE ID = $info->relid");
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();
        if (($info->valid_to < time() && $info->valid_to > 0) || ($info->valid_from > time() && $info->valid_from > 0) || $info->active != 1 || ($info->max_uses <= $info->uses && $info->max_uses >= 0) || ($info->user != 0 && $userId != -1 && $userId != $info->user)) {
            $this->removeVoucher(0);
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

    // Method to remove the active voucher
    public function removeVoucher($check = 1)
    {
        // Global some variables for security reasons
        global $db, $CFG;

        if ($check && $this->getVoucher() === false) {
            return false;
        }

        return $db->query("DELETE FROM client_cart WHERE type = 'voucher' AND user = $this->userId LIMIT 1");
    }

    // Method to add a voucher

    public function changeQty($id, $qty)
    {
        global $db, $CFG, $user;

        if (!isset($this->elements[$id])) {
            return false;
        }

        $id = intval($id);

        $oldQty = $this->elements[$id]['qty'];
        if (!empty($this->elements[$id]['additional']) && !in_array($qty, [0, 1])) {
            return false;
        }

        if (!in_array($this->elements[$id]['type'], array("product", "bundle"))) {
            return;
        }

        if ($db->query("SELECT ID FROM client_cart WHERE ID = '$id' AND (license = 'e' OR license = 'h')")->num_rows == 1) {
            // If entry in cart table is found, update the quantity / delete the entry (if @var qty == 0)
            if ($qty == 0) {
                $this->count -= $oldQty;
                $db->query("DELETE FROM client_cart WHERE ID = '$id' LIMIT 1");
                unset($this->elements[$id]);
            } else {
                if ($this->elements[$id]['type'] == "product" || $this->elements[$id]['type'] == "hosting") {
                    $sql = $db->query("SELECT available FROM products WHERE ID = " . $this->elements[$id]['relid']);
                    if ($sql->num_rows != 1) {
                        return;
                    }

                    $a = $sql->fetch_object()->available;
                    if ($a > 0 && $a < $qty) {
                        return;
                    }

                    $sql = $db->query("SELECT maxpc FROM products WHERE ID = " . $this->elements[$id]['relid']);
                    if ($sql->num_rows != 1) {
                        return;
                    }

                    $a = $sql->fetch_object()->maxpc;
                    if ($a > 0 && $a - $db->query("SELECT 1 FROM client_products WHERE product = " . $this->elements[$id]['relid'] . " AND user = " . $user->get()['ID'])->num_rows < $qty) {
                        return;
                    }

                }

                $this->count -= $oldQty;
                $this->count += $qty;
                $db->query("UPDATE client_cart SET qty = '$qty' WHERE ID = '$id' LIMIT 1");
                $this->elements[$id]['qty'] = $qty;
                $this->elements[$id]['sum'] = $this->elements[$id]['amount'] * $qty;
            }
        }
    }

    // Method to add a new cart entry
    // You can specify the related ID, the type and the license type (last for products)

    public function add($relid, $type = "product", $license = "e", $additional = "")
    {
        // Global some variables for security reasons
        global $db, $CFG, $user;

        // You cannot add an entry without specified a user ID before
        if ($this->userId == -1) {
            return false;
        }

        $license = $db->real_escape_string($license);

        // We will select the product from the table
        // Other types will be catched and produce a return of false
        switch ($type) {
            case 'product':

                if ($db->query("SELECT ID FROM products WHERE status = 1 AND ID = $relid LIMIT 1")->num_rows != 1) {
                    return false;
                }

                break;
        }

        // If the product already is in cart table for this user, we only increase the quantity, otherwise we will insert it
        $qry = "SELECT `ID`, `qty` FROM `client_cart` WHERE `user` = " . $this->userId . " AND `type` = '$type' AND `relid` = $relid AND `license` = '$license' LIMIT 1";
        $existsSql = $db->query($qry);
        if ($existsSql->num_rows == 1 && in_array($type, array("product", "bundle")) && empty($additional)) {
            $existsInfo = $existsSql->fetch_object();
            $existsId = $existsInfo->ID;

            $oldQty = $existsInfo->qty;
            if ($type == "product") {
                $sql = $db->query("SELECT available, maxpc FROM products WHERE ID = " . $relid);
                if ($sql->num_rows != 1) {
                    return false;
                }

                $i = $sql->fetch_object();

                if ($i->available == 0) {
                    return false;
                }

                if ($i->maxpc >= 0) {
                    $i->maxpc -= $db->query("SELECT 1 FROM client_products WHERE product = " . $relid . " AND user = " . $user->get()['ID'])->num_rows;
                    $i->maxpc -= $oldQty;
                    if ($i->maxpc <= 0) {
                        return false;
                    }

                }
            }

            $db->query("UPDATE client_cart SET qty = qty + 1 WHERE ID = $existsId AND license != 'r' LIMIT 1");
            if ($db->affected_rows > 0) {
                $this->count++;
            }

        } else {
            if ($existsSql->num_rows > 0 && empty($additional)) {
                $i = $existsSql->fetch_object()->ID;
                $db->query("UPDATE client_cart SET `added` = " . time() . ", `license` = '$license' WHERE ID = $i LIMIT 1");
            } else {
                if ($type == "product") {
                    $sql = $db->query("SELECT available, maxpc FROM products WHERE ID = " . $relid);
                    if ($sql->num_rows != 1) {
                        return false;
                    }

                    $i = $sql->fetch_object();

                    if ($i->available == 0) {
                        return false;
                    }

                    if ($i->maxpc >= 0) {
                        $i->maxpc -= $db->query("SELECT 1 FROM client_products WHERE product = " . $relid . " AND user = " . $user->get()['ID'])->num_rows;
                        if ($i->maxpc <= 0) {
                            return false;
                        }

                    }
                }
                $db->query("INSERT INTO client_cart (`user`, `type`, `relid`, `added`, `license`, `additional`) VALUES (" . $this->userId . ", '$type', $relid, " . time() . ", '$license', '" . $db->real_escape_string($additional) . "')");
                $this->count++;
            }
        }

        return true;
    }

    // Delete all items from the cart
    public function null()
    {
        // Global some variables for security reasons
        global $db, $CFG;

        // You cannot delete all items from all users
        if ($this->userId == -1) {
            return false;
        }

        $db->query("DELETE FROM client_cart WHERE user = " . $this->userId);
        $this->elements = array();
        $this->count = 0;
    }

    // Get all elements in cart
    public function get()
    {
        return $this->elements;
    }

    // Count all elements in cart
    public function count()
    {
        return $this->count;
    }

    // Method to remove an element
    public function removeElement($id)
    {
        global $db, $CFG;

        $id = intval($id);
        $this->count -= $this->elements[$id]['qty'];
        if ($this->userId == -1) {
            $db->query("DELETE FROM client_cart WHERE ID = $id");
        } else {
            $db->query("DELETE FROM client_cart WHERE user = " . $this->userId . " AND ID = $id");
        }

        unset($this->elements[$id]);
        return $db->affected_rows >= 1;
    }

    // Method to get the user ID by an element
    public function getElementsUser($id)
    {
        global $db, $CFG;

        $id = intval($id);
        if ($this->userId == -1) {
            $sql = $db->query("SELECT user FROM client_cart WHERE ID = $id");
            return $sql->num_rows == 1 ? $sql->fetch_object()->user : 0;
        } else {
            return $this->userId;
        }
    }

    // Method to get the discount by voucher
    public function getVoucherDiscount()
    {
        $discount = 0;
        foreach ($this->elements as $e) {
            if ($e['amount'] != $e['oldprice']) {
                $discount += ($e['oldprice'] - $e['amount']) * $e['qty'];
            }
        }

        return $discount;
    }

}
