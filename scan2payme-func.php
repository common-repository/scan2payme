<?php
namespace scan2payme;
defined( 'ABSPATH' ) || exit;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRDataInterface;

function generate_and_output_qr_code($option_textabove, $option_textunder, $epc_version, $epc_encoding, $epc_identity, $epc_bic, $epc_name, $epc_iban, $epc_total, $epc_use, $epc_ref, $epc_textref, $epc_hint){
    // only one line can be filled, ref or textref!
    if(strlen($epc_ref) > 0 && strlen($epc_textref) > 0){
        $epc_textref = "";
    }

    $qrdata = "BCD".PHP_EOL.$epc_version.PHP_EOL.$epc_encoding.PHP_EOL.$epc_identity.PHP_EOL.$epc_bic.PHP_EOL.$epc_name.PHP_EOL.$epc_iban.PHP_EOL.$epc_total.PHP_EOL.$epc_use.PHP_EOL.$epc_ref.PHP_EOL.$epc_textref.PHP_EOL.$epc_hint;
    $text_under_display = "";
    if(strlen($option_textunder) > 0){
        $text_under_display = htmlentities($option_textunder);
    }
    $text_above_display = "";
    if(strlen($option_textabove) > 0){
        $text_above_display = htmlentities($option_textabove);
    }

    $plainQRCodeOptions = new QROptions;
    $plainQRCodeOptions->version          = calculate_qr_version_for_data($qrdata, QRCode::ECC_H);
    $plainQRCodeOptions->eccLevel         = QRCode::ECC_H;
    $plainQRCodeOptions->imageBase64      = true;
    $plainQRCodeOptions->scale            = 5;
    $plainQRCodeOptions->imageTransparent = false; 
    $plainQRCode = new QRCode($plainQRCodeOptions);
    $logo_path = scan2payme_get_physical_logo_path();
    $logo_qr_created = false;
    if(isset($logo_path) && strlen($logo_path) > 0){
        try{
            // if logo is set, add logo
            $logoOptions = new QROptions;
            $logoOptions->version          = calculate_qr_version_for_data($qrdata, QRCode::ECC_H);
            $logoOptions->eccLevel         = QRCode::ECC_H;
            $logoOptions->imageBase64      = true;
            $logoOptions->scale            = 5;
            $logoOptions->imageTransparent = false; 
            $logoQRImage = new LogoQRImage($logoOptions, $plainQRCode->getMatrix($qrdata));
            // TODO make size 13/13 configurable
            $imgData = $logoQRImage->add_logo_to_qrimage($logo_path, 13, 13);
            $logo_qr_created = true;
        }catch(Exception $e){
            // TODO inform admin somehow. 
        }
    }

    // create plain qr if no logo is set or it failed.
    if(!$logo_qr_created){
        $imgData = $plainQRCode->render($qrdata);
    }

    // output html
    ?>
    <section class="woocommerce-columns woocommerce-columns--1">
            <div class="woocommerce-column woocommerce-column--1 col-1">
                <span style="display:block;text-align:center;"><?php echo esc_html($text_above_display); ?></span>
                <img style="display:block;margin:auto;" src="<?php echo esc_attr($imgData); ?>" alt="<?php echo esc_html($qrdata); ?>" />
                <span style="display:block;text-align:center;"><?php echo esc_html($text_under_display); ?></span>
            </div><!-- /.col-1 -->
    </section>
    <?php
}
