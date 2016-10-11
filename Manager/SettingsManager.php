<?php

namespace SmartCore\Bundle\SettingsBundle\Manager;

use Doctrine\DBAL\Exception\TableNotFoundException;
use RickySu\Tagcache\Adapter\TagcacheAdapter;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use SmartCore\Bundle\SettingsBundle\Model\SettingModel;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsManager
{
    use ContainerAwareTrait;

    /** @var \Doctrine\ORM\EntityManager $em */
    protected $em;

    /** @var \Doctrine\ORM\EntityRepository */
    protected $settingsRepo;

    /** @var TagcacheAdapter */
    protected $tagcache;

    /**
     * @param ContainerInterface $container
     * @param TagcacheAdapter $tagcache
     */
    public function __construct(ContainerInterface $container, TagcacheAdapter $tagcache)
    {
        $this->container = $container;
        $this->em        = $container->get('doctrine.orm.entity_manager');
        $this->tagcache  = $tagcache;
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

        if ($bundle) {
            return $this->settingsRepo->findBy(['bundle' => $bundle], ['name' => 'ASC']);
        }

        return $this->settingsRepo->findBy([], ['bundle' => 'ASC', 'name' => 'ASC']);
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
            $parts = explode('.', $bundle, 2);

            if (count($parts) !== 2) {
                throw new \Exception('Wrong setting name: "'.$bundle.'"');
            }

            $bundle = $parts[0];
            $name   = $parts[1];
        }

        $cache_key = $this->getCacheKey($bundle, $name);

        if (false == $setting = $this->tagcache->get($cache_key)) {
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

            $this->tagcache->set($cache_key, $setting, ['smart.settings']);
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

    /*
    public function getType($bundle, $name)
    {
        $cache_key = md5('smart_setting_type'.$bundle.$name);

        $type = 'text';
    }
    */

    /**
     * @param Setting $setting
     *
     * @return bool
     */
    public function updateEntity(Setting $setting)
    {
        $this->em->persist($setting);
        $this->em->flush($setting);

        $this->tagcache->deleteTag('smart.settings');

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
}
