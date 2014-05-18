<?php
/**
 * TemplateHelpers.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Templating
 * @since		5.0
 *
 * @date		12.05.14
 */

namespace IPub\Comments\Templating;

use Nette;
use Nette\Utils;

use IPub\Comments\CommentsFactory;
use IPub\Comments\Entities;
use IPub\Comments\Users;

class Helpers extends Nette\Object
{
	/**
	 * @var CommentsFactory
	 */
	private $commentsFactory;

	/**
	 * @param CommentsFactory $commentsFactory
	 */
	public function __construct(CommentsFactory $commentsFactory)
	{
		$this->commentsFactory	= $commentsFactory;
	}

	public function loader($method)
	{
		if ( method_exists($this, $method) ) {
			return callback($this, $method);
		}
	}

	/**
	 * Auto linkify urls, emails
	 *
	 * @param string $content comment content
	 *
	 * @return string
	 */
	public function formatComment($content)
	{
		$content = ' '.$content.' ';
		$content = preg_replace_callback('/(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)(?:\([-A-Z0-9+&@#\/%=~_|$?!:;,.]*\)|[-A-Z0-9+&@#\/%=~_|$?!:;,.])*(?:\([-A-Z0-9+&@#\/%=~_|$?!:;,.]*\)|[A-Z0-9+&@#\/%=~_|$])/ix', array($this, 'makeURLClickable'), $content);
		$content = preg_replace("/\s([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})([\s|\.|\,])/i"," <a href=\"mailto:$1\" rel=\"nofollow\">$1</a>$2", $content);
		$content = Utils\Strings::substring($content, 1);
		$content = Utils\Strings::substring($content, 0, -1);

		return nl2br($content);
	}

	/**
	 * Create comment author avatar
	 *
	 * @param Users\IUser $user
	 * @param int $size
	 *
	 * @return string
	 */
	public function showAvatar(Users\IUser $user, $size=50)
	{
		// Create default image
		return Utils\Html::el('img')
			->src($user->getAvatar($size))
			->title($user->getName())
			->width($size)
			->height($size);
	}

	/**
	 * Create clickable url
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	protected function makeURLClickable($matches)
	{
		$url = $originalUrl = $matches[0];

		if (empty($url)) {
			return $url;
		}

		// Prepend scheme if URL appears to contain no scheme (unless a relative link starting with / or a php file).
		if (strpos($url, ':') === FALSE &&	substr($url, 0, 1) != '/' && substr($url, 0, 1) != '#' && !preg_match('/^[a-z0-9-]+?\.php/i', $url)) {
			$url = 'http://' . $url;
		}

		return Utils\Html::el('a', array(
			'href'	=> $url,
			'rel'	=> 'nofollow'
		))
			->setText($originalUrl);
	}
}