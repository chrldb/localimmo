<?php
function get_results($dpe, $ges, $surface, $code_postal) {
    $surfaceMin = round($surface * 0.9, 1);
    $surfaceMax = round($surface * 1.1, 1);

    $base_url = "https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines";

    $params = [
        "size" => 1000,
        "sort" => "_rand",
        "select" => implode(",", [
            "_geopoint",
            "numero_dpe",
            "date_etablissement_dpe",
            "date_fin_validite_dpe",
            "etiquette_dpe",
            "etiquette_ges",
            "type_batiment",
            "annee_construction",
            "type_installation_chauffage",
            "type_installation_ecs",
            "surface_habitable_immeuble",
            "surface_habitable_logement",
            "adresse_ban",
            "code_postal_ban",
            "nom_commune_ban"
        ]),
        "qs" => "code_postal_ban:$code_postal AND (etiquette_dpe:$dpe) AND (etiquette_ges:$ges) AND surface_habitable_logement:[$surfaceMin TO $surfaceMax]"
    ];

    $url = $base_url . '?' . http_build_query($params);
    $json = file_get_contents($url);
    $results = json_decode($json, true)['results'] ?? [];

    foreach ($results as &$item) {
        $surf = $item['surface_habitable_logement'] ?? null;
        if ($surf) {
            $delta = abs($surf - $surface);
            $score = max(0, 100 - ($delta / $surface) * 100);
            $item['confiance'] = round($score, 1); // ex: 98.5
        } else {
            $item['confiance'] = 0;
        }
    }

    usort($results, fn($a, $b) => $b['confiance'] <=> $a['confiance']);
    return $results;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPE Finder</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">DPE Finder</div>
            <ul class="nav-links">
                <li><a href="#">Accueil</a></li>
                <li><a href="#resultats">Résultats</a></li>
                <li><a href="#">À propos</a></li>
            </ul>
            <a href="#" class="login-btn">Se connecter</a>
        </div>
    </nav>

    <main class="container">
        <section class="search-section">
            <h2>Recherchez des logements en fonction de leur DPE, GES, surface et localisation.</h2>
            <form method="get">
                <div class="form-group">
                    <label>DPE</label>
                    <input type="text" name="dpe" required placeholder="ex: C">
                </div>
                <div class="form-group">
                    <label>GES</label>
                    <input type="text" name="ges" required placeholder="ex: A">
                </div>
                <div class="form-group">
                    <label>Surface habitable (m²)</label>
                    <input type="number" name="surface" required placeholder="ex: 250">
                </div>
                <div class="form-group">
                    <label>Code postal</label>
                    <input type="text" name="cp" required placeholder="ex: 13520">
                </div>
                <button type="submit">Rechercher</button>
            </form>
        </section>

        <?php if ($_GET): ?>
            <section id="resultats">
                <h2>Résultats</h2>
                <?php
                    $results = get_results($_GET['dpe'], $_GET['ges'], $_GET['surface'], $_GET['cp']);
                    if (empty($results)) {
                        echo "<p>Aucun résultat trouvé.</p>";
                    } else {
                        foreach ($results as $item):
                            if (!empty($item['_geopoint'])) {
                                [$lat, $lon] = explode(',', $item['_geopoint']);
                ?>
                <div class="map-container">
                    <strong><?= htmlspecialchars($item['adresse_ban'] ?? 'Adresse inconnue') ?></strong><br>
                   Surface : <?= htmlspecialchars($item['surface_habitable_logement']) ?> m²<br>
                    DPE : <?= htmlspecialchars($item['etiquette_dpe']) ?> - GES : <?= htmlspecialchars($item['etiquette_ges']) ?><br>
                    Score de confiance : <?= $item['confiance'] ?>%
                    <div class="progress-container">
    <div class="progress-bar" style="width: <?= $item['confiance'] ?>%;"></div>
</div>
                    <iframe src="https://www.google.com/maps?q=<?= $lat ?>,<?= $lon ?>&t=k&output=embed" allowfullscreen></iframe>
                </div>
                <?php } endforeach; } ?>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        &copy; <?= date("Y") ?> chrldb. Tous droits réservés.
    </footer>
</body>
</html>
