<?php
namespace paragraph1\phpFCM;

use GuzzleHttp;

class Client implements ClientInterface
{
    const DEFAULT_API_URL = 'https://fcm.googleapis.com/fcm/send';
    const DEVICE_GROUP_API_URL = 'https://android.googleapis.com/gcm/notification';
    const TOPIC_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchAdd';

    /** @var string */
    private $apiKey;

    /** @var string */
    private $proxyApiUrl;

    /** @var string $senderId */
    private $senderId;

    /** @var GuzzleHttp\ClientInterface */
    private $guzzleClient;

    /**
     * __construct
     *
     * @param string $apiKey
     * @param string $senderId
     */
    public function __construct(string $apiKey, string $senderId)
    {
        $this->apiKey = $apiKey;
        $this->senderId = $senderId;
    }

    /**
     * injectHttpClient
     *
     * @param ClientInterface $client
     */
    public function injectHttpClient(GuzzleHttp\ClientInterface $client)
    {
        $this->guzzleClient = $client;
    }

    /**
     * people can overwrite the api url with a proxy server url of their own
     *
     * @param string $url
     *
     * @return \paragraph1\phpFCM\Client
     */
    public function setProxyApiUrl($url)
    {
        $this->proxyApiUrl = $url;
        return $this;
    }

    /**
     * sends your notification to the google servers and returns a guzzle repsonse object
     * containing their answer.
     *
     * @param Message $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function send(Message $message)
    {
        $headers = [
            'Authorization' => sprintf('key=%s', $this->apiKey),
            'Content-Type' => 'application/json'
        ];

        return $this->makeRequest('POST', $this->getApiUrl(), json_encode($message), $headers);
    }

    /**
     * createDeviceGroup
     *
     * @param string $groupName name or identifier (e.g., it can be a username) that is unique to a given group
     * @param array $registrationIds array of registration ids
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createDeviceGroup(string $groupName, array $registrationIds)
    {
        $body = [
            "operation" => "create",
            "notification_key_name" => $groupName,
            "registration_ids" => $registrationIds
        ];

        $headers = [
            'Authorization' => sprintf('key=%s', $this->apiKey),
            'Content-Type' => 'application/json',
            'project_id' => $this->senderId
        ];

        return $this->makeRequest('POST', self::DEVICE_GROUP_API_URL, json_encode($body), $headers);
    }

    /**
     * retrieveNotificationKey
     *
     * @param string $groupName
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function retrieveNotificationKey(string $groupName)
    {
        $uri = self::DEVICE_GROUP_API_URL . "?notification_key_name=" . $groupName;

        $headers = [
            'Authorization' => sprintf('key=%s', $this->apiKey),
            'Content-Type' => 'application/json',
            'project_id' => $this->senderId
        ];

        return $this->makeRequest('GET', $uri, null, $headers);
    }

    /**
     * addDeviceToGroup
     *
     * @param string $groupName
     * @param string $notificationKey
     * @param array $registrationIds
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addDeviceToGroup(string $groupName, string $notificationKey, array $registrationIds)
    {
        $body = [
            "operation" => "add",
            "notification_key_name" => $groupName,
            "notification_key" => $notificationKey,
            "registration_ids" => $registrationIds,
        ];

        $headers = [
            'Authorization' => sprintf('key=%s', $this->apiKey),
            'Content-Type' => 'application/json',
            'project_id' => $this->senderId
        ];

        return $this->makeRequest('POST', self::DEVICE_GROUP_API_URL, json_encode($body), $headers);
    }

    /**
     * removeDeviceFromGroup
     *
     * @param string $groupName
     * @param string $notificationKey
     * @param array $registrationIds
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function removeDeviceFromGroup(string $groupName, string $notificationKey, array $registrationIds)
    {
        $body = [
            "operation" => "remove",
            "notification_key_name" => $groupName,
            "notification_key" => $notificationKey,
            "registration_ids" => $registrationIds,
        ];

        $headers = [
            'Authorization' => sprintf('key=%s', $this->apiKey),
            'Content-Type' => 'application/json',
            'project_id' => $this->senderId
        ];

        return $this->makeRequest('POST', self::DEVICE_GROUP_API_URL, json_encode($body), $headers);
    }

    /**
     * addTopicSubscription
     *
     * @param string $topicName
     * @param array $registrationTokens
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addTopicSubscription(string $topicName, array $registrationTokens)
    {
        $body = [
            "to" => '/topics/' . $topicName,
            "registration_tokens" => $registrationTokens
        ];

        $headers = [
            'Authorization' => sprintf('key=%s', $this->apiKey),
            'Content-Type' => 'application/json'
        ];

        return $this->makeRequest('POST', self::TOPIC_SUBSCRIPTION_API_URL, json_encode($body), $headers);
    }

    /**
     * makeRequest
     *
     * @param string $method
     * @param string $uri
     * @param mixed|null $body
     * @param string[]|null $headers
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function makeRequest(string $method, string $uri, $body = null, $headers = null)
    {
        if (!$this->guzzleClient) {
            throw new \RuntimeException('Http client not found');
        }

        return $this->guzzleClient->request($method, $uri, [
            'body' => $body,
            'headers' => $headers
        ]);
    }

    /**
     * getApiUrl
     *
     * @return string
     */
    private function getApiUrl()
    {
        return isset($this->proxyApiUrl) ? $this->proxyApiUrl : self::DEFAULT_API_URL;
    }
}