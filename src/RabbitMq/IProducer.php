<?php

namespace Damejidlo\RabbitMq;

use Damejidlo;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IProducer
{

	function setExchangeOptions(array $options = []);

	function setQueueOptions(array $options = []);

	function setRoutingKey($routingKey);

	function setContentType($contentType);

	function setDeliveryMode($deliveryMode);

	function publish($msgBody, $routingKey = '', $additionalProperties = []);

}
