<?php
/**
 * Control.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Components
 * @since		5.0
 *
 * @date		02.05.14
 */

namespace IPub\Comments\Components;

use Nette;
use Nette\Application;
use Nette\Caching;
use Nette\Http;
use Nette\Localization;

use Tracy\Debugger;

use IPub;
use IPub\Comments\CommentsFactory;
use IPub\Comments\Configuration;
use IPub\Comments\Entities;
use IPub\Comments\Users;
use IPub\Comments\SessionStorage;

use IPub\WebLoader;

use Kdyby\Facebook;
use Kdyby\Google;
use Kdyby\Github;

class Control extends Application\UI\Control
{
	/**
	 * Occurs when user is successfully logged in via social network
	 *
	 * @var array of function($type, Nette\ArrayHash $author);
	 */
	public $onAuthorLogin = array();

	/**
	 * @var CommentsFactory
	 */
	protected $commentsFactory;

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
	 * @var Http\Request
	 */
	protected $httpRequest;

	/**
	 * @var string
	 */
	protected $templateFile;

	/**
	 * @var Localization\ITranslator
	 */
	protected $translator;

	/**
	 * @var WebLoader\LoaderFactory
	 */
	protected $webLoader;

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
	 * Inject translator service
	 *
	 * @param Localization\ITranslator $translator
	 */
	public function injectTranslator(Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}

	/**
	 * Inject web loader service
	 *
	 * @param WebLoader\LoaderFactory $webLoader
	 */
	public function injectWebLoader(WebLoader\LoaderFactory $webLoader)
	{
		$this->webLoader = $webLoader;
	}

	/**
	 * Inject social networks services
	 *
	 * @param Facebook\Facebook $facebook
	 * @param Google\Google $google
	 * @param Github\Client $github
	 */
	public function injectSocialNetworks(Facebook\Facebook $facebook = NULL, Google\Google $google = NULL, Github\Client $github = NULL)
	{
		$this->facebook	= $facebook;
		$this->google	= $google;
		$this->github	= $github;
	}

	/**
	 * @param $presenter
	 */
	public function attached($presenter)
	{
		parent::attached($presenter);

		if ($presenter->isAjax()) {
			// Presenter snippets should be deactivated
			$this->presenter->redrawControl(NULL, FALSE);
			// Invalidate list & form snippets
			$this->redrawControl('contributors');
		}

		// Handle static files if it is possible
		if ($this->webLoader instanceof WebLoader\LoaderFactory) {
			// Add JS file to web loader
			$presenter['js']->getCompiler()->getCollection()->addFile(realpath(__DIR__ .'/../../../../client-side/') .'/comments.js');
			// Add CSS file to web loader
			$presenter['css']->getCompiler()->getCollection()->addFile(realpath(__DIR__ .'/../../../../client-side/') .'/comments.css');
		}
	}

	/**
	 * @param Http\Request $httpRequest
	 * @param CommentsFactory $commentsFactory
	 * @param Configuration $configuration
	 * @param SessionStorage $session
	 * @param Caching\IStorage $cacheStorage
	 */
	public function __construct(
		Http\Request $httpRequest,
		CommentsFactory $commentsFactory, Configuration $configuration, SessionStorage $session,
		Caching\IStorage $cacheStorage
	) {
		$this->httpRequest	= $httpRequest;

		// Extension services
		$this->commentsFactory	= $commentsFactory;
		$this->configuration	= $configuration;
		$this->session			= $session;
		$this->cache			= new Caching\Cache($cacheStorage, 'IPub.Comments');
	}

	/**
	 * Change default component template path
	 *
	 * @param string $templateFile
	 *
	 * @return $this
	 *
	 * @throws \Nette\FileNotFoundException
	 */
	public function setTemplate($templateFile)
	{
		// Check if template file exists...
		if (!is_file($templateFile)) {
			// ...if not throw exception
			throw new Nette\FileNotFoundException;
		}

		$this->templateFile = $templateFile;

		return $this;
	}

	/**
	 * Render comment component
	 */
	public function render()
	{
		// Store configuration
		$configuration = $this->configuration;

		// Comments to display
		$this->template->comments		= $this->commentsFactory->getComments($this->commentsFactory->getAuthor());
		$this->template->commentsCount	= $this->commentsFactory->getCommentsCount($this->commentsFactory->getAuthor());
		// Get actual author identity
		$this->template->activeAuthor	= $this->commentsFactory->getAuthor();
		// Social settings
		$this->template->enabledSocial	= array(
			'facebook'	=> $this->configuration->isSocialNetworkEnabled('facebook'),
			'twitter'	=> $this->configuration->isSocialNetworkEnabled('twitter'),
			'google'	=> $this->configuration->isSocialNetworkEnabled('google'),
			'github'	=> $this->configuration->isSocialNetworkEnabled('github'),
		);
		// Posting settings
		$this->template->onlyRegistered			= $this->configuration->posting->registeredOnly;
		$this->template->enabledSocialConnect	= call_user_func(function() use($configuration) {
			$socialEnabled = FALSE;

			foreach ($configuration->getSocialNetworks() as $network) {
				$socialEnabled = $network ?: $socialEnabled;
			}

			return $socialEnabled;
		});
		$this->template->enabledPosting			= $this->configuration->posting->enabled;
		$this->template->showAvatar				= $this->configuration->displaying->avatar;
		$this->template->maxDepth				= $this->configuration->displaying->maxDepth;

		// Check if translator is available
		if ($this->getTranslator() instanceof Localization\ITranslator) {
			$this->template->setTranslator($this->getTranslator());
		}

		// Get component template file
		$templateFile = !empty($this->templateFile) ? $this->templateFile : __DIR__ . DIRECTORY_SEPARATOR .'template'. DIRECTORY_SEPARATOR .'default.latte';
		$this->template->setFile($templateFile);

		// Render component template
		$this->template->render();
	}

	/**
	 * @return Facebook\Dialog\LoginDialog
	 */
	protected function createComponentFbLogin()
	{
		$dialog = $this->facebook->createDialog('login');

		// Define after login actions
		$dialog->onResponse[] = function (Facebook\Dialog\LoginDialog $dialog) {
			$fb = $dialog->getFacebook();

			if (!$fb->getUser()) {
				$this->flashMessage($this->translator->translate('comments.messages.socialAuthenticationFailed', NULL, array('network' => 'facebook')), 'warning');
				return;
			}

			/**
			 * If we get here, it means that the user was recognized
			 * and we can call the Facebook API
			 */

			try {
				// Get details about user
				$me = $fb->api('/me');

				// Create facebook author
				$author = new Users\Facebook($me->name, $me->email, NULL, $me->id);
				$author
					->setAccessToken($fb->getAccessToken());

				// Store author in factory
				$this->commentsFactory->setAuthor($author);

				// Save token to session
				$this->session->accessToken = $fb->getAccessToken();

				// Call event after login
				$this->onAuthorLogin(Entities\IAuthor::USER_TYPE_FACEBOOK, $author);

			} catch (Facebook\FacebookApiException $ex) {
				Debugger::log($ex->getMessage(), 'comments');

				$this->flashMessage($this->translator->translate('comments.messages.socialAuthenticationFailed', NULL, array('network' => 'facebook')), 'warning');
			}

			$this->redirect('this');
		};

		return $dialog;
	}

	/**
	 * @return Google\Dialog\LoginDialog
	 */
	protected function createComponentGoogleLogin()
	{
		// Get google client...
		$client = $this->google->getClient();
		// ...& set access type to offline
		$client->setAccessType('offline');

		$dialog = $this->google->createLoginDialog();

		// Define after login actions
		$dialog->onResponse[] = function (Google\Dialog\LoginDialog $dialog) {
			$google = $dialog->getGoogle();

			if (!$google->getUser()) {
				$this->flashMessage($this->translator->translate('comments.messages.socialAuthenticationFailed', NULL, array('network' => 'google')), 'warning');
				return;
			}

			/**
			 * If we get here, it means that the user was recognized
			 * and we can call the Google API
			 */

			try {
				// Get details about user
				$me = $google->getProfile();

				// Create facebook author
				$author = new Users\Google($me->getName(), $me->getEmail(), NULL, $me->getId());
				$author
					->setAccessToken($google->getAccessToken());

				// Store author in factory
				$this->commentsFactory->setAuthor($author);

				// Save token to session
				$this->session->accessToken = $google->getAccessToken();

				// Call event after login
				$this->onAuthorLogin(Entities\IAuthor::USER_TYPE_GOOGLE, $author);

				// For google user store his avatar
				$this->cache->save('avatar.'. $author->getType() .'.'. $me->getId(), $me->getPicture(), array(
					Caching\Cache::EXPIRE => '7 days',
				));

			} catch (\Google_Exception $ex) {
				Debugger::log($ex->getMessage(), 'comments');

				$this->flashMessage($this->translator->translate('comments.messages.socialAuthenticationFailed', NULL, array('network' => 'google')), 'warning');
			}
		};

		return $dialog;
	}

	/**
	 * @return Github\UI\LoginDialog
	 */
	protected function createComponentGithubLogin()
	{
		$dialog = new Github\UI\LoginDialog($this->github);

		$dialog->onResponse[] = function (Github\UI\LoginDialog $dialog) {
			$github = $dialog->getClient();

			if (!$github->getUser()) {
				$this->flashMessage($this->translator->translate('comments.messages.socialAuthenticationFailed', NULL, array('network' => 'github')), 'warning');
				return;
			}

			/**
			 * If we get here, it means that the user was recognized
			 * and we can call the Github API
			 */

			try {
				$me = $github->api('/user');

				// Create facebook author
				$author = new Users\Github($me->name, $me->email, NULL, $me->login);
				$author
					->setAccessToken($github->getAccessToken());

				// Store author in factory
				$this->commentsFactory->setAuthor($author);

				// Save token to session
				$this->session->accessToken = $github->getAccessToken();

				// Call event after login
				$this->onAuthorLogin(Entities\IAuthor::USER_TYPE_GITHUB, $author);

			} catch (Github\ApiException $ex) {
				Debugger::log($ex->getMessage(), 'comments');

				$this->flashMessage($this->translator->translate('comments.messages.socialAuthenticationFailed', NULL, array('network' => 'github')), 'warning');
			}

			$this->redirect('this');
		};

		return $dialog;
	}

	/**
	 * Disconnect from social network
	 *
	 */
	public function handleLogout()
	{
		switch($this->commentsFactory->getAuthor()->getType())
		{
			// Logout from facebook
			case Entities\IAuthor::USER_TYPE_FACEBOOK:
				$this->facebook->destroySession();
				break;

			case Entities\IAuthor::USER_TYPE_TWITTER:
				break;

			case Entities\IAuthor::USER_TYPE_GOOGLE:
				$this->google->destroySession();
				break;

			// Logout from github
			case Entities\IAuthor::USER_TYPE_GITHUB:
				$this->github->destroySession();
				break;
		}

		// Destroy author session details
		$this->session->clearAll();

		// Reset user & create guest
		$guest = new Users\Guest();

		// Destroy active author
		$this->commentsFactory->setAuthor($guest);

		// Check for ajax call
		if ($this->presenter->isAjax()) {
			$this->redrawControl('flashes');
			$this->redrawControl('formArea');

		// Normal request
		} else {
			$this->redirect('this');
		}
	}

	/**
	 * @return Application\UI\Form
	 */
	protected function createComponentForm()
	{
		$form = new Application\UI\Form();

		// Add form CSRF protection
		$form->addProtection();

		// Parent comment id
		$form->addHidden('parent');

		$form->addText('author', 'comments.form.author')
			->setDefaultValue($this->commentsFactory->getAuthor()->getName())
			->setAttribute('readonly', $this->commentsFactory->getAuthor()->isGuest() ? FALSE : TRUE);

		// Check if this field is required
		if ($this->commentsFactory->getAuthor()->isGuest() && $this->configuration->posting->require->name) {
			$form['author']
				->addRule($form::FILLED, 'comments.messages.yourNameIsRequired');
		}

		$form->addText('email', 'comments.form.email')
			->setDefaultValue($this->commentsFactory->getAuthor()->getEmail())
			->setAttribute('readonly', $this->commentsFactory->getAuthor()->isGuest() ? FALSE : TRUE)
			->addConditionOn($form['email'], $form::FILLED)
				->addRule($form::EMAIL, 'comments.messages.enterValidEmailAddress');

		// Check if this field is required
		if ($this->commentsFactory->getAuthor()->isGuest() && $this->configuration->posting->require->email) {
			$form['email']
				->addRule($form::FILLED, 'comments.messages.yourEmailIsRequired');
		}

		$form->addText('website', 'comments.form.website')
			->setDefaultValue($this->commentsFactory->getAuthor()->getWebsite())
			->addConditionOn($form['website'], $form::FILLED)
				->addRule($form::URL, 'comments.messages.enterValidUrlAddress');

		$form->addTextArea('content', 'comments.form.content')
			->setAttribute('cols', 105)
			->setAttribute('rows', 5)
			->addRule($form::FILLED, 'comments.messages.enterCommentContent');

		// Add submit buttons
		$form->addSubmit('save', 'comments.form.save');

		// When form is successfully submitted
		$form->onSuccess[] = array($this, 'postFormSubmitted');
		// Add comment validation
		$form->onValidate[] = array($this, 'validatePostForm');

		return $form;
	}

	/**
	 * @param Application\UI\Form $form
	 *
	 * @return bool
	 *
	 * @throws \Nette\NotSupportedException
	 */
	public  function validatePostForm(Application\UI\Form $form)
	{
		// Only registered users can comment
		if ($this->configuration->posting->registeredOnly && $this->commentsFactory->getAuthor()->isGuest()) {
			$form->addError($this->translator->translate('comments.messages.loginToLeaveComment'));

			return FALSE;
		}

		// Check quick multiple posts
		if ($lastComment = $this->commentsFactory->getAuthorLastComment($this->commentsFactory->getAuthor(), $this->httpRequest->getRemoteAddress())) {
			// Created value is DateTime
			if ($lastComment->getCreated() instanceof \DateTime) {
				$now = new Nette\DateTime();

				$diff = $now->diff($lastComment->getCreated());
				$diffSec = $diff->format('%r').(	// Prepend the sign - if negative, change it to R if you want the +, too
						($diff->s) +				// Seconds (no errors)
						(60*($diff->i)) +			// Minutes (no errors)
						(60*60*($diff->h)) +		// Hours (no errors)
						(24*60*60*($diff->d)) +		// Days (no errors)
						(30*24*60*60*($diff->m)) +	// Months (???)
						(365*24*60*60*($diff->y))	// Years (???)
					);

			// Created value is timestamp
			} else if (is_numeric($lastComment->getCreated())) {
				$diffSec = time() - $lastComment->getCreated();

			// Not supported value
			} else {
				throw new Nette\NotSupportedException('Comments extensions support only \DateTime or timestamp for creation date of post.');
			}

			if ($diffSec < $this->configuration->posting->timeBetweenUserPosts) {
				$form->addError($this->translator->translate('comments.messages.postingToQuickly'));

				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param Application\UI\Form $form
	 */
	public function postFormSubmitted(Application\UI\Form $form)
	{
		if ($form->hasErrors()) {
			return;
		}

		// Get submitted values
		$values = $form->getValues();

		try {
			// Values for new comment
			$comment = new ArrayHash();

			// Default comment values
			$comment->parent	= $values->parent;
			$comment->ipAddress	= $this->httpRequest->getRemoteAddress();
			$comment->status	= Entities\IComment::STATE_UNAPPROVED;

			// Create author
			$author = new Nette\ArrayHash();

			// User current ip address
			$author->ipAddress = $this->httpRequest->getRemoteAddress();

			// Author is guest
			if ($this->commentsFactory->getAuthor()->isGuest()) {
				$author->id			= $this->commentsFactory->getAuthor()->getId();
				$author->type		= Entities\IAuthor::USER_TYPE_GUEST;
				$author->name		= $values->author;
				$author->email		= $values->email;
				$author->website	= $values->website;

				// Update guest values
				if ($values->author) {
					$this->commentsFactory->getAuthor()->setName($values->author);
				}
				if ($values->email) {
					$this->commentsFactory->getAuthor()->setEmail($values->email);
				}
				if ($values->website) {
					$this->commentsFactory->getAuthor()->setWebsite($values->website);
				}

			// Get values from logged in user
			} else {
				$author->id			= $this->commentsFactory->getAuthor()->getId();
				$author->type		= $this->commentsFactory->getAuthor()->getType();
				$author->name		= $this->commentsFactory->getAuthor()->getName();
				$author->email		= $this->commentsFactory->getAuthor()->getEmail();
				$author->website	= $this->commentsFactory->getAuthor()->getWebsite();
			}

			// Filter input values
			$comment->content = $this->filterContentInput($values->content);

			// Check against spam blacklist
			if ($this->matchWords($values)) {
				$comment->status = Entities\IComment::STATE_SPAM;
			}

			// When author is site admin the automatically approve
			if ($this->commentsFactory->getAuthor()->isSiteAdmin()) {
				$comment->status = Entities\IComment::STATE_APPROVED;

			// Comment is automatically approved
			} else if ($this->configuration->posting->approving == 1) {
				$comment->status = Entities\IComment::STATE_APPROVED;

			// Check if author is verified
			} else if (!$this->commentsFactory->getAuthor()->isGuest() && $this->configuration->posting->approving == 2 && $this->commentsFactory->isAuthorVerified($this->commentsFactory->getAuthor())) {
				$comment->status = Entities\IComment::STATE_APPROVED;
			}

			// Call events for post creating
			$comment = $this->commentsFactory->createComment($comment, $this->commentsFactory->getAuthor());

		} catch (\Exception $ex) {
			// Log error message
			Debugger::log($ex->getMessage(), Debugger::ERROR);

			// Store system message
			$form->addError("comments.messages.postNotSaved");
		}

		// Refresh page
		$this->getPresenter()->redirect('this');
	}

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return $this
	 */
	public function setTranslator(Localization\ITranslator $translator)
	{
		$this->translator = $translator;

		return $this;
	}

	/**
	 * @return Localization\ITranslator|null
	 */
	public function getTranslator()
	{
		if ($this->translator instanceof Localization\ITranslator) {
			return $this->translator;
		}

		return NULL;
	}

	/**
	 * Remove html from comment content
	 *
	 * @param string $content comment content
	 *
	 * @return string
	 */
	protected function filterContentInput($content)
	{
		// Remove all html tags or escape if in [code] tag
		$content = preg_replace_callback('/\[code\](.+?)\[\/code\]/is', create_function('$matches', 'return htmlspecialchars($matches[0]);'), $content);
		$content = strip_tags($content);

		return $content;
	}

	/**
	 * Match words against comments content, author, URL, Email or IP
	 *
	 * @param Nette\ArrayHash $values
	 *
	 * @return bool
	 */
	protected function matchWords($values)
	{
		$vars = array('author', 'email', 'website', 'ipAddress', 'content');

		if ($words = explode("\n", $this->configuration->posting->blacklist)) {
			foreach ($words as $word) {
				if ($word = trim($word)) {
					$pattern = '/'.preg_quote($word).'/i';

					foreach ($vars as $var) {
						if (preg_match($pattern, $values->$var)) {
							return TRUE;
						}
					}
				}
			}
		}

		return FALSE;
	}
}