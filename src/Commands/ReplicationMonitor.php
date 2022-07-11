<?php

namespace Ggmanuilov\LaravelSlaveMonitor\Commands;

use Illuminate\Console\Command;

class ReplicationMonitor extends Command
{
    private int $behind_master;
    private int $cache_ban_timeout;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:db-replication';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and block a replication db server if behind it.';

    public function __construct()
    {
        $this->behind_master = config('database-slave-monitor.behind_master', 2);
        $this->cache_ban_timeout = config('database-slave-monitor.cache_ban_timeout', 15);
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $configMysql = config('database.connections.mysql');
        if (isset($configMysql['read'])) {
            foreach ($configMysql['read'] as $config) {
                \DB::disconnect();
                /*
                 * Replace array of read servers on one server
                 * that request was executed on one read server.
                 */
                \Config::set('database.connections.mysql.read', [$config]);
                $resp = null;
                try {
                    $resp = $this->slaveStatus();
                } catch (\Throwable $throwable) {
                    \Log::error('DatabaseManager '.$throwable->getMessage(), ['config' => $config]);
                }
                $this->printStatus($config, $resp);
                if (!$this->activeConnection($resp)) {
                    $this->deactivate($config, $resp);
                }
            }
        } else {
            \Log::info('DatabaseManager read server list is empty.');
        }

        return 0;
    }

    private function slaveStatus()
    {
        $query = \DB::raw("SHOW SLAVE STATUS");
        return collect(\DB::select($query, [], true))->first();
    }

    private function activeConnection($resp): bool
    {
        return $resp && $resp->Seconds_Behind_Master <= $this->behind_master;
    }

    private function writeToLogger(array $config, $resp)
    {
        if ($reason = $this->reason($resp)) {
            \Log::alert('DatabaseManager ban replications server', [
                'host'   => $config['host'],
                'reason' => $reason,
            ]);
        }
    }

    private function deactivate(array $config, $resp)
    {
        \Cache::remember(config('database-slave-monitor.cache_key').$config['host'],
            now()->addSeconds($this->cache_ban_timeout),
            function () use ($config) {
                return true;
            });
        $this->writeToLogger($config, $resp);
    }

    private function reason(?\stdClass $resp): string
    {
        $message = 'not available ';
        if ($resp) {
            $message = '';
            if (is_null($resp->Seconds_Behind_Master)) {
                $message = 'invalid behind response "NULL"';
            }
            if ($resp->Seconds_Behind_Master > $this->behind_master) {
                $message = sprintf('behind on "%s" sec.', $resp->Seconds_Behind_Master);
            }
        }
        return $message;
    }

    private function printStatus(array $config, ?\stdClass $resp)
    {
        $reason = $this->reason($resp);
        $this->info($config['host'].' '.$reason);
    }
}
