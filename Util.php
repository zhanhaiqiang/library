<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/1
 * Time: 下午 4:47
 */

class Util
{
    /**
     * GBK按长度截取字符串
     *
     * @param $str
     * @param int $length
     * @return string
     */
    public static function gbkSubStr($str, $length = 0)
    {
        if ($length >= strlen($str)) return $str;
        //判断是否是汉字字符
        if (ord(substr($str,$length-1,1)) > 0xa0 && ($length % 2 != 0)) {
            $length++;
        }
        $str=substr($str,0,$length);
        return $str.'...';
    }

    /**
     * @转化字符编码
     *
     * @param $from_charset
     * @param $to_charset
     * @param $value
     * @return bool|string
     */
    public static function iconvAll($from_charset, $to_charset, $value)
    {
        if (!$value) {
            return false;
        }
        if (is_string($value)) {
            $value = iconv($from_charset, $to_charset, $value);
        } else {
            foreach ($value as $item =>$v) {
                $value[$item] = self::iconvAll($from_charset, $to_charset, $v);
            }
        }
        return $value;
    }

    /**
     * @从图片流压缩图片
     *
     * @param $imgData
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     */
    public static function imgZipFromData($imgData, $maxWidth = 0, $maxHeight = 0,$quality = 1)
    {
        $source_img = @ImageCreateFromString($imgData);
        if (!$source_img){
            return false;
        }
        $source_img_info = getimagesizefromstring($imgData);
        $img_type = $source_img_info['mime'];
        $img_width = $source_img_info[0];
        $img_height = $source_img_info[1];
        $maxWidth = $maxWidth ? $maxWidth: $img_width;
        $maxHeight = $maxHeight ? $maxHeight: $img_height;
        $xw = $maxWidth/$img_width;
        $xh = $maxHeight/$img_height;
        if ($xw <= $xh){
            $new_width = $maxWidth;
            $new_height = $img_height * $xw;
        } else {
            $new_height = $maxHeight;
            $new_width = $img_width * $xh;
        }
        $new_img = imagecreatetruecolor($new_width, $new_height);
        if ($quality){
            imagecopyresampled($new_img, $source_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);
        } else {
            ImageCopyResized($new_img, $source_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);
        }
        imagedestroy($source_img);
        Header ( "Content-type: $img_type" );
        switch ($img_type){
            case 'image/jpeg':
                imagejpeg($new_img);
                break;
            case 'image/png':
                imagepng($new_img);
                break;
            case 'image/gif':
                //动态图不会有动画效果
                imagegif($new_img);
                break;
            default:
                return false;
        }
    }

    /**
     * @通过图片路径压缩图片
     *
     * @param $imgFile
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return bool
     */
    public static function imgZipFromFile($imgFile, $maxWidth = 0, $maxHeight = 0,$quality = 1)
    {
        $source_img_info = getimagesize($imgFile);
        $img_type = $source_img_info['mime'];
        $img_width = $source_img_info[0];
        $img_height = $source_img_info[1];
        switch ($img_type){
            case 'image/jpeg':
                $source_img = @imagecreatefromjpeg($imgFile);
                break;
            case 'image/png':
                $source_img = @imagecreatefrompng($imgFile);
                break;
            case 'image/gif':
                $source_img = @imagecreatefromgif($imgFile);
                break;
            default:
                return false;
        }
        if (!$source_img){
            return false;
        }
        $maxWidth = $maxWidth ? $maxWidth: $img_width;
        $maxHeight = $maxHeight ? $maxHeight: $img_height;
        $xw = $maxWidth/$img_width;
        $xh = $maxHeight/$img_height;
        if ($xw <= $xh){
            $new_width = $maxWidth;
            $new_height = $img_height * $xw;
        } else {
            $new_height = $maxHeight;
            $new_width = $img_width * $xh;
        }
        $new_img = imagecreatetruecolor($new_width, $new_height);
        if ($quality){
            imagecopyresampled($new_img, $source_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);
        } else {
            ImageCopyResized($new_img, $source_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);
        }
        imagedestroy($source_img);
        Header ( "Content-type: $img_type" );
        switch ($img_type){
            case 'image/jpeg':
                imagejpeg($new_img);
                break;
            case 'image/png':
                imagepng($new_img);
                break;
            case 'image/gif':
                //动态图不会有动画效果
                imagegif($new_img);
                break;
            default:
                return false;
        }
    }

    /**
     * @将xml转化成数组
     *
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml)),true);
    }

    /**
     * @数组转化成xml
     *
     * @param $array
     * @return bool|string
     */
    public static function arrayToXml($array)
    {
        $return_xml = '';
        if ($array) {
            foreach ($array as $key => $value ) {
                if (is_array($value)) {
                    $return_xml .= "<$key>";
                    $return_xml .= self::arrayToXml($value);
                    $return_xml .= "</$key>";
                } else {
                    $return_xml .= "<$key>$value</$key>";
                }
            }
            return $return_xml;
        } else {
            return $array;
        }
    }

    /**
     * @遍历目录下的文件
     *
     * @param $dir
     * @return array
     */
    public static function  readDir($dir)
    {
        if (is_dir($dir)) {
            $file_array = array();
            $open_dir = opendir($dir);
            while (($file = readdir($open_dir)) !== false) {
                if ($file != '.' && $file != '..') {
                    $cur_dir = $dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($cur_dir)) {
                        $file_array[$file] = self::readDir($cur_dir);
                    } else {
                        $file_array[] = $file;
                    }
                }
            }
            closedir($open_dir);
            return $file_array;
        } else {
            return $dir;
        }
    }

    /**
     * @遍历目录下的文件
     *
     * @param $dir
     * @return array
     */
    public static function  readDir2($dir)
    {
        if (is_dir($dir)) {
            $file_array = array();
            $open_dir = scandir($dir);
            foreach ($open_dir as $key => $value) {
                if ($value != '.' && $value != '..') {
                    $cur_dir = $dir . DIRECTORY_SEPARATOR . $value;
                    if (is_dir($cur_dir)) {
                        $file_array[$value] = self::readDir2($cur_dir);
                    } else {
                        $file_array[] = $value;
                    }
                }
            }
            return $file_array;
        } else {
            return $dir;
        }
    }

    /**
     * @生成验证码
     *
     * @param $width
     * @param $height
     * @param $frontFile
     * @param int $codeLength
     */
    public static function validateCode($width, $height, $frontFile = '', $codeLength = 4)
    {
        $width = $width ? $width : 130;
        $height = $height ? $height :50;
        $charset = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789';//随机因子
        $create_code = '';
        //生成随机码
        for ($i = 0; $i < $codeLength; $i++) {
            $create_code .= $charset[mt_rand(0, mb_strlen($charset))];
        }
        //生成背景
        $bg_img = imagecreatetruecolor($width, $height);
        $bg_color = imagecolorallocate($bg_img, mt_rand(130, 200), mt_rand(130, 200), mt_rand(130, 200));
        imagefilledrectangle($bg_img, 0, 0, $width, $height, $bg_color);
        //画线
        for ($i = 0; $i < 8; $i++) {
            $line_color = imagecolorallocate($bg_img, mt_rand(0, 130), mt_rand(0, 130), mt_rand(0, 130));
            imageline($bg_img, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $line_color);
        }
        //雪花
        for ($i = 0; $i < 20; $i++) {
            $str_color = imagecolorallocate($bg_img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($bg_img, mt_rand(0, 5), mt_rand(0, $width), mt_rand(0, $height), '*', $str_color);
        }
        //生成文字
        $x = $width / $codeLength;
        for ($i = 0; $i < $codeLength; $i++) {
            $front_color = imagecolorallocate($bg_img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            if ($frontFile) {
                imagettftext($bg_img, 20, mt_rand(-30, 30), $x*$i+mt_rand(1, 5), $height/1.4, $str_color, $frontFile, $create_code[$i]);
            } else {
                imagechar($bg_img, 5, $x*$i+mt_rand(1, 5), $height/2.3, $create_code[$i], $front_color);

            }
        }
        //输出验证码
        header('Content-type: image/png');
        imagepng($bg_img);
        imagedestroy($bg_img);
    }

    /**
     * @curl 获取数据
     *
     * @param $url
     * @param array $data
     * @param string $method
     * @return mixed
     */
    public static function curlGetData($url, $data = array(), $method = 'get')
    {
        $ch = curl_init();
        // 设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if (strtolower($method) != 'get') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        //抓取URL并把它传递给浏览器
        $out_res = curl_exec($ch);
        if ($out_res === false) {
            echo 'CURL Error : '.curl_error($ch);
        }
        //关闭cURL资源，并且释放系统资源
        curl_close($ch);
        return $out_res;
    }











}