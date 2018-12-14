<?php
namespace Nerio\Daemon;

class Worker
{
    protected $pid = 0;

    protected $running = false;

    public function run(callable $runner)
    {
        if ($this->pid < 1) {
            $this->pid = pcntl_fork();
            if ($this->pid < 0) {
                exit('can not fork');
            } else if ($this->pid > 0) {
                $this->running = true;
            } else {
                $code = 0;
                try {
                    call_user_func($runner);
                } catch (\Exception $exception) {
                    $code = $exception->getCode();
                }
                exit($code);
            }
        }
    }

    public function stop()
    {
        $this->running = false;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running && posix_kill($this->pid, SIG_DFL);
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }
}
