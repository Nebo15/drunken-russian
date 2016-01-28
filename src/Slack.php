<?php

namespace Drunken;

use GuzzleHttp\Client;

class Slack
{
    private $url;
    private $guzzle;
    private $channel;
    private $username;
    private $messages = [
        '#439FE0' => [],
        'danger' => [],
        'warning' => [],
    ];

    public function __construct($url, $channel, $username)
    {
        $this->guzzle = new Client;
        $this->url = $url;
        $this->channel = $channel;
        $this->username = $username;
    }

    public function info($message)
    {
        $this->messages['#439FE0'][] = $message;
    }

    public function warning($message)
    {
        $this->messages['warning'][] = $message;
    }

    public function error($message)
    {
        $this->messages['danger'][] = $message;
    }

    public function sendCollectedMessages()
    {
        foreach ($this->messages as $color => $messages) {
            if ($messages) {
                foreach ($messages as $key => $message) {
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

                    unset($this->messages[$color][$key]);
                }
            }
        }
    }
}
