<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Transporteur;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    private EntityManagerInterface $em;
    private UrlGeneratorInterface $generator;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator)
    {
        $this->em = $em;
        $this->generator = $generator;
    }

    #[Route('/order/create-session-stripe/{id}', name: 'payment_stripe',methods: ['POST'])]
    //il s'agit ici de l'id de la commande en cours qui nous sert aussi de référence
    public function stripeCheckout($id): RedirectResponse
    {
        $productStripe = [];
        $totalHT = 0;
        //recupére la commande en cours
      $order = $this->em->getRepository(Commande::class)->findOneBy(['id' => $id]);
     //dd($order);
     //si commande introuvable ou n'existe pas
     if(!$order){
        return $this->redirectToRoute('panier_index');
     }

     foreach ($order->getPaniers()->getValues() as $product) {
        //pour recup le nom du produit
        $productData = $this->em->getRepository(Produit::class)->findOneBy(['id' => $product->getPanPro()]);
        //dd($productData);
        //les info demandé par stripe
        $producStripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $product->getPanPrixUnite() * 100,
                'product_data' => [
                    'name' => $productData->getProNom()
                ]
                ],
                'quantity' => $product->getPanQuantite()
            ];

            $totalHT += $product->getPanPrixUnite() *  $product->getPanQuantite();
     }

     $producStripe[] = [
        'price_data' => [
            'currency' => 'eur',
            'unit_amount' => (round(($totalHT * 0.2),2)) * 100,
            'product_data' => [
                'name' => "TVA"
            ]
            ],
            'quantity' => 1,
        ];

     $transporteurData = $this->em->getRepository(Transporteur::class)->findOneBy(['id' => $order->getComTransporteur()]);
//dd($transporteurData);
     $producStripe[] = [
        'price_data' => [
            'currency' => 'eur',
            'unit_amount' => $transporteurData->getTraPrix() * 100,
            'product_data' => [
                'name' => $transporteurData->getTraNom()
            ]
            ],
            'quantity' => 1,
        ];
//dd($producStripe);
    Stripe::setApiKey('sk_test_51O4gGGGvIgBTzRRgBa14AvfL4wgEmJzvpnGifyiZaXno0TUPKp0QnTEkKu2BJnOEg18DCGlNN9skzyl8kmi4kvLV00uyd9aRzr');
    

//header('Content-Type: application/json');

//$YOUR_DOMAIN = 'http://localhost:4242';

$checkout_session = \Stripe\Checkout\Session::create([
    'customer_email' => $this->getUser()->getEmail(),
    'payment_method_types' => ['card'],
    'line_items' => [[
        $producStripe
    # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
    // 'price' => '{{PRICE_ID}}',
    // 'quantity' => 1,
    ]],
  'mode' => 'payment',
  'success_url' => $this->generator->generate('payment_success', [
    'id' => $order->getId()
  ],UrlGeneratorInterface::ABSOLUTE_URL),
  'cancel_url' => $this->generator->generate('payment_error', [
    'id' => $order->getId()
  ],UrlGeneratorInterface::ABSOLUTE_URL),
  //'success_url' => $YOUR_DOMAIN . '/success.html',
 // 'cancel_url' => $YOUR_DOMAIN . '/cancel.html',
]);

    // $order->setComStripeSessionId($checkout_session->id);
    // $this->em->flush();
    return new RedirectResponse($checkout_session->url);


    }

    #[Route('/order/success/{id}', name: 'payment_success')]
    public function StripeSuccess(EntityManagerInterface $em,$id): Response{
        $order = $this->em->getRepository(Commande::class)->findOneBy(['id' => $id]);
        $order->setComIsPaid(true);
        $order->setComFactureId($order->getId());
        $totalData = $this->em->getRepository(Commande::class)->totalPrixCom($id);
        $order->setComFactureTotalHt($totalData[0]["p_total"]);
        $order->setComFactureTva(($totalData[0]["p_total"] - $totalData[0]["p_fdp"]) * 0.2);
        $order->setComFactureTotalTtc((($totalData[0]["p_total"] - $totalData[0]["p_fdp"]) * 0.2) +$totalData[0]["p_total"]);
       // dd((($totalData[0]["p_total"] - $totalData[0]["p_fdp"]) * 0.2) +$totalData[0]["p_total"]);
       //on récupere le panier de la commande
       $panier = $this->em->getRepository(Panier::class)->findBy(['pan_com' => $id]);
       foreach($panier as $paniers){
        // on recupere les produits 
        $produit = $paniers->getPanPro();
        //on met a jour le stock du produit
        $newStock = $produit->getProStkphy() - $paniers->getPanQuantite();
        $produit->setProStkphy($newStock);
        $em->persist($produit);
        //dd($produit->getProStkphy());
       }
      

        $em->persist($order);
        $em->flush();
        //return $this->render('order/succes.html.twig');
        return $this->render('commande/success.html.twig');
    }

    #[Route('/order/error/{id}', name: 'payment_error')]
    public function StripeError($id): Response{
        //return $this->render('order/error.html.twig');
        return $this->render('commande/error.html.twig');
    }


}

