<?php

namespace SmartCore\Bundle\SettingsBundle\Twig;

use SmartCore\Bundle\SettingsBundle\Manager\SettingsManager;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;

class SettingsExtension extends \Twig_Extension
{
    /** @var SettingsManager */
    protected $settingsManager;

    /**
     * @param SettingsManager $settingsManager
     */
    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('setting',  [$this, 'getSetting']),
            new \Twig_SimpleFunction('is_setting',  [$this, 'isSetting']),
            new \Twig_SimpleFunction('is_setting_bool',  [$this, 'isSettingBool']),
            new \Twig_SimpleFunction('get_setting_option',  [$this, 'getSettingOption']),
            new \Twig_SimpleFunction('is_settings_show_bundle_column',  [$this, 'isSettingsShowBundleColumn']),
        ];
    }

    /**
     * @param string $pattern
     *
     * @return string
     */
    public function getSetting($pattern)
    {
        return $this->settingsManager->get($pattern);
    }

    /**
     * @param string $pattern
     * @param string $value
     *
     * @return bool
     */
    public function isSetting($pattern, $value)
    {
        if ($this->settingsManager->get($pattern) == $value) {
            return true;
        }

        return false;
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function isSettingBool(SettingModel $setting)
    {
        $settingConfig = $this->settingsManager->getSettingConfig($setting);

        if (is_array($settingConfig) and isset($settingConfig['type']) and $settingConfig['type'] == 'CheckboxType') {
            return true;
        }

        return false;
    }

    /**
     * @param SettingModel $setting
     * @param string       $option
     *
     * @return mixed|null
     */
    public function getSettingOption(SettingModel $setting, $option)
    {
        return $this->settingsManager->getSettingOption($setting, $option);
    }

    /**
     * @return bool
     */
    public function isSettingsShowBundleColumn()
    {
        return $this->settingsManager->isSettingsShowBundleColumn();
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'smart_core_settings_twig_extension';
    }
}
