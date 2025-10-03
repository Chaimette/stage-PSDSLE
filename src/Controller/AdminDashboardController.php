<?php

namespace App\Controller;

use App\Model\PrestaModel;
use App\Model\AdminPrestaModel;


class AdminDashboardController extends AbstractController
{
    private PrestaModel $model;
    private AdminPrestaModel $adminModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PrestaModel($this->pdo);
        $this->adminModel = new AdminPrestaModel($this->pdo);
    }

    public function index(): string
    {
        $this->requireAdmin();

        // POST => sauvegarde globale (sections, prestations, tarifs)
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->checkCsrf($_POST['csrf'] ?? '');

            $sections    = $_POST['sections']    ?? [];
            $prestations = $_POST['prestations'] ?? [];
            $tarifs      = $_POST['tarifs']      ?? [];

            $pdo = $this->pdo;
            $pdo->beginTransaction();
            try {
                // maps pour remapper IDs temporaires -> vrais IDs
                $sectionIdMap    = []; // 'new1'  => 12
                $prestationIdMap = []; // 'newP3' => 77

                // SECTIONS
                foreach ($sections as $sid => $s) {
                    $delete = !empty($s['delete']);

                    if (ctype_digit((string)$sid)) {
                        $id = (int)$sid;
                        if ($delete) {
                            $pdo->prepare("DELETE FROM sections WHERE id=?")->execute([$id]);
                            continue;
                        }

                        $nom   = $this->clip($s['nom'] ?? '', 150);
                        $slug  = $this->sanitizeSlug($s['slug'] ?? '');
                        if ($slug === '') $slug = $this->sanitizeSlug($s['nom'] ?? '');
                        $slug  = $this->ensureUniqueSlug($slug, $id);
                        $desc  = $this->clip($s['description'] ?? '', 2000);
                        $meta  = $this->clip($s['meta_description'] ?? '', 255);
                        $ordre = $this->posInt($s['ordre_affichage'] ?? 0);
                        $actif = !empty($s['actif']) ? 1 : 1;



                        $pdo->prepare("
    UPDATE sections 
    SET nom=?, slug=?, description=?, meta_description=?, ordre_affichage=?, actif=? 
    WHERE id=?
")->execute([$nom, $slug, $desc, $meta, $ordre, $actif, $id]);
                    } else {
                        if ($delete) continue;
                        $nom   = $this->clip($s['nom'] ?? '', 150);
                        $slug  = $this->sanitizeSlug($s['slug'] ?? '');
                        if ($slug === '') $slug = $this->sanitizeSlug($s['nom'] ?? '');
                        $slug  = $this->ensureUniqueSlug($slug, 0);
                        $desc  = $this->clip($s['description'] ?? '', 2000);
                        $meta  = $this->clip($s['meta_description'] ?? '', 255);
                        $ordre = $this->posInt($s['ordre_affichage'] ?? 0);
                        $actif = !empty($s['actif']) ? 1 : 1;

                        $pdo->prepare("
    INSERT INTO sections (nom, slug, description, meta_description, ordre_affichage, actif) 
    VALUES (?,?,?,?,?,?)
")->execute([$nom, $slug, $desc, $meta, $ordre, $actif]);

                        $sectionIdMap[$sid] = (int)$pdo->lastInsertId();
                    }
                }

                // PRESTAS
                foreach ($prestations as $pid => $p) {
                    $delete = !empty($p['delete']);

                    // remap section_id si temporaire
                    $secId = $p['section_id'] ?? null;
                    if ($secId && !ctype_digit((string)$secId) && isset($sectionIdMap[$secId])) {
                        $p['section_id'] = $sectionIdMap[$secId];
                    }

                    if (ctype_digit((string)$pid)) {
                        $id = (int)$pid;
                        if ($delete) {
                            $pdo->prepare("DELETE FROM prestations WHERE id=?")->execute([$id]);
                            continue;
                        }
                        $sectionId = (int)($p['section_id'] ?? 0);
                        $nom       = $this->clip($p['nom'] ?? '', 150);
                        $desc      = $this->clip($p['description'] ?? '', 2000);
                        $ordre     = $this->posInt($p['ordre_affichage'] ?? 0);
                        $actif     = !empty($p['actif']) ? 1 : 0;

                        $pdo->prepare("
    UPDATE prestations 
    SET section_id=?, nom=?, description=?, ordre_affichage=?, actif=? 
    WHERE id=?
")->execute([$sectionId, $nom, $desc, $ordre, $actif, $id]);
                    } else {
                        if ($delete) continue;
                        $sectionId = (int)($p['section_id'] ?? 0);
                        $nom       = $this->clip($p['nom'] ?? '', 150);
                        $desc      = $this->clip($p['description'] ?? '', 2000);
                        $ordre     = $this->posInt($p['ordre_affichage'] ?? 0);
                        $actif     = 1;

                        $pdo->prepare("
    INSERT INTO prestations (section_id, nom, description, ordre_affichage, actif) 
    VALUES (?,?,?,?,?)
")->execute([$sectionId, $nom, $desc, $ordre, $actif]);

                        $prestationIdMap[$pid] = (int)$pdo->lastInsertId();
                    }
                }

                // TARIFS
                foreach ($tarifs as $tid => $t) {
                    $delete = !empty($t['delete']);

                    // remap prestation_id si temporaire
                    $prId = $t['prestation_id'] ?? null;
                    if ($prId && !ctype_digit((string)$prId) && isset($prestationIdMap[$prId])) {
                        $t['prestation_id'] = $prestationIdMap[$prId];
                    }

                    if (ctype_digit((string)$tid)) {
                        $id = (int)$tid;
                        if ($delete) {
                            $pdo->prepare("DELETE FROM tarifs WHERE id=?")->execute([$id]);
                            continue;
                        }
                        $prestationId = (int)($t['prestation_id'] ?? 0);
                        $duree        = $this->clip($t['duree'] ?? '', 100);
                        $nb           = $this->clip($t['nb_seances'] ?? '', 50);
                        $prix         = $this->price($t['prix'] ?? 0);
                        $ordre        = $this->posInt($t['ordre_affichage'] ?? 0);

                        $pdo->prepare("
    UPDATE tarifs 
    SET prestation_id=?, duree=?, nb_seances=?, prix=?, ordre_affichage=? 
    WHERE id=?
")->execute([$prestationId, $duree, $nb, $prix, $ordre, $id]);
                    } else {
                        if ($delete) continue;
                        $prestationId = (int)($t['prestation_id'] ?? 0);
                        $duree        = $this->clip($t['duree'] ?? '', 100);
                        $nb           = $this->clip($t['nb_seances'] ?? '', 50);
                        $prix         = $this->price($t['prix'] ?? 0);
                        $ordre        = $this->posInt($t['ordre_affichage'] ?? 0);

                        $pdo->prepare("
    INSERT INTO tarifs (prestation_id, duree, nb_seances, prix, ordre_affichage) 
    VALUES (?,?,?,?,?)
")->execute([$prestationId, $duree, $nb, $prix, $ordre]);
                    }
                }

                $pdo->commit();

                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
                $_SESSION['flash_success'] = "Vos modifications ont bien été enregistrées!";
                header('Location: /admin');
                exit;
            } catch (\Throwable $e) {
                $pdo->rollBack();
                http_response_code(500);
                exit('Une erreur est survenue lors de la sauvegarde.');
            }
        }
        $flash = null;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (isset($_SESSION['flash_success'])) {
            $flash = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        // afficher le builder
        $tree = $this->adminModel->getAdminTree();
        return $this->render('admin/builder.html.twig', [
            'title' => 'Admin – Builder',
            'tree'  => $tree,
            'csrf'  => $this->csrfToken(),
            'flash' => $flash,
        ]);
    }
}
