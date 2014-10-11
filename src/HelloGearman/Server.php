<?php

namespace HelloGearman;

use HelloGearman\Command\CommandInterface;
use HelloGearman\Request\InvalidArgumentException;
use HelloGearman\Request\Request;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Server implements MessageComponentInterface
{
    /** @var \SplObjectStorage */
    private $clients;

    /** @var \GearmanClient */
    private $client;
    function __construct(\GearmanClient $client)
    {
        $this->client = $client;
        $this->clients = new \SplObjectStorage();
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $request = new Request($msg);
            $request->setClientId(spl_object_hash($from));
        } catch (InvalidArgumentException $e) {
            echo $e->getMessage() . PHP_EOL;
            return;
        }

        $this->client->doBackground('process-request', serialize($request));
    }

    /**
     * @return ConnectionInterface[]
     */
    public function getClients()
    {
        return $this->clients;
    }
}