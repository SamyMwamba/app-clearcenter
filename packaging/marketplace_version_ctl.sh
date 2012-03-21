#!/usr/clearos/sandbox/usr/bin/php -q
<?php

/**
 * ClearCenter Marketplace Version Submission Tool.
 *
 * @category   Apps
 * @package    Marketplace
 * @subpackage Libraries
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/app_license ClearCenter license
 * @link       http://www.clearcenter.com/support/documentation/clearos/marketplace/
 */

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

// C O N S T A N T S
//------------------
$WS_HOSTNAME = 'secure.clearcenter.com';
$WS_VERSION = '1.0';

$long_options = array('apikey:', 'action:', 'basename::', 'id::', 'param::', 'help::');

$helpopts  = '
  Common Options
  --------------

  --apikey=API Marketplace Key
  --action=Action
      +-- create_template
      +-- add_version
      +-- get_versions
      +-- update
      +-- details
  --help: help

';

$colours['red'] = '0;31';
$colours['green'] = '0;32';
$colours['yellow'] = '0;33';

$me = shell_exec('whoami');

$options = getopt(NULL, $long_options);

$help = isset($options['help']) ? TRUE : FALSE;

if ($help) {
    echo "usage: " . $argv[0] . " [parameters]\n";
    echo $helpopts;
    exit(0);
}
if (!isset($options['apikey'])) {
    echo "Requires --apikey=API Key\n";
    exit(1);
}
if (!isset($options['action'])) {
    echo "Requires --action=Action (ie. create_template, add_version etc.)\n";
    exit(1);
}

switch ($options['action']) {
    case 'create_template':
        create_template_file();
        return;
    case 'add_version':
        add_version();
        return;
    case 'get_versions':
        get_versions();
        return;
    case 'update':
        update();
        return;
    case 'details':
        details();
        return;
    default:
        echo "\033[" . $colours['red'] . "mInvalid action.\033[0m\n";
        return;
}

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Create template file.
 *
 * @return void
 */

function create_template_file()
{
    global $options;
    global $colours;
    global $me;
    try {
        if (!isset($options['basename'])) {
            echo "Requires --basename parameter\n";
            echo " ie. --basename=content_filter\n";
            exit(1);
        } else {
            $basename = preg_replace('/-/', '_', strtolower($options['basename']));
        }

        $filename = CLEAROS_TEMP_DIR . '/' . $basename . '-' . $me . '.template';
        $file = new File($filename);
        if ($file->exists()) {
            echo "Template file exists - OK to overwrite (y/n)?\n";
            $handle = fopen("php://stdin", "r");
            $response = fgets($handle);
            if(trim($response) != 'y'){
                echo "Exiting.\n";
                exit(1);
            }
            echo "Overwriting file " . $filename . "\n";
            $file->delete();
        }
        $file->create($me, $me, '640');
        $extras = array(
            'apikey' => $options['apikey'],
            'basename' => $basename
        );
        $result = json_decode(request('create_template', $extras));
        $lines = '';
        if ($result->code == 0) {
            foreach ($result->template as $key => $value) {
                $lines .= $key . ' = ' . $value . "\n";
            }
            $file->add_lines($lines);
            echo "File to edit: " . $filename . "\n";
        } else {
            echo "\033[" . $colours['red'] . "m" . $result->msg . "\033[0m\n";
        }
    } catch (Exception $e) {
        echo "An error occurred: " . clearos_exception_message($e) . "\n";
    }
}

/**
 * Display version details.
 *
 * @return void
 */

function details()
{
    global $options;
    global $colours;
    try {
        if (!isset($options['basename'])) {
            echo "Requires --basename parameter\n";
            echo " ie. --basename=content_filter\n";
            exit(1);
        } else {
            $basename = preg_replace('/-/', '_', strtolower($options['basename']));
        }

        if (!isset($options['id'])) {
            echo "\033[" . $colours['yellow'] . "mNo id supplied...fetching most recent entry.\033[0m\n";
            echo "Use --id=n (n is ID from get_versions) to retrieve older versions.\n";
        }
        $extras = array(
            'apikey' => $options['apikey'],
            'basename' => $basename
        );
        $result = json_decode(request('details', $extras));
        if ($result->code == 0) {
            $heading = str_pad("Field", 20) . "Value";
            echo "\n" . $heading . "\n";
            echo preg_replace("/./", "-", $heading) . "\n";
            $details = (array)$result->details;
            ksort($details);
            foreach ($details as $key => $value) {
                echo str_pad($key, 20) . $value . "\n";
            }
        } else {
            echo "\033[" . $colours['red'] . "m" . $result->msg . "\033[0m\n";
        }
    } catch (Exception $e) {
        echo "An error occurred: " . clearos_exception_message($e) . "\n";
    }
}

/**
 * Add Version.
 *
 * @return void
 */

function add_version()
{
    global $options;
    global $colours;
    global $me;
    try {
        if (!isset($options['basename'])) {
            echo "Requires --basename parameter\n";
            echo " ie. --basename=content_filter\n";
            exit(1);
        } else {
            $basename = preg_replace('/-/', '_', strtolower($options['basename']));
        }

        if (isset($options['template'])) {
            $file = new Configuration_File($options['template']);
            if (!$file->exists()) {
                echo "Template file does not exist.\n";
                exit(1);
            }
        } else {
            $filename = CLEAROS_TEMP_DIR . '/' . $basename . '-' . $me . '.template';
            $file = new Configuration_File($filename);
            if (!$file->exists()) {
                echo "Template file does not exist.\n";
                exit(1);
            }
        }
        $extras = $file->load();
        $extras['apikey'] = $options['apikey'];
        $extras['basename'] = $basename;
        $result = json_decode(request('add_version', $extras));
        if ($result->code == 0) {
            echo "Version added\n";
        } else {
            echo "\033[" . $colours['red'] . "m" . $result->msg . "\033[0m\n";
        }
    } catch (Exception $e) {
        echo "An error occurred: " . clearos_exception_message($e) . "\n";
    }
}

/**
 * List app version.
 *
 * @return void
 */

function get_versions()
{
    global $options;
    global $colours;
    try {
        if (!isset($options['basename'])) {
            echo "Requires --basename parameter\n";
            echo " ie. --basename=content_filter\n";
            exit(1);
        } else {
            $basename = preg_replace('/-/', '_', strtolower($options['basename']));
        }

        $extras['apikey'] = $options['apikey'];
        $extras['basename'] = $basename;
        $result = json_decode(request('get_versions', $extras));
        if ($result->code == 0) {
            $heading = "Version history - " . $basename;
            echo "\n" . $heading . "\n";
            echo preg_replace("/./", "=", $heading) . "\n\n";
            echo "ID\tVersion\tRelease\tState\tReleased\tRepo\n";
            foreach ($result->list as $version) {
                echo $version->id . "\t" . $version->version . "\t" . $version->release . "\t" . $version->state
                    . "\t" . $version->released . "\t" . $version->repo_name . "\n";
            }
            echo "\n";
        } else {
            echo "\033[" . $colours['red'] . "m" . $result->msg . "\033[0m\n";
        }
    } catch (Exception $e) {
        echo "An error occurred: " . clearos_exception_message($e) . "\n";
    }
}

/**
 * Update version parameter(s).
 *
 * @return void
 */

function update()
{
    global $options;
    global $colours;
    try {
        if (!isset($options['basename'])) {
            echo "Requires --basename=parameter\n";
            echo " ie. --basename=content_filter\n";
            exit(1);
        } else {
            $basename = preg_replace('/-/', '_', strtolower($options['basename']));
        }
        if (!isset($options['id'])) {
            echo "Requires --id=parameter\n";
            echo " ie. --id=46 (App Version ID can be retrieved from the get_versions routine)\n";
            exit(1);
        }
        if (!isset($options['param'])) {
            echo "Requires --param=\"key1=value1&key2=value2\"\n";
            echo " ie. --param=\"state=1000&released=2012-03-01\n";
            exit(1);
        } else {
            $key_value = preg_split("/\\&/", $options['param']);
            foreach ($key_value as $pair) {
                list($key, $value) = preg_split("/=/", $pair);
                $extras[$key] = $value;
            }
        }

        $extras['apikey'] = $options['apikey'];
        $extras['basename'] = $basename;
        $extras['id'] = $options['id'];
        $result = json_decode(request('update', $extras));
        if ($result->code == 0) {
            echo "App versioning information updated.\n";
        } else {
            echo "\033[" . $colours['red'] . "m" . $result->msg . "\033[0m\n";
        }
    } catch (Exception $e) {
        echo "An error occurred: " . clearos_exception_message($e) . "\n";
    }
}

/**
 * A generic way to communicate with the Service Delivery Network (SDN) using REST.
 *
 * @param string $method     method
 * @param string $parameters parameters to pass (eg ip=1.2.3.4)
 *
 * @return string JSON encoded response
 * @throws Engine_Exception
 */

function request($method, $parameters = "")
{
    global $WS_HOSTNAME;
    global $WS_VERSION;
    $data = "method=" . $method;

    if (is_array($parameters)) {
        foreach ($parameters as $key => $value)
            $data .= "&" . urlencode($key) . "=" . urlencode($value);
    }

    $url = "https://" . $WS_HOSTNAME . "/ws/" . $WS_VERSION . "/developer/";

    if (isset($ch))
        unset($ch);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "?" . $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $curl_response = chop(curl_exec($ch));
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    // Return useful errno messages for the most common errnos
    //--------------------------------------------------------
    if ($errno == 0)
        return $curl_response;
    echo $error . "\n";
}

// vim: syntax=php
