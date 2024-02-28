<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\SubCategory;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        CategoryRepository $categoryRepository,
        SluggerInterface $slugger
    ): Response
    {

        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $image = $form->get('image')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $image->move(
                        $this->getParameter('channels_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $category->setImage($newFilename);
                $url = $newFilename;
                $values = parse_url($url);

                $args = explode('.',$values['path']);

            }


//            dd($category->getName());

            $category->setName($category->getName());
//            dd($categoryLinkToSubCategory);
            $general = new SubCategory();
            $general->setName('general');
            $general->setCategory($category);
            $entityManager->persist($category);

            $entityManager->persist($general);
            $entityManager->flush();
            return $this->redirectToRoute('app_category');
        }
        $category = $categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
            'categories' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/delete/{id}/', name: 'app_category_delete')]
    public function delete(
        Category $category,
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        Request $request,

    ): Response {
        foreach ($category->getSubCategories() as $subCategories ){
            $subCategoryRepository->remove($subCategories, true);
        }
        $categoryRepository->remove($category, true);
        return $this->redirectToRoute('app_category');

    }
}
