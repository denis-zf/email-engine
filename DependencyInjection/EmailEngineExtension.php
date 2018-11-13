<?php

namespace SfCod\EmailEngineBundle\DependencyInjection;

use Psr\Log\LoggerInterface;
use SfCod\EmailEngineBundle\Mailer\Mailer;
use SfCod\EmailEngineBundle\Mailer\TemplateManager;
use SfCod\EmailEngineBundle\Template\ParametersAwareInterface;
use SfCod\EmailEngineBundle\Template\Params\ParameterResolver;
use SfCod\EmailEngineBundle\Template\Params\ParameterResolverInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EmailEngineExtension
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\EmailEngineBundle\DependencyInjection
 */
class EmailEngineExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new EmailEngineConfiguration();

        $config = $this->processConfiguration($configuration, $configs);

        $this->createSenders($config, $container);
        $this->createTemplates($config, $container);
        $this->createResolver($config, $container);
    }

    /**
     * Get extension alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'sfcod_email_engine';
    }

    /**
     * Create parameter resolver
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createResolver(array $config, ContainerBuilder $container)
    {
        $resolver = new Definition(ParameterResolverInterface::class);
        $resolver->setPublic(true);
        $resolver->setClass(ParameterResolver::class);
        $resolver->setArguments([
            new Reference(ContainerInterface::class),
        ]);

        $container->setDefinition(ParameterResolverInterface::class, $resolver);
    }

    /**
     * Create senders
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createSenders(array $config, ContainerBuilder $container)
    {
        $senders = [];
        $mainSender = $this->getSender($config['main_sender'], $config);

        if (isset($mainSender['chain'])) {
            foreach ($mainSender['chain']['senders'] as $sender) {
                $senders[$sender] = $this->getSender($sender, $config);
            }
        } else {
            $senders[$config['main_sender']] = $mainSender;
        }

        foreach ($senders as $name => $config) {
            if (false === isset($config['sender'], $config['repository']) ||
                false === isset($config['sender']['class'], $config['repository']['class'])) {
                throw  new InvalidConfigurationException(sprintf('"sender" and "repository" must be defined in "%s" sender.', $name));
            }

            $sender = new Definition($config['sender']['class']);
            $sender
                ->setPublic(true)
                ->setAutoconfigured(true)
                ->setAutowired(true);

            $repository = new Definition($config['repository']['class']);
            $repository
                ->setPublic(true)
                ->setAutoconfigured(true)
                ->setAutowired(true);

            $container->addDefinitions([
                $config['repository']['class'] => $repository,
                $config['sender']['class'] => $sender,
            ]);
        }

        $mailer = new Definition(Mailer::class);
        $mailer
            ->setPublic(true)
            ->setArguments([
                new Reference(ContainerInterface::class),
                new Reference(LoggerInterface::class),
            ])
            ->addMethodCall('setLogger', [new Reference(LoggerInterface::class)])
            ->addMethodCall('setSenders', [$senders]);

        $container->setDefinition(Mailer::class, $mailer);
    }

    /**
     * Get sender from senders config
     *
     * @param string $sender
     * @param array $config
     *
     * @return array
     */
    private function getSender(string $sender, array $config): array
    {
        if (false === isset($config['senders'][$sender])) {
            throw new InvalidConfigurationException(sprintf('Main sender "%s" does not exist in senders "%s".', $sender, json_encode(array_keys($config['senders']))));
        }

        return $config['senders'][$sender];
    }

    /**
     * Create templates
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createTemplates(array $config, ContainerBuilder $container)
    {
        foreach ($config['templates'] as $template) {
            if (in_array(ParametersAwareInterface::class, class_implements($template))) {
                /** @var ParametersAwareInterface $template */
                foreach ($template::listParameters() as $parameter) {
                    $definition = new Definition($parameter);
                    $definition
                        ->setPublic(true)
                        ->setAutowired(true)
                        ->setAutoconfigured(true)
                        ->addTag(sprintf('%s.parameter', $template));

                    $container->setDefinition($parameter, $definition);
                }
            }
        }

        $templateManager = new Definition(TemplateManager::class);
        $templateManager
            ->setPublic(true)
            ->addArgument($config['templates']);

        $container->setDefinition(TemplateManager::class, $templateManager);
    }
}
