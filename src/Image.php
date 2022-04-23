<?php
/**
 * Image.php
 *
 * This file is part of ImageManipulation.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2021 ImageManipulation
 * @license    https://github.com/muhammetsafak/ImageManipulation/blob/main/LICENSE  MIT
 * @version    0.3
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace ImageManipulation;

use \InvalidArgumentException;
use \RuntimeException;



/**
 * Resim dosyaları üzerinde manipülasyon yapmanızı sağlayacak basit bir sınıftır.
 *
 *
 * @property string $text_color
 * @property string $text_font
 * @property int $text_size
 * @property int $text_angle
 * @property int $text_coordinateX
 * @property int $text_coordinateY
 * @property int $text_align
 * @property bool $text_shadow
 * @property string $text_shadow_color
 * @property null|string $text_background
 * @property int $text_left
 * @property int $text_right
 * @property int $text_top
 * @property int $text_bottom
 * @property int $text_padding_y
 * @property int $text_padding_x
 * @property int $watermark_position
 * @property int $watermark_left
 * @property int $watermark_right
 * @property int $watermark_top
 * @property int $watermark_bottom
 * @property float $watermark_opacity
 * @property int $watermark_width
 * @property int $watermark_height
 */
class Image
{

    public const VERSION = '0.3';

    /** @var string|null  */
    protected ?string $path;

    /** @var resource */
    protected $resource = null;

    protected array $size = ['width' => 0, 'height' => 0];
    protected int $type;
    protected string $strSize;
    protected string $mime;
    protected ?string $extension = null;

    public const CONVERT_JPG = \IMAGETYPE_JPEG;
    public const CONVERT_GIF = \IMAGETYPE_GIF;
    public const CONVERT_PNG = \IMAGETYPE_PNG;
    public const CONVERT_WEBP = IMAGETYPE_WEBP;

    public ?int $convert = null;

    public const ALIGN_NO = 0;
    public const ALIGN_LEFT_TOP = 1;
    public const ALIGN_CENTER_TOP = 2;
    public const ALIGN_RIGHT_TOP = 3;
    public const ALIGN_LEFT_CENTER = 4;
    public const ALIGN_CENTER_CENTER = 5;
    public const ALIGN_RIGHT_CENTER = 6;
    public const ALIGN_LEFT_BOTTOM = 7;
    public const ALIGN_CENTER_BOTTOM = 8;
    public const ALIGN_RIGHT_BOTTOM = 9;

    protected array $supportTransparency = [
        \IMAGETYPE_PNG, \IMAGETYPE_WEBP,
    ];

    protected array $textOptions = [
        'font'              => __DIR__ . '/OpenSans.ttf',
        'size'              => 18, // Font-Size
        'angle'             => 0,
        'coordinateX'       => 0,
        'coordinateY'       => 0,
        'align'             => self::ALIGN_CENTER_BOTTOM,
        'color'             => '#000000',
        'shadow'            => false,
        'shadow_color'      => '#808080',
        'background'        => null,
        'left'              => 5,
        'right'             => 5,
        'top'               => 5,
        'bottom'            => 5,
        'padding_x'         => 0,
        'padding_y'         => 0,
    ];

    protected array $watermark = [
        'position'      => self::ALIGN_RIGHT_BOTTOM,
        "left"          => 5,
        "right"         => 5,
        "top"           => 5,
        "bottom"        => 5,
        'opacity'       => 1.0,
    ];

    public function __construct()
    {
        if(!\extension_loaded('gd')){
            throw new RuntimeException('The ' . __CLASS__ . ' library needs the "GD extension" to work.');
        }
    }

    public function __destruct()
    {
        $this->clean();
    }

    public function __set($name, $value)
    {
        if(\substr($name, 0, 5) === 'text_'){
            $name = \substr($name, 5);
            return $this->textOptions[$name] = $value;
        }elseif(\substr($name, 0, 10) === 'watermark_'){
            $name = \substr($name, 10);
            return $this->watermark[$name] = $value;
        }
        throw new RuntimeException('Property access denied. : '. $name);
    }

    /**
     * Özellikleri sıfırlar ve belleği boşaltır.
     *
     * @return void
     */
    public function clean(): void
    {
        if($this->resource !== null){
            \imagedestroy($this->resource);
        }
        $this->path = null;
        $this->size = ['width' => 0, 'height' => 0];
        $this->type = \IMAGETYPE_UNKNOWN;
        $this->strSize = '';
        $this->mime = '';
        $this->extension = null;
        $this->textOptions = [
            'font'              => __DIR__ . '/OpenSans.ttf',
            'size'              => 18,
            'angle'             => 0,
            'coordinateX'       => 0,
            'coordinateY'       => 0,
            'align'             => self::ALIGN_CENTER_BOTTOM,
            'color'             => '#000000',
            'shadow'            => false,
            'shadow_color'      => '#808080',
            'background'        => null,
            'left'              => 5,
            'right'             => 5,
            'top'               => 5,
            'bottom'            => 5,
            'padding_x'         => 0,
            'padding_y'         => 0,
        ];
        $this->watermark = [
            'position'      => self::ALIGN_RIGHT_BOTTOM,
            "left"          => 5,
            "right"         => 5,
            "top"           => 5,
            "bottom"        => 5,
            'opacity'       => 1.0,
        ];
    }

    /**
     * Manipüle edilecek resim dosyasını tanımlar.
     *
     * @throws InvalidArgumentException <p>Verilen $path bir dosya değilse.</p>
     * @throws RuntimeException <p><code>getimagesize()</code> işlevi belirtilen dosyayı çözümleyemezse.</p>
     * @param string $path <p>Resim dosyasının tam yolu</p>
     * @return $this
     */
    public function setImage(string $path): self
    {
        if($this->resource !== null){
            \imagedestroy($this->resource);
        }
        $info = $this->fileInfo($path);
        $this->path = $path;
        $this->mime = $info['mime'] ?? 'image/jpg';
        $this->size['width'] = $info[0];
        $this->size['height'] = $info[1];
        $this->type = $info[2];
        $this->strSize = $info[3];
        $this->extension = \pathinfo($this->path, \PATHINFO_EXTENSION);
        $this->getResource();
        return $this;
    }

    /**
     * Image sınıfının bir örneğini döndürür.
     *
     * @param string $path
     * @return Image
     */
    public function withImage(string $path): Image
    {
        $clone = clone $this;
        $clone->setImage($path);
        return $clone;
    }

    /**
     * Belirtilen ölçü ve türde bir boş bir resim oluşturur.
     *
     * @param int $width
     * @param int $height
     * @param int $type
     * @param string|null $bgHex
     * @return $this
     */
    public function create(int $width, int $height, int $type = \IMAGETYPE_JPEG, ?string $bgHex = null): self
    {
        if($this->resource !== null){
            \imagedestroy($this->resource);
        }
        $this->size['width'] = $width;
        $this->size['height'] = $height;
        $this->strSize = 'width="'.$width.'" height="'.$height.'"';
        $this->type = $type;

        switch($type){
            case \IMAGETYPE_JPEG:
                $this->mime = 'image/jpeg';
                $this->extension = 'jpeg';
                break;
            case \IMAGETYPE_GIF:
                $this->mime = 'image/gif';
                $this->extension = 'gif';
                break;
            case \IMAGETYPE_WEBP:
                $this->mime = 'image/webp';
                $this->extension = 'webp';
                break;
            case \IMAGETYPE_PNG:
                $this->mime = 'image/png';
                $this->extension = 'png';
                break;
            default:
                throw new InvalidArgumentException('Only jpeg, png, gif or webp. use "IMAGETYPE_" consts.');
        }
        if($this->resource !== null){
            \imagedestroy($this->resource);
        }
        $this->resource = \imagecreatetruecolor($width, $height);
        if($bgHex !== null){
            $rgb = $this->hextoRGB($bgHex);
            $bgColor = \imagecolorallocate($this->resource, $rgb['red'], $rgb['green'], $rgb['blue']);
            \imagefill($this->resource, 0, 0, $bgColor);
        }
        if(\in_array($this->type, $this->supportTransparency, true)){
            if($bgHex === null){
                $bgColor = \imagecolorallocatealpha($this->resource, 255, 255, 255, 127);
                \imagefill($this->resource, 0, 0, $bgColor);
            }
            \imagealphablending($this->resource, false);
            \imagesavealpha($this->resource, true);
        }
        return $this;
    }

    /**
     * Belirtilen ölçülerde ve türde bir resim oluşturur.
     *
     * @param int $width
     * @param int $height
     * @param int $type
     * @param string|null $bgHex
     * @return Image <p>\ImageManipulation\Image sınıfının örneğini döndürür.</p>
     */
    public function withCreate(int $width, int $height, int $type = \IMAGETYPE_JPEG, ?string $bgHex = null): Image
    {
        $clone = clone $this;
        $clone->create($width, $height, $type, $bgHex);
        return $clone;
    }

    /**
     * Resmin tamamını belirtilen renk ile boyar/doldurur.
     *
     * @param string $hex
     * @return $this
     */
    public function fill(string $hex = '#000000'): self
    {
        $rgb = $this->hextoRGB($hex);
        $color = \imagecolorallocate($this->resource, $rgb['red'], $rgb['green'], $rgb['blue']);
        \imagefill($this->resource, 0, 0, $color);
        return $this;
    }

    /**
     * Tanımlanmış resim dosyasını kopyalar.
     *
     * @throws RuntimeException <p>copy() işlevi başarısız olursa.</p>
     * @param string $name <p>Resim dosyasının yeni adı</p>
     * @param string|null $dir <p>Resim dosyasının kopyalanacağı dizin yolu. NULL ise aynı dizinde kopyalama yapar.</p>
     * @return $this
     */
    public function copy(string $name, ?string $dir = null): self
    {
        $to = $this->getPathCalc($name, $dir);
        if(\copy($this->path, $to) === FALSE){
            throw new RuntimeException('The copy operation failed.');
        }
        \imagedestroy($this->resource);
        return $this->setImage($to);
    }

    /**
     * Resim üzerindeki belirtilen rengi transparan yapmayı dener.
     * @param string $hex
     * @return $this
     */
    public function colorToTransparent(string $hex): self
    {
        $rgb = $this->hextoRGB($hex);
        $color = \imagecolorallocate($this->resource, $rgb['red'], $rgb['green'], $rgb['blue']);
        \imagecolortransparent($this->resource, $color);
        return $this;
    }

    /**
     * Tanımlanmış resim dosyasını taşır.
     *
     * @param string $name <p>Dosyanın yeni adı</p>
     * @param string|null $dir <p>Taşınacağı dizinin yolu. Null ise bulunduğu dizinde işlem yapılır.</p>
     * @return bool
     */
    public function move(string $name, ?string $dir = null): bool
    {
        return \move_uploaded_file($this->path, $this->getPathCalc($name, $dir));
    }

    /**
     * Resim yeniden boyutlandırır.
     *
     * @param int $w <p>Width / Genişlik</p>
     * @param int $h <p>Height / Yükseklik</p>
     * @param bool $crop <p>
     * <p>true ise resim boyutlandırılır ve merkez nokta baz alınarak kroplama işlemi yapılır.</p>
     * <p>false ise kroplama işlemi yapılmadan resim orantılı olarak boyutlandırılır.</p>
     * </p>
     * @return $this
     */
    public function resize(int $w, int $h, bool $crop = false): self
    {
        $width = $this->size['width'];
        $height = $this->size['height'];
        $ratio_original = $width / $height; // Original ratio
        $sourceWidth = $this->getWidth();
        $sourceHeight = $this->getHeight();
        $newHeight = $h;
        $newWidth = $w;
        if($crop === TRUE){
            if ($width > $height) {
                $width = ceil($width-($width*abs($ratio_original-$w/$h)));
            } else {
                $height = ceil($height-($height*abs($ratio_original-$w/$h)));
            }
        }else{
            if(($w / $h) > $ratio_original){
                $newWidth = ($h * $ratio_original);
            }else{
                $newHeight = ($w / $ratio_original);
            }
        }
        $newImage = \imagecreatetruecolor((int)$newWidth, (int)$newHeight);
        if(\in_array($this->type, $this->supportTransparency, true)){
            \imagealphablending($newImage, false);
            \imagesavealpha($newImage, true);
        }
        \imagecopyresampled($newImage, $this->resource, 0, 0, 0, 0, (int)$newWidth, (int)$newHeight, (int)$sourceWidth, (int)$sourceHeight);
        \imagedestroy($this->resource);
        $this->resource = $newImage;
        \imagedestroy($newImage);
        return $this;
    }

    /**
     * Katı kuralla yeniden boyutlandırır.
     *
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function strictResize(int $width, int $height): self
    {
        $newImage = \imagecreatetruecolor($width, $height);
        if(\in_array($this->type, $this->supportTransparency, true)){
            \imagealphablending($newImage, false);
            \imagesavealpha($newImage, true);
        }
        \imagecopyresampled($newImage, $this->resource, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        \imagedestroy($this->resource);
        $this->resource = $newImage;
        \imagedestroy($newImage);
        return $this;
    }

    /**
     * Tanımlı görselin merkezini baz alarak kroplama işlemi yapar.
     *
     * @see https://user-images.githubusercontent.com/9823597/147645933-b51a741a-44c2-418f-a53a-e68fc3dc36e8.jpg
     * @throws RuntimeException <p>imagecrop başarısız olursa.</p>
     * @param int $width
     * @param int $height
     * @param int $xAxis
     * @param int $yAxis
     * @return $this
     */
    public function crop(int $width, int $height, int $xAxis = 0, int $yAxis = 0): self
    {
        $centerX = $this->size['width'] / 2;
        $centerY = $this->size['height'] / 2;

        $xAxis = \ceil(($centerX - ($width / 2)) + $xAxis);
        if($xAxis < 0){
            $xAxis = 0;
        }elseif($xAxis > ($this->size['width'] - $width)){
            $xAxis = ($this->size['width'] - $width);
        }
        $yAxis = \ceil(($centerY - ($height / 2)) + $yAxis);
        if($yAxis < 0){
            $yAxis = 0;
        }elseif($yAxis > ($this->size['height'] - $height)){
            $yAxis = ($this->size['height'] - $height);
        }

        $src = \imagecrop($this->resource, ['x' => (int)$xAxis, 'y' => (int)$yAxis, 'width' => $width, 'height' => $height]);
        if($src === FALSE){
            throw new RuntimeException('imagecrop() failed.');
        }
        if(\in_array($this->type, $this->supportTransparency, true)){
            \imagealphablending($src, false);
            \imagesavealpha($src, true);
        }
        \imagedestroy($this->resource);
        $this->resource = $src;
        \imagedestroy($src);

        return $this;
    }

    /**
     * Resmi belirtilen yüksekliğe göre orantılayarak yeniden boyutlandırır.
     *
     * @param $height
     * @return $this
     */
    public function resizeToHeight($height): self
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        return $this->resize((int)$width, (int)$height);
    }

    /**
     * Resmi belirtilen genişliğe göre orantılayarak yeniden boyutlandırır.
     *
     * @param $width
     * @return $this
     */
    public function resizeToWidth($width): self
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        return $this->resize((int)$width, (int)$height);
    }

    /**
     * Resmi belli bir oranda yeniden boyutlandırır.
     *
     * @param int $scale
     * @return $this
     */
    public function resizeScale(int $scale): self
    {
        $width = $this->getWidth() * ($scale / 100);
        $height = $this->getheight() * ($scale / 100);
        return $this->resize((int)$width, (int)$height);
    }

    /**
     * Resmi belirtilen dereceyle çevirir.
     *
     * @param float|int $deg <p>Only 90, 180, 270</p>
     * @return $this
     */
    public function rotate($deg): self
    {
        $deg = (float)$deg;
        if(!\in_array($deg, [90.0, 180.0, 270.0], true)){
            throw new InvalidArgumentException('You can only rotate 90, 180 or 270 degrees.');
        }

        if($deg === 90.0 || $deg === 270.0){
            $size = $this->size;
            $this->size = [
                'width'     => $size['width'],
                'height'    => $size['height'],
            ];
        }

        if(($rotate = \imagerotate($this->resource, $deg, 0)) === FALSE){
            throw new RuntimeException('An error occurred during the rotation process.');
        }
        \imagedestroy($this->resource);

        \imagesavealpha($rotate, true);
        $this->resource = $rotate;
        \imagedestroy($rotate);

        return $this;
    }


    /**
     * Resmi döndürür.
     *
     * @throws InvalidArgumentException <p>Geçersiz $flipMode tanımlanırsa.</p>
     * @param string|int $flipMode <p>String : "vertical", "horizontal", "both" or Integer : \IMG_FLIP_VERTICAL, \IMG_FLIP_HORIZONTAL, \IMG_FLIP_BOTH</p>
     * @return $this
     */
    function flip($flipMode): self
    {
        if(\is_string($flipMode)){
            $flipMode = \strtolower($flipMode);
        }
        if(!\in_array($flipMode, ['vertical', 'horizontal', 'both', \IMG_FLIP_VERTICAL, \IMG_FLIP_HORIZONTAL, \IMG_FLIP_BOTH], true)){
            throw new InvalidArgumentException('It can only be rotated vertically or horizontally.');
        }
        switch($flipMode){
            case 'vertical' : $mode = \IMG_FLIP_VERTICAL; break;
            case 'horizontal' : $mode = \IMG_FLIP_HORIZONTAL; break;
            case 'both' : $mode = \IMG_FLIP_BOTH; break;
            default: $mode = $flipMode;
        }

        $width = $this->getWidth();
        $height = $this->getHeight();

        [$srcX, $srcY, $srcWidth, $srcHeight] = [0, 0, $width, $height];

        switch ( $mode )
        {
            case \IMG_FLIP_VERTICAL :
                $srcY = $height - 1;
                $srcHeight = -$height;
                break;
            case \IMG_FLIP_HORIZONTAL :
                $srcX = $width - 1;
                $srcWidth = -$width;
                break;
            case \IMG_FLIP_BOTH :
                $srcX = $width - 1;
                $srcY = $height - 1;
                $srcWidth = -$width;
                $srcHeight = -$height;
                break;
        }

        $dest = \imagecreatetruecolor($width, $height);
        if(\in_array($this->type, $this->supportTransparency, true)){
            \imagealphablending($dest, false);
            \imagesavealpha($dest, true);
        }

        \imagecopyresampled($dest, $this->resource, 0, 0, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);

        \imagedestroy($this->resource);

        $this->resource = $dest;

        \imagedestroy($dest);

        return $this;
    }

    /**
     * Görsele filter uygular.
     *
     * @param $filter <p><code>\IMG_FILTER_BRIGHTNESS, \IMG_FILTER_COLORIZE, \IMG_FILTER_CONTRAST, \IMG_FILTER_EDGEDETECT, \IMG_FILTER_EMBOSS, \IMG_FILTER_GAUSSIAN_BLUR, \IMG_FILTER_GRAYSCALE, \IMG_FILTER_MEAN_REMOVAL, \IMG_FILTER_NEGATE, \IMG_FILTER_PIXELATE, \IMG_FILTER_SCATTER, \IMG_FILTER_SELECTIVE_BLUR, \IMG_FILTER_SMOOTH</code></p>
     * @return $this
     */
    public function filter($filter): self
    {
        if(!\in_array($filter, [\IMG_FILTER_BRIGHTNESS, \IMG_FILTER_COLORIZE, \IMG_FILTER_CONTRAST, \IMG_FILTER_EDGEDETECT, \IMG_FILTER_EMBOSS, \IMG_FILTER_GAUSSIAN_BLUR, \IMG_FILTER_GRAYSCALE, \IMG_FILTER_MEAN_REMOVAL, \IMG_FILTER_NEGATE, \IMG_FILTER_PIXELATE, \IMG_FILTER_SCATTER, \IMG_FILTER_SELECTIVE_BLUR, \IMG_FILTER_SMOOTH], true)){
            throw new InvalidArgumentException('Just the "IMG_FILTER_*" constants.');
        }
        \imagefilter($this->resource, $filter);
        return $this;
    }

    /**
     * Resim çevresine istenilen kalınlıkta çerçeve çizer.
     *
     * @param int $thickness <p>Çerçeve kalınlığı</p>
     * @param string $colorHEX <p>Çerçevenin (HEX) renk kodu</p>
     * @return $this
     */
    public function frame(int $thickness, string $colorHEX = '#000000'): self
    {
        $rgb = $this->hextoRGB($colorHEX);
        $width = $this->getWidth();
        $height = $this->getHeight();
        $color = \imagecolorallocate($this->resource, $rgb['red'], $rgb['green'], $rgb['blue']);
        $x1 = 0;
        $y1 = 0;
        $x2 = $width - 1;
        $y2 = $height - 1;
        for($i = 0; $i < $thickness; $i++){
            \imagerectangle($this->resource, $x1++, $y1++, $x2--, $y2--, $color);
        }
        return $this;
    }

    /**
     * Resim üzerine yazı ekler.
     *
     * @param string $text
     * @param array $options
     * @return $this
     */
    public function text(string $text, array $options = []): self
    {
        if(isset($options['font']) && \is_string($options['font'])){
            $font = $options['font'];
        }else{
            $font = $this->textOptions['font'];
        }

        if(isset($options['size']) && \is_int($options['size'])){
            $size = $options['size'];
        }else{
            $size = $this->textOptions['size'];
        }

        if(isset($options['angle']) && \is_numeric($options['angle'])){
            $angle = $options['angle'];
        }else{
            $angle = $this->textOptions['angle'];
        }
        $shadow = $options['shadow'] ?? $this->textOptions['shadow'];

        $coordinate = $this->textAlignCoordinate($text, $options);

        $textColorDefault = $this->hextoRGB($this->textOptions['color']);
        if(isset($options['color'])){
            if(\is_array($options['color'])){
                $textColor = $options['color'];
            }elseif(\is_string($options['color'])){
                $textColor = $this->hextoRGB($options['color']);
            }
        }else{
            $textColor = $textColorDefault;
        }

        $red = $textColor['red'] ?? $textColorDefault['red'];
        $green = $textColor['green'] ?? $textColorDefault['green'];
        $blue = $textColor['blue'] ?? $textColorDefault['blue'];

        $color = \imagecolorallocate($this->resource, $red, $green, $blue);

        if($shadow === TRUE){
            $shadowColor = $this->hextoRGB(($options['shadow_color'] ?? $this->textOptions['shadow_color']));
            \imagettftext($this->resource,
                $size,
                $angle,
                $coordinate['x'] - 1,
                $coordinate['y'] - 1,
                (\imagecolorallocate($this->resource, $shadowColor['red'], $shadowColor['green'], $shadowColor['blue'])),
                $font,
                $text);
        }
        \imagettftext($this->resource, $size, $angle, $coordinate['x'], $coordinate['y'], $color, $font, $text);

        return $this;
    }



    /**
     * Bir resmin üzerine bir filigran ekler.
     *
     * @param string|Image $image
     * @param array $options
     * @return $this
     */
    public function watermark($image, array $options = []): self
    {
        if(\is_string($image)){
            $info = $this->fileInfo($image);
            switch($info[2]){
                case \IMAGETYPE_JPEG:
                    $resource = \imagecreatefromjpeg($image);
                    break;
                case \IMAGETYPE_GIF:
                    $resource = \imagecreatefromgif($image);
                    break;
                case \IMAGETYPE_PNG:
                    $resource = \imagecreatefrompng($image);
                    break;
                case \IMAGETYPE_WEBP:
                    $resource = \imagecreatefromwebp($image);
                    break;
                default:
                    throw new RuntimeException('You can manipulate only jpeg, gif, png, webp files.');
            }
            if(\in_array($info[2], $this->supportTransparency, true)){
                \imagealphablending($resource, false);
                \imagesavealpha($resource, true);
            }
        }elseif($image instanceof Image){
            $info = [
                $image->getWidth(),
                $image->getHeight(),
                $image->getType(),
                'mime' => $image->getMime(),
            ];
            $resource = $image->getResource();
        }else{
            throw new InvalidArgumentException('Only string (image path) or \ImageManipulation\Image object. ');
        }

        $mime = $info['mime'] ?? 'image/jpg';
        $width = $info[0];
        $height = $info[1];
        $type = $info[2];

        $sourceWidth = $this->getWidth();
        $sourceHeight = $this->getHeight();
        $sourceXCenter = $sourceWidth / 2;
        $sourceYCenter = $sourceHeight / 2;

        $xAxis = 0;
        $yAxis = 0;

        $position = $options['position'] ?? $this->watermark['position'];
        $left = $options['left'] ?? $this->watermark['left'];
        $right = $options['right'] ?? $this->watermark['right'];
        $bottom = $options['bottom'] ?? $this->watermark['bottom'];
        $top = $options['top'] ?? $this->watermark['top'];
        $opacity = $options['opacity'] ?? $this->watermark['opacity'];
        $opacity = ($opacity <= 1 && $opacity >= 0) ? ($opacity * 100) : 100;


        $reWidth = $options['width'] ?? ($this->watermark['width'] ?? $info[0]);
        $reHeight = $options['height'] ?? ($this->watermark['height'] ?? $info[1]);

        if($reWidth !== $width || $reHeight !== $height){
            $ratio = $width / $height;
            if(($reWidth / $reHeight) > $ratio){
                $reWidth = ($reHeight * $ratio);
            }else{
                $reHeight = ($reWidth / $ratio);
            }
            $newWatermark = \imagecreatetruecolor((int)$reWidth, (int)$reHeight);
            if(\in_array($type, $this->supportTransparency, true)){
                $bgColor = \imagecolorallocatealpha($newWatermark, 255, 255, 255, 127);
                \imagefill($newWatermark, 0, 0, $bgColor);
                \imagealphablending($newWatermark, false);
                \imagesavealpha($newWatermark, true);
            }
            \imagecopyresampled($newWatermark, $resource, 0, 0, 0, 0, (int)$reWidth, (int)$reHeight, (int)$width, (int)$height);
            \imagedestroy($resource);
            $resource = $newWatermark;
            \imagedestroy($newWatermark);
            $width = $reWidth;
            $height = $reHeight;
        }

        switch($position){
            case self::ALIGN_NO:
            case self::ALIGN_LEFT_TOP:
                $xAxis = $left;
                $yAxis = $top;
                break;
            case self::ALIGN_CENTER_TOP:
                $xAxis = ($sourceXCenter - ($width / 2));
                $yAxis = $top;
                break;
            case self::ALIGN_RIGHT_TOP:
                $xAxis = ($sourceWidth - $width) - $right;
                $yAxis = $top;
                break;
            case self::ALIGN_LEFT_CENTER:
                $xAxis = $left;
                $yAxis = ($sourceYCenter - ($height / 2));
                break;
            case self::ALIGN_CENTER_CENTER:
                $xAxis = ($sourceXCenter - ($width / 2));
                $yAxis = ($sourceYCenter - ($height / 2));
                break;
            case self::ALIGN_RIGHT_CENTER:
                $xAxis = $sourceWidth - ($width + $right);
                $yAxis = $sourceYCenter - ($height / 2);
                break;
            case self::ALIGN_LEFT_BOTTOM:
                $xAxis = $left;
                $yAxis = $sourceHeight - ($height + $bottom);
                break;
            case self::ALIGN_CENTER_BOTTOM:
                $xAxis = $sourceXCenter - ($width / 2);
                $yAxis = $sourceHeight - ($height + $bottom);
                break;
            case self::ALIGN_RIGHT_BOTTOM:
                $xAxis = $sourceWidth - ($width + $right);
                $yAxis = $sourceHeight - ($height + $bottom);
                break;
            default:
                throw new RuntimeException('Alignment not understood.');
        }
        \imagecopymerge($this->resource, $resource, (int)$xAxis, (int)$yAxis, 0, 0, $width, $height, (int)$opacity);
        \imagedestroy($resource);
        return $this;
    }

    /**
     * Tanımlanmış resmin genişliğini döndürür.
     *
     * @return int
     */
    public function getWidth(): int
    {
        return (int) \imagesx($this->resource);
    }

    /**
     * Tanımlanmış resmin genişliğini döndürür.
     *
     * @return int
     */
    public function getHeight(): int
    {
        return (int) \imagesy($this->resource);
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    public function getType(): int
    {
        return $this->type;
    }


    /**
     * Yapılan değişiklikleri kaydeder.
     *
     * @param string|null $path <p>Kaydedilecek dosya yolu. NULL ise tanımlanmış resim değiştirilir.</p>
     * @param int $quality <p>0-100 arasında JPG, PNG ve WEBP resimleri için kalite.</p>
     * @return bool
     */
    public function save(?string $path = null, int $quality = 90): bool
    {
        if($path === null){
            $path = $this->path;
        }else{
            if($this->dirOrFile($path) === 'dir'){
                $path = \rtrim($path, '/') . '/';
                if(!\is_dir($path)){
                    \mkdir($path, 0755);
                }
                $path .= \basename($this->path);
            }
        }
        if($quality > 100){
            $quality = 100;
        }
        if($this->convert === null){
            $saveType = $this->type;
        }else{
            $saveType = $this->convert;
        }
        switch($this->type){
            case \IMAGETYPE_JPEG:
                $save = \imagejpeg($this->resource, $path, $quality);
                break;
            case \IMAGETYPE_GIF:
                $save = \imagegif($this->resource, $path);
                break;
            case \IMAGETYPE_PNG:
                $quality = ($quality > 90) ? 90 : $quality;
                $quality = ($quality > 0) ? (9 - \ceil($quality / 10)) : $quality;
                $save = \imagepng($this->resource, $path, (int)$quality);
                break;
            case \IMAGETYPE_WEBP:
                $save = \imagewebp($this->resource, $path, $quality);
                break;
            default:
                throw new RuntimeException('You can manipulate only jpeg, gif, png, webp files.');
        }
        \imagedestroy($this->resource);
        return $save;
    }

    protected function setResource($resource)
    {
        if($this->resource !== null){
            \imagedestroy($this->resource);
        }
        $this->resource = $resource;
    }

    public function getResource()
    {
        if($this->resource !== null){
            return $this->resource;
        }
        switch($this->type){
            case \IMAGETYPE_JPEG:
                $this->resource = \imagecreatefromjpeg($this->path);
                break;
            case \IMAGETYPE_GIF:
                $this->resource = \imagecreatefromgif($this->path);
                break;
            case \IMAGETYPE_PNG:
                $this->resource = \imagecreatefrompng($this->path);
                break;
            case \IMAGETYPE_WEBP:
                $this->resource = \imagecreatefromwebp($this->path);
                break;
            default:
                throw new RuntimeException('You can manipulate only jpeg, gif, png, webp files.');
        }
        if(\in_array($this->type, $this->supportTransparency, true)){
            \imagealphablending($this->resource, false);
            \imagesavealpha($this->resource, true);
        }
        return $this->resource;
    }

    protected function getPathCalc(string $name, ?string $dir = null): string
    {
        if($dir === null){
            $dir = \rtrim(\dirname($this->path), \DIRECTORY_SEPARATOR);
        }
        $dir = \rtrim($dir, '/') . '/';
        if(!\is_dir($dir)){
            \mkdir($dir,0755);
        }
        if(\strpos($name, '.') === FALSE && $this->extension !== null){
            $name .= '.' . $this->extension;
        }
        $name = \ltrim($name, '/');
        return $dir . $name;
    }

    protected function dirOrFile(string $path): string
    {
        if(\is_dir($path)){
            return 'dir';
        }elseif(\is_file($path)){
            return 'file';
        }else{
            $info = \pathinfo($path);
            return isset($info['extension']) ? 'file' : 'dir';
        }
    }

    protected function textAlignCoordinate(string $text, array $options = []): array
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $len = \strlen($text);

        $size = $options['size'] ?? $this->textOptions['size'];
        $font = $options['font'] ?? $this->textOptions['font'];
        $angle = $options['angle'] ?? $this->textOptions['angle'];
        $bbox = \imagettfbbox($size, $angle, $font, $text);
        $fontWidth = $bbox[0] + $bbox[2];
        $fontHeight = \abs($bbox[1] + $bbox[5]);

        $background = $options['background'] ?? $this->textOptions['background'];
        if($background !== null){
            $padding_x = $options['padding_x'] ?? $this->textOptions['padding_x'];
            $padding_y = $options['padding_y'] ?? $this->textOptions['padding_y'];

            $bg = $this->hextoRGB($background);
            $bgColor = \imagecolorallocate($this->resource, $bg['red'], $bg['green'], $bg['blue']);
        }

        $left = $options['left'] ?? $this->textOptions['left'];
        $right = $options['right'] ?? $this->textOptions['right'];
        $top = $options['top'] ?? $this->textOptions['top'];
        $bottom = $options['bottom'] ?? $this->textOptions['bottom'];


        $x_center = \ceil(($width - $fontWidth) / 2);
        $y_center = \ceil(($height - $fontHeight) / 2);

        $align = $options['align'] ?? $this->textOptions['align'];

        switch($align){
            case self::ALIGN_LEFT_TOP:
            case self::ALIGN_NO:
                $x = $left;
                $y = $fontHeight + $top;
                break;
            case self::ALIGN_CENTER_TOP:
                $x = $x_center;
                $y = $fontHeight + $top;
                break;
            case self::ALIGN_RIGHT_TOP:
                $x = ($width - $fontWidth) - $right;
                $y = $fontHeight + $top;
                break;
            case self::ALIGN_LEFT_CENTER:
                $x = $left;
                $y = $y_center;
                break;
            case self::ALIGN_CENTER_CENTER:
                $x = $x_center;
                $y = $y_center;
                break;
            case self::ALIGN_RIGHT_CENTER:
                $x = ($width - $fontWidth) - $right;
                $y = $y_center;
                break;
            case self::ALIGN_LEFT_BOTTOM:
                $x = $left;
                $y = ($height - $fontHeight) - $bottom;
                break;
            case self::ALIGN_CENTER_BOTTOM:
                $x = $x_center;
                $y = ($height - $fontHeight) - $bottom;
                break;
            case self::ALIGN_RIGHT_BOTTOM:
                $x = ($width - $fontWidth) - $right;
                $y = ($height - $fontHeight) - $bottom;
                break;
            default: $x = 0; $y = 0;
        }

        if(isset($options['x']) && \is_int($options['x'])){
            $x = $x + $options['x'];
        }else{
            $x = $x + $this->textOptions['coordinateX'];
        }
        if(isset($options['y']) && \is_int($options['y'])){
            $y = $y + $options['y'];
        }else{
            $y = $y + $this->textOptions['coordinateY'];
        }
        if($background !== null){
            $bgX = $x;
            $bgY = $y - $fontHeight;
            $bgAxisX = $x + $fontWidth;
            $bgAxisY = $bgY + $fontHeight;

            if($padding_x > 0){
                $bgX -= $padding_x;
                $bgAxisX += $padding_x;
            }
            if($padding_y > 0){
                $bgY -= $padding_y;
                $bgAxisY += $padding_y;
            }
            \imagefilledrectangle($this->resource,
                (int)($bgX),
                (int)($bgY),
                (int)$bgAxisX,
                (int)$bgAxisY,
                $bgColor);
        }
        return [
            'x'     => (int)$x,
            'y'     => (int)$y,
        ];
    }

    protected function hextoRGB(string $hex): array
    {
        $hex = \trim(\ltrim($hex, '#'));
        $len = \strlen($hex);
        if($len === 3){
            $hex = $hex . $hex;
        }
        if(\strlen($hex) !== 6){
            throw new InvalidArgumentException('The HEX code must be 6 characters.');
        }
        $red = \hexdec($hex[0] . $hex[1]);
        $green = \hexdec($hex[2] . $hex[3]);
        $blue = \hexdec($hex[4] . $hex[5]);

        return [
            'red'   => (int)$red,
            'green' => (int)$green,
            'blue'  => (int)$blue
        ];
    }

    protected function fileInfo(string $path): array
    {
        if(!\is_file($path)){
            throw new RuntimeException($path . ' not found.');
        }

        if(($info = \getimagesize($path)) === FALSE){
            throw new RuntimeException('The image could not be resolved.');
        }

        if(!isset($info['mime'])){
            $types = [
                \IMAGETYPE_JPEG => 'jpeg',
                \IMAGETYPE_WEBP => 'webp',
                \IMAGETYPE_PNG => 'png',
                \IMAGETYPE_GIF => 'gif',
            ];
            $info['mime'] = 'image/' . $types[$info[2]] ?? 'jpg';
        }

        return $info;
    }

}
