<?php namespace Illuminate\Auth\Reminders;

use Illuminate\Contracts\Auth\Remindable;

interface ReminderRepositoryInterface {

	/**
	 * Create a new reminder record and token.
	 *
	 * @param  \Illuminate\Contracts\Auth\Remindable  $user
	 * @return string
	 */
	public function create(Remindable $user);

	/**
	 * Determine if a reminder record exists and is valid.
	 *
	 * @param  \Illuminate\Contracts\Auth\Remindable  $user
	 * @param  string  $token
	 * @return bool
	 */
	public function exists(Remindable $user, $token);
	
	/**
	 * Determine if a reminder record already exists user and is valid
	 * Return the valid token is exist
	 *
	 * @param  string $email
	 * @return mixed
	 */
	public function requested($email);

	/**
	 * Delete a reminder record by token.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function delete($token);

	/**
	 * Delete expired reminders.
	 *
	 * @return void
	 */
	public function deleteExpired();

}
