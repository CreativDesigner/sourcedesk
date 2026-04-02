<?php
// Addon for supporting DomainConnect

class DomainConnectAddon extends Addon
{
    public static $shortName = "domainconnect";

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
            'version' => "1.0",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function getSettings()
    {
        return array();
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function clientPages()
    {
        return array("domainconnect" => "client");
    }

    public function hooks()
    {
        return [
            ["DnsZoneCreated", "setDnsRecord", 0],
        ];
    }

    public function setDnsRecord($pars)
    {
        global $CFG, $lang;

        $dns = $pars['driver'];
        $domain = $pars["domain"];

        $val = substr($CFG['PAGEURL'], strpos($CFG['PAGEURL'], "://") + 3);
        $val = substr($val, 0, strpos($val, "/"));
        $val .= "/" . $lang['ISOCODE'] . "/domainconnect";

        $record = [
            "_domainconnect." . $domain,
            "TXT",
            $val,
            3600,
            0,
            true,
        ];

        $dns->addRecord($pars['domain'], $record, true);
    }

    protected function settings($domain)
    {
        global $CFG;

        $host = substr($CFG['PAGEURL'], strpos($CFG['PAGEURL'], "://") + 3);
        $host = substr($host, 0, strpos($host, "/"));

        $config = [
            "providerId" => $host,
            "providerName" => $CFG['PAGENAME'],
            "urlSyncUX" => $CFG['PAGEURL'] . "domainconnect",
            "urlAPI" => $CFG['PAGEURL'] . "domainconnect",
            "urlControlPanel" => $CFG['PAGEURL'] . "domain/%domain%",
        ];

        echo json_encode($config);
    }

    protected function verifySignature()
    {
        global $pars;

        $template = $this->getTemplate();
        $host = $_GET['key'] . "." . $template["syncPubKeyDomain"];

        $txtRecords = dns_get_record($host, DNS_TXT);
        $algorithm = "";
        $certificateType = "";
        $keyParts = [];

        foreach ($txtRecords as $rr) {
            $values = [];
            $ex = explode(",", $rr['txt']);

            foreach ($ex as $value) {
                list($key, $value) = explode("=", $value, 2);
                $values[$key] = $value;

                if (!isset($values["p"])) {
                    throw new Exception("No part indication in TXT record");
                }

                $algo = $values["a"] ?? "";
                if (!empty($algo) && !empty($algorithm) && $algo != $algorithm) {
                    throw new Exception("Algorithm change detected");
                }
                $algorithm = $algo;

                $cert = $values["t"] ?? "";
                if (!empty($cert) && !empty($certificateType) && $cert != $certificateType) {
                    throw new Exception("Certificate type change detected");
                }
                $certificateType = $cert;

                $keyParts[$values["p"]] = $values["d"];
            }
        }

        if (empty($certificateType)) {
            $certificateType = "x509";
        }
        $certificateType = strtolower($certificateType);

        if (empty($algorithm)) {
            $algorithm = "rs256";
        }
        $algorithm = strtolower($algorithm);

        if (!in_array($algorithm, ["rs256"])) {
            throw new Exception("Unsupported algorithm");
        }

        if (!in_array($certificateType, ["x509"])) {
            throw new Exception("Unsupported certificate type");
        }

        ksort($keyParts);
        $signKey = "-----BEGIN PUBLIC KEY-----\n" . implode("", $keyParts) . "\n-----END PUBLIC KEY-----";

        $signature = $_GET['sig'];
        unset($_GET['p'], $_GET['sig'], $_GET['key']);

        $signStr = http_build_query($_GET);
        $res = openssl_verify($signStr, base64_decode($signature), $signKey, OPENSSL_ALGO_SHA256);
        if ($res !== 1) {
            throw new Exception("Signature is wrong");
        }
    }

    protected function apply()
    {
        global $tpl, $var, $title, $lang, $pars, $CFG, $user, $db;

        try {
            $originalGet = $_GET;
            $template = $this->getTemplate();

            $signedRequest = false;
            if (!empty($_GET['sig']) && !empty($_GET['key'])) {
                $this->verifySignature();
                $signedRequest = true;
            } else if (!empty($template["syncPubKeyDomain"])) {
                throw new Exception("Signature is required");
            }

            $var['warn_phishing'] = boolval($template["warnPhishing"]);

            $domain = $var['domain'] = $_GET['domain'];
            unset($_GET['p'], $_GET['sig'], $_GET['key']);
            $configVariables = $_GET;
            $_GET = $originalGet;

            if (count($configVariables) && !$signedRequest) {
                $var['warn_phishing'] = true;
            }

            if (!$var['logged_in']) {
                unset($_GET['p']);
                $pars = implode("/", $pars);
                $redirect = urlencode("domainconnect/$pars");
                header('Location: ' . $CFG['PAGEURL'] . 'login?redirect_to=' . $redirect . '&' . http_build_query($_GET));
                exit;
            }

            $domainSql = $db->query("SELECT * FROM domains WHERE domain = '" . $db->real_escape_string($domain) . "' AND user = " . $user->get()['ID'] . " AND status IN ('REG_OK', 'KK_OK')");
            if (!$domainSql->num_rows) {
                throw new Exception("Domain not found");
            }
            $domainInfo = $domainSql->fetch_object();

            $dns = DNSHandler::getDriver($domain);
            if (!$dns) {
                throw new Exception("Internal DNS fault");
            }

            $zone = $dns->getZone($domain);
            if (!is_array($zone)) {
                throw new Exception("DNS zone not found");
            }

            if (isset($_POST['apply'])) {
                foreach ($template["records"] as $rr) {
                    foreach ($rr as &$val) {
                        $val = str_replace("@", "%fqdn%", $val);

                        $val = str_replace("%domain%", $_GET['domain'], $val);
                        $val = str_replace("%host%", $_GET['host'] ?? "", $val);

                        if (!empty($_GET['host'])) {
                            $val = str_replace("%fqdn%", $_GET['host'] . "." . $_GET['domain'], $val);
                        } else {
                            $val = str_replace("%fqdn%", $_GET['domain'], $val);
                        }

                        foreach ($configVariables as $k => $v) {
                            $val = str_replace("%$k%", $v, $val);
                        }
                    }
                    unset($val);

                    $record = [
                        $rr['host'],
                        $rr['type'],
                        "",
                        $rr['ttl'] ?? 3600,
                        $rr["priority"] ?? 0,
                    ];

                    if ($record[0] == $domain) {
                        $record[0] = "";
                    }

                    if (substr($record[0], (strlen($domain) + 1) / -1) == "." . $domain) {
                        $record[0] = substr($record[0], 0, (strlen($domain) + 1) / -1);
                    }

                    switch ($rr['type']) {
                        case "A":
                        case "AAAA":
                        case "CNAME":
                            $record[2] = $rr["pointsTo"];

                            foreach ($zone as $rid => $zrr) {
                                if (strtolower($zrr[0]) != strtolower($record[0])) {
                                    continue;
                                }

                                if (!in_array(strtolower($zrr[1]), ["a", "aaaa", "cname"])) {
                                    continue;
                                }

                                $dns->removeRecord($domain, $rid);
                            }
                            break;

                        case "MX":
                            $record[2] = $rr["pointsTo"];

                            foreach ($zone as $rid => $zrr) {
                                if (strtolower($zrr[0]) != strtolower($record[0])) {
                                    continue;
                                }

                                if (strtolower($zrr[1]) != "mx") {
                                    continue;
                                }

                                $dns->removeRecord($domain, $rid);
                            }
                            break;

                        case "TXT":
                            $record[2] = $rr["data"];

                            $conflictMode = $template["txtConflictMatchingMode"] ?? "None";

                            if ($conflictMode != "None") {
                                $conflictPrefix = "";
                                if ($conflictMode == "Prefix") {
                                    $conflictPrefix = $template["txtConflictMatchingPrefix"] ?? "";
                                }

                                foreach ($zone as $rid => $zrr) {
                                    if (strtolower($zrr[0]) != strtolower($record[0])) {
                                        continue;
                                    }

                                    if (strtolower($zrr[1]) != "txt") {
                                        continue;
                                    }

                                    $prefix = substr($zrr[2], 0, strlen($conflictPrefix));
                                    if (strtolower($prefix) != strtolower($conflictPrefix)) {
                                        continue;
                                    }

                                    $dns->removeRecord($domain, $rid);
                                }
                            }

                            break;

                        default:
                            continue;
                    }

                    $dns->addRecord($domain, $record);
                }

                exit;
            }

            $tpl = __DIR__ . "/apply.tpl";
            $title = $this->getLang("TITLE");
            $var['addonlang'] = $this->getLang();
            $var['redirect_url'] = str_replace('"', '', $_GET['redirect_uri'] ?? "");
            $var['service_provider'] = $template["shared"] && !empty($_GET['providerName']) ? $_GET['providerName'] : $template["providerName"];
            $var['sp_logo'] = $template["logoUrl"] ?? "";

            if (!empty($var['redirect_url'])) {
                $var['redirect_url'] .= strpos($var['redirect_url'], "?") === false ? "?" : "&";

                $urlComponents = parse_url($var['redirect_url']);
                parse_str($urlComponents["query"], $urlQuery);

                $allowedHost = $template["syncRedirectDomain"];
                if (!$signedRequest && substr($urlComponents["host"], strlen($allowedHost) / -1) != $allowedHost) {
                    throw new Exception("Redirect URI is not allowed");
                }

                if (array_key_exists("state", $urlQuery)) {
                    $var['redirect_url'] .= "state=" . $urlQuery["state"] . "&";
                }
            }

            $var['records'] = [];
            foreach ($template["records"] as $rr) {
                $vals = array_slice($rr, 0, 4);

                foreach ($vals as &$val) {
                    $val = str_replace("%domain%", $_GET['domain'], $val);
                    $val = str_replace("%host%", $_GET['host'] ?? "", $val);

                    if (!empty($_GET['host'])) {
                        $val = str_replace("%fqdn%", $_GET['host'] . "." . $_GET['domain'], $val);
                    } else {
                        $val = str_replace("%fqdn%", $_GET['domain'], $val);
                    }

                    foreach ($configVariables as $k => $v) {
                        $val = str_replace("%$k%", $v, $val);
                    }
                }
                unset($val);

                $var['records'][] = $vals;
            }
        } catch (Exception $ex) {
            $var['error'] = $ex->getMessage();
            $title = $lang['ERROR']['TITLE'];
            $tpl = "error";
        }
    }

    protected function getTemplate()
    {
        global $pars;

        $provider = $pars[3] ?? "";
        if (empty($provider)) {
            http_response_code(404);
            throw new Exception("No provider specified");
        }

        $template = $pars[5] ?? "";
        if (empty($template)) {
            http_response_code(404);
            throw new Exception("No template specified");
        }

        if (!file_exists(__DIR__ . "/templates/" . strtolower(basename($provider)) . "." . strtolower(basename($template)) . ".json")) {
            http_response_code(404);
            throw new Exception("Template not found");
        }

        $template = @json_decode(file_get_contents(__DIR__ . "/templates/" . strtolower(basename($provider)) . "." . strtolower(basename($template)) . ".json"), true);
        if (!$template) {
            http_response_code(404);
            throw new Exception("Invalid template");
        }

        if ($template["syncBlock"]) {
            throw new Exception("Template not supported");
        }

        return $template;
    }

    public function client()
    {
        global $pars;

        try {
            $version = $pars[0] ?? "";
            if ($version != "v2") {
                throw new Exception("We only support version 2");
            }

            if ("settings" == ($pars[2] ?? "")) {
                $domain = $pars[1] ?? "";
                if (empty($domain) || !filter_var($pars[1], FILTER_VALIDATE_DOMAIN)) {
                    throw new Exception("No valid domain specified");
                }

                $this->settings($domain);
                exit;
            }

            if ("domainTemplates" == ($pars[1] ?? "")) {
                $template = $this->getTemplate();

                if ("apply" == ($pars[6] ?? "")) {
                    $this->apply();
                    return;
                }

                die(json_encode(["version" => $template["version"]]));
            }

            throw new Exception("Route not found");
        } catch (Exception $ex) {
            die(json_encode(["status" => "error", "message" => $ex->getMessage()]));
        }
    }
}
