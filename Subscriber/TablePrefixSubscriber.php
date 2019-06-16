<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * @deprecated
 */
class TablePrefixSubscriber implements EventSubscriber
{
    protected $prefix = '';

    public function __construct($prefix)
    {
        $this->prefix = (string) $prefix;
    }

    public function getSubscribedEvents()
    {
        return ['loadClassMetadata'];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $classMetadata = $args->getClassMetadata();
        if ($classMetadata->namespace == 'SmartCore\Bundle\SettingsBundle\Entity') {
            $classMetadata->setTableName($this->prefix.$classMetadata->getTableName());
        }
    }
}
