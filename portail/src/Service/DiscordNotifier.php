<?php
declare(strict_types=1);

namespace Pixelwave\Service;

use Pixelwave\Core\Config;

/**
 * Service externe : envoie un message dans le salon Discord de l'équipe QA
 * à chaque nouveau signalement (webhook Discord, API HTTP + JSON).
 * Une panne de Discord ne bloque jamais le joueur : l'erreur est journalisée.
 */
final class DiscordNotifier
{
    public function __construct(private readonly ?string $url = null)
    {
    }

    public function nouveauTicket(?array $ticket): bool
    {
        $url = $this->url ?? (string) Config::get('discord_webhook', '');
        if ($ticket === null || $url === '' || !str_starts_with($url, 'https://discord.com/api/webhooks/')) {
            return false;
        }

        $message = [
            'username' => 'Portail PixelWave',
            'embeds'   => [[
                'title'       => $ticket['reference'] . ' · ' . $ticket['titre'],
                'description' => extrait($ticket['description'], 300),
                'color'       => 0x3DDBC4,
                'fields'      => [
                    ['name' => 'Jeu',        'value' => $ticket['produit'] ?? '—',    'inline' => true],
                    ['name' => 'Plateforme', 'value' => $ticket['plateforme'] ?? '—', 'inline' => true],
                    ['name' => 'Version',    'value' => $ticket['version_jeu'] ?? '—', 'inline' => true],
                ],
                'url'         => rtrim((string) Config::get('url'), '/') . '/support/' . $ticket['reference'],
            ]],
        ];

        $contexte = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => json_encode($message, JSON_UNESCAPED_UNICODE),
            'timeout'       => 3,
            'ignore_errors' => true,
        ]]);
        $reponse = @file_get_contents($url, false, $contexte);
        $entetes = function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?? []) : ($http_response_header ?? []);
        $statut = isset($entetes[0]) && preg_match('/\s(\d{3})(\s|$)/', $entetes[0], $m) ? (int) $m[1] : 0;
        if ($reponse === false || $statut >= 300) {
            error_log("[discord] échec de notification pour {$ticket['reference']} (HTTP $statut)");
            return false;
        }
        return true;
    }
}

