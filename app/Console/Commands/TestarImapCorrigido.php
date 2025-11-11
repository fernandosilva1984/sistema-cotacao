<?php
// app/Console/Commands/TestarImapCorrigido.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestarImapCorrigido extends Command
{
    protected $signature = 'email:testar-imap';
    protected $description = 'Teste IMAP corrigido para Titan Email';

    public function handle()
    {
        $this->info('🧪 Teste IMAP para Titan Email');

        if (!function_exists('imap_open')) {
            $this->error('❌ Extensão IMAP do PHP não está instalada');
            return 1;
        }

        $host = env('IMAP_HOST', 'imap.titan.email');
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');

        $this->line("Host: {$host}");
        $this->line("Usuário: {$username}");
        $this->line("Senha: " . ($password ? '***' : 'NÃO CONFIGURADA'));

        if (!$username || !$password) {
            $this->error('❌ Credenciais não configuradas no .env');
            return 1;
        }

        // Configurações específicas para Titan Email
        $configuracoes = [
            [
                'mailbox' => "{{$host}:993/imap/ssl}INBOX",
                'desc' => 'Titan Email - SSL (993)'
            ],
            [
                'mailbox' => "{{$host}:143/imap/tls}INBOX",
                'desc' => 'Titan Email - TLS (143)'
            ],
            [
                'mailbox' => "{{$host}:993/imap/ssl/novalidate-cert}INBOX",
                'desc' => 'Titan Email - SSL sem validação'
            ],
            [
                'mailbox' => "{{$host}:143/imap/tls/novalidate-cert}INBOX",
                'desc' => 'Titan Email - TLS sem validação'
            ],
        ];

        foreach ($configuracoes as $config) {
            $this->line("");
            $this->info("Tentando: {$config['desc']}");
            $this->line("Mailbox: {$config['mailbox']}");

            // Limpar erros anteriores
            imap_errors();
            
            $imap = @imap_open(
                $config['mailbox'], 
                $username, 
                $password,
                OP_READONLY, // Modo somente leitura
                1 // Número de tentativas
            );

            if ($imap) {
                $this->info("✅ CONEXÃO BEM-SUCEDIDA: {$config['desc']}");
                
                // Obter informações
                $info = imap_check($imap);
                $this->line("📬 Total de emails: " . $info->Nmsgs);
                $this->line("📁 Caixa: " . $info->Mailbox);
                
                // Contar não lidos
                $unread = imap_search($imap, 'UNSEEN');
                $this->line("📨 Emails não lidos: " . ($unread ? count($unread) : 0));
                
                imap_close($imap);
                
                $this->line("");
                $this->info('🎉 IMAP configurado com sucesso!');
                $this->line('Agora você pode usar o processamento automático de respostas.');
                
                return 0;
            } else {
                $errors = imap_errors();
                $last_error = imap_last_error();
                
                $this->warn("❌ Falhou: {$config['desc']}");
                if ($last_error) {
                    $this->line("Erro: " . $last_error);
                }
                if ($errors) {
                    foreach ($errors as $error) {
                        $this->line("Detalhe: " . $error);
                    }
                }
            }
        }

        $this->error('❌ Não foi possível conectar com nenhuma configuração');
        $this->mostrarConfiguracoesTitan();
        return 1;
    }

    private function mostrarConfiguracoesTitan(): void
    {
        $this->line("");
        $this->info('🔧 CONFIGURAÇÕES TITAN EMAIL:');
        $this->line("");
        $this->line('📧 CONFIGURAÇÃO DE ENVIO (SMTP):');
        $this->line('MAIL_MAILER=smtp');
        $this->line('MAIL_HOST=smtp.titan.email');
        $this->line('MAIL_PORT=587');
        $this->line('MAIL_USERNAME=seuemail@seudominio.com');
        $this->line('MAIL_PASSWORD=sua_senha');
        $this->line('MAIL_ENCRYPTION=tls');
        $this->line("");
        $this->line('📨 CONFIGURAÇÃO DE RECEBIMENTO (IMAP):');
        $this->line('Para IMAP automático, use estas configurações:');
        $this->line('Servidor: imap.titan.email');
        $this->line('Porta: 993 (SSL) ou 143 (TLS)');
        $this->line('criptografia: SSL/TLS');
        $this->line("");
        $this->line('💡 SOLUÇÃO ALTERNATIVA:');
        $this->line('Use o processamento MANUAL - é mais confiável!');
        $this->line('1. Acesse "Processar Respostas" no menu do sistema');
        $this->line('2. Cole as respostas dos fornecedores manualmente');
        $this->line('3. O sistema funciona perfeitamente sem IMAP automático');
    }
}