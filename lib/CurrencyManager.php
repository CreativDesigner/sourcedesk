<?php

// Class for managing different currencies

class CurrencyManager
{
    // Defines base currency
    private $base_currency = null;

    // Constructur gets information from database
    public function __construct()
    {
        // Global database connection for security reasons
        global $db, $CFG;

        $sql = $db->query("SELECT currency_code FROM currencies WHERE base = 1 AND conversion_rate = 1 LIMIT 1");
        if ($sql->num_rows == 1) {
            $this->base_currency = $sql->fetch_object()->currency_code;
        }

    }

    // Get base currency code
    public function getCurrent()
    {
        global $var;

        if (!isset($var['myCurrency'])) {
            return new Currency($this->base_currency);
        }

        return new Currency($var['myCurrency']);
    }

    // Get current currency object
    public function getPrefix($currency = null)
    {
        if ($currency === null) {
            if ($this->base_currency === null) {
                return false;
            }

            $currency = $this->base_currency;
        }

        $curObject = new Currency($currency);
        return $curObject->getPrefix();
    }

    // Get prefix
    public function getSuffix($currency = null)
    {
        if ($currency === null) {
            if ($this->base_currency === null) {
                return false;
            }

            $currency = $this->base_currency;
        }

        $curObject = new Currency($currency);
        return $curObject->getSuffix();
    }

    // Get real local amount from local amount (if double rounding)
    public function realAmount($amount, $round = true, $round2 = true)
    {
        global $var;
        $amount = $this->convertAmount($this->base_currency, $amount, $var['myCurrency'], $round, $round2);
        return $this->convertAmount($var['myCurrency'], $amount, $this->base_currency, $round, $round2);
    }

    // Get suffix
    public function convertAmount($old_currency = null, $amount = 0, $new_currency = null, $round = true, $round2 = false)
    {
        global $var, $user;

        try {
            if ($old_currency === null) {
                if ($this->base_currency === null) {
                    return false;
                }

                $old_currency = $this->base_currency;
            }

            if ($new_currency === null) {
                if (!isset($var['myCurrency'])) {
                    return false;
                }

                $new_currency = $var['myCurrency'];
            }

            $curObjectOld = new Currency($old_currency);
            $curObjectNew = new Currency($new_currency);
            if (empty($curObjectOld->getName()) || empty($curObjectNew->getName())) {
                return false;
            }

            return $curObjectNew->convertTo($curObjectOld->convertFrom($amount, $round), $round, $round2);
        } catch (CurrencyException $ex) {
            return false;
        }
    }

    public function getConversionRate($old_currency = null, $new_currency = null)
    {
        global $var, $user;

        try {
            if ($old_currency === null) {
                if ($this->base_currency === null) {
                    return false;
                }

                $old_currency = $this->base_currency;
            }

            if ($new_currency === null) {
                if (!isset($var['myCurrency'])) {
                    return false;
                }

                $new_currency = $var['myCurrency'];
            }

            $curObjectOld = new Currency($old_currency);
            $curObjectNew = new Currency($new_currency);
            if (empty($curObjectOld->getName()) || empty($curObjectNew->getName())) {
                return false;
            }

            $rate1 = $curObjectOld->conversion_rate;
            $rate2 = $curObjectNew->conversion_rate;

            return round($rate1 / $rate2, 2);
        } catch (CurrencyException $ex) {
            return false;
        }
    }

    // Function to add prefix and suffix to infix

    public function convertBack($amount = 0, $currency = null, $round = true, $round2 = true)
    {
        global $var;

        try {

            if ($this->base_currency === null) {
                return false;
            }

            $new_currency = $this->base_currency;

            if (!isset($var['myCurrency']) && !is_string($currency)) {
                return false;
            }

            $old_currency = isset($var['myCurrency']) ? $var['myCurrency'] : $currency;

            $curObjectOld = new Currency($old_currency);
            $curObjectNew = new Currency($new_currency);

            return $curObjectNew->convertTo($curObjectOld->convertFrom($amount, $round), $round, $round2);
        } catch (CurrencyException $ex) {
            return false;
        }
    }

    // Function to convert

    public function formatAmount($amount, $decimal_plates = 2, $reduce_dp = 0, $currency = null)
    {
        try {
            if ($currency === null) {
                if ($this->base_currency === null) {
                    return false;
                }

                $currency = $this->base_currency;
            }

            $curObject = new Currency($currency);
            return $curObject->formatAmount($amount, $decimal_plates, $reduce_dp);
        } catch (CurrencyException $ex) {
            return false;
        }
    }

    public function conva_smarty($params)
    {
        global $var;

        return $this->convertAmount(null, $params['n'], null);
    }

    // Function to convert back to base currency

    public function infix_smarty($params)
    {
        global $var;

        if ($params['c'] == "base") {
            $cur = $this->getBaseCurrency();
        } else if ($params['c'] == "choosed") {
            $cur = $var['myCurrency'];
        } else {
            $cur = $params['c'];
        }

        return $this->infix($params['n'], $cur);
    }

    // Function to format

    public function getBaseCurrency()
    {
        return $this->base_currency;
    }

    // Method to make infix from Smarty

    public function infix($infix, $currency = null)
    {
        global $var;

        if ($currency === null) {
            if (!isset($var['myCurrency'])) {
                return false;
            }

            $currency = $var['myCurrency'];
        }

        $curObject = ($currency instanceof Currency) ? $currency : new Currency($currency);
        return $curObject->getPrefix() . $infix . $curObject->getSuffix();
    }

    // Method to infix colorful

    public function colorInfix($infix, $currency = null)
    {
        global $nfo;

        $raw = $nfo->phpize($infix);
        if ($raw > 0) {
            return '<font color="green">' . $this->infix($infix, $currency) . '</font>';
        }

        if ($raw < 0) {
            return '<font color="red">' . $this->infix($infix, $currency) . '</font>';
        }

        return $this->infix($infix, $currency);
    }

    // Method to get currency
    public static function getCurrency($cur)
    {
        try {
            return new Currency($cur);
        } catch (CurrencyException $ex) {
            return false;
        }
    }
}

// Class storing currencies
class CurrencyStorage
{
    private static $currencies;
    private $cur;

    public function __construct($cur)
    {
        $this->cur = strtolower($cur);
    }

    public function fetch()
    {
        if (empty(self::$currencies)) {
            self::build();
        }

        if (!array_key_exists($this->cur, self::$currencies)) {
            return new stdClass;
        }

        return self::$currencies[$this->cur];
    }

    private static function build()
    {
        // Global database connection for security reasons
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM currencies");
        while ($row = $sql->fetch_object()) {
            self::$currencies[strtolower($row->ID)] = self::$currencies[strtolower($row->currency_code)] = $row;
        }

    }
}

// Class handling Exceptions
class CurrencyException extends Exception
{
}
