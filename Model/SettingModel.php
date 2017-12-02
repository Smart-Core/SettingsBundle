<?php

namespace SmartCore\Bundle\SettingsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Validator\Constraints as Assert;

abstract class SettingModel
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\UpdatedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32, nullable=false)
     * @Assert\NotBlank()
     */
    protected $bundle;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $category;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, nullable=false)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_serialized;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":0})
     */
    protected $is_hidden;

    /**
     * @var SettingHistoryModel[]
     *
     * @ORM\OneToMany(targetEntity="SettingHistory", mappedBy="setting", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $history;

    /**
     * SettingModel constructor.
     */
    public function __construct()
    {
        $this->category      = 'default';
        $this->created_at    = new \DateTime();
        $this->is_serialized = false;
        $this->is_hidden     = false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->bundle.':'.$this->name;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdateEvent()
    {
        $this->updated_at = new \DateTime();
    }

    /**
     * @param string $bundle
     *
     * @return $this
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $this->is_serialized = true;
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
        return $this->is_serialized ? unserialize($this->value) : $this->value;
    }

    /**
     * @return string
     */
    public function getValueAsString()
    {
        $str = $this->is_serialized ? unserialize($this->value) : $this->value;

        if (is_array($str)) {
            $str = implode(', ', $str);
        }

        return $str;
    }

    /**
     * @return boolean
     */
    public function getIsSerialized(): bool
    {
        return $this->is_serialized;
    }

    /**
     * @return boolean
     */
    public function isSerialized(): bool
    {
        return $this->is_serialized;
    }

    /**
     * @param boolean $is_serialized
     *
     * @return $this
     */
    public function setIsSerialized($is_serialized)
    {
        $this->is_serialized = $is_serialized;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->is_hidden;
    }

    /**
     * @param bool $is_hidden
     *
     * @return $this
     */
    public function setIsHidden($is_hidden)
    {
        $this->is_hidden = $is_hidden;

        return $this;
    }

    /**
     * @return SettingHistoryModel[]
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param SettingHistoryModel[] $history
     *
     * @return $this
     */
    public function setHistory($history)
    {
        $this->history = $history;

        return $this;
    }
}
