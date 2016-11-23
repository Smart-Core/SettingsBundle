<?php

namespace SmartCore\Bundle\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SmartCore\Bundle\SettingsBundle\Model\SettingHistoryModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="settings_history")
 */
class SettingHistory extends SettingHistoryModel
{
}
