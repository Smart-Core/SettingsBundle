<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\Listener;

use SmartCore\Bundle\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class WarmupDatabaseListener
{
    /** @var  */
    protected $settingsManager;

    /** @var bool */
    protected $debug;

    /** @var string */
    protected $cacheDir;

    /**
     * WarmupDatabaseListener constructor.
     *
     * @param SettingsManager $settingsManager
     * @param string          $cacheDir
     * @param bool            $debug
     */
    public function __construct(SettingsManager $settingsManager, string $cacheDir, bool $debug)
    {
        $this->cacheDir = $cacheDir;
        $this->debug    = $debug;
        $this->settingsManager = $settingsManager;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onRequest(GetResponseEvent $event): void
    {
        if ($this->debug and HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $lockFile = $this->cacheDir.'/'.SettingsManager::LOCK_FILE;

            if (file_exists($lockFile)) {
                $this->settingsManager->warmupDatabase();

                unlink($lockFile);
            }
        }
    }
}
