<?php
/*
Plugin Name: Last.fm Played
Plugin URI: https://github.com/stephenyeargin/wp-lastfm-played
Description: Simple Last.fm feed reader for WordPress.
Author: Stephen Yeargin
Version: 1.1
Author URI: http://stephenyeargin.com/
*/

/**
 * LastFmSidebar Class
 */
class LastFmSidebar {
  
  function __construct() {
    $this->config = array();
    $this->config['template']= '<li><a href="%url%" title="Played: %played%">%song%</a><br /><small>%artist%</small></li>' . PHP_EOL;
    $this->config['error']= '<li>Unable to reach Last.fm feed for "%username%"</li>';
    $this->config['username'] = 'yearginsm'; // default 
    $this->config['count'] = 5; // default
    $this->config['gmt_offset'] = 3600*get_option('gmt_offset'); // default
    $this->config['url'] = 'http://ws.audioscrobbler.com/1.0/user/' . $this->config['username'] . '/recenttracks.rss';
  }

  /**
   * Set Configuration
   *
   * @param array $config Config variables
   * @return void
   */
  function setConfig($config = array()) {
    $this->config['username'] = $config['username'];
    $this->config['count'] = $config['count'];
  }

  /**
   * Get Last.fm Data
   *
   * @return object $rss_items Data object
   */
  function getLastFmData() {
    $url = $this->config['url'];
    $rss = fetch_feed($url);
    if ( !is_wp_error($rss) && !empty($url) ) : // Checks that the object is created correctly 
        $maxitems = $rss->get_item_quantity($this->config['count']); 
        $rss_items = $rss->get_items(0, $maxitems);
      return $rss_items;
    endif;
  }

  /**
   * Show HTML
   *
   * @return string
   */
  function showHtml() {
    $data = $this->getLastFmData();
    if ( empty($data) ) {
      // Catch error
      $this->showError();
      return;
    }
    
    $i=0;
    foreach ($data as $item) {
      $this->showSong($item);
    } 
  }
  
  /**
   * Show Song
   *
   * @param object $item Called wihin showHtml(), passed single item from $data object
   * @return string
   */
  function showSong($item) {
    
    // Exploding song data into variables
    $info = explode(' – ',$item->get_title());
      $artist= ($info[0]);
      $song = ($info[1]);
      $url = $item->get_link();
      $played = date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->get_date())+$this->config['gmt_offset']);
    
    // Loading template and parsing template
    $html = $this->config['template'];
    $html = str_replace('%artist%', $artist, $html);
    $html = str_replace('%song%', $song, $html);
    $html = str_replace('%url%', $url, $html);
    $html = str_replace('%played%', $played, $html);
    
    echo $html;   
  }

  /**
   * Show Error
   *
   * @return string
   */
  function showError() {
    
    // Loading template and parsing template
    $html = $this->config['error'];
    $html = str_replace('%username%', $this->config['username'], $html);    
    echo $html;   
  }
  
  /**
   * Flush Cache
   *
   * @return void
   */
  function flushCache() {
    global $wpdb;
    $feed_hash = md5($this->config['url']);
    $wpdb->query("UPDATE wp_options SET option_value = 0 WHERE option_name LIKE '%$feed_hash%'; ");
    return;
  }
}

/**
 * Last.fm Basic Function
 *
 * @param string $username Your lastfm username.
 * @param int $count Count of songs to display
 * @return string
 */
function lastfm($username, $count) {
  $lastfm = new LastFmSidebar;
  $lastfm->setConfig(array('username' => $username, 'count' => $count));
  $lastfm->showHtml();
}

/**
 * Last.fm Advanced Function
 *
 * @param string $username Your Last.fm username.
 * @param int $count Count of songs to display
 * @return string
 */
function lastfm2($config) {
  $lastfm = new LastFmSidebar;
  $lastfm->setConfig($config);
  $lastfm->showHtml();
}
