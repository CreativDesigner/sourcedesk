<?php
// Telegram integration

class Telegram
{
    private static function linksToMarkdown($str)
    {
        return preg_replace('/\<a href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/', '<$1|$3>', $str);
    }

    private static function linksToSlack($str)
    {
        return preg_replace('/\<a href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/', '[$3]($1)', $str);
    }

    public static function sendMessage($msg)
    {
        global $CFG;

        if ($CFG['TELEGRAM_CHAT'] == "mattermost") {
            $data = [
                "text" => self::linksToMarkdown($msg),
                "username" => $CFG['PAGENAME'],
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $CFG['TELEGRAM_TOKEN']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_POST, 1);

            $res = curl_exec($ch);
            return $res == "ok";
        } else if ($CFG['TELEGRAM_CHAT'] == "slack") {
            $data = [
                "text" => self::linksToSlack($msg),
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $CFG['TELEGRAM_TOKEN']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_POST, 1);

            $res = curl_exec($ch);
            return boolval($res);
        } else if ($CFG['TELEGRAM_CHAT'] == "discord") {
            $data = [
                "content" => self::linksToSlack($msg),
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $CFG['TELEGRAM_TOKEN']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_POST, 1);

            $res = curl_exec($ch);
            return empty($res);
        } else {
            $msg = preg_replace('/\[(.*?)\]\((.*?)\)/', "<a href='$2'>$1</a>", $msg);
            $msg = html_entity_decode($msg);

            $data = [
                "chat_id" => $CFG['TELEGRAM_CHAT'],
                "text" => $msg,
                "parse_mode" => "html",
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $CFG['TELEGRAM_TOKEN'] . "/sendMessage");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_POST, 1);
            $res = json_decode(curl_exec($ch));
            if (curl_errno($ch)) {
                return false;
            }

            curl_close($ch);

            if ($res === false) {
                return false;
            }

            return (bool) $res->ok;
        }
    }
}
