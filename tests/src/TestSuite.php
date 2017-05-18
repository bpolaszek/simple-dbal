<?php

namespace BenTools\SimpleDBAL\Tests;

use BenTools\SimpleDBAL\Contract\CredentialsInterface;
use BenTools\SimpleDBAL\Model\Credentials;
use Symfony\Component\Yaml\Yaml;

class TestSuite
{

    const CONFIG_DIR = __DIR__ . '/../config';

    /**
     * @param $fileName
     * @return string
     */
    public static function getConfigFile($fileName)
    {
        return sprintf('%s/%s', self::CONFIG_DIR, $fileName);
    }

    /**
     * @return array
     */
    public static function getSettings(): array
    {
        $settingsFile = TestSuite::getConfigFile('settings.yml');
        if (!is_readable($settingsFile)) {
            throw new \RuntimeException("settings.yml is not readable.");
        }
        $settings = Yaml::parse(file_get_contents($settingsFile))['parameters'];
        return $settings;
    }

    /**
     * @return CredentialsInterface
     */
    public static function getCredentialsFromSettings(array $settings): CredentialsInterface
    {
        return new Credentials($settings['database_host'], $settings['database_user'], $settings['database_password'], $settings['database_name'], $settings['database_platform'], $settings['database_port']);
    }
}
