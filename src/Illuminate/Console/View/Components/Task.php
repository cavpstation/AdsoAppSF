<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\terminal;
use Throwable;

class Task extends Component
{
    /**
     * The counter of class instances.
     *
     * @var int
     */
    private static $counter = 0;

    /**
     * The list of separators.
     *
     * @var array
     */
    private $separators = ['.', '-'];

    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $description
     * @param  (callable(): bool)|null  $task
     * @param  int  $verbosity
     * @return void
     */
    public function render($description, $task = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $description = $this->mutate($description, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $descriptionWidth = mb_strlen(preg_replace("/\<[\w=#\/\;,:.&,%?]+\>|\\e\[\d+m/", '$1', $description) ?? '');

        $this->output->write("  $description ", false, $verbosity);

        $startTime = microtime(true);

        $result = false;

        try {
            $result = ($task ?: fn () => true)();
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $runTime = $task
                ? (' '.number_format((microtime(true) - $startTime) * 1000).'ms')
                : '';

            $runTimeWidth = mb_strlen($runTime);
            $width = min(terminal()->width(), 150);
            $dots = max($width - $descriptionWidth - $runTimeWidth - 10, 0);

            self::$counter++;
            $separator = $this->separators[(self::$counter % 2)];

            $this->output->write(str_repeat("<fg=gray>$separator</>", $dots), false, $verbosity);
            $this->output->write("<fg=gray>$runTime</>", false, $verbosity);

            $this->output->writeln(
                $result !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>',
                $verbosity,
            );
        }
    }
}
