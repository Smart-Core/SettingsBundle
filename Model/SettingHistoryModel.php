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
        $this->created_at    = new \DateTime();

        if ($setting) {
            $this->setting = $setting;
        }
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

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
}
