CSVResponse
===========
![Active repository](http://www.repostatus.org/badges/latest/active.svg)
[![License](https://poser.pugx.org/issei-m/streamed-csv-response/license.svg)](https://packagist.org/packages/ilbee/csv-response)
[![SymfonyInsight](https://insight.symfony.com/projects/98a48652-16bb-4100-89bd-e4ef7579c429/mini.svg)](https://insight.symfony.com/projects/98a48652-16bb-4100-89bd-e4ef7579c429)
![Php Composer](https://github.com/ilbee/csv-response/actions/workflows/php.yml/badge.svg) 

Add a CSV export Response in your [Symfony] controller.

Installation
------------

Use [Composer] to install this package :

    composer require ilbee/csv-response 

How to use ?
------------

Just return a CSVResponse object in your Symfony Controller 
and you will be able to download a CSV file.

Here is a simple example : 
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

[Symfony]: https://symfony.com/
[Composer]: https://getcomposer.org
