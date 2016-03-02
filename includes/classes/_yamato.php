<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |   
// | http://www.zen-cart.com/index.php                                    |   
// |                                                                      |   
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
/*

  Yamato Shipping Calculator.
  Calculate shipping costs.

  2002/03/29 written by TAMURA Toshihiko (tamura@bitscope.co.jp)
  2003/04/10 modified for ms1
  2004/02/27 modified for ZenCart by HISASUE Takahiro ( hisa@flatz.jp )
  2005/02/15 modified for Yamato Transport by HIRAOKA Tadahito ( hira@s-page.net )
 * @author obitastar
 */
/*
    $rate = new _Yamato('yamato','通常便');
    $rate->SetOrigin('北海道', 'JP');   // 北海道から
    $rate->SetDest('東京都', 'JP');     // 東京都まで
    $rate->SetWeight(10);           // kg
    $quote = $rate->GetQuote();
    print $quote['type'] . "<br>";
    print $quote['cost'] . "\n";
*/
class _Yamato {
    var $quote;
    var $OriginZone;
    var $OriginCountryCode = 'JP';
    var $DestZone;
    var $DestCountryCode = 'JP';
    var $Weight = 0;
    var $Length = 0;
    var $Width  = 0;
    var $Height = 0;

    // コンストラクタ
    // $id:   module id
    // $titl: module name
    // $zone: 都道府県コード '01'～'47'
    // $country: country code
    function __construct($id, $title, $zone = NULL, $country = NULL) {
        $this->quote = array('id' => $id, 'title' => $title);
        if($zone) {
            $this->SetOrigin($zone, $country);
        }
    }
    // 発送元をセットする
    // $zone: 都道府県コード '01'～'47'
    // $country: country code
    function SetOrigin($zone, $country = NULL) {
        $this->OriginZone = $zone;
        if($country) {
            $this->OriginCountryCode = $country;
        }
    }
    function SetDest($zone, $country = NULL) {
        $this->DestZone = $zone;
        if($country) {
            $this->DestCountryCode = $country;
        }
    }
    function SetWeight($weight) {
        //$this->Weight = $weight;
        $this->Weight = $weight;
    }
    function SetSize($length = NULL, $width = NULL, $height = NULL) {
        if($length) {
            $this->Length = $length;
        }
        if($width) {
            $this->Width = $width;
        }
        if($height) {
            $this->Height = $height;
        }
    }
    // サイズ区分(0～4)を返す
    // 規格外の場合は9を返す
    //
    // 区分  サイズ名  ３辺計   重量
    // ----------------------------------
    // 0     60サイズ  60cmまで  2kgまで
    // 1     80サイズ  80cmまで  5kgまで
    // 2    100サイズ 100cmまで 10kgまで
    // 3    120サイズ 120cmまで 15kgまで
    // 4    140サイズ 140cmまで 20kgまで
    // 5    160サイズ 160cmまで 25kgまで
    // 9    規格外    
    function GetSizeClass() {
        $a_classes = array(
            array(0,  60,  2),  // 区分,３辺計,重量
            array(1,  80,  5),
            array(2, 100, 10),
            array(3, 120, 15),
            array(4, 140, 20),
            array(5, 160, 25)
        );

        $n_totallength = $this->Length + $this->Width + $this->Height;

        while (list($n_index, $a_limit) = each($a_classes)) {
            if ($n_totallength <= $a_limit[1] && $this->Weight <= $a_limit[2]) {
                return $a_limit[0];
            }
        }
        return -1;  // 規格外
    }
    // 送付元と送付先からキーを作成する
    //
    function GetDistKey() {
        $s_key = '';
        $s_z1 = $this->GetLZone($this->OriginZone);
        $s_z2 = $this->GetLZone($this->DestZone);
        if ( $s_z1 && $s_z2 ) {
            // 地帯コードをアルファベット順に連結する
            if ( ord($s_z1) < ord($s_z2) ) {
                $s_key = $s_z1 . $s_z2;
            } else {
                $s_key = $s_z2 . $s_z1;
            }
        }
        return $s_key;
    }
    // 都道府県コードから地帯コードを取得する
    // $zone: 都道府県コード
    function GetLZone($zone) {
        $areas['A'] = explode(',', '北海道');
        $areas['B'] = explode(',', '青森県,岩手県,秋田県');
        $areas['C'] = explode(',', '宮城県,山形県,福島県');
        $areas['D'] = explode(',', '東京都,神奈川県,千葉県,埼玉県,茨城県,栃木県,群馬県,山梨県');
        $areas['E'] = explode(',', '新潟県,長野県');
        $areas['F'] = explode(',', '岐阜県,静岡県,愛知県,三重県');
        $areas['G'] = explode(',', '富山県,石川県,福井県');
        $areas['H'] = explode(',', '大阪府,京都府,兵庫県,奈良県,滋賀県,和歌山県');
        $areas['I'] = explode(',', '岡山県,広島県,鳥取県,島根県,山口県');
        $areas['J'] = explode(',', '徳島県,香川県,愛媛県,高知県');
        $areas['K'] = explode(',', '福岡県,佐賀県,長崎県,大分県,熊本県,宮崎県,鹿児島県');
        $areas['L'] = explode(',', '沖縄県');
        $a_zonemap = array();
        foreach($areas as $code=>$area) {
            foreach($area as $pref) {
                $a_zonemap[$pref] = $code;
            }
        }
        return $a_zonemap[$zone];
    }

    function GetQuote() {
        // 距離別の価格ランク: ランクコード => 価格(60,80,100,120,140,160)
        // 2014/04～
        $a_pricerank = array(
        'N01'=>array( 756, 972,1188,1404,1620,1836), // 通常便(01) 近距離
        'N02'=>array( 864,1080,1296,1512,1728,1944), // 通常便(02)
        'N03'=>array( 972,1188,1404,1620,1836,2052), // 通常便(03)
        'N04'=>array(1080,1296,1512,1728,1944,2160), // 通常便(04)
        'N05'=>array(1188,1404,1620,1836,2052,2268), // 通常便(05)
        'N06'=>array(1296,1512,1728,1944,2160,2376), // 通常便(06)
        'N07'=>array(1404,1620,1836,2052,2268,2484), // 通常便(07)
        'N08'=>array(1512,1728,1944,2160,2376,2592), // 通常便(08)
        'N09'=>array(1620,1836,2052,2268,2484,2700), // 通常便(09)
        'N10'=>array(1728,1944,2160,2376,2592,2808), // 通常便(10)
        'N11'=>array(1836,2052,2268,2484,2700,2916),  // 通常便(11) 遠距離
        'X05'=>array(1188,1728,2268,2808,3348,3888),//
        'X06'=>array(1296,1836,2376,2916,3456,3996),//
        'X07'=>array(1404,1944,2484,3024,3564,4104),//
        'X08'=>array(1512,2052,2592,3132,3672,4212),//
        'X09'=>array(1620,2160,2700,3240,3780,4320),//
        'X12'=>array(1944,2484,3024,3564,4104,4644) //
        );
        // 地帯 - 地帯間の価格ランク
        // (参照) http://partner.kuronekoyamato.co.jp/estimate/all_est.html
        $a_dist_to_rank = array(
        'AA'=>'N01',
        'AB'=>'N03','BB'=>'N01',
        'AC'=>'N04','BC'=>'N01','CC'=>'N01',
        'AD'=>'N05','BD'=>'N02','CD'=>'N01','DD'=>'N01',
        'AE'=>'N05','BE'=>'N02','CE'=>'N01','DE'=>'N01','EE'=>'N01',
        'AF'=>'N06','BF'=>'N03','CF'=>'N02','DF'=>'N01','EF'=>'N01','FF'=>'N01',
        'AG'=>'N06','BG'=>'N03','CG'=>'N02','DG'=>'N01','EG'=>'N01','FG'=>'N01','GG'=>'N01',
        'AH'=>'N08','BH'=>'N04','CH'=>'N03','DH'=>'N02','EH'=>'N02','FH'=>'N01','GH'=>'N01','HH'=>'N01',
        'AI'=>'N09','BI'=>'N05','CI'=>'N05','DI'=>'N03','EI'=>'N03','FI'=>'N02','GI'=>'N02','HI'=>'N01','II'=>'N01',
        'AJ'=>'N10','BJ'=>'N06','CJ'=>'N06','DJ'=>'N04','EJ'=>'N04','FJ'=>'N03','GJ'=>'N03','HJ'=>'N02','IJ'=>'N02','JJ'=>'N01',
        'AK'=>'N11','BK'=>'N07','CK'=>'N07','DK'=>'N05','EK'=>'N05','FK'=>'N03','GK'=>'N03','HK'=>'N02','IK'=>'N01','JK'=>'N02','KK'=>'N01',
        'AL'=>'X12','BL'=>'X09','CL'=>'X08','DL'=>'X06','EL'=>'X07','FL'=>'X06','GL'=>'X07','HL'=>'X06','IL'=>'X06','JL'=>'X06','KL'=>'X05','LL'=>'N01'
        );

        $s_key = $this->GetDistKey();
        if ( $s_key ) {
            $s_rank = $a_dist_to_rank[$s_key];
            if ( $s_rank ) {
                $n_sizeclass = $this->GetSizeClass();
                if ($n_sizeclass < 0) {
                    $this->quote['error'] = MODULE_SHIPPING_YAMATO_TEXT_OVERSIZE;
                } else {
                    $this->quote['cost'] = $a_pricerank[$s_rank][$n_sizeclass];
                }
              //$this->quote['DEBUG'] = ' zone=' . $this->OriginZone . '=>' . $this->DestZone   //DEBUG
              //              . ' cost=' . $a_pricerank[$s_rank][$n_sizeclass];           //DEBUG
            } else {
                $this->quote['error'] = MODULE_SHIPPING_YAMATO_TEXT_OUT_OF_AREA . '(' . $s_key .')';
            }
        } else {
            $this->quote['error'] = MODULE_SHIPPING_YAMATO_TEXT_ILLEGAL_ZONE . '(' . $this->OriginZone . '=>' . $this->DestZone . ')';
        }
        return $this->quote;
    }
}
