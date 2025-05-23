<?php
function extract_info_from_html($html, $url) {
    $info = ['surface' => null, 'dpe' => null, 'ges' => null, 'adresse' => null];

    if (strpos($url, 'bellesdemeures.com') !== false) {
        preg_match('/<li class="annonceSpecsListItem js_livingArea">.*?<span[^>]*>Surface de ([\d,.]+) m\u00b2<\/span>/is', $html, $m);
        if ($m) $info['surface'] = str_replace(',', '.', trim($m[1]));
        preg_match('/<div[^>]*id="dpeClassActive"[^>]*>\s*<p>([A-G])<\/p>/i', $html, $m);
        if ($m) $info['dpe'] = trim($m[1]);
        preg_match('/<div[^>]*id="gesClassActive"[^>]*>\s*<p>([A-G])<\/p>/i', $html, $m);
        if ($m) $info['ges'] = trim($m[1]);
        preg_match('/<div class="annonceSpecsListItemVille">\s*(.*?)\s*<\/div>/i', $html, $m);
        if ($m) $info['adresse'] = trim(strip_tags($m[1]));
    }
    elseif (strpos($url, 'seloger.com') !== false) {
        preg_match_all('/data-testid="aviv\\.CDP\\.Sections\\.Energy\\.Preview\\.EfficiencyClass">([A-G])<\/div>/', $html, $matches);
        if (count($matches[1]) >= 2) {
            $info['dpe'] = $matches[1][0];
            $info['ges'] = $matches[1][1];
        } elseif (count($matches[1]) === 1) {
            $info['dpe'] = $matches[1][0];
        }
        preg_match('/<div class="css-1ytyjyb">(.*?)<\/div>/', $html, $m);
        if ($m) $info['adresse'] = trim(strip_tags($m[1]));
        preg_match('/<span class="css-1b9ytm">([\d.,]+) m\u00b2<\/span>/', $html, $m);
        if ($m) $info['surface'] = str_replace(',', '.', $m[1]);
    }
    elseif (strpos($url, 'logic-immo.com') !== false) {
        preg_match('/<span class="css-1nxshv1">([\d.,]+) m\u00b2<\/span>/', $html, $m);
        if ($m) $info['surface'] = str_replace(',', '.', $m[1]);
        preg_match_all('/data-testid="aviv\\.CDP\\.Sections\\.Energy\\.Preview\\.EfficiencyClass">([A-G])<\/div>/', $html, $matches);
        if (count($matches[1]) >= 2) {
            $info['dpe'] = $matches[1][0];
            $info['ges'] = $matches[1][1];
        }
        preg_match('/<div class="css-1ytyjyb">(.*?)<\/div>/', $html, $m);
        if ($m) $info['adresse'] = trim(strip_tags($m[1]));
    }
    elseif (strpos($url, 'bienici.com') !== false) {
        preg_match('/<div class="labelInfo"><span>([\d.,]+)&nbsp;m\u00b2<\/span>/', $html, $m);
        if ($m) $info['surface'] = str_replace(',', '.', $m[1]);
        preg_match('/<div class="dpe-line__classification">.*?<div>([A-G])<\/div>/', $html, $m);
        if ($m) $info['dpe'] = trim($m[1]);
        preg_match('/<div class="ges-line__classification"[^>]*><span>([A-G])<\/span>/', $html, $m);
        if ($m) $info['ges'] = trim($m[1]);
        preg_match('/<span class="fullAddress">(.*?)<\/span>/', $html, $m);
        if ($m) $info['adresse'] = trim(strip_tags($m[1]));
    }

    return $info;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localiser une annonce immo</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">DPE Finder</div>
            <ul class="nav-links">
                <li><a href="/">Accueil</a></li>
                <li><a href="/result">Résultats</a></li>
                <li><a href="#">À propos</a></li>
            </ul>
            <a href="#" class="login-btn">Se connecter</a>
        </div>
    </nav>

    <main class="container">
        <section class="search-section">
            <h2>Localiser une annonce immobilière</h2>
            <form method="post">
                <div class="form-group">
                    <label>URL de l'annonce :</label>
                    <input type="url" name="url" placeholder="https://..." required>
                    <button type="submit">Localiser</button>
                </div>
            </form>
        </section>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])):
            $url = $_POST['url'];
            $api_key = getenv('ZENROWS_API_KEY');
            $proxy_url = 'https://api.zenrows.com/v1/?apikey=' . $api_key .
                '&url=' . urlencode($url) .
                '&premium_proxy=true&js_render=true';

            $html = @file_get_contents($proxy_url);
            if (!$html) {
                echo "<p style='color: red;'>Échec de la récupération via ZenRows.</p>";
                echo "<pre>URL : " . htmlspecialchars($proxy_url) . "</pre>";
            } else {
                $infos = extract_info_from_html($html, $url);
        ?>
        <section class="search-section">
            <form method="post" action="/result" class="validate">
                <h2>Modifier ou valider les informations extraites</h2>
                <div class="form-group">
                    <label for="adresse">Adresse :</label>
                    <input type="text" name="adresse" id="adresse" value="<?= htmlspecialchars($infos['adresse'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="surface">Surface (m²) :</label>
                    <input type="number" step="0.1" name="surface" id="surface" value="<?= htmlspecialchars($infos['surface'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="dpe">DPE (A à G) :</label>
                    <input type="text" name="dpe" id="dpe" maxlength="1" value="<?= htmlspecialchars($infos['dpe'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="ges">GES (A à G) :</label>
                    <input type="text" name="ges" id="ges" maxlength="1" value="<?= htmlspecialchars($infos['ges'] ?? '') ?>" required>
                </div>
                <button type="submit">Valider et continuer</button>
            </form>
        </section>
        <?php } endif; ?>
    </main>

    <footer>
        &copy; <?= date("Y") ?> chrldb. Tous droits réservés.
    </footer>
</body>
</html>
