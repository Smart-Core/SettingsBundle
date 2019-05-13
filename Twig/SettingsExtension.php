<?php

namespace SmartCore\Bundle\SettingsBundle\Twig;

use SmartCore\Bundle\SettingsBundle\Manager\SettingsManager;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
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
            new TwigFunction('setting',  [$this, 'getSetting']),
            new TwigFunction('is_setting',  [$this, 'isSettingEquals']),
            new TwigFunction('is_setting_bool',  [$this, 'isSettingBool']),
            new TwigFunction('is_setting_choice',  [$this, 'isSettingChoice']),
            new TwigFunction('get_setting_choice_title',  [$this, 'getSettingChoiceTitle']),
            new TwigFunction('get_setting_option',  [$this, 'getSettingOption']),
            new TwigFunction('get_setting_value_as_string',  [$this, 'getSettingValueAsString']),
            new TwigFunction('is_settings_show_bundle_column',  [$this, 'isSettingsShowBundleColumn']),
            new TwigFunction('has_setting_personal',  [$this, 'hasSettingPersonal']),
        ];
    }

    /**
     * @param string $pattern
     * @param true $array_flip
     *
     * @return string
     */
    public function getSetting($pattern, $array_flip = false)
    {
        $result = $this->settingsManager->get($pattern);

        if ($array_flip and is_array($result)) {
            $result = array_flip($result);
        }

        return $result;
    }

    /**
     * @param string $pattern
     * @param string $value
     *
     * @return bool
     */
    public function isSettingEquals($pattern, $value)
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
     *
     * @return bool
     */
    public function isSettingChoice(SettingModel $setting)
    {
        $settingConfig = $this->settingsManager->getSettingConfig($setting);

        if (is_array($settingConfig) and isset($settingConfig['type']) and $settingConfig['type'] == 'ChoiceType') {
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
     * @param SettingModel $setting
     * @param string|null  $value
     *
     * @return string
     */
    public function getSettingChoiceTitle(SettingModel $setting, $value = null)
    {
        return $this->settingsManager->getSettingChoiceTitle($setting, $value);
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function hasSettingPersonal(SettingModel $setting)
    {
        return $this->settingsManager->hasSettingPersonal($setting);
    }

    /**
     * @param array|string $value
     *
     * @return string
     */
    public function getSettingValueAsString($value)
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'smart_core_settings_twig_extension';
    }
}
