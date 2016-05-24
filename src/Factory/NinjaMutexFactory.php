<?php

namespace WShafer\NinjaMutexZf2\Factory;

use Interop\Container\ContainerInterface;
use NinjaMutex\MutexFabric;
use WShafer\NinjaMutexZf2\Exception\MissingConfigException;
use WShafer\NinjaMutexZf2\Exception\MissingServiceException;
use WShafer\NinjaMutexZf2\Lock\ZendCacheLock;
use Zend\Cache\Storage\StorageInterface;

class NinjaMutexFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        
        if (empty($config['NinjaMutexZf2']['cache'])) {
            throw new MissingConfigException(
                'Missing config key $config[\'NinjaMutexZf2\'][\'cache\']'
            );
        }
        
        $cache = $container->get($config['NinjaMutexZf2']['cache']);
        $cacheLock = new ZendCacheLock($cache);
        return new MutexFabric('NinjaMutexZf2', $cacheLock);
    }
}
