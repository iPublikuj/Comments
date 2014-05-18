<?php
/**
 * IComment.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Entities
 * @since		5.0
 *
 * @date		15.03.14
 */

namespace IPub\Comments\Entities;

use Doctrine\Common\Collections\ArrayCollection;

use Nette\InvalidArgumentException;

use IPub\Comments\Users;

interface IComment
{
	/**
	 * Define statuses
	 */
	const STATE_UNAPPROVED	= 'unapproved';
	const STATE_APPROVED	= 'approved';
	const STATE_DECLINED	= 'declined';
	const STATE_SPAM		= 'spam';

	/**
	 * @param IComment $parent
	 *
	 * @return $this
	 */
	public function setParent(IComment $parent = NULL);

	/**
	 * @return IComment
	 */
	public function getParent();

	/**
	 * @param array $children
	 *
	 * @return $this
	 */
	public function setChildren(array $children);

	/**
	 * @param IComment $child
	 *
	 * @return $this
	 */
	public function addChild(IComment $child);

	/**
	 * @return ArrayCollection
	 */
	public function getChildren();

	/**
	 * @param IComment $child
	 *
	 * @return $this
	 */
	public function removeChild(IComment $child);

	/**
	 * @param IAuthor $author
	 *
	 * @return $this
	 */
	public function setAuthor(IAuthor $author);

	/**
	 * @return IAuthor
	 */
	public function getAuthor();

	/**
	 * @param string $ipAddress
	 *
	 * @return $this
	 */
	public function setIpAddress($ipAddress);

	/**
	 * @return string
	 */
	public function getIpAddress();

	/**
	 * @param string $content
	 *
	 * @return $this
	 */
	public function setContent($content);

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @param string $status
	 *
	 * @return $this
	 *
	 * @throws \Nette\InvalidArgumentException
	 */
	public function setStatus($status);

	/**
	 * @return string
	 */
	public function getStatus();

	/**
	 * @param Users\IUser $user
	 *
	 * @return $this
	 */
	public function setUser(Users\IUser $user);

	/**
	 * @return Users\IUser
	 */
	public function getUser();
}