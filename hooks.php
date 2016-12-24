<?php

namespace google_data;

/**
 * Implements hook_permission()
 */
function permission() {
    return array(
        'administer_google_data',
        'access_google_data'
    );
}

/**
 * Implements hook_menu()
 */
function menu() {
    $items['secondary_menu']['/admin/google_data'] = array(
        'link' => l('Google Data', '/admin/google_data/'),
        'class' => array(),
        'weight' => 0,
        'access' => 'administer_google_data',
        'menu' => array()
    );
    return $items;
}

/**
 * Implements hook_url()
 */
function url() {
    return array(
        '/admin/google_data/?(\?.*)?' => 'google_data\controller\AdminGoogleData',
    );
}

/**
 * Implements hook_libraries()
 */
function libraries() {
    return array(
        'GoogleDataAPI.php'
    );
}

/**
 * Implements hook_cron()
 */
function cron() {
    // execute actions to be performed on cron
}

/**
 * Implements hook_twig_function()
 */
function twig_function() {
    // return an array of key value pairs.
    // key: twig_function_name
    // value: actual_function_name
    // You may use object functions as well
    // e.g. ObjectClass::actual_function_name
    return array();
}
