<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Foundation\Concerns\ResolvesDumpSource;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class Frame
{
    use ResolvesDumpSource;

    /**
     * The "flatten" exception instance.
     *
     * @var \Symfony\Component\ErrorHandler\Exception\FlattenException
     */
    protected $exception;

    /**
     * The application's class map.
     *
     * @var array<string, string>
     */
    protected $classMap;

    /**
     * The frame's raw data from the "flatten" exception.
     *
     * @var array{file: string, line: int, class?: string, type?: string, function?: string}
     */
    protected $frame;

    /**
     * The application's base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Create a new frame instance.
     *
     * @param  \Symfony\Component\ErrorHandler\Exception\FlattenException  $exception
     * @param  array<string, string>  $classMap
     * @param  array{file: string, line: int, class?: string, type?: string, function?: string}  $frame
     * @param  string  $basePath
     * @return void
     */
    public function __construct(FlattenException $exception, array $classMap, array $frame, string $basePath)
    {
        $this->exception = $exception;
        $this->classMap = $classMap;
        $this->frame = $frame;
        $this->basePath = $basePath;
    }

    /**
     * Get the frame's source / origin.
     *
     * @return string
     */
    public function source()
    {
        return match (true) {
            is_string($this->class()) => $this->class(),
            default => $this->file(),
        };
    }

    /**
     * Get the frame's editor link.
     *
     * @return string
     */
    public function editorHref()
    {
        return $this->resolveSourceHref($this->frame['file'], $this->frame['line']);
    }

    /**
     * Get the frame class, if any.
     *
     * @return string|null
     */
    public function class()
    {
        $class = array_search((string) realpath($this->frame['file']), $this->classMap, true);

        return $class === false ? null : $class;
    }

    /**
     * Get the frame file.
     *
     * @return string
     */
    public function file()
    {
        return str_replace($this->basePath.'/', '', $this->frame['file']);
    }

    /**
     * Get the frame line number.
     *
     * @return int
     */
    public function line()
    {
        return $this->frame['line'];
    }

    /**
     * Get the frame function or method.
     *
     * @return string
     */
    public function callable()
    {
        return match (true) {
            ! empty($this->frame['function']) => $this->frame['function'],
            default => 'throw',
        };
    }

    /**
     * Get the frame code snippet.
     *
     * @return string
     */
    public function snippet()
    {
        $contents = file($this->frame['file']) ?: [];

        $start = max($this->frame['line'] - 6, 0);

        $length = 8 * 2 + 1;

        return implode('', array_slice($contents, $start, $length));
    }

    /**
     * Determine if the frame is from the vendor directory.
     *
     * @return bool
     */
    public function isFromVendor()
    {
        return ! str_starts_with($this->frame['file'], $this->basePath)
            || str_starts_with($this->frame['file'], $this->basePath.'/vendor');
    }
}
