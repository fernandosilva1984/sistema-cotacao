<?php
// app/Console/Commands/TestarEmailBasico.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestarEmailBasico extends Command
{
    protected $signature = 'email:testar-basico';
    protected $description = 'Teste básico de configuração de email';

    public function handle()
    {
        $this->info('🧪 Teste Básico de Configuração de Email');
        $this->line('');

        // Mostrar configurações atuais
        $this->info('📋 CONFIGURAÇÕES ATUAIS:');
        $this->line("MAIL_MAILER: " . env('MAIL_MAILER'));
        $this->line("MAIL_HOST: " . env('MAIL_HOST'));
        $this->line("MAIL_PORT: " . env('MAIL_PORT'));
        $this->line("MAIL_USERNAME: " . env('MAIL_USERNAME'));
        $this->line("MAIL_PASSWORD: " . (env('MAIL_PASSWORD') ? '***' : 'NÃO CONFIGURADO'));
        $this->line("MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION'));
        $this->line("MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS'));
        $this->line("MAIL_FROM_NAME: " . env('MAIL_FROM_NAME'));

        $this->line('');
        $this->info('🔍 VERIFICANDO PRÉ-REQUISITOS:');

        // Verificar extensões
        $extensoes = ['openssl', 'pdo', 'mbstring', 'xml', 'ctype', 'json'];
        foreach ($extensoes as $ext) {
            if (extension_loaded($ext)) {
                $this->info("✅ {$ext}");
            } else {
                $this->error("❌ {$ext}");
            }
        }

        // Verificar se podemos enviar email
        $this->line('');
        $this->info('📤 TESTANDO ENVIO DE EMAIL...');

        try {
            $testEmail = env('MAIL_USERNAME');
            
            if (!$testEmail) {
                $this->error('❌ MAIL_USERNAME não configurado');
                return 1;
            }

            $this->line("Enviando teste para: {$testEmail}");

            Mail::raw('Este é um email de teste do Sistema de Cotações.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('✅ Teste de Email - Sistema Cotações')
                        ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            $this->info('✅ Email de teste ENVIADO com sucesso!');
            $this->line('Verifique sua caixa de entrada (e spam)');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao enviar email: ' . $e->getMessage());
            $this->diagnosticarErro($e);
            return 1;
        }
    }

    private function diagnosticarErro(\Exception $e): void
    {
        $this->line('');
        $this->info('🔧 DIAGNÓSTICO DO ERRO:');

        $mensagem = $e->getMessage();

        if (strpos($mensagem, 'Connection refused') !== false) {
            $this->line('❌ Conexão recusada - verifique host e porta');
        } elseif (strpos($mensagem, 'Authentication failed') !== false) {
            $this->line('❌ Autenticação falhou - verifique usuário e senha');
            $this->line('   Para Gmail: use senha de app, não senha normal');
        } elseif (strpos($mensagem, 'SSL') !== false || strpos($mensagem, 'TLS') !== false) {
            $this->line('❌ Problema de SSL/TLS - verifique MAIL_ENCRYPTION');
        } elseif (strpos($mensagem, 'timed out') !== false) {
            $this->line('❌ Timeout - verifique firewall ou rede');
        } else {
            $this->line("❌ Erro específico: {$mensagem}");
        }

        $this->line('');
        $this->info('💡 AÇÃO RECOMENDADA:');
        $this->line('1. Use a solução manual de processamento por enquanto');
        $this->line('2. Configure o email corretamente no .env');
        $this->line('3. Teste novamente com este comando');
    }
}