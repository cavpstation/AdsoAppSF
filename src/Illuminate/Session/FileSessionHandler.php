<?php namespace Illuminate\Session;

use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FileNotFoundException;

class FileSessionHandler implements \SessionHandlerInterface {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path where sessions should be stored.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new file driven handler instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $path
	 * @return void
	 */
	public function __construct(Filesystem $files, $path)
	{
		$this->path = $path;
		$this->files = $files;
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		try
		{
			return $this->files->get($this->path.'/'.$sessionId);
		}
		catch (FileNotFoundException $e)
		{
			return '';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		$this->files->put($this->path.'/'.$sessionId, $data);
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		$this->files->delete($this->path.'/'.$sessionId);
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		$files = Finder::create()
					->in($this->path)
					->files()
					->ignoreDotFiles(true)
					->date('<= now - '.$lifetime.' seconds');

		foreach ($files as $file)
		{
			$this->files->delete($file->getRealPath());
		}
	}

}
