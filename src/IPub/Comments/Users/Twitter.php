<?php
/**
 * Twitter.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Users
 * @since		5.0
 *
 * @date		02.05.14
 */

namespace IPub\Comments\Users;

use IPub\Comments\Entities;

class Twitter extends Guest
{
	/**
	 * Define user type name
	 */
	protected $type = Entities\IAuthor::USER_TYPE_TWITTER;

	/**
	 * @param int $size
	 *
	 * @return string
	 */
	public function getAvatar($size=32)
	{
		// Get app instance
		$app = IPUBCoreApplication::getInstance();

		if ( $this->user_id ) {
			// Avatar url
			$url = '';
			
			if ( ( IPUBFilesystemFiles::exists($this->cacheFile) && $app->timestamp - filemtime($this->cacheFile) > 604800 ) ) {
				IPUBFilesystemFiles::delete($this->cacheFile);
				
				// Reset cache content
				$cache = null;
				
			} else {
				// Get cache content
				$cache = IPUBFilesystemFiles::read($this->cacheFile);
			}

			// Convert to params
			$cache = new IPUBItemParamsHelper(unserialize($cache));

			// Try to get avatar url from cache
			$url = $cache->getParam($this->user_id);

			// if url is empty, try to get avatar url from twitter
			if ( empty($url) ) {
				// Load twitter class
				$app->manualFileLoader('libraries|utilites|webservices|twitter', 'php');
				
				$info = TwitterHelper::fields($this->user_id, array('profile_image_url'), $app->configuration->getVariable('twitter_consumer_key'), $app->configuration->getVariable('twitter_consumer_secret'));
				
				if ( isset($info['profile_image_url']) ) {
					$url = $info['profile_image_url'];
				}

				$cache->setParam($this->user_id, $url);

				// Save cache
				IPUBFilesystemFiles::write($this->cacheFile, $cache->serialize());
			}
			
			if ( !empty($url) ) {
				return '<img alt="'.$this->name.'" title="'.$this->name.'" src="'.$url.'" height="'.$size.'" width="'.$size.'" />';
			}

		}
		
	    return parent::getAvatar($size);
	}
}