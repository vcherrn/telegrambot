<?php
use App\Http\Controllers\BotManController;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage; 
use BotMan\BotMan\Interfaces\Middleware\MySending;

$botman = resolve('botman'); 

$botman->hears('/start', BotManController::class.'@startConversation'); 