<?php
/**
 * Created by PhpStorm.
 * User: cjy
 * Date: 2018/12/11
 * Time: 10:41
 */

namespace Nerio\Daemon;


use Nerio\Daemon\Exceptions\DetachException;
use Nerio\Daemon\Exceptions\ForkException;

abstract class Daemon
{
    private $pidFile;

    public function __construct($pidFile = null)
    {
        $this->setPidFile($pidFile);
    }

    protected function setPidFile($pidFile = null)
    {
        if (is_null($pidFile)) {
            $class = str_replace('\\', '_',  get_class($this));
            $pidFile = __DIR__ .'/../' . $class . '.pid';
        }

        $this->pidFile = $pidFile;
    }

    public function start()
    {
        if (!$this->getPid()) {
            $this->demonize();
        }

        return true;
    }

    public function stop() {
        $pid = $this->getPid();
        if ($pid > 0) {
            posix_kill($pid, SIGTERM);
            unlink($this->pidFile);
            return true;
        } else {
            return false;
        }
    }

    protected function demonize()
    {
        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new ForkException();
        } else if ($pid > 0) {
            exit();
        }

        if (posix_setsid() === -1) {
            throw new DetachException();
        }

        chdir('/');
        umask(0);
        if ($fp = fopen($this->pidFile, 'w')) {
            fwrite($fp, posix_getpid());
            fclose($fp);
            $this->beforeStart();
            $this->runningAsDaemon();
            $this->stop();
        }
    }

    protected function beforeStart()
    {

    }

    public function status() {
        return $this->getPid() > 0;
    }

    /**
     * Run your daemon process here.
     * @return mixed
     */
    abstract protected function runningAsDaemon();

    public function getPid()
    {
        if (!file_exists($this->pidFile)) {
            return 0;
        }

        $pid = intval(file_get_contents($this->pidFile));

        if (posix_kill($pid, SIG_DFL)) {
            return $pid;
        } else {
            unlink($this->pidFile);
            return 0;
        }
    }
}
