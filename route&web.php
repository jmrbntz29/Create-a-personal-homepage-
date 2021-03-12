WebSocketController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Ratchet\WebSocket\Version\RFC6455\Connection;

class WebSocketController
{
    /**
     * @var Connection
     */
    protected $connection = null;

    /**
     * @var array|mixed
     */
    protected $data = [];

    /**
     * @var Connection
     */
    protected $currentClient = null;

    /**
     * @var Connection[]
     */
    protected $otherClients = [];

    public function __construct(Request $request)
    {
        $this->connection = $request->get('connection');
        $this->data = $request->get('data');
        $this->currentClient = $request->get('current_client');
        $this->otherClients = $request->get('other_clients');
    }

    public function sendToOthers(array $data) {
        foreach ($this->otherClients as $client) {
            $client->send(json_encode($data));
        }
    }

    public function onOpen(Request $request)
    {
        if (!Auth::check()) {
            $this->currentClient->close();
            return;
        }

        echo 'Opened' . PHP_EOL;
        echo $request->ip() . PHP_EOL;

        $this->sendToOthers([
            'type' => 'USER_CONNECTED',
        ]);
    }

    public function onMessage(Request $request)
    {
        if (!Auth::check()) {
            $this->currentClient->close();
            return;
        }

        echo 'New message' . PHP_EOL;
        echo $request->ip() . PHP_EOL;

        $this->sendToOthers([
            'type' => 'MESSAGE_RECEIVED',
            'data' => [
                'user' => Auth::user()->name,
                'message' => $this->data->message,
            ]
        ]);
    }

    public function onClose(Request $request)
    {
        echo 'Closed' . PHP_EOL;
        echo $request->ip() . PHP_EOL;

        $this->sendToOthers([
            'type' => 'USER_DISCONNECTED',
        ]);
    }

    public function onError(Request $request)
    {
        echo 'Error' . PHP_EOL;
    }
}
