<?php

namespace MaplePHP\Unitary\Mocker;

use Closure;
use MaplePHP\Unitary\TestWrapper;

class MethodItem
{
    private ?Mocker $mocker = null;
    public mixed $return = null;
    public ?int $count = null;

    public ?string $class = null;
    public ?string $name = null;
    public ?bool $isStatic = null;
    public ?bool $isPublic = null;
    public ?bool $isPrivate = null;
    public ?bool $isProtected = null;
    public ?bool $isAbstract = null;
    public ?bool $isFinal = null;
    public ?bool $returnsReference = null;
    public ?bool $hasReturnType = null;
    public ?string $returnType = null;
    public ?bool $isConstructor = null;
    public ?bool $isDestructor = null;
    public ?array $parameters = null;
    public ?array $hasDocComment = null;
    public ?int $startLine = null;
    public ?int $endLine = null;
    public ?string $fileName = null;
    protected bool $hasReturn = false;
    protected ?Closure $wrapper = null;

    public function __construct(?Mocker $mocker = null)
    {
        $this->mocker = $mocker;
    }

    public function wrap($call): self
    {
        $inst = $this;
        $wrap = new class($this->mocker->getClassName()) extends TestWrapper {
        };
        $call->bindTo($this->mocker);
        $this->wrapper = $wrap->bind($call);
        return $inst;
    }

    public function getWrap(): ?Closure
    {
        return $this->wrapper;
    }

    public function hasReturn(): bool
    {
        return $this->hasReturn;
    }

    /**
     * Check if method has been called x times
     * @param int $count
     * @return $this
     */
    public function count(int $count): self
    {
        $inst = $this;
        $inst->count = $count;
        return $inst;
    }

    /**
     * Change what the method should return
     *
     * @param mixed $value
     * @return $this
     */
    public function return(mixed $value): self
    {
        $inst = $this;
        $inst->hasReturn = true;
        $inst->return = $value;
        return $inst;
    }

    /**
     * Set the class name.
     *
     * @param string $class
     * @return self
     */
    public function class(string $class): self
    {
        $inst = $this;
        $inst->class = $class;
        return $inst;
    }

    /**
     * Set the method name.
     *
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        $inst = $this;
        $inst->name = $name;
        return $inst;
    }

    /**
     * Mark the method as static.
     *
     * @return self
     */
    public function isStatic(): self
    {
        $inst = $this;
        $inst->isStatic = true;
        return $inst;
    }

    /**
     * Mark the method as public.
     *
     * @return self
     */
    public function isPublic(): self
    {
        $inst = $this;
        $inst->isPublic = true;
        return $inst;
    }

    /**
     * Mark the method as private.
     *
     * @return self
     */
    public function isPrivate(): self
    {
        $inst = $this;
        $inst->isPrivate = true;
        return $inst;
    }

    /**
     * Mark the method as protected.
     *
     * @return self
     */
    public function isProtected(): self
    {
        $inst = $this;
        $inst->isProtected = true;
        return $inst;
    }

    /**
     * Mark the method as abstract.
     *
     * @return self
     */
    public function isAbstract(): self
    {
        $inst = $this;
        $inst->isAbstract = true;
        return $inst;
    }

    /**
     * Mark the method as final.
     *
     * @return self
     */
    public function isFinal(): self
    {
        $inst = $this;
        $inst->isFinal = true;
        return $inst;
    }

    /**
     * Mark the method as returning by reference.
     *
     * @return self
     */
    public function returnsReference(): self
    {
        $inst = $this;
        $inst->returnsReference = true;
        return $inst;
    }

    /**
     * Mark the method as having a return type.
     *
     * @return self
     */
    public function hasReturnType(): self
    {
        $inst = $this;
        $inst->hasReturnType = true;
        return $inst;
    }

    /**
     * Set the return type of the method.
     *
     * @param string $type
     * @return self
     */
    public function returnType(string $type): self
    {
        $inst = $this;
        $inst->returnType = $type;
        return $inst;
    }

    /**
     * Mark the method as a constructor.
     *
     * @return self
     */
    public function isConstructor(): self
    {
        $inst = $this;
        $inst->isConstructor = true;
        return $inst;
    }

    /**
     * Mark the method as a destructor.
     *
     * @return self
     */
    public function isDestructor(): self
    {
        $inst = $this;
        $inst->isDestructor = true;
        return $inst;
    }

    /**
     * Not yet working
     * Set the parameters of the method.
     *
     * @param array $parameters
     * @return self
     */
    public function parameters(array $parameters): self
    {
        throw new \BadMethodCallException('Method Item::parameters() does not "YET" exist.');
        $inst = $this;
        $inst->parameters = $parameters;
        return $inst;
    }

    /**
     * Set the doc comment for the method.
     *
     * @return self
     */
    public function hasDocComment(): self
    {
        $inst = $this;
        $inst->hasDocComment = [
            "isString" => [],
            "startsWith" => ["/**"]
        ];
        return $inst;
    }

    /**
     * Set the starting line number of the method.
     *
     * @param int $line
     * @return self
     */
    public function startLine(int $line): self
    {
        $inst = $this;
        $inst->startLine = $line;
        return $inst;
    }

    /**
     * Set the ending line number of the method.
     *
     * @param int $line
     * @return self
     */
    public function endLine(int $line): self
    {
        $inst = $this;
        $inst->endLine = $line;
        return $inst;
    }

    /**
     * Set the file name where the method is declared.
     *
     * @param string $file
     * @return self
     */
    public function fileName(string $file): self
    {
        $inst = $this;
        $inst->fileName = $file;
        return $inst;
    }
}