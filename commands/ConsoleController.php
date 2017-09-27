<?php

namespace app\commands;

use yii\console\Controller;

/**
 * Class ConsoleController - базовый класс консольного контроллера
 *
 */
abstract class ConsoleController extends Controller
{
    /** Уровень информативности метода */
    const VERBOSE_DEBUG = 'debug';

    public $verbose = null;

    /**
     * Output data to stdout
     *
     * @param string $msg for output
     * @return bool|int
     */
    public function write($msg)
    {
        if ($this->verbose === static::VERBOSE_DEBUG) {
            $this->stdout($msg);
        }
    }

    /**
     * Output data to stdout with new line character
     *
     * @param string $msg for output
     * @return bool|int
     */
    public function writeln($msg)
    {
        return $this->write($msg . PHP_EOL);
    }

    /**
     * Получение текущего времени
     * @return int
     */
    public function getTimeNow()
    {
        $date = new \DateTime();
        return $date->format('Y-m-d H:i:s');
    }
}