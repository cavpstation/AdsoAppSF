<?php

namespace Illuminate\Console;

use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    use Concerns\CallsCommands,
        Concerns\HasParameters,
        Concerns\InteractsWithIO,
        Concerns\InteractsWithSignals,
        Macroable;

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $laravel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

    /**
     * The console command help text.
     *
     * @var string
     */
    protected $help;

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // We will go ahead and set the name, description, and parameters on console
        // commands just to make things a little easier on the developer. This is
        // so they don't have to all be manually specified in the constructors.
        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->name);
        }

        // Once we have constructed the command, we'll set the description and other
        // related properties of the command. If a signature wasn't used to build
        // the command we'll set the arguments and the options on this command.
        $this->setDescription((string) $this->description);

        $this->setHelp((string) $this->help);

        $this->setHidden($this->isHidden());

        if (! isset($this->signature)) {
            $this->specifyParameters();
        }

        if ($this instanceof Isolatable) {
            $this->configureIsolation();
        }
    }

    /**
     * Configure the console command using a fluent definition.
     *
     * @return void
     */
    protected function configureUsingFluentDefinition()
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        parent::__construct($this->name = $name);

        // After parsing the signature we will spin through the arguments and options
        // and set them on this command. These will already be changed into proper
        // instances of these "InputArgument" and "InputOption" Symfony classes.
        $this->getDefinition()->addArguments($arguments);
        $this->getDefinition()->addOptions($options);
    }

    /**
     * Configure the console command for isolation.
     *
     * @return void
     */
    protected function configureIsolation()
    {
        $this->getDefinition()->addOption(new InputOption(
            'isolated',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not run the command if another instance of the command is already running'
        ));
    }

    /**
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $this->laravel->make(
            OutputStyle::class, ['input' => $input, 'output' => $output]
        );

        $this->components = $this->laravel->make(Factory::class, ['output' => $this->output]);

        try {
            return parent::run(
                $this->input = $input, $this->output
            );
        } finally {
            $this->untrap();
        }
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this instanceof Isolatable && $this->option('isolated') &&
            ! $this->commandIsolationMutex()->create($this)) {
            $this->comment(sprintf(
                'The [%s] command is already running.', $this->getName()
            ));

            return (int) (is_numeric($this->option('isolated'))
                        ? $this->option('isolated')
                        : self::SUCCESS);
        }

        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        try {
            return (int) $this->laravel->call([$this, $method]);
        } finally {
            if ($this instanceof Isolatable && $this->option('isolated')) {
                $this->commandIsolationMutex()->forget($this);
            }
        }
    }

    /**
     * Get a command isolation mutex instance for the command.
     *
     * @return \Illuminate\Console\CommandMutex
     */
    protected function commandIsolationMutex()
    {
        return $this->laravel->bound(CommandMutex::class)
            ? $this->laravel->make(CommandMutex::class)
            : $this->laravel->make(CacheCommandMutex::class);
    }

    /**
     * Resolve the console command instance for the given command.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function resolveCommand($command)
    {
        if (! class_exists($command)) {
            return $this->getApplication()->find($command);
        }

        $command = $this->laravel->make($command);

        if ($command instanceof SymfonyCommand) {
            $command->setApplication($this->getApplication());
        }

        if ($command instanceof self) {
            $command->setLaravel($this->getLaravel());
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * {@inheritdoc}
     */
    public function setHidden(bool $hidden = true): static
    {
        parent::setHidden($this->hidden = $hidden);

        return $this;
    }

    /**
     * Get the Laravel application instance.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }

    /**
     * Set the Laravel application instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $laravel
     * @return void
     */
    public function setLaravel($laravel)
    {
        $this->laravel = $laravel;
    }
}
