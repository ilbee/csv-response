# CSVResponse
![Active repository](http://www.repostatus.org/badges/latest/active.svg)
[![License](https://poser.pugx.org/issei-m/streamed-csv-response/license.svg)](https://packagist.org/packages/ilbee/csv-response)
![PHP Composer](https://github.com/ilbee/csv-response/actions/workflows/php.yml/badge.svg)

Add a CSV export response in your [Symfony] controller.

## 📖 Table of Contents
1. [ℹ️ Prerequisites](#-prerequisites)
2. [⚙ Installation](#-installation)
3. [🛠️ How to Use](#-how-to-use)
4. [🔗 Useful Links](#-useful-links)
5. [🙏 Thanks](#-thanks)

## ℹ️ Prerequisites
- PHP >= 7.4
- Symfony >= 4.4

## ⚙ Installation
Use [Composer] to install this package:
```sh
composer require ilbee/csv-response
```

## 🛠️ How to use ?
Simply return a **CSVResponse** object in your *Symfony controller*, and you will be able to download a CSV file.

Here’s a simple example:
```php
<?php
// ./src/Controller/MyController.php
namespace App\Controller;

use Ilbee\CSVResponse\CSVResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MyController extends AbstractController
{
    /**
     * @Route("/download-csv", name="download_csv") 
     */
    public function downloadCsv(): CSVResponse
    {
        $data = [];
        $data[] = [
            'firstName' => 'Marcel',
            'lastName'  => 'TOTO',
        ];   
        $data[] = [
            'firstName' => 'Maurice',
            'lastName'  => 'TATA',
        ];
        
        return new CSVResponse($data);
    }
}
```

### Explanation
1. **CSVResponse**: This class generates an HTTP response that will trigger a CSV file download based on the provided data.
2. **Data Example**: You can replace the `$data` array with your own data, fetched from a database or other sources.

## 🔗 Useful Links
- [Symfony](https://symfony.com/) - Official Symfony Documentation
- [Composer](https://getcomposer.org) - PHP Dependency Manager

## 🙏 Thanks
Special thanks to [Paul Mitchum](https://github.com/paul-m) and [Dan Feder](https://github.com/dafeder) for their contributions!
