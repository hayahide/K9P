<?php

namespace Cake\Controller\Component;

use Cake\Controller\Component;

/**
 * Files Component
 *
 * @author Dinh Van Huong
 */
class FilesComponent extends Component
{

    public $options = [
        'ext'     => ['jpg', 'jpeg', 'png', 'gif'],
        'maxSize' => 2097152 // 2MB.
    ];

    /**
     * get value of option
     *
     * @param string $key
     * @return mixed (string|int|array|boolse)
     *
     * @since 1.0
     * @version 1.0
     * @author Dinh Van Huong
     */
    private function getOptionValue($key)
    {
        return $this->options[$key];
    }

    /**
     * upload file
     *
     * @param array $picture
     * @param string $path path file name e.g 'uploads/file-name.jpg'
     * @param boolse $crop
     * @param int $width
     * @param int $height
     * @param array $exts extention of file name allow to upload e.g ['jpg', 'jpeg', 'gif', ...]
     * @return array $data
     *
     * @since 1.0
     * @version 1.0
     * @author Dinh Van Huong
     */
    public function upload($picture = array(), $path = '', $crop = false, $width = 300, $height = 200, $exts = array())
    {
        $data = [];
        if (isset($picture['name'])) {

            $file    = $picture;
            $fileExt = substr(strtolower(strrchr($file['name'], '.')), 1);

            /* set extention default */
            if (empty($exts)) {
                $exts = $this->getOptionValue('ext');
            }

            /* check extention of file */
            if (!in_array($fileExt, $exts)) {
                return $data['error'][] = 'extention file must be in "' . implode(', ', $this->getOptionValue('ext')) . '"';
            }

            /* check filesize */
            if ($file['size'] > $this->getOptionValue('maxSize')) {
                return $data['error'][] = 'File size too big, Max size to upload : 2MB';
            }

            /* create folder if not existed */
            if ($path) {
                $this->createDir($path);
            }

            /* upload file */
            if (!move_uploaded_file($file['tmp_name'], $path . $file['name'])) {
                return $data['error'][] = 'File upload fail';
            }

            if ($crop) {
                $this->cropImage($path . $file['name'], $width, $height);
            }

            $data['name'] = $file['name'];
        }

        return $data;
    }

    /**
     * crop image
     *
     * @param string $imagePath
     * @param int $width
     * @param int $height
     * @return void
     *
     * @since 1.0
     * @version 1.0
     * @author Dinh Van Huong
     */
    public function cropImage($imagePath, $width, $height)
    {
        $allowedTypes = array( 
            'gif',
            'jpg',
            'jpeg',
            'png',
            'bmp'
        );
        
        if (!is_file($imagePath)) {
            return;
        }

        $pathImage = explode('/', $imagePath);
        list($fileName, $type)                  = explode('.', array_pop($pathImage));
        list($origWidthSize, $origHeightSize)   = getimagesize($imagePath);
        
        $type = strtolower($type);
        if (!in_array($type, $allowedTypes)) { 
            return; 
        }
        
        switch ($type) { 
            case 'gif' : 
                $im = imageCreateFromGif($imagePath); 
                break; 
            case 'jpg' :
            case 'jpeg':
                $im = imageCreateFromJpeg($imagePath); 
                break; 
            case 'png' : 
                $im = imageCreateFromPng($imagePath); 
                break; 
            case 'bmp' : 
                $im = imageCreateFromBmp($imagePath); 
                break; 
            default:
                return;
        }
        
        // save thumb image as thumbs folder //
        $folders      = implode('/', $pathImage);
        $thumbsFolder = $folders . '/thumbs';
        
        if (!is_dir($thumbsFolder)) {
            mkdir($thumbsFolder);
        }
        
        $oriRatio = $origWidthSize / $origHeightSize;
        
        if ($origWidthSize < $width) {
            if (($width/$height) > $oriRatio) {
                $newWidth   = $width;
                $newHeight  = round($height/$oriRatio);
            } else {
                $newWidth   = round($width*$oriRatio);
                $newHeight  = $height;
            }
            
            $xMid       = ceil($newWidth / 2) - (ceil($width / 2));
            $yMid       = ceil($newHeight / 2) - (ceil($height / 2));
            $process    = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($process, $im, 0, 0, 0, 0, $newWidth, $newHeight, $origWidthSize, $origHeightSize);        
            $thumbIm      = imagecrop($process, ['x' => $xMid, 'y' => $yMid, 'width' => $width, 'height' => $height]);
            imagejpeg($thumbIm, $thumbsFolder . '/' . $fileName . '.' . $type, 100);
            imagedestroy($process);
            
        } else {
            $newWidth   = ceil($origWidthSize / 2) - (ceil($width / 2));
            $newHeight  = ceil($origHeightSize / 2) - (ceil($height / 2));
            $thumbIm    = imagecrop($im, ['x' => $newWidth, 'y' => $newHeight, 'width' => $width, 'height' => $height]);
            imagejpeg($thumbIm, $thumbsFolder . '/' . $fileName . '.' . $type, 100);
        }
        
        imagedestroy($thumbIm);
        imagedestroy($im);
    }

    /**
     * create dir if dir not existed
     *
     * @param type $path
     * @return boolean
     *
     * @since 1.0
     * @version 1.0
     * @author Dinh Van Huong
     */
    public function createDir($path)
    {
        $listFolder = explode('/', $path);
        $childPath  = '';
        foreach ($listFolder as $folder) {
            $childPath = (empty($childPath)) ? $folder : $childPath . '/' . $folder;
            if (!is_dir($childPath) && !empty($childPath)) {
                mkdir($childPath);
            }
        }

        return true;
    }

}
