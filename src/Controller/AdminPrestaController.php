<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Model\AdminPrestaModel;
use App\Model\PrestaModel;


class AdminPrestaController extends AbstractController
{
    private AdminPrestaModel $adminPrestationModel;
    private PrestaModel $prestationModel;

    public function __construct()
    {
        parent::__construct();
        $this->adminPrestationModel = new AdminPrestaModel($this->pdo);
        $this->prestationModel = new PrestaModel($this->pdo);
    }
//TODO : SORT THROUGH CRUD FUNCTIONS AND CLEAN UP
    // Liste
    public function adminSectionsList(): string
    {

        $this->requireAdmin();

        $sections = $this->prestationModel->listSections();
        return $this->render('admin/sections/list.html.twig', [
            'title'    => 'Gestion des sections',
            'sections' => $sections,
        ]);
    }

    // Créer
    public function adminSectionCreate(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'nom'              => trim($_POST['nom'] ?? ''),
                'slug'             => trim($_POST['slug'] ?? ''),
                'description'      => trim($_POST['description'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'ordre_affichage'  => (int)($_POST['ordre_affichage'] ?? 0),
                'actif'            => isset($_POST['actif']) ? 1 : 1, // PAR DEFAUT ACTIF
            ];
            if ($this->adminPrestationModel->createSection($data)) {
                header('Location: /admin/sections');
                exit;
            }
        }

        return $this->render('admin/sections/create.html.twig', [
            'title' => 'Créer une section',
        ]);
    }

    // Editer
    public function adminSectionEdit(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /admin/sections');
            exit;
        }

        $section = $this->adminPrestationModel->findSection($id);
        if (!$section) {
            http_response_code(404);
            return $this->render('errors/404.html.twig', ['message' => 'Section non trouvée']);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'nom'              => trim($_POST['nom'] ?? ''),
                'slug'             => trim($_POST['slug'] ?? ''),
                'description'      => trim($_POST['description'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'ordre_affichage'  => (int)($_POST['ordre_affichage'] ?? 0),
                'actif'            => isset($_POST['actif']) ? 1 : 0,
            ];
            if ($this->adminPrestationModel->updateSection($id, $data)) {
                header('Location: /admin/sections');
                exit;
            }
        }

        return $this->render('admin/sections/edit.html.twig', [
            'title'   => 'Modifier la section',
            'section' => $section,
        ]);
    }

    // Supprimer 
    public function adminSectionDelete(): void
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $this->adminPrestationModel->deleteSection($id);
            }
        }
        header('Location: /admin/sections');
        exit;
    }

    // Liste (+ filtre section_id optionnel ?section_id=..)
    public function adminPrestationsList(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');
        $sectionId  = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;
        $prestations = $this->adminPrestationModel->listPrestations($sectionId);
        $sections    = $this->prestationModel->getAllSections();

        return $this->render('admin/prestations/list.html.twig', [
            'title'              => 'Gestion des prestations',
            'prestations'        => $prestations,
            'sections'           => $sections,
            'current_section_id' => $sectionId,
        ]);
    }

    // Créer
    public function adminPrestationCreate(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');
        $sections = $this->prestationModel->getAllSections();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'section_id'      => (int)($_POST['section_id'] ?? 0),
                'nom'             => trim($_POST['nom'] ?? ''),
                'description'     => trim($_POST['description'] ?? ''),
                'ordre_affichage' => (int)($_POST['ordre_affichage'] ?? 0),
            ];
            if ($this->adminPrestationModel->createPrestation($data)) {
                header('Location: /admin/prestations');
                exit;
            }
        }

        return $this->render('admin/prestations/create.html.twig', [
            'title'    => 'Créer une prestation',
            'sections' => $sections,
        ]);
    }

    // Editer
    public function adminPrestationEdit(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /admin/prestations');
            exit;
        }

        $prestation = $this->adminPrestationModel->findPrestation($id);
        if (!$prestation) {
            http_response_code(404);
            return $this->render('errors/404.html.twig', ['message' => 'Prestation non trouvée']);
        }

        $sections = $this->prestationModel->getAllSections();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'section_id'      => (int)($_POST['section_id'] ?? 0),
                'nom'             => trim($_POST['nom'] ?? ''),
                'description'     => trim($_POST['description'] ?? ''),
                'ordre_affichage' => (int)($_POST['ordre_affichage'] ?? 0),
                'actif'           => isset($_POST['actif']) ? 1 : 0,
            ];
            if ($this->adminPrestationModel->updatePrestation($id, $data)) {
                header('Location: /admin/prestations');
                exit;
            }
        }

        return $this->render('admin/prestations/edit.html.twig', [
            'title'      => 'Modifier la prestation',
            'prestation' => $prestation,
            'sections'   => $sections,
        ]);
    }

    // Supprimer
    public function adminPrestationDelete(): void
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $this->adminPrestationModel->deletePrestation($id);
            }
        }
        header('Location: /admin/prestations');
        exit;
    }

    // Liste tarifs d’une prestation (?prestation_id=..)
    public function adminTarifsList(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');
        $prestationId = (int)($_GET['prestation_id'] ?? 0);
        if (!$prestationId) {
            header('Location: /admin/prestations');
            exit;
        }

        $prestation = $this->adminPrestationModel->findPrestation($prestationId);
        if (!$prestation) {
            http_response_code(404);
            return $this->render('errors/404.html.twig', ['message' => 'Prestation non trouvée']);
        }

        $tarifs = $this->adminPrestationModel->listTarifs($prestationId);

        return $this->render('admin/tarifs/list.html.twig', [
            'title'      => 'Gestion des tarifs',
            'prestation' => $prestation,
            'tarifs'     => $tarifs,
        ]);
    }

    // Créer tarif (?prestation_id=..)
    public function adminTarifCreate(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');
        $prestationId = (int)($_GET['prestation_id'] ?? 0);
        if (!$prestationId) {
            header('Location: /admin/prestations');
            exit;
        }

        $prestation = $this->adminPrestationModel->findPrestation($prestationId);
        if (!$prestation) {
            http_response_code(404);
            return $this->render('errors/404.html.twig', ['message' => 'Prestation non trouvée']);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'prestation_id'   => $prestationId,
                'duree'           => trim($_POST['duree'] ?? ''),
                'nb_seances'      => trim($_POST['nb_seances'] ?? ''),
                'prix'            => (float)($_POST['prix'] ?? 0),
                'ordre_affichage' => (int)($_POST['ordre_affichage'] ?? 0),
            ];
            if ($this->adminPrestationModel->createTarif($data)) {
                header("Location: /admin/tarifs?prestation_id={$prestationId}");
                exit;
            }
        }

        return $this->render('admin/tarifs/create.html.twig', [
            'title'      => 'Créer un tarif',
            'prestation' => $prestation,
        ]);
    }

    // Editer tarif (?id=..)
    public function adminTarifEdit(): string
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /admin/prestations');
            exit;
        }

        $tarif = $this->adminPrestationModel->findTarif($id);
        if (!$tarif) {
            http_response_code(404);
            return $this->render('errors/404.html.twig', ['message' => 'Tarif non trouvé']);
        }
        $prestation = $this->adminPrestationModel->findPrestation((int)$tarif['prestation_id']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'duree'           => trim($_POST['duree'] ?? ''),
                'nb_seances'      => trim($_POST['nb_seances'] ?? ''),
                'prix'            => (float)($_POST['prix'] ?? 0),
                'ordre_affichage' => (int)($_POST['ordre_affichage'] ?? 0),
            ];
            if ($this->adminPrestationModel->updateTarif($id, $data)) {
                header("Location: /admin/tarifs?prestation_id={$tarif['prestation_id']}");
                exit;
            }
        }

        return $this->render('admin/tarifs/edit.html.twig', [
            'title'      => 'Modifier le tarif',
            'tarif'      => $tarif,
            'prestation' => $prestation,
        ]);
    }

    // Supprimer tarif (POST id)
    public function adminTarifDelete(): void
    {
        $this->requireAdmin();
        $this->checkCsrf($_POST['csrf'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $tarif = $this->adminPrestationModel->findTarif($id);
            if ($tarif) {
                $this->adminPrestationModel->deleteTarif($id);
            }
            $pid = $tarif['prestation_id'] ?? 0;
            header("Location: /admin/tarifs?prestation_id={$pid}");
            exit;
        }
        header('Location: /admin/prestations');
        exit;
    }
}
