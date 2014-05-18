<?php
/**
 * IDataProvider.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Data providers
 * @since		5.0
 *
 * @date		12.05.14
 */

namespace IPub\Comments\DataProviders;

use Nette;

use IPub\Comments\Entities;
use IPub\Comments\Users;

interface IDataProvider
{
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
	public function getComments(Users\IUser $author, $orderDir = 'DESC');

	/**
	 * Get all available comments count
	 *
	 * @param Users\IUser $author
	 *
	 * @return int
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function getCommentsCount(Users\IUser $author = NULL);

	/**
	 * Store new comment to database
	 *
	 * @param Nette\ArrayHash $comment
	 * @param Users\IUser $author
	 *
	 * @return Entities\IComment
	 */
	public function createComment(Nette\ArrayHash $comment, Users\IUser $author);

	/**
	 * Get author last comment
	 *
	 * @param Users\IUser $author
	 * @param string $ipAddress
	 *
	 * @return null|Entities\IComment
	 */
	public function getAuthorLastComment(Users\IUser $author, $ipAddress = NULL);

	/**
	 * Check if author have some post already verified
	 *
	 * @param Users\IUser $author
	 *
	 * @return bool
	 */
	public function isAuthorVerified(Users\IUser $author);
}