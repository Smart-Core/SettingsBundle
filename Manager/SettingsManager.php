<?php

namespace SmartCore\Bundle\SettingsBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Exception\TableNotFoundException;
use SmartCore\Bundle\SettingsBundle\Cache\DummyCacheProvider;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class SettingsManager
{
    use ContainerAwareTrait;

    /** @var \Doctrine\ORM\EntityManager $em */
    protected $em;

    /** @var \Doctrine\ORM\EntityRepository */
    protected $settingsRepo;

    /** @var CacheProvider */
    protected $cache;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $container->get('doctrine.orm.entity_manager');
        $cache_provider  = $container->getParameter('smart_core.settings.doctrine_cache_provider');

        if (!empty($cache_provider) and $container->has('doctrine_cache.providers'.$cache_provider)) {
            $this->cache = $container->get('doctrine_cache.providers'.$cache_provider);
        } else {
            $this->cache = new DummyCacheProvider();
        }
    }

    /**
     * Lazy repository initialization.
     */
    protected function initRepo()
    {
        if (null === $this->settingsRepo) {
            $this->settingsRepo = $this->container->get('doctrine.orm.entity_manager')->getRepository('SmartSettingsBundle:Setting');
        }
    }

    /**
     * @param string|null $bundle
     *
     * @return array
     */
    public function all($bundle = null)
    {
        $this->initRepo();

        $criteria = $bundle ? ['bundle' => $bundle] : [];

        return $this->settingsRepo->findBy($criteria, ['bundle' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * @param int $id
     *
     * @return SettingModel|null
     */
    public function findById($id)
    {
        $this->initRepo();

        return $this->settingsRepo->find($id);
    }

    /**
     * @param string      $bundle
     * @param string|null $name
     *
     * @return mixed
     */
    public function get($bundle, $name = null)
    {
        if (empty($name)) {
            $parts = explode(':', $bundle, 2);

            if (count($parts) !== 2) {
                throw new \Exception('Wrong setting name: "'.$bundle.'"');
            }

            $bundle = $parts[0];
            $name   = $parts[1];
        }

        $cache_key = $this->getCacheKey($bundle, $name);

        if (false == $setting = $this->cache->fetch($cache_key)) {
            $this->initRepo();

            try {
                $setting = $this->settingsRepo->findOneBy([
                    'bundle' => $bundle,
                    'name'   => $name,
                ]);

                if (empty($setting)) {
                    throw new \Exception('Wrong bundle-key pair in setting. (Bundle: '.$bundle.', Key name: '.$name.')');
                }
            } catch (TableNotFoundException $e) {
                if ($this->container->getParameter('kernel.debug')) {
                    // @todo remove
                    echo "TableNotFoundException for Bundle: $bundle, Key name: $name\n";
                }

                return null;
            }

            $this->cache->save($cache_key, $setting);
        }

        return $setting->getValue();
    }

    /**
     * @param string $bundle
     * @param string $name
     *
     * @return string
     */
    protected function getCacheKey($bundle, $name)
    {
        return md5('smart_setting'.$bundle.$name);
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function updateEntity(SettingModel $setting)
    {
        $this->em->persist($setting);
        $this->em->flush($setting);

        $this->cache->delete($this->getCacheKey($setting->getBundle(), $setting->getName()));

        return true;
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function removeEntity(SettingModel $setting)
    {
        $this->em->remove($setting);
        $this->em->flush($setting);

        $this->cache->delete($this->getCacheKey($setting->getBundle(), $setting->getName()));

        return true;
    }

    /**
     * @param string       $bundle
     * @param string       $name
     * @param string|array $value
     */
    public function createSetting($bundle, $name, $value)
    {
        $this->persistSetting(new Setting(), $bundle, $name, $value);
    }

    /**
     * @param SettingModel $setting
     * @param string       $bundle
     * @param string       $name
     * @param string|array $value
     */
    protected function persistSetting(SettingModel $setting, $bundle, $name, $value)
    {
        $setting
            ->setBundle($bundle)
            ->setName($name)
            ->setValue($value)
        ;

        $errors = $this->container->get('validator')->validate($setting);

        if (count($errors) > 0) {
            $this->em->detach($setting);
        } else {
            $this->em->persist($setting);
        }
    }

    /**
     * @param SettingModel $setting
     *
     * @return array
     * @throws \Exception
     */
    public function getSettingConfig(SettingModel $setting)
    {
        foreach ($this->container->getParameter('kernel.bundles') as $bundleName => $bundleClass) {
            /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
            $bundle = new $bundleClass();

            if ($bundle->getContainerExtension()->getAlias() != $setting->getBundle()) {
                continue;
            }

            $reflector = new \ReflectionClass($bundleClass);
            $settingsConfig = dirname($reflector->getFileName()).'/Resources/config/settings.yml';
            if (file_exists($settingsConfig)) {
                $settingsConfig = Yaml::parse(file_get_contents($settingsConfig));

                if (empty($settingsConfig)) {
                    continue;
                }

                if (!isset($settingsConfig[$setting->getName()])) {
                    $this->removeEntity($setting);

                    return [];
                }

                return $settingsConfig[$setting->getName()];
            }
        }

        return [];
    }

    /**
     * @return bool
     */
    public function isSettingsShowBundleColumn()
    {
        return $this->container->getParameter('smart_core.settings.show_bundle_column');
    }

    /**
     * @param SettingModel $setting
     * @param string       $option
     * @param mixed|null   $default
     *
     * @return mixed|null
     */
    public function getSettingOption(SettingModel $setting, $option, $default = null)
    {
        $settingConfig = $this->getSettingConfig($setting);

        if (is_array($settingConfig) and isset($settingConfig[$option])) {
            return $settingConfig[$option];
        }

        return $default;
    }
}
