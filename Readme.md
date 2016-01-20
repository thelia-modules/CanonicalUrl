# Canonical Url

This module generates a canonical URL for every page of your shop. Once activated, you'll find a `<link rel="canonical" href="..." />` tag in the header of your pages.

## Examples

- If the page URL is not rewritten, the canonical URL contains the "$_GET" parameters. Example for : For URL ```http://demo.thelia.net/?view=product&locale=en_US&product_id=18```
    ```html
    <link rel="canonical" href="http://demo.thelia.net/?view=product&locale=en_US&product_id=18" />
    ```

- When the page URL contains the script name (index.php), it will be removed from the canonical URL. Example, the canonical URL of ```http://demo.thelia.net/index.php?view=product&locale=en_US&product_id=18``` is :
    ```html
    <link rel="canonical" href="http://demo.thelia.net/?view=product&locale=en_US&product_id=18" />
    ```
    
    When a rewritten URL contains parameters, these parameters a removed. For ```http://demo.thelia.net/index.php/en_en-your-path.html?page=44```, the canonical URL is :
    ```html
    <link rel="canonical" href="http://demo.thelia.net/en_en-your-path" />
    ```

- If the page URL contains a domain which is not the main shop domain, this domain is replaced by the main shop domain. For ```http://demo458.thelia.net/index.php/en_en-your-path.html?page=44``` the canonical URL is :
    ```html
    <link rel="canonical" href="http://demo.thelia.net/en_en-your-path" />
    ```

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is CanonicalUrl.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/canonical-url-module:~1.0.0
```

## Usage

You just have to activate the module and check the meta tags of your shop.