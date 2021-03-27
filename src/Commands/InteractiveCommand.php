<?php

namespace Evets11\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class InteractiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'interactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'An interactive artisan command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $allCommands = $this->getAllCommands();

        $groupedCommands = $this->getGroupedCommands($allCommands);

        $group = $this->askForCommandGroup($groupedCommands);

        $commands = $this->getCommandsForGroup($groupedCommands, $group);

        $command = $this->askForCommand($commands);

        $signature = $this->getCommandSignature($group, $command);

        $arguments = $this->askForCommandArguments($signature);

        $options = $this->askForCommandOptions($signature);

        return $this->callCommand($signature, $arguments, $options);
    }

    /**
     * Ask the user to select the command group they want to run a command from.
     *
     * @param Collection $commands
     * @return string
     */
    protected function askForCommandGroup(Collection $commands): string
    {
        $groups = $commands->keys();
        $groups = $groups->combine($groups);

        return $this->choice(
            'What type of command would like to run?',
            $groups->all()
        );
    }

    /**
     * Ask the user to select a specific command to run.
     *
     * @param Collection $commands
     * @return string
     */
    protected function askForCommand(Collection $commands): string
    {
        return $this->choice(
            'Which command would you like to run?',
            $commands->keyBy('command')->map(function ($item) {
                return $item['description'];
            })->all()
        );
    }

    /**
     * Ask the user to enter values for any arguments that are set on the command.
     *
     * @param string $signature
     * @return array
     */
    protected function askForCommandArguments(string $signature): array
    {
        $return = [];
        $appCommand = $this->resolveCommand($signature);
        $arguments = $appCommand->getArguments();

        if ($arguments ?? []) {
            $this->info('The below arguments are taken from the command, they may or many not be optional depending on the command you are running.');

            foreach ($arguments as $argument) {
                $value = $this->ask($argument[2], '');
                if ($value) {
                    $return[$argument[0]] = $value;
                }
            }
        }

        return $return;
    }

    /**
     * Ask the user to enter any options to append to the command, if applicable.
     *
     * @param string $signature
     * @return array
     */
    protected function AskForCommandOptions(string $signature): array
    {
        $return = [];
        $appCommand = $this->resolveCommand($signature);
        $options = $appCommand->getOptions();

        if ($options) {
            $this->info('For example, you could enter -m when making a model to create a migration.');
            $this->info('Or --queue=somequeuename if running a queue related command.');

            $value = $this->ask('Enter any command options', '');
            $options = explode(' ', $value);

            foreach ($options as $option) {
                $optionValue = explode('=', $option);

                if ($optionValue[0]) {
                    if (count($options) === 1) {
                        $return[$optionValue[0]] = 1;
                    } else {
                        $return[$optionValue[0]] = $optionValue[1];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Call the command based on the user input given.
     *
     * @param string $signature
     * @param array $arguments
     * @param array $options
     * @return mixed
     */
    protected function callCommand(string $signature, array $arguments, array $options)
    {
        $argsAndOptions = array_merge($arguments, $options);

        return $this->call($signature, $argsAndOptions);
    }

    /**
     * Get all available command for this application.
     *
     * @return Collection
     */
    protected function getAllCommands(): Collection
    {
        $app = $this->getApplication();
        return collect($app->all());
    }

    /**
     * Group command using the prefix before the : in the signature.
     *
     * @param Collection $commands
     * @return Collection
     */
    protected function getGroupedCommands(Collection $commands): Collection
    {
        return $commands->map(function ($item) {
            $signature = explode(':', $item->getName());
            return [
                'group' => $signature[0],
                'command' => $signature[1] ?? '',
                'description' => $item->getDescription()
            ];
        })->groupBy('group')->sortKeys();
    }

    /**
     * Return all commands for a specific group.
     *
     * @param Collection $commands
     * @param string $group
     * @return Collection
     */
    protected function getCommandsForGroup(Collection $commands, string $group): Collection
    {
        return $commands[$group];
    }

    /**
     * Generate the command signature based on the group and command.
     *
     * @param string $group
     * @param string $command
     * @return string
     */
    protected function getCommandSignature(string $group, string $command): string
    {
        $prefixedCommand = $command ? ":{$command}" : $command;

        return "{$group}{$prefixedCommand}";
    }
}
