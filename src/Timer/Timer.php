<?php
/**
 * Created by PhpStorm.
 * User: cjy
 * Date: 2018/12/13
 * Time: 15:32
 */

namespace Nerio\Daemon\Timer;


use Nerio\Daemon\Daemon;
use Nerio\Daemon\Worker;

class Timer extends Daemon
{
    const MICROSECOND = 1;
    const MILLISECOND = 1 * 1000;
    const SECOND      = 1 * 1000 * 1000;

    /**
     * @var Schedule[]
     */
    protected $schedules = [];
    /**
     * @var Worker[]
     */
    protected $workers = [];
    /**
     * @var int
     */
    private $workerCount;

    public function __construct($workerCount = 4, $pidFile = null)
    {
        parent::__construct($pidFile);
        $this->workerCount = $workerCount;
    }


    /**
     * Run your daemon process here.
     * @return mixed
     */
    protected function runningAtBackground()
    {
        while (1) {
            $this->checkWorkers();
            foreach($this->schedules as $schedule) {
                if (!$schedule->waiting()) {
                    $worker = new Worker();
                    $this->workers[] = $worker;
                    if ($this->runningCounts() < $this->workerCount) {
                        $worker->run(
                            function () use ($schedule) {
                                $schedule->run();
                            }
                        );
                        $schedule->isOnce() || $schedule->readyNext();
                    }
                }
            }

            usleep(Timer::MICROSECOND * 500);
        }
    }

    public function schedule(callable $runner, int $interval, ...$params)
    {
        $schedule = new Schedule($runner, $interval, ...$params);
        $this->schedules[] = $schedule;
        return $schedule;
    }

    protected function runningCounts()
    {
        return count(array_filter($this->workers, function ($w) {
            return $w->isRunning();
        }));
    }

    protected function checkWorkers()
    {
        $results = [];
        foreach($this->workers as $worker) {
            $worker->isRunning() && pcntl_waitpid($worker->getPid(), $status, WNOHANG);
            $results[] = [
                'pid' => $worker->getPid(),
                'running' => $worker->isRunning(),
            ];
        }

        return $results;
    }


    public static function microtime()
    {
        return microtime(true) * 1000;
    }

}
