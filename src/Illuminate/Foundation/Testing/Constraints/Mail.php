<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Closure;
use Illuminate\Foundation\Testing\Constraints\Mail\HtmlLink;
use Illuminate\Foundation\Testing\Constraints\Mail\Subject;
use Illuminate\Foundation\Testing\Constraints\Mail\ToAddress;
use Swift_Message;

class Mail
{
    protected $constraints = [];
    protected $lastErrorMessage;

    public function __construct(Closure $assertions = null)
    {
        if ($assertions) {
            $assertions($this);
        }
    }

    public function to($users)
    {
        return $this->addConstraint(new ToAddress($users));
    }

    public function subject($subject)
    {
        return $this->addConstraint(new Subject($subject));
    }

    public function htmlLink($text, $url = null)
    {
        return $this->addConstraint(new HtmlLink($text, $url));
    }

    public function matches(Swift_Message $message)
    {
        foreach ($this->constraints as $constraint) {
            if (!$constraint->matches($message)) {
                return false;
            }
        }

        return true;
    }

    protected function addConstraint($constraint)
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    public function __toString()
    {
        return implode(', ', $this->constraints);
    }
}
