<?php
/**
 * System.php
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

use Tracy\Debugger;

use IPub\Comments\Entities;

class System extends Guest
{
	/**
	 * Define user type name
	 */
	protected $type = Entities\IAuthor::USER_TYPE_SYSTEM;

	/**
	 * @var mixed
	 */
	protected $systemUser;

	/**
	 * Set system user entity
	 *
	 * @param mixed $systemUser
	 *
	 * @return $this
	 */
	public function setSystemUser($systemUser)
	{
		$this->systemUser = $systemUser;

		return $this;
	}

	/**
	 * @param int $size
	 *
	 * @return string
	 */
	public function getAvatar($size=32)
	{
		// Check if avatar is in cache
		if (!$avatarUrl = $this->cache->load('avatar.'. $this->type .'.'. $this->id)) {
			try {
				// Try to get avatar from system user
				if ($avatarPath = $this->systemUser->getAvatarPath()) {
					// Check given avatar file with path
					if (is_file($avatarPath) && filesize($avatarPath) <= 10240 && preg_match('/\.(gif|png|jpg)$/i', $avatarPath, $extension)) {
						// Create default avatar in base64 coding
						$avatarUrl = sprintf('data:image/%s;base64,%s', str_replace('jpg', 'jpeg', strtolower($extension[1])), base64_encode(file_get_contents($avatarPath)));

						// Store facebook avatar url into cache
						$this->cache->save('avatar.'. $this->type .'.'. $this->id, $avatarUrl, array(
							Caching\Cache::EXPIRE => '7 days',
						));

						return $avatarUrl;
					}
				}

			} catch (\Exception $ex) {
				Debugger::log('Application user doesn\'t implement method getAvatarPath() which should return absolute avatar image path.', 'comments');
			}

		} else {
			return $avatarUrl;
		}

		return parent::getAvatar($size);
	}
}