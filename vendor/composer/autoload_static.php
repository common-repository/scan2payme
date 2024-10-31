<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3fcc8221f28f1da38e727b39772243b1
{
    public static $prefixLengthsPsr4 = array (
        'c' => 
        array (
            'chillerlan\\Settings\\' => 20,
            'chillerlan\\QRCode\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'chillerlan\\Settings\\' => 
        array (
            0 => __DIR__ . '/..' . '/chillerlan/php-settings-container/src',
        ),
        'chillerlan\\QRCode\\' => 
        array (
            0 => __DIR__ . '/..' . '/chillerlan/php-qrcode/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3fcc8221f28f1da38e727b39772243b1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3fcc8221f28f1da38e727b39772243b1::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3fcc8221f28f1da38e727b39772243b1::$classMap;

        }, null, ClassLoader::class);
    }
}
