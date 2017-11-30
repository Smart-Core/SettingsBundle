<?php

namespace SmartCore\Bundle\SettingsBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\Tools\SchemaValidator;
use FOS\UserBundle\Model\UserInterface;
use SmartCore\Bundle\SettingsBundle\Cache\DummyCacheProvider;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use SmartCore\Bundle\SettingsBundle\Entity\SettingHistory;
use SmartCore\Bundle\SettingsBundle\Entity\SettingPersonal;
use SmartCore\Bundle\SettingsBundle\Model\SettingHistoryModel;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use SmartCore\Bundle\SettingsBundle\Model\SettingPersonalModel;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Yaml\Yaml;

class SettingsManager
{
    use ContainerAwareTrait;

    /** @var \Doctrine\ORM\EntityManager $em */
    protected $em;

    /** @var \Doctrine\ORM\EntityRepository */
    protected $settingsRepo;

    /** @var \Doctrine\ORM\EntityRepository */
    protected $settingsPersonalRepo;

    /** @var \Doctrine\ORM\EntityRepository */
    protected $settingsHistoryRepo;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $settingsConfigRuntimeCache;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, CacheProvider $cache)
    {
        $this->cache     = $cache;
        $this->container = $container;
        $this->em        = $container->get('doctrine.orm.entity_manager');
        $this->settingsConfigRuntimeCache = [];
    }

    /**
     * Lazy repository initialization.
     *
     * @param bool $force
     */
    public function initRepo($force = false)
    {
        if (null === $this->settingsRepo or $force) {
            $this->settingsRepo         = $this->em->getRepository(Setting::class);
            $this->settingsHistoryRepo  = $this->em->getRepository(SettingHistory::class);
            $this->settingsPersonalRepo = $this->em->getRepository(SettingPersonal::class);
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
     * @param string  $bundle
     * @param string  $name
     * @param bool    $personal
     *
     * @return SettingModel|object|null
     */
    public function findBy($bundle, $name)
    {
        $this->initRepo();

        return $this->settingsRepo->findOneBy(['bundle' => $bundle, 'name' => $name]);
    }

    /**
     * @param $id
     *
     * @return SettingModel|null|object
     */
    public function findById($id)
    {
        $this->initRepo();

        return $this->settingsRepo->find($id);
    }

    /**
     * @param SettingModel $setting
     * @param int          $user
     *
     * @return SettingPersonalModel|object|null
     */
    public function findPersonal(SettingModel $setting, $user)
    {
        $this->initRepo();

        return $this->settingsPersonalRepo->findOneBy(['setting' => $setting, 'user' => $user]);
    }
    
    /**
     * @param int $id
     *
     * @return SettingHistoryModel|null
     */
    public function findHistoryById($id)
    {
        $this->initRepo();

        return $this->settingsHistoryRepo->find($id);
    }

    /**
     * @param string $pattern
     *
     * @return mixed
     */
    public function get($pattern)
    {
        $parts = explode(':', $pattern, 2);

        if (count($parts) !== 2) {
            throw new \Exception('Wrong setting name: "'.$pattern.'"');
        }

        $bundle = $parts[0];
        $name   = $parts[1];
        $userId = 0;

        $token = $this->container->get('security.token_storage')->getToken();
        if ($token instanceof TokenInterface and $token->getUser() instanceof UserInterface) {
            $userId = $token->getUser()->getId();
        }

        $cache_key = $this->getCacheKey($bundle, $name, $userId);

        if (false == $value = $this->cache->fetch($cache_key)) {
            $this->initRepo();

            try {
                $tryCount = 1;

                trygetsetting:

                $setting = $this->settingsRepo->findOneBy([
                    'bundle' => $bundle,
                    'name'   => $name,
                ]);

                if ($setting instanceof SettingModel) {
                    $value = $setting->getValue();

                    $settingPersonal = $this->settingsPersonalRepo->findOneBy(['setting' => $setting, 'user' => $userId]);

                    if (!empty($settingPersonal)) {
                        $value = $settingPersonal->getValue();
                    }
                } elseif ($tryCount == 1 and empty($setting)) {
                    $this->warmupDatabase();
                    $tryCount = 2;
                    goto trygetsetting;
                } elseif ($tryCount > 1) {
                    throw new \Exception('Wrong bundle-key pair in setting. (Bundle: '.$bundle.', Key name: '.$name.')');
                }
            } catch (TableNotFoundException $e) {
                if ($this->container->getParameter('kernel.debug')) {
                    // @todo remove
                    echo "TableNotFoundException for Bundle: $bundle, Key name: $name\n";
                }

                return null;
            }

            $this->cache->save($cache_key, $value);
        }

        return $value;
    }

    /**
     * @param string $bundle
     * @param string $name
     * @param int    $userId
     *
     * @return string
     */
    protected function getCacheKey($bundle, $name, $userId = 0)
    {
        return md5('smart_setting'.$bundle.$name.'_user_'.$userId);
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function updateEntity($entity)
    {
        if ($entity instanceof SettingModel) {
            return $this->updateEntitySetting($entity);
        } elseif ($entity instanceof SettingPersonalModel) {
            return $this->updateEntitySettingPersonal($entity);
        } else {
            throw new \Exception('Не поддеживается класс '.get_class($entity).' для сохранения универсальным методом "updateEntity".');
        }
    }

    /**
     * @param SettingPersonalModel $settingPersonal
     *
     * @return bool
     */
    public function updateEntitySettingPersonal(SettingPersonalModel $settingPersonal)
    {
        $uow = $this->em->getUnitOfWork();

        if ($settingPersonal->getUseDefault()) {
            if (\Doctrine\ORM\UnitOfWork::STATE_MANAGED === $uow->getEntityState($settingPersonal)) {
                $this->em->remove($settingPersonal);
                $this->em->flush($settingPersonal);

                $userId = 0;

                $token = $this->container->get('security.token_storage')->getToken();
                if ($token instanceof TokenInterface and $token->getUser() instanceof UserInterface) {
                    $userId = $token->getUser()->getId();
                }

                $this->cache->delete($this->getCacheKey($settingPersonal->getSetting()->getBundle(), $settingPersonal->getSetting()->getName(), $userId));

                return true;
            } else {
                return false;
            }
        }

        if (\Doctrine\ORM\UnitOfWork::STATE_MANAGED !== $uow->getEntityState($settingPersonal)) {
            $token = $this->container->get('security.token_storage')->getToken();
            if ($token instanceof TokenInterface and $token->getUser() instanceof UserInterface) {
                $settingPersonal->setUser($token->getUser());
            }

            $this->cache->delete($this->getCacheKey($settingPersonal->getSetting()->getBundle(), $settingPersonal->getSetting()->getName(), $token->getUser()->getId()));

            $this->em->persist($settingPersonal);
            $this->em->flush($settingPersonal);

            return true;
        }

        $uow->computeChangeSets();

        if ($uow->isEntityScheduled($settingPersonal)) {
            $history = $this->factorySettingHistory($settingPersonal->getSetting());
            $history
                ->setValue($settingPersonal->getValue())
                ->setIsPersonal(true)
            ;

            $userId = 0;

            $token = $this->container->get('security.token_storage')->getToken();
            if ($token instanceof TokenInterface and $token->getUser() instanceof UserInterface) {
                $history->setUser($token->getUser());
                $userId = $token->getUser()->getId();
            }

            $this->em->persist($history);
            $this->em->flush($history);

            $this->em->persist($settingPersonal);
            $this->em->flush($settingPersonal);

            $this->cache->delete($this->getCacheKey($settingPersonal->getSetting()->getBundle(), $settingPersonal->getSetting()->getName(), $userId));

            return true;
        }

        return false;
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function updateEntitySetting(SettingModel $setting)
    {
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();

        if ($uow->isEntityScheduled($setting)) {
            $history = $this->factorySettingHistory($setting);
            $history->setValue($setting->getValue());

            $token = $this->container->get('security.token_storage')->getToken();
            if ($token instanceof TokenInterface and $token->getUser() instanceof UserInterface) {
                $history->setUser($token->getUser());
            }

            $this->em->persist($history);
            $this->em->flush($history);

            $this->em->persist($setting);
            $this->em->flush($setting);

            $this->cache->delete($this->getCacheKey($setting->getBundle(), $setting->getName()));

            return true;
        }

        return false;
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
     * @return SettingHistoryModel
     */
    public function factorySettingHistory(SettingModel $setting)
    {
        return new SettingHistory($setting);
    }

    /**
     * @return SettingPersonalModel
     */
    public function factorySettingPersonal(SettingModel $setting)
    {
        return new SettingPersonal($setting);
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

            if (empty($bundle->getContainerExtension()) or $bundle->getContainerExtension()->getAlias() != $setting->getBundle()) {
                continue;
            }

            if (isset($this->settingsConfigRuntimeCache[$setting->getBundle()][$setting->getName()])) {
                return $this->settingsConfigRuntimeCache[$setting->getBundle()][$setting->getName()];
            } else {
                $reflector = new \ReflectionClass($bundleClass);
                $settingsConfigFile = dirname($reflector->getFileName()).'/Resources/config/settings.yml';

                if (file_exists($settingsConfigFile)) {
                    $settingsConfig = Yaml::parse(file_get_contents($settingsConfigFile));

                    if (empty($settingsConfig)) {
                        continue;
                    }

                    if (!isset($settingsConfig[$setting->getName()])) {
                        $this->removeEntity($setting);

                        return [];
                    }

                    $this->settingsConfigRuntimeCache[$setting->getBundle()] = $settingsConfig;

                    return $settingsConfig[$setting->getName()];
                }
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

    /**
     * @param SettingModel $setting
     * @param string|null  $value
     *
     * @return string
     */
    public function getSettingChoiceTitle(SettingModel $setting, $value)
    {
        if (null === $value) {
            $value = $setting->getValue();
        }

        $settingConfig = $this->getSettingConfig($setting);

        if (is_array($value)) {
            $values = [];
            foreach ($value as $var) {
                if (isset($settingConfig['choices']) and isset($settingConfig['choices'][$var])) {

                }
                $values[] = $settingConfig['choices'][$var];
            }

            $str = implode(', ', $values);

            if (empty($str)) {
                $str = '[]';
            }

            return $str;
        }

        if (isset($settingConfig['choices']) and isset($settingConfig['choices'][$value])) {
            return $settingConfig['choices'][$value];
        }

        return 'N/A';
    }

    /**
     * @param SettingModel $setting
     *
     * @return bool
     */
    public function hasSettingPersonal(SettingModel $setting)
    {
        $token = $this->container->get('security.token_storage')->getToken();

        if ($token instanceof TokenInterface and $token->getUser() instanceof UserInterface) {
            $settingPersonal = $this->settingsPersonalRepo->findOneBy(['setting' => $setting, 'user' => $token->getUser()->getId()]);

            if ($settingPersonal instanceof SettingPersonalModel) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function warmupDatabase()
    {
        $validator = new SchemaValidator($this->em);
        if (false === $validator->schemaInSyncWithMetadata()) {
            return;
        }

        foreach ($this->container->getParameter('kernel.bundles') as $bundleName => $bundleClass) {
            $reflector = new \ReflectionClass($bundleClass);
            $settingsConfig = dirname($reflector->getFileName()).'/Resources/config/settings.yml';

            if (file_exists($settingsConfig)) {
                /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
                $bundle = new $bundleClass();

                $settingsConfig = Yaml::parse(file_get_contents($settingsConfig));

                if (!empty($settingsConfig)) {
                    foreach ($settingsConfig as $name => $val) {
                        if (empty($bundle->getContainerExtension())) {
                            continue;
                        }

                        if (is_array($val)) {
                            if(isset($val['value'])) {
                                $val = $val['value'];
                            } else {
                                throw new \Exception("Missing value for key '$name' in Bundle '$bundleName'.");
                            }
                        }

                        $this->createSetting($bundle->getContainerExtension()->getAlias(), $name, $val);
                    }

                    $this->em->flush();
                }
            } // _end file_exists($settingsConfig)
        }
    }
}
