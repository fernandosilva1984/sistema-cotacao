<?php
// app/Console/Commands/TestarImapSimples.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestarImapSimples extends Command
{
    protected $signature = 'email:testar-imap-simples';
    protected $description = 'Teste IMAP super simplificado';

    public function handle()
    {
        $this->info('🧪 Teste IMAP Simplificado');

        if (!function_exists('imap_open')) {
            $this->error('❌ Extensão IMAP do PHP não está instalada');
            $this->line('No Ubuntu: sudo apt-get install php-imap');
            $this->line('No Windows: descomente extension=imap no php.ini');
            return 1;
        }

        $host = env('MAIL_HOST');
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');

        $this->line("Tentando conectar: {$username}@{$host}");

        // Testar com a função nativa do PHP
        try {
            $mailbox = "imap.titan.email:993/imap/ssl}INBOX";
            $this->line("String: {$mailbox}");

            $imap = @imap_open($mailbox, $username, $password, 0, 1);
            
            if ($imap) {
                $this->info('✅ Conexão IMAP bem-sucedida!');
                
                // Informações básicas
                $info = imap_check($imap);
                $this->line("Mensagens: " . $info->Nmsgs);
                $this->line("Caixa: " . $info->Mailbox);
                
                imap_close($imap);
                return 0;
            } else {
                $this->error('❌ Falha na conexão: ' . imap_last_error());
                
                // Tentar sem SSL
                $this->line('');
                $this->info('Tentando sem SSL...');
                $mailbox2 = "{{$host}:143/imap/tls}INBOX";
                $imap2 = @imap_open($mailbox2, $username, $password, 0, 1);
                
                if ($imap2) {
                    $this->info('✅ Conexão sem SSL bem-sucedida!');
                    imap_close($imap2);
                    return 0;
                } else {
                    $this->error('❌ Também falhou sem SSL: ' . imap_last_error());
                    return 1;
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            return 1;
        }
    }
}