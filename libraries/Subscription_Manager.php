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

clearos_load_library('base/Engine');

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
        $subscriptions = array();

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
