<?php
// app/Console/Commands/TestarConexaoImap.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestarConexaoImap extends Command
{
    protected $signature = 'email:testar-conexao';
    protected $description = 'Testar conexão IMAP com o servidor de email';

    public function handle()
    {
        $this->info('🧪 Testando conexão IMAP...');

        // Verificar se a biblioteca está disponível
        if (!class_exists('PhpImap\Mailbox')) {
            $this->error('❌ Biblioteca PHP IMAP não encontrada');
            $this->line('Execute: composer require php-imap/php-imap');
            return 1;
        }

        $host = env('MAIL_HOST');
        $port = env('MAIL_PORT', 993);
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');

        $this->line("Host: {$host}");
        $this->line("Porta: {$port}");
        $this->line("Usuário: {$username}");
        $this->line("Senha: " . ($password ? '***' : 'NÃO CONFIGURADA'));

        if (!$host || !$username || !$password) {
            $this->error('❌ Configuração de email incompleta no .env');
            $this->line('Verifique: MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD');
            return 1;
        }

        try {
            // Criar diretório de anexos
            $attachmentPath = storage_path('app/email_attachments');
            if (!is_dir($attachmentPath)) {
                if (!mkdir($attachmentPath, 0755, true)) {
                    throw new \Exception("Não foi possível criar o diretório: {$attachmentPath}");
                }
                $this->info("✅ Diretório criado: {$attachmentPath}");
            }

            // Testar diferentes configurações de conexão
            $configuracoes = [
                [
                    'string' => "{{$host}:{$port}/imap/ssl}INBOX",
                    'desc' => "SSL (Porta {$port})"
                ],
                [
                    'string' => "{{$host}:{$port}/imap/tls}INBOX",
                    'desc' => "TLS (Porta {$port})"
                ],
                [
                    'string' => "{{$host}:{$port}/imap/novalidate-cert}INBOX",
                    'desc' => "Sem validação de certificado"
                ],
            ];

            $conectado = false;

            foreach ($configuracoes as $config) {
                $this->line("");
                $this->info("Tentando: {$config['desc']}");
                $this->line("String: {$config['string']}");

                try {
                    $mailbox = new \PhpImap\Mailbox(
                        $config['string'],
                        $username,
                        $password,
                        $attachmentPath,
                        'UTF-8'
                    );

                    // Definir timeout menor para teste
                    $mailbox->setConnectionArgs(
                        CL_EXPUNGE, // flags
                        [], // options
                        30  // timeout em segundos
                    );

                    // Tentar conectar
                    $this->line("Conectando...");
                    $mailbox->checkMailbox();
                    
                    $this->info("✅ Conexão bem-sucedida com: {$config['desc']}");
                    
                    // Contar emails
                    $mailsIds = $mailbox->searchMailbox('ALL');
                    $this->info("Total de emails na caixa: " . count($mailsIds));

                    $unread = $mailbox->searchMailbox('UNSEEN');
                    $this->info("Emails não lidos: " . count($unread));

                    // Listar algumas pastas disponíveis
                    $this->line("Pastas disponíveis:");
                    $folders = $mailbox->getMailboxes('*');
                    foreach ($folders as $folder) {
                        $this->line(" - {$folder->name}");
                    }

                    $mailbox->disconnect();
                    $conectado = true;
                    break;

                } catch (\Exception $e) {
                    $this->warn("❌ Falha com {$config['desc']}: " . $e->getMessage());
                    continue;
                }
            }

            if (!$conectado) {
                $this->error('❌ Não foi possível conectar com nenhuma configuração');
                $this->mostrarSolucoes($host);
                return 1;
            }

            $this->info('✅ Teste de conexão concluído com sucesso!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro geral: ' . $e->getMessage());
            $this->line('Arquivo: ' . $e->getFile() . ':' . $e->getLine());
            $this->mostrarSolucoes($host);
            return 1;
        }
    }

    private function mostrarSolucoes(string $host): void
    {
        $this->line('');
        $this->info('💡 SOLUÇÕES RECOMENDADAS:');
        
        if (strpos($host, 'gmail') !== false) {
            $this->line('📧 PARA GMAIL:');
            $this->line('1. Ative "Acesso a app menos seguro": https://myaccount.google.com/lesssecureapps');
            $this->line('2. Ou crie uma "Senha de app": https://myaccount.google.com/apppasswords');
            $this->line('3. Use a senha de app no .env (não a senha normal)');
            $this->line('4. Verifique se a verificação em 2 etapas está ativa');
        } elseif (strpos($host, 'outlook') !== false || strpos($host, 'office365') !== false) {
            $this->line('📧 PARA OUTLOOK/OFFICE 365:');
            $this->line('1. Use a senha normal da conta');
            $this->line('2. Verifique se o acesso IMAP está ativo nas configurações');
            $this->line('3. Para contas corporativas, pode precisar de autenticação especial');
        } else {
            $this->line('📧 CONFIGURAÇÃO GERAL:');
            $this->line('1. Verifique host, porta, usuário e senha');
            $this->line('2. Teste com cliente de email (Outlook, Thunderbird)');
            $this->line('3. Verifique firewall e bloqueios de rede');
            $this->line('4. Contate seu provedor de email para configurações IMAP');
        }

        $this->line('');
        $this->info('🔧 CONFIGURAÇÕES .ENV COMUNS:');
        
        $this->line('GMAIL:');
        $this->line('MAIL_HOST=smtp.gmail.com');
        $this->line('MAIL_PORT=587');
        $this->line('MAIL_USERNAME=seuemail@gmail.com');
        $this->line('MAIL_PASSWORD=sua_senha_de_app');
        $this->line('MAIL_ENCRYPTION=tls');
        
        $this->line('');
        $this->line('OUTLOOK:');
        $this->line('MAIL_HOST=smtp.office365.com');
        $this->line('MAIL_PORT=587');
        $this->line('MAIL_USERNAME=seuemail@outlook.com');
        $this->line('MAIL_PASSWORD=sua_senha');
        $this->line('MAIL_ENCRYPTION=tls');
        
        $this->line('');
        $this->line('HOTMAIL:');
        $this->line('MAIL_HOST=smtp.live.com');
        $this->line('MAIL_PORT=587');
        $this->line('MAIL_USERNAME=seuemail@hotmail.com');
        $this->line('MAIL_PASSWORD=sua_senha');
        $this->line('MAIL_ENCRYPTION=tls');
    }
}