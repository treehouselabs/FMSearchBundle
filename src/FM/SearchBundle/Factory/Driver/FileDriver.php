<?php

namespace FM\SearchBundle\Factory\Driver;

class FileDriver implements DriverInterface
{
    private $paths;
    private $files;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function getAllFiles()
    {
        if (!$this->files) {
            $this->files = array();

            foreach ($this->paths as $path) {
                if (!is_dir($path)) {
                    throw new \LogicException(sprintf('"%s" is not a valid path', $path));
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {

                    $fileName = $file->getBasename('.php');

                    if ($fileName === $file->getBasename()) {
                        continue;
                    }

                    $sourceFile = realpath($file->getPathName());
                    require_once $sourceFile;
                    $this->files[] = $sourceFile;
                }
            }
        }

        return $this->files;
    }

    public function getAllClassNames()
    {
        $classes = array();

        $includedFiles = $this->getAllFiles();

        $declared = get_declared_classes();
        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}
