<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Model\PrestaModel;
use App\Model\AdminPrestaModel;


class PrestaController extends AbstractController
{

    private PrestaModel $prestationModel;


    public function __construct()
    {
        parent::__construct();
        $this->prestationModel = new PrestaModel($this->pdo);
    }

    public function index(): string
    {
          if (!empty($_GET['section'])) {
        return $this->show((string)$_GET['section']);
    }

        $catalog = $this->prestationModel->getCatalog();

        return $this->render('prestations/index.html.twig', [
            'title'   => 'Toutes les prestations',
            'sections' => $catalog,
        ]);
    }

    public function show(string $slug): string
    {
        if (!$this->prestationModel->sectionExists($slug)) {
            http_response_code(404);
            return $this->render('errors/404.html.twig', ['message' => 'Section non trouvée']);
        }

        $section = $this->prestationModel->getSectionBySlug($slug);
        $prestations = $this->prestationModel->getPrestationsBySection($section['id']);

        return $this->render('prestations/detail.html.twig', [
            'title'            => $section['nom'],
            'meta_description' => $section['meta_description'] ?? '',
            'section'          => $section,
            'prestations'      => $prestations,
        ]);
    }

   

}
