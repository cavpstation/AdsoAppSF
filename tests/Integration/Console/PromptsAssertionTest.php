<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class PromptsAssertionTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app[Kernel::class]->registerCommand(new DummyPromptsTextareaAssertionCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsTextAssertionCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsPasswordAssertionCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsConfirmAssertionCommand());
    }

    public function testAssertionForTextPrompt()
    {
        $this
            ->artisan(DummyPromptsTextareaAssertionCommand::class)
            ->expectsQuestion('What is your name?', 'John')
            ->expectsOutput('John');
    }

    public function testAssertionForTextareaPrompt()
    {
        $this
            ->artisan(DummyPromptsTextareaAssertionCommand::class)
            ->expectsQuestion('What is your name?', 'John')
            ->expectsOutput('John');
    }

    public function testAssertionForPasswordPrompt()
    {
        $this
            ->artisan(DummyPromptsPasswordAssertionCommand::class)
            ->expectsQuestion('What is your password?', 'secret')
            ->expectsOutput('secret');
    }

    public function testAssertionForConfirmPrompt()
    {
        $this
            ->artisan(DummyPromptsConfirmAssertionCommand::class)
            ->expectsQuestion('Is your name John?', false)
            ->expectsOutput('Your name is not John.');

        $this
            ->artisan(DummyPromptsConfirmAssertionCommand::class)
            ->expectsQuestion('Is your name John?', true)
            ->expectsOutput('Your name is John.');
    }
}

class DummyPromptsTextAssertionCommand extends Command
{
    protected $signature = 'ask:text';

    public function handle()
    {
        $name = text('What is your name?', 'John');

        $this->line($name);
    }
}

class DummyPromptsTextareaAssertionCommand extends Command
{
    protected $signature = 'ask:textarea';

    public function handle()
    {
        $name = textarea('What is your name?', 'John');

        $this->line($name);
    }
}

class DummyPromptsPasswordAssertionCommand extends Command
{
    protected $signature = 'ask:password';

    public function handle()
    {
        $name = password('What is your password?', 'secret');

        $this->line($name);
    }
}

class DummyPromptsConfirmAssertionCommand extends Command
{
    protected $signature = 'ask:confirm';

    public function handle()
    {
        $confirmed = confirm('Is your name John?');

        if ($confirmed) {
            $this->line('Your name is John.');
        } else {
            $this->line('Your name is not John.');
        }
    }
}
