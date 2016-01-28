<?php

namespace Drunken;

use GuzzleHttp\Client;

class Slack
{
    private $url;
    private $guzzle;
    private $channel;
    private $username;

    public function __construct($url, $channel, $username)
    {
        $this->guzzle = new Client;
        $this->url = $url;
        $this->channel = $channel;
        $this->username = $username;
    }

    public function info($message)
    {
        $this->sendMessage($message, '#439FE0');
    }

    public function warning($message)
    {
        $this->sendMessage($message, 'warning');
    }

    public function error($message)
    {
        $this->sendMessage($message, 'danger');
    }

    public function sendMessage($message, $color)
    {
        $data = [
            'username' => $this->username,
            "icon_emoji" => ":cop:",
            'attachments' => [
                [
                    "fallback" => "MBill.co report: $message",
                    'color' => $color,
                    "text" => $message,
                    'pretext' => "MBill.co report:",
                ]
            ],
        ];
        $this->guzzle->post($this->url, ['json' => $data]);
    }
}
