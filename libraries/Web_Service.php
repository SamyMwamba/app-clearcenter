<?php

/**
 * Web service class.
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

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Product as Product;
use \clearos\apps\language\Locale as Locale;
use \clearos\apps\suva\Suva as Suva;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Product');
clearos_load_library('language/Locale');
clearos_load_library('suva/Suva');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\clearcenter\Not_Registered_Exception as Not_Registered_Exception;
use \clearos\apps\clearcenter\Remote_Exception as Remote_Exception;
use \clearos\apps\clearcenter\Remote_Unavailable_Exception as Remote_Unavailable_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('clearcenter/Not_Registered_Exception');
clearos_load_library('clearcenter/Remote_Exception');
clearos_load_library('clearcenter/Remote_Unavailable_Exception');


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web service class.
 *
 * The Web Service class interacts with ClearCenter's backend
 * systems -- the Service Delivery Network.  This network provides dynamic DNS,
 * Dynamic VPN, software updates, content filter updates, and much more.
 * This class will be replaced by direct SOAP calls in the future.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Web_Service extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $service;
    protected $cachefile;
    protected $config = array();
    protected $is_loaded = FALSE;
    protected $hostkey = "";
    protected $langcode = "en_US";
    protected $vendor = "";
    protected $os_version = "";
    protected $os_name = "";

    const CONSTANT_UNKNOWN = 'unknown';
    const CONSTANT_UNLIMITED = 'unlimited';
    const CONSTANT_INCLUDED = 'included';
    const CONSTANT_EXTRA = 'extra';
    const CONSTANT_ASP = 'asp';
    const CONSTANT_NOT_REGISTERED = 20;

    const PATH_APPS = '/var/clearos/clearcenter/apps';
    const FILE_REGISTERED = '/var/clearos/registration/registered';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * The WebServices constructor.
     *
     * The constructor requires the name of the remote service, for example:
     *
     *  - DynamicDNS - Dynamic DNS service
     *  - DynamicVPN - Dynamic VPN service
     *  - IntrusionDetection - Intrusion detection rules updates service
     *  - etc.
     *
     * @param string $service service name
     */

    public function __construct($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->service = $service;
        $this->cachefile = self::PATH_APPS . "/$service/subscription";

        include clearos_app_base('clearcenter') . '/deploy/config.php';
        $this->config = $config;
    }

    /**
     * A generic way to communicate with the Service Delivery Network (SDN).
     *
     * You can send IP updates, retrieve software update information, check
     * dynamic VPN status, etc.  The method will immediately attempt a
     * connection with alternate servers in the SDN if a previous connection
     * attempt fails.
     *
     * @param string $action     action
     * @param string $postfields post fields (eg ?ip=1.2.3.4)
     *
     * @access private
     * @return string payload
     * @throws Engine_Exception, Validation_Exception, Remote_Exception
     */

    public function request($action, $postfields = "")
    {
        clearos_profile(__METHOD__, __LINE__);

        // The FILE_REGISTERED file is created when a box is successfully
        // registered.  We check to see if this file exists to save the
        // backend from handling endless invalid requests.

        if (! file_exists(self::FILE_REGISTERED))
            throw new Not_Registered_Exception();

        $sdnerror = "";
        $resource = strtolower($this->service) . ".php?action=" . strtolower($action) . $postfields;

        // We always send the following information:
        // - hostkey
        // - vendor
        // - OS name
        // - OS version
        // - language (error messages will be translated one of these days)

        if (!$this->hostkey || !$this->vendor || !$this->langcode)
            $this->_load_default_request_fields();

        $resource .= "&hostkey=" . $this->hostkey . 
            "&vendor=" . $this->vendor . 
            "&osversion=" . $this->os_version . 
            "&osname=" . urlencode($this->os_name) . 
            "&lang=" . $this->langcode;

        for ($inx = 1; $inx <= $this->config['sdn_servers']; $inx++) {
            $server = $this->config['sdn_prefix'] . "$inx" . "." . $this->config['sdn_domain'];

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

            curl_setopt($ch, CURLOPT_URL, "https://" . $server . "/" . $this->config['sdn_version'] . "/" . $resource);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            $rawmessage = chop(curl_exec($ch));
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            // Return useful errno messages for the most common errnos
            //--------------------------------------------------------

            if ($errno == 0) {

                //------------------------------------------------------------------
                //
                // Data is ok... return the payload and error message (if any)
                //
                // payload format:
                // exit_code|<code>|<error message>
                // <payload>
                //
                // e.g., a request for dynamic DNS information looks like:
                // exit_code|0|ok
                // 123.12.12.32|1052023323|test.system.net
                //
                //------------------------------------------------------------------

                // Make sure the return data is valid

                if (!preg_match("/exit_code|\d|/", $rawmessage)) {
                    $sdnerror = "$server: invalid page or data";
                } else {
                    $message = explode("\n", $rawmessage, 2);
                    $returned = explode("|", $message[0], 3);

                    if (!isset($returned[1])) {
                        $sdnerror = "$server: invalid return code";
                    } else if ($returned[1] == self::CONSTANT_NOT_REGISTERED) {
                        throw new Not_Registered_Exception();
                    } else if ($returned[1] != 0) {
                        throw new Remote_Exception($returned[2], CLEAROS_INFO);
                    } else {
                        // Not all replies have a payload (just TRUE/FALSE)
                        if (isset($message[1]))
                            return $message[1];
                        else
                            return "";
                    }
                }
            }
        }

        // None of the SDN servers responded -- send the last error code.
        if ($sdnerror)
            throw new Engine_Exception($sdnerror, CLEAROS_WARNING);
        else if ($errno == CURLE_COULDNT_RESOLVE_HOST)
            throw new Engine_Exception(lang('clearcenter_dns_lookup_failed'), CLEAROS_WARNING);
        else if ($errno == CURLE_OPERATION_TIMEOUTED)
            throw new Engine_Exception(lang('clearcenter_unable_to_contact_remote_server'), CLEAROS_WARNING);
        else
            throw new Engine_Exception(lang('clearcenter_connection_failed:') . ' ' . $error, CLEAROS_WARNING);
    }

    /**
     * Returns subscription status.
     *
     * Information in hash array includes:
     *  - policy
     *  - expiry
     *  - license
     *  - title
     *  - subscribed
     *  - status
     *
     * @param boolean $real_time set real_time to TRUE to fetch real-time data
     *
     * @return array information about subscription
     * @throws Engine_Exception, Validation_Exception, Remote_Exception
     */

    public function get_subscription_status($real_time)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->cachefile);

        $file_exists = $file->exists();

        if (!$real_time && $file_exists) {
            $subscribed = $file->lookup_value("/^subscribed\s*=\s*/");
            $state = $file->lookup_value("/^state\s*=\s*/");

            $info["subscribed"] = ($subscribed == "t") ? TRUE : FALSE;
            $info["state"] = ($state == "t") ? TRUE : FALSE;

            $info["policy"] = $file->lookup_value("/^policy\s*=\s*/");
            $info["expiry"] = $file->lookup_value("/^expiry\s*=\s*/");
            $info["license"] = $file->lookup_value("/^license\s*=\s*/");
            $info["title"] = $file->lookup_value("/^title\s*=\s*/");
            $info["message"] = $file->lookup_value("/^message\s*=\s*/");
            $info["updated"] = $file->lookup_value("/^updated\s*=\s*/");
            $info["cached"] = $file->last_modified();

            return $info;
        } else if (!$real_time && !$file_exists) {
            throw new Remote_Unavailable_Exception();
        }

        // Catch the harmless exceptions -- we do not want them to
        // throw a CLEAROS_ERROR exception.

        try {
            $payload = $this->request("GetSubscriptionInfo");
        } catch (Not_Registered_Exception $e) {
            throw new Not_Registered_Exception();
        } catch (Remote_Exception $e) {
            throw new Remote_Exception($e->get_message(), CLEAROS_WARNING);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), CLEAROS_ERROR);
        }

        // payload format -- subscribed|policy|expiry|license_type|title|message|state
        $result = explode("|", $payload);

        if ($result[0] == "t")
            $info["subscribed"] = TRUE;
        else
            $info["subscribed"] = FALSE;

        $info["policy"] = $result[1];
        $info["expiry"] = $result[2];
        $info["license"] = $result[3];
        $info["title"] = $result[4];
        $info["message"] = $result[5];

        if ($result[6] == "t")
            $info["state"] = TRUE;
        else
            $info["state"] = FALSE;

        $info["updated"] = $result[7];

        // Cache info
        //-----------

        $folder = new Folder(self::PATH_APPS . "/$this->service");

        if (! $folder->exists())
            $folder->create("suva", "suva", "0755");

        if ($file_exists)
            $file->delete();

        $file->create("suva", "suva", "0644");
        $file->add_lines(
            "subscribed = $result[0]\n" .
            "policy = $result[1]\n" .
            "expiry = $result[2]\n" .
            "license = $result[3]\n" .
            "title = $result[4]\n" .
            "message = $result[5]\n" .
            "state = $result[6]\n" .
            "updated = $result[7]\n"
        );

        $info['cached'] = time();

        return $info;
    }

    /**
     * Loads default remote request fields.
     * 
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_default_request_fields()
    {
        clearos_profile(__METHOD__, __LINE__);

        $suva = new Suva();
        $this->hostkey = $suva->get_hostkey();

        $product = new Product();
        $this->os_name = $product->get_name();
        $this->os_version = $product->get_version();
        $this->vendor = $product->get_vendor();
    
        $language= new Locale();
        $this->langcode = $language->get_language_code();
    }
}
