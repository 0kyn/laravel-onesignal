<?php

namespace Okn\OneSignal;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

class OneSignalClient
{

    const API_URL = 'https://onesignal.com/api/v1';

    const ENDPOINT_NOTIFICATIONS = '/notifications';
    const ENDPOINT_PLAYERS = '/players';

    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_CHARSET = 'utf-8';

    const MAX_RETRIES = 3;
    const RETRY_DELAY = 700;

    const LIMIT_VIEW_DEVICES = 300;

    private $appId;
    private $restApiKey;
    private $sslVerify;

    private $client;
    private $notification;
    private $async = false;

    public function __construct($config)
    {
        $this->appId = $config['app_id'];
        $this->restApiKey = $config['rest_api_key'];
        $this->sslVerify = isset($config['ssl_verify']) && !$config['ssl_verify'] ? false : true;

        $headers = [
            'Authorization' => 'Basic ' . $this->restApiKey,
            'Content-Type' => 'application/json; charset=' . self::DEFAULT_CHARSET
        ];
        $params = [
            'app_id' => $this->appId,
        ];
        $this->client = new Client([
            'handler' => $this->createGuzzleHandler(self::API_URL, $headers, $params),
            'verify' => $this->sslVerify
        ]);
    }

    private function createGuzzleHandler($baseUri, $headers, $params)
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($baseUri, $headers, $params) {
            $uri = $baseUri . $request->getUri();
            $headers = array_merge($request->getHeaders(), $headers);

            $bodyContents = json_decode($request->getBody()->getContents(), true);

            $requestMethod = $request->getMethod();
            if ($requestMethod === 'GET') {
                $uri .= '?app_id=' . $params['app_id'];
                $uri .= !is_null($bodyContents) ? http_build_query($bodyContents) : null;
                $body = $request->getBody();
            } elseif ($requestMethod === 'POST') {
                $body = array_merge($bodyContents, $params);
            }

            $body = json_encode($body);

            return new Request(
                $request->getMethod(),
                $uri,
                $headers,
                $body,
                $request->getProtocolVersion()
            );
        }));

        $stack->push(Middleware::retry(function ($retries, Request $request, Response $response = null, RequestException $exception = null) {

            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                $statusCode = $response->getStatusCode();
                if ($statusCode >= 400) {
                    throw new RequestException('Error ' . $statusCode . ': ' . $response->getReasonPhrase() . ' - You should check your credentials in the config file config/onesignal.php.', $request);
                }

                if ($statusCode >= 500) {
                    return true;
                }
            }

            return false;
        }, function ($retries) {
            return $retries * self::RETRY_DELAY;
        }));

        return $stack;
    }

    private function getDevices($limit = 300, $offset = 0)
    {
        if ($limit > self::LIMIT_VIEW_DEVICES) {
            throw new \Exception('You have exceeded the maximum limit of devices (300).');
        }

        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];
        $response = $this->client->get(self::ENDPOINT_PLAYERS, [
            RequestOptions::JSON => $params
        ]);

        $devices = json_decode($response->getBody()->getContents(), true);

        return $devices;
    }

    public function getUsers($limit = 300, $offset = 0)
    {
        return $this->getDevices($limit, $offset)['players'];
    }

    public function getUser($playerId)
    {
        $response = $this->client->get(self::ENDPOINT_PLAYERS . '/' . $playerId);
        $user = json_decode($response->getBody()->getContents(), true);

        return $user;
    }

    public function createNotification(array $content = [])
    {
        array_walk($content, function (&$item, $key) {
            $cond = $key === 'headings' || $key === 'contents';
            if ($cond) {
                $item = [self::DEFAULT_LANGUAGE => $item];
            }
        });

        $this->notification = $content;

        return $this;
    }

    public function send(array $to = [])
    {
        $notification = [];

        if (count($to) > 0) {
            extract($to);
            if (isset($segments)) {
                $notification['included_segments'] = $segments;
            }
            if (isset($ids)) {
                $notification['include_player_ids'] = $ids;
            }
        } else {
            $notification['included_segments'] = ['Admin'];
        }
        if (isset($this->notification)) {
            $notification = array_merge($notification, $this->notification);
        } else {
            throw new \Exception('No notification to send. You might create one with OneSignal::createNotification([$params]).');
        }

        $clientMode = !$this->async ? 'post' : 'postAsync';
        $response = $this->client->{$clientMode}(self::ENDPOINT_NOTIFICATIONS, [
            RequestOptions::JSON => $notification,
        ]);

        return $response;
    }

    public function async()
    {
        $this->async = true;

        return $this;
    }

    public function withButtons(array $params)
    {
        $buttons = [
            'web_buttons' => $params
        ];

        if (is_array($this->notification)) {
            $this->notification = array_merge($this->notification, $buttons);
        } else {
            throw new \Exception('No notification to attach button(s) to. You need to create one before adding buttons.');
        }

        return $this;
    }

    public function test(array $to = [])
    {
        $testNotif = [
            'headings' => 'TEST TITLE',
            'contents' => 'Test loremipsum...',
        ];

        $this->createNotification($testNotif)->send($to);
    }
}
