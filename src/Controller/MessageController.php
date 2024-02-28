<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Message;
use App\Entity\SubCategory;
use App\Form\MessageType;
use App\Form\SearchType;
use App\Model\SearchData;
use App\Repository\CategoryRepository;
use App\Repository\MessageRepository;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class MessageController extends AbstractController
{
    #[Route('/message', name: 'app_message')]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        MessageRepository $messageRepository,
        CategoryRepository $categoryRepository

    ): Response
    {

        if($request->query->get('id')) {
            $message = $entityManager->getRepository(Message::class)->find($request->query->get('id'));

        } else {
            $message = new Message();
        }

        $categories = $categoryRepository->findAll();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($message);
            $entityManager->flush();
            return $this->redirectToRoute('app_message');
        }
        $message = $messageRepository->findAll();

        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
            'form' => $form->createView(),
            'messages' => $message,
            'categories' => $categories
        ]);
    }








    #[Route('/message/{category}/{subcategory}/', name: 'app__message_sub_category')]
    public function subcategory(
        EntityManagerInterface $entityManager,
        Request $request,
        MessageRepository $messageRepository,
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        SluggerInterface $slugger

    ): Response {

//dd($request->query->get('id'));
        if($request->query->get('id')) {
            $message = $entityManager->getRepository(Message::class)->find($request->query->get('id'));

        } else {
            $message = new Message();
        }

        $url = $_SERVER['PHP_SELF'];
        $values = parse_url($url);
        $args = explode('message/',$values['path']);
//        $args = str_replace( array('/'), '', $args);

        $string = $args[1];
        $category_args = explode('/', $string);
        $result = $args[1];

//        dd($category_args[1]);
        $categories = $categoryRepository->findAll();
        $user = $this->getUser();
        $message->setPseudo($user->getPseudo());
        $message->setCategory($category_args[0]);
        $message->setSubCategory($category_args[1]);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){


            $file = $form->get('file')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $message->setFile($newFilename);
                $url = $newFilename;
                $values = parse_url($url);

                $args = explode('.',$values['path']);

            }

            if($request->query->get('response_id')) {
                $message->setResponse_id($request->query->get('response_id'));
                $message_de_base = $messageRepository->findBy(array('id' => $request->query->get('response_id')));
                for ($i = 0; $i < count($message_de_base); $i++)
                {
                    $m = $message_de_base[$i];
                    $message->setPseudoParent($m->getPseudo());
                    $message->setContentParent($m->getContent());
                }

//                dd($message_de_base);
//

            }else{
                $message->setResponse_id(0);
            }


            $entityManager->persist($message);
            $entityManager->flush();
            return $this->redirectToRoute('app__message_sub_category',
                [
                    'category' => $message->getCategory(),
                    'subcategory' => $message->getSubCategory(),
                    'ext' => $category_args[0]
                ]);
        }

        $category = $category_args[0];
        $subCategory = $category_args[1];
        $server_name = $category_args[0];
        $channel_name = $category_args[1];
//        dd($subCategory);

        $message = $messageRepository->messagesBySubCategories($category, $subCategory);
        $response = $messageRepository->messagesByResponses($category, $request->query->get('response_id'));
        $recup = $messageRepository->recup($request->query->get('response_id'));


        /*----------------*/
//        dd($category2);
//        $categoryToFind = $categoryRepository->findBy(['id' => 7]);
////        dd($categoryToFind);
////        $subCategory = $subCategoryRepository->findBy(['category_id' => 7]);


//        $categoryId = $category2->getId();

//        $subCategories = $entityManager->getRepository(SubCategory::class)->findBy(['category_id' => $categoryId]);
        $category2 = $categoryRepository->findOneBy(['name' => $category]);

        $subCategories = $subCategoryRepository->findBy(['category' => $category2->getId()]);

        $subCategoryName = [];

        foreach ($subCategories as $subCategory) {
            $subCategoryName[] = $subCategory;
        }
//        dd($subCategoryName);


//        $subCategory = [];
//        $subCategoryName = 'bep';

        /*----------------*/

        $searchData = new SearchData();
        $searchForm = $this->createForm(SearchType::class, $searchData);
        $searchForm->handleRequest($request);

        $subCategory = $category_args[1];
        if ($searchForm->isSubmitted() && $searchForm->isValid()){

            $messages = $messageRepository->findBySearch($searchData, $category, $subCategory);
//            dd($messages);

            return $this->render('message/show.html.twig', [
                'controller_name' => 'MessageController',
                'messages' => $messages,
                'response' => $response,
                'recup' => $recup,
                'categories' => $categories,
                'subCategories' => $subCategory,
                'subCategoryName' => $subCategoryName,
                'form' => $form->createView(),
                'searchForm' => $searchForm->createView(),
                'server_name' => $server_name,
                'channel_name' => $channel_name,
            ]);
        }

        return $this->render('message/show.html.twig', [
            'controller_name' => 'MessageController',
            'messages' => $message,
            'response' => $response,
            'recup' => $recup,
            'categories' => $categories,
            'subCategories' => $subCategory,
            'subCategoryName' => $subCategoryName,
            'form' => $form->createView(),
            'searchForm' => $searchForm->createView(),
            'server_name' => $server_name,
            'channel_name' => $channel_name,
        ]);

    }

    #[Route('/message/{category}/{subcategory}/', name: 'app_response_message_category')]
    public function response(
        EntityManagerInterface $entityManager,
        Request $request,
        MessageRepository $messageRepository,
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        SluggerInterface $slugger

    ): Response {



    }

    #[Route('/message/{category}/general/', name: 'app_message_category')]
    public function category(
        EntityManagerInterface $entityManager,
        Request $request,
        MessageRepository $messageRepository,
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        SluggerInterface $slugger

    ): Response {





        if($request->query->get('id')) {
            $message = $entityManager->getRepository(Message::class)->find($request->query->get('id'));

        } else {
            $message = new Message();
        }

        $url = $_SERVER['PHP_SELF'];
        $values = parse_url($url);
        $args = explode('message/',$values['path']);
        $args = str_replace( array('/'), '', $args);



        $categories = $categoryRepository->findAll();
        $user = $this->getUser();
        $message->setPseudo($user->getPseudo());
        $message->setCategory($args[1]);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){


            $file = $form->get('file')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $message->setFile($newFilename);
                $url = $newFilename;
                $values = parse_url($url);

                $args = explode('.',$values['path']);

            }

            if($request->query->get('response_id')) {
                $message->setResponse_id($request->query->get('response_id'));
                $message_de_base = $messageRepository->findBy(array('id' => $request->query->get('response_id')));
                for ($i = 0; $i < count($message_de_base); $i++)
                {
                    $m = $message_de_base[$i];
                    $message->setPseudoParent($m->getPseudo());
                    $message->setContentParent($m->getContent());
                }

//                dd($message_de_base);
//

            }else{
                $message->setResponse_id(0);
            }


            $entityManager->persist($message);
            $entityManager->flush();
            return $this->redirectToRoute('app_message_category',
                [
                    'category' => $message->getCategory(),
                    'ext' => $args[1]
                ]);
        }

        $category = $args[1];

        $message = $messageRepository->messagesByCategories($category);
        $response = $messageRepository->messagesByResponses($category, $request->query->get('response_id'));
        $recup = $messageRepository->recup($request->query->get('response_id'));


        /*----------------*/

        //cat controller
//        $category2 = $categoryRepository->findOneBy(['name' => $category]);
////        dd($category2->getId());
//        $categoryID = $category2->getId();
//        $categoryToFind = $categoryRepository->findBy(['id' => 7]);
////        dd($categoryToFind);
////        $subCategory = $subCategoryRepository->findBy(['category_id' => 7]);
////        $subCategories = $this->entityManager->getRepository(SubCategory::class)->findBy(['category_id' => $categoryId]);
//
//        $subCategories = $subCategoryRepository->findBy(['category' => $category2->getId()]);
//        $subCategoryName = [];
//
//        foreach ($subCategories as $subCategory) {
//            $subCategoryName[] = $subCategory;
//        }
//        dd($subCategoryName);

        $category2 = $categoryRepository->findOneBy(['name' => $category]);
//        dd($category2);
        $subCategories = $subCategoryRepository->findBy(['category' => $category2->getId()]);

        $subCategoryName = [];

        foreach ($subCategories as $subCategory) {
            $subCategoryName[] = $subCategory;
        }

        $subCategory = [];
        $subCategoryName = 'bep';


        /*----------------*/

        $searchData = new SearchData();
        $searchForm = $this->createForm(SearchType::class, $searchData);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()){

            $messages = $messageRepository->findBySearch($searchData);
            return $this->render('message/show.html.twig', [
                'controller_name' => 'MessageController',
                'messages' => $messages,
                'response' => $response,
                'recup' => $recup,
                'categories' => $categories,
                'subCategories' => $subCategory,
                'subCategoryName' => $subCategoryName,
                'form' => $form->createView(),
                'searchForm' => $searchForm->createView(),
            ]);
        }

        return $this->render('message/show.html.twig', [
            'controller_name' => 'MessageController',
            'messages' => $message,
            'response' => $response,
            'recup' => $recup,
            'categories' => $categories,
            'subCategories' => $subCategory,
            'subCategoryName' => $subCategoryName,
            'form' => $form->createView(),
            'searchForm' => $searchForm->createView(),
        ]);

    }

    #[Route('/message/delete/{id}/{category}/{subcategory}', name: 'app_message_delete')]
    public function delete(
        Message $message,
        MessageRepository $messageRepository,
        Request $request,

    ): Response {
        $messageRepository->remove($message, true);
        return $this->redirectToRoute('app__message_sub_category',
            [
                'category' => $message->getCategory(),
                'subcategory' => $message->getSubCategory(),
                'id' => $request->query->get('index')
            ]);
    }

}

