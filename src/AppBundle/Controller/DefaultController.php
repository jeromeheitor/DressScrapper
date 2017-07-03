<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    /**
     * @Route("{gender}/", requirements={"gender" = "femmes|hommes"}, defaults={"gender" = "femmes"}))
     */
    public function indexAction(Request $request, $gender = "femmes")
    {
        $this->container->get('dress.scrapp')->init($gender);
        $products = $this->container->get('dress.scrapp')->getProducts();

        return $this->render('Home/index.html.twig',
            array('products' => $products, 'gender' => $gender));
    }

    /**
     * @Route("/{gender}/update_database/", name="update_database")
     */
    public function updateDatabaseAction(Request $request, $gender)
    {
        $this->container->get('dress.scrapp')->init($gender);
        $this->container->get('dress.scrapp')->getProducts();
        $products = $this->container->get('dress.scrapp')->updateDatabase();

        return $this->render('Home/local_database.html.twig',
            array('products' => $products, 'gender' => $gender));
    }

    /**
     * @Route("/{gender}/export_csv/", name="export_csv")
     * @return RedirectResponse
     */
    public function exportCSVAction(Request $request, $gender)
    {
        $this->container->get('dress.scrapp')->init($gender);
        $data = $this->container->get('dress.scrapp')->getProducts();
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        file_put_contents(
            'data.csv',
            $serializer->encode($data, 'csv')
        );

        return $this->redirectToRoute('app_default_index', array('gender' => $gender));
    }

}
