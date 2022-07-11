<?php

namespace Ggmanuilov\LaravelSlaveMonitor;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Arr;

class DbReadConnectionFactory extends ConnectionFactory
{
    /**
     * Get a read / write level configuration.
     *
     * @param  array  $config
     * @param  string  $type
     * @return array
     */
    protected function getReadWriteConfig(array $config, $type)
    {
        if ($type === 'read') {
            $config = $this->removeBehinds($config);
        }
        return isset($config[$type][0])
            ? Arr::random($config[$type])
            : $config[$type];
    }

    /**
     * Removed from pool servers behind servers.
     *
     * @param  array  $config
     * @return array
     */
    private function removeBehinds(array $config): array
    {
        foreach ($config['read'] as $index => $slave) {
            if (\Cache::has(config('database-slave-monitor.cache_key').$slave['host'])) {
                unset($config['read'][$index]);
            }
        }
        return $config;
    }
}