# Canonical Url

- This module generates the canonical.

- If the url is not rewrite, the canonical url contains the "$_GET" parameters

Example for : For url ```http://demo.thelia.net/?view=product&locale=en_US&product_id=18```
```html
<link rel="canonical" href="/?view=product&locale=en_US&product_id=18" />
```

- If you have "index.php" in your URL, it will be deleted
Example for : For url ```http://demo.thelia.net/index.php?view=product&locale=en_US&product_id=18```
```html
<link rel="canonical" href="/?view=product&locale=en_US&product_id=18" />
```
Example for : For url ```http://demo.thelia.net/index.php/en_en-your-path.html?page=44```
```html
<link rel="canonical" href="/en_en-your-path" />
```

- If the user comes from the bad domain, the canonical URL contains the good domain
Example for : For url ```http://demo458.thelia.net/index.php/en_en-your-path.html?page=44```
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

You just have to activate the module and check the metas of your website.