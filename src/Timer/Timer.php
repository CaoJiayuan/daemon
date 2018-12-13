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

    public function __construct($workerCount = 4,$pidFile = null)
    {
        parent::__construct($pidFile);
        $this->workerCount = $workerCount;
    }

    protected function initWorkers()
    {
        for ($i = 0; $i < $this->workerCount; $i ++) {
            $this->workers[] =  new Worker($this);
        }
    }

    /**
     * Run your daemon process here.
     * @return mixed
     */
    protected function runningAsDaemon()
    {
        $this->initWorkers();
        foreach($this->workers as $worker) {
            $worker->run();
        }
        while (1) {
            usleep(Timer::MICROSECOND * 500);
            foreach($this->schedules as $schedule) {
                $str = $schedule->waiting() ? 'true' : 'false';
                if (!$schedule->waiting()) {
                    $schedule->run();
                }
            }
        }
    }

    public function schedule(callable $runner, int $interval, ...$params)
    {
        $schedule = new Schedule($runner, $interval, ...$params);
        $this->schedules[] = $schedule;
        return $schedule;
    }

    /**
     * @return Worker
     */
    public function selectWorker()
    {
        $workers = $this->workers;
        usort($workers, function ($w1, $w2) {
            $a = $w1->loads();
            $b = $w2->loads();
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
        return reset($workers);
    }
}
