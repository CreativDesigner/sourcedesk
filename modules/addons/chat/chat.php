<?php
// Addon for staff  chat

class ChatAddon extends Addon
{
    public static $shortName = "chat";
    private $clients = [];

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

    public function activate()
    {
        global $CFG, $db;
        parent::activate();

        $db->query("ALTER TABLE `admins` ADD `chat_ws` VARCHAR(255) NOT NULL DEFAULT '';");
        $db->query("CREATE TABLE `admin_chat` ( `ID` INT(11) NOT NULL AUTO_INCREMENT , `sender` INT(11) NOT NULL DEFAULT '0' , `recipient` INT(11) NOT NULL DEFAULT '0' , `time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' , `message` LONGTEXT NOT NULL , `read` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' , PRIMARY KEY (`ID`))");
    }

    public function getSettings()
    {
        return array();
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function hooks()
    {
        return [
            ["Websocket", "registerSocket", 0],
            ["AdminAreaAdditionalJS", "adminJs", 0],
            ["AdminAreaFooter", "adminFooter", 0],
            ["AdminAreaTopMenu", "topMenu", 0],
        ];
    }
    
    public function registerSocket($pars) {
        $server = $pars["server"];
        $server->registerApplication('chat', $this);
    }

    public function adminFooter() {
        return '<input type="hidden" id="chat_user">
            <div id="chat_window" style="display: none; position: fixed; bottom: 0px; right: 40px; height: 400px; max-height: 90%; width: 500px; max-width: 90%; z-index: 10000;">
                <div class="panel panel-primary" style="height: 100%; border-bottom: none; border-radius: 4px 4px 0 0;">
                    <div class="panel-heading"><span id="chat_name"></span><a href="#" id="close_chat_window" class="pull-right" style="color: white;"><i class="fa fa-times"></i></a></div>
                    <div class="panel-body" id="chat_msg" style="overflow: scroll; height: 78%; padding-bottom: 30px;">
                    </div>
                    <div class="panel-footer" style="border: none; background-color: white; padding: 0; border-radius: 0; position: absolute; bottom: 0; width: 99.5%;">
                        <div style="width: 100%; padding-left: 20px; padding-right: 5px; padding-bottom: 5px; padding-top: 3px; border-top: 1px solid #ddd;"><form id="chat_answer_form" style="margin: 0; padding: 0;"><input type="text" id="chat_answer" class="form-control" style="margin-left: -16px; border: none; box-shadow: none; -webkit-box-shadow: none;" placeholder="' . $this->getLang("ANSWER") . '"></form></div>                        
                    </div>
                </div>
            </div>';
    }

    public function topMenu($pars) {
        global $db, $CFG, $adminInfo;

        $code = '<li id="ws_chat" class="dropdown" style="display: none;"><a href="#" data-toggle="dropdown" id="chat_btn"><i class="fa fa-comment"></i> <span id="ws_chat_badge" class="badge">0</span></a><ul class="dropdown-menu dropdown-tasks">';

        $sql = $db->query("SELECT ID, name, online, avatar FROM admins WHERE ID != {$adminInfo->ID} ORDER BY name ASC");
        
        if (!$sql->num_rows) {
            $code .= "<li><a href='#'><div><p><center>" . $this->getLang("NOSTAFF") . "</center></p></div></a></li>";
        } else {
            for ($i = 0; $row = $sql->fetch_object(); $i++) {
                if (!empty($row->avatar) && file_exists(__DIR__ . "/../../../files/avatars/" . basename($row->avatar))) {
                    $avatar = htmlentities(basename($row->avatar));
                } else {
                    $avatar = "none.png";
                }

                $avatar = '<img src="../files/avatars/' . $avatar . '" title="' . htmlentities($row->name) . '" alt="' . htmlentities($row->name) . '" style="border-radius: 50%; height: 18px; width: 18px; margin-top: -2px;" />';

                $status = '<span class="badge" id="chat_unread_' . $row->ID . '">0</span>';

                $code .= "<li><a href='#' data-chat-open='{$row->ID}'><div><p style='padding: 5px; margin: 0;'>$avatar &nbsp;<span class='chat_name'>" . htmlentities($row->name) . "</span><span class='pull-right'>$status</span></p></div></a></li>";

                if ($i + 1 < $sql->num_rows) {
                    $code .= '<li class="divider"></li>';
                }
            }
        }

        return $code . '</ul></li>';
    }

    public function adminJs($pars) {
        global $CFG;

        if (!$CFG['WEBSOCKET_ACTIVE']) {
            return;
        }
        
        $port = $CFG['WEBSOCKET_PORT'];
        if (empty($port) || !is_numeric($port) || $port > 65536 || $port < 20) {
            return;
        }

        return "
            function wsChatGetCookie(cname) {
                var name = cname + \"=\";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(';');
                for(var i = 0; i <ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
                }
                return \"\";
            }

            function openChatFor(id) {
                $('#chat_user').val(id);
                $('#chat_window').show();
                $('#chat_name').html($('[data-chat-open=' + id + ']').find('.chat_name').html());
                $('#chat_msg').html('<p style=\"text-align: center;\"><br /><br /><i class=\"fa fa-spinner fa-spin\" style=\"font-size: 40px;\"></i></p>');
               
                chatSocket.send(JSON.stringify({
                    \"type\": \"history\",
                    \"partner\": id,
                }));

                document.cookie = 'ws_chat_partner=' + id;
            }

            $('[data-chat-open]').click(function(e) {
                e.preventDefault();
                openChatFor($(this).data('chat-open'));
            });

            $(document).ready(function() {
                $('#close_chat_window').click(function(e) {
                    e.preventDefault();
                    $('#chat_window').hide();
                    $('#chat_user').val('0');
                    document.cookie = 'ws_chat_partner=0';
                });

                $('#chat_answer_form').submit(function(e) {
                    e.preventDefault();
                    
                    chatSocket.send(JSON.stringify({
                        \"type\": \"msg\",
                        \"rec\": $('#chat_user').val(),
                        \"msg\": $('#chat_answer').val(),
                    }));

                    $('#chat_answer').val(\"\");
                });
            });

            chatSocket = new WebSocket('ws" . ($_SERVER['HTTPS'] ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . ":$port/chat');
            chatSocket.onmessage = function(msg) {
                $('#ws_chat').show();

                var data = JSON.parse(msg.data);
                
                if ('count' in data) {
                    $('#ws_chat_badge').html(data.count.toString());

                    if (data.count > 0) {
                        $('#ws_chat_badge').css('background-color', '#f0ad4e');
                    } else {
                        $('#ws_chat_badge').css('background-color', '#777');
                    }
                }

                if ('userCount' in data) {
                    for (let id in data.userCount) {
                        $('#chat_unread_' + id).html(data.userCount[id].toString());

                        if (data.userCount[id] > 0) {
                            $('#chat_unread_' + id).css('background-color', '#f0ad4e');
                        } else {
                            $('#chat_unread_' + id).css('background-color', '#777');
                        }
                    }
                }

                if ('push' in data) {
                    var n = new Notification(data.push.sender, {
                        'body': data.push.body
                    });
                }

                if ('history' in data) {
                    if (data.history.partner == $('#chat_user').val()) {
                        $('#chat_msg').html(data.history.html);
                        $('#chat_msg').animate({ scrollTop: $('#chat_msg').prop('scrollHeight') }, 'slow');

                        chatSocket.send(JSON.stringify({
                            \"type\": \"read\",
                            \"partner\": $('#chat_user').val(),
                        }));
                    }
                }
            }

            chatSocket.onopen = function () {
                chatSocket.send('Ping');

                var cookie = wsChatGetCookie('ws_chat_partner');
                if (cookie && cookie > 0) {
                    openChatFor(cookie);
                }
            };
        ";
    }

    private function getHistory($adminInfo, $partner) {
        global $db, $CFG;

        $html = "";

        $sql = $db->query("SELECT * FROM admin_chat WHERE (sender = {$adminInfo->ID} AND recipient = $partner) OR (recipient = {$adminInfo->ID} AND sender = $partner) ORDER BY time ASC, ID ASC LIMIT 25");
        if (!$sql->num_rows) {
            $html = $this->getLang("NOMSG");
        } else {
            $color = "#428bca";
            if (preg_match('/^#[a-f0-9]{6}$/i', $CFG['ADMIN_COLOR'])) {
                $color = $CFG['ADMIN_COLOR'];
            } elseif( preg_match('/^[a-f0-9]{6}$/i', $CFG['ADMIN_COLOR'])) {
                $color = '#' . $CFG['ADMIN_COLOR'];
            }

            while ($row = $sql->fetch_object()) {
                if ($row->sender == $adminInfo->ID) {
                    $html .= '<div style="width: 90%; float: right; background-color: #ededed; padding: 10px; border-radius: 5px; margin-bottom: 5px;">' . htmlentities($row->message) . '</div>';
                } else {
                    $html .= '<div style="width: 90%; float: left; color: white; background-color: ' . $color . '; padding: 10px; border-radius: 5px; margin-bottom: 5px;">' . htmlentities($row->message) . '</div>';
                }
            }
        }

        return $html;
    }

    public function onData($data, $client) {
        global $db, $CFG;

        $clientId = $client->getId();
        $headers = $client->getHeaders();

        if (!is_array($headers) || !array_key_exists("Cookie", $headers)) {
            return;
        }

        $cookieStr = $headers["Cookie"];
        $cookies = [];

        $ex = explode(";", $cookieStr);
        foreach ($ex as $c) {
            $ex2 = explode("=", $c, 2);
            if (count($ex2) < 2) {
                continue;
            }

            $cookies[trim($ex2[0])] = trim($ex2[1]);
        }

        $sid = $db->real_escape_string(substr(hash("sha512", $cookies["PHPSESSID"]), 0, 64));
        $sql = $db->query("SELECT ID, name FROM admins WHERE last_sid = '$sid'");
        if ($sql->num_rows != 1) {
            return;
        }
        
        $adminInfo = $sql->fetch_object();
        $adminId = $adminInfo->ID;
        $clientId = $db->real_escape_string($clientId);

        $this->clients[$clientId] = $client;

        $db->query("UPDATE admins SET chat_ws = '$clientId' WHERE ID = $adminId");

        if ($data == "Ping") {
            $res = [
                "count" => 0,
                "userCount" => [],
            ];
    
            $sql = $db->query("SELECT COUNT(*) c, sender s FROM admin_chat WHERE recipient = {$adminInfo->ID} AND `read` = '0000-00-00 00:00:00' GROUP BY sender");
            while ($row = $sql->fetch_object()) {
                $res["count"] += $row->c;
                $res["userCount"][$row->s] = $row->c;
            }

            $client->send(json_encode($res));
            return;
        } else {
            $data = @json_decode($data, true);
            if (!$data || empty($data['type'])) {
                return;
            }

            switch ($data['type']) {
                case "history":
                    $partner = intval($data['partner'] ?? 0);

                    $client->send(json_encode([
                        "history" => [
                            "partner" => $partner,
                            "html" => $this->getHistory($adminInfo, $partner),
                        ],
                    ]));

                    $db->query("UPDATE admin_chat SET `read` = '" . date("Y-m-d H:i:s") . "' WHERE recipient = {$adminInfo->ID} AND sender = $partner AND `read` = '0000-00-00 00:00:00'");
                    break;

                case "read":
                    $partner = intval($data['partner'] ?? 0);

                    $db->query("UPDATE admin_chat SET `read` = '" . date("Y-m-d H:i:s") . "' WHERE recipient = {$adminInfo->ID} AND sender = $partner AND `read` = '0000-00-00 00:00:00'");
                    break;

                case "msg":
                    $rec = intval($data['rec'] ?? 0);
                    $sql = $db->query("SELECT chat_ws FROM admins WHERE ID = $rec LIMIT 1");
                    if (!$sql->num_rows) {
                        return;
                    }

                    $time = date("Y-m-d H:i:s");
                    $msg = $db->real_escape_string($data['msg'] ?? "");

                    if (empty($msg)) {
                        return;
                    }

                    $db->query("INSERT INTO admin_chat (sender, recipient, time, message) VALUES ({$adminInfo->ID}, $rec, '$time', '$msg')");

                    $cid = $sql->fetch_object()->chat_ws;
                    if (!array_key_exists($cid, $this->clients)) {
                        return;
                    }

                    $res = [
                        "count" => 0,
                        "userCount" => [],
                    ];
            
                    $sql = $db->query("SELECT COUNT(*) c, sender s FROM admin_chat WHERE recipient = {$rec} AND `read` = '0000-00-00 00:00:00' GROUP BY sender");
                    while ($row = $sql->fetch_object()) {
                        $res["count"] += $row->c;
                        $res["userCount"][$row->s] = $row->c;
                    }

                    $client->send(json_encode([
                        "history" => [
                            "partner" => $rec,
                            "html" => $this->getHistory($adminInfo, $rec),
                        ],
                    ]));
                    
                    $this->clients[$cid]->send(json_encode(array_merge($res, [
                        "history" => [
                            "partner" => $adminInfo->ID,
                            "html" => $this->getHistory($db->query("SELECT * FROM admins WHERE ID = $rec")->fetch_object(), $adminInfo->ID),
                        ],
                        "push" => [
                            "sender" => $adminInfo->name,
                            "body" => $data['msg'],
                        ],
                    ])));
                    break;
            }
        }
	}
}
