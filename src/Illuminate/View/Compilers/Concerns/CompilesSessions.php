<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesSessions
{
    /**
     * Compile the session statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSession($expression)
    {
        $expression = $this->stripParentheses($expression);

        return '<?php $__sessionArgs = ['.$expression.'];
if (session()->has($__sessionArgs[0])) :
if (isset($session)) { $__sessionOriginal = $session; }
$session = session()->get($__sessionArgs[0]); ?>';
    }

    /**
     * Compile the endsession statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndsession($expression)
    {
        return '<?php unset($session);
if (isset($__sessionOriginal)) { $session = $__sessionOriginal; }
endif;
unset($__sessionArgs); ?>';
    }
}
