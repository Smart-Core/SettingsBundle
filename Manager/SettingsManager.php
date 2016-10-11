<?php

namespace SmartCore\Bundle\SettingsBundle\Manager;

use Doctrine\DBAL\Exception\TableNotFoundException;
use RickySu\Tagcache\Adapter\TagcacheAdapter;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SettingsManager
{
    use ContainerAwareTrait;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $settingsRepo = null;

    /**
     * @var \RickySu\Tagcache\Adapter\TagcacheAdapter
     */
    protected $tagcache;

    /**
     * @param ContainerInterface $container
     * @param TagcacheAdapter $tagcache
     */
    public function __construct(ContainerInterface $container, TagcacheAdapter $tagcache)
    {
        $this->container = $container;
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
     * @return Setting|null
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

        $cache_key = md5('smart_setting'.$bundle.$name);

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

    public function getType($bundle, $name)
    {
        $cache_key = md5('smart_setting_type'.$bundle.$name);

        $type = 'text';
    }

    /**
     * @param Setting $setting
     *
     * @return bool
     */
    public function updateEntity(Setting $setting)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $em->persist($setting);
        $em->flush($setting);

        $this->tagcache->deleteTag('smart.settings');

        return true;
    }

    /**
     * @param string        $bundle
     * @param string        $name
     * @param string|array  $value
     */
    public function createSetting($bundle, $name, $value)
    {
        $setting = new Setting();
        $setting
            ->setBundle($bundle)
            ->setName($name)
            ->setValue($value)
        ;

        $errors = $this->container->get('validator')->validate($setting);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        if (count($errors) > 0) {
            $em->detach($setting);
        } else {
            $em->persist($setting);
        }
    }
}
