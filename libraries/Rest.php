<?php

/**
 * Rest class.
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

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\OS as OS;
use \clearos\apps\base\Product as Product;
use \clearos\apps\base\Webconfig as Webconfig;
use \clearos\apps\language\Locale as Locale;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\suva\Suva as Suva;
use \clearos\apps\base\Daemon as Daemon;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/OS');
clearos_load_library('base/Product');
clearos_load_library('base/Webconfig');
clearos_load_library('language/Locale');
clearos_load_library('base/Shell');
clearos_load_library('suva/Suva');
clearos_load_library('base/Daemon');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Rest for ClearCenter web-services class.
 *
 * @category   apps
 * @package    clearcenter
 * @subpackage libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/clearcenter/
 */

class Rest extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const GENERAL_ERR = 1;
    const NO_SUCH_METHOD = 2;
    const DEVICE_NOT_REGISTERED = 3;
    const AUTH_ERR = 4;
    const SESSION_TIMEOUT = 5;
    const INVALID_TOKEN = 6;
    const COMMAND_NSCD = '/usr/sbin/nscd';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $service;
    protected $cachefile;
    protected $is_loaded = FALSE;
    protected $langcode = "en_US";
    protected $hostkey = "";
    protected $vendor = "";
    protected $os_version = "";
    protected $os_name = "";
    protected $software_id = 0;
    protected $jws_nodes = 0;
    protected $jws_domain = "";
    protected $jws_realm = "";
    protected $jws_version = 0;
    protected $jws_prefix = "";
    protected $CI = NULL;
    protected $is_cli = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Rest constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
        // See how we were called...may not have session instance
        if (php_sapi_name() === 'cli') {
            $this->is_cli = TRUE;
        } else {
            $this->CI =& get_instance();
            // Don't change random range below without looking at regex in delete_cache function
            if ($this->CI->session->userdata('sdn_rest_id') == NULL)
                $this->CI->session->set_userdata(array('sdn_rest_id' => rand(10000, 10000000)));
        }
    }

    /**
     * Get SDN information.
     *
     * @param boolean $realtime set realtime to TRUE to fetch real-time data
     *
     * @return Object JSON-encoded response
     * @throws Engine_Exception
     */

    public function get_sdn_info($realtime = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $cachekey = __CLASS__ . '-' . __FUNCTION__; 

            if (!$realtime && $this->_check_cache($cachekey))
                return $this->cache;
    
            $result = $this->request('sdn', 'get_sdn_info');

            $this->_save_to_cache($cachekey, $result);

            return $result;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Check SDN authentication.
     *
     * @param string $username username
     * @param string $password password
     * @param string $email    e-mail
     *
     * @return Object JSON-encoded response
     * @throws Engine_Exception
     */

    public function is_authenticated($username = NULL, $password = NULL, $email = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
    
            $extras = array();
            if ($username != NULL)
                $extras['username'] = $username;
            if ($password != NULL)
                $extras['password'] = $password;
            if ($email != NULL)
                $extras['email'] = $email;

            $result = $this->request('sdn', 'is_authenticated', $extras);

            return $result;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Save to cache
     *
     * @param string $sig    signature
     * @param string $result cached data
     *
     * @access private
     *
     * @return void
     */

    protected function _save_to_cache($sig, $result)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {
            if ($this->is_cli)
                return;
            $json = json_decode($result);
            if (!is_object($json) || $json->code !== 0)
                return;
            file_put_contents(CLEAROS_CACHE_DIR . "/" . md5($sig) . "." . $this->CI->session->userdata['sdn_rest_id'], $result);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }
    }

    /**
     * Check the cache availability
     *
     * @param string $sig signature
     *
     * @access private
     *
     * @return boolean true if cached data available
     */

    protected function _check_cache($sig)
    {
        clearos_profile(__METHOD__, __LINE__, $sig);

        try {
            if ($this->is_cli)
                return FALSE;
            // 2 hours in seconds
            $cache_time = 7200;
            $filename = CLEAROS_CACHE_DIR . "/" . md5($sig) . "." . $this->CI->session->userdata['sdn_rest_id']; 

            if (file_exists($filename))
                $lastmod = filemtime($filename);
            else
                $lastmod = 0;

            if ($lastmod && (time() - $lastmod < $cache_time)) {
                $this->cache = file_get_contents($filename);
                $json = json_decode($this->cache);
                if ($json->code === 0)
                    return TRUE;
            }
            return FALSE;
        } catch (Exception $e) {
            clearos_profile('Cache Error occurred ' . clearos_exception_message($e), __LINE__);
            return FALSE;
        }
    }

    /**
     * Get cache size.
     *
     * @return int cache size in bytes
     *
     * @throws Engine_Exception
     */

    public function get_cache_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder(CLEAROS_CACHE_DIR);
        return $folder->get_size();
    }

    /**
     * Deletes a cache file.
     *
     * @param string  $filename  a filename or set to NULL for all cache files
     * @param boolean $force_all flag to delete all cache files created by this library
     *
     * @return array information
     *
     * @throws Engine_Exception
     */

    public function delete_cache($filename = NULL, $force_all = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_cli)
            return;
        $folder = new Folder(CLEAROS_CACHE_DIR);
        $listing = $folder->get_listing(TRUE);
        foreach ($listing as $element) {
            if ($force_all) {
                if (preg_match("/^app-logo.*\.png$/", $element['name'])) {
                    // Delete app logos
                } else if (preg_match("/^app-screenshots.*\.png$/", $element['name'])) {
                    // Delete app screenshots
                } else if (preg_match("/^app-.*\.[\d]{5,8}$/", $element['name'])) {
                    // Delete app cart info
                } else if (preg_match("/^[a-f0-9]{32}\.[\d]{5,8}$/", $element['name'])) {
                    // Delete app cart info
                } else {
                    // Don't delete anything else
                    continue;
                }
            } else if ($filename == NULL && !preg_match("/.*" . $this->CI->session->userdata['sdn_rest_id'] . "$/", $element['name'])) {
                continue;
            } else if ($filename != NULL && $filename != $element['name']) {
                continue;
            }

            $file = new File(CLEAROS_CACHE_DIR . "/" . $element['name']);

            if ($file->exists())
                $file->delete();
        }
    }

    /**
     * A generic way to communicate with the Service Delivery Network (SDN) using REST.
     *
     * @param string $resource   resource
     * @param string $method     method
     * @param string $parameters parameters to pass (eg ip=1.2.3.4)
     *
     * @return string JSON encoded response
     * @throws Engine_Exception
     */

    public function request($resource, $method, $parameters = "")
    {
        clearos_profile(__METHOD__, __LINE__);

        // We always send the following information:
        // - hostkey
        // - vendor
        // - OS name
        // - OS version
        // - language (error messages will be translated one of these days)

        // Check hostkey as bellwether
        if (!$this->hostkey)
            $this->_load_defaults();

        $data = "method=" . $method .
            "&hostkey=" . $this->hostkey . 
            "&vendor=" . urlencode($this->vendor) . 
            "&software_id=" . $this->software_id . 
            "&os_version=" . urlencode($this->os_version) . 
            "&os_name=" . urlencode($this->os_name) . 
            "&language=" . $this->langcode;

        if (!$this->is_cli) {
            if ($this->CI->session->userdata('sdn_token')) {
                if (is_array($parameters))
                    $parameters['token'] = $this->CI->session->userdata('sdn_token');
                else
                    $parameters = array('token' => $this->CI->session->userdata('sdn_token'));
            }
        }
            
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value)
                $data .= "&" . urlencode($key) . "=" . urlencode($value);
        }

        // TODO - Use same server to preserve session.
        for ($inx = 1; $inx <= $this->jws_nodes; $inx++) {
            $server = $this->jws_prefix . "$inx" . "." . $this->jws_domain;
            $url = "https://" . $server . "/" . $this->jws_realm . "/" . $this->jws_version . "/" . $resource . "/";

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

            curl_setopt($ch, CURLOPT_URL, $url . "?" . $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            if (!$this->is_cli) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, CLEAROS_TEMP_DIR . "/cookie." . $this->CI->session->userdata('sdn_rest_id'));
                curl_setopt($ch, CURLOPT_COOKIEFILE, CLEAROS_TEMP_DIR . "/cookie." . $this->CI->session->userdata('sdn_rest_id'));
            }
            $curl_response = chop(curl_exec($ch));
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            // Return useful errno messages for the most common errnos
            //--------------------------------------------------------

            if ($errno == 0) {
                // Check for token and other SDN variables we save in session
                $json = json_decode($curl_response);
                if (!$this->is_cli && $json != NULL) {
                    if (! empty($json->sdn_username))
                        $this->CI->session->set_userdata(array('sdn_username' => $json->sdn_username));
                    if (! empty($json->sdn_org))
                        $this->CI->session->set_userdata(array('sdn_org' => $json->sdn_org));
                    if (! empty($json->sdn_token))
                        $this->CI->session->set_userdata(array('sdn_token' => $json->sdn_token));
                    // Check for invalid token or session timeout...good to clear out session data
                    if (isset($json->code) && ($json->code == self::SESSION_TIMEOUT || $json->code == self::INVALID_TOKEN))
                        $this->CI->session->unset_userdata('sdn_token');
                }
                return $curl_response;
            }
        }

        // None of the SDN servers responded -- send the last error code.
        if ($errno == CURLE_COULDNT_RESOLVE_HOST) {
            try {
                // Ensure an invalid IP has not been cached
                // Reset DNS cache
                clearos_profile(__METHOD__, __LINE__, 'Resetting DNS cache...');
                $dnsmasq = new Daemon('dnsmasq');
                $dnsmasq->reset();
                
                $shell = new Shell();
                // Purge DNS caching
                $shell->execute(self::COMMAND_NSCD, "-i hosts", TRUE);
            } catch (Engine_Exception $e) {
                // Carry on ... We're going to throw an exception anyways
            }
            throw new Engine_Exception(lang('clearcenter_dns_lookup_failed'), CLEAROS_INFO);
        } else if ($errno == CURLE_SSL_CACERT || $errno == 77) {
            // This is the "problem with the SSL CA cert (path? access rights)?" error
            $webconfig = new Webconfig();
            $webconfig->reset_gently();
        } else if ($errno == CURLE_OPERATION_TIMEOUTED) {
            throw new Engine_Exception(lang('clearcenter_unable_to_contact_remote_server'), CLEAROS_INFO);
        } else {
            throw new Engine_Exception(lang('clearcenter_connection_failed:') . ' ' . $error, CLEAROS_INFO);
        }
    }

    /**
     * Loads default variables.
     * 
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_defaults()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $suva = new Suva();
            $this->hostkey = $suva->get_hostkey();

            $os = new OS();
            $this->os_name = $os->get_name();
            $this->os_version = $os->get_version();
        
            $locale = new Locale();
            $this->langcode = $locale->get_language_code();

            $product = new Product();
            $this->software_id = $product->get_software_id();
            $this->vendor = $product->get_vendor();
            $this->jws_nodes = $product->get_jws_nodes();
            $this->jws_domain = $product->get_jws_domain();
            $this->jws_realm = $product->get_jws_realm();
            $this->jws_version = $product->get_jws_version();
            $this->jws_prefix = $product->get_jws_prefix();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }
    
}

?>
