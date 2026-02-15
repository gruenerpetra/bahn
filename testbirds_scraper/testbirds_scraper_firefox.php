<?php
// testbirds_scraper_firefox.php
// PHP CLI script: log in to Nest (Testbirds), filter projects with "Bahn", grab YOUR bugs and save to MySQL.
// Requirements:
//   - php-webdriver (composer require php-webdriver/webdriver)
//   - geckodriver running (listening on 127.0.0.1:4444)
//   - Firefox installed

require __DIR__ . '/vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\WebDriverBy;

// ======= CONFIG =======
// Prefer environment variables for credentials in production.
// For quick start, fill the variables below (but consider moving them to .env later).
$TB_EMAIL = getenv('TB_EMAIL') ?: 'gruener.petra.92@web.de';
$TB_PASS  = getenv('TB_PASS')  ?: 'Estoy20';

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'testberichte';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

// WebDriver server (geckodriver) default:
$seleniumUrl = 'http://127.0.0.1:4444'; // geckodriver default

// Options:
$headless = false; // true => headless mode (no browser window). For debugging set false.
$filterKeyword = 'Bahn'; // nur Projekte mit diesem Wort im Namen

// Timeout / wait
$implicitWaitSec = 5;

// =======================

// --- DB connection
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Prepared upsert
$insertStmt = $pdo->prepare("
    INSERT INTO meine_bahn_bugs
      (bug_id, titel, status, projekt, beschreibung, erstellt_am, raw_html, last_seen)
    VALUES
      (:bug_id, :titel, :status, :projekt, :beschreibung, :erstellt_am, :raw_html, NOW())
    ON DUPLICATE KEY UPDATE
      titel = VALUES(titel),
      status = VALUES(status),
      projekt = VALUES(projekt),
      beschreibung = VALUES(beschreibung),
      erstellt_am = VALUES(erstellt_am),
      raw_html = VALUES(raw_html),
      last_seen = NOW()
");

// --- Start WebDriver (Firefox)
$options = new FirefoxOptions();
if ($headless) {
    $options->addArguments(['--headless']);
}
$capabilities = DesiredCapabilities::firefox();
$capabilities->setCapability(FirefoxOptions::CAPABILITY, $options);

echo "Verbinde mit geckodriver...\n";
$driver = RemoteWebDriver::create($seleniumUrl, $capabilities, 5000);

// Implicit wait
$driver->manage()->timeouts()->implicitlyWait($implicitWaitSec);

// --- 1) Seite öffnen und einloggen
$loginUrl = 'https://nest.testbirds.com/login';
echo "Öffne Login-Seite...\n";
$driver->get($loginUrl);

// Versuche typische Login-Formfelder zu füllen. Selectors sind generisch — ggf. anpassen.
try {
    // Warte, bis Eingabefeld vorhanden ist
    $driver->findElement(WebDriverBy::name('_username'))->clear();
    $driver->findElement(WebDriverBy::name('_username'))->sendKeys($TB_EMAIL);
    $driver->findElement(WebDriverBy::name('_password'))->sendKeys($TB_PASS);

    // Senden
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"], button.login'))->click();
} catch (Exception $e) {
    echo "Login-Felder nicht gefunden: " . $e->getMessage() . PHP_EOL;
    $driver->quit();
    exit(1);
}

// Warte kurz bis Dashboard geladen ist (evtl. 2FA manuell bestätigen)
sleep(5);

// Optional: detect if login failed (look for an element present only on login page)
$current = $driver->getCurrentURL();
if (stripos($current, '/login') !== false) {
    echo "Anscheinend nicht eingeloggt. Prüfe deine Zugangsdaten oder 2FA. Skript beendet.\n";
    $driver->quit();
    exit(1);
}

echo "Eingeloggt. Navigiere zur eigenen Bug-Liste...\n";

// --- 2) Navigiere zur "My Bugs" (oder ähnlicher) Seite
// Übliche URL: /bugs/my-bugs oder /bugs
$possibleUrls = [
    'https://nest.testbirds.com/bugs/my-bugs',
    'https://nest.testbirds.com/bugs',
    'https://nest.testbirds.com/issue' // Fallback
];

$opened = false;
foreach ($possibleUrls as $u) {
    $driver->get($u);
    sleep(3);
    // Check if we are on a page with a bug list: look for table rows or a list item
    try {
        $rows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr, .bug-list-item, .list-row'));
        if (count($rows) > 0) {
            $opened = true;
            break;
        }
    } catch (Exception $e) {
        // ignore and continue
    }
}
if (!$opened) {
    // fallback: go to /dashboard and then try to find link to "My Bugs"
    $driver->get('https://nest.testbirds.com/dashboard');
    sleep(2);
    echo "Konnte die Bug-Liste nicht automatisch finden. Bitte öffne https://nest.testbirds.com/bugs/my-bugs manuell im Browser und drücke Enter.\n";
    readline("Weiter mit Enter...");
    // Try again
    $rows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr, .bug-list-item, .list-row'));
}

// --- 3) Falls vorhanden: Filter nach Projektname "Bahn"
// Versuche, ein Suchfeld oder Filterbox zu benutzen
try {
    // Suche nach einem globalen Suchfeld
    $search = null;
    $possibleSearchSelectors = [
        'input[type="search"]',
        'input[placeholder*="Search"]',
        'input[placeholder*="Projekt"]',
        'input[placeholder*="Search projects"]',
        '.search-input input'
    ];
    foreach ($possibleSearchSelectors as $sel) {
        $elements = $driver->findElements(WebDriverBy::cssSelector($sel));
        if (count($elements) > 0) {
            $search = $elements[0];
            break;
        }
    }
    if ($search) {
        $search->clear();
        $search->sendKeys($filterKeyword);
        sleep(2); // warten, bis das Filterergebnis geladen ist
    } else {
        echo "Kein Suchfeld gefunden — es wird clientseitig nach 'Bahn' gefiltert (falls möglich)\n";
    }
} catch (Exception $e) {
    echo "Fehler beim Suchen: {$e->getMessage()}\n";
}

// --- 4) Paging und Zeilen auslesen
// Diese Logik ist bewusst defensiv: es versucht mehrere typische List-Layouts.
function parseDateToMysql($str) {
    // Versucht mit strtotime, ansonsten null
    $t = @strtotime($str);
    return $t ? date('Y-m-d H:i:s', $t) : null;
}

$total = 0;
$page = 1;
$maxPages = 50; // Sicherheitsschalter

do {
    echo "Seite $page auslesen...\n";
    sleep(1);

    // Lade alle Bug-Zeilen (mehrere mögliche Selectors)
    $rows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr, .bug-list-item, .list-row, .list-item'));
    echo "Gefundene Zeilen auf der Seite: " . count($rows) . "\n";

    foreach ($rows as $row) {
        try {
            $rawHtml = $row->getAttribute('outerHTML');

            // Versuche konkrete Spalten zu extrahieren, mehrere Fallbacks
            $titel = $projekt = $status = $beschreibung = $erstellt = null;
            // 1) Table row common case
            $cells = $row->findElements(WebDriverBy::cssSelector('td'));
            if (count($cells) >= 1) {
                // heuristisch
                $titel = trim($cells[0]->getText());
                if (isset($cells[1])) $projekt = trim($cells[1]->getText());
                if (isset($cells[2])) $status = trim($cells[2]->getText());
                if (isset($cells[3])) $erstellt = trim($cells[3]->getText());
            } else {
                // 2) List item case: try to find inner elements
                try {
                    $titelEl = $row->findElement(WebDriverBy::cssSelector('.title, .bug-title, h3, .list-title'));
                    $titel = trim($titelEl->getText());
                } catch (Exception $e) {}
                try {
                    $projektEl = $row->findElement(WebDriverBy::cssSelector('.project, .project-name, .meta .project'));
                    $projekt = trim($projektEl->getText());
                } catch (Exception $e) {}
                try {
                    $statusEl = $row->findElement(WebDriverBy::cssSelector('.status, .state'));
                    $status = trim($statusEl->getText());
                } catch (Exception $e) {}
                try {
                    $erstEl = $row->findElement(WebDriverBy::cssSelector('.date, .created'));
                    $erstellt = trim($erstEl->getText());
                } catch (Exception $e) {}
            }

            // If no project text available, maybe the list shows project in a badge or parent
            if (!$projekt) {
                try {
                    $badge = $row->findElement(WebDriverBy::cssSelector('.badge, .project-badge, .project-tag'));
                    $projekt = trim($badge->getText());
                } catch (Exception $e) {}
            }

            // Only save rows that belong to Bahn projects
            if (!$projekt || stripos($projekt, $GLOBALS['filterKeyword']) === false) {
                continue;
            }

            // Try to get a stable bug id: data-id attribute or link -> href with id
            $bugId = null;
            try {
                $link = $row->findElement(WebDriverBy::cssSelector('a[href*="bug"], a[href*="issues"], a[href*="/bugs/"], a.issue-link'));
                $href = $link->getAttribute('href');
                // extract trailing number if present
                if (preg_match('~(\d{4,})~', $href, $m)) {
                    $bugId = $m[1];
                } else {
                    $bugId = md5($href);
                }
            } catch (Exception $e) {
                // fallback to hashing visible fields
                $bugId = md5(($titel ?? '') . ($projekt ?? '') . ($erstellt ?? ''));
            }

            // If description not present in list, try to open detail panel (click) and scrape
            $gotDescription = false;
            if (empty($beschreibung)) {
                try {
                    // open detail in a new tab or expand detail region if possible
                    $driver->executeScript('arguments[0].scrollIntoView(true);', [$row]);
                    // click row to open detail (if clickable)
                    $row->click();
                    sleep(1);
                    // After opening, try to find a description in a side panel / modal
                    $descEl = null;
                    $possibleDescSelectors = [
                        '.bug-description', '.description', '.modal .description', '.details .description', '.issue-description'
                    ];
                    foreach ($possibleDescSelectors as $sel) {
                        $els = $driver->findElements(WebDriverBy::cssSelector($sel));
                        if (count($els) > 0) {
                            $descEl = $els[0];
                            break;
                        }
                    }
                    if ($descEl) {
                        $beschreibung = trim($descEl->getText());
                        $gotDescription = true;
                    }
                    // Close modal or go back if necessary - try escape
                    $driver->get($driver->getCurrentURL()); // naive reload to ensure stable state
                    sleep(1);
                } catch (Exception $e) {
                    // ignore - description optional
                }
            }

            // Convert date
            $erstellt_at = parseDateToMysql($erstellt);

            // Insert/Upsert into DB
            $insertStmt->execute([
                ':bug_id' => $bugId,
                ':titel' => mb_substr($titel ?? '', 0, 512),
                ':status' => mb_substr($status ?? '', 0, 128),
                ':projekt' => mb_substr($projekt ?? '', 0, 255),
                ':beschreibung' => $beschreibung ?? null,
                ':erstellt_am' => $erstellt_at,
                ':raw_html' => $rawHtml
            ]);

            $total++;
        } catch (Exception $e) {
            echo "Fehler beim Verarbeiten einer Zeile: " . $e->getMessage() . "\n";
            continue;
        }
    }

    // --- Paging: versuche "Next" Button zu klicken (typische selectors)
    $nextClicked = false;
    try {
        $nextSelectors = [
            'a[rel="next"]',
            '.pagination .next',
            '.pager .next',
            'button.next'
        ];
        foreach ($nextSelectors as $sel) {
            $n = $driver->findElements(WebDriverBy::cssSelector($sel));
            if (count($n) > 0) {
                $n[0]->click();
                $nextClicked = true;
                sleep(2);
                break;
            }
        }
    } catch (Exception $e) {
        // ignore
    }

    // If no next found, try to detect infinite-scroll: scroll & wait for new rows
    if (!$nextClicked) {
        // try one scroll to bottom and wait
        $driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');
        sleep(2);
        // re-evaluate rows count; if same then stop
        $newRows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr, .bug-list-item, .list-row, .list-item'));
        if (count($newRows) == 0 || $page >= $maxPages) {
            break;
        }
        // If same count and no next, assume end
        if (count($newRows) <= count($rows)) {
            break;
        }
    }

    $page++;
} while ($page <= $maxPages);

echo "Fertig. Gesamt verarbeitete Einträge: $total\n";

$driver->quit();
?>