<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'clearcenter';
$app['version'] = '1.0.6';
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
    'app-language-core',
    'app-suva-core',
);

$app['core_file_manifest'] = array( 
   'clearcenter-update' => array(
        'target' => '/usr/sbin/clearcenter-update',
        'mode' => '0755',
    ),
   'wc-yum' => array(
        'target' => '/usr/sbin/wc-yum',
        'mode' => '0755',
    ),
   'marketplace_version_ctl' => array(
        'target' => '/usr/sbin/marketplace_version_ctl',
        'mode' => '0755',
    ),
    'clearos-gpg-key' => array('target' => '/etc/pki/rpm-gpg/clearos-gpg-key'),
);

$app['core_directory_manifest'] = array( 
   '/var/clearos/clearcenter' => array(),
);
