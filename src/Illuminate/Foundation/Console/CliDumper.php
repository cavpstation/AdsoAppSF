<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Foundation\Concerns\ResolvesDumpSource;
use Illuminate\Foundation\VarDumper\Concerns\HandlesDumps;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;

class CliDumper extends BaseCliDumper
{
    use HandlesDumps;
    use ResolvesDumpSource;

    /**
     * The base path of the application.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The output instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The compiled view path for the application.
     *
     * @var string
     */
    protected $compiledViewPath;

    /**
     * If the dumper is currently dumping.
     *
     * @var bool
     */
    protected $dumping = false;

    /**
     * Create a new CLI dumper instance.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $basePath
     * @param  string  $compiledViewPath
     * @return void
     */
    public function __construct($output, $basePath, $compiledViewPath)
    {
        parent::__construct();

        $this->basePath = $basePath;
        $this->output = $output;
        $this->compiledViewPath = $compiledViewPath;
    }

    /**
     * Dump a variable with its source file / line.
     *
     * @param  \Symfony\Component\VarDumper\Cloner\Data  $data
     * @return void
     */
    public function dumpWithSource(Data $data)
    {
        if ($this->dumping) {
            $this->dump($data);

            return;
        }

        $this->dumping = true;

        $output = (string) $this->dump($data, true);
        $lines = explode("\n", $output);

        $lines[0] .= $this->getDumpSourceContent();

        $this->output->write(implode("\n", $lines));

        $this->dumping = false;
    }

    /**
     * Get the dump's source console content.
     *
     * @return string
     */
    protected function getDumpSourceContent()
    {
        if (is_null($dumpSource = $this->resolveDumpSource())) {
            return '';
        }

        [$file, $relativeFile, $line] = $dumpSource;

        return sprintf(
            ' <fg=gray>// <fg=gray;href=file://%s%s>%s%s</></>',
            $file,
            is_null($line) ? '' : "#L$line",
            $relativeFile,
            is_null($line) ? '' : ":$line"
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function supportsColors(): bool
    {
        return $this->output->isDecorated();
    }
}
