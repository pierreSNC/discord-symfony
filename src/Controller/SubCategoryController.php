<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Form\SubCategoryFormType;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubCategoryController extends AbstractController
{
    #[Route('/sub_category', name: 'app_sub_category')]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        SubCategoryRepository $subCategoryRepository,
    ): Response
    {

        $subCategory = new SubCategory();
        $form = $this->createForm(SubCategoryFormType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $entityManager->persist($subCategory);
            $entityManager->flush();
            return $this->redirectToRoute('app_sub_category');
        }
        $subCategory = $subCategoryRepository->findAll();
        return $this->render('sub_category/index.html.twig', [
            'controller_name' => 'SubCategoryController',
            'subCategories' => $subCategory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sub_category/delete/{id}/', name: 'app_sub_category_delete')]
    public function delete(
        SubCategory $subCategory,
        SubCategoryRepository $subCategoryRepository,
        Request $request,

    ): Response {
        $subCategoryRepository->remove($subCategory, true);
        return $this->redirectToRoute('app_sub_category');

    }
}
