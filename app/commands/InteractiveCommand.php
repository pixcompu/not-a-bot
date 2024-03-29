<?php

namespace app\commands;

abstract class InteractiveCommand extends Command
{
	/**
	 * The function that will trigger on a message that triggers this command
	 * @return mixed
	 */
	public abstract function execute() : void;

	/**
	 * The function that will trigger on a user interaction with a message generated by this command
	 */
	public abstract function interact() : void;

	/**
	 * Creates an interaction id that we can assign to each component
	 *
	 * All interactions go through the same listener and the only information that we have available in that listener is the custom_id
	 * that we assigned to each component, so it's really important that each custom_id have the following information:
	 * - Which class we need to call to follow up the interaction
	 * - Which action we need to trigger to follow up the interaction
	 *
	 * So our approach will be to compose the custom_id with the following structure: <class name with namespace>|<action name>
	 * that way we can know which class and action we need to invoke to follow up the interaction later.
	 * @param $id
	 * @return string
	 */
	protected function getInteractionCustomId($id)
	{
		return get_class($this) . '|' . $id;
	}

	/**
	 * Asserts if an interaction name matches with a custom id
	 *
	 * This is an utility method just to avoid having the explode logic everytime we want to know if an interaction
	 * belongs to a specific action
	 * @param $interactionFullName
	 * @param $interactionName
	 * @return bool
	 */
	protected function isInteraction($interactionFullName, $interactionName)
	{
		$interactionParsedName = explode('|', $interactionFullName)[1] ?? '';
		return $interactionParsedName === $interactionName;
	}
}