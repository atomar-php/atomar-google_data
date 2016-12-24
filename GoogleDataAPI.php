<?php

namespace google_data;

use atomic\Atomic;
use atomic\core\Logger;

require_once 'vendor/autoload.php';

/**
 * This is the internal api class that can be used by third party extensions
 */
class GoogleDataAPI {
    private static $cache = 'google_data/';

    public static function get_upcoming_events($options = array()) {
        if (!is_dir(Atomic::$config['cache'] . self::$cache)) {
            mkdir(Atomic::$config['cache'] . self::$cache);
        }
        $events_cache = Atomic::$config['cache'] . self::$cache . 'upcoming_events';

        if (Atomic::$config['debug']) {
            $cache_life = 0;
        } else {
            $cache_life = variable_get('google_data_cache_lifetime');
        }

        $filemtime = self::cache_current_life($events_cache);
        if (!$filemtime or (time() - $filemtime >= $cache_life)) {
            // retrieve, process, and cache the feed data
            $event_feed = self::get_event_feed($options);
            $data = self::process_event_feed($event_feed);
            self::cache_write($events_cache, $data);
        } else {
            // read from the cache
            $data = self::cache_read($events_cache);
        }
        return $data;
    }

    private static function get_event_feed($options) {
        $event_feed = array();

        try {
            $client = self::getClient();

            $calendar = variable_get('google_data_calendar');
            $feed_limit = variable_get('google_data_max_events');

            if ($calendar == null) {
                Logger::log_warning("Missing the google data user. Make sure you have properly configured the module.");
                return array();
            } else {
                $service = new \Google_Service_Calendar($client);
                $now = new \DateTime('NOW');
                $event_feed = $service->events->listEvents($calendar, array(
                    'orderBy' => 'starttime',
                    'singleEvents' => true,
                    'maxResults' => isset($options['limit']) ? $options['limit'] : $feed_limit,
                    'timeMin' => $now->format('c'),
                ));
            }
        } catch(\Exception $e) {
            Logger::log_error('Failed to get the event feed', $e->getMessage());
        }
        return $event_feed;
    }

    private static function getClient() {
        $client = new \Google_Client();
        $client->setDeveloperKey(variable_get('google_data_api_token'));
        $client->setScopes(array('https://www.googleapis.com/auth/calendar.readonly'));
        return $client;
    }

    /**
     * Creates the client object from the service account
     * @param $service_email the service account email
     * @param $private_key the service account private key
     * @param $user_to_impersonate the user to impersonate in the organization
     * @return \Google_Client
     */
    private static function getServiceClient($service_email, $private_key, $user_to_impersonate) {
        $scopes = array('https://www.googleapis.com/auth/calendar.readonly');
        $credentials = new \Google_Auth_AssertionCredentials(
            $service_email,
            $scopes,
            $private_key,
            'notasecret',
            'http://oauth.net/grant_type/jwt/1.0/bearer',
            $user_to_impersonate
        );

        $client = new \Google_Client();
        $client->setAssertionCredentials($credentials);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion();
        }

        return $client;
    }

    private static function process_event_feed($event_feed) {
        // return $event_feed;
        $data = array();
        if(isset($event_feed['modelData'])) {
            foreach ($event_feed['modelData']['items'] as $event) {
                $node = array();
                $start_date = new \DateTime($event['start']['dateTime']);
                $end_date = new \DateTime($event['end']['dateTime']);
                $node['startTime'] = $start_date->format('Y-m-d H:i:s');
                $node['endTime'] = $end_date->format('Y-m-d H:i:s');
                $node['title'] = $event['summary'];
                $data[] = $node;
            }
        }
        return $data;
    }

    /**
     * get the current cache lifetime
     * @param $cache
     * @return int
     */
    private static function cache_current_life($cache) {
        return @filemtime($cache); // returns false if file does not exist
    }

    /**
     * Save the event feed to the cache
     * @param array the event feed.
     */
    private static function cache_write($cache, $event_feed) {
        try {
            $feed_data = new GData($event_feed);
            $s = serialize($feed_data);
            file_put_contents($cache, $s);
        } catch (Exception $e) {
            Logger::log_error('failed to cache the google events feed', $e->getMessage());
        }
    }

    /**
     * Deletes the cache file
     *
     */
    public static function cache_clear() {
        if (file_exists(Atomic::$config['cache'] . self::$cache)) {
            unlink(Atomic::$config['cache'] . self::$cache);
        }
    }

    /**
     * Read the event feed from the cache
     * @return the event feed
     */
    private static function cache_read($cache) {
        if(file_exists($cache)) {
            $s = file_get_contents($cache);
            $feed_data = unserialize($s);
            return $feed_data->data();
        } else {
            return '';
        }
    }
}

/**
 * Class to encapsulate feed data for serialization
 */
class GData {
    private $data;

    function __construct($newData = array()) {
        $this->data = $newData;
    }

    /**
     * Gives back the data
     * @return array
     */
    public function data() {
        return $this->data;
    }
}