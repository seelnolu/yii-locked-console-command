<?php

namespace Wikimart\YiiLockedConsoleCommand;

/**
 * LockedCommand class file.
 * Abstract class represents an executable locked console command
 * and cannot by used on Windows operation systems.
 *
 * @author Sergey Nosov <sergejs.nosovs@gmail.com>
 */
abstract class LockedCommand extends \CConsoleCommand
{
    const CATEGORY = 'console';

    /**
     * Full path to lock file
     * @var string
     */
    protected $lockFile;

    /**
     * Returns path to lock file
     * You may override this method to do last-minute preparation for the action.
     * @return string
     */
    protected function getLockPath()
    {
        return \Yii::app()->runtimePath;
    }

    /**
     * Returns lock filename
     * You may override this method to do last-minute preparation for the action.
     * @param string $action the action name
     * @return string
     */
    protected function getLockFilename($action)
    {
        return $this->getName() . '-' . strtolower($action) . '.lock';
    }

    protected function beforeAction($action, $params)
    {
        $this->lockFile = $this->getLockPath() . DIRECTORY_SEPARATOR . $this->getLockFilename($action);
        if ($this->isLocked()) {
            \Yii::log("Action was canceled because it's locked now", \CLogger::LEVEL_WARNING, self::CATEGORY);
            return false;
        }
        \Yii::log("Lock before action", \CLogger::LEVEL_TRACE, self::CATEGORY);
        return parent::beforeAction($action, $params);
    }

    protected function afterAction($action, $params, $exitCode = 0)
    {
        \Yii::log("Unlock after action", \CLogger::LEVEL_TRACE, self::CATEGORY);
        unlink($this->lockFile);
        return parent::afterAction($action, $params, $exitCode);
    }

    /**
     * If lock file exists, check if stale.  If exists and is not stale, return TRUE
     * Else, create lock file and return FALSE.
     * @return boolean
     */
    protected function isLocked()
    {
        if (file_exists($this->lockFile)) {

            // Check if it's stale
            $lockingPID = trim(file_get_contents($this->lockFile));
            // Get all active PIDs.
            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));
            // If PID is still active, return true
            if (in_array($lockingPID, $pids)) {
                return true;
            }
            // Lock-file is stale, so kill it.  Then move on to re-creating it.
            \Yii::log("Removing stale lock file " . $this->lockFile, \CLogger::LEVEL_WARNING, self::CATEGORY);
            unlink($this->lockFile);
        }

        file_put_contents($this->lockFile, getmypid() . "\n");
        return false;
    }
}
