<?php
namespace AppBundle\Services;

use AppBundle\Entity\Product;
use Doctrine\ORM\EntityManager;
use Goutte\Client;
use Psr\Log\LoggerInterface;

class DressScrapp
{
    private $em;
    private $logger;
    private $client;
    private $crawler;
    private $products;
    private $productRepository;
    private $url;
    private $currentUrl;
    private $nbrArticle;
    private $pages;

    public function __construct(EntityManager $manager, LoggerInterface $logger)
    {
        $this->em = $manager;
        $this->logger = $logger;
        $this->productRepository = $manager->getRepository('AppBundle:Product');
        $this->client = new Client();
        $this->nbrArticle = 0;
    }

    public function init($gender)
    {
        $this->url = 'http://www.adidas.fr/chaussures-' . $gender;
        $this->pages[] =  $this->url;
        $this->crawler = $this->client->request('GET', $this->url);
        for ($i=0;$i < 4;$i++) {
            $this->pages[] = $this->getNextUrl();
        }
    }

    public function getProducts()
    {
        foreach ($this->pages as $url) {
            $this->crawler = $this->client->request('GET', $url);
            $productIds = $this->crawler->filterXPath('//span[contains(@id, "salesprice-")]')
                ->evaluate('substring-after(@id, "-")');
            $this->currentUrl = $url;
            $this->getAdidasProducts($productIds);
        }
        return $this->products;
    }

    public function getAdidasProducts($productIds)
    {
        foreach ($productIds as $id) {
            $product = new Product();
            $product->setProductId($id);
            $price = $this->getPricing("salesprice", $id);
            $basePrice = $this->getPricing("baseprice", $id);
            if (!$basePrice)
                $basePrice = $price;
            $product->setPrice($price);
            $product->setBasePrice($basePrice);
            $product->setDiscount($this->getDiscount($price, $basePrice));
            $dataTrack = explode('_', $id)[0];
            $name = $this->crawler->filter('a[data-track="' . $dataTrack . '"] > span')->text();
            if (!$name) {
                $this->logger->error("Error Scrapping Name's Product Product for ID: \"$id\" at \"$this->url");
            }
            else
            {
                $product->setName($name);
                $this->products[] = $product;
            }
        }
        usort($this->products, function( $a, $b ){
            return strcmp($a->getProductId(), $b->getProductId());
        });
    }

    public function updateDatabase()
    {
        foreach ($this->products as $product)
        {
            if (!$this->productRepository->findOneByProductId($product->getProductId()))
            {
                $this->em->persist($product);
            }
        }
        $this->em->flush();
        return $this->productRepository->findAll();
    }

    public function getDiscount($price, $basePrice)
    {
        return intval((($price / $basePrice) * 100) - 100);
    }

    public function getNextUrl()
    {
        $node = $this->crawler->filterXPath('//span[contains(@id, "size_" )]');
        if ($node->count() > 0) {
            $nbrArticleStr = preg_replace("/[^0-9]/", "", $node->text());
            $nbrArticle = intval($nbrArticleStr);
            $start = $this->nbrArticle + $nbrArticle;
            $nextUrl = $this->url . "?start=" . $start;
            $this->nbrArticle = $start;
        }
        else
            $nextUrl = null;
        return $nextUrl;
    }

    public function getPricing($priceType, $id)
    {
        $price = 0;
        $node = $this->crawler->filterXPath('//span[contains(@id, "' . $priceType . '-' .$id.'" )]');
        
        if ($node->count() > 0)
        {
            $priceStr = preg_replace("/[^0-9,.]/","", $node->text());
            $price = floatval(strtr($priceStr, ',', '.'));
            if ($price == 0)
                $this->logger->error("Error Scrapping Price's Product for ID: \"$id\" at \"$this->url");
        }
        else if ($node->count() > 0 && strcmp($priceType, "salesprice"))
        {
            $this->logger->error("Error Scrapping $priceType 's Product for ID: \"$id\" at \"$this->url");
        }
        else if ($node->count() > 0 && strcmp($priceType, "baseprice"))
        {
            $this->logger->error("Error Scrapping $priceType 's Product for ID: \"$id\" at \"$this->url");
            return false;
        }
        return $price;
    }
}