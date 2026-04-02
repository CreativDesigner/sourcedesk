<?php

// Class for doing some tasks in KeyHelp
if (!class_exists("KeyApi")) {
    class KeyApi
    {
        protected $url;
        protected $sid;

        // Constructor sets URL
        public function __construct($url)
        {
            $this->url = rtrim($url, "/") . "/";
        }

        // Method for logging in
        // Returns true if login is successful and false if login failed
        public function login($username, $password)
        {
            $r = $this->req($this->url . "index.php", array("username" => $username, "password" => $password, "lang" => "0", "submit" => "1"), true);
            if (strpos($r, "index.php?page=admin_dashboard&sid=") !== false) {
                $this->sid = substr($r, -26);
                return true;
            }
            return false;
        }

        // Method for getting a list of packages
        public function getPackages()
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "index.php?page=admin_template_account&sid=" . $this->sid, array());

            $dom = new DOMDocument;
            @$dom->loadHTML($r);
            $rows = $dom->getElementById("form-template-account")->getElementsByTagName('tr');

            $packages = array();
            foreach ($rows as $i => $row) {
                if (!$i || $i >= $rows->length - 1) {
                    continue;
                }

                @parse_str(trim($row->getElementsByTagName('td')->item(11)->getElementsByTagName('a')->item(0)->getAttribute("href")), $params);
                if (empty($params['id'])) {
                    return false;
                }

                $id = $params['id'];
                $name = trim($row->getElementsByTagName('td')->item(0)->textContent);

                $packages[$id] = $name;
            }

            asort($packages);
            return $packages;
        }

        // Method for getting the data of a package
        public function getPackage($id)
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "ajax.php", array(
                "action" => "get_account_template",
                "auth_sid" => $this->sid,
                "auth_id" => $this->getAuthId(),
                "account_template_id" => $id,
            ));

            return json_decode($r, true)["template"];
        }

        // Method for creating a customer account
        public function createAccount($username, $password, $data, $package, $standardsubdomain = true, $language = "de", $forceSSH = false)
        {
            if (empty($this->sid)) {
                return false;
            }

            while (strlen($username) > 11) {
                $username = substr(explode("-", $username)[0], 0, -1) . "-" . explode("-", $username)[1];
            }

            $data = array_merge($data, array(
                "id" => "",
                "submit" => "1",
                "email" => "",
                "username" => $username,
                "password" => $password,
                "password_confirmation" => $password,
                "account_template" => $package,
                "createsubdomain" => $standardsubdomain ? "1" : "0",
                "language" => $language,
                "sid" => $this->sid,
                "disk_space_multiplier" => "1",
                "sendmail_from" => "",
            ));

            $packageData = $this->getPackage($package);
            if (!is_array($packageData)) {
                return false;
            }

            $data = array_merge($data, $packageData);
            if ($forceSSH) {
                $data["ssh"] = "1";
            }

            $r = $this->req($this->url . "index.php?page=admin_accounts_user&action=add", $data, true);
            if (strpos($r, "index.php?page=admin_accounts_user&sid=") !== false) {
                return true;
            }

            return false;
        }

        // Method for enable SSL for a domain (Lets Encrypt)
        public function enableSSL($domain)
        {
            if (empty($this->sid)) {
                return false;
            }

            if (!$this->domainChange($domain, array("certificate" => "letsencrypt", "force_https" => "1", "apply_to_subdomains" => "all"))) {
                return false;
            }

            return true;
        }

        // Method for assigning a domain to an account
        public function assignDomain($username, $domain)
        {
            if (empty($this->sid)) {
                return false;
            }

            $id = $this->getAccountId($username);
            if ($id === false) {
                return false;
            }

            $data = array(
                "sid" => $this->sid,
                "send" => "adddomain",
                "domaintype" => "main",
                "main_domain" => $domain,
                "createwwwsubdomain" => 1,
                "parent" => "0",
                "owner" => $id,
                "isemaildomain" => "1",
            );

            $r = $this->req($this->url . "index.php?page=admin_domain_add&sid=" . $this->sid, $data, true);
            if (strpos($r, "index.php?page=admin_domain_index&sid=") !== false) {
                return true;
            }

            return false;
        }

        // Method for deleting a customer account
        public function deleteAccount($username)
        {
            if (empty($this->sid)) {
                return false;
            }

            $id = $this->getAccountId($username);
            $status = $this->getAccountStatus($username);
            $this->req($this->url . "index.php?page=admin_accounts_user&action=delete", array("sid" => $this->sid, "submit" => "1", "deletion_confirmation" => "1", "ids" => array($id)));
            if ($this->getAccountStatus($username) != $status) {
                return true;
            }

            return false;
        }

        // Method for suspend a customer account
        public function suspendAccount($username)
        {
            if (empty($this->sid)) {
                return false;
            }

            return $this->profileChange($username, array("is_suspended" => "1"));
        }

        // Method for unsuspend a customer account
        public function unsuspendAccount($username)
        {
            if (empty($this->sid)) {
                return false;
            }

            return $this->profileChange($username, array("is_suspended" => "0"));
        }

        // Method for changing the package of an account
        public function changePackage($username, $package, $forceSSH = false)
        {
            if (empty($this->sid)) {
                return false;
            }

            $packageData = $this->getPackage($package);
            if (!is_array($packageData)) {
                return false;
            }

            $packageData["disk_space_multiplier"] = "1";
            if ($forceSSH) {
                $packageData["ssh"] = "1";
            }

            return $this->profileChange($username, $packageData);
        }

        // Method for set a new password for a customer account
        public function changePassword($username, $password)
        {
            if (empty($this->sid)) {
                return false;
            }

            return $this->profileChange($username, array("password" => $password, "password_confirmation" => $password, "sendmail" => "0"));
        }

        // Method for doing profile changes
        public function profileChange($username, $data)
        {
            if (empty($this->sid)) {
                return false;
            }

            $id = $this->getAccountId($username);

            $formData = $this->parseForm($this->req($this->url . "index.php?page=admin_accounts_user&action=edit&id=$id&sid=" . $this->sid, array()));
            $data = array_merge($formData, $data);

            $r = $this->req($this->url . "index.php?page=admin_accounts_user&action=edit", $data, true);
            if (strpos($r, "index.php?page=admin_accounts_user&sid=") !== false) {
                return true;
            }

            return false;
        }

        // Method for doing domain changes
        public function domainChange($domain, $data)
        {
            if (empty($this->sid)) {
                return false;
            }

            $id = $this->getDomainId($domain);

            $formData = $this->parseForm($this->req($this->url . "index.php?page=admin_domain_edit&id=$id&sid=" . $this->sid, array()));
            $data = array_merge($formData, $data);
            $r = $this->req($this->url . "index.php?page=admin_domain_edit", $data, true);
            if (strpos($r, "index.php?page=admin_domain_index&sid=") !== false) {
                return true;
            }

            return false;
        }

        // Method for creating a database
        public function createDatabase($username, $db_password, $db_comment = "")
        {
            if (empty($this->sid)) {
                return false;
            }

            $sid = $this->getCustomerSid($username);

            $r = $this->req($this->url . "index.php?page=user_mysql_add", array("sid" => $sid, "send" => "addmysql", "comment" => $db_comment, "password" => $db_password, "passwordconfirm" => $db_password));

            $dom = new DOMDocument;
            @$dom->loadHTML($r);
            $form = $dom->getElementById("form-mysql");

            if (!($form instanceof DOMElement)) {
                return false;
            }

            $trs = $form->getElementsByTagName("tr");

            if ($trs->length <= 2) {
                return false;
            }

            $tr = $trs->item($trs->length - 1);
            return trim($tr->getElementsByTagName("td")->item(0)->textContent);
        }

        // Internal method for parsing a form
        protected function parseForm($html)
        {
            $dom = new DOMDocument;
            @$dom->loadHTML($html);

            $data = array();

            $inputs = $dom->getElementsByTagName("input");
            foreach ($inputs as $input) {
                $type = $input->getAttribute("type");
                if ($type == "hidden" || $type == "text" || $type == "password" || $type == "email" || $type == "number") {
                    $data[$input->getAttribute("name")] = $input->getAttribute("value");
                } else if ($type == "checkbox") {
                    $data[$input->getAttribute("name")] = $input->getAttribute("checked") ? $input->getAttribute("value") : "";
                } else if ($type == "radio") {
                    if ($input->getAttribute("checked")) {
                        $data[$input->getAttribute("name")] = $input->getAttribute("value");
                    }
                }

            }

            $textareas = $dom->getElementsByTagName("textarea");
            foreach ($textareas as $textarea) {
                $data[$textarea->getAttribute("name")] = $textarea->nodeValue;
            }

            $selects = $dom->getElementsByTagName("select");
            foreach ($selects as $select) {
                $name = $select->getAttribute("name");
                $options = $select->getElementsByTagName("option");

                $value = "";
                if (count($options) > 0) {
                    $value = $options->item(0)->getAttribute("value");
                }

                foreach ($options as $option) {
                    if ($option->getAttribute("selected")) {
                        $value = $option->getAttribute("value");
                    }
                }

                $data[$name] = $value;
            }

            return $data;
        }

        // Internal method for getting the ID by username
        protected function getAccountId($username)
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "index.php?page=admin_accounts_user", array(
                "sid" => $this->sid,
                "update_view" => "1",
            ));

            $dom = new DOMDocument;
            @$dom->loadHTML($r);
            $rows = $dom->getElementById("form-user-account-index")->getElementsByTagName('tr');

            foreach ($rows as $i => $row) {
                if (!$i || $i >= $rows->length - 1) {
                    continue;
                }

                if (trim($row->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0)->textContent) == $username) {
                    $url = trim($row->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0)->getAttribute("href"));
                    parse_str($url, $params);
                    return $params['id'];
                }
            }

            return false;
        }

        // Internal method for getting the ID by domain
        protected function getDomainId($domain)
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "index.php?page=admin_domain_index&sid=" . $this->sid, array());
            $dom = new DOMDocument;
            @$dom->loadHTML($r);
            $rows = $dom->getElementById("form-domain")->getElementsByTagName('tr');

            foreach ($rows as $i => $row) {
                if ($i < 2) {
                    continue;
                }

                if (trim($row->getElementsByTagName('td')->item(1)->textContent) == $domain) {
                    $url = trim($row->getElementsByTagName('td')->item(7)->getElementsByTagName('a')->item(0)->getAttribute("href"));
                    parse_str($url, $params);
                    return $params['id'];
                }
            }

            return false;
        }

        // Internal method for getting a customer login url
        protected function getCustomerSid($username)
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "index.php?page=admin_accounts_index&sid=" . $this->sid, array());

            $dom = new DOMDocument;
            @$dom->loadHTML($r);
            $rows = $dom->getElementById("form-user")->getElementsByTagName('tr');

            foreach ($rows as $i => $row) {
                if ($i < 2) {
                    continue;
                }

                if (trim($row->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0)->textContent) == $username) {
                    $url = trim($row->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0)->getAttribute("href"));
                    $url = $this->req($this->url . $url, array(), true);
                    parse_str($url, $params);
                    return $params['sid'];
                }
            }

            return false;
        }

        // Internal method for getting the stauts of an account
        protected function getAccountStatus($username)
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "index.php?page=admin_accounts_user&sid=" . $this->sid, array());

            $dom = new DOMDocument;
            @$dom->loadHTML($r);
            $rows = $dom->getElementById("form-user-account-index")->getElementsByTagName('tr');

            foreach ($rows as $i => $row) {
                if (!$i || $i >= $rows->length - 1) {
                    continue;
                }

                if (trim($row->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0)->textContent) == $username) {
                    return trim($row->getElementsByTagName('td')->item(0)->getElementsByTagName('span')->item(0)->getAttribute("title"));
                }
            }

            return false;
        }

        // Internal method for getting the auth ID (required for Ajax)
        protected function getAuthId()
        {
            if (empty($this->sid)) {
                return false;
            }

            $r = $this->req($this->url . "index.php?page=admin_index&sid=" . $this->sid, array());
            $pos = strpos($r, '"auth.id":"');
            if ($pos === false) {
                return false;
            }

            return intval(substr($r, $pos + 11));
        }

        // Internal method for request
        protected function req($url, $data, $effectiveUrl = false)
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            if (is_array($data) && count($data)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);

            if ($effectiveUrl) {
                $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                curl_close($ch);
                return $url;
            }

            curl_close($ch);
            return $res;
        }
    }
}
