<?php

namespace NotificationChannels\PagerDuty;

use GuzzleHttp\Client;
use NotificationChannels\PagerDuty\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;

class PagerDutyChannel
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @throws \NotificationChannels\PagerDuty\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {

        if (!$routing_key = $notifiable->routeNotificationFor('PagerDuty')) {
            return;
        }

        /** @var PagerDutyMessage $data */
        $data = $notification->toPagerDuty($notifiable);
        $data->routingKey($routing_key);

        $response = $this->client->post('https://events.pagerduty.com/v2/enqueue', [
            'body' => json_encode($data->getPayload())
        ]);

        switch ($response->getStatusCode()) {
            case 200:
                return;
            case 400:
                throw CouldNotSendNotification::serviceBadRequest($response->getBody());
            case 429:
                throw CouldNotSendNotification::rateLimit();
            default:
                throw CouldNotSendNotification::unknownError($response->getStatusCode());
        }
    }
}
