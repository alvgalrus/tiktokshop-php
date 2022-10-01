<?php
/*
 * This file is part of tiktokshop-client.
 *
 * (c) Jin <j@sax.vn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NVuln\TiktokShop\Tests;

use NVuln\TiktokShop\Client;
use NVuln\TiktokShop\Errors\TiktokShopException;
use NVuln\TiktokShop\Webhook;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WebhookTest extends TestCase
{
    public static $webhook;
    public static $sample_data = '{"type":1,"shop_id":"7494726673780148744","timestamp":1664095733,"data":{"order_id":"576684019603311365","order_status":"AWAITING_SHIPMENT","update_time":1664095610}}';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $client = new Client('fake_app_key', 'fake_app_secret');
        $webhook = new Webhook($client);

        // put sample data
        $webhook->capture(json_decode(static::$sample_data, true));

        static::$webhook = $webhook;
    }

    public function testGetShopId()
    {
        $this->assertEquals('7494726673780148744', static::$webhook->getShopId());
    }

    public function testVerify()
    {
        $stringToBeSigned = 'fake_app_key'.static::$sample_data;
        $signature = hash_hmac('sha256', $stringToBeSigned, 'fake_app_secret');

        // correct signature
        $this->assertEquals(true, static::$webhook->verify($signature, static::$sample_data));

        // in-correct signature without thrown exception
        $this->assertEquals(false, static::$webhook->verify('wrong signature', static::$sample_data, false));

        // in-correct signature thrown exception
        $this->expectException(TiktokShopException::class);
        static::$webhook->verify('wrong signature', static::$sample_data);
    }

    public function testGetType()
    {
        $this->assertEquals(1, static::$webhook->getType());
    }

    public function testGetTimestamp()
    {
        $this->assertEquals(1664095733, static::$webhook->getTimestamp());
    }

    public function testGetData()
    {
        $sample_data = json_decode(static::$sample_data, true);
        $this->assertEquals($sample_data['data'], static::$webhook->getData());
    }

    public function test__construct()
    {
        $client = new Client('fake_app_key', 'fake_app_secret');
        $webhook = new Webhook($client);

        $reflect = new ReflectionClass($webhook);
        $this->assertInstanceOf(Client::class, $reflect->getProperty('client')->getValue($webhook));
    }

    public function testCapture()
    {
        $webhook = static::$webhook;
        $sample_data = json_decode(static::$sample_data, true);

        $webhook->capture($sample_data);
        $reflect = new ReflectionClass($webhook);

        $this->assertEquals(1, $reflect->getProperty('type')->getValue($webhook));
        $this->assertEquals('7494726673780148744', $reflect->getProperty('shop_id')->getValue($webhook));
        $this->assertEquals($sample_data['data'], $reflect->getProperty('data')->getValue($webhook));
        $this->assertEquals(1664095733, $reflect->getProperty('timestamp')->getValue($webhook));
    }
}
