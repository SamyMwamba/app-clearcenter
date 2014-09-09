<?php

/**
 * Static Content class.
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

clearos_load_language('clearcenter');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Static Content for ClearCenter web-services class.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2014 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Static_Content extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const HOSTNAME_STATIC = 'static.clearsdn.com';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Static Content constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * A generic way to get static content from the Service Delivery Network (SDN).
     *
     * @param string $resource resource
     * @param string $filename filename
     *
     * @return mixed content
     * @throws Engine_Exception
     */

    public function get($resource, $filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $url = "http://" . self::HOSTNAME_STATIC . '/1.0/' . $resource . "/" . $filename;

        if (isset($ch))
            unset($ch);

        $ch = curl_init();

        // Check for upstream proxy settings
        //----------------------------------

        if (clearos_app_installed('upstream_proxy')) {
            clearos_load_library('upstream_proxy/Proxy');

            $proxy = new \clearos\apps\upstream_proxy\Proxy();

            $proxy_server = $proxy->get_server();
            $proxy_port = $proxy->get_port();
            $proxy_username = $proxy->get_username();
            $proxy_password = $proxy->get_password();

            if (! empty($proxy_server))
                curl_setopt($ch, CURLOPT_PROXY, $proxy_server);

            if (! empty($proxy_port))
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);

            if (! empty($proxy_username))
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_username . ':' . $proxy_password);
        }

        // Set main curl options
        //----------------------

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);

        $curl_response = chop(curl_exec($ch));
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Return useful errno messages for the most common errnos
        //--------------------------------------------------------

        if ($errno == 0)
            return $curl_response;

        if ($httpCode == 404)
            throw new Engine_Exception(lang('clearcenter_file_not_found') . " ($filename).", CLEAROS_INFO);
        else if ($errno == CURLE_COULDNT_RESOLVE_HOST)
            throw new Engine_Exception(lang('clearcenter_dns_lookup_failed'), CLEAROS_INFO);
        else if ($errno == CURLE_OPERATION_TIMEOUTED)
            throw new Engine_Exception(lang('clearcenter_unable_to_contact_remote_server'), CLEAROS_INFO);
        else
            throw new Engine_Exception(lang('clearcenter_connection_failed:') . ' ' . $error, CLEAROS_INFO);
    }

}

?>
