<?php
declare(strict_types=1);

// Sous-ensemble compatible de PHPUnit\Framework\TestCase.
// Chargé uniquement si PHPUnit n'est pas installé : les mêmes fichiers de test
// tournent avec `vendor/bin/phpunit` (serveur Debian) ou `php tests/run.php` (poste sans Composer).

namespace PHPUnit\Framework;

if (!class_exists(TestCase::class, false)) {

    class AssertionFailedError extends \Exception
    {
    }

    abstract class TestCase
    {
        private ?string $exceptionAttendue = null;
        private ?int $codeAttendu = null;

        protected function setUp(): void
        {
        }

        protected function tearDown(): void
        {
        }

        /** Utilisé par tests/run.php */
        public function executer(string $methode): void
        {
            $this->exceptionAttendue = null;
            $this->codeAttendu = null;
            $this->setUp();
            try {
                try {
                    $this->$methode();
                } catch (AssertionFailedError $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    if ($this->exceptionAttendue !== null && $e instanceof $this->exceptionAttendue) {
                        if ($this->codeAttendu !== null && $e->getCode() !== $this->codeAttendu) {
                            throw new AssertionFailedError("Code d'exception {$e->getCode()} au lieu de {$this->codeAttendu}");
                        }
                        return;
                    }
                    throw $e;
                }
                if ($this->exceptionAttendue !== null) {
                    throw new AssertionFailedError("Exception {$this->exceptionAttendue} attendue mais non levée");
                }
            } finally {
                $this->tearDown();
            }
        }

        public function expectException(string $classe): void
        {
            $this->exceptionAttendue = $classe;
        }

        public function expectExceptionCode(int $code): void
        {
            $this->codeAttendu = $code;
        }

        private static function echec(string $message, string $defaut): never
        {
            throw new AssertionFailedError($message !== '' ? $message : $defaut);
        }

        public static function assertTrue(mixed $v, string $m = ''): void
        {
            $v === true || self::echec($m, 'Valeur fausse : ' . var_export($v, true));
        }

        public static function assertFalse(mixed $v, string $m = ''): void
        {
            $v === false || self::echec($m, 'Valeur vraie : ' . var_export($v, true));
        }

        public static function assertSame(mixed $attendu, mixed $obtenu, string $m = ''): void
        {
            $attendu === $obtenu || self::echec($m, 'Attendu ' . var_export($attendu, true) . ', obtenu ' . var_export($obtenu, true));
        }

        public static function assertEquals(mixed $attendu, mixed $obtenu, string $m = ''): void
        {
            $attendu == $obtenu || self::echec($m, 'Attendu ' . var_export($attendu, true) . ', obtenu ' . var_export($obtenu, true));
        }

        public static function assertNotSame(mixed $a, mixed $b, string $m = ''): void
        {
            $a !== $b || self::echec($m, 'Valeurs identiques : ' . var_export($a, true));
        }

        public static function assertNull(mixed $v, string $m = ''): void
        {
            $v === null || self::echec($m, 'Null attendu, obtenu ' . var_export($v, true));
        }

        public static function assertNotNull(mixed $v, string $m = ''): void
        {
            $v !== null || self::echec($m, 'Valeur non nulle attendue');
        }

        public static function assertCount(int $n, \Countable|array $v, string $m = ''): void
        {
            count($v) === $n || self::echec($m, "Attendu $n éléments, obtenu " . count($v));
        }

        public static function assertArrayHasKey(string|int $cle, array $v, string $m = ''): void
        {
            array_key_exists($cle, $v) || self::echec($m, "Clé « $cle » absente");
        }

        public static function assertStringContainsString(string $aiguille, string $botte, string $m = ''): void
        {
            str_contains($botte, $aiguille) || self::echec($m, "« $aiguille » absent de « $botte »");
        }

        public static function assertMatchesRegularExpression(string $motif, string $v, string $m = ''): void
        {
            preg_match($motif, $v) === 1 || self::echec($m, "« $v » ne respecte pas $motif");
        }

        public static function assertGreaterThan(mixed $min, mixed $v, string $m = ''): void
        {
            $v > $min || self::echec($m, var_export($v, true) . ' n\'est pas > ' . var_export($min, true));
        }

        public static function fail(string $m = ''): never
        {
            self::echec($m, 'Échec');
        }
    }
}
