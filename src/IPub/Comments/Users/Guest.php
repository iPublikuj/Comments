<?php
/**
 * Guest.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Users
 * @since		5.0
 *
 * @date		03.05.14
 */

namespace IPub\Comments\Users;

use Nette;
use Nette\Caching;
use Nette\Utils;

use Kdyby\Doctrine;

use IPub\Comments\Entities;

use IPub\Gravatar\Gravatar;

class Guest implements IUser
{
	/**
	 * Define user type name
	 */
	protected $type = Entities\IAuthor::USER_TYPE_GUEST;

	/**
	 * @var string
	 */
	protected $id = NULL;

	/**
	 * @var string
	 */
	protected $name = 'Guest';

	/**
	 * @var string
	 */
	protected $email = NULL;

	/**
	 * @var string
	 */
	protected $website = NULL;

	/**
	 * @var mixed
	 */
	protected $accessToken;

	/**
	 * @var Caching\Cache
	 */
	protected $cache;

	/**
	 * @var Gravatar
	 */
	protected $gravatar;

	/**
	 * Module helper contstructor
	 *
	 */
	public function __construct($name=NULL, $email=NULL, $website=NULL, $id=NULL)
	{
		// Set vars
		$this->name		= $name;
		$this->email	= $email;
		$this->website	= $website;
		$this->id		= $id;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return bool
	 */
	public function isGuest()
	{
		return $this->type == Entities\IAuthor::USER_TYPE_GUEST;
	}

	/**
	 * @return bool
	 */
	public function isSiteAdmin()
	{
		return FALSE;
	}

	/**
	 * @param string $id
	 *
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = (string) $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		// For guest user create some unique id
		if ($this->isGuest() && !$this->id) {
			$this->id = uniqid();
		}

		return $this->id;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = (string) $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return (string) $this->name;
	}

	/**
	 * @param string $email
	 *
	 * @return $this
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function setEmail($email)
	{
		// Check if email is valid
		if (!Utils\Validators::isEmail($email)) {
			throw new Nette\InvalidArgumentException('Invalid email given');
		}

		$this->email = (string) $email;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return (string) $this->email;
	}

	/**
	 * @param string $website
	 *
	 * @return $this
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function setWebsite($website)
	{
		if (!Utils\Validators::isUrl($website)) {
			throw new Nette\InvalidArgumentException('Invalid website given');
		}

		$this->website = (string) $website;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getWebsite()
	{
		return (string) $this->website;
	}

	/**
	 * @param Caching\Cache $cache
	 *
	 * @return $this
	 */
	public function setCache(Caching\Cache $cache)
	{
		$this->cache = $cache;

		return $this;
	}

	/**
	 * Set gravatar service
	 *
	 * @param Gravatar $gravatar
	 *
	 * @return $this
	 */
	public function setGravatar(Gravatar $gravatar = NULL)
	{
		$this->gravatar = $gravatar;

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
			// Create default avatar in base64 coding
			$avatarUrl = sprintf('data:image/%s;base64,%s', 'png', base64_encode(file_get_contents(realpath(__DIR__ .'/../../../../client-side/') .'/avatar.png')));

			// Only if user have email & gravatar extension is loaded
			if ($this->email && $this->gravatar instanceof Gravatar) {
				// Generate gravatar url
				$gravatarUrl = $this->gravatar->setDefaultImage('404')->buildUrl($this->email, $size);

				// Get headers from gravatar
				$fileHeaders = get_headers($gravatarUrl);

				// Check for 404 page
				if (substr($fileHeaders[0], 9, 3) != '404') {
					$avatarUrl = $gravatarUrl;
				}
			}

			// Store facebook avatar url into cache
			$this->cache->save('avatar.'. $this->type .'.'. $this->id, $avatarUrl, array(
				Caching\Cache::EXPIRE => '7 days',
			));

			return $avatarUrl;

		} else {
			return $avatarUrl;
		}

		return $avatarUrl;
	}

	/**
	 * @param mixed $accessToken
	 *
	 * @return $this
	 */
	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}
}