<?php
/**
 * Created by PhpStorm.
 * User: cjy
 * Date: 2018/12/14
 * Time: 14:44
 */

namespace Nerio\Daemon\Traits;


use Nerio\Daemon\Worker;

trait HasWorkers
{
    /**
     * @var Worker[]
     */
    protected $workers = [];


    protected function checkWorkers()
    {
        $results = [];
        $workers = $this->workers;
        foreach($workers as $k => $worker) {
            if ($worker->isRunning()) {
                pcntl_waitpid($worker->getPid(), $status, WNOHANG);
            } else {
                unset($this->workers[$k]);
            }
            $results[] = [
                'pid' => $worker->getPid(),
                'running' => $worker->isRunning(),
            ];
        }

        return $results;
    }

    protected function runningWorkerCount()
    {
        return count(array_filter($this->workers, function ($w) {
            return $w->isRunning();
        }));
    }

    protected function pushWorker(Worker $worker)
    {
        $this->workers[] = $worker;
    }
}
