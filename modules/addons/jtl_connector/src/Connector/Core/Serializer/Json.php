<?php
/**
 * @copyright JTL-Software GmbH
 * @package jtl\Connector\Core\Serializer
 */

namespace jtl\Connector\Core\Serializer;

/**
 * Yaml Class
 *
 * @access public
 * @author Andreas Jütten <andy@jtl-software.de>
 */
class Json implements ISerializer
{
    public static function encode($object, $pretty = false)
    {
        if ($pretty) {
            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                return json_encode($object, JSON_PRETTY_PRINT);
            } else {
                return self::pretty(json_encode($object));
            }
        }

        return json_encode($object);
    }

    public static function decode($string, $assoc = false)
    {
        if (empty($string) || !is_string($string)) {
            throw new \InvalidArgumentException(sprintf('Invalid parameter "%s"', var_export($string, true)));
        }

        $object = json_decode($string, $assoc);
        $errno = json_last_error();

        if ($errno != JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf("Error while decoding json string: \r\ncode:%d \r\nstring: %s", $errno, $string));
        }

        return $object;
    }

    public static function pretty($json, $html = false)
    {
        $result = '';
        $level = 0;
        $prev_char = '';
        $in_quotes = false;
        $ends_line_level = null;
        $json_length = strlen($json);

        for ($i = 0; $i < $json_length; $i++) {
            $char = $json[$i];
            $new_line_level = null;
            $post = "";
            if ($ends_line_level !== null) {
                $new_line_level = $ends_line_level;
                $ends_line_level = null;
            }
            if ($char === '"' && $prev_char != '\\') {
                $in_quotes = !$in_quotes;
            } elseif (!$in_quotes) {
                switch ($char) {
                    case '}': case ']':
                        $level--;
                        $ends_line_level = null;
                        $new_line_level = $level;
                        break;
                    case '{': case '[':
                        $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;
                    case ':':
                        $post = " ";
                        break;
                    case " ": case "\t": case "\n": case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = null;
                        break;
                }
            }
            
            if ($new_line_level !== null) {
                $result .= "\n".str_repeat("    ", $new_line_level);
            }
            
            $result .= $char.$post;
            $prev_char = $char;
        }

        return $result;
    }
}
