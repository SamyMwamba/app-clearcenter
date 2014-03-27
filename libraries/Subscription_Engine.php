<?php

/**
 * Core engine class for paid app API.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2013 ClearCenter
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

clearos_load_language('clearcenter');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Core engine class for paid app API.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2013 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Subscription_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const TYPE_USER = 'user';
    const TYPE_DEVICE = 'device';
    const CACHE_TIME_SECONDS = 600;
    const FILE_CACHE_TIMESTAMP = '.clearcenter_subscriptions';
    const FOLDER_SUBSCRIPTIONS = '/var/clearos/clearcenter/subscriptions';
    const CMD_CLEAR_SUB_UPDATE = '/usr/sbin/clearcenter-subscriptions';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Subscription engine constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Get subscription updates from SDN.
     *
     * @param boolean clear_cache Flag to clear cache
     *
     * @return void
     */

    public function get_subscription_updates($clear_cache = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {

            if ($clear_cache) {
                $file = new File(CLEAROS_TEMP_DIR . '/' . self::FILE_CACHE_TIMESTAMP);
                if ($file->exists())
                    $file->delete();
            }

            $options = array(
                'background' => TRUE,
                'env' => 'LANG=en_US'
            );
            // We don't hang around on this since it could impede UI
            $shell = new Shell();
            $shell->execute(self::CMD_CLEAR_SUB_UPDATE, '', FALSE, $options);
        } catch (Exception $e) {
            clearos_log('clearcenter', 'ClearCenter Subscription update failed: ' . clearos_exception_message($e));            
        }
    }
}
