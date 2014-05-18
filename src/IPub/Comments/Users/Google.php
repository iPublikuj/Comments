<?php
/**
 * Google.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Users
 * @since		5.0
 *
 * @date		06.05.14
 */

namespace IPub\Comments\Users;

use Nette;
use Nette\Caching;
use Nette\Diagnostics\Debugger;

use IPub\Comments\Entities;

use Kdyby\Google\Google as GoogleClient;

class Google extends Guest
{
	/**
	 * Define user type name
	 */
	protected $type = Entities\IAuthor::USER_TYPE_GOOGLE;

	/**
	 * @var GoogleClient
	 */
	protected $googleClient;

	/**
	 * Set google client to user
	 *
	 * @param GoogleClient $googleClient
	 *
	 * @return $this
	 */
	public function setGoogleClient(GoogleClient $googleClient)
	{
		$this->googleClient = $googleClient;

		return $this;
	}

	/**
	 * @param int $size
	 *
	 * @return string
	 */
	public function getAvatar($size = 32)
	{
		// Check if avatar is in cache
		if (!$avatarUrl = $this->cache->load('avatar.'. $this->type .'.'. $this->id)) {
			// Check for google client and user access token
			if ($this->googleClient && $this->accessToken) {
				try {
					// Set user access token to google client
					$this->googleClient->setAccessToken($this->accessToken);
					// Get profile
					$profile = $this->googleClient->getProfile();

					// Get avatar url from profile
					$avatarUrl = $profile->getPicture();

					// Store facebook avatar url into cache
					$this->cache->save('avatar.'. $this->type .'.'. $this->id, $avatarUrl, array(
						Caching\Cache::EXPIRE => '7 days',
					));

					return $avatarUrl;

				} catch (\Google_Exception $ex) {
					Debugger::log('Author logged via google has expired access token and is withou refresh token.', 'comments');
				}
			}

		} else {
			return $avatarUrl;
		}

		return parent::getAvatar($size);
	}
}