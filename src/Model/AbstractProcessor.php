<?php
namespace Zero1\LayoutXmlPlus\Model;

use Zero1\LayoutXmlPlus\Model\Processor\Sanitizer;

abstract class AbstractProcessor implements ProcessorInterface
{
    protected Sanitizer $sanitizer;

    public function __construct(
        Sanitizer $sanitizer
    ){
        $this->sanitizer = $sanitizer;   
    }

    protected function sanitize($value)
    {
        return $this->sanitizer->sanitize($value);
    }

    protected function unsanitize($value)
    {
        return $this->sanitizer->unsanitize($value);
    }
}
