<?php

class Domain {
	public static function sendMail($mail, $user, $domain) {
		global $maq, $CFG;

		$user = User::getInstance($user, "ID");
		$language = $user->getLanguage();
		$currency = $user->getCurrency();

		$mtObj = new MailTemplate($mail);
		$title = $mtObj->getTitle($language);
		$mail = $mtObj->getMail($language, $user->get()['name']);

		$maq->enqueue([
			"domain" => $domain,
			"url" => $CFG['PAGEURL'] . "domain/" . str_replace(".", "/", $domain),
		], $mtObj, $user->get()['mail'], $title, $mail, "From: {$CFG['PAGENAME']} <{$CFG['MAIL_SENDER']}>", $user->get()['ID'], true, 0, 0, $mtObj->getAttachments($language));
	}
}