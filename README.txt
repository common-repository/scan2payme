=== scan2payme ===
Contributors: awaldherr
Donate link: 
Tags: woocommerce, payment, qr code, bank transfer, girocode
Requires at least: 6.4
Tested up to: 6.6.1
Stable tag: 1.0.3
Requires PHP: 8.0
License: MIT
License URI: https://opensource.org/license/mit/

Plugin for displaying payment QR-Codes in WooCommerce order pages.

== Description ==

This plugin generates QR-Codes containing the banking details of your shop and displays them in the WooCommerce order status page. Your customers can scan this code with their banking app to initiate a SEPA bank transfer without typing. 

== Screenshots ==

1. QR-Code on the WooCommerce order page
2. Preview of the screenshot in the settings page
3. All available settings

== Frequently Asked Questions ==

= How can my customers use this QR-Code? =

Your customers need a banking application capable of reading EPC-QR-Codes. Which such an application, they can initiate a SEPA-transfer without typing in your banking details, the amount or the reference number.

= How can I match a bank transfer with an order? =

By default, the reference number in the transfer will be equal to the WooCommerce order number. If you need additional customization for this field, feel free to contact me.

== Changelog ==

= 1.0 =
* Initial release.

= 1.0.1 =
* You can now directly use the BACS accounts from WooCommerce

= 1.0.2 =
* added some screenshots
* fixed errors in README.txt

= 1.0.3 =
* instantly show banking details when selecting a WooCommerce BACS account in settings
* added logo for plugin directory
* fixed typos in settings page

== European Payment Council (EPC) QR-Code ==

More information on the EPC QR-Code: [https://www.europeanpaymentscouncil.eu/document-library/guidance-documents/standardisation-qr-codes-mscts](https://www.europeanpaymentscouncil.eu/document-library/guidance-documents/standardisation-qr-codes-mscts)
