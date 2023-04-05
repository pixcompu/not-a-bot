<?php

namespace app\commands;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Component;
use Kreait\Firebase\Exception\DatabaseException;
use util\Storage;
use util\Text;

class Catalog extends InteractiveCommand
{
	private Storage $storage;

	public function __construct(Discord $discord, Interaction $interaction)
	{
		parent::__construct($discord, $interaction);
		$this->storage = Storage::getInstance();
	}

	public function execute(): void
	{
		// show all responses saved
		$catalogMessage = $this->getResponseCatalogMessage();
		$this->reply($catalogMessage, true);
	}

	/**
	 * Main entrypoint for interactions in this class
	 * @throws DatabaseException
	 */
	public function interact(): void
	{
		if($this->isInteraction($this->interaction->data->custom_id,'RESPONSE_SELECT')){
			// the user selected a keyword from the selectable list
			$this->interaction->updateMessage(
				$this->getResponseCatalogMessage(
					$this->interaction->data->values[0]
				)
			);
		} elseif($this->isInteraction($this->interaction->data->custom_id,'BUTTON_REMOVE')){
			// the user wants to delete a response
			$selectedKeyword = $this->getSelectedOption($this->interaction);

			// retrieve the response to show it one last time
			$response = $this->storage->getResponse(
				$this->interaction->guild_id,
				$selectedKeyword
			);

			// remove the response from the db
			$this->storage->removeResponse($this->interaction->guild_id, $selectedKeyword);

			// update the catalog to continue deleting things
			$this->interaction->updateMessage(
				$this->getResponseCatalogMessage()
			);

			// notify on the channel that the response was deleted
			$this->postOnChannel(sprintf(tt('command.catalog.deleted'), Text::bold($this->interaction->user->username), Text::bold($selectedKeyword)));
			$this->postOnChannel($response['value']);
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
		$channel = $this->interaction->channel;
		$responses = $this->storage->getAllResponses($channel->guild_id) ?? [];
		if(empty($responses)){
			// we will update the catalog response with a PATCH so we explicitly need to set the components as empty
			// when there aren't responses anymore after deleting the last one
			return MessageBuilder::new()
				->setComponents([])
				->setContent(tt('command.catalog.empty'));
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

		// REMOVE will remove the response from the catalog
		$removeButton = Button::new(Button::STYLE_DANGER)
			->setLabel(tt('command.catalog.remove_button'))
			->setCustomId($this->getInteractionCustomId('BUTTON_REMOVE'));

		// add the row of buttons to the interface
		$row = ActionRow::new()
			->addComponent($removeButton);
		$builder->addComponent($row);
		return $builder;
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