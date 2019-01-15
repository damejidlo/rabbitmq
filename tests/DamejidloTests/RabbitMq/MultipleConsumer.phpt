<?php

/**
 * Test: Damejidlo\RabbitMq\Extension.
 *
 * @testCase DamejidloTests\RabbitMq\MultipleConsumerTest
 * @package Damejidlo\RabbitMq
 */

namespace DamejidloTests\RabbitMq;

use Damejidlo;
use Damejidlo\RabbitMq\IConsumer;
use Damejidlo\RabbitMq\MultipleConsumer;
use DamejidloTests;
use Mockery\Mock;
use Nette;
use PhpAmqpLib\Message\AMQPMessage;
use Tester;
use Tester\Assert;



require_once __DIR__ . '/TestCase.php';

class MultipleConsumerTest extends TestCase
{

	/**
	 * Check if the message is requeued or not correctly.
	 *
	 * @dataProvider processMessageProvider
	 */
	public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = null)
	{
		/** @var Damejidlo\RabbitMq\Connection|Mock $amqpConnection */
		$amqpConnection = $this->getMockery('Damejidlo\RabbitMq\Connection', ['127.0.0.1', 5672, 'guest', 'guest'])
			->makePartial();

		/** @var Damejidlo\RabbitMq\Channel|Mock $amqpChannel */
		$amqpChannel = $this->getMockery('Damejidlo\RabbitMq\Channel', [$amqpConnection])
			->makePartial();

		$consumer = new MultipleConsumer($amqpConnection);
		$consumer->setChannel($amqpChannel);

		$callback = function($msg) use (&$lastQueue, $processFlag) { return $processFlag; };
		$consumer->setQueues(['test-1' => ['callback' => $callback], 'test-2'  => ['callback' => $callback]]);

		// Create a default message
		$amqpMessage = new AMQPMessage('foo body');
		$amqpMessage->delivery_info['channel'] = $amqpChannel;
		$amqpMessage->delivery_info['delivery_tag'] = 0;

		$amqpChannel->shouldReceive('basic_reject')
			->andReturnUsing(function ($delivery_tag, $requeue) use ($expectedMethod, $expectedRequeue) {
				Assert::same($expectedMethod, 'basic_reject'); // Check if this function should be called.
				Assert::same($requeue, $expectedRequeue); // Check if the message should be requeued.
			});

		$amqpChannel->shouldReceive('basic_ack')
			->andReturnUsing(function ($delivery_tag) use ($expectedMethod) {
				Assert::same($expectedMethod, 'basic_ack'); // Check if this function should be called.
			});

		$consumer->processQueueMessage('test-1', $amqpMessage);
		$consumer->processQueueMessage('test-2', $amqpMessage);
	}



	public function processMessageProvider()
	{
		return [
			[null, 'basic_ack'], // Remove message from queue only if callback return not false
			[true, 'basic_ack'], // Remove message from queue only if callback return not false
			[false, 'basic_reject', true], // Reject and requeue message to RabbitMQ
			[IConsumer::MSG_ACK, 'basic_ack'], // Remove message from queue only if callback return not false
			[IConsumer::MSG_REJECT_REQUEUE, 'basic_reject', true], // Reject and requeue message to RabbitMQ
			[IConsumer::MSG_REJECT, 'basic_reject', false], // Reject and drop
		];
	}

}

(new MultipleConsumerTest())->run();
