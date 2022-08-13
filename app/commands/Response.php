<?php

namespace app\commands;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Component;
use Kreait\Firebase\Exception\DatabaseException;
use util\Storage;
use util\Text;

class Response extends InteractiveCommand
{
	private Storage $storage;

	public function __construct(Discord $botDiscord, Discord $messageDiscord, Message $message, array $args = [])
	{
		parent::__construct($botDiscord, $messageDiscord, $message, $args);
		$this->storage = Storage::getInstance();
	}

	public function execute(): void
	{
		// the message only have the reference to the channel, but with the channel we can get the guild
		$channel = $this->messageDiscord->getChannel($this->message->channel_id);

		$args = $this->args;
		// we could use the same command to save and retrieve custom bot messages
		if(empty($this->args)) {
			// show all responses saved
			$catalogMessage = $this->getResponseCatalogMessage();
			$catalogMessage->setReplyTo($this->message);
			$this->message->reply($catalogMessage);
		} elseif (count($this->args) === 1) {
			$keyword = array_shift($args);
			$this->showResponse($keyword);
		} else {
			$keyword = array_shift($args);
			$value = implode(' ', $args);
			// set a new custom response for a specific keyword
			// if the keyword was defined previously it will be overwritten, so be careful
			$this->storage->setResponse(
				$channel->guild_id,
				$keyword,
				$value
			);
			$this->message->reply(sprintf(tt('command.response.set'), $keyword));
		}
	}

	/**
	 * Main entrypoint for interactions in this class
	 * @param Interaction $interaction
	 * @throws NoPermissionsException
	 * @throws DatabaseException
	 */
	public function interact(Interaction $interaction): void
	{
		if($this->isInteraction($interaction->data->custom_id,'RESPONSE_SELECT')){
			// the user selected a keyword from the selectable list
			$interaction->message->edit(
				$this->getResponseCatalogMessage(
					$interaction->data->values[0]
				)
			);
		} elseif($this->isInteraction($interaction->data->custom_id,'BUTTON_ACCEPT')){
			// the user wants to post this response
			$selectedKeyword = $this->getSelectedOption($interaction);
			$catalogMessage = (new MessageBuilder())->setContent($selectedKeyword);
			$interaction->message->edit($catalogMessage);
			$this->showResponse($selectedKeyword);
		} elseif($this->isInteraction($interaction->data->custom_id,'BUTTON_REMOVE')){
			// the user wants to delete a response
			$selectedKeyword = $this->getSelectedOption($interaction);
			$storage = Storage::getInstance();
			$channel = $this->messageDiscord->getChannel($this->message->channel_id);
			$storage->removeResponse($channel->guild_id, $selectedKeyword);
			$catalogMessage = (new MessageBuilder())
				->setContent(sprintf(tt('command.response.remove'), Text::bold($selectedKeyword)));
			$interaction->message->edit($catalogMessage);
		} elseif($this->isInteraction($interaction->data->custom_id,'BUTTON_CANCEL')){
			// the user wants to cancel the catalog of responses
			$messageId = $interaction->message->message_reference->message_id;
			$interaction->channel->deleteMessages([$messageId]);
			$interaction->message->delete();
		}
	}

	/**
	 * Build the response component that will allow the user to:
	 * - browse the catalog of responses
	 * - select a response to share in the channel
	 * - remove a response
	 * - close the response browser
	 *
	 * Each time the user triggers any action of this component a interaction will be generated
	 * @param null $keywordSelected
	 * @return MessageBuilder
	 */
	private function getResponseCatalogMessage($keywordSelected = null)
	{
		// the message only have the reference to the channel, but with the channel we can get the guild
		$channel = $this->messageDiscord->getChannel($this->message->channel_id);
		$storage = Storage::getInstance();
		$responses = $storage->getAllResponses($channel->guild_id) ?? [];
		if(empty($responses)){
			return (new MessageBuilder())
				->setContent(tt('command.response.empty'));
		}

		// the response is an object with multiple keys, each one holding the information of the response stored
		// as we want an iterable list instead of a map we first extract the list of keys from the response
		$keywords = array_keys($responses);

		// for the user will be way easier to browse the responses if they are sorted alphabetically
		// so we sort the list of response keywords that we will display to the user
		sort($keywords);

		// there are 2 instances where this method will be called:
		// - each time the user wants to select a response, we should show the response selected
		$currentKeyword = $keywordSelected;
		if(is_null($keywordSelected)){
			// - the first time we open the catalog, and we should show the first response stored
			$currentKeyword = $keywords[0];
		}

		// to create the user interface we should use a MessageBuilder object which will hold all the components
		$builder = MessageBuilder::new();
		// first we set the preview, which is just passing the response content to the builder
		// discord will automatically render it as a image/video/text
		$builder->setContent($responses[$currentKeyword]['value']);

		// our interface will have a selectable list so the user can select a keyword and do something with it
		// discord have a limit of 25 items per selectable list and we also dont want to make it too long
		// so our approach will be similar to the pages of a paginator, first we show the first 10
		// then we will be showing 5 before and 5 after the keyword selected so the user can go forward and back
		$maxOptions = 10;
		$optionsRemaining = $maxOptions;
		$indexSelected = 0;
		// first we will get the index of the keyword selected by the user, so we can know which keywords are before and after
		foreach ($keywords as $i => $keyword){
			if($keyword === $currentKeyword){
				$indexSelected = $i;
			}
		}
		// we could just subtract 5 from the index and add 5 to the index to get the min and max indexes of the options
		$max = $indexSelected + ($maxOptions / 2);
		$min = $indexSelected - ($maxOptions / 2);

		// but we need to consider that for example if the user select the first keyword there aren't keywords before it
		// and we still want to show the same amount of keywords, so in that case we want to add the extra to the max instead
		if($min < 0){
			$max += abs($min);
		}
		// also in case the user selects the lasts keywords we need to do a similar process subtracting from the min
		if($max > count($keywords)){
			$min -= $max - count($keywords);
		}

		// now that we have the max and min index we will build the selectable list
		$select = SelectMenu::new($this->getInteractionCustomId('RESPONSE_SELECT'));
		foreach ($keywords as $i => $keyword){
			$title = ($i + 1) . '. ' . $keyword;
			$isSelected = $indexSelected === $i;
			$canAddOption = $min < $i && $i < $max && $optionsRemaining > 0;
			if($canAddOption){
				// the set default option will make this option selected
				$select->addOption(
					Option::new($title, $keyword)
						->setDefault($isSelected)
				);
				$optionsRemaining--;
			}
		}
		$builder->addComponent($select);

		// lastly we will draw a row of buttons that will trigger actions with the response selected
		// ACCEPT will post the response on the channel in the same fashion as $re <keyword>
		$selectButton = Button::new(Button::STYLE_SUCCESS)
			->setLabel('Seleccionar')
			->setCustomId($this->getInteractionCustomId('BUTTON_ACCEPT'));
		// REMOVE will remove the response from the catalog
		$removeButton = Button::new(Button::STYLE_DANGER)
			->setLabel('Eliminar')
			->setCustomId($this->getInteractionCustomId('BUTTON_REMOVE'));
		// CANCEL will remove the catalog and the original message that triggered the catalog
		$cancelButton = Button::new(Button::STYLE_SECONDARY)
			->setLabel('Cancelar')
			->setCustomId($this->getInteractionCustomId('BUTTON_CANCEL'));

		// add the row of buttons to the interface
		$row = ActionRow::new()
			->addComponent($selectButton)
			->addComponent($removeButton)
			->addComponent($cancelButton);
		$builder->addComponent($row);
		return $builder;
	}

	/**
	 * Show the response selected in the channel
	 * @param $keyword
	 * @throws NoPermissionsException
	 * @throws DatabaseException
	 */
	private function showResponse($keyword)
	{
		// the message only have the reference to the channel, but with the channel we can get the guild
		$channel = $this->messageDiscord->getChannel($this->message->channel_id);

		// get a custom response in particular
		$response = $this->storage->getResponse(
			$channel->guild_id,
			$keyword
		);
		// we could have 2 outcomes
		// the response is set and can be returned
		if(isset($response)){
			$this->message->channel->sendMessage($response['value']);
		} else {
			// we didn't find a response for this keyword
			$this->message->reply(tt('command.response.missing'));
		}
	}

	/**
	 * When we do any action that is not select an option from the selectable list, we can't get the item selected
	 * by reading the data from the interaction.
	 *
	 * For those scenarios we need to scrap the component and look manually which of their options is selected
	 * @param Interaction $interaction
	 * @return null
	 */
	private function getSelectedOption(Interaction $interaction)
	{
		// iterate the message components
		foreach($interaction->message->components as $parentComponents){
			// by default each component have a list of child components because we could have rows
			// so we iterate once more to access the selectable lists
			foreach($parentComponents->components as $childComponent){
				/** @var Component $childComponent */
				// check if this component is the selectable list that we created
				if($this->isInteraction($childComponent->custom_id,'RESPONSE_SELECT')){
					$options = $childComponent->options;
					// now iterate the options and check which is default (is the way to set if it's selected)
					foreach($options as $option){
						if($option->default){
							return $option->value;
						}
					}
				}
			}
		}
		return null;
	}
}