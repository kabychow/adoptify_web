<?php

ini_set('gd.jpeg_ignore_warning', true);


class ImageResizer {

    public $error;
    private $source_path;
    private $target_path;

    
    public function __construct()
    {
        $this->error = 0;
    }


    public function resize($source_path, $target_path, $width = 0, $height = 0) {

        $this->source_path = $source_path;
        $this->target_path = $target_path;

        if ($this->_create_from_source()) {

            if ($width == 0 && $height > 0) {

                $aspect_ratio = $this->source_width / $this->source_height;
                $target_height = $height;
                $target_width = round($height * $aspect_ratio);

            } elseif ($width > 0 && $height == 0) {

                $aspect_ratio = $this->source_height / $this->source_width;
                $target_width = $width;
                $target_height = round($width * $aspect_ratio);

            } elseif ($width > 0 && $height > 0) {

                $vertical_aspect_ratio = $height / $this->source_height;
                $horizontal_aspect_ratio = $width / $this->source_width;

                if (round($horizontal_aspect_ratio * $this->source_height < $height)) {

                    $target_width = $width;
                    $target_height = round($horizontal_aspect_ratio * $this->source_height);

                } else {

                    $target_height = $height;
                    $target_width = round($vertical_aspect_ratio * $this->source_width);

                }

            } else {

                $target_width = $this->source_width;
                $target_height = $this->source_height;

            }

            $target_identifier = $this->_prepare_image(
                ($width > 0 && $height > 0 ? $width : $target_width),
                ($width > 0 && $height > 0 ? $height : $target_height)
            );

            imagecopyresampled(
                $target_identifier,
                $this->source_identifier,
                ($width > 0 && $height > 0 ? ($width - $target_width) / 2 : 0),
                ($width > 0 && $height > 0 ? ($height - $target_height) / 2 : 0),
                0,
                0,
                $target_width,
                $target_height,
                $this->source_width,
                $this->source_height
            );

            return $this->_write_image($target_identifier);
        }

        return false;
    }


    private function _create_from_source() {

        if (!function_exists('gd_info')) {

            $this->error = 7;
            return false;

        } elseif (!is_file($this->source_path)) {

            $this->error = 1;
            return false;

        } elseif (!is_readable($this->source_path)) {

            $this->error = 2;
            return false;

        } elseif ($this->target_path == $this->source_path && !is_writable($this->source_path)) {

            $this->error = 3;
            return false;

        } elseif (!list($this->source_width, $this->source_height, $this->source_type) = @getimagesize($this->source_path)) {

            $this->error = 4;
            return false;

        } else {

            $this->target_type = strtolower(substr($this->target_path, strrpos($this->target_path, '.') + 1));

            switch ($this->source_type) {

                case IMAGETYPE_GIF:

                    $identifier = imagecreatefromgif($this->source_path);

                    if (($this->source_transparent_color_index = imagecolortransparent($identifier)) >= 0)

                        $this->source_transparent_color = @imagecolorsforindex($identifier, $this->source_transparent_color_index);

                    break;

                case IMAGETYPE_JPEG:

                    $identifier = imagecreatefromjpeg($this->source_path);

                    break;

                case IMAGETYPE_PNG:

                    $identifier = imagecreatefrompng($this->source_path);
                    imagealphablending($identifier, false);

                    break;

                default:

                    $this->error = 4;

                    return false;

            }

        }

        $this->source_image_time = filemtime($this->source_path);
        $this->source_identifier = $identifier;

        return true;
    }


    private function _prepare_image($width, $height) {

        $identifier = imagecreatetruecolor((int)$width <= 0 ? 1 : (int)$width, (int)$height <= 0 ? 1 : (int)$height);

        if ($this->target_type == 'png') {

            imagealphablending($identifier, false);
            $transparent_color = imagecolorallocatealpha($identifier, 0, 0, 0, 127);
            imagefill($identifier, 0, 0, $transparent_color);
            imagesavealpha($identifier, true);

        } elseif ($this->target_type == 'gif' && $this->source_transparent_color_index >= 0) {

            $transparent_color = imagecolorallocate(
                $identifier,
                $this->source_transparent_color['red'],
                $this->source_transparent_color['green'],
                $this->source_transparent_color['blue']
            );

            imagefill($identifier, 0, 0, $transparent_color);
            imagecolortransparent($identifier, $transparent_color);

        } else {

            $background_color = imagecolorallocate($identifier, 255, 255, 255);
            imagefill($identifier, 0, 0, $background_color);

        }

        return $identifier;
    }


    private function _write_image($identifier) {

        switch ($this->target_type) {

            case 'gif':

                if (!function_exists('imagegif')) {

                    $this->error = 6;
                    return false;

                } elseif (@!imagegif($identifier, $this->target_path)) {

                    $this->error = 3;
                    return false;

                }

                break;

            case 'jpg':
            case 'jpeg':

                if (!function_exists('imagejpeg')) {

                    $this->error = 6;
                    return false;

                } elseif (@!imagejpeg($identifier, $this->target_path, 85)) {

                    $this->error = 3;
                    return false;

                }

                break;

            case 'png':

                imagesavealpha($identifier, true);

                if (!function_exists('imagepng')) {

                    $this->error = 6;
                    return false;

                } elseif (@!imagepng($identifier, $this->target_path, 9)) {

                    $this->error = 3;
                    return false;

                }

                break;

            default:

                $this->error = 5;
                return false;

        }

        $disabled_functions = @ini_get('disable_functions');

        if ($disabled_functions == '' || strpos('chmod', $disabled_functions) === false) {

            chmod($this->target_path, intval(0755, 8));

        } else $this->error = 8;

        if (isset($this->source_image_time)) {

            @touch($this->target_path, $this->source_image_time);

        }

        return true;
    }

}