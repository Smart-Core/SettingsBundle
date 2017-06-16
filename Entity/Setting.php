<?php

namespace SmartCore\Bundle\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="settings",
 *      indexes={
 *          @ORM\Index(columns={"category"}),
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"bundle", "name"}),
 *      }
 * )
 *
 * @UniqueEntity(fields={"bundle", "name"}, message="Each bandle must have unique keys")
 */
class Setting extends SettingModel
{
}
