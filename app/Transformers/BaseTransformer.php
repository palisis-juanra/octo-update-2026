<?php
namespace App\Transformers;

use League\Fractal\Manager;
use \League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    public const BASIC = 'BASIC';
    public const FULL_TRANSFORM = 'FULL_TRANSFORM';
    public const ERROR_MESSAGE_MODE_NOT_SUPPORTED = 'Transformer mode is not supported';

    private string $mode;
    protected Manager $manager;

    public function __construct(string $mode = self::BASIC)
    {
        $this->mode = $mode;
        $this->manager = new Manager;
    }

    public final function transform(object $object): array
    {
        if ($this->mode === self::BASIC){
            return $this->basicTransform($object);
        } 
        if ($this->mode === self::FULL_TRANSFORM){
            return $this->fullTransform($object);
        }

        if (method_exists($this, $this->mode)) {
            $mode = $this->mode;
            return $this->$mode($object);
        }

        throw new \InvalidArgumentException(self::ERROR_MESSAGE_MODE_NOT_SUPPORTED);
    }

    abstract protected function basicTransform($object): array;

    abstract protected function fullTransform($object): array;

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }
}