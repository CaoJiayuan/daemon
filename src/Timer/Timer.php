<?php
declare(ticks=1);

namespace Nerio\Daemon\Timer;


use Nerio\Daemon\Daemon;
use Nerio\Daemon\Traits\HasWorkers;
use Nerio\Daemon\Worker;

class Timer extends Daemon
{
    use HasWorkers;

    const MICROSECOND = 1;
    const MILLISECOND = 1 * 1000;
    const SECOND      = 1 * 1000 * 1000;

    /**
     * @var Schedule[]
     */
    protected $schedules = [];

    /**
     * @var int
     */
    private $workerCount;

    private $tick = Timer::MICROSECOND * 500;


    public function __construct($workerCount = 4, $pidFile = null)
    {
        parent::__construct($pidFile);
        $this->workerCount = $workerCount;
    }

    /**
     * @param int $tick
     * @return Timer
     */
    public function setTick(int $tick): Timer
    {
        $this->tick = $tick;
        return $this;
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
                    $this->pushWorker($worker);
                    if ($this->runningWorkerCount() < $this->workerCount) {
                        if ($w = $this->selectWorker()) {
                            $schedule->lock();
                            $w->run(
                                function () use ($schedule) {
                                    $schedule->run();
                                }
                            );
                            $schedule->isOnce() || $schedule->readyNext();
                        }
                    }
                }
            }

            usleep($this->tick);
        }
    }

    public function schedule(callable $runner, int $interval, ...$params)
    {
        $schedule = new Schedule($runner, $interval, ...$params);
        $i = count($this->schedules) - 1;
        $this->schedules[$i] = $schedule;
        return $this->schedules[$i];
    }

    public function once(callable $runner, ...$params)
    {
        return $this->schedule($runner, 0, $params)->once();
    }

    public static function millisecond()
    {
        return microtime(true) * 1000;
    }


    public function runningForeground()
    {
        $this->foreground = true;
        return $this;
    }
}
