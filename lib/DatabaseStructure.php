<?php
// Classes for establishing and merging database structure

class DatabaseStructure
{
    protected $tables = null;
    protected $time = null;

    public function __construct()
    {
        $this->tables = $this->time = [];
    }

    public function calcHash()
    {
        $str = "";
        foreach ($this->tables as $table) {
            $str .= $table->calcHash();
        }

        return md5($str);
    }

    public function table(DatabaseTable $table)
    {
        array_push($this->tables, $table);
    }

    public function deploy(DB $db, $prefix = "")
    {
        $db = $db->getUnderlyingDriver();

        foreach ($this->tables as $table) {
            $this->time[$table->name] = microtime(true);

            $name = $db->real_escape_string($prefix . $table->name);
            $pk = $table->pk;
            $columns = $table->columns;

            if ($db->query("SELECT 1 FROM `$name`")) {
                $columns = [];

                $sql = $db->query("SHOW COLUMNS FROM `$name`");
                while ($row = $sql->fetch_object()) {
                    $columns[$row->Field] = [
                        $row->Type,
                        $row->Default,
                        $row->Key,
                        $row->Extra,
                        $row->Null,
                    ];
                }

                foreach ($table->columns as $column) {
                    $cname = $db->real_escape_string($column->name);

                    if (!array_key_exists($column->name, $columns)) {
                        $def = $column->type;
                        if ($column->not_null) {
                            $def .= " NOT NULL";
                        }
                        if ($column->default !== null) {
                            $def .= " DEFAULT '" . $db->real_escape_string($column->default) . "'";
                        }
                        if ($column->options) {
                            $def .= " " . $column->options;
                        }

                        $db->query("ALTER TABLE `$name` ADD `$cname` $def");
                    } else {
                        $ec = $columns[$column->name];

                        if (strtolower(str_replace(" ", "", $ec[0])) != strtolower(str_replace(" ", "", $column->type))) {
                            $def = $column->type;
                            if ($column->not_null) {
                                $def .= " NOT NULL";
                            }
                            if ($column->default !== null) {
                                $def .= " DEFAULT '" . $db->real_escape_string($column->default) . "'";
                            }
                            if ($column->options) {
                                $def .= " " . $column->options;
                            }

                            $db->query("ALTER TABLE `$name` MODIFY `$cname` $def");
                        }

                        if (($column->default === null && $ec[1] !== null) || ($column->default !== null && $ec[1] === null)) {
                            if ($column->default === null) {
                                $db->query("ALTER TABLE `$name` ALTER `$cname` DROP DEFAULT");
                            } else {
                                $db->query("ALTER TABLE `$name` ALTER `$cname` SET DEFAULT '" . $db->real_escape_string($column->default) . "'");
                            }
                        } else if ($column->default !== null && $column->default != $ec[1]) {
                            $db->query("ALTER TABLE `$name` ALTER `$cname` SET DEFAULT '" . $db->real_escape_string($column->default) . "'");
                        }

                        if (stripos($column->options, "AUTO_INCREMENT") !== false) {
                            if (stripos($ec[3], "AUTO_INCREMENT") === false) {
                                $def = $column->type;
                                if ($column->not_null) {
                                    $def .= " NOT NULL";
                                }
                                if ($column->default !== null) {
                                    $def .= " DEFAULT '" . $db->real_escape_string($column->default) . "'";
                                }
                                if ($column->options) {
                                    $def .= " " . $column->options;
                                }

                                $db->query("ALTER TABLE `$name` MODIFY `$cname` $def");
                            }
                        }
                    }
                }

                if ($table->pk) {
                    $columns = [];
                    $sql = $db->query("SHOW COLUMNS FROM `$name`");
                    while ($row = $sql->fetch_object()) {
                        $columns[$row->Field] = [
                            $row->Type,
                            $row->Default,
                            $row->Key,
                            $row->Extra,
                        ];
                    }

                    foreach ($table->pk as $pk) {
                        if (!array_key_exists($pk, $columns)) {
                            break;
                        }

                        if (strpos($columns[$pk][2], "PRI") === false) {
                            $cols = "`" . implode("`,`", array_map([$db, "real_escape_string"], $table->pk)) . "`";

                            $db->query("ALTER TABLE `$name` DROP PRIMARY_KEY");
                            $db->query("ALTER TABLE `$name` ADD PRIMARY KEY ($cols)");
                            break;
                        }
                    }
                }

                if ($table->uk) {
                    $columns = [];
                    $sql = $db->query("SHOW COLUMNS FROM `$name`");
                    while ($row = $sql->fetch_object()) {
                        $columns[$row->Field] = [
                            $row->Type,
                            $row->Default,
                            $row->Key,
                            $row->Extra,
                        ];
                    }

                    foreach ($table->uk as $uk) {
                        if (!array_key_exists($uk, $columns)) {
                            break;
                        }

                        if (strpos($columns[$uk][2], "UNI") === false) {
                            $cols = "`" . implode("`,`", array_map([$db, "real_escape_string"], $table->uk)) . "`";

                            $db->query("ALTER TABLE `$name` DROP UNIQUE_KEY");
                            $db->query("ALTER TABLE `$name` ADD CONSTRAINT UNIQUE_KEY UNIQUE ($cols)");
                            break;
                        }
                    }
                }
            } else {
                $sql = "CREATE TABLE `$name` (";

                foreach ($columns as $column) {
                    $sql .= "`" . $db->real_escape_string($column->name) . "` " . $column->type;
                    if ($column->not_null) {
                        $sql .= " NOT NULL";
                    }
                    if ($column->default !== null) {
                        $sql .= " DEFAULT '" . $db->real_escape_string($column->default) . "'";
                    }
                    if ($column->options) {
                        $sql .= " " . $column->options;
                    }
                    $sql .= ", ";
                }

                if (is_array($pk) && count($pk)) {
                    $sql .= "PRIMARY KEY (";
                    foreach ($pk as $k) {
                        $sql .= "`" . $db->real_escape_string($k) . "`, ";
                    }
                    $sql = rtrim($sql, ", ") . ")";
                }
                $sql = rtrim($sql, ", ") . ")";

                $this->time[$table->name . "_create_exec"] = microtime(true);
                $db->query($sql);
                $this->time[$table->name . "_create_exec"] = microtime(true) - $this->time[$table->name . "_create_exec"];
            }

            $this->time[$table->name] = microtime(true) - $this->time[$table->name];
        }

        $this->initData($db, $prefix);

        if (isset($_GET['time_debug'])) {
            $sum = 0;
            foreach ($this->time as $table => $time) {
                $sum += $time;
                echo $table . " - " . strval($time) . "<br />";
            }

            echo "Sum: $sum<br /><br />";
        }
    }

    public function init()
    {
        $t = new DatabaseTable("abuse");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)"));
        $t->column(new DatabaseColumn("contract", "int(11)"));
        $t->column(new DatabaseColumn("status", "enum('open','resolved')", "open"));
        $t->column(new DatabaseColumn("time", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("deadline", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ""));
        $t->column(new DatabaseColumn("ticket", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("abuse_messages");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("report", "int(11)"));
        $t->column(new DatabaseColumn("author", "enum('client','staff','reporter')", "client"));
        $t->column(new DatabaseColumn("time", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("text", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("accounts");
        $t->column(new DatabaseColumn("user", "int(11)"));
        $t->column(new DatabaseColumn("account", "varchar(255)"));
        $t->column(new DatabaseColumn("data", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("addons");
        $t->column(new DatabaseColumn("addon", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["addon", "setting"]);
        $this->table($t);

        // Admin table is also defined in required.dist.sql!
        $t = new DatabaseTable("admins");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("username", "varchar(50)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("password", "varchar(255)"));
        $t->column(new DatabaseColumn("salt", "varchar(255)", ""));
        $t->column(new DatabaseColumn("name", "varchar(255)"));
        $t->column(new DatabaseColumn("email", "varchar(80)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("language", "varchar(255)", ""));
        $t->column(new DatabaseColumn("rights", "longtext"));
        $t->column(new DatabaseColumn("notifications", "longtext"));
        $t->column(new DatabaseColumn("tfa", "varchar(255)", "none"));
        $t->column(new DatabaseColumn("hide_sidebar", "int(1)", "0"));
        $t->column(new DatabaseColumn("notes", "longtext"));
        $t->column(new DatabaseColumn("online", "int(1)", "0"));
        $t->column(new DatabaseColumn("call_info", "longtext"));
        $t->column(new DatabaseColumn("widgets", "longtext"));
        $t->column(new DatabaseColumn("last_sign", "int(11)", "0"));
        $t->column(new DatabaseColumn("sa", "longtext"));
        $t->column(new DatabaseColumn("sat", "varchar(255)", ""));
        $t->column(new DatabaseColumn("api_key", "varchar(255)", ""));
        $t->column(new DatabaseColumn("tfa_second", "varchar(255)", ""));
        $t->column(new DatabaseColumn("tfa_valid", "int(11)", "0"));
        $t->column(new DatabaseColumn("open_menu", "int(1)", "1"));
        $t->column(new DatabaseColumn("next_ticket", "int(1)", "1"));
        $t->column(new DatabaseColumn("avatar", "varchar(255)", ""));
        $t->column(new DatabaseColumn("last_sid", "varchar(255)", ""));
        $t->column(new DatabaseColumn("per_page", "int(11)", "25"));
        $this->table($t);

        $t = new DatabaseTable("admin_cookie");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("string", "varchar(128)", "", "UNIQUE KEY"));
        $t->column(new DatabaseColumn("valid", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("auth", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("admin_log");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("action", "varchar(255)", ""));
        $t->column(new DatabaseColumn("ip", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("admin_order");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("table", "varchar(255)", ""));
        $t->column(new DatabaseColumn("field", "varchar(255)", ""));
        $t->column(new DatabaseColumn("direction", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("admin_reminders");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("title", "varchar(255)", ""));
        $t->column(new DatabaseColumn("description", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("admin_shortcut");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("url", "text"));
        $t->column(new DatabaseColumn("text", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("admin_tfa");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("code", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("admin_times");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("start", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("end", "datetime", "0000-00-00 00:00:00"));
        $this->table($t);

        $t = new DatabaseTable("backup_log");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("log", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("blacklist_ip");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("ip", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("reason", "varchar(255)", ""));
        $t->column(new DatabaseColumn("inserted", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("blacklist_mail");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("email", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("inserted", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("branding");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("host", "varchar(255)"));
        $t->column(new DatabaseColumn("pageurl", "varchar(255)", ""));
        $t->column(new DatabaseColumn("pagename", "varchar(255)", ""));
        $t->column(new DatabaseColumn("pagemail", "varchar(255)", ""));
        $t->column(new DatabaseColumn("design", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("bugtracker");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("date", "datetime"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("product", "int(11)", "0"));
        $t->column(new DatabaseColumn("ticket", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("cashbox");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("hash", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("cms_blog");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("title", "longtext"));
        $t->column(new DatabaseColumn("text", "longtext"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("cms_faq");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("question", "longtext"));
        $t->column(new DatabaseColumn("answer", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("cms_links");
        $t->column(new DatabaseColumn("slug", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("target", "varchar(255)", ""));
        $t->column(new DatabaseColumn("status", "int(1)", "0"));
        $t->column(new DatabaseColumn("calls", "bigint(20)", "0"));
        $this->table($t);

        $t = new DatabaseTable("cms_menu");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "longtext"));
        $t->column(new DatabaseColumn("parent", "int(11)", "0"));
        $t->column(new DatabaseColumn("prio", "int(11)", "0"));
        $t->column(new DatabaseColumn("type", "longtext"));
        $t->column(new DatabaseColumn("relid", "int(11)", "0"));
        $t->column(new DatabaseColumn("status", "int(1)", "1"));
        $this->table($t);

        $t = new DatabaseTable("cms_pages");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("slug", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("title", "longtext"));
        $t->column(new DatabaseColumn("content", "longtext"));
        $t->column(new DatabaseColumn("seo", "longtext"));
        $t->column(new DatabaseColumn("container", "int(1)", "1"));
        $t->column(new DatabaseColumn("active", "int(1)", "1"));
        $t->column(new DatabaseColumn("min_age", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("cronjobs");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("key", "varchar(100)", "", "UNIQUE KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("foreign_name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("last_call", "int(11)", "0"));
        $t->column(new DatabaseColumn("intervall", "int(11)", "60"));
        $t->column(new DatabaseColumn("password", "varchar(255)", ""));
        $t->column(new DatabaseColumn("active", "int(11)", "1"));
        $this->table($t);

        $t = new DatabaseTable("currencies");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("prefix", "varchar(255)", ""));
        $t->column(new DatabaseColumn("suffix", "varchar(255)", ""));
        $t->column(new DatabaseColumn("conversion_rate", "double(100,8)", "0.00000000"));
        $t->column(new DatabaseColumn("currency_code", "varchar(255)", ""));
        $t->column(new DatabaseColumn("base", "int(1)", "0"));
        $t->column(new DatabaseColumn("round", "double(15,2)", "-1.00"));
        $this->table($t);

        $t = new DatabaseTable("clients");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("mail", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("nickname", "varchar(100)", ""));
        $t->column(new DatabaseColumn("avatar", "varchar(100)", ""));
        $t->column(new DatabaseColumn("salutation", "enum('MALE', 'FEMALE', 'DIVERS', '')", ''));
        $t->column(new DatabaseColumn("firstname", "varchar(255)", ''));
        $t->column(new DatabaseColumn("lastname", "varchar(255)", ''));
        $t->column(new DatabaseColumn("company", "varchar(255)", ''));
        $t->column(new DatabaseColumn("vatid", "varchar(255)", ''));
        $t->column(new DatabaseColumn("street", "varchar(255)", ''));
        $t->column(new DatabaseColumn("street_number", "varchar(25)", ''));
        $t->column(new DatabaseColumn("postcode", "varchar(10)", ''));
        $t->column(new DatabaseColumn("city", "varchar(255)", ''));
        $t->column(new DatabaseColumn("country", "int(11)", '0'));
        $t->column(new DatabaseColumn("coordinates", "varchar(255)", ''));
        $t->column(new DatabaseColumn("telephone", "varchar(255)", ''));
        $t->column(new DatabaseColumn("telephone_verified", "int(1)", '0'));
        $t->column(new DatabaseColumn("fax", "varchar(255)", ''));
        $t->column(new DatabaseColumn("birthday", "date", '0000-00-00'));
        $t->column(new DatabaseColumn("last_birthday", "date", '0000-00-00'));
        $t->column(new DatabaseColumn("website", "varchar(255)", ''));
        $t->column(new DatabaseColumn("language", "varchar(255)", ''));
        $t->column(new DatabaseColumn("currency", "varchar(255)", ''));
        $t->column(new DatabaseColumn("pwd", "varchar(255)", ''));
        $t->column(new DatabaseColumn("credit", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("special_credit", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("cashbox_active", "int(1)", '1'));
        $t->column(new DatabaseColumn("reseller", "int(1)", '0'));
        $t->column(new DatabaseColumn("tos", "int(11)", '0'));
        $t->column(new DatabaseColumn("registered", "int(11)", '0'));
        $t->column(new DatabaseColumn("last_login", "int(11)", '0'));
        $t->column(new DatabaseColumn("last_active", "int(11)", '0'));
        $t->column(new DatabaseColumn("last_sms_code", "int(11)", "0"));
        $t->column(new DatabaseColumn("sms_code", "int(11)", "0"));
        $t->column(new DatabaseColumn("sms_code_tries", "int(11)", "0"));
        $t->column(new DatabaseColumn("sms_code_number", "varchar(255)", ""));
        $t->column(new DatabaseColumn("last_ip", "varchar(255)", ''));
        $t->column(new DatabaseColumn("locked", "int(1)", '0'));
        $t->column(new DatabaseColumn("verified", "int(1)", '0'));
        $t->column(new DatabaseColumn("api_key", "varchar(255)", ''));
        $t->column(new DatabaseColumn("login_notify", "int(1)", '1'));
        $t->column(new DatabaseColumn("tfa", "varchar(255)", 'none'));
        $t->column(new DatabaseColumn("last_pwreset", "int(11)", '0'));
        $t->column(new DatabaseColumn("reset_hash", "varchar(255)", ''));
        $t->column(new DatabaseColumn("newsletter", "varchar(255)", ''));
        $t->column(new DatabaseColumn("salt", "varchar(255)", ''));
        $t->column(new DatabaseColumn("pricelevel", "double(100,2)", '100.00'));
        $t->column(new DatabaseColumn("telephone_pin", "int(11)", '0'));
        $t->column(new DatabaseColumn("telephone_pin_set", "int(11)", '0'));
        $t->column(new DatabaseColumn("failed_login", "int(11)", '0'));
        $t->column(new DatabaseColumn("failed_login_mail", "int(11)", '0'));
        $t->column(new DatabaseColumn("affiliate", "int(11)", '0'));
        $t->column(new DatabaseColumn("affiliate_source", "varchar(255)", ''));
        $t->column(new DatabaseColumn("affiliate_credit", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("birthday_mail", "int(1)", '1'));
        $t->column(new DatabaseColumn("withdrawal_rules", "int(1)", '0'));
        $t->column(new DatabaseColumn("privacy_policy", "int(1)", '0'));
        $t->column(new DatabaseColumn("confirmed", "int(1)", '0'));
        $t->column(new DatabaseColumn("social_login", "int(1)", '1'));
        $t->column(new DatabaseColumn("domain_contacts", "int(1)", '0'));
        $t->column(new DatabaseColumn("no_reminders", "int(1)", '0'));
        $t->column(new DatabaseColumn("disabled_payment", "longtext", null));
        $t->column(new DatabaseColumn("data", "longtext", null));
        $t->column(new DatabaseColumn("invoicelater", "int(11)", '1'));
        $t->column(new DatabaseColumn("invoicelast", "date", '0000-00-00'));
        $t->column(new DatabaseColumn("domain_api", "int(1)", '0'));
        $t->column(new DatabaseColumn("postpaid", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("dns_server", "longtext", null));
        $t->column(new DatabaseColumn("update_service", "int(1)", '0'));
        $t->column(new DatabaseColumn("cgroup", "int(11)", '0'));
        $t->column(new DatabaseColumn("cgroup_before", "int(11)", '0'));
        $t->column(new DatabaseColumn("cgroup_contract", "int(11)", '0'));
        $t->column(new DatabaseColumn("auth_lock", "int(1)", '0'));
        $t->column(new DatabaseColumn("fields", "longtext", null));
        $t->column(new DatabaseColumn("sepa_fav", "int(11)", '0'));
        $t->column(new DatabaseColumn("sepa_limit", "double(100,2)", '100.00'));
        $t->column(new DatabaseColumn("orders_active", "int(1)", '1'));
        $t->column(new DatabaseColumn("group_recurring", "int(1)", '1'));
        $t->column(new DatabaseColumn("exclude_mail_templates", "longtext", null));
        $t->column(new DatabaseColumn("registrar_settings", "longtext", null));
        $t->column(new DatabaseColumn("auto_payment_provider", "varchar(255)", ''));
        $t->column(new DatabaseColumn("auto_payment_credentials", "longtext", null));
        $t->column(new DatabaseColumn("disabled_support_prio", "varchar(255)", ''));
        $t->column(new DatabaseColumn("reseller_pagename", "varchar(255)", ''));
        $t->column(new DatabaseColumn("inv_street", "varchar(255)", ''));
        $t->column(new DatabaseColumn("inv_street_number", "varchar(25)", ''));
        $t->column(new DatabaseColumn("inv_postcode", "varchar(10)", ''));
        $t->column(new DatabaseColumn("inv_city", "varchar(255)", ''));
        $t->column(new DatabaseColumn("inv_tthof", "varchar(255)", ''));
        $t->column(new DatabaseColumn("inv_due", "int(11)", '-1'));
        $t->column(new DatabaseColumn("cust_source", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("client_affiliate");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("affiliate", "int(11)", '0'));
        $t->column(new DatabaseColumn("amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("cancelled", "int(1)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_calls");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("content", "longtext", null));
        $t->column(new DatabaseColumn("admin", "int(11)", '0'));
        $t->column(new DatabaseColumn("billed", "int(1)", '0'));
        $t->column(new DatabaseColumn("endtime", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_cart");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("type", "varchar(255)", 'product'));
        $t->column(new DatabaseColumn("relid", "int(11)", '0'));
        $t->column(new DatabaseColumn("added", "int(11)", '0'));
        $t->column(new DatabaseColumn("license", "longtext", null));
        $t->column(new DatabaseColumn("qty", "int(11)", '1'));
        $t->column(new DatabaseColumn("additional", "longtext", null));
        $this->table($t);

        $t = new DatabaseTable("client_changes");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("diff", "longtext", null));
        $t->column(new DatabaseColumn("who", "varchar(255)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_contacts");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("client", "int(11)", '0'));
        $t->column(new DatabaseColumn("mail", "varchar(100)", null));
        $t->column(new DatabaseColumn("firstname", "varchar(255)", ''));
        $t->column(new DatabaseColumn("lastname", "varchar(255)", ''));
        $t->column(new DatabaseColumn("company", "varchar(255)", ''));
        $t->column(new DatabaseColumn("street", "varchar(255)", ''));
        $t->column(new DatabaseColumn("street_number", "varchar(255)", ''));
        $t->column(new DatabaseColumn("postcode", "varchar(255)", ''));
        $t->column(new DatabaseColumn("city", "varchar(255)", ''));
        $t->column(new DatabaseColumn("country", "int(11)", '0'));
        $t->column(new DatabaseColumn("telephone", "varchar(255)", ''));
        $t->column(new DatabaseColumn("language", "varchar(255)", ''));
        $t->column(new DatabaseColumn("currency", "varchar(255)", ''));
        $t->column(new DatabaseColumn("mail_templates", "longtext", null));
        $t->column(new DatabaseColumn("type", "varchar(255)", ''));
        $t->column(new DatabaseColumn("rights", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("client_cookie");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("string", "varchar(128)", '', "UNIQUE KEY"));
        $t->column(new DatabaseColumn("valid", "int(11)", '0'));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("auth", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("client_countries");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("alpha2", "varchar(2)", ''));
        $t->column(new DatabaseColumn("active", "int(1)", '1'));
        $t->column(new DatabaseColumn("tax", "varchar(255)", ''));
        $t->column(new DatabaseColumn("percent", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("b2b", "int(1)", '0'));
        $t->column(new DatabaseColumn("b2c", "int(1)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_customers");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("uid", "int(11)", '0'));
        $t->column(new DatabaseColumn("mail", "varchar(255)", ''));
        $t->column(new DatabaseColumn("password", "varchar(255)", ''));
        $t->column(new DatabaseColumn("login", "varchar(255)", ''));
        $t->uniqueKey(["uid", "mail"]);
        $this->table($t);

        $t = new DatabaseTable("client_fields");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("foreign_name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("active", "int(1)", '1'));
        $t->column(new DatabaseColumn("position", "int(11)", '0'));
        $t->column(new DatabaseColumn("customer", "int(1)", '0'));
        $t->column(new DatabaseColumn("duty", "int(1)", '0'));
        $t->column(new DatabaseColumn("system", "int(1)", '0'));
        $t->column(new DatabaseColumn("regex", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("client_files");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("filename", "varchar(255)"));
        $t->column(new DatabaseColumn("filepath", "varchar(255)"));
        $t->column(new DatabaseColumn("expire", "int(11)", '-1'));
        $t->column(new DatabaseColumn("user_access", "int(1)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_groups");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)"));
        $t->column(new DatabaseColumn("color", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("client_letters");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("client", "int(11)", '0'));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("text", "longtext"));
        $t->column(new DatabaseColumn("sent", "int(1)", '0'));
        $t->column(new DatabaseColumn("recipient", "longtext"));
        $t->column(new DatabaseColumn("date", "date", '0000-00-00'));
        $this->table($t);

        $t = new DatabaseTable("client_log");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("action", "varchar(255)", ''));
        $t->column(new DatabaseColumn("ip", "varchar(255)", ''));
        $t->column(new DatabaseColumn("ua", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("client_mailchanges");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("new", "varchar(255)"));
        $t->column(new DatabaseColumn("hash", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("client_mails");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("recipient", "varchar(255)", ''));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("text", "longtext"));
        $t->column(new DatabaseColumn("headers", "varchar(255)", ''));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("sent", "int(11)", '0'));
        $t->column(new DatabaseColumn("newsletter", "int(11)", '0'));
        $t->column(new DatabaseColumn("resend", "int(1)", '0'));
        $t->column(new DatabaseColumn("wait", "int(1)", '0'));
        $t->column(new DatabaseColumn("seen", "int(1)", '0'));
        $t->column(new DatabaseColumn("template_id", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_newsletters");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("language", "varchar(255)", ''));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("text", "longtext"));
        $t->column(new DatabaseColumn("recipients", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_notes");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("title", "varchar(255)", ''));
        $t->column(new DatabaseColumn("text", "longtext"));
        $t->column(new DatabaseColumn("last_changed", "int(11)", '0'));
        $t->column(new DatabaseColumn("admin", "int(11)", '0'));
        $t->column(new DatabaseColumn("priority", "int(11)", '0'));
        $t->column(new DatabaseColumn("display", "varchar(255)", 'none'));
        $t->column(new DatabaseColumn("sticky", "int(1)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_products");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("date", "int(11)", '0'));
        $t->column(new DatabaseColumn("product", "int(11)", '0'));
        $t->column(new DatabaseColumn("name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("username", "varchar(255)", ""));
        $t->column(new DatabaseColumn("active", "int(1)", '1'));
        $t->column(new DatabaseColumn("type", "varchar(1)", 'e'));
        $t->column(new DatabaseColumn("key", "varchar(255)", ''));
        $t->column(new DatabaseColumn("key_reissue", "int(11)", '0'));
        $t->column(new DatabaseColumn("key_host", "longtext"));
        $t->column(new DatabaseColumn("key_ip", "longtext"));
        $t->column(new DatabaseColumn("key_dir", "longtext"));
        $t->column(new DatabaseColumn("version", "int(11)", '0'));
        $t->column(new DatabaseColumn("description", "varchar(25)", ''));
        $t->column(new DatabaseColumn("price", "double(100,6)", '0.000000'));
        $t->column(new DatabaseColumn("billing", "varchar(255)", ''));
        $t->column(new DatabaseColumn("module", "varchar(255)", ''));
        $t->column(new DatabaseColumn("module_settings", "longtext"));
        $t->column(new DatabaseColumn("assigned_domains", "longtext"));
        $t->column(new DatabaseColumn("last_billed", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("ct", "varchar(255)", ''));
        $t->column(new DatabaseColumn("mct", "varchar(255)", ''));
        $t->column(new DatabaseColumn("np", "varchar(255)", ''));
        $t->column(new DatabaseColumn("cancellation_date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("module_data", "longtext"));
        $t->column(new DatabaseColumn("error", "longtext"));
        $t->column(new DatabaseColumn("cf", "longtext"));
        $t->column(new DatabaseColumn("data", "longtext"));
        $t->column(new DatabaseColumn("cancellation_allowed", "int(1)", '1'));
        $t->column(new DatabaseColumn("start", "int(11)", '0'));
        $t->column(new DatabaseColumn("payment", "int(11)", '0'));
        $t->column(new DatabaseColumn("paid_until", "int(11)", '0'));
        $t->column(new DatabaseColumn("prepaid", "int(1)", "0"));
        $t->column(new DatabaseColumn("server_id", "int(11)", "-1"));
        $t->column(new DatabaseColumn("reseller_customer", "int(11)", "0"));
        $t->column(new DatabaseColumn("notes_public", "longtext"));
        $t->column(new DatabaseColumn("notes_private", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("client_quotes");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("client", "int(11)", '0'));
        $t->column(new DatabaseColumn("intro", "longtext"));
        $t->column(new DatabaseColumn("items", "longtext"));
        $t->column(new DatabaseColumn("extro", "longtext"));
        $t->column(new DatabaseColumn("terms", "longtext"));
        $t->column(new DatabaseColumn("status", "int(1)", '0'));
        $t->column(new DatabaseColumn("recipient", "longtext"));
        $t->column(new DatabaseColumn("duration", "int(1)", '1'));
        $t->column(new DatabaseColumn("vat", "int(1)", '1'));
        $t->column(new DatabaseColumn("date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("valid", "date", "0000-00-00"));
        $this->table($t);

        $t = new DatabaseTable("client_quote_stages");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("quote", "int(11)"));
        $t->column(new DatabaseColumn("days", "int(11)", '0'));
        $t->column(new DatabaseColumn("percent", "int(11)", '100'));
        $this->table($t);

        $t = new DatabaseTable("client_scoring");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("entry", "longtext"));
        $t->column(new DatabaseColumn("details", "longtext"));
        $t->column(new DatabaseColumn("rating", "varchar(1)", 'F'));
        $this->table($t);

        $t = new DatabaseTable("client_sepa");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("client", "int(11)", '0'));
        $t->column(new DatabaseColumn("iban", "varchar(255)", ''));
        $t->column(new DatabaseColumn("bic", "varchar(255)", ''));
        $t->column(new DatabaseColumn("date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("last_used", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("account_holder", "varchar(255)", ''));
        $t->column(new DatabaseColumn("status", "int(1)", '0'));
        $t->column(new DatabaseColumn("stripe", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("client_tfa");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("code", "varchar(255)", ''));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("client_transactions");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("time", "int(15)", '0'));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("stem", "int(1)", '0'));
        $t->column(new DatabaseColumn("cashbox_subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("deposit", "int(1)", '0'));
        $t->column(new DatabaseColumn("waiting", "int(1)", '0'));
        $t->column(new DatabaseColumn("sepa_done", "int(1)", '1'));
        $t->column(new DatabaseColumn("fibu", "int(11)", '0'));
        $t->column(new DatabaseColumn("who", "int(11)", '0'));
        $t->column(new DatabaseColumn("chargeback", "int(11)", '0'));
        $t->column(new DatabaseColumn("payment_reference", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("csv_import");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("transactionId", "varchar(255)", ''));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("sender", "varchar(255)", ''));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ''));
        $t->column(new DatabaseColumn("clientId", "int(11)", '0'));
        $t->column(new DatabaseColumn("done", "int(1)", '0'));
        $this->table($t);

        $t = new DatabaseTable("dns_templates");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("ns_set", "tinyint(1)", '1'));
        $this->table($t);

        $t = new DatabaseTable("dns_template_records");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("template_id", "int(11)", '0'));
        $t->column(new DatabaseColumn("name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("type", "varchar(255)", ''));
        $t->column(new DatabaseColumn("content", "varchar(255)", ''));
        $t->column(new DatabaseColumn("ttl", "int(11)", '3600'));
        $t->column(new DatabaseColumn("priority", "int(11)", '0'));
        $t->column(new DatabaseColumn("hidden", "tinyint(1)", '0'));
        $this->table($t);

        $t = new DatabaseTable("domains");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("domain", "varchar(255)", ''));
        $t->column(new DatabaseColumn("reg_info", "longtext"));
        $t->column(new DatabaseColumn("recurring", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("created", "date", '0000-00-00'));
        $t->column(new DatabaseColumn("expiration", "date", '0000-00-00'));
        $t->column(new DatabaseColumn("expiration_prov", "date", '0000-00-00'));
        $t->column(new DatabaseColumn("auto_renew", "int(1)", '1'));
        $t->column(new DatabaseColumn("transfer_lock", "int(1)", '0'));
        $t->column(new DatabaseColumn("privacy", "int(1)", '0'));
        $t->column(new DatabaseColumn("privacy_price", "double(100,2)", '-1.00'));
        $t->column(new DatabaseColumn("status", "enum('REG_WAITING','KK_WAITING','REG_OK','KK_OK','KK_OUT','EXPIRED','TRANSIT','REG_ERROR','KK_ERROR','DELETED')"));
        $t->column(new DatabaseColumn("sent", "int(1)", '0'));
        $t->column(new DatabaseColumn("sent_dns", "int(1)", '0'));
        $t->column(new DatabaseColumn("registrar", "varchar(255)", ''));
        $t->column(new DatabaseColumn("dns_provider", "varchar(255)", ''));
        $t->column(new DatabaseColumn("last_sync", "datetime", '0000-00-00 00:00:00'));
        $t->column(new DatabaseColumn("changed", "int(1)", '0'));
        $t->column(new DatabaseColumn("error", "longtext"));
        $t->column(new DatabaseColumn("customer_wish", "int(1)", '0'));
        $t->column(new DatabaseColumn("customer_when", "datetime", '0000-00-00 00:00:00'));
        $t->column(new DatabaseColumn("trade", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("trade_waiting", "int(1)", '0'));
        $t->column(new DatabaseColumn("csr", "longtext"));
        $t->column(new DatabaseColumn("ssl_cert", "longtext"));
        $t->column(new DatabaseColumn("ssl_sync", "datetime", '0000-00-00 00:00:00'));
        $t->column(new DatabaseColumn("inclusive_id", "int(11)", '0'));
        $t->column(new DatabaseColumn("addon_id", "int(11)", '0'));
        $t->column(new DatabaseColumn("ignore_failed", "int(1)", '0'));
        $t->column(new DatabaseColumn("payment", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("domain_actions");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "PRIMARY KEY AUTO_INCREMENT"));
        $t->column(new DatabaseColumn("tld", "varchar(255)"));
        $t->column(new DatabaseColumn("type", "enum('REG','KK','RENEW')"));
        $t->column(new DatabaseColumn("start", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("end", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("price", "double(100,2)", "0.00"));
        $this->table($t);

        $t = new DatabaseTable("domain_auth2");
        $t->column(new DatabaseColumn("tld", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("price", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("registrar", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("domain_cache");
        $t->column(new DatabaseColumn("domain", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("status", "enum('FREE','TRANS','INVALID')"));
        $t->column(new DatabaseColumn("last_check", "datetime", '0000-00-00 00:00:00'));
        $this->table($t);

        $t = new DatabaseTable("domain_dns_drivers");
        $t->column(new DatabaseColumn("driver", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["driver", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("domain_log");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("domain", "varchar(255)", ''));
        $t->column(new DatabaseColumn("registrar", "varchar(255)", ''));
        $t->column(new DatabaseColumn("url", "varchar(255)", ''));
        $t->column(new DatabaseColumn("request", "longtext"));
        $t->column(new DatabaseColumn("response", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("domain_pricing");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("tld", "varchar(100)", '', 'UNIQUE KEY'));
        $t->column(new DatabaseColumn("top", "int(1)", '0'));
        $t->column(new DatabaseColumn("register", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("register_ek", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("transfer", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("transfer_ek", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("renew", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("renew_ek", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("trade", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("privacy", "double(100,4)", '-1.0000'));
        $t->column(new DatabaseColumn("period", "int(1)", '1'));
        $t->column(new DatabaseColumn("domain_lock", "int(1)", '1'));
        $t->column(new DatabaseColumn("registrar", "varchar(255)", ''));
        $t->column(new DatabaseColumn("dns_provider", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("domain_pricing_override");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("tld", "varchar(255)", ''));
        $t->column(new DatabaseColumn("register", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("transfer", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("renew", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("trade", "double(100,4)", '0.0000'));
        $t->column(new DatabaseColumn("privacy", "double(100,4)", '-1.0000'));
        $this->table($t);

        $t = new DatabaseTable("domain_pricing_template");
        $t->column(new DatabaseColumn("name", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("pricing", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("domain_registrars");
        $t->column(new DatabaseColumn("registrar", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["registrar", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("email_templates");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(100)", null, 'UNIQUE KEY'));
        $t->column(new DatabaseColumn("foreign_name", "varchar(255)", ''));
        $t->column(new DatabaseColumn("title", "longtext"));
        $t->column(new DatabaseColumn("content", "longtext"));
        $t->column(new DatabaseColumn("admin_notification", "int(1)", '0'));
        $t->column(new DatabaseColumn("category", "varchar(255)", 'System'));
        $t->column(new DatabaseColumn("active", "tinyint(1)", '1'));
        $t->column(new DatabaseColumn("vars", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("encashment");
        $t->column(new DatabaseColumn("provider", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["provider", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("fail2ban");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("ip", "varchar(100)", '', 'UNIQUE KEY'));
        $t->column(new DatabaseColumn("failed", "int(11)", '0'));
        $t->column(new DatabaseColumn("until", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("fibu_accounts");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("type", "int(1)", "0"));
        $t->column(new DatabaseColumn("handling", "int(1)", "0"));
        $t->column(new DatabaseColumn("description", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("fibu_journal");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("account", "int(11)", "0"));
        $t->column(new DatabaseColumn("account2", "int(11)", "0"));
        $t->column(new DatabaseColumn("year", "int(4)", "0"));
        $t->column(new DatabaseColumn("month", "int(2)", "0"));
        $t->column(new DatabaseColumn("day", "int(2)", "0"));
        $t->column(new DatabaseColumn("amount", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("amount2", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("tax", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("taxacct", "int(11)", "0"));
        $t->column(new DatabaseColumn("description", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("forum");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(100)"));
        $t->column(new DatabaseColumn("description", "varchar(255)"));
        $t->column(new DatabaseColumn("public", "int(1)", "1"));
        $t->column(new DatabaseColumn("pids", "varchar(255)", ""));
        $t->column(new DatabaseColumn("order", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("forum_entries");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("thread", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("text", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("forum_moderators");
        $t->column(new DatabaseColumn("forum_id", "int(11)"));
        $t->column(new DatabaseColumn("user_id", "int(11)"));
        $t->primaryKey(["forum_id", "user_id"]);
        $this->table($t);

        $t = new DatabaseTable("forum_threads");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("title", "varchar(100)"));
        $t->column(new DatabaseColumn("forum", "int(11)", "0"));
        $t->column(new DatabaseColumn("lock", "int(1)", "0"));
        $this->table($t);

        $t = new DatabaseTable("gateway_logs");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("data", "longtext"));
        $t->column(new DatabaseColumn("log", "longtext"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("gateway", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("gateway_settings");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("gateway", "varchar(255)"));
        $t->column(new DatabaseColumn("setting", "varchar(255)"));
        $t->column(new DatabaseColumn("value", "varchar(255)", ''));
        $this->table($t);

        $t = new DatabaseTable("guest_orders");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("hash", "varchar(255)", ''));
        $t->column(new DatabaseColumn("info", "longtext"));
        $t->column(new DatabaseColumn("cart", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("invoiceitems");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("invoice", "int(11)", '0'));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("qty", "double(100,2)", '1.00'));
        $t->column(new DatabaseColumn("unit", "varchar(20)", ''));
        $t->column(new DatabaseColumn("relid", "int(11)", '0'));
        $t->column(new DatabaseColumn("recurring", "int(11)", '0'));
        $t->column(new DatabaseColumn("tax", "int(1)", '1'));
        $this->table($t);

        $t = new DatabaseTable("invoicelater");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("paid", "int(1)", '1'));
        $this->table($t);

        $t = new DatabaseTable("invoices");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("duedate", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("deliverydate", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("client", "int(11)", '0'));
        $t->column(new DatabaseColumn("customno", "varchar(255)", ""));
        $t->column(new DatabaseColumn("status", "int(1)", '0'));
        $t->column(new DatabaseColumn("reminder", "int(11)", '0'));
        $t->column(new DatabaseColumn("no_reminders", "int(1)", '0'));
        $t->column(new DatabaseColumn("client_data", "longtext"));
        $t->column(new DatabaseColumn("voucher", "int(11)", '0'));
        $t->column(new DatabaseColumn("attachment", "varchar(255)", ""));
        $t->column(new DatabaseColumn("latefee", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("paid_amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("cancel_invoice", "int(11)", '0'));
        $t->column(new DatabaseColumn("letter_sent", "int(1)", '0'));
        $t->column(new DatabaseColumn("encashment_provider", "varchar(255)", ""));
        $t->column(new DatabaseColumn("encashment_file", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("invoice_items_recurring");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("first", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("last", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("status", "int(1)", '0'));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("amount", "double(100,2)", '0.00'));
        $t->column(new DatabaseColumn("show_period", "int(1)", '1'));
        $t->column(new DatabaseColumn("period", "varchar(255)", "1 month"));
        $t->column(new DatabaseColumn("limit_invoices", "int(11)", "-1"));
        $t->column(new DatabaseColumn("limit_date", "date", "0000-00-00"));
        $this->table($t);

        $t = new DatabaseTable("ip_addresses");
        $t->column(new DatabaseColumn("ip", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("product", "int(11)", '0'));
        $t->column(new DatabaseColumn("contract", "int(11)", '0'));
        $this->table($t);

        $t = new DatabaseTable("ip_logs");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)", '0'));
        $t->column(new DatabaseColumn("user", "int(11)", '0'));
        $t->column(new DatabaseColumn("ip", "varchar(255)", ""));
        $t->column(new DatabaseColumn("country", "varchar(255)", ""));
        $t->column(new DatabaseColumn("city", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("knowledgebase");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("category", "int(11)", "0"));
        $t->column(new DatabaseColumn("title", "varchar(255)"));
        $t->column(new DatabaseColumn("article", "longtext"));
        $t->column(new DatabaseColumn("views", "int(11)", "0"));
        $t->column(new DatabaseColumn("ratings", "int(11)", "0"));
        $t->column(new DatabaseColumn("positive", "int(11)", "0"));
        $t->column(new DatabaseColumn("status", "int(1)", "1"));
        $t->column(new DatabaseColumn("order", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("knowledgebase_categories");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)"));
        $t->column(new DatabaseColumn("status", "int(1)", "1"));
        $t->column(new DatabaseColumn("order", "int(11)", "0"));
        $t->column(new DatabaseColumn("language", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("languages");
        $t->column(new DatabaseColumn("language", "varchar(50)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("active", "int(1)", "1"));
        $this->table($t);

        $t = new DatabaseTable("letter_providers");
        $t->column(new DatabaseColumn("provider", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["provider", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("license_texts");
        $t->column(new DatabaseColumn("type", "varchar(50)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)"));
        $t->column(new DatabaseColumn("text", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("newsletter");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("email", "varchar(255)", ""));
        $t->column(new DatabaseColumn("hash", "varchar(255)", ""));
        $t->column(new DatabaseColumn("language", "varchar(255)", ""));
        $t->column(new DatabaseColumn("lists", "varchar(255)", ""));
        $t->column(new DatabaseColumn("reg_time", "int(11)", '0'));
        $t->column(new DatabaseColumn("reg_ip", "varchar(255)", ""));
        $t->column(new DatabaseColumn("conf_time", "int(11)", '0'));
        $t->column(new DatabaseColumn("conf_ip", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("newsletter_categories");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "longtext"));
        $t->column(new DatabaseColumn("standard", "int(1)", "0"));
        $this->table($t);

        $t = new DatabaseTable("notifications");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("text", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("offers");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("title", "longtext"));
        $t->column(new DatabaseColumn("start", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("end", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("status", "int(11)", '0'));
        $t->column(new DatabaseColumn("old_price", "longtext"));
        $t->column(new DatabaseColumn("price", "longtext"));
        $t->column(new DatabaseColumn("url", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("panels");
        $t->column(new DatabaseColumn("server", "int(11)"));
        $t->column(new DatabaseColumn("module", "varchar(50)"));
        $t->column(new DatabaseColumn("data", "longtext"));
        $t->primaryKey(["server", "module"]);
        $this->table($t);

        $t = new DatabaseTable("payment_accounts");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("bank", "varchar(255)", ""));
        $t->column(new DatabaseColumn("account", "varchar(255)", ""));
        $t->column(new DatabaseColumn("credentials", "longtext"));
        $t->column(new DatabaseColumn("balance", "double(100,2)", "0.00"));
        $this->table($t);

        $t = new DatabaseTable("products_prepaid");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("product", "int(11)"));
        $t->column(new DatabaseColumn("days", "int(11)", "30"));
        $t->column(new DatabaseColumn("bonus", "double(100,2)", "0.00"));
        $this->table($t);

        $t = new DatabaseTable("products");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "longtext"));
        $t->column(new DatabaseColumn("order", "int(11)", "0"));
        $t->column(new DatabaseColumn("version", "varchar(255)", "1.0"));
        $t->column(new DatabaseColumn("date", "date"));
        $t->column(new DatabaseColumn("status", "int(1)", "0"));
        $t->column(new DatabaseColumn("price", "double(100,6)"));
        $t->column(new DatabaseColumn("tax", "varchar(255)", "gross"));
        $t->column(new DatabaseColumn("category", "int(11)", "0"));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("affiliate", "double(100,2)", "-1.00"));
        $t->column(new DatabaseColumn("available", "int(11)", "-1"));
        $t->column(new DatabaseColumn("type", "enum('SOFTWARE','HOSTING')", "HOSTING"));
        $t->column(new DatabaseColumn("billing", "varchar(255)", ""));
        $t->column(new DatabaseColumn("module", "varchar(255)", ""));
        $t->column(new DatabaseColumn("username_format", "varchar(255)", "c{contractId}"));
        $t->column(new DatabaseColumn("username_next", "int(11)", "1"));
        $t->column(new DatabaseColumn("username_step", "int(11)", "1"));
        $t->column(new DatabaseColumn("welcome_mail", "int(11)", "0"));
        $t->column(new DatabaseColumn("gitlab_id", "varchar(255)", ""));
        $t->column(new DatabaseColumn("maxpc", "int(11)", "-1"));
        $t->column(new DatabaseColumn("ct", "varchar(255)", ""));
        $t->column(new DatabaseColumn("mct", "varchar(255)", ""));
        $t->column(new DatabaseColumn("np", "varchar(255)", ""));
        $t->column(new DatabaseColumn("setup", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("currency_active", "int(1)", "0"));
        $t->column(new DatabaseColumn("currency_price", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("currency_id", "int(11)", "0"));
        $t->column(new DatabaseColumn("incldomains", "int(11)", "0"));
        $t->column(new DatabaseColumn("incltlds", "longtext"));
        $t->column(new DatabaseColumn("preorder", "int(1)", "0"));
        $t->column(new DatabaseColumn("only_verified", "int(1)", "0"));
        $t->column(new DatabaseColumn("public", "int(1)", "1"));
        $t->column(new DatabaseColumn("hide", "int(1)", "0"));
        $t->column(new DatabaseColumn("customer_groups", "longtext"));
        $t->column(new DatabaseColumn("autodelete", "int(11)", "0"));
        $t->column(new DatabaseColumn("ip_product", "int(11)", "0"));
        $t->column(new DatabaseColumn("product_change", "longtext"));
        $t->column(new DatabaseColumn("price_cgroups", "longtext"));
        $t->column(new DatabaseColumn("usage_billing", "longtext"));
        $t->column(new DatabaseColumn("variants", "longtext"));
        $t->column(new DatabaseColumn("new_cgroup", "int(11)", "-1"));
        $t->column(new DatabaseColumn("desc_on_invoice", "int(1)", "1"));
        $t->column(new DatabaseColumn("domain_choose", "int(1)", "0"));
        $t->column(new DatabaseColumn("uses_wishlist", "int(11)", "0"));
        $t->column(new DatabaseColumn("min_age", "int(11)", "0"));
        $t->column(new DatabaseColumn("prepaid", "int(1)", "0"));
        $t->column(new DatabaseColumn("prorata", "int(1)", "0"));
        $t->column(new DatabaseColumn("old_service", "int(11)", "0"));
        $t->column(new DatabaseColumn("dns_template", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("products_cf");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("product", "int(11)", "0"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("type", "varchar(255)", ""));
        $t->column(new DatabaseColumn("options", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("product_bundles");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "longtext"));
        $t->column(new DatabaseColumn("products", "longtext"));
        $t->column(new DatabaseColumn("price", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("sells", "int(11)", "0"));
        $t->column(new DatabaseColumn("affiliate", "double(100,2)", "-1.00"));
        $this->table($t);

        $t = new DatabaseTable("product_categories");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "longtext"));
        $t->column(new DatabaseColumn("cast", "longtext"));
        $t->column(new DatabaseColumn("view", "int(1)", "1"));
        $t->column(new DatabaseColumn("template", "varchar(100)", "standard"));
        $this->table($t);

        $t = new DatabaseTable("product_provisioning");
        $t->column(new DatabaseColumn("module", "varchar(50)"));
        $t->column(new DatabaseColumn("pid", "int(11)", "0"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["module", "pid", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("product_usage");
        $t->column(new DatabaseColumn("cid", "int(11)", "0"));
        $t->column(new DatabaseColumn("date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("parameter", "varchar(50)", ""));
        $t->column(new DatabaseColumn("utilization", "int(11)", "0"));
        $t->primaryKey(["cid", "date", "parameter"]);
        $this->table($t);

        $t = new DatabaseTable("projects");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("due", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("entgelt", "double(100,2)", "-1.00"));
        $t->column(new DatabaseColumn("entgelt_type", "int(1)", "0"));
        $t->column(new DatabaseColumn("entgelt_done", "int(11)", "0"));
        $t->column(new DatabaseColumn("time_tracking", "varchar(50)", "exact"));
        $t->column(new DatabaseColumn("status", "int(1)", "0"));
        $t->column(new DatabaseColumn("star", "int(1)", "0"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("show_details", "int(1)", "0"));
        $t->column(new DatabaseColumn("files", "longtext"));
        $t->column(new DatabaseColumn("files_expiry", "longtext"));
        $t->column(new DatabaseColumn("product", "int(11)", "0"));
        $t->column(new DatabaseColumn("time_contingent", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("project_tasks");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("project", "int(11)", "0"));
        $t->column(new DatabaseColumn("status", "int(1)", "0"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("entgelt", "double(100,2)", "-1.00"));
        $t->column(new DatabaseColumn("entgelt_type", "int(1)", "0"));
        $t->column(new DatabaseColumn("entgelt_done", "int(11)", "0"));
        $t->column(new DatabaseColumn("color", "varchar(6)", ""));
        $this->table($t);

        $t = new DatabaseTable("project_times");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("admin", "int(11)", "0"));
        $t->column(new DatabaseColumn("task", "int(11)", "0"));
        $t->column(new DatabaseColumn("start", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("end", "datetime", "0000-00-00 00:00:00"));
        $this->table($t);

        $t = new DatabaseTable("project_templates");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("tasks", "longtext"));
        $t->column(new DatabaseColumn("price", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("monitoring_announcements");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("start", "int(11)", "-1"));
        $t->column(new DatabaseColumn("until", "int(11)", "-1"));
        $t->column(new DatabaseColumn("title", "varchar(255)", ""));
        $t->column(new DatabaseColumn("message", "longtext", ""));
        $t->column(new DatabaseColumn("last_changed", "int(11)", "0"));
        $t->column(new DatabaseColumn("priority", "varchar(255)", ""));
        $t->column(new DatabaseColumn("status", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("monitoring_server");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("visible", "int(1)", '1'));
        $t->column(new DatabaseColumn("operating_system", "varchar(1)", ""));
        $t->column(new DatabaseColumn("ssh_host", "varchar(255)", ""));
        $t->column(new DatabaseColumn("ssh_port", "int(5)", "22"));
        $t->column(new DatabaseColumn("ssh_key", "longtext"));
        $t->column(new DatabaseColumn("ssh_error", "varchar(255)", ""));
        $t->column(new DatabaseColumn("ssh_fingerprint", "varchar(400)", ""));
        $t->column(new DatabaseColumn("ssh_fingerprint_last", "varchar(400)", ""));
        $t->column(new DatabaseColumn("ssh_last", "int(11)", "0"));
        $t->column(new DatabaseColumn("ssh_hash", "varchar(255)", ""));
        $t->column(new DatabaseColumn("ssh_valid", "int(11)", "0"));
        $t->column(new DatabaseColumn("server_group", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("monitoring_server_groups");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("monitoring_services");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("server", "int(11)", "0"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("type", "varchar(255)", ""));
        $t->column(new DatabaseColumn("settings", "longtext"));
        $t->column(new DatabaseColumn("last_called", "int(11)", "0"));
        $t->column(new DatabaseColumn("last_result", "longtext"));
        $t->column(new DatabaseColumn("internal", "int(1)", "0"));
        $t->column(new DatabaseColumn("active", "int(1)", "1"));
        $this->table($t);

        $t = new DatabaseTable("monitoring_updates");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("server", "int(11)", "0"));
        $t->column(new DatabaseColumn("package", "varchar(255)", ""));
        $t->column(new DatabaseColumn("new", "varchar(255)", ""));
        $t->column(new DatabaseColumn("status", "enum('new','waiting','ignored')"));
        $this->table($t);

        $t = new DatabaseTable("reminders");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("days", "int(11)", "0"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("color", "varchar(255)", ""));
        $t->column(new DatabaseColumn("bold", "int(1)", "0"));
        $t->column(new DatabaseColumn("countries", "longtext"));
        $t->column(new DatabaseColumn("b2c", "int(1)", "0"));
        $t->column(new DatabaseColumn("b2c_mail", "int(11)", "0"));
        $t->column(new DatabaseColumn("b2c_admin_mail", "int(11)", "0"));
        $t->column(new DatabaseColumn("b2c_item", "varchar(255)", ""));
        $t->column(new DatabaseColumn("b2c_percent", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("b2c_absolute", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("b2c_letter", "int(1)", "0"));
        $t->column(new DatabaseColumn("b2c_letter_text", "longtext"));
        $t->column(new DatabaseColumn("b2c_letter_send", "varchar(255)", ""));
        $t->column(new DatabaseColumn("b2b", "int(1)", "0"));
        $t->column(new DatabaseColumn("b2b_mail", "int(11)", "0"));
        $t->column(new DatabaseColumn("b2b_admin_mail", "int(11)", "0"));
        $t->column(new DatabaseColumn("b2b_item", "varchar(255)", ""));
        $t->column(new DatabaseColumn("b2b_percent", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("b2b_absolute", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("b2b_letter", "int(1)", "0"));
        $t->column(new DatabaseColumn("b2b_letter_text", "longtext"));
        $t->column(new DatabaseColumn("b2b_letter_send", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("salutations");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "PRIMARY KEY AUTO_INCREMENT"));
        $t->column(new DatabaseColumn("time", "varchar(100)", ""));
        $t->column(new DatabaseColumn("language", "varchar(100)", ""));
        $t->column(new DatabaseColumn("gender", "enum('MALE', 'FEMALE', 'DIVERS', '')", ""));
        $t->column(new DatabaseColumn("cgroup", "int(11)", "-1"));
        $t->column(new DatabaseColumn("b2b", "int(1)", "-1"));
        $t->column(new DatabaseColumn("country", "int(11)", "-1"));
        $t->column(new DatabaseColumn("salutation", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("scoring");
        $t->column(new DatabaseColumn("provider", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["provider", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("settings");
        $t->column(new DatabaseColumn("key", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("sms_providers");
        $t->column(new DatabaseColumn("provider", "varchar(50)"));
        $t->column(new DatabaseColumn("setting", "varchar(50)"));
        $t->column(new DatabaseColumn("value", "longtext"));
        $t->primaryKey(["provider", "setting"]);
        $this->table($t);

        $t = new DatabaseTable("external_dyndns");
        $t->column(new DatabaseColumn("domain", "varchar(100)", null, "PRIMARY KEY"));
        $t->column(new DatabaseColumn("password", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("sofort_open_transactions");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("amount", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("date", "int(11)", "0"));
        $t->column(new DatabaseColumn("last_reminder", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("suppliers");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)"));
        $t->column(new DatabaseColumn("street", "varchar(255)", ""));
        $t->column(new DatabaseColumn("street_number", "varchar(255)", ""));
        $t->column(new DatabaseColumn("postcode", "varchar(255)", ""));
        $t->column(new DatabaseColumn("city", "varchar(255)", ""));
        $t->column(new DatabaseColumn("products", "varchar(255)"));
        $t->column(new DatabaseColumn("notes", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("supplier_contracts");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("supplier", "int(11)", "0"));
        $t->column(new DatabaseColumn("price", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("period", "enum('1','3','6','12')", "1"));
        $t->column(new DatabaseColumn("ct", "varchar(255)", ""));
        $t->column(new DatabaseColumn("np", "varchar(255)", ""));
        $t->column(new DatabaseColumn("cancellation_date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("name", "varchar(255)"));
        $t->column(new DatabaseColumn("notes", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("support_answers");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("cat", "int(11)", "0"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("message", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("support_answer_categories");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("support_departments");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(100)", null, "UNIQUE KEY"));
        $t->column(new DatabaseColumn("public", "int(1)", "0"));
        $t->column(new DatabaseColumn("confirmation", "int(11)", "0"));
        $this->table($t);

        $t = new DatabaseTable("support_department_staff");
        $t->column(new DatabaseColumn("staff", "int(11)", "0"));
        $t->column(new DatabaseColumn("dept", "int(11)", "0"));
        $t->primaryKey(["staff", "dept"]);
        $this->table($t);

        $t = new DatabaseTable("support_email");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("dept", "int(11)", "0"));
        $t->column(new DatabaseColumn("email", "varchar(255)"));
        $t->column(new DatabaseColumn("pop3", "int(1)", "0"));
        $t->column(new DatabaseColumn("pop3_host", "varchar(255)", ""));
        $t->column(new DatabaseColumn("pop3_ssl", "int(1)", "0"));
        $t->column(new DatabaseColumn("pop3_port", "int(11)", "110"));
        $t->column(new DatabaseColumn("pop3_user", "varchar(255)", ""));
        $t->column(new DatabaseColumn("pop3_password", "varchar(255)", ""));
        $t->column(new DatabaseColumn("send", "int(1)", "0"));
        $t->column(new DatabaseColumn("sender_name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("smtp", "int(1)", "0"));
        $t->column(new DatabaseColumn("smtp_host", "varchar(255)", ""));
        $t->column(new DatabaseColumn("smtp_ssl", "int(1)", "0"));
        $t->column(new DatabaseColumn("smtp_port", "int(11)", "25"));
        $t->column(new DatabaseColumn("smtp_user", "varchar(255)", ""));
        $t->column(new DatabaseColumn("smtp_password", "varchar(255)", ""));
        $t->column(new DatabaseColumn("catchall", "int(1)", "0"));
        $this->table($t);

        $t = new DatabaseTable("support_escalations");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("department", "longtext"));
        $t->column(new DatabaseColumn("status", "longtext"));
        $t->column(new DatabaseColumn("priority", "longtext"));
        $t->column(new DatabaseColumn("cgroup", "longtext"));
        $t->column(new DatabaseColumn("upgrade", "longtext"));
        $t->column(new DatabaseColumn("time_elapsed", "int(11)"));
        $t->column(new DatabaseColumn("new_department", "varchar(255)", ""));
        $t->column(new DatabaseColumn("new_status", "varchar(255)", ""));
        $t->column(new DatabaseColumn("new_priority", "varchar(255)", ""));
        $t->column(new DatabaseColumn("realtime_notification", "longtext"));
        $t->column(new DatabaseColumn("webhook_url", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("support_filter");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("field", "enum('subject','email')"));
        $t->column(new DatabaseColumn("type", "enum('contains','is')"));
        $t->column(new DatabaseColumn("value", "varchar(255)"));
        $t->column(new DatabaseColumn("action", "enum('delete','close')"));
        $this->table($t);

        $t = new DatabaseTable("support_signatures");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("text", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("support_signature_staff");
        $t->column(new DatabaseColumn("staff", "int(11)", "0"));
        $t->column(new DatabaseColumn("signature", "int(11)", "0"));
        $t->primaryKey(["staff", "signature"]);
        $this->table($t);

        $t = new DatabaseTable("support_tickets");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ""));
        $t->column(new DatabaseColumn("dept", "int(11)", "0"));
        $t->column(new DatabaseColumn("status", "int(1)", "0"));
        $t->column(new DatabaseColumn("fake_status", "int(1)", "-1"));
        $t->column(new DatabaseColumn("created", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("updated", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("priority", "int(1)", "0"));
        $t->column(new DatabaseColumn("sender", "varchar(255)", ""));
        $t->column(new DatabaseColumn("customer", "int(11)", "0"));
        $t->column(new DatabaseColumn("admins_read", "longtext"));
        $t->column(new DatabaseColumn("cc", "longtext"));
        $t->column(new DatabaseColumn("draft", "longtext"));
        $t->column(new DatabaseColumn("draft_owner", "int(11)", "0"));
        $t->column(new DatabaseColumn("rating", "int(1)", "0"));
        $t->column(new DatabaseColumn("customer_access", "int(1)", "1"));
        $t->column(new DatabaseColumn("can_closed", "int(1)", "1"));
        $t->column(new DatabaseColumn("recall", "int(11)", "0"));
        $t->column(new DatabaseColumn("escalations", "longtext"));
        $t->column(new DatabaseColumn("upgrade_id", "int(11)", "0"));
        $t->column(new DatabaseColumn("upgrade_prio_before", "int(1)", "-1"));
        $this->table($t);

        $t = new DatabaseTable("support_ticket_answers");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("ticket", "int(11)", "0"));
        $t->column(new DatabaseColumn("time", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ""));
        $t->column(new DatabaseColumn("message", "longtext"));
        $t->column(new DatabaseColumn("priority", "int(1)", "0"));
        $t->column(new DatabaseColumn("sender", "varchar(255)", ""));
        $t->column(new DatabaseColumn("staff", "int(11)", "0"));
        $t->column(new DatabaseColumn("customer_read", "int(1)", "0"));
        $this->table($t);

        $t = new DatabaseTable("support_ticket_attachments");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("message", "int(11)", "0"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("file", "longblob"));
        $this->table($t);

        $t = new DatabaseTable("support_upgrades");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("name", "varchar(255)", ""));
        $t->column(new DatabaseColumn("icon", "varchar(255)", ""));
        $t->column(new DatabaseColumn("link", "varchar(255)", ""));
        $t->column(new DatabaseColumn("department", "longtext", ""));
        $t->column(new DatabaseColumn("status", "longtext", ""));
        $t->column(new DatabaseColumn("price", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("valid", "enum('answer','unlimited')", "answer"));
        $t->column(new DatabaseColumn("color", "varchar(7)", ""));
        $t->column(new DatabaseColumn("new_priority", "int(1)", "-1"));
        $this->table($t);

        $t = new DatabaseTable("system_status");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("type", "varchar(255)", "download"));
        $t->column(new DatabaseColumn("relship", "varchar(255)"));
        $this->table($t);

        $t = new DatabaseTable("tax_deduct");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("country", "int(11)", "0"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("description", "varchar(255)", ""));
        $t->column(new DatabaseColumn("amount", "double(100,2)", "0.00"));
        $this->table($t);

        $t = new DatabaseTable("terms_of_service");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "int(11)"));
        $t->column(new DatabaseColumn("text", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("testimonials");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("active", "int(1)", "0"));
        $t->column(new DatabaseColumn("rating", "int(1)", "5"));
        $t->column(new DatabaseColumn("subject", "varchar(255)", ""));
        $t->column(new DatabaseColumn("text", "longtext"));
        $t->column(new DatabaseColumn("author", "int(11)", "0"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("answer", "longtext"));
        $this->table($t);

        $t = new DatabaseTable("visits");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("time", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("ip", "varchar(255)", ""));
        $t->column(new DatabaseColumn("os", "varchar(255)", ""));
        $t->column(new DatabaseColumn("browser", "varchar(255)", ""));
        $t->column(new DatabaseColumn("start_page", "varchar(255)", ""));
        $t->column(new DatabaseColumn("pages", "int(11)", "1"));
        $t->column(new DatabaseColumn("last_action", "datetime", "0000-00-00 00:00:00"));
        $t->column(new DatabaseColumn("end_page", "varchar(255)", ""));
        $t->column(new DatabaseColumn("country", "varchar(255)", ""));
        $this->table($t);

        $t = new DatabaseTable("vouchers");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("code", "varchar(100)", "", "UNIQUE KEY"));
        $t->column(new DatabaseColumn("type", "varchar(255)", "percentage"));
        $t->column(new DatabaseColumn("value", "double(100,2)", "0.00"));
        $t->column(new DatabaseColumn("uses", "int(11)", "0"));
        $t->column(new DatabaseColumn("max_uses", "int(11)", "-1"));
        $t->column(new DatabaseColumn("valid_for", "longtext"));
        $t->column(new DatabaseColumn("valid_from", "int(11)", "0"));
        $t->column(new DatabaseColumn("valid_to", "int(11)", "0"));
        $t->column(new DatabaseColumn("max_per_user", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("active", "int(11)", "1"));
        $this->table($t);

        $t = new DatabaseTable("wishlist");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("product", "int(11)", "0"));
        $t->column(new DatabaseColumn("title", "varchar(255)", ""));
        $t->column(new DatabaseColumn("description", "longtext"));
        $t->column(new DatabaseColumn("answer", "varchar(255)", ""));
        $t->column(new DatabaseColumn("date", "date", "0000-00-00"));
        $t->column(new DatabaseColumn("ack", "int(1)", "0"));
        $this->table($t);

        $t = new DatabaseTable("wishlist_comments");
        $t->column(new DatabaseColumn("ID", "int(11)", null, "AUTO_INCREMENT PRIMARY KEY"));
        $t->column(new DatabaseColumn("wish", "int(11)", "0"));
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("time", "int(11)", "0"));
        $t->column(new DatabaseColumn("message", "longtext"));
        $t->column(new DatabaseColumn("author", "varchar(255)", ""));
        $t->column(new DatabaseColumn("ack", "int(1)", "0"));
        $this->table($t);

        $t = new DatabaseTable("wishlist_likes");
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("wish", "int(11)", "0"));
        $t->primaryKey(["user", "wish"]);
        $this->table($t);

        $t = new DatabaseTable("wishlist_product_abo");
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("product", "int(11)", "0"));
        $t->primaryKey(["user", "product"]);
        $this->table($t);

        $t = new DatabaseTable("wishlist_wish_abo");
        $t->column(new DatabaseColumn("user", "int(11)", "0"));
        $t->column(new DatabaseColumn("wish", "int(11)", "0"));
        $t->primaryKey(["user", "wish"]);
        $this->table($t);
    }

    public function initData($db, $prefix)
    {
        global $CFG;

        $this->time["init_client_fields"] = microtime(true);

        $db->query(str_replace("%prefix%", $prefix, "INSERT INTO `%prefix%client_fields` (`ID`, `name`, `foreign_name`, `active`, `position`, `customer`, `duty`, `system`, `regex`) VALUES
        (1, 'Vorname', 'Firstname', 1, 0, 1, 1, 1, ''),
        (2, 'Nachname', 'Lastname', 1, 0, 1, 1, 1, ''),
        (3, 'Firma', 'Company', 1, 0, 2, 0, 1, ''),
        (4, 'E-Mailadresse', 'Email address', 1, 0, 2, 1, 1, ''),
        (5, 'Straße', 'Street', 1, 0, 2, 1, 2, ''),
        (6, 'Hausnummer', 'Street number', 1, 0, 2, 1, 2, ''),
        (7, 'Postleitzahl', 'Postcode', 1, 0, 2, 1, 2, ''),
        (8, 'Ort', 'City', 1, 0, 2, 1, 2, ''),
        (9, 'Land', 'Country', 1, 0, 2, 1, 2, ''),
        (10, 'Telefonnummer', 'Phone number', 1, 0, 2, 0, 2, ''),
        (11, 'Geburtstag', 'Birthday', 1, 0, 2, 0, 2, ''),
        (12, 'Preislevel', 'Price level', 1, 1, 0, 1, 1, ''),
        (13, 'Faxnummer', 'Fax number', 1, 0, 2, 0, 2, ''),
        (16, 'USt-IdNr.', 'VAT ID', 1, 0, 2, 0, 2, ''),
        (17, 'Webseite', 'Website', 1, 0, 2, 0, 2, ''),
        (18, 'Anrede', 'Salutation', 1, -1, 1, 1, 1, '');"));

        $db->query(str_replace("%prefix%", $prefix, "INSERT INTO `%prefix%client_fields` (`ID`, `name`, `foreign_name`, `active`, `position`, `customer`, `duty`, `system`, `regex`) VALUES
        (100, 'Woher kennen Sie uns?', 'How did you find us?', 1, 1, 0, 0, 1, '');"));

        $this->time["init_client_fields"] = microtime(true) - $this->time["init_client_fields"];

        $this->time["init_dns_templates"] = microtime(true);
        if (!$db->query("SELECT 1 FROM " . $prefix . "dns_templates WHERE ID = 1")->num_rows) {
            $db->query("INSERT INTO " . $prefix . "dns_templates (`ID`, `name`) VALUES (1, 'Standard')");
            $db->query("DELETE FROM " . $prefix . "dns_template_records WHERE template_id = 1");

            $db->query("INSERT INTO " . $prefix . "dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES (1, '', 'A', '%ip%', 3600, 0)");
            $db->query("INSERT INTO " . $prefix . "dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES (1, 'www', 'A', '%ip%', 3600, 0)");
            $db->query("INSERT INTO " . $prefix . "dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES (1, '*', 'A', '%ip%', 3600, 0)");
            $db->query("INSERT INTO " . $prefix . "dns_template_records (`template_id`, `name`, `type`, `content`, `ttl`, `priority`) VALUES (1, '', 'MX', '%hostname%', 3600, 10)");
        }
        $this->time["init_dns_templates"] = microtime(true) - $this->time["init_dns_templates"];

        $this->time["init_license_texts"] = microtime(true);

        $query = <<<EOQ
        INSERT INTO `%prefix%license_texts` (`type`, `name`, `text`) VALUES
        ('e', 'a:2:{s:7:\"deutsch\";s:19:\"Einzelplatzlizenzen\";s:7:\"english\";s:21:\"Single place licenses\";}', 'a:2:{s:7:\"deutsch\";s:0:\"\";s:7:\"english\";s:0:\"\";}'),
        ('r', 'a:2:{s:7:\"deutsch\";s:16:\"Resellerlizenzen\";s:7:\"english\";s:17:\"Reseller licenses\";}', 'a:2:{s:7:\"deutsch\";s:0:\"\";s:7:\"english\";s:0:\"\";}');
EOQ;

        $db->query(str_replace("%prefix%", $prefix, $query));

        $this->time["init_license_texts"] = microtime(true) - $this->time["init_license_texts"];
        $this->time["init_email_templates"] = microtime(true);

        $queries = <<<EOQ
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(2, 'Header', 'Header', '', 'a:2:{s:7:"deutsch";s:12:"%salutation%";s:7:"english";s:12:"%salutation%";}', 0, 'System', '%name%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(3, 'Bestellbestätigung', 'Order confirmation', 'a:2:{s:7:\"deutsch\";s:15:\"Ihre Bestellung\";s:7:\"english\";s:10:\"Your order\";}', 'a:2:{s:7:\"deutsch\";s:225:\"Vielen Dank für Ihre Bestellung über %amount% bei %pagename%.\r\n\r\nFalls Sie Dienstleistungen bestellt haben, werden wir uns nochmal seperat per E-Mail melden.\r\n\r\nIm Anhang finden Sie unsere allgemeinen Geschäftsbedingungen.\";s:7:\"english\";s:185:\"Thank you for your order over %amount% at %pagename%.\r\n\r\nIf you have ordered any services, you will receive another mail for this shortly.\r\n\r\nYou can find our terms of service attached.\";}', 0, 'Kunde', '%amount%\r\n%order%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(4, 'Konto wurde aktiviert', 'Account activated', 'a:3:{s:7:\"english\";s:26:\"Your account was activated\";s:7:\"deutsch\";s:25:\"Ihr Konto wurde aktiviert\";s:16:\"deutsch_informal\";s:26:\"Dein Konto wurde aktiviert\";}', 'a:3:{s:7:\"english\";s:153:\"Thank you for your registration at %pagename%!\r\n\r\nYour account was just created successful, you can now login in the customer area with your credentials.\";s:7:\"deutsch\";s:140:\"Vielen Dank für Ihre Registrierung auf %pagename%!\r\n\r\nIhr Konto wurde soeben angelegt, Sie können sich nun in den Kundenbereich einloggen.\";s:16:\"deutsch_informal\";s:140:\"Vielen Dank für Deine Registrierung auf %pagename%!\r\n\r\nDein Konto wurde soeben angelegt, Du kannst Dich nun in den Kundenbereich einloggen.\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(5, 'E-Mailadresse geändert', 'Email changed', 'a:3:{s:7:\"english\";s:21:\"Email address changed\";s:7:\"deutsch\";s:23:\"E-Mailadresse geändert\";s:16:\"deutsch_informal\";s:23:\"E-Mailadresse geändert\";}', 'a:3:{s:7:\"english\";s:177:\"Your email address at %pagename% was just changed:\r\n\r\nOld email address: %old%\r\nNew email address: %new%\r\n\r\nIf you do not made any changes, please inform us as soon as possible!\";s:7:\"deutsch\";s:220:\"Ihre E-Mailadresse auf %pagename% wurde soeben geändert:\r\n\r\nAlte E-Mailadresse: %old%\r\nNeue E-Mailadresse: %new%\r\n\r\nSollten Sie diese Änderung nicht durchgeführt haben, melden Sie sich bitte schnellstmöglich bei uns!\";s:16:\"deutsch_informal\";s:216:\"Deine E-Mailadresse auf %pagename% wurde soeben geändert:\r\n\r\nAlte E-Mailadresse: %old%\r\nNeue E-Mailadresse: %new%\r\n\r\nSolltest Du diese Änderung nicht durchgeführt haben, melde Dich bitte schnellstmöglich bei uns!\";}', 0, 'Kunde', '%old%\r\n%new%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(6, 'E-Mailänderung storniert', 'Email change cancelled', 'a:3:{s:7:\"english\";s:22:\"Email change cancelled\";s:7:\"deutsch\";s:25:\"E-Mailänderung storniert\";s:16:\"deutsch_informal\";s:25:\"E-Mailänderung storniert\";}', 'a:3:{s:7:\"english\";s:157:\"You have just cancelled all wished email changes at %pagename%.\r\n\r\nIf you do not have cancelled anything, please change your credentials as soon as possible!\";s:7:\"deutsch\";s:200:\"Sie haben soeben alle Ihre Wünsche für eine E-Mailänderung auf %pagename% storniert.\r\n\r\nSollten Sie diese Änderung nicht durchgeführt haben, ändern Sie bitte schnellstmöglich Ihre Zugangsdaten!\";s:16:\"deutsch_informal\";s:196:\"Du hast soeben alle Deine Wünsche für eine E-Mailänderung auf %pagename% storniert.\r\n\r\nSolltest Du diese Änderung nicht durchgeführt haben, ändere bitte schnellstmöglich Deine Zugangsdaten!\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(7, 'Passwort angefordert', 'Requested password', 'a:3:{s:7:\"english\";s:16:\"Password request\";s:7:\"deutsch\";s:19:\"Passwortanforderung\";s:16:\"deutsch_informal\";s:19:\"Passwortanforderung\";}', 'a:3:{s:7:\"english\";s:243:\"Someone just requested a new password for your account at %pagename%.\r\n\r\nPlease click on this link to set a new password:\r\n%link%\r\n\r\nThis link is only valid for two hours!\r\n\r\nIf you do not requested any password reset, please delete this mail.\";s:7:\"deutsch\";s:281:\"Soeben wurde auf %pagename% ein neues Passwort für Ihr Konto angefordert.\r\n\r\nBitte klicken Sie auf den folgenden Link, um ein neues Passwort zu setzen:\r\n%link%\r\n\r\nDer Link ist nur 2 Stunden gültig!\r\n\r\nFalls Sie diese Mail nicht angefordert hast, ignorieren Sie sie bitte einfach.\";s:16:\"deutsch_informal\";s:271:\"Soeben wurde auf %pagename% ein neues Passwort für Dein Konto angefordert.\r\n\r\nBitte klicke auf den folgenden Link, um ein neues Passwort zu setzen:\r\n%link%\r\n\r\nDer Link ist nur 2 Stunden gültig!\r\n\r\nFalls Du diese Mail nicht angefordert hast, ignoriere sie bitte einfach.\";}', 0, 'Kunde', '%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(8, 'Login-Benachrichtigung', 'Login notification', 'a:3:{s:7:\"english\";s:18:\"Login notification\";s:7:\"deutsch\";s:21:\"Loginbenachrichtigung\";s:16:\"deutsch_informal\";s:21:\"Loginbenachrichtigung\";}', 'a:3:{s:7:\"english\";s:140:\"Someone just logged into your account at %pagename%.\r\n\r\nIf you do not performed a login, please change your credentials as soon as possible.\";s:7:\"deutsch\";s:139:\"Soeben hat ein Login in Ihr Konto auf %pagename% stattgefunden.\r\n\r\nFalls Sie das nicht waren, ändern Sie bitte umgehend Ihre Zugangsdaten.\";s:16:\"deutsch_informal\";s:136:\"Soeben hat ein Login in Dein Konto auf %pagename% stattgefunden.\r\n\r\nFalls Du das nicht warst, ändere bitte umgehend Deine Zugangsdaten.\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(9, 'Zwei-Faktor aktiviert', 'Two-factor activated', 'a:3:{s:7:\"english\";s:35:\"Two-factor authentication activated\";s:7:\"deutsch\";s:13:\"2FA aktiviert\";s:16:\"deutsch_informal\";s:13:\"2FA aktiviert\";}', 'a:3:{s:7:\"english\";s:159:\"You have just activated the two-factor authentication for your account at %pagename%.\r\n\r\nIf you did not made this change, please inform us as soon as possible!\";s:7:\"deutsch\";s:183:\"Sie haben soeben die Zwei-Faktor-Authentifizierung auf %pagename% aktiviert.\r\n\r\nSollten Sie diese Änderung nicht durchgeführt haben, melden Sie sich bitte schnellstmöglich bei uns!\";s:16:\"deutsch_informal\";s:176:\"Du hast soeben die Zwei-Faktor-Authentifizierung auf %pagename% aktiviert.\r\n\r\nSolltest Du diese Änderung nicht durchgeführt haben, melde Dich bitte schnellstmöglich bei uns!\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(10, 'Zwei-Faktor deaktiviert', 'Two-factor deactivated', 'a:3:{s:7:\"english\";s:37:\"Two-factor authentication deactivated\";s:7:\"deutsch\";s:15:\"2FA deaktiviert\";s:16:\"deutsch_informal\";s:15:\"2FA deaktiviert\";}', 'a:3:{s:7:\"english\";s:161:\"You have just deactivated the two-factor authentication for your account at %pagename%.\r\n\r\nIf you did not made this change, please inform us as soon as possible!\";s:7:\"deutsch\";s:185:\"Sie haben soeben die Zwei-Faktor-Authentifizierung auf %pagename% deaktiviert.\r\n\r\nSollten Sie diese Änderung nicht durchgeführt haben, melden Sie sich bitte schnellstmöglich bei uns!\";s:16:\"deutsch_informal\";s:178:\"Du hast soeben die Zwei-Faktor-Authentifizierung auf %pagename% deaktiviert.\r\n\r\nSolltest Du diese Änderung nicht durchgeführt haben, melde Dich bitte schnellstmöglich bei uns!\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(11, 'Benutzerdaten geändert', 'User data changed', 'a:3:{s:7:\"english\";s:15:\"Profile changed\";s:7:\"deutsch\";s:16:\"Profil geändert\";s:16:\"deutsch_informal\";s:16:\"Profil geändert\";}', 'a:3:{s:7:\"english\";s:124:\"You have just changed your profile at %pagename%.\r\n\r\nIf you have not make any changes, please inform us as soon as possible!\";s:7:\"deutsch\";s:158:\"Sie haben soeben Ihr Profil auf %pagename% bearbeitet.\r\n\r\nWenn Sie diese Änderung nicht durchgeführt haben, melden Sie sich bitte schnellstmöglich bei uns!\";s:16:\"deutsch_informal\";s:155:\"Du hast soeben Dein Profil auf %pagename% bearbeitet.\r\n\r\nSolltest Du diese Änderung nicht durchgeführt haben, melde Dich bitte schnellstmöglich bei uns!\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(12, 'Neuregistrierung', 'Registration confirmation', 'a:3:{s:7:\"english\";s:12:\"Registration\";s:7:\"deutsch\";s:18:\"Ihre Registrierung\";s:16:\"deutsch_informal\";s:19:\"Deine Registrierung\";}', 'a:3:{s:7:\"english\";s:299:\"You have just requested a new account at %pagename%.\r\n\r\nTo complete the registration process and set your password, please click on this link within the next 48 hours:\r\n%link%\r\n\r\nIf you do not submitted your email address on our page, please delete this email. Our system does not hold any data yet.\";s:7:\"deutsch\";s:355:\"Sie haben gerade bei %pagename% eine Registrierung beantragt.\r\n\r\nUm diese zu vervollständigen und Ihr Passwort zu setzen, klicken Sie bitte auf folgenden Link:\r\n%link%\r\n\r\nDer Link ist nur 48 Stunden gültig!\r\n\r\nWenn Sie diese Registrierung nicht durchgeführt haben, löschen Sie diese E-Mail einfach. In unserem System sind noch keine Daten gespeichert.\";s:16:\"deutsch_informal\";s:342:\"Du hast gerade bei %pagename% eine Registrierung beantragt.\r\n\r\nUm diese zu vervollständigen und Dein Passwort zu setzen, klicke bitte auf folgenden Link:\r\n%link%\r\n\r\nDer Link ist nur 48 Stunden gültig!\r\n\r\nWenn Du diese Registrierung nicht durchgeführt hast, lösche diese E-Mail einfach. In unserem System sind noch keine Daten gespeichert.\";}', 0, 'Kunde', '%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(13, 'Passwort zurückgesetzt', 'Password reset successful', 'a:3:{s:7:\"english\";s:14:\"Password reset\";s:7:\"deutsch\";s:23:\"Passwort zurückgesetzt\";s:16:\"deutsch_informal\";s:23:\"Passwort zurückgesetzt\";}', 'a:3:{s:7:\"english\";s:121:\"You just have reset your password at %pagename%.\r\n\r\nIf you do not made this change, please inform us as soon as possible!\";s:7:\"deutsch\";s:167:\"Sie haben soeben Ihr Passwort auf %pagename% zurückgesetzt.\r\n\r\nSollten Sie diese Änderung nicht durchgeführt haben, melden Sie sich bitte schnellstmöglich bei uns!\";s:16:\"deutsch_informal\";s:161:\"Du hast soeben Dein Passwort auf %pagename% zurückgesetzt.\r\n\r\nSolltest Du diese Änderung nicht durchgeführt haben, melde Dich bitte schnellstmöglich bei uns!\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(14, 'Zwei-Faktor-Code fehlerhaft', 'Two-factor code wrong', 'a:3:{s:7:\"english\";s:32:\"Two-factor authentication failed\";s:7:\"deutsch\";s:19:\"2FA-Code fehlerhaft\";s:16:\"deutsch_informal\";s:19:\"2FA-Code fehlerhaft\";}', 'a:3:{s:7:\"english\";s:294:\"Someone tried to gain access to your account on %pagename% with a wrong two-factor code.\r\n\r\nThis means, that he could not login into your account at all (if you do not get another mail) but recognizes your password.\r\n\r\nIf this person is not you, please change your password as soon as possible!\";s:7:\"deutsch\";s:292:\"Soeben wurde versucht, sich auf %pagename% mit einem falschen 2FA-Code zu authentifizieren.\r\n\r\nDas bedeutet, dass derjenige keinen vollständigen Zugriff auf Ihr Konto hat, jedoch Ihr Passwort kennt.\r\n\r\nSollten Ihr das nicht gewesen sein, ändern Sie bitte schnellstmöglich Ihr Zugangsdaten!\";s:16:\"deutsch_informal\";s:292:\"Soeben wurde versucht, sich auf %pagename% mit einem falschen 2FA-Code zu authentifizieren.\r\n\r\nDas bedeutet, dass derjenige keinen vollständigen Zugriff auf Dein Konto hat, jedoch Dein Passwort kennt.\r\n\r\nSolltest Du das nicht gewesen sein, ändere bitte schnellstmöglich Deine Zugangsdaten!\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(15, 'Zwei-Faktor-Code richtig', 'Two-factor code correct', 'a:3:{s:7:\"english\";s:14:\"TFA successful\";s:7:\"deutsch\";s:19:\"2FA-Code akzeptiert\";s:16:\"deutsch_informal\";s:19:\"2FA-Code akzeptiert\";}', 'a:3:{s:7:\"english\";s:368:\"Someone just entered a correct two factor authentication code for your account at %pagename%.\r\n\r\nThis means that this person have your password and perhaps even your tfa secret (or is very lucky).\r\n\r\nIf this person is not you, please change your password and two factor settings as soon as possible! Please also check your two factor device for any malicious software.\";s:7:\"deutsch\";s:427:\"Soeben hat jemand für Ihr Konto auf %pagename% einen richtigen 2FA-Code eingegeben.\r\n\r\nDas bedeutet, dass diese Person Ihr Passwort und vielleicht sogar Ihren 2FA-Schlüssel kennt (oder sehr viel Glück hatte).\r\n\r\nWenn Sie diese Eingabe nicht durchgeführt haben, ändern Sie bitte schnellstmöglich Ihr Passwort und Ihre 2FA-Einstellungen! Prüfen Sie bitte auch, ob Sie irgendwelche Schadsoftware auf Ihrem 2FA-Gerät haben.\";s:16:\"deutsch_informal\";s:420:\"Soeben hat jemand für Dein Konto auf %pagename% einen richtigen 2FA-Code eingegeben.\r\n\r\nDas bedeutet, dass diese Person Dein Passwort und vielleicht sogar Deinen 2FA-Schlüssel kennt (oder sehr viel Glück hatte).\r\n\r\nWenn Du diese Eingabe nicht durchgeführt hast, ändere bitte schnellstmöglich Dein Passwort und Deine 2FA-Einstellungen! Prüfe bitte auch, ob Du irgendwelche Schadsoftware auf Deinem 2FA-Gerät hast.\";}', 0, 'Kunde', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(18, 'Guthabenaufladung', 'Credit top-up', 'a:2:{s:7:\"deutsch\";s:17:\"Guthabenaufladung\";s:7:\"english\";s:16:\"Account charging\";}', 'a:2:{s:7:\"deutsch\";s:96:\"Ihr Guthaben bei %pagename% wurde soeben per %processor% um %amount% aufgeladen.\r\n\r\nVielen Dank!\";s:7:\"english\";s:87:\"Your credit at %pagename% has just been charged %amount% via %processor%.\r\n\r\nThank you!\";}', 0, 'Kunde', '%processor%\r\n%amount%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(22, 'Footer', 'Footer', '', 'a:2:{s:7:\"deutsch\";s:37:\"Mit freundlichen Grüßen\r\n%pagename%\";s:7:\"english\";s:24:\"Best Regards\r\n%pagename%\";}', 0, 'System', '');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(23, 'E-Mailänderung (alte Adresse)', 'Email change (old address)', 'a:3:{s:7:\"english\";s:24:\"Request for email change\";s:7:\"deutsch\";s:25:\"E-Mailänderung beantragt\";s:16:\"deutsch_informal\";s:25:\"E-Mailänderung beantrage\";}', 'a:3:{s:7:\"english\";s:211:\"You have just requested to change your email address for your account at %pagename%.\r\n\r\nOld email address: %old%\r\nNew email address: %new%\r\n\r\nlf you do not made any changes, please inform us as soon as possible.\";s:7:\"deutsch\";s:248:\"Es wurde soeben eine Änderung Ihrer E-Mailadresse auf %pagename% beantragt.\r\n\r\nAlte E-Mailadresse: %old%\r\nNeue E-Mailadresse: %new%\r\n\r\nSollten Sie diese Änderung nicht vorgenommen haben, wenden Sie sich bitte schnellstmöglich an unseren Support.\";s:16:\"deutsch_informal\";s:232:\"Es wurde soeben eine Änderung Deiner E-Mailadresse auf %pagename% beantragt.\r\n\r\nAlte E-Mailadresse: %old%\r\nNeue E-Mailadresse: %new%\r\n\r\nSolltest Du diese Änderung nicht vorgenommen haben, wende Dich bitte schnellstmöglich an uns.\";}', 0, 'Kunde', '%old%\r\n%new%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(24, 'E-Mailänderung (neue Adresse)', 'Email change (new address)', 'a:3:{s:7:\"english\";s:0:\"\";s:7:\"deutsch\";s:23:\"Neue E-Mail bestätigen\";s:16:\"deutsch_informal\";s:23:\"Neue E-Mail bestätigen\";}', 'a:3:{s:7:\"english\";s:250:\"You have just requested to change your email address for your account at %pagename%.\r\n\r\nOld email address: %old%\r\nNew email address: %new%\r\n\r\nPlease confirm the new email address:\r\n%link%\r\n\r\nIf you do not made any changes, please click here:\r\n%link2%\";s:7:\"deutsch\";s:284:\"Es wurde soeben eine Änderung Ihrer E-Mailadresse auf %pagename% beantragt.\r\n\r\nAlte E-Mailadresse: %old%\r\nNeue E-Mailadresse: %new%\r\n\r\nBitte bestätigen Sie die neue E-Mailadresse unter:\r\n%link%\r\n\r\nSollten Sie diese Änderung nicht vorgenommen haben, klicken Sie bitte hier:\r\n%link2%\";s:16:\"deutsch_informal\";s:275:\"Es wurde soeben eine Änderung Deiner E-Mailadresse auf %pagename% beantragt.\r\n\r\nAlte E-Mailadresse: %old%\r\nNeue E-Mailadresse: %new%\r\n\r\nBitte bestätige die neue E-Mailadresse unter:\r\n%link%\r\n\r\nSolltest Du diese Änderung nicht vorgenommen haben, klicke bitte hier:\r\n%link2%\";}', 0, 'Kunde', '%old%\r\n%new%\r\n%link%\r\n%link2%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(29, 'Neue Bestellung', 'New order', 'a:2:{s:7:\"deutsch\";s:15:\"Neue Bestellung\";s:7:\"english\";s:9:\"New order\";}', 'a:2:{s:7:\"deutsch\";s:257:\"Soeben wurde eine neue Bestellung auf %pagename% getätigt.\r\n\r\nKunde: %customer%\r\nBetrag: %amount%\r\nZahlungsart: Guthaben\r\n\r\n# Bestellte Produkte #\r\n%items%\r\n--Telegram--\r\n[%customer%](%clink%) hat soeben eine Bestellung über %amount% getätigt:\r\n\r\n%items%\";s:7:\"english\";s:222:\"There was a new order just now at %pagename%.\r\n\r\nCustomer: %customer%\r\nAmount: %amount%\r\nPayment method: Credit\r\n\r\n# Ordered products #\r\n%items%\r\n--Telegram--\r\n[%customer%](%clink%) made an order about %amount%:\r\n\r\n%items%\";}', 1, 'Administrator', '%customer%\r\n%amount%\r\n%items%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(26, 'Newsletter-Disclaimer', 'Newsletter disclaimer', 'a:0:{}', 'a:2:{s:7:\"english\";s:156:\"You receive this newsletter as you are registered at %pagename% and have not opted-out.\r\nFor opt-out from the newsletter, simply click on this link:\r\n%stop%\";s:7:\"deutsch\";s:186:\"Sie erhalten diesen Newsletter, weil Sie auf %pagename% angemeldet sind und den Newsletter abonniert haben.\r\nZum Abbestellen des Newsletters klicken Sie bitte auf folgenden Link:\r\n%stop%\";}', 0, 'System', '%stop%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(27, 'Passwort vergessen?', 'Forgot password?', 'a:2:{s:7:\"english\";s:20:\"You wanted to login?\";s:7:\"deutsch\";s:27:\"Sie wollten sich einloggen?\";}', 'a:2:{s:7:\"english\";s:277:\"You tried to login into your account at our page (%time%). Unfortunately, you entered the wrong password.\r\n\r\nIf you do not know your correct password anymore, you can request a password reset with this link:\r\n%link%\r\n\r\nPlease inform us via email, if you did not tried to login.\";s:7:\"deutsch\";s:338:\"Sie hatten versucht, sich vor einiger Zeit bei uns einzuloggen (%time%). Dies hat leider bisher nicht geklappt.\r\n\r\nFunktioniert Ihr Passwort eventuell nicht? Sie können mit einem Klick auf den folgenden Link ein Passwort-Reset beantragen:\r\n%link%\r\n\r\nFalls Sie nicht versucht haben, sich einzuloggen, informieren Sie uns bitte per E-Mail.\";}', 0, 'Kunde', '%time%\r\n%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(28, 'Passwort zurücksetzen', 'Reset password', 'a:2:{s:7:\"deutsch\";s:14:\"Passwort-Reset\";s:7:\"english\";s:14:\"Password reset\";}', 'a:2:{s:7:\"deutsch\";s:195:\"Soeben wurde ein neues Passwort für die Administration angefordert. Sollten Sie dies gewesen sein, geben Sie bitte folgenden Code in Ihrem Browser ein, um ein neues Passwort zu erstellen:\r\n\r\n%c%\";s:7:\"english\";s:188:\"There was just a request for a new password for your administrator account. If you requested a new password, please enter the following code in the browser to create a new password:\r\n\r\n%c%\";}', 0, 'Administrator', '%c%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(30, 'Neuer Bug', 'New bug', 'a:2:{s:7:\"deutsch\";s:15:\"Neue Bugmeldung\";s:7:\"english\";s:14:\"New bug report\";}', 'a:2:{s:7:\"deutsch\";s:239:\"Es wurde soeben ein neuer Bug durch einen Kunden gemeldet.\r\n\r\nKunde: %customer%\r\nProdukt: %product%\r\n\r\nDetails: %pageurl%admin/?p=bugtracker\r\n--Telegram--\r\n%customer% hat einen [Bug](%pageurl%admin/?p=bugtracker) für \"%product%\" gemeldet.\";s:7:\"english\";s:234:\"There was a new bug reported by a customer just now.\r\n\r\nCustomer: %customer%\r\nProduct: %product%\r\n\r\nDetails: %pageurl%admin/?p=bugtracker\r\n--Telegram--\r\n%customer% has sent a [bug report](%pageurl%admin/?p=bugtracker) for \"%product%\".\";}', 1, 'Administrator', '%customer%\r\n%product%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(31, 'Neuer Kunde', 'New customer', 'a:2:{s:7:\"deutsch\";s:11:\"Neuer Kunde\";s:7:\"english\";s:12:\"New customer\";}', 'a:2:{s:7:\"deutsch\";s:251:\"Soeben hat sich ein neuer Kunde bei %pagename% registriert.\r\n\r\nName: %name%\r\nE-Mail: %email%\r\n\r\nKundenprofil: %pageurl%admin/?p=customers&edit=%cid%\r\n--Telegram--\r\n[%name%](%pageurl%admin/?p=customers&edit=%cid%) (%email%) hat sich soeben registriert.\";s:7:\"english\";s:277:\"Someone has registered for a client account just now at %pagename%.\r\n\r\nName: %name%\r\nEmail address: %email%\r\n\r\nCustomer profile: %pageurl%admin/?p=customers&edit=%cid%\r\n--Telegram--\r\n[%name%](%pageurl%admin/?p=customers&edit=%cid%) (%email%) has registered for an user account.\";}', 1, 'Administrator', '%name%\r\n%email%\r\n%cid%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(32, 'Fehlgeschlagener Admin-Login', 'Failed admin login', 'a:2:{s:7:\"deutsch\";s:28:\"Fehlgeschlagener Admin-Login\";s:7:\"english\";s:18:\"Failed admin login\";}', 'a:2:{s:7:\"deutsch\";s:195:\"Jemand hat gerade versucht, sich in die Administration einzuloggen.\r\n\r\nMitarbeiter: %usr%%staff%\r\nIP-Adresse: %ip%\r\n--Telegram--\r\nFolgender Login-Versuch von %ip% ist fehlgeschlagen: %usr%%staff%\";s:7:\"english\";s:160:\"Someone just tried to log in into the administration.\r\n\r\nUsername: %usr%%staff%\r\nIP address: %ip%\r\n--Telegram--\r\nThis login attempt of %ip% failed: %usr%%staff%\";}', 1, 'Administrator', '%usr%\r\n%staff%\r\n%ip%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(33, 'IPN-Gutschrift', 'IPN payment', 'a:2:{s:7:\"deutsch\";s:19:\"Zahlungs-Gutschrift\";s:7:\"english\";s:14:\"Payment credit\";}', 'a:2:{s:7:\"deutsch\";s:263:\"Es wurde soeben eine Zahlung gutgeschrieben.\r\n\r\nBetrag: %amount%\r\nGebühren: %fees%\r\nZahlungsart: %gateway%\r\nKunde: %customer%\r\n\r\nLink zum Kundenprofil:\r\n%clink%\r\n--Telegram--\r\n[%customer%](%clink%) hat soeben %amount% per %gateway% eingezahlt (%fees% Gebühren).\";s:7:\"english\";s:256:\"A payment was just credited to a customers account.\r\n\r\nAmount: %amount%\r\nFees: %fees%\r\nPayment gateway: %gateway%\r\nCustomer: %customer%\r\n\r\nCustomers profile:\r\n%clink%\r\n--Telegram--\r\n[%customer%](%clink%) has deposited %amount% via %gateway% (Fees: %fees%).\";}', 1, 'Administrator', '%amount%\r\n%fees%\r\n%gateway%\r\n%customer%\r\n%clink%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(34, 'Rechnung', 'Invoice', 'a:2:{s:7:\"deutsch\";s:13:\"Ihre Rechnung\";s:7:\"english\";s:12:\"Your invoice\";}', 'a:2:{s:7:\"deutsch\";s:120:\"Im Anhang finden Sie Ihre Rechnung %invoice% über %amount% vom %date%.\r\n\r\nVielen Dank für Ihr Vertrauen in %pagename%!\";s:7:\"english\";s:133:\"You can find your invoice %invoice% of %amount% in attachment. It was generated on %date%.\r\n\r\nThank you for your trust in %pagename%!\";}', 0, 'Kunde', '%invoice%\r\n%amount%\r\n%date%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(36, 'Neue Rechnung', 'New invoice', 'a:2:{s:7:\"deutsch\";s:13:\"Neue Rechnung\";s:7:\"english\";s:11:\"New invoice\";}', 'a:2:{s:7:\"deutsch\";s:206:\"Wir haben für Sie die Rechnung %invoice% über %amount% erstellt. Bitte begleichen Sie den Rechnungsbetrag bis zum %due%. Sie finden die Rechnung im Anhang.\r\n\r\nVielen Dank für Ihr Vertrauen in %pagename%!\";s:7:\"english\";s:180:\"We have issued the invoice %invoice% of %amount% for you. Please pay the invoice until the %due%. You can find the invoice in attachment.\r\n\r\nThank you for your trust in %pagename%!\";}', 0, 'Kunde', '%invoice%\r\n%amount%\r\n%due%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(37, 'Neues Passwort', 'New password', 'a:2:{s:7:\"deutsch\";s:18:\"Ihr neues Passwort\";s:7:\"english\";s:17:\"Your new password\";}', 'a:2:{s:7:\"deutsch\";s:175:\"Wir haben Ihnen für Ihr Konto %mail% soeben ein neues Passwort generiert.\r\n\r\nDieses lautet: %password%\r\n\r\nBitte ändern Sie dieses Passwort schnellstmöglich auf Ihr eigenes.\";s:7:\"english\";s:145:\"We have generated a new password for your account %mail%.\r\n\r\nYour new password is: %password%\r\n\r\nPlease change this password as fast as possible.\";}', 0, 'Kunde', '%mail%\r\n%password%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(38, 'Registrierung per sozialem Login', 'Registered via social login', 'a:2:{s:7:\"deutsch\";s:27:\"Registrierung via %service%\";s:7:\"english\";s:26:\"Registration via %service%\";}', 'a:2:{s:7:\"deutsch\";s:367:\"Sie haben sich bei %pagename% via %service% eingeloggt. Dadurch wurde für Sie automatisch ein Konto bei uns angelegt. Sie können folgende Daten zum Login ohne %service% verwenden:\r\n\r\nE-Mailadresse: %email%\r\nPasswort: %password%\r\n\r\nBitte ändern Sie das zufallsgenerierte Passwort zu Ihrer eigenen Sicherheit.\r\n\r\nWir freuen uns, Sie als Kunden begrüßen zu dürfen.\";s:7:\"english\";s:309:\"You signed in to %pagename% via %service%. An account for you was created automatically. You can use this data to sign in without %service%:\r\n\r\nEmail address: %email%\r\nPassword: %password%\r\n\r\nPlease change the password as soon as possible for your own safety.\r\n\r\nWe are happy to welcome you as a new customer!\";}', 0, 'Kunde', '%service%\r\n%email%\r\n%password%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(40, 'Warenkorb-Erinnerung', 'Cart reminder', 'a:2:{s:7:\"deutsch\";s:13:\"Ihr Warenkorb\";s:7:\"english\";s:9:\"Your cart\";}', 'a:2:{s:7:\"deutsch\";s:339:\"Sie haben zuletzt am %date% einen Artikel bei uns in den Warenkorb gelegt, den Kauf aber nicht abgeschlossen. Ihr Warenkorb enthält folgende Artikel:\r\n\r\n%products%\r\n\r\nGerne sind wir Ihnen behilflich, wenn Sie Probleme bei der Bestellung oder Fragen zu den Produkten haben. Schreiben Sie uns doch einfach eine E-Mail oder rufen Sie uns an!\";s:7:\"english\";s:312:\"You have added an article to your cart last time at %date%, but you did not bought this articles. Your cart contains the following articles:\r\n\r\n%products%\r\n\r\nPlease do not hesitate to contact us if you have problems with checking out or any questions about the products. Just write us an email or give us a call!\";}', 0, 'Kunde', '%products%\r\n%date%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(48, 'Domain registriert', 'Domain registered', 'a:2:{s:7:\"deutsch\";s:36:\"[%domain%] Registrierung erfolgreich\";s:7:\"english\";s:34:\"[%domain%] Registration successful\";}', 'a:2:{s:7:\"deutsch\";s:370:\"Die beauftragte Registrierung der Domain %domain% wurde erfolgreich durchgeführt. Sie können die Domain nun in der Domainverwaltung sehen und bearbeiten.\r\n\r\nDirekt zur Domainverwaltung: %url%\r\n\r\nEin Hinweis: Die weltweite Übernahme der Domain-Registrierung kann bis zu 48 Stunden dauern. In diesem Zeitraum ist Ihre Domain eventuell noch nicht einwandfrei erreichbar.\";s:7:\"english\";s:335:\"The registration of your domain %domain% was successful. You can view and edit the domain now in the domain management.\r\n\r\nDirectly to your domain management: %url%\r\n\r\nPlease note that the registration of the domain can take up to 48 hours to be propagated world-wide. In the mean time the domain may not be reachable without problems.\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(49, 'Domain transferiert', 'Domain transferred', 'a:2:{s:7:\"deutsch\";s:31:\"[%domain%] Transfer erfolgreich\";s:7:\"english\";s:30:\"[%domain%] Transfer successful\";}', 'a:2:{s:7:\"deutsch\";s:475:\"Der beauftragte Transfer Ihrer Domain %domain% wurde erfolgreich durchgeführt. Die Änderungen an der Domain wurden erfolgreich durchgeführt und an die Registrierungsstelle übermittelt. Sie können die Domain nun in der Domainverwaltung sehen und bearbeiten.\r\n\r\nDirekt zur Domainverwaltung: %url%\r\n\r\nEin Hinweis: Die weltweite Übernahme der Änderungen an der Domain kann bis zu 48 Stunden dauern. In diesem Zeitraum weist Ihre Domain eventuell noch auf alte IP-Adressen.\";s:7:\"english\";s:388:\"The transfer of your domain %domain% was successful. We have propagated the changes of the domain to the registry. You can view and edit the domain now in the domain management.\r\n\r\nDirectly to your domain management: %url%\r\n\r\nPlease note that any changes of the domain can take up to 48 hours to be propagated world-wide. In the mean time there may exist old DNS entries and IP addresses.\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(50, 'Domain-Registrierung fehlgeschlagen', 'Domain registration failed', 'a:2:{s:7:\"deutsch\";s:39:\"[%domain%] Registrierung fehlgeschlagen\";s:7:\"english\";s:30:\"[%domain%] Registration failed\";}', 'a:2:{s:7:\"deutsch\";s:431:\"Leider ist die beauftragte Registrierung Ihrer Domain %domain% fehlgeschlagen. Dies kann zum Beispiel daran liegen, dass die Domain in der Zwischenzeit bereits registriert wurde oder ein technischer Fehler aufgetreten ist.\r\n\r\nSie können den Auftrag jederzeit kostenlos erneut starten. Sollte das Problem fortbestehen oder Sie eine Erstattung wünschen, kontaktieren Sie bitte unseren Support.\r\n\r\nDirekt zur Domainverwaltung: %url%\";s:7:\"english\";s:331:\"Unfortunately, the registration of your domain %domain% failed. Maybe the domain was registered in the mean time by someone else or a technical error occured.\r\n\r\nYou can start the task at any time again for free. If the problem persists or you want a refund, please contact our support.\r\n\r\nDirectly to your domain management: %url%\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(51, 'Domain-Transfer fehlgeschlagen', 'Domain transfer failed', 'a:2:{s:7:\"deutsch\";s:34:\"[%domain%] Transfer fehlgeschlagen\";s:7:\"english\";s:26:\"[%domain%] Transfer failed\";}', 'a:2:{s:7:\"deutsch\";s:393:\"Leider ist der beauftragte Transfer Ihrer Domain %domain% fehlgeschlagen. Dies kann zum Beispiel daran liegen, dass es noch eine Transfer-Sperre für die Domain gibt oder der eingegebene AuthCode falsch war.\r\n\r\nSie können den Auftrag jederzeit kostenlos erneut starten. Dabei können Sie auch den AuthCode erneut eingeben, wenn dieser falsch sein sollte.\r\n\r\nDirekt zur Domainverwaltung: %url%\";s:7:\"english\";s:303:\"Unfortunately, the transfer of your domain %domain% failed. Maybe there is a transfer lock set or the provided authcode was wrong.\r\n\r\nYou can start the task at any time again for free. It is also possible to change the authcode if the provided one was wrong.\r\n\r\nDirectly to your domain management: %url%\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(52, 'Ausgehender Domain-Transfer', 'Outgoing domain transfer', 'a:2:{s:7:\"deutsch\";s:31:\"[%domain%] Ausgehender Transfer\";s:7:\"english\";s:28:\"[%domain%] Outgoing transfer\";}', 'a:2:{s:7:\"deutsch\";s:90:\"Ihre Domain %domain% wurde soeben transferiert. Sie liegt damit nicht mehr bei %pagename%.\";s:7:\"english\";s:73:\"Your domain %domain% was transferred away from %pagename% one moment ago.\";}', 0, 'Domain', '%domain%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(53, 'Domain zurückgegeben', 'Domain returned to registry', 'a:2:{s:7:\"deutsch\";s:44:\"[%domain%] Rückgabe an Registrierungsstelle\";s:7:\"english\";s:29:\"[%domain%] Return to registry\";}', 'a:2:{s:7:\"deutsch\";s:330:\"Wir haben Ihre Domain %domain% soeben an die Registrierungsstelle zurückgegeben. Der Admin-C sollte in Kürze telefonisch, postalisch oder per E-Mail entsprechende Informationen erhalten.\r\n\r\nDie Rückgabe der Domain kann mehrere Gründe haben. Wir führen sie auf Ihren Wunsch hin durch oder wenn eine Domain nicht bezahlt wurde.\";s:7:\"english\";s:149:\"We have returned your domain %domain% to the registry. The Admin-C should get information about the further proceedings via telephone, email or post.\";}', 0, 'Domain', '%domain%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(54, 'Domain ausgelaufen', 'Domain expired', 'a:2:{s:7:\"deutsch\";s:29:\"[%domain%] Domain ausgelaufen\";s:7:\"english\";s:25:\"[%domain%] Domain expired\";}', 'a:2:{s:7:\"deutsch\";s:381:\"Der Registrierungszeitraum Ihrer Domain %domain% ist ausgelaufen. Leider haben Sie Ihre Domain nicht bei uns kostenpflichtig verlängert. Daher wurde Ihre Domain automatisch gelöscht.\r\n\r\nSollte die Domain versehentlich ausgelaufen sein, wenden Sie sich bitte an unseren Support. In diesem Fall ist oft eine Wiederherstellung der Domain möglich, wenn dies zeitnah beauftragt wird.\";s:7:\"english\";s:277:\"Your domain %domain% has expired. Unfortunately, you have not renewed the domain. Therefore your domain was deleted automatically.\r\n\r\nIf the domain has expired accidently, please contact our support as soon as possible. In this case, a restore of the domain should be possible.\";}', 0, 'Domain', '%domain%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(56, 'Auslauf-Warnung (manuell)', 'Expiration warning (manually)', 'a:2:{s:7:\"deutsch\";s:24:\"[%domain%] Ablaufwarnung\";s:7:\"english\";s:29:\"[%domain%] Expiration warning\";}', 'a:2:{s:7:\"deutsch\";s:234:\"Ihre Domain %domain% läuft am %date% ab. Sie sollten schnellstmöglich die automatische Verlängerung aktivieren und über genügend Guthaben verfügen, um den Verlust Ihrer Domain zu verhindern.\r\n\r\nDirekt zur Domainverwaltung: %url%\";s:7:\"english\";s:207:\"Your domain %domain% expires on the %date%. You should activate the automatically renew and have enough credit as soon as possible to prevent loosing your domain.\r\n\r\nDirectly to your domain management: %url%\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(59, 'Domain aktualisiert', 'Domain changed', 'a:2:{s:7:\"deutsch\";s:30:\"[%domain%] Domain aktualisiert\";s:7:\"english\";s:25:\"[%domain%] Domain updated\";}', 'a:2:{s:7:\"deutsch\";s:355:\"Vielen Dank für die Änderungen an Ihrer Domain %domain%. Diese haben wir soeben erfolgreich an die Registrierungsstelle übermittelt.\r\n\r\nDirekt zur Domainverwaltung: %url%\r\n\r\nEin Hinweis: Die weltweite Übernahme von Nameserver- und DNS-Änderungen kann bis zu 48 Stunden dauern. In diesem Zeitraum werden eventuell noch alte DNS-Einträge ausgeliefert.\";s:7:\"english\";s:313:\"Thank you for your changes for your domain %domain%. We have propagated your changes to the domain registry.\r\n\r\nDirectly to your domain management: %url%\r\n\r\nPlease note that changing of the nameservers or DNS entries can take up to 48 hours to be propagated world-wide. In the mean time old DNS records may exist.\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(60, 'Fehlgeschlagene Aktualisierung', 'Domain change failed', 'a:2:{s:7:\"deutsch\";s:41:\"[%domain%] Fehlgeschlagene Aktualisierung\";s:7:\"english\";s:24:\"[%domain%] Update failed\";}', 'a:2:{s:7:\"deutsch\";s:150:\"Leider konnten die Änderungen an Ihrer Domain %domain% nicht übernommen werden. Bitte versuchen Sie es erneut.\r\n\r\nDirekt zur Domainverwaltung: %url%\";s:7:\"english\";s:122:\"Unfortunately, the changes for your domain %domain% failed. Please try again.\r\n\r\nDirectly to your domain management: %url%\";}', 0, 'Domain', '%domain%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(61, 'Newsletter bestätigen', 'Confirm newsletter', 'a:2:{s:7:\"deutsch\";s:20:\"Newsletter bestellen\";s:7:\"english\";s:21:\"Newsletter abonnement\";}', 'a:2:{s:7:\"deutsch\";s:226:\"Vielen Dank für Ihr Interesse an unserem Newsletter und das Eintragen Ihrer E-Mailadresse. Bitte bestätigen Sie uns Ihren Wunsch noch einmal kurz mit einem Klick auf diesen Link, Sie werden dann sofort eingetragen:\r\n\r\n%link%\";s:7:\"english\";s:181:\"Thank you for being interested in our newsletter and wanting an abonnement. Please confirm this once again by click this link, and your email will be inserted immediately:\r\n\r\n%link%\";}', 0, 'System', '%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(62, 'Newsletter abbestellen', 'Newsletter opt-out', 'a:2:{s:7:\"deutsch\";s:22:\"Newsletter abbestellen\";s:7:\"english\";s:28:\"Cancel newsletter abonnement\";}', 'a:2:{s:7:\"deutsch\";s:229:\"Wir haben mit Bedauern zur Kenntnis genommen, dass Sie unseren Newsletter abbestellen würden. Bitte bestätigen Sie uns Ihren Wunsch noch einmal kurz mit einem Klick auf diesen Link, Sie werden dann sofort ausgetragen:\r\n\r\n%link%\";s:7:\"english\";s:166:\"Unfortunately, you want to cancel your newsletter abonnement. Please confirm this once again by click this link, and your email will be deleted immediately:\r\n\r\n%link%\";}', 0, 'System', '%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(63, 'Neuer Wunsch', 'New wish', 'a:2:{s:7:\"deutsch\";s:12:\"Neuer Wunsch\";s:7:\"english\";s:8:\"New wish\";}', 'a:2:{s:7:\"deutsch\";s:106:\"Es wurde ein neuer Wunsch für das Produkt \"%product%\" angelegt. Sie können ihn im Adminbereich einsehen.\";s:7:\"english\";s:83:\"There is a new wish for the product \"%product%\". You can view it in the admin area.\";}', 1, 'Administrator', '%product%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(64, 'Neuer Wunsch (Abo)', 'New wish (abo)', 'a:2:{s:7:\"deutsch\";s:12:\"Neuer Wunsch\";s:7:\"english\";s:8:\"New wish\";}', 'a:2:{s:7:\"deutsch\";s:138:\"Für das von Ihnen abonnierte Produkt \"%product%\" wurde ein neuer Wunsch \"%wish%\" veröffentlicht. Sie können ihn hier einsehen:\r\n\r\n%url%\";s:7:\"english\";s:117:\"For the product \"%product%\" you have subscribed to, a new wish \"%wish%\" was published. You can view it here:\r\n\r\n%url%\";}', 0, 'Wunschliste', '%product%\r\n%wish%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(65, 'Neuer Kommentar (Abo)', 'New comment (abo)', 'a:2:{s:7:\"deutsch\";s:15:\"Neuer Kommentar\";s:7:\"english\";s:11:\"New comment\";}', 'a:2:{s:7:\"deutsch\";s:113:\"Zum Wunsch \"%wish%\" wurde ein neuer Kommentar von \"%author\" hinterlassen. Sie können ihn hier einsehen:\r\n\r\n%url%\";s:7:\"english\";s:92:\"For the wish \"%wish\", a new comment was posted by \"%author%\". You can view it here:\r\n\r\n%url%\";}', 0, 'Wunschliste', '%wish%\r\n%author%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(66, 'Statusänderung (Abo)', 'Status changed (abo)', 'a:2:{s:7:\"deutsch\";s:15:\"Statusänderung\";s:7:\"english\";s:13:\"Status change\";}', 'a:2:{s:7:\"deutsch\";s:105:\"Für den Wunsch \"%wish%\" wurde der Status geändert. Sie können den neuen Status hier einsehen:\r\n\r\n%url%\";s:7:\"english\";s:89:\"For the wish \"%wish%\", the status was changed. You can view the new status here:\r\n\r\n%url%\";}', 0, 'Wunschliste', '%wish%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(69, 'Dateiversand', 'Send file', 'a:2:{s:7:\"deutsch\";s:13:\"Datei: %file%\";s:7:\"english\";s:12:\"File: %file%\";}', 'a:2:{s:7:\"deutsch\";s:38:\"Im Anhang finden Sie die Datei %file%.\";s:7:\"english\";s:38:\"You can find the file %file% attached.\";}', 0, 'System', '%file%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(70, 'Ihr Angebot', 'Your quote', 'a:2:{s:7:\"deutsch\";s:16:\"Ihr Angebot %nr%\";s:7:\"english\";s:15:\"Your quote %nr%\";}', 'a:2:{s:7:\"deutsch\";s:162:\"Im Anhang finden Sie unser Angebot %nr% über %amount%. Das Angebot ist freibleibend und bis zum %valid% gültig.\r\n\r\nVielen Dank für Ihr Vertrauen in %pagename%!\";s:7:\"english\";s:126:\"You can find our quote %nr% of %amount% attached. The quote is valid until %valid%.\r\n\r\nThank you for your trust in %pagename%!\";}', 0, 'Kunde', '%nr%\r\n%amount%\r\n%valid%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(71, 'Kündigungsbestätigung', 'Cancellation confirmation', 'a:2:{s:7:\"deutsch\";s:15:\"Ihre Kündigung\";s:7:\"english\";s:17:\"Your cancellation\";}', 'a:2:{s:7:\"deutsch\";s:225:\"Wir haben Ihre Kündigung für Ihr Produkt \"%product%\" zum %date% erhalten und werden diese wunschgemäß durchführen.\r\n\r\nSollten Sie sich noch umentscheiden, können Sie die Kündigung im Kundenbereich jederzeit stornieren.\";s:7:\"english\";s:222:\"We have received your cancellation for your product \"%product%\". Your product will be deleted on %date%.\r\n\r\nIf you do not want to cancel your product anymore, you can revoke the cancellation in the client area at any time.\";}', 0, 'Kunde', '%product%\r\n%date%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(84, 'Gast-Bestellung', 'Guest order', 'a:2:{s:7:\"deutsch\";s:31:\"Bestätigen Sie Ihre Bestellung\";s:7:\"english\";s:18:\"Confirm your order\";}', 'a:2:{s:7:\"deutsch\";s:361:\"Vielen Dank für Ihr Interesse an unseren Produkten!\r\n\r\nSie müssen Ihre E-Mailadresse mit einem Klick auf diesen Link bestätigen, um fortfahren zu können:\r\n%link%\r\n\r\nDadurch wird ein Kundenkonto für Sie angelegt und Sie können die Bestellung abschließen.\r\n\r\nWenn Sie kein Kundenkonto bei uns angefordert haben, können Sie diese E-Mail einfach ignorieren.\";s:7:\"english\";s:300:\"Thank you for ordering at %pagename%!\r\n\r\nYou need to confirm your email by clicking on the following link:\r\n%link%\r\n\r\nAfter clicking, a customer account is created for you and you can finish the order on our website.\r\n\r\nIf you have not requested a customer account with us, you can ignore this email.\";}', 0, 'Kunde', '%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(85, 'SEPA-Benachrichtigung', 'SEPA notification', 'a:2:{s:7:\"deutsch\";s:22:\"SEPA-Vorabankündigung\";s:7:\"english\";s:20:\"SEPA prenotification\";}', 'a:2:{s:7:\"deutsch\";s:110:\"Wir weisen Sie darauf hin, dass wir Ihr Konto %iban% am %duedate% mit dem Betrag von %amount% belasten werden.\";s:7:\"english\";s:80:\"We will debit your SEPA account %iban% on %duedate% with the amount of %amount%.\";}', 0, 'Eigene', '%iban%\r\n%duedate%\r\n%amount%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(700, 'Neues Ticket', 'New ticket', 'a:2:{s:7:\"deutsch\";s:12:\"Neues Ticket\";s:7:\"english\";s:10:\"New ticket\";}', 'a:2:{s:7:\"deutsch\";s:59:\"%sender% hat das Ticket %subject% eröffnet (%department%).\";s:7:\"english\";s:53:\"%sender% created the ticket %subject% (%department%).\";}', 1, 'Administrator', '%sender%\r\n%subject%\r\n%department%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(701, 'Ticket-Antwort', 'Ticket answer', 'a:2:{s:7:\"deutsch\";s:14:\"Ticket-Antwort\";s:7:\"english\";s:13:\"Ticket answer\";}', 'a:2:{s:7:\"deutsch\";s:65:\"%sender% hat auf das Ticket %subject% geantwortet (%department%).\";s:7:\"english\";s:57:\"%sender% answered to the ticket %subject% (%department%).\";}', 1, 'Administrator', '%sender%\r\n%subject%\r\n%department%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(1040, 'Abuse-Meldung aktualisiert', 'Abuse report update', 'a:2:{s:7:\"deutsch\";s:24:\"Abuse-Meldung: %subject%\";s:7:\"english\";s:23:\"Abuse report: %subject%\";}', 'a:2:{s:7:\"deutsch\";s:221:\"Bitte beachten Sie, dass für Ihr Kundenkonto eine Abuse-Meldung vorliegt:\r\n%url%\r\n\r\nSie erhalten diese E-Mail, da die Meldung soeben erstellt oder geändert wurde. Bitte schauen Sie sich die aktuelle Meldung umgehend an.\";s:7:\"english\";s:206:\"Please note that an abuse report exists for your customer account:\r\n%url%\r\n\r\nYou got this email because the report was created or changed a moment ago. Please look at the current report as soon as possible.\";}', 0, 'Kunde', '%subject%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(1137, 'Antwort im Forum', 'Answer in forum', 'a:2:{s:7:\"deutsch\";s:21:\"Neue Antwort im Forum\";s:7:\"english\";s:19:\"New answer in forum\";}', 'a:2:{s:7:\"deutsch\";s:77:\"Es gibt eine neue Antwort im Thread \"%thread%\" von \"%author%\".\r\n\r\nLink: %url%\";s:7:\"english\";s:72:\"There is a new answer in thread \"%thread%\" by \"%author%\".\r\n\r\nLink: %url%\";}', 0, 'Kunde', '%thread%\r\n%author%\r\n%url%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(2783, 'Ablauf-Warnung', 'Expiration warning', 'a:2:{s:7:\"deutsch\";s:25:\"Ablauf-Warnung: %product%\";s:7:\"english\";s:29:\"Expiration warning: %product%\";}', 'a:2:{s:7:\"deutsch\";s:77:\"Ihr Produkt %product% läuft am %expiration% ab!\r\n\r\nJetzt verlängern: %link%\";s:7:\"english\";s:68:\"Your product %product% expires on %expiration%!\r\n\r\nRenew now: %link%\";}', 0, 'Kunde', '%product%\r\n%expiration%\r\n%link%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(3991, 'Neue Kündigung', 'New cancellation', 'a:2:{s:7:\"deutsch\";s:15:\"Neue Kündigung\";s:7:\"english\";s:16:\"New cancellation\";}', 'a:2:{s:7:\"deutsch\";s:316:\"Soeben wurde eine Kündigung angelegt:\r\n\r\nKunde: %name%\r\nProdukt: %product%\r\n\r\nKundenprofil: %pageurl%admin/?p=customers&edit=%cid%\r\nVertrag: %pageurl%admin/?p=hosting&id=%hid%\r\n--Telegram--\r\n[%name%](%pageurl%admin/?p=customers&edit=%cid%) hat den Vertrag [%product%](%pageurl%admin/?p=hosting&id=%hid%) gekündigt.\";s:7:\"english\";s:317:\"A new cancellation was created:\r\n\r\nCustomer: %name%\r\nProduct: %product%\r\n\r\nCustomer profile: %pageurl%admin/?p=customers&edit=%cid%\r\nContract: %pageurl%admin/?p=hosting&id=%hid%\r\n--Telegram--\r\n[%name%](%pageurl%admin/?p=customers&edit=%cid%) has cancelled the contract [%product%](%pageurl%admin/?p=hosting&id=%hid%).\";}', 1, 'Administrator', '%name%\r\n%product%\r\n%cid%\r\n%hid%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(5971, 'Rückbuchung', 'Chargeback', 'a:2:{s:7:\"deutsch\";s:12:\"Rückbuchung\";s:7:\"english\";s:10:\"Chargeback\";}', 'a:2:{s:7:\"deutsch\";s:186:\"Bei folgender Transaktion kam es leider zu einer Rückbuchung:\r\n\r\nDatum: %date%\r\nBetreff: %subject%\r\nBetrag: %amount%\r\n\r\nFür die Rückbuchung haben wir eine Gebühr von %fee% berechnet.\";s:7:\"english\";s:160:\"For the following transaction, a chargeback occured:\r\n\r\nDate: %date%\r\nSubject: %subject%\r\nAmount: %amount%\r\n\r\nWe charged you a fee of %fee% for this chargeback.\";}', 0, 'Kunde', '%date%\r\n%subject%\r\n%amount%\r\n%fee%');
        INSERT INTO `%prefix%email_templates` (`ID`, `name`, `foreign_name`, `title`, `content`, `admin_notification`, `category`, `vars`) VALUES(8713, 'Guthaben-Mahnung', 'Credit reminder', 'a:2:{s:7:\"deutsch\";s:24:\"Ihr Guthaben ist negativ\";s:7:\"english\";s:23:\"Your credit is negative\";}', 'a:2:{s:7:\"deutsch\";s:115:\"Ihr aktuelles Guthaben bei %pagename% beträgt %credit%.\r\n\r\nBitte gleichen Sie Ihr negatives Guthaben umgehend aus.\";s:7:\"english\";s:109:\"Your current credit at %pagename% is %credit%.\r\n\r\nPlease compensate your negative credit as soon as possible.\";}', 0, 'Kunde', '%credit%');
EOQ;

        $myTemplates = [];
        $sql = $db->query("SELECT `ID` FROM " . $prefix . "email_templates");
        while ($row = $sql->fetch_object()) {
            array_push($myTemplates, $row->ID);
        }

        $ex = explode(";\n", $queries);
        foreach ($ex as $query) {
            $query = trim($query);

            $id = intval(substr($query, strpos($query, "VALUES(") + 7));

            if (!in_array($id, $myTemplates)) {
                $db->query(str_replace("%prefix%", $prefix, $query));
            }
        }

        $this->time["init_email_templates"] = microtime(true) - $this->time["init_email_templates"];

        $this->time["init_client_countries"] = microtime(true);
        if (!$db->query("SELECT 1 FROM " . $prefix . "client_countries WHERE ID = 1")->num_rows) {
            $db->query("INSERT INTO " . $prefix . "client_countries (`ID`, `name`, `alpha2`) VALUES (1, 'Deutschland', 'DE')");
        }
        $this->time["init_client_countries"] = microtime(true) - $this->time["init_client_countries"];

        $this->time["init_newsletter_categories"] = microtime(true);

        $db->query(str_replace("%prefix%", $prefix, "INSERT INTO `%prefix%newsletter_categories` (`ID`, `name`, `standard`) VALUES (1, 'Newsletter', 1);"));

        $this->time["init_newsletter_categories"] = microtime(true) - $this->time["init_newsletter_categories"];
        $this->time["init_settings"] = microtime(true);

        $path = $_SERVER['REQUEST_URI'];
        $ex = explode("/", $path);
        $ex = array_slice($ex, 0, -2);
        $path = implode("/", $ex);
        $path = trim($path, "/");
        if (!empty($path)) {
            $path .= "/";
        }

        $pageurl = (!empty($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/" . $path;

        $settings = [
            'customer_authcode' => '1',
            'tax_wise' => 'ist',
            'postpaid_def' => '0',
            'url_rewrite' => '',
            'invoice_advance' => '0',
            'admin_notes' => '',
            'allow_reg' => '1',
            'bugtracker_dept' => '-1',
            'csrf_disabled' => '',
            'min_quolen' => '6',
            'min_invlen' => '6',
            'wtip' => '0',
            'cnr_prefix' => '',
            'log_support_mails' => '0',
            'home_intro' => 'a:2:{s:2:"de";s:33:"Stolz präsentiert von haseDESK";s:2:"en";s:31:"Proudly presented by haseDESK";}',
            'default_ip' => '5.9.7.9',
            'provisioning_encoded' => '0',
            'no_invoicing' => '0',
            'breakdown' => '0',
            'hide_normal_server' => '0',
            'patchlevel' => '20200805',
            'admin_color' => '#428bca',
            'micropatches' => '1',
            'tin_modal' => '',
            'pnr_prefix' => '',
            'telegram_notifications' => '',
            'telegram_chat' => '',
            'telegram_token' => '',
            'sms_provider' => '',
            'telephone_log' => '',
            'display_errors' => 'no',
            'maintenance' => '0',
            'theme' => 'standard',
            'imprint' => '',
            'stem' => '0',
            'loan' => '0',
            'mail_type' => 'php',
            'smtp_host' => '',
            'smtp_user' => '',
            'smtp_password' => '',
            'pagename' => 'haseDESK',
            'pageurl' => $pageurl,
            'pagemail' => '',
            'mail_sender' => '',
            'explicit_ssl' => '0',
            'hash_method' => $_SESSION['config']['hash_method'] ?? "sha512",
            'backup_method' => 'file',
            'backup_data' => 'files',
            'backup_retention' => '0',
            'backup_file_path' => '',
            'backup_ftp_host' => '',
            'backup_ftp_user' => '',
            'backup_ftp_password' => '',
            'backup_ftp_encryption' => 'ssl',
            'backup_ftp_path' => '/',
            'date_format' => 'a:2:{s:7:"deutsch";s:10:"DD.MM.YYYY";s:7:"english";s:10:"YYYY-MM-DD";}',
            'timezone' => 'a:2:{s:7:"deutsch";s:13:"Europe/Berlin";s:7:"english";s:7:"Etc/UTC";}',
            'number_format' => 'a:2:{s:7:"deutsch";s:2:"de";s:7:"english";s:2:"us";}',
            'lang' => 'deutsch',
            'mail_leadtime' => '0',
            'taxes' => '0',
            'debtors_other' => '0',
            'hash_method_admin' => $_SESSION['config']['hash_method'] ?? "sha512",
            'csv_import' => '',
            'mailqueue_auto' => '1',
            'fail2ban_active' => '1',
            'fail2ban_failed' => '10',
            'fail2ban_locked' => '15',
            'tracking' => '',
            'seo' => '',
            'stem_auto' => '0',
            'global_salt' => $_SESSION['config']['global_salt'] ?? "",
            'master' => '0',
            'version' => file_get_contents(__DIR__ . "/../install/req/version.dist.txt"),
            'actual_version' => file_get_contents(__DIR__ . "/../install/req/version.dist.txt"),
            'default_country' => '1',
            'default_cgroup' => '0',
            'birthday_text' => "a:3:{s:7:\"english\";s:288:\"We wish you all the best for your %j. birthday and we hope, that you will remain to be a happy client.\r\n\r\nFor your birthday, you get a discount of 10% for all our products and services. Please use the voucher code '%c', which is valid only for your account and within the next seven days.\";s:16:\"deutsch_informal\";s:352:\"Wir wünschen Dir alles Gute zu Deinem %j. Geburtstag und hoffen, dass Du uns weiterhin als Kunde treu bleibst.\r\n\r\nZu Deinem Geburtstag bekommst Du bei uns einen einmaligen Rabatt von 10% auf alle Produkte und Dienstleistungen. Nutze dafür einfach den Gutschein '%c', dieser ist nur mit Deinem Konto und innerhalb der nächsten sieben Tage einlösbar.\";s:7:\"deutsch\";s:358:\"Wir wünschen Ihnen alles Gute zu Ihrem %j. Geburtstag und hoffen, dass Sie uns weiterhin als Kunde treu bleiben.\r\n\r\nZu Ihrem Geburtstag bekommen Sie bei uns einen einmaligen Rabatt von 10% auf alle Produkte und Dienstleistungen. Nutzen Sie dafür einfach den Gutschein '%c', dieser ist nur mit Ihrem Konto und innerhalb der nächsten sieben Tage einlösbar.\";}",
            'birthday_title' => 'a:3:{s:7:"english";s:19:"Happy %j. birthday!";s:16:"deutsch_informal";s:43:"Herzlichen Glückwunsch zum %j. Geburtstag!";s:7:"deutsch";s:43:"Herzlichen Glückwunsch zum %j. Geburtstag!";}',
            'birthday_voucher' => '0',
            'admin_whitelist' => 'a:0:{}',
            'admin_whitelist_pw' => '',
            'recaptcha_public' => '',
            'recaptcha_private' => '',
            'captcha_type' => 'calcCaptcha',
            'last_version_update' => '0',
            'license_id' => '',
            'license_key' => $_SESSION['license_key'] ?? "",
            'invoice_prefix' => 'RE-',
            'invoice_duedate' => '7',
            'active_gateways' => '',
            'cashbox_active' => '1',
            'cashbox_prefix' => 'Cashbox-',
            'hsts' => '0',
            'top_alert_type' => 'none',
            'top_alert_msg' => '',
            'clientside_hashing' => $_SESSION['config']['clientside_hashing'] ?? "",
            'clientside_hashing_admin' => $_SESSION['config']['clientside_hashing'] ?? "",
            'password_history' => '0',
            'search_hidden' => '',
            'affiliate_active' => '0',
            'affiliate_commission' => '3.50',
            'affiliate_cookie' => '30',
            'withdrawal_rules' => 'a:2:{s:7:"deutsch";s:0:"";s:7:"english";s:0:"";}',
            'privacy_policy' => 'a:2:{s:7:"deutsch";s:0:"";s:7:"english";s:0:"";}',
            'cookie_accept' => '0',
            'user_confirmation' => '0',
            'sms_verify' => '',
            'gitlab_host' => '',
            'github_user' => '',
            'github_key' => '',
            'git_type' => '',
            'gitlab_key' => '',
            'mogelmail' => '0',
            'smtp_security' => '',
            'trim_whitespace' => '1',
            'maintenance_msg' => '',
            'facebook_login' => '0',
            'twitter_login' => '0',
            'facebook_id' => '',
            'facebook_secret' => '',
            'twitter_id' => '',
            'twitter_secret' => '',
            'social_login_toggle' => '0',
            'eu_vat' => '0',
            'disqus' => '',
            'block_proxy' => '0',
            'fallback_salutation' => serialize(["english" => "Dear Sir or Madam,", "deutsch" => "Sehr geehrter Kunde, sehr geehrte Kundin,"]),
            'ns1' => '',
            'ns2' => '',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'dns_driver' => '',
            'whois_data' => '',
            'piwik_ecommerce' => '0',
            'letter_provider' => '',
            'offer_prefix' => 'AN-',
            'offer_intro' => "Vielen Dank für Ihre Anfrage nach einem Angebot aus unserem Haus.\r\n\r\nNachfolgend finden Sie eine unverbindliche Kostenaufstellung für Ihren geplanten Auftrag.",
            'offer_extro' => "Wir würden uns freuen, Sie schon bald als unseren Kunden begrüßen zu dürfen.\r\n\r\nMit freundlichen Grüßen",
            'offer_terms' => 'Bei Auftragserteilung ist im Voraus eine Anzahlung in Höhe des Angebotsbetrages zu leisten.',
            'affiliate_days' => '14',
            'affiliate_min' => '10,00',
            'invoice_dist_min' => '1',
            'invoice_dist_max' => '1',
            'cdn_urls' => '',
            'exchange_source' => 'ecb',
            'sepa_limit' => '100.00',
            'ext_affiliate' => '',
            'ext_affiliate_ex' => '',
            'websocket_active' => '0',
            'websocket_port' => '8057',
            'websocket_ao' => 'localhost,127.0.0.1,::1',
            'websocket_pem' => '',
            'websocket_key' => '',
            'gmap_key' => '',
            'ses_id' => '',
            'ses_secret' => '',
            'support_autoclose' => '',
            'support_rating' => '',
            'support_rating_mail' => '',
            'branding' => '1',
            'pdf_address' => "Max Mustermann GmbH\r\nMusterstr. 1\r\n12345 Musterdorf\r\nDeutschland\r\n\r\n%invoice.email%: max@mustermann.de\r\n%invoice.telephone%: +49 123 45678 90\r\n%invoice.telefax%: +49 123 45678 91\r\n\r\n%invoice.ceo%: Max Mustermann\r\n%invoice.trade_register%: HRB 12345 / Musterstadt\r\n%invoice.euvat_number%: DE123456789",
            'pdf_color' => '#aabbcc',
            'pdf_sender' => 'Max Mustermann GmbH * Musterstr. 1 * 12345 Musterdorf',
            'pdf_bank' => 'Sparkasse Musterdorf',
            'pdf_bic' => 'MUSTDEDOXXX',
            'pdf_iban' => 'DE12 3456 7890 0123 3456 78',
            'pdf_recipient' => "Max Mustermann GmbH\r\nMusterstr. 1\r\n12345 Musterdorf\r\n\r\n%email%: max@mustermann.de\r\n%fax%: +49 123 45678 91",
            'domain_action_conf' => '1',
            'domain_log' => '0',
            'redirect_login' => '0',
            'sms_verify_orders' => '0',
            'db_hash' => $this->calcHash(),
            'sm_fb_id' => '',
            'sm_fb_secret' => '',
            'sm_fb_key' => '',
            'sm_fb_page_id' => '',
            'sm_twitter_ck' => '',
            'sm_twitter_cs' => '',
            'sm_twitter_at' => '',
            'sm_twitter_ats' => '',
            'min_age' => '0',
            'domain_renew_mail_updated' => '1',
            'terms_date' => '1',
            'terms_history' => '1',
            'cust_source' => serialize([
                ["deutsch" => "Google", "english" => "Google"],
                ["deutsch" => "Andere Suchmaschine", "english" => "Other search engine"],
                ["deutsch" => "Printmedien", "english" => "Print media"],
                ["deutsch" => "Empfehlung", "english" => "Recommendation"],
                ["deutsch" => "Newsletter", "english" => "Newsletter"],
                ["deutsch" => "Sonstiges", "english" => "Other"],
            ]),
            'remind_credit' => '0',
        ];

        $mySettings = [];
        $sql = $db->query("SELECT `key` FROM " . $prefix . "settings");
        while ($row = $sql->fetch_object()) {
            array_push($mySettings, $row->key);
        }

        foreach ($settings as $k => $v) {
            $k = $db->real_escape_string($k);
            $v = $db->real_escape_string($v);

            if (!in_array($k, $mySettings)) {
                $db->query("INSERT INTO `{$prefix}settings` (`key`, `value`) VALUES ('$k', '$v')");
            }
        }

        $this->time["init_settings"] = microtime(true) - $this->time["init_settings"];

        $this->time["update_mail_templates"] = microtime(true);

        if (true || empty($CFG) || !is_array($CFG) || !array_key_exists("DOMAIN_RENEW_MAIL_UPDATED", $CFG) || !$CFG["DOMAIN_RENEW_MAIL_UPDATED"]) {
            $db->query("DELETE FROM {$prefix}email_templates WHERE ID = 57");

            $content = $db->real_escape_string(serialize([
                "deutsch" => "Wir haben Ihre Domain/s soeben bei der Registrierungsstelle verlängert:

%domains%

Wir haben das Ablaufdatum aktualisiert und Ihr Konto mit dem Verlängerungspreis belastet.",
                "english" => "We have renewed your domain/s at the registry:

%domains%

We have changed the expiration date and charged your account with the renewal price.",
            ]));

            $db->query("INSERT INTO {$prefix}email_templates (ID, name, foreign_name, title, content, admin_notification, category, vars) VALUES (57, 'Domain verlängert', 'Domain renewed', 'a:2:{s:7:\"deutsch\";s:18:\"Domain verlängert\";s:7:\"english\";s:14:\"Domain renewed\";}', '$content', 0, 'Domain', '%domains%');");
        }

        $this->time["update_mail_templates"] = microtime(true) - $this->time["update_mail_templates"];

        $this->time["init_cms_pages"] = microtime(true);

        $db->query("INSERT INTO `{$prefix}cms_pages` (`slug`, `title`, `content`, `seo`, `container`, `active`) VALUES ('index', 'a:2:{s:7:\"deutsch\";s:10:\"Startseite\";s:7:\"english\";s:8:\"Homepage\";}', 'a:2:{s:7:\"deutsch\";s:1660:\"PGRpdiBjbGFzcz0ianVtYm90cm9uIj4NCiAgICA8ZGl2IGNsYXNzPSJjb250YWluZXIiPg0KICAgICAgICA8aDE+eyRsYW5nLklOREVYLkhFQURMSU5FfHJlcGxhY2U6IiVwIjokY2ZnLlBBR0VOQU1FfTwvaDE+DQogICAgICAgIDxwPnskaW50cm98bmwyYnJ9PC9wPg0KICAgIDwvZGl2Pg0KPC9kaXY+DQoNCjxkaXYgY2xhc3M9ImNvbnRhaW5lciI+DQogICAgPGRpdiBjbGFzcz0icm93Ij4NCiAgICAgICAgPGRpdiBjbGFzcz0iY29sLW1kLTYiPg0KICAgICAgICAgICAgPHVsIGNsYXNzPSJsaXN0LWdyb3VwIj4NCiAgICAgICAgICAgICAgICA8bGkgY2xhc3M9Imxpc3QtZ3JvdXAtaXRlbSBhY3RpdmUiPnskbGFuZy5JTkRFWC5ORVdfUFJPRFVDVFN9PC9saT4NCiAgICAgICAgICAgICAgICB7Zm9yZWFjaCBmcm9tPSRucCBpdGVtPXB9PGxpIGNsYXNzPSJsaXN0LWdyb3VwLWl0ZW0iPnskcC5uYW1lfSA8c3BhbiBzdHlsZT0iZmxvYXQ6cmlnaHQiPjxhIGhyZWY9InskY2ZnLlBBR0VVUkx9cHJvZHVjdC97JHAuSUR9Ij57JGxhbmcuSU5ERVguSU5GT308L2E+PC9zcGFuPjwvbGk+DQogICAgICAgICAgICAgICAge2ZvcmVhY2hlbHNlfTxsaSBjbGFzcz0ibGlzdC1ncm91cC1pdGVtIj48Y2VudGVyPnskbGFuZy5JTkRFWC5OT1RISU5HfTwvY2VudGVyPjwvbGk+DQogICAgICAgICAgICAgICAgey9mb3JlYWNofQ0KICAgICAgICAgICAgPC91bD4NCiAgICAgICAgPC9kaXY+DQogICAgICAgIDxkaXYgY2xhc3M9ImNvbC1tZC02Ij4NCiAgICAgICAgICAgIDx1bCBjbGFzcz0ibGlzdC1ncm91cCI+DQogICAgICAgICAgICAgICAgPGxpIGNsYXNzPSJsaXN0LWdyb3VwLWl0ZW0gYWN0aXZlIj57JGxhbmcuSU5ERVguUE9QVUxBUl9QUk9EVUNUU308L2xpPg0KICAgICAgICAgICAgICAgIHtmb3JlYWNoIGZyb209JHBwIGl0ZW09cH08bGkgY2xhc3M9Imxpc3QtZ3JvdXAtaXRlbSI+eyRwLm5hbWV9IDxzcGFuIHN0eWxlPSJmbG9hdDpyaWdodCI+PGEgaHJlZj0ieyRjZmcuUEFHRVVSTH1wcm9kdWN0L3skcC5JRH0iPnskbGFuZy5JTkRFWC5JTkZPfTwvYT48L3NwYW4+PC9saT4NCiAgICAgICAgICAgICAgICB7Zm9yZWFjaGVsc2V9PGxpIGNsYXNzPSJsaXN0LWdyb3VwLWl0ZW0iPjxjZW50ZXI+eyRsYW5nLklOREVYLk5PVEhJTkd9PC9jZW50ZXI+PC9saT4NCiAgICAgICAgICAgICAgICB7L2ZvcmVhY2h9DQogICAgICAgICAgICA8L3VsPg0KICAgICAgICA8L2Rpdj4NCiAgICA8L2Rpdj4NCjwvZGl2Pg==\";s:7:\"english\";s:1660:\"PGRpdiBjbGFzcz0ianVtYm90cm9uIj4NCiAgICA8ZGl2IGNsYXNzPSJjb250YWluZXIiPg0KICAgICAgICA8aDE+eyRsYW5nLklOREVYLkhFQURMSU5FfHJlcGxhY2U6IiVwIjokY2ZnLlBBR0VOQU1FfTwvaDE+DQogICAgICAgIDxwPnskaW50cm98bmwyYnJ9PC9wPg0KICAgIDwvZGl2Pg0KPC9kaXY+DQoNCjxkaXYgY2xhc3M9ImNvbnRhaW5lciI+DQogICAgPGRpdiBjbGFzcz0icm93Ij4NCiAgICAgICAgPGRpdiBjbGFzcz0iY29sLW1kLTYiPg0KICAgICAgICAgICAgPHVsIGNsYXNzPSJsaXN0LWdyb3VwIj4NCiAgICAgICAgICAgICAgICA8bGkgY2xhc3M9Imxpc3QtZ3JvdXAtaXRlbSBhY3RpdmUiPnskbGFuZy5JTkRFWC5ORVdfUFJPRFVDVFN9PC9saT4NCiAgICAgICAgICAgICAgICB7Zm9yZWFjaCBmcm9tPSRucCBpdGVtPXB9PGxpIGNsYXNzPSJsaXN0LWdyb3VwLWl0ZW0iPnskcC5uYW1lfSA8c3BhbiBzdHlsZT0iZmxvYXQ6cmlnaHQiPjxhIGhyZWY9InskY2ZnLlBBR0VVUkx9cHJvZHVjdC97JHAuSUR9Ij57JGxhbmcuSU5ERVguSU5GT308L2E+PC9zcGFuPjwvbGk+DQogICAgICAgICAgICAgICAge2ZvcmVhY2hlbHNlfTxsaSBjbGFzcz0ibGlzdC1ncm91cC1pdGVtIj48Y2VudGVyPnskbGFuZy5JTkRFWC5OT1RISU5HfTwvY2VudGVyPjwvbGk+DQogICAgICAgICAgICAgICAgey9mb3JlYWNofQ0KICAgICAgICAgICAgPC91bD4NCiAgICAgICAgPC9kaXY+DQogICAgICAgIDxkaXYgY2xhc3M9ImNvbC1tZC02Ij4NCiAgICAgICAgICAgIDx1bCBjbGFzcz0ibGlzdC1ncm91cCI+DQogICAgICAgICAgICAgICAgPGxpIGNsYXNzPSJsaXN0LWdyb3VwLWl0ZW0gYWN0aXZlIj57JGxhbmcuSU5ERVguUE9QVUxBUl9QUk9EVUNUU308L2xpPg0KICAgICAgICAgICAgICAgIHtmb3JlYWNoIGZyb209JHBwIGl0ZW09cH08bGkgY2xhc3M9Imxpc3QtZ3JvdXAtaXRlbSI+eyRwLm5hbWV9IDxzcGFuIHN0eWxlPSJmbG9hdDpyaWdodCI+PGEgaHJlZj0ieyRjZmcuUEFHRVVSTH1wcm9kdWN0L3skcC5JRH0iPnskbGFuZy5JTkRFWC5JTkZPfTwvYT48L3NwYW4+PC9saT4NCiAgICAgICAgICAgICAgICB7Zm9yZWFjaGVsc2V9PGxpIGNsYXNzPSJsaXN0LWdyb3VwLWl0ZW0iPjxjZW50ZXI+eyRsYW5nLklOREVYLk5PVEhJTkd9PC9jZW50ZXI+PC9saT4NCiAgICAgICAgICAgICAgICB7L2ZvcmVhY2h9DQogICAgICAgICAgICA8L3VsPg0KICAgICAgICA8L2Rpdj4NCiAgICA8L2Rpdj4NCjwvZGl2Pg==\";}', 'a:2:{s:2:\"de\";a:2:{s:4:\"desc\";s:0:\"\";s:8:\"keywords\";s:0:\"\";}s:2:\"en\";a:2:{s:4:\"desc\";s:0:\"\";s:8:\"keywords\";s:0:\"\";}}', 0, 1);");

        $this->time["init_cms_pages"] = microtime(true) - $this->time["init_currencies"];

        $this->time["init_currencies"] = microtime(true);

        $db->query("INSERT INTO `{$prefix}currencies` (`ID`, `name`, `prefix`, `suffix`, `conversion_rate`, `currency_code`, `base`) VALUES (1, 'Euro', '', ' €', 1.00000000, 'EUR', 1)");

        $this->time["init_currencies"] = microtime(true) - $this->time["init_currencies"];

        $this->time["init_salutations"] = microtime(true);

        $salutations = [
            1 => [
                "language" => "deutsch",
                "gender" => "MALE",
                "salutation" => "Sehr geehrter Herr {lastName},",
            ],
            2 => [
                "language" => "deutsch",
                "gender" => "FEMALE",
                "salutation" => "Sehr geehrte Frau {lastName},",
            ],
            3 => [
                "language" => "english",
                "gender" => "MALE",
                "salutation" => "Dear Mr. {lastName},",
            ],
            4 => [
                "language" => "english",
                "gender" => "FEMALE",
                "salutation" => "Dear Mrs. {lastName},",
            ],
        ];

        foreach ($salutations as $id => $content) {
            if (!$db->query("SELECT 1 FROM " . $prefix . "salutations WHERE ID = $id")->num_rows) {
                $db->query("INSERT INTO " . $prefix . "salutations (`" . implode("`,`", array_keys($content)) . "`) VALUES ('" . implode("','", array_values($content)) . "')");
            }
        }

        $this->time["init_salutations"] = microtime(true) - $this->time["init_salutations"];
        $this->time["init_cronjobs"] = microtime(true);

        $pwd = Security::generatePassword(12, false, "lud");
        $db->query("INSERT INTO `{$prefix}cronjobs` (`ID`, `key`, `name`, `foreign_name`, `last_call`, `intervall`, `password`, `active`) VALUES
        (1, 'backup', 'Backups', 'Backups', 0, 86400, '{$pwd}', 0),
        (2, 'queue', 'E-Mailwarteschlange', 'Mail queue', 0, 60, '{$pwd}', 1),
        (3, 'transfer_import', 'Überweisungs-Import', 'Bank import', 0, 300, '{$pwd}', 1),
        (4, 'currency', 'Währungs-Kurse', 'Currency rates', 0, 3600, '{$pwd}', 1),
        (5, 'system_status', 'System-Status', 'System status', 0, 21600, '{$pwd}', 1),
        (6, 'birthday', 'Geburtstags-Glückwünsche', 'Birthday mail', 0, 86400, '{$pwd}', 0),
        (7, 'geo_ip', 'IP-Lokalisierung', 'IP localization', 0, 60, '{$pwd}', 1),
        (8, 'block_proxy', 'Proxy-Adressen aktualisieren', 'Refresh proxy addresses', 0, 3600, '{$pwd}', 1),
        (9, 'recurring', 'Abrechnungen', 'Recurring invoices', 0, 86400, '{$pwd}', 1),
        (10, 'reminders', 'Mahnungen', 'Invoice reminders', 0, 86400, '{$pwd}', 1),
        (11, 'domain_jobs', 'Domain-Änderungen', 'Domain changes', 0, 60, '{$pwd}', 1),
        (12, 'domain_sync', 'Domain-Synchronisierung', 'Domain sync', 0, 60, '{$pwd}', 1),
        (13, 'domain_renew', 'Domain-Verlängerungen', 'Domain renewals', 0, 86400, '{$pwd}', 1),
        (14, 'provisioning', 'Produkt-Einrichtung', 'Provisioning', 0, 60, '{$pwd}', 1),
        (15, 'ticket_import', 'Ticket-Import (POP3)', 'Ticket import', 0, 60, '{$pwd}', 1),
        (16, 'monitoring', 'Server-Monitoring', 'Server monitoring', 0, 60, '{$pwd}', 1);");

        $this->time["init_cronjobs"] = microtime(true) - $this->time["init_cronjobs"];
        $this->time["init_languages"] = microtime(true);

        foreach (Language::getLanguageFiles() as $file) {
            $file = $db->real_escape_string($file);
            $db->query("INSERT INTO `{$prefix}languages` (`language`) VALUES ('$file')");
        }

        $this->time["init_languages"] = microtime(true) - $this->time["init_languages"];
    }
}

class DatabaseTable
{
    public $name = "";
    public $columns = null;
    public $pk = null;
    public $uk = null;

    public function __construct($name)
    {
        $this->name = $name;
        $this->columns = [];
        $this->pk = [];
        $this->uk = [];
    }

    public function column(DatabaseColumn $col)
    {
        array_push($this->columns, $col);
    }

    public function primaryKey(array $pk)
    {
        $this->pk = $pk;
    }

    public function uniqueKey(array $uk)
    {
        $this->uk = $uk;
    }

    public function calcHash()
    {
        $str = $this->name;
        foreach ($this->columns as $col) {
            $str .= $col->calcHash();
        }
        $str .= implode("", $this->pk);
        $str .= implode("", $this->uk);

        return md5($str);
    }
}

class DatabaseColumn
{
    public $name = "";
    public $type = "";
    public $default = null;
    public $options = null;
    public $not_null = true;

    public function __construct($name, $type, $default = null, $options = null, $not_null = true)
    {
        $this->name = $name;
        $this->type = $type;
        $this->default = $default;
        $this->options = $options;
        $this->not_null = $not_null;
    }

    public function calcHash()
    {
        return md5($this->name . $this->type . strval($this->default) . strval($this->options) . strval($this->not_null));
    }
}
