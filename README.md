<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Coliship Chronopost Export Plugin</h1>

<p align="center">Create Chronopost shipping labels in Sylius .</p>
<p align="center">/!\ Currently in alpha /!\</p>

## Quickstart

Install & configure [BitBagCommerce / SyliusShippingExportPlugin](https://github.com/BitBagCommerce/SyliusShippingExportPlugin). <br> 
Install & configure [Setono / SyliusPickupPointPlugin](https://github.com/Setono/SyliusPickupPointPlugin).


```
$ composer require ikuzostudio/chronopost-plugin
```

Add plugin dependencies to your `config/bundles.php` file:

```php
return [
  // ...
  Ikuzo\SyliusChronopostPlugin\IkuzoSyliusChronopostPlugin::class => ['all' => true],
];
```

Then configure your new Chronopost gateway 