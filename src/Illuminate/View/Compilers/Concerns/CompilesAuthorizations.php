<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesAuthorizations
{
    /**
     * Compile the can statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCan($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the cannot statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCannot($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the canany statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCanany($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->any{$expression}): ?>";
    }

    /**
     * Compile the allows statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileAllows($expression)
    {
        return '<?php $access = app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->inspect' . $expression . '; ?>
<?php if ($access->allowed()): ?>
<?php if (isset($message)) { $__messageOriginal = $message; } $message = $access->message(); ?>';
    }

    /**
     * Compile the else-can statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecan($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the else-cannot statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecannot($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the else-canany statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecanany($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->any{$expression}): ?>";
    }

    /**
     * Compile the else-allows statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElseallows($expression)
    {
        return '<?php else: ?>
<?php if (isset($message)) { $__messageOriginal = $message; } $message = $access->message(); ?>';
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcan()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-cannot statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcannot()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-canany statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcanany()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-allows statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndallows()
    {
        return '<?php endif; ?>';
    }
}
