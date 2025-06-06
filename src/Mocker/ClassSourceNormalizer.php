<?php

namespace MaplePHP\Unitary\Mocker;

use MaplePHP\Http\Stream;
use ReflectionClass;
use ReflectionException;

class ClassSourceNormalizer
{
    private string $className;
    private string $shortClassName;
    private string $namespace = "";
    private ?string $source = null;

    public function __construct(string $className)
    {
        $this->className = $className;

        $shortClassName = explode("\\", $className);
        $this->shortClassName = (string)end($shortClassName);
    }

    /**
     * Add a namespace to class
     *
     * @param string $namespace
     * @return void
     */
    public function addNamespace(string $namespace): void
    {
        $this->namespace = ltrim($namespace, "\\");
    }

    public function getClassName(): string
    {
        return $this->namespace . "\\" . $this->shortClassName;
    }

    /**
     * Retrieves the raw source code of the class.
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        try {

            $ref = new ReflectionClass($this->className);

            var_dump($ref->getInterfaceNames());
            die;
            $file = $ref->getFileName();
            if (!$file || !is_file($file)) {
                // Likely an eval'd or dynamically declared class.
                return null;
            }

            $stream = new Stream($file, 'r');
            $this->source = $stream->getLines($ref->getStartLine(), $ref->getEndLine());
            var_dump($this->source);

            die("ww");
            return $this->source;

        } catch (ReflectionException) {
            return null;
        }
    }

    /**
     * Normalize PHP visibility modifiers in source code.
     * - Removing 'final' from class and method declarations
     * - Replacing 'private' with 'protected' for visibility declarations (except promoted properties)
     *
     * @param string $code
     * @return string
     */
    public function normalizeVisibility(string $code): string
    {
        $code = preg_replace('/\bfinal\s+(?=class\b)/i', '', $code);
        $code = preg_replace('/\bfinal\s+(?=(public|protected|private|static)?\s*function\b)/i', '', $code);
        $code = preg_replace_callback('/(?<=^|\s)(private)(\s+(static\s+)?(?:function|\$))/mi', [$this, 'replacePrivateWithProtected'], $code);
        $code = preg_replace_callback('/__construct\s*\((.*?)\)/s', [$this, 'convertConstructorVisibility'], $code);
        return $code;
    }

    /**
     * Returns the normalized, mockable version of the class source.
     *
     * @return string|false
     */
    public function getMockableSource(): string|false
    {
        $source = "namespace {$this->namespace};\n" . $this->getSource();
        return $source !== null ? $this->normalizeVisibility($source) : false;
    }

    /**
     * Replace `private` with `protected` in method or property declarations.
     *
     * @param array $matches
     * @return string
     */
    protected function replacePrivateWithProtected(array $matches): string
    {
        return 'protected' . $matches[2];
    }

    /**
     * Convert `private` to `protected` in constructor-promoted properties.
     *
     * @param array $matches
     * @return string
     */
    protected function convertConstructorVisibility(array $matches): string
    {
        $params = preg_replace('/\bprivate\b/', 'protected', $matches[1]);
        return '__construct(' . $params . ')';
    }

}
