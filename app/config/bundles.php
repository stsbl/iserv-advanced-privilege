<?php

return [
    IServ\Bundle\Asset\IServAssetBundle::class => ['all' => true],
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    IServ\Bundle\Authentication\IServAuthenticationBundle::class => ['all' => true],
    IServ\Bundle\AdminIntegration\IServAdminIntegrationBundle::class => ['all' => true],
    IServ\Bundle\Module\IServModuleBundle::class => ['all' => true],
    IServ\Bundle\TranslationGettext\IServTranslationGettextBundle::class => ['all' => true],
    IServ\Bundle\Error\IServErrorBundle::class => ['all' => true],
    IServ\Bundle\Autocomplete\IServAutocompleteBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    IServ\BootstrapBundle\IServBootstrapBundle::class => ['all' => true],
    IServ\Bundle\TwigComponents\IServTwigComponentsBundle::class => ['all' => true],
    IServ\Bundle\Form\IServFormBundle::class => ['all' => true],
    IServ\Bundle\Config\IServConfigBundle::class => ['all' => true],
    IServ\Bundle\TestBrowser\IServTestBrowserBundle::class => ['test' => true],
];
