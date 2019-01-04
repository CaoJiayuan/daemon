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

        $workers = $this->workers;
        foreach($workers as $k => $worker) {
            $worker->check();

            if ($worker->isExited()) {
                unset($this->workers[$k]);
            }
        }
    }

    /**
     * @return Worker|null
     */
    protected function selectWorker()
    {
        $ws = array_filter($this->workers, function ($w) {
            /** @var Worker $w */
            return $w->getWorkerStatus() === Worker::STATUS_PENDING;
        });

        return reset($ws);
    }
    protected function runningWorkerCount()
    {
        return count(array_filter($this->workers, function ($w) {
            /** @var Worker $w */
            return $w->isRunning();
        }));
    }

    protected function pushWorker(Worker $worker)
    {
        $this->workers[] = $worker;
        return $this;
    }

    public function addWorker($count = 1)
    {
        for ($i = 0; $i < $count; $i ++) {
            $this->workers[] = new Worker();
        }
        return $this;
    }
}
