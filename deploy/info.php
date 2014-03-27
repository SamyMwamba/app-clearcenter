<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'clearcenter';
$app['version'] = '1.5.11';
$app['release'] = '1';
$app['vendor'] = 'ClearCenter';
$app['packager'] = 'ClearCenter';
$app['license'] = 'Proprietary';
$app['license_core'] = 'Proprietary';
$app['description'] = lang('clearcenter_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('clearcenter_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-base-core >= 1:1.5.31',
    'app-language-core',
    'app-suva-core',
    'csplugin-audit',
    'webconfig-zend-guard-loader'
);

$app['core_file_manifest'] = array( 
    'clearcenter-update' => array(
        'target' => '/usr/sbin/clearcenter-update',
        'mode' => '0755',
    ),
    'clearcenter-subscriptions' => array(
        'target' => '/usr/sbin/clearcenter-subscriptions',
        'mode' => '0755',
    ),
    'marketplace_version_ctl.sh' => array(
        'target' => '/usr/sbin/marketplace_version_ctl.sh',
        'mode' => '0755',
    ),
    'clearos-gpg-key' => array('target' => '/etc/pki/rpm-gpg/clearos-gpg-key'),
    'license.ini' => array('target' => '/usr/clearos/sandbox/etc/php.d/license.ini'),
    'license.zl' => array('target' => '/var/clearos/clearcenter/license.zl'),
);

$app['core_directory_manifest'] = array( 
    '/var/clearos/clearcenter' => array(),
    '/var/clearos/clearcenter/apps' => array(),
    '/var/clearos/clearcenter/subscriptions' => array(),
);
