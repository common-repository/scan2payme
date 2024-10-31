<?php
namespace scan2payme;
defined( 'ABSPATH' ) || exit;
use chillerlan\QRCode\Output\QRImage;

class LogoQRImage extends QRImage{
    public function add_logo_to_qrimage($logopath, $logoSpaceW, $logoSpaceH){
        if(!is_file($logopath)){
            return null;
        }

        $this->matrix->setLogoSpace($logoSpaceW, $logoSpaceH);
        
        $loadedLogo = imagecreatefrompng($logopath);
        $w = imagesx($loadedLogo);
        $h = imagesy($loadedLogo);

        $lw = ($logoSpaceW - 2) * $this->options->scale;
        $lh = ($logoSpaceH - 2) * $this->options->scale;
        $ql = $this->matrix->size() * $this->options->scale;

        // populate $this->image
        $this->dump();
        // copy into the center of the image
        imagecopyresampled($this->image, $loadedLogo, ($ql - $lw) / 2, ($ql - $lh) / 2, 0, 0, $lw, $lh, $w, $h);
        $imageData = $this->dumpImage();
        return 'data:image/'.$this->options->outputType.';base64,'.base64_encode($imageData);
    }
}
?>