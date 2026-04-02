<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

use \jtl\Connector\Core\Exception\LanguageException;

class Language
{
    const CACHE_KEY_CONVERT = 'c';
    const CACHE_KEY_MAP = 'm';

    protected static $cache = array();

    protected static $locales = array(
        'ar_DZ' => 'ar',    'ar_EG' => 'ar',    'ar_KW' => 'ar',
        'ar_MA' => 'ar',    'ar_SA' => 'ar',    'az_AZ' => 'az',
        'be_BY' => 'be',    'bg_BG' => 'bg',    'bn_BD' => 'bn',
        'bs_BA' => 'bs',    'ca_ES' => 'ca',    'cs_CZ' => 'cs',
        'cy_GB' => 'cy',    'da_DK' => 'da',    'de_DE' => 'de',
        'de_CH' => 'de',    'de_AT' => 'de',    'el_GR' => 'el',
        'en_GB' => 'en',    'en_AU' => 'en',    'en_CA' => 'en',
        'en_NZ' => 'en',    'en_US' => 'en',    'es_ES' => 'es',
        'es_CO' => 'es',    'es_PA' => 'es',    'gl_ES' => 'gl',
        'es_CR' => 'es',    'es_AR' => 'es',    'es_MX' => 'es',
        'es_EU' => 'es',    'es_PE' => 'es',    'et_EE' => 'et',
        'fa_IR' => 'fa',    'fi_FI' => 'fi',    'fil_PH' => 'fil',
        'fr_FR' => 'fr',    'fr_CA' => 'fr',    'gu_IN' => 'gu',
        'he_IL' => 'he',    'hi_IN' => 'hi',    'hr_HR' => 'hr',
        'hu_HU' => 'hu',    'id_ID' => 'id',    'is_IS' => 'is',
        'it_IT' => 'it',    'it_CH' => 'it',    'ja_JP' => 'ja',
        'ka_GE' => 'ka',    'km_KH' => 'km',    'ko_KR' => 'ko',
        'lo_LA' => 'lo',    'lt_LT' => 'lt',    'lv_LV' => 'lv',
        'mk_MK' => 'mk',    'mn_MN' => 'mn',    'ms_MY' => 'ms',
        'nl_NL' => 'nl',    'nb_NO' => 'nb',    'nn_NO' => 'nn',
        'pl_PL' => 'pl',    'pt_BR' => 'pt',    'pt_PT' => 'pt',
        'ro_RO' => 'ro',    'ru_RU' => 'ru',    'sk_SK' => 'sk',
        'sl_SI' => 'sl',    'sq_AL' => 'sq',    'sr_RS' => 'sr',
        'sv_SE' => 'sv',    'sw_KE' => 'sw',    'th_TH' => 'th',
        'tr_TR' => 'tr',    'uk_UA' => 'uk',    'vi_VN' => 'vi',
        'zh_CN' => 'zh',    'zh_HK' => 'zh',    'zh_TW' => 'zh',
        'es_CL' => 'es',    'es_VE' => 'es',    'de_LU' => 'de',
        'en_IE' => 'en',    'af_ZA' => 'af',	'de_LI' => 'de',
		'en_BZ' => 'en', 	'en_CG' => 'en', 	'en_IN' => 'en',
		'en_JM' => 'en', 	'en_PH' => 'en',    'en_ZA' => 'en',
        'en_TT' => 'en'
    );
    
    protected static $languages = array(
        'ab' => 'abk',    'aa' => 'aar',    'af' => 'afr',
        'ak' => 'aka',    'sq' => 'alb',    'am' => 'amh',
        'ar' => 'ara',    'an' => 'arg',    'hy' => 'arm',
        'as' => 'asm',    'av' => 'ava',    'ae' => 'ave',
        'ay' => 'aym',    'az' => 'aze',    'bm' => 'bam',
        'ba' => 'bak',    'eu' => 'baq',    'be' => 'bel',
        'bn' => 'ben',    'bh' => 'bih',    'bi' => 'bis',
        'bs' => 'bos',    'br' => 'bre',    'bg' => 'bul',
        'my' => 'bur',    'ca' => 'cat',    'ch' => 'cha',
        'ce' => 'che',    'ny' => 'nya',    'zh' => 'chi',
        'cv' => 'chv',    'kw' => 'cor',    'co' => 'cos',
        'cr' => 'cre',    'hr' => 'hrv',    'cs' => 'cze',
        'da' => 'dan',    'dv' => 'div',    'nl' => 'dut',
        'dz' => 'dzo',    'en' => 'eng',    'eo' => 'epo',
        'et' => 'est',    'ee' => 'ewe',    'fo' => 'fao',
        'fj' => 'fij',    'fi' => 'fin',    'fr' => 'fre',
        'ff' => 'ful',    'gl' => 'glg',    'ka' => 'geo',
        'de' => 'ger',    'el' => 'gre',    'gn' => 'grn',
        'gu' => 'guj',    'ht' => 'hat',    'ha' => 'hau',
        'he' => 'heb',    'hz' => 'her',    'hi' => 'hin',
        'ho' => 'hmo',    'hu' => 'hun',    'ia' => 'ina',
        'id' => 'ind',    'ie' => 'ile',    'ga' => 'gle',
        'ig' => 'ibo',    'ik' => 'ipk',    'io' => 'ido',
        'is' => 'ice',    'it' => 'ita',    'iu' => 'iku',
        'ja' => 'jpn',    'jv' => 'jav',    'kl' => 'kal',
        'kn' => 'kan',    'kr' => 'kau',    'ks' => 'kas',
        'kk' => 'kaz',    'km' => 'khm',    'ki' => 'kik',
        'rw' => 'kin',    'ky' => 'kir',    'kv' => 'kom',
        'kg' => 'kon',    'ko' => 'kor',    'ku' => 'kur',
        'kj' => 'kua',    'la' => 'lat',    'lb' => 'ltz',
        'lg' => 'lug',    'li' => 'lim',    'ln' => 'lin',
        'lo' => 'lao',    'lt' => 'lit',    'lu' => 'lub',
        'lv' => 'lav',    'gv' => 'glv',    'mk' => 'mac',
        'mg' => 'mlg',    'ms' => 'may',    'ml' => 'mal',
        'mt' => 'mlt',    'mi' => 'mao',    'mr' => 'mar',
        'mh' => 'mah',    'mn' => 'mon',    'na' => 'nau',
        'nv' => 'nav',    'nb' => 'nob',    'nd' => 'nde',
        'ne' => 'nep',    'ng' => 'ndo',    'nn' => 'nno',
        'no' => 'nor',    'ii' => 'iii',    'nr' => 'nbl',
        'oc' => 'oci',    'oj' => 'oji',    'cu' => 'chu',
        'om' => 'orm',    'or' => 'ori',    'os' => 'oss',
        'pa' => 'pan',    'pi' => 'pli',    'fa' => 'per',
        'pl' => 'pol',    'ps' => 'pus',    'pt' => 'por',
        'qu' => 'que',    'rm' => 'roh',    'rn' => 'run',
        'ro' => 'rum',    'ru' => 'rus',    'sa' => 'san',
        'sc' => 'srd',    'sd' => 'snd',    'se' => 'sme',
        'sm' => 'smo',    'sg' => 'sag',    'sr' => 'srp',
        'gd' => 'gla',    'sn' => 'sna',    'si' => 'sin',
        'sk' => 'slo',    'sl' => 'slv',    'so' => 'som',
        'st' => 'sot',    'es' => 'spa',    'su' => 'sun',
        'sw' => 'swa',    'ss' => 'ssw',    'sv' => 'swe',
        'ta' => 'tam',    'te' => 'tel',    'tg' => 'tgk',
        'th' => 'tha',    'ti' => 'tir',    'bo' => 'tib',
        'tk' => 'tuk',    'tl' => 'tgl',    'tn' => 'tsn',
        'to' => 'ton',    'tr' => 'tur',    'ts' => 'tso',
        'tt' => 'tat',    'tw' => 'twi',    'ty' => 'tah',
        'ug' => 'uig',    'uk' => 'ukr',    'ur' => 'urd',
        'uz' => 'uzb',    've' => 'ven',    'vi' => 'vie',
        'vo' => 'vol',    'wa' => 'wln',    'cy' => 'wel',
        'wo' => 'wol',    'fy' => 'fry',    'xh' => 'xho',
        'yi' => 'yid',    'yo' => 'yor',    'za' => 'zha',
        'zu' => 'zul'
    );
    
    public static function convert($short = null, $long = null)
    {
        if (is_null($short) && is_null($long)) {
            throw new LanguageException('Short and Long cannot be null');
        }
        
        if (!is_null($short) && !empty($short) && isset(self::$languages[$short])) {
            return self::$languages[$short];
        }
        
        if (!is_null($long) && !empty($long)) {
            $long = strtolower($long);

            if (($short = array_search($long, self::$languages)) !== false) {
                return $short;
            }
        }
        
        return null;
    }
    
    public static function map($locale = null, $country = null, $lang = null, $useLong = true)
    {
        if (is_null($locale) && is_null($country) && is_null($lang)) {
            throw new LanguageException('Locale, Country and Language cannot be null');
        }
        
        if (!is_null($locale) && !empty($locale) && isset(self::$locales[$locale])) {
            return $useLong ? self::convert(self::$locales[$locale]) : self::$locales[$locale];
        }
        
        if (!is_null($lang)) {
            $country = self::convert(null, $lang);
        }

        if (!is_null($country) && !empty($country)) {
            $country = strtolower($country);

            if (($locale = array_search($country, self::$locales)) !== false) {
                return $locale;
            }
        }
        
        return null;
    }
}
