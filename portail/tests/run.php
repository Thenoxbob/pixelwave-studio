<?php
declare(strict_types=1);

// Lanceur de tests sans Composer : php tests/run.php [unit|integration]
// Avec PHPUnit installé, préférer : vendor/bin/phpunit

require __DIR__ . '/bootstrap.php';

$suites = isset($argv[1]) ? [$argv[1]] : ['unit', 'integration'];
$resultats = ['ok' => 0, 'echec' => 0, 'erreur' => 0];
$echecs = [];
$debut = microtime(true);

foreach ($suites as $suite) {
    echo "\n== Suite « $suite » ==\n";
    foreach (glob(__DIR__ . "/$suite/*Test.php") as $fichier) {
        $avant = get_declared_classes();
        require_once $fichier;
        foreach (array_diff(get_declared_classes(), $avant) as $classe) {
            $ref = new ReflectionClass($classe);
            if ($ref->isAbstract() || !$ref->isSubclassOf(PHPUnit\Framework\TestCase::class)) {
                continue;
            }
            echo "\n" . $ref->getShortName() . "\n";
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if (!str_starts_with($m->getName(), 'test')) {
                    continue;
                }
                $nom = $m->getName();
                try {
                    (new $classe())->executer($nom);
                    $resultats['ok']++;
                    echo "  [OK]     $nom\n";
                } catch (PHPUnit\Framework\AssertionFailedError $e) {
                    $resultats['echec']++;
                    $echecs[] = "$classe::$nom — " . $e->getMessage();
                    echo "  [ÉCHEC]  $nom : " . $e->getMessage() . "\n";
                } catch (Throwable $e) {
                    $resultats['erreur']++;
                    $echecs[] = "$classe::$nom — " . $e::class . ': ' . $e->getMessage();
                    echo "  [ERREUR] $nom : " . $e::class . ': ' . $e->getMessage() . "\n";
                }
            }
        }
    }
}

$total = array_sum($resultats);
printf("\n%d tests, %d réussis, %d échecs, %d erreurs (%.2f s)\n", $total, $resultats['ok'], $resultats['echec'], $resultats['erreur'], microtime(true) - $debut);
exit($resultats['echec'] + $resultats['erreur'] > 0 ? 1 : 0);
