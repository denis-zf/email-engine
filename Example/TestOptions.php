<?php

namespace SfCod\EmailEngineBundle\Example;

use SfCod\EmailEngineBundle\Template\OptionsInterface;

/**
 * Class TestArguments
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\EmailEngineBundle\Example
 */
class TestOptions implements OptionsInterface
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $filePath;

    /**
     * TestArguments constructor.
     *
     * @param string $message
     * @param string $filePath
     */
    public function __construct(string $message, string $filePath)
    {
        $this->message = $message;
        $this->filePath = $filePath;
    }
}
