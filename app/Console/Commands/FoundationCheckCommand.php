<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class FoundationCheckCommand extends Command
{
    protected $signature = 'eduxora:foundation-check
                            {--mail : Envoie un message de vérification à Mailpit}';

    protected $description = 'Vérifie PostgreSQL, Redis et facultativement Mailpit en local.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Cette commande est limitée aux environnements local et testing.');

            return self::FAILURE;
        }

        try {
            DB::select('select 1');
            $this->components->info('PostgreSQL : connexion opérationnelle');

            Redis::connection()->command('ping');
            $this->components->info('Redis : connexion opérationnelle');

            if ((bool) $this->option('mail')) {
                Mail::raw(
                    'Le socle EduXora communique correctement avec Mailpit.',
                    fn ($message) => $message
                        ->to('foundation@eduxora.test')
                        ->subject('EduXora — vérification Mailpit'),
                );
                $this->components->info('Mailpit : message de vérification envoyé');
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Échec du diagnostic Foundation. Consultez les logs applicatifs.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
