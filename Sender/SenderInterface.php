<?php

namespace SfCod\EmailEngineBundle\Sender;

use SfCod\EmailEngineBundle\Template\TemplateInterface;

/**
 * Sender interface
 *
 * Interface SenderInterface
 *
 * @package SfCod\EmailEngineBundle\Sender
 */
interface SenderInterface
{
    /**
     * Send email to receiver
     *
     * @param TemplateInterface $template
     * @param string $email
     *
     * @return bool
     */
    public function send(TemplateInterface $template, string $email): bool;
}
