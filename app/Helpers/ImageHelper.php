<?php
namespace App\Helpers;

use Imagick;

class ImageHelper {
    private $file;
    private $image;
    private $width;
    private $height;
    private $bits;
    private $mime;

    public function __construct($file) {

        if (file_exists($file)) {
            $this->file = $file;

            $_arrAvailableFormats = [
                'image/gif',
                'image/png',
                'image/jpeg'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $isImage = in_array(finfo_file($finfo, $file), $_arrAvailableFormats);
            finfo_close($finfo);
            if($isImage){
                $info = getimagesize($file);

                $this->width  = $info[0];
                $this->height = $info[1];
                //$this->bits = isset($info['bits']) ? $info['bits'] : '';
                $this->mime = isset($info['mime']) ? $info['mime'] : '';

                if ($this->mime == 'image/gif') {
                    $this->image = imagecreatefromgif($file);
                } elseif ($this->mime == 'image/png') {
                    $this->image = imagecreatefrompng($file);
                } elseif ($this->mime == 'image/jpeg') {
                    $this->image = imagecreatefromjpeg($file);
                }
            } else {
                exit('Error: Incorrect image ' . $file . '!');
            }
        } else {
            exit('Error: Could not load image ' . $file . '!');
        }
    }

    public function getFile() {
        return $this->file;
    }


    public function save($file, $quality = 85, $ext = null) {
        $info = pathinfo($file);

        $extension = $ext??strtolower($info['extension']);

        if (is_resource($this->image)) {
            if ($extension == 'jpeg' || $extension == 'jpg') {
                imageinterlace($this->image, true);
                imagejpeg($this->image, $file, $quality);
            } elseif ($extension == 'png') {
                imagepng($this->image, $file);
            } elseif ($extension == 'gif') {
                imagegif($this->image, $file);
            }

            imagedestroy($this->image);
        }
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $default
     */
    public function resize($width = 0, $height = 0, $default = '') {
        if (!$this->width || !$this->height) {
            return;
        }

        $xpos = 0;
        $ypos = 0;
        $scale = 1;

        $scale_w = $width / $this->width;
        $scale_h = $height / $this->height;

        if ($default == 'w') {
            $scale = $scale_w;
        } elseif ($default == 'h') {
            $scale = $scale_h;
        } else {
            $scale = min($scale_w, $scale_h);
        }

        if ($scale == 1 && $scale_h == $scale_w && $this->mime != 'image/png') {
            return;
        }

        $new_width = (int)($this->width * $scale);
        $new_height = (int)($this->height * $scale);
        $xpos = (int)(($width - $new_width) / 2);
        $ypos = (int)(($height - $new_height) / 2);

        $image_old = $this->image;
        //$this->image = imagecreatetruecolor($width, $height);
        $this->image = imagecreatetruecolor($new_width, $new_height);

        if ($this->mime == 'image/png') {
            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
            $background = imagecolorallocatealpha($this->image, 255, 255, 255, 127);
            imagecolortransparent($this->image, $background);
        } else {
            $background = imagecolorallocate($this->image, 255, 255, 255);
        }

        //imagefilledrectangle($this->image, 0, 0, $width, $height, $background);

        //imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->width, $this->height);
        imagecopyresampled($this->image, $image_old, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);
        imagedestroy($image_old);

        $this->width = $width;
        $this->height = $height;
    }



    public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
        $image_old = $this->image;
        $this->image = imagecreatetruecolor($bottom_x - $top_x, $bottom_y - $top_y);

        imagecopy($this->image, $image_old, 0, 0, $top_x, $top_y, $this->width, $this->height);
        imagedestroy($image_old);

        $this->width = $bottom_x - $top_x;
        $this->height = $bottom_y - $top_y;
    }

    public function rotate($degree, $color = 'FFFFFF') {
        $rgb = $this->html2rgb($color);

        $this->image = imagerotate($this->image, $degree, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    private function filter() {
        $args = func_get_args();

        call_user_func_array('imagefilter', $args);
    }

    private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
        $rgb = $this->html2rgb($color);

        imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
    }

    private function merge($merge, $x = 0, $y = 0, $opacity = 100) {
        imagecopymerge($this->image, $merge->getImage(), $x, $y, 0, 0, $merge->getWidth(), $merge->getHeight(), $opacity);
    }

    private function html2rgb($color) {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 6) {
            list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return false;
        }

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return array($r, $g, $b);
    }
}
