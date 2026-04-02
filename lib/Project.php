<?php

class Project {
	public static function working($admin = null) {
		global $adminInfo, $db, $CFG;
		if ($admin === null && !isset($adminInfo->ID)) {
			return false;
		}

		if ($admin === null) {
			$admin = $adminInfo->ID;
		}

		$admin = intval($admin);

		$sql = $db->query("SELECT task FROM project_times WHERE end = '0000-00-00 00:00:00' AND admin = $admin");
		if ($sql->num_rows != 1) {
			return false;
		}
		
		$task = $sql->fetch_object()->task;

		if ($task < 0) {
			if (!$db->query("SELECT 1 FROM projects WHERE ID = " . (intval($task) / -1))->num_rows) {
				return false;
			}
		} else {
			if (!$db->query("SELECT 1 FROM project_tasks WHERE ID = " . intval($task))->num_rows) {
				return false;
			}
		}

		return $task;
	}

	public static function invoice($project) {
		global $db, $CFG, $lang;

		$arr = [];
		
		$sql = $db->query("SELECT * FROM projects WHERE ID = " . intval($project));
		if ($sql->num_rows != 1) {
			return $arr;
		}
		$info = $sql->fetch_object();
		$standard_entgelt = 0;

		if (!$info->entgelt_type && !$info->entgelt_done && $info->entgelt) {
			$arr[0] = ["<b>{$lang['PROJECT_CLASS']['PROJECT']} {$info->name}</b><br />{$lang['PROJECT_CLASS']['PAUSCH']}", doubleval($info->entgelt), -1];
		}

		$time_contingent = max(0, $info->time_contingent);
		
		if ($info->entgelt_type) {
			$standard_entgelt = $info->entgelt;

			$sql2 = $db->query("SELECT `start`, `end` FROM project_times WHERE task = -{$info->ID}");

			$workMinutes = $info->entgelt_done / -1;
			while($tt = $sql2->fetch_object()){
				if($tt->end == "0000-00-00 00:00:00") continue;
				if(($subTime = strtotime($tt->end) - strtotime($tt->start)) > 0) $workMinutes += $subTime / 60;
			}

			$con = min($time_contingent, $workMinutes);
			$workMinutes -= $con;
			$time_contingent -= $con;

			if ($workMinutes >= 1) {
				$raw = $workMinutes = self::roundTime($workMinutes, $info->time_tracking);
				$workHours = floor($workMinutes / 60);
				$workMinutes = $workMinutes % 60;
				$str = self::timeStr($workHours, $workMinutes);

				$loan = ceil(($workHours * $info->entgelt + round($workMinutes * $info->entgelt / 60, 2)) * 2) / 2;

				$arr[$t->ID] = ["<b>{$lang['PROJECT_CLASS']['PROJECT']} {$info->name}</b><br />$str", doubleval($loan), round($raw)];
			}
		}

		$sql = $db->query("SELECT * FROM project_tasks WHERE project = {$info->ID}");
		while ($t = $sql->fetch_object()) {
			if ($t->entgelt <= 0) {
				if ($info->entgelt_type) {
					$t->entgelt_type = $info->entgelt_type;
					$t->entgelt = $standard_entgelt;
				} else {
					continue;
				}
			}

			if (!$t->entgelt_type) {
				if ($t->entgelt_done) {
					continue;
				}

				$arr[$t->ID] = ["<b>{$lang['PROJECT_CLASS']['TASK']} {$t->name}</b><br />{$lang['PROJECT_CLASS']['PAUSCH']}", doubleval($t->entgelt), -1];
			} else {
				$sql2 = $db->query("SELECT `start`, `end` FROM project_times WHERE task = {$t->ID}");

				$workMinutes = $t->entgelt_done / -1;
				while($tt = $sql2->fetch_object()){
					if($tt->end == "0000-00-00 00:00:00") continue;
					if(($subTime = strtotime($tt->end) - strtotime($tt->start)) > 0) $workMinutes += $subTime / 60;
				}

				$con = min($time_contingent, $workMinutes);
				$workMinutes -= $con;
				$time_contingent -= $con;

				if ($workMinutes >= 1) {
					$raw = $workMinutes = self::roundTime($workMinutes, $info->time_tracking);
					$workHours = floor($workMinutes / 60);
					$workMinutes = $workMinutes % 60;
					$str = self::timeStr($workHours, $workMinutes);

					$loan = ceil(($workHours * $t->entgelt + round($workMinutes * $t->entgelt / 60, 2)) * 2) / 2;

					$arr[$t->ID] = ["<b>{$lang['PROJECT_CLASS']['TASK']} {$t->name}</b><br />$str", doubleval($loan), round($raw)];
				}
			}
		}

		return $arr;
	}

	private static function timeStr($workHours, $workMinutes) {
		global $lang;

		$str = "";
		if($workHours == 1) $str = $lang['PROJECT_CLASS']['1HOUR'];
		else if($workHours > 0) $str = $workHours . " " . $lang['PROJECT_CLASS']['HOURS'];

		if($workHours > 0){
			if($workMinutes == 1) $str .= " " . $lang['PROJECT_CLASS']['AOM'];
			else if($workMinutes > 0) $str .= " " . str_replace("%m", $workMinutes, $lang['PROJECT_CLASS']['AXM']);
		} else {
			if($workMinutes == 1) $str .= $lang['PROJECT_CLASS']['1MINUTE'];
			else if($workMinutes > 0) $str .= $workMinutes . " " . $lang['PROJECT_CLASS']['MINUTES'];
		}

		return $str;
	}

	public static function time($sec, $done = false) {
		global $lang;

		$hours = floor($sec / 3600);
		$sec = $sec - $hours * 3600;
		$minutes = floor($sec / 60);
		$seconds = $sec % 60;

		if ($hours > 0) {
			if ($hours == 1) {
				$str = "1 " . $lang['PROJECT_CLASS']['HOUR'];
			} else if ($hours > 0) {
				$str = $hours . " " . $lang['PROJECT_CLASS']['HOURS'];
			}

			if ($minutes > 0 || $seconds > 0) {
				return $str . ", " . self::time($sec - 3600 * $hours, true);
			}

			return $str;
		} else {
			if ($minutes > 0) {
				if ($minutes == 1) {
					$str = "1 " . $lang['PROJECT_CLASS']['MINUTE'];
				} else {
					$str = $minutes . " " . $lang['PROJECT_CLASS']['MINUTES'];
				}

				if ($seconds == 1) {
					$str .= ", 1 " . $lang['PROJECT_CLASS']['SECOND'];
				} else if ($seconds > 0) {
					$str .= ", $seconds " . $lang['PROJECT_CLASS']['SECONDS'];
				}

				return $str;
			} else {
				if ($seconds == 1) {
					return "1 " . $lang['PROJECT_CLASS']['SECOND'];
				} else if ($seconds == 0 && $done !== true) {
					return "<i>" . $lang['PROJECT_CLASS']['NTY'] . "</i>";
				} else {
					return $seconds . " " . $lang['PROJECT_CLASS']['SECONDS'];
				}

			}
		}
	}

	public static function roundTime($minutes, $rounding = "exact") {
		$minutes = intval($minutes);

		if (substr($rounding, 0, 5) == "floor") {
			$base = intval(substr($rounding, 5));
			$minutes = floor($minutes / $base) * $base;
		} else if (substr($rounding, 0, 4) == "ceil") {
			$base = intval(substr($rounding, 4));
			$minutes = ceil($minutes / $base) * $base;
		}

		return $minutes;
	}
}