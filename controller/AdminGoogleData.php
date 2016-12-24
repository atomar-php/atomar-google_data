<?php

namespace google_data\controller;

use atomic\core\Auth;
use atomic\core\Controller;
use google_data\GoogleDataAPI;

class AdminGoogleData extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('administer_google_data');

        $calendar = variable_get('google_data_calendar');
        $cache_lifetime = variable_get('google_data_cache_lifetime', 120);
        $feed_limit = variable_get('google_data_max_events', 4);
        $api_token = variable_get('google_data_api_token');

        // render page
        echo $this->render_view('google_data/views/admin.settings.html', array(
            'calendar' => $calendar,
            'cache_lifetime' => $cache_lifetime,
            'feed_limit' => $feed_limit,
            'api_token' => $api_token
        ));
    }

    function POST($matches = array()) {
        Auth::authenticate('administer_google_data');

        $calendar = $_REQUEST['calendar'];
        if (is_email($calendar)) {
            variable_set('google_data_calendar', $calendar);
        } else {
            set_error('Invalid calendar');
        }

        variable_set('google_data_cache_lifetime', $_REQUEST['cache_lifetime']);
        variable_set('google_data_max_events', $_REQUEST['feed_limit']);
        variable_set('google_data_api_token', $_REQUEST['api_token']);

        // delete the cache so that our settings will become immediately available.
        GoogleDataAPI::cache_clear();

        set_success('Your settings have been saved.');

        $this->go('/admin/google_data');
    }
}