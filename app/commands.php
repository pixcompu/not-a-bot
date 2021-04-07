<?php
return [
	[
		'name' => tt('command.kitten.name'),
		'class' => 'Meow',
		'description' => tt('command.kitten.description'),
		'namespace' => '\\app\\commands',
		'usage' => tt('command.kitten.usage'),
		'keywords' => ['cat', 'miau', 'meow']
	],
	[
		'name' => tt('command.response.name'),
		'class' => 'Response',
		'description' => tt('command.response.description'),
		'namespace' => '\\app\\commands',
		'usage' => tt('command.response.usage'),
		'keywords' => ['response', 're']
	],
	[
		'name' => tt('command.gif.name'),
		'class' => 'Gif',
		'description' => tt('command.gif.description'),
		'namespace' => '\\app\\commands',
		'usage' => tt('command.gif.usage'),
		'keywords' => ['gif']
	],
	[
		'name' => tt('command.help.name'),
		'class' => 'Help',
		'description' => tt('command.help.description'),
		'namespace' => '\\app\\commands',
		'usage' => tt('command.help.usage'),
		'keywords' => ['help', 'man']
	]
];