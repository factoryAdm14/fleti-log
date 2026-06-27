<?php

namespace App\Lib;

class FletiLegalPagesContent
{
    public const COMPANY = 'Fleti Log Ltda.';
    public const CNPJ = '50.228.256/0001-29';
    public const LGPD_EMAIL = 'privacidade@fleti.com.br';
    public const CONTACT_EMAIL = 'contato@fleti.com.br';
    public const VERSION = '1.0';
    public const UPDATED_AT = '26/06/2026';

    public static function keys(): array
    {
        return [
            'about_us',
            'privacy_policy',
            'terms_and_conditions',
            'refund_policy',
            'legal',
        ];
    }

    public static function shortDescription(string $key): string
    {
        return match ($key) {
            'about_us' => 'Conheça a Fleti Log, plataforma de mobilidade urbana e logística que conecta clientes, motoristas e entregadores.',
            'privacy_policy' => 'Como a Fleti Log coleta, utiliza, armazena e protege seus dados pessoais em conformidade com a LGPD.',
            'terms_and_conditions' => 'Regras gerais de uso da plataforma Fleti Log para clientes e usuários.',
            'refund_policy' => 'Regras de cancelamento, reembolso, estorno e prazos aplicáveis aos serviços da Fleti Log.',
            'legal' => 'Termos do motorista parceiro, aviso legal, cookies, pagamentos, segurança, antifraude e diretrizes da comunidade.',
            default => '',
        };
    }

    public static function html(string $key): string
    {
        return match ($key) {
            'about_us' => self::aboutUs(),
            'privacy_policy' => self::privacyPolicy(),
            'terms_and_conditions' => self::termsAndConditions(),
            'refund_policy' => self::refundPolicy(),
            'legal' => self::legal(),
            default => '',
        };
    }

    private static function footer(): string
    {
        return '<p class="fleti-legal-meta"><strong>Última atualização:</strong> ' . self::UPDATED_AT
            . ' &middot; <strong>Versão:</strong> ' . self::VERSION . '</p>';
    }

    private static function aboutUs(): string
    {
        return <<<HTML
<div class="fleti-legal-content">
<h2>Sobre a Fleti Log</h2>
<p>A <strong>{COMPANY}</strong>, inscrita no CNPJ sob nº <strong>{CNPJ}</strong>, é uma plataforma digital de mobilidade urbana e logística que conecta clientes, motoristas e entregadores em soluções modernas para corridas, entregas e serviços logísticos.</p>

<h3>Missão</h3>
<p>Oferecer uma experiência segura, prática e transparente de transporte e entregas, conectando pessoas e negócios com tecnologia de qualidade.</p>

<h3>Visão</h3>
<p>Ser referência em mobilidade e logística urbana no Brasil, com foco em confiança, eficiência e inovação.</p>

<h3>Valores</h3>
<ul>
<li>Segurança e respeito entre usuários</li>
<li>Transparência nas tarifas e pagamentos</li>
<li>Inovação contínua na plataforma</li>
<li>Compromisso com a privacidade e a LGPD</li>
<li>Excelência no atendimento</li>
</ul>

<h3>Como funciona</h3>
<ol>
<li><strong>O cliente solicita</strong> uma corrida ou entrega pelo aplicativo ou web.</li>
<li><strong>O motorista aceita</strong> a solicitação na região disponível.</li>
<li><strong>Acompanhamento em tempo real</strong> da rota e do status do serviço.</li>
<li><strong>Pagamento seguro</strong> via PIX, cartão ou dinheiro, conforme disponibilidade.</li>
<li><strong>Avaliação final</strong> para manter a qualidade da comunidade.</li>
</ol>

<h3>Para clientes</h3>
<p>Solicite corridas e entregas, acompanhe em tempo real, pague com PIX ou cartão e consulte seu histórico de pedidos com praticidade.</p>

<h3>Para motoristas</h3>
<p>Cadastre-se, escolha quando trabalhar, receba repasses da plataforma, solicite saques e, quando habilitado, assine planos mensais ou anuais.</p>

<h3>Delivery e empresas</h3>
<p>Oferecemos soluções para entregas pontuais, múltiplos pontos de entrega, rastreamento e demandas corporativas com painel administrativo e relatórios.</p>

<h3>Dados institucionais</h3>
<p><strong>Razão social:</strong> {COMPANY}<br>
<strong>CNPJ:</strong> {CNPJ}<br>
<strong>Contato:</strong> <a href="mailto:{CONTACT_EMAIL}">{CONTACT_EMAIL}</a></p>
{FOOTER}
</div>
HTML;
    }

    private static function privacyPolicy(): string
    {
        return <<<HTML
<div class="fleti-legal-content">
<h2>Política de Privacidade</h2>
<p>A <strong>{COMPANY}</strong>, inscrita no CNPJ sob nº <strong>{CNPJ}</strong>, valoriza a privacidade e a proteção dos dados pessoais de seus usuários, motoristas, entregadores, parceiros e clientes.</p>

<h3>1. Dados coletados</h3>
<p>Podemos coletar: nome, CPF/CNPJ, e-mail, telefone, foto de perfil, endereços, histórico de corridas e entregas, avaliações, dados de pagamento, identificadores de dispositivo, logs de acesso e dados de localização durante o uso da plataforma.</p>

<h3>2. Dados de localização</h3>
<p>A localização é utilizada para encontrar motoristas próximos, calcular rotas, acompanhar corridas e entregas em tempo real e aumentar a segurança dos usuários. O uso da localização é essencial para o funcionamento do serviço.</p>

<h3>3. Dados de pagamento</h3>
<p>Transações podem ser processadas por parceiros como Mercado Pago e Banco EFI. A Fleti Log não armazena dados completos de cartão quando o processamento é feito pelo gateway parceiro.</p>

<h3>4. Cookies e tecnologias similares</h3>
<p>Utilizamos cookies essenciais, de preferência, analíticos e, quando aplicável, de marketing para melhorar a experiência, lembrar preferências e medir desempenho. Consulte também nossa política de cookies na página jurídica.</p>

<h3>5. Finalidade do tratamento</h3>
<ul>
<li>Prestação dos serviços de mobilidade e logística</li>
<li>Cadastro, autenticação e suporte</li>
<li>Processamento de pagamentos e repasses</li>
<li>Prevenção a fraudes e segurança</li>
<li>Cumprimento de obrigações legais</li>
<li>Comunicações operacionais e, com consentimento, promocionais</li>
</ul>

<h3>6. Base legal (LGPD)</h3>
<p>O tratamento ocorre com fundamento em execução de contrato, cumprimento de obrigação legal, legítimo interesse, consentimento quando exigido e exercício regular de direitos.</p>

<h3>7. Compartilhamento</h3>
<p>Os dados podem ser compartilhados com motoristas/entregadores envolvidos no serviço, gateways de pagamento, provedores de nuvem, autoridades competentes e parceiros necessários à operação, sempre com medidas de segurança.</p>

<h3>8. Armazenamento e segurança</h3>
<p>Adotamos controles de acesso, criptografia quando aplicável, monitoramento, backups e políticas internas de segurança da informação para proteger os dados.</p>

<h3>9. Direitos do titular</h3>
<p>Você pode solicitar confirmação de tratamento, acesso, correção, anonimização, portabilidade, eliminação, informação sobre compartilhamento e revogação de consentimento, nos termos da LGPD.</p>

<h3>10. Exclusão de conta</h3>
<p>A exclusão pode ser solicitada pelo aplicativo ou pelo e-mail <a href="mailto:{LGPD_EMAIL}">{LGPD_EMAIL}</a>, observadas obrigações legais de retenção.</p>

<h3>11. Retenção</h3>
<p>Os dados são mantidos pelo tempo necessário à prestação do serviço, cumprimento legal, defesa de direitos e prevenção a fraudes.</p>

<h3>12. Encarregado LGPD</h3>
<p>Contato: <a href="mailto:{LGPD_EMAIL}">{LGPD_EMAIL}</a></p>

<h3>13. Alterações</h3>
<p>Esta política pode ser atualizada. A data da última versão será indicada no final do documento e, quando relevante, notificaremos os usuários.</p>
{FOOTER}
</div>
HTML;
    }

    private static function termsAndConditions(): string
    {
        return <<<HTML
<div class="fleti-legal-content">
<h2>Termos de Uso</h2>
<p>Ao utilizar a plataforma <strong>Fleti Log</strong>, operada pela <strong>{COMPANY}</strong> (CNPJ <strong>{CNPJ}</strong>), você concorda com estes Termos de Uso.</p>

<h3>1. Aceitação</h3>
<p>O cadastro e o uso dos serviços implicam aceite integral destes termos e da Política de Privacidade.</p>

<h3>2. Descrição da plataforma</h3>
<p>A Fleti Log é uma plataforma tecnológica que intermedia a conexão entre clientes e motoristas/entregadores independentes para corridas, entregas e serviços logísticos.</p>

<h3>3. Cadastro</h3>
<p>O usuário deve fornecer informações verdadeiras, manter seus dados atualizados e proteger suas credenciais de acesso.</p>

<h3>4. Responsabilidades do usuário</h3>
<ul>
<li>Utilizar a plataforma de forma lícita e respeitosa</li>
<li>Fornecer endereços e informações corretas</li>
<li>Efetuar pagamentos devidos pelos serviços solicitados</li>
<li>Não praticar condutas fraudulentas ou abusivas</li>
</ul>

<h3>5. Termos do cliente</h3>
<ul>
<li>Solicitar corridas e entregas apenas para fins lícitos</li>
<li>Informar corretamente coleta, destino e detalhes do pedido</li>
<li>Não transportar objetos proibidos por lei ou pelas regras da plataforma</li>
<li>Respeitar motoristas e entregadores</li>
<li>Avaliar o serviço de forma justa</li>
</ul>

<h3>6. Pagamentos</h3>
<p>Os valores, taxas, gorjetas e formas de pagamento disponíveis são exibidos antes ou durante a solicitação. Pagamentos podem ocorrer via PIX, cartão, dinheiro ou outros meios habilitados.</p>

<h3>7. Cancelamentos</h3>
<p>Cancelamentos podem gerar taxas conforme regras operacionais e política de cancelamento e reembolso.</p>

<h3>8. Condutas proibidas</h3>
<ul>
<li>Fraude, assédio, discriminação ou ameaça</li>
<li>Uso indevido de contas ou dados de terceiros</li>
<li>Manipulação de localização ou avaliações</li>
<li>Atividades ilegais ou que coloquem em risco terceiros</li>
</ul>

<h3>9. Suspensão e bloqueio</h3>
<p>A Fleti Log pode suspender ou encerrar contas em caso de violação destes termos, suspeita de fraude ou exigência legal.</p>

<h3>10. Propriedade intelectual</h3>
<p>Marcas, layout, software e conteúdos da plataforma pertencem à Fleti Log ou a seus licenciadores.</p>

<h3>11. Limitação de responsabilidade</h3>
<p>A Fleti Log atua como intermediadora tecnológica. A execução do transporte é de responsabilidade do motorista/entregador parceiro, dentro dos limites legais.</p>

<h3>12. Alterações</h3>
<p>Estes termos podem ser atualizados. O uso continuado após alterações constitui aceite da nova versão.</p>

<h3>13. Foro</h3>
<p>Fica eleito o foro da comarca da sede da empresa, salvo disposição legal em contrário aplicável ao consumidor.</p>
{FOOTER}
</div>
HTML;
    }

    private static function refundPolicy(): string
    {
        return <<<HTML
<div class="fleti-legal-content">
<h2>Política de Cancelamento e Reembolso</h2>
<p>Esta política descreve as regras de cancelamento e reembolso dos serviços prestados por meio da plataforma <strong>Fleti Log</strong>, operada pela <strong>{COMPANY}</strong> (CNPJ <strong>{CNPJ}</strong>).</p>

<h3>1. Cancelamento pelo cliente</h3>
<p>O cliente pode cancelar uma solicitação antes ou durante o atendimento, conforme status exibido no aplicativo. Cancelamentos tardios ou após início do deslocamento podem gerar cobrança parcial ou integral da taxa aplicável.</p>

<h3>2. Cancelamento pelo motorista</h3>
<p>O motorista pode recusar ou cancelar solicitações conforme regras da plataforma. Cancelamentos indevidos ou recorrentes podem resultar em penalidades ou suspensão.</p>

<h3>3. Cancelamento automático</h3>
<p>Solicitações podem ser canceladas automaticamente por tempo de espera, indisponibilidade de motorista, falha de pagamento ou inconsistência cadastral.</p>

<h3>4. Reembolso via PIX</h3>
<p>Quando aplicável, reembolsos por PIX serão processados para a mesma titularidade ou chave utilizada, dentro dos prazos operacionais e bancários.</p>

<h3>5. Estorno via cartão</h3>
<p>Estornos em cartão seguem prazos das bandeiras e do gateway de pagamento (ex.: Mercado Pago). O crédito pode aparecer em faturas futuras.</p>

<h3>6. Taxas de cancelamento</h3>
<p>Taxas podem ser cobradas para compensar deslocamento, tempo de espera ou custos operacionais, conforme exibido na plataforma no momento do cancelamento.</p>

<h3>7. Prazos</h3>
<p>Reembolsos e estornos são iniciados após análise do caso e confirmação do direito ao reembolso. Prazos finais dependem da instituição financeira.</p>

<h3>8. Casos sem reembolso</h3>
<ul>
<li>Serviço já concluído sem falha comprovada</li>
<li>Cancelamento por violação dos termos</li>
<li>Uso indevido ou fraude</li>
<li>Informações incorretas fornecidas pelo solicitante</li>
</ul>

<h3>9. Suporte</h3>
<p>Dúvidas: <a href="mailto:{CONTACT_EMAIL}">{CONTACT_EMAIL}</a></p>
{FOOTER}
</div>
HTML;
    }

    private static function legal(): string
    {
        return <<<HTML
<div class="fleti-legal-content">
<h2>Documentos Jurídicos Complementares</h2>
<p>Documentos aplicáveis à operação da <strong>{COMPANY}</strong> (CNPJ <strong>{CNPJ}</strong>) na plataforma Fleti Log.</p>

<h2>Termos do Motorista Parceiro</h2>
<h3>Cadastro e documentos</h3>
<p>O motorista deve possuir cadastro válido, CNH em dia, CRLV do veículo, documentos exigidos pela plataforma e passar por validação cadastral e facial, quando habilitada.</p>
<h3>Prestação do serviço</h3>
<p>O motorista é responsável pela condução segura, cumprimento das leis de trânsito, cordialidade e qualidade do atendimento em corridas e entregas.</p>
<h3>Repasses, comissões e planos</h3>
<p>Repasses, comissões administrativas, split de pagamento, saques e planos mensais/anuais seguem as regras financeiras exibidas no painel do motorista e nas configurações vigentes.</p>
<h3>Bloqueios e avaliações</h3>
<p>Notas baixas, cancelamentos excessivos, fraude ou conduta inadequada podem resultar em bloqueio temporário ou definitivo.</p>

<h2>Aviso Legal</h2>
<p>A Fleti Log é operadora de plataforma digital de intermediação. Não substitui obrigações legais de motoristas, clientes e terceiros envolvidos na prestação do serviço de transporte.</p>

<h2>Política de Cookies</h2>
<p>Utilizamos cookies essenciais (login e segurança), de preferência (idioma e configurações), analíticos (desempenho) e, quando autorizado, de marketing. A desativação de cookies essenciais pode impedir o funcionamento de partes da plataforma.</p>

<h2>Política de Pagamentos</h2>
<ul>
<li>PIX, cartão e dinheiro, conforme disponibilidade na região</li>
<li>Integração com Mercado Pago e Banco EFI</li>
<li>Split de pagamento e comissão administrativa</li>
<li>Repasse ao motorista e solicitação de saque</li>
<li>Planos mensais e anuais para motoristas, quando habilitados</li>
<li>Medidas de segurança nas transações</li>
</ul>

<h2>Política Antifraude</h2>
<p>Monitoramos transações, validamos documentos e identidade, bloqueamos contas suspeitas e analisamos indícios de fraude em PIX, cartão e localização. Casos podem ser revisados manualmente pela administração.</p>

<h2>Política de Segurança da Informação</h2>
<p>Adotamos criptografia, controle de acesso, logs de auditoria, backup e monitoramento. Usuários devem proteger senhas e notificar acessos indevidos.</p>

<h2>Política de Comunidade</h2>
<p>Exigimos respeito mútuo, proibimos discriminação, ameaças e fraude. Penalidades incluem advertência, suspensão e exclusão. Denúncias: <a href="mailto:{CONTACT_EMAIL}">{CONTACT_EMAIL}</a></p>

<h2>Consentimento LGPD</h2>
<p>No cadastro, o usuário deve aceitar Termos de Uso e Política de Privacidade, autorizar localização para funcionamento da plataforma e pode optar por comunicações promocionais.</p>
{FOOTER}
</div>
HTML;
    }

    private static function interpolate(string $html): string
    {
        return str_replace(
            ['{COMPANY}', '{CNPJ}', '{LGPD_EMAIL}', '{CONTACT_EMAIL}', '{FOOTER}'],
            [self::COMPANY, self::CNPJ, self::LGPD_EMAIL, self::CONTACT_EMAIL, self::footer()],
            $html
        );
    }

    public static function render(string $key): string
    {
        return self::interpolate(self::html($key));
    }

    public static function renderShort(string $key): string
    {
        return self::shortDescription($key);
    }
}
