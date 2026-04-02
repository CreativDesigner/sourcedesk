<?php
// Class for posting to Social Media channels

use DG\Twitter\Twitter;
use DG\Twitter\TwitterException;

class SocialMedia
{
    /**
     * Post to Facebook
     */
    public static function facebookPost($post)
    {
        global $CFG;

        $data = [
            "message" => $post,
            "access_token" => $CFG['SM_FB_KEY'],
        ];

        $ch = curl_init('https://graph.facebook.com/' . $CFG['SM_FB_PAGE_ID'] . '/feed');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @$return = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return !empty($return["id"]);
    }

    /**
     * Post to Twitter
     */
    public static function twitterPost($post)
    {
        global $CFG;

        $twitter = new Twitter($CFG['SM_TWITTER_CK'], $CFG['SM_TWITTER_CS'], $CFG['SM_TWITTER_AT'], $CFG['SM_TWITTER_ATS']);

        try {
            $twitter->send($post);
            return true;
        } catch (TwitterException $ex) {
            return false;
        }
    }
}
