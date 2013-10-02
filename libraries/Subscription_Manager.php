<?php

/**
 * Subscription manager class.
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
use \clearos\apps\base\Folder as Folder;

clearos_load_library('base/Engine');
clearos_load_library('base/Folder');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Subscription manager class.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2013 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Subscription_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const PATH_SUBSCRIPTIONS = '/var/clearos/clearcenter/subscriptions';

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
     * Returns subscription information.
     *
     * @return array subscription information
     * @throws Engine_EXception
     */

    public function get_subscriptions()
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder(self::PATH_SUBSCRIPTIONS);

        $apps = $folder->get_listing();
        $subscriptions = array();

        foreach ($apps as $basename) {
            // Use the CodeIgniter philosophy of following a standard pattern
            // when it comes to filenames, classes, etc.

            $subscription_class = ucwords(preg_replace('/_/', ' ', $basename));
            $subscription_class = preg_replace('/ /', '_', $subscription_class) . '_Subscription';
            $subscription_full_class = '\clearos\apps\\' . $basename . '\\' . $subscription_class;

            clearos_load_library($basename . '/' . $subscription_class);

            $subscription = new $subscription_full_class();
            
            $info = $subscription->get_info();
        }
return;

        // FIXME: just test data below
        $subscriptions['active_directory'] = array(
            'app_name' => 'Active Directory Connector',
            'used' => 49,
            'total' => 50,
            'available' => 1,
            'marketplace_link' => 'active_directory',
            'warning_message' => '45/50 connector licenses in use',
            'type' => 'user'
        );

        $subscriptions['network_map'] = array(
            'app_name' => 'Network Map',
            'used' => 9,
            'total' => 10,
            'available' => 1,
            'marketplace_link' => '',
            'warning_message' => '19/20 mappings in use',
            'type' => 'device'
        );

        $subscriptions['zarafa_small_business'] = array(
            'app_name' => 'Zarafa Small Business',
            'used' => 10,
            'total' => 10,
            'available' => 0,
            'marketplace_link' => 'zarafa_small_business_5_users',
            'warning_message' => '10/10 Zarafa user licenses in use',
            'type' => 'user',
            'user_extension' => 'zarafa',
            'user_keys' => array('account_flag', 'administrator_flag'),
        );

        return $subscriptions;
    }

    /**
     * Returns extension limits.
     *
     * @return array extension limits
     * @throws Engine_EXception
     */

    public function get_extension_limits()
    {
        clearos_profile(__METHOD__, __LINE__);

        $limits = array();

        // If zarafa and CAL available == 0, disable Zarafa extension to prevent user
        // from borking they're Zarafa mail server by going over count.

        // FIXME: just test data below
        $limits['zarafa'] = array(
            'user_key' => 'account_flag',
        );

        return $limits;
    }
}
