<?php
namespace app\service\game;

use think\App;

class GamePlatformFactory
{
    public static function getPlatformService($platform, $config, $data)
    {
        $class = "app\\service\\game\\{$platform}PlatformService";
        if (class_exists($class)) {
            return new $class($config, $data);
        } else {
            throw new \Exception("Unknown platform: $platform");
        }
    }
}