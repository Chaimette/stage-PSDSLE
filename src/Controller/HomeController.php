<?php
namespace App\Controller;

use App\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function index()
    {
        return $this->render('home.html.twig', [
            'title' => 'Prendre Soin de Soi L\'Essentiel',
            'message' => 'L’art du bien-être et de l’harmonie.'
        ]);
    }
    public function contact() {
        $data = [
            'title' => 'Prendre Soin de Soi L\'Essentiel',
            'meta_description' => 'Contactez-nous pour plus d\'informations'
        ];
        
        return $this->render('prestations/contact.html.twig', $data);
    }
    
    public function rdv() {
        $data = [
            'title' => 'Prendre Soin de Soi L\'Essentiel',
            'meta_description' => 'Prenez rendez-vous en ligne'
        ];
        
       return $this->render('pages/rdv.html.twig', $data);
    }
}
