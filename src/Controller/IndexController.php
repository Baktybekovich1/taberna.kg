<?php

namespace App\Controller;

use App\Entity\Admins;
use App\Entity\Products;
use App\Repository\AdminsRepository;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        //

    }

   #[Route("/", name: "index_index")]
    public function index(RequestStack $requestStack, ProductsRepository $productsRepository, EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
//        $product = $productsRepository->findWithSql(2);
//        dd($productsRepository->find());
        $query = $productsRepository->createQueryBuilder('p')
            ->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );
        $session = $this->requestStack->getSession();
        if (null !== $session->get('cart')) {
            $count = count($session->get('cart'));
        } else {
            $count = 0;
        }

        return $this->render('index/index.html.twig', ['pagination' => $pagination, 'count' => $count]);
    }


    #[Route("add", name: "products_add")]
    public function products_add(Request $request, ProductsRepository $productsRepository): Response
    {
        $session = $this->requestStack->getSession();
        $sssss = $session->get('status');
        if (isset($sssss)) {
            if ($request->getMethod() == Request::METHOD_POST) {
                /** @var UploadedFile $file */
                $file = $request->files->get('file');
                if ($file->getClientOriginalName() != null) {
                    $file->move('uploads', $file->getClientOriginalName());
                    $product = new Products();
                    $product->setName($request->request->get('name'));
                    $product->setDate(date('Y-m-d  H:i:s'));
                    $product->setMaker($request->request->get('maker'));
                    $product->setType($request->request->get('type'));
                    $product->setComment($request->request->get('text'));
                    $product->setPrice($request->request->get('price'));
                    $product->setImg($file->getClientOriginalName());
                    $productsRepository->add($product, true);
                    return $this->redirect('/');
                }

            }
        } else {
            return $this->redirect('/auto');
        }
        return $this->render('index/add.html.twig');
    }

    #[Route("/more/{id}", name: "product_more")]
    public function product_more(ProductsRepository $productsRepository, Request $request): Response
    {
        $product = $productsRepository->find($request->get('id'));
        return $this->render('index/detailed.html.twig', ['product' => $product]);
    }

    #[Route("/reg",name: "registration")]
    public function registration(Request $request, AdminsRepository $adminsRepository): Response
    {
        if ($request->getMethod() == Request::METHOD_POST) {

            $admin = new Admins();
            $admin->setEmail($request->request->get('email'));
            $admin->setPassword($request->request->get('password'));
            $adminsRepository->add($admin, true);
        }
        return $this->render("index/registration.html.twig");
    }

    #[Route("/auto",name: "authorisation")]
    public function authorisation(Request $request, AdminsRepository $adminsRepository): Response
    {
        if ($request->getMethod() == Request::METHOD_POST) {
            $admins = $adminsRepository->findAll();
            foreach ($admins as $admin) {
                if ($request->request->get('email') == $admin->getEmail() && $request->request->get('password') == $admin->getPassword()) {
                    $session = $this->requestStack->getSession();
                    $session->set('status', 'admin');
                    return $this->redirect('/add');
                }
            }

        }
        return $this->render('index/authorisation.html.twig');
    }

    #[Route("/cart",name: "cart")]
    public function cart(ProductsRepository $productsRepository): Response
    {
        $session = $this->requestStack->getSession();
        if (null !== $session->get('cart')) {
            $count = count($session->get('cart'));
        } else {
            $count = 0;
        }

        $ids = $session->get('cart');
        $products = $productsRepository->findBy(['id' => $ids]);
        $price = 0;
        foreach ($products as $product) {
            $price += $product->getPrice();
        }
        return $this->render('index/cart.html.twig', ['products' => $products, 'price' => $price, 'count' => $count]);
    }

    #[Route("cart_add/{id}",name: "cart_add")]
    public function cart_add(Request $request): Response
    {
        $session = $this->requestStack->getSession();
        $a = [];
        $ss = $session->get('cart');
        if (isset($ss)) {
            $a = $ss;
        }
        $a[] = $request->get('id');
        $session->set('cart', $a);
        return $this->redirect('/');
    }
}