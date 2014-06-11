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
use \clearos\apps\clearcenter\Subscription_Engine as Subscription_Engine;

clearos_load_library('base/Engine');
clearos_load_library('base/Folder');
clearos_load_library('clearcenter/Subscription_Engine');

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

        if (! $folder->exists())
            return array();

        $apps = $folder->get_listing();
        $subscriptions = array();

        // Use the CodeIgniter philosophy of following a standard pattern
        // when it comes to filenames, classes, etc.

        foreach ($apps as $basename) {
            $subscription_class = ucwords(preg_replace('/_/', ' ', $basename));
            $subscription_class = preg_replace('/ /', '_', $subscription_class) . '_Subscription';
            $subscription_full_class = '\clearos\apps\\' . $basename . '\\' . $subscription_class;

            if (!clearos_load_library($basename . '/' . $subscription_class))
                continue;

            $subscription = new $subscription_full_class();
            
            $subscriptions[$basename] = $subscription->get_info();

            if ($subscriptions[$basename]['available'] < 3) {
                if ($subscriptions[$basename]['type'] === Subscription_Engine::TYPE_USER)
                    $subscriptions[$basename]['warning_message'] = lang('clearcenter_remaining_user_licenses:') . ' ' .
                        $subscriptions[$basename]['available'];
            }
        }

        return $subscriptions;
    }

    /**
     * Returns user limits.
     *
     * @return array extension limits
     * @throws Engine_EXception
     */

    public function get_user_limits()
    {
        clearos_profile(__METHOD__, __LINE__);

        $limits = array();

        $subscriptions = $this->get_subscriptions();

        foreach ($subscriptions as $app => $details) {
            if ($details['available'] === 0)
                $limits = $details['user_limit'];
        }

        return $limits;
    }
}
