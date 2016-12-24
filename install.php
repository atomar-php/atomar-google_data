<?php

namespace google_data;

/**
 * Implements hook_uninstall()
 */
function uninstall() {
    // destroy tables and variables
    return true;
}

/**
 * Implements hook_update_version()
 */
function update_1() {
    // TODO: perform any nessesary database changes when updating to this version.
    return true;
}
