<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'bug')]
class BugCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'bug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start to create a bug report';

    /**
     * The issue url.
     *
     * @var string
     */
    protected $url = 'https://github.com/laravel/framework/issues/new';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $osCommand = $this->detectOsName();
        $url = $this->getUrl();

        exec("$osCommand $url");

        return 0;
    }

    /**
     * Detect the OS name and get the current command.
     *
     * @return string
     */
    protected function detectOsName()
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'start',
            'Linux', 'Darwin' => 'xdg-open',
        };
    }

    /**
     * Get the full url.
     *
     * @return string
     */
    protected function getUrl()
    {
        $dbInfo = config('database.default').'-'.DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);

        return implode([$this->url, '?', http_build_query([
            'assignees' => null,
            'labels' => null,
            'template' => 'Bug_report.yml',
            'laravel_version=' => $this->laravel->version(),
            'php_version' => phpversion(),
            'database_info' => $dbInfo,
        ])]);
    }
}
