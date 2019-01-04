<?php
namespace Nerio\Daemon;

class Worker
{
    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_STOPPED = 2;

    protected $pid = 0;

    protected $status = Worker::STATUS_PENDING;

    public function run(callable $runner)
    {
        if ($this->pid < 1) {
            $this->pid = pcntl_fork();
            if ($this->pid < 0) {
                exit('can not fork');
            } else if ($this->pid > 0) {
                $this->status = Worker::STATUS_RUNNING;
                return;
            } else {
                $code = 0;
                try {
                    call_user_func($runner);
                } catch (\Exception $exception) {
                    $code = $exception->getCode();
                }
                posix_kill(posix_getppid(), SIGCHLD);
                exit($code);
            }
        }
    }

    public function stop()
    {
        $this->status = Worker::STATUS_STOPPED;
        $this->exit();
    }

    public function exit()
    {
        if ($this->pid) {
            posix_kill($this->pid, SIGTERM);
            $this->pid = 0;
        }
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->status === Worker::STATUS_RUNNING && posix_kill($this->pid, SIG_DFL);
    }

    public function isExited()
    {
        return $this->status === Worker::STATUS_RUNNING && !posix_kill($this->pid, SIG_DFL);
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getWorkerStatus(): int
    {
        return $this->status;
    }

    public function check($option = WNOHANG)
    {
        if ($this->isRunning()) {
            pcntl_waitpid($this->pid, $status, $option);
        }
    }


    public function refresh()
    {
        $this->exit();
        $this->status = self::STATUS_PENDING;
        return $this;
    }
}
