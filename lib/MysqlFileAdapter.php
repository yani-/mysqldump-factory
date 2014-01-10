<?php

class MysqlFileAdapter
{
	protected $fileHandler = null;

	public function open($fileName)
    {
		$this->fileHandler = fopen($fileName, 'wb');
		if (false === $this->fileHandler) {
            throw new \Exception('Output file is not writable', 2);
		}
	}

	public function write($str)
    {
		$bytesWritten = 0;
		if (false === ($bytesWritten = fwrite($this->fileHandler, $str))) {
			throw new \Exception('Writting to file failed! Probably, there is no more free space left?', 4);
		}

		return $bytesWritten;
	}

	public function close()
    {
		return fclose($this->fileHandler);
	}
}
