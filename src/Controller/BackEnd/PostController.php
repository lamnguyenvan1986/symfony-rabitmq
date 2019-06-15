<?php

namespace App\Controller\BackEnd;

use App\Entity\Category;
use App\Entity\Post;
use App\Form\Type\CategoryType;
use App\Form\Type\PostType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/posts")
 */
class PostController extends AbstractController
{
    /**
     * @Route("", name="post_index", methods={"GET"})
     *
     * @param Request $request
     * @param PostRepository $repository
     * @return Response
     */
    public function index(Request $request,PostRepository $repository): Response
    {
        $criteria = [];
        $page = $request->get('page', 1);
        $size = $request->get('size', 2);
        $sort = $request->get('sort', ['name' => 'asc']);
        $pager = $repository->search($criteria, $sort)->setMaxPerPage($size)->setCurrentPage($page);

        return $this->render('backend/post/index.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * @Route("/create", name="post_create", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Create category successfully.');
            if ($url = $request->get('redirect_url')) {
                return $this->redirect($url);
            }

            return $this->redirectToRoute('post_create');
        }

        return $this->render('backend/post/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id<\d+>}", name="post_edit", methods={"GET", "PUT"})
     *
     * @param Request $request
     * @param Post $post
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Edit post successfully.');

            if ($url = $request->get('redirect_url')) {
                return $this->redirect($url);
            }
        }

        return $this->render('backend/post/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id<\d+>}", name="post_delete", methods={"DELETE"})
     *
     * @param Post $post
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     */
    public function delete(Post $post, EntityManagerInterface $entityManager): Response
    {
        $post->setDeleted(true);
        $entityManager->flush();
        $this->addFlash('success', 'Delete post successfully.');

        return $this->redirectToRoute('post_index');
    }

}
