<?php

namespace App\Controller;

use App\Entity\Detail;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PanierController extends AbstractController
{
    /**
     * @Route("/panier", name="panier")
     */
    public function index(SessionInterface $session): Response
    {
        $panier = $session->get("panier");   // je récupère l'indice panier dans la session
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
        ]);
    }

    /**
     * @Route("/panier/ajouter-produit/{id}", name="panier_ajouter")
     */
    public function ajouter(Request $request, SessionInterface $session, Produit $produit)
    {
        $panier = $session->get("panier", []);  // le 2ème argument est la valeur retourné par 'get' s'il n'y a pas de panier dans la session
        $qte = $request->query->get("quantite", 1);
        // $qte = $qte > 0 ? $qte : 1;
        $produitPresent = false;
        foreach ($panier as $indice => $article) {
            if( $article["produit"]->getId() == $produit->getId() ){
                $panier[$indice]["quantite"] += $qte;
                if( $panier[$indice]["quantite"] <= 0 ){
                    return $this->redirectToRoute("panier_supprimer", [ "id" => $produit->getId() ]);
                }
                $produitPresent = true;
                break;  // break permet de sortir de la boucle (pour éviter de continuer à comparer les produits alors qu'ils seront forcément différents)
            }
        }
        if( !$produitPresent ){
            $panier[] = [ "produit" => $produit, "quantite" => $qte ];
        }
        $session->set("panier", $panier);   // j'ajoute en session un indice panier qui contient un array $panier qui est composé d'array pour chaque produit
        if( $qte > 0) {
            return $this->redirectToRoute("accueil");
        } else {
            return $this->redirectToRoute("panier");
        }
    }

    /**
     * @Route("/panier/supprimer-produit/{id}", name="panier_supprimer")
     */
    public function supprimer(SessionInterface $session, $id)
    {
        /* EXO : écrire la fonction 'supprimer' : le but est de supprimer une ligne du panier */
        $panier = $session->get("panier", []);
        foreach ($panier as $indice => $article) {
            if( $article["produit"]->getId() == $id ){
                unset($panier[$indice]);
                break;
            }
        }
        $session->set("panier", $panier);
        return $this->redirectToRoute("panier");
    }

    /**
     * @Route("/panier/vider", name="panier_vider")
     */
    public function vider(SessionInterface $session)
    {
        // Pour supprimer une valeur de la session, on utilise : remove
        $session->remove("panier");
        return $this->redirectToRoute("panier");
    }

    /**
     * @Route("/panier/valider", name="panier_valider")
     */
    public function valider(SessionInterface $session, EntityManagerInterface $em, ProduitRepository $pr)
    {
        $panier = $session->get("panier", []);
        $cmd = new Commande;
        $cmd->setClient( $this->getUser() );
        $cmd->setDate( new \DateTime() );
        $cmd->setEtat("en attente");
        $montant = 0;
        foreach($panier as $article) {
            $produit = $pr->find( $article["produit"]->getId() );
            $montant += $produit->getPrix() * $article["quantite"];
            
            $detail = new Detail();
            $detail->setCommande($cmd);
            $detail->setProduit($produit);
            $detail->setQuantite( $article["quantite"] );
            $detail->setPrix( $produit->getPrix() );
            $em->persist($detail);

            $produit->setStock( $produit->getStock() - $article["quantite"] );  //⚠ il faudrait vérifier s'il reste bien assez de stock avant de valider la commande
        }
        $cmd->setMontant( $montant );
        $em->persist( $cmd );
        $em->flush();
        $session->remove("panier");
        return $this->redirectToRoute("espace");
    }
}
