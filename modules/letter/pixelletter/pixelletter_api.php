<?

// PixelLetter Class - Version: 2.01
// Last change: 15.01.2016
// www.pixelletter.de

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '')
    {
        return "@$filename;filename=" . ($postname ?: basename($filename)) . ($mimetype ? ";type=$mimetype" : '');
    }
}

class PixelLetterApi
{

    public function remove_chars($val)
    {
        $val = str_replace("&", "&amp;", $val);
        $val = str_replace(">", "&gt;", $val);
        $val = str_replace("<", "&lt;", $val);
        return $val;
    }

    public function __construct($email, $pw, $agb, $widerrufsverzicht, $testmodus)
    {
        $this->email = $email; // Ihre e-mailadresse mit der Sie bei Pixelletter registriert sind
        $this->pw = $pw; // Ihr PixelLetter Passwort
        $this->agb = $agb; // muss auf ja stehen
        $this->widerrufsverzicht = $widerrufsverzicht; // ja = Verzicht akzeptiert / nein = Verzicht nicht akzeptiert
        $this->testmodus = $testmodus; // true = Testmodus eingeschaltet / false = Testmodus ausgeschaltet
    }

    public function signature_options($signature_arr)
    { // ist optional.
        if ($signature_arr) {
            return '
				<sender>' . $this->remove_chars($signature_arr["sender"]) . '</sender>
				<recipient>' . $this->remove_chars($signature_arr["recipient"]) . '</recipient>
				<cc>' . $this->remove_chars($signature_arr["cc"]) . '</cc>
				<bcc>' . $this->remove_chars($signature_arr["bcc"]) . '</bcc>
				<subject>' . $this->remove_chars($signature_arr["subject"]) . '</subject>
				<body>' . $this->remove_chars($signature_arr["body"]) . '</body>
				<filename>' . $this->remove_chars($signature_arr["filename"]) . '</filename>';
        }
    }

    public function cashondelivery_options($cashondelivery_arr)
    { // ist nur bei Nachnahme (addoption 31) verpflichtend.
        if ($cashondelivery_arr) {
            return '
				<wiretransfer>
					<recipient>
						<name>' . $this->remove_chars($cashondelivery_arr["name"]) . '</name>
						<bankaccountid>' . $this->remove_chars($cashondelivery_arr["bankaccountid"]) . '</bankaccountid>
						<blz>' . $this->remove_chars($cashondelivery_arr["blz"]) . '</blz>
						<bankname>' . $this->remove_chars($cashondelivery_arr["bankname"]) . '</bankname>
					</recipient>
					<reasonforpayment1>' . $this->remove_chars($cashondelivery_arr["reasonforpayment1"]) . '</reasonforpayment1>
					<reasonforpayment2>' . $this->remove_chars($cashondelivery_arr["reasonforpayment2"]) . '</reasonforpayment2>
					<amount>' . $this->remove_chars($cashondelivery_arr["amount"]) . '</amount>
				</wiretransfer>';
        }
    }

    public function submit_text($action, $transaction, $address, $fax, $subject, $message, $location, $addoption = "", $destination = "", $signature_arr = "", $returnaddress = "", $control = "", $cashondelivery_arr = "")
    {
        $this->command = '<command>
		<order type="text">
			<options>
				<action>' . $action . '</action>
				<transaction>' . $this->remove_chars($transaction) . '</transaction>
				<control>' . $this->remove_chars($control) . '</control>
				<fax>' . $fax . '</fax>
				<location>' . $location . '</location>
				<destination>' . $destination . '</destination>
				<addoption>' . $addoption . '</addoption>
				<returnaddress>' . $this->remove_chars($returnaddress) . '</returnaddress>' . $this->signature_options($signature_arr) . $this->cashondelivery_options($cashondelivery_arr) . '
			</options>
			<text>
				<address>
				' . $this->remove_chars($address) . '
				</address>
				<subject>' . $this->remove_chars($subject) . '</subject>
				<message>
				' . $this->remove_chars($message) . '
				</message>
			</text>
		</order>
	</command>';
        return $result = $this->submit();
    }

    public function submit_upload($action, $transaction, $fax, $file_array, $location, $addoption = "", $destination = "", $signature_arr = "", $returnaddress = "", $control = "", $cashondelivery_arr = "")
    {
        $this->file_array = $file_array;
        $this->command = '<command>
		<order type="upload">
			<options>
				<action>' . $action . '</action>
				<transaction>' . $this->remove_chars($transaction) . '</transaction>
				<control>' . $this->remove_chars($control) . '</control>
				<fax>' . $fax . '</fax>
				<location>' . $location . '</location>
				<destination>' . $destination . '</destination>
				<addoption>' . $addoption . '</addoption>
				<returnaddress>' . $this->remove_chars($returnaddress) . '</returnaddress>' . $this->signature_options($signature_arr) . $this->cashondelivery_options($cashondelivery_arr) . '
			</options>
		</order>
	</command>';
        return $result = $this->submit();
    }

    public function submit_postcard($action, $transaction, $address, $message, $file_array, $location, $addoption = "", $destination = "", $returnaddress = "", $control = "", $font = "")
    {
        $this->file_array = $file_array;
        $this->command = '<command>
		<order type="text">
			<options>
				<action>' . $action . '</action>
				<transaction>' . $this->remove_chars($transaction) . '</transaction>
				<control>' . $this->remove_chars($control) . '</control>
				<location>' . $location . '</location>
				<destination>' . $destination . '</destination>
				<addoption>' . $addoption . '</addoption>
				<font>' . $font . '</font>
				<returnaddress>' . $this->remove_chars($returnaddress) . '</returnaddress>
			</options>
			<text>
				<address>' . $this->remove_chars($address) . '</address>
				<message>' . $this->remove_chars($message) . '</message>
			</text>
		</order>
	</command>';
        return $result = $this->submit();
    }

    public function cancel_order($order_id)
    {
        $this->file_array = [];

        $this->command = '<command>
		<order type="cancel">
			<id>' . $order_id . '</id>
		</order>
	</command>';
        return $result = $this->submit();
    }

    public function get_account_info()
    {
        $this->command = '<command>
		<info>
			<account:info type="all" />
		</info>
	</command>';
        return $result = $this->submit();
    }

    public function submit()
    {

        $url = "https://www.pixelletter.de/xml/index.php";
        $formvars = array();
        $formvars["xml"] = utf8_encode('<?xml version="1.0" encoding="UTF-8" standalone="yes"?' . '>
<pixelletter version="1.3">
	<auth>
		<email>' . $this->email . '</email>
		<password>' . $this->pw . '</password>
		<agb>' . $this->agb . '</agb>
		<widerrufsverzicht>' . $this->widerrufsverzicht . '</widerrufsverzicht>
		<testmodus>' . $this->testmodus . '</testmodus>
		<ref></ref>
	</auth>
	' . $this->command . '
</pixelletter>');

        $file_array = $this->file_array;

        if (is_array($file_array)) {
            for ($i = 0; $i < count($file_array); $i++) {
                if (file_exists($file_array[$i])) {
                    $formvars["uploadfile" . $i] = curl_file_create($file_array[$i], 'application/pdf');
                } else {
                    exit("Fehler: Datei " . $file_array[$i] . " existiert lokal nicht!");
                }
            }
        } elseif (is_string($file_array)) { // deprecated Version 1.1
            if (file_exists($file_array)) {
                $formvars["userfile"] = curl_file_create($file_array, 'application/pdf');
            }
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formvars);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
