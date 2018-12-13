<?php
/**
 * Created by PhpStorm.
 * User: cjy
 * Date: 2018/12/13
 * Time: 15:33
 */

namespace Nerio\Daemon\Timer;


class Schedule
{
    const INTERVAL_MILLISECOND = 1;
    const INTERVAL_SECOND = 1 * 1000;
    const INTERVAL_MINUTE = 1 * 1000 * 60;
    const INTERVAL_HOUR = 1 * 1000 * 60 * 60;

    private $runner;
    /**
     * @var array
     */
    private $params;
    private $serveAt = 0;
    private $locked = false;
    private $once = false;
    private $interval;

    public function __construct(callable $runner, int $interval, ...$params)
    {
        $this->runner = $runner;
        $this->params = $params;
        $this->interval = $interval;
    }

    public function run()
    {
        $this->lock();
        $this->setServeAt($this->microtime() + $this->interval);
        call_user_func_array($this->runner, $this->params);
        $this->once || $this->unlock();
    }


    /**
     * @param int $serveAt
     * @return Schedule
     */
    protected function setServeAt(int $serveAt)
    {
        $this->serveAt = $serveAt;
        return $this;
    }

    public function lock()
    {
        $this->locked = true;
        return $this;
    }

    public function unlock()
    {
        $this->locked = false;
        return $this;
    }

    public function once()
    {
        $this->once = true;
        return $this;
    }

    protected function microtime()
    {
        return microtime(true) * 1000;
    }

    /**
     * @return bool
     */
    public function waiting(): bool
    {
        return $this->locked || $this->serveAt > $this->microtime();
    }
}
