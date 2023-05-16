<?php

namespace App\Conversations;

use App\Category;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class TradeConversation extends Conversation
{
    public $botMsgId;
    public $from = 0;
    public $weapons;

    public function showInterface()
    {
        $category = Category::all();
        $btnCategory = array();

        foreach ($category as $c) {
            $btnCategory[] = Button::create($c->title)->value($c->id);
        }

        $question = Question::create('Выберите категорию')
            ->addButtons($btnCategory);

        $this->ask($question, function (Answer $answer) use ($category) {

            if ($answer->isInteractiveMessageReply()) {
                foreach ($category as $c) {
                    if ((int) $answer->getValue() == $c->id) {
                        $this->showProductFromCategory($c->id);
                        break;
                    }
                }
            }
        });
    }

    public function showProductFromCategory($idCategory)
    {
        $this->weapons = Category::find($idCategory)->weapon()->skip($this->from)->take(5)->get();
        $count = Category::find($idCategory)->weapon()->count(); 

        $btnItem = array();

        foreach ($this->weapons as $w) {
            $btnItem[] = Button::create($w->title)->value($w->id);
        }

        $cardWeapon = Question::create("Товары из категории")
            ->addButtons(array_merge($btnItem,
                array_filter([
                    $this->from + 5 < $count ? Button::create('➡️')->value('next') : null,
                    $this->from - 5 >= 0 ? Button::create('⬅️')->value('prev') : null,
                    Button::create('Сменить категорию')->value('category')
                ])));

        $func = function (Answer $answer) use ($idCategory, $count, &$func) {
            $id = json_decode(strstr((string) $this->getPayload(), '{'))->result->message_id;

            if ($answer->isInteractiveMessageReply()) {
                switch ($answer->getValue()) {
                    case "next":
                        $this->from += 5;
                        $this->weapons = Category::find($idCategory)->weapon()->skip($this->from)->take(5)->get();

                        $itemsArr = array();

                        foreach ($this->weapons as $w) {
                            $itemsArr[] = array('text' => $w->title, 'callback_data' => $w->id);
                        }

                        $keyboard = array(
                            $itemsArr,
                            array_filter(array(
                                $this->from - 5 >= 0 ? array('text' => '⬅️', 'callback_data' => "prev") : null,
                                $this->from + 5 < $count ? array('text' => '➡️', 'callback_data' => "next") : null,
                            )),
                            array(array('text' => 'Сменить категорию', 'callback_data' => "category"))
                        );

                        $inlineKeyboardMarkup = array(
                            'inline_keyboard' => $keyboard,
                        );

                        $this->bot->sendRequest('editMessageText',
                            [
                                'message_id' => $id,
                                'text' => "Товары из категории",
                                'reply_markup' => json_encode($inlineKeyboardMarkup),
                            ]);
                        break;
                    case "prev":
                        $this->from -= 5;
                        $this->weapons = Category::find($idCategory)->weapon()->skip($this->from)->take(5)->get();

                        $itemsArr = array();

                        foreach ($this->weapons as $w) {
                            $itemsArr[] = array('text' => $w->title, 'callback_data' => $w->id);
                        }

                        $keyboard = array(
                            $itemsArr,
                            array_filter(array(
                                $this->from - 5 >= 0 ? array('text' => '⬅️', 'callback_data' => "prev") : null,
                                $this->from + 5 < $count ? array('text' => '➡️', 'callback_data' => "next") : null,
                            )),
                            array(array('text' => 'Сменить категорию', 'callback_data' => "category"))
                        );

                        $inlineKeyboardMarkup = array(
                            'inline_keyboard' => $keyboard,
                        ); 

                        $this->bot->sendRequest('editMessageText',
                            [
                                'message_id' => $id,
                                'text' => "Товары из категории",
                                'reply_markup' => json_encode($inlineKeyboardMarkup),
                            ]); 
                        break; 
                    case "category": 

                        $this->from = 0;
                        $this->showInterface();
                        return;

                        break;   
                    default:
                        foreach ($this->weapons as $w) {
                            if ((int) $answer->getValue() == $w->id) {

                                $invoice = [
                                    'title' => $w->title,
                                    'description' => "$w->description",
                                    'payload' => 'info',
                                    'provider_token' => '1744374395:TEST:1dfb64d29434ce0da459',
                                    'currency' => 'RUB',
                                    'prices' => json_encode(array(array(
                                        'label' => 'Finish cost',
                                        'amount' => 100000,
                                    ))),
                                    'photo_url' => asset($w->image),
                                    'photo_width' => 500,
                                    'photo_height' => 300,
                                ];

                                $this->bot->sendRequest('sendInvoice', $invoice);

                                break;
                            }
                        }
                        break;
                }

                $this->askWithoutMessage($func);
            }
        };

        $this->ask($cardWeapon, $func, ['parse_mode' => 'Markdown']);

    }

    public function convertHttpMessage($answer)
    {
        return json_decode(strstr((string) $answer, '{'));
    } 

    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $this->showInterface();
    }
}
