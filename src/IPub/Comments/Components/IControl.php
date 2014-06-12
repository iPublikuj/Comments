<?php
/**
 * IControl.php
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

interface IControl
{
	/**
	 * @return Control
	 */
	function create();
}