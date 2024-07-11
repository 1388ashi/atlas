<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbd25f299f1d13aa0fd400f17086b797b
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Modules\\Admin\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Modules\\Admin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Modules\\Admin\\Database\\Seeders\\AdminDatabaseSeeder' => __DIR__ . '/../..' . '/Database/Seeders/AdminDatabaseSeeder.php',
        'Modules\\Admin\\Entities\\Admin' => __DIR__ . '/../..' . '/Entities/Admin.php',
        'Modules\\Admin\\Http\\Controllers\\AdminController' => __DIR__ . '/../..' . '/Http/Controllers/AdminController.php',
        'Modules\\Admin\\Providers\\AdminServiceProvider' => __DIR__ . '/../..' . '/Providers/AdminServiceProvider.php',
        'Modules\\Admin\\Providers\\RouteServiceProvider' => __DIR__ . '/../..' . '/Providers/RouteServiceProvider.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbd25f299f1d13aa0fd400f17086b797b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbd25f299f1d13aa0fd400f17086b797b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbd25f299f1d13aa0fd400f17086b797b::$classMap;

        }, null, ClassLoader::class);
    }
}