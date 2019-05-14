<?php

/**
 * Package builder.
 */
class Builder extends RecursiveFilterIterator
{
    /** @var string The src directory. */
    static protected $srcDir;

    /** @var string The out file. */
    static protected $outFile;

    /** @var array The files filter map */
    static protected $filesInclude = [
        '/\\.php$/',
        '/\\.twig$/',
        '/^install\\.xml$/',
        '/qiwi\\.png$/',
        '/cacert.pem$/'
    ];

    /** @var array The files filter map */
    static protected $filesExclude = [
        '/tests/',
        '/jautoload\\.php$/',
        '/psalm-autoload\\.php/',
        '/build_phar\\.php/',
    ];

    /**
     * Make extension package.
     */
    static public function make()
    {
        self::$srcDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
        self::$outFile = __DIR__ . DIRECTORY_SEPARATOR . 'qiwi.ocmod.zip';
        echo 'Generate extension file ' . self::$outFile . PHP_EOL;
        $zip = new ZipArchive;
        $zip->open(self::$outFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new self(
                new RecursiveDirectoryIterator(
                    self::$srcDir,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            ),
            RecursiveIteratorIterator::LEAVES_ONLY | RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $name => $fileInfo)
        {
            if (!$fileInfo->isDir()) {
                $relativePath = self::getRelativePath($fileInfo);
                $zip->addFile($fileInfo->getRealPath(), $relativePath);
                echo 'Add ', $relativePath, PHP_EOL;
            }
        }
        $zip->close();
    }

    /**
     * Get relative path from src.
     *
     * @param SplFileInfo $fileInfo The file info.
     *
     * @return string
     */
    static protected function getRelativePath(SplFileInfo $fileInfo)
    {
        return substr(str_replace('\\', '/', $fileInfo->getRealPath()), strlen(self::$srcDir) + 1);
    }

    /**
     * Build file filtering.
     *
     * @return bool
     */
    public function accept()
    {
        return $this->current()->isDir() ||
            array_reduce(self::$filesInclude, [$this, 'checkFile'], false) &&
            !array_reduce(self::$filesExclude, [$this, 'checkFile'], false);
    }

    /**
     * Check file by patterns.
     *
     * @param bool $carry The result.
     * @param string $item The pattern.
     *
     * @return bool
     */
    protected function checkFile($carry, $item)
    {
        return $carry || preg_match($item, self::getRelativePath($this->current()));
    }
}
