<?php
/**
 * Github.php
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

use IPub\Comments\Entities;

use Kdyby\Github\Client as GithubClient;

class Github extends Guest
{
	/**
	 * Define user type name
	 */
	protected $type = Entities\IAuthor::USER_TYPE_GITHUB;

	/**
	 * @var GithubClient
	 */
	protected $githubClient;

	/**
	 * Set github client to user
	 *
	 * @param GithubClient $githubClient
	 *
	 * @return $this
	 */
	public function setGithubClient(GithubClient $githubClient)
	{
		$this->githubClient = $githubClient;

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
			if ($this->githubClient && $profile = $this->githubClient->getProfile($this->id)) {
				// Get avatar url from profile
				$avatarUrl = $profile->getDetails('avatar_url');

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