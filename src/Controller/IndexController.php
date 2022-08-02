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

    /**
     * @Route("/", name="index_index")
     * @return Response
     */
    public function index(RequestStack $requestStack, ProductsRepository $productsRepository, EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $productsRepository->createQueryBuilder('p')
            ->getQuery();
//-----------------------------------------------------------------
        $session = $this->requestStack->getSession();
        $session->set('status', 'visit');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('index/index.html.twig', ['pagination' => $pagination]);
    }


    /**
     * @Route("/add", name="products_add")
     * @return Response
     */
    public function products_add(Request $request, ProductsRepository $productsRepository): Response
    {
        $session = $this->requestStack->getSession();
//        dd($session->get('status'));
        if ($session->get('status') != 'admin') {
            return $this->redirect('/auto');
        }
        if ($request->getMethod() == Request::METHOD_POST) {
            /** @var UploadedFile $file */
            $file = $request->files->get('file');
//            dump($file->move('uploads',$file->getClientOriginalName()));
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
        return $this->render('index/add.html.twig');
    }

    /**
     * @Route("/more/{id}",name="product_more")
     * @return Response
     */
    public function product_more(ProductsRepository $productsRepository, Request $request): Response
    {
        $product = $productsRepository->find($request->get('id'));
        return $this->render('index/detailed.html.twig', ['product' => $product]);
    }
    /**
     * @Route("/reg", name="registration")
     * @return Response
     */
    public function registration( Request $request,AdminsRepository $adminsRepository): Response {
        if ($request->getMethod()==Request::METHOD_POST) {

//            dd($request->request->get('password'),$request->request->get('email'));
            $admin = new Admins();
            $admin->setEmail($request->request->get('email'));
            $admin->setPassword($request->request->get('password'));
            $adminsRepository->add($admin,true);
        }
        return $this->render("index/registration.html.twig");
    }
    /**
     * @Route("/auto",name="authorisation")
     * @return Response
     */
    public function authorisation(Request $request,AdminsRepository $adminsRepository): Response {
        if ($request->getMethod()==Request::METHOD_POST) {
            $admins = $adminsRepository->findAll();
            foreach ($admins as $admin) {
//                dd($admin->getEmail(),$admin->getPassword(),$request->request->get('email'),$request->request->get('password'));
                if ($request->request->get('email') == $admin->getEmail() && $request->request->get('password') == $admin->getPassword()) {
                    $session = $this->requestStack->getSession();
                    $session->set('status','admin');
//                    dd($session->get('status'));
                    return $this->redirect('/add');
                }
            }

        }
        return $this->render('index/authorisation.html.twig');
    }
    /**
     * @Route("/cart",name="cart")
     * @return Response
     */
    public function cart(): Response {
        return $this->render('index/cart.html.twig');
    }
    /**
     * @Route("/cart_add/{id}", name="cart_add")
     * @return Response
     */
    public function cart_add(): Response {
        $session = $this->requestStack->getSession();
        return $this->redirect('/');
    }

}