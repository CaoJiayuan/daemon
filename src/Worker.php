<?php
/**
 * Created by PhpStorm.
 * User: cjy
 * Date: 2018/12/13
 * Time: 16:40
 */

namespace Nerio\Daemon;


class Worker
{
    protected $pid = 0;

    protected $running = false;
    protected $waiting = false;
    /**
     * @var callable[]
     */
    private $runners = [];

    private $active = true;
    /**
     * @var Daemon
     */
    private $daemon;

    public function __construct(Daemon $daemon)
    {
        $this->daemon = $daemon;
    }

    public function run()
    {
        if ($this->pid < 1) {
            $this->pid = pcntl_fork();
            if ($this->pid < 0) {
                exit('can not fork');
            } else if ($this->pid > 0) {
                $this->running = true;
//                pcntl_wait($status);
                return;
            } else {
                try {
                    while (1) {
                        if (!$this->daemon->getPid()) {
                            break;
                        }
                        usleep(Timer\Timer::MICROSECOND * 100);
                        $runner = array_shift($this->runners);
                        if (!is_null($runner)) {
                            $this->waiting = false;
                            call_user_func($runner);
                        } else {
                            $this->waiting = true;
                        }
                    }
                } catch (\Exception $exception) {
                    $this->running = false;
                    file_put_contents(__DIR__ .'/../error.log', $exception->getMessage() . PHP_EOL, FILE_APPEND);
                    exit($exception->getCode());
                }
                exit(0);
            }
        }
    }

    public function queue(callable $runner)
    {
        $this->runners[] = $runner;
        return $this;
    }

    public function stop()
    {
        $this->active = false;
    }

    public function loads()
    {
        return count($this->runners);
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }
}
