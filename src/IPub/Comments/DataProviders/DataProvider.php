<?php
/**
 * DataProvider.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Data Sources
 * @since		5.0
 *
 * @date		12.05.14
 */

namespace IPub\Comments\DataProviders;

use Nette;
use Nette\Utils;

use IPub\Comments\Entities;
use IPub\Comments\Users;

class DataProvider implements IDataProvider
{
	/**
	 * Define callback types
	 */
	const CALLBACK_GET_COMMENTS				= 'comments';
	const CALLBACK_GET_COMMENTS_COUNT		= 'commentsCount';
	const CALLBACK_CREATE_COMMENT			= 'createComment';
	const CALLBACK_VALIDATE_COMMENT			= 'validateComment';
	const CALLBACK_GET_AUTHOR_LAST_COMMENTS	= 'authorLastComment';
	const CALLBACK_IS_AUTHOR_VERIFIED		= 'isAuthorVerified';

	/**
	 * Callbacks collection
	 *
	 * @var array
	 */
	protected $callbacks = array();

	/**
	 * @param string $type
	 * @param callable function $callback
	 *
	 * @return $this
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function setCallback($type, $callback)
	{
		// Check callback type
		if (!in_array($type, array(self::CALLBACK_GET_COMMENTS, self::CALLBACK_GET_COMMENTS_COUNT, self::CALLBACK_CREATE_COMMENT, self::CALLBACK_VALIDATE_COMMENT, self::CALLBACK_GET_AUTHOR_LAST_COMMENTS, self::CALLBACK_IS_AUTHOR_VERIFIED))) {
			throw new Nette\InvalidArgumentException('Invalid callback type defined.');
		}

		// Check if callback is callable
		if (!is_callable($callback)) {
			throw new Nette\InvalidArgumentException('Invalid callback provided. Please provide callable function.');
		}

		$this->callbacks[$type] = $callback;

		return $this;
	}

	/**
	 * Get all available comments
	 *
	 * @param Users\IUser $author
	 * @param string $orderDir
	 *
	 * @return array
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function getComments(Users\IUser $author, $orderDir = 'DESC')
	{
		// Check callback
		$this->checkCallback(self::CALLBACK_GET_COMMENTS);

		return Utils\Callback::invokeArgs($this->callbacks[self::CALLBACK_GET_COMMENTS], array(
			$author,
			$orderDir,
		));
	}

	/**
	 * Get all available comments count
	 *
	 * @param Users\IUser $author
	 *
	 * @return int
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function getCommentsCount(Users\IUser $author = NULL)
	{
		// Check callback
		$this->checkCallback(self::CALLBACK_GET_COMMENTS_COUNT);

		return (int) Utils\Callback::invokeArgs($this->callbacks[self::CALLBACK_GET_COMMENTS_COUNT], array(
			$author
		));
	}

	/**
	 * Store new comment to database
	 *
	 * @param Nette\ArrayHash $comment
	 * @param Users\IUser $author
	 *
	 * @return Entities\IComment|mixed
	 */
	public function createComment(Nette\ArrayHash $comment, Users\IUser $author)
	{
		// Check callback
		$this->checkCallback(self::CALLBACK_CREATE_COMMENT);

		return Utils\Callback::invokeArgs($this->callbacks[self::CALLBACK_CREATE_COMMENT], array(
			$comment,
			$author,
		));
	}

	/**
	 * Get author last comment
	 *
	 * @param Users\IUser $author
	 * @param string $ipAddress
	 *
	 * @return null|Entities\IComment
	 */
	public function getAuthorLastComment(Users\IUser $author, $ipAddress = NULL)
	{
		// Check callback
		$this->checkCallback(self::CALLBACK_GET_AUTHOR_LAST_COMMENTS);

		return Utils\Callback::invokeArgs($this->callbacks[self::CALLBACK_GET_AUTHOR_LAST_COMMENTS], array(
			$author,
			$ipAddress
		));
	}

	/**
	 * Check if author have some post already verified
	 *
	 * @param Users\IUser $author
	 *
	 * @return bool
	 */
	public function isAuthorVerified(Users\IUser $author)
	{
		// Check callback
		$this->checkCallback(self::CALLBACK_IS_AUTHOR_VERIFIED);

		return Utils\Callback::invokeArgs($this->callbacks[self::CALLBACK_IS_AUTHOR_VERIFIED], array(
			$author
		)) ? TRUE : FALSE;
	}

	/**
	 * Check if callback is ok
	 *
	 * @param $type
	 *
	 * @return bool
	 *
	 * @throws \Nette\InvalidArgumentException
	 */
	protected function checkCallback($type)
	{
		// Check callback type
		if (!in_array($type, array(self::CALLBACK_GET_COMMENTS, self::CALLBACK_GET_COMMENTS_COUNT, self::CALLBACK_CREATE_COMMENT, self::CALLBACK_VALIDATE_COMMENT, self::CALLBACK_GET_AUTHOR_LAST_COMMENTS, self::CALLBACK_IS_AUTHOR_VERIFIED))) {
			throw new Nette\InvalidArgumentException('Invalid callback type defined.');
		}

		// Check if callback is set & if is callable
		if (!isset($this->callbacks[$type]) || !is_callable($this->callbacks[$type])) {
			throw new Nette\InvalidArgumentException('Callback for "'. $type .'" is not set. Please add callback call.');
		}

		return TRUE;
	}
}