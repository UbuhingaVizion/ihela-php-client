<?php

declare(strict_types=1);

namespace Ihela\Core;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function base64_encode;
use function is_string;
use function json_encode;

class HttpFactory
{
    private static ?ClientInterface $client = null;
    private static ?RequestFactoryInterface $requestFactory = null;
    private static ?StreamFactoryInterface $streamFactory = null;

    public static function setClient(ClientInterface $client): void
    {
        self::$client = $client;
    }

    public static function discover(): ClientInterface
    {
        if (self::$client !== null) {
            return self::$client;
        }

        self::$client = Psr18ClientDiscovery::find();

        return self::$client;
    }

    public static function createRequest(string $method, string $uri, array $options = []): RequestInterface
    {
        if (self::$requestFactory === null) {
            self::$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        }
        if (self::$streamFactory === null) {
            self::$streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        }

        $request = self::$requestFactory->createRequest($method, $uri);

        if (isset($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        if (isset($options['body'])) {
            $body = is_string($options['body'])
                ? $options['body']
                : json_encode($options['body']);
            $request = $request->withBody(
                self::$streamFactory->createStream($body)
            );
        }

        if (isset($options['auth'])) {
            $user = $options['auth'][0] ?? '';
            $pass = $options['auth'][1] ?? '';
            $request = $request->withHeader(
                'Authorization',
                'Basic '.base64_encode($user.':'.$pass)
            );
        }

        return $request;
    }

    public static function reset(): void
    {
        self::$client = null;
        self::$requestFactory = null;
        self::$streamFactory = null;
    }
}
