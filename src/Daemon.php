<?php
declare(ticks=1);

namespace Nerio\Daemon;

use Nerio\Daemon\Exceptions\ForkException;

abstract class Daemon
{
    private $pidFile;

    protected $foreground = false;


    public function __construct($pidFile = null)
    {
        $this->setPidFile($pidFile);
    }

    protected function setPidFile($pidFile = null)
    {
        if (is_null($pidFile)) {
            $class = str_replace('\\', '_', get_class($this));
            $pidFile = __DIR__ . '/../' . $class . '.pid';
        }

        $this->pidFile = $pidFile;

    }

    public function start()
    {
        if (!$this->getPid()) {
            $this->registerHandleFunction();
            $this->foreground ? $this->foreground() : $this->demonize();
        }

        return true;
    }

    public function stop()
    {
        $pid = $this->getPid();
        if ($pid > 0) {
            $this->beforeStop();
            posix_kill($pid, SIGTERM);
            @unlink($this->pidFile);
            return true;
        } else {
            return false;
        }
    }

    protected function beforeStop()
    {

    }

    protected function demonize()
    {
        $this->beforeStart();
        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new ForkException();
        } else if ($pid > 0) {
            exit();
        }

        if (posix_setsid() === -1) {
            exit('daemon detach error');
        }

        chdir('/');
        umask(0);
        $this->exec();
    }

    protected function foreground()
    {
        $this->beforeStart();
        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new ForkException();
        } else if ($pid > 0) {
            pcntl_wait($status);
        } else {
            $this->exec();
        }
    }

    protected function registerHandleFunction()
    {
        pcntl_signal(SIGINT, function () {
            $this->stop();
            exit(0);
        });
    }

    protected function savePid()
    {
        if ($fp = fopen($this->pidFile, 'w')) {
            fwrite($fp, posix_getpid());
            fclose($fp);
            return true;
        }
        return false;
    }

    protected function beforeStart()
    {

    }

    public function status()
    {
        return $this->getPid() > 0;
    }

    /**
     * Run your process here.
     * @return mixed
     */
    abstract protected function runningAtBackground();

    public function getPid()
    {
        if (!file_exists($this->pidFile)) {
            return 0;
        }

        try {
            $pid = intval(file_get_contents($this->pidFile));
        } catch (\Exception $exception) {
            return 0;
        }

        if (posix_kill($pid, SIG_DFL)) {
            return $pid;
        } else {
            unlink($this->pidFile);
            return 0;
        }
    }

    protected function exec()
    {
        if ($this->savePid()) {
            try {
                $this->runningAtBackground();
            } catch (\Exception $exception) {
                exit($exception->getCode());
            }
        } else {
            exit(1);
        }
    }
}
