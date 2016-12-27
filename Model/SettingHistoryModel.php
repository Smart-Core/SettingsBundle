<?php

namespace SmartCore\Bundle\SettingsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;

class SettingHistoryModel
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\FosUser;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $is_personal;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @var SettingModel
     *
     * @ORM\ManyToOne(targetEntity="Setting", inversedBy="history", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $setting;

    /**
     * SettingHistoryModel constructor.
     *
     * @param SettingModel|null $setting
     */
    public function __construct(SettingModel $setting = null)
    {
        $this->created_at   = new \DateTime();
        $this->is_personal  = false;

        if ($setting) {
            $this->setting = $setting;
        }
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->setting->getIsSerialized() ? unserialize($this->value) : $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $this->value = serialize($value);
        } else {
            $this->value = $value;
        }

        return $this;
    }

    /**
     * @return SettingModel
     */
    public function getSetting(): SettingModel
    {
        return $this->setting;
    }

    /**
     * @param SettingModel $setting
     *
     * @return $this
     */
    public function setSetting(SettingModel $setting)
    {
        $this->setting = $setting;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsPersonal(): bool
    {
        return $this->is_personal;
    }

    /**
     * @param bool $is_personal
     *
     * @return $this
     */
    public function setIsPersonal($is_personal)
    {
        $this->is_personal = $is_personal;

        return $this;
    }
}
