<?php
namespace Change\Events;

/**
 * @name \Change\Events\DefaultRegistration
 */
class DefaultRegistration
{
	public function __construct(\Change\Events\EventManager $eventManager)
	{
		echo __METHOD__, PHP_EOL;
		//$eventManager->attach($event, $callBack);
	}
}