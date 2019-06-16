<?php

declare(strict_types=1);

namespace SmartCore\Bundle\SettingsBundle\Cache;

class DummyCacheProvider
{
    public function fetch($id)
    {
        return false;
    }

    public function delete($id)
    {
        return false;
    }

    public function save($id, $data, $lifeTime = 0)
    {
        return false;
    }
}
