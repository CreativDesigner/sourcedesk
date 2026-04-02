<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package jtl\Connector\Core\Utilities
 */

namespace jtl\Connector\Core\Utilities;

use \jtl\Connector\Core\Exception\CountryException;

class Country
{
    protected static $_countries = array(
        'de' => 'deu','at' => 'aut','ch' => 'che',
        'li' => 'lie','it' => 'ita','lu' => 'lux',
        'fr' => 'fra','se' => 'swe','fi' => 'fin',
        'gb' => 'gbr','ie' => 'irl','nl' => 'nld',
        'be' => 'bel','pt' => 'prt','es' => 'esp',
        'gr' => 'grc','af' => 'afg','al' => 'alb',
        'dz' => 'dza','as' => 'asm','ad' => 'and',
        'ao' => 'ago','ai' => 'aia','aq' => 'ata',
        'ag' => 'atg','ar' => 'arg','am' => 'arm',
        'aw' => 'abw','au' => 'aus','az' => 'aze',
        'bs' => 'bhs','bh' => 'bhr','bd' => 'bgd',
        'bb' => 'brb','by' => 'blr','bz' => 'blz',
        'bj' => 'ben','bm' => 'bmu','bt' => 'btn',
        'bo' => 'bol','ba' => 'bih','bw' => 'bwa',
        'bv' => 'bvt','br' => 'bra','io' => 'iot',
        'bn' => 'brn','bg' => 'bgr','bf' => 'bfa',
        'bi' => 'bdi','kh' => 'khm','cm' => 'cmr',
        'ca' => 'can','cv' => 'cpv','ky' => 'cym',
        'cf' => 'caf','td' => 'tcd','cl' => 'chl',
        'cn' => 'chn','cx' => 'cxr','cc' => 'cck',
        'co' => 'col','km' => 'com','cg' => 'cog',
        'ck' => 'cok','cr' => 'cri','ci' => 'civ',
        'hr' => 'hrv','cu' => 'cub','cy' => 'cyp',
        'cz' => 'cze','dk' => 'dnk','dj' => 'dji',
        'dm' => 'dma','do' => 'dom','tl' => 'tls',
        'ec' => 'ecu','eg' => 'egy','sv' => 'slv',
        'gq' => 'gnq','er' => 'eri','ee' => 'est',
        'et' => 'eth','fk' => 'flk','fo' => 'fro',
        'fj' => 'fji','gf' => 'guf','pf' => 'pyf',
        'tf' => 'atf','ga' => 'gab','gm' => 'gmb',
        'ge' => 'geo','gh' => 'gha','gi' => 'gib',
        'gl' => 'grl','gd' => 'grd','gp' => 'glp',
        'gu' => 'gum','gt' => 'gtm','gn' => 'gin',
        'gw' => 'gnb','gy' => 'guy','ht' => 'hti',
        'hm' => 'hmd','hn' => 'hnd','hk' => 'hkg',
        'hu' => 'hun','is' => 'isl','in' => 'ind',
        'id' => 'idn','ir' => 'irn','iq' => 'irq',
        'il' => 'isr','jm' => 'jam','jp' => 'jpn',
        'jo' => 'jor','kz' => 'kaz','ke' => 'ken',
        'ki' => 'kir','kp' => 'prk','kr' => 'kor',
        'kw' => 'kwt','kg' => 'kgz','la' => 'lao',
        'lv' => 'lva','lb' => 'lbn','ls' => 'lso',
        'lr' => 'lbr','ly' => 'lby','lt' => 'ltu',
        'mo' => 'mac','mk' => 'mkd','mg' => 'mdg',
        'mw' => 'mwi','my' => 'mys','mv' => 'mdv',
        'ml' => 'mli','mt' => 'mlt','mh' => 'mhl',
        'mq' => 'mtq','mr' => 'mrt','mu' => 'mus',
        'yt' => 'myt','mx' => 'mex','fm' => 'fsm',
        'md' => 'mda','mc' => 'mco','mn' => 'mng',
        'ms' => 'msr','ma' => 'mar','mz' => 'moz',
        'mm' => 'mmr','na' => 'nam','nr' => 'nru',
        'np' => 'npl','an' => 'ant','nc' => 'ncl',
        'nz' => 'nzl','ni' => 'nic','ne' => 'ner',
        'ng' => 'nga','nu' => 'niu','nf' => 'nfk',
        'mp' => 'mnp','no' => 'nor','om' => 'omn',
        'pk' => 'pak','pw' => 'plw','pa' => 'pan',
        'pg' => 'png','py' => 'pry','pe' => 'per',
        'ph' => 'phl','pn' => 'pcn','pl' => 'pol',
        'pr' => 'pri','qa' => 'qat','re' => 'reu',
        'ro' => 'rou','ru' => 'rus','rw' => 'rwa',
        'kn' => 'kna','lc' => 'lca','vc' => 'vct',
        'ws' => 'wsm','sm' => 'smr','st' => 'stp',
        'sa' => 'sau','sn' => 'sen','rs' => 'srb',
        'sc' => 'syc','sl' => 'sle','sg' => 'sgp',
        'sk' => 'svk','si' => 'svn','sb' => 'slb',
        'so' => 'som','za' => 'zaf','lk' => 'lka',
        'sh' => 'shn','pm' => 'spm','sd' => 'sdn',
        'sr' => 'sur','sj' => 'sjm','sz' => 'swz',
        'sy' => 'syr','tw' => 'twn','tj' => 'tjk',
        'tz' => 'tza','th' => 'tha','tg' => 'tgo',
        'tk' => 'tkl','to' => 'ton','tt' => 'tto',
        'tn' => 'tun','tr' => 'tur','tm' => 'tkm',
        'tc' => 'tca','tv' => 'tuv','ug' => 'uga',
        'ua' => 'ukr','ae' => 'are','us' => 'usa',
        'um' => 'umi','uy' => 'ury','uz' => 'uzb',
        'vu' => 'vut','va' => 'vat','ve' => 'ven',
        'vn' => 'vnm','vg' => 'vgb','vi' => 'vir',
        'wf' => 'wlf','eh' => 'esh','ye' => 'yem',
        'ax' => 'ala','zm' => 'zmb','zw' => 'zwe',
        'me' => 'mne','cd' => 'cod','gg' => 'ggy',
        'im' => 'imn','je' => 'jey','ps' => 'pse',
        'bl' => 'blm','mf' => 'maf','gs' => 'sgs'
    );
    
    public static function map($short = null, $long = null)
    {
        if ($short === null && $long === null) {
            throw new CountryException("Short and Long cannot be null");
        }
        
        if ($short !== null && isset(self::$_countries[$short])) {
            return self::$_countries[$short];
        }
        
        if ($long !== null) {
            $long = strtolower($long);
            if (in_array($long, self::$_countries)) {
                foreach (self::$_countries as $short => $listlong) {
                    if ($listlong == $long) {
                        return $short;
                    }
                }
            }
        }
        
        return null;
    }
}
