<?php

use Illuminate\Filesystem\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase {

	public function testGetRetrievesFiles()
	{
		file_put_contents(__DIR__.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$this->assertEquals('Hello World', $files->get(__DIR__.'/file.txt'));
		@unlink(__DIR__.'/file.txt');
	}


	/**
	 * @expectedException Illuminate\Filesystem\FileNotFoundException
	 */
	public function testGetThrowsExceptionNonexisitingFile()
	{
		$files = new Filesystem;
		$files->get(__DIR__.'/unknown-file.txt');
	}


	public function testGetRequireReturnsProperly()
	{
		file_put_contents(__DIR__.'/file.php', '<?php return "Howdy?"; ?>');
		$files = new Filesystem;
		$this->assertEquals('Howdy?',$files->getRequire(__DIR__.'/file.php'));
		@unlink(__DIR__.'/file.php');
	}


	/**
	 * @expectedException Illuminate\Filesystem\FileNotFoundException
	 */
	public function testGetRequireThrowsExceptionNonexisitingFile()
	{
		$files = new Filesystem;
		$files->getRequire(__DIR__.'/file.php');
	}


	public function testAppendAddsDataToFile()
	{
		file_put_contents(__DIR__.'/file.txt', 'foo');
		$files = new Filesystem;
		$bytesWritten = $files->append(__DIR__.'/file.txt','bar');
		$this->assertEquals(mb_strlen('bar','8bit'),$bytesWritten);
		$this->assertFileExists(__DIR__.'/file.txt');
		$this->assertStringEqualsFile(__DIR__.'/file.txt','foobar');
		@unlink(__DIR__.'/file.txt');
	}


	public function testMoveMovesFiles()
	{
		file_put_contents(__DIR__.'/foo.txt', 'foo');
		$files = new Filesystem;
		$files->move(__DIR__.'/foo.txt',__DIR__.'/bar.txt');
		$this->assertFileExists(__DIR__.'/bar.txt');
		$this->assertFileNotExists(__DIR__.'/foo.txt');
		@unlink(__DIR__.'/bar.txt');
	}


	public function testExtensionReturnsExtension()
	{
		file_put_contents(__DIR__.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals('txt',$files->extension(__DIR__.'/foo.txt'));
		@unlink(__DIR__.'/foo.txt');
	}


	public function testTypeIndentifiesFile()
	{
		file_put_contents(__DIR__.'/foo.txt', 'foo');
		$files = new Filesystem;
		$this->assertEquals('file',$files->type(__DIR__.'/foo.txt'));
		@unlink(__DIR__.'/foo.txt');
	}


	public function testTypeIndentifiesDirectory()
	{
		mkdir(__DIR__.'/foo');
		$files = new Filesystem;
		$this->assertEquals('dir',$files->type(__DIR__.'/foo'));
		@rmdir(__DIR__.'/foo');
	}


	public function testPutStoresFiles()
	{
		$files = new Filesystem;
		$files->put(__DIR__.'/file.txt', 'Hello World');
		$this->assertEquals('Hello World', file_get_contents(__DIR__.'/file.txt'));
		@unlink(__DIR__.'/file.txt');
	}


	public function testDeleteRemovesFiles()
	{
		file_put_contents(__DIR__.'/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->delete(__DIR__.'/file.txt');
		$this->assertFileNotExists(__DIR__.'/file.txt');
		@unlink(__DIR__.'/file.txt');
	}


	public function testPrependExistingFiles()
	{
		$files = new Filesystem;
		$files->put(__DIR__.'/file.txt', 'World');
		$files->prepend(__DIR__.'/file.txt', 'Hello ');
		$this->assertEquals('Hello World', file_get_contents(__DIR__.'/file.txt'));
		@unlink(__DIR__.'/file.txt');
	}


	public function testPrependNewFiles()
	{
		$files = new Filesystem;
		$files->prepend(__DIR__.'/file.txt', 'Hello World');
		$this->assertEquals('Hello World', file_get_contents(__DIR__.'/file.txt'));
		@unlink(__DIR__.'/file.txt');
	}


	public function testDeleteDirectory()
	{
		mkdir(__DIR__.'/foo');
		file_put_contents(__DIR__.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->deleteDirectory(__DIR__.'/foo');
		$this->assertFalse(is_dir(__DIR__.'/foo'));
		$this->assertFileNotExists(__DIR__.'/foo/file.txt');
	}


	public function testCleanDirectory()
	{
		mkdir(__DIR__.'/foo');
		file_put_contents(__DIR__.'/foo/file.txt', 'Hello World');
		$files = new Filesystem;
		$files->cleanDirectory(__DIR__.'/foo');
		$this->assertTrue(is_dir(__DIR__.'/foo'));
		$this->assertFileNotExists(__DIR__.'/foo/file.txt');
		@rmdir(__DIR__.'/foo');
	}


	public function testFilesMethod()
	{
		mkdir(__DIR__.'/foo');
		file_put_contents(__DIR__.'/foo/1.txt', '1');
		file_put_contents(__DIR__.'/foo/2.txt', '2');
		mkdir(__DIR__.'/foo/bar');
		$files = new Filesystem;
		$this->assertEquals(array(__DIR__.'/foo/1.txt', __DIR__.'/foo/2.txt'), $files->files(__DIR__.'/foo'));
		unset($files);
		@unlink(__DIR__.'/foo/1.txt');
		@unlink(__DIR__.'/foo/2.txt');
		@rmdir(__DIR__.'/foo/bar');
		@rmdir(__DIR__.'/foo');
	}


	public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
	{
		$files = new Filesystem;
		$this->assertFalse($files->copyDirectory(__DIR__.'/foo/bar/baz/breeze/boom', __DIR__));
	}


	public function testCopyDirectoryMovesEntireDirectory()
	{
		mkdir(__DIR__.'/tmp', 0777, true);
		file_put_contents(__DIR__.'/tmp/foo.txt', '');
		file_put_contents(__DIR__.'/tmp/bar.txt', '');
		mkdir(__DIR__.'/tmp/nested', 0777, true);
		file_put_contents(__DIR__.'/tmp/nested/baz.txt', '');

		$files = new Filesystem;
		$files->copyDirectory(__DIR__.'/tmp', __DIR__.'/tmp2');
		$this->assertTrue(is_dir(__DIR__.'/tmp2'));
		$this->assertFileExists(__DIR__.'/tmp2/foo.txt');
		$this->assertFileExists(__DIR__.'/tmp2/bar.txt');
		$this->assertTrue(is_dir(__DIR__.'/tmp2/nested'));
		$this->assertFileExists(__DIR__.'/tmp2/nested/baz.txt');

		unlink(__DIR__.'/tmp/nested/baz.txt');
		rmdir(__DIR__.'/tmp/nested');
		unlink(__DIR__.'/tmp/bar.txt');
		unlink(__DIR__.'/tmp/foo.txt');
		rmdir(__DIR__.'/tmp');

		unlink(__DIR__.'/tmp2/nested/baz.txt');
		rmdir(__DIR__.'/tmp2/nested');
		unlink(__DIR__.'/tmp2/foo.txt');
		unlink(__DIR__.'/tmp2/bar.txt');
		rmdir(__DIR__.'/tmp2');
	}

}
