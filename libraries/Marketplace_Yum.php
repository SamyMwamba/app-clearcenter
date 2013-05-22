<?php

/**
 * Marketplace yum install class.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\clearcenter;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('clearcenter');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Marketplace yum install class.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Marketplace_Yum extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_PID = '/sbin/pidof';
    const COMMAND_WC_YUM = '/usr/sbin/wc-yum';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Marketplace_yum constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Installs a package (or list of packages) from the update server.
     *
     * @param array $packages package list
     *
     * @return integer exit code
     * @throws Validation_Exception
     */

    public function install($packages)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($packages))
            $packages = array($packages);

        $is_busy = TRUE;

        for ($inx = 1; $inx < 100; $inx++) {
            if (! $this->is_busy())
                break;

            sleep(60);
        }

        // Create rpm list
        //----------------

        $rpmlist = '';

        foreach ($packages as $package)
            $rpmlist .= escapeshellarg($package) . ' ';

        // Run install script
        //-------------------

        $shell = new Shell();
        $exit_code = $shell->Execute(self::COMMAND_WC_YUM, '-i ' . $rpmlist, TRUE);

        return $exit_code;
    }

    /**
     * Checks to see if yum is already running. 
     *
     * @return boolean TRUE if yum is busy
     */

    public function is_busy()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $options['validate_exit_code'] = FALSE;
        $exit_code = $shell->execute(self::COMMAND_PID, '-s -x ' . self::COMMAND_WC_YUM, FALSE, $options);

        if ($exit_code == 0)
            return TRUE;
        else
            return FALSE;
    }
}
