<?php

/**
 * Application web service class.
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

use \clearos\apps\base\Software as Software;
use \clearos\apps\base\Yum as Yum;
use \clearos\apps\clearcenter\Web_Service as Web_Service;

clearos_load_library('base/Software');
clearos_load_library('base/Yum');
clearos_load_library('clearcenter/Web_Service');

// Exceptions
//-----------

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Application web service class.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Application_Web_Service extends Web_Service
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_DO_UPDATE = 'do_update = ';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $package = '';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Application web services constructor.
     *
     * The constructor requires the name of the remote service.
     *
     * @param string $service service name
     * @param string $package RPM package name of update
     */

    public function __construct($service, $package)
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct($service);

        $this->package = $package;
    }

    /**
     * Checks for an available update.
     *
     * @return boolean TRUE if update is required
     * @throws Engine_Exception, WebServicesRemoteException
     */

    public function check_for_update()
    {
        clearos_profile(__METHOD__, __LINE__);

        $software = new Software($this->package);

        if ($software->is_installed()) {
            $version = $software->get_version();
            $release = $software->get_release();
        } else {
            $version = 0;
            $release = 0;
        }

        $payload = $this->request('CheckForUpdate', '&version=' . $version . '&release=' . $release);

        if (preg_match('/' . self::CONSTANT_DO_UPDATE . 'true/i', $payload))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Installs the latest update if one is available.
     *
     * @return void
     * @throws Engine_Exception, WebServicesRemoteException
     */

    public function install_update()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $update = new Yum();
            $update->install(array($this->package), FALSE);
        } catch (Exception $e) {
            // Keep going, see note below.
        }

        // Sanity check to see if RPM was installed
        // Since yum might come back "nothing to do / exit 0"
        // we cannot use the exit code to determine if the
        // update was actually installed
        $rpm = new Software($this->package);
        $installtime = $rpm->get_install_time();
        $delta = time() - $installtime;

        if (abs($delta) < '172800')
            $this->_set_update_complete();
    }

    /**
     * Sends ok message to Service Delivery Network.
     *
     * @return void
     */

    private function _set_update_complete()
    {
        clearos_profile(__METHOD__, __LINE__);

        $payload = $this->request('SetUpdateComplete');
    }
}
