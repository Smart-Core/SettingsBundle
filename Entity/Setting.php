<?php

namespace SmartCore\Bundle\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="settings",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"bundle", "name"}),
 *      }
 * )
 *
 * @UniqueEntity(fields={"bundle", "name"}, message="В каждом бандле должены быть уникальные ключи")
 */
class Setting extends SettingModel
{
}
