<?php

namespace App\Controller;

use App\Entity\Commande;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class EspaceController extends AbstractController
{
    /**
     * @Route("/espace-client", name="espace")
     */
    public function index(): Response
    {
        return $this->render('espace/index.html.twig');
    }

    /**
     * @Route("/espace/commande/{id}/detail", name="espace_detail_commande")
     */
    public function detail(Commande $commande): Response
    {
        return $this->render('espace/detail_commande.html.twig', compact("commande"));
    }


}
