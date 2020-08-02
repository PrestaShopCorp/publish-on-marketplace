# Publish on Marketplace

![PHP tests](https://github.com/PrestaShopCorp/publish-on-marketplace/workflows/PHP%20tests/badge.svg)

This tool provide an way to upload new versions of extensions on the Marketplace, by using the seller API.

## Quickstart

### Installation

It may be installed separately from a project, by using the parameter `global`  of a project.

```bash
$ composer global require prestashop/publish-on-marketplace
```

### Usage

It requires the following parameters to make a successful upload:

```
Usage:
  prestashop:marketplace:publish [options]

Options:
      --api-key[=API-KEY]            API Key of the marketplace (Optional if environment variable MARKETPLACE_API_KEY is set)
      --changelog=CHANGELOG          Content of the changelog of the version to upload
      --metadata-json=METADATA-JSON  Path to Json file containing details of product
      --archive=ARCHIVE              Path to the archive to upload
      --update-type=UPDATE-TYPE      Type of upgrade (Minor update / Major / new) [default: "updatemin"]
```

Note `--metadata-json` and `--archive` needs to be valid paths to your files.

The Json content will be sent as parameters to the Marketplace API. The [API signature can be found on Swagger](https://app.swaggerhub.com/apis/Addons/PushModules/1.0.0#/free). is an example of metadata Json file for the module [ps_checkout](https://github.com/PrestaShopCorp/ps_checkout):

```json
{
    "id_product" : "46347",
    "technical_name" : "ps_checkout",
    "display_name" : "PrestaShop Checkout",
    "channel" : "stable",
    "product_type" : "module",
    "compatible_from" : "1.6.1.0"
}
```

An example of command is:

```
php ../publish-on-marketplace/bin/publish-on-marketplace --api-key=SomeKey --archive=$PWD/ps_checkout-9.9.9.zip --metadata-json=$PWD/metadata.json --changelog="New test release for tool"
```

## Development

Install dependencies with composer. Two CI tools are configured for this project: php-cs-fixer and phpstan

```
composer install
php vendor/bin/php-cs-fixer fix --no-interaction --dry-run --diff
php phpstan analyse tests/phpstan/phpstan.neon
```
