<?php namespace Illuminate\Validation;

use Closure;
use Symfony\Component\Translation\TranslatorInterface;

class Factory {

	/**
	 * The Translator implementation.
	 *
	 * @var Symfony\Component\Translator\TranslatorInterface
	 */
	protected $translator;

	/**
	 * The Presence Verifier implementation.
	 *
	 * @var Illuminate\Validation\PresenceVerifierInterface
	 */
	protected $presenceVerifier;

	/**
	 * All of the custom validator extensions.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * All of the custom implicit validator extensions.
	 *
	 * @var array
	 */
	protected $implicitExtensions = array();

	/**
	 * List of new non iterable rules.
	 *
	 * @var array
	 */
	protected $nonIterative = array();

	/**
	 * The Validator resolver instance.
	 *
	 * @var Closure
	 */
	protected $resolver;

	/**
	 * Create a new Validator factory instance.
	 *
	 * @param  Symfony\Component\Translation\TranslatorInterface  $translator
	 * @return void
	 */
	public function __construct(TranslatorInterface $translator)
	{
		$this->translator = $translator;
	}

	/**
	 * Create a new Validator instance.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @param  array  $messages
	 * @return Illuminate\Validation\Validator
	 */
	public function make(array $data, array $rules, array $messages = array())
	{
		$validator = $this->resolve($data, $rules, $messages);

		// The presence verifier is responsible for checking the unique and exists data
		// for the validator. It is behind an interface so that multiple versions of
		// it may be written besides database. We'll inject it into the validator.
		if ( ! is_null($this->presenceVerifier))
		{
			$validator->setPresenceVerifier($this->presenceVerifier);
		}

		$validator->addExtensions($this->extensions);

		// Next, we will add the implicit extensions, which are similar to the required
		// and accepted rule in that they are run even if the attributes is not in a
		// array of data that is given to a validator instances via instantiation.
		$implicit = $this->implicitExtensions;

		if ( ! empty($this->nonIterative))
		{
			$validator->addNonIterative($this->nonIterative);
		}

		$validator->addImplicitExtensions($implicit);

		return $validator;
	}

	/**
	 * Resolve a new Validator instance.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @param  array  $messages
	 * @return Illuminate\Validation\Validator
	 */
	protected function resolve($data, $rules, $messages)
	{
		if (is_null($this->resolver))
		{
			return new Validator($this->translator, $data, $rules, $messages);
		}
		else
		{
			return call_user_func($this->resolver, $this->translator, $data, $rules, $messages);
		}
	}

	/**
	 * Register a custom validator extension.
	 *
	 * @param  string  $rule
	 * @param  Closure  $extension
	 * @return void
	 */
	public function extend($rule, Closure $extension)
	{
		$this->extensions[$rule] = $extension;
	}

	/**
	 * Register a custom implicit validator extension.
	 *
	 * @param  string  $rule
	 * @param  Closure $extension
	 * @return void
	 */
	public function extendImplicit($rule, Closure $extension)
	{
		$this->implicitExtensions[$rule] = $extension;
	}

	/**
	 * Register non iterative rules.
	 *
	 * @param  string/array  $rules
	 * @return void
	 */
	public function setNonIterative($rules)
	{
		$this->nonIterative = (array) $rules;
	}

	/**
	 * Register additional non iterative rules.
	 *
	 * @param  string/array  $rules
	 * @return void
	 */
	public function addNonIterative($rules)
	{
		$this->nonIterative = array_merge($this->nonIterative, (array) $rules);
	}

	/**
	 * Remove non iterative rule.
	 *
	 * @param  string/array  $rules
	 * @return void
	 */
	public function removeNonIterative($rules)
	{
		$this->nonIterative = array_diff($this->nonIterative, (array) $rules);
	}

	/**
	 * Get non iterative rules.
	 *
	 * @return array
	 */
	public function getNonIterative()
	{
		return $this->nonIterative;
	}

	/**
	 * Set the Validator instance resolver.
	 *
	 * @param  Closure  $resolver
	 * @return void
	 */
	public function resolver(Closure $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * Get the Translator implementation.
	 *
	 * @return Symfony\Component\Translation\TranslatorInterface
	 */
	public function getTranslator()
	{
		return $this->translator;
	}

	/**
	 * Get the Presence Verifier implementation.
	 *
	 * @return Illuminate\Validation\PresenceVerifierInterface
	 */
	public function getPresenceVerifier()
	{
		return $this->presenceVerifier;
	}

	/**
	 * Set the Presence Verifier implementation.
	 *
	 * @param  Illuminate\Validation\PresenceVerifierInterface  $presenceVerifier
	 * @return void
	 */
	public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
	{
		$this->presenceVerifier = $presenceVerifier;
	}

}
