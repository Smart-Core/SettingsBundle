<?php

namespace SmartCore\Bundle\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SmartCore\Bundle\SettingsBundle\Model\SettingPersonalModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="settings_personal",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"setting_id", "user_id"}),
 *      }
 * )
 *
 * @UniqueEntity(fields={"setting", "user"}, message="Возможна только одна настройка для каждого пользователя.")
 */
class SettingPersonal extends SettingPersonalModel
{
}
