<?php

namespace Deployer\Log;

final class Log
{

    private $messages = [];

    private $debug = false;

    static $instance = null;


    private function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public static function instance($debug = false)
    {

        if (self::$instance === null) {
            self::$instance = new Log($debug);
        }

        return self::$instance;
    }

    public function getMessages(): array { return $this->messages; }

    public function count(): int { return count($this->messages); }

    /**
     * Clean the already logged messages.
     */
    public function clear()
    {
        $this->messages = [];
    }

    /**
     * Create a new message.
     *
     * @param string $type
     * @param string $text
     */
    public function message(string $type, string $text)
    {
        $message = new Message($type, $text);
        $this->messages[] = $message;

        if ($this->inDebug()) {
            $message->log();
        }
    }

    /**
     * Create a new message of type info.
     *
     * @param string $message
     */
    public function info(string $message)
    {
        $this->message('info', $message);
    }

    /**
     * Create a new message of type error.
     *
     * @param string $message
     */
    public function error(string $message)
    {
        $this->message('error', $message);
    }

    /**
     * Create a new message of type warning.
     *
     * @param string $message
     */
    public function warning(string $message)
    {
        $this->message('warning', $message);
    }

    /**
     * Create a new message of type success.
     *
     * @param string $message
     */
    public function success(string $message)
    {
        $this->message('success', $message);
    }

    /**
     * Check if the current log contains any message of the given type.
     *
     * @param string $type
     * @return bool
     */
    public function hasAny(string $type): bool
    {
        foreach ($this->getMessages() as $message) {
            if ($message->is($type)) {
                return true;
            }
        }

        return false;
    }

    public function setDebug(bool $state)
    {
        $this->debug = $state;
    }

    public function inDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Retrieve all the messages.
     *
     * @return string
     */
    public function dump()
    {
        return implode('', array_map(function (Message $message) {
            return $message->formatted();
        }, $this->getMessages()));
    }

    public static function destroy()
    {
        self::$instance = null;
    }
}