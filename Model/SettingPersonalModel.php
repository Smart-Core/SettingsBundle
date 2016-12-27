<?php

namespace SmartCore\Bundle\SettingsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Validator\Constraints as Assert;

abstract class SettingPersonalModel
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\FosUser;
    use ColumnTrait\UpdatedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @var SettingModel
     *
     * @ORM\ManyToOne(targetEntity="Setting", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $setting;

    /**
     * Unmapped
     *
     * @var bool
     */
    protected $use_default = true;

    /**
     * Constructor.
     */
    public function __construct(SettingModel $setting = null)
    {
        $this->created_at    = new \DateTime();
        $this->use_default   = true;

        if ($setting) {
            $this->setting = $setting;
            $this->setValue($setting->getValue());
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->setting->getName();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdateEvent()
    {
        $this->updated_at = new \DateTime();
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
     * @return string
     */
    public function getValue()
    {
        return $this->setting->getIsSerialized() ? unserialize($this->value) : $this->value;
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
    public function setSetting($setting)
    {
        $this->setting = $setting;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUseDefault(): bool
    {
        return $this->use_default;
    }

    /**
     * @param bool $use_default
     *
     * @return $this
     */
    public function setUseDefault($use_default)
    {
        $this->use_default = $use_default;

        return $this;
    }
}
