<?php

class Teamspeak3Prov extends Provisioning
{
    protected $name = "TeamSpeak 3";
    protected $short = "teamspeak3";
    protected $lang;
    protected $options = array();
    protected $serverMgmt = true;
    protected $version = "1.1";

    public function Config($id, $product = true)
    {
        $this->loadOptions($id, $product);

        if (isset($_POST['host'])) {
            if ($_POST['_mgmt_server']) {
                $_POST = $this->serverData($_POST['_mgmt_server']);
            }

            require_once __DIR__ . "/ts3admin.class.php";

            $ts = new ts3admin($_POST['host'], 10011);

            if (!is_resource(@fsockopen($_POST['host'], 10011))) {
                die('<div class="alert alert-danger">' . $this->getLang("CONNFAIL") . '</div>');
            }

            $ts->connect();
            if (!$ts->login($_POST['api_username'], $_POST['api_password'])['success']) {
                die('<div class="alert alert-danger">' . $this->getLang("LOGFAIL") . '</div>');
            }

            die('<div class="alert alert-success">' . $this->getLang("CONNOK") . '</div>');
        }

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row">
			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("hostname");?></label>
					<input type="text" data-setting="host" value="<?=$this->getOption("host");?>" placeholder="ts.sourceway.de" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("QUERYUSER");?></label>
					<input type="text" data-setting="api_username" value="<?=$this->getOption("api_username");?>" placeholder="serveradmin" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-4" mgmt="1">
				<div class="form-group">
					<label><?=$this->getLang("QUERYPW");?></label>
					<input type="password" data-setting="api_password" value="<?=$this->getOption("api_password");?>" placeholder="5xyypj1lymnyvydfk14aqekhaiklebng" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("slots");?></label>
					<input type="text" data-setting="slots" value="<?=$this->getOption("slots");?>" placeholder="<?=$this->getLang("AFN");?>" class="form-control prov_settings" />
				</div>
			</div>

            <div class="col-md-6" mgmt="0">
				<div class="form-group">
					<label><?=$this->getLang("server");?></label>
					<input type="text" data-setting="server" value="<?=$this->getOption("server");?>" placeholder="<?=$this->getLang("AFN");?>" class="form-control prov_settings" />
				</div>
			</div>
		</div>

		<a href="#" id="check_conn" mgmt="0" class="btn btn-default btn-block"><?=$this->getLang("CHECKCONN");?></a>

		<script>
        if (!mgmt) {
		var doing = false;
		function request(){
			if(doing) return;
			doing = !doing;
			$("#check_conn").html('<i class="fa fa-spin fa-spinner"></i> <?=$this->getLang("CHECKINGCONN");?>');
			$.post("?p=<?php if ($product) {?>product_<?php }?>hosting&id=<?=$_GET['id'];?>&module=teamspeak3", {
				host: $("[data-setting=host]").val(),
				api_username: $("[data-setting=api_username]").val(),
				api_password: $("[data-setting=api_password]").val(),
                _mgmt_server: $("[data-setting=_mgmt_server]").val(),
				"csrf_token": "<?=CSRF::raw();?>"
			}, function(r){
				doing = false;
				$("#check_conn").html('<?=$this->getLang("CHECKCONN");?>');
				$("#server_conf").html(r);
			});
		}
		<?php if (!empty($this->getOption("host"))) {
            echo 'request();';
        }
        ?>

		$("#check_conn").click(function(e){
			e.preventDefault();
			request();
		});
        }
		</script>

		<br /><div id="server_conf"></div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        return array(true, ["servers" => serialize([])]);
    }

    public function Delete($id)
    {
        $this->loadOptions($id);

        try {
            require_once __DIR__ . "/ts3admin.class.php";
            $ts = new ts3admin($this->getOption('host'), 10011);
            if (!is_resource(@fsockopen($this->getOption('host'), 10011))) {
                throw new Exception("An connection error occured.");
            }

            $ts->connect();
            if (!$ts->login($this->getOption('api_username'), $this->getOption('api_password'))['success']) {
                throw new Exception("An technical error occured.");
            }

        } catch (Exception $ex) {
            return array(false, $ex->getMessage());
        }

        $servers = unserialize($this->getData("servers"));
        foreach ($servers as $server) {
            $port = $server[0];

            if (!$ts->selectServer($port)['success']) {
                $l = $ts->serverList(["onlyoffline" => "1"])['data'];
                foreach ($l as $se) {
                    if ($se["virtualserver_port"] != $port) {
                        continue;
                    }

                    $se['virtualserver_status'] = "offline";
                    $r = $se;
                    break;
                }
                if (empty($r['virtualserver_status'])) {
                    continue;
                }

            } else {
                $r = $ts->serverInfo()['data'];
                $ts->serverStop($r['virtualserver_id']);
            }

            $ts->serverDelete($r['virtualserver_id']);
        }

        $this->setData("servers", serialize([]));

        return array(true);
    }

    public function Suspend($id)
    {
        $this->loadOptions($id);

        try {
            require_once __DIR__ . "/ts3admin.class.php";
            $ts = new ts3admin($this->getOption('host'), 10011);
            if (!is_resource(@fsockopen($this->getOption('host'), 10011))) {
                throw new Exception("An connection error occured");
            }

            $ts->connect();
            if (!$ts->login($this->getOption('api_username'), $this->getOption('api_password'))['success']) {
                throw new Exception("A technical error occured.");
            }

        } catch (Exception $ex) {
            return array(false, $ex->getMessage());
        }

        $servers = unserialize($this->getData("servers"));
        foreach ($servers as &$server) {
            $port = $server[0];

            if (!$ts->selectServer($port)['success']) {
                $l = $ts->serverList(["onlyoffline" => "1"])['data'];
                foreach ($l as $se) {
                    if ($se["virtualserver_port"] != $port) {
                        continue;
                    }

                    $se['virtualserver_status'] = "offline";
                    $r = $se;
                    break;
                }
                if (empty($r['virtualserver_status'])) {
                    continue;
                }

                $ts->serverStart($r['virtualserver_id']);
                $ts->selectServer($port);
            } else {
                $r = $ts->serverInfo()['data'];
            }

            $ts->serverEdit([
                "VIRTUALSERVER_MAXCLIENTS" => "1",
                "VIRTUALSERVER_AUTOSTART" => "0",
            ]);
            $ts->serverStop($r['virtualserver_id']);

            $server[1] = 1;
        }

        $this->setData("servers", serialize($server));

        return array(true);
    }

    public function Unsuspend($id)
    {
        $this->loadOptions($id);
        return array(true);
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        $slots = $this->getOption("slots");
        if ((!is_numeric($slots) || $slots <= 0) && isset($this->cf[$slots])) {
            $slots = $this->cf[$slots];
        }

        $server = $this->getOption("server");
        if ((!is_numeric($server) || $server <= 0) && isset($this->cf[$server])) {
            $server = $this->cf[$server];
        }

        $used = 0;
        $usedServer = 0;

        $servers = unserialize($this->getData("servers"));
        if (!is_array($servers)) {
            $servers = [];
        }

        foreach ($servers as $s) {
            $used += $s[1];
            $usedServer++;
        }

        if (isset($_POST['list'])) {
            try {
                require_once __DIR__ . "/ts3admin.class.php";
                $ts = new ts3admin($this->getOption('host'), 10011);
                if (!is_resource(@fsockopen($this->getOption('host'), 10011))) {
                    throw new Exception("An connection error occured");
                }

                $ts->connect();
                if (!$ts->login($this->getOption('api_username'), $this->getOption('api_password'))['success']) {
                    throw new Exception("A technical error occured.");
                }

            } catch (Exception $ex) {
                die('<div class="alert alert-danger" style="margin-bottom: 0;">' . $ex->getMessage() . '</div>');
            }

            $ip = gethostbyname($this->getOption("host"));
            ?>
			<div class="table-responsive">
				<table class="table table-bordered table-striped" style="margin-bottom: 0;">
					<tr>
						<th><?=$this->getLang("name2");?></th>
						<th><?=$this->getLang("ip");?></th>
						<th><?=$this->getLang("port");?></th>
						<th><?=$this->getLang("status");?></th>
						<th width="32px"></th>
					</tr>

					<?php foreach ($servers as $i => $s) {
                $r = $ts->selectServer($s[0]);
                if (substr($r['errors'][0], 0, 13) == "ErrorID: 1033") {
                    $l = $ts->serverList(["onlyoffline" => "1"])['data'];

                    foreach ($l as $se) {
                        if ($se["virtualserver_port"] != $s[0]) {
                            continue;
                        }

                        $se['virtualserver_status'] = "offline";
                        $r = $se;
                        break;
                    }

                    if (empty($r['virtualserver_status'])) {
                        continue;
                    }

                } else {
                    $r = $ts->serverInfo()['data'];
                }
                ?>
					<tr>
						<td><?=htmlentities($r['virtualserver_name']);?></td>
						<td><?=$ip;?></td>
						<td><?=$s[0];?></td>
						<td><span data-lid="<?=$s[0];?>" class="label label-<?=$r['virtualserver_status'] == "online" ? 'success' : 'warning';?>"><?=$r['virtualserver_status'] == "online" ? $this->getLang("ONLINE") : $this->getLang("OFFLINE");?></span></td>
						<td><center><a href="#" class="loadServer" data-id="<?=$s[0];?>"<?=$r['virtualserver_status'] == "online" ? '' : ' style="display: none;"';?>><i class="fa fa-arrow-right"></i></a><?php if ($r['virtualserver_status'] != "online") {?><a href="#" class="startServer" data-sid="<?=$s[0];?>"><i class="fa fa-play"></i></a><?php }?></center></td>
					</tr>
					<?php }?>
				</table>
			</div>
			<?php
exit;
        }

        if (isset($_POST['id']) && $_POST['id'] == "-1") {
            ?>
			<div class="progress">
				<div class="progress-bar" role="progressbar" aria-valuenow="<?=$used;?>" aria-valuemin="0" aria-valuemax="<?=$slots;?>" style="width: <?=$used == 0 ? 0 : $used / $slots * 100;?>%; min-width: 15em;">
					<?=$used;?> / <?=$slots;?> <?=$this->getLang("SLOTSUSED");?>
				</div>
			</div>

            <div class="progress">
				<div class="progress-bar" role="progressbar" aria-valuenow="<?=$usedServer;?>" aria-valuemin="0" aria-valuemax="<?=$server;?>" style="width: <?=$used == 0 ? 0 : $used / $server * 100;?>%; min-width: 15em;">
					<?=$usedServer;?> / <?=$server;?> <?=$this->getLang("SERVERUSED");?>
				</div>
			</div>

			<?php if ($slots - $used < 4) {?>
			<div class="alert alert-danger" style="margin-bottom: 0;"><?=$this->getLang("NEEDSLOTS");?></div>
            <?php } else if ($usedServer >= $server) {?>
            <div class="alert alert-danger" style="margin-bottom: 0;"><?=$this->getLang("needserver");?></div>
			<?php } else {?>
			<form method="POST">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label><?=$this->getLang("SNAME");?></label>
							<input type="text" name="name" placeholder="<?=$this->getLang("SNAMEP");?>" required="required" class="form-control" maxlength="50">
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label><?=$this->getLang("SLOTS");?></label>
							<input type="number" name="slots" min="4" max="<?=$slots - $used;?>" value="4" class="form-control" required="required">
						</div>
					</div>
				</div>

				<?=CSRF::html();?>
				<input type="submit" class="btn btn-primary btn-block" value="<?=$this->getLang("CREATESERVER");?>">
			</form>
			<?php
}
            exit;
        } else if (isset($_POST['id']) && $_POST['id'] > "0") {
            try {
                $found = 0;
                foreach ($servers as $s) {
                    if ($s[0] == $_POST['id']) {
                        $found = 1;
                        break;
                    }
                }

                if (!$found) {
                    throw new Exception($this->getLang("NORIGHT"));
                }

                require_once __DIR__ . "/ts3admin.class.php";
                $ts = new ts3admin($this->getOption('host'), 10011);
                if (!is_resource(@fsockopen($this->getOption('host'), 10011))) {
                    throw new Exception("An connection error occured");
                }

                $ts->connect();
                if (!$ts->login($this->getOption('api_username'), $this->getOption('api_password'))['success']) {
                    throw new Exception("A technical error occured.");
                }

                if (isset($_POST['start'])) {
                    $l = $ts->serverList(["onlyoffline" => "1"])['data'];

                    foreach ($l as $se) {
                        if ($se["virtualserver_port"] != $_POST['id']) {
                            continue;
                        }

                        $se['virtualserver_status'] = "offline";
                        $r = $se;
                        break;
                    }

                    if (empty($r['virtualserver_status'])) {
                        exit;
                    }

                    $ts->serverStart($r['virtualserver_id']);

                    exit;
                }

                if (!$ts->selectServer($_POST['id'])['success']) {
                    throw new Exception($this->getLang("SERVEROFF"));
                }

                $r = $ts->serverInfo()['data'];
            } catch (Exception $ex) {
                die('<div class="alert alert-danger" style="margin-bottom: 0;">' . $ex->getMessage() . '</div>');
            }

            if (isset($_POST['token'])) {
                $g = $ts->serverGroupList()['data'];
                $gid = 0;
                foreach ($g as $i) {
                    if ($i['name'] == "Server Admin") {
                        $gid = $i['sgid'];
                    }
                }

                die($ts->privilegekeyAdd("0", $gid, "0")['data']['token']);
            }

            if (isset($_POST['password'])) {
                die($ts->serverEdit(["VIRTUALSERVER_PASSWORD" => ""])['success'] ? $this->getLang("UNSET") : '<font color="red">' . $this->getLang("ERR") . '</font>');
            }

            if (isset($_POST['delete'])) {
                $ts->serverStop($r['virtualserver_id']);

                if ($ts->serverDelete($r['virtualserver_id'])['success']) {
                    foreach ($servers as $k => $v) {
                        if ($v[0] == $_POST['id']) {
                            unset($servers[$k]);
                        }
                    }

                    $this->setData("servers", serialize($servers));
                }

                exit;
            }

            if (isset($_POST['maxclients'])) {
                $_POST['maxclients'] = max(4, intval($_POST['maxclients']));
                $_POST['maxclients'] = min($_POST['maxclients'], $slots - $used + $r['virtualserver_maxclients']);

                $ts->serverEdit([
                    "VIRTUALSERVER_MAXCLIENTS" => $_POST['maxclients'],
                    "VIRTUALSERVER_AUTOSTART" => $_POST['autostart'] ? "1" : "0",
                ]);

                if ($_POST['status'] == "offline") {
                    $ts->serverStop($r['virtualserver_id']);
                } else {
                    $ts->serverStart($r['virtualserver_id']);
                }

                foreach ($servers as &$v) {
                    if ($v[0] == $_POST['id']) {
                        $v[1] = $_POST['maxclients'];
                    }
                }

                $this->setData("servers", serialize($servers));

                exit;
            }

            ?>
			<form method="POST">
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("SLOTS");?></label>
						<input type="number" required="required" min="4" max="<?=$slots - $used + $r['virtualserver_maxclients'];?>" name="maxclients" class="form-control" value="<?=htmlentities($r['virtualserver_maxclients']);?>">
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("autoboot");?></label>
						<select name="autostart" class="form-control">
							<option value="0" style="color: red;"><?=$this->getLang("INACT");?></option>
							<option value="1" style="color: green;"<?=$r['virtualserver_autostart'] == 1 ? ' selected=""' : '';?>><?=$this->getLang("ACT");?></option>
						</select>
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						<label><?=$this->getLang("status");?></label>
						<select name="status" class="form-control">
							<option value="offline" style="color: red;"><?=$this->getLang("offline");?></option>
							<option value="online" style="color: green;"<?=$r['virtualserver_status'] == "online" ? ' selected=""' : '';?>><?=$this->getLang("online");?></option>
						</select>
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("TOKEN");?></label><br />
						<span class="control-label">
							<a href="#" id="generate_token"><?=$this->getLang("generate");?></a>
						</span>
					</div>
				</div>

				<div class="col-md-6">
					<div class="form-group">
						<label><?=$this->getLang("password");?></label><br />
						<span class="control-label">
							<?php if (empty($r['virtualserver_password'])) {echo $this->getLang("unset");} else {?>
							<a href="#" id="delete_password"><?=$this->getLang("delete");?></a>
							<?php }?>
						</span>
					</div>
				</div>
			</div>

			<div class="row">
				<?=CSRF::html();?>
				<div class="col-md-6">
					<input type="submit" class="btn btn-primary btn-block" id="save" value="<?=$this->getLang("save");?>">
				</div>

				<div class="col-md-6">
					<a href="#" id="delete_server" class="btn btn-default btn-block"><?=$this->getLang("del");?></a>
				</div>
			</div>
			</form>

			<script>
			$("#save").click(function(e){
				e.preventDefault();

				$(this).val("<?=$this->getLang("OMP");?>");

				$.post("", {
					"rawts3": "1",
					"id": "<?=$_POST['id'];?>",
					"maxclients": $("[name=maxclients]").val(),
					"autostart": $("[name=autostart]").val(),
					"status": $("[name=status]").val(),
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r){
					location.reload();
				});
			});

			$("#delete_server").click(function(e){
				e.preventDefault();

				if(!confirm("<?=$this->getLang("READEL");?>"))
					return;

				$(this).html("<i class='fa fa-spinner fa-spin' style='color: black;'></i><?=$this->getLang("OMP");?>");

				$.post("", {
					"rawts3": "1",
					"id": "<?=$_POST['id'];?>",
					"delete": "1",
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r){
					location.reload();
				});
			});

			$("#generate_token").click(function(e){
				e.preventDefault();

				var p = $(this).parent();
				p.html("<i class='fa fa-spinner fa-spin'></i><?=$this->getLang("OMP");?>");

				$.post("", {
					"rawts3": "1",
					"id": "<?=$_POST['id'];?>",
					"token": "1",
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r){
					p.html(r);
				});
			});

			$("#delete_password").click(function(e){
				e.preventDefault();

				var p = $(this).parent();
				p.html("<i class='fa fa-spinner fa-spin'></i><?=$this->getLang("OMP");?>");

				$.post("", {
					"rawts3": "1",
					"id": "<?=$_POST['id'];?>",
					"password": "1",
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r){
					p.html(r);
				});
			});
			</script>
			<?php

            exit;
        }

        ob_start();
        if (!isset($_POST['rawts3'])) {
            ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("TSS");?><span class="pull-right"><a href="#" class="loadServer" id="create" data-id="-1"><i class="fa fa-plus"></i></a><a href="#" class="loadServer" id="back" data-id="0" style="display: none;"><i class="fa fa-mail-reply"></i></a></span></div>
			<div class="panel-body" id="content">
			<?php }?>
				<?php if (isset($_POST['name']) && isset($_POST['slots'])) {
            try {
                if (empty($_POST['name'])) {
                    throw new Exception($this->getLang("err1"));
                }

                if (empty($_POST['slots']) || !is_numeric($_POST['slots']) || $_POST['slots'] < 4) {
                    throw new Exception($this->getLang("err2"));
                }

                if ($_POST['slots'] > $slots - $used) {
                    throw new Exception($this->getLang("err3"));
                }

                if ($usedServer + 1 > $server) {
                    throw new Exception($this->getLang("err4"));
                }

                require_once __DIR__ . "/ts3admin.class.php";
                $ts = new ts3admin($this->getOption('host'), 10011);
                if (!is_resource(@fsockopen($this->getOption('host'), 10011))) {
                    throw new Exception("An connection error occured");
                }

                $ts->connect();
                if (!$ts->login($this->getOption('api_username'), $this->getOption('api_password'))['success']) {
                    throw new Exception("A technical error occured.");
                }

                $r = $ts->serverCreate([
                    "VIRTUALSERVER_NAME" => substr(trim($_POST['name']), 0, 50),
                    "VIRTUALSERVER_MAXCLIENTS" => $_POST['slots'],
                ]);

                if (!$r['success']) {
                    throw new Exception($this->getLang("err5"));
                }

                $port = $r['data']['virtualserver_port'];
                $used += $_POST['slots'];

                $servers[] = [$port, $_POST['slots']];
                $this->setData("servers", serialize($servers));

                $_SESSION['ts3_client_html'] = '<div class="alert alert-success">' . $this->getLang("creasuc") . '</div>';
            } catch (Exception $ex) {
                $_SESSION['ts3_client_html'] = '<div class="alert alert-danger">' . $ex->getMessage() . '</div>';
            }

            header('Refresh: 0');
            exit;
        }

        if (!empty($_SESSION['ts3_client_html'])) {
            echo $_SESSION['ts3_client_html'];
            $_SESSION['ts3_client_html'] = "";
        }
        ?>
				<div class="progress">
					<div class="progress-bar" role="progressbar" aria-valuenow="<?=$used;?>" aria-valuemin="0" aria-valuemax="<?=$slots;?>" style="width: <?=$used == 0 ? 0 : $used / $slots * 100;?>%; min-width: 15em;">
						<?=$used;?> / <?=$slots;?> <?=$this->getLang("slotsused");?>
					</div>
				</div>

                <div class="progress">
                    <div class="progress-bar" role="progressbar" aria-valuenow="<?=$usedServer;?>" aria-valuemin="0" aria-valuemax="<?=$server;?>" style="width: <?=$used == 0 ? 0 : $used / $server * 100;?>%; min-width: 15em;">
                        <?=$usedServer;?> / <?=$server;?> <?=$this->getLang("serverused");?>
                    </div>
                </div>

				<?php if (count($servers) == 0) {?>
				<?=$this->getLang("noserver");?>
				<?php } else {?>
				<div id="list">
					<i class="fa fa-spinner fa-spin"></i> <?=$this->getLang("OMP");?>
				</div>

				<script>
				$(document).ready(function(){
					$.post("", {
						"list": "1",
						"csrf_token": "<?=CSRF::raw();?>",
					}, function(r){
						$("#list").html(r);
						bindTS3();
					});
				});
				</script>
			<?php }if (!isset($_POST['rawts3'])) {?>
			</div>
		</div>
		<?php
} else {
            exit;
        }
        ?>

		<script>
		function bindTS3(){
			$(".startServer").unbind("click").click(function(e){
				e.preventDefault();

				var id = $(this).data("sid");
				var t = $(this).find("i").removeClass("fa-play").addClass("fa-spinner fa-spin");

				$.post("", {
					"rawts3": "1",
					"id": id,
					"start": "1",
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r){
					t.remove();
					$("[data-id=" + id + "]").show();
					$("[data-lid=" + id + "]").removeClass("label-warning").addClass("label-success").html("<?=$this->getLang("ONLINE");?>");
				});
			});

			$(".loadServer").unbind("click").click(function(e){
				e.preventDefault();

				var id = $(this).data("id");
				$(this).find("i").removeClass("fa-mail-reply fa-arrow-right").addClass("fa-spinner fa-spin");

				$.post("", {
					"rawts3": "1",
					"id": id,
					"csrf_token": "<?=CSRF::raw();?>",
				}, function(r){
					$("#content").html(r);

					if(id == 0){
						$("#back").hide();
						$("#create").show();
					} else {
						$("#back").show();
						$("#create").hide();
					}

					$("#back").find("i").removeClass("fa-spinner fa-spin").addClass("fa-mail-reply");
					$("#create").find("i").removeClass("fa-spinner fa-spin").addClass("fa-plus");

					bindTS3();
				});
			});
		}

		$(document).ready(function(){
			bindTS3();
		});
		</script>
		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function AllEmailVariables()
    {
        return array(
            "url",
        );
    }

    public function EmailVariables($id)
    {
        global $raw_cfg;
        $this->loadOptions($id);

        return array(
            "url" => $raw_cfg['PAGEURL'] . "/hosting/" . $id,
        );
    }
}
