<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

final class DataInjector
{
    const TYPE_ARRAY = 1;
    const TYPE_OBJECT = 2;

    public static function inject($type, &$data, $key, $value, $onSubs = false)
    {
        switch ($type) {
            case self::TYPE_ARRAY:
                if (!is_array($data)) {
                    throw new \InvalidArgumentException('no data array given');
                }
                self::injectIntoArray($data, $key, $value, $onSubs);
                break;
            case self::TYPE_OBJECT:
                if (!is_object($data)) {
                    throw new \InvalidArgumentException('no data object given');
                }
                self::injectIntoObject($data, $key, $value);
                break;
        }
    }
    
    protected static function injectIntoArray(&$data, $key, $value, $onSubs)
    {
        if ($onSubs) {
            $dataKeys = array_keys($data);
            foreach ($dataKeys as $dataKey) {
                if (is_array($key) && is_array($value)) {
                    foreach ($key as $i => $k) {
                        $data[$dataKey][$k] = $value[$i];
                    }
                } elseif (is_array($key)) {
                    foreach ($key as $i => $k) {
                        $data[$dataKey][$k] = $value;
                    }
                } else {
                    $data[$dataKey][$key] = $value;
                }
            }
        } else {
            if (is_array($key) && is_array($value)) {
                foreach ($key as $i => $k) {
                    $data[$k] = $value[$i];
                }
            } elseif (is_array($key)) {
                foreach ($key as $i => $k) {
                    $data[$k] = $value;
                }
            } else {
                $data[$key] = $value;
            }
        }
    }
    
    protected static function injectIntoObject(&$data, $key, $value)
    {
        if ($onSubs) {
            $dataKeys = array_keys($data);
            foreach ($dataKeys as $dataKey) {
                if (is_array($key) && is_array($value)) {
                    foreach ($key as $i => $k) {
                        $data[$dataKey]->$k = $value[$i];
                    }
                } elseif (is_array($key)) {
                    foreach ($key as $i => $k) {
                        $data[$dataKey]->$k = $value;
                    }
                } else {
                    $data[$dataKey]->$key = $value;
                }
            }
        } else {
            if (is_array($key) && is_array($value)) {
                foreach ($key as $i => $k) {
                    $data->$k = $value[$i];
                }
            } elseif (is_array($key)) {
                foreach ($key as $i => $k) {
                    $data->$k = $value;
                }
            } else {
                $data->$key = $value;
            }
        }
    }
}
