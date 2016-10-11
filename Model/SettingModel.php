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
     * Constructor.
     */
    public function __construct()
    {
        $this->category      = 'default';
        $this->created_at    = new \DateTime();
        $this->is_serialized = false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->bundle.':'.$this->name;
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
     * @return boolean
     */
    public function isIsSerialized()
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
}
