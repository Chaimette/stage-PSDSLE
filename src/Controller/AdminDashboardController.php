<?php

namespace App\Controller;

use App\Model\PrestaModel;

class AdminDashboardController extends AbstractController
{
    private PrestaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PrestaModel($this->pdo);
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
                        $pdo->prepare("UPDATE sections SET nom=?, slug=?, description=?, meta_description=?, ordre_affichage=?, actif=? WHERE id=?")
                            ->execute([
                                trim($s['nom'] ?? ''),
                                trim($s['slug'] ?? ''),
                                trim($s['description'] ?? ''),
                                trim($s['meta_description'] ?? ''),
                                (int)($s['ordre_affichage'] ?? 0),
                                !empty($s['actif']) ? 1 : 0,
                                $id
                            ]);
                    } else {
                        if ($delete) continue;
                        $pdo->prepare("INSERT INTO sections (nom, slug, description, meta_description, ordre_affichage, actif) VALUES (?,?,?,?,?,?)")
                            ->execute([
                                trim($s['nom'] ?? ''),
                                trim($s['slug'] ?? ''),
                                trim($s['description'] ?? ''),
                                trim($s['meta_description'] ?? ''),
                                (int)($s['ordre_affichage'] ?? 0),
                                !empty($s['actif']) ? 1 : 1
                            ]);
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
                        $pdo->prepare("UPDATE prestations SET section_id=?, nom=?, description=?, ordre_affichage=?, actif=? WHERE id=?")
                            ->execute([
                                (int)($p['section_id'] ?? 0),
                                trim($p['nom'] ?? ''),
                                trim($p['description'] ?? ''),
                                (int)($p['ordre_affichage'] ?? 0),
                                !empty($p['actif']) ? 1 : 0,
                                $id
                            ]);
                    } else {
                        if ($delete) continue;
                        $pdo->prepare("INSERT INTO prestations (section_id, nom, description, ordre_affichage, actif) VALUES (?,?,?,?,?)")
                            ->execute([
                                (int)($p['section_id'] ?? 0),
                                trim($p['nom'] ?? ''),
                                trim($p['description'] ?? ''),
                                (int)($p['ordre_affichage'] ?? 0),
                                !empty($p['actif']) ? 1 : 1
                            ]);
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
                        $pdo->prepare("UPDATE tarifs SET prestation_id=?, duree=?, nb_seances=?, prix=?, ordre_affichage=? WHERE id=?")
                            ->execute([
                                (int)($t['prestation_id'] ?? 0),
                                trim($t['duree'] ?? ''),
                                trim($t['nb_seances'] ?? ''),
                                (float)($t['prix'] ?? 0),
                                (int)($t['ordre_affichage'] ?? 0),
                                $id
                            ]);
                    } else {
                        if ($delete) continue;
                        $pdo->prepare("INSERT INTO tarifs (prestation_id, duree, nb_seances, prix, ordre_affichage) VALUES (?,?,?,?,?)")
                            ->execute([
                                (int)($t['prestation_id'] ?? 0),
                                trim($t['duree'] ?? ''),
                                trim($t['nb_seances'] ?? ''),
                                (float)($t['prix'] ?? 0),
                                (int)($t['ordre_affichage'] ?? 0)
                            ]);
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
                echo "Erreur sauvegarde: " . htmlspecialchars($e->getMessage());
                exit;
            }
        }
        $flash = null;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (isset($_SESSION['flash_success'])) {
            $flash = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']); // on l’affiche une seule fois
        }

        // afficher le builder
        $tree = $this->model->getAdminTree();
        return $this->render('admin/builder.html.twig', [
            'title' => 'Admin – Builder',
            'tree'  => $tree,
            'csrf'  => $this->csrfToken(),
            'flash' => $flash,
        ]);
    }
}
