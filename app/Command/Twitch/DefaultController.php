<?php

namespace App\Command\Twitch;

use App\TwitchChatClient;
use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle()
    {
        $this->getPrinter()->info("Launching the bot...");

        $app = $this->getApp();

        // Get the username and oauth from the config
        $twitch_user = $app->config->twitch_user;
        $twitch_oauth = $app->config->twitch_oauth;

        if (!$twitch_user OR !$twitch_oauth) {
            $this->getPrinter()->error("Missing 'twitch_user' and/or 'twitch_oauth' config settings.");
            return;
        }

        $client = new TwitchChatClient($twitch_user, $twitch_oauth);
        $client->connect();

        if (!$client->isConnected()) {
            $this->getPrinter()->error("It was not possible to connect.");
            return;
        }

        $this->getPrinter()->info("Connected.\n");

        while (true) {
            $content = $client->read(512);

            //is it a ping?
            if (strstr($content, 'PING')) {
                $client->send('PONG :tmi.twitch.tv');
                continue;
            }

            //is it an actual msg?
            if (strstr($content, 'PRIVMSG')) {
                if($this->getContent($content) === '!hello') {
                  $client->send('Hello '.$this->getNick($content));
                }
                $this->printMessage($content);
                continue;
            }

        }
    }

    public function printMessage($raw_message)
    {
      // Split the msg ad the username
      $parts = explode(":", $raw_message, 3);
      $nick_parts = explode("!", $parts[1]);

      $nick = $nick_parts[0];
      $message = $parts[2];

      $style_nick = "info";

      if ($nick === $this->getApp()->config->twitch_user) {
        $style_nick = "info_alt";
      }

      $this->getPrinter()->out($nick, $style_nick);
      $this->getPrinter()->out(': ');
      $this->getPrinter()->out($message);
      $this->getPrinter()->newline();
    }
    public function getContent ($msg) {

      $parts = explode(":", $msg, 3);
      return trim( $parts[2] );
    }
    public function getNick($msg) {
      $parts = explode(":", $msg, 3);
      $nick_parts = explode("!", $parts[1]);
      return trim($nick_parts[0]);
    }
}