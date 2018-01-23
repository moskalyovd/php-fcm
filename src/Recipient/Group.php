<?php
namespace paragraph1\phpFCM\Recipient;

class Group implements Recipient
{
    /**
     * @var string $token
     */
    private $token;

    /**
     * __construct
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getIdentifier(): string
    {
        return $this->token;
    }
}