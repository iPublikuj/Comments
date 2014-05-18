<?php
/**
 * Facebook.php
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

use Nette;
use Nette\Caching;

use IPub\Comments\Entities;

use Kdyby\Facebook\Facebook as FacebookClient;

class Facebook extends Guest
{
	/**
	 * Define user type name
	 */
	protected $type = Entities\IAuthor::USER_TYPE_FACEBOOK;

	/**
	 * @var FacebookClient
	 */
	protected $facebookClient;

	/**
	 * Set facebook client to user
	 *
	 * @param FacebookClient $facebookClient
	 *
	 * @return $this
	 */
	public function setFacebookClient(FacebookClient $facebookClient)
	{
		$this->facebookClient = $facebookClient;

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
			// Try to load avatar from facebook
			if ($this->facebookClient && $profile = $this->facebookClient->getProfile($this->id)) {
				// Get avatar url from profile
				$avatarUrl = $profile->getPictureUrl();

				// Store facebook avatar url into cache
				$this->cache->save('avatar.'. $this->type .'.'. $this->id, $avatarUrl, array(
					Caching\Cache::EXPIRE => '7 days',
				));

				return $avatarUrl;
			}

		} else {
			return $avatarUrl;
		}

		return parent::getAvatar($size);
	}
}