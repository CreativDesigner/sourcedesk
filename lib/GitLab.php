<?php
// Class for Git integration (supports GitLab and GitHub)

if ($GLOBALS["CFG"]["GIT_TYPE"] == "github") {
	class GitLab {
		private static function addNamespace($pid) {
			global $CFG;
			return strpos($pid, "/") !== false ? $pid : $CFG['GITHUB_USER'] . "/" . $pid;
		}

		private static function removeNamespace($pid) {
			$ex = explode("/", $pid);
			return array_pop($ex);
		}

		public static function getTags($pid) {
			global $CFG;

			$pid = self::addNamespace($pid);

			$ch = curl_init("https://api.github.com/repos/$pid/releases");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERPWD, urlencode($CFG['GITHUB_USER']) . ":" . urlencode($CFG['GITHUB_KEY']));
			curl_setopt($ch, CURLOPT_USERAGENT, 'haseDESK');
			$r = json_decode(curl_exec($ch));
			curl_close($ch);

			if ($r === false) {
				return false;
			}

			$tags = [];
			foreach ($r as $c) {
				$tags[$c->tag_name] = Array($c->body, $c->id, strtotime($c->published_at));
			}

			return $tags;
		}

		public static function getGitlabName($pid) {
			global $CFG;
			return self::removeNamespace($pid);
		}

		public static function getFile($pid, $tname) {
			global $CFG;

			$pid = self::addNamespace($pid);

			$ch = curl_init("https://github.com/$pid/archive/$tname.zip");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERPWD, urlencode($CFG['GITHUB_USER']) . ":" . urlencode($CFG['GITHUB_KEY']));
			curl_setopt($ch, CURLOPT_USERAGENT, 'haseDESK');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$r = curl_exec($ch);
			curl_close($ch);

			if ($r === false) {
				return false;
			}

			return $r;
		}
	}
} else {
	class GitLab {
		public static function getTags($pid) {
			global $CFG;

			$ch = curl_init($CFG['GITLAB_HOST'] . "/api/v4/projects/$pid/repository/tags");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["PRIVATE-TOKEN: " . $CFG['GITLAB_KEY']]);
			$r = json_decode(curl_exec($ch));
			curl_close($ch);

			if ($r === false) {
				return false;
			}

			$tags = [];
			foreach ($r as $c) {
				$tags[$c->name] = Array($c->release->description, $c->commit->id, strtotime($c->commit->authored_date));
			}

			return $tags;
		}

		public static function getGitlabName($pid) {
			global $CFG;

			$ch = curl_init($CFG['GITLAB_HOST'] . "/api/v4/projects/$pid");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["PRIVATE-TOKEN: " . $CFG['GITLAB_KEY']]);
			$r = json_decode(curl_exec($ch));
			curl_close($ch);

			if ($r === false) {
				return false;
			}

			return basename($r->web_url);
		}

		public static function getFile($pid, $tname) {
			global $CFG;

			$ch = curl_init($CFG['GITLAB_HOST'] . "/api/v4/projects/$pid");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["PRIVATE-TOKEN: " . $CFG['GITLAB_KEY']]);
			$r = json_decode(curl_exec($ch));
			curl_close($ch);

			if ($r === false) {
				return false;
			}

			$ch = curl_init($r->web_url . "/repository/archive.zip?ref=" . urlencode($tname));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["PRIVATE-TOKEN: " . $CFG['GITLAB_KEY']]);
			$r = curl_exec($ch);
			curl_close($ch);

			if ($r === false) {
				return false;
			}

			return $r;
		}
	}
}