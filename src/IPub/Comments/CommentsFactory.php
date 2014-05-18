<?php
/**
 * CommentsFactory.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	common
 * @since		5.0
 *
 * @date		02.05.14
 */

namespace IPub\Comments;

use Nette;
use Nette\Caching;
use Nette\Http;
use Nette\Security;
use Nette\Utils;

use IPub\Comments\DataProviders;
use IPub\Comments\Entities;
use IPub\Comments\Users;
use IPub\Comments\Templating;

use IPub\Gravatar\Gravatar;

use Kdyby\Facebook;
use Kdyby\Google;
use Kdyby\Github;

class CommentsFactory extends Nette\Object
{
	/**
	 * Actual active author
	 *
	 * @var Users\IUser
	 */
	protected $author;

	/**
	 * Array of module comments
	 *
	 * @var array
	 */
	protected $comments = array();

	/**
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * @var SessionStorage
	 */
	protected $session;

	/**
	 * @var Caching\Cache
	 */
	protected $cache;

	/**
	 * @var DataProviders\IDataProvider
	 */
	protected $dataProvider;

	/**
	 * @var Facebook\Facebook
	 */
	protected $facebook;

	/**
	 * @var Google\Google
	 */
	protected $google;

	/**
	 * @var Github\Client
	 */
	protected $github;

	/**
	 * @var Gravatar
	 */
	protected $gravatar;

	/**
	 * Inject social networks services
	 *
	 * @param Facebook\Facebook $facebook
	 * @param Google\Google $google
	 * @param Github\Client $github
	 */
	public function injectSocialNetworks(
		Facebook\Facebook $facebook = NULL,
		Google\Google $google = NULL,
		Github\Client $github = NULL
	) {
		$this->facebook	= $facebook;
		$this->google	= $google;
		$this->github	= $github;
	}

	/**
	 * @param Configuration $configuration
	 * @param SessionStorage $session
	 * @param DataProviders\DataProvider $dataProvider
	 * @param Caching\IStorage $cacheStorage
	 * @param Gravatar $gravatar
	 */
	public function __construct(
		Configuration $configuration, SessionStorage $session, DataProviders\DataProvider $dataProvider,
		Caching\IStorage $cacheStorage,
		Gravatar $gravatar = NULL
	) {
		// Extension services
		$this->configuration	= $configuration;
		$this->session			= $session;
		$this->dataProvider		= $dataProvider;
		$this->cache			= new Caching\Cache($cacheStorage, 'IPub.Comments');

		// Additional extensions
		$this->gravatar	= $gravatar;
	}

	/**
	 * @param Users\IUser $author
	 *
	 * @return $this
	 */
	public function setAuthor(Users\IUser $author)
	{
		$this->author = $author;

		return $this;
	}

	/**
	 * @return Users\IUser
	 */
	public function getAuthor()
	{
		return $this->author;
	}

	/**
	 * @param DataProviders\IDataProvider $dataProvider
	 *
	 * @return $this
	 */
	public function setDataProvider(DataProviders\IDataProvider $dataProvider)
	{
		$this->dataProvider = $dataProvider;

		return $this;
	}

	/**
	 * @return DataProviders\IDataProvider
	 */
	public function getDataProvider()
	{
		return $this->dataProvider;
	}

	/**
	 * Get all comments to display
	 *
	 * @param Users\IUser $author
	 *
	 * @return array
	 */
	public function getComments(Users\IUser $author)
	{
		// Check if comments are loaded
		if (!$this->comments) {
			// Load comments via data provider
			if ($comments = $this->dataProvider->getComments($author, $this->configuration->displaying->ordering)) {
				$this->comments = $comments;
			}
		}

		return $this->comments;
	}

	/**
	 * Get comments count
	 *
	 * @param Users\IUser $author
	 *
	 * @return int
	 */
	public function getCommentsCount(Users\IUser $author)
	{
		return $this->dataProvider->getCommentsCount($author->isSiteAdmin() ? NULL : $author);
	}

	/**
	 * Create new comment
	 *
	 * @param Nette\ArrayHash $comment
	 * @param Users\IUser $author
	 *
	 * @return Entities\IComment
	 */
	public function createComment(Nette\ArrayHash $comment, Users\IUser $author)
	{
		return $this->dataProvider->createComment($comment, $author);
	}

	/**
	 * Get author last inserted comment
	 *
	 * @param Users\IUser $author
	 * @param string $ipAddress
	 *
	 * @return Entities\IComment|null
	 */
	public function getAuthorLastComment(Users\IUser $author, $ipAddress = NULL)
	{
		return $this->dataProvider->getAuthorLastComment($author, $ipAddress);
	}

	/**
	 * Check if author is verfied
	 *
	 * @param Users\IUser $author
	 *
	 * @return bool
	 */
	public function isAuthorVerified(Users\IUser $author)
	{
		return $this->dataProvider->isAuthorVerified($author);
	}

	/**
	 * Check if actual author is owner of the comment
	 *
	 * @param Entities\IComment $comment
	 *
	 * @return bool
	 */
	public function isOwner(Entities\IComment $comment)
	{
		// Special check for application user
		if ($comment->getAuthor()->getType() == Entities\IAuthor::USER_TYPE_SYSTEM) {
			return $comment->getAuthor()->getUser()->getId() == $this->author->getId();

		// Other social networks users
		} else {
			return ($comment->getAuthor()->getUserId() == $this->author->getId() && $comment->getAuthor()->getType() == $this->author->getType());
		}
	}

	/**
	 * Create comment user
	 *
	 * @param Entities\IAuthor $author
	 *
	 * @return Users\Facebook|Users\Github|Users\Google|Users\Guest|Users\System|Users\Twitter
	 */
	public function createAuthor(Entities\IAuthor $author)
	{
		// Comment author
		switch($author->getType())
		{
			case Entities\IAuthor::USER_TYPE_SYSTEM:
				$user = new Users\System($author->getName(), $author->getEmail(), $author->getWebsite(), $author->getUser()->getId());
				// Set application user
				$user->setSystemUser($author->getUser());
				break;

			case Entities\IAuthor::USER_TYPE_FACEBOOK:
				$user = new Users\Facebook($author->getName(), $author->getEmail(), $author->getWebsite(), $author->getUserId());
				// Set social client service
				$user->setFacebookClient($this->facebook);
				break;

			case Entities\IAuthor::USER_TYPE_TWITTER:
				$user = new Users\Twitter($author->getName(), $author->getEmail(), $author->getWebsite(), $author->getUserId());
				// Set social client service
				$user->setTwitterClient($this->twitter);
				break;

			case Entities\IAuthor::USER_TYPE_GOOGLE:
				$user = new Users\Google($author->getName(), $author->getEmail(), $author->getWebsite(), $author->getUserId());
				// Set social client service
				$user->setGoogleClient($this->google);
				break;

			case Entities\IAuthor::USER_TYPE_GITHUB:
				$user = new Users\Github($author->getName(), $author->getEmail(), $author->getWebsite(), $author->getUserId());
				// Set social client service
				$user->setGithubClient($this->github);
				break;

			default:
				$user = new Users\Guest($author->getName(), $author->getEmail(), $author->getWebsite(), $author->getUserId());
				break;
		}

		$user
			// Add cache to the user
			->setCache($this->cache)
			// Add gravatar extension if is available
			->setGravatar($this->gravatar)
			// Set access token if is available
			->setAccessToken($author->getParam('accessToken'));

		return $user;
	}

	/**
	 * @return Templating\Helpers
	 */
	public function createTemplateHelpers()
	{
		return new Templating\Helpers($this);
	}
}