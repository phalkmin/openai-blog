<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit078d42ba981ac1677617e1d6fe549438
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'Orhanerday\\OpenAi\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Orhanerday\\OpenAi\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
            1 => __DIR__ . '/..' . '/orhanerday/open-ai/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Orhanerday\\OpenAi\\OpenAi' => __DIR__ . '/..' . '/orhanerday/open-ai/src/OpenAi.php',
        'Orhanerday\\OpenAi\\Url' => __DIR__ . '/..' . '/orhanerday/open-ai/src/Url.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit078d42ba981ac1677617e1d6fe549438::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit078d42ba981ac1677617e1d6fe549438::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit078d42ba981ac1677617e1d6fe549438::$classMap;

        }, null, ClassLoader::class);
    }
}