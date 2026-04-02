<?php

// Class for handling one mail template

class MailTemplate {
	private $titleArr = null;
	private $contentArr = null;
	private $name = null;

	// Constructor gets necessary information
	public function __construct($template) {
		// Global some variables for security reasons
		global $db, $CFG;

		$template = $db->real_escape_string($template);

		if (is_numeric($template)) {
			$sql = $db->query("SELECT * FROM email_templates WHERE ID = '$template'");
		} else {
			$sql = $db->query("SELECT * FROM email_templates WHERE name = '$template'");
		}

		if ($sql->num_rows != 1) {
			return;
		}

		$templateInfo = $sql->fetch_object();

		// Write title and content array into object properties
		$this->titleArr = unserialize($templateInfo->title);
		$this->contentArr = unserialize($templateInfo->content);
		$this->id = $templateInfo->ID;
		$this->name = $templateInfo->name;
	}

	// Method to check if everything is loaded correct
	public function isInit() {
		return is_array($this->contentArr) && count($this->contentArr) > 0;
	}

	// Method to return title by language key
	public function getTitle($lang) {
		return is_array($this->titleArr) && isset($this->titleArr[$lang]) ? $this->titleArr[$lang] : false;
	}

	// Method to return content by language key
	public function getName() {
		return $this->name;
	}

	public function getForeignName() {
		return $this->foreign_name;
	}

	// Method to get the name

	public function getMail($lang, $name) {
		$mail = "";

		// Gets the header
		$headerObj = new MailTemplate("Header");

		if ($headerObj->getContent($lang) !== false) {
			$mail .= str_replace("%name%", $name, $headerObj->getContent($lang));
			// Double break for mail design
			$mail .= "\r\n\r\n";
		}

		// Gets the mail itself
		$mail .= $this->getContent($lang);

		// Gets the footer
		$footerObj = new MailTemplate("Footer");

		if ($footerObj->getContent($lang) !== false) {
			// Double break for mail design
			$mail .= "\r\n\r\n";

			$mail .= $footerObj->getContent($lang);
		}

		return $mail;
	}

	// Method to return full message with footer and header
	// Requires the name of the client
	public function getContent($lang) {
		return is_array($this->contentArr) && isset($this->contentArr[$lang]) ? $this->contentArr[$lang] : false;
	}

	// Method to get attachments by email template
	public function getAttachments($lang) {
		$arr = Array();

		$lang = basename($lang);
		foreach (glob(__DIR__ . "/../files/email_templates/{$this->id}/$lang/*.txt") as $f) {
			array_push($arr, realpath($f));
		}

		return $arr;
	}

	// Method to get ID
	public function getID() {
		return $this->id;
	}
}
